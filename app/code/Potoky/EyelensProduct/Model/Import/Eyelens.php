<?php


namespace Potoky\EyelensProduct\Model\Import;

use Magento\Framework\App\ResourceConnection;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportExport;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Magento\Eav\Model\Config;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\Framework\Stdlib\StringUtils;
use Exception;
use Magento\ImportExport\Model\Import;
use Magento\Framework\DB\Adapter\AdapterInterface;

class Eyelens extends AbstractEntity
{
    const ENTITY_CODE = 'eyelens';
    const ERROR_CODE_REQUIRED_ATTRIBUTE_MISSING = 'requiredAttributeMissing';
    const ERROR_CODE_INCORRECT_CUSTOM_OPTIONS = 'incorrectCustomOptions';

    /**
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     *
     */
    private $preliminaryImportData = [];

    /**
     *
     */
    private $finalImportData = [];

    /**
     * Valid column names
     *
     * @array
     */
    protected $validColumnNames = ['sku', 'name', 'custom_options', 'price', 'brand'];

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * If we should check column names
     *
     * @var bool
     */
    protected $needColumnCheck = true;

    /**
     * Permanent entity columns.
     *
     * @var string[]
     */
    protected $_permanentAttributes = ['sku', 'name', 'price'];

    /**
     * Eyelens constructor.
     * @param JsonHelper $jsonHelper
     * @param ImportExport $importExportData
     * @param ImportData $importData
     * @param Config $config
     * @param ResourceConnection $resource
     * @param ResourceHelper $resourceHelper
     * @param StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        JsonHelper $jsonHelper,
        ImportExport $importExportData,
        ImportData $importData,
        ResourceConnection $resource,
        ResourceHelper $resourceHelper,
        ProcessingErrorAggregatorInterface $errorAggregator,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productReposirory = $productRepository;
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator = $errorAggregator;
        $this->errorMessageTemplates[self::ERROR_CODE_REQUIRED_ATTRIBUTE_MISSING] = 'Missing required value for field: %s.';
        $this->errorMessageTemplates[self::ERROR_CODE_INCORRECT_CUSTOM_OPTIONS] = 'Custom Options are built incorrectly.';

        $this->initMessageTemplates();
    }

    /**
     *
     *
     */
    private function initMessageTemplates()
    {
        $this->addMessageTemplate(
            self::ERROR_CODE_INVALID_ATTRIBUTE,
            __($this->errorMessageTemplates[self::ERROR_CODE_INVALID_ATTRIBUTE])
        );
        $this->addMessageTemplate(
            self::ERROR_CODE_COLUMN_EMPTY_HEADER,
            __($this->errorMessageTemplates[self::ERROR_CODE_COLUMN_EMPTY_HEADER])
        );
        $this->addMessageTemplate(
            self::ERROR_CODE_COLUMN_NAME_INVALID,
            __($this->errorMessageTemplates[self::ERROR_CODE_COLUMN_NAME_INVALID])
        );
        $this->addMessageTemplate(
            self::ERROR_CODE_COLUMN_NOT_FOUND,
            __($this->errorMessageTemplates[self::ERROR_CODE_COLUMN_NOT_FOUND])
        );
        $this->addMessageTemplate(
            self::ERROR_CODE_REQUIRED_ATTRIBUTE_MISSING,
            __($this->errorMessageTemplates[self::ERROR_CODE_REQUIRED_ATTRIBUTE_MISSING])
        );
        $this->addMessageTemplate(
            self::ERROR_CODE_INCORRECT_CUSTOM_OPTIONS,
            __($this->errorMessageTemplates[self::ERROR_CODE_INCORRECT_CUSTOM_OPTIONS])
        );
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return static::ENTITY_CODE;
    }

    /**
     * Row validation
     *
     * @param array $rowData
     * @param int $rowNum
     *
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum)
    {
        foreach ($this->_permanentAttributes as $permanentAttribute) {
            if (!$rowData[$permanentAttribute]) {
                $this->addRowError(
                    self::ERROR_CODE_REQUIRED_ATTRIBUTE_MISSING,
                    $rowNum,
                    $permanentAttribute
                );
                $this->_validatedRows[$rowNum] = false;
            } else {
                $this->preliminaryImportData[$rowNum][$permanentAttribute] = $rowData[$permanentAttribute];
            }
        }

        $this->preliminaryImportData[$rowNum]['custom_options'] = $this->validateCustomOptions($rowData['custom_options'], $rowNum);

        if (!$this->preliminaryImportData[$rowNum]['custom_options']) {
            $this->_validatedRows[$rowNum] = false;
        }

        if (isset($this->_validatedRows[$rowNum]) && $this->_validatedRows[$rowNum] === false) {
            unset($this->preliminaryImportData[$rowNum]);
        } else {
            $this->preliminaryImportData[$rowNum]['brand'] = $rowData['brand'];
            $this->_validatedRows[$rowNum] = true;
        }

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    private function validateCustomOptions($customOptions, $rowNum)
    {
        $customOptions = explode(PHP_EOL, $customOptions);
        $pattern = '#^[*]?[^:\n]+:[^:\n]*$#';
        $refinedOptions = [];
        foreach ($customOptions as $customOption) {
            $customOption =trim($customOption);
            if (preg_match($pattern, $customOption)) {
                $keyValue = explode(':', $customOption);
                $refinedOptions[$keyValue[0]] = ltrim($keyValue[1]);
            } else {
                $this->addRowError(
                    self::ERROR_CODE_INCORRECT_CUSTOM_OPTIONS,
                    $rowNum
                );

                return false;
            }
        }

        return $refinedOptions;
    }

    public function validateData()
    {
        $parent =  parent::validateData();
        $this->compressPreliminaryImport();

        return $parent;
    }

    private function compressPreliminaryImport()
    {
        $skus = array_column($this->preliminaryImportData, 'sku');
        $unique = array_unique($skus);

        $preliminaryReindexed = array_values($this->preliminaryImportData);
        $processedSkusArr = [];
        foreach ($preliminaryReindexed as $num => $val) {
            if (!in_array($val['sku'], $processedSkusArr)) {
                $this->finalImportData[] = $val;
                $processedSkusArr[$val['sku']] = $num;
            } else {
                $this->mergeRecords($val, $processedSkusArr[$val['sku']]);

            }
        }

    }

    private function mergeRecords($currentRecord, $mergeRecordNumber)
    {
        $mergeRecordData = $this->finalImportData[$mergeRecordNumber];
        if ($this->finalImportData[$mergeRecordNumber]['name'] != $currentRecord['name'] ||
            $this->finalImportData[$mergeRecordNumber]['price'] != $currentRecord['price']) {

            return false;
        }
        $mergedCustomOptions = $this->mergeCustomOptions();

        if ($mergedCustomOptions) {
            $this->finalImportData[$mergeRecordNumber]['custom_options'] = $mergedCustomOptions;
            $this->finalImportData[$mergeRecordNumber]['brand'] += $currentRecord['brand'];
        }

        return true;
    }

    /**
     * Import data
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function _importData(): bool
    {

        return true;
    }
}

<?php


namespace Potoky\EyelensProduct\Model\Import;

use Magento\Framework\App\ResourceConnection;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
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
    const ERROR_CODE_INCORRECT_VALUE_DISCORDANCE = 'valueDiscordance';

    /**
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     *
     */
    private $preparedCustomOptionsInfo = [];

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
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory
    ) {
        $this->productReposirory = $productRepository;
        $this->productFactory = $productFactory;
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator = $errorAggregator;
        $this->errorMessageTemplates[self::ERROR_CODE_REQUIRED_ATTRIBUTE_MISSING] = 'Missing required value for field: %s.';
        $this->errorMessageTemplates[self::ERROR_CODE_INCORRECT_CUSTOM_OPTIONS] = 'Custom Options are built incorrectly.';
        $this->errorMessageTemplates[self::ERROR_CODE_INCORRECT_VALUE_DISCORDANCE] = 'Can\'t megre products with sku of %s. Name or Price value differs from one product to another.';

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
        $this->addMessageTemplate(
            self::ERROR_CODE_INCORRECT_VALUE_DISCORDANCE,
            __($this->errorMessageTemplates[self::ERROR_CODE_INCORRECT_VALUE_DISCORDANCE])
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
            }
        }

        if (isset($rowData['custom_options'])) {
            $preparedCustomOptions = $this->validateAndPrepareCustomOptions($rowData['custom_options'], $rowNum);
            if ($preparedCustomOptions === false) {
                $this->_validatedRows[$rowNum] = false;
            } else {
                $this->preparedCustomOptionsInfo = [$rowNum => $preparedCustomOptions];
            }
        }

        $this->_validatedRows[$rowNum] = true;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    private function validateAndPrepareCustomOptions($customOptions, $rowNum)
    {
        if ($customOptions === 0 || $customOptions === null) {

            return [];
        }
        $customOptions = explode(PHP_EOL, $customOptions);
        $pattern = '#^[*]?[^:\n]+:[^:\n]*$#';
        $refinedOptions = [];
        foreach ($customOptions as $customOption) {
            $customOption =trim($customOption);
            if (preg_match($pattern, $customOption)) {
                $keyValue = explode(':', $customOption);
                $key = $keyValue[0];
                $value = trim($keyValue[1]);
                if (strpos($key, '*') === 0) {
                    $key = substr($key, 1);
                    $refinedOptions[$key]['isRequired'] = true;
                } else {
                    $refinedOptions[$key]['isRequired'] = false;
                }
                $refinedOptions[$key]['values'][] = $value;
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

    private function compressPreliminaryImportData()
    {
        $preliminaryImportData = array_values($this->_dataSourceModel->getNextBunch());
        $processedSkusArr = [];
        $corruptedSkus = [];
        foreach ($preliminaryImportData as $num => $val) {
            if (in_array($val['sku'], $corruptedSkus)) {
                continue;
            }

            if (!in_array($val['sku'], $processedSkusArr)) {
                $this->finalImportData[] = $val;
                $processedSkusArr[$val['sku']] = $num;
            } else if (!$this->mergeRecords($val, $processedSkusArr[$val['sku']])) {
                $this->addRowError(
                    self::ERROR_CODE_INCORRECT_VALUE_DISCORDANCE,
                    $processedSkusArr[$val['sku']],
                    $val['sku']
                );
            }
        }
    }

    private function mergeRecords($currentRecord, $mergeRecordNumber)
    {
        if ($this->finalImportData[$mergeRecordNumber] === $currentRecord) {
            $this->finalImportData[$mergeRecordNumber] = $currentRecord;

            return true;
        }
        if ($this->finalImportData[$mergeRecordNumber]['name'] != $currentRecord['name'] ||
            $this->finalImportData[$mergeRecordNumber]['price'] != $currentRecord['price']) {

            return false;
        }

        $this->finalImportData[$mergeRecordNumber]['custom_options'] = $this->mergeCustomOptions(
            $this->finalImportData[$mergeRecordNumber]['custom_options'],
            $currentRecord['custom_options']
        );
        $this->finalImportData[$mergeRecordNumber]['brand'] .= $currentRecord['brand'];

        return true;
    }

    private function mergeCustomOptions($options1, $options2)
    {
        $toggleKeyStringiness = function($key, $toString)
        {
            if ($toString === true) {
                $key = '_' . $key;

                return $key;
            }

            if ($toString === false) {
                $key = substr($key, 1);

                return $key;
            }

            return false;
        };

        $this->stringifyOptionKeys(true, $toggleKeyStringiness,$options1, $options2);
        $mergeArray = array_merge_recursive(
            $options1,
            $options2
        );

        foreach ($mergeArray as &$finalOption) {
            $finalOption['isRequired'] = (bool) ($finalOption['isRequired'][0] + $finalOption['isRequired'][1]);
            $finalOption['values'] = array_unique($finalOption['values']);
        }

        unset($finalOption);
        $this->stringifyOptionKeys(false, $toggleKeyStringiness, $mergeArray);

        return $mergeArray;
    }

    private function stringifyOptionKeys($toString, $callback, &...$mergingOptionsMultiple)
    {
        foreach ($mergingOptionsMultiple as &$mergingOptionsSingle) {
            foreach ($mergingOptionsSingle as $key => $value) {
                unset($mergingOptionsSingle[$key]);
                $key = $callback($key, $toString);
                $mergingOptionsSingle[$key] = $value;
            }
        }
        unset($mergingOptionsSingle);
    }

    protected function _prepareRowForDb(array $rowData)
    {
        $rowData = parent::_prepareRowForDb($rowData);
        $key = $this->_getSource()->key();

        if (isset($this->preparedCustomOptionsInfo[$key])) {
            $rowData['custom_options'] = $this->preparedCustomOptionsInfo[$key];
        }

        return $rowData;
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
        $this->compressPreliminaryImportData();

        return true;
    }
}

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
            }
        }


        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }

        $this->_validatedRows[$rowNum] = true;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
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

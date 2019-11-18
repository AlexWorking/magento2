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

    public function __construct(
        JsonHelper $jsonHelper,
        ImportExport $importExportData,
        ImportData $importData,
        Config $config,
        ResourceConnection $resource,
        ResourceHelper $resourceHelper,
        StringUtils $string,
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

        $this->initMessageTemplates();
    }

    /**
     *
     *
     */
    private function initMessageTemplates()
    {
        $this->addMessageTemplate(
            'invalidAttributeName',
            __('Some fields have incorrect names')
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

    public function validateRow(array $rowData, $rowNum)
    {
        // TODO: Implement validateRow() method.
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
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->deleteEntity();
                break;
            case Import::BEHAVIOR_REPLACE:
                $this->saveAndReplaceEntity();
                break;
            case Import::BEHAVIOR_APPEND:
                $this->saveAndReplaceEntity();
                break;
        }

        return true;
    }

    protected function deleteEntity()
    {
    }

    protected function saveAndReplaceEntity()
    {
    }
}

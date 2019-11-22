<?php


namespace Potoky\EyelensProduct\Model\Import;

use Magento\Framework\App\ResourceConnection;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
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
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
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
     *
     * @var ProductFactory
     */
    private $productFactory;

    /**
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     *
     * @var Config
     */
    private $eavConfig;

    /**
     *
     * @var OptionFactory
     */
    private $productOptionFactory;

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
        ProductFactory $productFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OptionFactory $optionFactory,
        StoreManagerInterface $storeManager,
        Config $eavConfig
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator = $errorAggregator;
        $this->productOptionFactory = $optionFactory;
        $this->storeManager = $storeManager;
        $this->eavConfig = $eavConfig;

        $this->errorMessageTemplates[self::ERROR_CODE_REQUIRED_ATTRIBUTE_MISSING] = 'Missing required value for field: %s.';
        $this->errorMessageTemplates[self::ERROR_CODE_INCORRECT_CUSTOM_OPTIONS] = 'Custom Options are built incorrectly.';
        $this->errorMessageTemplates[self::ERROR_CODE_INCORRECT_VALUE_DISCORDANCE] = 'Can\'t megre products with sku of %s. Name, Price or Brand values differ from one product to another.';

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
                $refinedOptions[$key]['title'] = $key;
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

            if (!array_key_exists($val['sku'], $processedSkusArr)) {
                $this->finalImportData[$val['sku']] = $val;
                $processedSkusArr[$val['sku']] = $num;
            } else if (!$this->mergeRecords($val, $val['sku'])) {
                $this->addRowError(
                    self::ERROR_CODE_INCORRECT_VALUE_DISCORDANCE,
                    $processedSkusArr[$val['sku']],
                    $val['sku']
                );
                $corruptedSkus[] = $val['sku'];
                unset($this->finalImportData[$val['sku']]);
            }
        }

    }

    private function mergeRecords($currentRecord, $meringSku)
    {
        if ($this->finalImportData[$meringSku] === $currentRecord) {
            $this->finalImportData[$meringSku] = $currentRecord;

            return true;
        }

        if ($this->finalImportData[$meringSku]['name'] == '' || $currentRecord['name'] == '') {
            $this->finalImportData[$meringSku]['name'] .= $currentRecord['name'];
        } elseif ($this->finalImportData[$meringSku]['name'] != $currentRecord['name']) {
            return false;
        }

        if ($this->finalImportData[$meringSku]['price'] == '' || $currentRecord['price'] == '') {
            $this->finalImportData[$meringSku]['price'] .= $currentRecord['price'];
        } elseif ($this->finalImportData[$meringSku]['price'] != $currentRecord['price']) {
            return false;
        }

        $this->finalImportData[$meringSku]['custom_options'] = $this->mergeCustomOptions(
            $this->finalImportData[$meringSku]['custom_options'],
            $currentRecord['custom_options']
        );

        if ($this->finalImportData[$meringSku]['brand'] == '' || $currentRecord['brand'] == '') {
            $this->finalImportData[$meringSku]['brand'] .= $currentRecord['brand'];
        } elseif ($this->finalImportData[$meringSku]['brand'] != $currentRecord['brand']) {
            return false;
        }


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
            if (is_array($finalOption['title'])) {
                $finalOption['isRequired'] = (bool) ($finalOption['isRequired'][0] + $finalOption['isRequired'][1]);
            }

            if (is_array($finalOption['title'])) {
                $finalOption['title'] = $finalOption['title'][0];
            }

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

    private function buildOptionArray($sku)
    {
        $optionsBefore = $this->finalImportData[$sku]['custom_options'];
        $optionsAfter = [];
        $sortOrderCounter = 0;
        foreach ($optionsBefore as $option) {
            $valuesArr = [];
            foreach ($option['values'] as $val) {
                $valuesArr[] = [
                    'title' => $val,
                    'price' => '0',
                    //'price_type' => 'fixed',
                    //'sku' => 'A',
                    'sort_order' => $sortOrderCounter,
                    //'is_delete' => '0'
                ];
                $sortOrderCounter++;
            }
            $optionsAfter[] = [
                    'sort_order' => $sortOrderCounter,
                    'title' => $option['title'],
                    //'price_type' => 'fixed',
                    //'price' => '',
                    'type' => 'drop_down',
                    'is_require' => $option['isRequired'],
                    'values' => $valuesArr
            ];
            $sortOrderCounter++;
        }

        return $optionsAfter;
    }

    private function assignCustomOptionsToProduct($product, $sku, $unsetBefore = false)
    {
        $options = $this->buildOptionArray($sku);
        if ($unsetBefore === true) {
            $product->unsetData('options');
        }
        foreach ($options as $optionArray) {
            $option = $this->productOptionFactory->create();
            $option->setProductId($product->getId())
                ->setStoreId($product->getStoreId())
                ->addData($optionArray);
            $option->save();
            $product->addOption($option);
        }
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
        $toProcessSkus = array_keys($this->finalImportData);
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', implode(',', $toProcessSkus), 'in')->create();
        $products = $this->productRepository->getList($searchCriteria)->getItems();
        foreach ($products as $product) {
            $sku = $product->getSku();
            $product->setName($this->finalImportData[$sku]['name']);
            $product->setPrice($this->finalImportData[$sku]['price']);
            //$product->setCustomAttribute('color', 4);
            $brandAttribute = $this->eavConfig->getAttribute('catalog_product', 'manufacturer');
            $options = $brandAttribute->getSource()->getAllOptions();
            $brandId = null;
            foreach ($options as $option) {
                if ($option['label'] == $this->finalImportData[$sku]['brand']) {
                    $brandId = $option['value'];
                    break;
                }
            }
            if ($brandId) {
                $product->setCustomAttribute('manufacturer', $brandId);
            }

            $this->assignCustomOptionsToProduct($product, $sku, true);
            $toProcessSkus = array_diff($toProcessSkus, [$sku]);
            $this->productRepository->save($product);
        }

        foreach ($toProcessSkus as $toProcessSku) {
            $product= $this->productFactory->create();
            $product->setTypeId('simple');
            $product->setSku($toProcessSku);
            $product->setName($this->finalImportData[$toProcessSku]['name']);
            $product->setPrice($this->finalImportData[$toProcessSku]['price']);
            $product->setAttributeSetId(4);
            $product->setStatus(0);
            $product->setWeight(1);
            $product->setVisibility(1);


            $brandAttribute = $this->eavConfig->getAttribute('catalog_product', 'manufacturer');
            $options = $brandAttribute->getSource()->getAllOptions();
            $brandId = null;
            foreach ($options as $option) {
                if ($option['label'] == $this->finalImportData[$toProcessSku]['brand']) {
                    $brandId = $option['value'];
                    break;
                }
            }
            if ($brandId) {
                $product->setCustomAttribute('manufacturer', $brandId);
            }

            //$defaultWebsiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
            $product->setStoreId(1);

            $brandAttribute = $this->eavConfig->getAttribute('catalog_product', 'manufacturer');
            $brandId = $brandAttribute->getSource()->getOptionId($this->finalImportData[$toProcessSku]['brand']);
            if ($brandId) {
                $product->setCustomAttribute('manufacturer', $brandId);
            }

            $product->save();
            $this->assignCustomOptionsToProduct($product, $toProcessSku);
        }

        return true;
    }
}

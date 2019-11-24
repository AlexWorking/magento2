<?php


namespace Potoky\EyelensProduct\Model\Product\Type;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Exception;
use Magento\Catalog\Model\ProductFactory;
use Potoky\EyelensProduct\Helper\Data as ModuleHelper;

class Eyelens extends \Magento\Catalog\Model\Product\Type\AbstractType
{
    const TYPE_CODE = 'eyelens';
    const LINK_TYPE_EYELENS = 6;

    /**
     *
     */
    private $productFactory;

    /**
     * Cache key for Associated Products
     *
     * @var string
     */
    protected $_keyTwicedProducts = '_cache_instance_twiced_products';

    /**
     * Cache key for Associated Product Ids
     *
     * @var string
     */
    protected $_keyTwicedProductIds = '_cache_instance_twiced_product_ids';

    /**
     * Cache key for Status Filters
     *
     * @var string
     */
    protected $_keyStatusFilters = '_cache_instance_status_filters';

    /**
     * Product is composite properties
     *
     * @var bool
     */
    protected $_isComposite = true;

    /**
     * Product is possible to configure
     *
     * @var bool
     */
    protected $_canConfigure = true;

    /**
     * Catalog product status
     *
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $_catalogProductStatus;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Catalog product link
     *
     * @var \Magento\GroupedProduct\Model\ResourceModel\Product\Link
     */
    protected $productLinks;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    /**
     * @var \Magento\Msrp\Helper\Data
     */
    protected $msrpData;

    /**
     *
     * @var ModuleHelper
     */
    private $moduleHelper;

    /**
     * @param ProductFactory
     * @param \Magento\Catalog\Model\Product\Option $catalogProductOption
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Catalog\Model\Product\Type $catalogProductType
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Psr\Log\LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\GroupedProduct\Model\ResourceModel\Product\Link $catalogProductLink
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $catalogProductStatus
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Msrp\Helper\Data $msrpData
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @param ModuleHelper $moduleHelper;
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ModuleHelper $moduleHelper,
        ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Option $catalogProductOption,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        \Magento\GroupedProduct\Model\ResourceModel\Product\Link $catalogProductLink,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $catalogProductStatus,
        \Magento\Framework\App\State $appState,
        \Magento\Msrp\Helper\Data $msrpData,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->moduleHelper =$moduleHelper;
        $this->productFactory =$productFactory;
        $this->productLinks = $catalogProductLink;
        $this->_storeManager = $storeManager;
        $this->_catalogProductStatus = $catalogProductStatus;
        $this->_appState = $appState;
        $this->msrpData = $msrpData;
        parent::__construct(
            $catalogProductOption,
            $eavConfig,
            $catalogProductType,
            $eventManager,
            $fileStorageDb,
            $filesystem,
            $coreRegistry,
            $logger,
            $productRepository,
            $serializer
        );
    }

    /**
     * Return relation info about used products
     *
     * @return \Magento\Framework\DataObject Object with information data
     */
    public function getRelationInfo()
    {
        $info = new \Magento\Framework\DataObject();
        $info->setTable(
            'catalog_product_link'
        )->setParentFieldName(
            'product_id'
        )->setChildFieldName(
            'linked_product_id'
        )->setWhere(
            'link_type_id=' . static::LINK_TYPE_EYELENS
        );
        return $info;
    }

    /**
     * Retrieve parent ids array by requested child
     *
     * @param int|array $childId
     * @return array
     */
    public function getParentIdsByChild($childId)
    {
        return $this->productLinks->getParentIdsByChild(
            $childId,
            static::LINK_TYPE_EYELENS
        );
    }

    /**
     * Retrieve array of associated products
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getAssociatedProducts($product)
    {
        if (!$product->hasData($this->_keyTwicedProducts)) {
            $associatedProducts = [];

            $this->setSaleableStatus($product);

            $collection = $this->getAssociatedProductCollection(
                $product
            )->addAttributeToSelect(
                ['name', 'price', 'special_price', 'special_from_date', 'special_to_date', 'tax_class_id', 'image']
            )->setPositionOrder()->addStoreFilter(
                $this->getStoreFilter($product)
            )->addAttributeToFilter(
                'status',
                ['in' => $this->getStatusFilters($product)]
            );

            foreach ($collection as $item) {
                $associatedProducts[] = $item;
            }

            $product->setData($this->_keyTwicedProducts, $associatedProducts);
        }
        return $product->getData($this->_keyTwicedProducts);
    }

    /**
     * Flush Associated Products Cache
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     * @since 100.1.0
     */
    public function flushAssociatedProductsCache($product)
    {
        return $product->unsetData($this->_keyTwicedProducts);
    }

    /**
     * Add status filter to collection
     *
     * @param  int $status
     * @param  \Magento\Catalog\Model\Product $product
     * @return $this
     */
    public function addStatusFilter($status, $product)
    {
        $statusFilters = $product->getData($this->_keyStatusFilters);
        if (!is_array($statusFilters)) {
            $statusFilters = [];
        }

        $statusFilters[] = $status;
        $product->setData($this->_keyStatusFilters, $statusFilters);

        return $this;
    }

    /**
     * Set only saleable filter
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return $this
     */
    public function setSaleableStatus($product)
    {
        $product->setData($this->_keyStatusFilters, $this->_catalogProductStatus->getSaleableStatusIds());
        return $this;
    }

    /**
     * Return all assigned status filters
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getStatusFilters($product)
    {
        if (!$product->hasData($this->_keyStatusFilters)) {
            return [
                \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
                \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED,
            ];
        }
        return $product->getData($this->_keyStatusFilters);
    }

    /**
     * Retrieve related products identifiers
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getAssociatedProductIds($product)
    {
        if (!$product->hasData($this->_keyTwicedProductIds)) {
            $associatedProductIds = [];
            /** @var $item \Magento\Catalog\Model\Product */
            foreach ($this->getAssociatedProducts($product) as $item) {
                $associatedProductIds[] = $item->getId();
            }
            $product->setData($this->_keyTwicedProductIds, $associatedProductIds);
        }
        return $product->getData($this->_keyTwicedProductIds);
    }

    /**
     * Retrieve collection of associated products
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection
     */
    public function getAssociatedProductCollection($product)
    {
        /** @var \Magento\Catalog\Model\Product\Link  $links */
        $links = $product->getLinkInstance();
        $links->setLinkTypeId(static::LINK_TYPE_EYELENS);
        $collection = $links->getProductCollection()->setFlag(
            'product_children',
            true
        )->setIsStrongMode();
        $collection->setProduct($product);
        return $collection;
    }

    /**
     * Returns product info
     *
     * @param \Magento\Framework\DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $isStrictProcessMode
     * @return array|string
     */
    protected function getProductInfo(\Magento\Framework\DataObject $buyRequest, $product, $isStrictProcessMode)
    {
        $productsInfo = $buyRequest->getSuperGroup() ?: [];
        $associatedProducts = $this->getAssociatedProducts($product);

        if (!is_array($productsInfo)) {
            return __('Please specify the quantity of product(s).')->render();
        }
        foreach ($associatedProducts as $subProduct) {
            if (!isset($productsInfo[$subProduct->getId()])) {
                if ($isStrictProcessMode && !$subProduct->getQty()) {
                    return __('Please specify the quantity of product(s).')->render();
                }
                $productsInfo[$subProduct->getId()] = $subProduct->isSalable() ? (float)$subProduct->getQty() : 0;
            }
        }

        return $productsInfo;
    }

    /**
     * Prepare product and its configuration to be added to some products list.
     *
     * Perform standard preparation process and add logic specific to Eyelens product type.
     *
     * @param \Magento\Framework\DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param string $processMode
     * @return \Magento\Framework\Phrase|array|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _prepareProduct(\Magento\Framework\DataObject $buyRequest, $product, $processMode)
    {
        $associatedId = $this->getAssociatedProductIds($product)[0];
        $firstLens = $this->productFactory->create()->loadByAttribute('entity_id', $associatedId);

        if (!$firstLens->getId()) {
            return __('Current Eyelens Product has no linked real product.')->render();
        }

        $secondLens = $this->productFactory->create()->loadByAttribute('entity_id', $associatedId);

        $qty = $buyRequest->getQty();

        $firstLens->addCustomOption('add ' . $product->getSku(), 'first')->setQty($qty)->setCartQty($qty);
        $secondLens->addCustomOption('add ' . $product->getSku(), 'second')->setQty($qty)->setCartQty($qty);

        $secondLens->addCustomOption('separate_add', 'second');

        return [$firstLens, $secondLens];
    }

    /**
     * Retrieve products divided into groups required to purchase
     *
     * At least one product in each group has to be purchased
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getProductsToPurchaseByReqGroups($product)
    {
        return [$this->getAssociatedProducts($product)];
    }

    /**
     * Prepare selected qty for eyelens product's options
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\DataObject $buyRequest
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function processBuyRequest($product, $buyRequest)
    {
        $superGroup = $buyRequest->getSuperGroup();
        $superGroup = is_array($superGroup) ? array_filter($superGroup, 'intval') : [];

        $options = ['super_group' => $superGroup];

        return $options;
    }

    /**
     * Check that product of this type has weight
     *
     * @return bool
     */
    public function hasWeight()
    {
        return false;
    }

    /**
     * Delete data specific for Eyelens product type
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock
     */
    public function deleteTypeSpecificData(\Magento\Catalog\Model\Product $product)
    {
    }
    //phpcs:enable

    /**
     * Make changes to current Eyelens Product considering
     * parameters of the Twiced Product being linked to it at now
     *
     * @throws \Exception
     */
    public function beforeSave($product)
    {
        $product->unsetData($this->_keyTwicedProducts);

        try {
            $twicedProductId = $this->moduleHelper->getTwicedProductIdFromPost($product);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        if (!$twicedProductId) {
            $product->setQuantityAndStockStatus(['qty' => false, 'is_in_stock' => 0]);
        } else {
            $twicedProduct = $this->productRepository->getById($twicedProductId);
        }

        $customOptions = $twicedProduct->getData('options');

        if ($customOptions) {
            $this->moduleHelper->assignCustomOptionsToProduct($product, $customOptions);
            $product->setHasOptions(true);
        } else {
            $product->unsetData('options');
            $product->setHasOptions(false);
        }

        $twicedStockStatus = $twicedProduct->getQuantityAndStockStatus()['is_in_stock'];
        $product->setQuantityAndStockStatus(['qty' => false, 'is_in_stock' => $twicedStockStatus]);

        return parent::beforeSave($product);
    }

    /**
     * Returns msrp for children products
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    public function getChildrenMsrp(\Magento\Catalog\Model\Product $product)
    {
        $prices = [];
        foreach ($this->getAssociatedProducts($product) as $item) {
            if ($item->getMsrp() !== null) {
                $prices[] = $item->getMsrp();
            }
        }
        return $prices ? min($prices) : 0;
    }
}

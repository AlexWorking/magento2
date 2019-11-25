<?php

namespace Potoky\EyelensProduct\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;

class Data extends AbstractHelper
{
    /**
     *
     * @var OptionFactory
     */
    private $productOptionFactory;

    /**
     *
     * @var ResourceConnection;
     */
    private $resource;

    /**
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Data constructor.
     * @param Context $context
     * @param OptionFactory $optionFactory
     * @param ResourceConnection $resource
     * @param ProductRepositoryInterface $productRepository
     * @return void
     */
    public function __construct(
        Context $context,
        OptionFactory $optionFactory,
        ResourceConnection $resource,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->productOptionFactory = $optionFactory;
        $this->resource = $resource;
        $this->productRepository = $productRepository;
    }

    /**
     * Getter for the resource.
     *
     * @return ResourceConnection
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Getter for product repository.
     *
     * @return ProductRepositoryInterface
     */
    public function getProductRepository()
    {
        return $this->productRepository;
    }

    /**
     * Get Twiced Product from the Post request undergoing
     * necessary validations.
     *
     * @param $product
     * @return null|int
     * @throws \Exception
     */
    public function getTwicedProductIdFromPost($product)
    {
        $post = $this->_getRequest()->getPost();

        if (!isset($post['links']) || empty($post['links'])) {
            return null;
        }

        $links = $post['links'];

        if (!isset($links['twiced']) || count($links) > 1 || count($links['twiced']) > 1) {
            throw new \Exception("The number of linked products or link types exceeds one.");
        }

        /** @var \Magento\Catalog\Model\Product $product*/
        /** @var \Magento\Catalog\Model\Product $associatedProduct*/
        $associatedProduct = ($product->getTypeInstance()->getAssociatedProducts($product)[0]) ?? null;

        if ($associatedProduct &&
            $associatedProduct->getId() != $links['twiced'][0]['id'] &&
            $associatedProduct->getStatus() == 2
        ) {
            throw new \Exception(sprintf(
                "There is already a currently disabled Twiced Product with sku of %s being linked to this Product",
                $associatedProduct->getSku()
            ));
        }

        return $links['twiced'][0]['id'];
    }

    /**
     * Bind Custom Options to the Product.
     *
     * @param $product
     * @param array $options
     * @return void
     * @throws \Exception
     */
    public function assignCustomOptionsToProduct($product, $options)
    {
        /** @var \Magento\Catalog\Model\Product $product*/
        $product->unsetData('options');
        $options = $this->buildOptionArray($options);

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
     * Getter/Setter of the product's stock status.
     *
     * @param $product
     * @return void|int
     */
    public function stockStatus($product, $setValues = [])
    {
        /** @var \Magento\Catalog\Model\Product $product*/
        if (!empty($setValues)) {
            $product->setQuantityAndStockStatus(['qty' => $setValues['qty'], 'is_in_stock' => $setValues['is_in_stock']]);

            return;
        }

        return $product->getStockData()['is_in_stock'] ?? $product->getQuantityAndStockStatus()['is_in_stock'] ?? 0;
    }

    /**
     * Based on prepared Custom Options data
     * build an array acceptable for
     * creating and storing Custom Options as
     * Product Custom Options in core tables.
     *
     * @param array $optionsBefore
     * @return array
     */
    private function buildOptionArray($optionsBefore)
    {
        $optionsAfter = [];
        $sortOrderCounter = 0;
        foreach ($optionsBefore as $option) {
            $isObject = gettype($option) === 'object';
            $valuesArr = [];
            $values = ($isObject) ? $option->getValues() : $option['values'];
            foreach ($values as $val) {
                $valuesArr[] = [
                    'title' => ($isObject) ? $val->getData('title') : $val,
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
                'title' => ($isObject) ? $option->getData('title') : $option['title'],
                //'price_type' => 'fixed',
                //'price' => '',
                'type' => ($isObject) ? $option->getData('type') : 'drop_down',
                'is_require' => ($isObject) ? $option->getData('is_require') : $option['isRequired'],
                'values' => $valuesArr
            ];
            $sortOrderCounter++;
        }

        return $optionsAfter;
    }
}

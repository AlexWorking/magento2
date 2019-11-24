<?php


namespace Potoky\EyelensProduct\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Product\OptionFactory;
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
     * Data constructor.
     * @param Context $context
     * @param OptionFactory $optionFactory
     * @param ResourceConnection $recource
     */
    public function __construct(Context $context,  OptionFactory $optionFactory, ResourceConnection $resource)
    {
        parent::__construct($context);
        $this->productOptionFactory = $optionFactory;
        $this->resource = $resource;
    }

    /**
     * public getter for the resource
     *
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * get Twiced Product from the Post request undergoing
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

            throw new \Exception("The number of linked products or link types exceed one.");
        }

        $associatedProduct = $product->getInstanceType()->getAssociatedProducts()[0];

        if ($associatedProduct-getId() != $links['twiced'][0]['id']) {

            throw new \Exception(sprintf(
                "There is already a currently disabled Twiced Product with sku of %s being linked to this Product",
                $links['twiced'][0]['sku']
            ));
        }

        return $links['twiced'][0]['id'];
    }

    /**
     * bind Custom Options to the Product.
     *
     * @param $product
     * @param array $options
     * @param boolean $areAlreadyBuild
     * @param boolean $unsetBefore
     *
     * @throws \Exception
     */
    public function assignCustomOptionsToProduct($product, $options)
    {
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
     * based on prepared Custom Options data
     * build an array acceptable for
     * creating and storing Custom Options as
     * Product Custom Options in core tables
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

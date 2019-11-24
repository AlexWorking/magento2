<?php


namespace Potoky\EyelensProduct\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Product\OptionFactory;

class Data extends AbstractHelper
{
    /**
     *
     * @var OptionFactory
     */
    private $productOptionFactory;

    /**
     * Data constructor.
     * @param Context $context
     * @param OptionFactory $optionFactory
     */
    public function __construct(Context $context,  OptionFactory $optionFactory)
    {
        parent::__construct($context);
        $this->productOptionFactory = $optionFactory;
    }

    /**
     * get Twiced Product from the Post request undergoing
     * necessary validations.
     *
     * @param $product
     * @return boolean|null|int
     */
    public function getTwicedProduct($product)
    {
        $post = $this->_getRequest()->getPost();

        if (!isset($post['links']) || empty($post['links'])) {

            return null;
        }

        $links = $post['links'];

        if (!isset($links['twiced']) || count($links) > 1 || count($links['twiced']) > 1) {

            return false;
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
    public function assignCustomOptionsToProduct($product, $options, $unsetBefore = true)
    {
        $options = $this->buildOptionArray($options);

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

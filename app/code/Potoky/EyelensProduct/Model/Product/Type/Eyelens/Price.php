<?php


namespace Potoky\EyelensProduct\Model\Product\Type\Eyelens;


class Price extends \Magento\Catalog\Model\Product\Type\Price
{
    public function getPrice($product)
    {
        $parent = parent::getPrice($product);
        $twicedProduct = $product->getTypeInstance()->getAssociatedProducts($product)[0];

        return ($twicedProduct) ? 2 * $twicedProduct->getPrice() : 0;
    }
}

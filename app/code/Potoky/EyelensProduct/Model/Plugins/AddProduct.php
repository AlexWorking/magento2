<?php


namespace Potoky\EyelensProduct\Model\Plugins;

use Magento\Catalog\Model\Product;
class AddProduct
{
    public function aroundAddProduct($subject, $procede, Product $productInfo, $requestInfo = null)
    {
        if ($productInfo->getTypeId() === 'eyelens') {
            $realLens = $productInfo->getTypeInstance()->getAssociatedProducts($productInfo)[0];
            $productInfo = $realLens;
            $requestInfo['product'] = $productInfo->getId();
            $procede($productInfo, $requestInfo);
            $procede($productInfo, $requestInfo);
        } else {
            $procede($productInfo, $requestInfo);
        }

        return $subject;
    }
}

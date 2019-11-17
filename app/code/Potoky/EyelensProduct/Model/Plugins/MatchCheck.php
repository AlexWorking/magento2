<?php


namespace Potoky\EyelensProduct\Model\Plugins;

use Magento\Catalog\Model\Product;
class MatchCheck
{
    /*
     * Comparison array
     *
     * @var array
     */
    private $comparisonArray = [
        'grouped' => 'associated',
        'eyelens'  => 'twiced'
    ];

    public function aroundGetCollection($subject, $procede, Product $product, $type)
    {
        $productType = $product->getTypeId();
        $needsAdjustment = false;

        if (array_key_exists($productType, $this->comparisonArray) &&
            $this->comparisonArray[$productType] !== $type) {
            $needsAdjustment = true;
        }

        $result = $procede($product, $type);

        return ($needsAdjustment) ? [] : $result;
    }
}

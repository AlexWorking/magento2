<?php


namespace Potoky\EyelensProduct\Model\Product\Type;


use Magento\Catalog\Api\ProductRepositoryInterface;

class Eyelens extends \Magento\Catalog\Model\Product\Type\AbstractType
{
    const TYPE_CODE = 'eyelens';
    const LINK_TYPE_EYELENS = 6;

    /**
     * Delete data specific for Grouped product type
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock
     */
    public function deleteTypeSpecificData(\Magento\Catalog\Model\Product $product)
    {
    }
}

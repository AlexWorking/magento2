<?php


namespace Potoky\EyelensProduct\Model\Plugins;

use Potoky\EyelensProduct\Helper\Data as ModuleHelper;
class Product
{
    /**
     *
     * @var ModuleHelper
     */
    private $moduleHelper;

    /**
     * Product pluhin constructor.
     *
     * @var ModuleHelper
     */
    public function __construct(ModuleHelper $moduleHelper)
    {
        $this->moduleHelper = $moduleHelper;
    }

    public function afterInitProduct($subject, $product)
    {
        $typeInstance = $product->getTypeInstance();
        if ($typeInstance::TYPE_CODE == 'eyelens') {
            $twicedProductId = $typeInstance->getAssociatedProductIds($product)[0];
            $twicedProduct = $this->moduleHelper->getProductRepository()->getById($twicedProductId);
            $options = $twicedProduct->getOptions() ?? null;
            if ($options) {
                $product->setData('options', $options);
                $product->setData('has_options', 1);
            }
        }

        return $product;
    }
}

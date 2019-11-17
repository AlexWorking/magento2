<?php


namespace Potoky\EyelensProduct\Model\Plugins;

use Magento\Catalog\Model\Locator\LocatorInterface;
class Forbid
{
    const PRODUCT_TYPE = 'product_type';
    const LINK_TYPE = 'link_type';
    /*
     * @val LocatorInterface
     */
    private $locator;

    /*
     * Adding locator to the  Plugin
     *
     * @var LocatorInterface $locator;
     */
    public function __construct(LocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    public function afterModifyData($subject, $result)
    {
        $product = $this->locator->getProduct();
        $modelId = $product->getId();
        $productType = $product->getTypeId();
        if ($productType !== static::PRODUCT_TYPE) {
            $result[$product->getId()]['links'][static::LINK_TYPE] = [];
        }

        return $result;
    }
}

<?php


namespace Potoky\EyelensProduct\Model\Plugins;

use Magento\Catalog\Model\Locator\LocatorInterface;
class ForbidGrouped extends Forbid
{
    const PRODUCT_TYPE = 'grouped';
    const LINK_TYPE = 'associated';
}

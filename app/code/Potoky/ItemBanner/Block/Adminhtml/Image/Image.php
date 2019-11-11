<?php


namespace Potoky\ItemBanner\Block\Adminhtml\Image;

use Magento\Framework\View\Element\AbstractBlock;
class Image extends AbstractBlock
{
    public function toHtml()
    {
        return '<h1>Hello PHP Block Rendered in JS</h1>';
    }
}

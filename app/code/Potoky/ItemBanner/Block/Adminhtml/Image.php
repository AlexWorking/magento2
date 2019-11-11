<?php


namespace Potoky\ItemBanner\Block\Adminhtml;

use Magento\Backend\Block\Template\Context as TemplateContext;
use Magento\Framework\Data\Form\Element\Factory as FormElementFactory;
use Magento\Backend\Block\Template;
use Magento\Ui\Component\Form\Element\DataType\Media\Image as ImageUiComponent;
class Image extends Template
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $elementFactory;

    protected $objectManager;

    /**
     * @param ImageUiComponent $imageUiComponent
     * @param TemplateContext $context
     * @param FormElementFactory $elementFactory
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        FormElementFactory $elementFactory,
        $data = []
    ) {
        $this->elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    public function prepareElementHtml($element)
    {
        /**@var \Magento\Framework\View\Element\Template $imageDomSource*/
        $imageDomSource = $this->getLayout()->createBlock('Magento\Backend\Block\Template')->setTemplate('Potoky_ItemBanner::imageDomSource.phtml');
        $element->setData('after_element_html', $imageDomSource->_toHtml());
        return $element;
    }
}

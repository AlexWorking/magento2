<?php


namespace Potoky\ItemBanner\Block\Adminhtml;

use Magento\Framework\Data\Form\Element\AbstractElement as Element;
use Magento\Backend\Block\Template\Context as TemplateContext;
use Magento\Framework\Data\Form\Element\Factory as FormElementFactory;
use Magento\Backend\Block\Template;
use Magento\Ui\Component\Form\Element\DataType\Media\Image as ImageUiComponent;
class Image extends Template
{
    protected $imageUiComponent;
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $elementFactory;

    /**
     * @param ImageUiComponent $imageUiComponent
     * @param TemplateContext $context
     * @param FormElementFactory $elementFactory
     * @param array $data
     */
    public function __construct(
        ImageUiComponent $imageUiComponent,
        TemplateContext $context,
        FormElementFactory $elementFactory,
        $data = []
    ) {
        $this->imageUiComponent = $imageUiComponent;
        $this->elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    public function prepareElementHtml($element)
    {
        $this->imageUiComponent->prepare();
        $element->setData('after_element_html', $this->imageUiComponent->getData());
        return $element;
    }

    public function setNameInLayout($name)
    {
        $name = 'potoky_itembanner_instance_parameter';
        return parent::setNameInLayout($name);
    }
}

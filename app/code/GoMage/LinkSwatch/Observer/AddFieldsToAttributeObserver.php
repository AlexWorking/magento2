<?php
namespace GoMage\LinkSwatch\Observer;

use Magento\Framework\Data\Form;

class AddFieldsToAttributeObserver extends \Magento\Swatches\Observer\AddFieldsToAttributeObserver
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->moduleManager->isOutputEnabled('Magento_Swatches')) {
            return;
        }

        /** @var Form $form */
        $form = $observer->getForm();
        $fieldset = $form->getElement('base_fieldset');
        $yesnoSource = $this->yesNo->toOptionArray();

        $fieldset->addField(
            'use_swatch_as_link',
            'select',
            [
                'name' => 'use_swatch_as_link',
                'label' => __('Use Swatch as Link'),
                'title' => __('Use Swatch as Link'),
                'note' => __('If set to Yes clicking will redirect to other Product'),
                'values' => $yesnoSource,
            ],
            'use_product_image_for_swatch'
        );
    }
}

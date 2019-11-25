<?php


namespace Potoky\EyelensProduct\Model\Import;

use Magento\ImportExport\Model\Import;
use \Magento\ImportExport\Model\Source\Import\AbstractBehavior;

class Behavior extends AbstractBehavior
{
    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_APPEND => __('Add/Replace'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return 'eyelens';
    }
}

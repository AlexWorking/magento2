<?php


namespace Potoky\EyelensProduct\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Upgrades DB for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $data = [
            [
                'link_type_id' => \Potoky\EyelensProduct\Model\Product\Type\Eyelens::LINK_TYPE_EYELENS,
                'code' => 'eyelens',
            ]
        ];
        foreach ($data as $bind) {
            $setup->getConnection()
                ->insertForce($setup->getTable('catalog_product_link_type'), $bind);
        }

        $data = [
            'link_type_id' => \Potoky\EyelensProduct\Model\Product\Type\Eyelens::LINK_TYPE_EYELENS,
            'product_link_attribute_code' => 'position',
            'data_type' => 'int',
        ];
        $setup->getConnection()
            ->insertMultiple($setup->getTable('catalog_product_link_attribute'), $data);

        $setup->endSetup();
    }
}

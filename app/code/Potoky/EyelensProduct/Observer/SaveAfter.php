<?php

namespace Potoky\EyelensProduct\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Potoky\EyelensProduct\Helper\Data as ModuleHelper;

class SaveAfter implements ObserverInterface
{
    /**
     *
     * @var ModuleHelper
     */
    private $moduleHelper;

    /**
     * SaveAfter constructor.
     *
     * @param ModuleHelper $moduleHelper
     * @return void
     */
    public function __construct(ModuleHelper $moduleHelper)
    {
        $this->moduleHelper = $moduleHelper;
    }

    /**
     *
     * @param Observer $observer
     * @return $this|void
     *
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getData('product');

        if ($product->getTypeId() == 'simple') {
            if ($product->getStatus() == 2 || $this->moduleHelper->stockStatus($product) == 0) {
                $connection = $this->moduleHelper->getResource()->getConnection();
                $cond = sprintf(
                    "l.linked_product_id=%s AND link_type_id=%s",
                    $product->getId(),
                    \Potoky\EyelensProduct\Model\Product\Type\Eyelens::LINK_TYPE_EYELENS
                );
                $select = $connection->select()
                    ->from(
                        ['l' => 'catalog_product_link'],
                        ['product_id']
                    )->where($cond);
                $rows = $connection->fetchAll($select);
                foreach ($rows as $row) {
                    $eyelens = $this->moduleHelper->getProductRepository()->getById($row['product_id']);
                    $eyelens->setQuantityAndStockStatus(['qty' => false, 'is_in_stock' => 0]);
                    $eyelens->setSaveFromPost(false);
                    $this->moduleHelper->getProductRepository()->save($eyelens);
                }
            }
        }

        return $this;
    }
}

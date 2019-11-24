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
     * @param ModuleHelper $moduleHelper
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

        switch ($product->getTypeId()) {
            case 'eyelens':
                if ($this->moduleHelper->getCustomOptions()) {
                    $this->moduleHelper->assignCustomOptionsToProduct(
                        $product,
                        $this->moduleHelper->getCustomOptions()
                    );
                }
                break;

            case 'simple':
                if ($product->getStatus() == 0 || $this->moduleHelper->stockStatus($product) == 0) {
                    $connection = $this->moduleHelper->getResource()->getConnection();
                    $select = $connection->select()
                        ->from(
                            ['l' => 'catalog_product_link'],
                            ['product_id']
                        )->where(
                            "l.linked_product_id=?",
                            $product->getId()
                        );
                    $rows = $connection->fetchAll($select);
                    foreach ($rows as $row) {
                        $eyelens = $this->moduleHelper->getProductRepository()->getById($row['product_id']);
                        $eyelens->setQuantityAndStockStatus(['qty' => false, 'is_in_stock' => 0]);
                        $this->moduleHelper->getProductRepository()->save($eyelens);
                    }
                }
                break;
        }

        return $this;
    }
}

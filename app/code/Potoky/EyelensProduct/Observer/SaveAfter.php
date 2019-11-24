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
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getData('product');
        $connection = $this->moduleHelper->getResource()->getConnection();

        return $this;
    }
}

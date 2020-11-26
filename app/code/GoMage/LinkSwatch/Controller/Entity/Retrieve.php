<?php

namespace GoMage\LinkSwatch\Controller\Entity;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableType;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Retrieve extends Action
{
    const SWATCH_ATTRIBUTE = 'colour_swatch';
    const DISPLAY_ATTRIBUTE = 'displayed_color_name';

    /**
     *
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     *
     * @var ConfigurableType
     */
    private $configurableType;

    /**
     *
     * @var Config
     */
    private $eavConfig;

    /**
     * Retrieve constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ConfigurableType $configurableType
     * @param Config $eavConfig
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ProductRepositoryInterface $productRepository,
        ConfigurableType $configurableType,
        Config $eavConfig
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
        $this->eavConfig = $eavConfig;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest()->getParams();
        $confProd = $this->productRepository->getById($request['product']);
        $anySimpleProd = $confProd->getTypeInstance()->getUsedProducts($confProd)[0];
        $confParentIds = $this->configurableType->getParentIdsByChild($anySimpleProd->getId());
        $confParentIds = array_diff($confParentIds, [$request['product']]);

        $swatchAttr = $this->eavConfig->getAttribute('catalog_product', self::SWATCH_ATTRIBUTE);
        $options = $swatchAttr->getSource()->getAllOptions();
        $optionLabel = '';
        foreach ($options as $option) {
            if ($option['value'] === $request['value']) {
                $optionLabel = $option['label'];

                break;
            }
        }
        $displayAttr = $this->eavConfig->getAttribute('catalog_product', self::DISPLAY_ATTRIBUTE);
        $options = $displayAttr->getSource()->getAllOptions();
        $optionId = '';
        foreach ($options as $option) {
            if ($option['label'] === $optionLabel) {
                $optionId = $option['value'];

                break;
            }
        }
        foreach ($confParentIds as $confParentId) {
            $confProd = $this->productRepository->getById($confParentId);

            if ($confProd->getData($displayAttr->getAttributeCode()) === $optionId) {
                $resultRedirect->setPath($confProd->getData('url_key') . '.html');

                return $resultRedirect;
            }
        }
        $resultRedirect->setPath($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}

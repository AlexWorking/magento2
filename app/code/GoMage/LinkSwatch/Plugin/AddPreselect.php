<?php

namespace GoMage\LinkSwatch\Plugin;

use GoMage\LinkSwatch\Controller\Entity\Retrieve;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Eav\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;

class AddPreselect
{
    /**
     *
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     *
     * @var DecoderInterface
     */
    protected $jsonDecoder;

    /**
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     *
     * @var Config
     */
    private $eavConfig;

    /**
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * AddPreselect constructor.
     *
     * @param EncoderInterface $jsonEncoder
     * @param DecoderInterface $jsonDecoder
     * @param ProductRepositoryInterface $productRepository
     * @param Config $eavConfig
     * @param RequestInterface
     */
    public function __construct(
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        ProductRepositoryInterface $productRepository,
        Config $eavConfig,
        RequestInterface $request
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->jsonDecoder = $jsonDecoder;
        $this->productRepository = $productRepository;
        $this->eavConfig = $eavConfig;
        $this->request = $request;
    }

    /**
     * Add attribute option for being preselected
     * after Product Page has been loaded.
     *
     * @param Configurable $subject
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(Configurable $subject, string $result)
    {
        $productId = $this->request->getParam('id');
        $confProd = $this->productRepository->getById($productId);
        $swatchAttr = $this->eavConfig->getAttribute('catalog_product', Retrieve::SWATCH_ATTRIBUTE);
        $displayAttr = $this->eavConfig->getAttribute('catalog_product', Retrieve::DISPLAY_ATTRIBUTE);
        $attrVal = $confProd->getData($displayAttr->getAttributeCode());
        $options = $displayAttr->getSource()->getAllOptions();
        foreach ($options as $option) {
            if ($option['value'] === $attrVal) {
                $attrVal = $option['label'];

                break;
            }
        }
        $options = $swatchAttr->getSource()->getAllOptions();
        foreach ($options as $option) {
            if ($option['label'] === $attrVal) {
                $attrVal = $option['value'];

                break;
            }
        }

        $result = $this->jsonDecoder->decode($result);
        $result['preselect'] = $swatchAttr->getId() . ':' . $attrVal;
        $result = $this->jsonEncoder->encode($result);

        return $result;
    }
}

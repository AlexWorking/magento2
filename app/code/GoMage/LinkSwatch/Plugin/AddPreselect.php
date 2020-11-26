<?php

namespace GoMage\LinkSwatch\Plugin;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\App\RequestInterface;
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
     * @var RequestInterface
     */
    private $request;

    /**
     * AddPreselect constructor.
     *
     * @param EncoderInterface
     * @param RequestInterface
     */
    public function __construct(EncoderInterface $jsonEncoder, RequestInterface $request)
    {
        $this->jsonEncoder = $jsonEncoder;
        $this->request = $request;
    }

    /**
     * Add attribute option for being preselected
     * after Product Page has been loaded.
     *
     * @param Configurable $subject
     * @param string $result
     */
    public function afterGetJsonConfig(Configurable $subject, string $result)
    {
        $params = $this->request->getParams();
        $test = 'test';

        return $result;
    }
}

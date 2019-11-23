<?php


namespace Potoky\EyelensProduct\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
class Data extends AbstractHelper
{
    public function validateLink($product)
    {
        $post = $this->_getRequest()->getPost();

        if (!isset($post['links']) || empty($post['links'])) {

            return 0;
        }

        $links = $post['links'];

        if (!isset($links['twiced']) || count($links) > 1 || count($links['twiced']) > 1) {

            return false;
        }

        return 1;
    }
}

<?php

namespace Dotdigitalgroup\Email\Block;

class Edc extends \Magento\Framework\View\Element\Template
{


    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
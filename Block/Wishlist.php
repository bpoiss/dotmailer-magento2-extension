<?php

namespace Dotdigitalgroup\Email\Block;

class Wishlist extends \Magento\Catalog\Block\Product\AbstractProduct
{

    protected $_website;

    public $helper;
    public $priceHelper;
    protected $_customerFactory;
    protected $_wishlistFactory;

    /**
     * Wishlist constructor.
     *
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Block\Product\Context  $context
     * @param \Dotdigitalgroup\Email\Helper\Data      $helper
     * @param \Magento\Framework\Pricing\Helper\Data  $priceHelper
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_wishlistFactory = $wishlistFactory;
        $this->_customerFactory = $customerFactory;
        $this->helper           = $helper;
        $this->priceHelper      = $priceHelper;
    }

    public function getWishlistItems()
    {
        $wishlist = $this->_getWishlist();
        if ($wishlist && count($wishlist->getItemCollection())) {
            return $wishlist->getItemCollection();
        } else {
            return false;
        }
    }

    protected function _getWishlist()
    {
        $customerId = $this->getRequest()->getParam('customer_id');
        if ( ! $customerId) {
            return false;
        }

        $customer = $this->_customerFactory->create()
            ->load($customerId);
        if ( ! $customer->getId()) {
            return false;
        }

        $collection = $this->_wishlistFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('updated_at', 'DESC')
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        } else {
            return false;
        }

    }

    public function getMode()
    {
        return $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_WIHSLIST_DISPLAY
        );
    }

    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
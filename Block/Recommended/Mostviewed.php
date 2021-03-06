<?php

namespace Dotdigitalgroup\Email\Block\Recommended;


class Mostviewed extends \Magento\Catalog\Block\Product\AbstractProduct
{

    public $helper;
    public $priceHelper;
    public $recommnededHelper;

    protected $_localeDate;
    protected $_productCollection;
    protected $_categoryFactory;
    protected $_productFactory;
    protected $_productCollectionFactory;
    protected $_reportProductCollection;

    /**
     * Mostviewed constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data                             $helper
     * @param \Magento\Catalog\Block\Product\Context                         $context
     * @param \Magento\Framework\Pricing\Helper\Data                         $priceHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory
     * @param \Magento\Catalog\Model\ProductFactory                          $productFactory
     * @param \Dotdigitalgroup\Email\Helper\Recommended                      $recommended
     * @param \Magento\Catalog\Model\CategoryFactory                         $categtoryFactory
     * @param \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param array                                                          $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Model\CategoryFactory $categtoryFactory,
        \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $reportProductCollection,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productFactory    = $productFactory;
        $this->_categoryFactory   = $categtoryFactory;
        $this->_reportProductCollection = $reportProductCollection;
        $this->helper             = $helper;
        $this->recommnededHelper  = $recommended;
        $this->priceHelper        = $priceHelper;
        $this->storeManager       = $this->_storeManager;

        parent::__construct($context, $data);
    }

    /**
     * Get product collection.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        $productsToDisplay = array();
        $mode              = $this->getRequest()->getActionName();
        $limit
                           = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $from              = $this->recommnededHelper->getTimeFromConfig($mode);
        $to                = new \Zend_Date($this->_localeDate->date()
            ->getTimestamp());

        $reportProductCollection = $this->_reportProductCollection->create()
            ->addViewsCount($from, $to->toString(\Zend_Date::ISO_8601))
            ->setPageSize($limit);

        //filter collection by category by category_id
        if ($cat_id = $this->getRequest()->getParam('category_id')) {
            $category = $this->_categoryFactory->create()->load($cat_id);
            if ($category->getId()) {
                $reportProductCollection->getSelect()
                    ->joinLeft(
                        array("ccpi" => 'catalog_category_product_index'),
                        "e.entity_id = ccpi.product_id",
                        array("category_id")
                    )
                    ->where('ccpi.category_id =?', $cat_id);
            } else {
                $this->helper->log('Most viewed. Category id ' . $cat_id
                    . ' is invalid. It does not exist.');
            }
        }

        //filter collection by category by category_name
        if ($cat_name = $this->getRequest()->getParam('category_name')) {
            $category = $this->_categoryFactory->create()
                ->loadByAttribute('name', $cat_name);
            if ($category) {
                $reportProductCollection->getSelect()
                    ->joinLeft(
                        array("ccpi" => 'catalog_category_product_index'),
                        "e.entity_id  = ccpi.product_id",
                        array("category_id")
                    )
                    ->where('ccpi.category_id =?', $category->getId());
            } else {
                $this->helper->log('Most viewed. Category name ' . $cat_name
                    . ' is invalid. It does not exist.');
            }
        }

        //product ids from the report product collection
        $productIds = $reportProductCollection->getColumnValues('entity_id');

        $productCollectionFactory = $this->_productCollectionFactory->create();
        $productCollectionFactory->addIdFilter($productIds)
            ->addAttributeToSelect(
                array('product_url', 'name', 'store_id', 'small_image', 'price')
            );

        //product collection
        foreach ($productCollectionFactory as $_product) {
            //add only saleable products
            if ($_product->isSalable()) {
                $productsToDisplay[] = $_product;
            }
        }

        return $productsToDisplay;
    }


    /**
     * Display mode type.
     *
     * @return mixed|string
     */
    public function getMode()
    {
        return $this->recommnededHelper->getDisplayType();
    }


    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
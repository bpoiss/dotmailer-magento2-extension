<?php

namespace Dotdigitalgroup\Email\Helper;

use \Dotdigitalgroup\Email\Helper\Config as EmailConfig;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_context;
    protected $_resourceConfig;
    protected $_storeManager;
    protected $_objectManager;
    protected $_backendConfig;
    protected $_contactFactory;
    protected $_productMetadata;
    protected $_sessionModel;
    protected $_adapter;

    /**
     * Data constructor.
     *
     * @param \Magento\Backend\Model\Auth\Session         $sessionModel
     * @param \Magento\Framework\App\ProductMetadata      $productMetadata
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Magento\Config\Model\ResourceModel\Config  $resourceConfig
     * @param \Magento\Framework\App\ResourceConnection   $adapter
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $sessionModel,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\ResourceConnection $adapter,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {

        $this->_adapter         = $adapter;
        $this->_sessionModel    = $sessionModel;
        $this->_productMetadata = $productMetadata;
        $this->_contactFactory  = $contactFactory;
        $this->_resourceConfig  = $resourceConfig;
        $this->_storeManager    = $storeManager;
        $this->_objectManager   = $objectManager;

        parent::__construct($context);
    }

    /**
     * Get api creadentials enabled.
     *
     * @param int $website
     *
     * @return mixed
     */
    public function isEnabled($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);
        $enabled = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website
        );

        return $enabled;
    }

    /**
     * Get all stores.
     *
     * @param bool|false $default
     *
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    public function getStores($default = false)
    {
        return $this->_storeManager->getStores($default);
    }

    public function auth($authRequest)
    {
        if ($authRequest != $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE
            )
        ) {

            //throw new Exception('Authentication failed : ' . $authRequest);
            return false;
        }

        return true;
    }

    public function authIpAddress()
    {
        if ($ipString = $this->_getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_IP_RESTRICTION_ADDRESSES,
            'default'
        )
        ) {
            //string to array
            $ipArray = explode(',', $ipString);

            //remove white spaces
            foreach ($ipArray as $key => $ip) {
                $ipArray[$key] = preg_replace('/\s+/', '', $ip);
            }

            //ip address
            $ipAddress = $this->_remoteAddress->getRemoteAddress();

            if (in_array($ipAddress, $ipArray)) {
                return true;
            }
        } else {
            //empty ip list from configuration will ignore the validation
            return true;
        }

        return false;
    }

    protected function _getConfigValue($path, $contextScope = 'default',
        $contextScopeId = null
    ) {

        $config = $this->scopeConfig->getValue(
            $path, $contextScope, $contextScopeId
        );

        return $config;
    }

    public function getMappedCustomerId()
    {
        return $this->_getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_ID,
            'default'
        );
    }

    public function getMappedOrderId()
    {
        return $this->_getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID,
            'default'
        );
    }

    /**
     * Get website selected in admin.
     *
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    public function getWebsite()
    {
        $websiteId = $this->_request->getParam('website', false);
        if ($websiteId) {
            return $this->_storeManager->getWebsite($websiteId);
        }

        return $this->_storeManager->getWebsite();
    }

    /**
     *
     * @return mixed
     */
    public function getPasscode()
    {
        $websiteId = $this->_request->getParam('website', false);

        $scope   = 'default';
        $scopeId = '0';
        if ($websiteId) {
            $scope   = 'website';
            $scopeId = $websiteId;
        }

        $passcode = $this->_getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE,
            $scope, $scopeId
        );

        return $passcode;
    }

    /**
     * Save config data.
     *
     * @param $path
     * @param $value
     * @param $scope
     * @param $scopeId
     */
    public function saveConfigData($path, $value, $scope, $scopeId)
    {
        $this->_resourceConfig->saveConfig(
            $path,
            $value,
            $scope,
            $scopeId
        );
    }

    public function disableTransactionalDataConfig($scope, $scopeId)
    {
        $this->_resourceConfig->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
            0,
            $scope,
            $scopeId
        );
    }

    /**
     * Customer last order id.
     *
     * @return mixed
     */
    public function getLastOrderId()
    {
        return $this->_getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID,
            'default'
        );
    }

    public function log($data)
    {
        $this->_logger->info($data);
    }

    public function debug($title, $context)
    {
        $this->_logger->debug($title, $context);
    }

    public function error($title, $error)
    {
        $this->_logger->error($title, $error);
    }

    public function getDebugEnabled()
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADVANCED_DEBUG_ENABLED
        );
    }

    /**
     * Extension version number.
     *
     * @return string
     */
    public function getConnectorVersion()
    {
        return '';
    }

    public function getPageTrackingEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED
        );
    }

    public function getRoiTrackingEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED
        );
    }

    public function getMappedStoreName($website)
    {
        $mapped    = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME
        );
        $storeName = ($mapped) ? $mapped : '';

        return $storeName;
    }

    /**
     * Get the contact id for the customer based on website id.
     *
     * @param $email
     * @param $websiteId
     *
     * @return bool
     */
    public function getContactId($email, $websiteId)
    {
        $contact = $this->_contactFactory->create()
            ->loadByCustomerEmail($email, $websiteId);
        if ($contactId = $contact->getContactId()) {
            return $contactId;
        }

        $client   = $this->getWebsiteApiClient($websiteId);
        $response = $client->postContacts($email);

        if (isset($response->message)) {
            return false;
        }
        //save contact id
        if (isset($response->id)) {
            $contact->setContactId($response->id)
                ->save();
        }

        return $response->id;
    }

    /**
     * Api client by website.
     *
     * @param int $website
     *
     * @return bool
     */
    public function getWebsiteApiClient($website = 0)
    {
        $apiUsername = $this->getApiUsername($website);
        $apiPassword = $this->getApiPassword($website);
        if ( ! $apiUsername || ! $apiPassword) {
            return false;
        }

        $client = $this->_objectManager->create(
            'Dotdigitalgroup\Email\Model\Apiconnector\Client',
            ['username' => $apiUsername, 'password' => $apiPassword]
        );

        return $client;
    }

    /**
     * @param int /object $website
     *
     * @return mixed
     */
    public function getApiUsername($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_USERNAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );
    }

    public function getApiPassword($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_PASSWORD,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );
    }

    /**
     * Get the addres book for customer.
     *
     * @param int $website
     *
     * @return mixed
     */
    public function getCustomerAddressBook($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website
        );
    }

    public function getSubscriberAddressBook($website)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );
    }

    /**
     * Guest address book.
     *
     * @param $website
     *
     * @return mixed
     */
    public function getGuestAddressBook($website)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getid()
        );
    }

    /**
     * @return $this
     */
    public function allowResourceFullExecution()
    {
        if ($this->getResourceAllocationEnabled()) {

            /* it may be needed to set maximum execution time of the script to longer,
             * like 60 minutes than usual */
            set_time_limit(7200);

            /* and memory to 512 megabytes */
            ini_set('memory_limit', '512M');
        }

        return $this;
    }

    /**
     * Use recommended resource allocation.
     *
     * @return bool
     */
    public function getResourceAllocationEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_RESOURCE_ALLOCATION
        );
    }

    public function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2)
        . ' ' . $unit[$i];
    }

    /**
     * @return string
     */
    public function getStringWebsiteApiAccounts()
    {
        $accounts = array();
        $websites = $this->getWebsites();
        foreach ($websites as $website) {
            $websiteId              = $website->getId();
            $apiUsername            = $this->getApiUsername($website);
            $accounts[$apiUsername] = $apiUsername . ', websiteId: '
                . $websiteId . ' name ' . $website->getName();
        }

        return implode('</br>', $accounts);
    }

    /**
     * Get all websites.
     *
     * @param bool|false $default
     *
     * @return \Magento\Store\Api\Data\WebsiteInterface[]
     */
    public function getWebsites($default = false)
    {
        return $this->_storeManager->getWebsites($default);
    }

    /**
     * @param int $website
     *
     * @return array|mixed
     */
    public function getCustomAttributes($website = 0)
    {
        $attr = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );

        if ( ! $attr) {
            return array();
        }

        return unserialize($attr);
    }


    /**
     * Enterprise custom datafields attributes.
     *
     * @param int $website
     *
     * @return array
     */
    public function getEnterpriseAttributes($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);
        $result  = array();
        $attrs   = $website->getConfig(
            'connector_data_mapping/enterprise_data'
        );
        //get individual mapped keys
        foreach ($attrs as $key => $one) {
            $config = $website->getConfig(
                'connector_data_mapping/enterprise_data/' . $key
            );
            //check for the mapped field
            if ($config) {
                $result[$key] = $config;
            }
        }

        if (empty($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Retrieve authorisation code.
     */
    public function getCode()
    {
        $adminUser = $this->_sessionModel
            ->getUser();
        $code      = $adminUser->getEmailCode();

        return $code;
    }

    /**
     * Autorisation url for OAUTH.
     *
     * @return string
     */
    public function getAuthoriseUrl()
    {
        $clientId = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CLIENT_ID
        );

        //callback uri if not set custom
        $redirectUri = $this->getRedirectUri();
        $redirectUri .= 'connector/email/callback';

        $adminUser = $this->_sessionModel->getUser();
        //query params
        $params = array(
            'redirect_uri'  => $redirectUri,
            'scope'         => 'Account',
            'state'         => $adminUser->getId(),
            'response_type' => 'code'
        );

        $authorizeBaseUrl = $this->_objectManager->create(
            'Dotdigitalgroup\Email\Helper\Config'
        )->getAuthorizeLink();
        $url              = $authorizeBaseUrl . http_build_query($params)
            . '&client_id=' . $clientId;

        return $url;
    }

    public function getRedirectUri()
    {
        $callback = $this->_objectManager->create(
            'Dotdigitalgroup\Email\Helper\Config'
        )->getCallbackUrl();

        return $callback;
    }

    /**
     * order status config value
     *
     * @param int $website
     *
     * @return mixed order status
     */
    public function getConfigSelectedStatus($website = 0)
    {
        $status = $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS,
            $website
        );
        if ($status) {
            return explode(',', $status);
        } else {
            return false;
        }
    }

    /**
     * Get website level config.
     *
     * @param     $path
     * @param int $website
     *
     * @return mixed
     */
    public function getWebsiteConfig($path, $website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );
    }

    public function getConfigSelectedCustomOrderAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES,
            $website
        );
        if ($customAttributes) {
            return explode(',', $customAttributes);
        } else {
            return false;
        }
    }

    public function setConnectorContactToReImport($customerId)
    {
        $contactModel = $this->_objectManager->create(
            'Dotdigitalgroup\Email\Model\Contact'
        );
        $contactModel
            ->loadByCustomerId($customerId)
            ->setEmailImported(
                \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED
            )
            ->save();
    }

    /**
     * Disable website config when the request is made admin area only!
     *
     * @param $path
     *
     */
    public function disableConfigForWebsite($path)
    {
        $scopeId = 0;
        if ($website = $this->_request->getParam('website')) {
            $scope   = 'websites';
            $scopeId = $this->_storeManager->getWebsite($website)->getId();
        } else {
            $scope = "default";
        }
        $this->_resourceConfig->saveConfig(
            $path,
            0,
            $scope,
            $scopeId
        );
    }

    /**
     * number of customers with duplicate emails, emails as total number
     */
    public function getCustomersWithDuplicateEmails()
    {
        $customers = $this->_objectManager->create(
            'Magento\Customer\Model\Customer'
        )->getCollection();

        //duplicate emails
        $customers->getSelect()
            ->columns(array('emails' => 'COUNT(e.entity_id)'))
            ->group('email')
            ->having('emails > ?', 1);

        return $customers;
    }

    /**
     * Generate the baseurl for the default store
     * dynamic content will be displayed
     *
     * @return string
     */
    public function generateDynamicUrl()
    {
        $website = $this->_request->getParam('website', false);

        //set website url for the default store id
        $website = ($website) ? $this->_storeManager->getWebsite($website) : 0;

        $defaultGroup = $this->_storeManager->getWebsite($website)
            ->getDefaultGroup();

        if ( ! $defaultGroup) {
            return $mage = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_WEB
            );
        }

        //base url
        $baseUrl = $this->_storeManager->getStore(
            $defaultGroup->getDefaultStore()
        )->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);

        return $baseUrl;
    }

    /**
     *
     *
     * @param int $store
     *
     * @return mixed
     */
    public function isNewsletterSuccessDisabled($store = 0)
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS,
            'store', $store
        );
    }

    /**
     * @param int $store
     *
     * @return mixed
     */
    public function isCustomerSuccessDisabled($store = 0)
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS,
            'store', $store
        );
    }

    /**
     * get sales_flat_order table description
     *
     * @return array
     */
    public function getOrderTableDescription()
    {

        $salesTable = $this->_adapter->getTableName('sales_order');
        $adapter    = $this->_adapter->getConnection('read');
        $columns    = $adapter->describeTable($salesTable);

        return $columns;
    }

    /**
     * get sales_flat_quote table description
     *
     * @return array
     */
    public function getQuoteTableDescription()
    {

        $quoteTable = $this->_adapter->getTableName('quote');
        $adapter    = $this->_adapter->getConnection('read');
        $columns    = $adapter->describeTable($quoteTable);

        return $columns;
    }

    /**
     * @return bool
     */
    public function getEasyEmailCapture()
    {
        return (bool)$this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE
        );
    }

    /**
     * @return bool
     */
    public function getEasyEmailCaptureForNewsletter()
    {
        return (bool)$this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE_NEWSLETTER
        );
    }

    /**
     * get feefo logon config value
     *
     * @return mixed
     */
    public function getFeefoLogon()
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_FEEFO_LOGON
        );
    }

    /**
     * get feefo reviews limit config value
     *
     * @return mixed
     */
    public function getFeefoReviewsPerProduct()
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_FEEFO_REVIEWS
        );
    }

    /**
     * get feefo logo template config value
     *
     * @return mixed
     */
    public function getFeefoLogoTemplate()
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_FEEFO_TEMPLATE
        );
    }

    /**
     * update data fields.
     *
     * @param $email
     * @param $website
     * @param $storeName
     */
    public function updateDataFields($email, $website, $storeName)
    {
        $data = array();
        if ($store_name = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME
        )
        ) {
            $data[] = array(
                'Key'   => $store_name,
                'Value' => $storeName
            );
        }
        if ($website_name = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
        )
        ) {
            $data[] = array(
                'Key'   => $website_name,
                'Value' => $website->getName()
            );
        }
        if ( ! empty($data)) {
            //update data fields
            $client = $this->getWebsiteApiClient($website);
            $client->updateContactDatafieldsByEmail($email, $data);
        }
    }

    /**
     * Is magento enterprise.
     *
     * @return bool
     */
    public function isEnterprise()
    {
        //get edition name from the product metadata
        $edition = $this->_productMetadata->getEdition();
        if ($edition == 'Community') {
            return false;
        }

        return true;
    }

    /**
     * Update last quote id datafield.
     *
     * @param $quoteId
     * @param $email
     * @param $websiteId
     */
    public function updateLastQuoteId($quoteId, $email, $websiteId)
    {
        $client = $this->getWebsiteApiClient($websiteId);
        //last quote id config data mapped
        $quoteIdField = $this->getLastQuoteId();

        $data[] = array(
            'Key'   => $quoteIdField,
            'Value' => $quoteId
        );
        //update datafields for conctact
        $client->updateContactDatafieldsByEmail($email, $data);
    }

    public function getLastQuoteId()
    {
        return $this->_getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID,
            'default'
        );
    }

    /**
     * Get order sync enabled value from configuration.
     *
     * @param int $websiteId
     *
     * @return bool
     */
    public function getOrderSyncEnabled($websiteId = 0)
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get the catalog sync enabled value from config.
     *
     * @param int $websiteId
     *
     * @return bool
     */
    public function getCatalogSyncEnabled($websiteId = 0)
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Customer sync enabled.
     *
     * @param int $website
     *
     * @return bool
     */
    public function getCustomerSyncEnabled($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);

        $enabled = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website
        );

        return $enabled;
    }

    /**
     * Customer sync size limit.
     *
     * @param int $website
     *
     * @return mixed
     */
    public function getSyncLimit($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website
        );
    }

    /**
     * Get the guest sync enabled value.
     *
     * @param int $websiteId
     *
     * @return bool
     */
    public function getGuestSyncEnabled($websiteId = 0)
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function getSubscriberSyncEnabled($websiteId = 0)
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get customer datafields mapped - exclude custom attributes.
     *
     * @param $website
     *
     * @return mixed
     */
    public function getWebsiteCustomerMappingDatafields($website)
    {
        //customer mapped data
        $store      = $website->getDefaultStore();
        $mappedData = $this->scopeConfig->getValue(
            'connector_data_mapping/customer_data',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId()
        );
        unset($mappedData['custom_attributes'], $mappedData['abandoned_prod_name']);
        //skip non mapped customer datafields
        foreach ($mappedData as $key => $value) {
            if ( ! $value) {
                unset($mappedData[$key]);
            }
        }

        return $mappedData;
    }

    /**
     * @return bool
     */
    public function getCronInstalled()
    {
        $lastCustomerSync = $this->_objectManager->create(
            'Dotdigitalgroup\Email\Model\Cron'
        )
            ->getLastCustomerSync();

        $timespan = $this->dateDiff($lastCustomerSync);

        //last customer cron was less then 15 min
        if ($timespan <= 15 * 60) {
            return true;
        }

        return false;
    }

    /**
     * Diff between to times;
     *
     * @param $time1
     * @param $time2
     *
     * @return int
     */
    public function dateDiff($time1, $time2 = null)
    {
        if (is_null($time2)) {
            $time2 = new \Datetime();
        }
        $time1 = strtotime($time1);
        $time2 = strtotime($time2);

        return $time2 - $time1;
    }

    /**
     * Get the config id by the automation type.
     *
     * @param     $automationType
     * @param int $websiteId
     *
     * @return mixed
     */
    public function getAutomationIdByType($automationType, $websiteId = 0)
    {
        $path                 = constant(
            EmailConfig::class . '::' . $automationType
        );
        $automationCampaignId = $this->getWebsiteConfig($path, $websiteId);

        return $automationCampaignId;
    }

    /**
     * api- update the product name most expensive.
     *
     * @param $name
     * @param $email
     * @param $websiteId
     */
    public function updateAbandonedProductName($name, $email, $websiteId)
    {
        $client = $this->getWebsiteApiClient($websiteId);
        // id config data mapped
        $field = $this->getAbandonedProductName();

        if ($field) {
            $data[] = array(
                'Key'   => $field,
                'Value' => $name
            );
            //update data field for contact
            $client->updateContactDatafieldsByEmail($email, $data);
        }
    }

    public function getAbandonedProductName()
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ABANDONED_PRODUCT_NAME
        );
    }

    /**
     * Api request response time limit that should be logged.
     *
     * @param int $websiteId
     *
     * @return mixed
     */
    public function getApiResponseTimeLimit($websiteId = 0)
    {
        $website = $this->_storeManager->getWebsite($websiteId);
        $limit   = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DEBUG_API_REQUEST_LIMIT
        );

        return $limit;
    }

    /**
     * Main email for an account.
     *
     * @param int $website
     *
     * @return string
     */
    public function getAccountEmail($website = 0)
    {
        $client = $this->getWebsiteApiClient($website);
        $info   = $client->getAccountInfo();
        $email  = '';

        $properties = $info->properties;

        foreach ($properties as $property) {

            if ($property->name == 'MainEmail') {
                $email = $property->value;
            }
        }

        return $email;
    }

    /**
     * Check if mailcheck feature is enabled for current store
     *
     * @return mixed
     */
    public function isMailCheckEnabledForCurrentStore()
    {
        $store = $this->_storeManager->getStore();

        return (boolean)$this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_MAILCHECK_ENABLED,
            'store', $store
        );
    }

    public function getEmailCaptureUrl()
    {
        return $this->_storeManager->getStore()->getUrl(
            'connector/ajax/emailcapture'
        );
    }

    /**
     * Product review from config to link the product link.
     *
     * @param $website
     *
     * @return mixed
     */
    public function getReviewReminderAnchor($website)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_AUTOMATION_REVIEW_ANCHOR,
            $website
        );
    }


    /**
     * Dynamic styles from config.
     *
     * @return array
     */
    public function getDynamicStyles()
    {
        return $dynamicStyle = array(
            'nameStyle'          => explode(
                ',', $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_STYLE
            )
            ),
            'priceStyle'         => explode(
                ',', $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_STYLE
            )
            ),
            'linkStyle'          => explode(
                ',', $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_STYLE
            )
            ),
            'otherStyle'         => explode(
                ',', $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_STYLE
            )
            ),
            'nameColor'          => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_COLOR
            ),
            'fontSize'           => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_FONT_SIZE
            ),
            'priceColor'         => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_COLOR
            ),
            'priceFontSize'      => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_FONT_SIZE
            ),
            'urlColor'           => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_COLOR
            ),
            'urlFontSize'        => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_FONT_SIZE
            ),
            'otherColor'         => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_COLOR
            ),
            'otherFontSize'      => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_FONT_SIZE
            ),
            'docFont'            => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_FONT
            ),
            'docBackgroundColor' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_BG_COLOR
            ),
            'dynamicStyling'     => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_STYLING
            )
        );
    }


    public function getReviewDisplayType($website)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_REVIEW_DISPLAY_TYPE,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return boolean
     */
    public function isReviewReminderEnabled($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_ENABLED,
            $website
        );
    }

    /**
     * get config value on website level
     *
     * @param $path
     * @param $website
     *
     * @return mixed
     */
    public function getReviewWebsiteSettings($path, $website)
    {
        return $this->getWebsiteConfig($path, $website);
    }

    /**
     * @param $website
     *
     * @return string
     */
    public function getOrderStatus($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_STATUS,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return int
     */
    public function getDelay($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_DELAY,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return boolean
     */
    public function isNewProductOnly($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_NEW_PRODUCT,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return int
     */
    public function getCampaign($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_CAMPAIGN,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return string
     */
    public function getAnchor($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_ANCHOR,
            $website
        );
    }

    /**
     * @param $website
     *
     * @return string
     */
    public function getDisplayType($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_DISPLAY_TYPE,
            $website
        );
    }
}

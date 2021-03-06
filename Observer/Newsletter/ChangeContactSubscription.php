<?php


namespace Dotdigitalgroup\Email\Observer\Newsletter;


class ChangeContactSubscription
    implements \Magento\Framework\Event\ObserverInterface
{

    protected $_helper;
    protected $_registry;
    protected $_storeManager;
    protected $_contactFactory;
    protected $_subscriberFactory;
    protected $_automationFactory;
    protected $_proccessor;

    /**
     * ChangeContactSubscription constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory    $subscriberFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory    $contactFactory
     * @param \Magento\Framework\Registry                    $registry
     * @param \Dotdigitalgroup\Email\Helper\Data             $data
     * @param \Magento\Store\Model\StoreManagerInterface     $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Model\Proccessor        $proccessor
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Model\Proccessor $proccessor
    ) {
        $this->_automationFactory = $automationFactory;
        $this->_subscriberFactory = $subscriberFactory->create();
        $this->_contactFactory    = $contactFactory;
        $this->_helper            = $data;
        $this->_storeManager      = $storeManagerInterface;
        $this->_registry          = $registry;
        $this->_proccessor        = $proccessor;
    }


    /**
     * Change contact subscription status.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $subscriber       = $observer->getEvent()->getSubscriber();
        $email            = $subscriber->getEmail();
        $storeId          = $subscriber->getStoreId();
        $subscriberStatus = $subscriber->getSubscriberStatus();
        $websiteId
                          = $this->_storeManager->getStore($subscriber->getStoreId())
            ->getWebsiteId();
        //check if enabled
        if ( ! $this->_helper->isEnabled($websiteId)) {
            return $this;
        }

        try {
            $contactEmail = $this->_contactFactory->create()
                ->loadByCustomerEmail($email, $websiteId);
            // only for subsribers
            if ($subscriber->isSubscribed()) {

                //set contact as subscribed
                $contactEmail->setSubscriberStatus($subscriberStatus)
                    ->setIsSubscriber('1');
                $this->_proccessor->registerQueue(
                    \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED,
                    array('email' => $email),
                    \Dotdigitalgroup\Email\Model\Proccessor::MODE_SUBSCRIBER_RESUBSCRIBED,
                    $websiteId
                );

                // reset the subscriber as suppressed
                $contactEmail->setSuppressed(null);

                //not subscribed
            } else {
                //skip if contact is suppressed
                if ($contactEmail->getSuppressed()) {
                    return $this;
                }

                //update contact id for the subscriber
                $contactId = $contactEmail->getContactId();
                //get the contact id
                if ( ! $contactId) {
                    $this->_proccessor->registerQueue(
                        \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_SUBSCRIBER_UPDATE,
                        array(
                            'email' => $email,
                            'id'    => $contactEmail->getId()
                        ),
                        \Dotdigitalgroup\Email\Model\Proccessor::MODE_SUBSCRIBER_UPDATE,
                        $websiteId
                    );
                }
                $contactEmail->setIsSubscriber(null)
                    ->setSubscriberStatus(\Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED);
            }

            // fix for a multiple hit of the observer. stop adding the duplicates on the automation
            $emailReg = $this->_registry->registry($email . '_subscriber_save');
            if ($emailReg) {
                return $this;
            }
            $this->_registry->register($email . '_subscriber_save', $email);
            //add subscriber to automation
            $this->_addSubscriberToAutomation($email, $subscriber, $websiteId);

            //update the contact
            $contactEmail->setStoreId($storeId);

            //update contact
            $contactEmail->save();

        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }

        return $this;
    }


    private function _addSubscriberToAutomation($email, $subscriber, $websiteId)
    {

        $storeId = $subscriber->getStoreId();
        $store   = $this->_storeManager->getStore($storeId);
        $programId
                 = $this->_helper->getWebsiteConfig('connector_automation/visitor_automation/subscriber_automation',
            $websiteId);
        //not mapped ignore
        if ( ! $programId) {
            return;
        }
        try {
            //check the subscriber alredy exists
            $enrolment = $this->_automationFactory->create()
                ->getCollection()
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('automation_type',
                    \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER)
                ->addFieldToFilter('website_id', $websiteId)
                ->getFirstItem();

            //add new subscriber to automation
            if ( ! $enrolment->getId()) {
                //save subscriber to the queue
                $automation = $this->_automationFactory->create()
                    ->setEmail($email)
                    ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER)
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($subscriber->getId())
                    ->setWebsiteId($websiteId)
                    ->setStoreName($store->getName())
                    ->setProgramId($programId);
                $automation->save();
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
}

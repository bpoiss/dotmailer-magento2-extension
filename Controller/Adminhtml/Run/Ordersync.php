<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Ordersync extends \Magento\Backend\App\AbstractAction
{
	protected $messageManager;
	protected $_cronFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\CronFactory $cronFactory,
		\Magento\Backend\App\Action\Context $context
	)
	{
		$this->_cronFactory = $cronFactory;
		$this->messageManager = $context->getMessageManager();
		parent::__construct($context);

	}

	/**
	 * Refresh suppressed contacts.
	 */
	public function execute()
	{
		$result = $this->_cronFactory->create()
			->orderSync();

		$this->messageManager->addSuccess($result['message']);

		$redirectUrl = $this->getUrl('adminhtml/system_config/edit', array('section' => 'connector_developer_settings'));

		$this->_redirect($redirectUrl);
	}
}
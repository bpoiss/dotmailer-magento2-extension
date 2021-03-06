<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Connector;


class Ajaxvalidation extends \Magento\Backend\App\Action
{
	protected $data;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Backend\App\Action\Context $context)
	{
		$this->data = $data;
		parent::__construct($context);

	}
	/**
	 * Validate api user.
	 */
	public function execute()
	{
		$params = $this->getRequest()->getParams();
		$apiUsername     = $params['api_username'];
		$apiPassword     = base64_decode($params['api_password']);
		//validate api, check against account info.
		$client = $this->data->getWebsiteApiClient();
		$result = $client->validate($apiUsername, $apiPassword);

		$resonseData['success'] = true;
		//validation failed
		if (! $result) {
			$resonseData['success'] = false;
			$resonseData['message'] = 'Authorization has been denied for this request.';
		}

		$this->getResponse()->representJson(
			$this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonEncode($resonseData)
		);

	}

}

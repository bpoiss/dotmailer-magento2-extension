<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;


class Value extends \Magento\Backend\App\AbstractAction
{
	protected $_http;

	public function __construct(
		Context $context,
		\Magento\Framework\App\Response\Http $http
	)
	{
		parent::__construct($context);
		$this->_http = $http;
	}

	/**
	 * Check the permission to run it
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return $this->_authorization->isAllowed('Dotdigitalgroup_Email::exclusion_rules');
	}

	public function execute()
	{
		$valueName = $this->getRequest()->getParam('value');
		$conditionValue = $this->getRequest()->getParam('condValue');;
		$attributeValue = $this->getRequest()->getParam('attributeValue');

		if($valueName && $attributeValue && $conditionValue){
			if($conditionValue == 'null'){
				$valueOptions = $this->_objectManager->create(
					'Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value'
				)->getValueSelectOptions($attributeValue, true);
				$response['cvalue'] = $this->_getOptionHtml('cvalue', $valueName, $valueOptions);
			}
			else{
				$elmType = $this->_objectManager->create(
					'Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value'
				)->getValueElementType($attributeValue);
				if($elmType == 'select'){
					$valueOptions = $this->_objectManager->create(
						'Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value'
					)->getValueSelectOptions($attributeValue);
					$response['cvalue'] = $this->_getOptionHtml('cvalue', $valueName, $valueOptions);
				}elseif($elmType == 'text'){
					$html = "<input style='width:160px' title='cvalue' class='' id='' name=$valueName />";
					$response['cvalue'] = $html;
				}
			}
			$this->_http->getHeaders()->clearHeaders();
			$this->_http->setHeader('Content-Type', 'application/json')->setBody(
				$this->_objectManager->create('Magento\Framework\Json\Encoder')->encode($response)
			);
		}
	}

	protected function _getOptionHtml($title, $name, $options)
	{
		$block = $this->_view->getLayout()->createBlock('Magento\Framework\View\Element\Html\Select');
		$block->setOptions($options)
			->setId('')
			->setClass('')
			->setTitle($title)
			->setName($name)
			->setExtraParams('style="width:160px"');
		return $block->toHtml();
	}
}

<?php
namespace Dotdigitalgroup\Email\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class Campaign extends Action
{

	/**
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return $this->_authorization->isAllowed('Dotdigitalgroup_Email::campaign');
	}
}
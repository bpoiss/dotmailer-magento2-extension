<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Importer extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Block constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_importer';
        $this->_headerText = __('Importer');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}


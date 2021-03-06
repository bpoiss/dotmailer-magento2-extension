<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Review extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_buttonLabel = 'Contact Report';


    public function setButtonLabel($buttonLabel)
    {
        $this->_buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * Set template to itself
     *
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ( ! $this->getTemplate()) {
            $this->setTemplate('system/config/reportlink.phtml');
        }

        return $this;
    }

    public function getLink()
    {
        return $this->getUrl(
            'dotdigitalgroup_email/review/index'
        );
    }

    /**
     * Unset some non-related element parameters
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $originalData = $element->getOriginalData();
        $buttonLabel  = ! empty($originalData['button_label'])
            ? $originalData['button_label'] : $this->_buttonLabel;
        $url
                      = $this->_urlBuilder->getUrl('dotdigitalgroup_email/addressbook/save');
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id'      => $element->getHtmlId(),
                'ajax_url'     => $url,
            ]
        );

        return $this->_toHtml();
    }


}

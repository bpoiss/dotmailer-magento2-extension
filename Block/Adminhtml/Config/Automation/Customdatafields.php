<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation;

class Customdatafields extends
    \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{

    protected $_statusRenderer;
    protected $_automationRenderer;
    protected $_programFactory;
    protected $_elementFactory;

    /**
     * Customdatafields constructor.
     *
     * @param \Magento\Framework\Data\Form\Element\Factory                         $elementFactory
     * @param \Dotdigitalgroup\Email\Model\Config\Source\Automation\ProgramFactory $programFactory
     * @param \Magento\Backend\Block\Template\Context                              $context
     * @param array                                                                $data
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Dotdigitalgroup\Email\Model\Config\Source\Automation\ProgramFactory $programFactory,
        \Magento\Backend\Block\Template\Context $context,
        $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        $this->_programFactory = $programFactory->create();
        parent::__construct($context, $data);
    }

    protected function _prepareToRender()
    {
        $this->_getStatusRenderer     = null;
        $this->_getAutomationRenderer = null;
        $this->addColumn(
            'status',
            array(
                'label' => __('Order Status'),
                'style' => 'width:120px',
            )
        );
        $this->addColumn(
            'automation', array(
                'label' => __('Automation Program'),
                'style' => 'width:120px',
            )
        );
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add New Enrolment');

    }

    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'status' && isset($this->_columns[$columnName])) {

            $options = $this->getElement()->getValues();
            $element = $this->_elementFactory->create('select');
            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $options
            );

            return str_replace("\n", '', $element->getElementHtml());
        }
        if ($columnName == 'automation'
            && isset($this->_columns[$columnName])
        ) {

            $options = $this->_programFactory->toOptionArray();
            $element = $this->_elementFactory->create('select');
            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $options
            );

            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * @param \Magento\Framework\DataObject $row
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->_getStatusRenderer()
            ->calcOptionHash($row->getData('status'))]
                         = 'selected="selected"';
        $optionExtraAttr['option_' . $this->_getAutomationRenderer()
            ->calcOptionHash($row->getData('automation'))]
                         = 'selected="selected"';
        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }

    protected function _getStatusRenderer()
    {
        $this->_statusRenderer = $this->getLayout()->createBlock(
            'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );

        return $this->_statusRenderer;
    }

    protected function _getAutomationRenderer()
    {
        $this->_automationRenderer = $this->getLayout()->createBlock(
            'Dotdigitalgroup\Email\Block\Adminhtml\Config\Select',
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );

        return $this->_automationRenderer;
    }

    public function _toHtml()
    {
        return '<input type="hidden" id="' . $this->getElement()->getHtmlId()
        . '"/>' . parent::_toHtml();

    }
}

<?php

namespace Dotdigitalgroup\Email\Block;
class Coupon extends \Magento\Framework\View\Element\Template
{

    public $helper;
    protected $_ruleFactory;
    protected $_massGeneratorFactory;
    protected $_couponFactory;


    /**
     * Coupon constructor.
     *
     * @param \Magento\SalesRule\Model\Coupon\MassgeneratorFactory $massgeneratorFactory
     * @param \Magento\Salesrule\Model\CouponFactory               $couponFactory
     * @param \Magento\Framework\View\Element\Template\Context     $context
     * @param \Dotdigitalgroup\Email\Helper\Data                   $helper
     * @param \Magento\Salesrule\Model\RuleFactory                 $ruleFactory
     * @param array                                                $data
     */
    public function __construct(

        \Magento\SalesRule\Model\Coupon\MassgeneratorFactory $massgeneratorFactory,
        \Magento\Salesrule\Model\CouponFactory $couponFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Salesrule\Model\RuleFactory $ruleFactory,
        array $data = []
    ) {
        $this->helper                = $helper;
        $this->_ruleFactory          = $ruleFactory;
        $this->_couponFactory        = $couponFactory;
        $this->_massGeneratorFactory = $massgeneratorFactory;
        $this->storeManager          = $this->_storeManager;

        parent::__construct($context, $data);
    }

    /**
     * Generates the coupon code based on the code id.
     *
     * @return bool
     */
    public function generateCoupon()
    {
        $params = $this->getRequest()->getParams();
        //check for param code and id
        if ( ! isset($params['id']) || ! isset($params['code'])) {
            $this->helper->log('Coupon no id or code is set');

            return false;
        }
        //coupon rule id
        $couponCodeId = $params['id'];

        if ($couponCodeId) {

            $rule      = $this->_ruleFactory->create()
                ->load($couponCodeId);
            $generator = $this->_massGeneratorFactory->create();
            $generator->setFormat(
                \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC
            );
            $generator->setRuleId($couponCodeId);
            $generator->setUsesPerCoupon(1);
            $generator->setDash(3);
            $generator->setLength(9);
            $generator->setPrefix('');
            $generator->setSuffix('');
            //set the generation settings
            $rule->setCouponCodeGenerator($generator);
            $rule->setCouponType(
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
            );
            //generate the coupon
            $coupon     = $rule->acquireCoupon();
            $couponCode = $coupon->getCode();
            //save the type of coupon

            $couponModel = $this->_couponFactory->create()
                ->loadByCode($couponCode);
            $couponModel->setType(
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
            );
            $couponModel->save();

            return $couponCode;
        }

        return false;
    }


    public function getStyle()
    {
        return explode(
            ',', $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_STYLE
        )
        );
    }

    public function getCouponColor()
    {
        return $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_COLOR
        );
    }

    public function getFontSize()
    {
        return $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT_SIZE
        );
    }

    public function getFont()
    {
        return $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT
        );
    }

    public function getBackgroundColor()
    {
        return $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_BG_COLOR
        );
    }
}
<?php

namespace Dotdigitalgroup\Email\Model\Resource;

use Magento\Framework\Stdlib\DateTime as LibDateTime;

class Review extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('email_review', 'id');
    }


    /**
     * Reset the email reviews for reimport.
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetReviews()
    {
        $conn = $this->getConnection();
        try {
            $num = $conn->update($conn->getTableName('email_review'),
                array('review_imported' => new \Zend_Db_Expr('null')),
                $conn->quoteInto('review_imported is ?',
                    new \Zend_Db_Expr('not null'))
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }
}
<?php

namespace Plugin\SimpleNemPay\Entity;

class NemHistory extends \Eccube\Entity\AbstractEntity
{

    /**
     * @var integer
     */
    private $id;

    /**
     * @var NemOrder
     */
    private $NemOrder;

    /**
     * @var string
     */
    private $transaction_id;

    /**
     * @var float
     */
    private $amount;

    /**
     * Set id
     *
     * @param integer $id
     * @return NemHistory
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set NemOrder
     *
     * @param  NemOrder $NemOrder
     * @return NemHistory
     */
    public function setNemOrder(\Plugin\SimpleNemPay\Entity\NemOrder $NemOrder)
    {
        $this->NemOrder = $NemOrder;

        return $this;
    }

    /**
     * Get NemOrder
     *
     * @return NemOrder
     */
    public function getNemOrder()
    {
        return $this->NemOrder;
    }

    /**
     * Set transaction_id
     *
     * @param  string transactionId
     * @return NemHistory
     */
    public function setTransactionId($transactionId)
    {
        $this->transaction_id = $transactionId;

        return $this;
    }

    /**
     * Get transaction_id
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }
    /**
     * Set amount
     *
     * @param  integer $amount
     * @return NemHistory
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

}

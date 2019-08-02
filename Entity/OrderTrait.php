<?php

namespace Plugin\SimpleNemPay\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @var float
     * 
     * @ORM\Column(name="rate", type="decimal", precision=12, scale=2)
     */
    private $rate;

    /**
     * @var float
     * 
     * @ORM\Column(name="payment_amount", type="decimal", precision=12, scale=2)
     */
    private $payment_amount;

    /**
     * @var float
     * 
     * @ORM\Column(name="remittance_amount", type="decimal", precision=12, scale=2)
     */
    private $remittance_amount;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="Plugin\SimpleNemPay\Entity\SimpleNemHistory", mappedBy="Order", cascade={"persist", "remove"})
     */
    private $NemHistoryes;

    /**
     * {@inheritdoc}
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentAmount($paymentAmount)
    {
        $this->payment_amount = $paymentAmount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAmount()
    {
        return $this->payment_amount;
    }

    /**
     * {@inheritdoc}
     */
    public function setRemittanceAmount($remittanceAmount)
    {
        $this->remittance_amount = $remittanceAmount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRemittanceAmount()
    {
        return $this->remittance_amount;
    }

    /**
     * {@inheritdoc}
     */
    public function addSimpleNemHistory(\Plugin\SimpleNemPay\Entity\SimpleNemHistory $SimpleNemHistory)
    {
        $this->SimpleNemHistoryes[] = $SimpleNemHistory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSimpleNemHistoryes()
    {
        return $this->SimpleNemHistoryes;
    }
}

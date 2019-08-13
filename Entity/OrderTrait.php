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
     * @ORM\Column(name="nem_rate", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $nem_rate;

    /**
     * @var float
     * 
     * @ORM\Column(name="nem_payment_amount", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $nem_payment_amount;

    /**
     * @var float
     * 
     * @ORM\Column(name="nem_remittance_amount", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $nem_remittance_amount;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="Plugin\SimpleNemPay\Entity\SimpleNemHistory", mappedBy="Order", cascade={"persist", "remove"})
     */
    private $NemHistoryes;

    /**
     * {@inheritdoc}
     */
    public function setNemRate($nemRate)
    {
        $this->nem_rate = $nemRate;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNemRate()
    {
        return $this->nem_rate;
    }

    /**
     * {@inheritdoc}
     */
    public function setNemPaymentAmount($nemPaymentAmount)
    {
        $this->nem_payment_amount = $nemPaymentAmount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNemPaymentAmount()
    {
        return $this->nem_payment_amount;
    }

    /**
     * {@inheritdoc}
     */
    public function setNemRemittanceAmount($nemRemittanceAmount)
    {
        $this->nem_remittance_amount = $nemRemittanceAmount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNemRemittanceAmount()
    {
        return $this->nem_remittance_amount;
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

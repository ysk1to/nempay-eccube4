<?php

namespace Plugin\SimpleNemPay\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

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
     * @var NemStatus
     * @ORM\ManyToOne(targetEntity="Plugin\SimpleNemPay\Entity\Master\NemStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="nem_status_id", referencedColumnName="id")
     * })
     */
    private $NemStatus;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="Plugin\SimpleNemPay\Entity\NemHistory", mappedBy="Order", cascade={"persist", "remove"})
     */
    private $NemHistories;

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
    public function setNemStatus(\Plugin\SimpleNemPay\Entity\Master\NemStatus $NemStatus)
    {
        $this->NemStatus = $NemStatus;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNemStatus()
    {
        return $this->NemStatus;
    }

    /**
     * {@inheritdoc}
     */
    public function addNemHistory(\Plugin\SimpleNemPay\Entity\NemHistory $NemHistory)
    {
        $this->NemHistories[] = $NemHistory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNemHistories()
    {
        return $this->NemHistories;
    }
}

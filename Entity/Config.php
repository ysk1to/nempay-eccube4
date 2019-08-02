<?php

namespace Plugin\SimpleNemPay\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * Config
 * 
 * @ORM\Table(name="plg_simple_nem_pay_config")
 * @ORM\Entity(repositoryClass="Plugin\SimpleNemPay\Repository\ConfigRepository")
 */
class Config extends AbstractEntity
{

    /**
     * @var int
     * 
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     * 
     * @ORM\Column(name="env", type="integer")
     */
    private $env;

    /**
     * @var string
     * 
     * @ORM\Column(name="seller_nem_addr", type="string", length=255, nullable=true)
     */
    private $seller_nem_addr;

    /**
     * Constructor
     */
    public function __construct()
    { }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSellerNemAddr()
    {
        return $this->seller_nem_addr;
    }

    /**
     * {@inheritdoc}
     */
    public function setSellerNemAddr($seller_nem_addr)
    {
        $this->seller_nem_addr = $seller_nem_addr;

        return $this;
    }
}

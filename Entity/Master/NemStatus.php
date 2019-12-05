<?php

namespace Plugin\SimpleNemPay\Entity\Master;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * NemStatus
 * 
 * @ORM\Table(name="plg_simple_nem_pay_status")
 * @ORM\Entity(repositoryClass="Plugin\SimpleNemPay\Repository\Master\NemStatusRepository")
 */
class NemStatus extends AbstractMasterEntity
{
    /**
     * 送金待ち
     */
    const PAY_WAITING = 1;

    /**
     * 送金済み
     */
    const PAY_DONE = 2;
}

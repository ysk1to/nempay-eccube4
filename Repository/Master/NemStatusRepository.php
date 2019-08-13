<?php

namespace Plugin\SimpleNemPay\Repository\Master;

use Eccube\Repository\AbstractRepository;
use Plugin\SimpleNemPay\Entity\Master\NemStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

class NemStatusRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, NemStatus::class);
    }
}

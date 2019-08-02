<?php

namespace Plugin\SimpleNemPay\Repository\Master;

use Eccube\Repository\AbstractRepository;
use Plugin\SimpleNemPay\Entity\Master\SimpleNemStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SimpleNemStatusRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SimpleNemStatus::class);
    }
}

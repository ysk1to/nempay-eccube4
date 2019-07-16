<?php

namespace Plugin\SimpleNemPay;

use Eccube\Event\TemplateEvent;
use Eccube\Common\EccubeConfig;
use Plugin\SimpleNemPay\Repository\ConfigRepository;
use Plugin\SimpleNemPay\Service\Method\SimpleNemPay;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SimpleNemPayEvent implements EventSubscriberInterface
{

    /**
     * SimpleNemPayEvent
     * 
     * @param eccubeConfig $eccubeConfig
     * @param ConfigRepository $configRepository
     */
    public function __construct()
    { }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [];
    }
}

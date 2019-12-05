<?php

namespace Plugin\SimpleNemPay;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Event\TemplateEvent;
use Eccube\Common\EccubeConfig;
use Plugin\SimpleNemPay\Repository\ConfigRepository;
use Plugin\SimpleNemPay\Service\Method\SimpleNemPay;
use Plugin\SimpleNemPay\Service\NemRequestService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SimpleNemPayEvent implements EventSubscriberInterface
{

    /**
     * SimpleNemPayEvent
     *
     * @param EntityManagerInterface $entityManager
     * @param NemRequestService $nemRequestService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        NemRequestService $nemRequestService
    )
    {
        $this->entityManager = $entityManager;
        $this->nemRequestService = $nemRequestService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'index',
            'Shopping/confirm.twig' => 'confirm',
        ];
    }

    public function index(TemplateEvent $event)
    {
        $Order = $event->getParameter('Order');

        if (strpos(SimpleNemPay::class, $Order->getPayment()->getMethodClass()) !== false) {
            $parameters = $event->getParameters();

            $nem_rate = $this->nemRequestService->getRate();
            $nem_payment_amount = round($Order->getPaymentTotal() / $nem_rate, 3);

            $parameters['nem_rate'] = $nem_rate;
            $parameters['nem_payment_amount'] = $nem_payment_amount;
            
            $event->setParameters($parameters);
            $event->addSnippet('@SimpleNemPay/default/Shopping/simple_nem_pay_info.twig');

            $Order->setNemRate($nem_rate);
            $Order->setNemPaymentAmount($nem_payment_amount);
            $this->entityManager->flush($Order);
        }
    }

    public function confirm(TemplateEvent $event)
    {
        $Order = $event->getParameter('Order');

        if (strpos(SimpleNemPay::class, $Order->getPayment()->getMethodClass()) !== false) {
            $parameters = $event->getParameters();

            $parameters['nem_rate'] = $Order->getNemRate();
            $parameters['nem_payment_amount'] = $Order->getNemPaymentAmount();

            $event->setParameters($parameters);
            $event->addSnippet('@SimpleNemPay/default/Shopping/simple_nem_pay_info.twig');
        }
    }
}

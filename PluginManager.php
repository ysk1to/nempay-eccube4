<?php

namespace Plugin\SimpleNemPay;

use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Payment;
use Eccube\Entity\PaymentOption;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\DeliveryRepository;
use Plugin\SimpleNemPay\Entity\Config;
use Plugin\SimpleNemPay\Service\Method\SimpleNemPay;

class PluginManager extends AbstractPluginManager
{
    /**
     * PluginManager constructor.
     */
    public function __construct()
    { }

    public function install(array $config, ContainerInterface $container)
    { }

    public function enable(array $config, ContainerInterface $container)
    {
        $this->createSimpleNemPay($container);

        $this->createPlgSimpleNemPayConfig($container);
        $this->createPlgSimpleNemPayStatus($container);
    }

    public function uninstall(array $config, ContainerInterface $container)
    { }

    public function disable(array $config, ContainerInterface $container)
    {
        $this->disableSimpleNemPay($container);
    }

    private function createSimpleNemPay(ContainerInterface $container)
    {
        $entityManage = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $container->get(PaymentRepository::class);

        $Payment = $paymentRepository->findOneBy(['method_class' => SimpleNemPay::class]);
        if ($Payment) {
            $Payment->setVisible(true);
            $entityManage->flush($Payment);

            return;
        }

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        // かんたんNEM決済
        $Payment = new Payment();
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod('かんたんNEM決済');
        $Payment->setMethodClass(SimpleNemPay::class);
        $Payment->setRuleMin(0);

        $entityManage->persist($Payment);
        $entityManage->flush($Payment);

        // 各配送方法に登録
        $deliveryRepository = $container->get(DeliveryRepository::class);
        $Deliveries = $deliveryRepository->findAll();
        foreach ($Deliveries as $Delivery) {
            $PaymentOption = new PaymentOption();
            $PaymentOption->setDelivery($Delivery);
            $PaymentOption->setDeliveryId($Delivery->getId());
            $PaymentOption->setPayment($Payment);
            $PaymentOption->setPaymentId($Payment->getId());

            $entityManage->persist($PaymentOption);
            $entityManage->flush($PaymentOption);
        }
    }

    private function disableSimpleNemPay(ContainerInterface $container)
    {
        $entityManage = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $container->get(PaymentRepository::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => SimpleNemPay::class]);
        if ($Payment) {
            $Payment->setVisible(false);
            $entityManage->flush($Payment);
        }
    }

    /**
     * create table plg_SimpleNem_pay_config
     *
     * @param ContainerInterface $container
     */
    public function createPlgSimpleNemPayConfig(ContainerInterface $container)
    {
        $entityManage = $container->get('doctrine.orm.entity_manager');
        $Config = $entityManage->find(Config::class, 1);
        if ($Config) {
            return;
        }

        // プラグイン情報初期セット
        // 動作設定
        $Config = new Config();
        $Config->setEnv(1);                      // テスト環境

        $entityManage->persist($Config);
        $entityManage->flush($Config);
    }

    public function createPlgSimpleNemPayStatus(ContainerInterface $container)
    {
        $entityManage = $container->get('doctrine.orm.entity_manager');

        $statuses = [
            1 => '送金待ち',
            2 => '送金済み',
        ];

        $i = 0;
        foreach ($statuses as $id => $name) {
            $SimpleNemStatus = $entityManage->find(SimpleNemStatus::class, $id);
            if ($SimpleNemStatus) {
                continue;
            }

            $SimpleNemStatus = new SimpleNemStatus();

            $SimpleNemStatus->setId($id);
            $SimpleNemStatus->setName($name);
            $SimpleNemStatus->setSortNo($i++);

            $entityManage->persist($SimpleNemStatus);
            $entityManage->flush($SimpleNemStatus);
        }
    }
}

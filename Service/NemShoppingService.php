<?php

namespace Plugin\SimpleNemPay\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Plugin\SimpleNemPay\Entity\Master\NemStatus;
use Plugin\SimpleNemPay\Entity\NemHistory;
use Plugin\SimpleNemPay\Repository\ConfigRepository;
use Plugin\SimpleNemPay\Repository\Master\NemStatusRepository;
use Plugin\SimpleNemPay\Service\NemRequestService;

class NemShoppingService
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param \Swift_Mailer $mailer
     * @param ConfigRepository $configRepository
     * @param EccubeConfig $eccubeConfig
     * @param NemStatusRepository $nemStatusRepository
     * @param \Plugin\SimpleNemPay\Service\NemRequestService $nemRequestService
     * @param BaseInfoRepository $baseInfoRepository
     * @param OrderStatusRepository $orderStatusRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        \Swift_Mailer $mailer,
        ConfigRepository $configRepository,
        EccubeConfig $eccubeConfig,
        NemStatusRepository $nemStatusRepository,
        NemRequestService $nemRequestService,
        BaseInfoRepository $baseInfoRepository,
        OrderStatusRepository $orderStatusRepository
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->Config = $configRepository->get();
        $this->auth_magic = $eccubeConfig->get('eccube_auth_magic');

        $this->nemStatusRepository = $nemStatusRepository;
        $this->nemRequestService = $nemRequestService;
        $this->baseInfoRepository = $baseInfoRepository;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    public function confirmNemRemittance($Order)
    {
        $isUpdate = false;

        // キーを変換
        $arrNemOrderTemp = [];
        $shortHash = $this->getShortHash($Order);
        $arrNemOrderTemp[$shortHash] = $Order;
        $arrNemOrder = $arrNemOrderTemp;

        // NEM受信トランザクション取得
        $arrData = $this->nemRequestService->getIncommingTransaction();
        foreach ($arrData as $data) {
            if (isset($data->transaction->otherTrans)) {
                $trans = $data->transaction->otherTrans;
            } else {
                $trans = $data->transaction;
            }

            if (empty($trans->message->payload)) {
                continue;
            }

            $msg = pack("H*", $trans->message->payload);

            // 対象受注
            if (isset($arrNemOrder[$msg])) {
                $Order = $arrNemOrder[$msg];

                // トランザクションチェック
                $transaction_id = $data->meta->id;
                $NemHistoryes = $Order->getNemHistories();
                if (!empty($NemHistoryes)) {
                    $exist_flg = false;
                    foreach ($NemHistoryes as $NemHistory) {
                        if ($NemHistory->getTransactionId() == $transaction_id) {
                            $exist_flg = true;
                        }
                    }

                    if ($exist_flg) {
                        logs('simple_nem_pay')->info("batch error: processed transaction. transaction_id = " . $transaction_id);
                        continue;
                    }
                }

                // トランザクション制御
                $this->entityManager->beginTransaction();

                $order_id = $Order->getId();
                $amount = $trans->amount / 1000000;
                $payment_amount = $Order->getNemPaymentAmount();
                $remittance_amount = $Order->getNemRemittanceAmount();

                $pre_amount = empty($remittance_amount) ? 0 : $remittance_amount;
                $remittance_amount = $pre_amount + $amount;
                $Order->setNemRemittanceAmount($remittance_amount);

                $NemHistory = new NemHistory();
                $NemHistory->setTransactionId($transaction_id);
                $NemHistory->setAmount($amount);
                $NemHistory->setOrder($Order);

                logs('simple_nem_pay')->info("received. order_id = " . $order_id . " amount = " . $amount);

                if ($payment_amount <= $remittance_amount) {
                    $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
                    $Order->setOrderStatus($OrderStatus);
                    $Order->setPaymentDate(new \DateTime());

                    $NemStatus = $this->nemStatusRepository->find(NemStatus::PAY_DONE);
                    $Order->setNemStatus($NemStatus);

                    $this->sendPayEndMail($Order);
                    logs('simple_nem_pay')->info("pay end. order_id = " . $order_id);
                }

                $isUpdate = true;

                // 更新
                $this->entityManager->persist($NemHistory);
                $this->entityManager->flush();
                $this->entityManager->commit();
            }
        }

        return $isUpdate;
    }

    public function sendPayEndMail($Order)
    {
        $BaseInfo = $this->baseInfoRepository->get();

        $order_id = $Order->getId();
        $name01 = $Order->getName01();
        $name02 = $Order->getName02();
        $payment_total = $Order->getPaymentTotal();
        $payment_amount = $Order->getNemPaymentAmount();

        $body = <<< __EOS__
{$name01} {$name02} 様

この度はご注文いただき誠にありがとうございます。 
下記かんたんNEM決済の送金を確認致しました。

************************************************ 
　ご注文情報 
************************************************ 

ご注文番号：{$order_id}
お支払い合計：¥ {$payment_total} ({$payment_amount} XEM)

============================================ 


このメッセージはお客様へのお知らせ専用ですので、 
このメッセージへの返信としてご質問をお送りいただいても回答できません。 
ご了承ください。 

ご質問やご不明な点がございましたら、こちらからお願いいたします。
__EOS__;

        $message = new \Swift_Message();
        $message 
            ->setSubject('【' . $BaseInfo->getShopName() . '】かんたんNEM決済 送金確認通知')
            ->setFrom(array($BaseInfo->getEmail03() => $BaseInfo->getShopName()))
            ->setTo(array($Order->getEmail()))
            ->setBcc(array($BaseInfo->getEmail03() => $BaseInfo->getShopName()))
            ->setBody($body);

        $count = $this->mailer->send($message);
    }
    
    function getQrcodeImagePath(Order $Order) {
        return  __DIR__ . '/../Resource/qrcode/'. $Order->getId() . '.png';
    }
    
    function getShortHash(Order $Order) {
        return rtrim(base64_encode(md5($this->Config->getSellerNemAddr() . $Order->getId() . $this->auth_magic, true)), '=');  
    }

}

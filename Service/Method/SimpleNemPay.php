<?php

namespace Plugin\SimpleNemPay\Service\Method;

use Eccube\Entity\Order;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\SimpleNemPay\Repository\ConfigRepository;
use Plugin\SimpleNemPay\Entity\Master\NemStatus;
use Plugin\SimpleNemPay\Repository\Master\NemStatusRepository;
use Plugin\SimpleNemPay\Service\NemShoppingService;
use Symfony\Component\Form\FormInterface;
use Endroid\QrCode\QrCode;

class SimpleNemPay implements PaymentMethodInterface
{
    /**
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param ConfigRepository $configRepository
     * @param NemStatusRepository $nemStatusRepository
     * @param NemShoppingService $nemShoppingService
     */
    public function __construct(
            PurchaseFlow $shoppingPurchaseFlow,
            ConfigRepository $configRepository,
            NemStatusRepository $nemStatusRepository,
            NemShoppingService $nemShoppingService
    ) {
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->Config = $configRepository->get();
        $this->nemStatusRepository = $nemStatusRepository;
        $this->nemShoppingService = $nemShoppingService;
    }

    /**
     * {@inheritdoc}
     */  
    public function verify()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        return false;
    }

    /**
     * {@inheritdoc}
     */ 
    public function checkout()
    {
        $NemStatus = $this->nemStatusRepository->find(NemStatus::PAY_WAITING);
        $this->Order->setNemStatus($NemStatus);

        $msg = $this->nemShoppingService->getShortHash($this->Order);
        $pay_info = $this->getPaymentInfo($msg);

        $this->setOrderCompleteMessages($pay_info, $msg);

        $this->purchaseFlow->commit($this->Order, new PurchaseContext());

        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    private function getPaymentInfo($msg)
    {
        
        $amount = $this->Order->getNemPaymentAmount();
        $sellerNemAddr = $this->Config->getSellerNemAddr();

        $pay_info = <<< __EOS__
かんたんNEM決済についてのご連絡

【お支払いについてのご説明】
お客様の注文はまだ決済が完了していません。
お支払い情報に記載されている支払い先アドレスに指定の金額とメッセージを送信してください。
送金から一定時間経過後、本サイトに反映され決済が完了します。

※NanoWalletでQRコードをスキャンするとお支払い情報が読み込まれます。
※メッセージが誤っている場合、注文に反映されませんのご注意ください。
※送金金額が受付時の金額に満たない場合、決済は完了されません。複数回送金された場合は合算した金額で判定されます。

【お支払い情報】
支払先アドレス : {$sellerNemAddr}
お支払い金額 : {$amount} XEM
メッセージ : {$msg}
__EOS__;

        return $pay_info;
    }

    /**
     * 決済情報を受注完了メッセージにセット
     * @param string pay_info
     */
    private function setOrderCompleteMessages($pay_info, $msg)
    {
        $complete_mail_message = <<<__EOS__
************************************************
　NEM決済情報
************************************************
{$pay_info}
__EOS__;

        $pay_info = nl2br($pay_info, false);

        // QRコード作成
        $data = [
            'v' => 2,
            'type' => 2,
            'data' =>
            [
                'addr' => $this->Config->getSellerNemAddr(),
                'amount' => $this->Order->getNemPaymentAmount() * 1000000,
                'msg' => $msg,
                'name' => '',
            ],
        ];
        $qrCode = new QrCode(json_encode($data));
        $qrCode->setSize(300);
        $img = base64_encode($qrCode->writeString());

        $complete_message = <<<__EOS__
<div class="ec-rectHeading">
    <h2>■NEM決済情報</h2>
</div>
<p style="text-align:left; word-wrap: break-word; white-space: normal;">{$pay_info}</p>
<img src="data:image/gif;base64,{$img}" />
__EOS__;

        // 注文完了メールにメッセージを追加
        $this->Order->appendCompleteMailMessage($complete_mail_message);
        // 注文完了画面にメッセージを追加
        $this->Order->appendCompleteMessage($complete_message);
    }

    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
}

<?php

namespace Plugin\SimpleNemPay\Service;

use Plugin\SimpleNemPay\Entity\NemOrder;
use Plugin\SimpleNemPay\Entity\NemHistory;
use Plugin\SimpleNemPay\Repository\ConfigRepository;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\MailHistory;
use Eccube\Entity\Order;

class NemShoppingService
{
    /**
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
        ConfigRepository $configRepository,
        EccubeConfig $eccubeConfig
    ) {
        $this->Config = $configRepository->get();
        $this->auth_magic = $eccubeConfig->get('eccube_auth_magic');
    }

    /**
     * 受注メール送信を行う
     *
     * @param Order $Order
     * @return MailHistory
     */
    public function sendOrderMail(Order $Order)
    {
        // メール送信
        $message = $this->app['eccube.plugin.simple_nempay.service.nem_mail']->sendOrderMail($Order);

        // 送信履歴を保存.
        $MailTemplate = $this->app['eccube.repository.mail_template']->find(1);

        $MailHistory = new MailHistory();
        $MailHistory
            ->setSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setMailTemplate($MailTemplate)
            ->setSendDate(new \DateTime())
            ->setOrder($Order);

        $this->app['orm.em']->persist($MailHistory);
        $this->app['orm.em']->flush($MailHistory);

        return $MailHistory;

    }

    /**
     * Nemの受注情報を登録
     *
     * @param Order $Order
     */
    public function getNemOrder(Order $Order)
    {
        $NemOrder = $this->app['eccube.plugin.simple_nempay.repository.nem_order']->findOneBy(array('Order' => $Order));
        
        if (empty($NemOrder)) {
            // Nem受注情報を登録
            $NemOrder = new NemOrder();
            $NemOrder->setOrder($Order);
            
            $this->app['orm.em']->persist($NemOrder);
            $this->app['orm.em']->flush();
        }

        return $NemOrder;
    }
    

    
    function createQrcodeImage(Order $Order, $NemOrder, $msg) {
        $amount = $NemOrder->getPaymentAmount();
        
        $arrData = array(
            'v' => 2,
            'type' => 2,
            'data' => 
                array (
                    'addr' => $this->nemSettings['seller_addr'],
                    'amount' => $amount * 1000000,
                    'msg' => $msg,
                    'name' => '',
            ),
        );
        
        $filepath = $this->getQrcodeImagePath($Order);

        // $qr = new \Image_QRCode();
        // $image = $qr->makeCode(json_encode($arrData), 
        //                        array('output_type' => 'return'));
        // imagepng($image, $filepath);
        // imagedestroy($image);
    }
    
    function confirmNemRemittance($arrNemOrder) {
        $arrUpdateOrderId = array();
              
        // キーを変換
        $arrNemOrderTemp = array();
        foreach ($arrNemOrder as $NemOrder) {
            $shortHash = $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->getShortHash($NemOrder->getOrder());
            $arrNemOrderTemp[$shortHash] = $NemOrder;
        }
        $arrNemOrder = $arrNemOrderTemp;
        
        // NEM受信トランザクション取得
        $arrData = $this->app['eccube.plugin.simple_nempay.service.nem_request']->getIncommingTransaction();
		foreach ($arrData as $data) {
            if (isset($data['transaction']['otherTrans'])) {
                $trans = $data['transaction']['otherTrans'];
            } else {
                $trans = $data['transaction'];
            }
            
            if (empty($trans['message']['payload'])) {
                continue;
            }
            
            $msg = pack("H*", $trans['message']['payload']);
            
            // 対象受注
            if (isset($arrNemOrder[$msg])) {
                $NemOrder = $arrNemOrder[$msg];
                $Order = $NemOrder->getOrder();
                
                // トランザクションチェック
                $transaction_id = $data['meta']['id'];
                $NemHistoryes = $NemOrder->getNemHistoryes();
                if (!empty($NemHistoryes)) {
                    $exist_flg = false;
                    foreach ($NemHistoryes as $NemHistory) {
                        if ($NemHistory->getTransactionId() == $transaction_id) {
                            $exist_flg = true;
                        }
                    }
                    
                    if ($exist_flg) {
						$this->app['monolog.simple_nempay']->addInfo("batch error: processed transaction. transaction_id = " . $transaction_id);
                        continue;
                    }       
                }
                
                // トランザクション制御
                $em = $this->app['orm.em'];
                $em->getConnection()->beginTransaction();
                
                $order_id = $Order->getId();
                $amount = $trans['amount'] / 1000000;
                $payment_amount = $NemOrder->getPaymentAmount();
                $remittance_amount = $NemOrder->getRemittanceAmount();
                
                $pre_amount = empty($remittance_amount) ? 0 : $remittance_amount;
                $remittance_amount = $pre_amount + $amount;
                $NemOrder->setRemittanceAmount($remittance_amount);
                
                $NemHistory = new NemHistory();
                $NemHistory->setTransactionId($transaction_id);
                $NemHistory->setAmount($amount);
                $NemHistory->setNemOrder($NemOrder);

				$this->app['monolog.simple_nempay']->addInfo("received. order_id = " . $order_id . " amount = " . $amount);

                if ($payment_amount <= $remittance_amount) {
                    $OrderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_pre_end']);
                    $Order->setOrderStatus($OrderStatus);
                    $Order->setPaymentDate(new \DateTime());
					
					$this->sendPayEndMail($NemOrder);
					$this->app['monolog.simple_nempay']->addInfo("pay end. order_id = " . $order_id);
                }
                
                $arrUpdateOrderId[] = $order_id;
                
                // 更新
                $em->persist($NemHistory);
                $em->commit();
                $em->flush();				
            }
            
		}
        
        return $arrUpdateOrderId;
    }
    
    public function sendPayEndMail($NemOrder)
    {
        $BaseInfo = $this->app['eccube.repository.base_info']->get();
        $Order = $NemOrder->getOrder();
        
        $order_id = $Order->getId();
        $name01 = $Order->getName01();
        $name02 = $Order->getName02();
        $payment_total = $Order->getPaymentTotal();
        $payment_amount = $NemOrder->getPaymentAmount();

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

        $message = \Swift_Message::newInstance()
            ->setSubject('【' . $BaseInfo->getShopName() . '】かんたんNEM決済 送金確認通知')
            ->setFrom(array($BaseInfo->getEmail03() => $BaseInfo->getShopName()))
            ->setTo(array($Order->getEmail()))
            ->setBcc(array($BaseInfo->getEmail03() => $BaseInfo->getShopName()))
            ->setBody($body);
        $this->app->mail($message);

        $this->app['swiftmailer.spooltransport']->getSpool()->flushQueue($this->app['swiftmailer.transport']);
    }
    
    function getQrcodeImagePath(Order $Order) {
        return  __DIR__ . '/../Resource/qrcode/'. $Order->getId() . '.png';
    }
    
    function getShortHash(Order $Order) {
        return rtrim(base64_encode(md5($this->Config->getSellerNemAddr() . $Order->getId() . $this->auth_magic, true)), '=');  
    }

}

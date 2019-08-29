<?php

namespace Plugin\SimpleNemPay\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\Master\OrderStatusRepository;
use Plugin\SimpleNemPay\Entity\NemHistory;
use Plugin\SimpleNemPay\Repository\ConfigRepository;
use Plugin\SimpleNemPay\Repository\Master\NemStatusRepository;
use Plugin\SimpleNemPay\Service\NemShoppingService;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;

class NemRequestService
{

    public function __construct(
        EccubeConfig $eccubeConfig,
        EntityManagerInterface $entityManager,
        ConfigRepository $configRepository,
        NemStatusRepository $nemStatusRepository,
        NemShoppingService $nemShoppingService,
        OrderStatusRepository $orderStatusRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
        $this->nemStatusRepository = $nemStatusRepository;
        $this->nemShoppingService = $nemShoppingService;
        $this->orderStatusRepository = $orderStatusRepository;

        $this->Config = $this->configRepository->get();

        if ($this->Config->getEnv() == $this->eccubeConfig['simple_nem_pay']['env']['prod']) {
            $this->nemNisUrl = $this->eccubeConfig['simple_nem_pay']['nis_url']['prod'];
        } else {
            $this->nemNisUrl = $this->eccubeConfig['simple_nem_pay']['nis_url']['sandbox'];
        }

        $this->nemTickerUrl = $this->eccubeConfig['simple_nem_pay']['ticker_url'];
    }
    
    function getIncommingTransaction() {
        $url = $this->nemNisUrl . '/account/transfers/incoming';
        $parameter = ['address' => $this->Config->getSellerNemAddr()];
        
        $result = $this->req($url, $parameter);
        dump($result);
        
        return $result->data;        
    }

    public function confirmNemRemittance($Order)
    {
        $isUpdate = false;

        // キーを変換
        $arrNemOrderTemp = [];
        $shortHash = $this->nemShoppingService->getShortHash($Order);
        $arrNemOrderTemp[$shortHash] = $Order;
        $arrNemOrder = $arrNemOrderTemp;

        // NEM受信トランザクション取得
        $arrData = $this->getIncommingTransaction();
        dump($arrData);
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

                    $this->nemShoppingService->sendPayEndMail($Order);
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
    
    function getRate() {
        $url = $this->nemTickerUrl;
        $parameter = [];
        
        $result = $this->req($url, $parameter);
        
        return $result->vwap; 
    }

    private function req($url, $parameters, $count = 1) {
        $qs = $this->_getParametersAsString($parameters);

        $config = [
            'curl' => [
                CURLOPT_RETURNTRANSFER => true,
            ],
        ];

        $client = new Client($config);
        try {
            $response = $client->get($url, [
                'query' => $qs,
            ]);
        } catch (CurlException $e) {
            logs('simple_nem_pay')->info('CurlException. url=' . $url . ' error=' . $e);
            return false;
        } catch (BadResponseException $e) {
            logs('simple_nem_pay')->info('BadResponseException. url=' . $url . ' error=' . $e);
            return false;
        } catch (\Exception $e) {
            logs('simple_nem_pay')->info('Exception. url=' . $url . ' error=' . $e);
            return false;
        }

        // $c = curl_init();
        // curl_setopt($c, CURLOPT_URL, $url . $qs);
        // curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        // $r = curl_exec($c);
        // $info = curl_getinfo($c);
        // $errno = curl_errno($c);
        // $error = curl_error($c);
        // curl_close($c);
        // if (!$r || $errno) {
        //     $this->app['monolog.simple_nempay']->addInfo("nis error: unexpected response. url：". $url." parameter：".var_export($parameters, true));
        //     return false;
        // }

        // if ($info['http_code'] != 200) {
        //     $this->app['monolog.simple_nempay']->addInfo("nis error: unexpected response. url：". $url." parameter：".var_export($parameters, true));
        //     return false;
        // }
        dump($response);

        $d = json_decode($response->getBody()->getContents());
        return $d;
        // $arrRes = json_decode($r, true);

        // return $arrRes;
    }

    private function _urlencode($value) {
        return str_replace('%7E', '~', rawurlencode($value));
    }

    private function _getParametersAsString(array $parameters) {
        $queryParameters = array();
        foreach ($parameters as $key => $value) {
            $queryParameters[] = $key . '=' . $this->_urlencode($value);
        }
        return implode('&', $queryParameters);
    }
    
}
?>

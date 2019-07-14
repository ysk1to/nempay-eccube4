<?php

namespace Plugin\SimpleNemPay\Controller;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NemShoppingController extends AbstractController
{

    /**
     * @var string 非会員用セッションキー
     */
    private $sessionKey = 'eccube.front.shopping.nonmember';
    
    /**
     * 購入画面表示
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function index(Application $app, Request $request)
    {
        $this->app = $app;

        $Order = $this->app['eccube.repository.order']->findOneBy(array('pre_order_id' => $this->app['eccube.service.cart']->getPreOrderId()));
        if (empty($Order)) {
            $this->app['monolog.simple_nempay']->addInfo('pay process error. not found Order in index.');
            $error_title = 'システムエラーが発生しました。';
            $error_message = '注文情報の取得が出来ませんでした。この手続きは無効となりました。';
            return $this->app['view']->render('error.twig', compact('error_title', 'error_message'));
        }
        
        // 商品公開ステータスチェック、商品制限数チェック、在庫チェック
        if (!$this->app['eccube.service.shopping']->isOrderProduct($this->app['orm.em'], $Order)) {
            $this->app->addError('front.shopping.stock.error');
            return $this->app->redirect($this->app->url('shopping_error'));
        }

        $render_flg = true;
        $form = $this->app['form.factory']
            ->createBuilder('simple_nempay')
            ->getForm();

        // リクエストパラメータをセット
        if ('POST' === $this->app['request']->getMethod()) {
            $form->handleRequest($this->app['request']);
            if ($form->isValid()) {
                $this->changeOrderData($Order);
                
                // カート削除
                $this->app['eccube.service.cart']->clear()->save();
                
                // 受注メール送信
                $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->sendOrderMail($Order);
                
                // 受注番号をセット
                $this->app['session']->set('eccube.plugin.simple_nempay.order_id', $Order->getId());
                
                // 注文完了処理
                return $this->app->redirect($this->app->url('shopping_complete'));
            }
        }
        
        $payment_total = $Order->getPaymentTotal();
        // レート取得
        $rate = $this->app['eccube.plugin.simple_nempay.service.nem_request']->getRate();
        if (empty($rate)) {
            $error_title = '決済エラーが発生しました。';
            $error_message = '決済情報の取得に失敗しました。もう一度決済を試みてください。';
            return $this->app['view']->render('error.twig', compact('error_title', 'error_message'));
        }
        
        $payment_amount = round($payment_total / $rate, 3);

        $NemOrder = $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->getNemOrder($Order);
        $NemOrder->setRate($rate);
        $NemOrder->setPaymentAmount($payment_amount);
        $this->app['orm.em']->flush();
                
        return $this->app['view']->render('SimpleNemPay/Twig/Shopping/simple_nempay.twig', array(
            'form' => $form->createView(),
            'title' => 'かんたんNEM決済',
            'order_id' => $Order->getId(),
            'payment_total' => $payment_total,
            'payment_amount' => $payment_amount,
            'rate' => $rate,
        ));
    }
    
    public function back(\Eccube\Application $app)
    {
        $this->app = $app;

        // 受注番号を取得
        $order_id = $this->app['request']->get('order_id');
        $Order = $this->app['eccube.repository.order']->find($order_id);
        if (empty($Order)) {
            $this->app['monolog.simple_nempay']->addInfo('pay process error. not found Order in index.');
            $error_title = 'システムエラーが発生しました。';
            $error_message = '注文情報の取得が出来ませんでした。この手続きは無効となりました。';
            return $this->app['view']->render('error.twig', compact('error_title', 'error_message'));
        }

        // 以前の受注情報を更新
        $Order->setOrderStatus($this->app['eccube.repository.order_status']->find($this->app['config']['order_processing']));

        $this->app['orm.em']->persist($Order);
        $this->app['orm.em']->flush();

        $this->app['monolog.simple_nempay']->addInfo('back. order_id = ' . $Order->getId());
        return $this->app->redirect($this->app->url('shopping'));
    }


    public function changeOrderData($Order)
    {
        $NemOrder = $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->getNemOrder($Order);

        // トランザクション制御
        $em = $this->app['orm.em'];
        $em->getConnection()->beginTransaction();
        
        // Nem決済情報追加
        $msg = $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->getShortHash($Order);
        $arrPaymentInfo = $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->getPaymentInfo($NemOrder, $msg);
        $NemOrder->setPaymentInfo(serialize($arrPaymentInfo));
        // QRコード生成
        $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->createQrcodeImage($Order, $NemOrder, $msg);
        
        // 受注情報更新
        $OrderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_pay_wait']);
        $Order->setOrderStatus($OrderStatus);
        $Order->setOrderDate(new \DateTime());

        // 在庫情報更新
        $this->app['eccube.service.order']->setStockUpdate($em, $Order);

        if ($this->app->isGranted('ROLE_USER')) {
            // 会員の場合、購入金額を更新
            $this->app['eccube.service.order']->setCustomerUpdate($em, $Order, $this->app->user());
        }

        $this->app['eccube.service.shopping']->notifyComplete($Order);

        $em->commit();
        $em->flush();
    }
    
}

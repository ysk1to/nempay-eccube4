<?php

namespace Plugin\SimpleNemPay;

use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class SimpleNemPay
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function onRenderShoppingBefore(FilterResponseEvent $event) {
        $nonMember = $this->app['session']->get('eccube.front.shopping.nonmember');
        if ($this->app->isGranted('ROLE_USER') || !is_null($nonMember)) {
            $Order = null;
            $pre_order_id = $this->app['eccube.service.cart']->getPreOrderId();
            if (!empty($pre_order_id)) {
                $Order = $this->app['eccube.repository.order']->findOneBy(array('pre_order_id' => $pre_order_id));
            }

            if (!is_null($Order)) {
                $Payment = $Order->getPayment();
                $SimpleNemPay = $this->app['eccube.plugin.simple_nempay.repository.nem_info']->getSimpleNemPay();
                if ($Payment == $SimpleNemPay) {
                    // Get request
                    $request = $event->getRequest();
                    // Get response
                    $response = $event->getResponse();
                    // Proccess html
                    $html = $this->getHtmlShoppingConfirm($request, $response);
                    // Set content for response
                    $response->setContent($html);
                    $event->setResponse($response);
                }
            }
        }
    }
    
    public function onRenderShoppingCompleteBefore(FilterResponseEvent $event) {
        $nonMember = $this->app['session']->get('eccube.front.shopping.nonmember');
        if ($this->app->isGranted('ROLE_USER') || !is_null($nonMember)) {
            // Return if no order Id in session (normal payment method was selected)
            $order_id = $this->app['session']->get('eccube.plugin.simple_nempay.order_id');
            if ($order_id == null) {
                return;
            }
        
            // Return if payment method is not Gmo payment
            $Order = $this->app['eccube.repository.order']->find($order_id);
            if (!is_null($Order)) {
                $Payment = $Order->getPayment();
                $SimpleNemPay = $this->app['eccube.plugin.simple_nempay.repository.nem_info']->getSimpleNemPay();

                if ($Payment == $SimpleNemPay) {
                    // Get request
                    $request = $event->getRequest();
                    // Get response
                    $response = $event->getResponse();
                    // Find dom and add extension template
                    $html = $this->getHTMLShoppingComplete($request, $response, $Order);
                    // Set content for response
                    $response->setContent($html);
                    $event->setResponse($response);
                }
            }
            
            // Remove orderId from session
            $this->app['session']->set('eccube.plugin.simple_nempay.order_id', null);
        }
    }
    
    public function onControllerShoppingConfirmBefore($event = null) {
        $nonMember = $this->app['session']->get('eccube.front.shopping.nonmember');
        if ($this->app->isGranted('ROLE_USER') || !is_null($nonMember)) {
            $pre_order_id = $this->app['eccube.service.cart']->getPreOrderId();
            if (empty($pre_order_id)) {
                return;
            }
            $Order = $this->app['eccube.repository.order']->findOneBy(array('pre_order_id' => $pre_order_id));

            $form = $this->app['eccube.service.shopping']->getShippingForm($Order);

            if ('POST' === $this->app['request']->getMethod()) {
                $form->handleRequest($this->app['request']);
                if ($form->isValid()) {
                    $Payment = $Order->getPayment();
                    $SimpleNemPay = $this->app['eccube.plugin.simple_nempay.repository.nem_info']->getSimpleNemPay();

                    if ($Payment == $SimpleNemPay) {
                        $formData = $form->getData();
                        // 受注情報、配送情報を更新（決済処理中として更新する）
                        $this->app['eccube.service.order']->setOrderUpdate($this->app['orm.em'], $Order, $formData);
                        // 2017.03.06 購入処理中は受注日をセットしない
                        $Order->setOrderDate(null);
                        $Order->setOrderStatus($this->app['eccube.repository.order_status']->find($this->app['config']['order_processing']));
                        $this->app['orm.em']->persist($Order);
                        $this->app['orm.em']->flush();

                        $url = $this->app->url('shopping_simple_nempay');

                        if ($event instanceof \Symfony\Component\HttpKernel\Event\KernelEvent) {
                            $response = $this->app->redirect($url);
                            $event->setResponse($response);
                            return;
                        } else {
                            header("Location: " . $url);
                            exit;
                        }
                    }
                }
            }
        }
    }
    
    public function onSendOrderMail(EventArgs $event)
    {
        if ($event->hasArgument('Order')) {
            $Order = $event->getArgument('Order');
            $NemOrder = $this->app['eccube.plugin.simple_nempay.repository.nem_order']->findOneBy(array('Order' => $Order,));
            if (is_null($NemOrder)) {
                return;
            }
        }

        if ($event->hasArgument('message')) {
            $message = $event->getArgument('message');
        }

        if (!is_null($NemOrder)) {
            // メールボディ取得
            $body = $message->getBody();
            // 情報置換用のキーを取得
            $search = array();
            preg_match_all('/メッセージ：.*\\n/u', $body, $search);
            
            $arrPaymentInfo = unserialize($NemOrder->getPaymentInfo());
            // メール本文置換
            $snippet = PHP_EOL;
            $snippet .= PHP_EOL;
            $snippet .= '***********************************************'.PHP_EOL;
            $snippet .= '　かんたんNEM決済情報                          '.PHP_EOL;
            $snippet .= '***********************************************'.PHP_EOL;
            foreach ($arrPaymentInfo as $key => $item) {
                if ($key != 'title') {
                    if (!empty($item['name'])) {
                        $snippet .= $item['name'] . '：';
                    }
                    
                    $snippet .= $item['value'].PHP_EOL;
                }
            }
            $snippet .= PHP_EOL;
            $replace = $search[0][0].$snippet;
            $body = preg_replace('/'.$search[0][0].'/u', $replace, $body);

            $message->setBody($body);
        }
    }

    
    /**
     * Filter and add rename button submit in shopping confirm page 
     * @param Request $request
     * @param Response $response
     * @param type $Payment
     * @return html
     */
    private function getHtmlShoppingConfirm(Request $request, Response $response){
        $crawler = new Crawler($response->getContent());
        $html = $this->getHtml($crawler);
        $newMethod = 'かんたんNEM決済確認画面へ';
        $oldMethod = $crawler->filter('#order-button')->html();

        $html = str_replace($oldMethod, $newMethod, $html);
        
        return $html;
    }
    
    /**
     * Find and add extension template to response.
     * @param FilterResponseEvent $event
     * @return type
     */
    public function getHTMLShoppingComplete(Request $request, Response $response, Order $Order){
        $crawler = new Crawler($response->getContent());
        $html = $this->getHtml($crawler);
        // Get info which need for extension template
        $NemOrder = $this->app['eccube.plugin.simple_nempay.repository.nem_order']->findOneBy(array('Order' => $Order));
        $arrOther = unserialize($NemOrder->getPaymentInfo());
        
        $filepath = $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->getQrcodeImagePath($Order);
        $arrOther['qr_code']['value'] = '<img src="data:image/png;base64,' . base64_encode(file_get_contents($filepath)) . '" alt="QR">';

        // Get and render extension template
        $insert = $this->app->renderView('SimpleNemPay/Twig/Shopping/simple_nempay_info.twig', array(
            'arrOther' => $arrOther,
        ));
        
        // add extension template to response's html
        $oldHtml = $crawler->filter('#deliveradd_input > div > div')->html();
        $newHtml = $oldHtml . $insert;
        $html = str_replace($oldHtml, $newHtml, $html);
        
        return $html;
    }
    
    /**
     * 解析用HTMLを取得
     *
     * @param Crawler $crawler
     * @return string
     */
    public static function getHtml(Crawler $crawler){
        $html = '';
        foreach ($crawler as $domElement) {
            $domElement->ownerDocument->formatOutput = true;
            $html .= $domElement->ownerDocument->saveHTML();
        }
        return self::my_html_entity_decode($html);
    }
    
    /**
     * HTMLエンティティに変換されたUTF-8文字列と円記号に関してのみデコードする
     *
     * @param string $html
     * @return string
     */
    public static function my_html_entity_decode($html) {
        $result = preg_replace_callback
            ("/(&#[0-9]+;|&yen;)/",
             function ($m) {
                return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
             },
             $html);
        return $result;
    }

}

 ?>

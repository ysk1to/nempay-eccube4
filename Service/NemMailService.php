<?php

namespace Plugin\SimpleNemPay\Service;

use Eccube\Application;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;

class NemMailService
{
    /** @var \Eccube\Application */
    public $app;

    /** @var \Eccube\Entity\BaseInfo */
    public $BaseInfo;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->BaseInfo = $app['eccube.repository.base_info']->get();
    }

    public function sendOrderMail(\Eccube\Entity\Order $Order)
    {
        log_info('受注メール送信開始');

        $MailTemplate = $this->app['eccube.repository.mail_template']->find(1);

        $body = $this->app->renderView($MailTemplate->getFileName(), array(
            'header' => $MailTemplate->getHeader(),
            'footer' => $MailTemplate->getFooter(),
            'Order' => $Order,
        ));
        
        // QRコード
        $filepath = $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->getQrcodeImagePath($Order);
        $attachment = \Swift_Attachment::fromPath($filepath)->setContentType('image/png');
        
        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $MailTemplate->getSubject())
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($Order->getEmail()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body)
            ->attach($attachment);

        $event = new EventArgs(
            array(
                'message' => $message,
                'Order' => $Order,
                'MailTemplate' => $MailTemplate,
                'BaseInfo' => $this->BaseInfo,
            ),
            null
        );
        $this->app['eccube.event.dispatcher']->dispatch(EccubeEvents::MAIL_ORDER, $event);

        $count = $this->app->mail($message);

        log_info('受注メール送信完了', array('count' => $count));

        return $message;

    }

}

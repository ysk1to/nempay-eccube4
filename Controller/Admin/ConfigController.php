<?php

namespace Plugin\SimpleNemPay\Controller\Admin;

use Eccube\Application;
use Plugin\SimpleNemPay\Form\Type\Admin\ConfigType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

class ConfigController
{

    private $app;
    private $const;

    public function index(Application $app, Request $request)
    {
        $this->app = $app;
        $this->const = $app['config']['SimpleNemPay']['const'];

        $nemSettings = $app['eccube.plugin.simple_nempay.repository.nem_info']->getNemSettings();
        $configFrom = new ConfigType($this->app, $nemSettings);
        $form = $this->app['form.factory']->createBuilder($configFrom)->getForm();

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formData = $form->getData();

                // 設定値を登録
                $app['eccube.plugin.simple_nempay.repository.nem_info']->registerSettings($formData);

                $app->addSuccess('admin.register.complete', 'admin');
                return $app->redirect($app['url_generator']->generate('plugin_SimpleNemPay_config'));
            }
        }

        return $this->app['view']->render('SimpleNemPay/Twig/admin/config.twig',
            array(
                'form' => $form->createView(),
            ));
    }
}

<?php

namespace Plugin\SimpleNemPay\Command;

use Plugin\SimpleNemPay\Entity\NemOrder;
use Plugin\SimpleNemPay\Entity\NemHistory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * 入金確認実行コマンド
 *
 * app/consoleに要追記
 * $console->add(new Plugin\SimpleNemPay\Command\RemittanceConfirmBatchCommand(new Eccube\Application()));
 *
 * crontab  ex. 0 * * * * /usr/bin/php /var/www/html/eccube-3.0.15/app/console simple_nempay:remittance_confirm
 */
class RemittanceConfirmBatchCommand extends Command
{

    private $app;

    public function __construct(\Eccube\Application $app, $name = null)
    {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure()
    {
        $this->setName('simple_nempay:remittance_confirm')
             ->setDescription('送金確認バッチ処理');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app->initialize();
        $this->app->initializePlugin();
        $this->app->boot();

        $softDeleteFilter = $this->app['orm.em']->getFilters()->getFilter('soft_delete');
        $softDeleteFilter->setExcludes(array(
            'Eccube\Entity\Order'
        ));
        
        // 対象の受注を取得
        $arrNemOrder = $this->app['eccube.plugin.simple_nempay.repository.nem_order']->getOrderPayWaitForSimpleNemPay();
        if (empty($arrNemOrder)) {
            return;
        }
        
        $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->confirmNemRemittance($arrNemOrder);
    }

}

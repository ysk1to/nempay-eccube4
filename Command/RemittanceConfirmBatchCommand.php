<?php

namespace Plugin\SimpleNemPay\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Eccube\Repository\OrderRepository;
use Plugin\SimpleNemPay\Entity\Master\NemStatus;
use Plugin\SimpleNemPay\Repository\Master\NemStatusRepository;
use Plugin\SimpleNemPay\Service\NemShoppingService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 入金確認実行コマンド
 */
class RemittanceConfirmBatchCommand extends DoctrineCommand
{
    protected static $defaultName = 'simple_nem_pay:remittance_confirm';

    public function __construct(
        NemStatusRepository $nemStatusRepository,
        NemShoppingService $nemShoppingService,
        OrderRepository $orderRepository
    ) {
        parent::__construct();
        $this->nemStatusRepository = $nemStatusRepository;
        $this->nemShoppingService = $nemShoppingService;
        $this->orderRepository = $orderRepository;
    }

    protected function configure()
    {
        $this->setDescription('送金確認バッチ処理');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // 対象の受注を取得
        $NemStatus = $this->nemStatusRepository->find(NemStatus::PAY_WAITING);
        $Orders = $this->orderRepository->findBy(['NemStatus' => $NemStatus]);
        if (empty($Orders)) {
            return;
        }

        foreach ($Orders as $Order) {
            $this->nemShoppingService->confirmNemRemittance($Order);
        }
    }
}

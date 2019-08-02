<?php

namespace Plugin\SimpleNemPay\Controller\Admin\Order;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\SimpleNemPay\Form\Type\Admin\SearchPaymentType;
use Plugin\SimpleNemPay\Service\Method\SimpleNemPay;
use Plugin\SimpleNemPay\Repository\Master\SimpleNemStatusRepository;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * 決済状況管理
 */
class PaymentStatusController extends AbstractController
{
    /**
     * @var PaymentStatusRepository
     */
    protected $paymentStatusRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var array
     */
    protected $bulkActions = [
        ['id' => 1, 'name' => '一括確認'],
        ['id' => 2, 'name' => '一括取消'],
        ['id' => 3, 'name' => '一括再オーソリ'],
    ];

    /**
     * PaymentController constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     */
    public function __construct(
        SimpleNemStatusRepository $simpleNemStatusRepository,
        PageMaxRepository $pageMaxRepository,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->simpleNemStatusRepository = $simpleNemStatusRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * 決済状況一覧画面
     *
     * @Route("/%eccube_admin_route%/simple_nem_pay/payment_status", name="simple_nem_pay_admin_payment_status")
     * @Route("/%eccube_admin_route%/simple_nem_pay/payment_status/{page_no}", requirements={"page_no" = "\d+"}, name="simple_nem_pay_admin_payment_status_pageno")
     * @Template("@SimpleNemPay/admin/Order/payment_status.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $searchForm = $this->createForm(SearchPaymentType::class);

        /**
         * ページの表示件数は, 以下の順に優先される.
         * - リクエストパラメータ
         * - セッション
         * - デフォルト値
         * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
         **/
        $page_count = $this->session->get(
            'simple_nem_pay.admin.payment_status.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count')
        );

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('simple_nem_pay.admin.payment_status.search.page_count', $page_count);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * 検索が実行された場合は, セッションに検索条件を保存する.
                 * ページ番号は最初のページ番号に初期化する.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set('simple_nem_pay.admin.payment_status.search', FormUtil::getViewData($searchForm));
                $this->session->set('simple_nem_pay.admin.payment_status.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                /*
                 * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                 */
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set('simple_nem_pay.admin.payment_status.search.page_no', (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get('simple_nem_pay.admin.payment_status.search.page_no', 1);
                }
                $viewData = $this->session->get('simple_nem_pay.admin.payment_status.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * 初期表示の場合.
                 */
                $page_no = 1;
                $searchData = [];

                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set('simple_nem_pay.admin.payment_status.search', $searchData);
                $this->session->set('simple_nem_pay.admin.payment_status.search.page_no', $page_no);
            }
        }

        $qb = $this->createQueryBuilder($searchData);
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
            'bulkActions' => $this->bulkActions,
        ];
    }

    private function createQueryBuilder(array $searchData)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('o')
            ->from(Order::class, 'o')
            ->orderBy('o.order_date', 'DESC')
            ->addOrderBy('o.id', 'DESC');

        // かんたんNEM決済のみ
        $Payment = $this->paymentRepository->findOneBy(array('method_class' => SimpleNemPay::class));
        $qb->andWhere('o.Payment = :Payment')
            ->setParameter('Payment', $Payment)
            ->andWhere('o.SimpleNemStatus IS NOT NULL');

        // 決済済みのみ
        $qb->andWhere('o.order_date IS NOT NULL');

        if (!empty($searchData['OrderStatuses']) && count($searchData['OrderStatuses']) > 0) {
            $qb->andWhere($qb->expr()->in('o.OrderStatus', ':OrderStatuses'))
                ->setParameter('OrderStatuses', $searchData['OrderStatuses']);
        }

        if (!empty($searchData['SimpleNemStatuses']) && count($searchData['SimpleNemStatuses']) > 0) {
            $qb->andWhere($qb->expr()->in('o.SimpleNemStatus', ':SimpleNemStatuses'))
                ->setParameter('SimpleNemStatuses', $searchData['SimpleNemStatuses']);
        }

        return $qb;
    }
}

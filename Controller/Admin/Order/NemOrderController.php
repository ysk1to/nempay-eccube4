<?php

namespace Plugin\SimpleNemPay\Controller\Admin\Order;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CsvType;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NemOrderController extends AbstractController
{

    public function index(Application $app, Request $request, $page_no = null)
    {
        $session = $request->getSession();

        $builder = $app['form.factory']
            ->createBuilder('admin_search_order');

        $event = new EventArgs(
            array(
                'builder' => $builder,
            ),
            $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        $pagination = array();

        $disps = $app['eccube.repository.master.disp']->findAll();
        $pageMaxis = $app['eccube.repository.master.page_max']->findAll();

        // 表示件数は順番で取得する、1.SESSION 2.設定ファイル
        $page_count = $session->get('eccube.admin.order.search.page_count', $app['config']['default_page_count']);

        $page_count_param = $request->get('page_count');
        // 表示件数はURLパラメターから取得する
        if($page_count_param && is_numeric($page_count_param)){
            foreach($pageMaxis as $pageMax){
                if($page_count_param == $pageMax->getName()){
                    $page_count = $pageMax->getName();
                    // 表示件数入力値正し場合はSESSIONに保存する
                    $session->set('eccube.admin.order.search.page_count', $page_count);
                    break;
                }
            }
        }

        $active = false;

        // 検索実行時
        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();

                // paginator
                $qb = $app['eccube.plugin.simple_nempay.repository.nem_order']->getQueryBuilderBySearchDataForAdmin($searchData);

                $event = new EventArgs(
                    array(
                        'form' => $searchForm,
                        'qb' => $qb,
                    ),
                    $request
                );
                $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_SEARCH, $event);

                $page_no = 1;
                $pagination = $app['paginator']()->paginate(
                    $qb,
                    $page_no,
                    $page_count
                );

                // sessionに検索条件を保持.
                $viewData = \Eccube\Util\FormUtil::getViewData($searchForm);
                $session->set('eccube.plugin.simple_nempay.admin.nem_order.search', $viewData);
                $session->set('eccube.plugin.simple_nempay.admin.nem_order.search.page_no', $page_no);
            }
        // 初回遷移、ページ遷移時
        } else {
            if (is_null($page_no) && $request->get('resume') != Constant::ENABLED) {
                // sessionを削除
                $session->remove('eccube.plugin.simple_nempay.admin.nem_order.search');
                $session->remove('eccube.plugin.simple_nempay.admin.nem_order.search.page_no');
                $session->remove('eccube.plugin.simple_nempay.admin.nem_order.search.page_count');
            } else {
                // pagingなどの処理
                if (is_null($page_no)) {
                    $page_no = intval($session->get('eccube.plugin.simple_nempay.admin.nem_order.search.page_no'));
                } else {
                    $session->set('eccube.plugin.simple_nempay.admin.nem_order.search.page_no', $page_no);
                }
                $viewData = $session->get('eccube.plugin.simple_nempay.admin.nem_order.search');
                if (!is_null($viewData)) {
                    // sessionに保持されている検索条件を復元.
                    $searchData = \Eccube\Util\FormUtil::submitAndGetData($searchForm, $viewData);
                }
                if (!is_null($searchData)) {
                    // 表示件数
                    $pcount = $request->get('page_count');

                    $page_count = empty($pcount) ? $page_count : $pcount;

                    $qb = $app['eccube.plugin.simple_nempay.repository.nem_order']->getQueryBuilderBySearchDataForAdmin($searchData);

                    $event = new EventArgs(
                        array(
                            'form' => $searchForm,
                            'qb' => $qb,
                        ),
                        $request
                    );
                    $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_SEARCH, $event);

                    $pagination = $app['paginator']()->paginate(
                        $qb,
                        $page_no,
                        $page_count
                    );
                }
            }
        }

        return $app->render('SimpleNemPay/Twig/admin/Order/index.twig', array(
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'disps' => $disps,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'active' => $active,
        ));

    }
    
    public function check(Application $app, Request $request)
    {
        // $this->isTokenValid($app);
        $session = $request->getSession();
        $page_no = intval($session->get('eccube.plugin.simple_nempay.admin.nem_order.search.page_no'));
        $page_no = $page_no ? $page_no : Constant::ENABLED;

        // 対象の定期受注番号を取得
        $params = $request->query->all();
        $ids = array();
        foreach ($params as $key => $value) {
            $ids[] = str_replace('ids', '', $key);
        }

        // 対象受注を取得
        $arrNemOrder = $app['eccube.plugin.simple_nempay.repository.nem_order']->getOrderPayWaitForSimpleNemPay($ids);
        
        $arrUpdateOrderId = $app['eccube.plugin.simple_nempay.service.nem_shopping']->confirmNemRemittance($arrNemOrder);

        if (empty($arrUpdateOrderId)) {
            $msg = "■新しい送金情報はありませんでした。";
            $app->addError($msg, 'admin');
        } else {
            foreach ($arrUpdateOrderId as $order_id) {
                $msg = "■注文番号 " . $order_id . " ： " . "送金金額を更新しました。";
                $app->addSuccess($msg, 'admin');
            }
        }
        
        return $app->redirect($app->url('simple_nempay_order_page', array('page_no' => $page_no)).'?resume='.Constant::ENABLED);
    }
}

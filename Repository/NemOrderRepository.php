<?php

namespace Plugin\SimpleNemPay\Repository;

use Doctrine\ORM\EntityRepository;

class NemOrderRepository extends EntityRepository
{
    protected $app;

    public function setApplication($app)
    {
        $this->app = $app;
    }
    
    public function getOrderPayWaitForSimpleNemPay($ids = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        // 入金待ち
        $OrderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_pay_wait']);
        
        $qb
            ->select('no')
            ->from('\Plugin\SimpleNemPay\Entity\NemOrder', 'no')
            ->innerJoin('\Eccube\Entity\Order', 'o', 'WITH', 'o = no.Order')
            ->andWhere('o.OrderStatus = :OrderStatus')
            ->setParameter('OrderStatus', $OrderStatus); 
        
        if (!is_null($ids)) {
            $qb->andWhere($qb->expr()->in('o.id', ':ids'))
                ->setParameter('ids', $ids);
        }
        
        return $qb
            ->getQuery()
            ->getResult();
    }
    
    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('no');
        $qb->innerJoin('\Eccube\Entity\Order', 'o', 'WITH', 'o = no.Order');

        // order_id_start
        if (isset($searchData['order_id_start']) && Str::isNotBlank($searchData['order_id_start'])) {
            $qb
                ->andWhere('o.id >= :order_id_start')
                ->setParameter('order_id_start', $searchData['order_id_start']);
        }
        // multi
        if (isset( $searchData['multi']) && Str::isNotBlank($searchData['multi'])) {
            $multi = preg_match('/^\d+$/', $searchData['multi']) ? $searchData['multi'] : null;
            $qb
                ->andWhere('o.id = :multi OR o.name01 LIKE :likemulti OR o.name02 LIKE :likemulti OR ' .
                           'o.kana01 LIKE :likemulti OR o.kana02 LIKE :likemulti OR o.company_name LIKE :likemulti')
                ->setParameter('multi', $multi)
                ->setParameter('likemulti', '%' . $searchData['multi'] . '%');
        }

        // order_id_end
        if (isset($searchData['order_id_end']) && Str::isNotBlank($searchData['order_id_end'])) {
            $qb
                ->andWhere('o.id <= :order_id_end')
                ->setParameter('order_id_end', $searchData['order_id_end']);
        }

        // status
        $filterStatus = false;
        if (!empty($searchData['status']) && $searchData['status']) {
            $qb
                ->andWhere('o.OrderStatus = :status')
                ->setParameter('status', $searchData['status']);
            $filterStatus = true;
        }
        if (!empty($searchData['multi_status']) && count($searchData['multi_status'])) {
            $qb
                ->andWhere($qb->expr()->in('o.OrderStatus', ':multi_status'))
                ->setParameter('multi_status', $searchData['multi_status']->toArray());
            $filterStatus = true;
        }
        if (!$filterStatus) {
            // 購入処理中は検索対象から除外
            $OrderStatuses = $this->getEntityManager()
                ->getRepository('Eccube\Entity\Master\OrderStatus')
                ->findNotContainsBy(array('id' => $this->app['config']['order_processing']));
            $qb->andWhere($qb->expr()->in('o.OrderStatus', ':status'))
                ->setParameter('status', $OrderStatuses);
        }

        // name
        if (isset($searchData['name']) && Str::isNotBlank($searchData['name'])) {
            $qb
                ->andWhere('CONCAT(o.name01, o.name02) LIKE :name')
                ->setParameter('name', '%' . $searchData['name'] . '%');
        }

        // kana
        if (isset($searchData['kana']) && Str::isNotBlank($searchData['kana'])) {
            $qb
                ->andWhere('CONCAT(o.kana01, o.kana02) LIKE :kana')
                ->setParameter('kana', '%' . $searchData['kana'] . '%');
        }

        // email
        if (isset($searchData['email']) && Str::isNotBlank($searchData['email'])) {
            $qb
                ->andWhere('o.email like :email')
                ->setParameter('email', '%' . $searchData['email'] . '%');
        }

        // tel
        if (isset($searchData['tel']) && Str::isNotBlank($searchData['tel'])) {
            $qb
                ->andWhere('CONCAT(o.tel01, o.tel02, o.tel03) LIKE :tel')
                ->setParameter('tel', '%' . $searchData['tel'] . '%');
        }

        // sex
        if (!empty($searchData['sex']) && count($searchData['sex']) > 0) {
            $qb
                ->andWhere($qb->expr()->in('o.Sex', ':sex'))
                ->setParameter('sex', $searchData['sex']->toArray());
        }

        // payment
        $SimpleNemPay = $this->app['eccube.plugin.simple_nempay.repository.nem_info']->getSimpleNemPay();
        $qb
            ->leftJoin('o.Payment', 'p')
            ->andWhere('p = :payment')
            ->setParameter('payment', $SimpleNemPay);

        // oreder_date
        if (!empty($searchData['order_date_start']) && $searchData['order_date_start']) {
            $date = $searchData['order_date_start']
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.order_date >= :order_date_start')
                ->setParameter('order_date_start', $date);
        }
        if (!empty($searchData['order_date_end']) && $searchData['order_date_end']) {
            $date = clone $searchData['order_date_end'];
            $date = $date
                ->modify('+1 days')
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.order_date < :order_date_end')
                ->setParameter('order_date_end', $date);
        }

        // payment_date
        if (!empty($searchData['payment_date_start']) && $searchData['payment_date_start']) {
            $date = $searchData['payment_date_start']
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.payment_date >= :payment_date_start')
                ->setParameter('payment_date_start', $date);
        }
        if (!empty($searchData['payment_date_end']) && $searchData['payment_date_end']) {
            $date = clone $searchData['payment_date_end'];
            $date = $date
                ->modify('+1 days')
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.payment_date < :payment_date_end')
                ->setParameter('payment_date_end', $date);
        }

        // commit_date
        if (!empty($searchData['commit_date_start']) && $searchData['commit_date_start']) {
            $date = $searchData['commit_date_start']
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.commit_date >= :commit_date_start')
                ->setParameter('commit_date_start', $date);
        }
        if (!empty($searchData['commit_date_end']) && $searchData['commit_date_end']) {
            $date = clone $searchData['commit_date_end'];
            $date = $date
                ->modify('+1 days')
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.commit_date < :commit_date_end')
                ->setParameter('commit_date_end', $date);
        }


        // update_date
        if (!empty($searchData['update_date_start']) && $searchData['update_date_start']) {
            $date = $searchData['update_date_start']
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.update_date >= :update_date_start')
                ->setParameter('update_date_start', $date);
        }
        if (!empty($searchData['update_date_end']) && $searchData['update_date_end']) {
            $date = clone $searchData['update_date_end'];
            $date = $date
                ->modify('+1 days')
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        }

        // payment_total
        if (isset($searchData['payment_total_start']) && Str::isNotBlank($searchData['payment_total_start'])) {
            $qb
                ->andWhere('o.payment_total >= :payment_total_start')
                ->setParameter('payment_total_start', $searchData['payment_total_start']);
        }
        if (isset($searchData['payment_total_end']) && Str::isNotBlank($searchData['payment_total_end'])) {
            $qb
                ->andWhere('o.payment_total <= :payment_total_end')
                ->setParameter('payment_total_end', $searchData['payment_total_end']);
        }

        // buy_product_name
        if (isset($searchData['buy_product_name']) && Str::isNotBlank($searchData['buy_product_name'])) {
            $qb
                ->leftJoin('o.OrderDetails', 'od')
                ->andWhere('od.product_name LIKE :buy_product_name')
                ->setParameter('buy_product_name', '%' . $searchData['buy_product_name'] . '%');
        }

        // Order By
        $qb->orderBy('o.update_date', 'DESC');
        $qb->addorderBy('o.id', 'DESC');

        return $qb;
    }
}

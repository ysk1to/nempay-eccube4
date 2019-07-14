<?php

namespace Plugin\SimpleNemPay\Repository;

use Doctrine\ORM\EntityRepository;

class NemInfoRepository extends EntityRepository
{
    protected $app;

    public function setApplication($app)
    {
        $this->app = $app;
    }

    public function getSettingData()
    {
        $NemInfo = $this->find(1);

        return unserialize($NemInfo->getSettingData());
    }

    public function getSimpleNemPay()
    {
        return $this->find(1)->getPayment();
    }

    public function getNemSettings()
    {
        $nemSettings = $this->getSettingData();

        // 本番環境
        if (isset($nemSettings['prod_mode']) && $nemSettings['prod_mode'] == 1) {
            $nemSettings['nis_url'] = 'http://alice3.nem.ninja:7890';
        // テスト環境
        } else {
            $nemSettings['nis_url'] = 'http://104.128.226.60:7890';
        }
        
        $nemSettings['ticker_url'] = 'https://api.zaif.jp/api/1/ticker/xem_jpy';

        return $nemSettings;
    }

    /**
     * サブデータをDBへ登録する
     *
     * @param mixed $data
     */
    function registerSettings($settingData)
    {
        $NemInfo = $this
            ->find(1)
            ->setSettingData(serialize($settingData));

        $em = $this->getEntityManager();
        $em->persist($NemInfo);
        $em->flush();
    }
}

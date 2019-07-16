<?php

namespace Plugin\SimpleNemPay;

use Eccube\Common\EccubeNav;

class SimpleNemPayNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'order' => [
                'children' => [
                    'simple_nem_pay_admin_payment_status' => [
                        'name' => 'simple_nem_pay.admin.nav.payment_list',
                        'url' => 'simple_nem_pay_admin_payment_status',
                    ]
                ]
            ],
        ];
    }
}

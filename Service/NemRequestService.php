<?php

namespace Plugin\SimpleNemPay\Service;

use Eccube\Application;

class NemRequestService
{

    private $app;

    private $nemSettings;

    public function __construct(Application $app)
    {
        $this->app = $app;

        // かんたんNEM決済値読み込み
        $this->nemSetting = $app['eccube.plugin.simple_nempay.repository.nem_info']->getNemSettings();
    }
    
    function getIncommingTransaction() {
        $url = $this->nemSetting['nis_url'] . '/account/transfers/incoming';
        $parameter = array('address' => $this->nemSetting['seller_addr']);
        
        $result = $this->req($url, $parameter);
        
        return $result['data'];        
    }
    
    function getRate() {
        $url = $this->nemSetting['ticker_url'];
        $parameter = array();
        
        $result = $this->req($url, $parameter);
        
        return $result['vwap']; 
    }

    private function req($url, $parameters, $count = 1) {
        $qs = $this->_getParametersAsString($parameters);

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url . $qs);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $r = curl_exec($c);
        $info = curl_getinfo($c);
        $errno = curl_errno($c);
        $error = curl_error($c);
        curl_close($c);
        if (!$r || $errno) {
            $this->app['monolog.simple_nempay']->addInfo("nis error: unexpected response. url：". $url." parameter：".var_export($parameters, true));
            return false;
        }

        if ($info['http_code'] != 200) {
            $this->app['monolog.simple_nempay']->addInfo("nis error: unexpected response. url：". $url." parameter：".var_export($parameters, true));
            return false;
        }

        $arrRes = json_decode($r, true);

        return $arrRes;
    }

    private function _urlencode($value) {
        return str_replace('%7E', '~', rawurlencode($value));
    }

    private function _getParametersAsString(array $parameters) {
        $queryParameters = array();
        foreach ($parameters as $key => $value) {
            $queryParameters[] = $key . '=' . $this->_urlencode($value);
        }
        return '?' . implode('&', $queryParameters);
    }
    
}
?>

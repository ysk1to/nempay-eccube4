<?php

namespace Plugin\SimpleNemPay\Service;

use Eccube\Common\EccubeConfig;
use Plugin\SimpleNemPay\Repository\ConfigRepository;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;

class NemRequestService
{

    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;

        $this->Config = $this->configRepository->get();

        if ($this->Config->getEnv() == $this->eccubeConfig['simple_nem_pay']['env']['prod']) {
            $this->nemNisUrl = $this->eccubeConfig['simple_nem_pay']['nis_url']['prod'];
        } else {
            $this->nemNisUrl = $this->eccubeConfig['simple_nem_pay']['nis_url']['sandbox'];
        }

        $this->nemTickerUrl = $this->eccubeConfig['simple_nem_pay']['ticker_url'];
    }
    
    function getIncommingTransaction() {
        $url = $this->nemNisUrl . '/account/transfers/incoming';
        $parameter = ['address' => $this->Config->getSellerNemAddr()];
        
        $result = $this->req($url, $parameter);
        
        return $result['data'];        
    }
    
    function getRate() {
        $url = $this->nemTickerUrl;
        $parameter = [];
        
        $result = $this->req($url, $parameter);
        
        return $result->vwap; 
    }

    private function req($url, $parameters, $count = 1) {
        $qs = $this->_getParametersAsString($parameters);

        $config = [
            'curl' => [
                CURLOPT_RETURNTRANSFER => true,
            ],
        ];

        $client = new Client($config);
        try {
            $response = $client->get($url);
        } catch (CurlException $e) {
            logs('simple_nem_pay')->info('CurlException. url=' . $url . ' error=' . $e);
            return false;
        } catch (BadResponseException $e) {
            logs('simple_nem_pay')->info('BadResponseException. url=' . $url . ' error=' . $e);
            return false;
        } catch (\Exception $e) {
            logs('simple_nem_pay')->info('Exception. url=' . $url . ' error=' . $e);
            return false;
        }

        // $c = curl_init();
        // curl_setopt($c, CURLOPT_URL, $url . $qs);
        // curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        // $r = curl_exec($c);
        // $info = curl_getinfo($c);
        // $errno = curl_errno($c);
        // $error = curl_error($c);
        // curl_close($c);
        // if (!$r || $errno) {
        //     $this->app['monolog.simple_nempay']->addInfo("nis error: unexpected response. url：". $url." parameter：".var_export($parameters, true));
        //     return false;
        // }

        // if ($info['http_code'] != 200) {
        //     $this->app['monolog.simple_nempay']->addInfo("nis error: unexpected response. url：". $url." parameter：".var_export($parameters, true));
        //     return false;
        // }

        $d = json_decode($response->getBody()->getContents());
        return $d;
        // $arrRes = json_decode($r, true);

        // return $arrRes;
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

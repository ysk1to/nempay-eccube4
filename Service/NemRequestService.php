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
        $this->Config = $configRepository->get();

        if ($this->Config->getEnv() == $this->eccubeConfig['simple_nem_pay']['env']['prod']) {
            $nemNisUrl = $this->eccubeConfig['simple_nem_pay']['nis_url']['prod'];
        } else {
            $nemNisUrl = $this->eccubeConfig['simple_nem_pay']['nis_url']['sandbox'];
        }

        $this->nemIncommingUrl = $nemNisUrl . '/account/transfers/incoming';
        $this->nemTickerUrl = $this->eccubeConfig['simple_nem_pay']['ticker_url'];
    }
    
    public function getIncommingTransaction() {
        $parameter = ['address' => $this->Config->getSellerNemAddr()];
        
        $result = $this->req($this->nemIncommingUrl, $parameter);
        
        return $result->data;        
    }
    
    public function getRate() {
        $parameter = [];
        
        $result = $this->req($this->nemTickerUrl, $parameter);
        
        return $result->vwap; 
    }

    private function req($url, $parameters) {
        $qs = $this->_getParametersAsString($parameters);

        $config = [
            'curl' => [
                CURLOPT_RETURNTRANSFER => true,
            ],
        ];

        $client = new Client($config);
        try {
            $response = $client->get($url, [
                'query' => $qs,
            ]);
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

        $d = json_decode($response->getBody()->getContents());
        return $d;
    }

    private function _urlencode($value) {
        return str_replace('%7E', '~', rawurlencode($value));
    }

    private function _getParametersAsString(array $parameters) {
        $queryParameters = array();
        foreach ($parameters as $key => $value) {
            $queryParameters[] = $key . '=' . $this->_urlencode($value);
        }
        return implode('&', $queryParameters);
    }
    
}
?>

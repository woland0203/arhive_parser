<?php
/**
 * Created by PhpStorm.
 * User: vkarpenko
 * Date: 20.08.18
 * Time: 18:47
 */

namespace console\components\translator;


class Freeonlinetranslators
{
    protected $proxyConfig;

    public function translate($text, $from = 'ru', $to = 'en'){
        $text = $this->clearText($text);
        $html = $this->get($text, $from, $to);
        return $this->extractText($html);
    }

    protected function get($text, $from, $to)
    {
        $ch = curl_init('http://de.freeonlinetranslators.net/');
      //  curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_COOKIE, http_build_query($_COOKIE, null, ';'));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36');

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'input' => $text,
            'from' => $from,
            'to' => $to,
            'result' => ''
        ]));

        if($this->proxyConfig){
            print_r($this->proxyConfig);
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyConfig['proxy_ip']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxyConfig['proxy_port']);

            /*curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
            curl_setopt($ch, CURLOPT_PROXY, "http://".$this->proxyConfig['proxy_ip'].":".$this->proxyConfig['proxy_port']."/");
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
*/

            /*curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
            curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:8101"); // Default privoxy port
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
*/
        }
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    protected function clearText($text){
        $text = preg_replace('|[^\d\sa-zA-Zа-яА-Я\.\,\-#%\(\)@\?!_]+|u', '', $text);
        return $text;
    }

    protected function extractText($html){
        $document = \phpQuery::newDocumentHTML($html);
        return $document->find('.translationTextarea')->eq(1)->val();
    }

    public function setProxy($proxyConfig){
        $this->proxyConfig = $proxyConfig;
    }
}
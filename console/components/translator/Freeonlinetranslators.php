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
    public function translate($text, $from = 'ru', $to = 'en'){
        $text = $this->clearText($text);
        $html = $this->get($text, $from, $to);
        return $this->extractText($html);
    }

    protected function get($text, $from, $to)
    {
        $ch = curl_init('http://ru.freeonlinetranslators.net/');
      //  curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_COOKIE, http_build_query($_COOKIE, null, ';'));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36');
        /*
         curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
         -H 'Connection: keep-alive'
        -H 'Pragma: no-cache'
        -H 'Cache-Control: no-cache'
        -H 'Origin: http://ru.freeonlinetranslators.net'
        -H 'Upgrade-Insecure-Requests: 1'
        -H 'Content-Type: application/x-www-form-urlencoded'
        -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36'
        */
          //          -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
        /*-H 'Referer: http://ru.freeonlinetranslators.net/'
        -H 'Accept-Encoding: gzip, deflate'
        -H 'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6'
        */
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'input' => $text,
            'from' => $from,
            'to' => $to,
            'result' => ''
        ]));
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
}
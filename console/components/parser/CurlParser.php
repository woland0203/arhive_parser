<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 12.11.18
 * Time: 23:17
 */

namespace console\components\parser;


class CurlParser
{
    public static function request($url){
        $header = [
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        ];
         $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        $r= curl_exec( $ch );
        curl_close ($ch);
        return $r;
    }

}
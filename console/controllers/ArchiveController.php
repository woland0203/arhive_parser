<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 04.11.18
 * Time: 22:53
 */

namespace console\controllers;

use yii\console\Controller;

class ArchiveController extends Controller
{
    public function actionParse($url = 'https://web.archive.org/web/20150424123607/http://www.boatinfo.org:80/blog'){
        $path = '/home/vlad/work_data/myboatshrinkwrapping/parsed_archive';

        $urlSplit = preg_split('|/web/\d+/|', $url);
        $urlSplit[1] = trim($urlSplit[1]);
        $host = parse_url($urlSplit[1], PHP_URL_HOST);

        $queue = new \console\components\parser\Queue($path . DIRECTORY_SEPARATOR . $host . '.csv');

        $parser = new \console\components\parser\sites_archive\BoatinfoOrg($path);
        $queue->addLinks([$url]);

        while ( ($queueUrl = $queue->get()) ){

            try {
                echo $queueUrl . PHP_EOL;

                $links = $parser->parse($queueUrl);
                //  die();
            }catch (\Exception $exception){
                if($exception->getCode() != 404){
                    throw new Exception($exception->getMessage(), $exception->getCode());
                }
                else{
                    echo ' Parser Error(404): ' . $queueUrl . PHP_EOL;
                }
                continue;
            }
            if(!empty($links)){
                $queue->addLinks($links);
            }
            if(!(rand(1,10)%10)){ //25% sleep
                echo 'sleep' . PHP_EOL;
                sleep(2);
            }

        }
    }
}
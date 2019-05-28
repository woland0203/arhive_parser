<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 04.11.18
 * Time: 22:53
 */

namespace console\controllers;

use yii\console\Controller;
use console\components\loader\PostHelper;
use console\components\loader\WpLoader;


class ArchiveController extends Controller
{
    /**
     * @param string $url
     *
     * Спарсить
     * https://web.archive.org/web/20100402050220/http://seekhealthanswers.com:80/category/hypertension
     * https://web.archive.org/web/20090615134444/http://www.hemorrhoidanswers.com:80/causes-of-hemorrhoids.html
     * https://web.archive.org/web/20130823164651/http://dentalhealthanswers.com/dental-hygiene-products/
     * https://web.archive.org/web/20031030223227/http://www.answers-health.com:80/answers-health/cankersores.html
     */
    public function actionParse($url = 'https://web.archive.org/web/20101119183636/http://externalhemorrhoids.biz:80/'){
        $path = '/home/vlad/work_data/healthlifemag/parsed/externalhemorrhoids';

        $urlSplit = preg_split('|/web/\d+/|', $url);
        $urlSplit[1] = trim($urlSplit[1]);
        $host = parse_url($urlSplit[1], PHP_URL_HOST);

        $queue = new \console\components\parser\Queue($path . DIRECTORY_SEPARATOR . $host . '.csv');

        $parser = new \console\components\parser\sites_archive\ExternalhemorrhoidsBiz($path);
        $queue->addLinks([$url]);



        while ( ($queueUrl = $queue->get()) ){

            try {
            $queueUrl = str_replace('*', '', $queueUrl);
                echo $queueUrl . PHP_EOL;

                $links = $parser->parse($queueUrl);
                //  die();
            }catch (\Exception $exception){
                if($exception->getCode() != 404){
               //     throw new \Exception($exception->getMessage(), $exception->getCode());
                }
                else{
                    sleep(2);
                    echo ' Parser Error(404): ' . $queueUrl . PHP_EOL;
                }
                continue;
            }

            if(!empty($links)){
                $queue->addLinks($links);
            }
           // sleep(2);
           /* if(!(rand(1,10)%10)){ //25% sleep
                echo 'sleep' . PHP_EOL;
                sleep(2);
            }*/

        }
    }


    public function actionPost($count = 1, $path = '/home/vlad/work_data/medicineanswersnet/parsed_archive/www.medicineanswers.net'){
        $shaduleFilePath = $path . '/shadule.txt';
        $postedCount = 0;
        if(file_exists($shaduleFilePath)){
            $shaduleFile = json_decode(file_get_contents($path . '/shadule.txt' ), true) ;

            if(date('Y-m-d') == $shaduleFile['date']){
                $postedCount = $shaduleFile['count'];
                $count = $count - $shaduleFile['count'];
                if($count <= 0){
                    echo 'Today already posted' . PHP_EOL;
                    return;
                }
            }
        }

        $parser = new \console\components\parser\sites_archive\MedicineanswersNet($path);
        $loader = new WpLoader();

        $d = dir($path . DIRECTORY_SEPARATOR . 'dst');

        while ($count && false !== ($entry = $d->read())) {
            $file = $path . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . $entry;

            //echo $file . PHP_EOL;
            $fileAlreadyLoaded = $path . DIRECTORY_SEPARATOR . 'dst_loaded' .  DIRECTORY_SEPARATOR .$entry;
            if(!is_file($file)){
                continue;
            }

            $post = $loader->createPostFromFile($file);

            $post['content'] = PostHelper::prepareContent($post['content']);
            $post['content'] = PostHelper::removeAttributes( \phpQuery::newDocumentHTML($post['content']) );
            $post['content'] = $parser->prepareContentPost($post['content']);
            $post['category_id'] = $parser->determinateCategory($post['metaData']);


            $loader->loadPost($post);
            echo $file . PHP_EOL;
            rename($file, $fileAlreadyLoaded);
            $count--;
            $postedCount++;



        }
        $shaduleFile = ['date' => date('Y-m-d'), 'count' => $postedCount];
        file_put_contents($path . '/shadule.txt', json_encode($shaduleFile));
    }
}
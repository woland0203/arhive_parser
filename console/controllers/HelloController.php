<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use console\components\site_parsers\Parser;
use console\components\file_parsers\ArchiveTxt;
use console\components\file_parsers\ArchiveUrl;
use console\components\loader\WpLoader;

class HelloController extends Controller
{
    public function actionIndex(){
        $archiveTxt = new ArchiveTxt();
        $archiveUrls = $archiveTxt->parseInline('/home/vlad/work_data/healthlifemag/medical_articles.txt');
        //$archiveUrls = $archiveTxt->parseInline('/home/vkarpenko/tmp/tmp2/test.txt');

        //print_r($archiveUrls);


        /**
         * @var $parsers \console\components\site_parsers\Parser[]
         */
        $parsers = [];
        foreach ($archiveUrls as $archiveUrl){
            if(!isset($parsers[$archiveUrl->parserClass])){
                try{
                    if(class_exists($archiveUrl->parserClass)){
                        $parsers[$archiveUrl->parserClass] = new $archiveUrl->parserClass();
                    }

                }
                catch (\Exception $exception){
                    echo $archiveUrl->parserClass . ' class not exists' . PHP_EOL;
                }

            }

            if(isset($parsers[$archiveUrl->parserClass])){
                echo $archiveUrl->parseUrl . PHP_EOL;
               try {
                    $parsers[$archiveUrl->parserClass]->parseArticle($archiveUrl->parseUrl, $archiveUrl->url);
                }catch (\Exception $exception){
                        echo ' Parser Error(404)' . PHP_EOL;
                   }
               // break;
            }

        }

    }

    public function actionPost(){
        $loader = new WpLoader();
        $loader->loadFromFolder('/home/vlad/Загрузки/add_this/tt');
    }
}
<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use console\components\site_parsers\Parser;
use console\components\file_parsers\ArchiveTxt;
use console\components\file_parsers\ArchiveUrl;
use console\components\loader\WpLoader;
use yii\db\Exception;

class HelloController extends Controller
{
    public function actionIndex(){
        $archiveTxt = new ArchiveTxt();
        //$archiveUrls = $archiveTxt->parse('/home/vlad/work_data/healthlifemag/medical_articles.txt');
        $archiveUrls = $archiveTxt->parseInline('/home/vkarpenko/work_data/project/healthlifemag/test.txt');

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
                      //  echo ' Parser Error(404)' . PHP_EOL;
                        //throw new Exception('404((', 40000);

                       if($exception->getCode() != 40000){
                            throw new Exception($exception->getMessage(), $exception->getCode());
                       }
                       else{
                           echo ' Parser Error(404)' . PHP_EOL;
                       }
                   }
               // break;
            }

        }

    }

    public function actionPost(){
        $loader = new WpLoader();
        $loader->loadFromFolder('/home/vlad/Загрузки/add_this/tt');
    }

    public function actionParse($url = 'https://doc.ua/bolezn/kandidoz/simptomy-i-lechenie-molochnicy-izbavlyaemsya-ot-kandidoza'){
        $path = '/home/vlad/work_data/healthlifemag/parsed';
        $host = parse_url($url, PHP_URL_HOST);        ;

        $queue = new \console\components\parser\Queue($path . DIRECTORY_SEPARATOR . $host . '.csv');

        $parser = new \console\components\parser\sites\DocUa($path);
        $queue->addLinks([$url]);

        while ( ($queueUrl = $queue->get()) ){
            if(strpos($queueUrl, 'bolezn') === false){
                continue;
            }

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

    public function actionTranslate($path = '/home/vlad/work_data/healthlifemag/parsed/doc.ua'){

        $Translator = new \console\components\translator\Translator();
        $HtmlProcessor = new \console\components\translator\HtmlProcessor();

        $filePath = $path . '/dst/_bolezn_kandidoz_simptomy-i-lechenie-molochnicy-izbavlyaemsya-ot-kandidoza.html';
        $filePathTranslated = $path . DIRECTORY_SEPARATOR . 'dst_translate' . DIRECTORY_SEPARATOR . '_bolezn_kandidoz_simptomy-i-lechenie-molochnicy-izbavlyaemsya-ot-kandidoza.html';


      /*  $html = $Translator->translateHtml( file_get_contents($filePath) );
        $html = $HtmlProcessor->process($html);
        file_put_contents($filePathTranslated, $html);
        die();
*/
        $d = dir($path . DIRECTORY_SEPARATOR . 'dst');

        while (false !== ($entry = $d->read())) {
            $filePath = $d->path . DIRECTORY_SEPARATOR  . $entry;
            $filePathTranslated = $path . DIRECTORY_SEPARATOR . 'dst_translate' . DIRECTORY_SEPARATOR . $entry;
            $filePathAlreadyTranslated = $path . DIRECTORY_SEPARATOR . 'dst_already_translate' . DIRECTORY_SEPARATOR . $entry;
            echo $filePath . PHP_EOL;
            echo $filePathTranslated . PHP_EOL ;
            echo '---------------' . PHP_EOL ;
            if(is_file($filePath) && !is_file($filePathTranslated)){

                $html = $Translator->translateHtml( file_get_contents($filePath) );
                $html = $HtmlProcessor->process($html);
                file_put_contents($filePathTranslated, $html);
                rename($filePath, $filePathAlreadyTranslated);

                if(!(rand(1,10)%10)){ //25% sleep
                    echo 'sleep' . PHP_EOL;
                    sleep(2);
                }

            }
        }
        $d->close();



      //  $html = file_get_contents('/home/vlad/tmp/t.html');


    }
}
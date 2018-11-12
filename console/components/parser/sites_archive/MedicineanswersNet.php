<?php

namespace console\components\parser\sites_archive;
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 26.08.18
 * Time: 18:10
 */
use console\components\parser\Parser;
use console\components\parser\ArchiveHelper;

class MedicineanswersNet extends Parser
{
    public $contentSelector = '.main #content';
    public $titleSelector = 'h1';

    use ArchiveHelper;


    protected function isArticle($dom){
        echo 'isArticle . ' . count($dom->find('.main #content')) . PHP_EOL;
        return count($dom->find('.main #content'));
    }

    public function filterUrl(&$links = []){
        foreach ($links as $key => $value) {
            $links[$key] = str_replace('*', '', $links[$key]);


            if (strpos($key, 'www.medicineanswers.net') === false) {
                unset($links[$key]);
            }
        }
    }


    protected function prepareArticleToSave($url, $article, $dom){

        $bread = $dom->find('.leftbox h3 a');
        $cat = '';
        $cnt = 0;
        foreach ($bread as $be){
            $cnt++;
            $cat = ', "cat":"' . $be->textContent . '"';
        }
        if($cnt < 2){
            $cat = '';
        }

        echo $cat . PHP_EOL;

        return $article['title'] . PHP_EOL . PHP_EOL . '<br><br>' . PHP_EOL . $article['content'] . PHP_EOL .
            '<script type="application/ld+json">{"url":"' . $url . '"'. $cat . '}</script>';
    }
}
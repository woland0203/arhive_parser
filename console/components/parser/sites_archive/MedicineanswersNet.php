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
use SebastianBergmann\CodeCoverage\Report\PHP;

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

    public function prepareContentPost($content){
        /*$poses = [
            '<b>Answer: </b>',
            '<b>Answer:</b>',
            '<b>Answers: </b>',
            '<b>Answers:</b>',
        ];*/

        $p = preg_split('|<b>Answer:[\s]*</b>|', $content);
        if(count($p) < 2){
            $p = preg_split('|<b>Answers:[\s]*</b>|', $content);
        }
        $firstPart = '';
        $secondPart = $p[0];
        if(count($p) >= 2){
            $firstPart = $p[0];
            $secondPart = $p[1];
        }

        $firstPart = strip_tags($firstPart);
        $firstPart = preg_replace('|https*:\/\/[^\s\n]+|', '', $firstPart);
        $firstPart = preg_replace('|[\n]+|', "\n", $firstPart);
        $firstPart = trim($firstPart);

        $secondPart = preg_replace("=<br */?>=i", "\n", $secondPart);

        $secondPart = preg_replace('|https*:\/\/[^\s\n]+|', '', $secondPart);
        $secondPart = preg_replace('|[\n]+|', "\n", $secondPart);

        $secondParts = explode('<hr>', $secondPart);
        foreach ($secondParts as &$secondPartItem){
            $secondPartItem = strip_tags($secondPartItem);
            $secondPartItem = trim($secondPartItem);

            $upper = mb_strtoupper(mb_substr($secondPartItem, 0, 2));
            $secondPartItem = '<div class="answer_bl">' . PHP_EOL .
            '<div class="user_bl bg_1">' . $upper . '</div>' . PHP_EOL .
            $secondPartItem .
            '</div>';
        }

        //echo $firstPart . PHP_EOL;
        //print_r($secondParts);
        //echo $firstPart . PHP_EOL . '<b class="answers_header">Answers:</b>' . PHP_EOL . implode(PHP_EOL, $secondParts);
        return $firstPart . '<p><b class="answers_header">Answers:</b></p>' . PHP_EOL . implode(PHP_EOL, $secondParts);
    }

    public function determinateCategory($meta){
        $default = 'Health questions';

        $cats= [
            'Medical questions' => 'Medicine Answers',
            'Alternative medicine questions' => 'Alternative Medicine',
            'Diseases conditions questions' => 'Diseases Conditions',
            'Health care questions' => 'Health Care',
            'Mental health questions' => 'Mental Health',
            'Diet fitness questions' => 'Diet Fitness',
            'Mens health questions' => 'Mens Health',
            'Women\'s health questions' => 'Womens Health',
            'Dental questions' => 'Dental',
            'Health questions' => 'Other'
        ];
        if(empty($meta['cat'])){
            $cat = $default;
        }
        $cat = array_search(trim($meta['cat']), $cats);
        if(empty($cat)){
            $cat = $default;
        }
       // echo $cat;

        return $cat;
    }
}
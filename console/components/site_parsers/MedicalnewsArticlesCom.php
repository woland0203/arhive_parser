<?php
namespace console\components\site_parsers;

use GuzzleHttp\Client; // подключаем Guzzle

class MedicalnewsArticlesCom extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = '#content .entry';
    public $titleSelector = '#content .post_top .post_title h2 a';

    protected function getDomain(){
        return self::DOMAIN;
    }

    public function parseArticle($parseUrl, $url){

        $body = $this->parseExec($parseUrl,$url);
        //$body = file_get_contents('/home/vlad/work_data/healthlifemag/tmp/1.htm');
        //file_put_contents('/home/vlad/work_data/healthlifemag/tmp/1.htm', $body);

        $document = \phpQuery::newDocumentHTML($body);
        $content = $document->find($this->contentSelector);
        $title = $document->find($this->titleSelector);


        $content = $title->html() . PHP_EOL . PHP_EOL . $content->html();
        $content = $this->replace($content);
        $this->saveDst($url, $content);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 22.08.18
 * Time: 22:42
 */

namespace console\components\translator;

use \monitorbacklinks\yii2wp\Wordpress;

class HtmlProcessor
{
    /**
     * @return Wordpress
     */
    public function blog(){
        return \Yii::$app->blog;
    }


    public function process($html){
        return $this->clear($html);
    }

    protected function clear($html){
        $html = preg_replace('|<!--\s*([\s\S]*?)\s*-->|mu', '', $html);
        $html = preg_replace('|<a.+>(?:<.*>)*?(.+?)(?:<.*>)*?<\/a>|mu', '$1', $html);
        $document = \phpQuery::newDocumentHTML($html);
        $document->find('a')->remove();
        return $document->html();

    }

    protected function uploadImages($html){
       // $this->blog()->uploadFile()
        return $html;
    }
}
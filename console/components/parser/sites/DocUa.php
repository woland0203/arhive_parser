<?php

namespace console\components\parser\sites;
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 26.08.18
 * Time: 18:10
 */

use console\components\parser\Parser;

class DocUa extends Parser
{
    public $contentSelector = '.disease-content';
    public $titleSelector = 'h1';

    protected function isArticle($dom){
        return count($dom->find('.disease-content'));
    }

    protected function filterUrl(&$links = []){
        foreach ($links as $key => $value){
            if(strpos($key, 'bolezn') === false){
                unset($links[$key]);
            }
        }
    }
}
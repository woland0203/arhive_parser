<?php

namespace console\components\parser\sites_archive;
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 26.08.18
 * Time: 18:10
 */

use console\components\parser\Parser;

class BoatinfoOrg extends Parser
{
    public $contentSelector = '#content .type-post .entry-content';
    public $titleSelector = '#content .type-post h1';

    protected function isArticle($dom){
        return count($dom->find('.type-post'));
    }

    public function filterUrl(&$links = []){
        foreach ($links as $key => $value){
            if(strpos($key, 'http://www.boatinfo.org') === false){
                unset($links[$key]);
            }
        }
    }
}
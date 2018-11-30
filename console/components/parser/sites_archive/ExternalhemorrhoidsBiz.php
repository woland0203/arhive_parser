<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 19.11.18
 * Time: 22:15
 */

namespace console\components\parser\sites_archive;

use console\components\parser\Parser;
use console\components\parser\ArchiveHelper;

class ExternalhemorrhoidsBiz extends Parser
{
    public $contentSelector = '.postcontent';
    public $titleSelector = 'h1';

    use ArchiveHelper;


    protected function isArticle($dom){
        echo 'isArticle . ' . !count($dom->find('#wp-pagenavi')) . PHP_EOL;
        return !count($dom->find('#wp-pagenavi'));
    }

    public function filterUrl(&$links = []){
        foreach ($links as $key => $value) {
            $links[$key] = str_replace('*', '', $links[$key]);


            if (strpos($key, 'externalhemorrhoids.biz') === false) {
                unset($links[$key]);
            }
        }
    }

    protected function findTitle($document){
        $t1 = $document->find('h1');
        if(count($t1)) return $t1;

        $t1 = $document->find('h2');
        if(count($t1)) return $t1;

        throw new \Exception('404((', 404);
    }

}
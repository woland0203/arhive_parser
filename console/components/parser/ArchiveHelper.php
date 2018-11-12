<?php

namespace console\components\parser;


trait ArchiveHelper {

    protected function prepareUrlSrcDst($url){
        $urlSplit = preg_split('|/web/\d+/|', $url);
        return trim($urlSplit[1]);


    }

}
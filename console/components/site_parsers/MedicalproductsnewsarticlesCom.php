<?php
namespace console\components\site_parsers;

use GuzzleHttp\Client; // подключаем Guzzle

class MedicalproductsnewsarticlesCom extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = '#content .entry';
    public $titleSelector = '#content .post_top .post_title h2 a';


}
<?php
namespace console\components\site_parsers;

use GuzzleHttp\Client; // подключаем Guzzle

class HealthlifeandfamilyCom extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = '.entry';
    public $titleSelector = '.title h2 a';


}
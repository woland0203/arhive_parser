<?php
namespace console\components\site_parsers;

use GuzzleHttp\Client; // подключаем Guzzle

class MedicalresearcharticlesCom extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = '#content .entry-content';
    public $titleSelector = '#content h2';


}
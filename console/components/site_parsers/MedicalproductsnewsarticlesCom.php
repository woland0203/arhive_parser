<?php
namespace console\components\site_parsers;

use GuzzleHttp\Client; // подключаем Guzzle

class MedicalproductsnewsarticlesCom extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = '#outline_box .box_content_standard';
    public $titleSelector = '#outline_box > div > span.page_pr_title_red';


}
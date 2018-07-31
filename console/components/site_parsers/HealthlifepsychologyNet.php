<?php
namespace console\components\site_parsers;



class HealthlifepsychologyNet extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = '.entry';
    public $titleSelector = 'h2.title';


}
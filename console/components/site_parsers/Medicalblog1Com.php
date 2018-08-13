<?php
namespace console\components\site_parsers;



class Medicalblog1Com extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = '.entry';
    public $titleSelector = 'h2.title';


}
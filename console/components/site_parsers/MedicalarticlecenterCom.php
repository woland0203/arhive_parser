<?php
namespace console\components\site_parsers;



class MedicalarticlecenterCom extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = 'body > table.main > tbody > tr > td.main > div.main';
    public $titleSelector = 'body > table.main > tbody > tr > td.main > div.main > h1';


}
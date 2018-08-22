<?php
namespace console\components\site_parsers;


class MedicalnewsWebsite extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = '.entry-content';
    public $titleSelector = 'header > h1';


}
<?php
namespace console\components\site_parsers;

use GuzzleHttp\Client; // подключаем Guzzle

class MedicaldevicearticlesCom extends Parser
{
  //  const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = '#im-mainContent .entry';
    public $titleSelector = '.post h1';


}
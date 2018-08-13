<?php
namespace console\components\site_parsers;

use GuzzleHttp\Client; // подключаем Guzzle

 class Parser{
  //  const PATH = '/home/vkarpenko/work_data/project/healthlifemag/parsed';
    const PATH = '/home/vlad/work_data/healthlifemag/tmp';

   // abstract public function parseArticle($parseUrl, $url);
   // abstract protected function getDomain();

    protected function getDomain($url){
       // return static::DOMAIN;
        return parse_url($url, PHP_URL_HOST);
    }

    public function parseArticle($parseUrl, $url){

        $body = $this->parseExec($parseUrl,$url);

        $srcPath = $this->getSrcPath($url);
        $this->saveSrc($srcPath, $body);


        $document = \phpQuery::newDocumentHTML($body);
        $title = $this->findTitle($document);
        $title = $this->replace($title);

        echo $title . PHP_EOL;

        $content = $this->findContent($document);


        //var_dump($title);
      //  die();


        $content = $this->replace($content);
        $content = $title . PHP_EOL . PHP_EOL . '<br><br>' . PHP_EOL . $content;
        $this->saveDst($url, $content);
    }

     protected function findContent($document){
        return $document->find($this->contentSelector);
     }

     protected function findTitle($document){
         return $document->find($this->titleSelector);
     }

    protected function parseExec($parseUrl, $url, $force = false){
        $srcPath = $this->getSrcPath($url);
        if(file_exists($srcPath) && !$force){
            return file_get_contents($srcPath);
        }

        $client = new Client();

        $res =$client->request('GET', $parseUrl, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            ]
        ]);

        //$res = $client->request('GET', $parseUrl);
        $body = $res->getBody();
        //$body = '111sasa';


        return $body;
    }

    protected function getSrcPath($url){
        $dir =  static::PATH . '/' .$this->getDomain($url);
        if(!is_dir($dir)){
            mkdir($dir);
        }
        $dir = $dir . '/src';
        if(!is_dir($dir)){
            mkdir($dir);
        }
        $query = parse_url($url, PHP_URL_QUERY);
        return  $dir . '/' . str_replace('/', '_', parse_url($url, PHP_URL_PATH)) .
            ($query ? ('?'.$query) : '') .
            '.html';
    }

    protected function saveSrc($srcPath, $body){
        file_put_contents($srcPath, $body);

    }

    protected function saveDst($url, $body){
        $dir =  static::PATH . '/' .$this->getDomain($url);
        if(!is_dir($dir)){
            mkdir($dir);
        }
        $dir = $dir . '/dst';
        if(!is_dir($dir)){
            mkdir($dir);
        }
        $query = parse_url($url, PHP_URL_QUERY);
        $srcPath = $dir . '/' .  str_replace('/', '_', parse_url($url, PHP_URL_PATH)) .
            ($query ? ('?'.$query) : '') .
            '.html';
        file_put_contents($srcPath, $body);
    }

    protected function replace(\phpQueryObject $body){
        $activeObjects = $body->find('script,iframe,frame');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        $body = $body->html();
        return preg_replace('|<a[^>]+>([^>]+)</a>|', '$1', $body);
    }
}
<?php
namespace console\components\parser;

use GuzzleHttp\Client;
use yii\db\Exception; // подключаем Guzzle

abstract class Parser{

    protected $savePath;

    public function __construct($savePath)
    {
        $this->savePath = $savePath;
    }

    public function parse($url){
        $body = $this->parseExec($url);
        $this->saveSrc($url, $body);
        $dom = \phpQuery::newDocumentHTML($body);
        $links = $this->extractLincks($url, $dom);

        if($this->isArticle($dom)){
            $article = $this->parseArticle($dom);
            $this->saveDst($url, $article['title'] . PHP_EOL . PHP_EOL . '<br><br>' . PHP_EOL . $article['content']);
        }
        return $links;
    }

    protected function extractLincks($url, $dom){
        $links = [];
        $domain =  $this->getDomain($url);
        foreach( $dom->find('a') as $link){
            $href = $link->getAttribute('href');
            if(!empty($href)){
                $hrefDomain =  $this->getDomain($href);
                if(empty($hrefDomain)){
                    $hrefPath = trim( parse_url($href, PHP_URL_PATH), '/');
                    $hrefScheme = parse_url($href, PHP_URL_SCHEME);
                    $hrefQuery = parse_url($href, PHP_URL_QUERY);
                    $hrefQuery = !empty($hrefQuery) ? ('?' . $hrefQuery) : '';
                    $links[] = $hrefScheme . '://' . $hrefDomain . '/' . $hrefPath . $hrefQuery;
                }
                if($hrefDomain == $domain){
                    $links[] = $href;
                }
            }
        }
        return $links;
    }

    abstract protected function isArticle($dom);


     protected function getDomain($url){
        return parse_url($url, PHP_URL_HOST);
    }

    public function parseArticle($body){
        $document = \phpQuery::newDocumentHTML($body);
        $title = $this->findTitle($document);
        $title = $this->replace($title);

        echo $title . PHP_EOL;

        $content = $this->findContent($document);

        $content = $this->replace($content);
        return [
            'title' => $title,
            'content' => $content
        ];

    }

     protected function findContent($document){
        return $document->find($this->contentSelector);
     }

     protected function findTitle($document){
         return $document->find($this->titleSelector);
     }

     protected function parseExec($url, $force = false){
        $srcPath = $this->getSrcPath($url);
        if(file_exists($srcPath) && !$force){
            return file_get_contents($srcPath);
        }

        $client = new Client();
        try{
            $res =$client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                ]
            ]);
        }
        catch (\Exception $e){
            throw new \Exception('404((', 40000);
        }
        $body = $res->getBody();
        return $body;
    }

    public function getSrcPath($url){
        $dir =  $this->savePath. '/' .$this->getDomain($url);
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

    protected function saveSrc($url, $body){
        $srcPath = $this->getSrcPath($url);
        file_put_contents($srcPath, $body);

    }

    public function getDstPath($url){
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
    }

    protected function saveDst($url, $body){
        $dstPath = $this->getDstPath($url);
        file_put_contents($dstPath, $body);
    }

    protected function replace(\phpQueryObject $body){
        $activeObjects = $body->find('script,iframe,frame');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        return $activeObjects;
    }
}
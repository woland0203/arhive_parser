<?php
namespace console\components\file_parsers;

class ArchiveTxt{

    /**
     * @param $path
     * @return ArchiveUrl[]
     */
    public function parse($path){
        $data = file($path);
        $r = [];
        foreach ($data as $row){
            $row = explode(',', $row);

            $url = explode('/web/', $row[0]);


             $archiveUrl = new ArchiveUrl();
             $archiveUrl->url = $url[1];
             $archiveUrl->domain = parse_url($url[1], PHP_URL_HOST);
             $archiveUrl->parseUrl = $row[0];

             $parserClasses = explode('.', $archiveUrl->domain);
             array_walk($parserClasses, [$this, 'parseClass']);
             $parserClasses = implode('', $parserClasses);

            $parserClasses = explode('-', $parserClasses);
             array_walk($parserClasses, [$this, 'parseClass']);
             $parserClasses = implode('', $parserClasses);

             $archiveUrl->parserClass = '\console\components\site_parsers\\' . $parserClasses ;
                $r[] = $archiveUrl;
             //yield $archiveUrl;
        }
        return $r;

    }

    /**
     * @param $path
     * @return ArchiveUrl[]
     */
    public function parseInline($path){
        $data = file($path);
        $r = [];
        foreach ($data as $row){
            $row = trim($row);
            $url = preg_split('|/web/\d+/|', $row);
            $url[1] = trim($url[1]);

            //https://web.archive.org/web/20121010084310/http://acidrefluxcough.net/cure-acid-reflux-cough
            $archiveUrl = new ArchiveUrl();
            $archiveUrl->url = $url[1];
            $archiveUrl->domain = parse_url($url[1], PHP_URL_HOST);
            $archiveUrl->parseUrl = $row;

            $parserClasses = explode('.', $archiveUrl->domain);
            array_walk($parserClasses, [$this, 'parseClass']);
            $parserClasses = implode('', $parserClasses);

            $parserClasses = explode('-', $parserClasses);
            array_walk($parserClasses, [$this, 'parseClass']);
            $parserClasses = implode('', $parserClasses);

            $archiveUrl->parserClass = '\console\components\site_parsers\\' . $parserClasses ;
            $r[] = $archiveUrl;
            //yield $archiveUrl;
        }
        return $r;

    }

    protected function parseClass(&$item, $key){
        if($item == 'www') $item='';
        $item = ucfirst($item);
    }
}
<?php
namespace console\components\loader;

use \monitorbacklinks\yii2wp\Wordpress;

class WpLoader{
    /**
     * @return Wordpress
     */
    public function blog(){
        return \Yii::$app->blog;
    }

    public function loadPost($post = []){
        $this->blog()->getClient()->onError(function($error, $event) {
           print_r($error);
        });
        return $this->blog()->newPost($post['title'], mb_convert_encoding($post['content'], 'UTF-8'));
    }

    public function loadFromFolder($folder){
        $d = dir($folder);
        while (false !== ($entry = $d->read())) {
            if(is_file($d->path . DIRECTORY_SEPARATOR . $entry)){
                $post = $this->createPostFromFile($d->path . DIRECTORY_SEPARATOR . $entry);
               if( ($added = $this->loadPost($post)) ){
                   echo 'Added: ' . $added . PHP_EOL;
               }
            }
        }
        $d->close();
    }

    public function createPostFromFile($file){
        $content = file_get_contents($file);
        $matces= preg_split('|\n\n|', $content);
        $title = $this->prepareTitle($matces[0]);

        unset($matces[0]);
        $content = implode((PHP_EOL . PHP_EOL), $matces);

        $url = null;
        $metaPattern = '|<script type="application\/ld\+json">(.+)<\/script>|';
        preg_match($metaPattern, $content, $matchMeta);
        if(!empty($matchMeta) && !empty($matchMeta[1])){
            $metaData = json_decode($matchMeta[1], true);
            if($metaData && !empty($metaData['url'])){
                $url =  $metaData['url'];
            }
        }
        $content = preg_replace($metaPattern, '', $content);

        //echo implode((PHP_EOL . PHP_EOL), $matces);
        return [
            'title' => $title,
            'content' => $content,
            'url' => $url,
        ];
    }

    public function prepareTitle($title){
        $title = strip_tags($title);
        $title = preg_replace('|\s+|', ' ', $title);
        $title =preg_replace('|[^a-zA-Z\d-_\s]+|', ' ', $title);
        return trim($title);
    }
}
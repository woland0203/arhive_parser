<?php

namespace console\components\parser\sites;

use console\components\parser\Parser;

class LovepankyCom extends Parser
{
    public $contentSelector = '.mt5.single-post-top-ads';
    public $titleSelector = 'h1';

    protected function isArticle($dom){
        echo 'isArticle ' . count($dom->find($this->contentSelector));
        return count($dom->find($this->contentSelector));
    }

    public function filterUrl(&$links = []){
        foreach ($links as $key => $value){
            if(!preg_match('|^https://www.lovepanky.com/men/|', $value)){
                unset($links[$key]);
            }
        }
    }


    public function parseArticle($document)
    {
        $title = $this->findTitle($document);
        $title = $this->replace($title);

        $contentDom = $this->findContent($document);
        $content = '';
        if(count($contentDom)){
            $content = $this->replace($contentDom);
        }

        $thumbSrc = null;
        if(count($document->find('.for-ads .pb50 img'))){
            $thumbs = $document->find('.for-ads .pb50 img');

            foreach ($thumbs as $thumb){
                  if(!$thumbSrc) $thumbSrc  = $thumb->getAttribute('src');
            }
        }


        return [
            'title' => $title,
            'content' => $content,
            'thumbSrc' => $thumbSrc,
        ];

    }


    protected function prepareArticleToSave($url, $article, $dom){
        $article['url'] = $url;
        return json_encode($article);
    }




    public function createPostFromFile($file)
    {
        return json_decode(file_get_contents($file), true);


    }
}
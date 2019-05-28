<?php

namespace console\components\parser\sites;

use console\components\parser\Parser;

class BluebncCom extends Parser
{
    public $contentSelector = '.post-body.content-stlye';
    public $titleSelector = 'h1';

    protected function isArticle($dom){
        echo 'isArticle ' . count($dom->find($this->contentSelector));
        return count($dom->find($this->contentSelector));
    }

    public function filterUrl(&$links = []){
        foreach ($links as $key => $value){

            if(!preg_match('|^https://blog.bluebnc.com/en/|', $value)){
                unset($links[$key]);
            }
            if(!preg_match('|^https://blog.bluebnc.com/en/post/[\w-]+|', $value)){
                unset($links[$key]);
            }

        }
        print_r($links);
    }


    public function parseArticle($document)
    {
        $title = $this->findTitle($document);
        $title = $this->replace($title);

        $contentDom = $this->findContent($document);


        $activeObjects = $contentDom->find('ins');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }

        $activeObjects = $contentDom->find('.social-buttons');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }

        $htmlText = \console\components\loader\PostHelper::removeAttributes($contentDom);
        $contentDom = \phpQuery::newDocumentHTML($htmlText);

        $htmlText = \console\components\loader\PostHelper::clearAHref($contentDom);
        $contentDom = \phpQuery::newDocumentHTML($htmlText);

        $content = '';
        if(count($contentDom)){
            $content = $this->replace($contentDom);
        }


        $thumbSrc = null;
        if(count($document->find('.post-body.content-stlye img'))){
            $thumbs = $document->find('.post-body.content-stlye img');

            foreach ($thumbs as $thumb){
                  if(!$thumbSrc) $thumbSrc  = $thumb->getAttribute('src');
            }
        }

        return [
            'title' => $title,
            'content' => $content,
            'thumbSrc' => $thumbSrc,
            'url' => $this->currentUrl,
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
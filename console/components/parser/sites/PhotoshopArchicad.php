<?php

namespace console\components\parser\sites;

use console\components\parser\Parser;

class PhotoshopArchicad extends Parser
{
    public $contentSelector = '.articlebody';
    public $titleSelector = 'h1,.contentheading [itemprop="name"],.componentheading';

    protected function isArticle($dom){
        echo 'isArticle ' . count($dom->find('#articlewrapp'));
        return true;

        return count($dom->find('#articlewrapp'));
    }

    public function filterUrl(&$links = []){

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

        $type = null;

        if(count($document->find('.breadcrumbs a')) == 2){
            //is category
            $type = 'category';
            if(!count($document->find('#articlewrapp'))){
                $type = 'category_list';
            }
        }
        if(count($document->find('.breadcrumbs a')) == 3){
            //is post
            $type = 'post';
        }

        $br = $document->find('.breadcrumbs a');
        $parentCategoryUrl = null;
        foreach ($br as $brItem){
            $parentCategoryUrl = $brItem->getAttribute('href');
            $parentCategoryUrl = str_replace('../', '/', $parentCategoryUrl);
        }

       /* print_r([
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'parentCategoryUrl' => $parentCategoryUrl
        ]);
*/

        return [
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'parentCategoryUrl' => $parentCategoryUrl
        ];

    }


    protected function prepareArticleToSave($url, $article, $dom){
        $article['url'] = $url;
        return json_encode($article);
    }


    protected function replace(\phpQueryObject $body){
        $activeObjects = $body->find('script, h1');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        return $body->html();
    }

    public function createPostFromFile($file)
    {
        return json_decode(file_get_contents($file), true);


    }
}
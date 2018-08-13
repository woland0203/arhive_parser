<?php
namespace console\components\site_parsers;


class StarthealthlifeCom extends Parser
{
    const DOMAIN = 'medicalnews-articles.com';
    public $contentSelector = 'body > table > tr:nth-child(2) > td:nth-child(2) > table > tr > td:nth-child(2)';
    public $titleSelector = 'h1';

    protected function findContent($document){
        $body = $document->find('body > table > tr:nth-child(2) > td:nth-child(2) > table > tr > td:nth-child(2)');

        $activeObjects = $body->find('#Layer1');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        $activeObjects = $body->find('*');
        $last = 0;
        foreach ($activeObjects as $elem) {
            if($last || strtolower($elem->tagName) == 'h3' ){
                $pq = pq($elem);
                $pq->remove();
                $last = 1;
            }

        }
        $activeObjects = $body->find('font > i');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        $activeObjects = $body->find('img');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        $activeObjects = $body->find('h1');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        $activeObjects = $body->find('hr');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }

        $activeObjects = $body->find('font');
        $lastF = count($activeObjects);
        $c = 0;
        foreach ($activeObjects as $elem) {
            $c++;
            $pq = pq($elem);
            if($c == $lastF){
                $pq->remove();
            }

        }

        return $body;

    }



}
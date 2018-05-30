<?php
namespace console\components\site_parsers;



class AcidrefluxcoughNet extends Parser
{
    const DOMAIN = 'acidrefluxcough.net';
    public $contentSelector = '.entry-container';
    public $titleSelector = 'h1 a';

    protected function findContent($document){
        $body = $document->find('.entry-container');

        $activeObjects = $body->find('.entry > div');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        $activeObjects = $body->find('.related_post_title');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        $activeObjects = $body->find('.related_post');
        foreach ($activeObjects as $elem) {
            $pq = pq($elem);
            $pq->remove();
        }
        $activeObjects = $body->find('a');
        $last = count($activeObjects);
        $c = 0;
        foreach ($activeObjects as $elem) {
            $c++;
            $pq = pq($elem);
            if($c == $last){
                $pq->remove();
            }

        }


        return $body;

    }

}
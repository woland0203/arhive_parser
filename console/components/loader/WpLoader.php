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
           //    if( ($added = $this->loadPost($post)) ){
             //      echo 'Added: ' . $added . PHP_EOL;
               //}
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
        $content = $this->prepareContent($content);
        echo $content;

        //echo implode((PHP_EOL . PHP_EOL), $matces);
        return [
            'title' => $title,
            'content' => $content
        ];
    }

    public function prepareTitle($title){
        $title = strip_tags($title);
        $title = preg_replace('|\s+|', ' ', $title);
        $title =preg_replace('|[^a-zA-Z\d-_\s]+|', ' ', $title);
        return trim($title);
    }

    public function prepareContent($title){
        $title = $this->rip_tags($title, '<li><ul><ol><b><i><u>');


        $text['content'] = preg_replace('/^[^\w]+/', '', $text['content']);
        $text['content'] = preg_replace('/[\x00-\x09\x0B-\x1F\x7F-\xFF]/', '', $text['content']); //7 bit ASCII
        $text['content'] = preg_replace('|\x0A{2,}|', PHP_EOL . PHP_EOL, $text['content']);

        return trim($title);
    }


public function rip_tags($str, $allowable_tags = '', $strip_attrs = false, $preserve_comments = false, callable $callback = null) {
    $allowable_tags = array_map( 'strtolower', array_filter( // lowercase
        preg_split( '/(?:>|^)\\s*(?:<|$)/', $allowable_tags, -1, PREG_SPLIT_NO_EMPTY ), // get tag names
        function( $tag ) { return preg_match( '/^[a-z][a-z0-9_]*$/i', $tag ); } // filter broken
    ) );
    $comments_and_stuff = preg_split( '/(<!--.*?(?:-->|$))/', $str, -1, PREG_SPLIT_DELIM_CAPTURE );
    foreach ( $comments_and_stuff as $i => $comment_or_stuff ) {
        if ( $i % 2 ) { // html comment
            if ( !( $preserve_comments && preg_match( '/<!--.*?-->/', $comment_or_stuff ) ) ) {
                $comments_and_stuff[$i] = '';
            }
        } else { // stuff between comments
            $tags_and_text = preg_split( "/(<(?:[^>\"']++|\"[^\"]*+(?:\"|$)|'[^']*+(?:'|$))*(?:>|$))/", $comment_or_stuff, -1, PREG_SPLIT_DELIM_CAPTURE );
            foreach ( $tags_and_text as $j => $tag_or_text ) {
                $is_broken = false;
                $is_allowable = true;
                $result = $tag_or_text;
                if ( $j % 2 ) { // tag
                    if ( preg_match( "%^(</?)([a-z][a-z0-9_]*)\\b(?:[^>\"'/]++|/+?|\"[^\"]*\"|'[^']*')*?(/?>)%i", $tag_or_text, $matches ) ) {
                        $tag = strtolower( $matches[2] );
                        if ( in_array( $tag, $allowable_tags ) ) {
                            if ( $strip_attrs ) {
                                $opening = $matches[1];
                                $closing = ( $opening === '</' ) ? '>' : $closing;
                                $result = $opening . $tag . $closing;
                            }
                        } else {
                            $is_allowable = false;
                            $result = '';
                        }
                    } else {
                        $is_broken = true;
                        $result = '';
                    }
                } else { // text
                    $tag = false;
                }
                if ( !$is_broken && isset( $callback ) ) {
                    // allow result modification
                    call_user_func_array( $callback, array( &$result, $tag_or_text, $tag, $is_allowable ) );
                }
                $tags_and_text[$j] = $result;
            }
            $comments_and_stuff[$i] = implode( '', $tags_and_text );
        }
    }
    $str = implode( '', $comments_and_stuff );
    return $str;

    }
}
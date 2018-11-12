<?php
namespace console\components\loader;


class PostHelper{
    public static function createDom($html){
        return \phpQuery::newDocumentHTML(html);
    }

    public static function prepareContent($title){
        //$title = $this->rip_tags($title, '<li><ul><ol><b><i><u>');
        $title = preg_replace('/^[^\w<]+/', '', $title);
        $title = preg_replace('/[\x00-\x09\x0B-\x1F\x7F-\xFF]/', '', $title); //7 bit ASCII
        $title = preg_replace('|\x0A{2,}|', PHP_EOL . PHP_EOL,$title);// \n

        $title = preg_replace('|<h1>[\s\S]*<\/h1>|i', '',$title);
        $title = preg_replace("/<br><br>/", '', $title);
        $title = preg_replace("/<hr>/", '', $title);

        return trim($title);
    }

    public static function removeAttribute(&$node, $attributeName){
        foreach( $node->attributes as $attribute) {
            if($attribute->name == $attributeName){
                $node->removeAttributeNode($attribute);
            }
        }
    }


    public static function removeAttributes($document){
        foreach( $document->find('*')->contents() as $node){
           // $href = $link->getAttribute('href');
          //  $node->a
            if(!empty($node->attributes)) {

                self::removeAttribute($node, 'style');
                self::removeAttribute($node, 'title');
                self::removeAttribute($node, 'alt');
                self::removeAttribute($node, 'id');
                self::removeAttribute($node, 'height');
                self::removeAttribute($node, 'width');
            }
        }
        $html = $document->html();
        $html = preg_replace('/ class="[^"]+"/', '', $html);
        $html = preg_replace("/ class='[^']+'/", '', $html);

        return $html;
    }

    public static function createList($document){
        $counter = 1;
        $list = [];
        foreach( $document->find('h2') as $node){
            $id = 'list_element_' . $counter;
            $node->setAttribute('id', $id);

            $list[$id] = '<li><a href="#'. $id . '">'. trim($node->textContent) . '</a></li>';
            $counter++;
        }
        $html = $document->html();
        if(!empty($list)){
            $html =
            '<ol class="rounded-list">' . PHP_EOL
            . ( implode(PHP_EOL, $list) ) . PHP_EOL
            . '</ol>' . PHP_EOL
            . $html;
        }
        return $html;
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
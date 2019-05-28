<?php
namespace console\components\loader;

use function GuzzleHttp\Psr7\build_query;
use \monitorbacklinks\yii2wp\Wordpress;

class WpLoader
{
    /**
     * @return Wordpress
     */
    public function blog()
    {
        return \Yii::$app->blog;
    }

    public function loadPost($post = [])
    {
        $this->blog()->getClient()->onError(function ($error, $event) {
            print_r($error);
        });
        $thumbnailId = !empty($post['thumbnail_id']) ? $post['thumbnail_id'] : null;
       // if(empty($images)){
           // $images = $this->getImageFromSearch($post['title']);
        //}
        $post = $this->prepareImages($post);

       /* if(!empty($post['images'])){
            $thumbnail = current($post['images']);
            $thumbnailId = !empty($thumbnail['attachment_id']) ? $thumbnail['attachment_id'] : null;
        }*/
        if(!empty( $post['category_id'])){
            $categories = array( $post['category_id'] );
        }

        if(!empty( $post['categories'])){
            $categories =  array_unique( array_values($post['categories']) );
        }
        $content = [
            'post_thumbnail' => $thumbnailId,
            /*'custom_fields' => [
                [
                    'key'   => 'content_featured_img',
                    'value' => 'off'
                ]
            ]*/
        ];
        if(!empty($categories)){
            $content['terms_names'] = [ 'category' => $categories];
        }

        return $this->blog()->newPost($post['title'], mb_convert_encoding($post['content'], 'UTF-8'), $content);
    }


    public function createPostFromFile($file)
    {
        $content = file_get_contents($file);
        $matces = preg_split('|\n\n|', $content);
        $title = $this->prepareTitle($matces[0]);

        unset($matces[0]);
        $content = implode((PHP_EOL . PHP_EOL), $matces);

        $url = null;
        $metaData = null;
        $metaPattern = '|<script type="application\/ld\+json">(.+)<\/script>|';
        preg_match($metaPattern, $content, $matchMeta);
        if (!empty($matchMeta) && !empty($matchMeta[1])) {
            $metaData = json_decode($matchMeta[1], true);
            if ($metaData && !empty($metaData['url'])) {
                $url = $metaData['url'];
            }
        }
        $content = preg_replace($metaPattern, '', $content);

        //echo implode((PHP_EOL . PHP_EOL), $matces);
        return [
            'title' => $title,
            'content' => $content,
            'url' => $url,
            'metaData' => $metaData,
        ];
    }

    public function prepareTitle($title)
    {
        $title = strip_tags($title);
        $title = preg_replace('|\s+|', ' ', $title);
        $title = preg_replace('|[^a-zA-Z\d-_\s\?]+|', ' ', $title);
        return trim($title);
    }

    public function prepareImages($post)
    {
        $content = $post['content'];
        $url = $post['url'];

        $document = \phpQuery::newDocumentHTML($content);
        $images = [];
        foreach ($document->find('img') as $img) {

            $src = $img->getAttribute('src');
            $srcLazy = $img->getAttribute('data-lazy-src');

            echo PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . 'WITH_IMG1 ' . $src ;
            echo PHP_EOL  . 'WITH_IMG2 ' . $srcLazy .  PHP_EOL . PHP_EOL . PHP_EOL;

            $src = !$srcLazy ? $src : $srcLazy;

            $srcData = $img->getAttribute('data-src');
            $src = !$srcData ? $src : $srcData;

            $src = $this->resolveUrl($url, $src);


            $image = $src = $this->loadImage($src);

            if($image){
                $images[] = $image;
                $img->setAttribute('src', $image['url']);
            }

        }
        $content = $document->html();
        $post['images'] = $images;
        $post['content'] = $content;

        return $post;
    }

    public function resolveUrl($currentUrl, $url)
    {
        $returnUrl = parse_url($currentUrl, PHP_URL_SCHEME) . '://' . parse_url($currentUrl, PHP_URL_HOST) . '/' . $url;
        $firstProt = substr($url, 0, 2);
        if ($firstProt == '//') {
            $returnUrl =  parse_url($currentUrl, PHP_URL_SCHEME) . ':' . $url;
        }

        $first = substr($url, 0, 1);
        if ($first == '/') {
            $returnUrl =  parse_url($currentUrl, PHP_URL_SCHEME) . '://' . parse_url($currentUrl, PHP_URL_HOST) . $url;
        }
        $firstProt = substr($url, 0, 4);
        if ($firstProt == 'http') {
            $returnUrl =  $url;
        }
        //echo '$returnUrl ' . $returnUrl . PHP_EOL;
        $returnUrl = preg_replace('|\s|', '+', $returnUrl);
        //echo '$returnUrl ' . $returnUrl . PHP_EOL;
       // die();
        return $returnUrl;
    }

    public function loadImage($url)
    {
        try{
            return $this->loadImageWithQ($url);
        } catch (\Exception $e){
            return;
        }


        echo 'ImgUrl:' . $url . PHP_EOL;
        $path = parse_url($url, PHP_URL_PATH);
        $pathParts = explode('/', $path);
        $name = array_pop($pathParts);

        //if(!e)
        $pathPartsStr = implode('_',$pathParts);
        $pathPartsStr = !empty($pathPartsStr) ? (DIRECTORY_SEPARATOR . $pathPartsStr) : '';

        $dstPath = \Yii::$app->params['tmpDir'] . $pathPartsStr;
        if(!is_dir($dstPath)) mkdir($dstPath);

        $dstFile = $dstPath . DIRECTORY_SEPARATOR . urldecode($name);
        $dstFile = preg_replace('|\s+|', '+', $dstFile);
        //file_put_contents($dstFile, file_get_contents($url));
        $queryPos = strpos($url, '?');
        if($queryPos !== false){
            $url = substr($url, 0, $queryPos);
        }
        echo '$dstFile: ' . $dstFile . PHP_EOL;
        //  echo 'cd ' . $dstPath . ' && wget --timeout=10 --connect-timeout=10 --read-timeout=10 --tries=1 -t 1 ' . $url . PHP_EOL;

        exec('cd ' . $dstPath . ' && wget --timeout=10 --connect-timeout=10 --read-timeout=10 --tries=1 -t 1 ' . $url);


        if(!file_exists($dstFile)) return false;

        //   $dstFile = $this->resizeImage($dstFile);

        $fh = fopen($dstFile, 'r');
        $fs = filesize($dstFile);
        $theData = fread($fh, $fs);
        fclose($fh);

        $this->blog()->getClient()->onError(function ($error, $event) {
            print_r($error);
        });

        $image = $this->blog()->uploadFile($name, mime_content_type($dstFile), $theData);
        //print_r($image);

        // unlink($dstFile);
        //  unlink($dstPath);

        return $image;
    }


    public function loadImageWithQ($url)
    {
        echo 'ImgUrl:' . $url . PHP_EOL;
        $path = parse_url($url, PHP_URL_PATH);
        $pathParts = explode('/', $path);
        $name = array_pop($pathParts);

        $urlQuery = parse_url($url, PHP_URL_QUERY);
        if(!empty($urlQuery)){
            $name = urldecode($name) . '?'. preg_replace('|/|', '%2F', $urlQuery);
        }

        //if(!e)
        $pathPartsStr = implode('_',$pathParts);
        $pathPartsStr = !empty($pathPartsStr) ? (DIRECTORY_SEPARATOR . $pathPartsStr) : '';

        $dstPath = \Yii::$app->params['tmpDir'] . $pathPartsStr;
        if(!is_dir($dstPath)) mkdir($dstPath);

        $dstFile = $dstPath . DIRECTORY_SEPARATOR . $name;
        $dstFile = preg_replace('|\s+|', '+', $dstFile);
        //file_put_contents($dstFile, file_get_contents($url));
        $queryPos = strpos($url, '?');
        if($queryPos !== false){
            //   $url = substr($url, 0, $queryPos);
        }
        echo '$dstFile: ' . $dstFile . PHP_EOL;
        echo 'cd ' . $dstPath . ' && wget --timeout=10 --connect-timeout=10 --read-timeout=10 --tries=1 -t 1 ' . $url . PHP_EOL;

        exec('cd ' . $dstPath . ' && wget --timeout=10 --connect-timeout=10 --read-timeout=10 --tries=1 -t 1 ' . $url);


        if(!file_exists($dstFile)) return false;

        //   $dstFile = $this->resizeImage($dstFile);

        $fh = fopen($dstFile, 'r');
        $fs = filesize($dstFile);
        $theData = fread($fh, $fs);
        fclose($fh);

        $this->blog()->getClient()->onError(function ($error, $event) {
            print_r($error);
        });

        $image = $this->blog()->uploadFile($name, mime_content_type($dstFile), $theData);
        //       var_dump($image);
///die();
        // unlink($dstFile);
        //  unlink($dstPath);

        return $image;
    }

    public function getImageFromSearch($q){
        //echo 'getImageFromSearch --- ' . $q . PHP_EOL;
        $dstFile = \Yii::$app->params['tmpDir'] . DIRECTORY_SEPARATOR .  mktime(true);
        try{
        exec('cd ' . \Yii::$app->params['tmpDir'] . ' && wget -O ' . $dstFile . ' --timeout=10 --connect-timeout=10 --read-timeout=10 --tries=1 -t 1 https://www.bing.com/images/search?' . build_query(['q' => $q]));

            $document = \phpQuery::newDocumentHTML(file_get_contents($dstFile));
            foreach($document->find('.content .thumb') as $node){

                $href = $node->getAttribute('href');
                if(!empty($href)){
                    unlink($dstFile);
                    return [$this->loadImage($href)];
                }
            }
        }
        catch (\Exception $e){

        }

        unlink($dstFile);
        return [];
    }

    public function resizeImage($fn){
        $pathParts = pathinfo($fn);
        $target_filename_here = $pathParts['dirname'] . DIRECTORY_SEPARATOR .  $pathParts['filename'] . '.png';
        $size = getimagesize($fn);
        $dstSize = 600;

        if($size[0] < $dstSize && $size[1] < $dstSize){
            return $fn;
        }
        $ratio = $size[0]/$size[1]; // width/height
        if( $ratio > 1) {
            $width = $dstSize;
            $height = $dstSize/$ratio;
        }
        else {
            $width = $dstSize*$ratio;
            $height = $dstSize;
        }

        $src = imagecreatefromstring(file_get_contents($fn));
        $dst = imagecreatetruecolor($width,$height);
        imagecopyresampled($dst,$src,0,0,0,0,$width,$height,$size[0],$size[1]);
        imagedestroy($src);
        imagepng($dst,$target_filename_here); // adjust format as needed
        imagedestroy($dst);
       // print_r(error_get_last());
        return $target_filename_here;

    }
}
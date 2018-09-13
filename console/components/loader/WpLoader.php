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


        $thumbnailId = null;
        $images = $this->prepareImages($post['content'], $post['url']);

        if(empty($images)){
            $images = $this->getImageFromSearch($post['title']);
        }

        if(!empty($images)){
            $thumbnail = current($images);
            $thumbnailId = $thumbnail['id'];
        }

        return $this->blog()->newPost($post['title'], mb_convert_encoding($post['content'], 'UTF-8'), [
            'post_thumbnail' => $thumbnailId,
            'terms_names' => [ 'category' => array( $post['category_id'] ) ],
            'custom_fields' => [
                [
                    'key'   => 'content_featured_img',
                    'value' => 'off'
                ]
            ]
        ]);
    }

    public function createPostFromFile($file)
    {
        $content = file_get_contents($file);
        $matces = preg_split('|\n\n|', $content);
        $title = $this->prepareTitle($matces[0]);

        unset($matces[0]);
        $content = implode((PHP_EOL . PHP_EOL), $matces);

        $url = null;
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
        ];
    }

    public function prepareTitle($title)
    {
        $title = strip_tags($title);
        $title = preg_replace('|\s+|', ' ', $title);
        $title = preg_replace('|[^a-zA-Z\d-_\s]+|', ' ', $title);
        return trim($title);
    }

    protected function prepareImages(&$content, $url)
    {
        $document = \phpQuery::newDocumentHTML($content);
        $images = [];
        foreach ($document->find('img') as $img) {
            $src = $img->getAttribute('src');
            $src = $this->resolveUrl($url, $src);

            $image = $src = $this->loadImage($src);
            $images[] = $image;
            $img->setAttribute('src', $image['url']);
        }
        $content = $document->html();
        return $images;
    }

    protected function resolveUrl($currentUrl, $url)
    {
        $first = substr($url, 0, 1);
        if ($first == '/') {
            return parse_url($currentUrl, PHP_URL_SCHEME) . '://' . parse_url($currentUrl, PHP_URL_HOST) . $url;
        }

        return parse_url($currentUrl, PHP_URL_SCHEME) . '://' . parse_url($currentUrl, PHP_URL_HOST) . '/' . $url;
    }

    protected function loadImage($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $pathParts = explode('/', $path);
        $name = array_pop($pathParts);

        $dstFile = \Yii::$app->params['tmpDir'] . DIRECTORY_SEPARATOR . urldecode($name);
        $dstFile = preg_replace('|\s+|', '+', $dstFile);
        //file_put_contents($dstFile, file_get_contents($url));
        $queryPos = strpos($url, '?');
        if($queryPos !== false){
            $url = substr($url, 0, $queryPos);
        }
        exec('cd ' . \Yii::$app->params['tmpDir'] . ' && wget --timeout=10 --connect-timeout=10 --read-timeout=10 --tries=1 -t 1 ' . $url);

        echo $dstFile . PHP_EOL;
        $dstFile = $this->resizeImage($dstFile);

        $fh = fopen($dstFile, 'r');
        $fs = filesize($dstFile);
        $theData = fread($fh, $fs);
        fclose($fh);

        $image = $this->blog()->uploadFile($name, mime_content_type($dstFile), $theData);
        unlink($dstFile);
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
                    return [$this->loadImage($href)];
                }
            }
        }
        catch (\Exception $e){

        }

      //  unlink($dstFile);
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
        print_r(error_get_last());
        return $target_filename_here;

    }
}
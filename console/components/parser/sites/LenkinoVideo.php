<?php

namespace console\components\parser\sites;
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 26.08.18
 * Time: 18:10
 */
use console\components\parser\Parser;

class LenkinoVideo extends Parser
{
    public $contentSelector = '.video-description';
    public $titleSelector = 'h1';

    protected function isArticle($dom){
        return count($dom->find('.video-player'));
    }

    public function filterUrl(&$links = []){

    }

    public function parseArticle($document){

        $title = $this->findTitle($document);
        $title = $this->replace($title);

        $content = $this->findContent($document);
        $content = $this->replace($content);

        $categories = [];
        $categoriesDom = $document->find('[itemprop=genre]');
        foreach ($categoriesDom as $category){
            $categories[] = parse_url($category->getAttribute('href'), PHP_URL_PATH);
        }

        $stars = [];
        $starsDom = $document->find('.link-models a');
        foreach ($starsDom as $star){
            $stars[] = [
                'name' => $star->textContent,
                'link' => parse_url($star->getAttribute('href'), PHP_URL_PATH)
            ];
        }

        $studios = [];
        $sudiosDom = $document->find('.link-sites a');

        foreach ($sudiosDom as $sudio){

            $studios[] = [
                'name' => $sudio->textContent,
                'link' => parse_url($sudio->getAttribute('href'), PHP_URL_PATH)
            ];
     }

        $relateds = [];
        $relatedDom = $document->find('.box.list-videos .item a');
        $i = 0;
        foreach ($relatedDom as $related){
            $relateds[$i] = [
                'link' => parse_url($related->getAttribute('href'), PHP_URL_PATH),
                'data-id' => $related->getAttribute('data-id'),
            ];
            $i++;
        }
        $relatedDom = $document->find('.box.list-videos .item a img.thumb');
        $i = 0;
        foreach ($relatedDom as $related){
            $relateds[$i] += [
                 'name' => $related->getAttribute('alt'),
                'src'  => $related->getAttribute('src'),
            ];
            $i++;
        }

        preg_match('|flashvars[^{]+({[\s\S]+})[\s\S]+kt_player\(|', $document->html(), $match);
        $videoData = isset($match[1]) ? $match[1] : null;

        return [
            'title' => $title,
            'content' => $content,
            'categories' => $categories,
            'stars' => $stars,
            'studios' => $studios,
            'relateds' => $relateds,
            'videoData' => $videoData,
        ];

    }

    protected function prepareArticleToSave($url, $article, $dom){
        $article['url'] = $url;
        return json_encode($article);
    }

    /*************************** FOR POSTING START **************************/
    public function createPostFromFile($file)
    {
        $data = json_decode( file_get_contents($file), true );

        $title = !empty($data['title_eng']) ? $data['title_eng'] : '';
        $content = !empty($data['content_eng']) ? $data['content_eng'] : '';
        $categories = [];
        if(!empty($data['categories'])){
            foreach ($data['categories'] as $category){
                $category = trim($category);
                $category = trim($category, '/');
                $categoryName = preg_replace('|[-_]+|', ' ', $category);
                $categories[$category] = ucfirst($categoryName);
            }
        }

        $videoId = null;
        $data['videoData'] = preg_replace('|\s+|', ' ', $data['videoData']);
        $data['videoData'] = trim($data['videoData'], '}{');
        $videoData = explode(',', $data['videoData']);
        $videoDataResult = ['alt_video' => []];
        foreach ($videoData as $videoDataRow){
            $videoDataRow = trim($videoDataRow);
            $pos = strpos($videoDataRow, ':');
            if($pos !== false){
                $videoDataArr[0] = substr($videoDataRow, 0, $pos);
                $videoDataArr[1] = substr($videoDataRow, $pos);
                if(count($videoDataArr) == 2){
                    $key = trim($videoDataArr[0]);
                    $val = trim($videoDataArr[1], ":");
                    $val = trim($val);
                    $val = trim($val, "'");
                    if( in_array($key, ['preview_url', 'video_url']) ){
                        $videoDataResult[$key] = $val;
                    }
                    if( $key == 'video_id' ){
                        $videoId = $val;
                    }
                    if( $key == 'video_alt_url' ){
                        $videoDataResult['alt_video'][0]['url'] = $val;
                    }
                    if( $key == 'video_alt_url_text' ){
                        $videoDataResult['alt_video'][0]['text'] = $val;
                    }

                    if( $key == 'video_alt_url2' ){
                        $videoDataResult['alt_video'][1]['url'] = $val;
                    }
                    if( $key == 'video_alt_url2_text' ){
                        $videoDataResult['alt_video'][1]['text'] = $val;
                    }


                    if( $key == 'video_alt_url3' ){
                        $videoDataResult['alt_video'][2]['url'] = $val;
                    }
                    if( $key == 'video_alt_url3_text' ){
                        $videoDataResult['alt_video'][2]['text'] = $val;
                    }

                }
            }
        }
        if(empty($videoDataResult['video_url'])) return false;

        $newVideoUrl = $this->resolveRedirects($videoDataResult['video_url']);
        $wpUrl = '/get_vfile/'.base64_encode($newVideoUrl);
        $this->saveVideoRedirect($videoId, $videoDataResult['video_url'], $newVideoUrl, $wpUrl);
        $videoDataResult['video_url'] = $wpUrl;



        foreach ($videoDataResult['alt_video'] as $altVideoKey => &$altVideo){
            $newAltVideoUrl = $this->resolveRedirects($altVideo['url']);
            $wpAltUrl = '/get_vfile/'.base64_encode($newAltVideoUrl);
            $this->saveVideoRedirect($videoId, $altVideo['url'], $newAltVideoUrl, $wpAltUrl);
            $videoDataResult['alt_video'][$altVideoKey]['url'] = $wpAltUrl;
        }

        //echo implode((PHP_EOL . PHP_EOL), $matces);
        return [
            'id' => $videoId,
            'title' => trim($title, '.'),
            'content' => $content,
            'categories' => $categories,
            'stars' => !empty($data['stars']) ? $data['stars'] : [],
            'studios' => !empty($data['studios']) ? $data['studios'] : [],
            'videoDataResult' => $videoDataResult,
            'relateds' => !empty($data['relateds']) ? $data['relateds'] : []
        ];
    }

    public function resolveRedirects($url){
        $header = [
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36',
        ];
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 4);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt( $ch, CURLOPT_NOBODY, 1);
        curl_exec( $ch );
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close ($ch);
        return $last_url;
    }

    public function saveVideoRedirect($lenkinoId, $lenkinoUrl, $resultUrl, $wpUrl)
    {
        /**
         * @var \yii\db\Connection $dbph
         */
        $dbph = \Yii::$app->dbph;
        $dbph->createCommand()->insert(
    'import_video_url_mapper', [
                'lenkino_post_id' => $lenkinoId,
                'lenkino_url' => $lenkinoUrl,
                'result_url' => $resultUrl,
                'wp_url' => $wpUrl
        ])->execute();
    }

    /*************************** FOR POSTING END **************************/
}
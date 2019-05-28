<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 16.12.18
 * Time: 17:26
 */

namespace console\components\loader;


class WpLoaderLenkino extends WpLoader
{
    protected $createdCategories = [];
    protected $createdStars = [];
    protected $createdChanels = [];

    const CAT_ID = 227;
    const STARS_ID = 228;
    const CHANELS_ID = 229;

    public function __construct()
    {
        $parentCat = self::CAT_ID;
        $parentStars = self::STARS_ID;
        $parentChanels = self::CHANELS_ID;
        $taxonomy = $this->blog()->getTerms('category');

        foreach ($taxonomy as $taxonomyItem){
            if($taxonomyItem['parent'] == $parentCat){
                $this->createdCategories[ $taxonomyItem['name'] ] = $taxonomyItem['term_id'];
            }
            if($taxonomyItem['parent'] == $parentStars){
                $this->createdStars[ $taxonomyItem['name'] ] = $taxonomyItem['term_id'];
            }
            if($taxonomyItem['parent'] == $parentChanels){
                $this->createdChanels[ $taxonomyItem['name'] ] = $taxonomyItem['term_id'];
            }
        }

    }

    public function prepareCategories($categories){
        foreach ($categories as $categoryKey => $categoryName){
            if(isset($this->createdCategories[$categoryName])){
                continue;
            }
            $this->createdCategories[$categoryName] = $this->loadCategory($categoryKey, $categoryName, self::CAT_ID);
            $this->saveCategoryMapper($this->createdCategories[$categoryName], 'cat', $categoryName);

        }
        return $categories;
    }

    public function prepareStars($stars){
        $result = [];
        if(count($stars)>1) $stars = [array_shift($stars)];

        foreach ($stars as $starRow){
            $result[] = $starRow['name_eng'];
            $url = str_replace('/pornstar/', '', $starRow['link']);
            if(isset($this->createdStars[$starRow['name_eng']])){
                continue;
            }
            $this->createdStars[$starRow['name_eng']] = $this->loadCategory($url, $starRow['name_eng'], self::STARS_ID);
            $this->saveCategoryMapper($this->createdStars[$starRow['name_eng']], 'star', $starRow['name']);
        }
        return $result;
    }

    public function prepareChanels($studios){
        $result = [];
        foreach ($studios as $studioRow){
            $result[] = $studioRow['name'];
            $url = str_replace('/site/', '', $studioRow['link']);
            $url = str_replace('/channel/', '',$url);
            if(isset($this->createdChanels[$studioRow['name']])){
                continue;
            }
            $this->createdChanels[$studioRow['name']] = $this->loadCategory($url, $studioRow['name'], self::CHANELS_ID);
            $this->saveCategoryMapper($this->createdChanels[$studioRow['name']], 'chanel', $studioRow['name']);
        }
        return $result;
    }

    public function loadCategory($url, $name, $parentId){
        $this->blog()->getClient()->onError(function ($error, $event) {
            print_r($error);
        });
        return $this->blog()->newTerm($name, 'category', $url, '', $parentId);
    }

    protected function saveCategoryMapper($wpId, $type, $lenkinoName){
        /*
                      * @var \yii\db\Connection $dbph
                      */
        $dbph = \Yii::$app->dbph;
        $dbph->createCommand()->insert(
            'import_cat_mapper', [
            'wp_id' => $wpId,
            'type' => $type,
            'lenkino_name' => $lenkinoName
        ])->execute();
    }

    public function prepareImages($post)
    {
        $images = [
            $this->loadImage($post['videoDataResult']['preview_url'])
        ];
        return $images;
    }

    public function resizeImage($fn){
        return $fn;
    }

    public function prepareContent($post){

        $thumbnail = current($post['images']);
        $sources = '';
        foreach ($post['videoDataResult']['alt_video'] as $altVideo){
            $size = preg_replace('|[^\d]+|', '', $altVideo['text']);
            $sources .= '<source src="' . $altVideo['url'] . '" type="video/mp4" size="'. $size . '">';
        }
        $poster = !empty($thumbnail['link']) ? $thumbnail['link'] : '';

        return '<video id="player" src="'. $post['videoDataResult']['video_url'] . '" poster="'. $poster . '" controls="controls">' . $sources . '</video>' .
        '<p>'. $post['content'] .'</p>';
    }

    public function setPostAsExist($lenkinoId, $wpId)
    {
        /**
         * @var \yii\db\Connection $dbph
         */
        $dbph = \Yii::$app->dbph;
        $dbph->createCommand()->insert(
            'import_post_mapper', ['wp_id' => $wpId, 'lenkino_id' => $lenkinoId]
        )->execute();
    }

    public function existPost($id)
    {
        /**
         * @var \yii\db\Connection $dbph
         */
        $dbph = \Yii::$app->dbph;
        return $dbph->createCommand('select wp_id from import_post_mapper where lenkino_id=:lenkino_id limit 1',
            [':lenkino_id' => $id])->queryScalar();

    }

    public function createRelatedPosts($id, $relatedPosts)
    {
        $relatedPostsData = [];
        foreach ($relatedPosts as $postId){
            $post = $this->blog()->getPost($postId);
            if($post && !empty($post['post_id'])){
                $relatedPostsData[$post['post_id']] = [
                    'id' => $post['post_id'],
                    'title' => $post['post_title'],
                    'permalink' => '/'.$post['post_id'],
                    'status' => $post['post_status'],
                    'date' => date('Y-m-d H:i:s',$post['post_date']->timestamp)

                ];
            }

        }
        /*
         * @var \yii\db\Connection $dbph
         */
        $dbph = \Yii::$app->dbph;
        $dbph->createCommand()->insert(
            'wp_postmeta', ['post_id' => $id, 'meta_key' => 'crp_relations_to', 'meta_value' => serialize($relatedPostsData)]
        )->execute();

    }

}
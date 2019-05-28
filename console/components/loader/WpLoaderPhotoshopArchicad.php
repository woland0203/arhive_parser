<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 24.03.19
 * Time: 12:51
 */

namespace console\components\loader;


class WpLoaderPhotoshopArchicad extends WpLoader
{
    public function findCategory($taxonomies, $searchValue){
        $parentCatId = null;
        foreach ($taxonomies as $taxonomy){
            if(!empty($taxonomy['custom_fields'])){
                foreach ($taxonomy['custom_fields'] as $customField){
                    if($customField['key'] == 'post_url' && $customField['value'] == $searchValue){
                        $parentCatId = $taxonomy['term_id'];
                        break;
                    }
                }
            }
        }
        return $parentCatId;
    }
}
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
        //$r = file_get_contents('/home/vlad/Загрузки/add_this/74.32_candida-remedies.html.html');
        $r = $this->blog()->newPost('Candida Remedies',
            htmlspecialchars(
                file_get_contents('/home/vlad/Загрузки/add_this/74.32_candida-remedies.html.html'),
                ENT_SUBSTITUTE
            )
        );
       // echo htmlentities($r);


    }
}
<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 24.11.18
 * Time: 22:37
 */

namespace app\models;

use  yii\base\Model;

class SiteRepository extends Model
{
    const PROXY_LIST = '/bin/proxy_list.txt';
    const PROXY_IP = '127.0.0.1';
    const PROXY_PORT_PREFIX = '80';

    /**
     * @return Site
     */
    public function getAvailableSite(){
        \Yii::$app->db->createCommand('set autocommit=0')->execute();
        $data = \Yii::$app->db->createCommand('select * from site where status=' . Site::STATUS_AVAILABLE . ' and active=1 limit 1 for update')->queryOne();
        if(!empty($data)){
            \Yii::$app->db->createCommand(' update site set status=' . Site::STATUS_IN_PROCESS . ' where id='. $data['id'])->execute();
        }
        \Yii::$app->db->createCommand('commit')->execute();
        if(empty($data)){
            return null;
        }
        $site = new Site();
        $site->setIsNewRecord(false);
        $site->setAttributes($data, false);
        return $site;
    }

    public function setAccessesAttributes(Site &$site){

        if(!$site->login_id){
            $login = Login::find()->orderBy('weight, id')->limit(1)->one();
            $site->setAttribute('login_id', $login->id);
        }

        if(!$site->password_id){
            $password = Password::find()->orderBy('weight, id')->limit(1)->one();
            $site->setAttribute('password_id', $password->id);
        }

        $password = Password::find()->where('id>' .  (int)$site->password_id)->orderBy('weight, id')->limit(1)->one();

        if(!$password){
            $login = Login::find()->where('id>' .  (int)$site->login_id)->orderBy('weight, id')->limit(1)->one();
            if(!$login){
                return false;
            }
            $site->setAttribute('login_id', $login->id);
            $password = Password::find()->orderBy('weight, id')->limit(1)->one();
        }
        $site->setAttribute('password_id', $password->id);
        return true;
    }
}
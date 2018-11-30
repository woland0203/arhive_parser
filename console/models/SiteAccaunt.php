<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "site_accaunt".
 *
 * @property int $id
 * @property int $site_id
 * @property string $login
 * @property string $password
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Site $site
 */
class SiteAccaunt extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'site_accaunt';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['site_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['login', 'password'], 'string', 'max' => 255],
            [['site_id'], 'exist', 'skipOnError' => true, 'targetClass' => Site::className(), 'targetAttribute' => ['site_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'site_id' => 'Site ID',
            'login' => 'Login',
            'password' => 'Password',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::className(), ['id' => 'site_id']);
    }
}

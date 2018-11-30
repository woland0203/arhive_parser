<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "password".
 *
 * @property int $id
 * @property string $password
 * @property int $weight
 * @property string $created_at
 *
 * @property Site[] $sites
 */
class Password extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'password';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['weight'], 'integer'],
            [['created_at'], 'safe'],
            [['password'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'password' => 'Password',
            'weight' => 'Weight',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSites()
    {
        return $this->hasMany(Site::className(), ['password_id' => 'id']);
    }
}

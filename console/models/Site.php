<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "site".
 *
 * @property int $id
 * @property string $host
 * @property int $https
 * @property int $login_id
 * @property int $password_id
 * @property string $ip
 * @property int $port
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Login $login
 * @property Password $password
 * @property SiteAccaunt[] $siteAccaunts
 */
class Site extends \yii\db\ActiveRecord
{
    const STATUS_AVAILABLE = 0;
    const STATUS_IN_PROCESS = 1;
    const STATUS_COMPLEATED = 2;
    const STATUS_UNCOMPLEATED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'site';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['https', 'login_id', 'password_id', 'port', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['host'], 'string', 'max' => 255],
            [['ip'], 'string', 'max' => 15],
            [['login_id'], 'exist', 'skipOnError' => true, 'targetClass' => Login::className(), 'targetAttribute' => ['login_id' => 'id']],
            [['password_id'], 'exist', 'skipOnError' => true, 'targetClass' => Password::className(), 'targetAttribute' => ['password_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'host' => 'Host',
            'https' => 'Https',
            'login_id' => 'Login ID',
            'password_id' => 'Password ID',
            'ip' => 'Ip',
            'port' => 'Port',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLogin()
    {
        return $this->hasOne(Login::className(), ['id' => 'login_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPassword()
    {
        return $this->hasOne(Password::className(), ['id' => 'password_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSiteAccaunts()
    {
        return $this->hasMany(SiteAccaunt::className(), ['site_id' => 'id']);
    }

    public function compleated(){
        //echo ' compleated ' . PHP_EOL;
        $this->status = self::STATUS_COMPLEATED;
        $siteAccaunt = new SiteAccaunt();
        $siteAccaunt->setAttributes([
            'login'     => $this->login->login,
            'password'  => $this->password->password
        ], false);
        $siteAccaunt->save();
        \Yii::$app->db->createCommand(' update site set ' .
            'status=' . Site::STATUS_COMPLEATED . ', '.
            'port=' . $this->port. ', '.
            'login_id=' . $this->login_id. ', '.
            'password_id=' . $this->password_id .
            ' where id='. $this->id)->execute();
        //$this->update();
    }

    public function available(){
      //  echo ' available ' . PHP_EOL;

        $this->status = self::STATUS_AVAILABLE;
        //$r = $this->update(false);
        \Yii::$app->db->createCommand(' update site set ' .
          'status=' . Site::STATUS_AVAILABLE . ', '.
          'port=' . $this->port. ', '.
          'login_id=' . $this->login_id. ', '.
          'password_id=' . $this->password_id .
        ' where id='. $this->id)->execute();
    }

    public function uncompleated(){
          echo ' uncompleated ' .  $this->id . PHP_EOL;
        //$r = $this->update(false);

       $r = \Yii::$app->db->createCommand('update site set ' .
            '`status`=' . self::STATUS_UNCOMPLEATED .', '.
            'port=' . $this->port. ', '.
           'login_id=' . $this->login_id. ', '.
            'password_id=' . $this->password_id .
            ' where id='. $this->id)->execute();
      // var_dump($r);
      //  die();
    }

    public function revert(){
        \Yii::$app->db->createCommand(' update site set ' .
            'status=' . Site::STATUS_AVAILABLE . ', '.
            'port=' . $this->port.
            ' where id='. $this->id)->execute();
    }
}

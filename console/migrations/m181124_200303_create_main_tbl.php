<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Class m181124_200303_create_main_tbl
 */
class m181124_200303_create_main_tbl extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%login}}', [
            'id' => $this->primaryKey(),
            'login' => $this->string(),
            'weight' => $this->integer()->defaultValue(0),
            'created_at' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->createTable('{{%password}}', [
            'id' => $this->primaryKey(),
            'password' => $this->string(),
            'weight' => $this->integer()->defaultValue(0),
            'created_at' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->createTable('{{%site}}', [
            'id' => $this->primaryKey(),
            'host' => $this->string(),
            'https' => $this->boolean(),
            'login_id' => $this->integer(),
            'password_id' => $this->integer(),
            'ip' => $this->string(15),
            'port' => $this->integer(),
            'status' => $this->integer()->defaultValue(0),
            'active' => $this->boolean(),
            'created_at' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);


        $this->addForeignKey('fk-site-login_id', '{{%site}}', 'login_id', '{{%login}}', 'id', 'SET NULL', 'SET NULL');
        $this->addForeignKey('fk-site_id', '{{%site}}', 'password_id', '{{%password}}', 'id', 'SET NULL', 'SET NULL');

        $this->createTable('{{%site_accaunt}}', [
            'id' => $this->primaryKey(),
            'site_id' => $this->integer(),
            'login' => $this->string(),
            'password' => $this->string(),
            'created_at' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->addForeignKey('fk-site_accaunt-site_id', '{{%site_accaunt}}', 'site_id', '{{%site}}', 'id', 'CASCADE', 'CASCADE');



    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m181124_200303_create_main_tbl cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        insert into site(host ) values('s1')
     'id' => $this->primaryKey(),
            'host' => $this->string(),
            'https' => $this->boolean(),
            'login_id' => $this->integer(),
            'password_id' => $this->integer(),
            'ip' => $this->string(15),
            'port' => $this->integer(),
            'status' => $this->integer(),
            'created_at' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at'

    }

    public function down()
    {
        echo "m181124_200303_create_main_tbl cannot be reverted.\n";

        return false;
    }
    */
}

<?php

use yii\db\Migration;

class m141015_133459_create_tables extends Migration
{
    public function up()
    {
		$item = "CREATE TABLE `item` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`title` VARCHAR(255) NOT NULL DEFAULT '',
			`url` VARCHAR(255) NOT NULL DEFAULT '',
			`order` INT(11) NULL DEFAULT '0',
			PRIMARY KEY (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;
		";
		$this->execute($item);

		$image = "CREATE TABLE `image` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(255) NOT NULL DEFAULT '',
			`type` ENUM('main','cover','extra_big','extra_small','thumbnail') NOT NULL,
			`item_id` INT(11) NOT NULL,
			PRIMARY KEY (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;
		";
		$this->execute($image);

		$user = "CREATE TABLE `user` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`username` VARCHAR(255) NULL DEFAULT NULL,
			`password` VARCHAR(255) NULL DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE INDEX `username` (`username`)
		)
		ENGINE=InnoDB;
		";
		$this->execute($user);
    }

    public function down()
    {
		$this->dropTable('item');
		$this->dropTable('image');
		$this->dropTable('user');
    }
}

<?php
namespace app\models;

use yii\db\ActiveRecord;

class Item extends ActiveRecord
{
	/**
	 * @return string the name of the table associated with this ActiveRecord class.
	 */
	public static function tableName()
	{
		return 'item';
	}

	public function getMainImage()
	{
		return $this->hasOne(Image::className(), ['item_id' => 'id'])
			->where('type = :type', [':type' => 'main']);
	}

	public function getCoverImage()
	{
		return $this->hasOne(Image::className(), ['item_id' => 'id'])
			->where('type = :type', [':type' => 'cover']);
	}

	public function getExtraImages()
	{
		return $this->hasMany(Image::className(), ['item_id' => 'id'])
			->where('type = :type', [':type' => 'extra_big']);
	}
}

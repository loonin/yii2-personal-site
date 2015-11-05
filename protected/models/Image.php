<?php
namespace app\models;

use yii\db\ActiveRecord;

class Image extends ActiveRecord
{
	public static $img_max_size = "10485760"; //byte
	public static $img_max_width = "672";
	public static $img_max_height = "3000";
	public static $previews_in_row = "5";
	public static $previews_on_page = "30";
	public static $cover_max_width = "310";
	public static $cover_max_height = "310";
	public static $extra_previews_max_width = "230";
	public static $extra_previews_max_height = "230";
	public static $thumbnail_max_width = "100";
	public static $thumbnail_max_height = "100";

	public static $preview_type = "square"; // {square|nature}
	public static $thumbnail_type = "square"; // {square|nature}
	public static $v_align_cut_area = "top"; // {top, middle, bottom}
	public static $h_align_cut_area = "center"; //{left, center, right}

	public static $view_sequence_photo_in_album = "LIFO"; //LIFO = Last In First Out (DESC), FIFO = First In First Out (ASC)
	public static $view_sequence_albums = "FIFO";
	public static $view_sequence_photo_in_last = "LIFO";

	public static $last_photos_number = 5;

	public static $length_random_sequence = 15; //sequence for random filename

	public static $jpg_compression = 75;

	/**
	 * @return string the name of the table associated with this ActiveRecord class.
	 */
	public static function tableName()
	{
		return 'image';
	}

	public function getItem()
	{
		return $this->hasOne(Item::className(), ['id' => 'item_id']);
	}

	public function getImageUrl($type = null)
	{
		if (!$type) {
			$type = $this->type;
		}

		switch ($type) {
			case 'extra_small':
				$imagePath = \Yii::$app->params['imagePreviewPath'];
				break;
			case 'cover':
				$imagePath = \Yii::$app->params['imageCoverPath'];
				break;
			default:
				$imagePath = \Yii::$app->params['imagePath'];
		}

		return $imagePath . $this->name;
	}

	public function getThumbnail()
	{
		$imagePath = \Yii::$app->params['imageThumbnailPath'];

		return $imagePath . $this->name;
	}

	/**
	* Генерирует случайную последовательность
	*/
	public static function generateSequence($length = 10){
		$chars = 'abcdefghigklmnopqrstuvwxyzABCDEFGHIGKLMNOPQRSTUVWXYZ1234567890';
		$numChars = strlen($chars);
		$string = '';

		for ($i = 0; $i < $length; $i++) {
			$string .= substr($chars, rand(1, $numChars) - 1, 1);
		}

		return $string;
	}

	/**
	* Сохраняет изображение
	*/
	public static function saveImg($file, $img_kind, $name = null){
		if (!is_uploaded_file($file['tmp_name'])) {
			return false;
		}

		$file_tmp = $file['tmp_name'];
		$file_name = $file['name'];

		list($width, $height, $image_type) = getimagesize($file_tmp);

		if ($image_type != IMAGETYPE_JPEG
			&& $image_type != IMAGETYPE_GIF
			&& $image_type != IMAGETYPE_PNG
		){
			return false;
		}

		if (filesize($file_tmp) > self::$img_max_size) {
			echo "Выбранное изображение имеет слишком большой размер!<br>";
			echo "Изображение загружено не было.<br>";
			return false;
		}

		switch ($img_kind) {
			case "preview":
				$img_dir = \Yii::$app->params['imagePreviewPath'];
				$max_width = self::$extra_previews_max_width;
				$max_height = self::$extra_previews_max_height;
				switch (self::$preview_type) {
					case "square": $sides = "square"; break;
					default: $sides = "nature";
				}
				break;
			case "cover":
				$img_dir = \Yii::$app->params['imageCoverPath'];
				$max_width = self::$cover_max_width;
				$max_height = self::$cover_max_height;
				switch (self::$preview_type) {
					case "square": $sides = "square"; break;
					default: $sides = "nature";
				}
				break;
			case "thumbnail":
				$img_dir = \Yii::$app->params['imageThumbnailPath'];
				$max_width = self::$thumbnail_max_width;
				$max_height = self::$thumbnail_max_height;
				switch (self::$thumbnail_type) {
					case "square": $sides = "square"; break;
					default: $sides = "nature";
				}
				break;
			default:
				// "normal" case
				$img_dir = \Yii::$app->params['imagePath'];
				$max_width = self::$img_max_width;
				$max_height = self::$img_max_height;
				$sides = "nature";
				break;
		}

		if ($sides == "square") {
			if ($width > $max_width) {
				$ratio = $max_width / $width;
			} elseif ($height > $max_width) {
				$ratio = $max_width / $height;
			} else {
				$ratio = 1;
			}
		} elseif ($width > $max_width){
				$ratio = $max_width / $width;

				if ($height > $ratio * $max_height){
					$new_ratio = $max_height / $height;
					if ($new_ratio < $ratio) {
						$ratio = $new_ratio;
					}
				}
		} elseif ($height > $max_height) {
				$ratio = $max_height / $height;
		} else {
				$ratio = 1;
		}

		// размеры нового изображения
		$new_width = $width * $ratio;
		$new_height = $height * $ratio;

		// координаты области для копирования оригинального изображения
		$x = 0;
		$y = 0;

		if ($sides == "square") {
			$new_height = $new_width;
			if ($height > $width) {
				switch (self::$v_align_cut_area) {
					case "top":
						$y = 0;
						break;
					case "middle":
						$y = ($height - $width)/2;
						break;
					case "bottom":
						$y = $height - $width;
						break;
				}
				$height = $width;
			} else {
				switch (self::$h_align_cut_area) {
					case "left":
						$x = 0;
						break;
					case "center":
						$x = ($width - $height)/2;
						break;
					case "right":
						$x = $width - $height;
						break;
				}
				$width = $height;
			}
		}

		// проверка на русские символы
		if (preg_match('/[а-яёА-ЯЁ]/',$file_name)){
			$file_name = self::generateSequence(self::$length_random_sequence);
			switch ($image_type){
				case IMAGETYPE_JPEG: $file_name = $file_name.".jpg"; break;
				case IMAGETYPE_GIF: $file_name = $file_name.".gif"; break;
				case IMAGETYPE_PNG: $file_name = $file_name.".png"; break;
			}
		}

		// проверяем, есть ли уже такое имя файла, если есть, то генерируем случайную последовательность для имени
		// на случай повторного совпадения делаем это в цикле

		$flag = true;

		do{
			$fileList = glob(\Yii::$app->basePath . '/../' . $img_dir . $file_name);
			if (!empty($fileList)) {
				$file_name = self::generateSequence(self::$length_random_sequence);
				switch ($image_type){
					case IMAGETYPE_JPEG: $file_name = $file_name.".jpg"; break;
					case IMAGETYPE_GIF: $file_name = $file_name.".gif"; break;
					case IMAGETYPE_PNG: $file_name = $file_name.".png"; break;
				}
			} else {
				$flag = false;
			}
		} while ($flag);

		$new_image = imagecreatetruecolor($new_width, $new_height) or die('Невозможно инициализировать GD поток');
		$white = imagecolorallocate($new_image, 255, 255, 255);
		imagefill($new_image, 0, 0, $white);

		switch ($image_type) {
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg($file_tmp);
				break;
			case IMAGETYPE_GIF:
				$image = imagecreatefromgif($file_tmp);
				break;
			case IMAGETYPE_PNG:
				$image = imagecreatefrompng($file_tmp);
				break;
			default:
				$image = imagecreatefromjpeg($file_tmp);
		}

		imagecopyresampled($new_image, $image, 0, 0, $x, $y, $new_width, $new_height, $width, $height);

		if ($name) {
			$file_name = $name;
		}

		switch ($image_type) {
			case IMAGETYPE_JPEG:
				imagejpeg($new_image, \Yii::$app->basePath . '/../' . $img_dir . $file_name, self::$jpg_compression);
				break;
			case IMAGETYPE_GIF:
				imagegif($new_image, \Yii::$app->basePath . '/../' . $img_dir.$file_name);
				break;
			case IMAGETYPE_PNG:
				imagepng($new_image, \Yii::$app->basePath . '/../' . $img_dir.$file_name);
				break;
		}

		return $file_name;
	}
}

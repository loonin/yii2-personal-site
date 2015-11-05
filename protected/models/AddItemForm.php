<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class AddItemForm extends Model
{
    public $title;
    public $url;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['title', 'url'], 'required', 'message' => 'Пожалуйста, заполните поле']
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'title' => 'Заголовок',
            'url' => 'URL',
        ];
    }
}

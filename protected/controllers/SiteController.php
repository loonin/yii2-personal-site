<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\Item;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
		/** @var Item $items */
		$items = Item::find()->orderBy('order')->all();

		return $this->renderPartial('index.twig', [
			'items' => $items
		]);
    }

	public function actionSendFeedback()
	{
		require \Yii::$app->basePath . '/components/mailer/class.phpmailer.php';
		require \Yii::$app->basePath . '/components/mailer/send.php';
	}

	public function actionGetItem()
	{
		if (!\Yii::$app->request->isPost && !\Yii::$app->request->isAjax) {
			echo json_encode(['error' => 'Работа не найдена']);

			\Yii::$app->end();
		}

		$id = \Yii::$app->request->post('id');

		/** @var Item $item */
		$item = Item::findOne($id);

		if (!$item) {
			echo json_encode(['error' => 'Работа не найдена']);

			\Yii::$app->end();
		}

		$result = [
			'title' => $item->title,
			'url' 	=> $item->url,
			'image' => $item->mainImage->getImageUrl(),
		];

		$extra = [];

		$images = $item->extraImages;

		foreach ($images as $image) {
			$extra[] = [
				'extra-image-big' => $image->getImageUrl(),
				'extra-image-small' => $image->getImageUrl('extra_small')
			];
		}

		$result['extra-images'] = $extra;

		echo json_encode($result);
	}
}

<?php

namespace app\controllers;

use app\models\AddItemForm;
use app\models\Image;
use app\models\Item;
use app\models\LoginForm;
use Yii;
use yii\base\Exception;
use yii\web\Controller;


class BackController extends Controller
{
	public function beforeAction($action)
	{
		if (\Yii::$app->user->isGuest && $action->id != 'login') {
			return $this->redirect('login');
		}

		return true;
	}

	public function actionAdd()
	{
		$model = new AddItemForm();

		if (!Yii::$app->request->isPost) {
			return $this->render('add', [
				'model' => $model,
			]);
		}

		$model->load(Yii::$app->request->post());

		if (!$model->validate()) {
			return $this->render('add', [
				'model' => $model,
			]);
		}

		$connection = \Yii::$app->db;
		$transaction = $connection->beginTransaction();

		$itemModel = new Item();
		$itemModel->title = $model->title;
		$itemModel->url = $model->url;
		$itemModel->save();


		if (empty($_FILES["img_cover"]['tmp_name']) || !is_uploaded_file($_FILES["img_cover"]['tmp_name'])){
			\Yii::$app->session->setFlash('error', 'Не загрузилась обложка или не была выбрана');
			$transaction->rollBack();

			return $this->render('add', [
				'model' => $model,
			]);
		}

		$imgCoverName = Image::saveImg($_FILES["img_cover"],"cover");
		$imgCoverThumbnailName = Image::saveImg($_FILES["img_cover"],"thumbnail", $imgCoverName);

		if (!$imgCoverName || !$imgCoverThumbnailName) {
			\Yii::$app->session->setFlash('error', 'Ошибка сохранения обложки');
			$transaction->rollBack();

			return $this->render('add', [
				'model' => $model,
			]);
		}

		$imgModel = new Image();
		$imgModel->name = $imgCoverName;
		$imgModel->type = 'cover';
		$imgModel->item_id = $itemModel->id;
		$imgModel->save();

		$imgThumbModel = new Image();
		$imgThumbModel->name = $imgCoverThumbnailName;
		$imgThumbModel->type = 'thumbnail';
		$imgThumbModel->item_id = $itemModel->id;
		$imgThumbModel->save();

		if (empty($_FILES["img_main"]['tmp_name']) || !is_uploaded_file($_FILES["img_main"]['tmp_name'])){
			\Yii::$app->session->setFlash('error', 'Не загрузилась главная картинка или не была выбрана');
			$transaction->rollBack();

			return $this->render('add', [
				'model' => $model,
			]);
		}

		$imgMainName = Image::saveImg($_FILES["img_main"],"normal");
		$imgMainThumbnailName = Image::saveImg($_FILES["img_main"],"thumbnail", $imgMainName);

		if (!$imgMainName || !$imgMainThumbnailName) {
			\Yii::$app->session->setFlash('error', 'Ошибка сохранения главной картинки');
			$transaction->rollBack();

			return $this->render('add', [
				'model' => $model,
			]);
		}

		$imgModel = new Image();
		$imgModel->name = $imgMainName;
		$imgModel->type = 'main';
		$imgModel->item_id = $itemModel->id;
		$imgModel->save();

		$imgThumbModel = new Image();
		$imgThumbModel->name = $imgMainThumbnailName;
		$imgThumbModel->type = 'thumbnail';
		$imgThumbModel->item_id = $itemModel->id;
		$imgThumbModel->save();


		for ($i=1; $i<=5; $i++){
			if (empty($_FILES["img{$i}"]['tmp_name'])) {
				continue;
			}

			if (!is_uploaded_file($_FILES["img{$i}"]['tmp_name'])){
				Yii::$app->session->setFlash('error', 'Не загрузилась дополнительная картинка');
				$transaction->rollBack();

				return $this->render('add', [
					'model' => $model,
				]);
			}

			$imgBigName = Image::saveImg($_FILES["img{$i}"], "normal");
			$imgSmallName = Image::saveImg($_FILES["img{$i}"], "preview", $imgBigName);
			$imgThumbnailName = Image::saveImg($_FILES["img{$i}"], "thumbnail", $imgBigName);

			if (!$imgBigName || !$imgSmallName || !$imgSmallName) {
				\Yii::$app->session->setFlash('error', 'Ошибка сохранения дополнительной картинки');
				$transaction->rollBack();

				return $this->render('add', [
					'model' => $model,
				]);
			}

			$imgModelBig = new Image();
			$imgModelBig->name = $imgBigName;
			$imgModelBig->type = 'extra_big';
			$imgModelBig->item_id = $itemModel->id;
			$imgModelBig->save();

			$imgModelSmall = new Image();
			$imgModelSmall->name = $imgSmallName;
			$imgModelSmall->type = 'extra_small';
			$imgModelSmall->item_id = $itemModel->id;
			$imgModelSmall->save();

			$imgModelThumbnail = new Image();
			$imgModelThumbnail->name = $imgThumbnailName;
			$imgModelThumbnail->type = 'thumbnail';
			$imgModelThumbnail->item_id = $itemModel->id;
			$imgModelThumbnail->save();
		}

		Yii::$app->session->setFlash('success', 'Новая работа была добавлена успешно');
		$transaction->commit();

		return $this->redirect(['edit', 'id' => $itemModel->id]);
	}

	public function actionDelete($id)
	{
		/** @var Item $item */
		$item = Item::findOne($id);

		$connection = \Yii::$app->db;
		$transaction = $connection->beginTransaction();

		if (!$item) {
			\Yii::$app->session->setFlash('error', 'Не найдено работы для удаления');

			return $this->redirect('/back');
		}

		try {
			/** @var Image[] $images */
			$images = Image::find()
				->where([
					'item_id' => $item->id
				])
				->all();

			foreach ($images as $image) {
				$url = \Yii::$app->basePath . '/..' . $image->getImageUrl();
				
				if (file_exists($url)) {
					unlink($url);
				}
				
				$image->delete();
			}

			$item->delete();
		} catch (Exception $e) {
			$transaction->rollBack();
			\Yii::$app->session->setFlash('error', 'Поризошла ошибка. Работа не удалена');

			return $this->redirect('/back');
		}

		\Yii::$app->session->setFlash('success', 'Работа успешно удалена');
		$transaction->commit();

		return $this->redirect('/back');
	}

	public function actionDeleteImage()
	{
		if (!\Yii::$app->request->isPost && !\Yii::$app->request->isAjax) {
			echo json_encode(['error' => 'Изображение не найдено']);

			\Yii::$app->end();
		}

		$id = \Yii::$app->request->post('id');

		/** @var Image $item */
		$image = Image::findOne($id);

		if (!$image) {
			echo json_encode(['error' => 'Изображение не найдено']);

			\Yii::$app->end();
		}

		unlink(\Yii::$app->basePath . '/..' . $image->getImageUrl());
		$image->delete();

		echo json_encode(['success' => true]);
	}

	public function actionEdit($id) {
		$item = Item::findOne($id);

		if (!$item) {
			Yii::$app->session->setFlash('error', 'Работа не найдена');

			return $this->redirect('index');
		}

		$model = new AddItemForm();
		$model->attributes = $item->attributes;

		if (!Yii::$app->request->isPost) {
			return $this->render('edit', [
				'model' => $model,
				'item' => $item
			]);
		}

		$model->load(Yii::$app->request->post());

		if (!$model->validate()) {
			return $this->render('edit', [
				'model' => $model,
				'item' => $item
			]);
		}

		$item->title = $model->title;
		$item->url = $model->url;
		$item->save();

		$connection = \Yii::$app->db;
		$transaction = $connection->beginTransaction();

		if (!empty($_FILES["img_cover"]['tmp_name'])){
			if (!is_uploaded_file($_FILES["img_cover"]['tmp_name'])) {
				\Yii::$app->session->setFlash('error', 'Не загрузилась обложка');
				$transaction->rollBack();

				return $this->render('edit', [
					'model' => $model,
					'item' => $item
				]);
			} else {
				unlink(\Yii::$app->basePath . '/..' . $item->coverImage->getImageUrl());
				$item->coverImage->delete();

				$imgCoverName = Image::saveImg($_FILES["img_cover"],"cover");
				$imgCoverThumbnailName = Image::saveImg($_FILES["img_cover"],"thumbnail", $imgCoverName);

				if (!$imgCoverName || !$imgCoverThumbnailName) {
					\Yii::$app->session->setFlash('error', 'Ошибка сохранения обложки');
					$transaction->rollBack();

					return $this->render('add', [
						'model' => $model,
					]);
				}

				$imgModel = new Image();
				$imgModel->name = $imgCoverName;
				$imgModel->type = 'cover';
				$imgModel->item_id = $item->id;
				$imgModel->save();

				$imgThumbModel = new Image();
				$imgThumbModel->name = $imgCoverThumbnailName;
				$imgThumbModel->type = 'thumbnail';
				$imgThumbModel->item_id = $item->id;
				$imgThumbModel->save();
			}
		}

		if (!empty($_FILES["img_main"]['tmp_name'])){
			if (!is_uploaded_file($_FILES["img_main"]['tmp_name'])) {
				\Yii::$app->session->setFlash('error', 'Не загрузилась главная картинка');
				$transaction->rollBack();

				return $this->render('edit', [
					'model' => $model,
					'item' => $item
				]);
			} else {
				unlink(\Yii::$app->basePath . '/..' . $item->mainImage->getImageUrl());
				$item->mainImage->delete();

				$imgMainName = Image::saveImg($_FILES["img_main"],"normal");
				$imgMainThumbnailName = Image::saveImg($_FILES["img_main"],"thumbnail", $imgMainName);

				if (!$imgMainName || !$imgMainThumbnailName) {
					\Yii::$app->session->setFlash('error', 'Ошибка сохранения главной картинки');
					$transaction->rollBack();

					return $this->render('add', [
						'model' => $model,
					]);
				}

				$imgModel = new Image();
				$imgModel->name = $imgMainName;
				$imgModel->type = 'main';
				$imgModel->item_id = $item->id;
				$imgModel->save();

				$imgThumbModel = new Image();
				$imgThumbModel->name = $imgMainThumbnailName;
				$imgThumbModel->type = 'thumbnail';
				$imgThumbModel->item_id = $item->id;
				$imgThumbModel->save();
			}
		}

		for ($i=1; $i<=5; $i++){
			if (empty($_FILES["img{$i}"]['tmp_name'])) {
				continue;
			}

			if (!is_uploaded_file($_FILES["img{$i}"]['tmp_name'])){
				Yii::$app->session->setFlash('error', 'Не загрузилась картинка ' . $i);
				$transaction->rollBack();

				return $this->render('edit', [
					'model' => $model,
					'item' => $item
				]);
			}

			$imgBigName = Image::saveImg($_FILES["img{$i}"], "normal");
			$imgSmallName = Image::saveImg($_FILES["img{$i}"], "preview", $imgBigName);
			$imgThumbnailName = Image::saveImg($_FILES["img{$i}"], "thumbnail", $imgBigName);

			if (!$imgBigName || !$imgSmallName || !$imgSmallName) {
				\Yii::$app->session->setFlash('error', 'Ошибка сохранения дополнительной картинки');
				$transaction->rollBack();

				return $this->render('add', [
					'model' => $model,
				]);
			}

			$imgModelBig = new Image();
			$imgModelBig->name = $imgBigName;
			$imgModelBig->type = 'extra_big';
			$imgModelBig->item_id = $item->id;
			$imgModelBig->save();

			$imgModelSmall = new Image();
			$imgModelSmall->name = $imgSmallName;
			$imgModelSmall->type = 'extra_small';
			$imgModelSmall->item_id = $item->id;
			$imgModelSmall->save();

			$imgModelThumbnail = new Image();
			$imgModelThumbnail->name = $imgThumbnailName;
			$imgModelThumbnail->type = 'thumbnail';
			$imgModelThumbnail->item_id = $item->id;
			$imgModelThumbnail->save();
		}

		Yii::$app->session->setFlash('success', 'Работа была обновлена успешно');
		$transaction->commit();

		return $this->refresh('edit', [
			'model' => $model,
			'item' => $item
		]);
	}

	public function actionIndex()
    {
		/** @var Item $itemModels */
		$itemModels = Item::find()->orderBy('order')->all();
		$items = [];

		foreach ($itemModels as $itemModel) {
			$items[] = $itemModel;
		}

		return $this->render('index.twig', [
			'items' => $items
		]);
    }

	public function actionLogin()
	{
		if (!\Yii::$app->user->isGuest) {
            return $this->redirect('index');
        }

        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
			return $this->redirect('index');
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
	}

	public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

	public function actionUpdate()
	{
		/** @var Item $itemModels */
		$itemModels = Item::find()->all();

		$items = \Yii::$app->request->post('items');

		foreach ($itemModels as $item) {
			/** @var Item $item */
			$item->setAttribute('order', $items[$item->id]);
			$item->update();
		}

		echo json_encode(['success' => 'Список работ успешно обновлён']);
	}
}

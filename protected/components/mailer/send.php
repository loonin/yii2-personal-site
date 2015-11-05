<?php
function complete_mail()
{
	$_POST['message'] =  substr(htmlspecialchars(trim($_POST['message'])), 0, 1000000);
	$_POST['name'] =  substr(htmlspecialchars(trim($_POST['name'])), 0, 30);
	$_POST['phone'] =  substr(htmlspecialchars(trim($_POST['phone'])), 0, 30);
	$_POST['email'] =  substr(htmlspecialchars(trim($_POST['email'])), 0, 50);

	$errors = array();

	// если не заполнено поле "Имя" - показываем ошибку 0
	if (empty($_POST['name'])) {
		$errors[] = 0;
	}

	// если неправильно заполнено поле email - показываем ошибку 1
	if (preg_match("/[0-9a-z_]+@[0-9a-z_^\.]+\.[a-z]{2,3}/i", $_POST['email']) == 0) {
		$errors[] = 1;
	}

	// если неправильно заполнено поле phone - показываем ошибку 2
	if (!empty($_POST['phone']) && preg_match("/[0-9+ ]{5,}/i", $_POST['phone']) == 0) {
		$errors[] = 2;
	}

	// если не заполнено поле "Сообщение" - показываем ошибку 3
	if(empty($_POST['message'])) {
		$errors[] = 3;
	}

		output_err($errors);

	$mess = '
		<b>Имя отправителя: </b>'.$_POST['name'].'<br>
		<b>Контактный телефон: </b>'.$_POST['phone'].'<br>
		<b>Контактный email: </b>'.$_POST['email'].'<br>
		<b>Сообщение: </b>'.$_POST['message'].'<br>';

	$mail = new PHPMailer();
	$mail->From = 'webxaker@bk.ru ';      // от кого vrnkrov@yandex.ru
	$mail->FromName = 'Admin LOONIN.RU';   // от кого
	$mail->AddAddress('webxaker@bk.ru ', 'Admin'); // кому - адрес, Имя
	$mail->IsHTML(true);        // выставляем формат письма HTML
	$mail->Subject = 'Сообщение с сайта LOONIN.RU';  // тема письма

	$mail->Body = $mess;

	// отправляем письмо
	if (!$mail->Send()) {
		echo json_encode(array('errors' => array('Mailer Error: '. $mail->ErrorInfo)));
	}

	$success = 'Спасибо! <br> Ваше письмо отправлено. <br> Скоро мы свяжемся с Вами.';

	echo json_encode(array('success' => $success));
}

function output_err($nums)
{
    if (!$nums) {
		return true;
	}
	
	$err[0] = 'Нужно указать Ваше имя<br>';
	$err[1] = 'Неправильно указана<br> электронная почта<br>';
	$err[2] = 'Неправильно указан телефон<br>';
    $err[3] = 'Нужно ввести сообщение<br>';
	
	$sendErrors = array();
	
	foreach ($nums as $num) {
		$sendErrors[] = $err[$num];
	}
	
    echo json_encode(array('errors' => $sendErrors));

    exit();
}

if (!empty($_POST['submit'])) {
	complete_mail();
}
?>

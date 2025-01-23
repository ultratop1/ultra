<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
Header("Content-Type: text/html;charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit();
if (!isset($_POST['name']) OR empty($_POST['name'])) exit();

$subject = 'Заявка с сайта';
$toemail = ['dieton.r@gmail.com'];
$baseurl = $_SERVER['SERVER_NAME'];
$ip = $_SERVER['REMOTE_ADDR'];

if (isset($_POST['subject']) and !empty($_POST['subject'])) $subject = $_POST['subject'];

$contentHTML = '';
if (isset($_POST['name']) and !empty($_POST['name'])) $contentHTML .= "<p><b>Заказчик: </b>" . $_POST['name'] . "</p>";
if (isset($_POST['phone']) and !empty($_POST['phone'])) $contentHTML .= "<p><b>Телефон: </b>" . $_POST['phone'] . "</p>";
$contentHTML .= "<p><strong>IP: </strong> " . $ip . "</p>";

$body = '<!DOCTYPE html><html><head><meta charset="UTF-8" /><title>' . $subject . '</title></head><body>
	<table style="width:600px;margin:30px auto;background-color:#ffffff;color:#000000;">
		<tr><td>
			<p style="text-align:center">
				<a href="http://' . $baseurl . '/" title="' . $baseurl . '" target="_blank">
					<img src="http://' . $baseurl . '/images/logo.png" alt="' . $baseurl . '" />
				</a>
			</p>
			<p>&nbsp;<p>
			<h3 style="text-align:center">' . $subject . '</h3>
			' . nl2br($contentHTML) . '
			<p>&nbsp;<p>
			<p style="text-align:center">
				<em>Copyright &copy;
				<a href="http://' . $baseurl . '/" title="' . $baseurl . '" target="_blank" style="color:#000;">' . $baseurl . '</a>
				' . date("Y") . '</em>
			</p>
		</td></tr>
	</table>
</body></html>';

$old_price = 669;
$new_price = 439;
$total_sum = $new_price;

if (isset($_POST['quantity']) and !empty($_POST['quantity'])) {
	$total_sum = $_POST['quantity'] * $new_price;
	$body = '<!DOCTYPE html><html><head><meta charset="UTF-8" /><title>Заказ УТ</title></head><body>
		<table style="width:600px;margin:30px auto;background-color:#ffffff;color:#000000;">
			<tr><td>';

	if (isset($_POST['lastname']) and !empty($_POST['lastname']) and isset($_POST['name']) and !empty($_POST['name'])) $body .= '<p><b>Заказчик: </b> ' . $_POST['lastname'] . ' ' . $_POST['name'] . '</p>';
	if (isset($_POST['phone']) and !empty($_POST['phone'])) $body .= '<p><b>Телефон: </b> ' . $_POST['phone'] . '</p>';
	if (isset($_POST['email']) and !empty($_POST['email'])) $body .= '<p><b>Email: </b> ' . $_POST['email'] . '</p>';
	
	if (isset($_POST['payment']) and !empty($_POST['payment'])) $body .= '<p><b>Способ оплаты: </b> ' . $_POST['payment'] . '</p>';
	if (isset($_POST['delivery']) and !empty($_POST['delivery'])) $body .= '<p><b>Способ доставки: </b> ' . $_POST['delivery'] . '</p>';
$body .= '<p><b>IP: </b> ' . $ip . '</p>';

	$body .= '<table width="100%" border="1">';

	$body .= '<tr>
		<th width="70%" align="left" scope="col">Товар</th>
		<th width="10%" align="center" scope="col">Количество</th>
		<th width="20%" align="center" scope="col">Предварительный итог</th>
	</tr>';

	$body .= '<tr>
		<td align="left">УТ-</td>
		<td align="center">' . $_POST['quantity'] . '</td>
		<td align="center">' . ($_POST['quantity'] * $old_price) . '</td>
	</tr>';

	$body .= '<tr>
		<td colspan="2" align="right">Скидка 35%</td>
		<td align="center">' . ($_POST['quantity'] * $new_price) . '</td>
	</tr>';

	$total = '<tr>
		<td colspan="2" align="right">Итого</td>
		<td align="center">' . ($_POST['quantity'] * $new_price) . '</td>
	</tr>';

	if (isset($_POST['promo']) and !empty($_POST['promo'])) {
		require_once 'promocodes.php';
		$promocode = null;
		foreach ($promocodes as $promocode_arr) {
			$promocode_arr = explode('|', $promocode_arr);
			if ($promocode_arr[0] == trim($_POST['promo'])) {
				$promocode = $promocode_arr;
				break;
			}
		}
		if ($promocode){
			$total = '<tr>
				<td colspan="2" align="right">Применен промокод "'.$promocode[0].'", скидка в размере</td>
				<td align="center">' . $promocode[2] . '</td>
			</tr>';
			$total .= '<tr>
				<td colspan="2" align="right">Итого</td>
				<td align="center">' . (($_POST['quantity'] * $new_price) - $promocode[2]) . '</td>
			</tr>';
			$total_sum = $total_sum - $promocode[2];
		}
	}

	$body .= $total;

	$body .= '</table></td></tr><tr><td>
		<p style="text-align:center">
			<em>
				Copyright &copy;
				<a href="http://' . $baseurl . '/" title="' . $baseurl . '" target="_blank" style="color:#000000;">
					' . $baseurl . '
				</a>
				' . date("Y") . '
			</em>
		</p>
	</td></tr></table></body></html>';
}

use PHPMailer\PHPMailer;
use PHPMailer\SMTP;
use PHPMailer\Exception;

require_once 'phpmailer.php';

$mail = new PHPMailer(true);

$mail->CharSet = 'UTF-8';
$mail->isMAIL();
$mail->setFrom('from@' . $baseurl);
foreach ($toemail as $email) $mail->addAddress($email);
$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body = $body;

if (!$mail->send()) {
	echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
} else {
	echo 'Message has been sent';
}

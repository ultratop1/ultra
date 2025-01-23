<?php

require_once 'promocodes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
	if (isset($_POST['promo']) AND !empty($_POST['promo'])){

		$promocode = null;
		foreach ($promocodes as $promocode_arr) {
			$promocode_arr = explode('|', $promocode_arr);
			if ($promocode_arr[0] == trim($_POST['promo'])) {
				$promocode = $promocode_arr;
				break;
			}
		}

		if ($promocode) {
			if (time() < strtotime($promocode[1])) {
				exit(json_encode(['message' => 'Ваш промокод подтвержден!', 'count' => $promocode[2]]));
			}else{
				exit(json_encode(['error' => 1,'message' => 'Ваш промокод просрочен!']));
			}
		}
	}
}

exit(json_encode(['error' => 1, 'message' => 'Ваш промокод недействителен!']));

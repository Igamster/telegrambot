<?php

	header('Content-Type: text/html; charset=utf-8');

	$site_dir = dirname(dirname(__FILE__)).'/'; // корень сайта
	$bot_token ='...................................'; // токен вашего бота
	$data = file_get_contents('php://input'); // весь ввод перенаправляем в $data
	$data = json_decode($data, true); // декодируем json-закодированные-текстовые данные в PHP-массив
	$chat_id = $data['message']['chat']['id'];
	$text = trim($data['message']['text']);
	$callback_query = $data['callback_query'];
	$callback_data = $callback_query["data"];
	$first_name = $callback_query['from']['first_name'];
	if ($data['message']['chat']['id'] !== NULL) {
		$chat_id = $data['message']['chat']['id'];
	} else {
		$chat_id = $callback_query["message"]["chat"]["id"];
	}
	$keyboard = json_encode([
		'inline_keyboard' => [
			[
				['text' => 'Инфо', 'callback_data' => 'Инфо'],
				['text' => 'Курсы', 'callback_data' => 'Курсы']
			],
			[
				['text' => 'Что отправляет телега', 'callback_data' => 'Что отправляет телега']
			]
		]
	], true);

	file_put_contents(__DIR__ . '/message.txt', print_r($data, true));

	if (trim($data['message']['text']) == '/start') {
		$text_return ="Привет, $first_name, вот команды, что я понимаю: 
		Инфо - список команд
		Курсы - отображение курсов на сегодняшнее число
		";
		message_to_telegram($bot_token, $chat_id, $text_return, $keyboard);
		set_bot_state ($chat_id, 'Инфо');
	}
	if (!empty($data['message']['text'])) {
		switch($text) {
			case 'Курсы':
				$text_return = get_exchange_rate();
				message_to_telegram($bot_token, $chat_id, $text_return, $keyboard);
				set_bot_state ($chat_id, 'Курсы');
				break;
			case 'Что отправляет телега':
				$text_return = array_to_string($callback_query);
				message_to_telegram($bot_token, $chat_id, $text_return, $keyboard);
				set_bot_state ($chat_id, 'Что отправляет телега');
				break;
			case 'Инфо':
				$text_return ="Привет, $first_name, вот команды, что я понимаю: 
				Инфо - список команд
				Курсы - отображение курсов на сегодняшнее число
				";
				message_to_telegram($bot_token, $chat_id, $text_return, $keyboard);
				set_bot_state ($chat_id, 'Инфо');
				break;
		}
		// вывод информации Помощь
	}

	if (!empty($callback_data)) {
		switch($callback_data) {
			case 'Курсы':
				$text_return = get_exchange_rate();
				message_to_telegram($bot_token, $chat_id, $text_return, $keyboard);
				set_bot_state ($chat_id, 'Курсы');
				break;
			case 'Что отправляет телега':
				$text_return = array_to_string($callback_query);
				message_to_telegram($bot_token, $chat_id, $text_return, $keyboard);
				set_bot_state ($chat_id, 'Что отправляет телега');
				break;
			case 'Инфо':
				$text_return ="Привет, $first_name, вот команды, что я понимаю: 
				Инфо - список команд
				Курсы - отображение курсов на сегодняшнее число
				";
				message_to_telegram($bot_token, $chat_id, $text_return, $keyboard);
				set_bot_state ($chat_id, 'Инфо');
				break;
		}
		// вывод информации Помощь
	}


	function message_to_telegram ($bot_token, $chat_id, $text, $reply_markup)
	{
		$ch = curl_init();
		$ch_post = [
		CURLOPT_URL => 'https://api.telegram.org/bot' . $bot_token . '/sendMessage',
		CURLOPT_POST => TRUE,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_TIMEOUT => 1,
		CURLOPT_POSTFIELDS => [
		'chat_id' => $chat_id,
		'parse_mode' => 'HTML',
		'text' => $text,
		'disable_web_page_preview' => false,
		'reply_markup' => $reply_markup
		]
		];

		curl_setopt_array($ch, $ch_post);
		curl_exec($ch);
	}

	function set_bot_state ($chat_id, $data)
	{
		file_put_contents(__DIR__ . '/users/'.$chat_id.'.txt', $data);
	}

	function get_bot_state ($chat_id)
	{
		if (file_exists(__DIR__ . '/users/'.$chat_id.'.txt')) {
			$data = file_get_contents(__DIR__ . '/users/'.$chat_id.'.txt');
			return $data;
		}
		else {
			return '';
		}
	}

	function get_exchange_rate ()
	{
		$date = date("d/m/Y"); // Сегодняшняя дата в необходимом формате
		$currency = [
			'USD' => 'Доллар',
			'EUR' => 'Евро',
			'CNY' => 'Китайская Йена'
		];
		$text_return = '';
		foreach ($currency as $key => $value) {
			$link = "https://www.alphavantage.co/query?function=CURRENCY_EXCHANGE_RATE&from_currency=".$key."&to_currency=RUB&apikey=HJVYTUVIHNJNUHIYVYU&datatype=json"; // Ссылка на файл с курсами валют(за место apikey вставить ключ доступа)
			$nano = time_nanosleep(0, 300000);
			$content = file_get_contents($link); // Скачиваем содержимое страницы
			$rate = json_decode($content, true);
			$rate = $rate['Realtime Currency Exchange Rate']['5. Exchange Rate'];
			$text_return .= $value." - ".$rate." рублей\n";
		}
		return $text_return;
	}

	// function get_exchange_rate ()
	// {
		// $date = date("d/m/Y"); // Сегодняшняя дата в необходимом формате
		// $link = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=".$date; // Ссылка на XML-файл с курсами валют
		// $content = file_get_contents($link); // Скачиваем содержимое страницы
		// $dom = new domDocument("1.0", "cp1251"); // Создаём DOM
		// $dom->loadXML($content); // Загружаем в DOM XML-документ
		// $root = $dom->documentElement; // Берём корневой элемент
		// $childs = $root->childNodes; // Получаем список дочерних элементов
		// $data = []; // Набор данных
		// $text_return = strval($date)."\n";

		// for ($i = 0; $i < $childs->length; $i++) {
			// $childs_new = $childs->item($i)->childNodes; // Берём дочерние узлы
			// for ($j = 0; $j < $childs_new->length; $j++) {
				// /* Ищем интересующие нас валюты */
				// $el = $childs_new->item($j);
				// $code = $el->nodeValue;
				// if (($code == "USD") || ($code == "EUR") || ($code == "GBP") || ($code == "CNY")) $data[] = $childs_new; // Добавляем необходимые валюты в массив
			// }
		// }
		// /* Перебор массива с данными о валютах */
		// for ($i = 0; $i < count($data); $i++) {
			// $list = $data[$i];
			// for ($j = 0; $j < $list->length; $j++) {
				// $el = $list->item($j);
				// /* Выводим курсы валют */
				// if ($el->nodeName == "Name") 
					// $text_return .= $el->nodeValue." - ";
				// elseif ($el->nodeName == "Value") 
					// $text_return .= $el->nodeValue." рублей\n";
			// }
		// }
		// return $text_return;
	// }

	function array_to_string($array) {
		ob_start();
		var_dump($array);
		return ob_get_clean();
	}

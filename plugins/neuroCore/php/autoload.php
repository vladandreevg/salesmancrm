<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Автозагрузка классов плагина Нейроядро (namespace \Neuro).
 *
 * Соответствие namespace ↔ путь: Neuro\Provider\Gemini → php/Class/Provider/Gemini.php
 * Классы ядра намеренно не зависят от inc/func.php и SafeMySQL, чтобы их можно было
 * проверять юнит-тестами без БД, сессии и веб-контекста (.dsh/tests/neuro_*.php).
 */

spl_autoload_register(static function ($class) {

	$class = (string)$class;

	if (strpos($class, 'Neuro\\') !== 0) {
		return;
	}

	$rel  = str_replace('\\', '/', substr($class, 6));
	$file = dirname(__DIR__).'/php/Class/'.$rel.'.php';

	if (file_exists($file)) {
		require_once $file;
	}

});

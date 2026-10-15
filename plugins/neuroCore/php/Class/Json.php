<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro;

/**
 * Разбор и проверка JSON-ответов модели.
 *
 * Модели редко отдают «чистый» JSON: обычно это блок ```json … ```, текст с
 * пояснением до и после, иногда markdown-список. Поэтому извлечение терпимое, а
 * вот проверка — строгая: фича должна получить либо валидные данные, либо
 * понятную ошибку, но не молча пустой массив.
 *
 * Class Json
 *
 * @package Neuro
 */
class Json {

	/**
	 * Извлекает JSON из текста ответа.
	 *
	 * @param string $text
	 *
	 * @return array|null - null, если JSON не найден
	 */
	public static function extract(string $text) {

		$text = trim($text);

		if ($text === '') {
			return NULL;
		}

		// 1. ответ целиком — уже JSON
		$data = json_decode($text, true);

		if (is_array($data)) {
			return $data;
		}

		// 2. блок ```json … ```
		if (preg_match('/```(?:json)?\s*(.+?)\s*```/is', $text, $m) === 1) {

			$data = json_decode(trim($m[1]), true);

			if (is_array($data)) {
				return $data;
			}

		}

		// 3. первый сбалансированный объект или массив в тексте
		foreach (['{' => '}', '[' => ']'] as $open => $close) {

			$start = strpos($text, $open);

			if ($start === false) {
				continue;
			}

			$depth   = 0;
			$inQuote = false;
			$escaped = false;
			$length  = strlen($text);

			for ($i = $start; $i < $length; $i++) {

				$char = $text[$i];

				if ($inQuote) {

					if ($escaped) {
						$escaped = false;
					}
					elseif ($char === '\\') {
						$escaped = true;
					}
					elseif ($char === '"') {
						$inQuote = false;
					}

					continue;

				}

				if ($char === '"') {
					$inQuote = true;
					continue;
				}

				if ($char === $open) {
					$depth++;
				}
				elseif ($char === $close) {

					$depth--;

					if ($depth === 0) {

						$data = json_decode(substr($text, $start, $i - $start + 1), true);

						if (is_array($data)) {
							return $data;
						}

						break;

					}

				}

			}

		}

		return NULL;

	}

	/**
	 * Проверка значения по схеме.
	 *
	 * Поддерживаются ключи: type (строка или список), properties, required, items,
	 * enum, const. Этого достаточно для задач CRM («верни объект с такими полями»),
	 * а сложные схемы лучше описывать явно и расширять здесь же.
	 *
	 * @param mixed  $data
	 * @param array  $schema
	 * @param string $path - путь до узла для сообщения об ошибке
	 *
	 * @return array - список ошибок; пустой массив = соответствует схеме
	 */
	public static function validate($data, array $schema, string $path = ''): array {

		$errors = [];
		$where  = $path === '' ? 'ответ' : $path;

		if (isset($schema['enum']) && !in_array($data, (array)$schema['enum'], true)) {
			$errors[] = $where.': допустимые значения — '.implode(', ', array_map('strval', (array)$schema['enum']));
		}

		if (array_key_exists('const', $schema) && $data !== $schema['const']) {
			$errors[] = $where.': ожидалось значение «'.(is_scalar($schema['const']) ? (string)$schema['const'] : 'значение').'»';
		}

		if (!empty($errors)) {
			return $errors;
		}

		$types = isset($schema['type']) ? (array)$schema['type'] : [];

		if (!empty($types) && !self::matchesType($data, $types)) {
			return [$where.': ожидался тип '.implode(' | ', $types).', получено '.self::typeName($data)];
		}

		if (is_array($data) && !empty($schema['properties'])) {

			foreach ((array)($schema['required'] ?? []) as $key) {
				if (!array_key_exists($key, $data)) {
					$errors[] = $where.': нет обязательного поля «'.$key.'»';
				}
			}

			foreach ((array)$schema['properties'] as $key => $sub) {
				if (array_key_exists($key, $data) && is_array($sub)) {
					$errors = array_merge($errors, self::validate($data[$key], $sub, $where.'.'.$key));
				}
			}

			return $errors;

		}

		if (is_array($data) && isset($schema['items']) && is_array($schema['items'])) {

			foreach ($data as $index => $item) {
				$errors = array_merge($errors, self::validate($item, $schema['items'], $where.'['.$index.']'));
			}

		}

		return $errors;

	}

	/**
	 * Соответствует ли значение одному из перечисленных типов.
	 *
	 * @param mixed $data
	 * @param array $types
	 *
	 * @return bool
	 */
	private static function matchesType($data, array $types): bool {

		foreach ($types as $type) {

			$ok = false;

			switch ((string)$type) {

				case 'object':
					$ok = is_array($data) && (empty($data) || array_keys($data) !== range(0, count($data) - 1));
					break;

				case 'array':
					$ok = is_array($data) && (empty($data) || array_keys($data) === range(0, count($data) - 1));
					break;

				case 'string':
					$ok = is_string($data);
					break;

				case 'integer':
					$ok = is_int($data);
					break;

				case 'number':
					$ok = is_int($data) || is_float($data);
					break;

				case 'boolean':
					$ok = is_bool($data);
					break;

				case 'null':
					$ok = $data === NULL;
					break;

			}

			if ($ok) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Название типа значения для сообщения об ошибке.
	 *
	 * @param mixed $data
	 *
	 * @return string
	 */
	private static function typeName($data): string {

		if ($data === NULL) {
			return 'null';
		}

		if (is_bool($data)) {
			return 'логическое значение';
		}

		if (is_int($data)) {
			return 'целое число';
		}

		if (is_float($data)) {
			return 'число';
		}

		if (is_string($data)) {
			return 'строка';
		}

		if (is_array($data)) {
			return array_keys($data) === range(0, count($data) - 1) ? 'массив' : 'объект';
		}

		return 'неизвестный тип';

	}

}

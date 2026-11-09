<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

/**
 * Кэш кастомных шрифтов для dompdf 3 (PTSans/Arial/DejaVu и т.п.).
 *
 * dompdf 3 читает пользовательские семейства из <fontDir>/installed-fonts.json,
 * а значения-варианты склеивает с fontDir (относительные имена без расширения —
 * Cpdf::openFont сам добавляет .ttf/.ufm). Поэтому файл должен лежать
 * в каталоге, куда есть запись: на каждой установке это /cash/dompdf/
 * (vendor/dompdfFontsCastom/ может быть read-only — он используется только
 * как источник .ttf/.ufm при первом запуске).
 *
 * Class DompdfFonts
 *
 * @package Salesman
 */
class DompdfFonts {

	const FONTS_FILE = 'installed-fonts.json';

	/**
	 * Канонические имена семейств (как их пишут шаблоны), ключ — префикс файла
	 *
	 * @var array
	 */
	private static array $families = [
		'PTSans'         => 'PT Sans',
		'ArialNarrow'    => 'Arial Narrow',
		'Arial'          => 'Arial',
		'DejaVuSansMono' => 'DejaVu Sans Mono',
		'DejaVuSans'     => 'DejaVu Sans',
		'DejaVuSerif'    => 'DejaVu Serif',
	];

	/**
	 * Суффиксы начертаний
	 *
	 * @var array
	 */
	private static array $suffixes = [
		'-BoldOblique' => 'bold_italic',
		'-BoldItalic'  => 'bold_italic',
		'-Oblique'     => 'italic',
		'-Italic'      => 'italic',
		'-Bold'        => 'bold',
	];

	/**
	 * Гарантирует каталог шрифтов и актуальный installed-fonts.json.
	 * Пишет только в $fontDir (по умолчанию cash/dompdf).
	 *
	 * @param string      $rootpath - корень CRM
	 * @param string|null $fontDir  - переопределение каталога (для тестов/установщика)
	 * @return string - путь к каталогу шрифтов
	 */
	public static function ensure(string $rootpath, ?string $fontDir = null): string {

		$fontDir = self ::normalizeDir($fontDir ?? ($rootpath . '/cash/dompdf'));

		if (!is_dir($fontDir)) {
			@mkdir($fontDir, 0777, true);
		}

		self ::seedFromVendor($rootpath, $fontDir);

		if (!self ::isFresh($fontDir)) {
			self ::rebuild($fontDir);
		}

		return $fontDir;

	}

	/**
	 * Пересобирает installed-fonts.json по .ttf в каталоге
	 *
	 * @param string $fontDir
	 * @return bool
	 */
	public static function rebuild(string $fontDir): bool {

		$fontDir = self ::normalizeDir($fontDir);

		if (!is_dir($fontDir)) {
			return false;
		}

		$result = [];

		foreach (glob($fontDir . '*.ttf') ?: [] as $ttf) {

			$name  = basename($ttf, '.ttf');
			$style = 'normal';
			$base  = $name;

			foreach (self ::$suffixes as $suffix => $st) {
				if (str_ends_with($name, $suffix)) {
					$style = $st;
					$base  = substr($name, 0, -strlen($suffix));
					break;
				}
			}

			if (!isset(self ::$families[$base])) {
				continue;
			}

			$result[strtolower(self ::$families[$base])][$style] = $name;

		}

		ksort($result);
		foreach ($result as &$variants) {
			ksort($variants);
		}
		unset($variants);

		$json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		return file_put_contents($fontDir . self ::FONTS_FILE, $json . "\n") !== false;

	}

	/**
	 * Досоздаёт недостающие .ttf/.ufm из vendor/dompdfFontsCastom (read-only безопасен)
	 *
	 * @param string $rootpath
	 * @param string $fontDir
	 * @return void
	 */
	private static function seedFromVendor(string $rootpath, string $fontDir): void {

		$source = self ::normalizeDir($rootpath . '/vendor/dompdfFontsCastom');

		if (!is_dir($source)) {
			return;
		}

		foreach (array_merge(glob($source . '*.ttf') ?: [], glob($source . '*.ufm') ?: []) as $src) {
			$dst = $fontDir . basename($src);
			if (!is_file($dst)) {
				@copy($src, $dst);
			}
		}

	}

	/**
	 * Кэш актуален, если JSON валиден, покрывает все .ttf каталога и без абсолютных путей
	 *
	 * @param string $fontDir
	 * @return bool
	 */
	private static function isFresh(string $fontDir): bool {

		$file = $fontDir . self ::FONTS_FILE;

		if (!is_file($file)) {
			return false;
		}

		$json = json_decode((string)file_get_contents($file), true);

		if (!is_array($json) || $json === []) {
			return false;
		}

		$have = [];
		$mapped = [];

		foreach (glob($fontDir . '*.ttf') ?: [] as $ttf) {
			$have[] = basename($ttf, '.ttf');
		}

		foreach ($json as $variants) {
			foreach ((array)$variants as $name) {
				if (!is_string($name) || str_contains($name, ':') || str_contains($name, '\\') || str_ends_with($name, '.ttf')) {
					return false;
				}
				$mapped[] = $name;
			}
		}

		sort($have);
		sort($mapped);

		return $have === $mapped;

	}

	/**
	 * Приводит путь к виду "без завершающего слэша + /"
	 *
	 * @param string $dir
	 * @return string
	 */
	private static function normalizeDir(string $dir): string {
		return rtrim($dir, '/\\') . '/';
	}

}

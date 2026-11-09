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
 * Генерация QR-кодов для счетов/документов.
 *
 * bacon/bacon-qr-code 3.x даёт GDLibRenderer — PNG через GD (ext-gd есть
 * в require), поэтому imagick для QR больше не нужен: ни для PDF-счёта,
 * ни для docx-договоров (там нужен именно файл-картинка).
 *
 * Fallback оставлен на случай урезанного окружения: imagick → PNG,
 * иначе SVG (dompdf 3 рендерит SVG, но TBS-вставка SVG в docx не гарантирована).
 *
 * Class QrCode
 *
 * @package Salesman
 */
class QrCode {

	/**
	 * Какой backend использовать для рендера
	 *
	 * @return string - gd|imagick|svg
	 */
	private static function backend(): string {

		if (extension_loaded('gd') && class_exists('BaconQrCode\\Renderer\\GDLibRenderer')) {
			return 'gd';
		}

		if (extension_loaded('imagick')) {
			return 'imagick';
		}

		return 'svg';

	}

	/**
	 * Рендерит QR-код в строку (PNG, если доступен GD/imagick; иначе SVG)
	 *
	 * @param string $payload - содержимое QR
	 * @param int    $size    - размер изображения в пикселях
	 * @return string
	 */
	public static function render(string $payload, int $size = 400): string {

		switch (self ::backend()) {

			case 'gd':

				$renderer = new \BaconQrCode\Renderer\GDLibRenderer($size);

				break;

			case 'imagick':

				$renderer = new \BaconQrCode\Renderer\ImageRenderer(
					new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size),
					new \BaconQrCode\Renderer\Image\ImagickImageBackEnd()
				);

				break;

			default:

				$renderer = new \BaconQrCode\Renderer\ImageRenderer(
					new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size),
					new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
				);

		}

		$writer = new \BaconQrCode\Writer($renderer);

		return $writer -> writeString($payload, 'UTF-8');

	}

	/**
	 * MIME-тип результата render()
	 *
	 * @return string
	 */
	public static function mime(): string {

		return self ::backend() === 'svg' ? 'image/svg+xml' : 'image/png';

	}

	/**
	 * Записывает QR в файл
	 *
	 * @param string $payload
	 * @param string $path
	 * @param int    $size
	 * @return bool
	 */
	public static function writeFile(string $payload, string $path, int $size = 400): bool {

		return file_put_contents($path, self ::render($payload, $size)) !== false;

	}

}

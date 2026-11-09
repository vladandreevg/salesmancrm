<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

/**
 * Чтение импортируемых таблиц (xlsx/csv/ods/xls) без abandoned-пакета
 * nuovo/spreadsheet-reader.
 *
 * API совместим с прежним SpreadsheetReader (SeekableIterator, Countable,
 * ChangeSheet(), Sheets()), чтобы вызовы `new SpreadsheetReader($file)` +
 * `foreach` работали без изменений.
 *
 * XLSX — через shuchkin/simplexlsx, XLS — через уже присутствующий
 * vendor/excel_reader/excel_reader2.php, CSV — свой парсер с авто-определением
 * разделителя и BOM, ODS — минимальный парсер content.xml.
 */
class SpreadsheetReader implements SeekableIterator, Countable {

	const TYPE_XLSX = 'XLSX';
	const TYPE_XLS = 'XLS';
	const TYPE_CSV = 'CSV';
	const TYPE_ODS = 'ODS';

	/** @var array */
	private $Options = [
		'Delimiter' => '',
		'Enclosure' => '"',
	];

	/** @var int */
	private $Index = 0;

	/** @var int */
	private $SheetIndex = 0;

	/** @var string */
	private $Type = '';

	/** @var array - индекс листа => имя */
	private $Sheets = [];

	/** @var array - индекс листа => строки */
	private $Rows = [];

	/**
	 * @param string       $Filepath         - путь к файлу
	 * @param string|false $OriginalFilename - исходное имя (для определения типа)
	 * @param string|false $MimeType         - MIME от загрузки (необязательно)
	 */
	public function __construct($Filepath, $OriginalFilename = false, $MimeType = false) {

		if (!is_readable($Filepath)) {
			throw new Exception('SpreadsheetReader: File ('.$Filepath.') not readable');
		}

		$ext = strtolower(pathinfo($OriginalFilename ?: $Filepath, PATHINFO_EXTENSION));

		$this -> Type = $this -> detectType($ext, $MimeType);

		switch ($this -> Type) {

			case self::TYPE_XLSX:
				$this -> loadXlsx($Filepath);
				break;

			case self::TYPE_CSV:
				$this -> Sheets = [0 => 'CSV'];
				$this -> Rows   = [0 => $this -> readCsv($Filepath)];
				break;

			case self::TYPE_ODS:
				$this -> loadOds($Filepath);
				break;

			case self::TYPE_XLS:
				$this -> loadXls($Filepath);
				break;

			default:
				throw new Exception('SpreadsheetReader: неподдерживаемый формат файла ('.$ext.')');

		}

		$this -> SheetIndex = 0;

		// значения ячеек — строками, как ожидает код импорта (trim/untag/enc_detect)
		foreach ($this -> Rows as $i => $rows) {
			$this -> Rows[$i] = $this -> normalizeRows($rows);
		}

	}

	/**
	 * @param array $rows
	 * @return array
	 */
	private function normalizeRows(array $rows): array {

		return array_map(static function ($row) {
			$row = is_array($row) ? $row : [];
			return array_map(static function ($value) {
				if ($value === null) {
					return '';
				}
				if (is_bool($value)) {
					return $value ? '1' : '';
				}
				return is_scalar($value) ? (string)$value : '';
			}, array_values($row));
		}, $rows);

	}

	/**
	 * @param string       $ext
	 * @param string|false $mime
	 * @return string
	 */
	private function detectType(string $ext, $mime): string {

		$mime = is_string($mime) ? strtolower($mime) : '';

		if (str_contains($mime, 'spreadsheetml')) {
			return self::TYPE_XLSX;
		}
		if (str_contains($mime, 'opendocument.spreadsheet')) {
			return self::TYPE_ODS;
		}
		if ($mime === 'text/csv' || $mime === 'text/comma-separated-values' || $mime === 'text/plain') {
			return self::TYPE_CSV;
		}

		switch ($ext) {
			case 'xlsx':
			case 'xlsm':
			case 'xltx':
			case 'xltm':
				return self::TYPE_XLSX;
			case 'ods':
			case 'odt':
				return self::TYPE_ODS;
			case 'xls':
			case 'xlt':
				return self::TYPE_XLS;
			default:
				return self::TYPE_CSV;
		}

	}

	/**
	 * XLSX через SimpleXLSX
	 *
	 * @param string $file
	 * @return void
	 */
	private function loadXlsx(string $file): void {

		if (!class_exists('Shuchkin\\SimpleXLSX')) {
			throw new Exception('SpreadsheetReader: не установлен shuchkin/simplexlsx');
		}

		$xlsx = Shuchkin\SimpleXLSX ::parse($file);

		if (!$xlsx) {
			throw new Exception('SpreadsheetReader: '.Shuchkin\SimpleXLSX ::parseError());
		}

		$this -> Sheets = $xlsx -> sheetNames();

		foreach ($this -> Sheets as $i => $name) {
			$this -> Rows[$i] = $xlsx -> rows($i);
		}

	}

	/**
	 * XLS: SimpleXLS (shuchkin/simplexls), при его отсутствии — legacy-ридер
	 *
	 * @param string $file
	 * @return void
	 */
	private function loadXls(string $file): void {

		if (class_exists('Shuchkin\\SimpleXLS')) {

			$xls = Shuchkin\SimpleXLS ::parse($file);

			if (!$xls) {
				throw new Exception('SpreadsheetReader: '.Shuchkin\SimpleXLS ::parseError());
			}

			$this -> Sheets = $xls -> sheetNames();
			$this -> Sheets = ($this -> Sheets === []) ? [0 => 'Sheet1'] : $this -> Sheets;

			foreach ($this -> Sheets as $i => $name) {
				$this -> Rows[$i] = $xls -> rows($i);
			}

			return;

		}

		if (!class_exists('Spreadsheet_Excel_Reader', false)) {
			$path = dirname(__DIR__, 2).'/vendor/excel_reader/excel_reader2.php';
			if (!is_file($path)) {
				throw new Exception('SpreadsheetReader: для XLS нет ридера (shuchkin/simplexls или vendor/excel_reader)');
			}
			require_once $path;
		}

		$reader = new Spreadsheet_Excel_Reader();
		$reader -> read($file);

		$this -> Sheets = [];
		$this -> Rows   = [];

		$count = max(1, (int)$reader -> sheets[0]['numRows']);
		for ($i = 0; $i < $count; $i++) {
			$this -> Sheets[$i] = 'Sheet'.($i + 1);
			$rows = $reader -> dumptoarray($i);
			$this -> Rows[$i] = array_map('array_values', array_values((array)$rows));
		}

	}

	/**
	 * CSV/TSV/TXT: авто-разделитель + BOM, как в прежнем ридере
	 *
	 * @param string $file
	 * @return array
	 */
	private function readCsv(string $file): array {

		$content = (string)file_get_contents($file);

		// BOM / кодировка → UTF-8
		$bom = substr($content, 0, 4);
		$enc = null;

		if (str_starts_with($bom, "\xFF\xFE\x00\x00")) {
			$enc = 'UTF-32LE';
			$content = substr($content, 4);
		}
		elseif (str_starts_with($bom, "\x00\x00\xFE\xFF")) {
			$enc = 'UTF-32BE';
			$content = substr($content, 4);
		}
		elseif (str_starts_with($bom, "\xFF\xFE")) {
			$enc = 'UTF-16LE';
			$content = substr($content, 2);
		}
		elseif (str_starts_with($bom, "\xFE\xFF")) {
			$enc = 'UTF-16BE';
			$content = substr($content, 2);
		}
		elseif (str_starts_with($bom, "\xEF\xBB\xBF")) {
			$content = substr($content, 3);
			$enc = 'UTF-8';
		}

		if ($enc && $enc !== 'UTF-8') {
			$content = (string)mb_convert_encoding($content, 'UTF-8', $enc);
		}

		// разделитель: сравниваем число колонок в первой строке
		$delimiter = $this -> detectDelimiter($content);

		$handle = fopen('php://temp', 'r+');
		fwrite($handle, $content);
		rewind($handle);

		$rows = [];

		while (($row = fgetcsv($handle, 0, $delimiter, $this -> Options['Enclosure'], '\\')) !== false) {
			$rows[] = $row;
		}

		fclose($handle);

		return $rows;

	}

	/**
	 * @param string $content
	 * @return string
	 */
	private function detectDelimiter(string $content): string {

		$first = strtok($content, "\r\n");
		$first = ($first === false) ? '' : $first;

		if (!str_contains($first, ',')) {
			return ';';
		}
		if (!str_contains($first, ';')) {
			return ',';
		}

		$semicolon = substr_count($first, ';');
		$comma     = substr_count($first, ',');
		$tab       = substr_count($first, "\t");

		if ($tab > $semicolon && $tab > $comma) {
			return "\t";
		}

		return ($comma > $semicolon) ? ',' : ';';

	}

	/**
	 * ODS: минимальный парсер content.xml (без сторонних библиотек)
	 *
	 * @param string $file
	 * @return void
	 */
	private function loadOds(string $file): void {

		if (!class_exists('ZipArchive')) {
			throw new Exception('SpreadsheetReader: для ODS нужно расширение zip');
		}

		$zip = new ZipArchive();
		if ($zip -> open($file) !== true) {
			throw new Exception('SpreadsheetReader: не удалось открыть ODS');
		}

		$xml = $zip -> getFromName('content.xml');
		$zip -> close();

		if ($xml === false) {
			throw new Exception('SpreadsheetReader: в ODS нет content.xml');
		}

		$doc = @simplexml_load_string($xml);
		if (!$doc) {
			throw new Exception('SpreadsheetReader: не разобрался content.xml ODS');
		}

		$doc -> registerXPathNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
		$doc -> registerXPathNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
		$doc -> registerXPathNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');

		$tables = $doc -> xpath('//table:table') ?: [];

		$this -> Sheets = [];
		$this -> Rows   = [];

		foreach ($tables as $t => $table) {

			$table -> registerXPathNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
			$table -> registerXPathNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');

			$name = (string)$table['table:name'];
			$this -> Sheets[$t] = ($name !== '') ? $name : 'Sheet'.($t + 1);

			$rows = [];

			foreach ($table -> xpath('table:table-row') ?: [] as $row) {

				$cells = [];
				$rowRepeat = max(1, (int)$row['table:number-rows-repeated']);

				foreach ($row -> xpath('table:table-cell') ?: [] as $cell) {

					$repeat = max(1, (int)$cell['table:number-columns-repeated']);

					$value = '';
					$parts = $cell -> xpath('.//text:p') ?: [];
					foreach ($parts as $p) {
						$value .= ($value === '' ? '' : "\n").trim((string)$p);
					}
					if ($value === '' && isset($cell['office:value'])) {
						$value = (string)$cell['office:value'];
					}

					for ($r = 0; $r < min($repeat, 1000); $r++) {
						$cells[] = $value;
					}

				}

				// пустые повторяющиеся хвосты не размножаем
				$isEmpty = (count(array_filter($cells, static fn($v) => $v !== '')) === 0);

				if ($isEmpty && $rowRepeat > 1) {
					break;
				}

				for ($r = 0; $r < min($rowRepeat, 1000); $r++) {
					$rows[] = $cells;
				}

			}

			$this -> Rows[$t] = $rows;

		}

		if ($this -> Sheets === []) {
			$this -> Sheets = [0 => 'Sheet1'];
			$this -> Rows   = [0 => []];
		}

	}

	/**
	 * @return array - индекс листа => имя
	 */
	public function Sheets(): array {
		return $this -> Sheets;
	}

	/**
	 * @param int $Index
	 * @return bool
	 */
	public function ChangeSheet($Index): bool {

		$Index = (int)$Index;

		if (!isset($this -> Sheets[$Index])) {
			return false;
		}

		$this -> SheetIndex = $Index;
		$this -> Index = 0;

		return true;

	}

	// --- SeekableIterator / Countable ---------------------------------------

	public function rewind(): void {
		$this -> Index = 0;
	}

	public function current(): mixed {
		return $this -> Rows[$this -> SheetIndex][$this -> Index] ?? null;
	}

	public function next(): void {
		$this -> Index++;
	}

	public function key(): mixed {
		return $this -> Index;
	}

	public function valid(): bool {
		return isset($this -> Rows[$this -> SheetIndex][$this -> Index]);
	}

	public function count(): int {
		return count($this -> Rows[$this -> SheetIndex] ?? []);
	}

	public function seek($Position): void {

		$Position = (int)$Position;

		if (!isset($this -> Rows[$this -> SheetIndex][$Position])) {
			throw new OutOfBoundsException('SpreadsheetReader: позиция '.$Position.' не найдена');
		}

		$this -> Index = $Position;

	}

}

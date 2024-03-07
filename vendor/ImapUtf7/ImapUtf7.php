<?php
namespace Imap;

class ImapUtf7 {

	static $imap_base64 =
		'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+,';

	static private function encode_b64imap($s) {
		$a   = 0;
		$al  = 0;
		$res = '';
		$n   = strlen($s);
		for ($i = 0; $i < $n; $i++) {
			$a  = ($a << 8) | ord($s[ $i ]);
			$al += 8;
			for (; $al >= 6; $al -= 6) $res .= self ::$imap_base64[ ($a >> ($al - 6)) & 0x3F ];
		}
		if ($al > 0) {
			$res .= self ::$imap_base64[ ($a << (6 - $al)) & 0x3F ];
		}

		return $res;
	}

	static private function encode_utf8_char($w) {
		if ($w & 0x80000000) return '';
		if ($w & 0xFC000000) $n = 5;
		else
			if ($w & 0xFFE00000) $n = 4;
			else
				if ($w & 0xFFFF0000) $n = 3;
				else
					if ($w & 0xFFFFF800) $n = 2;
					else
						if ($w & 0xFFFFFF80) $n = 1;
						else return chr($w);
		$res = chr(((255 << (7 - $n)) | ($w >> ($n * 6))) & 255);
		while (--$n >= 0) $res .= chr((($w >> ($n * 6)) & 0x3F) | 0x80);

		return $res;
	}

	static private function decode_b64imap($s) {
		$a   = 0;
		$al  = 0;
		$res = '';
		$n   = strlen($s);
		for ($i = 0; $i < $n; $i++) {
			$k = strpos(self ::$imap_base64, $s[ $i ]);
			if ($k === false) continue;
			$a  = ($a << 6) | $k;
			$al += 6;
			if ($al >= 8) {
				$res .= chr(($a >> ($al - 8)) & 255);
				$al  -= 8;
			}
		}
		$r2 = '';
		$n  = strlen($res);
		for ($i = 0; $i < $n; $i++) {
			$c = ord($res[ $i ]);
			$i++;
			if ($i < $n) $c = ($c << 8) | ord($res[ $i ]);
			$r2 .= self ::encode_utf8_char($c);
		}

		return $r2;
	}

	static function encode($s) {
		$n   = strlen($s);
		$err = 0;
		$buf = '';
		$res = '';
		for ($i = 0; $i < $n;) {
			$x = ord($s[ $i++ ]);
			if (($x & 0x80) == 0x00) {
				$r = $x;
				$w = 0;
			}
			else if (($x & 0xE0) == 0xC0) {
				$w = 1;
				$r = $x & 0x1F;
			}
			else if (($x & 0xF0) == 0xE0) {
				$w = 2;
				$r = $x & 0x0F;
			}
			else if (($x & 0xF8) == 0xF0) {
				$w = 3;
				$r = $x & 0x07;
			}
			else if (($x & 0xFC) == 0xF8) {
				$w = 4;
				$r = $x & 0x03;
			}
			else if (($x & 0xFE) == 0xFC) {
				$w = 5;
				$r = $x & 0x01;
			}
			else if (($x & 0xC0) == 0x80) {
				$w = 0;
				$r = -1;
				$err++;
			}
			else {
				$w = 0;
				$r = -2;
				$err++;
			}
			for ($k = 0; $k < $w && $i < $n; $k++) {
				$x = ord($s[ $i++ ]);
				if ($x & 0xE0 != 0x80) {
					$err++;
				}
				$r = ($r << 6) | ($x & 0x3F);
			}
			if ($r < 0x20 || $r > 0x7E) {
				$buf .= chr(($r >> 8) & 0xFF);
				$buf .= chr($r & 0xFF);
			}
			else {
				if (strlen($buf)) {
					$res .= '&'.self ::encode_b64imap($buf).'-';
					$buf = '';
				}
				if ($r == 0x26) {
					$res .= '&-';
				}
				else $res .= chr($r);
			}
		}
		if (strlen($buf)) $res .= '&'.self ::encode_b64imap($buf).'-';

		return $res;
	}

	static function decode($s) {
		$res = '';
		$n   = strlen($s);
		$h   = 0;
		while ($h < $n) {
			$t = strpos($s, '&', $h);
			if ($t === false) $t = $n;
			$res .= substr($s, $h, $t - $h);
			$h   = $t + 1;
			if ($h >= $n) break;
			$t = strpos($s, '-', $h);
			if ($t === false) $t = $n;
			$k = $t - $h;
			if ($k == 0) $res .= '&';
			else $res .= self ::decode_b64imap(substr($s, $h, $k));
			$h = $t + 1;
		}

		return $res;
	}

}
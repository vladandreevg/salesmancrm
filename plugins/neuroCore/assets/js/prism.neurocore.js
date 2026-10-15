/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Дополнительные языки для подсветки примеров в справке Нейроядра.
 *
 * Базовый набор Prism (assets/js/prism.min.js) знает markup, CSS, JS и PHP — этого
 * хватает на примеры кода, но не на то, что встречается в справке чаще всего:
 * JSON-ответы модели, консольные команды и SQL-запросы к таблицам ядра. Без
 * грамматик эти блоки остались бы серым текстом, а справка для разработчика — это
 * в первую очередь примеры.
 *
 * Грамматики написаны здесь, а не взяты готовыми файлами Prism, по двум причинам:
 * сборка Prism в CRM уже готовая и «дособирать» её отдельными компонентами нельзя,
 * а языков нужно всего три. Заодно это снимает зависимость от версии Prism:
 * используются только те приёмы (pattern/lookbehind/inside/alias), которые есть в
 * любой версии 1.x.
 *
 * Готовые языки здесь не переопределяются: php, javascript, css и markup приходят
 * из базовой сборки, и подменять их «своим» вариантом нельзя — подсветка PHP в
 * справке должна оставаться полной.
 */

(function () {

	'use strict';

	if (typeof Prism === 'undefined' || !Prism.languages) {
		return;
	}

	/** Грамматику не трогаем, если такой язык уже подключён кем-то ещё. */
	function define(name, grammar) {

		if (Prism.languages[name]) {
			return;
		}

		Prism.languages[name] = grammar;

	}

	// --- JSON: ответы модели и примеры схем ------------------------------------

	define('json', {
		property: {
			pattern: /(^|[^\\])"(?:\\.|[^\\"\r\n])*"(?=\s*:)/,
			lookbehind: true,
			greedy: true
		},
		string: {
			pattern: /(^|[^\\])"(?:\\.|[^\\"\r\n])*"(?!\s*:)/,
			lookbehind: true,
			greedy: true
		},
		number: /-?\b\d+(?:\.\d+)?(?:e[+-]?\d+)?\b/i,
		boolean: /\b(?:true|false)\b/,
		null: {
			pattern: /\bnull\b/,
			alias: 'keyword'
		},
		punctuation: /[{}[\],]/
	});

	// --- Консоль: запуск воркера, команды планировщика --------------------------

	define('bash', {
		comment: {
			pattern: /(^|[^"'{\\$])#.*/,
			lookbehind: true,
			greedy: true
		},
		string: {
			pattern: /"(?:\\.|[^"\\\r\n])*"|'(?:[^'\\\r\n]|\\.)*'/,
			greedy: true
		},
		// переменные окружения — их в консольных примерах подставляют руками
		variable: /(?:\$\{?[A-Za-z_][A-Za-z0-9_]*\}?)/,
		// сам интерпретатор и типовые команды примеров
		keyword: /\b(?:php|sudo|cd|export|source|set|env|crontab|mysql|chmod|chown|systemctl|service|composer|git)\b/,
		// длинные опции вида --limit=5 — на них в примерах смотрят в первую очередь
		operator: /(?:--?[A-Za-z][\w-]*=?|&&|\|\||[|<>]=?|=)/,
		number: /\b\d+\b/,
		punctuation: /[;()]/
	});

	// --- SQL: выборка результатов задания и служебные запросы -------------------

	define('sql', {
		comment: {
			pattern: /(^|[^\\])(?:\/\*[\s\S]*?\*\/|--.*)/,
			lookbehind: true,
			greedy: true
		},
		string: {
			pattern: /'(?:''|[^'])*'|"(?:[^"]|"")*"/,
			greedy: true
		},
		keyword: /\b(?:SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|AND|OR|NOT|NULL|IS|IN|AS|ON|CREATE|ALTER|TABLE|IF|EXISTS|PRIMARY|UNIQUE|KEY|INDEX|ENGINE|DEFAULT|COMMENT|ORDER|BY|LIMIT|GROUP|JOIN|LEFT|INNER|SET|VALUES|INTO|COUNT|SUM|MIN|MAX|COALESCE)\b/i,
		function: /\b(?:NOW|CURDATE|DATE|DATE_FORMAT|JSON_EXTRACT)\b(?=\s*\()/i,
		number: /\b\d+(?:\.\d+)?\b/,
		operator: /[<>!=]=?|[-+*/%]/,
		punctuation: /[(),;]/
	});

})();

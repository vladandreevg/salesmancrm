/**
 * @license Copyright (c) 2003-2016, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	config.width = '100%';
	config.height = 400;
	config.skin = 'moono-lisa';
	config.allowedContent = true;
	config.disableNativeSpellChecker = false;
	//config.removePlugins = 'elementspath';
	//config.resize_enabled = false;
	config.extraPlugins = 'stylesheetparser';
	config.contentsCss = '/assets/css/editor.css';
	config.extraPlugins = 'image2';
	config.extraPlugins = 'widget';
	config.extraPlugins = 'dialog';
	config.extraPlugins = 'lineutils';
	config.extraPlugins = 'clipboard';
	config.extraPlugins = 'dialogui';
	config.extraPlugins = 'imageuploader';
	//config.extraPlugins = 'base64image';
	config.extraPlugins = 'textselection,codemirror';
	config.codemirror = {

		// Set this to the theme you wish to use (codemirror themes)
		theme: 'base16-light',

		// Whether or not you want to show line numbers
		lineNumbers: true,

		// Whether or not you want to use line wrapping
		lineWrapping: true,

		// Whether or not you want to highlight matching braces
		matchBrackets: true,

		// Whether or not you want tags to automatically close themselves
		autoCloseTags: true,

		// Whether or not you want Brackets to automatically close themselves
		autoCloseBrackets: true,

		// Whether or not to enable search tools, CTRL+F (Find), CTRL+SHIFT+F (Replace), CTRL+SHIFT+R (Replace All), CTRL+G (Find Next), CTRL+SHIFT+G (Find Previous)
		enableSearchTools: true,

		// Whether or not you wish to enable code folding (requires 'lineNumbers' to be set to 'true')
		enableCodeFolding: true,

		// Whether or not to enable code formatting
		enableCodeFormatting: true,

		// Whether or not to automatically format code should be done when the editor is loaded
		autoFormatOnStart: true,

		// Whether or not to automatically format code should be done every time the source view is opened
		autoFormatOnModeChange: true,

		// Whether or not to automatically format code which has just been uncommented
		autoFormatOnUncomment: true,

		// Define the language specific mode 'htmlmixed' for html including (css, xml, javascript), 'application/x-httpd-php' for php mode including html, or 'text/javascript' for using java script only
		mode: 'htmlmixed',

		// Whether or not to show the search Code button on the toolbar
		showSearchButton: true,

		// Whether or not to show Trailing Spaces
		showTrailingSpace: true,

		// Whether or not to highlight all matches of current word/selection
		highlightMatches: true,

		// Whether or not to show the format button on the toolbar
		showFormatButton: true,

		// Whether or not to show the comment button on the toolbar
		showCommentButton: true,

		// Whether or not to show the uncomment button on the toolbar
		showUncommentButton: true,

		// Whether or not to show the showAutoCompleteButton button on the toolbar
		showAutoCompleteButton: true,

		// Whether or not to highlight the currently active line
		styleActiveLine: true
	};
	
	config.toolbar =
	[
		{ name: 'document', items : [ 'Source','-','Save','NewPage','DocProps','Preview','Print','-','Templates' ] },
		{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
		{ name: 'insert', items : [ 'Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','Iframe' ] },
		{ name: 'tools', items : [ 'Maximize', 'ShowBlocks','-','About' ] },
		{ name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
		{ name: 'colors', items : [ 'TextColor','BGColor' ] },
		{ name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv',
		'-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
		{ name: 'links', items : [ 'Link','Unlink','Anchor' ] },
		'/',
		{ name: 'styles', items : [ 'Styles','Format','Font','FontSize','CopyFormatting','RemoveFormat' ] }
	];

};
CKEDITOR.config.enterMode = CKEDITOR.ENTER_BR;
/**
 * Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	// %REMOVE_START%
	config.plugins = configPlugins();

	config.toolbarGroups = configToolbarGroup();

	config.extraPlugins =
		'abbr,' +
		'pbckcode,' +
		'bootstrapTabs,' +
		'grid,' +
		'codesnippet,' +
		'codeplayer,' +
		'codemirror,' +
		'timestamp,' +
		'fontawesome' +
		'';

	config.allowedContent = true;
	// config.extraAllowedContent = true;
	config.contentsLangDirection = 'rtl';
	// config.magicline_everywhere = true;
	// config.magicline_tabuList = [ 'data-tabu' ];

	config.height = 500;
	config.uiColor = '#CCEAEE';

	config.stylesSet = configStylesSet();

	config.pbckcode = configPbckcode();
	config.codeplayer = configCodeplayer();
	config.codemirror = configCodemirror();

};

function configPlugins() {
	return 'about,' +
		'a11yhelp,' +
		'basicstyles,' +
		'bidi,' +
		'blockquote,' +
		'clipboard,' +
		'colorbutton,' +
		'colordialog,' +
		'copyformatting,' +
		'contextmenu,' +
		'dialogadvtab,' +
		'div,' +
		'elementspath,' +
		'enterkey,' +
		'entities,' +
		'filebrowser,' +
		'find,' +
		'flash,' +
		'floatingspace,' +
		'font,' +
		'format,' +
		'forms,' +
		'horizontalrule,' +
		'htmlwriter,' +
		'image,' +
		'iframe,' +
		'indentlist,' +
		'indentblock,' +
		'justify,' +
		'language,' +
		'link,' +
		'list,' +
		'liststyle,' +
		'magicline,' +
		'maximize,' +
		'newpage,' +
		'pagebreak,' +
		'pastefromword,' +
		'pastetext,' +
		'preview,' +
		'print,' +
		'removeformat,' +
		'resize,' +
		'save,' +
		'selectall,' +
		'showblocks,' +
		'showborders,' +
		'smiley,' +
		'sourcearea,' +
		'specialchar,' +
		'stylescombo,' +
		'tab,' +
		'table,' +
		'tableselection,' +
		'tabletools,' +
		'templates,' +
		'toolbar,' +
		'undo,' +
		'uploadimage,' +
		'wysiwygarea';
}
function configCodemirror() {
	return {

		// Set this to the theme you wish to use (codemirror themes)
		theme: 'default',

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
}
function configPbckcode() {
	return {
		cls: '',

		highlighter: 'SYNTAX_HIGHLIGHTER', // HIGHLIGHT, PRETTIFY, PRISM, SYNTAX_HIGHLIGHTER
		modes: [
			['PHP', 'php'],
			['HTML', 'html'],
			['CSS', 'css'],
			['JS', 'javascript'],
			['C/C++', 'c_cpp'],
			['C9Search', 'c9search'],
			['Clojure', 'clojure'],
			['CoffeeScript', 'coffee'],
			['ColdFusion', 'coldfusion'],
			['C#', 'csharp'],
			['Diff', 'diff'],
			['Glsl', 'glsl'],
			['Go', 'golang'],
			['Groovy', 'groovy'],
			['haXe', 'haxe'],
			['Jade', 'jade'],
			['Java', 'java'],
			['JSON', 'json'],
			['JSP', 'jsp'],
			['JSX', 'jsx'],
			['LaTeX', 'latex'],
			['LESS', 'less'],
			['Liquid', 'liquid'],
			['Lua', 'lua'],
			['LuaPage', 'luapage'],
			['Markdown', 'markdown'],
			['OCaml', 'ocaml'],
			['Perl', 'perl'],
			['pgSQL', 'pgsql'],
			['Powershell', 'powershel1'],
			['Python', 'python'],
			['R', 'ruby'],
			['OpenSCAD', 'scad'],
			['Scala', 'scala'],
			['SCSS/Sass', 'scss'],
			['SH', 'sh'],
			['SQL', 'sql'],
			['SVG', 'svg'],
			['Tcl', 'tcl'],
			['Text', 'text'],
			['Textile', 'textile'],
			['XML', 'xml'],
			['XQuery', 'xq'],
			['YAML', 'yaml']
		],

		theme: 'eclipse',

		tab_size: '4'
	};
}
function configCodeplayer() {
	return {
		cls: '',

		highlighter: 'SYNTAX_HIGHLIGHTER', // HIGHLIGHT, PRETTIFY, PRISM, SYNTAX_HIGHLIGHTER
		modes: [
			['PHP', 'php'],
			['HTML', 'html'],
			['CSS', 'css'],
			['JS', 'javascript'],
			['C/C++', 'c_cpp'],
			['C9Search', 'c9search'],
			['Clojure', 'clojure'],
			['CoffeeScript', 'coffee'],
			['ColdFusion', 'coldfusion'],
			['C#', 'csharp'],
			['Diff', 'diff'],
			['Glsl', 'glsl'],
			['Go', 'golang'],
			['Groovy', 'groovy'],
			['haXe', 'haxe'],
			['Jade', 'jade'],
			['Java', 'java'],
			['JSON', 'json'],
			['JSP', 'jsp'],
			['JSX', 'jsx'],
			['LaTeX', 'latex'],
			['LESS', 'less'],
			['Liquid', 'liquid'],
			['Lua', 'lua'],
			['LuaPage', 'luapage'],
			['Markdown', 'markdown'],
			['OCaml', 'ocaml'],
			['Perl', 'perl'],
			['pgSQL', 'pgsql'],
			['Powershell', 'powershel1'],
			['Python', 'python'],
			['R', 'ruby'],
			['OpenSCAD', 'scad'],
			['Scala', 'scala'],
			['SCSS/Sass', 'scss'],
			['SH', 'sh'],
			['SQL', 'sql'],
			['SVG', 'svg'],
			['Tcl', 'tcl'],
			['Text', 'text'],
			['Textile', 'textile'],
			['XML', 'xml'],
			['XQuery', 'xq'],
			['YAML', 'yaml']
		],

		url: 'https://api.projekt.ir/codeplayers',

		theme: 'eclipse',

		tab_size: '4'
	};
}
function configToolbarGroup() {
	return [
		{name: 'document', groups: ['mode', 'document', 'doctools']},
		{name: 'clipboard', groups: ['clipboard', 'undo']},
		{name: 'editing', groups: ['find', 'selection', 'spellchecker']},
		{name: 'forms'},
		'/',
		{name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
		{name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi']},
		{name: 'links'},
		'/',
		{name: 'insert'},
		{
			name: 'code'
		},
		'/',
		{name: 'styles'},
		{name: 'colors'},
		{name: 'tools'},
		{name: 'others'},
		{name: 'about'}
	];
}
function configStylesSet() {
	return [
		/* Inline Styles */
		{ name: 'Marker', element: 'span', attributes: { 'class': 'marker' } },
		{ name: 'Cited Work', element: 'cite' },
		{ name: 'Inline Quotation', element: 'q' },

		/* Object Styles */
		{
			name: 'Special Container',
			element: 'div',
			styles: {
				padding: '5px 10px',
				background: '#eee',
				border: '1px solid #ccc'
			}
		},
		{
			name: 'Compact table',
			element: 'table',
			attributes: {
				cellpadding: '5',
				cellspacing: '0',
				border: '1',
				bordercolor: '#ccc'
			},
			styles: {
				'border-collapse': 'collapse'
			}
		},
		{ name: 'Borderless Table', element: 'table', styles: { 'border-style': 'hidden', 'background-color': '#E6E6FA' } },
		{ name: 'Square Bulleted List', element: 'ul', styles: { 'list-style-type': 'square' } }
	];
}


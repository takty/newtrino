/**
 *
 * Editor Commands for TinyMCE (Sample)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-09-17
 *
 */


window.NT.tiny_mce_before_init.push(function (args, lang, urlAssets) {
	const ls_ja = { column_2: '2段組', column_3: '3段組', column_4: '4段組' };
	const ls_en = { column_2: '2 Columns', column_3: '3 Columns', column_4: '4 Columns' };
	const ls = (lang === 'ja') ? ls_ja : ls_en;

	function insert(ed, str) {
		ed.execCommand('mceInsertContent', false, { content: str, merge: true, paste: true });
	}
	tinymce.create('tinymce.plugins.columns', {
		init: function (ed, url) {
			ed.addCommand('column_2', function () {
				insert(ed, '<div class="column-2"><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div></div>&nbsp;');
			});
			ed.addCommand('column_3', function () {
				insert(ed, '<div class="column-3"><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div></div>&nbsp;');
			});
			ed.addCommand('column_4', function () {
				insert(ed, '<div class="column-4"><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div></div>&nbsp;');
			});
			ed.addButton('column_2', {
				title: ls[0],
				cmd: 'column_2',
				image: urlAssets + 'icon-column-2.svg'
			});
			ed.addButton('column_3', {
				title: ls[1],
				cmd: 'column_3',
				image: urlAssets + 'icon-column-3.svg'
			});
			ed.addButton('column_4', {
				title: ls[2],
				cmd: 'column_4',
				image: urlAssets + 'icon-column-4.svg'
			});
		}
	});
	tinymce.PluginManager.add('columns', tinymce.plugins.columns);

	args.plugins.push('columns');
	args.toolbar2 += ' column_2 column_3 column_4';
	return args;
});

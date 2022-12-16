/**
 * Editor Commands for TinyMCE (Sample)
 *
 * @author Takuto Yanagida
 * @version 2021-06-02
 */

window.NT.tiny_mce_before_init.push(function (args, lang, urlAssets) {
	const ls_ja = { column_2: '2段組', column_3: '3段組', column_4: '4段組' };
	const ls_en = { column_2: '2 Columns', column_3: '3 Columns', column_4: '4 Columns' };
	const ls    = (lang === 'ja') ? ls_ja : ls_en;

	const htmls = {
		column_2: '<div class="column-2"><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div></div>&nbsp;',
		column_3: '<div class="column-3"><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div></div>&nbsp;',
		column_4: '<div class="column-4"><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div><div><p>&nbsp;</p></div></div>&nbsp;',
	};
	const icons = {
		column_2: '<svg width="16" height="16"><path d="M2 2h5v12H2zM9 2h5v12H9z"/></svg>',
		column_3: '<svg width="16" height="16"><path d="M2 2h3v12H2zM7 2h3v12H7zM12 2h3v12h-3z"/></svg>',
		column_4: '<svg width="16" height="16"><path d="M1 2h2v12H1zM5 2h2v12H5zM9 2h2v12H9zM13 2h2v12h-2z"/></svg>',
	}
	function insert(ed, str) {
		ed.execCommand('mceInsertContent', false, { content: str, merge: true, paste: true });
	}
	tinymce.create('tinymce.plugins.columns', {
		init: function (ed, url) {
			for (const key of Object.keys(ls)) {
				ed.addCommand(key, () => { insert(ed, htmls[key]); });
				ed.ui.registry.addIcon(key, icons[key]);
				ed.ui.registry.addButton(key, {
					icon: key,
					tooltip: ls[key],
					onAction: function () { ed.execCommand(key); },
				});
			}
		}
	});
	tinymce.PluginManager.add('columns', tinymce.plugins.columns);

	args.plugins.push('columns');
	args.toolbar2 += ' column_2 column_3 column_4';
	return args;
});

/**
 * Gulpfile - Tasks for copying libraries
 *
 * @author Takuto Yanagida
 * @version 2022-08-19
 */

import gulp from 'gulp';

import { pkgDir } from './_common.mjs';
import { makeCopyTask } from './_task-copy.mjs';

const DIST_ADMIN = './dist/admin/';


// -----------------------------------------------------------------------------


const makeTaskCopyNacssReset = () => {
	const dir = pkgDir('nacss-reset');
	const f = makeCopyTask(dir + '/dist/css/reset.min.css*(.map)', DIST_ADMIN + 'css/');
	f.displayName = 'adminLibCopyNacssReset';
	return f;
};

const makeTaskCopyJssha = () => {
	const dir = pkgDir('jssha');
	const f = makeCopyTask(dir + '/dist/sha256.js', DIST_ADMIN + 'js/jssha/');
	f.displayName = 'adminLibCopyJssha';
	return f;
};

const makeTaskCopyLuxon = () => {
	const dir = pkgDir('luxon');
	const f = makeCopyTask(dir + '/build/global/luxon.min.js*(.map)', DIST_ADMIN + 'js/luxon/');
	f.displayName = 'adminLibCopyLuxon';
	return f;
};

const makeTaskCopyFlatpickr = () => {
	const dir = pkgDir('flatpickr');
	const f = gulp.parallel(
		makeCopyTask(dir + '/dist/flatpickr.min.js', DIST_ADMIN + 'js/flatpickr/'),
		makeCopyTask(dir + '/dist/flatpickr.min.css', DIST_ADMIN + 'css/flatpickr/'),
		makeCopyTask(dir + '/dist/l10n/ja.js', DIST_ADMIN + 'js/flatpickr/')
	);
	f.displayName = 'adminLibCopyFlatpickr';
	return f;
};

const makeTaskCopyTinymce = () => {
	const dir      = pkgDir('tinymce');
	const dir_i18n = pkgDir('tinymce') + '/../tinymce-i18n';  // pkgDir('tinymce-i18n');
	const ups = [  // Unused plugins
		'autoresize', 'autosave', 'bbcode', 'codesample', 'colorpicker', 'contextmenu', 'emoticons',
		'fullpage', 'fullscreen', 'help', 'imagetools', 'importcss', 'legacyoutput', 'pagebreak',
		'preview', 'save', 'spellchecker', 'tabfocus', 'textcolor', 'toc', 'template', 'wordcount'
	];
	const f = gulp.parallel(
		makeCopyTask(dir + '/tinymce.min.js', DIST_ADMIN + 'js/tinymce/'),
		makeCopyTask(dir + '/skins/**/*', DIST_ADMIN + 'js/tinymce/skins/', dir + '/skins/'),
		makeCopyTask(dir + '/icons/**/*', DIST_ADMIN + 'js/tinymce/icons/', dir + '/icons/'),
		makeCopyTask(dir + '/themes/silver/**/*', DIST_ADMIN + 'js/tinymce/themes/silver/', dir + '/themes/silver/'),
		makeCopyTask(dir_i18n + '/langs5/ja.js', DIST_ADMIN + 'js/tinymce/langs/'),
		makeCopyTask([dir + '/plugins/**/*', ...ups.map(e => `!${dir}/plugins/${e}/**/*`)], DIST_ADMIN + 'js/tinymce/plugins/', dir + '/plugins/')
	);
	f.displayName = 'adminLibCopyTinymce';
	return f;
};

export const taskAdminLib = gulp.parallel(
	makeTaskCopyNacssReset(),
	makeTaskCopyJssha(),
	makeTaskCopyFlatpickr(),
	makeTaskCopyLuxon(),
	makeTaskCopyTinymce(),
);

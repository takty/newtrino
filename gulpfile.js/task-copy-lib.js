/**
 *
 * Gulpfile - Tasks for copying libraries
 *
 * @author Takuto Yanagida
 * @version 2021-06-13
 *
 */


'use strict';

const gulp = require('gulp');

const { pkgDir, makeCopyTask } = require('./common');

const DIST_BASE  = './dist/';
const DIST_ADMIN = './dist/admin/';


// -----------------------------------------------------------------------------


const makeTaskCopyMustache = () => {
	// Dest directory must be capitalized
	const f = makeCopyTask('./vendor/mustache/mustache/src/Mustache/**/*', DIST_BASE + 'core/lib/Mustache/', './vendor/mustache/mustache/src/Mustache/');
	f.displayName = 'copyLibCopyMustache';
	return f;
};

const makeTaskCopyNacssReset = () => {
	const dir = pkgDir('nacss-reset');
	const f = makeCopyTask(dir + '/dist/reset.min.css*(.map)', DIST_ADMIN + 'css/');
	f.displayName = 'copyLibCopyNacssReset';
	return f;
};

const makeTaskCopyJssha = () => {
	const dir = pkgDir('jssha');
	const f = makeCopyTask(dir + '/dist/sha256.js', DIST_ADMIN + 'js/jssha/');
	f.displayName = 'copyLibCopyJssha';
	return f;
};

const makeTaskCopyMoment = () => {
	const dir = pkgDir('moment');
	const f = makeCopyTask(dir + '/min/moment.min.js*(.map)', DIST_ADMIN + 'js/moment/');
	f.displayName = 'copyLibCopyMoment';
	return f;
};

const makeTaskCopyFlatpickr = () => {
	const dir = pkgDir('flatpickr');
	const f = gulp.parallel(
		makeCopyTask(dir + '/dist/flatpickr.min.js', DIST_ADMIN + 'js/flatpickr/'),
		makeCopyTask(dir + '/dist/flatpickr.min.css', DIST_ADMIN + 'css/flatpickr/'),
		makeCopyTask(dir + '/dist/l10n/ja.js', DIST_ADMIN + 'js/flatpickr/')
	);
	f.displayName = 'copyLibCopyFlatpickr';
	return f;
};

const makeTaskCopyTinymce = () => {
	const dir      = pkgDir('tinymce');
	const dir_i18n = pkgDir('tinymce-i18n');
	const ups = [  // Unused plugins
		'autoresize', 'autosave', 'bbcode', 'codesample', 'colorpicker', 'contextmenu', 'emoticons',
		'fullpage', 'fullscreen', 'help', 'importcss', 'legacyoutput', 'pagebreak',
		'preview', 'save', 'tabfocus', 'textcolor', 'toc', 'template', 'wordcount'
	];
	const f = gulp.parallel(
		makeCopyTask(dir + '/tinymce.min.js', DIST_ADMIN + 'js/tinymce/'),
		makeCopyTask(dir + '/skins/**/*', DIST_ADMIN + 'js/tinymce/skins/', dir + '/skins/'),
		makeCopyTask(dir + '/icons/**/*', DIST_ADMIN + 'js/tinymce/icons/', dir + '/icons/'),
		makeCopyTask(dir + '/themes/silver/**/*', DIST_ADMIN + 'js/tinymce/themes/silver/', dir + '/themes/silver/'),
		makeCopyTask(dir_i18n + '/langs5/ja.js', DIST_ADMIN + 'js/tinymce/langs/'),
		makeCopyTask([dir + '/plugins/**/*', ...ups.map(e => `!${dir}/plugins/${e}/**/*`)], DIST_ADMIN + 'js/tinymce/plugins/', dir + '/plugins/')
	);
	f.displayName = 'copyLibCopyTinymce';
	return f;
};

exports.taskCopyLib = gulp.parallel(
	makeTaskCopyMustache(),
	makeTaskCopyNacssReset(),
	makeTaskCopyJssha(),
	makeTaskCopyFlatpickr(),
	makeTaskCopyMoment(),
	makeTaskCopyTinymce(),
);

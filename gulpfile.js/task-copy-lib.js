/**
 *
 * Gulpfile - Copy Libraries
 *
 * @author Takuto Yanagida
 * @version 2021-06-10
 *
 */


'use strict';

const fs   = require('fs-extra');
const gulp = require('gulp');

const { copySync, packageDir } = require('./common');

const DIST_BASE  = './dist/';
const DIST_ADMIN = './dist/admin/';


// -----------------------------------------------------------------------------


const copyJssha = (done) => {
	const dir = packageDir('jssha');
	copySync(dir + '/dist/sha256.js', DIST_ADMIN + 'js/jssha/');
	done();
};

const copyFlatpickr = (done) => {
	const dir = packageDir('flatpickr');
	copySync(dir + '/dist/flatpickr.min.js', DIST_ADMIN + 'js/flatpickr/');
	copySync(dir + '/dist/flatpickr.min.css', DIST_ADMIN + 'css/flatpickr/');
	copySync(dir + '/dist/l10n/ja.js', DIST_ADMIN + 'js/flatpickr/');
	done();
};

const copyTinymce = (done) => {
	const dir = packageDir('tinymce');
	copySync(dir + '/tinymce.min.js', DIST_ADMIN + 'js/tinymce/');
	copySync(dir + '/plugins/*', DIST_ADMIN + 'js/tinymce/plugins/');
	copySync(dir + '/skins/*', DIST_ADMIN + 'js/tinymce/skins/');
	copySync(dir + '/icons/*', DIST_ADMIN + 'js/tinymce/icons/');
	copySync(dir + '/themes/silver/*', DIST_ADMIN + 'js/tinymce/themes/silver/');
	const dir_i18n = packageDir('tinymce-i18n');
	copySync(dir_i18n + '/langs5/ja.js', DIST_ADMIN + 'js/tinymce/langs/');
	const ups = [  // Unused plugins
		'autoresize', 'autosave', 'bbcode', 'codesample', 'colorpicker',
		'contextmenu', 'emoticons', 'fullpage', 'fullscreen', 'help',
		'importcss', 'legacyoutput', 'pagebreak', 'preview', 'save',
		'tabfocus', 'textcolor', 'toc', 'template', 'wordcount'
	];
	for (let up of ups) fs.removeSync(DIST_ADMIN + 'js/tinymce/plugins/' + up);
	done();
};

const copyNacssReset = (done) => {
	const dir = packageDir('nacss-reset');
	copySync(dir + '/dist/reset.min.css', DIST_ADMIN + 'css/');
	copySync(dir + '/dist/reset.min.css.map', DIST_ADMIN + 'css/');
	done();
};

const copyMustache = (done) => {
	// Dest directory must be capitalized
	copySync('./vendor/mustache/mustache/src/Mustache/*', DIST_BASE + 'core/lib/Mustache/');
	done();
};

const copyMoment = (done) => {
	const dir = packageDir('moment');
	copySync(dir + '/min/moment.min.js', DIST_ADMIN + 'js/moment/');
	copySync(dir + '/min/moment.min.js.map', DIST_ADMIN + 'js/moment/');
	done();
};

exports.taskCopyLib = gulp.parallel(
	copyJssha,
	copyFlatpickr,
	copyTinymce,
	copyNacssReset,
	copyMustache,
	copyMoment,
);

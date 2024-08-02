'use strict';

const fs   = require('fs-extra');
const glob = require('glob');
const path = require('path');
const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const $    = require('gulp-load-plugins')({ pattern: ['gulp-*', '!gulp-sass'] });

function copySync(from, to) {
	const isToDir = to.endsWith('/');
	const files = glob.sync(from);
	for (let f of files) {
		if (isToDir) {
			const fn = path.basename(f);
			fs.copySync(f, path.join(to, fn));
		} else {
			fs.copySync(f, to);
		}
	}
}

const SRC_PRIVATE = './src/topic/private/';
const DIST_BASE = './dist/topic/';
const DIST_PRIVATE = DIST_BASE + 'private/';

gulp.task('copy-jssha', (done) => {
	copySync('./node_modules/jssha/dist/sha256.js', DIST_PRIVATE + 'js/jssha/');
	done();
});

gulp.task('copy-flatpickr', (done) => {
	const dir = './node_modules/flatpickr/dist/';
	copySync(dir + 'flatpickr.min.js', DIST_PRIVATE + 'js/flatpickr/');
	copySync(dir + 'flatpickr.min.css', DIST_PRIVATE + 'css/flatpickr/');
	copySync(dir + 'l10n/ja.js', DIST_PRIVATE + 'js/flatpickr/');
	done();
});

gulp.task('copy-tinymce', (done) => {
	const dir = './node_modules/tinymce/';
	copySync(dir + 'tinymce.min.js', DIST_PRIVATE + 'js/tinymce/');
	copySync(dir + 'plugins/**/*', DIST_PRIVATE + 'js/tinymce/plugins/');
	copySync(dir + 'skins/**/*', DIST_PRIVATE + 'js/tinymce/skins/');
	copySync(dir + 'themes/**/*', DIST_PRIVATE + 'js/tinymce/themes/');
	copySync('./node_modules/tinymce-i18n/langs/ja.js', DIST_PRIVATE + 'js/tinymce/langs/');
	fs.removeSync(DIST_PRIVATE + 'js/tinymce/themes/inlite');
	fs.removeSync(DIST_PRIVATE + 'js/tinymce/themes/mobile');
	const ups = [
		'autoresize',
		'autosave',
		'bbcode',
		'codesample',
		'colorpicker',
		'directionality',
		'emoticons',
		'fullpage',
		'help',
		'imagetools',
		'importcss',
		'legacyoutput',
		'noneditable',
		'pagebreak',
		'save',
		'tabfocus',
		'template',
		'textpattern',
		'wordcount',
	];
	for (let up of ups) fs.removeSync(DIST_PRIVATE + 'js/tinymce/plugins/' + up);
	done();
});

gulp.task('copy-stile-sass', (done) => {
	copySync('./node_modules/stile/dist/sass/*', SRC_PRIVATE + 'lib/stile/sass/');
	done();
});

gulp.task('copy-lib', gulp.parallel(
	'copy-jssha',
	'copy-flatpickr',
	'copy-tinymce',
	'copy-stile-sass',
));

gulp.task('copy-src', (done) => {
	copySync('./src', './dist');
	copySync(SRC_PRIVATE + 'sass/*.css', DIST_PRIVATE + 'css/');
	fs.removeSync(DIST_BASE + 'post/*');
	fs.removeSync(DIST_PRIVATE + 'sass');
	fs.removeSync(DIST_PRIVATE + 'lib');
	for (let f of glob.sync(DIST_PRIVATE + 'js/*.js')) fs.removeSync(f);
	done();
});

gulp.task('copy-res', (done) => {
	copySync(SRC_PRIVATE + 'sass/*.svg', DIST_PRIVATE + 'css');
	done();
});

gulp.task('copy', gulp.series('copy-src', 'copy-lib', 'copy-res'));

gulp.task('delete-var', (done) => {
	fs.removeSync(DIST_BASE + 'core/var/log');
	fs.removeSync(DIST_PRIVATE + 'var/session');
	done();
});

gulp.task('js', () => {
	return gulp.src([SRC_PRIVATE + 'js/**/*.js'])
		.pipe($.plumber())
		.pipe($.babel())
		.pipe($.uglify())
		.pipe($.rename({ extname: '.min.js' }))
		.pipe(gulp.dest(DIST_PRIVATE + 'js'));
});

gulp.task('sass', () => {
	return gulp.src([SRC_PRIVATE + 'sass/style.scss'])
		.pipe($.plumber())
		.pipe(sass({ outputStyle: 'compressed' }))
		.pipe($.autoprefixer({ remove: false }))
		.pipe($.rename({ extname: '.min.css' }))
		.pipe(gulp.dest(DIST_PRIVATE + 'css/'));
});

gulp.task('default', gulp.series('copy', 'delete-var', 'js', 'sass'));

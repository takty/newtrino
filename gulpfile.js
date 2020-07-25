'use strict';

const fs   = require('fs-extra');
const glob = require('glob');
const path = require('path');
const gulp = require('gulp');
const $    = require('gulp-load-plugins')({ pattern: ['gulp-*'] });

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

const SRC_ADMIN = './src/admin/';
const DIST_BASE = './dist/';
const DIST_ADMIN = DIST_BASE + 'admin/';

gulp.task('copy-jssha', (done) => {
	copySync('./node_modules/jssha/dist/sha256.js', DIST_ADMIN + 'js/jssha/');
	done();
});

gulp.task('copy-flatpickr', (done) => {
	const dir = './node_modules/flatpickr/dist/';
	copySync(dir + 'flatpickr.min.js', DIST_ADMIN + 'js/flatpickr/');
	copySync(dir + 'flatpickr.min.css', DIST_ADMIN + 'css/flatpickr/');
	copySync(dir + 'l10n/ja.js', DIST_ADMIN + 'js/flatpickr/');
	done();
});

gulp.task('copy-tinymce', (done) => {
	const dir = './node_modules/tinymce/';
	copySync(dir + 'tinymce.min.js', DIST_ADMIN + 'js/tinymce/');
	copySync(dir + 'plugins/**/*', DIST_ADMIN + 'js/tinymce/plugins/');
	copySync(dir + 'skins/**/*', DIST_ADMIN + 'js/tinymce/skins/');
	copySync(dir + 'themes/**/*', DIST_ADMIN + 'js/tinymce/themes/');
	copySync('./node_modules/tinymce-i18n/langs/ja.js', DIST_ADMIN + 'js/tinymce/langs/');
	fs.removeSync(DIST_ADMIN + 'js/tinymce/themes/inlite');
	fs.removeSync(DIST_ADMIN + 'js/tinymce/themes/mobile');
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
	for (let up of ups) fs.removeSync(DIST_ADMIN + 'js/tinymce/plugins/' + up);
	done();
});

gulp.task('copy-nacss-reset', (done) => {
	copySync('./node_modules/nacss-reset/dist/reset.min.css', DIST_ADMIN + 'css/');
	done();
});

gulp.task('copy-mustache', (done) => {
	copySync('./vendor/mustache/mustache/src/Mustache/*', DIST_BASE + 'core/lib/mustache/');
	done();
});

gulp.task('copy-lib', gulp.parallel(
	'copy-jssha',
	'copy-flatpickr',
	'copy-tinymce',
	'copy-nacss-reset',
	'copy-mustache',
));

gulp.task('copy-src', (done) => {
	copySync('./src', './dist');
	copySync(SRC_ADMIN + 'sass/*.css', DIST_ADMIN + 'css/');
	fs.removeSync(DIST_ADMIN + 'sass');
	fs.removeSync(DIST_ADMIN + 'lib');
	for (let f of glob.sync(DIST_ADMIN + 'js/*.js')) fs.removeSync(f);
	for (let f of glob.sync(DIST_BASE + 'core/*.js')) fs.removeSync(f);
	for (let f of glob.sync(DIST_BASE + '*.js')) fs.removeSync(f);
	done();
});

gulp.task('copy-res', (done) => {
	copySync(SRC_ADMIN + 'sass/*.svg', DIST_ADMIN + 'css');
	done();
});

gulp.task('copy', gulp.series('copy-src', 'copy-lib', 'copy-res'));

gulp.task('delete-var', (done) => {
	fs.removeSync(DIST_BASE + 'core/var/log');
	fs.removeSync(DIST_ADMIN + 'var/session');
	done();
});

gulp.task('js', () => gulp.src(['src/*.js', 'src/**/*.js', '!src/**/*.min.js'])
	.pipe($.plumber())
	.pipe($.babel({
		presets: [['@babel/preset-env']],
	}))
	.pipe($.terser())
	.pipe($.rename({ extname: '.min.js' }))
	.pipe(gulp.dest('./dist'))
);

gulp.task('sass', () => {
	return gulp.src([SRC_ADMIN + 'sass/style.scss'])
		.pipe($.plumber())
		.pipe($.dartSass({ outputStyle: 'compressed' }))
		.pipe($.autoprefixer({ remove: false }))
		.pipe($.rename({ extname: '.min.css' }))
		.pipe(gulp.dest(DIST_ADMIN + 'css/'));
});

gulp.task('sample', () => {
	return gulp.src(['dist/**/*'])
		.pipe($.plumber())
		.pipe($.ignore.include({ isFile: true }))
		.pipe($.changed('sample/nt', { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest('sample/nt'));
});

gulp.task('watch', () => {
	gulp.watch('src/**/*.js', gulp.series('js'));
	gulp.watch('src/**/*.scss', gulp.series('sass'));
	gulp.watch(['src/**/*.html', 'src/**/*.php'], gulp.series('copy'));
	gulp.watch('src/**/*', gulp.series('sample'));
});

gulp.task('default', gulp.series('copy', 'delete-var', 'js', 'sass', 'sample', 'watch'));

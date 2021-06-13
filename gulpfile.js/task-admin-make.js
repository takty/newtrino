/**
 *
 * Gulpfile - Tasks for making system
 *
 * @author Takuto Yanagida
 * @version 2021-06-13
 *
 */


'use strict';

const gulp = require('gulp');
const $    = require('gulp-load-plugins')({ pattern: ['gulp-*'] });

const { verStr, makeJsTask, makeCopyTask } = require('./common');

const REP_VERSION = '%VERSION%';
const VERSION     = verStr(' [dev]');


// -----------------------------------------------------------------------------


const copySrc = makeCopyTask([
	'./src/login.php',
	'./src/admin/**/*',
	'./src/admin/**/.htaccess',
	'./src/admin/js/tinymce/langs/*.js',
	'!./src/admin/js/**/*',
	'!./src/admin/sass/**/*',
], './dist', './src');
copySrc.displayName = 'adminMakeCopySrc';

const copyCss = makeCopyTask('./src/admin/sass/*.{css,svg,png}', './dist/admin/css');
copyCss.displayName = 'adminMakeCopyCss';

const minifyJs = makeJsTask([
	'./src/admin/js/**/[^_]*.js',
	'!./src/admin/js/tinymce/langs/*.js'
], './dist', './src');
minifyJs.displayName = 'adminMakeMinifyJs';

const sass = () => gulp.src('./src/admin/sass/style.scss', { sourcemaps: true })
	.pipe($.plumber())
	.pipe($.dartSass({ outputStyle: 'compressed' }))
	.pipe($.autoprefixer({ remove: false }))
	.pipe($.replace(REP_VERSION, VERSION))
	.pipe($.rename({ extname: '.min.css' }))
	.pipe(gulp.dest('./dist/admin/css/', { sourcemaps: '.' }));
sass.displayName = 'adminMakeSass';

exports.taskAdminSrc  = copySrc;
exports.taskAdminCss  = copyCss;
exports.taskAdminJs   = minifyJs;
exports.taskAdminSass = sass;


// -----------------------------------------------------------------------------


const watch = () => {
	const opt = { delay: 1000 };
	gulp.watch([
		'./src/login.php',
		'./src/admin/**/*',
		'./src/admin/**/.htaccess',
		'./src/admin/js/tinymce/langs/*.js',
		'!./src/admin/js/**/*',
		'!./src/admin/sass/**/*',
	], opt, copySrc);
	gulp.watch([
		'./src/admin/sass/*.{css,svg,png}'
	], opt, copyCss);
	gulp.watch([
		'./src/admin/js/**/*.js',
		'!./src/admin/js/tinymce/langs/*.js'
	], opt, minifyJs);
	gulp.watch([
		'./src/admin/sass/style.scss'
	], opt, sass);
};

exports.watchAdmin = watch;

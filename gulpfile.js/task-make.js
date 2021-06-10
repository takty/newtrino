/**
 *
 * Gulpfile - Tasks for making system
 *
 * @author Takuto Yanagida
 * @version 2021-06-10
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
	'./src/**/*',
	'./src/**/.htaccess',
	'!./src/*.js',
	'!./src/**/*.js',
	'!./src/data/**/*',
	'!./src/admin/sass/*',
], './dist', './src');

const copyCss = makeCopyTask('./src/admin/sass/*.{css,svg,png}', './dist/admin/css');

const minifyJs = makeJsTask([
	'./src/[^_]*.js',
	'./src/**/[^_]*.js',
	'!./src/**/*.min.js',
	'!./src/data/*.js',
	'!./src/admin/js/tinymce/langs/*.js'
], './dist', './src');

const copyJs = makeCopyTask(['./src/admin/js/tinymce/langs/*.js'], './dist', './src');

const sass = () => gulp.src(['./src/admin/sass/style.scss'])
	.pipe($.plumber())
	.pipe($.sourcemaps.init())
	.pipe($.dartSass({ outputStyle: 'compressed' }))
	.pipe($.autoprefixer({ remove: false }))
	.pipe($.replace(REP_VERSION, VERSION))
	.pipe($.rename({ extname: '.min.css' }))
	.pipe($.sourcemaps.write('.'))
	.pipe(gulp.dest('./dist/admin/css/'));

exports.taskCopy = gulp.series(copySrc, copyCss);
exports.taskJs   = gulp.series(minifyJs, copyJs);;
exports.taskSass = sass;

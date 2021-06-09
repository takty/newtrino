/**
 *
 * Gulpfile
 *
 * @author Takuto Yanagida
 * @version 2021-06-10
 *
 */


'use strict';

const gulp = require('gulp');
const $ = require('gulp-load-plugins')({ pattern: ['gulp-*'] });

const { makeVersionString, makeTaskJs, makeTaskCopy } = require('./common');

const REP_VERSION = '%VERSION%';
const VERSION = makeVersionString(' [dev]');


// -----------------------------------------------------------------------------


const copySrc = makeTaskCopy([
	'./src/**/*',
	'./src/**/.htaccess',
	'!./src/*.js',
	'!./src/**/*.js',
	'!./src/data/**/*',
	'!./src/admin/sass/*',
], './dist', './src');

const copyCss = makeTaskCopy([
	'./src/admin/sass/*.css',
	'./src/admin/sass/*.svg',
	'./src/admin/sass/*.png',
], './dist/admin/css');

const jsMinify = makeTaskJs([
	'./src/[^_]*.js',
	'./src/**/[^_]*.js',
	'!./src/**/*.min.js',
	'!./src/data/*.js',
	'!./src/admin/js/tinymce/langs/*.js'
], './dist', './src');

const jsRaw = makeTaskCopy(['./src/admin/js/tinymce/langs/*.js'], './dist', './src');

// const js = gulp.series(jsMinify);
const js = gulp.series(jsMinify, jsRaw);

const sass = () => gulp.src(['./src/admin/sass/style.scss'])
	.pipe($.plumber())
	.pipe($.sourcemaps.init())
	.pipe($.dartSass({ outputStyle: 'compressed' }))
	.pipe($.autoprefixer({ remove: false }))
	.pipe($.replace(REP_VERSION, VERSION))
	.pipe($.rename({ extname: '.min.css' }))
	.pipe($.sourcemaps.write('.'))
	.pipe(gulp.dest('./dist/admin/css/'));

exports.copyWatch = gulp.series(copySrc, copyCss);
exports.js        = js;
exports.sass      = sass;

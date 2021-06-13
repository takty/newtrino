/**
 *
 * Common functions for gulp process
 *
 * @author Takuto Yanagida
 * @version 2021-06-13
 *
 */


'use strict';

const path = require('path');

function pkgDir(name) {
	return path.dirname(require.resolve(name + '/package.json'));
}

function verStr(devPostfix = ' [dev]') {
	const getBranchName = require('current-git-branch');

	const bn  = getBranchName();
	const pkg = require('../package.json');
	return 'v' + pkg['version'] + ((bn === 'develop') ? devPostfix : '');
}

exports.pkgDir = pkgDir;
exports.verStr = verStr;


// -----------------------------------------------------------------------------


const gulp = require('gulp');
const $    = require('gulp-load-plugins')({ pattern: ['gulp-*'] });

function makeJsTask(src, dest = './dist', base = null) {
	const jsTask = () => gulp.src(src, { base: base, sourcemaps: true })
		.pipe($.plumber())
		.pipe($.preprocess())
		.pipe($.babel())
		.pipe($.terser())
		.pipe($.rename({ extname: '.min.js' }))
		.pipe($.changed(dest, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(dest, { sourcemaps: '.' }));
	return jsTask;
}

function makeSassTask(src, dest = './dist', base = null) {
	const sassTask = () => gulp.src(src, { base: base, sourcemaps: true })
		.pipe($.plumber())
		.pipe($.dartSass({ outputStyle: SASS_OUTPUT_STYLE }))
		.pipe($.autoprefixer({ remove: false }))
		.pipe($.rename({ extname: '.min.css' }))
		.pipe($.changed(dest, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(dest, { sourcemaps: '.' }));
	return sassTask;
}

function makeCopyTask(src, dest = './dist', base = null) {
	const copyTask = () => gulp.src(src, { base: base })
		.pipe($.plumber())
		.pipe($.ignore.include({ isFile: true }))
		.pipe($.changed(dest, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(dest));
	return copyTask;
}

exports.makeJsTask   = makeJsTask;
exports.makeSassTask = makeSassTask;
exports.makeCopyTask = makeCopyTask;

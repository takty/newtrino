/**
 *
 * Common functions for gulp process
 *
 * @author Takuto Yanagida
 * @version 2021-06-10
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
	return () => gulp.src(src, { base: base })
		.pipe($.plumber())
		.pipe($.include())
		.pipe($.sourcemaps.init())
		.pipe($.babel())
		.pipe($.terser())
		.pipe($.rename({ extname: '.min.js' }))
		.pipe($.sourcemaps.write('.'))
		.pipe($.changed(dest, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(dest));
}

function makeSassTask(src, dest = './dist', base = null) {
	return () => gulp.src(src, { base: base })
		.pipe($.plumber())
		.pipe($.sourcemaps.init())
		.pipe($.dartSass({ outputStyle: SASS_OUTPUT_STYLE }))
		.pipe($.autoprefixer({ remove: false }))
		.pipe($.rename({ extname: '.min.css' }))
		.pipe($.sourcemaps.write('.'))
		.pipe($.changed(dest, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(dest));
}

function makeCopyTask(src, dest = './dist', base = null) {
	return () => gulp.src(src, { base: base })
		.pipe($.plumber())
		.pipe($.ignore.include({ isFile: true }))
		.pipe($.changed(dest, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(dest));
}

exports.makeJsTask   = makeJsTask;
exports.makeSassTask = makeSassTask;
exports.makeCopyTask = makeCopyTask;

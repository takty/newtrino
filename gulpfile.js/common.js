/**
 *
 * Common functions for gulp process
 *
 * @author Takuto Yanagida
 * @version 2021-06-09
 *
 */


'use strict';

const fs = require('fs-extra');
const glob = require('glob');
const path = require('path');

function copySync(from, to) {
	const isToDir = to.endsWith('/');
	const files = glob.sync(from);
	for (let f of files) {
		const tar = isToDir ? path.join(to, path.basename(f)) : to;
		if (fs.statSync(f).isFile()) {
			const fromBuf = fs.readFileSync(f);
			if (fs.existsSync(tar)) {
				const toBuf = fs.readFileSync(tar);
				if (!fromBuf.equals(toBuf)) {
					fs.copySync(f, tar);
				}
			} else {
				fs.copySync(f, tar);
			}
		} else {
			fs.copySync(f, tar);
		}
	}
}

function packageDir(name) {
	return path.dirname(require.resolve(name + '/package.json'));
}

function makeVersionString(devPostfix = ' [dev]') {
	const getBranchName = require('current-git-branch');
	const BRANCH_NAME = getBranchName();
	const config = require('../package.json');
	return 'v' + config['version'] + ((BRANCH_NAME === 'develop') ? devPostfix : '');
}

exports.copySync          = copySync;
exports.packageDir        = packageDir;
exports.makeVersionString = makeVersionString;


// -----------------------------------------------------------------------------


const gulp = require('gulp');
const $ = require('gulp-load-plugins')({ pattern: ['gulp-*'] });

function makeTaskJs(src, dest = './dist', base = null) {
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

function makeTaskSass(src, dest = './dist', base = null) {
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

function makeTaskCopy(src, dest = './dist', base = null) {
	return () => gulp.src(src, { base: base })
		.pipe($.plumber())
		.pipe($.ignore.include({ isFile: true }))
		.pipe($.changed(dest, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(dest));
}

exports.makeTaskJs   = makeTaskJs;
exports.makeTaskSass = makeTaskSass;
exports.makeTaskCopy = makeTaskCopy;

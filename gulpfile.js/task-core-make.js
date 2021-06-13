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

const { makeJsTask, makeCopyTask } = require('./common');


// -----------------------------------------------------------------------------


const copySrc = makeCopyTask([
	'./src/index.php',
	'./src/core/**/*',
	'./src/core/**/.htaccess',
], './dist', './src');
copySrc.displayName = 'coreMakeCopySrc';

const minifyJs = makeJsTask([
	'./src/index.js',
], './dist', './src');
minifyJs.displayName = 'coreMakeMinifyJs';

exports.taskCoreSrc = copySrc;
exports.taskCoreJs  = minifyJs;


// -----------------------------------------------------------------------------


const watch = () => {
	const opt = { delay: 1000 };
	gulp.watch([
		'./src/index.php',
		'./src/core/**/*',
		'./src/core/**/.htaccess',
	], opt, copySrc);
	gulp.watch([
		'./src/index.js',
	], opt, minifyJs);
};

exports.watchCore = watch;

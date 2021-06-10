/**
 *
 * Gulpfile - Tasks for building sample
 *
 * @author Takuto Yanagida
 * @version 2021-06-10
 *
 */


'use strict';

const gulp = require('gulp');

const { makeJsTask, makeCopyTask } = require('./common');


// -----------------------------------------------------------------------------


const copyNt = makeCopyTask([
	'./dist/**/*',
	'./dist/**/.htaccess'
], './sample/nt');

const copyData = makeCopyTask([
	'./src/data/**/*',
	'./src/data/**/.htaccess',
	'!./src/data/*.js'
], './sample/nt', './src');

const minifyDataJs = makeJsTask('./src/data/*.js', './sample/nt/data');

exports.taskSample = gulp.series(copyNt, copyData, minifyDataJs);

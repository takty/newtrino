/**
 *
 * Gulpfile - Tasks for building sample
 *
 * @author Takuto Yanagida
 * @version 2021-06-13
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
copyNt.displayName = 'sampleCopyNt';

const copyData = makeCopyTask([
	'./src/data/**/*',
	'./src/data/**/.htaccess',
	'!./src/data/*.js'
], './sample/nt', './src');
copyData.displayName = 'sampleCopyData';

const minifyDataJs = makeJsTask('./src/data/*.js', './sample/nt/data');
minifyDataJs.displayName = 'sampleMinifyDataJs';

exports.taskSample = gulp.series(copyNt, copyData, minifyDataJs);

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

exports.taskSampleNt   = copyNt;
exports.taskSampleData = copyData;
exports.taskSampleJs   = minifyDataJs;


// -----------------------------------------------------------------------------


const watch = () => {
	const opt = { delay: 1000 };
	gulp.watch([
		'./dist/**/*',
		'./dist/**/.htaccess'
	], opt, copyNt);
	gulp.watch([
		'./src/data/**/*',
		'./src/data/**/.htaccess',
		'!./src/data/*.js'
	], opt, copyData);
	gulp.watch([
		'./src/data/*.js'
	], opt, minifyDataJs);
};

exports.watchSample = watch;

/**
 *
 * Gulpfile - Sample
 *
 * @author Takuto Yanagida
 * @version 2021-06-10
 *
 */


'use strict';

const gulp = require('gulp');

const { makeTaskJs, makeTaskCopy } = require('./common');


// -----------------------------------------------------------------------------


const sampleSystem = makeTaskCopy([
	'./dist/**/*',
	'./dist/**/.htaccess'
], './sample/nt');

const sampleData = makeTaskCopy([
	'./src/data/**/*',
	'./src/data/**/.htaccess',
	'!./src/data/*.js'
], './sample/nt', './src');

const sampleDataJs = makeTaskJs([
	'./src/data/*.js'
], './sample/nt/data');

exports.task_sample = gulp.series(sampleSystem, sampleData, sampleDataJs);

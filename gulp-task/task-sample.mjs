/**
 * Gulpfile - Tasks for building sample
 *
 * @author Takuto Yanagida
 * @version 2022-08-19
 */

import gulp from 'gulp';

import { makeCopyTask } from './_task-copy.mjs';
import { makeJsTask } from './_task-js.mjs';


// -----------------------------------------------------------------------------


export const taskSampleNt = makeCopyTask([
	'./dist/**/*',
	'./dist/**/.htaccess'
], './sample/nt');
taskSampleNt.displayName = 'sampleCopyNt';

export const taskSampleData = makeCopyTask([
	'./src/data/**/*',
	'./src/data/**/.htaccess',
	'!./src/data/*.js'
], './sample/nt', './src');
taskSampleData.displayName = 'sampleCopyData';

export const taskSampleJs = makeJsTask('./src/data/*.js', './sample/nt/data');
taskSampleJs.displayName = 'sampleMinifyDataJs';


// -----------------------------------------------------------------------------


export const watchSample = () => {
	const opt = { delay: 1000 };
	gulp.watch([
		'./dist/**/*',
		'./dist/**/.htaccess'
	], opt, taskSampleNt);
	gulp.watch([
		'./src/data/**/*',
		'./src/data/**/.htaccess',
		'!./src/data/*.js'
	], opt, taskSampleData);
	gulp.watch([
		'./src/data/*.js'
	], opt, taskSampleJs);
};

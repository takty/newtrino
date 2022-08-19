/**
 * Gulpfile - Tasks for building sample
 *
 * @author Takuto Yanagida
 * @version 2022-08-18
 */

import gulp from 'gulp';

import { makeCopyTask } from './_task-copy.mjs';
import { makeJsTask } from './_task-js.mjs';


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

export const taskSampleNt   = copyNt;
export const taskSampleData = copyData;
export const taskSampleJs   = minifyDataJs;


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

export const watchSample = watch;

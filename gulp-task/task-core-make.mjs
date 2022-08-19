/**
 * Gulpfile - Tasks for making system
 *
 * @author Takuto Yanagida
 * @version 2022-08-19
 */

import gulp from 'gulp';

import { makeCopyTask } from './_task-copy.mjs';
import { makeJsTask } from './_task-js.mjs';


// -----------------------------------------------------------------------------


export const taskCoreSrc = makeCopyTask([
	'./src/index.php',
	'./src/core/**/*',
	'./src/core/**/.htaccess',
], './dist', './src');
taskCoreSrc.displayName = 'coreMakeCopySrc';

export const taskCoreJs = makeJsTask([
	'./src/index.js',
], './dist', './src');
taskCoreJs.displayName = 'coreMakeMinifyJs';


// -----------------------------------------------------------------------------


export const watchCore = () => {
	const opt = { delay: 1000 };
	gulp.watch([
		'./src/index.php',
		'./src/core/**/*',
		'./src/core/**/.htaccess',
	], opt, taskCoreSrc);
	gulp.watch([
		'./src/index.js',
	], opt, taskCoreJs);
};

/**
 * Gulpfile - Tasks for making system
 *
 * @author Takuto Yanagida
 * @version 2022-08-18
 */

import gulp from 'gulp';

import { makeCopyTask } from './_task-copy.mjs';
import { makeJsTask } from './_task-js.mjs';


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

export const taskCoreSrc = copySrc;
export const taskCoreJs  = minifyJs;


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

export const watchCore = watch;

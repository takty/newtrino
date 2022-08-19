/**
 * Gulpfile - Tasks for copying libraries
 *
 * @author Takuto Yanagida
 * @version 2022-08-19
 */

import gulp from 'gulp';

import { makeCopyTask } from './_task-copy.mjs';

const DIST_BASE  = './dist/';


// -----------------------------------------------------------------------------


const makeTaskCopyMustache = () => {
	// Dest directory must be capitalized
	const f = makeCopyTask('./vendor/mustache/mustache/src/Mustache/**/*', DIST_BASE + 'core/lib/Mustache/', './vendor/mustache/mustache/src/Mustache/');
	f.displayName = 'coreLibCopyMustache';
	return f;
};

export const taskCoreLib = gulp.parallel(
	makeTaskCopyMustache()
);

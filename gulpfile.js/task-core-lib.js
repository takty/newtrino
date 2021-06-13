/**
 *
 * Gulpfile - Tasks for copying libraries
 *
 * @author Takuto Yanagida
 * @version 2021-06-13
 *
 */


'use strict';

const gulp = require('gulp');

const { makeCopyTask } = require('./common');

const DIST_BASE  = './dist/';


// -----------------------------------------------------------------------------


const makeTaskCopyMustache = () => {
	// Dest directory must be capitalized
	const f = makeCopyTask('./vendor/mustache/mustache/src/Mustache/**/*', DIST_BASE + 'core/lib/Mustache/', './vendor/mustache/mustache/src/Mustache/');
	f.displayName = 'coreLibCopyMustache';
	return f;
};

exports.taskCoreLib = gulp.parallel(
	makeTaskCopyMustache()
);

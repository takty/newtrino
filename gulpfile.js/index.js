/**
 *
 * Gulpfile
 *
 * @author Takuto Yanagida
 * @version 2021-06-13
 *
 */


'use strict';

const gulp = require('gulp');


// -----------------------------------------------------------------------------


const { taskCopyLib } = require('./task-copy-lib');

const { taskSrc, taskCss, taskJs, taskSass } = require('./task-make');

const { taskSample } = require('./task-sample');

const taskBuild = gulp.parallel(gulp.series(taskSrc, taskCss, taskCopyLib), taskJs, taskSass);


// -----------------------------------------------------------------------------


const watch = (done) => {
	const opt = { delay: 1000 };
	gulp.watch('src/**/*.{html,svg,png,php}', opt, gulp.series(taskSrc, taskSample));
	gulp.watch('src/**/*.css', opt, gulp.series(taskCss, taskSample));
	gulp.watch('src/**/*.js', opt, gulp.series(taskJs, taskSample));
	gulp.watch('src/**/*.scss', opt, gulp.series(taskSass, taskSample));
	done();
};

exports.default = gulp.series(taskBuild, taskSample, watch);
exports.build   = taskBuild;

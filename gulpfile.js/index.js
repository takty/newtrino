/**
 *
 * Gulpfile
 *
 * @author Takuto Yanagida
 * @version 2021-06-10
 *
 */


'use strict';

const gulp = require('gulp');


// -----------------------------------------------------------------------------


const { taskCopyLib } = require('./task-copy-lib');

const { taskCopy, taskJs, taskSass } = require('./task-make');

const { taskSample } = require('./task-sample');

const taskBuild = gulp.parallel(gulp.series(taskCopy, taskCopyLib), taskJs, taskSass);


// -----------------------------------------------------------------------------


const watch = (done) => {
	gulp.watch('src/**/*.{html,css,svg,png,php}', gulp.series(taskCopy, taskSample));
	gulp.watch('src/**/*.js', gulp.series(taskJs, taskSample));
	gulp.watch('src/**/*.scss', gulp.series(taskSass, taskSample));
	done();
};

exports.default = gulp.series(taskBuild, taskSample, watch);
exports.build   = taskBuild;

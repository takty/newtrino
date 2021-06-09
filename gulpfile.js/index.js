/**
 *
 * Gulpfile
 *
 * @author Takuto Yanagida
 * @version 2021-06-09
 *
 */


'use strict';

const gulp = require('gulp');


// -----------------------------------------------------------------------------


const { task_copy_lib } = require('./task-copy-lib');

const { copyWatch, js, sass } = require('./task-make');

const build = gulp.parallel(gulp.series(copyWatch, task_copy_lib), js, sass);

const { task_sample } = require('./task-sample');


// -----------------------------------------------------------------------------


const watch = (done) => {
	gulp.watch(['src/**/*.html', 'src/**/*.php'], gulp.series(copyWatch, task_sample));
	gulp.watch('src/**/*.js', gulp.series(js, task_sample));
	gulp.watch('src/**/*.scss', gulp.series(sass, task_sample));
	done();
};

exports.default = gulp.series(build, task_sample, watch);

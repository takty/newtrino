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

const { copyWatch, js, sass } = require('./task-make');

const build = gulp.parallel(gulp.series(copyWatch, taskCopyLib), js, sass);

const { taskSample } = require('./task-sample');


// -----------------------------------------------------------------------------


const watch = (done) => {
	gulp.watch(['src/**/*.html', 'src/**/*.php'], gulp.series(copyWatch, taskSample));
	gulp.watch('src/**/*.js', gulp.series(js, taskSample));
	gulp.watch('src/**/*.scss', gulp.series(sass, taskSample));
	done();
};

exports.default = gulp.series(build, taskSample, watch);

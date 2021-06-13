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


const { taskAdminLib } = require('./task-admin-lib');
const { taskAdminSrc, taskAdminCss, taskAdminJs, taskAdminSass, watchAdmin } = require('./task-admin-make');

exports.admin = gulp.series(taskAdminLib, taskAdminSrc, taskAdminCss, taskAdminJs, taskAdminSass);

const { taskCoreLib } = require('./task-core-lib');
const { taskCoreSrc, taskCoreJs, watchCore } = require('./task-core-make');

exports.core = gulp.series(taskCoreLib, taskCoreSrc, taskCoreJs);

const { taskSampleNt, taskSampleData, taskSampleJs, watchSample } = require('./task-sample');

exports.sample = gulp.series(taskSampleNt, taskSampleData, taskSampleJs );

exports.build = gulp.parallel(exports.admin, exports.core);


// -----------------------------------------------------------------------------


const watch = (done) => {
	watchAdmin();
	watchCore();
	watchSample();
	done();
};

exports.default = gulp.series(exports.build, exports.sample, watch);

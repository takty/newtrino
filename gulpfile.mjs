/**
 * Gulpfile
 *
 * @author Takuto Yanagida
 * @version 2022-12-16
 */

import gulp from 'gulp';

import { taskAdminLib } from './gulp-task/task-admin-lib.mjs';
import { taskAdminSrc, taskAdminCss, taskAdminJs, taskAdminSass, watchAdmin } from './gulp-task/task-admin-make.mjs';

export const admin = gulp.series(taskAdminLib, taskAdminSrc, taskAdminCss, taskAdminJs, taskAdminSass);

import { taskCoreLib } from './gulp-task/task-core-lib.mjs';
import { taskCoreSrc, taskCoreJs, watchCore } from './gulp-task/task-core-make.mjs';

export const core = gulp.series(taskCoreLib, taskCoreSrc, taskCoreJs);

import { taskSampleNt, taskSampleData, taskSampleJs, watchSample } from './gulp-task/task-sample.mjs';

export const sample = gulp.series(taskSampleNt, taskSampleData, taskSampleJs);

export const build = gulp.parallel(admin, core);


// -----------------------------------------------------------------------------


const watch = () => {
	watchAdmin();
	watchCore();
	watchSample();
};

export default gulp.series(build, sample, watch);

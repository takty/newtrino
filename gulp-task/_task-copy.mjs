/**
 * Function for gulp (Copy)
 *
 * @author Takuto Yanagida
 * @version 2023-11-08
 */

import gulp from 'gulp';
import plumber from 'gulp-plumber';
import ignore from 'gulp-ignore';
import changed, { compareContents } from 'gulp-changed';

export function makeCopyTask(src, dest = './dist', base = null) {
	const copyTask = () => gulp.src(src, { base: base })
		.pipe(plumber())
		.pipe(ignore.include({ isFile: true }))
		.pipe(changed(dest, { hasChanged: compareContents }))
		.pipe(gulp.dest(dest));
	return copyTask;
}

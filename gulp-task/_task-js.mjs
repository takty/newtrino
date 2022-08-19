/**
 * Function for gulp (JS)
 *
 * @author Takuto Yanagida
 * @version 2022-08-19
 */

import gulp from 'gulp';
import plumber from 'gulp-plumber';
import preprocess from 'gulp-preprocess';
import babel from 'gulp-babel';
import terser from 'gulp-terser';
import rename from 'gulp-rename';
import changed from 'gulp-changed';

export function makeJsTask(src, dest = './dist', base = null) {
	const jsTask = () => gulp.src(src, { base: base, sourcemaps: true })
		.pipe(plumber())
		.pipe(preprocess())
		.pipe(babel())
		.pipe(terser())
		.pipe(rename({ extname: '.min.js' }))
		.pipe(changed(dest, { hasChanged: changed.compareContents }))
		.pipe(gulp.dest(dest, { sourcemaps: '.' }));
	return jsTask;
}

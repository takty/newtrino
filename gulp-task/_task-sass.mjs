/**
 * Function for gulp (SASS)
 *
 * @author Takuto Yanagida
 * @version 2022-08-19
 */

const SASS_OUTPUT_STYLE = 'compressed';  // 'expanded' or 'compressed'

import gulp from 'gulp';
import plumber from 'gulp-plumber';
import autoprefixer from 'gulp-autoprefixer';
import rename from 'gulp-rename';
import changed from 'gulp-changed';

import dartSass from 'sass';
import gulpSass from 'gulp-sass';
const sass = gulpSass(dartSass);

const plumberOptions = {
	errorHandler: function (err) {
		console.log(err.messageFormatted ?? err);
		this.emit('end');
	}
};

export function makeSassTask(src, dest = './dist', base = null, addPostfix = true) {
	const sassTask = () => gulp.src(src, { base: base, sourcemaps: true })
		.pipe(plumber(plumberOptions))
		.pipe(sass.sync({ outputStyle: SASS_OUTPUT_STYLE }))
		.pipe(autoprefixer({ remove: false }))
		.pipe(rename({ extname: addPostfix ? '.min.css' : '.css' }))
		.pipe(changed(dest, { hasChanged: changed.compareContents }))
		.pipe(gulp.dest(dest, { sourcemaps: '.' }));
	return sassTask;
}

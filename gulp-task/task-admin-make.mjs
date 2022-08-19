/**
 * Gulpfile - Tasks for making system
 *
 * @author Takuto Yanagida
 * @version 2022-08-19
 */

import gulp from 'gulp';
import plumber from 'gulp-plumber';
import replace from 'gulp-replace';

import { verStr } from './_common.mjs';
import { makeCopyTask } from './_task-copy.mjs';
import { makeJsTask } from './_task-js.mjs';
import { makeSassTask } from './_task-sass.mjs';

const REP_VERSION = '%VERSION%';
const VERSION     = verStr(' [dev]');


// -----------------------------------------------------------------------------


export const taskAdminSrc = makeCopyTask([
	'./src/login.php',
	'./src/admin/**/*',
	'./src/admin/**/.htaccess',
	'./src/admin/js/tinymce/langs/*.js',
	'!./src/admin/js/**/*',
	'!./src/admin/sass/**/*',
], './dist', './src');
taskAdminSrc.displayName = 'adminMakeCopySrc';

export const taskAdminCss = makeCopyTask('./src/admin/sass/*.{css,svg,png}', './dist/admin/css');
taskAdminCss.displayName = 'adminMakeCopyCss';

export const taskAdminJs = makeJsTask([
	'./src/admin/js/**/[^_]*.js',
	'!./src/admin/js/tinymce/langs/*.js'
], './dist', './src');
taskAdminJs.displayName = 'adminMakeMinifyJs';

const sass = makeSassTask('./src/admin/sass/style.scss', './dist/admin/css/');
sass.displayName = 'adminMakeSass';

const replaceVer = () => gulp.src('./dist/admin/css/style.min.css')
	.pipe(plumber())
	.pipe(replace(REP_VERSION, VERSION))
	.pipe(gulp.dest('./dist/admin/css/'));

export const taskAdminSass = gulp.series(sass, replaceVer);


// -----------------------------------------------------------------------------


export const watchAdmin = () => {
	const opt = { delay: 1000 };
	gulp.watch([
		'./src/login.php',
		'./src/admin/**/*',
		'./src/admin/**/.htaccess',
		'./src/admin/js/tinymce/langs/*.js',
		'!./src/admin/js/**/*',
		'!./src/admin/sass/**/*',
	], opt, taskAdminSrc);
	gulp.watch([
		'./src/admin/sass/*.{css,svg,png}'
	], opt, taskAdminCss);
	gulp.watch([
		'./src/admin/js/**/*.js',
		'!./src/admin/js/tinymce/langs/*.js'
	], opt, taskAdminJs);
	gulp.watch([
		'./src/admin/sass/*.scss'
	], opt, taskAdminSass);
};

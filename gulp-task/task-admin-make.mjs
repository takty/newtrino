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


const copySrc = makeCopyTask([
	'./src/login.php',
	'./src/admin/**/*',
	'./src/admin/**/.htaccess',
	'./src/admin/js/tinymce/langs/*.js',
	'!./src/admin/js/**/*',
	'!./src/admin/sass/**/*',
], './dist', './src');
copySrc.displayName = 'adminMakeCopySrc';

const copyCss = makeCopyTask('./src/admin/sass/*.{css,svg,png}', './dist/admin/css');
copyCss.displayName = 'adminMakeCopyCss';

const minifyJs = makeJsTask([
	'./src/admin/js/**/[^_]*.js',
	'!./src/admin/js/tinymce/langs/*.js'
], './dist', './src');
minifyJs.displayName = 'adminMakeMinifyJs';

const sass = makeSassTask('./src/admin/sass/style.scss', './dist/admin/css/');
sass.displayName = 'adminMakeSass';

const replaceVer = () => gulp.src('./dist/admin/css/style.min.css')
	.pipe(plumber())
	.pipe(replace(REP_VERSION, VERSION))
	.pipe(gulp.dest('./dist/admin/css/'));

export const taskAdminSrc  = copySrc;
export const taskAdminCss  = copyCss;
export const taskAdminJs   = minifyJs;
export const taskAdminSass = gulp.series(sass, replaceVer);


// -----------------------------------------------------------------------------


const watch = () => {
	const opt = { delay: 1000 };
	gulp.watch([
		'./src/login.php',
		'./src/admin/**/*',
		'./src/admin/**/.htaccess',
		'./src/admin/js/tinymce/langs/*.js',
		'!./src/admin/js/**/*',
		'!./src/admin/sass/**/*',
	], opt, copySrc);
	gulp.watch([
		'./src/admin/sass/*.{css,svg,png}'
	], opt, copyCss);
	gulp.watch([
		'./src/admin/js/**/*.js',
		'!./src/admin/js/tinymce/langs/*.js'
	], opt, minifyJs);
	gulp.watch([
		'./src/admin/sass/*.scss'
	], opt, sass);
};

export const watchAdmin = watch;

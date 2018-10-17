'use strict';

const fs = require('fs-extra');
const glob = require('glob');
const path = require('path');
const gulp = require('gulp');
const $ = require('gulp-load-plugins')({pattern:['gulp-*']});

function copySync(from, to) {
	const isToDir = to.endsWith('/');
	const files = glob.sync(from);
	for (let f of files) {
		if (isToDir) {
			const fn = path.basename(f);
			fs.copySync(f, path.join(to, fn));
		} else {
			fs.copySync(f, to);
		}
	}
}

const PATH_DIST = './dist/topic/private/';

gulp.task('copy-jssha', (done) => {
	copySync('./node_modules/jssha/src/sha256.js', PATH_DIST + 'js/jssha/');
	done();
});

gulp.task('copy-flatpickr', (done) => {
	copySync('./node_modules/flatpickr/dist/flatpickr.min.js', PATH_DIST + 'js/flatpickr/');
	copySync('./node_modules/flatpickr/dist/flatpickr.min.css', PATH_DIST + 'css/flatpickr/');
	copySync('./node_modules/flatpickr/dist/l10n/ja.js', PATH_DIST + 'js/flatpickr/');
	done();
});

gulp.task('copy-tinymce', (done) => {
	copySync('./node_modules/tinymce/tinymce.min.js', PATH_DIST + 'js/tinymce/');
	copySync('./node_modules/tinymce/plugins/**/*', PATH_DIST + 'js/tinymce/plugins/');
	copySync('./node_modules/tinymce/skins/**/*', PATH_DIST + 'js/tinymce/skins/');
	copySync('./node_modules/tinymce/themes/**/*', PATH_DIST + 'js/tinymce/themes/');
	copySync('./node_modules/tinymce-i18n/langs/ja.js', PATH_DIST + 'js/tinymce/langs/');
	done();
});

gulp.task('copy-stile-sass', (done) => {
	copySync('./node_modules/stile/dist/sass/*', './src/topic/private/lib/stile/sass/');
	done();
});

gulp.task('copy-lib', gulp.parallel(
	'copy-jssha',
	'copy-flatpickr',
	'copy-tinymce',
	'copy-stile-sass',
));

gulp.task('copy-src', (done) => {
	copySync('./src', './dist');
	fs.removeSync('./dist/topic/post/*');
	fs.removeSync('./dist/topic/private/sass');
	fs.removeSync('./dist/topic/private/lib');
	for (let f of glob.sync('./dist/topic/private/js/*.js')) fs.removeSync(f);
	done();
});

gulp.task('copy-res', (done) => {
	copySync('./src/topic/private/sass/*.svg', PATH_DIST + 'css');
	done();
});

gulp.task('copy', gulp.series('copy-src', 'copy-lib', 'copy-res'));

gulp.task('js', () => {
	return gulp.src(['src/topic/private/js/**/*.js'])
		.pipe($.plumber())
		.pipe($.uglify())
		.pipe($.rename({extname: '.min.js'}))
		.pipe(gulp.dest('./dist/topic/private/js'));
});

gulp.task('sass', () => {
	return gulp.src(['src/topic/private/sass/style.scss', 'src/topic/private/sass/editor-style.scss'])
		.pipe($.plumber())
		.pipe($.sass({outputStyle: 'compressed'}))
		.pipe($.autoprefixer({browsers: ['ie >= 11'], remove: false}))
		.pipe($.rename({extname: '.min.css'}))
		.pipe(gulp.dest('./dist/topic/private/css/'));
});

gulp.task('default', gulp.series('copy', 'js', 'sass'));

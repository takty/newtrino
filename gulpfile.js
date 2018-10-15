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

const PATH_DIST = './dist/topic/private/asset/';

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

gulp.task('copy-lib', gulp.parallel(
	'copy-jssha',
	'copy-flatpickr',
	'copy-tinymce',
));

gulp.task('copy-src', (done) => {
	copySync('./src', './dist');
	fs.removeSync('./dist/topics/post/*');
	done();
});

gulp.task('copy-res', (done) => {
	copySync('./src/topic/private/asset/sass/*.svg', PATH_DIST + 'css');
	fs.removeSync('./dist/topics/post/*');
	done();
});

gulp.task('copy', gulp.series('copy-src', 'copy-lib', 'copy-res'));

gulp.task('js-private', () => {
	return gulp.src(['src/topic/private/asset/js/**/*.js'])
		.pipe($.plumber())
		.pipe($.uglify())
		.pipe($.rename({extname: '.min.js'}))
		.pipe(gulp.dest('./dist/topic/private/asset/js'));
});

gulp.task('sass-private', () => {
	return gulp.src(['src/topic/private/asset/sass/style.scss', 'src/topic/private/asset/sass/editor-style.scss'])
		.pipe($.plumber())
		.pipe($.sass({outputStyle: 'compressed'}))
		.pipe($.autoprefixer({browsers: ['ie >= 11'], remove: false}))
		.pipe(gulp.dest('./dist/topic/private/asset/css/'));
});

// gulp.task('others', function() {
// 	gulp.src(['src/img/**/*'])                    .pipe(gulp.dest('dist/img'));
// 	gulp.src(['src/part/**/*'])                   .pipe(gulp.dest('dist/part'));
// 	gulp.src(['src/*.php'])                       .pipe(gulp.dest('dist'));
// 	gulp.src(['src/topic/*.php'])                 .pipe(gulp.dest('dist/topic'));
// 	gulp.src(['src/topic/private/*.php'])         .pipe(gulp.dest('dist/topic/private'));
// 	gulp.src(['src/topic/private/data/*'])        .pipe(gulp.dest('dist/topic/private/data'));
// 	gulp.src(['src/topic/private/asset/php/**/*']).pipe(gulp.dest('dist/topic/private/asset/php'));
// })

gulp.task('default', gulp.series('copy', 'js-private', 'sass-private'));

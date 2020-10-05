/**
 *
 * Gulpfile
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-10-05
 *
 */


'use strict';

const fs   = require('fs-extra');
const glob = require('glob');
const path = require('path');
const gulp = require('gulp');
const $    = require('gulp-load-plugins')({ pattern: ['gulp-*'] });

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

function packageDir(name) {
	return path.dirname(require.resolve(name + '/package.json'));
}

const SRC_ADMIN = './src/admin/';
const DIST_BASE = './dist/';
const DIST_ADMIN = DIST_BASE + 'admin/';

const config = require('./package.json');

const REP_VERSION = '%VERSION%';
const VERSION     = 'v' + config['version'];


// -----------------------------------------------------------------------------

gulp.task('copy-jssha', (done) => {
	const dir = packageDir('jssha');
	copySync(dir + '/sha256.js', DIST_ADMIN + 'js/jssha/');
	done();
});

gulp.task('copy-flatpickr', (done) => {
	const dir = packageDir('flatpickr');
	copySync(dir + '/flatpickr.min.js', DIST_ADMIN + 'js/flatpickr/');
	copySync(dir + '/flatpickr.min.css', DIST_ADMIN + 'css/flatpickr/');
	copySync(dir + '/l10n/ja.js', DIST_ADMIN + 'js/flatpickr/');
	done();
});

gulp.task('copy-tinymce', (done) => {
	const dir = packageDir('tinymce');
	copySync(dir + '/tinymce.min.js', DIST_ADMIN + 'js/tinymce/');
	copySync(dir + '/plugins/*', DIST_ADMIN + 'js/tinymce/plugins/');
	copySync(dir + '/skins/lightgray/*', DIST_ADMIN + 'js/tinymce/skins/lightgray/');
	copySync(dir + '/themes/modern/*', DIST_ADMIN + 'js/tinymce/themes/modern/');
	const dir_i18n = packageDir('tinymce-i18n');
	copySync(dir_i18n + '/langs/ja.js', DIST_ADMIN + 'js/tinymce/langs/');
	const ups = [  // Removed plugins
		'autoresize',	'autosave',		'bbcode',	'codesample',
		'emoticons',	'fullpage',		'help',		'importcss',
		'legacyoutput',	'pagebreak',	'preview',	'save',
		'tabfocus', 	'toc',			'template',	'wordcount'
	];
	for (let up of ups) fs.removeSync(DIST_ADMIN + 'js/tinymce/plugins/' + up);
	done();
});

gulp.task('copy-nacss-reset', (done) => {
	const dir = packageDir('nacss-reset');
	copySync(dir + '/reset.min.css', DIST_ADMIN + 'css/');
	done();
});

gulp.task('copy-mustache', (done) => {
	// Dest directory must be capitalized
	copySync('./vendor/mustache/mustache/src/Mustache/*', DIST_BASE + 'core/lib/Mustache/');
	done();
});

gulp.task('copy-lib', gulp.parallel(
	'copy-jssha',
	'copy-flatpickr',
	'copy-tinymce',
	'copy-nacss-reset',
	'copy-mustache',
));


// -----------------------------------------------------------------------------


gulp.task('copy-src', () => gulp.src([
		'src/**/*',
		'src/**/.htaccess',
		'!src/*.js',
		'!src/**/*.js',
		'!src/data/**/*',
		'!src/admin/sass/*',
	], { base: 'src' })
	.pipe($.plumber())
	.pipe($.ignore.include({ isFile: true }))
	.pipe($.changed('dist', { hasChanged: $.changed.compareContents }))
	.pipe(gulp.dest('dist'))
);

gulp.task('copy-css', (done) => {
	copySync(SRC_ADMIN + 'sass/*.css', DIST_ADMIN + 'css/');
	copySync(SRC_ADMIN + 'sass/*.svg', DIST_ADMIN + 'css/');
	copySync(SRC_ADMIN + 'sass/*.png', DIST_ADMIN + 'css/');
	fs.removeSync(DIST_ADMIN + 'sass');
	done();
});

gulp.task('copy', gulp.series('copy-src', 'copy-css', 'copy-lib'));

gulp.task('js', () => gulp.src(['src/*.js', 'src/**/*.js', '!src/**/*.min.js', '!src/data/*.js'])
	.pipe($.plumber())
	.pipe($.sourcemaps.init())
	.pipe($.babel({
		presets: [['@babel/preset-env']],
	}))
	.pipe($.terser())
	.pipe($.rename({ extname: '.min.js' }))
	.pipe($.sourcemaps.write('.'))
	.pipe(gulp.dest('dist'))
);

gulp.task('sass', () => {
	return gulp.src([SRC_ADMIN + 'sass/style.scss'])
		.pipe($.plumber())
		.pipe($.sourcemaps.init())
		.pipe($.dartSass({ outputStyle: 'compressed' }))
		.pipe($.autoprefixer({ remove: false }))
		.pipe($.replace(REP_VERSION, VERSION))
		.pipe($.rename({ extname: '.min.css' }))
		.pipe($.sourcemaps.write('.'))
		.pipe(gulp.dest(DIST_ADMIN + 'css/'));
});


// -----------------------------------------------------------------------------


gulp.task('sample-system', () => {
	return gulp.src(['dist/**/*', 'dist/**/.htaccess'])
		.pipe($.plumber())
		.pipe($.ignore.include({ isFile: true }))
		.pipe($.changed('sample/nt', { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest('sample/nt'));
});

gulp.task('sample-data', () => {
	return gulp.src(['src/data/*', 'src/data/.htaccess', '!src/data/*.js'], { base: 'src' })
		.pipe($.plumber())
		.pipe($.ignore.include({ isFile: true }))
		.pipe($.changed('sample/nt', { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest('sample/nt'));
});

gulp.task('sample-data-js', () => gulp.src(['src/data/*.js'])
	.pipe($.plumber())
	.pipe($.sourcemaps.init())
	.pipe($.babel({
		presets: [['@babel/preset-env']],
	}))
	.pipe($.terser())
	.pipe($.rename({ extname: '.min.js' }))
	.pipe($.sourcemaps.write('.'))
	.pipe($.changed('sample/nt/data', { hasChanged: $.changed.compareContents }))
	.pipe(gulp.dest('sample/nt/data'))
);

gulp.task('sample', gulp.series('sample-system', 'sample-data', 'sample-data-js'));

gulp.task('watch', () => {
	gulp.watch(['src/**/*.html', 'src/**/*.php'], gulp.series('copy', 'sample'));
	gulp.watch('src/**/*.js', gulp.series('js', 'sample'));
	gulp.watch('src/**/*.scss', gulp.series('sass', 'sample'));
});

gulp.task('build', gulp.parallel('copy', 'js', 'sass'));

gulp.task('default', gulp.series('copy', 'js', 'sass', 'sample', 'watch'));

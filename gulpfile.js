/**
 *
 * Gulpfile
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2021-06-04
 *
 */


'use strict';

const getBranchName = require('current-git-branch');
const BRANCH_NAME = getBranchName();

const fs   = require('fs-extra');
const glob = require('glob');
const path = require('path');
const gulp = require('gulp');
const $    = require('gulp-load-plugins')({ pattern: ['gulp-*'] });

function copySync(from, to) {
	const isToDir = to.endsWith('/');
	const files = glob.sync(from);
	for (let f of files) {
		const tar = isToDir ? path.join(to, path.basename(f)) : to;
		if (fs.statSync(f).isFile()) {
			const fromBuf = fs.readFileSync(f);
			if (fs.existsSync(tar)) {
				const toBuf = fs.readFileSync(tar);
				if (!fromBuf.equals(toBuf)) {
					fs.copySync(f, tar);
				}
			} else {
				fs.copySync(f, tar);
			}
		} else {
			fs.copySync(f, tar);
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
const VERSION     = 'v' + config['version'] + ((BRANCH_NAME === 'develop') ? ' [dev]' : '');


// -----------------------------------------------------------------------------


gulp.task('copy-jssha', (done) => {
	const dir = packageDir('jssha');
	copySync(dir + '/dist/sha256.js', DIST_ADMIN + 'js/jssha/');
	done();
});

gulp.task('copy-flatpickr', (done) => {
	const dir = packageDir('flatpickr');
	copySync(dir + '/dist/flatpickr.min.js', DIST_ADMIN + 'js/flatpickr/');
	copySync(dir + '/dist/flatpickr.min.css', DIST_ADMIN + 'css/flatpickr/');
	copySync(dir + '/dist/l10n/ja.js', DIST_ADMIN + 'js/flatpickr/');
	done();
});

gulp.task('copy-tinymce', (done) => {
	const dir = packageDir('tinymce');
	copySync(dir + '/tinymce.min.js', DIST_ADMIN + 'js/tinymce/');
	copySync(dir + '/plugins/*', DIST_ADMIN + 'js/tinymce/plugins/');
	copySync(dir + '/skins/*', DIST_ADMIN + 'js/tinymce/skins/');
	copySync(dir + '/icons/*', DIST_ADMIN + 'js/tinymce/icons/');
	copySync(dir + '/themes/silver/*', DIST_ADMIN + 'js/tinymce/themes/silver/');
	const dir_i18n = packageDir('tinymce-i18n');
	copySync(dir_i18n + '/langs5/ja.js', DIST_ADMIN + 'js/tinymce/langs/');
	const ups = [  // Unused plugins
		'autoresize',	'autosave',		'bbcode',		'codesample',	'colorpicker',
		'contextmenu',	'emoticons',	'fullpage',		'fullscreen',	'help',
		'importcss', 	'legacyoutput',	'pagebreak',	'preview',		'save',
		'tabfocus',		'textcolor',	'toc',			'template',		'wordcount'
	];
	for (let up of ups) fs.removeSync(DIST_ADMIN + 'js/tinymce/plugins/' + up);
	done();
});

gulp.task('copy-nacss-reset', (done) => {
	const dir = packageDir('nacss-reset');
	copySync(dir + '/dist/reset.min.css', DIST_ADMIN + 'css/');
	copySync(dir + '/dist/reset.min.css.map', DIST_ADMIN + 'css/');
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
gulp.task('copy-watch', gulp.series('copy-src', 'copy-css'));

gulp.task('js-minify', () => gulp.src(['src/[^_]*.js', 'src/**/[^_]*.js', '!src/**/*.min.js', '!src/data/*.js', '!src/admin/js/tinymce/langs/*.js'], { base: 'src' })
	.pipe($.plumber())
	.pipe($.sourcemaps.init())
	.pipe($.include())
	.pipe($.babel({
		presets: [['@babel/preset-env']],
	}))
	.pipe($.terser())
	.pipe($.rename({ extname: '.min.js' }))
	.pipe($.sourcemaps.write('.'))
	.pipe(gulp.dest('dist'))
);

gulp.task('js-raw', () => gulp.src(['src/admin/js/tinymce/langs/*.js'], { base: 'src' })
	.pipe($.plumber())
	.pipe(gulp.dest('dist'))
);

// gulp.task('js', gulp.series('js-minify'));
gulp.task('js', gulp.series('js-minify', 'js-raw'));

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
	return gulp.src(['src/data/**/*', 'src/data/**/.htaccess', '!src/data/*.js'], { base: 'src' })
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


// -----------------------------------------------------------------------------


gulp.task('watch', () => {
	gulp.watch(['src/**/*.html', 'src/**/*.php'], gulp.series('copy-watch', 'sample'));
	gulp.watch('src/**/*.js', gulp.series('js', 'sample'));
	gulp.watch('src/**/*.scss', gulp.series('sass', 'sample'));
});

gulp.task('build', gulp.parallel('copy', 'js', 'sass'));

gulp.task('default', gulp.series('copy', 'js', 'sass', 'sample', 'watch'));

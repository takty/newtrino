var gulp     = require('gulp');
var plumber  = require('gulp-plumber');
var sass     = require('gulp-sass');
var cleanCSS = require('gulp-clean-css');
var concat   = require('gulp-concat');
var uglify   = require('gulp-uglify');
var rename   = require('gulp-rename');

gulp.task('js', function() {
	gulp.src('src/js/**/*.js')
	.pipe(plumber())
	.pipe(uglify())
	.pipe(rename({extname: '.min.js'}))
	.pipe(gulp.dest('./dist/js'));
});

gulp.task('sass', function() {
	gulp.src('src/sass/style.scss')
	.pipe(plumber())
	.pipe(sass())
	.pipe(cleanCSS())
	.pipe(gulp.dest('./dist/css'));
});

gulp.task('js-private', function() {
	gulp.src('src/topic/private/asset/js/**/*.js')
	.pipe(plumber())
	.pipe(uglify())
	.pipe(rename({extname: '.min.js'}))
	.pipe(gulp.dest('./dist/topic/private/asset/js'));
});

gulp.task('sass-private', function() {
	gulp.src('src/topic/private/asset/sass/style.scss')
	.pipe(plumber())
	.pipe(sass())
	.pipe(cleanCSS())
	.pipe(gulp.dest('./dist/topic/private/asset/css/'));

	gulp.src('src/topic/private/asset/sass/editor-style.scss')
	.pipe(plumber())
	.pipe(sass())
	.pipe(cleanCSS())
	.pipe(gulp.dest('./dist/topic/private/asset/css/'));
});

gulp.task('sass-private-image', function() {
	gulp.src(['src/topic/private/asset/sass/*.svg'])
	.pipe(gulp.dest('dist/topic/private/asset/css'));
});

gulp.task('lib', function() {
	gulp.src(['src/lib/**/*.js']).pipe(gulp.dest('dist/lib'));
});

gulp.task('others', function() {
	gulp.src(['src/img/**/*'])                    .pipe(gulp.dest('dist/img'));
	gulp.src(['src/part/**/*'])                   .pipe(gulp.dest('dist/part'));
	gulp.src(['src/*.php'])                       .pipe(gulp.dest('dist'));
	gulp.src(['src/topic/*.php'])                 .pipe(gulp.dest('dist/topic'));
	gulp.src(['src/topic/private/*.php'])         .pipe(gulp.dest('dist/topic/private'));
	gulp.src(['src/topic/private/data/*'])        .pipe(gulp.dest('dist/topic/private/data'));
	gulp.src(['src/topic/private/asset/php/**/*']).pipe(gulp.dest('dist/topic/private/asset/php'));
})

gulp.task('watch', function() {
	gulp.watch('./src/**/*.scss', ['sass', 'sass-private']);
	gulp.watch('./src/**/*.js', ['js', 'js-private']);
	gulp.watch('./src/**/*.php', ['others']);
});

gulp.task('default', ['others', 'lib', 'js', 'js-private', 'sass', 'sass-private', 'watch']);

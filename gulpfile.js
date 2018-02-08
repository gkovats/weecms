'use strict';

var gulp    = require('gulp');
var sass    = require('gulp-sass');
var uglify  = require('gulp-uglify');
var concat  = require('gulp-concat');
var jsDest  = './www/_/dist/';

gulp.task('default', ['sass', 'package-js'], function () {

});

gulp.task('sass', function() {
    return gulp.src('./sass/**/*.scss')
      .pipe(sass().on('error', sass.logError))
      .pipe(gulp.dest('./www/_/css'));
});

gulp.task('package-js', function() {
    return gulp.src([
            './node_modules/jquery/dist/jquery.min.js',
            './node_modules/popper.js/dist/umd/popper.min.js',
            './node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
            './www/_/js/script.js'
        ])
        .pipe(concat('scripts.js'))
        .pipe(gulp.dest(jsDest));
});

gulp.task('watch', function () {
  gulp.watch('./sass/**/*.scss', ['default']);
  gulp.watch('./www/_/js/*.js', ['package-js']);
});

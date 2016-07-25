'use strict';

var Promise = require('es6-promise').Promise;

var
  gulp = require('gulp'),
  sass = require('gulp-sass'),
  autoprefixer = require('gulp-autoprefixer'),
  minifyCSS = require('gulp-minify-css'),
  rename = require('gulp-rename'),
  header  = require('gulp-header'),
  jshint = require('gulp-jshint'),
  uglify = require('gulp-uglify'),
  packageInfo = require('./package.json');

var
  paths = {
    stylesDir: './resources/styles',
    scriptsDir: './resources/scripts'
  };

var
  banner = [
    '/*!\n',
    ' * <%= packageInfo.title %>\n',
    ' * <%= packageInfo.url %>\n',
    ' * @author <%= packageInfo.author %>\n',
    ' * @version <%= packageInfo.version %>\n',
    ' * Copyright ' + new Date().getFullYear(),
    ' */',
    '\n'
  ].join('');

gulp.task('styles', function() {
  return gulp.src(paths.stylesDir + '/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer('last 2 version'))
    .pipe(header(banner, { packageInfo : packageInfo }))
    .pipe(gulp.dest(paths.stylesDir))
    .pipe(minifyCSS())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.stylesDir));
});

gulp.task('scripts',function(){
  gulp.src(paths.scriptsDir + '/*-library.js')
    .pipe(jshint())
    .pipe(jshint.reporter('default'))
    .pipe(uglify())
    .pipe(header(banner, { packageInfo: packageInfo }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.scriptsDir));
});
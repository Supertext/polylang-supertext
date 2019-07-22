'use strict';

var
  gulp = require('gulp'),
  sass = require('gulp-sass'),
  autoprefixer = require('gulp-autoprefixer'),
  cleanCSS = require('gulp-clean-css'),
  rename = require('gulp-rename'),
  header = require('gulp-header'),
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

function styles() {
  console.info("Running styles...");

  return gulp.src(paths.stylesDir + '/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer('last 2 version'))
    .pipe(header(banner, {
      packageInfo: packageInfo
    }))
    .pipe(gulp.dest(paths.stylesDir))
    .pipe(cleanCSS())
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest(paths.stylesDir));
}

function scripts() {
  console.info("Running scripts...");

  gulp.src(paths.scriptsDir + '/*-library.js')
    .pipe(jshint())
    .pipe(jshint.reporter('default'))
    .pipe(uglify())
    .pipe(header(banner, {
      packageInfo: packageInfo
    }))
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest(paths.scriptsDir));
}

function defaultTask() {
  gulp.watch(paths.stylesDir + '/*.scss').on('change', styles);
  gulp.watch(paths.scriptsDir + '/*-library.js').on('change', scripts);
}

exports.default = defaultTask
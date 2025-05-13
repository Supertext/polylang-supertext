'use strict';

import { src, dest, watch } from 'gulp';
import dartSass from 'sass';
import gulpSass from 'gulp-sass';
import autoprefixer from 'gulp-autoprefixer';
import cleanCSS from 'gulp-clean-css';
import rename from 'gulp-rename';
import header from 'gulp-header';
import jshint from 'gulp-jshint';
import uglify from 'gulp-uglify';
import packageInfo from './package.json' assert { type: 'json' };

const sass = gulpSass(dartSass);
const { reporter } = jshint;
const paths = {
  stylesDir: './resources/styles',
  scriptsDir: './resources/scripts'
};

const banner = [
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
  console.info('Running styles...');

  return src(paths.stylesDir + '/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer('last 2 version'))
    .pipe(
      header(banner, {
        packageInfo: packageInfo
      })
    )
    .pipe(dest(paths.stylesDir))
    .pipe(cleanCSS())
    .pipe(
      rename({
        suffix: '.min'
      })
    )
    .pipe(dest(paths.stylesDir));
}

function scripts() {
  console.info('Running scripts...');

  src(paths.scriptsDir + '/*-library.js')
    .pipe(jshint())
    .pipe(reporter('default'))
    .pipe(uglify())
    .pipe(
      header(banner, {
        packageInfo: packageInfo
      })
    )
    .pipe(
      rename({
        suffix: '.min'
      })
    )
    .pipe(dest(paths.scriptsDir));
}

function defaultTask() {
  watch(paths.stylesDir + '/*.scss').on('change', styles);
  watch(paths.scriptsDir + '/*-library.js').on('change', scripts);
}

const _default = defaultTask;
export { _default as default };

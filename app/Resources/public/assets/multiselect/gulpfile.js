var gulp = require('gulp');

// include plug-ins
var rename = require('gulp-rename');  
var stripDebug = require('gulp-strip-debug');
var uglify = require('gulp-uglify');

// JS concat, strip debugging and minify
gulp.task('scripts', function() {
    gulp.src(['dist/js/multiselect.js'])
        .pipe(rename('multiselect.min.js'))
        .pipe(stripDebug())
        .pipe(uglify('multiselect.min.js', {
            outSourceMap: true
        }))
        .pipe(gulp.dest('dist/js/'));
});

gulp.task('default', ['scripts']);  

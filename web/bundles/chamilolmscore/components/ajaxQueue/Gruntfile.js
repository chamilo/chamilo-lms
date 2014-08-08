'use strict';

module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    // Metadata.
    pkg: grunt.file.readJSON("package.json"),
    // Task configuration.
    concat: {
      options: {
        banner: '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' +
          '<%= grunt.template.today("yyyy-mm-dd") %>\n' +
          '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' +
          '* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' +
          ' Licensed <%= _.pluck(pkg.licenses, "type").join(", ") %> */\n'
      },
      dist: {
        src: ['src/jquery.ajaxQueue.js'],
        dest: 'dist/jquery.ajaxQueue.js'
      },
    },
    uglify: {
      options: {
        banner: "/*! jQuery Ajax Queue v<%= pkg.version %> | (c) <%= grunt.template.today('yyyy') %> <%= pkg.author.name %> | Licensed <%= _.pluck(pkg.licenses, \"type\").join(\", \") %> */\n",
        sourceMap: "dist/jquery.ajaxQueue.min.map",
        beautify: {
          ascii_only: true
        }
      },
      all: {
        files: {
          "dist/jquery.ajaxQueue.min.js": ['src/jquery.ajaxQueue.js']
        }
      },
    },
    qunit: {
      files: ['test/**/*.html']
    },
    jshint: {
      gruntfile: {
        options: {
          jshintrc: '.jshintrc'
        },
        src: ['Gruntfile.js']
      },
      src: {
        options: {
          jshintrc: 'src/.jshintrc'
        },
        src: ['src/**/*.js']
      },
      test: {
        options: {
          jshintrc: 'test/.jshintrc'
        },
        src: ['test/**/*.js']
      },
    },
    watch: {
      gruntfile: {
        files: '<config:jshint.gruntfile.src>',
        tasks: ['jshint:gruntfile']
      },
      src: {
        files: '<config:jshint.src.src>',
        tasks: ['jshint:src', 'qunit']
      },
      test: {
        files: '<config:jshint.test.src>',
        tasks: ['jshint:test', 'qunit']
      },
    },
  });

  // Default task.
  grunt.loadNpmTasks("grunt-contrib-concat");
  grunt.loadNpmTasks("grunt-contrib-watch");
  grunt.loadNpmTasks("grunt-contrib-jshint");
  grunt.loadNpmTasks("grunt-contrib-uglify");
  grunt.loadNpmTasks("grunt-contrib-qunit");
  grunt.registerTask('default', ['jshint', 'concat', 'qunit', 'manifest', 'concat', 'uglify']);

  grunt.registerTask( "manifest", function() {
    var pkg = grunt.config( "pkg" );
    grunt.file.write( "ajaxQueue.jquery.json", JSON.stringify({
      name: "ajaxQueue",
      title: pkg.title,
      description: pkg.description,
      keywords: pkg.keywords,
      version: pkg.version,
      author: {
        name: pkg.author.name,
        url: pkg.author.url.replace( "master", pkg.version )
      },
      maintainers: pkg.maintainers,
      licenses: pkg.licenses.map(function( license ) {
        license.url = license.url.replace( "master", pkg.version );
        return license;
      }),
      bugs: pkg.bugs,
      homepage: pkg.homepage,
      docs: pkg.homepage,
      dependencies: {
        jquery: ">=1.5"
      }
    }, null, "\t" ) );
  });
};

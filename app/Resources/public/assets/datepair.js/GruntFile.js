module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        meta: {
            banner: '/*!\n' +
            ' * <%= pkg.name %> v<%= pkg.version %> - <%= pkg.description %>\n' +
            ' * Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %> - <%= pkg.homepage %>\n' +
            ' * License: <%= pkg.license %>\n' +
            ' */\n\n'
        },

        rig: {
            options: {
                banner: '<%= meta.banner %>'
            },
            dist: {
                files: {
                    'dist/datepair.js': ['src/wrapper.js'],
                    'dist/jquery.datepair.js' : ['src/jquery.datepair.js'],
                }
            }
        },

        uglify: {
            options: {
                banner: '<%= meta.banner %>',
                report: 'min'
            },
            dist: {
                files: {
                    'dist/datepair.min.js': 'dist/datepair.js',
                    'dist/jquery.datepair.min.js': ['dist/datepair.js', 'dist/jquery.datepair.js'],
                }
            }
        },
        jshint: {
            all: ['src/*.js']
        },

        watch: {
            options : {
                atBegin : true
            },
            files: ['src/*.js'],
            tasks: ['rig']
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-rigger');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-jshint');

    grunt.registerTask('default', ['rig', 'uglify']);
};

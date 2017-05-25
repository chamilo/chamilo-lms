module.exports = function (grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        compass: {
            auto: {
                options: {
                    sassDir: './',
                    cssDir: '../'
                }
            }
        },
        watch: {
            scss: {
                files: ['./**/*.scss'],
                tasks: ['compass']
            }
        }
    });
    grunt.loadNpmTasks('grunt-contrib-compass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.registerTask('default', ['watch']);
};
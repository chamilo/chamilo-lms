/*global module:false*/
module.exports = function(grunt) {
    "use strict";
    var coreFileList = [
        "vendor/cryptojs-v3.0.2/rollups/sha1.js",
        "vendor/cryptojs-v3.0.2/components/enc-base64.js",
        "src/TinCan.js",
        "src/Utils.js",
        "src/LRS.js",
        "src/AgentAccount.js",
        "src/Agent.js",
        "src/Group.js",
        "src/Verb.js",
        "src/Result.js",
        "src/Score.js",
        "src/InteractionComponent.js",
        "src/ActivityDefinition.js",
        "src/Activity.js",
        "src/ContextActivities.js",
        "src/Context.js",
        "src/StatementRef.js",
        "src/SubStatement.js",
        "src/Statement.js",
        "src/StatementsResult.js",
        "src/State.js",
        "src/ActivityProfile.js",
        "src/AgentProfile.js",
        "src/About.js"
    ],
    browserFileList = coreFileList.slice(),
    nodeFileList = coreFileList.slice(),
    pkg,
    bower;

    browserFileList.push(
        "src/Environment/Browser.js"
    );
    nodeFileList.push(
        "src/Environment/Node.js"
    );

    pkg = grunt.file.readJSON("package.json");
    bower = grunt.file.readJSON("bower.json");

    if (pkg.version !== bower.version) {
        grunt.fail.fatal("package.json and bower.json versions do not match");
    }

    // Project configuration.
    grunt.initConfig({
        pkg: pkg,

        watch: {
            files: ["src/**/*.js"],
            tasks: ["build"],
            options: {
                interrupt: true
            }
        },

        jshint: {
            all: ["Gruntfile.js", "src/**/*.js"],
            options: {
                bitwise: true,
                es3: true, // must use ES3 syntax (support for IE6/7/8/9)
                curly: true, // Always use curlys {}
                eqeqeq: true, // No more == for you, === only
                forin: true,
                freeze: true,
                immed: true, // prohibits the use of immediate function invocations without wrapping them in parentheses
                indent: 4, // force tab width of 4 spaces
                latedef: true, // no setting variables before they are defined
                newcap: true, // Always call constructors with a Cap
                noarg: true, // prohibits arguments.caller and arguments.callee
                noempty: true, // prevent empty blocks
                nonbsp: true,
                nonew: true, // don't allow non-captured constructor use
                plusplus: true, // prevent use of ++ and --
                quotmark: "double", // require strings to be double quoted
                undef: true, // prohibits the use of explicitly undeclared variables
                unused: true, // Warns on unused variables
                trailing: true, // Prohibits trailing whitespace
                maxdepth: 6, // Max nesting of methods 6 layers deep
                onevar: true,
                strict: true,
                globals: {
                    TinCan: true
                }
            }
        },

        concat: {
            options: {
                banner: "\"<%= pkg.version %>\";\n"
            },
            dist: {
                files: {
                    "build/tincan.js": browserFileList,
                    "build/tincan-node.js": nodeFileList
                },
                nonull: true
            }
        },

        uglify: {
            dist: {
                files: {
                    "build/tincan-min.js": ["build/tincan.js"]
                },
                options: {
                    sourceMap: true
                }
            }
        },

        yuidoc: {
            compile: {
                version: "<%= pkg.version %>",
                name: "TinCanJS",
                description: "Library for working with Tin Can API in JavaScript",
                url: "http://rusticisoftware.github.com/TinCanJS/",
                options: {
                    paths: "src/",
                    outdir: "doc/api/"
                },
                logo: "http://cdn4.tincanapi.com/wp-content/themes/tincanapi/images/logo.png"
            }
        }
    });

    // Load Tasks
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-contrib-concat");
    grunt.loadNpmTasks("grunt-contrib-jshint");
    grunt.loadNpmTasks("grunt-contrib-uglify");
    grunt.loadNpmTasks("grunt-contrib-yuidoc");

    // Define tasks
    grunt.registerTask("build", ["jshint", "concat", "uglify"]);
    grunt.registerTask("default", "build");
};

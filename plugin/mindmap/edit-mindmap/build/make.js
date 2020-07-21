var combine = require('./lib/combine'),
    dep = require('./lib/dependency'),
    fs = require('fs'),
    path = require('path'),
    config = require('./lib/config'),
    UglifyJS = require("uglify-js");

config.parse( path.join(__dirname, 'config.json') );
dep.init(config);
combine.init(dep, config);

var testUri = path.resolve(__dirname, '../', 'client/js/mindmap.js'),
    testOutput = path.resolve(__dirname, '../', 'client/js/mindmap.min.js');

combine.combineFile(testUri, testOutput);
fs.writeFileSync(testOutput, UglifyJS.minify(testOutput).code);
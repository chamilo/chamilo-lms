var dep = require('./lib/dependency'),
    fs = require('fs'),
    path = require('path'),
    config = require('./lib/config');

config.parse( path.join(__dirname, 'config.json') );
dep.init(config);
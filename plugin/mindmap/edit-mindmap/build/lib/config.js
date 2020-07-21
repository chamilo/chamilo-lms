var fs = require('fs'),
    path = require('path'),
    config = {};

exports.getProjectName = function() {
    return config.name;
};

//js文件夹必须和tools在同一级目录
exports.getJsDir = function() {
    if(config.jsDir) {
        return path.resolve(__dirname, '../../', config.jsDir);
    }
};

exports.parse = function(uri) {
    uri = path.normalize(uri);
    config = JSON.parse( fs.readFileSync(uri) );
};
var fs = require('fs'),
    path = require('path');

var //项目名
    prefix,
    //项目路径
    jsDir,
    //依赖文件路径
    depsPath,
    //依赖关系缓存
    dependencies = {
        pathToNames: {},    // 1 to many
        nameToPath: {},     // 1 to 1
        requires: {}        // 1 to many
    };

function addDepsToFile(src, provides, requires) {
    var insertText, fd;

    requires = '[' + requires.join(',') + ']';
    provides = '[' + provides.join(',') + ']';

    insertText = [
        prefix,
        '.addDependency(\'',
        src,
        '\', ',
        provides,
        ', ',
        requires,
        ');\n'
    ].join('');

    try{
        fd = fs.openSync(depsPath, 'a');
        fs.writeSync(fd, insertText, 0, 'utf8');
    }catch(e){
        return false;
    }

    return true;
}

function addDepsToObj(src, provides, requires) {
    var provide, require, deps = dependencies;

    for( var i = 0; (provide = provides[i]); i++) {
        deps.nameToPath[provide] = src;
        if (!(src in deps.pathToNames)) {
            deps.pathToNames[src] = {};
        }
        deps.pathToNames[src][provide] = true;
    }

    for( var j = 0; (require = requires[j]); j++) {
        if (!(src in deps.requires)) {
            deps.requires[src] = {};
        }
        deps.requires[src][require] = true;
    }
}

function scanFile(uri) {
    if( path.basename(uri) === 'deps.js') {
        return;
    }

    var reg = prefix + '\\.(require|provide)\\([\'\"]([-_\\.a-zA-Z0-9]+)[\'\"]\\);[\n\r]*',
        provides = [],
        requires = [],
        content = fs.readFileSync(uri),
        match;

    reg = new RegExp(reg, 'g');
    uri = path.relative(jsDir, uri).replace(/\\/g, '/');

    do {
        match = reg.exec(content);

        if(match) {
            if( match[1] === 'require' ) {
                requires.push('\'' + match[2] + '\'');
            }
            if( match[1] === 'provide' ) {
                //prefix不应该被包含在provide name中
                var provide = match[2].split('.');
                if(provide[0] === prefix) {
                    provide = provide.slice(1).join('.');
                } else {
                    provide = match[2];
                }

                provides.push('\'' + provide + '\'');
            }
        }
    } while(match);

    addDepsToFile( uri, provides, requires );
    addDepsToObj( uri, provides, requires );
    console.log('success! -> create one dependency record : ' + uri);
}

exports.scan = function(uri) {
    var stat = fs.statSync(uri);

    if( stat.isDirectory() ) {
        fs.readdirSync(uri).forEach(function(part) {
            exports.scan( path.join(uri, part) );
        });
    } else if( stat.isFile() ) {
        scanFile(uri);
    }
};

exports.init = function(config) {
    prefix = config.getProjectName();
    jsDir = config.getJsDir();
    depsPath = path.join(jsDir, 'deps.js');
    
    fs.writeFile(depsPath, '');
    console.log('success! -> create a new deps.js');

    exports.scan(jsDir);
};

exports.getRequiresByPath = function(src) {
    return dependencies.requires[src];
};

exports.getPathByName = function(name) {
    return dependencies.nameToPath[name];
};

exports.getDepsPath = function() {
    return depsPath;
};
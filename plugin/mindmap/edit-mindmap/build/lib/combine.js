var path = require('path'),
    fs = require('fs');

var //项目名
    prefix,
    //依赖关系
    dep,
    //项目路径
    jsDir;

var sort = function(src) {
    if(!dep) {
        console.log('Please call combine.init method first!');
        return;
    }

    var scripts = [],
        visited = {},
        visit = function(node) {
            if(node in visited) {
                return;
            }
            visited[node] = true;
            
            var requires = dep.getRequiresByPath(node);

            for(var require in requires) {
                visit( dep.getPathByName(require) );
            }
            
            scripts.push(node);
        };

    visit(src);

    return scripts;
};

var deleteRequire = function(code) {
    var reg = prefix + '\\.require\\([\'\"]([-_\\.a-zA-Z0-9]+)[\'\"]\\);[\n\r]*';
    reg = new RegExp(reg, 'g');
    return code.replace(reg, '');
};

exports.init = function(dependency, config) {
    dep = dependency;
    jsDir = config.getJsDir();
    prefix = config.getProjectName();
};

exports.combineCode = function(src) {
    if(!src) {
        console.log('Please input a file');
        return;
    }

    src = path.relative(jsDir, src).replace(/\\/g, '/');
    var scripts = sort(src), code = [];
    
    for(var i = 0, l = scripts.length; i < l; i++) {
        src = path.join(jsDir, scripts[i]);
        var content = fs.readFileSync(src).toString();
        code.push( deleteRequire(content) );
    }

    return code.join('\n\n');
};

exports.combineFile = function(src, output) {
    var content = exports.combineCode(src);
    fs.writeFileSync(output, content);
    console.log('success! -> Combined File : ' + src);
};

exports.combine = function() {};
/*
 * @Author : l.w.kampfer@gmail.com 
 */

(function(global) {
	
	var COMPILED = false;
	
	var kampfer = {};

	kampfer.global = global;

	kampfer.basePath = '';

	kampfer.implicitNamespaces = {};

	kampfer.isProvided = function(name) {
		return !kampfer.implicitNamespaces[name] && !!kampfer.getPropertyByName(name);
	};

	kampfer.getPropertyByName = function( name, obj ) {
		var namespace = name.split('.'),
			cur = obj || kampfer;
		for( var part; (part = namespace.shift()); ) {
			if( kampfer.isDefAndNotNull( cur[part] ) ) {
				cur = cur[part];
			} else {
				return null;
			}
		}
		return cur;
	};

	kampfer.exportPath = function(name, value, objectToExportTo) {
		
		var cur = objectToExportTo || kampfer.global,
			namespace = name.split('.');
			
		for( var part; (part = namespace.shift()); ) {
			if( !namespace.length && kampfer.isDefAndNotNull(value) ) {
				cur[part] = value;
			} else if( cur[part] ) {
				cur = cur[part];
			} else {
				cur = cur[part] = {};
			}
		}
		
	};

	kampfer.provide = function(name) {
		
		//if(!COMPILED) {
			
			if( kampfer.isProvided(name) ) {
				throw Error('Namespace "' + name + '" already declared.');
			}
			
			delete kampfer.implicitNamespaces[name];
			
			var namespace = name;
			while( (namespace = namespace.substring( 0, namespace.lastIndexOf('.') )) ) {
				if( kampfer.getPropertyByName(namespace) ) {
					break;
				} else {
					kampfer.implicitNamespaces[namespace] = true;
				}
			}
		//}
		
		kampfer.exportPath(name, null, kampfer);
		
	};
	
	kampfer.require = function(name) {
		
		if( !COMPILED ) {
			if ( !name || kampfer.isProvided(name) ) {
				return;
			}
			
			var path = kampfer._getPathFromDeps(name);
			if (path) {
				kampfer._included[path] = true;
				kampfer._writeScripts();
			}
		}
		
	};

	kampfer.addDependency = function( path, provides, requires ) {
		
		if( !COMPILED ) {
			
			var provide, require, deps;
			path = path.replace(/\\/g, '/');
			deps = kampfer._dependencies;
			
			for( var i = 0; (provide = provides[i]); i++) {
				deps.nameToPath[provide] = path;
				if (!(path in deps.pathToNames)) {
					deps.pathToNames[path] = {};
				}
				deps.pathToNames[path][provide] = true;
			}
			
			for( var j = 0; (require = requires[j]); j++) {
				if (!(path in deps.requires)) {
					deps.requires[path] = {};
				}
				deps.requires[path][require] = true;
			}
			
		}
		
	};
	
	
	if(!COMPILED) {

		kampfer._inHtmlDocument = function() {
			var doc = kampfer.global.document;
			return typeof doc != 'undefined' && 'write' in doc;
			// XULDocument misses write.
		};

		kampfer._getPathFromDeps = function(name) {
			if( name in kampfer._dependencies.nameToPath ) {
				return kampfer._dependencies.nameToPath[name];
			} else {
				return null;
			}
		};

		kampfer._importScript = function(src) {
			var _importScript = kampfer._writeScriptTag;
			if(!kampfer._dependencies.written[src] && _importScript(src)) {
				kampfer._dependencies.written[src] = true;
			}
		};

		kampfer._writeScripts = function() {

			var scripts = [],
				seenScript = {},
				deps = kampfer._dependencies;
			
			function visitNode(path) {
				if( path in deps.written ) {
					return;
				}

				if( path in deps.visited ) {
					if( !(path in seenScript) ) {
						seenScript[path] = true;
						scripts.push(path);
					}
					return;
				}

				deps.visited[path] = true;

				if (path in deps.requires) {
					for (var requireName in deps.requires[path]) {

						if (!kampfer.isProvided(requireName)) {
							if (requireName in deps.nameToPath) {
								visitNode(deps.nameToPath[requireName]);
							} else {
								throw Error('Undefined nameToPath for ' + requireName + ' in ' + path);
							}
						}
					}
				}

				if (!(path in seenScript)) {
					seenScript[path] = true;
					scripts.push(path);
				}
			}

			for( var path in kampfer._included ) {
				if( !deps.written[path] ) {
					visitNode(path);
				}
			}

			for( var i = 0; i < scripts.length; i++ ) {
				if ( scripts[i] ) {
					kampfer._importScript( kampfer.basePath + scripts[i] );
				} else {
					throw Error('Undefined script input');
				}
			}
		};
			

		kampfer._writeScriptTag = function(src) {
			if( kampfer._inHtmlDocument() ) {
				var doc = kampfer.global.document;
				doc.write('<script type="text/javascript" src="' + src + '"></' + 'script>');
				return true;
			} else {
				return false;
			}
		};

		kampfer._findBasePath = function() {
			
			if( !kampfer._inHtmlDocument() ) {
				return;
			}
			
			var doc = kampfer.global.document,
				scripts = doc.getElementsByTagName('script');
				
			for(var i = scripts.length - 1; i >= 0; --i) {
				
				var src = scripts[i].src,
					qmark = src.lastIndexOf('?'),
					l = (qmark === -1 ? src.length : qmark);
				
				if (src.substr(l - 7, 7) == 'base.js') {
					kampfer.basePath = src.substr(0, l - 7);
					return;
				}
				
			}
			
		};

		kampfer._dependencies = {
			pathToNames: {},	// 1 to many
			nameToPath: {},		// 1 to 1
			requires: {},		// 1 to many
			visited: {},		// 避免在拓扑排序时循环访问同一个节点。访问过的节点将保存在这里
			written: {}			// 记录已经被加载到页面的js文件名
		};
		

		kampfer._included = {};
		
		
		//寻找js默认路径（即base.js的路径）
		kampfer._findBasePath();
		
		//加载依赖关系记录
		kampfer._importScript( kampfer.basePath + 'deps.js'	);
		
	}

	kampfer.isDef = function(val) {
		return val !== undefined;
	};

	kampfer.isDefAndNotNull = function(val) {
		return val != null;
	};
	
	
	var _toString = Object.prototype.toString;

	kampfer.type = function(value) {
		return value == null ?
			String(value) :
			_class2type[ _toString.call(value) ] || 'object';
	};

	kampfer.isArray = function(val) {
		return kampfer.type(val) === 'array';
	};

	kampfer.isObject = function(val) {
		return kampfer.type(val) === 'object';
	};

	kampfer.isEmptyObject = function(val) {
		if( kampfer.type(val) !== 'object' ) {
			return;
		}
		for( var name in val ) {
			return false;
		}
		return true;
	};
	
	kampfer.isWindow = function(obj) {
		return !!obj && obj == obj.window;
	};

	kampfer.each = function( array, fn, thisObj ) {
		for( var i = 0, len = (array && array.length) || 0; i < len; ++i ) {
			if( i in array ) {
				fn.call( thisObj || kampfer.global, i, array[i], array );
			}
		}
	};

	kampfer.extend = function() {
		
		var src, target, name, len, i, deep, copyFrom, copyTo, clone;
		i = 1;
		len = arguments.length;
		deep = false;
		target = arguments[0] || {};
		
		if( typeof target === 'boolean' ) {
			deep = target;
			i = 2;
			target = arguments[1] || {};
		}

		if( i === len ) {
			target = this;
			--i;
		}
		
		for( ; i < len; i++ ) {
			src = arguments[i];
			if( src !== null ) {
				for( name in src ) {
					copyFrom = src[name];
					copyTo = target[name];
					if( copyTo === copyFrom ) {
						continue;
					}
					if( deep && copyFrom && ( kampfer.isArray(copyFrom) || 
						kampfer.isObject(copyFrom) ) ) {
						if( kampfer.isArray(copyFrom) ) {
							clone = copyTo && kampfer.isArray(copyTo) ? copyTo : [];
						} else if( kampfer.isObject(copyFrom) ) {
							clone =	copyTo && kampfer.isObject(copyTo) ? copyTo : {};
						}
						target[name] = kampfer.extend( deep, clone, copyFrom );
					} else if( copyFrom !== undefined ) {
						target[name] = copyFrom;
					}
				}
			}
		}
		
		return target;
		
	};

	kampfer.emptyFn = function() {};

	kampfer.now = function() {
		return +new Date();
	};

	kampfer.expando = 'kampfer' + kampfer.now();
	
	
	var _class2type = {};

	kampfer.each( "Boolean Number String Function Array Date RegExp Object".split(" "), function(i, name) {
		_class2type[ "[object " + name + "]" ] = name.toLowerCase();
	});

	if( kampfer.type( kampfer.global.kampfer ) != 'undefined' ) {
		kampfer._kampfer = kampfer.global.kampfer;
	}
	if( kampfer.type( kampfer.global.k ) != 'undefined' ) {
		kampfer._k = kampfer.global.k;
	}
	kampfer.global.kampfer = kampfer.global.k = kampfer;
	
})( (typeof exports !== 'undefined') ? exports : this );
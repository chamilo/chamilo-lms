if(typeof dojo == "undefined"){

/**
* @file bootstrap1.js
*
* summary: First file that is loaded that 'bootstraps' the entire dojo library suite.
* note:  Must run before hostenv_*.js file.
*
* @author  Copyright 2004 Mark D. Anderson (mda@discerning.com)
* TODOC: should the copyright be changed to Dojo Foundation?
* @license Licensed under the Academic Free License 2.1 http://www.opensource.org/licenses/afl-2.1.php
*
* $Id: dojo.js.uncompressed.js,v 1.1 2006/08/09 17:26:18 yannoo Exp $
*/

// TODOC: HOW TO DOC THE BELOW?
// @global: djConfig
// summary:  
//		Application code can set the global 'djConfig' prior to loading
//		the library to override certain global settings for how dojo works.  
// description:  The variables that can be set are as follows:
//			- isDebug: false
//			- allowQueryConfig: false
//			- baseScriptUri: ""
//			- baseRelativePath: ""
//			- libraryScriptUri: ""
//			- iePreventClobber: false
//			- ieClobberMinimal: true
//			- preventBackButtonFix: true
//			- searchIds: []
//			- parseWidgets: true
// TODOC: HOW TO DOC THESE VARIABLES?
// TODOC: IS THIS A COMPLETE LIST?
// note:
//		'djConfig' does not exist under 'dojo.*' so that it can be set before the 
//		'dojo' variable exists.  
// note:
//		Setting any of these variables *after* the library has loaded does nothing at all. 
// TODOC: is this still true?  Release notes for 0.3 indicated they could be set after load.
//



//TODOC:  HOW TO DOC THIS?
// @global: dj_global
// summary: 
//		an alias for the top-level global object in the host environment
//		(e.g., the window object in a browser).
// description:  
//		Refer to 'dj_global' rather than referring to window to ensure your
//		code runs correctly in contexts other than web browsers (eg: Rhino on a server).
var dj_global = this;



function dj_undef(/*String*/ name, /*Object?*/ object){
	//summary: Returns true if 'name' is defined on 'object' (or globally if 'object' is null).
	//description: Note that 'defined' and 'exists' are not the same concept.
	if(object==null){ object = dj_global; }
	// exception if object is not an Object
	return (typeof object[name] == "undefined");	// Boolean
}


// make sure djConfig is defined
if(dj_undef("djConfig")){ 
	var djConfig = {}; 
}


//TODOC:  HOW TO DOC THIS?
// dojo is the root variable of (almost all) our public symbols -- make sure it is defined.
if(dj_undef("dojo")){ 
	var dojo = {}; 
}

//TODOC:  HOW TO DOC THIS?
dojo.version = {
	// summary: version number of this instance of dojo.
	major: 0, minor: 3, patch: 1, flag: "",
	revision: Number("$Rev: 4342 $".match(/[0-9]+/)[0]),
	toString: function(){
		with(dojo.version){
			return major + "." + minor + "." + patch + flag + " (" + revision + ")";	// String
		}
	}
}

dojo.evalProp = function(/*String*/ name, /*Object*/ object, /*Boolean?*/ create){
	// summary: Returns 'object[name]'.  If not defined and 'create' is true, will return a new Object.
	// description: 
	//		Returns null if 'object[name]' is not defined and 'create' is not true.
	// 		Note: 'defined' and 'exists' are not the same concept.	
	return (object && !dj_undef(name, object) ? object[name] : (create ? (object[name]={}) : undefined));	// mixed
}


dojo.parseObjPath = function(/*String*/ path, /*Object?*/ context, /*Boolean?*/ create){
	// summary: Parse string path to an object, and return corresponding object reference and property name.
	// description: 
	//		Returns an object with two properties, 'obj' and 'prop'.  
	//		'obj[prop]' is the reference indicated by 'path'.
	// path: Path to an object, in the form "A.B.C".
	// context: Object to use as root of path.  Defaults to 'dj_global'.
	// create: If true, Objects will be created at any point along the 'path' that is undefined.
	var object = (context != null ? context : dj_global);
	var names = path.split('.');
	var prop = names.pop();
	for (var i=0,l=names.length;i<l && object;i++){
		object = dojo.evalProp(names[i], object, create);
	}
	return {obj: object, prop: prop};	// Object: {obj: Object, prop: String}
}


dojo.evalObjPath = function(/*String*/ path, /*Boolean?*/ create){
	// summary: Return the value of object at 'path' in the global scope, without using 'eval()'.
	// path: Path to an object, in the form "A.B.C".
	// create: If true, Objects will be created at any point along the 'path' that is undefined.
	if(typeof path != "string"){ 
		return dj_global; 
	}
	// fast path for no periods
	if(path.indexOf('.') == -1){
		return dojo.evalProp(path, dj_global, create);		// mixed
	}

	//MOW: old 'with' syntax was confusing and would throw an error if parseObjPath returned null.
	var ref = dojo.parseObjPath(path, dj_global, create);
	if(ref){
		return dojo.evalProp(ref.prop, ref.obj, create);	// mixed
	}
	return null;
}

// ****************************************************************
// global public utils
// TODOC: DO WE WANT TO NOTE THAT THESE ARE GLOBAL PUBLIC UTILS?
// ****************************************************************

dojo.errorToString = function(/*Error*/ exception){
	// summary: Return an exception's 'message', 'description' or text.

	// TODO: overriding Error.prototype.toString won't accomplish this?
 	// 		... since natively generated Error objects do not always reflect such things?
	if(!dj_undef("message", exception)){
		return exception.message;		// String
	}else if(!dj_undef("description", exception)){
		return exception.description;	// String
	}else{
		return exception;				// Error
	}
}


dojo.raise = function(/*String*/ message, /*Error?*/ exception){
	// summary: Throw an error message, appending text of 'exception' if provided.
	// note: Also prints a message to the user using 'dojo.hostenv.println'.
	if(exception){
		message = message + ": "+dojo.errorToString(exception);
	}

	// print the message to the user if hostenv.println is defined
	try {	dojo.hostenv.println("FATAL: "+message); } catch (e) {}

	throw Error(message);
}

//Stub functions so things don't break.
//TODOC:  HOW TO DOC THESE?
dojo.debug = function(){}
dojo.debugShallow = function(obj){}
dojo.profile = { start: function(){}, end: function(){}, stop: function(){}, dump: function(){} };


function dj_eval(/*String*/ scriptFragment){ 
	// summary: Perform an evaluation in the global scope.  Use this rather than calling 'eval()' directly.
	// description: Placed in a separate function to minimize size of trapped evaluation context.
	// note:
	//	 - JSC eval() takes an optional second argument which can be 'unsafe'.
	//	 - Mozilla/SpiderMonkey eval() takes an optional second argument which is the
	//  	 scope object for new symbols.
	return dj_global.eval ? dj_global.eval(scriptFragment) : eval(scriptFragment); 	// mixed
}



dojo.unimplemented = function(/*String*/ funcname, /*String?*/ extra){
	// summary: Throw an exception because some function is not implemented.
	// extra: Text to append to the exception message.
	var message = "'" + funcname + "' not implemented";
	if (extra != null) { message += " " + extra; }
	dojo.raise(message);
}


dojo.deprecated = function(/*String*/ behaviour, /*String?*/ extra, /*String?*/ removal){
	// summary: Log a debug message to indicate that a behavior has been deprecated.
	// extra: Text to append to the message.
	// removal: Text to indicate when in the future the behavior will be removed.
	var message = "DEPRECATED: " + behaviour;
	if(extra){ message += " " + extra; }
	if(removal){ message += " -- will be removed in version: " + removal; }
	dojo.debug(message);
}



dojo.inherits = function(/*Function*/ subclass, /*Function*/ superclass){
	// summary: Set up inheritance between two classes.
	if(typeof superclass != 'function'){ 
		dojo.raise("dojo.inherits: superclass argument ["+superclass+"] must be a function (subclass: [" + subclass + "']");
	}
	subclass.prototype = new superclass();
	subclass.prototype.constructor = subclass;
	subclass.superclass = superclass.prototype;
	// DEPRICATED: super is a reserved word, use 'superclass'
	subclass['super'] = superclass.prototype;
}

dojo.render = (function(){
	//TODOC: HOW TO DOC THIS?
	// summary: Details rendering support, OS and browser of the current environment.
	// TODOC: is this something many folks will interact with?  If so, we should doc the structure created...
	function vscaffold(prefs, names){
		var tmp = {
			capable: false,
			support: {
				builtin: false,
				plugin: false
			},
			prefixes: prefs
		};
		for(var prop in names){
			tmp[prop] = false;
		}
		return tmp;
	}

	return {
		name: "",
		ver: dojo.version,
		os: { win: false, linux: false, osx: false },
		html: vscaffold(["html"], ["ie", "opera", "khtml", "safari", "moz"]),
		svg: vscaffold(["svg"], ["corel", "adobe", "batik"]),
		vml: vscaffold(["vml"], ["ie"]),
		swf: vscaffold(["Swf", "Flash", "Mm"], ["mm"]),
		swt: vscaffold(["Swt"], ["ibm"])
	};
})();

// ****************************************************************
// dojo.hostenv methods that must be defined in hostenv_*.js
// ****************************************************************

/**
 * The interface definining the interaction with the EcmaScript host environment.
*/

/*
 * None of these methods should ever be called directly by library users.
 * Instead public methods such as loadModule should be called instead.
 */
dojo.hostenv = (function(){
	// TODOC:  HOW TO DOC THIS?
	// summary: Provides encapsulation of behavior that changes across different 'host environments' 
	//			(different browsers, server via Rhino, etc).
	// description: None of these methods should ever be called directly by library users.
	//				Use public methods such as 'loadModule' instead.
	
	// default configuration options
	var config = {
		isDebug: false,
		allowQueryConfig: false,
		baseScriptUri: "",
		baseRelativePath: "",
		libraryScriptUri: "",
		iePreventClobber: false,
		ieClobberMinimal: true,
		preventBackButtonFix: true,
		searchIds: [],
		parseWidgets: true
	};

	if (typeof djConfig == "undefined") { djConfig = config; }
	else {
		for (var option in config) {
			if (typeof djConfig[option] == "undefined") {
				djConfig[option] = config[option];
			}
		}
	}

	return {
		name_: '(unset)',
		version_: '(unset)',


		getName: function(){ 
			// sumary: Return the name of the host environment.
			return this.name_; 	// String
		},


		getVersion: function(){ 
			// summary: Return the version of the hostenv.
			return this.version_; // String
		},

		getText: function(/*String*/ uri){
			// summary:	Read the plain/text contents at the specified 'uri'.
			// description: 
			//			If 'getText()' is not implemented, then it is necessary to override 
			//			'loadUri()' with an implementation that doesn't rely on it.

			dojo.unimplemented('getText', "uri=" + uri);
		}
	};
})();


dojo.hostenv.getBaseScriptUri = function(){
	// summary: Return the base script uri that other scripts are found relative to.
	// TODOC: HUH?  This comment means nothing to me.  What other scripts? Is this the path to other dojo libraries?
	//		MAYBE:  Return the base uri to scripts in the dojo library.	 ???
	// return: Empty string or a path ending in '/'.
	if(djConfig.baseScriptUri.length){ 
		return djConfig.baseScriptUri;
	}

	// MOW: Why not:
	//			uri = djConfig.libraryScriptUri || djConfig.baseRelativePath
	//		??? Why 'new String(...)'
	var uri = new String(djConfig.libraryScriptUri||djConfig.baseRelativePath);
	if (!uri) { dojo.raise("Nothing returned by getLibraryScriptUri(): " + uri); }

	// MOW: uri seems to not be actually used.  Seems to be hard-coding to djConfig.baseRelativePath... ???
	var lastslash = uri.lastIndexOf('/');		// MOW ???
	djConfig.baseScriptUri = djConfig.baseRelativePath;
	return djConfig.baseScriptUri;	// String
}

/*
 * loader.js - runs before the hostenv_*.js file. Contains all of the package loading methods.
 */

//A semi-colon is at the start of the line because after doing a build, this function definition
//get compressed onto the same line as the last line in bootstrap1.js. That list line is just a
//curly bracket, and the browser complains about that syntax. The semicolon fixes it. Putting it
//here instead of at the end of bootstrap1.js, since it is more of an issue for this file, (using
//the closure), and bootstrap1.js could change in the future.
;(function(){
	//Additional properties for dojo.hostenv
	var _addHostEnv = {
		pkgFileName: "__package__",
	
		// for recursion protection
		loading_modules_: {},
		loaded_modules_: {},
		addedToLoadingCount: [],
		removedFromLoadingCount: [],
	
		inFlightCount: 0,
	
		// FIXME: it should be possible to pull module prefixes in from djConfig
		modulePrefixes_: {
			dojo: {name: "dojo", value: "src"}
		},
	
	
		setModulePrefix: function(module, prefix){
			this.modulePrefixes_[module] = {name: module, value: prefix};
		},
	
		getModulePrefix: function(module){
			var mp = this.modulePrefixes_;
			if((mp[module])&&(mp[module]["name"])){
				return mp[module].value;
			}
			return module;
		},

		getTextStack: [],
		loadUriStack: [],
		loadedUris: [],
	
		//WARNING: This variable is referenced by packages outside of bootstrap: FloatingPane.js and undo/browser.js
		post_load_: false,
		
		//Egad! Lots of test files push on this directly instead of using dojo.addOnLoad.
		modulesLoadedListeners: [],
		unloadListeners: [],
		loadNotifying: false
	};
	
	//Add all of these properties to dojo.hostenv
	for(var param in _addHostEnv){
		dojo.hostenv[param] = _addHostEnv[param];
	}
})();

/**
 * Loads and interprets the script located at relpath, which is relative to the
 * script root directory.  If the script is found but its interpretation causes
 * a runtime exception, that exception is not caught by us, so the caller will
 * see it.  We return a true value if and only if the script is found.
 *
 * For now, we do not have an implementation of a true search path.  We
 * consider only the single base script uri, as returned by getBaseScriptUri().
 *
 * @param relpath A relative path to a script (no leading '/', and typically
 * ending in '.js').
 * @param module A module whose existance to check for after loading a path.
 * Can be used to determine success or failure of the load.
 * @param cb a function to pass the result of evaluating the script (optional)
 */
dojo.hostenv.loadPath = function(relpath, module /*optional*/, cb /*optional*/){
	var uri;
	if((relpath.charAt(0) == '/')||(relpath.match(/^\w+:/))){
		// dojo.raise("relpath '" + relpath + "'; must be relative");
		uri = relpath;
	}else{
		uri = this.getBaseScriptUri() + relpath;
	}
	if(djConfig.cacheBust && dojo.render.html.capable){
		uri += "?" + String(djConfig.cacheBust).replace(/\W+/g,"");
	}
	try{
		return ((!module) ? this.loadUri(uri, cb) : this.loadUriAndCheck(uri, module, cb));
	}catch(e){
		dojo.debug(e);
		return false;
	}
}

/**
 * Reads the contents of the URI, and evaluates the contents.
 * Returns true if it succeeded. Returns false if the URI reading failed.
 * Throws if the evaluation throws.
 * The result of the eval is not available to the caller TODO: now it is; was this a deliberate restriction?
 *
 * @param uri a uri which points at the script to be loaded
 * @param cb a function to process the result of evaluating the script as an expression (optional)
 */
dojo.hostenv.loadUri = function(uri, cb /*optional*/){
	if(this.loadedUris[uri]){
		return 1;
	}
	var contents = this.getText(uri, null, true);
	if(contents == null){ return 0; }
	this.loadedUris[uri] = true;
	if(cb){ contents = '('+contents+')'; }
	var value = dj_eval(contents);
	if(cb){
		cb(value);
	}
	return 1;
}

// FIXME: probably need to add logging to this method
dojo.hostenv.loadUriAndCheck = function(uri, module, cb){
	var ok = true;
	try{
		ok = this.loadUri(uri, cb);
	}catch(e){
		dojo.debug("failed loading ", uri, " with error: ", e);
	}
	return ((ok)&&(this.findModule(module, false))) ? true : false;
}

dojo.loaded = function(){ }
dojo.unloaded = function(){ }

dojo.hostenv.loaded = function(){
	this.loadNotifying = true;
	this.post_load_ = true;
	var mll = this.modulesLoadedListeners;
	for(var x=0; x<mll.length; x++){
		mll[x]();
	}

	//Clear listeners so new ones can be added
	//For other xdomain package loads after the initial load.
	this.modulesLoadedListeners = [];
	this.loadNotifying = false;

	dojo.loaded();
}

dojo.hostenv.unloaded = function(){
	var mll = this.unloadListeners;
	while(mll.length){
		(mll.pop())();
	}
	dojo.unloaded();
}

/*
Call styles:
	dojo.addOnLoad(functionPointer)
	dojo.addOnLoad(object, "functionName")
*/
dojo.addOnLoad = function(obj, fcnName) {
	var dh = dojo.hostenv;
	if(arguments.length == 1) {
		dh.modulesLoadedListeners.push(obj);
	} else if(arguments.length > 1) {
		dh.modulesLoadedListeners.push(function() {
			obj[fcnName]();
		});
	}

	//Added for xdomain loading. dojo.addOnLoad is used to
	//indicate callbacks after doing some dojo.require() statements.
	//In the xdomain case, if all the requires are loaded (after initial
	//page load), then immediately call any listeners.
	if(dh.post_load_ && dh.inFlightCount == 0 && !dh.loadNotifying){
		dh.callLoaded();
	}
}

dojo.addOnUnload = function(obj, fcnName){
	var dh = dojo.hostenv;
	if(arguments.length == 1){
		dh.unloadListeners.push(obj);
	} else if(arguments.length > 1) {
		dh.unloadListeners.push(function() {
			obj[fcnName]();
		});
	}
}

dojo.hostenv.modulesLoaded = function(){
	if(this.post_load_){ return; }
	if((this.loadUriStack.length==0)&&(this.getTextStack.length==0)){
		if(this.inFlightCount > 0){ 
			dojo.debug("files still in flight!");
			return;
		}
		dojo.hostenv.callLoaded();
	}
}

dojo.hostenv.callLoaded = function(){
	if(typeof setTimeout == "object"){
		setTimeout("dojo.hostenv.loaded();", 0);
	}else{
		dojo.hostenv.loaded();
	}
}

dojo.hostenv.getModuleSymbols = function(modulename) {
	var syms = modulename.split(".");
	for(var i = syms.length - 1; i > 0; i--){
		var parentModule = syms.slice(0, i).join(".");
		var parentModulePath = this.getModulePrefix(parentModule);
		if(parentModulePath != parentModule){
			syms.splice(0, i, parentModulePath);
			break;
		}
	}
	return syms;
}

/**
* loadModule("A.B") first checks to see if symbol A.B is defined. 
* If it is, it is simply returned (nothing to do).
*
* If it is not defined, it will look for "A/B.js" in the script root directory,
* followed by "A.js".
*
* It throws if it cannot find a file to load, or if the symbol A.B is not
* defined after loading.
*
* It returns the object A.B.
*
* This does nothing about importing symbols into the current package.
* It is presumed that the caller will take care of that. For example, to import
* all symbols:
*
*    with (dojo.hostenv.loadModule("A.B")) {
*       ...
*    }
*
* And to import just the leaf symbol:
*
*    var B = dojo.hostenv.loadModule("A.B");
*    ...
*
* dj_load is an alias for dojo.hostenv.loadModule
*/
dojo.hostenv._global_omit_module_check = false;
dojo.hostenv.loadModule = function(modulename, exact_only, omit_module_check){
	if(!modulename){ return; }
	omit_module_check = this._global_omit_module_check || omit_module_check;
	var module = this.findModule(modulename, false);
	if(module){
		return module;
	}

	// protect against infinite recursion from mutual dependencies
	if(dj_undef(modulename, this.loading_modules_)){
		this.addedToLoadingCount.push(modulename);
	}
	this.loading_modules_[modulename] = 1;

	// convert periods to slashes
	var relpath = modulename.replace(/\./g, '/') + '.js';

	var syms = this.getModuleSymbols(modulename);
	var startedRelative = ((syms[0].charAt(0) != '/')&&(!syms[0].match(/^\w+:/)));
	var last = syms[syms.length - 1];
	// figure out if we're looking for a full package, if so, we want to do
	// things slightly diffrently
	var nsyms = modulename.split(".");
	if(last=="*"){
		modulename = (nsyms.slice(0, -1)).join('.');

		while(syms.length){
			syms.pop();
			syms.push(this.pkgFileName);
			relpath = syms.join("/") + '.js';
			if(startedRelative && (relpath.charAt(0)=="/")){
				relpath = relpath.slice(1);
			}
			ok = this.loadPath(relpath, ((!omit_module_check) ? modulename : null));
			if(ok){ break; }
			syms.pop();
		}
	}else{
		relpath = syms.join("/") + '.js';
		modulename = nsyms.join('.');
		var ok = this.loadPath(relpath, ((!omit_module_check) ? modulename : null));
		if((!ok)&&(!exact_only)){
			syms.pop();
			while(syms.length){
				relpath = syms.join('/') + '.js';
				ok = this.loadPath(relpath, ((!omit_module_check) ? modulename : null));
				if(ok){ break; }
				syms.pop();
				relpath = syms.join('/') + '/'+this.pkgFileName+'.js';
				if(startedRelative && (relpath.charAt(0)=="/")){
					relpath = relpath.slice(1);
				}
				ok = this.loadPath(relpath, ((!omit_module_check) ? modulename : null));
				if(ok){ break; }
			}
		}

		if((!ok)&&(!omit_module_check)){
			dojo.raise("Could not load '" + modulename + "'; last tried '" + relpath + "'");
		}
	}

	// check that the symbol was defined
	//Don't bother if we're doing xdomain (asynchronous) loading.
	if(!omit_module_check && !this["isXDomain"]){
		// pass in false so we can give better error
		module = this.findModule(modulename, false);
		if(!module){
			dojo.raise("symbol '" + modulename + "' is not defined after loading '" + relpath + "'"); 
		}
	}

	return module;
}

/**
* startPackage("A.B") follows the path, and at each level creates a new empty
* object or uses what already exists. It returns the result.
*/
dojo.hostenv.startPackage = function(packname){
	var modref = dojo.evalObjPath((packname.split(".").slice(0, -1)).join('.'));
	this.loaded_modules_[(new String(packname)).toLowerCase()] = modref;

	var syms = packname.split(/\./);
	if(syms[syms.length-1]=="*"){
		syms.pop();
	}
	return dojo.evalObjPath(syms.join("."), true);
}

/**
 * findModule("A.B") returns the object A.B if it exists, otherwise null.
 * @param modulename A string like 'A.B'.
 * @param must_exist Optional, defualt false. throw instead of returning null
 * if the module does not currently exist.
 */
dojo.hostenv.findModule = function(modulename, must_exist){
	// check cache
	/*
	if(!dj_undef(modulename, this.modules_)){
		return this.modules_[modulename];
	}
	*/

	var lmn = (new String(modulename)).toLowerCase();

	if(this.loaded_modules_[lmn]){
		return this.loaded_modules_[lmn];
	}

	// see if symbol is defined anyway
	var module = dojo.evalObjPath(modulename);
	if((modulename)&&(typeof module != 'undefined')&&(module)){
		this.loaded_modules_[lmn] = module;
		return module;
	}

	if(must_exist){
		dojo.raise("no loaded module named '" + modulename + "'");
	}
	return null;
}

//Start of old bootstrap2:

/*
 * This method taks a "map" of arrays which one can use to optionally load dojo
 * modules. The map is indexed by the possible dojo.hostenv.name_ values, with
 * two additional values: "default" and "common". The items in the "default"
 * array will be loaded if none of the other items have been choosen based on
 * the hostenv.name_ item. The items in the "common" array will _always_ be
 * loaded, regardless of which list is chosen.  Here's how it's normally
 * called:
 *
 *	dojo.kwCompoundRequire({
 *		browser: [
 *			["foo.bar.baz", true, true], // an example that passes multiple args to loadModule()
 *			"foo.sample.*",
 *			"foo.test,
 *		],
 *		default: [ "foo.sample.*" ],
 *		common: [ "really.important.module.*" ]
 *	});
 */
dojo.kwCompoundRequire = function(modMap){
	var common = modMap["common"]||[];
	var result = (modMap[dojo.hostenv.name_]) ? common.concat(modMap[dojo.hostenv.name_]||[]) : common.concat(modMap["default"]||[]);

	for(var x=0; x<result.length; x++){
		var curr = result[x];
		if(curr.constructor == Array){
			dojo.hostenv.loadModule.apply(dojo.hostenv, curr);
		}else{
			dojo.hostenv.loadModule(curr);
		}
	}
}

dojo.require = function(){
	dojo.hostenv.loadModule.apply(dojo.hostenv, arguments);
}

dojo.requireIf = function(){
	if((arguments[0] === true)||(arguments[0]=="common")||(arguments[0] && dojo.render[arguments[0]].capable)){
		var args = [];
		for (var i = 1; i < arguments.length; i++) { args.push(arguments[i]); }
		dojo.require.apply(dojo, args);
	}
}

dojo.requireAfterIf = dojo.requireIf;

dojo.provide = function(){
	return dojo.hostenv.startPackage.apply(dojo.hostenv, arguments);
}

dojo.setModulePrefix = function(module, prefix){
	return dojo.hostenv.setModulePrefix(module, prefix);
}

// determine if an object supports a given method
// useful for longer api chains where you have to test each object in the chain
dojo.exists = function(obj, name){
	var p = name.split(".");
	for(var i = 0; i < p.length; i++){
	if(!(obj[p[i]])) return false;
		obj = obj[p[i]];
	}
	return true;
}

};

if(typeof window == 'undefined'){
	dojo.raise("no window object");
}

// attempt to figure out the path to dojo if it isn't set in the config
(function() {
	// before we get any further with the config options, try to pick them out
	// of the URL. Most of this code is from NW
	if(djConfig.allowQueryConfig){
		var baseUrl = document.location.toString(); // FIXME: use location.query instead?
		var params = baseUrl.split("?", 2);
		if(params.length > 1){
			var paramStr = params[1];
			var pairs = paramStr.split("&");
			for(var x in pairs){
				var sp = pairs[x].split("=");
				// FIXME: is this eval dangerous?
				if((sp[0].length > 9)&&(sp[0].substr(0, 9) == "djConfig.")){
					var opt = sp[0].substr(9);
					try{
						djConfig[opt]=eval(sp[1]);
					}catch(e){
						djConfig[opt]=sp[1];
					}
				}
			}
		}
	}

	if(((djConfig["baseScriptUri"] == "")||(djConfig["baseRelativePath"] == "")) &&(document && document.getElementsByTagName)){
		var scripts = document.getElementsByTagName("script");
		var rePkg = /(__package__|dojo|bootstrap1)\.js([\?\.]|$)/i;
		for(var i = 0; i < scripts.length; i++) {
			var src = scripts[i].getAttribute("src");
			if(!src) { continue; }
			var m = src.match(rePkg);
			if(m) {
				var root = src.substring(0, m.index);
				if(src.indexOf("bootstrap1") > -1) { root += "../"; }
				if(!this["djConfig"]) { djConfig = {}; }
				if(djConfig["baseScriptUri"] == "") { djConfig["baseScriptUri"] = root; }
				if(djConfig["baseRelativePath"] == "") { djConfig["baseRelativePath"] = root; }
				break;
			}
		}
	}

	// fill in the rendering support information in dojo.render.*
	var dr = dojo.render;
	var drh = dojo.render.html;
	var drs = dojo.render.svg;
	var dua = drh.UA = navigator.userAgent;
	var dav = drh.AV = navigator.appVersion;
	var t = true;
	var f = false;
	drh.capable = t;
	drh.support.builtin = t;

	dr.ver = parseFloat(drh.AV);
	dr.os.mac = dav.indexOf("Macintosh") >= 0;
	dr.os.win = dav.indexOf("Windows") >= 0;
	// could also be Solaris or something, but it's the same browser
	dr.os.linux = dav.indexOf("X11") >= 0;

	drh.opera = dua.indexOf("Opera") >= 0;
	drh.khtml = (dav.indexOf("Konqueror") >= 0)||(dav.indexOf("Safari") >= 0);
	drh.safari = dav.indexOf("Safari") >= 0;
	var geckoPos = dua.indexOf("Gecko");
	drh.mozilla = drh.moz = (geckoPos >= 0)&&(!drh.khtml);
	if (drh.mozilla) {
		// gecko version is YYYYMMDD
		drh.geckoVersion = dua.substring(geckoPos + 6, geckoPos + 14);
	}
	drh.ie = (document.all)&&(!drh.opera);
	drh.ie50 = drh.ie && dav.indexOf("MSIE 5.0")>=0;
	drh.ie55 = drh.ie && dav.indexOf("MSIE 5.5")>=0;
	drh.ie60 = drh.ie && dav.indexOf("MSIE 6.0")>=0;
	drh.ie70 = drh.ie && dav.indexOf("MSIE 7.0")>=0;

	// TODO: is the HTML LANG attribute relevant?
	dojo.locale = (drh.ie ? navigator.userLanguage : navigator.language).toLowerCase();

	dr.vml.capable=drh.ie;
	drs.capable = f;
	drs.support.plugin = f;
	drs.support.builtin = f;
	if (document.implementation
		&& document.implementation.hasFeature
		&& document.implementation.hasFeature("org.w3c.dom.svg", "1.0")
	){
		drs.capable = t;
		drs.support.builtin = t;
		drs.support.plugin = f;
	}
})();

dojo.hostenv.startPackage("dojo.hostenv");

dojo.render.name = dojo.hostenv.name_ = 'browser';
dojo.hostenv.searchIds = [];

// These are in order of decreasing likelihood; this will change in time.
dojo.hostenv._XMLHTTP_PROGIDS = ['Msxml2.XMLHTTP', 'Microsoft.XMLHTTP', 'Msxml2.XMLHTTP.4.0'];

dojo.hostenv.getXmlhttpObject = function(){
    var http = null;
	var last_e = null;
	try{ http = new XMLHttpRequest(); }catch(e){}
    if(!http){
		for(var i=0; i<3; ++i){
			var progid = dojo.hostenv._XMLHTTP_PROGIDS[i];
			try{
				http = new ActiveXObject(progid);
			}catch(e){
				last_e = e;
			}

			if(http){
				dojo.hostenv._XMLHTTP_PROGIDS = [progid];  // so faster next time
				break;
			}
		}

		/*if(http && !http.toString) {
			http.toString = function() { "[object XMLHttpRequest]"; }
		}*/
	}

	if(!http){
		return dojo.raise("XMLHTTP not available", last_e);
	}

	return http;
}

/**
 * Read the contents of the specified uri and return those contents.
 *
 * @param uri A relative or absolute uri. If absolute, it still must be in the
 * same "domain" as we are.
 *
 * @param async_cb If not specified, load synchronously. If specified, load
 * asynchronously, and use async_cb as the progress handler which takes the
 * xmlhttp object as its argument. If async_cb, this function returns null.
 *
 * @param fail_ok Default false. If fail_ok and !async_cb and loading fails,
 * return null instead of throwing.
 */
dojo.hostenv.getText = function(uri, async_cb, fail_ok){

	var http = this.getXmlhttpObject();

	if(async_cb){
		http.onreadystatechange = function(){
			if(4==http.readyState){
				if((!http["status"])||((200 <= http.status)&&(300 > http.status))){
					// dojo.debug("LOADED URI: "+uri);
					async_cb(http.responseText);
				}
			}
		}
	}

	http.open('GET', uri, async_cb ? true : false);
	try{
		http.send(null);
		if(async_cb){
			return null;
		}
		if((http["status"])&&((200 > http.status)||(300 <= http.status))){
			throw Error("Unable to load "+uri+" status:"+ http.status);
		}
	}catch(e){
		if((fail_ok)&&(!async_cb)){
			return null;
		}else{
			throw e;
		}
	}

	return http.responseText;
}

/*
 * It turns out that if we check *right now*, as this script file is being loaded,
 * then the last script element in the window DOM is ourselves.
 * That is because any subsequent script elements haven't shown up in the document
 * object yet.
 */
 /*
function dj_last_script_src() {
    var scripts = window.document.getElementsByTagName('script');
    if(scripts.length < 1){
		dojo.raise("No script elements in window.document, so can't figure out my script src");
	}
    var script = scripts[scripts.length - 1];
    var src = script.src;
    if(!src){
		dojo.raise("Last script element (out of " + scripts.length + ") has no src");
	}
    return src;
}

if(!dojo.hostenv["library_script_uri_"]){
	dojo.hostenv.library_script_uri_ = dj_last_script_src();
}
*/

dojo.hostenv.defaultDebugContainerId = 'dojoDebug';
dojo.hostenv._println_buffer = [];
dojo.hostenv._println_safe = false;
dojo.hostenv.println = function (line){
	if(!dojo.hostenv._println_safe){
		dojo.hostenv._println_buffer.push(line);
	}else{
		try {
			var console = document.getElementById(djConfig.debugContainerId ?
				djConfig.debugContainerId : dojo.hostenv.defaultDebugContainerId);
			if(!console) { console = document.getElementsByTagName("body")[0] || document.body; }

			var div = document.createElement("div");
			div.appendChild(document.createTextNode(line));
			console.appendChild(div);
		} catch (e) {
			try{
				// safari needs the output wrapped in an element for some reason
				document.write("<div>" + line + "</div>");
			}catch(e2){
				window.status = line;
			}
		}
	}
}

dojo.addOnLoad(function(){
	dojo.hostenv._println_safe = true;
	while(dojo.hostenv._println_buffer.length > 0){
		dojo.hostenv.println(dojo.hostenv._println_buffer.shift());
	}
});

function dj_addNodeEvtHdlr(node, evtName, fp, capture){
	var oldHandler = node["on"+evtName] || function(){};
	node["on"+evtName] = function(){
		fp.apply(node, arguments);
		oldHandler.apply(node, arguments);
	}
	return true;
}


/* Uncomment this to allow init after DOMLoad, not after window.onload

// Mozilla exposes the event we could use
if (dojo.render.html.mozilla) {
   document.addEventListener("DOMContentLoaded", dj_load_init, null);
}
// for Internet Explorer. readyState will not be achieved on init call, but dojo doesn't need it
//Tighten up the comments below to allow init after DOMLoad, not after window.onload
/ * @cc_on @ * /
/ * @if (@_win32)
    document.write("<script defer>dj_load_init()<"+"/script>");
/ * @end @ * /
*/

// default for other browsers
// potential TODO: apply setTimeout approach for other browsers
// that will cause flickering though ( document is loaded and THEN is processed)
// maybe show/hide required in this case..
// TODO: other browsers may support DOMContentLoaded/defer attribute. Add them to above.
dj_addNodeEvtHdlr(window, "load", function(){
	// allow multiple calls, only first one will take effect
	if(arguments.callee.initialized){ return; }
	arguments.callee.initialized = true;

	var initFunc = function(){
		//perform initialization
		if(dojo.render.html.ie){
			dojo.hostenv.makeWidgets();
		}
	};

	if(dojo.hostenv.inFlightCount == 0){
		initFunc();
		dojo.hostenv.modulesLoaded();
	}else{
		dojo.addOnLoad(initFunc);
	}
});

dj_addNodeEvtHdlr(window, "unload", function(){
	dojo.hostenv.unloaded();
});

dojo.hostenv.makeWidgets = function(){
	// you can put searchIds in djConfig and dojo.hostenv at the moment
	// we should probably eventually move to one or the other
	var sids = [];
	if(djConfig.searchIds && djConfig.searchIds.length > 0) {
		sids = sids.concat(djConfig.searchIds);
	}
	if(dojo.hostenv.searchIds && dojo.hostenv.searchIds.length > 0) {
		sids = sids.concat(dojo.hostenv.searchIds);
	}

	if((djConfig.parseWidgets)||(sids.length > 0)){
		if(dojo.evalObjPath("dojo.widget.Parse")){
			// we must do this on a delay to avoid:
			//	http://www.shaftek.org/blog/archives/000212.html
			// IE is such a tremendous peice of shit.
				var parser = new dojo.xml.Parse();
				if(sids.length > 0){
					for(var x=0; x<sids.length; x++){
						var tmpNode = document.getElementById(sids[x]);
						if(!tmpNode){ continue; }
						var frag = parser.parseElement(tmpNode, null, true);
						dojo.widget.getParser().createComponents(frag);
					}
				}else if(djConfig.parseWidgets){
					var frag  = parser.parseElement(document.getElementsByTagName("body")[0] || document.body, null, true);
					dojo.widget.getParser().createComponents(frag);
				}
		}
	}
}

dojo.addOnLoad(function(){
	if(!dojo.render.html.ie) {
		dojo.hostenv.makeWidgets();
	}
});

try {
	if (dojo.render.html.ie) {
		document.write('<style>v\:*{ behavior:url(#default#VML); }</style>');
		document.write('<xml:namespace ns="urn:schemas-microsoft-com:vml" prefix="v"/>');
	}
} catch (e) { }

// stub, over-ridden by debugging code. This will at least keep us from
// breaking when it's not included
dojo.hostenv.writeIncludes = function(){}

dojo.byId = function(id, doc){
	if(id && (typeof id == "string" || id instanceof String)){
		if(!doc){ doc = document; }
		return doc.getElementById(id);
	}
	return id; // assume it's a node
}

//Semicolon is for when this file is integrated with a custom build on one line
//with some other file's contents. Sometimes that makes things not get defined
//properly, particularly with the using the closure below to do all the work.
;(function(){
	//Don't do this work if dojo.js has already done it.
	if(typeof dj_usingBootstrap != "undefined"){
		return;
	}

	var isRhino = false;
	var isSpidermonkey = false;
	var isDashboard = false;
	if((typeof this["load"] == "function")&&((typeof this["Packages"] == "function")||(typeof this["Packages"] == "object"))){
		isRhino = true;
	}else if(typeof this["load"] == "function"){
		isSpidermonkey  = true;
	}else if(window.widget){
		isDashboard = true;
	}

	var tmps = [];
	if((this["djConfig"])&&((djConfig["isDebug"])||(djConfig["debugAtAllCosts"]))){
		tmps.push("debug.js");
	}

	if((this["djConfig"])&&(djConfig["debugAtAllCosts"])&&(!isRhino)&&(!isDashboard)){
		tmps.push("browser_debug.js");
	}

	//Support compatibility packages. Right now this only allows setting one
	//compatibility package. Might need to revisit later down the line to support
	//more than one.
	if((this["djConfig"])&&(djConfig["compat"])){
		tmps.push("compat/" + djConfig["compat"] + ".js");
	}

	var loaderRoot = djConfig["baseScriptUri"];
	if((this["djConfig"])&&(djConfig["baseLoaderUri"])){
		loaderRoot = djConfig["baseLoaderUri"];
	}

	for(var x=0; x < tmps.length; x++){
		var spath = loaderRoot+"src/"+tmps[x];
		if(isRhino||isSpidermonkey){
			load(spath);
		} else {
			try {
				document.write("<scr"+"ipt type='text/javascript' src='"+spath+"'></scr"+"ipt>");
			} catch (e) {
				var script = document.createElement("script");
				script.src = spath;
				document.getElementsByTagName("head")[0].appendChild(script);
			}
		}
	}
})();

// Localization routines

/**
 * The locale to look for string bundles if none are defined for your locale.  Translations for all strings
 * should be provided in this locale.
 */
//TODO: this really belongs in translation metadata, not in code
dojo.fallback_locale = 'en';

/**
 * Returns canonical form of locale, as used by Dojo.  All variants are case-insensitive and are separated by '-'
 * as specified in RFC 3066
 */
dojo.normalizeLocale = function(locale) {
	return locale ? locale.toLowerCase() : dojo.locale;
};

/**
 * requireLocalization() is for loading translated bundles provided within a package in the namespace.
 * Contents are typically strings, but may be any name/value pair, represented in JSON format.
 * A bundle is structured in a program as follows:
 *
 * <package>/
 *  nls/
 *   de/
 *    mybundle.js
 *   de-at/
 *    mybundle.js
 *   en/
 *    mybundle.js
 *   en-us/
 *    mybundle.js
 *   en-gb/
 *    mybundle.js
 *   es/
 *    mybundle.js
 *  ...etc
 *
 * where package is part of the namespace as used by dojo.require().  Each directory is named for a
 * locale as specified by RFC 3066, (http://www.ietf.org/rfc/rfc3066.txt), normalized in lowercase.
 *
 * For a given locale, string bundles will be loaded for that locale and all general locales above it, as well
 * as a system-specified fallback.  For example, "de_at" will also load "de" and "en".  Lookups will traverse
 * the locales in this order.  A build step can preload the bundles to avoid data redundancy and extra network hits.
 *
 * @param modulename package in which the bundle is found
 * @param bundlename bundle name, typically the filename without the '.js' suffix
 * @param locale the locale to load (optional)  By default, the browser's user locale as defined
 *	in dojo.locale
 */
dojo.requireLocalization = function(modulename, bundlename, locale /*optional*/){

	dojo.debug("EXPERIMENTAL: dojo.requireLocalization"); //dojo.experimental

	var syms = dojo.hostenv.getModuleSymbols(modulename);
	var modpath = syms.concat("nls").join("/");

	locale = dojo.normalizeLocale(locale);

	var elements = locale.split('-');
	var searchlist = [];
	for(var i = elements.length; i > 0; i--){
		searchlist.push(elements.slice(0, i).join('-'));
	}
	if(searchlist[searchlist.length-1] != dojo.fallback_locale){
		searchlist.push(dojo.fallback_locale);
	}

	var bundlepackage = [modulename, "_nls", bundlename].join(".");
	var bundle = dojo.hostenv.startPackage(bundlepackage);
	dojo.hostenv.loaded_modules_[bundlepackage] = bundle;
	
	var inherit = false;
	for(var i = searchlist.length - 1; i >= 0; i--){
		var loc = searchlist[i];
		var pkg = [bundlepackage, loc].join(".");
		var loaded = false;
		if(!dojo.hostenv.findModule(pkg)){
			// Mark loaded whether it's found or not, so that further load attempts will not be made
			dojo.hostenv.loaded_modules_[pkg] = null;

			var filespec = [modpath, loc, bundlename].join("/") + '.js';
			loaded = dojo.hostenv.loadPath(filespec, null, function(hash) {
 				bundle[loc] = hash;
 				if(inherit){
					// Use mixins approach to copy string references from inherit bundle, but skip overrides.
					for(var x in inherit){
						if(!bundle[loc][x]){
							bundle[loc][x] = inherit[x];
						}
					}
 				}
/*
				// Use prototype to point to other bundle, then copy in result from loadPath
				bundle[loc] = new function(){};
				if(inherit){ bundle[loc].prototype = inherit; }
				for(var i in hash){ bundle[loc][i] = hash[i]; }
*/
			});
		}else{
			loaded = true;
		}
		if(loaded && bundle[loc]){
			inherit = bundle[loc];
		}
	}
};

dojo.provide("dojo.string.common");

dojo.require("dojo.string");

/**
 * Trim whitespace from 'str'. If 'wh' > 0,
 * only trim from start, if 'wh' < 0, only trim
 * from end, otherwise trim both ends
 */
dojo.string.trim = function(str, wh){
	if(!str.replace){ return str; }
	if(!str.length){ return str; }
	var re = (wh > 0) ? (/^\s+/) : (wh < 0) ? (/\s+$/) : (/^\s+|\s+$/g);
	return str.replace(re, "");
}

/**
 * Trim whitespace at the beginning of 'str'
 */
dojo.string.trimStart = function(str) {
	return dojo.string.trim(str, 1);
}

/**
 * Trim whitespace at the end of 'str'
 */
dojo.string.trimEnd = function(str) {
	return dojo.string.trim(str, -1);
}

/**
 * Return 'str' repeated 'count' times, optionally
 * placing 'separator' between each rep
 */
dojo.string.repeat = function(str, count, separator) {
	var out = "";
	for(var i = 0; i < count; i++) {
		out += str;
		if(separator && i < count - 1) {
			out += separator;
		}
	}
	return out;
}

/**
 * Pad 'str' to guarantee that it is at least 'len' length
 * with the character 'c' at either the start (dir=1) or
 * end (dir=-1) of the string
 */
dojo.string.pad = function(str, len/*=2*/, c/*='0'*/, dir/*=1*/) {
	var out = String(str);
	if(!c) {
		c = '0';
	}
	if(!dir) {
		dir = 1;
	}
	while(out.length < len) {
		if(dir > 0) {
			out = c + out;
		} else {
			out += c;
		}
	}
	return out;
}

/** same as dojo.string.pad(str, len, c, 1) */
dojo.string.padLeft = function(str, len, c) {
	return dojo.string.pad(str, len, c, 1);
}

/** same as dojo.string.pad(str, len, c, -1) */
dojo.string.padRight = function(str, len, c) {
	return dojo.string.pad(str, len, c, -1);
}

dojo.provide("dojo.string");
dojo.require("dojo.string.common");

dojo.provide("dojo.lang.common");
dojo.require("dojo.lang");

/*
 * Adds the given properties/methods to the specified object
 */
dojo.lang._mixin = function(obj, props){
	var tobj = {};
	for(var x in props){
		// the "tobj" condition avoid copying properties in "props"
		// inherited from Object.prototype.  For example, if obj has a custom
		// toString() method, don't overwrite it with the toString() method
		// that props inherited from Object.protoype
		if(typeof tobj[x] == "undefined" || tobj[x] != props[x]) {
			obj[x] = props[x];
		}
	}
	// IE doesn't recognize custom toStrings in for..in
	if(dojo.render.html.ie && dojo.lang.isFunction(props["toString"]) && props["toString"] != obj["toString"]) {
		obj.toString = props.toString;
	}
	return obj;
}

/*
 * Adds the properties/methods of argument Objects to obj
 */
dojo.lang.mixin = function(obj, props /*, props, ..., props */){
	for(var i=1, l=arguments.length; i<l; i++){
		dojo.lang._mixin(obj, arguments[i]);
	}
	return obj;
}

/*
 * Adds the properties/methods of argument Objects to ctor's prototype
 */
dojo.lang.extend = function(ctor /*function*/, props /*, props, ..., props */){
	for(var i=1, l=arguments.length; i<l; i++){
		dojo.lang._mixin(ctor.prototype, arguments[i]);
	}
	return ctor;
}

/**
 * See if val is in arr. Call signatures:
 *  find(array, value, identity) // recommended
 *  find(value, array, identity)
**/
dojo.lang.find = function(	/*Array*/	arr, 
							/*Object*/	val,
							/*boolean*/	identity,
							/*boolean*/	findLast){
	// support both (arr, val) and (val, arr)
	if(!dojo.lang.isArrayLike(arr) && dojo.lang.isArrayLike(val)) {
		var a = arr;
		arr = val;
		val = a;
	}
	var isString = dojo.lang.isString(arr);
	if(isString) { arr = arr.split(""); }

	if(findLast) {
		var step = -1;
		var i = arr.length - 1;
		var end = -1;
	} else {
		var step = 1;
		var i = 0;
		var end = arr.length;
	}
	if(identity){
		while(i != end) {
			if(arr[i] === val){ return i; }
			i += step;
		}
	}else{
		while(i != end) {
			if(arr[i] == val){ return i; }
			i += step;
		}
	}
	return -1;
}

dojo.lang.indexOf = dojo.lang.find;

dojo.lang.findLast = function(/*Array*/ arr, /*Object*/ val, /*boolean*/ identity){
	return dojo.lang.find(arr, val, identity, true);
}

dojo.lang.lastIndexOf = dojo.lang.findLast;

dojo.lang.inArray = function(arr /*Array*/, val /*Object*/){
	return dojo.lang.find(arr, val) > -1; // return: boolean
}

/**
 * Partial implmentation of is* functions from
 * http://www.crockford.com/javascript/recommend.html
 * NOTE: some of these may not be the best thing to use in all situations
 * as they aren't part of core JS and therefore can't work in every case.
 * See WARNING messages inline for tips.
 *
 * The following is* functions are fairly "safe"
 */

dojo.lang.isObject = function(wh){
	if(typeof wh == "undefined"){ return false; }
	return (typeof wh == "object" || wh === null || dojo.lang.isArray(wh) || dojo.lang.isFunction(wh));
}

dojo.lang.isArray = function(wh){
	return (wh instanceof Array || typeof wh == "array");
}

dojo.lang.isArrayLike = function(wh){
	if(dojo.lang.isString(wh)){ return false; }
	if(dojo.lang.isFunction(wh)){ return false; } // keeps out built-in ctors (Number, String, ...) which have length properties
	if(dojo.lang.isArray(wh)){ return true; }
	if(typeof wh != "undefined" && wh
		&& dojo.lang.isNumber(wh.length) && isFinite(wh.length)){ return true; }
	return false;
}

dojo.lang.isFunction = function(wh){
	if(!wh){ return false; }
	return (wh instanceof Function || typeof wh == "function");
}

dojo.lang.isString = function(wh){
	return (wh instanceof String || typeof wh == "string");
}

dojo.lang.isAlien = function(wh){
	if(!wh){ return false; }
	return !dojo.lang.isFunction() && /\{\s*\[native code\]\s*\}/.test(String(wh));
}

dojo.lang.isBoolean = function(wh){
	return (wh instanceof Boolean || typeof wh == "boolean");
}

/**
 * The following is***() functions are somewhat "unsafe". Fortunately,
 * there are workarounds the the language provides and are mentioned
 * in the WARNING messages.
 *
 * WARNING: In most cases, isNaN(wh) is sufficient to determine whether or not
 * something is a number or can be used as such. For example, a number or string
 * can be used interchangably when accessing array items (arr["1"] is the same as
 * arr[1]) and isNaN will return false for both values ("1" and 1). Should you
 * use isNumber("1"), that will return false, which is generally not too useful.
 * Also, isNumber(NaN) returns true, again, this isn't generally useful, but there
 * are corner cases (like when you want to make sure that two things are really
 * the same type of thing). That is really where isNumber "shines".
 *
 * RECOMMENDATION: Use isNaN(wh) when possible
 */
dojo.lang.isNumber = function(wh){
	return (wh instanceof Number || typeof wh == "number");
}

/**
 * WARNING: In some cases, isUndefined will not behave as you
 * might expect. If you do isUndefined(foo) and there is no earlier
 * reference to foo, an error will be thrown before isUndefined is
 * called. It behaves correctly if you scope yor object first, i.e.
 * isUndefined(foo.bar) where foo is an object and bar isn't a
 * property of the object.
 *
 * RECOMMENDATION: Use `typeof foo == "undefined"` when possible
 *
 * FIXME: Should isUndefined go away since it is error prone?
 */
dojo.lang.isUndefined = function(wh){
	return ((wh == undefined)&&(typeof wh == "undefined"));
}

// end Crockford functions

dojo.provide("dojo.lang.extras");

dojo.require("dojo.lang.common");

/**
 * Sets a timeout in milliseconds to execute a function in a given context
 * with optional arguments.
 *
 * setTimeout (Object context, function func, number delay[, arg1[, ...]]);
 * setTimeout (function func, number delay[, arg1[, ...]]);
 */
dojo.lang.setTimeout = function(func, delay){
	var context = window, argsStart = 2;
	if(!dojo.lang.isFunction(func)){
		context = func;
		func = delay;
		delay = arguments[2];
		argsStart++;
	}

	if(dojo.lang.isString(func)){
		func = context[func];
	}
	
	var args = [];
	for (var i = argsStart; i < arguments.length; i++) {
		args.push(arguments[i]);
	}
	return setTimeout(function () { func.apply(context, args); }, delay);
}

dojo.lang.getNameInObj = function(ns, item){
	if(!ns){ ns = dj_global; }

	for(var x in ns){
		if(ns[x] === item){
			return new String(x);
		}
	}
	return null;
}

dojo.lang.shallowCopy = function(obj) {
	var ret = {}, key;
	for(key in obj) {
		if(dojo.lang.isUndefined(ret[key])) {
			ret[key] = obj[key];
		}
	}
	return ret;
}

/**
 * Return the first argument that isn't undefined
 */
dojo.lang.firstValued = function(/* ... */) {
	for(var i = 0; i < arguments.length; i++) {
		if(typeof arguments[i] != "undefined") {
			return arguments[i];
		}
	}
	return undefined;
}

/**
 * Get a value from a reference specified as a string descriptor,
 * (e.g. "A.B") in the given context.
 * 
 * getObjPathValue(String objpath [, Object context, Boolean create])
 *
 * If context is not specified, dj_global is used
 * If create is true, undefined objects in the path are created.
 */
dojo.lang.getObjPathValue = function(objpath, context, create){
	with(dojo.parseObjPath(objpath, context, create)){
		return dojo.evalProp(prop, obj, create);
	}
}

/**
 * Set a value on a reference specified as a string descriptor. 
 * (e.g. "A.B") in the given context.
 * 
 * setObjPathValue(String objpath, value [, Object context, Boolean create])
 *
 * If context is not specified, dj_global is used
 * If create is true, undefined objects in the path are created.
 */
dojo.lang.setObjPathValue = function(objpath, value, context, create){
	if(arguments.length < 4){
		create = true;
	}
	with(dojo.parseObjPath(objpath, context, create)){
		if(obj && (create || (prop in obj))){
			obj[prop] = value;
		}
	}
}

dojo.provide("dojo.io.IO");
dojo.require("dojo.string");
dojo.require("dojo.lang.extras");

/******************************************************************************
 *	Notes about dojo.io design:
 *	
 *	The dojo.io.* package has the unenviable task of making a lot of different
 *	types of I/O feel natural, despite a universal lack of good (or even
 *	reasonable!) I/O capability in the host environment. So lets pin this down
 *	a little bit further.
 *
 *	Rhino:
 *		perhaps the best situation anywhere. Access to Java classes allows you
 *		to do anything one might want in terms of I/O, both synchronously and
 *		async. Can open TCP sockets and perform low-latency client/server
 *		interactions. HTTP transport is available through Java HTTP client and
 *		server classes. Wish it were always this easy.
 *
 *	xpcshell:
 *		XPCOM for I/O. A cluster-fuck to be sure.
 *
 *	spidermonkey:
 *		S.O.L.
 *
 *	Browsers:
 *		Browsers generally do not provide any useable filesystem access. We are
 *		therefore limited to HTTP for moving information to and from Dojo
 *		instances living in a browser.
 *
 *		XMLHTTP:
 *			Sync or async, allows reading of arbitrary text files (including
 *			JS, which can then be eval()'d), writing requires server
 *			cooperation and is limited to HTTP mechanisms (POST and GET).
 *
 *		<iframe> hacks:
 *			iframe document hacks allow browsers to communicate asynchronously
 *			with a server via HTTP POST and GET operations. With significant
 *			effort and server cooperation, low-latency data transit between
 *			client and server can be acheived via iframe mechanisms (repubsub).
 *
 *		SVG:
 *			Adobe's SVG viewer implements helpful primitives for XML-based
 *			requests, but receipt of arbitrary text data seems unlikely w/o
 *			<![CDATA[]]> sections.
 *
 *
 *	A discussion between Dylan, Mark, Tom, and Alex helped to lay down a lot
 *	the IO API interface. A transcript of it can be found at:
 *		http://dojotoolkit.org/viewcvs/viewcvs.py/documents/irc/irc_io_api_log.txt?rev=307&view=auto
 *	
 *	Also referenced in the design of the API was the DOM 3 L&S spec:
 *		http://www.w3.org/TR/2004/REC-DOM-Level-3-LS-20040407/load-save.html
 ******************************************************************************/

// a map of the available transport options. Transports should add themselves
// by calling add(name)
dojo.io.transports = [];
dojo.io.hdlrFuncNames = [ "load", "error", "timeout" ]; // we're omitting a progress() event for now

dojo.io.Request = function(url, mimetype, transport, changeUrl){
	if((arguments.length == 1)&&(arguments[0].constructor == Object)){
		this.fromKwArgs(arguments[0]);
	}else{
		this.url = url;
		if(mimetype){ this.mimetype = mimetype; }
		if(transport){ this.transport = transport; }
		if(arguments.length >= 4){ this.changeUrl = changeUrl; }
	}
}

dojo.lang.extend(dojo.io.Request, {

	/** The URL to hit */
	url: "",
	
	/** The mime type used to interrpret the response body */
	mimetype: "text/plain",
	
	/** The HTTP method to use */
	method: "GET",
	
	/** An Object containing key-value pairs to be included with the request */
	content: undefined, // Object
	
	/** The transport medium to use */
	transport: undefined, // String
	
	/** If defined the URL of the page is physically changed */
	changeUrl: undefined, // String
	
	/** A form node to use in the request */
	formNode: undefined, // HTMLFormElement
	
	/** Whether the request should be made synchronously */
	sync: false,
	
	bindSuccess: false,

	/** Cache/look for the request in the cache before attempting to request?
	 *  NOTE: this isn't a browser cache, this is internal and would only cache in-page
	 */
	useCache: false,

	/** Prevent the browser from caching this by adding a query string argument to the URL */
	preventCache: false,
	
	// events stuff
	load: function(type, data, evt){ },
	error: function(type, error){ },
	timeout: function(type){ },
	handle: function(){ },

	//FIXME: change BrowserIO.js to use timeouts? IframeIO?
	// The number of seconds to wait until firing a timeout callback.
	// If it is zero, that means, don't do a timeout check.
	timeoutSeconds: 0,
	
	// the abort method needs to be filled in by the transport that accepts the
	// bind() request
	abort: function(){ },
	
	// backButton: function(){ },
	// forwardButton: function(){ },

	fromKwArgs: function(kwArgs){
		// normalize args
		if(kwArgs["url"]){ kwArgs.url = kwArgs.url.toString(); }
		if(kwArgs["formNode"]) { kwArgs.formNode = dojo.byId(kwArgs.formNode); }
		if(!kwArgs["method"] && kwArgs["formNode"] && kwArgs["formNode"].method) {
			kwArgs.method = kwArgs["formNode"].method;
		}
		
		// backwards compatibility
		if(!kwArgs["handle"] && kwArgs["handler"]){ kwArgs.handle = kwArgs.handler; }
		if(!kwArgs["load"] && kwArgs["loaded"]){ kwArgs.load = kwArgs.loaded; }
		if(!kwArgs["changeUrl"] && kwArgs["changeURL"]) { kwArgs.changeUrl = kwArgs.changeURL; }

		// encoding fun!
		kwArgs.encoding = dojo.lang.firstValued(kwArgs["encoding"], djConfig["bindEncoding"], "");

		kwArgs.sendTransport = dojo.lang.firstValued(kwArgs["sendTransport"], djConfig["ioSendTransport"], false);

		var isFunction = dojo.lang.isFunction;
		for(var x=0; x<dojo.io.hdlrFuncNames.length; x++){
			var fn = dojo.io.hdlrFuncNames[x];
			if(isFunction(kwArgs[fn])){ continue; }
			if(isFunction(kwArgs["handle"])){
				kwArgs[fn] = kwArgs.handle;
			}
			// handler is aliased above, shouldn't need this check
			/* else if(dojo.lang.isObject(kwArgs.handler)){
				if(isFunction(kwArgs.handler[fn])){
					kwArgs[fn] = kwArgs.handler[fn]||kwArgs.handler["handle"]||function(){};
				}
			}*/
		}
		dojo.lang.mixin(this, kwArgs);
	}

});

dojo.io.Error = function(msg, type, num){
	this.message = msg;
	this.type =  type || "unknown"; // must be one of "io", "parse", "unknown"
	this.number = num || 0; // per-substrate error number, not normalized
}

dojo.io.transports.addTransport = function(name){
	this.push(name);
	// FIXME: do we need to handle things that aren't direct children of the
	// dojo.io namespace? (say, dojo.io.foo.fooTransport?)
	this[name] = dojo.io[name];
}

// binding interface, the various implementations register their capabilities
// and the bind() method dispatches
dojo.io.bind = function(request){
	// if the request asks for a particular implementation, use it
	if(!(request instanceof dojo.io.Request)){
		try{
			request = new dojo.io.Request(request);
		}catch(e){ dojo.debug(e); }
	}
	var tsName = "";
	if(request["transport"]){
		tsName = request["transport"];
		// FIXME: it would be good to call the error handler, although we'd
		// need to use setTimeout or similar to accomplish this and we can't
		// garuntee that this facility is available.
		if(!this[tsName]){ return request; }
	}else{
		// otherwise we do our best to auto-detect what available transports
		// will handle 
		for(var x=0; x<dojo.io.transports.length; x++){
			var tmp = dojo.io.transports[x];
			if((this[tmp])&&(this[tmp].canHandle(request))){
				tsName = tmp;
			}
		}
		if(tsName == ""){ return request; }
	}
	this[tsName].bind(request);
	request.bindSuccess = true;
	return request;
}

dojo.io.queueBind = function(request){
	if(!(request instanceof dojo.io.Request)){
		try{
			request = new dojo.io.Request(request);
		}catch(e){ dojo.debug(e); }
	}

	// make sure we get called if/when we get a response
	var oldLoad = request.load;
	request.load = function(){
		dojo.io._queueBindInFlight = false;
		var ret = oldLoad.apply(this, arguments);
		dojo.io._dispatchNextQueueBind();
		return ret;
	}

	var oldErr = request.error;
	request.error = function(){
		dojo.io._queueBindInFlight = false;
		var ret = oldErr.apply(this, arguments);
		dojo.io._dispatchNextQueueBind();
		return ret;
	}

	dojo.io._bindQueue.push(request);
	dojo.io._dispatchNextQueueBind();
	return request;
}

dojo.io._dispatchNextQueueBind = function(){
	if(!dojo.io._queueBindInFlight){
		dojo.io._queueBindInFlight = true;
		if(dojo.io._bindQueue.length > 0){
			dojo.io.bind(dojo.io._bindQueue.shift());
		}else{
			dojo.io._queueBindInFlight = false;
		}
	}
}
dojo.io._bindQueue = [];
dojo.io._queueBindInFlight = false;

dojo.io.argsFromMap = function(map, encoding, last){
	var enc = /utf/i.test(encoding||"") ? encodeURIComponent : dojo.string.encodeAscii;
	var mapped = [];
	var control = new Object();
	for(var name in map){
		var domap = function(elt){
			var val = enc(name)+"="+enc(elt);
			mapped[(last == name) ? "push" : "unshift"](val);
		}
		if(!control[name]){
			var value = map[name];
			// FIXME: should be isArrayLike?
			if (dojo.lang.isArray(value)){
				dojo.lang.forEach(value, domap);
			}else{
				domap(value);
			}
		}
	}
	return mapped.join("&");
}

dojo.io.setIFrameSrc = function(iframe, src, replace){
	try{
		var r = dojo.render.html;
		// dojo.debug(iframe);
		if(!replace){
			if(r.safari){
				iframe.location = src;
			}else{
				frames[iframe.name].location = src;
			}
		}else{
			// Fun with DOM 0 incompatibilities!
			var idoc;
			if(r.ie){
				idoc = iframe.contentWindow.document;
			}else if(r.safari){
				idoc = iframe.document;
			}else{ //  if(r.moz){
				idoc = iframe.contentWindow;
			}

			//For Safari (at least 2.0.3) and Opera, if the iframe
			//has just been created but it doesn't have content
			//yet, then iframe.document may be null. In that case,
			//use iframe.location and return.
			if(!idoc){
				iframe.location = src;
				return;
			}else{
				idoc.location.replace(src);
			}
		}
	}catch(e){ 
		dojo.debug(e); 
		dojo.debug("setIFrameSrc: "+e); 
	}
}

/*
dojo.io.sampleTranport = new function(){
	this.canHandle = function(kwArgs){
		// canHandle just tells dojo.io.bind() if this is a good transport to
		// use for the particular type of request.
		if(	
			(
				(kwArgs["mimetype"] == "text/plain") ||
				(kwArgs["mimetype"] == "text/html") ||
				(kwArgs["mimetype"] == "text/javascript")
			)&&(
				(kwArgs["method"] == "get") ||
				( (kwArgs["method"] == "post") && (!kwArgs["formNode"]) )
			)
		){
			return true;
		}

		return false;
	}

	this.bind = function(kwArgs){
		var hdlrObj = {};

		// set up a handler object
		for(var x=0; x<dojo.io.hdlrFuncNames.length; x++){
			var fn = dojo.io.hdlrFuncNames[x];
			if(typeof kwArgs.handler == "object"){
				if(typeof kwArgs.handler[fn] == "function"){
					hdlrObj[fn] = kwArgs.handler[fn]||kwArgs.handler["handle"];
				}
			}else if(typeof kwArgs[fn] == "function"){
				hdlrObj[fn] = kwArgs[fn];
			}else{
				hdlrObj[fn] = kwArgs["handle"]||function(){};
			}
		}

		// build a handler function that calls back to the handler obj
		var hdlrFunc = function(evt){
			if(evt.type == "onload"){
				hdlrObj.load("load", evt.data, evt);
			}else if(evt.type == "onerr"){
				var errObj = new dojo.io.Error("sampleTransport Error: "+evt.msg);
				hdlrObj.error("error", errObj);
			}
		}

		// the sample transport would attach the hdlrFunc() when sending the
		// request down the pipe at this point
		var tgtURL = kwArgs.url+"?"+dojo.io.argsFromMap(kwArgs.content);
		// sampleTransport.sendRequest(tgtURL, hdlrFunc);
	}

	dojo.io.transports.addTransport("sampleTranport");
}
*/

dojo.provide("dojo.lang.array");

dojo.require("dojo.lang.common");

// FIXME: Is this worthless since you can do: if(name in obj)
// is this the right place for this?
dojo.lang.has = function(obj, name){
	try{
		return (typeof obj[name] != "undefined");
	}catch(e){ return false; }
}

dojo.lang.isEmpty = function(obj) {
	if(dojo.lang.isObject(obj)) {
		var tmp = {};
		var count = 0;
		for(var x in obj){
			if(obj[x] && (!tmp[x])){
				count++;
				break;
			} 
		}
		return (count == 0);
	} else if(dojo.lang.isArrayLike(obj) || dojo.lang.isString(obj)) {
		return obj.length == 0;
	}
}

dojo.lang.map = function(arr, obj, unary_func){
	var isString = dojo.lang.isString(arr);
	if(isString){
		arr = arr.split("");
	}
	if(dojo.lang.isFunction(obj)&&(!unary_func)){
		unary_func = obj;
		obj = dj_global;
	}else if(dojo.lang.isFunction(obj) && unary_func){
		// ff 1.5 compat
		var tmpObj = obj;
		obj = unary_func;
		unary_func = tmpObj;
	}
	if(Array.map){
	 	var outArr = Array.map(arr, unary_func, obj);
	}else{
		var outArr = [];
		for(var i=0;i<arr.length;++i){
			outArr.push(unary_func.call(obj, arr[i]));
		}
	}
	if(isString) {
		return outArr.join("");
	} else {
		return outArr;
	}
}

// http://developer.mozilla.org/en/docs/Core_JavaScript_1.5_Reference:Global_Objects:Array:forEach
dojo.lang.forEach = function(anArray /* Array */, callback /* Function */, thisObject /* Object */){
	if(dojo.lang.isString(anArray)){ 
		anArray = anArray.split(""); 
	}
	if(Array.forEach){
		Array.forEach(anArray, callback, thisObject);
	}else{
		// FIXME: there are several ways of handilng thisObject. Is dj_global always the default context?
		if(!thisObject){
			thisObject=dj_global;
		}
		for(var i=0,l=anArray.length; i<l; i++){ 
			callback.call(thisObject, anArray[i], i, anArray);
		}
	}
}

dojo.lang._everyOrSome = function(every, arr, callback, thisObject){
	if(dojo.lang.isString(arr)){ 
		arr = arr.split(""); 
	}
	if(Array.every){
		return Array[ (every) ? "every" : "some" ](arr, callback, thisObject);
	}else{
		if(!thisObject){
			thisObject = dj_global;
		}
		for(var i=0,l=arr.length; i<l; i++){
			var result = callback.call(thisObject, arr[i], i, arr);
			if((every)&&(!result)){
				return false;
			}else if((!every)&&(result)){
				return true;
			}
		}
		return (every) ? true : false;
	}
}

dojo.lang.every = function(arr, callback, thisObject){
	return this._everyOrSome(true, arr, callback, thisObject);
}

dojo.lang.some = function(arr, callback, thisObject){
	return this._everyOrSome(false, arr, callback, thisObject);
}

dojo.lang.filter = function(arr, callback, thisObject) {
	var isString = dojo.lang.isString(arr);
	if(isString) { arr = arr.split(""); }
	if(Array.filter) {
		var outArr = Array.filter(arr, callback, thisObject);
	} else {
		if(!thisObject) {
			if(arguments.length >= 3) { dojo.raise("thisObject doesn't exist!"); }
			thisObject = dj_global;
		}

		var outArr = [];
		for(var i = 0; i < arr.length; i++) {
			if(callback.call(thisObject, arr[i], i, arr)) {
				outArr.push(arr[i]);
			}
		}
	}
	if(isString) {
		return outArr.join("");
	} else {
		return outArr;
	}
}

/**
 * Creates a 1-D array out of all the arguments passed,
 * unravelling any array-like objects in the process
 *
 * Ex:
 * unnest(1, 2, 3) ==> [1, 2, 3]
 * unnest(1, [2, [3], [[[4]]]]) ==> [1, 2, 3, 4]
 */
dojo.lang.unnest = function(/* ... */) {
	var out = [];
	for(var i = 0; i < arguments.length; i++) {
		if(dojo.lang.isArrayLike(arguments[i])) {
			var add = dojo.lang.unnest.apply(this, arguments[i]);
			out = out.concat(add);
		} else {
			out.push(arguments[i]);
		}
	}
	return out;
}

/**
 * Converts an array-like object (i.e. arguments, DOMCollection)
 * to an array
**/
dojo.lang.toArray = function(arrayLike, startOffset) {
	var array = [];
	for(var i = startOffset||0; i < arrayLike.length; i++) {
		array.push(arrayLike[i]);
	}
	return array;
}

dojo.provide("dojo.lang.func");

dojo.require("dojo.lang.common");

/**
 * Runs a function in a given scope (thisObject), can
 * also be used to preserve scope.
 *
 * hitch(foo, "bar"); // runs foo.bar() in the scope of foo
 * hitch(foo, myFunction); // runs myFunction in the scope of foo
 */
dojo.lang.hitch = function(thisObject, method) {
	if(dojo.lang.isString(method)) {
		var fcn = thisObject[method];
	} else {
		var fcn = method;
	}

	return function() {
		return fcn.apply(thisObject, arguments);
	}
}

dojo.lang.anonCtr = 0;
dojo.lang.anon = {};
dojo.lang.nameAnonFunc = function(anonFuncPtr, namespaceObj, searchForNames){
	var nso = (namespaceObj || dojo.lang.anon);
	if( (searchForNames) ||
		((dj_global["djConfig"])&&(djConfig["slowAnonFuncLookups"] == true)) ){
		for(var x in nso){
			if(nso[x] === anonFuncPtr){
				return x;
			}
		}
	}
	var ret = "__"+dojo.lang.anonCtr++;
	while(typeof nso[ret] != "undefined"){
		ret = "__"+dojo.lang.anonCtr++;
	}
	nso[ret] = anonFuncPtr;
	return ret;
}

dojo.lang.forward = function(funcName){
	// Returns a function that forwards a method call to this.func(...)
	return function(){
		return this[funcName].apply(this, arguments);
	};
}

dojo.lang.curry = function(ns, func /* args ... */){
	var outerArgs = [];
	ns = ns||dj_global;
	if(dojo.lang.isString(func)){
		func = ns[func];
	}
	for(var x=2; x<arguments.length; x++){
		outerArgs.push(arguments[x]);
	}
	// since the event system replaces the original function with a new
	// join-point runner with an arity of 0, we check to see if it's left us
	// any clues about the original arity in lieu of the function's actual
	// length property
	var ecount = (func["__preJoinArity"]||func.length) - outerArgs.length;
	// borrowed from svend tofte
	function gather(nextArgs, innerArgs, expected){
		var texpected = expected;
		var totalArgs = innerArgs.slice(0); // copy
		for(var x=0; x<nextArgs.length; x++){
			totalArgs.push(nextArgs[x]);
		}
		// check the list of provided nextArgs to see if it, plus the
		// number of innerArgs already supplied, meets the total
		// expected.
		expected = expected-nextArgs.length;
		if(expected<=0){
			var res = func.apply(ns, totalArgs);
			expected = texpected;
			return res;
		}else{
			return function(){
				return gather(arguments,// check to see if we've been run
										// with enough args
							totalArgs,	// a copy
							expected);	// how many more do we need to run?;
			}
		}
	}
	return gather([], outerArgs, ecount);
}

dojo.lang.curryArguments = function(ns, func, args, offset){
	var targs = [];
	var x = offset||0;
	for(x=offset; x<args.length; x++){
		targs.push(args[x]); // ensure that it's an arr
	}
	return dojo.lang.curry.apply(dojo.lang, [ns, func].concat(targs));
}

dojo.lang.tryThese = function(){
	for(var x=0; x<arguments.length; x++){
		try{
			if(typeof arguments[x] == "function"){
				var ret = (arguments[x]());
				if(ret){
					return ret;
				}
			}
		}catch(e){
			dojo.debug(e);
		}
	}
}

dojo.lang.delayThese = function(farr, cb, delay, onend){
	/**
	 * alternate: (array funcArray, function callback, function onend)
	 * alternate: (array funcArray, function callback)
	 * alternate: (array funcArray)
	 */
	if(!farr.length){ 
		if(typeof onend == "function"){
			onend();
		}
		return;
	}
	if((typeof delay == "undefined")&&(typeof cb == "number")){
		delay = cb;
		cb = function(){};
	}else if(!cb){
		cb = function(){};
		if(!delay){ delay = 0; }
	}
	setTimeout(function(){
		(farr.shift())();
		cb();
		dojo.lang.delayThese(farr, cb, delay, onend);
	}, delay);
}

dojo.provide("dojo.string.extras");

dojo.require("dojo.string.common");
dojo.require("dojo.lang");

/**
 * Performs parameterized substitutions on a string.  For example,
 *   dojo.string.substituteParams("File '%{0}' is not found in directory '%{1}'.","foo.html","/temp");
 * returns
 *   "File 'foo.html' is not found in directory '/temp'."
 * 
 * @param template the original string template with %{values} to be replaced
 * @param hash name/value pairs (type object) to provide substitutions.  Alternatively, substitutions may be
 *  included as arguments 1..n to this function, corresponding to template parameters 0..n-1
 * @return the completed string. Throws an exception if any parameter is unmatched
 */
//TODO: use ${} substitution syntax instead, like widgets do?
dojo.string.substituteParams = function(template /*string */, hash /* object - optional or ... */) {
	var map = (typeof hash == 'object') ? hash : dojo.lang.toArray(arguments, 1);

	return template.replace(/\%\{(\w+)\}/g, function(match, key){
		return map[key] || dojo.raise("Substitution not found: " + key);
	});
};

/**
 * Parameterized string function
 * str - formatted string with %{values} to be replaces
 * pairs - object of name: "value" value pairs
 * killExtra - remove all remaining %{values} after pairs are inserted
 */
dojo.string.paramString = function(str, pairs, killExtra) {
	dojo.deprecated("dojo.string.paramString",
		"use dojo.string.substituteParams instead", "0.4");

	for(var name in pairs) {
		var re = new RegExp("\\%\\{" + name + "\\}", "g");
		str = str.replace(re, pairs[name]);
	}

	if(killExtra) { str = str.replace(/%\{([^\}\s]+)\}/g, ""); }
	return str;
}

/** Uppercases the first letter of each word */
dojo.string.capitalize = function (str) {
	if (!dojo.lang.isString(str)) { return ""; }
	if (arguments.length == 0) { str = this; }

	var words = str.split(' ');
	for(var i=0; i<words.length; i++){
		words[i] = words[i].charAt(0).toUpperCase() + words[i].substring(1);
	}
	return words.join(" ");
}

/**
 * Return true if the entire string is whitespace characters
 */
dojo.string.isBlank = function (str) {
	if(!dojo.lang.isString(str)) { return true; }
	return (dojo.string.trim(str).length == 0);
}

dojo.string.encodeAscii = function(str) {
	if(!dojo.lang.isString(str)) { return str; }
	var ret = "";
	var value = escape(str);
	var match, re = /%u([0-9A-F]{4})/i;
	while((match = value.match(re))) {
		var num = Number("0x"+match[1]);
		var newVal = escape("&#" + num + ";");
		ret += value.substring(0, match.index) + newVal;
		value = value.substring(match.index+match[0].length);
	}
	ret += value.replace(/\+/g, "%2B");
	return ret;
}

dojo.string.escape = function(type, str) {
	var args = dojo.lang.toArray(arguments, 1);
	switch(type.toLowerCase()) {
		case "xml":
		case "html":
		case "xhtml":
			return dojo.string.escapeXml.apply(this, args);
		case "sql":
			return dojo.string.escapeSql.apply(this, args);
		case "regexp":
		case "regex":
			return dojo.string.escapeRegExp.apply(this, args);
		case "javascript":
		case "jscript":
		case "js":
			return dojo.string.escapeJavaScript.apply(this, args);
		case "ascii":
			// so it's encode, but it seems useful
			return dojo.string.encodeAscii.apply(this, args);
		default:
			return str;
	}
}

dojo.string.escapeXml = function(str, noSingleQuotes) {
	str = str.replace(/&/gm, "&amp;").replace(/</gm, "&lt;")
		.replace(/>/gm, "&gt;").replace(/"/gm, "&quot;");
	if(!noSingleQuotes) { str = str.replace(/'/gm, "&#39;"); }
	return str;
}

dojo.string.escapeSql = function(str) {
	return str.replace(/'/gm, "''");
}

dojo.string.escapeRegExp = function(str) {
	return str.replace(/\\/gm, "\\\\").replace(/([\f\b\n\t\r[\^$|?*+(){}])/gm, "\\$1");
}

dojo.string.escapeJavaScript = function(str) {
	return str.replace(/(["'\f\b\n\t\r])/gm, "\\$1");
}

dojo.string.escapeString = function(str){ 
	return ('"' + str.replace(/(["\\])/g, '\\$1') + '"'
		).replace(/[\f]/g, "\\f"
		).replace(/[\b]/g, "\\b"
		).replace(/[\n]/g, "\\n"
		).replace(/[\t]/g, "\\t"
		).replace(/[\r]/g, "\\r");
}

// TODO: make an HTML version
dojo.string.summary = function(str, len) {
	if(!len || str.length <= len) {
		return str;
	} else {
		return str.substring(0, len).replace(/\.+$/, "") + "...";
	}
}

/**
 * Returns true if 'str' ends with 'end'
 */
dojo.string.endsWith = function(str, end, ignoreCase) {
	if(ignoreCase) {
		str = str.toLowerCase();
		end = end.toLowerCase();
	}
	if((str.length - end.length) < 0){
		return false;
	}
	return str.lastIndexOf(end) == str.length - end.length;
}

/**
 * Returns true if 'str' ends with any of the arguments[2 -> n]
 */
dojo.string.endsWithAny = function(str /* , ... */) {
	for(var i = 1; i < arguments.length; i++) {
		if(dojo.string.endsWith(str, arguments[i])) {
			return true;
		}
	}
	return false;
}

/**
 * Returns true if 'str' starts with 'start'
 */
dojo.string.startsWith = function(str, start, ignoreCase) {
	if(ignoreCase) {
		str = str.toLowerCase();
		start = start.toLowerCase();
	}
	return str.indexOf(start) == 0;
}

/**
 * Returns true if 'str' starts with any of the arguments[2 -> n]
 */
dojo.string.startsWithAny = function(str /* , ... */) {
	for(var i = 1; i < arguments.length; i++) {
		if(dojo.string.startsWith(str, arguments[i])) {
			return true;
		}
	}
	return false;
}

/**
 * Returns true if 'str' contains any of the arguments 2 -> n
 */
dojo.string.has = function(str /* , ... */) {
	for(var i = 1; i < arguments.length; i++) {
		if(str.indexOf(arguments[i]) > -1){
			return true;
		}
	}
	return false;
}

dojo.string.normalizeNewlines = function (text,newlineChar) {
	if (newlineChar == "\n") {
		text = text.replace(/\r\n/g, "\n");
		text = text.replace(/\r/g, "\n");
	} else if (newlineChar == "\r") {
		text = text.replace(/\r\n/g, "\r");
		text = text.replace(/\n/g, "\r");
	} else {
		text = text.replace(/([^\r])\n/g, "$1\r\n");
		text = text.replace(/\r([^\n])/g, "\r\n$1");
	}
	return text;
}

dojo.string.splitEscaped = function (str,charac) {
	var components = [];
	for (var i = 0, prevcomma = 0; i < str.length; i++) {
		if (str.charAt(i) == '\\') { i++; continue; }
		if (str.charAt(i) == charac) {
			components.push(str.substring(prevcomma, i));
			prevcomma = i + 1;
		}
	}
	components.push(str.substr(prevcomma));
	return components;
}

dojo.provide("dojo.dom");
dojo.require("dojo.lang.array");

dojo.dom.ELEMENT_NODE                  = 1;
dojo.dom.ATTRIBUTE_NODE                = 2;
dojo.dom.TEXT_NODE                     = 3;
dojo.dom.CDATA_SECTION_NODE            = 4;
dojo.dom.ENTITY_REFERENCE_NODE         = 5;
dojo.dom.ENTITY_NODE                   = 6;
dojo.dom.PROCESSING_INSTRUCTION_NODE   = 7;
dojo.dom.COMMENT_NODE                  = 8;
dojo.dom.DOCUMENT_NODE                 = 9;
dojo.dom.DOCUMENT_TYPE_NODE            = 10;
dojo.dom.DOCUMENT_FRAGMENT_NODE        = 11;
dojo.dom.NOTATION_NODE                 = 12;
	
dojo.dom.dojoml = "http://www.dojotoolkit.org/2004/dojoml";

/**
 *	comprehensive list of XML namespaces
**/
dojo.dom.xmlns = {
	svg : "http://www.w3.org/2000/svg",
	smil : "http://www.w3.org/2001/SMIL20/",
	mml : "http://www.w3.org/1998/Math/MathML",
	cml : "http://www.xml-cml.org",
	xlink : "http://www.w3.org/1999/xlink",
	xhtml : "http://www.w3.org/1999/xhtml",
	xul : "http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul",
	xbl : "http://www.mozilla.org/xbl",
	fo : "http://www.w3.org/1999/XSL/Format",
	xsl : "http://www.w3.org/1999/XSL/Transform",
	xslt : "http://www.w3.org/1999/XSL/Transform",
	xi : "http://www.w3.org/2001/XInclude",
	xforms : "http://www.w3.org/2002/01/xforms",
	saxon : "http://icl.com/saxon",
	xalan : "http://xml.apache.org/xslt",
	xsd : "http://www.w3.org/2001/XMLSchema",
	dt: "http://www.w3.org/2001/XMLSchema-datatypes",
	xsi : "http://www.w3.org/2001/XMLSchema-instance",
	rdf : "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
	rdfs : "http://www.w3.org/2000/01/rdf-schema#",
	dc : "http://purl.org/dc/elements/1.1/",
	dcq: "http://purl.org/dc/qualifiers/1.0",
	"soap-env" : "http://schemas.xmlsoap.org/soap/envelope/",
	wsdl : "http://schemas.xmlsoap.org/wsdl/",
	AdobeExtensions : "http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/"
};

dojo.dom.isNode = function(wh){
	if(typeof Element == "object") {
		try {
			return wh instanceof Element;
		} catch(E) {}
	} else {
		// best-guess
		return wh && !isNaN(wh.nodeType);
	}
}

dojo.dom.getTagName = function(node){
	dojo.deprecated("dojo.dom.getTagName", "use node.tagName instead", "0.4");

	var tagName = node.tagName;
	if(tagName.substr(0,5).toLowerCase()!="dojo:"){
		
		if(tagName.substr(0,4).toLowerCase()=="dojo"){
			// FIXME: this assuumes tag names are always lower case
			return "dojo:" + tagName.substring(4).toLowerCase();
		}

		// allow lower-casing
		var djt = node.getAttribute("dojoType")||node.getAttribute("dojotype");
		if(djt){
			return "dojo:"+djt.toLowerCase();
		}
		
		if((node.getAttributeNS)&&(node.getAttributeNS(this.dojoml,"type"))){
			return "dojo:" + node.getAttributeNS(this.dojoml,"type").toLowerCase();
		}
		try{
			// FIXME: IE really really doesn't like this, so we squelch
			// errors for it
			djt = node.getAttribute("dojo:type");
		}catch(e){ /* FIXME: log? */ }
		if(djt){
			return "dojo:"+djt.toLowerCase();
		}

		if((!dj_global["djConfig"])||(!djConfig["ignoreClassNames"])){
			// FIXME: should we make this optionally enabled via djConfig?
			var classes = node.className||node.getAttribute("class");
			// FIXME: following line, without check for existence of classes.indexOf
			// breaks firefox 1.5's svg widgets
			if((classes)&&(classes.indexOf)&&(classes.indexOf("dojo-") != -1)){
				var aclasses = classes.split(" ");
				for(var x=0; x<aclasses.length; x++){
					if((aclasses[x].length>5)&&(aclasses[x].indexOf("dojo-")>=0)){
						return "dojo:"+aclasses[x].substr(5).toLowerCase();
					}
				}
			}
		}

	}
	return tagName.toLowerCase();
}

dojo.dom.getUniqueId = function(){
	do {
		var id = "dj_unique_" + (++arguments.callee._idIncrement);
	}while(document.getElementById(id));
	return id;
}
dojo.dom.getUniqueId._idIncrement = 0;

dojo.dom.firstElement = dojo.dom.getFirstChildElement = function(parentNode, tagName){
	var node = parentNode.firstChild;
	while(node && node.nodeType != dojo.dom.ELEMENT_NODE){
		node = node.nextSibling;
	}
	if(tagName && node && node.tagName && node.tagName.toLowerCase() != tagName.toLowerCase()) {
		node = dojo.dom.nextElement(node, tagName);
	}
	return node;
}

dojo.dom.lastElement = dojo.dom.getLastChildElement = function(parentNode, tagName){
	var node = parentNode.lastChild;
	while(node && node.nodeType != dojo.dom.ELEMENT_NODE) {
		node = node.previousSibling;
	}
	if(tagName && node && node.tagName && node.tagName.toLowerCase() != tagName.toLowerCase()) {
		node = dojo.dom.prevElement(node, tagName);
	}
	return node;
}

dojo.dom.nextElement = dojo.dom.getNextSiblingElement = function(node, tagName){
	if(!node) { return null; }
	do {
		node = node.nextSibling;
	} while(node && node.nodeType != dojo.dom.ELEMENT_NODE);

	if(node && tagName && tagName.toLowerCase() != node.tagName.toLowerCase()) {
		return dojo.dom.nextElement(node, tagName);
	}
	return node;
}

dojo.dom.prevElement = dojo.dom.getPreviousSiblingElement = function(node, tagName){
	if(!node) { return null; }
	if(tagName) { tagName = tagName.toLowerCase(); }
	do {
		node = node.previousSibling;
	} while(node && node.nodeType != dojo.dom.ELEMENT_NODE);

	if(node && tagName && tagName.toLowerCase() != node.tagName.toLowerCase()) {
		return dojo.dom.prevElement(node, tagName);
	}
	return node;
}

// TODO: hmph
/*this.forEachChildTag = function(node, unaryFunc) {
	var child = this.getFirstChildTag(node);
	while(child) {
		if(unaryFunc(child) == "break") { break; }
		child = this.getNextSiblingTag(child);
	}
}*/

dojo.dom.moveChildren = function(srcNode, destNode, trim){
	var count = 0;
	if(trim) {
		while(srcNode.hasChildNodes() &&
			srcNode.firstChild.nodeType == dojo.dom.TEXT_NODE) {
			srcNode.removeChild(srcNode.firstChild);
		}
		while(srcNode.hasChildNodes() &&
			srcNode.lastChild.nodeType == dojo.dom.TEXT_NODE) {
			srcNode.removeChild(srcNode.lastChild);
		}
	}
	while(srcNode.hasChildNodes()){
		destNode.appendChild(srcNode.firstChild);
		count++;
	}
	return count;
}

dojo.dom.copyChildren = function(srcNode, destNode, trim){
	var clonedNode = srcNode.cloneNode(true);
	return this.moveChildren(clonedNode, destNode, trim);
}

dojo.dom.removeChildren = function(node){
	var count = node.childNodes.length;
	while(node.hasChildNodes()){ node.removeChild(node.firstChild); }
	return count;
}

dojo.dom.replaceChildren = function(node, newChild){
	// FIXME: what if newChild is an array-like object?
	dojo.dom.removeChildren(node);
	node.appendChild(newChild);
}

dojo.dom.removeNode = function(node){
	if(node && node.parentNode){
		// return a ref to the removed child
		return node.parentNode.removeChild(node);
	}
}

dojo.dom.getAncestors = function(node, filterFunction, returnFirstHit) {
	var ancestors = [];
	var isFunction = dojo.lang.isFunction(filterFunction);
	while(node) {
		if (!isFunction || filterFunction(node)) {
			ancestors.push(node);
		}
		if (returnFirstHit && ancestors.length > 0) { return ancestors[0]; }
		
		node = node.parentNode;
	}
	if (returnFirstHit) { return null; }
	return ancestors;
}

dojo.dom.getAncestorsByTag = function(node, tag, returnFirstHit) {
	tag = tag.toLowerCase();
	return dojo.dom.getAncestors(node, function(el){
		return ((el.tagName)&&(el.tagName.toLowerCase() == tag));
	}, returnFirstHit);
}

dojo.dom.getFirstAncestorByTag = function(node, tag) {
	return dojo.dom.getAncestorsByTag(node, tag, true);
}

dojo.dom.isDescendantOf = function(node, ancestor, guaranteeDescendant){
	// guaranteeDescendant allows us to be a "true" isDescendantOf function
	if(guaranteeDescendant && node) { node = node.parentNode; }
	while(node) {
		if(node == ancestor){ return true; }
		node = node.parentNode;
	}
	return false;
}

dojo.dom.innerXML = function(node){
	if(node.innerXML){
		return node.innerXML;
	}else if (node.xml){
		return node.xml;
	}else if(typeof XMLSerializer != "undefined"){
		return (new XMLSerializer()).serializeToString(node);
	}
}

dojo.dom.createDocument = function(){
	var doc = null;

	if(!dj_undef("ActiveXObject")){
		var prefixes = [ "MSXML2", "Microsoft", "MSXML", "MSXML3" ];
		for(var i = 0; i<prefixes.length; i++){
			try{
				doc = new ActiveXObject(prefixes[i]+".XMLDOM");
			}catch(e){ /* squelch */ };

			if(doc){ break; }
		}
	}else if((document.implementation)&&
		(document.implementation.createDocument)){
		doc = document.implementation.createDocument("", "", null);
	}
	
	return doc;
}

dojo.dom.createDocumentFromText = function(str, mimetype){
	if(!mimetype){ mimetype = "text/xml"; }
	if(!dj_undef("DOMParser")){
		var parser = new DOMParser();
		return parser.parseFromString(str, mimetype);
	}else if(!dj_undef("ActiveXObject")){
		var domDoc = dojo.dom.createDocument();
		if(domDoc){
			domDoc.async = false;
			domDoc.loadXML(str);
			return domDoc;
		}else{
			dojo.debug("toXml didn't work?");
		}
	/*
	}else if((dojo.render.html.capable)&&(dojo.render.html.safari)){
		// FIXME: this doesn't appear to work!
		// from: http://web-graphics.com/mtarchive/001606.php
		// var xml = '<?xml version="1.0"?>'+str;
		var mtype = "text/xml";
		var xml = '<?xml version="1.0"?>'+str;
		var url = "data:"+mtype+";charset=utf-8,"+encodeURIComponent(xml);
		var req = new XMLHttpRequest();
		req.open("GET", url, false);
		req.overrideMimeType(mtype);
		req.send(null);
		return req.responseXML;
	*/
	}else if(document.createElement){
		// FIXME: this may change all tags to uppercase!
		var tmp = document.createElement("xml");
		tmp.innerHTML = str;
		if(document.implementation && document.implementation.createDocument) {
			var xmlDoc = document.implementation.createDocument("foo", "", null);
			for(var i = 0; i < tmp.childNodes.length; i++) {
				xmlDoc.importNode(tmp.childNodes.item(i), true);
			}
			return xmlDoc;
		}
		// FIXME: probably not a good idea to have to return an HTML fragment
		// FIXME: the tmp.doc.firstChild is as tested from IE, so it may not
		// work that way across the board
		return ((tmp.document)&&
			(tmp.document.firstChild ?  tmp.document.firstChild : tmp));
	}
	return null;
}

dojo.dom.prependChild = function(node, parent) {
	if(parent.firstChild) {
		parent.insertBefore(node, parent.firstChild);
	} else {
		parent.appendChild(node);
	}
	return true;
}

dojo.dom.insertBefore = function(node, ref, force){
	if (force != true &&
		(node === ref || node.nextSibling === ref)){ return false; }
	var parent = ref.parentNode;
	parent.insertBefore(node, ref);
	return true;
}

dojo.dom.insertAfter = function(node, ref, force){
	var pn = ref.parentNode;
	if(ref == pn.lastChild){
		if((force != true)&&(node === ref)){
			return false;
		}
		pn.appendChild(node);
	}else{
		return this.insertBefore(node, ref.nextSibling, force);
	}
	return true;
}

dojo.dom.insertAtPosition = function(node, ref, position){
	if((!node)||(!ref)||(!position)){ return false; }
	switch(position.toLowerCase()){
		case "before":
			return dojo.dom.insertBefore(node, ref);
		case "after":
			return dojo.dom.insertAfter(node, ref);
		case "first":
			if(ref.firstChild){
				return dojo.dom.insertBefore(node, ref.firstChild);
			}else{
				ref.appendChild(node);
				return true;
			}
			break;
		default: // aka: last
			ref.appendChild(node);
			return true;
	}
}

dojo.dom.insertAtIndex = function(node, containingNode, insertionIndex){
	var siblingNodes = containingNode.childNodes;

	// if there aren't any kids yet, just add it to the beginning

	if (!siblingNodes.length){
		containingNode.appendChild(node);
		return true;
	}

	// otherwise we need to walk the childNodes
	// and find our spot

	var after = null;

	for(var i=0; i<siblingNodes.length; i++){

		var sibling_index = siblingNodes.item(i)["getAttribute"] ? parseInt(siblingNodes.item(i).getAttribute("dojoinsertionindex")) : -1;

		if (sibling_index < insertionIndex){
			after = siblingNodes.item(i);
		}
	}

	if (after){
		// add it after the node in {after}

		return dojo.dom.insertAfter(node, after);
	}else{
		// add it to the start

		return dojo.dom.insertBefore(node, siblingNodes.item(0));
	}
}
	
/**
 * implementation of the DOM Level 3 attribute.
 * 
 * @param node The node to scan for text
 * @param text Optional, set the text to this value.
 */
dojo.dom.textContent = function(node, text){
	if (text) {
		dojo.dom.replaceChildren(node, document.createTextNode(text));
		return text;
	} else {
		var _result = "";
		if (node == null) { return _result; }
		for (var i = 0; i < node.childNodes.length; i++) {
			switch (node.childNodes[i].nodeType) {
				case 1: // ELEMENT_NODE
				case 5: // ENTITY_REFERENCE_NODE
					_result += dojo.dom.textContent(node.childNodes[i]);
					break;
				case 3: // TEXT_NODE
				case 2: // ATTRIBUTE_NODE
				case 4: // CDATA_SECTION_NODE
					_result += node.childNodes[i].nodeValue;
					break;
				default:
					break;
			}
		}
		return _result;
	}
}

dojo.dom.collectionToArray = function(collection){
	dojo.deprecated("dojo.dom.collectionToArray", "use dojo.lang.toArray instead", "0.4");
	return dojo.lang.toArray(collection);
}

dojo.dom.hasParent = function (node) {
	return node && node.parentNode && dojo.dom.isNode(node.parentNode);
}

/**
 * Determines if node has any of the provided tag names and
 * returns the tag name that matches, empty string otherwise.
 *
 * Examples:
 *
 * myFooNode = <foo />
 * isTag(myFooNode, "foo"); // returns "foo"
 * isTag(myFooNode, "bar"); // returns ""
 * isTag(myFooNode, "FOO"); // returns ""
 * isTag(myFooNode, "hey", "foo", "bar"); // returns "foo"
**/
dojo.dom.isTag = function(node /* ... */) {
	if(node && node.tagName) {
		var arr = dojo.lang.toArray(arguments, 1);
		return arr[ dojo.lang.find(node.tagName, arr) ] || "";
	}
	return "";
}

dojo.provide("dojo.undo.browser");
dojo.require("dojo.io");

try{
	if((!djConfig["preventBackButtonFix"])&&(!dojo.hostenv.post_load_)){
		document.write("<iframe style='border: 0px; width: 1px; height: 1px; position: absolute; bottom: 0px; right: 0px; visibility: visible;' name='djhistory' id='djhistory' src='"+(dojo.hostenv.getBaseScriptUri()+'iframe_history.html')+"'></iframe>");
	}
}catch(e){/* squelch */}

if(dojo.render.html.opera){
	dojo.debug("Opera is not supported with dojo.undo.browser, so back/forward detection will not work.");
}

/* NOTES:
 *  Safari 1.2: 
 *	back button "works" fine, however it's not possible to actually
 *	DETECT that you've moved backwards by inspecting window.location.
 *	Unless there is some other means of locating.
 *	FIXME: perhaps we can poll on history.length?
 *  Safari 2.0.3+ (and probably 1.3.2+):
 *	works fine, except when changeUrl is used. When changeUrl is used,
 *	Safari jumps all the way back to whatever page was shown before
 *	the page that uses dojo.undo.browser support.
 *  IE 5.5 SP2:
 *	back button behavior is macro. It does not move back to the
 *	previous hash value, but to the last full page load. This suggests
 *	that the iframe is the correct way to capture the back button in
 *	these cases.
 *	Don't test this page using local disk for MSIE. MSIE will not create 
 *	a history list for iframe_history.html if served from a file: URL. 
 *	The XML served back from the XHR tests will also not be properly 
 *	created if served from local disk. Serve the test pages from a web 
 *	server to test in that browser.
 *  IE 6.0:
 *	same behavior as IE 5.5 SP2
 * Firefox 1.0:
 *	the back button will return us to the previous hash on the same
 *	page, thereby not requiring an iframe hack, although we do then
 *	need to run a timer to detect inter-page movement.
 */
dojo.undo.browser = {
	initialHref: window.location.href,
	initialHash: window.location.hash,

	moveForward: false,
	historyStack: [],
	forwardStack: [],
	historyIframe: null,
	bookmarkAnchor: null,
	locationTimer: null,

	/**
	 * setInitialState sets the state object and back callback for the very first page that is loaded.
	 * It is recommended that you call this method as part of an event listener that is registered via
	 * dojo.addOnLoad().
	 */
	setInitialState: function(args){
		this.initialState = {"url": this.initialHref, "kwArgs": args, "urlHash": this.initialHash};
	},

	//FIXME: Would like to support arbitrary back/forward jumps. Have to rework iframeLoaded among other things.
	//FIXME: is there a slight race condition in moz using change URL with the timer check and when
	//       the hash gets set? I think I have seen a back/forward call in quick succession, but not consistent.
	/**
	 * addToHistory takes one argument, and it is an object that defines the following functions:
	 * - To support getting back button notifications, the object argument should implement a
	 *   function called either "back", "backButton", or "handle". The string "back" will be
	 *   passed as the first and only argument to this callback.
	 * - To support getting forward button notifications, the object argument should implement a
	 *   function called either "forward", "forwardButton", or "handle". The string "forward" will be
	 *   passed as the first and only argument to this callback.
	 * - If you want the browser location string to change, define "changeUrl" on the object. If the
	 *   value of "changeUrl" is true, then a unique number will be appended to the URL as a fragment
	 *   identifier (http://some.domain.com/path#uniquenumber). If it is any other value that does
	 *   not evaluate to false, that value will be used as the fragment identifier. For example,
	 *   if changeUrl: 'page1', then the URL will look like: http://some.domain.com/path#page1
	 *   
	 * Full example:
	 * 
	 * dojo.undo.browser.addToHistory({
	 *   back: function() { alert('back pressed'); },
	 *   forward: function() { alert('forward pressed'); },
	 *   changeUrl: true
	 * });
	 */
	addToHistory: function(args){
		var hash = null;
		if(!this.historyIframe){
			this.historyIframe = window.frames["djhistory"];
		}
		if(!this.bookmarkAnchor){
			this.bookmarkAnchor = document.createElement("a");
			(document.body||document.getElementsByTagName("body")[0]).appendChild(this.bookmarkAnchor);
			this.bookmarkAnchor.style.display = "none";
		}
		if((!args["changeUrl"])||(dojo.render.html.ie)){
			var url = dojo.hostenv.getBaseScriptUri()+"iframe_history.html?"+(new Date()).getTime();
			this.moveForward = true;
			dojo.io.setIFrameSrc(this.historyIframe, url, false);
		}
		if(args["changeUrl"]){
			this.changingUrl = true;
			hash = "#"+ ((args["changeUrl"]!==true) ? args["changeUrl"] : (new Date()).getTime());
			setTimeout("window.location.href = '"+hash+"'; dojo.undo.browser.changingUrl = false;", 1);
			this.bookmarkAnchor.href = hash;
			
			if(dojo.render.html.ie){
				var oldCB = args["back"]||args["backButton"]||args["handle"];

				//The function takes handleName as a parameter, in case the
				//callback we are overriding was "handle". In that case,
				//we will need to pass the handle name to handle.
				var tcb = function(handleName){
					if(window.location.hash != ""){
						setTimeout("window.location.href = '"+hash+"';", 1);
					}
					//Use apply to set "this" to args, and to try to avoid memory leaks.
					oldCB.apply(this, [handleName]);
				}
		
				//Set interceptor function in the right place.
				if(args["back"]){
					args.back = tcb;
				}else if(args["backButton"]){
					args.backButton = tcb;
				}else if(args["handle"]){
					args.handle = tcb;
				}
		
				//If addToHistory is called, then that means we prune the
				//forward stack -- the user went back, then wanted to
				//start a new forward path.
				this.forwardStack = []; 
				var oldFW = args["forward"]||args["forwardButton"]||args["handle"];
		
				//The function takes handleName as a parameter, in case the
				//callback we are overriding was "handle". In that case,
				//we will need to pass the handle name to handle.
				var tfw = function(handleName){
					if(window.location.hash != ""){
						window.location.href = hash;
					}
					if(oldFW){ // we might not actually have one
						//Use apply to set "this" to args, and to try to avoid memory leaks.
						oldFW.apply(this, [handleName]);
					}
				}

				//Set interceptor function in the right place.
				if(args["forward"]){
					args.forward = tfw;
				}else if(args["forwardButton"]){
					args.forwardButton = tfw;
				}else if(args["handle"]){
					args.handle = tfw;
				}

			}else if(dojo.render.html.moz){
				// start the timer
				if(!this.locationTimer){
					this.locationTimer = setInterval("dojo.undo.browser.checkLocation();", 200);
				}
			}
		}

		this.historyStack.push({"url": url, "kwArgs": args, "urlHash": hash});
	},

	checkLocation: function(){
		if (!this.changingUrl){
			var hsl = this.historyStack.length;

			if((window.location.hash == this.initialHash||window.location.href == this.initialHref)&&(hsl == 1)){
				// FIXME: could this ever be a forward button?
				// we can't clear it because we still need to check for forwards. Ugg.
				// clearInterval(this.locationTimer);
				this.handleBackButton();
				return;
			}
			// first check to see if we could have gone forward. We always halt on
			// a no-hash item.
			if(this.forwardStack.length > 0){
				if(this.forwardStack[this.forwardStack.length-1].urlHash == window.location.hash){
					this.handleForwardButton();
					return;
				}
			}
	
			// ok, that didn't work, try someplace back in the history stack
			if((hsl >= 2)&&(this.historyStack[hsl-2])){
				if(this.historyStack[hsl-2].urlHash==window.location.hash){
					this.handleBackButton();
					return;
				}
			}
		}
	},

	iframeLoaded: function(evt, ifrLoc){
		if(!dojo.render.html.opera){
			var query = this._getUrlQuery(ifrLoc.href);
			if(query == null){ 
				// alert("iframeLoaded");
				// we hit the end of the history, so we should go back
				if(this.historyStack.length == 1){
					this.handleBackButton();
				}
				return;
			}
			if(this.moveForward){
				// we were expecting it, so it's not either a forward or backward movement
				this.moveForward = false;
				return;
			}
	
			//Check the back stack first, since it is more likely.
			//Note that only one step back or forward is supported.
			if(this.historyStack.length >= 2 && query == this._getUrlQuery(this.historyStack[this.historyStack.length-2].url)){
				this.handleBackButton();
			}
			else if(this.forwardStack.length > 0 && query == this._getUrlQuery(this.forwardStack[this.forwardStack.length-1].url)){
				this.handleForwardButton();
			}
		}
	},

	handleBackButton: function(){
		//The "current" page is always at the top of the history stack.
		var current = this.historyStack.pop();
		if(!current){ return; }
		var last = this.historyStack[this.historyStack.length-1];
		if(!last && this.historyStack.length == 0){
			last = this.initialState;
		}
		if (last){
			if(last.kwArgs["back"]){
				last.kwArgs["back"]();
			}else if(last.kwArgs["backButton"]){
				last.kwArgs["backButton"]();
			}else if(last.kwArgs["handle"]){
				last.kwArgs.handle("back");
			}
		}
		this.forwardStack.push(current);
	},

	handleForwardButton: function(){
		var last = this.forwardStack.pop();
		if(!last){ return; }
		if(last.kwArgs["forward"]){
			last.kwArgs.forward();
		}else if(last.kwArgs["forwardButton"]){
			last.kwArgs.forwardButton();
		}else if(last.kwArgs["handle"]){
			last.kwArgs.handle("forward");
		}
		this.historyStack.push(last);
	},

	_getUrlQuery: function(url){
		var segments = url.split("?");
		if (segments.length < 2){
			return null;
		}
		else{
			return segments[1];
		}
	}
}

dojo.provide("dojo.io.BrowserIO");

dojo.require("dojo.io");
dojo.require("dojo.lang.array");
dojo.require("dojo.lang.func");
dojo.require("dojo.string.extras");
dojo.require("dojo.dom");
dojo.require("dojo.undo.browser");

dojo.io.checkChildrenForFile = function(node){
	var hasFile = false;
	var inputs = node.getElementsByTagName("input");
	dojo.lang.forEach(inputs, function(input){
		if(hasFile){ return; }
		if(input.getAttribute("type")=="file"){
			hasFile = true;
		}
	});
	return hasFile;
}

dojo.io.formHasFile = function(formNode){
	return dojo.io.checkChildrenForFile(formNode);
}

dojo.io.updateNode = function(node, urlOrArgs){
	node = dojo.byId(node);
	var args = urlOrArgs;
	if(dojo.lang.isString(urlOrArgs)){
		args = { url: urlOrArgs };
	}
	args.mimetype = "text/html";
	args.load = function(t, d, e){
		while(node.firstChild){
			if(dojo["event"]){
				try{
					dojo.event.browser.clean(node.firstChild);
				}catch(e){}
			}
			node.removeChild(node.firstChild);
		}
		node.innerHTML = d;
	};
	dojo.io.bind(args);
}

dojo.io.formFilter = function(node) {
	var type = (node.type||"").toLowerCase();
	return !node.disabled && node.name
		&& !dojo.lang.inArray(type, ["file", "submit", "image", "reset", "button"]);
}

// TODO: Move to htmlUtils
dojo.io.encodeForm = function(formNode, encoding, formFilter){
	if((!formNode)||(!formNode.tagName)||(!formNode.tagName.toLowerCase() == "form")){
		dojo.raise("Attempted to encode a non-form element.");
	}
	if(!formFilter) { formFilter = dojo.io.formFilter; }
	var enc = /utf/i.test(encoding||"") ? encodeURIComponent : dojo.string.encodeAscii;
	var values = [];

	for(var i = 0; i < formNode.elements.length; i++){
		var elm = formNode.elements[i];
		if(!elm || elm.tagName.toLowerCase() == "fieldset" || !formFilter(elm)) { continue; }
		var name = enc(elm.name);
		var type = elm.type.toLowerCase();

		if(type == "select-multiple"){
			for(var j = 0; j < elm.options.length; j++){
				if(elm.options[j].selected) {
					values.push(name + "=" + enc(elm.options[j].value));
				}
			}
		}else if(dojo.lang.inArray(type, ["radio", "checkbox"])){
			if(elm.checked){
				values.push(name + "=" + enc(elm.value));
			}
		}else{
			values.push(name + "=" + enc(elm.value));
		}
	}

	// now collect input type="image", which doesn't show up in the elements array
	var inputs = formNode.getElementsByTagName("input");
	for(var i = 0; i < inputs.length; i++) {
		var input = inputs[i];
		if(input.type.toLowerCase() == "image" && input.form == formNode
			&& formFilter(input)) {
			var name = enc(input.name);
			values.push(name + "=" + enc(input.value));
			values.push(name + ".x=0");
			values.push(name + ".y=0");
		}
	}
	return values.join("&") + "&";
}

dojo.io.FormBind = function(args) {
	this.bindArgs = {};

	if(args && args.formNode) {
		this.init(args);
	} else if(args) {
		this.init({formNode: args});
	}
}
dojo.lang.extend(dojo.io.FormBind, {
	form: null,

	bindArgs: null,

	clickedButton: null,

	init: function(args) {
		var form = dojo.byId(args.formNode);

		if(!form || !form.tagName || form.tagName.toLowerCase() != "form") {
			throw new Error("FormBind: Couldn't apply, invalid form");
		} else if(this.form == form) {
			return;
		} else if(this.form) {
			throw new Error("FormBind: Already applied to a form");
		}

		dojo.lang.mixin(this.bindArgs, args);
		this.form = form;

		this.connect(form, "onsubmit", "submit");

		for(var i = 0; i < form.elements.length; i++) {
			var node = form.elements[i];
			if(node && node.type && dojo.lang.inArray(node.type.toLowerCase(), ["submit", "button"])) {
				this.connect(node, "onclick", "click");
			}
		}

		var inputs = form.getElementsByTagName("input");
		for(var i = 0; i < inputs.length; i++) {
			var input = inputs[i];
			if(input.type.toLowerCase() == "image" && input.form == form) {
				this.connect(input, "onclick", "click");
			}
		}
	},

	onSubmit: function(form) {
		return true;
	},

	submit: function(e) {
		e.preventDefault();
		if(this.onSubmit(this.form)) {
			dojo.io.bind(dojo.lang.mixin(this.bindArgs, {
				formFilter: dojo.lang.hitch(this, "formFilter")
			}));
		}
	},

	click: function(e) {
		var node = e.currentTarget;
		if(node.disabled) { return; }
		this.clickedButton = node;
	},

	formFilter: function(node) {
		var type = (node.type||"").toLowerCase();
		var accept = false;
		if(node.disabled || !node.name) {
			accept = false;
		} else if(dojo.lang.inArray(type, ["submit", "button", "image"])) {
			if(!this.clickedButton) { this.clickedButton = node; }
			accept = node == this.clickedButton;
		} else {
			accept = !dojo.lang.inArray(type, ["file", "submit", "reset", "button"]);
		}
		return accept;
	},

	// in case you don't have dojo.event.* pulled in
	connect: function(srcObj, srcFcn, targetFcn) {
		if(dojo.evalObjPath("dojo.event.connect")) {
			dojo.event.connect(srcObj, srcFcn, this, targetFcn);
		} else {
			var fcn = dojo.lang.hitch(this, targetFcn);
			srcObj[srcFcn] = function(e) {
				if(!e) { e = window.event; }
				if(!e.currentTarget) { e.currentTarget = e.srcElement; }
				if(!e.preventDefault) { e.preventDefault = function() { window.event.returnValue = false; } }
				fcn(e);
			}
		}
	}
});

dojo.io.XMLHTTPTransport = new function(){
	var _this = this;

	var _cache = {}; // FIXME: make this public? do we even need to?
	this.useCache = false; // if this is true, we'll cache unless kwArgs.useCache = false
	this.preventCache = false; // if this is true, we'll always force GET requests to cache

	// FIXME: Should this even be a function? or do we just hard code it in the next 2 functions?
	function getCacheKey(url, query, method) {
		return url + "|" + query + "|" + method.toLowerCase();
	}

	function addToCache(url, query, method, http) {
		_cache[getCacheKey(url, query, method)] = http;
	}

	function getFromCache(url, query, method) {
		return _cache[getCacheKey(url, query, method)];
	}

	this.clearCache = function() {
		_cache = {};
	}

	// moved successful load stuff here
	function doLoad(kwArgs, http, url, query, useCache) {
		if(	((http.status>=200)&&(http.status<300))|| 	// allow any 2XX response code
			(http.status==304)|| 						// get it out of the cache
			(location.protocol=="file:" && (http.status==0 || http.status==undefined))||
			(location.protocol=="chrome:" && (http.status==0 || http.status==undefined))
		){
			var ret;
			if(kwArgs.method.toLowerCase() == "head"){
				var headers = http.getAllResponseHeaders();
				ret = {};
				ret.toString = function(){ return headers; }
				var values = headers.split(/[\r\n]+/g);
				for(var i = 0; i < values.length; i++) {
					var pair = values[i].match(/^([^:]+)\s*:\s*(.+)$/i);
					if(pair) {
						ret[pair[1]] = pair[2];
					}
				}
			}else if(kwArgs.mimetype == "text/javascript"){
				try{
					ret = dj_eval(http.responseText);
				}catch(e){
					dojo.debug(e);
					dojo.debug(http.responseText);
					ret = null;
				}
			}else if(kwArgs.mimetype == "text/json"){
				try{
					ret = dj_eval("("+http.responseText+")");
				}catch(e){
					dojo.debug(e);
					dojo.debug(http.responseText);
					ret = false;
				}
			}else if((kwArgs.mimetype == "application/xml")||
						(kwArgs.mimetype == "text/xml")){
				ret = http.responseXML;
				if(!ret || typeof ret == "string" || !http.getResponseHeader("Content-Type")) {
					ret = dojo.dom.createDocumentFromText(http.responseText);
				}
			}else{
				ret = http.responseText;
			}

			if(useCache){ // only cache successful responses
				addToCache(url, query, kwArgs.method, http);
			}
			kwArgs[(typeof kwArgs.load == "function") ? "load" : "handle"]("load", ret, http, kwArgs);
		}else{
			var errObj = new dojo.io.Error("XMLHttpTransport Error: "+http.status+" "+http.statusText);
			kwArgs[(typeof kwArgs.error == "function") ? "error" : "handle"]("error", errObj, http, kwArgs);
		}
	}

	// set headers (note: Content-Type will get overriden if kwArgs.contentType is set)
	function setHeaders(http, kwArgs){
		if(kwArgs["headers"]) {
			for(var header in kwArgs["headers"]) {
				if(header.toLowerCase() == "content-type" && !kwArgs["contentType"]) {
					kwArgs["contentType"] = kwArgs["headers"][header];
				} else {
					http.setRequestHeader(header, kwArgs["headers"][header]);
				}
			}
		}
	}

	this.inFlight = [];
	this.inFlightTimer = null;

	this.startWatchingInFlight = function(){
		if(!this.inFlightTimer){
			this.inFlightTimer = setInterval("dojo.io.XMLHTTPTransport.watchInFlight();", 10);
		}
	}

	this.watchInFlight = function(){
		var now = null;
		for(var x=this.inFlight.length-1; x>=0; x--){
			var tif = this.inFlight[x];
			if(!tif){ this.inFlight.splice(x, 1); continue; }
			if(4==tif.http.readyState){
				// remove it so we can clean refs
				this.inFlight.splice(x, 1);
				doLoad(tif.req, tif.http, tif.url, tif.query, tif.useCache);
			}else if (tif.startTime){
				//See if this is a timeout case.
				if(!now){
					now = (new Date()).getTime();
				}
				if(tif.startTime + (tif.req.timeoutSeconds * 1000) < now){
					//Stop the request.
					if(typeof tif.http.abort == "function"){
						tif.http.abort();
					}

					// remove it so we can clean refs
					this.inFlight.splice(x, 1);
					tif.req[(typeof tif.req.timeout == "function") ? "timeout" : "handle"]("timeout", null, tif.http, tif.req);
				}
			}
		}

		if(this.inFlight.length == 0){
			clearInterval(this.inFlightTimer);
			this.inFlightTimer = null;
		}
	}

	var hasXmlHttp = dojo.hostenv.getXmlhttpObject() ? true : false;
	this.canHandle = function(kwArgs){
		// canHandle just tells dojo.io.bind() if this is a good transport to
		// use for the particular type of request.

		// FIXME: we need to determine when form values need to be
		// multi-part mime encoded and avoid using this transport for those
		// requests.
		return hasXmlHttp
			&& dojo.lang.inArray((kwArgs["mimetype"].toLowerCase()||""), ["text/plain", "text/html", "application/xml", "text/xml", "text/javascript", "text/json"])
			&& !( kwArgs["formNode"] && dojo.io.formHasFile(kwArgs["formNode"]) );
	}

	this.multipartBoundary = "45309FFF-BD65-4d50-99C9-36986896A96F";	// unique guid as a boundary value for multipart posts

	this.bind = function(kwArgs){
		if(!kwArgs["url"]){
			// are we performing a history action?
			if( !kwArgs["formNode"]
				&& (kwArgs["backButton"] || kwArgs["back"] || kwArgs["changeUrl"] || kwArgs["watchForURL"])
				&& (!djConfig.preventBackButtonFix)) {
        dojo.deprecated("Using dojo.io.XMLHTTPTransport.bind() to add to browser history without doing an IO request",
        				"Use dojo.undo.browser.addToHistory() instead.", "0.4");
				dojo.undo.browser.addToHistory(kwArgs);
				return true;
			}
		}

		// build this first for cache purposes
		var url = kwArgs.url;
		var query = "";
		if(kwArgs["formNode"]){
			var ta = kwArgs.formNode.getAttribute("action");
			if((ta)&&(!kwArgs["url"])){ url = ta; }
			var tp = kwArgs.formNode.getAttribute("method");
			if((tp)&&(!kwArgs["method"])){ kwArgs.method = tp; }
			query += dojo.io.encodeForm(kwArgs.formNode, kwArgs.encoding, kwArgs["formFilter"]);
		}

		if(url.indexOf("#") > -1) {
			dojo.debug("Warning: dojo.io.bind: stripping hash values from url:", url);
			url = url.split("#")[0];
		}

		if(kwArgs["file"]){
			// force post for file transfer
			kwArgs.method = "post";
		}

		if(!kwArgs["method"]){
			kwArgs.method = "get";
		}

		// guess the multipart value		
		if(kwArgs.method.toLowerCase() == "get"){
			// GET cannot use multipart
			kwArgs.multipart = false;
		}else{
			if(kwArgs["file"]){
				// enforce multipart when sending files
				kwArgs.multipart = true;
			}else if(!kwArgs["multipart"]){
				// default 
				kwArgs.multipart = false;
			}
		}

		if(kwArgs["backButton"] || kwArgs["back"] || kwArgs["changeUrl"]){
			dojo.undo.browser.addToHistory(kwArgs);
		}

		var content = kwArgs["content"] || {};

		if(kwArgs.sendTransport) {
			content["dojo.transport"] = "xmlhttp";
		}

		do { // break-block
			if(kwArgs.postContent){
				query = kwArgs.postContent;
				break;
			}

			if(content) {
				query += dojo.io.argsFromMap(content, kwArgs.encoding);
			}
			
			if(kwArgs.method.toLowerCase() == "get" || !kwArgs.multipart){
				break;
			}

			var	t = [];
			if(query.length){
				var q = query.split("&");
				for(var i = 0; i < q.length; ++i){
					if(q[i].length){
						var p = q[i].split("=");
						t.push(	"--" + this.multipartBoundary,
								"Content-Disposition: form-data; name=\"" + p[0] + "\"", 
								"",
								p[1]);
					}
				}
			}

			if(kwArgs.file){
				if(dojo.lang.isArray(kwArgs.file)){
					for(var i = 0; i < kwArgs.file.length; ++i){
						var o = kwArgs.file[i];
						t.push(	"--" + this.multipartBoundary,
								"Content-Disposition: form-data; name=\"" + o.name + "\"; filename=\"" + ("fileName" in o ? o.fileName : o.name) + "\"",
								"Content-Type: " + ("contentType" in o ? o.contentType : "application/octet-stream"),
								"",
								o.content);
					}
				}else{
					var o = kwArgs.file;
					t.push(	"--" + this.multipartBoundary,
							"Content-Disposition: form-data; name=\"" + o.name + "\"; filename=\"" + ("fileName" in o ? o.fileName : o.name) + "\"",
							"Content-Type: " + ("contentType" in o ? o.contentType : "application/octet-stream"),
							"",
							o.content);
				}
			}

			if(t.length){
				t.push("--"+this.multipartBoundary+"--", "");
				query = t.join("\r\n");
			}
		}while(false);

		// kwArgs.Connection = "close";

		var async = kwArgs["sync"] ? false : true;

		var preventCache = kwArgs["preventCache"] ||
			(this.preventCache == true && kwArgs["preventCache"] != false);
		var useCache = kwArgs["useCache"] == true ||
			(this.useCache == true && kwArgs["useCache"] != false );

		// preventCache is browser-level (add query string junk), useCache
		// is for the local cache. If we say preventCache, then don't attempt
		// to look in the cache, but if useCache is true, we still want to cache
		// the response
		if(!preventCache && useCache){
			var cachedHttp = getFromCache(url, query, kwArgs.method);
			if(cachedHttp){
				doLoad(kwArgs, cachedHttp, url, query, false);
				return;
			}
		}

		// much of this is from getText, but reproduced here because we need
		// more flexibility
		var http = dojo.hostenv.getXmlhttpObject(kwArgs);	
		var received = false;

		// build a handler function that calls back to the handler obj
		if(async){
			var startTime = 
			// FIXME: setting up this callback handler leaks on IE!!!
			this.inFlight.push({
				"req":		kwArgs,
				"http":		http,
				"url":	 	url,
				"query":	query,
				"useCache":	useCache,
				"startTime": kwArgs.timeoutSeconds ? (new Date()).getTime() : 0
			});
			this.startWatchingInFlight();
		}

		if(kwArgs.method.toLowerCase() == "post"){
			// FIXME: need to hack in more flexible Content-Type setting here!
			http.open("POST", url, async);
			setHeaders(http, kwArgs);
			http.setRequestHeader("Content-Type", kwArgs.multipart ? ("multipart/form-data; boundary=" + this.multipartBoundary) : 
				(kwArgs.contentType || "application/x-www-form-urlencoded"));
			try{
				http.send(query);
			}catch(e){
				if(typeof http.abort == "function"){
					http.abort();
				}
				doLoad(kwArgs, {status: 404}, url, query, useCache);
			}
		}else{
			var tmpUrl = url;
			if(query != "") {
				tmpUrl += (tmpUrl.indexOf("?") > -1 ? "&" : "?") + query;
			}
			if(preventCache) {
				tmpUrl += (dojo.string.endsWithAny(tmpUrl, "?", "&")
					? "" : (tmpUrl.indexOf("?") > -1 ? "&" : "?")) + "dojo.preventCache=" + new Date().valueOf();
			}
			http.open(kwArgs.method.toUpperCase(), tmpUrl, async);
			setHeaders(http, kwArgs);
			try {
				http.send(null);
			}catch(e)	{
				if(typeof http.abort == "function"){
					http.abort();
				}
				doLoad(kwArgs, {status: 404}, url, query, useCache);
			}
		}

		if( !async ) {
			doLoad(kwArgs, http, url, query, useCache);
		}

		kwArgs.abort = function(){
			return http.abort();
		}

		return;
	}
	dojo.io.transports.addTransport("XMLHTTPTransport");
}

dojo.provide("dojo.event");

dojo.require("dojo.lang.array");
dojo.require("dojo.lang.extras");
dojo.require("dojo.lang.func");

dojo.event = new function(){
	this.canTimeout = dojo.lang.isFunction(dj_global["setTimeout"])||dojo.lang.isAlien(dj_global["setTimeout"]);

	// FIXME: where should we put this method (not here!)?
	function interpolateArgs(args, searchForNames){
		var dl = dojo.lang;
		var ao = {
			srcObj: dj_global,
			srcFunc: null,
			adviceObj: dj_global,
			adviceFunc: null,
			aroundObj: null,
			aroundFunc: null,
			adviceType: (args.length>2) ? args[0] : "after",
			precedence: "last",
			once: false,
			delay: null,
			rate: 0,
			adviceMsg: false
		};

		switch(args.length){
			case 0: return;
			case 1: return;
			case 2:
				ao.srcFunc = args[0];
				ao.adviceFunc = args[1];
				break;
			case 3:
				if((dl.isObject(args[0]))&&(dl.isString(args[1]))&&(dl.isString(args[2]))){
					ao.adviceType = "after";
					ao.srcObj = args[0];
					ao.srcFunc = args[1];
					ao.adviceFunc = args[2];
				}else if((dl.isString(args[1]))&&(dl.isString(args[2]))){
					ao.srcFunc = args[1];
					ao.adviceFunc = args[2];
				}else if((dl.isObject(args[0]))&&(dl.isString(args[1]))&&(dl.isFunction(args[2]))){
					ao.adviceType = "after";
					ao.srcObj = args[0];
					ao.srcFunc = args[1];
					var tmpName  = dl.nameAnonFunc(args[2], ao.adviceObj, searchForNames);
					ao.adviceFunc = tmpName;
				}else if((dl.isFunction(args[0]))&&(dl.isObject(args[1]))&&(dl.isString(args[2]))){
					ao.adviceType = "after";
					ao.srcObj = dj_global;
					var tmpName  = dl.nameAnonFunc(args[0], ao.srcObj, searchForNames);
					ao.srcFunc = tmpName;
					ao.adviceObj = args[1];
					ao.adviceFunc = args[2];
				}
				break;
			case 4:
				if((dl.isObject(args[0]))&&(dl.isObject(args[2]))){
					// we can assume that we've got an old-style "connect" from
					// the sigslot school of event attachment. We therefore
					// assume after-advice.
					ao.adviceType = "after";
					ao.srcObj = args[0];
					ao.srcFunc = args[1];
					ao.adviceObj = args[2];
					ao.adviceFunc = args[3];
				}else if((dl.isString(args[0]))&&(dl.isString(args[1]))&&(dl.isObject(args[2]))){
					ao.adviceType = args[0];
					ao.srcObj = dj_global;
					ao.srcFunc = args[1];
					ao.adviceObj = args[2];
					ao.adviceFunc = args[3];
				}else if((dl.isString(args[0]))&&(dl.isFunction(args[1]))&&(dl.isObject(args[2]))){
					ao.adviceType = args[0];
					ao.srcObj = dj_global;
					var tmpName  = dl.nameAnonFunc(args[1], dj_global, searchForNames);
					ao.srcFunc = tmpName;
					ao.adviceObj = args[2];
					ao.adviceFunc = args[3];
				}else if((dl.isString(args[0]))&&(dl.isObject(args[1]))&&(dl.isString(args[2]))&&(dl.isFunction(args[3]))){
					ao.srcObj = args[1];
					ao.srcFunc = args[2];
					var tmpName  = dl.nameAnonFunc(args[3], dj_global, searchForNames);
					ao.adviceObj = dj_global;
					ao.adviceFunc = tmpName;
				}else if(dl.isObject(args[1])){
					ao.srcObj = args[1];
					ao.srcFunc = args[2];
					ao.adviceObj = dj_global;
					ao.adviceFunc = args[3];
				}else if(dl.isObject(args[2])){
					ao.srcObj = dj_global;
					ao.srcFunc = args[1];
					ao.adviceObj = args[2];
					ao.adviceFunc = args[3];
				}else{
					ao.srcObj = ao.adviceObj = ao.aroundObj = dj_global;
					ao.srcFunc = args[1];
					ao.adviceFunc = args[2];
					ao.aroundFunc = args[3];
				}
				break;
			case 6:
				ao.srcObj = args[1];
				ao.srcFunc = args[2];
				ao.adviceObj = args[3]
				ao.adviceFunc = args[4];
				ao.aroundFunc = args[5];
				ao.aroundObj = dj_global;
				break;
			default:
				ao.srcObj = args[1];
				ao.srcFunc = args[2];
				ao.adviceObj = args[3]
				ao.adviceFunc = args[4];
				ao.aroundObj = args[5];
				ao.aroundFunc = args[6];
				ao.once = args[7];
				ao.delay = args[8];
				ao.rate = args[9];
				ao.adviceMsg = args[10];
				break;
		}

		if(dl.isFunction(ao.aroundFunc)){
			var tmpName  = dl.nameAnonFunc(ao.aroundFunc, ao.aroundObj, searchForNames);
			ao.aroundFunc = tmpName;
		}

		if(dl.isFunction(ao.srcFunc)){
			ao.srcFunc = dl.getNameInObj(ao.srcObj, ao.srcFunc);
		}

		if(dl.isFunction(ao.adviceFunc)){
			ao.adviceFunc = dl.getNameInObj(ao.adviceObj, ao.adviceFunc);
		}

		if((ao.aroundObj)&&(dl.isFunction(ao.aroundFunc))){
			ao.aroundFunc = dl.getNameInObj(ao.aroundObj, ao.aroundFunc);
		}

		if(!ao.srcObj){
			dojo.raise("bad srcObj for srcFunc: "+ao.srcFunc);
		}
		if(!ao.adviceObj){
			dojo.raise("bad adviceObj for adviceFunc: "+ao.adviceFunc);
		}
		return ao;
	}

	this.connect = function(){
		if(arguments.length == 1){
			var ao = arguments[0];
		}else{
			var ao = interpolateArgs(arguments, true);
		}

		if(dojo.lang.isArray(ao.srcObj) && ao.srcObj!=""){
			var tmpAO = {};
			for(var x in ao){
				tmpAO[x] = ao[x];
			}
			var mjps = [];
			dojo.lang.forEach(ao.srcObj, function(src){
				if((dojo.render.html.capable)&&(dojo.lang.isString(src))){
					src = dojo.byId(src);
					// dojo.debug(src);
				}
				tmpAO.srcObj = src;
				// dojo.debug(tmpAO.srcObj, tmpAO.srcFunc);
				// dojo.debug(tmpAO.adviceObj, tmpAO.adviceFunc);
				mjps.push(dojo.event.connect.call(dojo.event, tmpAO));
			});
			return mjps;
		}

		// FIXME: just doing a "getForMethod()" seems to be enough to put this into infinite recursion!!
		var mjp = dojo.event.MethodJoinPoint.getForMethod(ao.srcObj, ao.srcFunc);
		if(ao.adviceFunc){
			var mjp2 = dojo.event.MethodJoinPoint.getForMethod(ao.adviceObj, ao.adviceFunc);
		}

		mjp.kwAddAdvice(ao);

		return mjp;	// advanced users might want to fsck w/ the join point
					// manually
	}

	this.log = function(a1, a2){
		var kwArgs;
		if((arguments.length == 1)&&(typeof a1 == "object")){
			kwArgs = a1;
		}else{
			kwArgs = {
				srcObj: a1,
				srcFunc: a2
			};
		}
		kwArgs.adviceFunc = function(){
			var argsStr = [];
			for(var x=0; x<arguments.length; x++){
				argsStr.push(arguments[x]);
			}
			dojo.debug("("+kwArgs.srcObj+")."+kwArgs.srcFunc, ":", argsStr.join(", "));
		}
		this.kwConnect(kwArgs);
	}

	this.connectBefore = function(){
		var args = ["before"];
		for(var i = 0; i < arguments.length; i++) { args.push(arguments[i]); }
		return this.connect.apply(this, args);
	}

	this.connectAround = function(){
		var args = ["around"];
		for(var i = 0; i < arguments.length; i++) { args.push(arguments[i]); }
		return this.connect.apply(this, args);
	}

	this.connectOnce = function(){
		var ao = interpolateArgs(arguments, true);
		ao.once = true;
		return this.connect(ao);
	}

	this._kwConnectImpl = function(kwArgs, disconnect){
		var fn = (disconnect) ? "disconnect" : "connect";
		if(typeof kwArgs["srcFunc"] == "function"){
			kwArgs.srcObj = kwArgs["srcObj"]||dj_global;
			var tmpName  = dojo.lang.nameAnonFunc(kwArgs.srcFunc, kwArgs.srcObj, true);
			kwArgs.srcFunc = tmpName;
		}
		if(typeof kwArgs["adviceFunc"] == "function"){
			kwArgs.adviceObj = kwArgs["adviceObj"]||dj_global;
			var tmpName  = dojo.lang.nameAnonFunc(kwArgs.adviceFunc, kwArgs.adviceObj, true);
			kwArgs.adviceFunc = tmpName;
		}
		return dojo.event[fn](	(kwArgs["type"]||kwArgs["adviceType"]||"after"),
									kwArgs["srcObj"]||dj_global,
									kwArgs["srcFunc"],
									kwArgs["adviceObj"]||kwArgs["targetObj"]||dj_global,
									kwArgs["adviceFunc"]||kwArgs["targetFunc"],
									kwArgs["aroundObj"],
									kwArgs["aroundFunc"],
									kwArgs["once"],
									kwArgs["delay"],
									kwArgs["rate"],
									kwArgs["adviceMsg"]||false );
	}

	this.kwConnect = function(kwArgs){
		return this._kwConnectImpl(kwArgs, false);

	}

	this.disconnect = function(){
		var ao = interpolateArgs(arguments, true);
		if(!ao.adviceFunc){ return; } // nothing to disconnect
		var mjp = dojo.event.MethodJoinPoint.getForMethod(ao.srcObj, ao.srcFunc);
		return mjp.removeAdvice(ao.adviceObj, ao.adviceFunc, ao.adviceType, ao.once);
	}

	this.kwDisconnect = function(kwArgs){
		return this._kwConnectImpl(kwArgs, true);
	}
}

// exactly one of these is created whenever a method with a joint point is run,
// if there is at least one 'around' advice.
dojo.event.MethodInvocation = function(join_point, obj, args) {
	this.jp_ = join_point;
	this.object = obj;
	this.args = [];
	for(var x=0; x<args.length; x++){
		this.args[x] = args[x];
	}
	// the index of the 'around' that is currently being executed.
	this.around_index = -1;
}

dojo.event.MethodInvocation.prototype.proceed = function() {
	this.around_index++;
	if(this.around_index >= this.jp_.around.length){
		return this.jp_.object[this.jp_.methodname].apply(this.jp_.object, this.args);
		// return this.jp_.run_before_after(this.object, this.args);
	}else{
		var ti = this.jp_.around[this.around_index];
		var mobj = ti[0]||dj_global;
		var meth = ti[1];
		return mobj[meth].call(mobj, this);
	}
} 


dojo.event.MethodJoinPoint = function(obj, methname){
	this.object = obj||dj_global;
	this.methodname = methname;
	this.methodfunc = this.object[methname];
	this.before = [];
	this.after = [];
	this.around = [];
}

dojo.event.MethodJoinPoint.getForMethod = function(obj, methname) {
	// if(!(methname in obj)){
	if(!obj){ obj = dj_global; }
	if(!obj[methname]){
		// supply a do-nothing method implementation
		obj[methname] = function(){};
		if(!obj[methname]){
			// e.g. cannot add to inbuilt objects in IE6
			dojo.raise("Cannot set do-nothing method on that object "+methname);
		}
	}else if((!dojo.lang.isFunction(obj[methname]))&&(!dojo.lang.isAlien(obj[methname]))){
		return null; // FIXME: should we throw an exception here instead?
	}
	// we hide our joinpoint instance in obj[methname + '$joinpoint']
	var jpname = methname + "$joinpoint";
	var jpfuncname = methname + "$joinpoint$method";
	var joinpoint = obj[jpname];
	if(!joinpoint){
		var isNode = false;
		if(dojo.event["browser"]){
			if( (obj["attachEvent"])||
				(obj["nodeType"])||
				(obj["addEventListener"]) ){
				isNode = true;
				dojo.event.browser.addClobberNodeAttrs(obj, [jpname, jpfuncname, methname]);
			}
		}
		var origArity = obj[methname].length;
		obj[jpfuncname] = obj[methname];
		// joinpoint = obj[jpname] = new dojo.event.MethodJoinPoint(obj, methname);
		joinpoint = obj[jpname] = new dojo.event.MethodJoinPoint(obj, jpfuncname);
		obj[methname] = function(){ 
			var args = [];

			if((isNode)&&(!arguments.length)){
				var evt = null;
				try{
					if(obj.ownerDocument){
						evt = obj.ownerDocument.parentWindow.event;
					}else if(obj.documentElement){
						evt = obj.documentElement.ownerDocument.parentWindow.event;
					}else{
						evt = window.event;
					}
				}catch(e){
					evt = window.event;
				}

				if(evt){
					args.push(dojo.event.browser.fixEvent(evt, this));
				}
			}else{
				for(var x=0; x<arguments.length; x++){
					if((x==0)&&(isNode)&&(dojo.event.browser.isEvent(arguments[x]))){
						args.push(dojo.event.browser.fixEvent(arguments[x], this));
					}else{
						args.push(arguments[x]);
					}
				}
			}
			// return joinpoint.run.apply(joinpoint, arguments); 
			return joinpoint.run.apply(joinpoint, args); 
		}
		obj[methname].__preJoinArity = origArity;
	}
	return joinpoint;
}

dojo.lang.extend(dojo.event.MethodJoinPoint, {
	unintercept: function(){
		this.object[this.methodname] = this.methodfunc;
		this.before = [];
		this.after = [];
		this.around = [];
	},

	disconnect: dojo.lang.forward("unintercept"),

	run: function() {
		var obj = this.object||dj_global;
		var args = arguments;

		// optimization. We only compute once the array version of the arguments
		// pseudo-arr in order to prevent building it each time advice is unrolled.
		var aargs = [];
		for(var x=0; x<args.length; x++){
			aargs[x] = args[x];
		}

		var unrollAdvice  = function(marr){ 
			if(!marr){
				dojo.debug("Null argument to unrollAdvice()");
				return;
			}
		  
			var callObj = marr[0]||dj_global;
			var callFunc = marr[1];
			
			if(!callObj[callFunc]){
				dojo.raise("function \"" + callFunc + "\" does not exist on \"" + callObj + "\"");
			}
			
			var aroundObj = marr[2]||dj_global;
			var aroundFunc = marr[3];
			var msg = marr[6];
			var undef;

			var to = {
				args: [],
				jp_: this,
				object: obj,
				proceed: function(){
					return callObj[callFunc].apply(callObj, to.args);
				}
			};
			to.args = aargs;

			var delay = parseInt(marr[4]);
			var hasDelay = ((!isNaN(delay))&&(marr[4]!==null)&&(typeof marr[4] != "undefined"));
			if(marr[5]){
				var rate = parseInt(marr[5]);
				var cur = new Date();
				var timerSet = false;
				if((marr["last"])&&((cur-marr.last)<=rate)){
					if(dojo.event.canTimeout){
						if(marr["delayTimer"]){
							clearTimeout(marr.delayTimer);
						}
						var tod = parseInt(rate*2); // is rate*2 naive?
						var mcpy = dojo.lang.shallowCopy(marr);
						marr.delayTimer = setTimeout(function(){
							// FIXME: on IE at least, event objects from the
							// browser can go out of scope. How (or should?) we
							// deal with it?
							mcpy[5] = 0;
							unrollAdvice(mcpy);
						}, tod);
					}
					return;
				}else{
					marr.last = cur;
				}
			}

			// FIXME: need to enforce rates for a connection here!

			if(aroundFunc){
				// NOTE: around advice can't delay since we might otherwise depend
				// on execution order!
				aroundObj[aroundFunc].call(aroundObj, to);
			}else{
				// var tmjp = dojo.event.MethodJoinPoint.getForMethod(obj, methname);
				if((hasDelay)&&((dojo.render.html)||(dojo.render.svg))){  // FIXME: the render checks are grotty!
					dj_global["setTimeout"](function(){
						if(msg){
							callObj[callFunc].call(callObj, to); 
						}else{
							callObj[callFunc].apply(callObj, args); 
						}
					}, delay);
				}else{ // many environments can't support delay!
					if(msg){
						callObj[callFunc].call(callObj, to); 
					}else{
						callObj[callFunc].apply(callObj, args); 
					}
				}
			}
		}

		if(this.before.length>0){
			dojo.lang.forEach(this.before, unrollAdvice);
		}

		var result;
		if(this.around.length>0){
			var mi = new dojo.event.MethodInvocation(this, obj, args);
			result = mi.proceed();
		}else if(this.methodfunc){
			result = this.object[this.methodname].apply(this.object, args);
		}

		if(this.after.length>0){
			dojo.lang.forEach(this.after, unrollAdvice);
		}

		return (this.methodfunc) ? result : null;
	},

	getArr: function(kind){
		var arr = this.after;
		// FIXME: we should be able to do this through props or Array.in()
		if((typeof kind == "string")&&(kind.indexOf("before")!=-1)){
			arr = this.before;
		}else if(kind=="around"){
			arr = this.around;
		}
		return arr;
	},

	kwAddAdvice: function(args){
		this.addAdvice(	args["adviceObj"], args["adviceFunc"], 
						args["aroundObj"], args["aroundFunc"], 
						args["adviceType"], args["precedence"], 
						args["once"], args["delay"], args["rate"], 
						args["adviceMsg"]);
	},

	addAdvice: function(	thisAdviceObj, thisAdvice, 
							thisAroundObj, thisAround, 
							advice_kind, precedence, 
							once, delay, rate, asMessage){
		var arr = this.getArr(advice_kind);
		if(!arr){
			dojo.raise("bad this: " + this);
		}

		var ao = [thisAdviceObj, thisAdvice, thisAroundObj, thisAround, delay, rate, asMessage];
		
		if(once){
			if(this.hasAdvice(thisAdviceObj, thisAdvice, advice_kind, arr) >= 0){
				return;
			}
		}

		if(precedence == "first"){
			arr.unshift(ao);
		}else{
			arr.push(ao);
		}
	},

	hasAdvice: function(thisAdviceObj, thisAdvice, advice_kind, arr){
		if(!arr){ arr = this.getArr(advice_kind); }
		var ind = -1;
		for(var x=0; x<arr.length; x++){
			var aao = (typeof thisAdvice == "object") ? (new String(thisAdvice)).toString() : thisAdvice;
			var a1o = (typeof arr[x][1] == "object") ? (new String(arr[x][1])).toString() : arr[x][1];
			if((arr[x][0] == thisAdviceObj)&&(a1o == aao)){
				ind = x;
			}
		}
		return ind;
	},

	removeAdvice: function(thisAdviceObj, thisAdvice, advice_kind, once){
		var arr = this.getArr(advice_kind);
		var ind = this.hasAdvice(thisAdviceObj, thisAdvice, advice_kind, arr);
		if(ind == -1){
			return false;
		}
		while(ind != -1){
			arr.splice(ind, 1);
			if(once){ break; }
			ind = this.hasAdvice(thisAdviceObj, thisAdvice, advice_kind, arr);
		}
		return true;
	}
});

dojo.require("dojo.event");
dojo.provide("dojo.event.topic");

dojo.event.topic = new function(){
	this.topics = {};

	this.getTopic = function(topicName){
		if(!this.topics[topicName]){
			this.topics[topicName] = new this.TopicImpl(topicName);
		}
		return this.topics[topicName];
	}

	this.registerPublisher = function(topic, obj, funcName){
		var topic = this.getTopic(topic);
		topic.registerPublisher(obj, funcName);
	}

	this.subscribe = function(topic, obj, funcName){
		var topic = this.getTopic(topic);
		topic.subscribe(obj, funcName);
	}

	this.unsubscribe = function(topic, obj, funcName){
		var topic = this.getTopic(topic);
		topic.unsubscribe(obj, funcName);
	}

	this.destroy = function(topic){
		this.getTopic(topic).destroy();
		delete this.topics[topic];
	}

	this.publishApply = function(topic, args){
		var topic = this.getTopic(topic);
		topic.sendMessage.apply(topic, args);
	}

	this.publish = function(topic, message){
		var topic = this.getTopic(topic);
		// if message is an array, we treat it as a set of arguments,
		// otherwise, we just pass on the arguments passed in as-is
		var args = [];
		// could we use concat instead here?
		for(var x=1; x<arguments.length; x++){
			args.push(arguments[x]);
		}
		topic.sendMessage.apply(topic, args);
	}
}

dojo.event.topic.TopicImpl = function(topicName){
	this.topicName = topicName;

	this.subscribe = function(listenerObject, listenerMethod){
		var tf = listenerMethod||listenerObject;
		var to = (!listenerMethod) ? dj_global : listenerObject;
		dojo.event.kwConnect({
			srcObj:		this, 
			srcFunc:	"sendMessage", 
			adviceObj:	to,
			adviceFunc: tf
		});
	}

	this.unsubscribe = function(listenerObject, listenerMethod){
		var tf = (!listenerMethod) ? listenerObject : listenerMethod;
		var to = (!listenerMethod) ? null : listenerObject;
		dojo.event.kwDisconnect({
			srcObj:		this, 
			srcFunc:	"sendMessage", 
			adviceObj:	to,
			adviceFunc: tf
		});
	}

	this.destroy = function(){
		dojo.event.MethodJoinPoint.getForMethod(this, "sendMessage").disconnect();
	}

	this.registerPublisher = function(publisherObject, publisherMethod){
		dojo.event.connect(publisherObject, publisherMethod, this, "sendMessage");
	}

	this.sendMessage = function(message){
		// The message has been propagated
	}
}


dojo.provide("dojo.event.browser");
dojo.require("dojo.event");

// FIXME: any particular reason this is in the global scope?
dojo._ie_clobber = new function(){
	this.clobberNodes = [];

	function nukeProp(node, prop){
		// try{ node.removeAttribute(prop); 	}catch(e){ /* squelch */ }
		try{ node[prop] = null; 			}catch(e){ /* squelch */ }
		try{ delete node[prop]; 			}catch(e){ /* squelch */ }
		// FIXME: JotLive needs this, but I'm not sure if it's too slow or not
		try{ node.removeAttribute(prop);	}catch(e){ /* squelch */ }
	}

	this.clobber = function(nodeRef){
		var na;
		var tna;
		if(nodeRef){
			tna = nodeRef.all || nodeRef.getElementsByTagName("*");
			na = [nodeRef];
			for(var x=0; x<tna.length; x++){
				// if we're gonna be clobbering the thing, at least make sure
				// we aren't trying to do it twice
				if(tna[x]["__doClobber__"]){
					na.push(tna[x]);
				}
			}
		}else{
			try{ window.onload = null; }catch(e){}
			na = (this.clobberNodes.length) ? this.clobberNodes : document.all;
		}
		tna = null;
		var basis = {};
		for(var i = na.length-1; i>=0; i=i-1){
			var el = na[i];
			if(el["__clobberAttrs__"]){
				for(var j=0; j<el.__clobberAttrs__.length; j++){
					nukeProp(el, el.__clobberAttrs__[j]);
				}
				nukeProp(el, "__clobberAttrs__");
				nukeProp(el, "__doClobber__");
			}
		}
		na = null;
	}
}

if(dojo.render.html.ie){
	dojo.addOnUnload(function(){
		dojo._ie_clobber.clobber();
		try{
			if((dojo["widget"])&&(dojo.widget["manager"])){
				dojo.widget.manager.destroyAll();
			}
		}catch(e){}
		try{ window.onload = null; }catch(e){}
		try{ window.onunload = null; }catch(e){}
		dojo._ie_clobber.clobberNodes = [];
		// CollectGarbage();
	});
}

dojo.event.browser = new function(){

	var clobberIdx = 0;

	this.clean = function(node){
		if(dojo.render.html.ie){ 
			dojo._ie_clobber.clobber(node);
		}
	}

	this.addClobberNode = function(node){
		if(!dojo.render.html.ie){ return; }
		if(!node["__doClobber__"]){
			node.__doClobber__ = true;
			dojo._ie_clobber.clobberNodes.push(node);
			// this might not be the most efficient thing to do, but it's
			// much less error prone than other approaches which were
			// previously tried and failed
			node.__clobberAttrs__ = [];
		}
	}

	this.addClobberNodeAttrs = function(node, props){
		if(!dojo.render.html.ie){ return; }
		this.addClobberNode(node);
		for(var x=0; x<props.length; x++){
			node.__clobberAttrs__.push(props[x]);
		}
	}

	this.removeListener = function(node, evtName, fp, capture){
		if(!capture){ var capture = false; }
		evtName = evtName.toLowerCase();
		if(evtName.substr(0,2)=="on"){ evtName = evtName.substr(2); }
		// FIXME: this is mostly a punt, we aren't actually doing anything on IE
		if(node.removeEventListener){
			node.removeEventListener(evtName, fp, capture);
		}
	}

	this.addListener = function(node, evtName, fp, capture, dontFix){
		if(!node){ return; } // FIXME: log and/or bail?
		if(!capture){ var capture = false; }
		evtName = evtName.toLowerCase();
		if(evtName.substr(0,2)!="on"){ evtName = "on"+evtName; }

		if(!dontFix){
			// build yet another closure around fp in order to inject fixEvent
			// around the resulting event
			var newfp = function(evt){
				if(!evt){ evt = window.event; }
				var ret = fp(dojo.event.browser.fixEvent(evt, this));
				if(capture){
					dojo.event.browser.stopEvent(evt);
				}
				return ret;
			}
		}else{
			newfp = fp;
		}

		if(node.addEventListener){ 
			node.addEventListener(evtName.substr(2), newfp, capture);
			return newfp;
		}else{
			if(typeof node[evtName] == "function" ){
				var oldEvt = node[evtName];
				node[evtName] = function(e){
					oldEvt(e);
					return newfp(e);
				}
			}else{
				node[evtName]=newfp;
			}
			if(dojo.render.html.ie){
				this.addClobberNodeAttrs(node, [evtName]);
			}
			return newfp;
		}
	}

	this.isEvent = function(obj){
		// FIXME: event detection hack ... could test for additional attributes
		// if necessary
		return (typeof obj != "undefined")&&(typeof Event != "undefined")&&(obj.eventPhase);
		// Event does not support instanceof in Opera, otherwise:
		//return (typeof Event != "undefined")&&(obj instanceof Event);
	}

	this.currentEvent = null;
	
	this.callListener = function(listener, curTarget){
		if(typeof listener != 'function'){
			dojo.raise("listener not a function: " + listener);
		}
		dojo.event.browser.currentEvent.currentTarget = curTarget;
		return listener.call(curTarget, dojo.event.browser.currentEvent);
	}

	this.stopPropagation = function(){
		dojo.event.browser.currentEvent.cancelBubble = true;
	}

	this.preventDefault = function(){
	  dojo.event.browser.currentEvent.returnValue = false;
	}

	this.keys = {
		KEY_BACKSPACE: 8,
		KEY_TAB: 9,
		KEY_ENTER: 13,
		KEY_SHIFT: 16,
		KEY_CTRL: 17,
		KEY_ALT: 18,
		KEY_PAUSE: 19,
		KEY_CAPS_LOCK: 20,
		KEY_ESCAPE: 27,
		KEY_SPACE: 32,
		KEY_PAGE_UP: 33,
		KEY_PAGE_DOWN: 34,
		KEY_END: 35,
		KEY_HOME: 36,
		KEY_LEFT_ARROW: 37,
		KEY_UP_ARROW: 38,
		KEY_RIGHT_ARROW: 39,
		KEY_DOWN_ARROW: 40,
		KEY_INSERT: 45,
		KEY_DELETE: 46,
		KEY_LEFT_WINDOW: 91,
		KEY_RIGHT_WINDOW: 92,
		KEY_SELECT: 93,
		KEY_F1: 112,
		KEY_F2: 113,
		KEY_F3: 114,
		KEY_F4: 115,
		KEY_F5: 116,
		KEY_F6: 117,
		KEY_F7: 118,
		KEY_F8: 119,
		KEY_F9: 120,
		KEY_F10: 121,
		KEY_F11: 122,
		KEY_F12: 123,
		KEY_NUM_LOCK: 144,
		KEY_SCROLL_LOCK: 145
	};

	// reverse lookup
	this.revKeys = [];
	for(var key in this.keys){
		this.revKeys[this.keys[key]] = key;
	}

	this.fixEvent = function(evt, sender){
		if((!evt)&&(window["event"])){
			var evt = window.event;
		}
		
		if((evt["type"])&&(evt["type"].indexOf("key") == 0)){ // key events
			evt.keys = this.revKeys;
			// FIXME: how can we eliminate this iteration?
			for(var key in this.keys) {
				evt[key] = this.keys[key];
			}
			if((dojo.render.html.ie)&&(evt["type"] == "keypress")){
				evt.charCode = evt.keyCode;
			}
		}
	
		if(dojo.render.html.ie){
			if(!evt.target){ evt.target = evt.srcElement; }
			if(!evt.currentTarget){ evt.currentTarget = (sender ? sender : evt.srcElement); }
			if(!evt.layerX){ evt.layerX = evt.offsetX; }
			if(!evt.layerY){ evt.layerY = evt.offsetY; }
			// FIXME: scroll position query is duped from dojo.html to avoid dependency on that entire module
			var docBody = ((dojo.render.html.ie55)||(document["compatMode"] == "BackCompat")) ? document.body : document.documentElement;
			if(!evt.pageX){ evt.pageX = evt.clientX + (docBody.scrollLeft || 0) }
			if(!evt.pageY){ evt.pageY = evt.clientY + (docBody.scrollTop || 0) }
			// mouseover
			if(evt.type == "mouseover"){ evt.relatedTarget = evt.fromElement; }
			// mouseout
			if(evt.type == "mouseout"){ evt.relatedTarget = evt.toElement; }
			this.currentEvent = evt;
			evt.callListener = this.callListener;
			evt.stopPropagation = this.stopPropagation;
			evt.preventDefault = this.preventDefault;
		}
		return evt;
	}

	this.stopEvent = function(ev) {
		if(window.event){
			ev.returnValue = false;
			ev.cancelBubble = true;
		}else{
			ev.preventDefault();
			ev.stopPropagation();
		}
	}
}

dojo.kwCompoundRequire({
	common: ["dojo.event", "dojo.event.topic"],
	browser: ["dojo.event.browser"],
	dashboard: ["dojo.event.browser"]
});
dojo.provide("dojo.event.*");

dojo.provide("dojo.lfx.Animation");
dojo.provide("dojo.lfx.Line");

dojo.require("dojo.lang.func");

/*
	Animation package based on Dan Pupius' work: http://pupius.co.uk/js/Toolkit.Drawing.js
*/
dojo.lfx.Line = function(start, end){
	this.start = start;
	this.end = end;
	if(dojo.lang.isArray(start)){
		var diff = [];
		dojo.lang.forEach(this.start, function(s,i){
			diff[i] = this.end[i] - s;
		}, this);
		
		this.getValue = function(/*float*/ n){
			var res = [];
			dojo.lang.forEach(this.start, function(s, i){
				res[i] = (diff[i] * n) + s;
			}, this);
			return res;
		}
	}else{
		var diff = end - start;
			
		this.getValue = function(/*float*/ n){
			//	summary: returns the point on the line
			//	n: a floating point number greater than 0 and less than 1
			return (diff * n) + this.start;
		}
	}
}

dojo.lfx.easeIn = function(n){
	//	summary: returns the point on an easing curve
	//	n: a floating point number greater than 0 and less than 1
	return Math.pow(n, 3);
}

dojo.lfx.easeOut = function(n){
	//	summary: returns the point on the line
	//	n: a floating point number greater than 0 and less than 1
	return ( 1 - Math.pow(1 - n, 3) );
}

dojo.lfx.easeInOut = function(n){
	//	summary: returns the point on the line
	//	n: a floating point number greater than 0 and less than 1
	return ( (3 * Math.pow(n, 2)) - (2 * Math.pow(n, 3)) );
}

dojo.lfx.IAnimation = function(){}
dojo.lang.extend(dojo.lfx.IAnimation, {
	// public properties
	curve: null,
	duration: 1000,
	easing: null,
	repeatCount: 0,
	rate: 25,
	
	// events
	handler: null,
	beforeBegin: null,
	onBegin: null,
	onAnimate: null,
	onEnd: null,
	onPlay: null,
	onPause: null,
	onStop: null,
	
	// public methods
	play: null,
	pause: null,
	stop: null,
	
	fire: function(evt, args){
		if(this[evt]){
			this[evt].apply(this, (args||[]));
		}
	},
	
	// private properties
	_active: false,
	_paused: false
});

dojo.lfx.Animation = function(/*Object*/ handlers, /*int*/ duration, /*Array*/ curve, /*function*/ easing, /*int*/ repeatCount, /*int*/ rate){
	//	summary
	//		a generic animation object that fires callbacks into it's handlers
	//		object at various states
	//	handlers
	//		object { 
	//			handler: function(){}, 
	//			onstart: function(){}, 
	//			onstop: function(){}, 
	//			onanimate: function(){}
	//		}
	dojo.lfx.IAnimation.call(this);
	if(dojo.lang.isNumber(handlers)||(!handlers && duration.getValue)){
		// no handlers argument:
		rate = repeatCount;
		repeatCount = easing;
		easing = curve;
		curve = duration;
		duration = handlers;
		handlers = null;
	}else if(handlers.getValue||dojo.lang.isArray(handlers)){
		// no handlers or duration:
		rate = easing;
		repeatCount = curve;
		easing = duration;
		curve = handlers;
		duration = null;
		handlers = null;
	}
	if(dojo.lang.isArray(curve)){
		this.curve = new dojo.lfx.Line(curve[0], curve[1]);
	}else{
		this.curve = curve;
	}
	if(duration != null && duration > 0){ this.duration = duration; }
	if(repeatCount){ this.repeatCount = repeatCount; }
	if(rate){ this.rate = rate; }
	if(handlers){
		this.handler = handlers.handler;
		this.beforeBegin = handlers.beforeBegin;
		this.onBegin = handlers.onBegin;
		this.onEnd = handlers.onEnd;
		this.onPlay = handlers.onPlay;
		this.onPause = handlers.onPause;
		this.onStop = handlers.onStop;
		this.onAnimate = handlers.onAnimate;
	}
	if(easing && dojo.lang.isFunction(easing)){
		this.easing=easing;
	}
}
dojo.inherits(dojo.lfx.Animation, dojo.lfx.IAnimation);
dojo.lang.extend(dojo.lfx.Animation, {
	// "private" properties
	_startTime: null,
	_endTime: null,
	_timer: null,
	_percent: 0,
	_startRepeatCount: 0,

	// public methods
	play: function(delay, gotoStart){
		if(gotoStart){
			clearTimeout(this._timer);
			this._active = false;
			this._paused = false;
			this._percent = 0;
		}else if(this._active && !this._paused){
			return this;
		}
		
		this.fire("handler", ["beforeBegin"]);
		this.fire("beforeBegin");

		if(delay > 0){
			setTimeout(dojo.lang.hitch(this, function(){ this.play(null, gotoStart); }), delay);
			return this;
		}
		
		this._startTime = new Date().valueOf();
		if(this._paused){
			this._startTime -= (this.duration * this._percent / 100);
		}
		this._endTime = this._startTime + this.duration;

		this._active = true;
		this._paused = false;
		
		var step = this._percent / 100;
		var value = this.curve.getValue(step);
		if( this._percent == 0 ) {
			if(!this._startRepeatCount) {
				this._startRepeatCount = this.repeatCount;
			}
			this.fire("handler", ["begin", value]);
			this.fire("onBegin", [value]);
		}

		this.fire("handler", ["play", value]);
		this.fire("onPlay", [value]);

		this._cycle();
		return this;
	},

	pause: function() {
		clearTimeout(this._timer);
		if(!this._active){ return this; }
		this._paused = true;
		var value = this.curve.getValue(this._percent / 100);
		this.fire("handler", ["pause", value]);
		this.fire("onPause", [value]);
		return this;
	},

	gotoPercent: function(pct, andPlay) {
		clearTimeout(this._timer);
		this._active = true;
		this._paused = true;
		this._percent = pct;
		if( andPlay ) { this.play(); }
	},

	stop: function(gotoEnd) {
		clearTimeout(this._timer);
		var step = this._percent / 100;
		if( gotoEnd ) {
			step = 1;
		}
		var value = this.curve.getValue(step);
		this.fire("handler", ["stop", value]);
		this.fire("onStop", [value]);
		this._active = false;
		this._paused = false;
		return this;
	},

	status: function() {
		if( this._active ) {
			return this._paused ? "paused" : "playing";
		} else {
			return "stopped";
		}
	},

	// "private" methods
	_cycle: function() {
		clearTimeout(this._timer);
		if(this._active){
			var curr = new Date().valueOf();
			var step = (curr - this._startTime) / (this._endTime - this._startTime);

			if(step >= 1){
				step = 1;
				this._percent = 100;
			}else{
				this._percent = step * 100;
			}
			
			// Perform easing
			if((this.easing)&&(dojo.lang.isFunction(this.easing))){
				step = this.easing(step);
			}

			var value = this.curve.getValue(step);
			this.fire("handler", ["animate", value]);
			this.fire("onAnimate", [value]);

			if( step < 1 ) {
				this._timer = setTimeout(dojo.lang.hitch(this, "_cycle"), this.rate);
			} else {
				this._active = false;
				this.fire("handler", ["end"]);
				this.fire("onEnd");

				if( this.repeatCount > 0 ) {
					this.repeatCount--;
					this.play(null, true);
				} else if( this.repeatCount == -1 ) {
					this.play(null, true);
				} else {
					if(this._startRepeatCount) {
						this.repeatCount = this._startRepeatCount;
						this._startRepeatCount = 0;
					}
				}
			}
		}
		return this;
	}
});

dojo.lfx.Combine = function(){
	dojo.lfx.IAnimation.call(this);
	this._anims = [];
	this._animsEnded = 0;
	
	var anims = arguments;
	if(anims.length == 1 && (dojo.lang.isArray(anims[0]) || dojo.lang.isArrayLike(anims[0]))){
		anims = anims[0];
	}
	
	var _this = this;
	dojo.lang.forEach(anims, function(anim){
		_this._anims.push(anim);
		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		anim.onEnd = function(){ oldOnEnd(); _this._onAnimsEnded(); };
	});
}
dojo.inherits(dojo.lfx.Combine, dojo.lfx.IAnimation);
dojo.lang.extend(dojo.lfx.Combine, {
	// private members
	_animsEnded: 0,
	
	// public methods
	play: function(delay, gotoStart){
		if( !this._anims.length ){ return this; }

		this.fire("beforeBegin");

		if(delay > 0){
			setTimeout(dojo.lang.hitch(this, function(){ this.play(null, gotoStart); }), delay);
			return this;
		}
		
		if(gotoStart || this._anims[0].percent == 0){
			this.fire("onBegin");
		}
		this.fire("onPlay");
		this._animsCall("play", null, gotoStart);
		return this;
	},
	
	pause: function(){
		this.fire("onPause");
		this._animsCall("pause"); 
		return this;
	},
	
	stop: function(gotoEnd){
		this.fire("onStop");
		this._animsCall("stop", gotoEnd);
		return this;
	},
	
	// private methods
	_onAnimsEnded: function(){
		this._animsEnded++;
		if(this._animsEnded >= this._anims.length){
			this.fire("onEnd");
		}
		return this;
	},
	
	_animsCall: function(funcName){
		var args = [];
		if(arguments.length > 1){
			for(var i = 1 ; i < arguments.length ; i++){
				args.push(arguments[i]);
			}
		}
		var _this = this;
		dojo.lang.forEach(this._anims, function(anim){
			anim[funcName](args);
		}, _this);
		return this;
	}
});

dojo.lfx.Chain = function() {
	dojo.lfx.IAnimation.call(this);
	this._anims = [];
	this._currAnim = -1;
	
	var anims = arguments;
	if(anims.length == 1 && (dojo.lang.isArray(anims[0]) || dojo.lang.isArrayLike(anims[0]))){
		anims = anims[0];
	}
	
	var _this = this;
	dojo.lang.forEach(anims, function(anim, i, anims_arr){
		_this._anims.push(anim);
		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		if(i < anims_arr.length - 1){
			anim.onEnd = function(){ oldOnEnd(); _this._playNext(); };
		}else{
			anim.onEnd = function(){ oldOnEnd(); _this.fire("onEnd"); };
		}
	}, _this);
}
dojo.inherits(dojo.lfx.Chain, dojo.lfx.IAnimation);
dojo.lang.extend(dojo.lfx.Chain, {
	// private members
	_currAnim: -1,
	
	// public methods
	play: function(delay, gotoStart){
		if( !this._anims.length ) { return this; }
		if( gotoStart || !this._anims[this._currAnim] ) {
			this._currAnim = 0;
		}

		var currentAnimation = this._anims[this._currAnim];

		this.fire("beforeBegin");
		if(delay > 0){
			setTimeout(dojo.lang.hitch(this, function(){ this.play(null, gotoStart); }), delay);
			return this;
		}
		
		if(currentAnimation){
			if(this._currAnim == 0){
				this.fire("handler", ["begin", this._currAnim]);
				this.fire("onBegin", [this._currAnim]);
			}
			this.fire("onPlay", [this._currAnim]);
			currentAnimation.play(null, gotoStart);
		}
		return this;
	},
	
	pause: function(){
		if( this._anims[this._currAnim] ) {
			this._anims[this._currAnim].pause();
			this.fire("onPause", [this._currAnim]);
		}
		return this;
	},
	
	playPause: function(){
		if(this._anims.length == 0){ return this; }
		if(this._currAnim == -1){ this._currAnim = 0; }
		var currAnim = this._anims[this._currAnim];
		if( currAnim ) {
			if( !currAnim._active || currAnim._paused ) {
				this.play();
			} else {
				this.pause();
			}
		}
		return this;
	},
	
	stop: function(){
		var currAnim = this._anims[this._currAnim];
		if(currAnim){
			currAnim.stop();
			this.fire("onStop", [this._currAnim]);
		}
		return currAnim;
	},
	
	// private methods
	_playNext: function(){
		if( this._currAnim == -1 || this._anims.length == 0 ) { return this; }
		this._currAnim++;
		if( this._anims[this._currAnim] ){
			this._anims[this._currAnim].play(null, true);
		}
		return this;
	}
});

dojo.lfx.combine = function(){
	var anims = arguments;
	if(dojo.lang.isArray(arguments[0])){
		anims = arguments[0];
	}
	return new dojo.lfx.Combine(anims);
}

dojo.lfx.chain = function(){
	var anims = arguments;
	if(dojo.lang.isArray(arguments[0])){
		anims = arguments[0];
	}
	return new dojo.lfx.Chain(anims);
}

dojo.provide("dojo.graphics.color");
dojo.require("dojo.lang.array");

// TODO: rewrite the "x2y" methods to take advantage of the parsing
//       abilities of the Color object. Also, beef up the Color
//       object (as possible) to parse most common formats

// takes an r, g, b, a(lpha) value, [r, g, b, a] array, "rgb(...)" string, hex string (#aaa, #aaaaaa, aaaaaaa)
dojo.graphics.color.Color = function(r, g, b, a) {
	// dojo.debug("r:", r[0], "g:", r[1], "b:", r[2]);
	if(dojo.lang.isArray(r)) {
		this.r = r[0];
		this.g = r[1];
		this.b = r[2];
		this.a = r[3]||1.0;
	} else if(dojo.lang.isString(r)) {
		var rgb = dojo.graphics.color.extractRGB(r);
		this.r = rgb[0];
		this.g = rgb[1];
		this.b = rgb[2];
		this.a = g||1.0;
	} else if(r instanceof dojo.graphics.color.Color) {
		this.r = r.r;
		this.b = r.b;
		this.g = r.g;
		this.a = r.a;
	} else {
		this.r = r;
		this.g = g;
		this.b = b;
		this.a = a;
	}
}

dojo.graphics.color.Color.fromArray = function(arr) {
	return new dojo.graphics.color.Color(arr[0], arr[1], arr[2], arr[3]);
}

dojo.lang.extend(dojo.graphics.color.Color, {
	toRgb: function(includeAlpha) {
		if(includeAlpha) {
			return this.toRgba();
		} else {
			return [this.r, this.g, this.b];
		}
	},

	toRgba: function() {
		return [this.r, this.g, this.b, this.a];
	},

	toHex: function() {
		return dojo.graphics.color.rgb2hex(this.toRgb());
	},

	toCss: function() {
		return "rgb(" + this.toRgb().join() + ")";
	},

	toString: function() {
		return this.toHex(); // decent default?
	},

	blend: function(color, weight) {
		return dojo.graphics.color.blend(this.toRgb(), new dojo.graphics.color.Color(color).toRgb(), weight);
	}
});

dojo.graphics.color.named = {
	white:      [255,255,255],
	black:      [0,0,0],
	red:        [255,0,0],
	green:	    [0,255,0],
	blue:       [0,0,255],
	navy:       [0,0,128],
	gray:       [128,128,128],
	silver:     [192,192,192]
};

// blend colors a and b (both as RGB array or hex strings) with weight from -1 to +1, 0 being a 50/50 blend
dojo.graphics.color.blend = function(a, b, weight) {
	if(typeof a == "string") { return dojo.graphics.color.blendHex(a, b, weight); }
	if(!weight) { weight = 0; }
	else if(weight > 1) { weight = 1; }
	else if(weight < -1) { weight = -1; }
	var c = new Array(3);
	for(var i = 0; i < 3; i++) {
		var half = Math.abs(a[i] - b[i])/2;
		c[i] = Math.floor(Math.min(a[i], b[i]) + half + (half * weight));
	}
	return c;
}

// very convenient blend that takes and returns hex values
// (will get called automatically by blend when blend gets strings)
dojo.graphics.color.blendHex = function(a, b, weight) {
	return dojo.graphics.color.rgb2hex(dojo.graphics.color.blend(dojo.graphics.color.hex2rgb(a), dojo.graphics.color.hex2rgb(b), weight));
}

// get RGB array from css-style color declarations
dojo.graphics.color.extractRGB = function(color) {
	var hex = "0123456789abcdef";
	color = color.toLowerCase();
	if( color.indexOf("rgb") == 0 ) {
		var matches = color.match(/rgba*\((\d+), *(\d+), *(\d+)/i);
		var ret = matches.splice(1, 3);
		return ret;
	} else {
		var colors = dojo.graphics.color.hex2rgb(color);
		if(colors) {
			return colors;
		} else {
			// named color (how many do we support?)
			return dojo.graphics.color.named[color] || [255, 255, 255];
		}
	}
}

dojo.graphics.color.hex2rgb = function(hex) {
	var hexNum = "0123456789ABCDEF";
	var rgb = new Array(3);
	if( hex.indexOf("#") == 0 ) { hex = hex.substring(1); }
	hex = hex.toUpperCase();
	if(hex.replace(new RegExp("["+hexNum+"]", "g"), "") != "") {
		return null;
	}
	if( hex.length == 3 ) {
		rgb[0] = hex.charAt(0) + hex.charAt(0)
		rgb[1] = hex.charAt(1) + hex.charAt(1)
		rgb[2] = hex.charAt(2) + hex.charAt(2);
	} else {
		rgb[0] = hex.substring(0, 2);
		rgb[1] = hex.substring(2, 4);
		rgb[2] = hex.substring(4);
	}
	for(var i = 0; i < rgb.length; i++) {
		rgb[i] = hexNum.indexOf(rgb[i].charAt(0)) * 16 + hexNum.indexOf(rgb[i].charAt(1));
	}
	return rgb;
}

dojo.graphics.color.rgb2hex = function(r, g, b) {
	if(dojo.lang.isArray(r)) {
		g = r[1] || 0;
		b = r[2] || 0;
		r = r[0] || 0;
	}
	var ret = dojo.lang.map([r, g, b], function(x) {
		x = new Number(x);
		var s = x.toString(16);
		while(s.length < 2) { s = "0" + s; }
		return s;
	});
	ret.unshift("#");
	return ret.join("");
}

dojo.provide("dojo.uri.Uri");

dojo.uri = new function() {
	this.joinPath = function() {
		// DEPRECATED: use the dojo.uri.Uri object instead
		var arr = [];
		for(var i = 0; i < arguments.length; i++) { arr.push(arguments[i]); }
		return arr.join("/").replace(/\/{2,}/g, "/").replace(/((https*|ftps*):)/i, "$1/");
	}
	
	this.dojoUri = function (uri) {
		// returns a Uri object resolved relative to the dojo root
		return new dojo.uri.Uri(dojo.hostenv.getBaseScriptUri(), uri);
	}
		
	this.Uri = function (/*uri1, uri2, [...]*/) {
		// An object representing a Uri.
		// Each argument is evaluated in order relative to the next until
		// a conanical uri is producued. To get an absolute Uri relative
		// to the current document use
		//      new dojo.uri.Uri(document.baseURI, uri)

		// TODO: support for IPv6, see RFC 2732

		// resolve uri components relative to each other
		var uri = arguments[0];
		for (var i = 1; i < arguments.length; i++) {
			if(!arguments[i]) { continue; }

			// Safari doesn't support this.constructor so we have to be explicit
			var relobj = new dojo.uri.Uri(arguments[i].toString());
			var uriobj = new dojo.uri.Uri(uri.toString());

			if (relobj.path == "" && relobj.scheme == null &&
				relobj.authority == null && relobj.query == null) {
				if (relobj.fragment != null) { uriobj.fragment = relobj.fragment; }
				relobj = uriobj;
			} else if (relobj.scheme == null) {
				relobj.scheme = uriobj.scheme;
			
				if (relobj.authority == null) {
					relobj.authority = uriobj.authority;
					
					if (relobj.path.charAt(0) != "/") {
						var path = uriobj.path.substring(0,
							uriobj.path.lastIndexOf("/") + 1) + relobj.path;

						var segs = path.split("/");
						for (var j = 0; j < segs.length; j++) {
							if (segs[j] == ".") {
								if (j == segs.length - 1) { segs[j] = ""; }
								else { segs.splice(j, 1); j--; }
							} else if (j > 0 && !(j == 1 && segs[0] == "") &&
								segs[j] == ".." && segs[j-1] != "..") {

								if (j == segs.length - 1) { segs.splice(j, 1); segs[j - 1] = ""; }
								else { segs.splice(j - 1, 2); j -= 2; }
							}
						}
						relobj.path = segs.join("/");
					}
				}
			}

			uri = "";
			if (relobj.scheme != null) { uri += relobj.scheme + ":"; }
			if (relobj.authority != null) { uri += "//" + relobj.authority; }
			uri += relobj.path;
			if (relobj.query != null) { uri += "?" + relobj.query; }
			if (relobj.fragment != null) { uri += "#" + relobj.fragment; }
		}

		this.uri = uri.toString();

		// break the uri into its main components
		var regexp = "^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\\?([^#]*))?(#(.*))?$";
	    var r = this.uri.match(new RegExp(regexp));

		this.scheme = r[2] || (r[1] ? "" : null);
		this.authority = r[4] || (r[3] ? "" : null);
		this.path = r[5]; // can never be undefined
		this.query = r[7] || (r[6] ? "" : null);
		this.fragment  = r[9] || (r[8] ? "" : null);
		
		if (this.authority != null) {
			// server based naming authority
			regexp = "^((([^:]+:)?([^@]+))@)?([^:]*)(:([0-9]+))?$";
			r = this.authority.match(new RegExp(regexp));
			
			this.user = r[3] || null;
			this.password = r[4] || null;
			this.host = r[5];
			this.port = r[7] || null;
		}
	
		this.toString = function(){ return this.uri; }
	}
};

dojo.provide("dojo.style");
dojo.require("dojo.graphics.color");
dojo.require("dojo.uri.Uri");
dojo.require("dojo.lang.common");

(function(){
	var h = dojo.render.html;
	var ds = dojo.style;
	var db = document["body"]||document["documentElement"];

	ds.boxSizing = {
		MARGIN_BOX: "margin-box",
		BORDER_BOX: "border-box",
		PADDING_BOX: "padding-box",
		CONTENT_BOX: "content-box"
	};
	var bs = ds.boxSizing;
	
	ds.getBoxSizing = function(node){
		if((h.ie)||(h.opera)){ 
			var cm = document["compatMode"];
			if((cm == "BackCompat")||(cm == "QuirksMode")){ 
				return bs.BORDER_BOX; 
			}else{
				return bs.CONTENT_BOX; 
			}
		}else{
			if(arguments.length == 0){ node = document.documentElement; }
			var sizing = ds.getStyle(node, "-moz-box-sizing");
			if(!sizing){ sizing = ds.getStyle(node, "box-sizing"); }
			return (sizing ? sizing : bs.CONTENT_BOX);
		}
	}

	/*

	The following several function use the dimensions shown below

		+-------------------------+
		|  margin                 |
		| +---------------------+ |
		| |  border             | |
		| | +-----------------+ | |
		| | |  padding        | | |
		| | | +-------------+ | | |
		| | | |   content   | | | |
		| | | +-------------+ | | |
		| | +-|-------------|-+ | |
		| +-|-|-------------|-|-+ |
		+-|-|-|-------------|-|-|-+
		| | | |             | | | |
		| | | |<- content ->| | | |
		| |<------ inner ------>| |
		|<-------- outer -------->|
		+-------------------------+

		* content-box

		|m|b|p|             |p|b|m|
		| |<------ offset ----->| |
		| | |<---- client --->| | |
		| | | |<-- width -->| | | |

		* border-box

		|m|b|p|             |p|b|m|
		| |<------ offset ----->| |
		| | |<---- client --->| | |
		| |<------ width ------>| |
	*/

	/*
		Notes:

		General:
			- Uncomputable values are returned as NaN.
			- setOuterWidth/Height return *false* if the outer size could not
			  be computed, otherwise *true*.
			- (sjmiles) knows no way to find the calculated values for auto-margins. 
			- All returned values are floating point in 'px' units. If a
			  non-zero computed style value is not specified in 'px', NaN is
			  returned.

		FF:
			- styles specified as '0' (unitless 0) show computed as '0pt'.

		IE:
			- clientWidth/Height are unreliable (0 unless the object has 'layout').
			- margins must be specified in px, or 0 (in any unit) for any
			  sizing function to work. Otherwise margins detect as 'auto'.
			- padding can be empty or, if specified, must be in px, or 0 (in
			  any unit) for any sizing function to work.

		Safari:
			- Safari defaults padding values to 'auto'.

		See the unit tests for examples of (un)computable values in a given browser.

	*/

	// FIXME: these work for some elements (e.g. DIV) but not others (e.g. TABLE, TEXTAREA)

	ds.isBorderBox = function(node){
		return (ds.getBoxSizing(node) == bs.BORDER_BOX);
	}

	ds.getUnitValue = function(node, cssSelector, autoIsZero){
		var s = ds.getComputedStyle(node, cssSelector);
		if((!s)||((s == 'auto')&&(autoIsZero))){ return { value: 0, units: 'px' }; }
		if(dojo.lang.isUndefined(s)){return ds.getUnitValue.bad;}
		// FIXME: is regex inefficient vs. parseInt or some manual test? 
		var match = s.match(/(\-?[\d.]+)([a-z%]*)/i);
		if (!match){return ds.getUnitValue.bad;}
		return { value: Number(match[1]), units: match[2].toLowerCase() };
	}
	// FIXME: 'bad' value should be 0?
	ds.getUnitValue.bad = { value: NaN, units: '' };
	
	ds.getPixelValue = function(node, cssSelector, autoIsZero){
		var result = ds.getUnitValue(node, cssSelector, autoIsZero);
		// FIXME: there is serious debate as to whether or not this is the right solution
		if(isNaN(result.value)){ return 0; }
		// FIXME: code exists for converting other units to px (see Dean Edward's IE7) 
		// but there are cross-browser complexities
		if((result.value)&&(result.units != 'px')){ return NaN; }
		return result.value;
	}
	
	// FIXME: deprecated
	ds.getNumericStyle = function() {
		dojo.deprecated('dojo.(style|html).getNumericStyle', 'in favor of dojo.(style|html).getPixelValue', '0.4');
		return ds.getPixelValue.apply(this, arguments); 
	}

	ds.setPositivePixelValue = function(node, selector, value){
		if(isNaN(value)){return false;}
		node.style[selector] = Math.max(0, value) + 'px'; 
		return true;
	}
	
	ds._sumPixelValues = function(node, selectors, autoIsZero){
		var total = 0;
		for(var x=0; x<selectors.length; x++){
			total += ds.getPixelValue(node, selectors[x], autoIsZero);
		}
		return total;
	}

	ds.isPositionAbsolute = function(node){
		return (ds.getComputedStyle(node, 'position') == 'absolute');
	}

	ds.getBorderExtent = function(node, side){
		return (ds.getStyle(node, 'border-' + side + '-style') == 'none' ? 0 : ds.getPixelValue(node, 'border-' + side + '-width'));
	}

	ds.getMarginWidth = function(node){
		return ds._sumPixelValues(node, ["margin-left", "margin-right"], ds.isPositionAbsolute(node));
	}

	ds.getBorderWidth = function(node){
		return ds.getBorderExtent(node, 'left') + ds.getBorderExtent(node, 'right');
	}

	ds.getPaddingWidth = function(node){
		return ds._sumPixelValues(node, ["padding-left", "padding-right"], true);
	}

	ds.getPadBorderWidth = function(node) {
		return ds.getPaddingWidth(node) + ds.getBorderWidth(node);
	}
	
	ds.getContentBoxWidth = function(node){
		node = dojo.byId(node);
		return node.offsetWidth - ds.getPadBorderWidth(node);
	}

	ds.getBorderBoxWidth = function(node){
		node = dojo.byId(node);
		return node.offsetWidth;
	}

	ds.getMarginBoxWidth = function(node){
		return ds.getInnerWidth(node) + ds.getMarginWidth(node);
	}

	ds.setContentBoxWidth = function(node, pxWidth){
		node = dojo.byId(node);
		if (ds.isBorderBox(node)){
			pxWidth += ds.getPadBorderWidth(node);
		}
		return ds.setPositivePixelValue(node, "width", pxWidth);
	}

	ds.setMarginBoxWidth = function(node, pxWidth){
		node = dojo.byId(node);
		if (!ds.isBorderBox(node)){
			pxWidth -= ds.getPadBorderWidth(node);
		}
		pxWidth -= ds.getMarginWidth(node);
		return ds.setPositivePixelValue(node, "width", pxWidth);
	}

	// FIXME: deprecate and remove
	ds.getContentWidth = ds.getContentBoxWidth;
	ds.getInnerWidth = ds.getBorderBoxWidth;
	ds.getOuterWidth = ds.getMarginBoxWidth;
	ds.setContentWidth = ds.setContentBoxWidth;
	ds.setOuterWidth = ds.setMarginBoxWidth;

	ds.getMarginHeight = function(node){
		return ds._sumPixelValues(node, ["margin-top", "margin-bottom"], ds.isPositionAbsolute(node));
	}

	ds.getBorderHeight = function(node){
		return ds.getBorderExtent(node, 'top') + ds.getBorderExtent(node, 'bottom');
	}

	ds.getPaddingHeight = function(node){
		return ds._sumPixelValues(node, ["padding-top", "padding-bottom"], true);
	}

	ds.getPadBorderHeight = function(node) {
		return ds.getPaddingHeight(node) + ds.getBorderHeight(node);
	}
	
	ds.getContentBoxHeight = function(node){
		node = dojo.byId(node);
		return node.offsetHeight - ds.getPadBorderHeight(node);
	}

	ds.getBorderBoxHeight = function(node){
		node = dojo.byId(node);
		return node.offsetHeight; // FIXME: does this work?
	}

	ds.getMarginBoxHeight = function(node){
		return ds.getInnerHeight(node) + ds.getMarginHeight(node);
	}

	ds.setContentBoxHeight = function(node, pxHeight){
		node = dojo.byId(node);
		if (ds.isBorderBox(node)){
			pxHeight += ds.getPadBorderHeight(node);
		}
		return ds.setPositivePixelValue(node, "height", pxHeight);
	}

	ds.setMarginBoxHeight = function(node, pxHeight){
		node = dojo.byId(node);
		if (!ds.isBorderBox(node)){
			pxHeight -= ds.getPadBorderHeight(node);
		}
		pxHeight -= ds.getMarginHeight(node);
		return ds.setPositivePixelValue(node, "height", pxHeight);
	}

	// FIXME: deprecate and remove
	ds.getContentHeight = ds.getContentBoxHeight;
	ds.getInnerHeight = ds.getBorderBoxHeight;
	ds.getOuterHeight = ds.getMarginBoxHeight;
	ds.setContentHeight = ds.setContentBoxHeight;
	ds.setOuterHeight = ds.setMarginBoxHeight;

	/**
	 * dojo.style.getAbsolutePosition(xyz, true) returns xyz's position relative to the document.
	 * Itells you where you would position a node
	 * inside document.body such that it was on top of xyz.  Most people set the flag to true when calling
	 * getAbsolutePosition().
	 *
	 * dojo.style.getAbsolutePosition(xyz, false) returns xyz's position relative to the viewport.
	 * It returns the position that would be returned
	 * by event.clientX/Y if the mouse were directly over the top/left of this node.
	 */
	ds.getAbsolutePosition = ds.abs = function(node, includeScroll){
		node = dojo.byId(node);
		var ret = [];
		ret.x = ret.y = 0;
		var st = dojo.html.getScrollTop();
		var sl = dojo.html.getScrollLeft();

		if(h.ie){
			with(node.getBoundingClientRect()){
				ret.x = left-2;
				ret.y = top-2;
			}
		}else if(document.getBoxObjectFor){
			// mozilla
			var bo = document.getBoxObjectFor(node);
			ret.x = bo.x - ds.sumAncestorProperties(node, "scrollLeft");
			ret.y = bo.y - ds.sumAncestorProperties(node, "scrollTop");
		}else{
			if(node["offsetParent"]){
				var endNode;		
				// in Safari, if the node is an absolutely positioned child of
				// the body and the body has a margin the offset of the child
				// and the body contain the body's margins, so we need to end
				// at the body
				if(	(h.safari)&&
					(node.style.getPropertyValue("position") == "absolute")&&
					(node.parentNode == db)){
					endNode = db;
				}else{
					endNode = db.parentNode;
				}

				if(node.parentNode != db){
					var nd = node;
					if(window.opera){ nd = db; }
					ret.x -= ds.sumAncestorProperties(nd, "scrollLeft");
					ret.y -= ds.sumAncestorProperties(nd, "scrollTop");
				}
				do{
					var n = node["offsetLeft"];
					ret.x += isNaN(n) ? 0 : n;
					var m = node["offsetTop"];
					ret.y += isNaN(m) ? 0 : m;
					node = node.offsetParent;
				}while((node != endNode)&&(node != null));
			}else if(node["x"]&&node["y"]){
				ret.x += isNaN(node.x) ? 0 : node.x;
				ret.y += isNaN(node.y) ? 0 : node.y;
			}
		}

		// account for document scrolling!
		if(includeScroll){
			ret.y += st;
			ret.x += sl;
		}

		ret[0] = ret.x;
		ret[1] = ret.y;
		return ret;
	}

	ds.sumAncestorProperties = function(node, prop){
		node = dojo.byId(node);
		if(!node){ return 0; } // FIXME: throw an error?
		
		var retVal = 0;
		while(node){
			var val = node[prop];
			if(val){
				retVal += val - 0;
				if(node==document.body){ break; }// opera and khtml #body & #html has the same values, we only need one value
			}
			node = node.parentNode;
		}
		return retVal;
	}

	ds.getTotalOffset = function(node, type, includeScroll){
		return ds.abs(node, includeScroll)[(type == "top") ? "y" : "x"];
	}

	ds.getAbsoluteX = ds.totalOffsetLeft = function(node, includeScroll){
		return ds.getTotalOffset(node, "left", includeScroll);
	}

	ds.getAbsoluteY = ds.totalOffsetTop = function(node, includeScroll){
		return ds.getTotalOffset(node, "top", includeScroll);
	}

	ds.styleSheet = null;

	// FIXME: this is a really basic stub for adding and removing cssRules, but
	// it assumes that you know the index of the cssRule that you want to add 
	// or remove, making it less than useful.  So we need something that can 
	// search for the selector that you you want to remove.
	ds.insertCssRule = function(selector, declaration, index) {
		if (!ds.styleSheet) {
			if (document.createStyleSheet) { // IE
				ds.styleSheet = document.createStyleSheet();
			} else if (document.styleSheets[0]) { // rest
				// FIXME: should create a new style sheet here
				// fall back on an exsiting style sheet
				ds.styleSheet = document.styleSheets[0];
			} else { return null; } // fail
		}

		if (arguments.length < 3) { // index may == 0
			if (ds.styleSheet.cssRules) { // W3
				index = ds.styleSheet.cssRules.length;
			} else if (ds.styleSheet.rules) { // IE
				index = ds.styleSheet.rules.length;
			} else { return null; } // fail
		}

		if (ds.styleSheet.insertRule) { // W3
			var rule = selector + " { " + declaration + " }";
			return ds.styleSheet.insertRule(rule, index);
		} else if (ds.styleSheet.addRule) { // IE
			return ds.styleSheet.addRule(selector, declaration, index);
		} else { return null; } // fail
	}

	ds.removeCssRule = function(index){
		if(!ds.styleSheet){
			dojo.debug("no stylesheet defined for removing rules");
			return false;
		}
		if(h.ie){
			if(!index){
				index = ds.styleSheet.rules.length;
				ds.styleSheet.removeRule(index);
			}
		}else if(document.styleSheets[0]){
			if(!index){
				index = ds.styleSheet.cssRules.length;
			}
			ds.styleSheet.deleteRule(index);
		}
		return true;
	}

	// calls css by XmlHTTP and inserts it into DOM as <style [widgetType="widgetType"]> *downloaded cssText*</style>
	ds.insertCssFile = function(URI, doc, checkDuplicates){
		if(!URI){ return; }
		if(!doc){ doc = document; }
		var cssStr = dojo.hostenv.getText(URI);
		cssStr = ds.fixPathsInCssText(cssStr, URI);

		if(checkDuplicates){
			var styles = doc.getElementsByTagName("style");
			var cssText = "";
			for(var i = 0; i<styles.length; i++){
				cssText = (styles[i].styleSheet && styles[i].styleSheet.cssText) ? styles[i].styleSheet.cssText : styles[i].innerHTML;
				if(cssStr == cssText){ return; }
			}
		}

		var style = ds.insertCssText(cssStr);
		// insert custom attribute ex dbgHref="../foo.css" usefull when debugging in DOM inspectors, no?
		if(style && djConfig.isDebug){
			style.setAttribute("dbgHref", URI);
		}
		return style
	}

	// DomNode Style  = insertCssText(String ".dojoMenu {color: green;}"[, DomDoc document, dojo.uri.Uri Url ])
	ds.insertCssText = function(cssStr, doc, URI){
		if(!cssStr){ return; }
		if(!doc){ doc = document; }
		if(URI){// fix paths in cssStr
			cssStr = ds.fixPathsInCssText(cssStr, URI);
		}
		var style = doc.createElement("style");
		style.setAttribute("type", "text/css");
		// IE is b0rken enough to require that we add the element to the doc
		// before changing it's properties
		var head = doc.getElementsByTagName("head")[0];
		if(!head){ // must have a head tag 
			dojo.debug("No head tag in document, aborting styles");
			return;
		}else{
			head.appendChild(style);
		}
		if(style.styleSheet){// IE
			style.styleSheet.cssText = cssStr;
		}else{ // w3c
			var cssText = doc.createTextNode(cssStr);
			style.appendChild(cssText);
		}
		return style;
	}

	// String cssText = fixPathsInCssText(String cssStr, dojo.uri.Uri URI)
	// usage: cssText comes from dojoroot/src/widget/templates/HtmlFoobar.css
	// 	it has .dojoFoo { background-image: url(images/bar.png);} 
	//	then uri should point to dojoroot/src/widget/templates/
	ds.fixPathsInCssText = function(cssStr, URI){
		if(!cssStr || !URI){ return; }
		var pos = 0; var str = ""; var url = "";
		while(pos!=-1){
			pos = 0;url = "";
			pos = cssStr.indexOf("url(", pos);
			if(pos<0){ break; }
			str += cssStr.slice(0,pos+4);
			cssStr = cssStr.substring(pos+4, cssStr.length);
			url += cssStr.match(/^[\t\s\w()\/.\\'"-:#=&?]*\)/)[0]; // url string
			cssStr = cssStr.substring(url.length-1, cssStr.length); // remove url from css string til next loop
			url = url.replace(/^[\s\t]*(['"]?)([\w()\/.\\'"-:#=&?]*)\1[\s\t]*?\)/,"$2"); // clean string
			if(url.search(/(file|https?|ftps?):\/\//)==-1){
				url = (new dojo.uri.Uri(URI,url).toString());
			}
			str += url;
		};
		return str+cssStr;
	}

	ds.getBackgroundColor = function(node) {
		node = dojo.byId(node);
		var color;
		do{
			color = ds.getStyle(node, "background-color");
			// Safari doesn't say "transparent"
			if(color.toLowerCase() == "rgba(0, 0, 0, 0)") { color = "transparent"; }
			if(node == document.getElementsByTagName("body")[0]) { node = null; break; }
			node = node.parentNode;
		}while(node && dojo.lang.inArray(color, ["transparent", ""]));
		if(color == "transparent"){
			color = [255, 255, 255, 0];
		}else{
			color = dojo.graphics.color.extractRGB(color);
		}
		return color;
	}

	ds.getComputedStyle = function(node, cssSelector, inValue){
		node = dojo.byId(node);
		// cssSelector may actually be in camel case, so force selector version
		var cssSelector = ds.toSelectorCase(cssSelector);
		var property = ds.toCamelCase(cssSelector);
		if(!node || !node.style){
			return inValue;
		}else if(document.defaultView){ // W3, gecko, KHTML
			try{			
				var cs = document.defaultView.getComputedStyle(node, "");
				if (cs){ 
					return cs.getPropertyValue(cssSelector);
				} 
			}catch(e){ // reports are that Safari can throw an exception above
				if (node.style.getPropertyValue){ // W3
					return node.style.getPropertyValue(cssSelector);
				}else return inValue;
			}
		}else if(node.currentStyle){ // IE
			return node.currentStyle[property];
		}if(node.style.getPropertyValue){ // W3
			return node.style.getPropertyValue(cssSelector);
		}else{
			return inValue;
		}
	}

	/** 
	 * Retrieve a property value from a node's style object.
	 */
	ds.getStyleProperty = function(node, cssSelector){
		node = dojo.byId(node);
		// FIXME: should we use node.style.getPropertyValue over style[property]?
		// style[property] works in all (modern) browsers, getPropertyValue is W3 but not supported in IE
		// FIXME: what about runtimeStyle?
		return (node && node.style ? node.style[ds.toCamelCase(cssSelector)] : undefined);
	}

	/** 
	 * Retrieve a property value from a node's style object.
	 */
	ds.getStyle = function(node, cssSelector){
		var value = ds.getStyleProperty(node, cssSelector);
		return (value ? value : ds.getComputedStyle(node, cssSelector));
	}

	ds.setStyle = function(node, cssSelector, value){
		node = dojo.byId(node);
		if(node && node.style){
			var camelCased = ds.toCamelCase(cssSelector);
			node.style[camelCased] = value;
		}
	}

	ds.toCamelCase = function(selector) {
		var arr = selector.split('-'), cc = arr[0];
		for(var i = 1; i < arr.length; i++) {
			cc += arr[i].charAt(0).toUpperCase() + arr[i].substring(1);
		}
		return cc;		
	}

	ds.toSelectorCase = function(selector) {
		return selector.replace(/([A-Z])/g, "-$1" ).toLowerCase() ;
	}

	/* float between 0.0 (transparent) and 1.0 (opaque) */
	ds.setOpacity = function setOpacity(node, opacity, dontFixOpacity) {
		node = dojo.byId(node);
		if(!dontFixOpacity){
			if( opacity >= 1.0){
				if(h.ie){
					ds.clearOpacity(node);
					return;
				}else{
					opacity = 0.999999;
				}
			}else if( opacity < 0.0){ opacity = 0; }
		}
		if(h.ie){
			if(node.nodeName.toLowerCase() == "tr"){
				// FIXME: is this too naive? will we get more than we want?
				var tds = node.getElementsByTagName("td");
				for(var x=0; x<tds.length; x++){
					tds[x].style.filter = "Alpha(Opacity="+opacity*100+")";
				}
			}
			node.style.filter = "Alpha(Opacity="+opacity*100+")";
		}else if(h.moz){
			node.style.opacity = opacity; // ffox 1.0 directly supports "opacity"
			node.style.MozOpacity = opacity;
		}else if(h.safari){
			node.style.opacity = opacity; // 1.3 directly supports "opacity"
			node.style.KhtmlOpacity = opacity;
		}else{
			node.style.opacity = opacity;
		}
	}
		
	ds.getOpacity = function getOpacity (node){
		node = dojo.byId(node);
		if(h.ie){
			var opac = (node.filters && node.filters.alpha &&
				typeof node.filters.alpha.opacity == "number"
				? node.filters.alpha.opacity : 100) / 100;
		}else{
			var opac = node.style.opacity || node.style.MozOpacity ||
				node.style.KhtmlOpacity || 1;
		}
		return opac >= 0.999999 ? 1.0 : Number(opac);
	}

	ds.clearOpacity = function clearOpacity(node){
		node = dojo.byId(node);
		var ns = node.style;
		if(h.ie){
			try {
				if( node.filters && node.filters.alpha ){
					ns.filter = ""; // FIXME: may get rid of other filter effects
				}
			} catch(e) {
				/*
				 * IE7 gives error if node.filters not set;
				 * don't know why or how to workaround (other than this)
				 */
			}
		}else if(h.moz){
			ns.opacity = 1;
			ns.MozOpacity = 1;
		}else if(h.safari){
			ns.opacity = 1;
			ns.KhtmlOpacity = 1;
		}else{
			ns.opacity = 1;
		}
	}

	/** 
	* Set the given style attributes for the node. 
	* Patch submitted by Wolfram Kriesing, 22/03/2006.
	*
	* Ie. dojo.style.setStyleAttributes(myNode, "position:absolute; left:10px; top:10px;") 
	* This just makes it easier to set a style directly without the need to  
	* override it completely (as node.setAttribute() would). 
	* If there is a dojo-method for an attribute, like for "opacity" there 
	* is setOpacity, the dojo method is called instead. 
	* For example: dojo.style.setStyleAttributes(myNode, "opacity: .4"); 
	*  
	* Additionally all the dojo.style.set* methods can also be used. 
	* Ie. when attributes contains "outer-height: 10;" it will call dojo.style.setOuterHeight("10"); 
	* 
	* @param object The node to set the style attributes for. 
	* @param string Ie. "position:absolute; left:10px; top:10px;" 
	*/ 
	ds.setStyleAttributes = function(node, attributes) { 
		var methodMap={ 
			"opacity":dojo.style.setOpacity,
			"content-height":dojo.style.setContentHeight,
			"content-width":dojo.style.setContentWidth,
			"outer-height":dojo.style.setOuterHeight,
			"outer-width":dojo.style.setOuterWidth 
		} 

		var splittedAttribs=attributes.replace(/(;)?\s*$/, "").split(";"); 
		for(var i=0; i<splittedAttribs.length; i++){ 
			var nameValue=splittedAttribs[i].split(":"); 
			var name=nameValue[0].replace(/\s*$/, "").replace(/^\s*/, "").toLowerCase();
			var value=nameValue[1].replace(/\s*$/, "").replace(/^\s*/, "");
			if(dojo.lang.has(methodMap,name)) { 
				methodMap[name](node,value); 
			} else { 
				node.style[dojo.style.toCamelCase(name)]=value; 
			} 
		} 
	} 

	ds._toggle = function(node, tester, setter){
		node = dojo.byId(node);
		setter(node, !tester(node));
		return tester(node);
	}

	// show/hide are library constructs

	// show() 
	// if the node.style.display == 'none' then 
	// set style.display to '' or the value cached by hide()
	ds.show = function(node){
		node = dojo.byId(node);
		if(ds.getStyleProperty(node, 'display')=='none'){
			ds.setStyle(node, 'display', (node.dojoDisplayCache||''));
			node.dojoDisplayCache = undefined;	// cannot use delete on a node in IE6
		}
	}

	// if the node.style.display == 'none' then 
	// set style.display to '' or the value cached by hide()
	ds.hide = function(node){
		node = dojo.byId(node);
		if(typeof node["dojoDisplayCache"] == "undefined"){ // it could == '', so we cannot say !node.dojoDisplayCount
			var d = ds.getStyleProperty(node, 'display')
			if(d!='none'){
				node.dojoDisplayCache = d;
			}
		}
		ds.setStyle(node, 'display', 'none');
	}

	// setShowing() calls show() if showing is true, hide() otherwise
	ds.setShowing = function(node, showing){
		ds[(showing ? 'show' : 'hide')](node);
	}

	// isShowing() is true if the node.style.display is not 'none'
	// FIXME: returns true if node is bad, isHidden would be easier to make correct
	ds.isShowing = function(node){
		return (ds.getStyleProperty(node, 'display') != 'none');
	}

	// Call setShowing() on node with the complement of isShowing(), then return the new value of isShowing()
	ds.toggleShowing = function(node){
		return ds._toggle(node, ds.isShowing, ds.setShowing);
	}

	// display is a CSS concept

	// Simple mapping of tag names to display values
	// FIXME: simplistic 
	ds.displayMap = { tr: '', td: '', th: '', img: 'inline', span: 'inline', input: 'inline', button: 'inline' };

	// Suggest a value for the display property that will show 'node' based on it's tag
	ds.suggestDisplayByTagName = function(node)
	{
		node = dojo.byId(node);
		if(node && node.tagName){
			var tag = node.tagName.toLowerCase();
			return (tag in ds.displayMap ? ds.displayMap[tag] : 'block');
		}
	}

	// setDisplay() sets the value of style.display to value of 'display' parameter if it is a string.
	// Otherwise, if 'display' is false, set style.display to 'none'.
	// Finally, set 'display' to a suggested display value based on the node's tag
	ds.setDisplay = function(node, display){
		ds.setStyle(node, 'display', (dojo.lang.isString(display) ? display : (display ? ds.suggestDisplayByTagName(node) : 'none')));
	}

	// isDisplayed() is true if the the computed display style for node is not 'none'
	// FIXME: returns true if node is bad, isNotDisplayed would be easier to make correct
	ds.isDisplayed = function(node){
		return (ds.getComputedStyle(node, 'display') != 'none');
	}

	// Call setDisplay() on node with the complement of isDisplayed(), then
	// return the new value of isDisplayed()
	ds.toggleDisplay = function(node){
		return ds._toggle(node, ds.isDisplayed, ds.setDisplay);
	}

	// visibility is a CSS concept

	// setVisibility() sets the value of style.visibility to value of
	// 'visibility' parameter if it is a string.
	// Otherwise, if 'visibility' is false, set style.visibility to 'hidden'.
	// Finally, set style.visibility to 'visible'.
	ds.setVisibility = function(node, visibility){
		ds.setStyle(node, 'visibility', (dojo.lang.isString(visibility) ? visibility : (visibility ? 'visible' : 'hidden')));
	}

	// isVisible() is true if the the computed visibility style for node is not 'hidden'
	// FIXME: returns true if node is bad, isInvisible would be easier to make correct
	ds.isVisible = function(node){
		return (ds.getComputedStyle(node, 'visibility') != 'hidden');
	}

	// Call setVisibility() on node with the complement of isVisible(), then
	// return the new value of isVisible()
	ds.toggleVisibility = function(node){
		return ds._toggle(node, ds.isVisible, ds.setVisibility);
	}

	// in: coordinate array [x,y,w,h] or dom node
	// return: coordinate array
	ds.toCoordinateArray = function(coords, includeScroll) {
		if(dojo.lang.isArray(coords)){
			// coords is already an array (of format [x,y,w,h]), just return it
			while ( coords.length < 4 ) { coords.push(0); }
			while ( coords.length > 4 ) { coords.pop(); }
			var ret = coords;
		} else {
			// coords is an dom object (or dom object id); return it's coordinates
			var node = dojo.byId(coords);
			var pos = ds.getAbsolutePosition(node, includeScroll);
			var ret = [
				pos.x,
				pos.y,
				ds.getBorderBoxWidth(node),
				ds.getBorderBoxHeight(node)
			];
		}
		ret.x = ret[0];
		ret.y = ret[1];
		ret.w = ret[2];
		ret.h = ret[3];
		return ret;
	};
})();

dojo.provide("dojo.html");

dojo.require("dojo.lang.func");
dojo.require("dojo.dom");
dojo.require("dojo.style");
dojo.require("dojo.string");

dojo.lang.mixin(dojo.html, dojo.dom);
dojo.lang.mixin(dojo.html, dojo.style);

// FIXME: we are going to assume that we can throw any and every rendering
// engine into the IE 5.x box model. In Mozilla, we do this w/ CSS.
// Need to investigate for KHTML and Opera

dojo.html.clearSelection = function(){
	try{
		if(window["getSelection"]){ 
			if(dojo.render.html.safari){
				// pulled from WebCore/ecma/kjs_window.cpp, line 2536
				window.getSelection().collapse();
			}else{
				window.getSelection().removeAllRanges();
			}
		}else if(document.selection){
			if(document.selection.empty){
				document.selection.empty();
			}else if(document.selection.clear){
				document.selection.clear();
			}
		}
		return true;
	}catch(e){
		dojo.debug(e);
		return false;
	}
}

dojo.html.disableSelection = function(element){
	element = dojo.byId(element)||document.body;
	var h = dojo.render.html;
	
	if(h.mozilla){
		element.style.MozUserSelect = "none";
	}else if(h.safari){
		element.style.KhtmlUserSelect = "none"; 
	}else if(h.ie){
		element.unselectable = "on";
	}else{
		return false;
	}
	return true;
}

dojo.html.enableSelection = function(element){
	element = dojo.byId(element)||document.body;
	
	var h = dojo.render.html;
	if(h.mozilla){ 
		element.style.MozUserSelect = ""; 
	}else if(h.safari){
		element.style.KhtmlUserSelect = "";
	}else if(h.ie){
		element.unselectable = "off";
	}else{
		return false;
	}
	return true;
}

dojo.html.selectElement = function(element){
	element = dojo.byId(element);
	if(document.selection && document.body.createTextRange){ // IE
		var range = document.body.createTextRange();
		range.moveToElementText(element);
		range.select();
	}else if(window["getSelection"]){
		var selection = window.getSelection();
		// FIXME: does this work on Safari?
		if(selection["selectAllChildren"]){ // Mozilla
			selection.selectAllChildren(element);
		}
	}
}

dojo.html.selectInputText = function(element){
	element = dojo.byId(element);
	if(document.selection && document.body.createTextRange){ // IE
		var range = element.createTextRange();
		range.moveStart("character", 0);
		range.moveEnd("character", element.value.length);
		range.select();
	}else if(window["getSelection"]){
		var selection = window.getSelection();
		// FIXME: does this work on Safari?
		element.setSelectionRange(0, element.value.length);
	}
	element.focus();
}


dojo.html.isSelectionCollapsed = function(){
	if(document["selection"]){ // IE
		return document.selection.createRange().text == "";
	}else if(window["getSelection"]){
		var selection = window.getSelection();
		if(dojo.lang.isString(selection)){ // Safari
			return selection == "";
		}else{ // Mozilla/W3
			return selection.isCollapsed;
		}
	}
}

dojo.html.getEventTarget = function(evt){
	if(!evt) { evt = window.event || {} };
	var t = (evt.srcElement ? evt.srcElement : (evt.target ? evt.target : null));
	while((t)&&(t.nodeType!=1)){ t = t.parentNode; }
	return t;
}

dojo.html.getDocumentWidth = function(){
	dojo.deprecated("dojo.html.getDocument*", "replaced by dojo.html.getViewport*", "0.4");
	return dojo.html.getViewportWidth();
}

dojo.html.getDocumentHeight = function(){
	dojo.deprecated("dojo.html.getDocument*", "replaced by dojo.html.getViewport*", "0.4");
	return dojo.html.getViewportHeight();
}

dojo.html.getDocumentSize = function(){
	dojo.deprecated("dojo.html.getDocument*", "replaced of dojo.html.getViewport*", "0.4");
	return dojo.html.getViewportSize();
}

dojo.html.getViewportWidth = function(){
	var w = 0;

	if(window.innerWidth){
		w = window.innerWidth;
	}

	if(dojo.exists(document, "documentElement.clientWidth")){
		// IE6 Strict
		var w2 = document.documentElement.clientWidth;
		// this lets us account for scrollbars
		if(!w || w2 && w2 < w) {
			w = w2;
		}
		return w;
	}

	if(document.body){
		// IE
		return document.body.clientWidth;
	}

	return 0;
}

dojo.html.getViewportHeight = function(){
	if (window.innerHeight){
		return window.innerHeight;
	}

	if (dojo.exists(document, "documentElement.clientHeight")){
		// IE6 Strict
		return document.documentElement.clientHeight;
	}

	if (document.body){
		// IE
		return document.body.clientHeight;
	}

	return 0;
}

dojo.html.getViewportSize = function(){
	var ret = [dojo.html.getViewportWidth(), dojo.html.getViewportHeight()];
	ret.w = ret[0];
	ret.h = ret[1];
	return ret;
}

dojo.html.getScrollTop = function(){
	return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
}

dojo.html.getScrollLeft = function(){
	return window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0;
}

dojo.html.getScrollOffset = function(){
	var off = [dojo.html.getScrollLeft(), dojo.html.getScrollTop()];
	off.x = off[0];
	off.y = off[1];
	return off;
}

dojo.html.getParentOfType = function(node, type){
	dojo.deprecated("dojo.html.getParentOfType", "replaced by dojo.html.getParentByType*", "0.4");
	return dojo.html.getParentByType(node, type);
}

dojo.html.getParentByType = function(node, type) {
	var parent = dojo.byId(node);
	type = type.toLowerCase();
	while((parent)&&(parent.nodeName.toLowerCase()!=type)){
		if(parent==(document["body"]||document["documentElement"])){
			return null;
		}
		parent = parent.parentNode;
	}
	return parent;
}

// RAR: this function comes from nwidgets and is more-or-less unmodified.
// We should probably look ant Burst and f(m)'s equivalents
dojo.html.getAttribute = function(node, attr){
	node = dojo.byId(node);
	// FIXME: need to add support for attr-specific accessors
	if((!node)||(!node.getAttribute)){
		// if(attr !== 'nwType'){
		//	alert("getAttr of '" + attr + "' with bad node"); 
		// }
		return null;
	}
	var ta = typeof attr == 'string' ? attr : new String(attr);

	// first try the approach most likely to succeed
	var v = node.getAttribute(ta.toUpperCase());
	if((v)&&(typeof v == 'string')&&(v!="")){ return v; }

	// try returning the attributes value, if we couldn't get it as a string
	if(v && v.value){ return v.value; }

	// this should work on Opera 7, but it's a little on the crashy side
	if((node.getAttributeNode)&&(node.getAttributeNode(ta))){
		return (node.getAttributeNode(ta)).value;
	}else if(node.getAttribute(ta)){
		return node.getAttribute(ta);
	}else if(node.getAttribute(ta.toLowerCase())){
		return node.getAttribute(ta.toLowerCase());
	}
	return null;
}
	
/**
 *	Determines whether or not the specified node carries a value for the
 *	attribute in question.
 */
dojo.html.hasAttribute = function(node, attr){
	node = dojo.byId(node);
	return dojo.html.getAttribute(node, attr) ? true : false;
}
	
/**
 * Returns the string value of the list of CSS classes currently assigned
 * directly to the node in question. Returns an empty string if no class attribute
 * is found;
 */
dojo.html.getClass = function(node){
	node = dojo.byId(node);
	if(!node){ return ""; }
	var cs = "";
	if(node.className){
		cs = node.className;
	}else if(dojo.html.hasAttribute(node, "class")){
		cs = dojo.html.getAttribute(node, "class");
	}
	return dojo.string.trim(cs);
}

/**
 * Returns an array of CSS classes currently assigned
 * directly to the node in question. Returns an empty array if no classes
 * are found;
 */
dojo.html.getClasses = function(node) {
	var c = dojo.html.getClass(node);
	return (c == "") ? [] : c.split(/\s+/g);
}

/**
 * Returns whether or not the specified classname is a portion of the
 * class list currently applied to the node. Does not cover cascaded
 * styles, only classes directly applied to the node.
 */
dojo.html.hasClass = function(node, classname){
	return dojo.lang.inArray(dojo.html.getClasses(node), classname);
}

/**
 * Adds the specified class to the beginning of the class list on the
 * passed node. This gives the specified class the highest precidence
 * when style cascading is calculated for the node. Returns true or
 * false; indicating success or failure of the operation, respectively.
 */
dojo.html.prependClass = function(node, classStr){
	classStr += " " + dojo.html.getClass(node);
	return dojo.html.setClass(node, classStr);
}

/**
 * Adds the specified class to the end of the class list on the
 *	passed &node;. Returns &true; or &false; indicating success or failure.
 */
dojo.html.addClass = function(node, classStr){
	if (dojo.html.hasClass(node, classStr)) {
	  return false;
	}
	classStr = dojo.string.trim(dojo.html.getClass(node) + " " + classStr);
	return dojo.html.setClass(node, classStr);
}

/**
 *	Clobbers the existing list of classes for the node, replacing it with
 *	the list given in the 2nd argument. Returns true or false
 *	indicating success or failure.
 */
dojo.html.setClass = function(node, classStr){
	node = dojo.byId(node);
	var cs = new String(classStr);
	try{
		if(typeof node.className == "string"){
			node.className = cs;
		}else if(node.setAttribute){
			node.setAttribute("class", classStr);
			node.className = cs;
		}else{
			return false;
		}
	}catch(e){
		dojo.debug("dojo.html.setClass() failed", e);
	}
	return true;
}

/**
 * Removes the className from the node;. Returns
 * true or false indicating success or failure.
 */ 
dojo.html.removeClass = function(node, classStr, allowPartialMatches){
	var classStr = dojo.string.trim(new String(classStr));

	try{
		var cs = dojo.html.getClasses(node);
		var nca	= [];
		if(allowPartialMatches){
			for(var i = 0; i<cs.length; i++){
				if(cs[i].indexOf(classStr) == -1){ 
					nca.push(cs[i]);
				}
			}
		}else{
			for(var i=0; i<cs.length; i++){
				if(cs[i] != classStr){ 
					nca.push(cs[i]);
				}
			}
		}
		dojo.html.setClass(node, nca.join(" "));
	}catch(e){
		dojo.debug("dojo.html.removeClass() failed", e);
	}

	return true;
}

/**
 * Replaces 'oldClass' and adds 'newClass' to node
 */
dojo.html.replaceClass = function(node, newClass, oldClass) {
	dojo.html.removeClass(node, oldClass);
	dojo.html.addClass(node, newClass);
}

// Enum type for getElementsByClass classMatchType arg:
dojo.html.classMatchType = {
	ContainsAll : 0, // all of the classes are part of the node's class (default)
	ContainsAny : 1, // any of the classes are part of the node's class
	IsOnly : 2 // only all of the classes are part of the node's class
}


/**
 * Returns an array of nodes for the given classStr, children of a
 * parent, and optionally of a certain nodeType
 */
dojo.html.getElementsByClass = function(classStr, parent, nodeType, classMatchType, useNonXpath){
	parent = dojo.byId(parent) || document;
	var classes = classStr.split(/\s+/g);
	var nodes = [];
	if( classMatchType != 1 && classMatchType != 2 ) classMatchType = 0; // make it enum
	var reClass = new RegExp("(\\s|^)((" + classes.join(")|(") + "))(\\s|$)");
	var candidateNodes = [];
	
	if(!useNonXpath && document.evaluate) { // supports dom 3 xpath
		var xpath = "//" + (nodeType || "*") + "[contains(";
		if(classMatchType != dojo.html.classMatchType.ContainsAny){
			xpath += "concat(' ',@class,' '), ' " +
			classes.join(" ') and contains(concat(' ',@class,' '), ' ") +
			" ')]";
		}else{
			xpath += "concat(' ',@class,' '), ' " +
			classes.join(" ')) or contains(concat(' ',@class,' '), ' ") +
			" ')]";
		}
		var xpathResult = document.evaluate(xpath, parent, null, XPathResult.ANY_TYPE, null);
		var result = xpathResult.iterateNext();
		while(result){
			try{
				candidateNodes.push(result);
				result = xpathResult.iterateNext();
			}catch(e){ break; }
		}
		return candidateNodes;
	}else{
		if(!nodeType){
			nodeType = "*";
		}
		candidateNodes = parent.getElementsByTagName(nodeType);

		var node, i = 0;
		outer:
		while(node = candidateNodes[i++]){
			var nodeClasses = dojo.html.getClasses(node);
			if(nodeClasses.length == 0){ continue outer; }
			var matches = 0;
	
			for(var j = 0; j < nodeClasses.length; j++){
				if(reClass.test(nodeClasses[j])){
					if(classMatchType == dojo.html.classMatchType.ContainsAny){
						nodes.push(node);
						continue outer;
					}else{
						matches++;
					}
				}else{
					if(classMatchType == dojo.html.classMatchType.IsOnly){
						continue outer;
					}
				}
			}
	
			if(matches == classes.length){
				if(	(classMatchType == dojo.html.classMatchType.IsOnly)&&
					(matches == nodeClasses.length)){
					nodes.push(node);
				}else if(classMatchType == dojo.html.classMatchType.ContainsAll){
					nodes.push(node);
				}
			}
		}
		return nodes;
	}
}

dojo.html.getElementsByClassName = dojo.html.getElementsByClass;

/**
 * Returns the mouse position relative to the document (not the viewport).
 * For example, if you have a document that is 10000px tall,
 * but your browser window is only 100px tall,
 * if you scroll to the bottom of the document and call this function it
 * will return {x: 0, y: 10000}
 */
dojo.html.getCursorPosition = function(e){
	e = e || window.event;
	var cursor = {x:0, y:0};
	if(e.pageX || e.pageY){
		cursor.x = e.pageX;
		cursor.y = e.pageY;
	}else{
		var de = document.documentElement;
		var db = document.body;
		cursor.x = e.clientX + ((de||db)["scrollLeft"]) - ((de||db)["clientLeft"]);
		cursor.y = e.clientY + ((de||db)["scrollTop"]) - ((de||db)["clientTop"]);
	}
	return cursor;
}

dojo.html.overElement = function(element, e){
	element = dojo.byId(element);
	var mouse = dojo.html.getCursorPosition(e);

	with(dojo.html){
		var top = getAbsoluteY(element, true);
		var bottom = top + getInnerHeight(element);
		var left = getAbsoluteX(element, true);
		var right = left + getInnerWidth(element);
	}
	
	return (mouse.x >= left && mouse.x <= right &&
		mouse.y >= top && mouse.y <= bottom);
}

dojo.html.setActiveStyleSheet = function(title){
	var i = 0, a, els = document.getElementsByTagName("link");
	while (a = els[i++]) {
		if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title")){
			a.disabled = true;
			if (a.getAttribute("title") == title) { a.disabled = false; }
		}
	}
}

dojo.html.getActiveStyleSheet = function(){
	var i = 0, a, els = document.getElementsByTagName("link");
	while (a = els[i++]) {
		if (a.getAttribute("rel").indexOf("style") != -1 &&
			a.getAttribute("title") && !a.disabled) { return a.getAttribute("title"); }
	}
	return null;
}

dojo.html.getPreferredStyleSheet = function(){
	var i = 0, a, els = document.getElementsByTagName("link");
	while (a = els[i++]) {
		if(a.getAttribute("rel").indexOf("style") != -1
			&& a.getAttribute("rel").indexOf("alt") == -1
			&& a.getAttribute("title")) { return a.getAttribute("title"); }
	}
	return null;
}

dojo.html.body = function(){
	// Note: document.body is not defined for a strict xhtml document
	return document.body || document.getElementsByTagName("body")[0];
}

/**
 * Like dojo.dom.isTag, except case-insensitive
**/
dojo.html.isTag = function(node /* ... */) {
	node = dojo.byId(node);
	if(node && node.tagName) {
		var arr = dojo.lang.map(dojo.lang.toArray(arguments, 1),
			function(a) { return String(a).toLowerCase(); });
		return arr[ dojo.lang.find(node.tagName.toLowerCase(), arr) ] || "";
	}
	return "";
}

dojo.html.copyStyle = function(target, source){
	// work around for opera which doesn't have cssText, and for IE which fails on setAttribute 
	if(dojo.lang.isUndefined(source.style.cssText)){ 
		target.setAttribute("style", source.getAttribute("style")); 
	}else{
		target.style.cssText = source.style.cssText; 
	}
	dojo.html.addClass(target, dojo.html.getClass(source));
}

dojo.html._callExtrasDeprecated = function(inFunc, args) {
	var module = "dojo.html.extras";
	dojo.deprecated("dojo.html." + inFunc, "moved to " + module, "0.4");
	dojo["require"](module); // weird syntax to fool list-profile-deps (build)
	return dojo.html[inFunc].apply(dojo.html, args);
}

dojo.html.createNodesFromText = function() {
	return dojo.html._callExtrasDeprecated('createNodesFromText', arguments);
}

dojo.html.gravity = function() {
	return dojo.html._callExtrasDeprecated('gravity', arguments);
}

dojo.html.placeOnScreen = function() {
	return dojo.html._callExtrasDeprecated('placeOnScreen', arguments);
}

dojo.html.placeOnScreenPoint = function() {
	return dojo.html._callExtrasDeprecated('placeOnScreenPoint', arguments);
}

dojo.html.renderedTextContent = function() {
	return dojo.html._callExtrasDeprecated('renderedTextContent', arguments);
}

dojo.html.BackgroundIframe = function() {
	return dojo.html._callExtrasDeprecated('BackgroundIframe', arguments);
}

dojo.provide("dojo.lfx.html");
dojo.require("dojo.lfx.Animation");

dojo.require("dojo.html");

dojo.lfx.html._byId = function(nodes){
	if(!nodes){ return []; }
	if(dojo.lang.isArray(nodes)){
		if(!nodes.alreadyChecked){
			var n = [];
			dojo.lang.forEach(nodes, function(node){
				n.push(dojo.byId(node));
			});
			n.alreadyChecked = true;
			return n;
		}else{
			return nodes;
		}
	}else{
		var n = [];
		n.push(dojo.byId(nodes));
		n.alreadyChecked = true;
		return n;
	}
}

dojo.lfx.html.propertyAnimation = function(	/*DOMNode*/ nodes, 
											/*Array*/ propertyMap, 
											/*int*/ duration,
											/*function*/ easing){
	nodes = dojo.lfx.html._byId(nodes);
	
	if(nodes.length==1){
		// FIXME: we're only supporting start-value filling when one node is
		// passed
		
		dojo.lang.forEach(propertyMap, function(prop){
			if(typeof prop["start"] == "undefined"){
				if(prop.property != "opacity"){
					prop.start = parseInt(dojo.style.getComputedStyle(nodes[0], prop.property));
				}else{
					prop.start = dojo.style.getOpacity(nodes[0]);
				}
			}
		});
	}

	var coordsAsInts = function(coords){
		var cints = new Array(coords.length);
		for(var i = 0; i < coords.length; i++){
			cints[i] = Math.round(coords[i]);
		}
		return cints;
	}
	var setStyle = function(n, style){
		n = dojo.byId(n);
		if(!n || !n.style){ return; }
		for(var s in style){
			if(s == "opacity"){
				dojo.style.setOpacity(n, style[s]);
			}else{
				n.style[s] = style[s];
			}
		}
	}
	var propLine = function(properties){
		this._properties = properties;
		this.diffs = new Array(properties.length);
		dojo.lang.forEach(properties, function(prop, i){
			// calculate the end - start to optimize a bit
			if(dojo.lang.isArray(prop.start)){
				// don't loop through the arrays
				this.diffs[i] = null;
			}else if(prop.start instanceof dojo.graphics.color.Color){
				// save these so we don't have to call toRgb() every getValue() call
				prop.startRgb = prop.start.toRgb();
				prop.endRgb = prop.end.toRgb();
			}else{
				this.diffs[i] = prop.end - prop.start;
			}
		}, this);
		this.getValue = function(n){
			var ret = {};
			dojo.lang.forEach(this._properties, function(prop, i){
				var value = null;
				if(dojo.lang.isArray(prop.start)){
					// FIXME: what to do here?
				}else if(prop.start instanceof dojo.graphics.color.Color){
					value = (prop.units||"rgb") + "(";
					for(var j = 0 ; j < prop.startRgb.length ; j++){
						value += Math.round(((prop.endRgb[j] - prop.startRgb[j]) * n) + prop.startRgb[j]) + (j < prop.startRgb.length - 1 ? "," : "");
					}
					value += ")";
				}else{
					value = ((this.diffs[i]) * n) + prop.start + (prop.property != "opacity" ? prop.units||"px" : "");
				}
				ret[dojo.style.toCamelCase(prop.property)] = value;
			}, this);
			return ret;
		}
	}
	
	var anim = new dojo.lfx.Animation({
		onAnimate: function(propValues){
			dojo.lang.forEach(nodes, function(node){
				setStyle(node, propValues);
			});
		} }, duration, new propLine(propertyMap), easing);
	
	return anim;
}

dojo.lfx.html._makeFadeable = function(nodes){
	var makeFade = function(node){
		if(dojo.render.html.ie){
			// only set the zoom if the "tickle" value would be the same as the
			// default
			if( (node.style.zoom.length == 0) &&
				(dojo.style.getStyle(node, "zoom") == "normal") ){
				// make sure the node "hasLayout"
				// NOTE: this has been tested with larger and smaller user-set text
				// sizes and works fine
				node.style.zoom = "1";
				// node.style.zoom = "normal";
			}
			// don't set the width to auto if it didn't already cascade that way.
			// We don't want to f anyones designs
			if(	(node.style.width.length == 0) &&
				(dojo.style.getStyle(node, "width") == "auto") ){
				node.style.width = "auto";
			}
		}
	}
	if(dojo.lang.isArrayLike(nodes)){
		dojo.lang.forEach(nodes, makeFade);
	}else{
		makeFade(nodes);
	}
}

dojo.lfx.html.fadeIn = function(nodes, duration, easing, callback){
	nodes = dojo.lfx.html._byId(nodes);
	dojo.lfx.html._makeFadeable(nodes);
	var anim = dojo.lfx.propertyAnimation(nodes, [
		{	property: "opacity",
			start: dojo.style.getOpacity(nodes[0]),
			end: 1 } ], duration, easing);
	if(callback){
		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		anim.onEnd = function(){ oldOnEnd(); callback(nodes, anim); };
	}

	return anim;
}

dojo.lfx.html.fadeOut = function(nodes, duration, easing, callback){
	nodes = dojo.lfx.html._byId(nodes);
	dojo.lfx.html._makeFadeable(nodes);
	var anim = dojo.lfx.propertyAnimation(nodes, [
		{	property: "opacity",
			start: dojo.style.getOpacity(nodes[0]),
			end: 0 } ], duration, easing);
	if(callback){
		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		anim.onEnd = function(){ oldOnEnd(); callback(nodes, anim); };
	}

	return anim;
}

dojo.lfx.html.fadeShow = function(nodes, duration, easing, callback){
	var anim = dojo.lfx.html.fadeIn(nodes, duration, easing, callback);
	var oldBb = (anim["beforeBegin"]) ? dojo.lang.hitch(anim, "beforeBegin") : function(){};
	anim.beforeBegin = function(){ 
		oldBb();
		if(dojo.lang.isArrayLike(nodes)){
			dojo.lang.forEach(nodes, dojo.style.show);
		}else{
			dojo.style.show(nodes);
		}
	};
	
	return anim;
}

dojo.lfx.html.fadeHide = function(nodes, duration, easing, callback){
	var anim = dojo.lfx.html.fadeOut(nodes, duration, easing, function(){
		if(dojo.lang.isArrayLike(nodes)){
			dojo.lang.forEach(nodes, dojo.style.hide);
		}else{
			dojo.style.hide(nodes);
		}
		if(callback){ callback(nodes, anim); }
	});
	
	return anim;
}

dojo.lfx.html.wipeIn = function(nodes, duration, easing, callback){
	nodes = dojo.lfx.html._byId(nodes);
	var anims = [];

	dojo.lang.forEach(nodes, function(node){
		var overflow = dojo.style.getStyle(node, "overflow");
		if(overflow == "visible") {
			node.style.overflow = "hidden";
		}
		node.style.height = "0px";
		dojo.style.show(node);
		
		var anim = dojo.lfx.propertyAnimation(node,
			[{	property: "height",
				start: 0,
				end: node.scrollHeight }], duration, easing);
		
		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		anim.onEnd = function(){ 
			oldOnEnd(); 
			node.style.overflow = overflow;
			node.style.height = "auto";
			if(callback){ callback(node, anim); }
		};
		anims.push(anim);
	});
	
	if(nodes.length > 1){ return dojo.lfx.combine(anims); }
	else{ return anims[0]; }
}

dojo.lfx.html.wipeOut = function(nodes, duration, easing, callback){
	nodes = dojo.lfx.html._byId(nodes);
	var anims = [];
	
	dojo.lang.forEach(nodes, function(node){
		var overflow = dojo.style.getStyle(node, "overflow");
		if(overflow == "visible") {
			node.style.overflow = "hidden";
		}
		dojo.style.show(node);

		var anim = dojo.lfx.propertyAnimation(node,
			[{	property: "height",
				start: dojo.style.getContentBoxHeight(node),
				end: 0 } ], duration, easing);
		
		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		anim.onEnd = function(){ 
			oldOnEnd(); 
			dojo.style.hide(node);
			node.style.overflow = overflow;
			if(callback){ callback(node, anim); }
		};
		anims.push(anim);
	});

	if(nodes.length > 1){ return dojo.lfx.combine(anims); }
	else { return anims[0]; }
}

dojo.lfx.html.slideTo = function(nodes, coords, duration, easing, callback){
	nodes = dojo.lfx.html._byId(nodes);
	var anims = [];

	dojo.lang.forEach(nodes, function(node){
		var top = null;
		var left = null;
		
		var init = (function(){
			var innerNode = node;
			return function(){
				top = innerNode.offsetTop;
				left = innerNode.offsetLeft;

				if (!dojo.style.isPositionAbsolute(innerNode)) {
					var ret = dojo.style.abs(innerNode, true);
					dojo.style.setStyleAttributes(innerNode, "position:absolute;top:"+ret.y+"px;left:"+ret.x+"px;");
					top = ret.y;
					left = ret.x;
				}
			}
		})();
		init();
		
		var anim = dojo.lfx.propertyAnimation(node,
			[{	property: "top",
				start: top,
				end: coords[0] },
			{	property: "left",
				start: left,
				end: coords[1] }], duration, easing);
		
		var oldBb = (anim["beforeBegin"]) ? dojo.lang.hitch(anim, "beforeBegin") : function(){};
		anim.beforeBegin = function(){ oldBb(); init(); };

		if(callback){
			var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
			anim.onEnd = function(){ oldOnEnd(); callback(nodes, anim); };
		}

		anims.push(anim);
	});
	
	if(nodes.length > 1){ return dojo.lfx.combine(anims); }
	else{ return anims[0]; }
}

dojo.lfx.html.slideBy = function(nodes, coords, duration, easing, callback){
	nodes = dojo.lfx.html._byId(nodes);
	var anims = [];

	dojo.lang.forEach(nodes, function(node){
		var top = null;
		var left = null;
		
		var init = (function(){
			var innerNode = node;
			return function(){
				top = node.offsetTop;
				left = node.offsetLeft;

				if (!dojo.style.isPositionAbsolute(innerNode)) {
					var ret = dojo.style.abs(innerNode);
					dojo.style.setStyleAttributes(innerNode, "position:absolute;top:"+ret.y+"px;left:"+ret.x+"px;");
					top = ret.y;
					left = ret.x;
				}
			}
		})();
		init();
		
		var anim = dojo.lfx.propertyAnimation(node,
			[{	property: "top",
				start: top,
				end: top+coords[0] },
			{	property: "left",
				start: left,
				end: left+coords[1] }], duration, easing);

		var oldBb = (anim["beforeBegin"]) ? dojo.lang.hitch(anim, "beforeBegin") : function(){};
		anim.beforeBegin = function(){ oldBb(); init(); };

		if(callback){
			var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
			anim.onEnd = function(){ oldOnEnd(); callback(nodes, anim); };
		}

		anims.push(anim);
	});

	if(nodes.length > 1){ return dojo.lfx.combine(anims); }
	else{ return anims[0]; }
}

dojo.lfx.html.explode = function(start, endNode, duration, easing, callback){
	start = dojo.byId(start);
	endNode = dojo.byId(endNode);
	var startCoords = dojo.style.toCoordinateArray(start, true);
	var outline = document.createElement("div");
	dojo.html.copyStyle(outline, endNode);
	with(outline.style){
		position = "absolute";
		display = "none";
	}
	document.body.appendChild(outline);

	with(endNode.style){
		visibility = "hidden";
		display = "block";
	}
	var endCoords = dojo.style.toCoordinateArray(endNode, true);
	with(endNode.style){
		display = "none";
		visibility = "visible";
	}

	var anim = new dojo.lfx.propertyAnimation(outline, [
		{ property: "height", start: startCoords[3], end: endCoords[3] },
		{ property: "width", start: startCoords[2], end: endCoords[2] },
		{ property: "top", start: startCoords[1], end: endCoords[1] },
		{ property: "left", start: startCoords[0], end: endCoords[0] },
		{ property: "opacity", start: 0.3, end: 1.0 }
	], duration, easing);
	
	anim.beforeBegin = function(){
		dojo.style.setDisplay(outline, "block");
	};
	anim.onEnd = function(){
		dojo.style.setDisplay(endNode, "block");
		outline.parentNode.removeChild(outline);
	};
	if(callback){
		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		anim.onEnd = function(){ oldOnEnd(); callback(endNode, anim); };
	}
	return anim;
}

dojo.lfx.html.implode = function(startNode, end, duration, easing, callback){
	startNode = dojo.byId(startNode);
	end = dojo.byId(end);
	var startCoords = dojo.style.toCoordinateArray(startNode, true);
	var endCoords = dojo.style.toCoordinateArray(end, true);

	var outline = document.createElement("div");
	dojo.html.copyStyle(outline, startNode);
	dojo.style.setOpacity(outline, 0.3);
	with(outline.style){
		position = "absolute";
		display = "none";
	}
	document.body.appendChild(outline);

	var anim = new dojo.lfx.propertyAnimation(outline, [
		{ property: "height", start: startCoords[3], end: endCoords[3] },
		{ property: "width", start: startCoords[2], end: endCoords[2] },
		{ property: "top", start: startCoords[1], end: endCoords[1] },
		{ property: "left", start: startCoords[0], end: endCoords[0] },
		{ property: "opacity", start: 1.0, end: 0.3 }
	], duration, easing);
	
	anim.beforeBegin = function(){
		dojo.style.hide(startNode);
		dojo.style.show(outline);
	};
	anim.onEnd = function(){
		outline.parentNode.removeChild(outline);
	};
	if(callback){
		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		anim.onEnd = function(){ oldOnEnd(); callback(startNode, anim); };
	}
	return anim;
}

dojo.lfx.html.highlight = function(nodes, startColor, duration, easing, callback){
	nodes = dojo.lfx.html._byId(nodes);
	var anims = [];

	dojo.lang.forEach(nodes, function(node){
		var color = dojo.style.getBackgroundColor(node);
		var bg = dojo.style.getStyle(node, "background-color").toLowerCase();
		var bgImage = dojo.style.getStyle(node, "background-image");
		var wasTransparent = (bg == "transparent" || bg == "rgba(0, 0, 0, 0)");
		while(color.length > 3) { color.pop(); }

		var rgb = new dojo.graphics.color.Color(startColor);
		var endRgb = new dojo.graphics.color.Color(color);

		var anim = dojo.lfx.propertyAnimation(node, [{
			property: "background-color",
			start: rgb,
			end: endRgb
		}], duration, easing);

		var oldbb = (anim["beforeBegin"]) ? dojo.lang.hitch(anim, "beforeBegin") : function(){};
		anim.beforeBegin = function(){ 
			oldbb();
			if(bgImage){
				node.style.backgroundImage = "none";
			}
			node.style.backgroundColor = "rgb(" + rgb.toRgb().join(",") + ")";
		};

		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		anim.onEnd = function(){ 
			oldOnEnd();
			if(bgImage){
				node.style.backgroundImage = bgImage;
			}
			if(wasTransparent){
				node.style.backgroundColor = "transparent";
			}
			if(callback){
				callback(node, anim);
			}
		};

		anims.push(anim);
	});

	if(nodes.length > 1){ return dojo.lfx.combine(anims); }
	else{ return anims[0]; }
}

dojo.lfx.html.unhighlight = function(nodes, endColor, duration, easing, callback){
	nodes = dojo.lfx.html._byId(nodes);
	var anims = [];

	dojo.lang.forEach(nodes, function(node){
		var color = new dojo.graphics.color.Color(dojo.style.getBackgroundColor(node));
		var rgb = new dojo.graphics.color.Color(endColor);

		var bgImage = dojo.style.getStyle(node, "background-image");
		
		var anim = dojo.lfx.propertyAnimation(node, [{
			property: "background-color",
			start: color,
			end: rgb
		}], duration, easing);

		var oldbb = (anim["beforeBegin"]) ? dojo.lang.hitch(anim, "beforeBegin") : function(){};
		anim.beforeBegin = function(){ 
			oldbb();
			if(bgImage){
				node.style.backgroundImage = "none";
			}
			node.style.backgroundColor = "rgb(" + color.toRgb().join(",") + ")";
		};

		var oldOnEnd = (anim["onEnd"]) ? dojo.lang.hitch(anim, "onEnd") : function(){};
		anim.onEnd = function(){ 
			oldOnEnd();
			if(callback){
				callback(node, anim);
			}
		};

		anims.push(anim);
	});

	if(nodes.length > 1){ return dojo.lfx.combine(anims); }
	else{ return anims[0]; }
}

dojo.lang.mixin(dojo.lfx, dojo.lfx.html);

dojo.kwCompoundRequire({
	browser: ["dojo.lfx.html"],
	dashboard: ["dojo.lfx.html"]
});
dojo.provide("dojo.lfx.*");

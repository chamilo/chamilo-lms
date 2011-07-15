/****
 *
 * frameReady: Remote function calling for jQuery
 *
 * Version 1.2.1
 *
 * Copyright (c) 2007 Daemach (John Wilson) <daemach@gmail.com>, http://ideamill.synaptrixgroup.com
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 *	Credit John Resig and his excellent book for the ready function concepts.
 *
 * Credit to Mike Alsup for the logging code.
 * ============================================================================================
 * Usage: $.frameReady(function (function),target (string)[,options (map)][,callback (function)]);
 *
 * Function: (function/required) An anonymous function to be run within the target frame.
 *
 * Target: (string/required) The target frame.  This must be a window object name (in quotes),
 *		so work from the top down.  If you have 3 frames named topFrame, navFrame, mainFrame, and
 *		an iframe inside of mainframe named iFrame, use "top.topFrame", "top.navFrame",
 *		"top.mainFrame", "top.mainFrame.iFrame" respectively.
 *
 *	Options: (object/optional) Map of options in object literal form.  Options include:
 *
 * 		remote: (boolean/ default true)  Run the function in the context of the target frame.
 *				If true, jQuery will be loaded in the target frame automatically and you can run
 *				jQuery selectors in the target frame as if they were local. ie: $("p") instead of
 *				$("p",top.mainFrame.document).  If false, jQuery will not be loaded automatically
 *				and you must use a context in jquery selectors.
 *
 *		data: (object)  An object to be passed as-is to the target frame.  This is where you pass
 *			variable data, rather than in a closure.
 *
 *		load: (object or array of objects)  jquery is loaded by default. You can pass a single object to
 *			frameReady, or an array of objects that will be loaded and tested in order.  2 types
 *			of files can be loaded.  Scripts	and stylesheets:
 *
 *			scripts: {type:"script", src:"/js/myscript.js", id:"_ms", test:"afunction"}
 *			stylesheets: {type:"stylesheet", src:"/css/mycss.css", id:"_ss"}
 *
 *				type: (string/required) "script" for script files, "stylesheet" for stylesheets.
 *				src: (string/required)  The source of the file, ie: /js/myscript.js.
 *				id: (string/optional)  An id for the id attribute.  If one isn't provided it
 *					will be generated.
 *				test: (sting/optional) The name of a function that should exist once the script
 *					is loaded properly.  Until this function becomes available, the script will
 *					be considered not ready and no other files will be loaded.  If a test is not
 *					provided, the next file will be loaded immediately.  Tests are not useful
 *					with stylesheets.
 *
 *	One gotcha: You must have something other than space characters within the body tags of
 * 	target frame documents for frameReady to work properly.  A single character is enough.
 *		The reason for this is a workaround for an iFrame bug in Firefox, of all things.
 * ==============================================================================================
 *
 * Example:
 *
 *		$.frameReady(function(){
 *		  $("<div>I am a div element</div>").prependTo("body");
 *		}, "top.mainFrame",
 *		{ load: [
 *			{type:"script",id:"_fr",src:"/js/jquery.frameReady.js",test: "$.frameReady"},
 *			{type:"stylesheet",id:"_ss",src:"frameReady.css"}
 *			] }
 *		);
 *
 *
 * Release Notes:
 *
 *	1.2.0 - Added provision for a local callback function;
 *	        Added functionality to reset frame information if frame unloads for any reason;
 *
 *	1.1.0 - Added the ability to load scripts and stylesheets inside the target frame before
 *					processing function stack;
 *
 ****/

if (typeof $daemach == "undefined") {
    $daemach = {};
    $daemach.debug = false;  // set this to true to enable logging
    $daemach.log = function() {
        if (!top.window.console || !top.window.console.log || !$daemach.debug) {
            return;
        } else {
            top.window.console.log([].join.call(arguments,''));
        };
    };
    $daemach.time = function() {
        if (!top.window.console || !top.window.console.time || !$daemach.debug) {
            return;
        } else {
            top.window.console.time([].join.call(arguments,''));
        };
    };
    $daemach.timeEnd = function() {
        if (!top.window.console || !top.window.console.timeEnd || !$daemach.debug) {
            return;
        } else {
            top.window.console.timeEnd([].join.call(arguments,''));
        };
    };
};

if (typeof $daemach["frameReady"] == "undefined") {
    $daemach["frameReady"] = {};
};

jQuery.frameReady = function(f,t,r,j) {

    /************************************************************
        You must specify the path to your jquery.js file below!
    *************************************************************/

    //var jQueryPath = "/main/inc/lib/javascript/jquery.js";
    var jQueryPath = jQueryFrameReadyConfigPath; // Define this configuration parameter before loading this script.

    var u = "undefined";
    var $fr = $daemach["frameReady"];
    var fn = t.split(".").join("_");
    // create a branch
    if (typeof $fr[fn] == u) {
        $fr[fn] = {};
        $fr[fn]["settings"] = {
            remote: true,
            jquery: true,
            load: [ {type:"script",id:"_jq", src:jQueryPath, test:"jQuery"} ],
            bLoaded: false,
            loadInit: [],
            data: {},
            callback: false
        };
        $fr[fn]["target"] = t;
    };

    var fr = $fr[fn];
    var frs = fr["settings"];

    if (fr.done) {
        $daemach.log(fr.target + " is ready.  Running functions now.");
        return (frs.remote) ? eval(fr.target).eval("(" + f.toString() + ")()") : f();
    };

    // process arguments

    for (var a=2;a<arguments.length;a++){
        var arg = arguments[a];

        if ($.isFunction(arg)){
            frs.callback = arg;
        } else if (typeof arg == "object") {
            if (typeof arg.remote !== u) {
                frs.remote = arg.remote;
            };
            if (typeof arg.jquery !== u) {
                frs.jquery = arg.jquery;
            };
            if (typeof arg.data !== u) {
                frs.data = arg.data;
            };

            // if we're not running functions in the remote frame itself, no need for jQuery
            if (!frs.remote || !frs.jquery) {
                frs.load.pop();
            };

            if (typeof arg.load !== u) {
                var bl = true;
                if (arg.load.constructor == Array && arg.load.length){
                    for (var i=0;i<arg.load.length;i++){
                        bl = true;
                        for (var h=0;h<frs.load.length;h++){
                            if (frs.load[h].src == arg.load[i].src) { bl=false; };
                        };
                        if (bl) { frs.load.push(arg.load[i]); };
                    };
                } else if (typeof arg.load == "object") {
                    for (var h=0;h<frs.load.length;h++){
                        if (frs.load[h].src == arg.load.src) { bl=false; };
                    };
                    if (bl) { frs.load.push(arg.load); };
                };
            };
        };
    };

    if (fr.timer) {
        fr.ready.push(f);
    } else {
        fr.ready=[f];
        if (typeof addEvent !== "undefined"){ addEvent(window,"load",function(){ jQuery.isFrameReady(fn); }); };
        fr.timer = setInterval(function(){ jQuery.isFrameReady(fn); },13);
    };
};

jQuery.isFrameReady = function(fn){
    var u = "undefined";
    var $d = $daemach;
    var fr = $d["frameReady"][fn];
    var frs = fr["settings"];

    if (fr.done) { return false; };

    var fx = eval(fr.target);
    $d.log(fn, ": New Pass. Checking target");
    // make sure we have a target
    if (typeof fx !== "undefined") {
        $d.log(fn, ": Found target.  Checking DOM");
        var fd = fx.document;

        // make sure we have a DOM
        if (fd && fd.getElementsByTagName && fd.getElementById && fd.body && fd.body.innerHTML.length) {

            $d.log(fn, ": Found DOM");

            if (frs.load.length && !frs.bLoaded){
                for (var i=0;i<frs.load.length;i++){
                    var s = frs.load[i];
                    var _test;
                    try { _test = eval('typeof fx.'+s.test+ ' !== "undefined"'); }
                    catch(ex){ _test = false;}
                    finally { $d.log(fn, ": Running test for script ",i,". ", (_test || !s.test) ? "Passed.":"Failed."); };

                    if ((_test || !s.test) && frs.loadInit[i]) {
                        frs.bLoaded = (typeof s.test == u) ? true : _test;
                        continue;
                    } else {
                        frs.bLoaded = false;
                        if (typeof frs.loadInit[i] == u){
                            var id = s.id || "frs_"+i;
                            switch (s.type) {
                                case "script" :
                                    $d.log(fn, ": Loading script "+ i + " (" + s.src + ")");
                                    var ele=fd.createElement('script');
                                    ele.setAttribute('id', id);
                                    ele.setAttribute('src', s.src);
                                    fd.getElementsByTagName("body")[0].appendChild(ele);
                                    frs.loadInit[i] = true;
                                    break;
                                case "stylesheet" :
                                    $d.log(fn, ": Loading stylesheet "+ i + " (" + s.src + ")");
                                    var ele=fd.createElement('link');
                                    ele.setAttribute('href', s.src);
                                    ele.setAttribute('rel', "stylesheet");
                                    ele.setAttribute('type', "text/css");
                                    fd.getElementsByTagName("body")[0].appendChild(ele);
                                    frs.loadInit[i] = true;
                                break;
                                default :
                                    $d.log(fn, ": Script "+i+" has a bad or missing type attribute..." );
                            };
                        };
                        break;
                    };
                };
            } else {
                clearInterval(fr.timer);
                fr.timer = null;
                for (i in frs.data){
                    if (!fx.frData){
                        fx.frData={};
                    }
                    fx.frData[i] = frs.data[i];
                };

                fr.ready.push(function(){ window.frameReadyUnload = function(root, fn){ $(window).bind("unload",function(){ root.jQuery.frameReady.unload(fn); }); } });

                $d.log(fn, ": Processing function stack:");
                for (var i=0; i<fr.ready.length;i++){
                    (frs.remote) ? fx.eval("(" + fr.ready[i].toString() + ")()") : fr.ready[i]();
                };

                fx.frameReadyUnload(window,fn);

                $d.log(fn, ": Function stack processing complete.");

                // we're done here.  let's have a beer.
                fr.ready = null;
                fr.done=true;

                if (frs.callback){
                    $d.log(fn, ": Found a callback.  Executing...");
                    frs.callback();
                };
            };
        };
    };

    $d.log(fn, ":");
};

jQuery.frameReady.unload = function(fn){
    $daemach.log("Frame " + fn + " is unloading.  Resetting state.");
    $daemach["frameReady"][fn].done = false;
    $daemach["frameReady"][fn]["settings"].bLoaded = false;
    $daemach["frameReady"][fn]["settings"].loadInit = [];
};

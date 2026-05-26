"0.33.0";
/*
CryptoJS v3.0.2
code.google.com/p/crypto-js
(c) 2009-2012 by Jeff Mott. All rights reserved.
code.google.com/p/crypto-js/wiki/License
*/
var CryptoJS=CryptoJS||function(i,m){var p={},h=p.lib={},n=h.Base=function(){function a(){}return{extend:function(b){a.prototype=this;var c=new a;b&&c.mixIn(b);c.$super=this;return c},create:function(){var a=this.extend();a.init.apply(a,arguments);return a},init:function(){},mixIn:function(a){for(var c in a)a.hasOwnProperty(c)&&(this[c]=a[c]);a.hasOwnProperty("toString")&&(this.toString=a.toString)},clone:function(){return this.$super.extend(this)}}}(),o=h.WordArray=n.extend({init:function(a,b){a=
this.words=a||[];this.sigBytes=b!=m?b:4*a.length},toString:function(a){return(a||e).stringify(this)},concat:function(a){var b=this.words,c=a.words,d=this.sigBytes,a=a.sigBytes;this.clamp();if(d%4)for(var f=0;f<a;f++)b[d+f>>>2]|=(c[f>>>2]>>>24-8*(f%4)&255)<<24-8*((d+f)%4);else if(65535<c.length)for(f=0;f<a;f+=4)b[d+f>>>2]=c[f>>>2];else b.push.apply(b,c);this.sigBytes+=a;return this},clamp:function(){var a=this.words,b=this.sigBytes;a[b>>>2]&=4294967295<<32-8*(b%4);a.length=i.ceil(b/4)},clone:function(){var a=
n.clone.call(this);a.words=this.words.slice(0);return a},random:function(a){for(var b=[],c=0;c<a;c+=4)b.push(4294967296*i.random()|0);return o.create(b,a)}}),q=p.enc={},e=q.Hex={stringify:function(a){for(var b=a.words,a=a.sigBytes,c=[],d=0;d<a;d++){var f=b[d>>>2]>>>24-8*(d%4)&255;c.push((f>>>4).toString(16));c.push((f&15).toString(16))}return c.join("")},parse:function(a){for(var b=a.length,c=[],d=0;d<b;d+=2)c[d>>>3]|=parseInt(a.substr(d,2),16)<<24-4*(d%8);return o.create(c,b/2)}},g=q.Latin1={stringify:function(a){for(var b=
a.words,a=a.sigBytes,c=[],d=0;d<a;d++)c.push(String.fromCharCode(b[d>>>2]>>>24-8*(d%4)&255));return c.join("")},parse:function(a){for(var b=a.length,c=[],d=0;d<b;d++)c[d>>>2]|=(a.charCodeAt(d)&255)<<24-8*(d%4);return o.create(c,b)}},j=q.Utf8={stringify:function(a){try{return decodeURIComponent(escape(g.stringify(a)))}catch(b){throw Error("Malformed UTF-8 data");}},parse:function(a){return g.parse(unescape(encodeURIComponent(a)))}},k=h.BufferedBlockAlgorithm=n.extend({reset:function(){this._data=o.create();
this._nDataBytes=0},_append:function(a){"string"==typeof a&&(a=j.parse(a));this._data.concat(a);this._nDataBytes+=a.sigBytes},_process:function(a){var b=this._data,c=b.words,d=b.sigBytes,f=this.blockSize,e=d/(4*f),e=a?i.ceil(e):i.max((e|0)-this._minBufferSize,0),a=e*f,d=i.min(4*a,d);if(a){for(var g=0;g<a;g+=f)this._doProcessBlock(c,g);g=c.splice(0,a);b.sigBytes-=d}return o.create(g,d)},clone:function(){var a=n.clone.call(this);a._data=this._data.clone();return a},_minBufferSize:0});h.Hasher=k.extend({init:function(){this.reset()},
reset:function(){k.reset.call(this);this._doReset()},update:function(a){this._append(a);this._process();return this},finalize:function(a){a&&this._append(a);this._doFinalize();return this._hash},clone:function(){var a=k.clone.call(this);a._hash=this._hash.clone();return a},blockSize:16,_createHelper:function(a){return function(b,c){return a.create(c).finalize(b)}},_createHmacHelper:function(a){return function(b,c){return l.HMAC.create(a,c).finalize(b)}}});var l=p.algo={};return p}(Math);
(function(){var i=CryptoJS,m=i.lib,p=m.WordArray,m=m.Hasher,h=[],n=i.algo.SHA1=m.extend({_doReset:function(){this._hash=p.create([1732584193,4023233417,2562383102,271733878,3285377520])},_doProcessBlock:function(o,i){for(var e=this._hash.words,g=e[0],j=e[1],k=e[2],l=e[3],a=e[4],b=0;80>b;b++){if(16>b)h[b]=o[i+b]|0;else{var c=h[b-3]^h[b-8]^h[b-14]^h[b-16];h[b]=c<<1|c>>>31}c=(g<<5|g>>>27)+a+h[b];c=20>b?c+((j&k|~j&l)+1518500249):40>b?c+((j^k^l)+1859775393):60>b?c+((j&k|j&l|k&l)-1894007588):c+((j^k^l)-
899497514);a=l;l=k;k=j<<30|j>>>2;j=g;g=c}e[0]=e[0]+g|0;e[1]=e[1]+j|0;e[2]=e[2]+k|0;e[3]=e[3]+l|0;e[4]=e[4]+a|0},_doFinalize:function(){var i=this._data,h=i.words,e=8*this._nDataBytes,g=8*i.sigBytes;h[g>>>5]|=128<<24-g%32;h[(g+64>>>9<<4)+15]=e;i.sigBytes=4*h.length;this._process()}});i.SHA1=m._createHelper(n);i.HmacSHA1=m._createHmacHelper(n)})();

/*
CryptoJS v3.0.2
code.google.com/p/crypto-js
(c) 2009-2012 by Jeff Mott. All rights reserved.
code.google.com/p/crypto-js/wiki/License
*/
(function () {
    // Shortcuts
    var C = CryptoJS;
    var C_lib = C.lib;
    var WordArray = C_lib.WordArray;
    var C_enc = C.enc;

    /**
     * Base64 encoding strategy.
     */
    var Base64 = C_enc.Base64 = {
        /**
         * Converts a word array to a Base64 string.
         *
         * @param {WordArray} wordArray The word array.
         *
         * @return {string} The Base64 string.
         *
         * @static
         *
         * @example
         *
         *     var base64String = CryptoJS.enc.Base64.stringify(wordArray);
         */
        stringify: function (wordArray) {
            // Shortcuts
            var words = wordArray.words;
            var sigBytes = wordArray.sigBytes;
            var map = this._map;

            // Clamp excess bits
            wordArray.clamp();

            // Convert
            var base64Chars = [];
            for (var i = 0; i < sigBytes; i += 3) {
                var byte1 = (words[i >>> 2]       >>> (24 - (i % 4) * 8))       & 0xff;
                var byte2 = (words[(i + 1) >>> 2] >>> (24 - ((i + 1) % 4) * 8)) & 0xff;
                var byte3 = (words[(i + 2) >>> 2] >>> (24 - ((i + 2) % 4) * 8)) & 0xff;

                var triplet = (byte1 << 16) | (byte2 << 8) | byte3;

                for (var j = 0; (j < 4) && (i + j * 0.75 < sigBytes); j++) {
                    base64Chars.push(map.charAt((triplet >>> (6 * (3 - j))) & 0x3f));
                }
            }

            // Add padding
            var paddingChar = map.charAt(64);
            if (paddingChar) {
                while (base64Chars.length % 4) {
                    base64Chars.push(paddingChar);
                }
            }

            return base64Chars.join('');
        },

        /**
         * Converts a Base64 string to a word array.
         *
         * @param {string} base64Str The Base64 string.
         *
         * @return {WordArray} The word array.
         *
         * @static
         *
         * @example
         *
         *     var wordArray = CryptoJS.enc.Base64.parse(base64String);
         */
        parse: function (base64Str) {
            // Ignore whitespaces
            base64Str = base64Str.replace(/\s/g, '');

            // Shortcuts
            var base64StrLength = base64Str.length;
            var map = this._map;

            // Ignore padding
            var paddingChar = map.charAt(64);
            if (paddingChar) {
                var paddingIndex = base64Str.indexOf(paddingChar);
                if (paddingIndex != -1) {
                    base64StrLength = paddingIndex;
                }
            }

            // Convert
            var words = [];
            var nBytes = 0;
            for (var i = 0; i < base64StrLength; i++) {
                if (i % 4) {
                    var bitsHigh = map.indexOf(base64Str.charAt(i - 1)) << ((i % 4) * 2);
                    var bitsLow  = map.indexOf(base64Str.charAt(i)) >>> (6 - (i % 4) * 2);
                    words[nBytes >>> 2] |= (bitsHigh | bitsLow) << (24 - (nBytes % 4) * 8);
                    nBytes++;
                }
            }

            return WordArray.create(words, nBytes);
        },

        _map: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/='
    };
}());

/*!
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

TODO:

* Add statement queueing

@module TinCan
**/
var TinCan;

(function () {
    "use strict";
    var _reservedQSParams = {
        //
        // these are TC spec reserved words that may end up in queries to the endpoint
        //
        statementId:       true,
        voidedStatementId: true,
        verb:              true,
        object:            true,
        registration:      true,
        context:           true,
        actor:             true,
        since:             true,
        until:             true,
        limit:             true,
        authoritative:     true,
        sparse:            true,
        instructor:        true,
        ascending:         true,
        continueToken:     true,
        agent:             true,
        activityId:        true,
        stateId:           true,
        profileId:         true,

        //
        // these are suggested by the LMS launch spec addition that TinCanJS consumes
        //
        activity_platform: true,
        grouping:          true,
        "Accept-Language": true
    };

    /**
    @class TinCan
    @constructor
    @param {Object} [options] Configuration used to initialize.
        @param {String} [options.url] URL for determining launch provided
            configuration options
        @param {Array} [options.recordStores] list of pre-configured LRSes
        @param {Object|TinCan.Agent} [options.actor] default actor
        @param {Object|TinCan.Activity} [options.activity] default activity
        @param {String} [options.registration] default registration
        @param {Object|TinCan.Context} [options.context] default context
    **/
    TinCan = function (cfg) {
        this.log("constructor");

        /**
        @property recordStores
        @type Array
        */
        this.recordStores = [];

        /**
        Default actor used when preparing statements that
        don't yet have an actor set, and for saving state, etc.

        @property actor
        @type Object
        */
        this.actor = null;

        /**
        Default activity, may be used as a statement 'target'
        or incorporated into 'context'

        @property activity
        @type Object
        */
        this.activity = null;

        /**
        Default registration, included in default context when
        provided, otherwise used in statement queries

        @property registration
        @type String
        */
        this.registration = null;

        /**
        Default context used when preparing statements that
        don't yet have a context set, or mixed in when one
        has been provided, properties do NOT override on mixing

        @property context
        @type Object
        */
        this.context = null;

        this.init(cfg);
    };

    TinCan.prototype = {
        LOG_SRC: "TinCan",

        /**
        Safe version of logging, only displays when .DEBUG is true, and console.log
        is available

        @method log
        @param {String} msg Message to output
        */
        log: function (msg, src) {
            /* globals console */
            if (TinCan.DEBUG && typeof console !== "undefined" && console.log) {
                src = src || this.LOG_SRC || "TinCan";

                console.log("TinCan." + src + ": " + msg);
            }
        },

        /**
        @method init
        @param {Object} [options] Configuration used to initialize (see TinCan constructor).
        */
        init: function (cfg) {
            this.log("init");
            var i;

            cfg = cfg || {};

            if (cfg.hasOwnProperty("url") && cfg.url !== "") {
                this._initFromQueryString(cfg.url);
            }

            if (cfg.hasOwnProperty("recordStores") && cfg.recordStores !== undefined) {
                for (i = 0; i < cfg.recordStores.length; i += 1) {
                    this.addRecordStore(cfg.recordStores[i]);
                }
            }
            if (cfg.hasOwnProperty("activity")) {
                if (cfg.activity instanceof TinCan.Activity) {
                    this.activity = cfg.activity;
                }
                else {
                    this.activity = new TinCan.Activity (cfg.activity);
                }
            }
            if (cfg.hasOwnProperty("actor")) {
                if (cfg.actor instanceof TinCan.Agent) {
                    this.actor = cfg.actor;
                }
                else {
                    this.actor = new TinCan.Agent (cfg.actor);
                }
            }
            if (cfg.hasOwnProperty("context")) {
                if (cfg.context instanceof TinCan.Context) {
                    this.context = cfg.context;
                }
                else {
                    this.context = new TinCan.Context (cfg.context);
                }
            }
            if (cfg.hasOwnProperty("registration")) {
                this.registration = cfg.registration;
            }
        },

        /**
        @method _initFromQueryString
        @param {String} url
        @private
        */
        _initFromQueryString: function (url) {
            this.log("_initFromQueryString");

            var i,
                prop,
                qsParams = TinCan.Utils.parseURL(url).params,
                lrsProps = ["endpoint", "auth"],
                lrsCfg = {},
                contextCfg,
                extended = null
            ;

            if (qsParams.hasOwnProperty("actor")) {
                this.log("_initFromQueryString - found actor: " + qsParams.actor);
                try {
                    this.actor = TinCan.Agent.fromJSON(qsParams.actor);
                    delete qsParams.actor;
                }
                catch (ex) {
                    this.log("_initFromQueryString - failed to set actor: " + ex);
                }
            }

            if (qsParams.hasOwnProperty("activity_id")) {
                this.activity = new TinCan.Activity (
                    {
                        id: qsParams.activity_id
                    }
                );
                delete qsParams.activity_id;
            }

            if (
                qsParams.hasOwnProperty("activity_platform") ||
                qsParams.hasOwnProperty("registration") ||
                qsParams.hasOwnProperty("grouping")
            ) {
                contextCfg = {};

                if (qsParams.hasOwnProperty("activity_platform")) {
                    contextCfg.platform = qsParams.activity_platform;
                    delete qsParams.activity_platform;
                }
                if (qsParams.hasOwnProperty("registration")) {
                    //
                    // stored in two locations cause we always want it in the default
                    // context, but we also want to be able to get to it for Statement
                    // queries
                    //
                    contextCfg.registration = this.registration = qsParams.registration;
                    delete qsParams.registration;
                }
                if (qsParams.hasOwnProperty("grouping")) {
                    contextCfg.contextActivities = {};
                    contextCfg.contextActivities.grouping = qsParams.grouping;
                    delete qsParams.grouping;
                }

                this.context = new TinCan.Context (contextCfg);
            }

            //
            // order matters here, process the URL provided LRS last because it gets
            // all the remaining parameters so that they get passed through
            //
            if (qsParams.hasOwnProperty("endpoint")) {
                for (i = 0; i < lrsProps.length; i += 1) {
                    prop = lrsProps[i];
                    if (qsParams.hasOwnProperty(prop)) {
                        lrsCfg[prop] = qsParams[prop];
                        delete qsParams[prop];
                    }
                }

                // remove our reserved params so they don't end up  in the extended object
                for (i in qsParams) {
                    if (qsParams.hasOwnProperty(i)) {
                        if (_reservedQSParams.hasOwnProperty(i)) {
                            delete qsParams[i];
                        } else {
                            extended = extended || {};
                            extended[i] = qsParams[i];
                        }
                    }
                }
                if (extended !== null) {
                    lrsCfg.extended = extended;
                }

                lrsCfg.allowFail = false;

                this.addRecordStore(lrsCfg);
            }
        },

        /**
        @method addRecordStore
        @param {Object} Configuration data

         * TODO:
         * check endpoint for trailing '/'
         * check for unique endpoints
        */
        addRecordStore: function (cfg) {
            this.log("addRecordStore");
            var lrs;
            if (cfg instanceof TinCan.LRS) {
                lrs = cfg;
            }
            else {
                lrs = new TinCan.LRS (cfg);
            }
            this.recordStores.push(lrs);
        },

        /**
        @method prepareStatement
        @param {Object|TinCan.Statement} Base statement properties or
            pre-created TinCan.Statement instance
        @return {TinCan.Statement}
        */
        prepareStatement: function (stmt) {
            this.log("prepareStatement");
            if (! (stmt instanceof TinCan.Statement)) {
                stmt = new TinCan.Statement (stmt);
            }

            if (stmt.actor === null && this.actor !== null) {
                stmt.actor = this.actor;
            }
            if (stmt.target === null && this.activity !== null) {
                stmt.target = this.activity;
            }

            if (this.context !== null) {
                if (stmt.context === null) {
                    stmt.context = this.context;
                }
                else {
                    if (stmt.context.registration === null) {
                        stmt.context.registration = this.context.registration;
                    }
                    if (stmt.context.platform === null) {
                        stmt.context.platform = this.context.platform;
                    }

                    if (this.context.contextActivities !== null) {
                        if (stmt.context.contextActivities === null) {
                            stmt.context.contextActivities = this.context.contextActivities;
                        }
                        else {
                            if (this.context.contextActivities.grouping !== null && stmt.context.contextActivities.grouping === null) {
                                stmt.context.contextActivities.grouping = this.context.contextActivities.grouping;
                            }
                            if (this.context.contextActivities.parent !== null && stmt.context.contextActivities.parent === null) {
                                stmt.context.contextActivities.parent = this.context.contextActivities.parent;
                            }
                            if (this.context.contextActivities.other !== null && stmt.context.contextActivities.other === null) {
                                stmt.context.contextActivities.other = this.context.contextActivities.other;
                            }
                        }
                    }
                }
            }

            return stmt;
        },

        /**
        Calls saveStatement on each configured LRS, provide callback to make it asynchronous

        @method sendStatement
        @param {TinCan.Statement|Object} statement Send statement to LRS
        @param {Function} [callback] Callback function to execute on completion
        */
        sendStatement: function (stmt, callback) {
            this.log("sendStatement");

            // would prefer to use .bind instead of 'self'
            var self = this,
                lrs,
                statement = this.prepareStatement(stmt),
                rsCount = this.recordStores.length,
                i,
                results = [],
                callbackWrapper,
                callbackResults = []
            ;

            if (rsCount > 0) {
                /*
                   if there is a callback that is a function then we need
                   to wrap that function with a function that becomes
                   the new callback that reduces a closure count of the
                   requests that don't have allowFail set to true and
                   when that number hits zero then the original callback
                   is executed
                */
                if (typeof callback === "function") {
                    callbackWrapper = function (err, xhr) {
                        var args;

                        self.log("sendStatement - callbackWrapper: " + rsCount);
                        if (rsCount > 1) {
                            rsCount -= 1;
                            callbackResults.push(
                                {
                                    err: err,
                                    xhr: xhr
                                }
                            );
                        }
                        else if (rsCount === 1) {
                            callbackResults.push(
                                {
                                    err: err,
                                    xhr: xhr
                                }
                            );
                            args = [
                                callbackResults,
                                statement
                            ];
                            callback.apply(this, args);
                        }
                        else {
                            self.log("sendStatement - unexpected record store count: " + rsCount);
                        }
                    };
                }

                for (i = 0; i < rsCount; i += 1) {
                    lrs = this.recordStores[i];

                    results.push(
                        lrs.saveStatement(statement, { callback: callbackWrapper })
                    );
                }
            }
            else {
                this.log("[warning] sendStatement: No LRSs added yet (statement not sent)");
                if (typeof callback === "function") {
                    callback.apply(this, [ null, statement ]);
                }
            }

            return {
                statement: statement,
                results: results
            };
        },

        /**
        Calls retrieveStatement on the first LRS, provide callback to make it asynchronous

        @method getStatement
        @param {String} statement Statement ID to get
        @param {Function} [callback] Callback function to execute on completion
        @return {Array|Result} Array of results, or single result

        TODO: make TinCan track statements it has seen in a local cache to be returned easily
        */
        getStatement: function (stmtId, callback) {
            this.log("getStatement");

            var lrs;

            if (this.recordStores.length > 0) {
                //
                // for statements (for now) we only need to read from the first LRS
                // in the future it may make sense to get all from all LRSes and
                // compare to remove duplicates or allow inspection of them for differences?
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                return lrs.retrieveStatement(stmtId, { callback: callback });
            }

            this.log("[warning] getStatement: No LRSs added yet (statement not retrieved)");
        },

        /**
        Creates a statement used for voiding the passed statement/statement ID and calls
        send statement with the voiding statement.

        @method voidStatement
        @param {TinCan.Statement|String} statement Statement or statement ID to void
        @param {Function} [callback] Callback function to execute on completion
        @param {Object} [options] Options used to build voiding statement
            @param {TinCan.Agent} [options.actor] Agent to be used as 'actor' in voiding statement
        */
        voidStatement: function (stmt, callback, options) {
            this.log("voidStatement");

            // would prefer to use .bind instead of 'self'
            var self = this,
                lrs,
                actor,
                voidingStatement,
                rsCount = this.recordStores.length,
                i,
                results = [],
                callbackWrapper,
                callbackResults = []
            ;

            if (stmt instanceof TinCan.Statement) {
                stmt = stmt.id;
            }

            if (typeof options.actor !== "undefined") {
                actor = options.actor;
            }
            else if (this.actor !== null) {
                actor = this.actor;
            }

            voidingStatement = new TinCan.Statement(
                {
                    actor: actor,
                    verb: {
                        id: "http://adlnet.gov/expapi/verbs/voided"
                    },
                    target: {
                        objectType: "StatementRef",
                        id: stmt
                    }
                }
            );

            if (rsCount > 0) {
                /*
                   if there is a callback that is a function then we need
                   to wrap that function with a function that becomes
                   the new callback that reduces a closure count of the
                   requests that don't have allowFail set to true and
                   when that number hits zero then the original callback
                   is executed
                */
                if (typeof callback === "function") {
                    callbackWrapper = function (err, xhr) {
                        var args;

                        self.log("voidStatement - callbackWrapper: " + rsCount);
                        if (rsCount > 1) {
                            rsCount -= 1;
                            callbackResults.push(
                                {
                                    err: err,
                                    xhr: xhr
                                }
                            );
                        }
                        else if (rsCount === 1) {
                            callbackResults.push(
                                {
                                    err: err,
                                    xhr: xhr
                                }
                            );
                            args = [
                                callbackResults,
                                voidingStatement
                            ];
                            callback.apply(this, args);
                        }
                        else {
                            self.log("voidStatement - unexpected record store count: " + rsCount);
                        }
                    };
                }

                for (i = 0; i < rsCount; i += 1) {
                    lrs = this.recordStores[i];

                    results.push(
                        lrs.saveStatement(voidingStatement, { callback: callbackWrapper })
                    );
                }
            }
            else {
                this.log("[warning] voidStatement: No LRSs added yet (statement not sent)");
                if (typeof callback === "function") {
                    callback.apply(this, [ null, voidingStatement ]);
                }
            }

            return {
                statement: voidingStatement,
                results: results
            };
        },

        /**
        Calls retrieveVoidedStatement on the first LRS, provide callback to make it asynchronous

        @method getVoidedStatement
        @param {String} statement Statement ID to get
        @param {Function} [callback] Callback function to execute on completion
        @return {Array|Result} Array of results, or single result

        TODO: make TinCan track voided statements it has seen in a local cache to be returned easily
        */
        getVoidedStatement: function (stmtId, callback) {
            this.log("getVoidedStatement");

            var lrs;

            if (this.recordStores.length > 0) {
                //
                // for statements (for now) we only need to read from the first LRS
                // in the future it may make sense to get all from all LRSes and
                // compare to remove duplicates or allow inspection of them for differences?
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                return lrs.retrieveVoidedStatement(stmtId, { callback: callback });
            }

            this.log("[warning] getVoidedStatement: No LRSs added yet (statement not retrieved)");
        },

        /**
        Calls saveStatements with list of prepared statements

        @method sendStatements
        @param {Array} Array of statements to send
        @param {Function} Callback function to execute on completion
        */
        sendStatements: function (stmts, callback) {
            this.log("sendStatements");
            var self = this,
                lrs,
                statements = [],
                rsCount = this.recordStores.length,
                i,
                results = [],
                callbackWrapper,
                callbackResults = []
            ;
            if (stmts.length === 0) {
                if (typeof callback === "function") {
                    callback.apply(this, [ null, statements ]);
                }
            }
            else {
                for (i = 0; i < stmts.length; i += 1) {
                    statements.push(
                        this.prepareStatement(stmts[i])
                    );
                }

                if (rsCount > 0) {
                    /*
                       if there is a callback that is a function then we need
                       to wrap that function with a function that becomes
                       the new callback that reduces a closure count of the
                       requests that don't have allowFail set to true and
                       when that number hits zero then the original callback
                       is executed
                    */

                    if (typeof callback === "function") {
                        callbackWrapper = function (err, xhr) {
                            var args;

                            self.log("sendStatements - callbackWrapper: " + rsCount);
                            if (rsCount > 1) {
                                rsCount -= 1;
                                callbackResults.push(
                                    {
                                        err: err,
                                        xhr: xhr
                                    }
                                );
                            }
                            else if (rsCount === 1) {
                                callbackResults.push(
                                    {
                                        err: err,
                                        xhr: xhr
                                    }
                                );
                                args = [
                                    callbackResults,
                                    statements
                                ];
                                callback.apply(this, args);
                            }
                            else {
                                self.log("sendStatements - unexpected record store count: " + rsCount);
                            }
                        };
                    }

                    for (i = 0; i < rsCount; i += 1) {
                        lrs = this.recordStores[i];

                        results.push(
                            lrs.saveStatements(statements, { callback: callbackWrapper })
                        );
                    }
                }
                else {
                    this.log("[warning] sendStatements: No LRSs added yet (statements not sent)");
                    if (typeof callback === "function") {
                        callback.apply(this, [ null, statements ]);
                    }
                }
            }

            return {
                statements: statements,
                results: results
            };
        },

        /**
        @method getStatements
        @param {Object} [cfg] Configuration for request
            @param {Boolean} [cfg.sendActor] Include default actor in query params
            @param {Boolean} [cfg.sendActivity] Include default activity in query params
            @param {Object} [cfg.params] Parameters used to filter.
                            These are the same as those accepted by the
                            <a href="TinCan.LRS.html#method_queryStatements">LRS.queryStatements</a>
                            method.

            @param {Function} [cfg.callback] Function to run at completion

        TODO: support multiple LRSs and flag to use single
        */
        getStatements: function (cfg) {
            this.log("getStatements");
            var queryCfg = {},
                lrs,
                params
            ;
            if (this.recordStores.length > 0) {
                //
                // for get (for now) we only get from one (as they should be the same)
                // but it may make sense to long term try to merge statements, perhaps
                // by using statementId as unique
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                // TODO: need a clone function?
                params = cfg.params || {};

                if (cfg.sendActor && this.actor !== null) {
                    if (lrs.version === "0.9" || lrs.version === "0.95") {
                        params.actor = this.actor;
                    }
                    else {
                        params.agent = this.actor;
                    }
                }
                if (cfg.sendActivity && this.activity !== null) {
                    if (lrs.version === "0.9" || lrs.version === "0.95") {
                        params.target = this.activity;
                    }
                    else {
                        params.activity = this.activity;
                    }
                }
                if (typeof params.registration === "undefined" && this.registration !== null) {
                    params.registration = this.registration;
                }

                queryCfg = {
                    params: params
                };
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }

                return lrs.queryStatements(queryCfg);
            }

            this.log("[warning] getStatements: No LRSs added yet (statements not read)");
        },

        /**
        @method getState
        @param {String} key Key to retrieve from the state
        @param {Object} [cfg] Configuration for request
            @param {Object} [cfg.agent] Agent used in query,
                defaults to 'actor' property if empty
            @param {Object} [cfg.activity] Activity used in query,
                defaults to 'activity' property if empty
            @param {Object} [cfg.registration] Registration used in query,
                defaults to 'registration' property if empty
            @param {Function} [cfg.callback] Function to run with state
        */
        getState: function (key, cfg) {
            this.log("getState");
            var queryCfg,
                lrs
            ;

            if (this.recordStores.length > 0) {
                //
                // for state (for now) we are only going to store to the first LRS
                // so only get from there too
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                queryCfg = {
                    agent: (typeof cfg.agent !== "undefined" ? cfg.agent : this.actor),
                    activity: (typeof cfg.activity !== "undefined" ? cfg.activity : this.activity)
                };
                if (typeof cfg.registration !== "undefined") {
                    queryCfg.registration = cfg.registration;
                }
                else if (this.registration !== null) {
                    queryCfg.registration = this.registration;
                }
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }

                return lrs.retrieveState(key, queryCfg);
            }

            this.log("[warning] getState: No LRSs added yet (state not retrieved)");
        },

        /**
        @method setState
        @param {String} key Key to store into the state
        @param {String|Object} val Value to store into the state, objects will be stringified to JSON
        @param {Object} [cfg] Configuration for request
            @param {Object} [cfg.agent] Agent used in query,
                defaults to 'actor' property if empty
            @param {Object} [cfg.activity] Activity used in query,
                defaults to 'activity' property if empty
            @param {Object} [cfg.registration] Registration used in query,
                defaults to 'registration' property if empty
            @param {String} [cfg.lastSHA1] SHA1 of the previously seen existing state
            @param {String} [cfg.contentType] Content-Type to specify in headers
            @param {Boolean} [cfg.overwriteJSON] If the Content-Type is JSON, should a PUT be used? 
            @param {Function} [cfg.callback] Function to run with state
        */
        setState: function (key, val, cfg) {
            this.log("setState");
            var queryCfg,
                lrs
            ;

            if (this.recordStores.length > 0) {
                //
                // for state (for now) we are only going to store to the first LRS
                // so only get from there too
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                queryCfg = {
                    agent: (typeof cfg.agent !== "undefined" ? cfg.agent : this.actor),
                    activity: (typeof cfg.activity !== "undefined" ? cfg.activity : this.activity)
                };
                if (typeof cfg.registration !== "undefined") {
                    queryCfg.registration = cfg.registration;
                }
                else if (this.registration !== null) {
                    queryCfg.registration = this.registration;
                }
                if (typeof cfg.lastSHA1 !== "undefined") {
                    queryCfg.lastSHA1 = cfg.lastSHA1;
                }
                if (typeof cfg.contentType !== "undefined") {
                    queryCfg.contentType = cfg.contentType;
                    if ((typeof cfg.overwriteJSON !== "undefined") && (! cfg.overwriteJSON) && (TinCan.Utils.isApplicationJSON(cfg.contentType))) {
                        queryCfg.method = "POST";
                    }
                }
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }

                return lrs.saveState(key, val, queryCfg);
            }

            this.log("[warning] setState: No LRSs added yet (state not saved)");
        },

        /**
        @method deleteState
        @param {String|null} key Key to remove from the state, or null to clear all
        @param {Object} [cfg] Configuration for request
            @param {Object} [cfg.agent] Agent used in query,
                defaults to 'actor' property if empty
            @param {Object} [cfg.activity] Activity used in query,
                defaults to 'activity' property if empty
            @param {Object} [cfg.registration] Registration used in query,
                defaults to 'registration' property if empty
            @param {Function} [cfg.callback] Function to run with state
        */
        deleteState: function (key, cfg) {
            this.log("deleteState");
            var queryCfg,
                lrs
            ;

            if (this.recordStores.length > 0) {
                //
                // for state (for now) we are only going to store to the first LRS
                // so only get from there too
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                queryCfg = {
                    agent: (typeof cfg.agent !== "undefined" ? cfg.agent : this.actor),
                    activity: (typeof cfg.activity !== "undefined" ? cfg.activity : this.activity)
                };
                if (typeof cfg.registration !== "undefined") {
                    queryCfg.registration = cfg.registration;
                }
                else if (this.registration !== null) {
                    queryCfg.registration = this.registration;
                }
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }

                return lrs.dropState(key, queryCfg);
            }

            this.log("[warning] deleteState: No LRSs added yet (state not deleted)");
        },

        /**
        @method getActivityProfile
        @param {String} key Key to retrieve from the profile
        @param {Object} [cfg] Configuration for request
            @param {Object} [cfg.activity] Activity used in query,
                defaults to 'activity' property if empty
            @param {Function} [cfg.callback] Function to run with activity profile
        */
        getActivityProfile: function (key, cfg) {
            this.log("getActivityProfile");
            var queryCfg,
                lrs
            ;

            if (this.recordStores.length > 0) {
                //
                // for activity profiles (for now) we are only going to store to the first LRS
                // so only get from there too
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                queryCfg = {
                    activity: (typeof cfg.activity !== "undefined" ? cfg.activity : this.activity)
                };
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }

                return lrs.retrieveActivityProfile(key, queryCfg);
            }

            this.log("[warning] getActivityProfile: No LRSs added yet (activity profile not retrieved)");
        },

        /**
        @method setActivityProfile
        @param {String} key Key to store into the activity profile
        @param {String|Object} val Value to store into the activity profile, objects will be stringified to JSON
        @param {Object} [cfg] Configuration for request
            @param {Object} [cfg.activity] Activity used in query,
                defaults to 'activity' property if empty
            @param {String} [cfg.lastSHA1] SHA1 of the previously seen existing profile
            @param {String} [cfg.contentType] Content-Type to specify in headers
            @param {Boolean} [cfg.overwriteJSON] If the Content-Type is JSON, should a PUT be used?
            @param {Function} [cfg.callback] Function to run with activity profile
        */
        setActivityProfile: function (key, val, cfg) {
            this.log("setActivityProfile");
            var queryCfg,
                lrs
            ;

            if (this.recordStores.length > 0) {
                //
                // for activity profile (for now) we are only going to store to the first LRS
                // so only get from there too
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                queryCfg = {
                    activity: (typeof cfg.activity !== "undefined" ? cfg.activity : this.activity)
                };
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }
                if (typeof cfg.lastSHA1 !== "undefined") {
                    queryCfg.lastSHA1 = cfg.lastSHA1;
                }
                if (typeof cfg.contentType !== "undefined") {
                    queryCfg.contentType = cfg.contentType;
                    if ((typeof cfg.overwriteJSON !== "undefined") && (! cfg.overwriteJSON) && (TinCan.Utils.isApplicationJSON(cfg.contentType))) {
                        queryCfg.method = "POST";
                    }
                }

                return lrs.saveActivityProfile(key, val, queryCfg);
            }

            this.log("[warning] setActivityProfile: No LRSs added yet (activity profile not saved)");
        },

        /**
        @method deleteActivityProfile
        @param {String|null} key Key to remove from the activity profile, or null to clear all
        @param {Object} [cfg] Configuration for request
            @param {Object} [cfg.activity] Activity used in query,
                defaults to 'activity' property if empty
            @param {Function} [cfg.callback] Function to run with activity profile
        */
        deleteActivityProfile: function (key, cfg) {
            this.log("deleteActivityProfile");
            var queryCfg,
                lrs
            ;

            if (this.recordStores.length > 0) {
                //
                // for activity profile (for now) we are only going to store to the first LRS
                // so only get from there too
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                queryCfg = {
                    activity: (typeof cfg.activity !== "undefined" ? cfg.activity : this.activity)
                };
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }

                return lrs.dropActivityProfile(key, queryCfg);
            }

            this.log("[warning] deleteActivityProfile: No LRSs added yet (activity profile not deleted)");
        },

        /**
        @method getAgentProfile
        @param {String} key Key to retrieve from the profile
        @param {Object} [cfg] Configuration for request
            @param {Object} [cfg.agent] Agent used in query,
                defaults to 'actor' property if empty
            @param {Function} [cfg.callback] Function to run with agent profile
        */
        getAgentProfile: function (key, cfg) {
            this.log("getAgentProfile");
            var queryCfg,
                lrs
            ;

            if (this.recordStores.length > 0) {
                //
                // for agent profiles (for now) we are only going to store to the first LRS
                // so only get from there too
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                queryCfg = {
                    agent: (typeof cfg.agent !== "undefined" ? cfg.agent : this.actor)
                };
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }

                return lrs.retrieveAgentProfile(key, queryCfg);
            }

            this.log("[warning] getAgentProfile: No LRSs added yet (agent profile not retrieved)");
        },

        /**
        @method setAgentProfile
        @param {String} key Key to store into the agent profile
        @param {String|Object} val Value to store into the agent profile, objects will be stringified to JSON
        @param {Object} [cfg] Configuration for request
            @param {Object} [cfg.agent] Agent used in query,
                defaults to 'actor' property if empty
            @param {String} [cfg.lastSHA1] SHA1 of the previously seen existing profile
            @param {String} [cfg.contentType] Content-Type to specify in headers
            @param {Boolean} [cfg.overwriteJSON] If the Content-Type is JSON, should a PUT be used?
            @param {Function} [cfg.callback] Function to run with agent profile
        */
        setAgentProfile: function (key, val, cfg) {
            this.log("setAgentProfile");
            var queryCfg,
                lrs
            ;

            if (this.recordStores.length > 0) {
                //
                // for agent profile (for now) we are only going to store to the first LRS
                // so only get from there too
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                queryCfg = {
                    agent: (typeof cfg.agent !== "undefined" ? cfg.agent : this.actor)
                };
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }
                if (typeof cfg.lastSHA1 !== "undefined") {
                    queryCfg.lastSHA1 = cfg.lastSHA1;
                }
                if (typeof cfg.contentType !== "undefined") {
                    queryCfg.contentType = cfg.contentType;
                    if ((typeof cfg.overwriteJSON !== "undefined") && (! cfg.overwriteJSON) && (TinCan.Utils.isApplicationJSON(cfg.contentType))) {
                        queryCfg.method = "POST";
                    }
                }

                return lrs.saveAgentProfile(key, val, queryCfg);
            }

            this.log("[warning] setAgentProfile: No LRSs added yet (agent profile not saved)");
        },

        /**
        @method deleteAgentProfile
        @param {String|null} key Key to remove from the agent profile, or null to clear all
        @param {Object} [cfg] Configuration for request
            @param {Object} [cfg.agent] Agent used in query,
                defaults to 'actor' property if empty
            @param {Function} [cfg.callback] Function to run with agent profile
        */
        deleteAgentProfile: function (key, cfg) {
            this.log("deleteAgentProfile");
            var queryCfg,
                lrs
            ;

            if (this.recordStores.length > 0) {
                //
                // for agent profile (for now) we are only going to store to the first LRS
                // so only get from there too
                //
                // TODO: make this the first non-allowFail LRS but for now it should
                // be good enough to make it the first since we know the LMS provided
                // LRS is the first
                //
                lrs = this.recordStores[0];

                cfg = cfg || {};

                queryCfg = {
                    agent: (typeof cfg.agent !== "undefined" ? cfg.agent : this.actor)
                };
                if (typeof cfg.callback !== "undefined") {
                    queryCfg.callback = cfg.callback;
                }

                return lrs.dropAgentProfile(key, queryCfg);
            }

            this.log("[warning] deleteAgentProfile: No LRSs added yet (agent profile not deleted)");
        }
    };

    /**
    @property DEBUG
    @static
    @default false
    */
    TinCan.DEBUG = false;

    /**
    Turn on debug logging

    @method enableDebug
    @static
    */
    TinCan.enableDebug = function () {
        TinCan.DEBUG = true;
    };

    /**
    Turn off debug logging

    @method disableDebug
    @static
    */
    TinCan.disableDebug = function () {
        TinCan.DEBUG = false;
    };

    /**
    @method versions
    @return {Array} Array of supported version numbers
    @static
    */
    TinCan.versions = function () {
        // newest first so we can use the first as the default
        return [
            "1.0.1",
            "1.0.0",
            "0.95",
            "0.9"
        ];
    };

    /*global module*/
    // Support the CommonJS method for exporting our single global
    if (typeof module === "object") {
        module.exports = TinCan;
    }
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Utils
**/
(function () {
    "use strict";

    /**
    @class TinCan.Utils
    */
    TinCan.Utils = {
        /**
        Generates a UUIDv4 compliant string that should be reasonably unique

        @method getUUID
        @return {String} UUID
        @static

        Excerpt from: http://www.broofa.com/Tools/Math.uuid.js (v1.4)
        http://www.broofa.com
        mailto:robert@broofa.com
        Copyright (c) 2010 Robert Kieffer
        Dual licensed under the MIT and GPL licenses.
        */
        getUUID: function () {
            /*jslint bitwise: true, eqeq: true */
            return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(
                /[xy]/g,
                function (c) {
                    var r = Math.random() * 16|0, v = c == "x" ? r : (r&0x3|0x8);
                    return v.toString(16);
                }
            );
        },

        /**
        @method getISODateString
        @static
        @param {Date} date Date to stringify
        @return {String} ISO date String
        */
        getISODateString: function (d) {
            function pad (val, n) {
                var padder,
                    tempVal;
                if (typeof val === "undefined" || val === null) {
                    val = 0;
                }
                if (typeof n === "undefined" || n === null) {
                    n = 2;
                }
                padder = Math.pow(10, n-1);
                tempVal = val.toString();

                while (val < padder && padder > 1) {
                    tempVal = "0" + tempVal;
                    padder = padder / 10;
                }

                return tempVal;
            }

            return d.getUTCFullYear() + "-" +
                pad(d.getUTCMonth() + 1) + "-" +
                pad(d.getUTCDate()) + "T" +
                pad(d.getUTCHours()) + ":" +
                pad(d.getUTCMinutes()) + ":" +
                pad(d.getUTCSeconds()) + "." +
                pad(d.getUTCMilliseconds(), 3) + "Z";
        },

        /**
        @method convertISO8601DurationToMilliseconds
        @static
        @param {String} ISO8601Duration Duration in ISO8601 format
        @return {Int} Duration in milliseconds

        Note: does not handle input strings with years, months and days
        */
        convertISO8601DurationToMilliseconds: function (ISO8601Duration) {
            var isValueNegative = (ISO8601Duration.indexOf("-") >= 0),
                indexOfT = ISO8601Duration.indexOf("T"),
                indexOfH = ISO8601Duration.indexOf("H"),
                indexOfM = ISO8601Duration.indexOf("M"),
                indexOfS = ISO8601Duration.indexOf("S"),
                hours,
                minutes,
                seconds,
                durationInMilliseconds;

            if ((indexOfT === -1) || ((indexOfM !== -1) && (indexOfM < indexOfT)) || (ISO8601Duration.indexOf("D") !== -1) || (ISO8601Duration.indexOf("Y") !== -1)) {
                throw new Error("ISO 8601 timestamps including years, months and/or days are not currently supported");
            }

            if (indexOfH === -1) {
                indexOfH = indexOfT;
                hours = 0;
            }
            else {
                hours = parseInt(ISO8601Duration.slice(indexOfT + 1, indexOfH), 10);
            }

            if (indexOfM === -1) {
                indexOfM = indexOfT;
                minutes = 0;
            }
            else {
                minutes = parseInt(ISO8601Duration.slice(indexOfH + 1, indexOfM), 10);
            }

            seconds = parseFloat(ISO8601Duration.slice(indexOfM + 1, indexOfS));

            durationInMilliseconds = parseInt((((((hours * 60) + minutes) * 60) + seconds) * 1000), 10);
            if (isNaN(durationInMilliseconds)){
                durationInMilliseconds = 0;
            }
            if (isValueNegative) {
                durationInMilliseconds = durationInMilliseconds * -1;
            }

            return durationInMilliseconds;
        },

        /**
        @method convertMillisecondsToISO8601Duration
        @static
        @param {Int} inputMilliseconds Duration in milliseconds
        @return {String} Duration in ISO8601 format
        */
        convertMillisecondsToISO8601Duration: function (inputMilliseconds) {
            var hours,
                minutes,
                seconds,
                i_inputMilliseconds = parseInt(inputMilliseconds, 10),
                inputIsNegative = "",
                rtnStr = "";

            if (i_inputMilliseconds < 0) {
                inputIsNegative = "-";
                i_inputMilliseconds = i_inputMilliseconds * -1;
            }

            hours = parseInt(((i_inputMilliseconds) / 3600000), 10);
            minutes = parseInt((((i_inputMilliseconds) % 3600000) / 60000), 10);
            seconds = (((i_inputMilliseconds) % 3600000) % 60000) / 1000;

            rtnStr = inputIsNegative + "PT";
            if (hours > 0) {
                rtnStr += hours + "H";
            }

            if (minutes > 0) {
                rtnStr += minutes + "M";
            }

            rtnStr += seconds + "S";

            return rtnStr;
        },

        /**
        @method getSHA1String
        @static
        @param {String} str Content to hash
        @return {String} SHA1 for contents
        */
        getSHA1String: function (str) {
            /*global CryptoJS*/

            return CryptoJS.SHA1(str).toString(CryptoJS.enc.Hex);
        },

        /**
        @method getBase64String
        @static
        @param {String} str Content to encode
        @return {String} Base64 encoded contents
        */
        getBase64String: function (str) {
            /*global CryptoJS*/

            return CryptoJS.enc.Base64.stringify(
                CryptoJS.enc.Latin1.parse(str)
            );
        },

        /**
        Intended to be inherited by objects with properties that store
        display values in a language based "dictionary"

        @method getLangDictionaryValue
        @param {String} prop Property name storing the dictionary
        @param {String} [lang] Language to return
        @return {String}
        */
        getLangDictionaryValue: function (prop, lang) {
            var langDict = this[prop],
                key;

            if (typeof lang !== "undefined" && typeof langDict[lang] !== "undefined") {
                return langDict[lang];
            }
            if (typeof langDict.und !== "undefined") {
                return langDict.und;
            }
            if (typeof langDict["en-US"] !== "undefined") {
                return langDict["en-US"];
            }
            for (key in langDict) {
                if (langDict.hasOwnProperty(key)) {
                    return langDict[key];
                }
            }

            return "";
        },

        /**
        @method parseURL
        @param {String} url
        @return {Object} Object of values
        @private
        */
        parseURL: function (url) {
            //
            // see http://stackoverflow.com/a/21553982
            // and http://stackoverflow.com/a/2880929
            //
            var reURLInformation,
                match,
                result,
                paramMatch,
                pl     = /\+/g,  // Regex for replacing addition symbol with a space
                search = /([^&=]+)=?([^&]*)/g,
                decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); };

            reURLInformation = new RegExp(
                [
                    "^(https?:)//", // protocol
                    "(([^:/?#]*)(?::([0-9]+))?)", // host (hostname and port)
                    "(/[^?#]*)", // pathname
                    "(\\?[^#]*|)", // search
                    "(#.*|)$" // hash
                ].join("")
            );
            match = url.match(reURLInformation);
            result = {
                protocol: match[1],
                host: match[2],
                hostname: match[3],
                port: match[4],
                pathname: match[5],
                search: match[6],
                hash: match[7],
                params: {}
            };

            // 'path' is for backwards compatibility
            result.path = result.protocol + "//" + result.host + result.pathname;

            if (result.search !== "") {
                // extra parens to let jshint know this is an expression
                while ((paramMatch = search.exec(result.search.substring(1)))) {
                    result.params[decode(paramMatch[1])] = decode(paramMatch[2]);
                }
            }

            return result;
        },

        /**
        @method getServerRoot
        @param {String} absoluteUrl
        @return {String} server root of url
        @private
        */
        getServerRoot: function (absoluteUrl) {
            var urlParts = absoluteUrl.split("/");
            return urlParts[0] + "//" + urlParts[2];
        },

        /**
        @method getContentTypeFromHeader
        @static
        @param {String} Content-Type header value
        @return {String} Primary value from Content-Type
        */
        getContentTypeFromHeader: function (header) {
            return (String(header).split(";"))[0];
        },

        /**
        @method isApplicationJSON
        @static
        @param {String} Content-Type header value
        @return {Boolean} whether "application/json" was matched
        */
        isApplicationJSON: function (header) {
            return TinCan.Utils.getContentTypeFromHeader(header).toLowerCase().indexOf("application/json") === 0;
        }
    };
}());

/*
    Copyright 2012-2013 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.LRS
**/
(function () {
    "use strict";
    /**
    @class TinCan.LRS
    @constructor
    */
    var LRS = TinCan.LRS = function (cfg) {
        this.log("constructor");

        /**
        @property endpoint
        @type String
        */
        this.endpoint = null;

        /**
        @property version
        @type String
        */
        this.version = null;

        /**
        @property auth
        @type String
        */
        this.auth = null;

        /**
        @property allowFail
        @type Boolean
        @default true
        */
        this.allowFail = true;

        /**
        @property extended
        @type Object
        */
        this.extended = null;

        this.init(cfg);
    };
    LRS.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "LRS",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        */
        init: function (cfg) {
            this.log("init");

            var versions = TinCan.versions(),
                versionMatch = false,
                i
            ;

            cfg = cfg || {};

            if (cfg.hasOwnProperty("alertOnRequestFailure")) {
                this.log("'alertOnRequestFailure' is deprecated (alerts have been removed) no need to set it now");
            }

            if (! cfg.hasOwnProperty("endpoint") || cfg.endpoint === null || cfg.endpoint === "") {
                this.log("[error] LRS invalid: no endpoint");
                throw {
                    code: 3,
                    mesg: "LRS invalid: no endpoint"
                };
            }

            this.endpoint = String(cfg.endpoint);
            if (this.endpoint.slice(-1) !== "/") {
                this.log("adding trailing slash to endpoint");
                this.endpoint += "/";
            }

            if (cfg.hasOwnProperty("allowFail")) {
                this.allowFail = cfg.allowFail;
            }

            if (cfg.hasOwnProperty("auth")) {
                this.auth = cfg.auth;
            }
            else if (cfg.hasOwnProperty("username") && cfg.hasOwnProperty("password")) {
                this.auth = "Basic " + TinCan.Utils.getBase64String(cfg.username + ":" + cfg.password);
            }

            if (cfg.hasOwnProperty("extended")) {
                this.extended = cfg.extended;
            }

            //
            // provide a hook method that environments can override
            // to handle anything necessary in the initialization
            // process that is customized to them, such as cross domain
            // setup in browsers, default implementation is empty
            //
            // this hook must run prior to version detection so that
            // request handling can be set up before requesting the
            // LRS version via the /about resource
            //
            this._initByEnvironment(cfg);

            if (typeof cfg.version !== "undefined") {
                this.log("version: " + cfg.version);
                for (i = 0; i < versions.length; i += 1) {
                    if (versions[i] === cfg.version) {
                        versionMatch = true;
                        break;
                    }
                }
                if (! versionMatch) {
                    this.log("[error] LRS invalid: version not supported (" + cfg.version + ")");
                    throw {
                        code: 5,
                        mesg: "LRS invalid: version not supported (" + cfg.version + ")"
                    };
                }
                this.version = cfg.version;
            }
            else {
                //
                // assume max supported when not specified,
                // TODO: add detection of LRS from call to endpoint
                //
                this.version = versions[0];
            }
        },

        /**
        Method should be overloaded by an environment to do per
        environment specifics such that the LRS can make a call
        to set the version if not provided

        @method _initByEnvironment
        @private
        */
        _initByEnvironment: function () {
            this.log("_initByEnvironment not overloaded - no environment loaded?");
        },

        /**
        Method should be overloaded by an environment to do per
        environment specifics for sending requests to the LRS

        @method _makeRequest
        @private
        */
        _makeRequest: function () {
            this.log("_makeRequest not overloaded - no environment loaded?");
        },

        /**
        Method is overloaded by the browser environment in order to test converting an
        HTTP request that is greater than a defined length

        @method _IEModeConversion
        @private
        */
        _IEModeConversion: function () {
            this.log("_IEModeConversion not overloaded - browser environment not loaded.");
        },

        /**
        Method used to send a request via browser objects to the LRS

        @method sendRequest
        @param {Object} cfg Configuration for request
            @param {String} cfg.url URL portion to add to endpoint
            @param {String} [cfg.method] GET, PUT, POST, etc.
            @param {Object} [cfg.params] Parameters to set on the querystring
            @param {String} [cfg.data] String of body content
            @param {Object} [cfg.headers] Additional headers to set in the request
            @param {Function} [cfg.callback] Function to run at completion
                @param {String|Null} cfg.callback.err If an error occurred, this parameter will contain the HTTP status code.
                    If the operation succeeded, err will be null.
                @param {Object} cfg.callback.xhr XHR object
            @param {Boolean} [cfg.ignore404] Whether 404 status codes should be considered an error
        @return {Object} XHR if called in a synchronous way (in other words no callback)
        */
        sendRequest: function (cfg) {
            this.log("sendRequest");
            var fullUrl = this.endpoint + cfg.url,
                headers = {},
                prop
            ;

            // respect absolute URLs passed in
            if (cfg.url.indexOf("http") === 0) {
                fullUrl = cfg.url;
            }

            // add extended LMS-specified values to the params
            if (this.extended !== null) {
                cfg.params = cfg.params || {};

                for (prop in this.extended) {
                    if (this.extended.hasOwnProperty(prop)) {
                        // don't overwrite cfg.params values that have already been added to the request with our extended params
                        if (! cfg.params.hasOwnProperty(prop)) {
                            if (this.extended[prop] !== null) {
                                cfg.params[prop] = this.extended[prop];
                            }
                        }
                    }
                }
            }

            // consolidate headers
            headers.Authorization = this.auth;
            if (this.version !== "0.9") {
                headers["X-Experience-API-Version"] = this.version;
            }

            for (prop in cfg.headers) {
                if (cfg.headers.hasOwnProperty(prop)) {
                    headers[prop] = cfg.headers[prop];
                }
            }

            return this._makeRequest(fullUrl, headers, cfg);
        },

        /**
        Method used to determine the LRS version

        @method about
        @param {Object} cfg Configuration object for the about request
            @param {Function} [cfg.callback] Callback to execute upon receiving a response
            @param {Object} [cfg.params] this is needed, but can be empty
        @return {Object} About which holds the version, or asyncrhonously calls a specified callback
        */
        about: function (cfg) {
            this.log("about");
            var requestCfg,
                requestResult,
                callbackWrapper;

            cfg = cfg || {};

            requestCfg = {
                url: "about",
                method: "GET",
                params: {}
            };
            if (typeof cfg.callback !== "undefined") {
                callbackWrapper = function (err, xhr) {
                    var result = xhr;

                    if (err === null) {
                        result = TinCan.About.fromJSON(xhr.responseText);
                    }

                    cfg.callback(err, result);
                };
                requestCfg.callback = callbackWrapper;
            }

            requestResult = this.sendRequest(requestCfg);

            if (callbackWrapper) {
                return;
            }

            if (requestResult.err === null) {
                requestResult.xhr = TinCan.About.fromJSON(requestResult.xhr.responseText);
            }
            return requestResult;
        },

        /**
        Save a statement, when used from a browser sends to the endpoint using the RESTful interface.
        Use a callback to make the call asynchronous.

        @method saveStatement
        @param {Object} TinCan.Statement to send
        @param {Object} [cfg] Configuration used when saving
            @param {Function} [cfg.callback] Callback to execute on completion
        */
        saveStatement: function (stmt, cfg) {
            this.log("saveStatement");
            var requestCfg,
                versionedStatement;

            cfg = cfg || {};

            try {
                versionedStatement = stmt.asVersion( this.version );
            }
            catch (ex) {
                if (this.allowFail) {
                    this.log("[warning] statement could not be serialized in version (" + this.version + "): " + ex);
                    if (typeof cfg.callback !== "undefined") {
                        cfg.callback(null, null);
                        return;
                    }
                    return {
                        err: null,
                        xhr: null
                    };
                }

                this.log("[error] statement could not be serialized in version (" + this.version + "): " + ex);
                if (typeof cfg.callback !== "undefined") {
                    cfg.callback(ex, null);
                    return;
                }
                return {
                    err: ex,
                    xhr: null
                };
            }

            requestCfg = {
                url: "statements",
                data: JSON.stringify(versionedStatement),
                headers: {
                    "Content-Type": "application/json"
                }
            };
            if (stmt.id !== null) {
                requestCfg.method = "PUT";
                requestCfg.params = {
                    statementId: stmt.id
                };
            }
            else {
                requestCfg.method = "POST";
            }

            if (typeof cfg.callback !== "undefined") {
                requestCfg.callback = cfg.callback;
            }

            return this.sendRequest(requestCfg);
        },

        /**
        Retrieve a statement, when used from a browser sends to the endpoint using the RESTful interface.

        @method retrieveStatement
        @param {String} ID of statement to retrieve
        @param {Object} [cfg] Configuration options
            @param {Function} [cfg.callback] Callback to execute on completion
        @return {Object} TinCan.Statement retrieved
        */
        retrieveStatement: function (stmtId, cfg) {
            this.log("retrieveStatement");
            var requestCfg,
                requestResult,
                callbackWrapper;

            cfg = cfg || {};

            requestCfg = {
                url: "statements",
                method: "GET",
                params: {
                    statementId: stmtId
                }
            };
            if (typeof cfg.callback !== "undefined") {
                callbackWrapper = function (err, xhr) {
                    var result = xhr;

                    if (err === null) {
                        result = TinCan.Statement.fromJSON(xhr.responseText);
                    }

                    cfg.callback(err, result);
                };
                requestCfg.callback = callbackWrapper;
            }

            requestResult = this.sendRequest(requestCfg);
            if (! callbackWrapper) {
                requestResult.statement = null;
                if (requestResult.err === null) {
                    requestResult.statement = TinCan.Statement.fromJSON(requestResult.xhr.responseText);
                }
            }

            return requestResult;
        },

        /**
        Retrieve a voided statement, when used from a browser sends to the endpoint using the RESTful interface.

        @method retrieveVoidedStatement
        @param {String} ID of voided statement to retrieve
        @param {Object} [cfg] Configuration options
            @param {Function} [cfg.callback] Callback to execute on completion
        @return {Object} TinCan.Statement retrieved
        */
        retrieveVoidedStatement: function (stmtId, cfg) {
            this.log("retrieveVoidedStatement");
            var requestCfg,
                requestResult,
                callbackWrapper;

            cfg = cfg || {};

            requestCfg = {
                url: "statements",
                method: "GET",
                params: {}
            };
            if (this.version === "0.9" || this.version === "0.95") {
                requestCfg.params.statementId = stmtId;
            }
            else {
                requestCfg.params.voidedStatementId = stmtId;
            }

            if (typeof cfg.callback !== "undefined") {
                callbackWrapper = function (err, xhr) {
                    var result = xhr;

                    if (err === null) {
                        result = TinCan.Statement.fromJSON(xhr.responseText);
                    }

                    cfg.callback(err, result);
                };
                requestCfg.callback = callbackWrapper;
            }

            requestResult = this.sendRequest(requestCfg);
            if (! callbackWrapper) {
                requestResult.statement = null;
                if (requestResult.err === null) {
                    requestResult.statement = TinCan.Statement.fromJSON(requestResult.xhr.responseText);
                }
            }

            return requestResult;
        },

        /**
        Save a set of statements, when used from a browser sends to the endpoint using the RESTful interface.
        Use a callback to make the call asynchronous.

        @method saveStatements
        @param {Array} Array of statements or objects convertable to statements
        @param {Object} [cfg] Configuration used when saving
            @param {Function} [cfg.callback] Callback to execute on completion
        */
        saveStatements: function (stmts, cfg) {
            this.log("saveStatements");
            var requestCfg,
                versionedStatement,
                versionedStatements = [],
                i
            ;

            cfg = cfg || {};

            if (stmts.length === 0) {
                if (typeof cfg.callback !== "undefined") {
                    cfg.callback(new Error("no statements"), null);
                    return;
                }
                return {
                    err: new Error("no statements"),
                    xhr: null
                };
            }

            for (i = 0; i < stmts.length; i += 1) {
                try {
                    versionedStatement = stmts[i].asVersion( this.version );
                }
                catch (ex) {
                    if (this.allowFail) {
                        this.log("[warning] statement could not be serialized in version (" + this.version + "): " + ex);
                        if (typeof cfg.callback !== "undefined") {
                            cfg.callback(null, null);
                            return;
                        }
                        return {
                            err: null,
                            xhr: null
                        };
                    }

                    this.log("[error] statement could not be serialized in version (" + this.version + "): " + ex);
                    if (typeof cfg.callback !== "undefined") {
                        cfg.callback(ex, null);
                        return;
                    }
                    return {
                        err: ex,
                        xhr: null
                    };
                }
                versionedStatements.push(versionedStatement);
            }

            requestCfg = {
                url: "statements",
                method: "POST",
                data: JSON.stringify(versionedStatements),
                headers: {
                    "Content-Type": "application/json"
                }
            };
            if (typeof cfg.callback !== "undefined") {
                requestCfg.callback = cfg.callback;
            }

            return this.sendRequest(requestCfg);
        },

        /**
        Fetch a set of statements, when used from a browser sends to the endpoint using the
        RESTful interface.  Use a callback to make the call asynchronous.

        @method queryStatements
        @param {Object} [cfg] Configuration used to query
            @param {Object} [cfg.params] Query parameters
                @param {TinCan.Agent|TinCan.Group} [cfg.params.agent] Agent matches 'actor' or 'object'
                @param {TinCan.Verb} [cfg.params.verb] Verb to query on
                @param {TinCan.Activity} [cfg.params.activity] Activity to query on
                @param {String} [cfg.params.registration] Registration UUID
                @param {Boolean} [cfg.params.related_activities] Match related activities
                @param {Boolean} [cfg.params.related_agents] Match related agents
                @param {String} [cfg.params.since] Match statements stored since specified timestamp
                @param {String} [cfg.params.until] Match statements stored at or before specified timestamp
                @param {Integer} [cfg.params.limit] Number of results to retrieve
                @param {String} [cfg.params.format] One of "ids", "exact", "canonical" (default: "exact")
                @param {Boolean} [cfg.params.attachments] Include attachments in multipart response or don't (defualt: false)
                @param {Boolean} [cfg.params.ascending] Return results in ascending order of stored time

                @param {TinCan.Agent} [cfg.params.actor] (Removed in 1.0.0, use 'agent' instead) Agent matches 'actor'
                @param {TinCan.Activity|TinCan.Agent|TinCan.Statement} [cfg.params.target] (Removed in 1.0.0, use 'activity' or 'agent' instead) Activity, Agent, or Statement matches 'object'
                @param {TinCan.Agent} [cfg.params.instructor] (Removed in 1.0.0, use 'agent' + 'related_agents' instead) Agent matches 'context:instructor'
                @param {Boolean} [cfg.params.context] (Removed in 1.0.0, use 'activity' instead) When filtering on target, include statements with matching context
                @param {Boolean} [cfg.params.authoritative] (Removed in 1.0.0) Get authoritative results
                @param {Boolean} [cfg.params.sparse] (Removed in 1.0.0, use 'format' instead) Get sparse results

            @param {Function} [cfg.callback] Callback to execute on completion
                @param {String|null} cfg.callback.err Error status or null if succcess
                @param {TinCan.StatementsResult|XHR} cfg.callback.response Receives a StatementsResult argument
        @return {Object} Request result
        */
        queryStatements: function (cfg) {
            this.log("queryStatements");
            var requestCfg,
                requestResult,
                callbackWrapper;

            cfg = cfg || {};
            cfg.params = cfg.params || {};

            //
            // if they misconfigured (possibly do to version mismatches) the
            // query then don't try to send a request at all, rather than give
            // them invalid results
            //
            try {
                requestCfg = this._queryStatementsRequestCfg(cfg);
            }
            catch (ex) {
                this.log("[error] Query statements failed - " + ex);
                if (typeof cfg.callback !== "undefined") {
                    cfg.callback(ex, {});
                }

                return {
                    err: ex,
                    statementsResult: null
                };
            }

            if (typeof cfg.callback !== "undefined") {
                callbackWrapper = function (err, xhr) {
                    var result = xhr;

                    if (err === null) {
                        result = TinCan.StatementsResult.fromJSON(xhr.responseText);
                    }

                    cfg.callback(err, result);
                };
                requestCfg.callback = callbackWrapper;
            }

            requestResult = this.sendRequest(requestCfg);
            requestResult.config = requestCfg;

            if (! callbackWrapper) {
                requestResult.statementsResult = null;
                if (requestResult.err === null) {
                    requestResult.statementsResult = TinCan.StatementsResult.fromJSON(requestResult.xhr.responseText);
                }
            }

            return requestResult;
        },

        /**
        Build a request config object that can be passed to sendRequest() to make a query request

        @method _queryStatementsRequestCfg
        @private
        @param {Object} [cfg] See configuration for {{#crossLink "TinCan.LRS/queryStatements"}}{{/crossLink}}
        @return {Object} Request configuration object
        */
        _queryStatementsRequestCfg: function (cfg) {
            this.log("_queryStatementsRequestCfg");
            var params = {},
                returnCfg = {
                    url: "statements",
                    method: "GET",
                    params: params
                },
                jsonProps = [
                    "agent",
                    "actor",
                    "object",
                    "instructor"
                ],
                idProps = [
                    "verb",
                    "activity"
                ],
                valProps = [
                    "registration",
                    "context",
                    "since",
                    "until",
                    "limit",
                    "authoritative",
                    "sparse",
                    "ascending",
                    "related_activities",
                    "related_agents",
                    "format",
                    "attachments"
                ],
                i,
                prop,
                //
                // list of parameters that are supported in all versions (supported by
                // this library) of the spec
                //
                universal = {
                    verb: true,
                    registration: true,
                    since: true,
                    until: true,
                    limit: true,
                    ascending: true
                },
                //
                // future proofing here, "supported" is an object so that
                // in the future we can support a "deprecated" list to
                // throw warnings, hopefully the spec uses deprecation phases
                // for the removal of these things
                //
                compatibility = {
                    "0.9": {
                        supported: {
                            actor: true,
                            instructor: true,
                            target: true,
                            object: true,
                            context: true,
                            authoritative: true,
                            sparse: true
                        }
                    },
                    "1.0.0": {
                        supported: {
                            agent: true,
                            activity: true,
                            related_activities: true,
                            related_agents: true,
                            format: true,
                            attachments: true
                        }
                    }
                };

            compatibility["0.95"] = compatibility["0.9"];
            compatibility["1.0.1"] = compatibility["1.0.0"];

            if (cfg.params.hasOwnProperty("target")) {
                cfg.params.object = cfg.params.target;
            }

            //
            // check compatibility tables, either the configured parameter is in
            // the universal list or the specific version, if not then throw an
            // error which at least for .queryStatements will prevent the request
            // and potentially alert the user
            //
            for (prop in cfg.params) {
                if (cfg.params.hasOwnProperty(prop)) {
                    if (typeof universal[prop] === "undefined" && typeof compatibility[this.version].supported[prop] === "undefined") {
                        throw "Unrecognized query parameter configured: " + prop;
                    }
                }
            }

            //
            // getting here means that all parameters are valid for this version
            // to make handling the output formats easier
            //

            for (i = 0; i < jsonProps.length; i += 1) {
                if (typeof cfg.params[jsonProps[i]] !== "undefined") {
                    params[jsonProps[i]] = JSON.stringify(cfg.params[jsonProps[i]].asVersion(this.version));
                }
            }

            for (i = 0; i < idProps.length; i += 1) {
                if (typeof cfg.params[idProps[i]] !== "undefined") {
                    params[idProps[i]] = cfg.params[idProps[i]].id;
                }
            }

            for (i = 0; i < valProps.length; i += 1) {
                if (typeof cfg.params[valProps[i]] !== "undefined") {
                    params[valProps[i]] = cfg.params[valProps[i]];
                }
            }

            return returnCfg;
        },

        /**
        Fetch more statements from a previous query, when used from a browser sends to the endpoint using the
        RESTful interface.  Use a callback to make the call asynchronous.

        @method moreStatements
        @param {Object} [cfg] Configuration used to query
            @param {String} [cfg.url] More URL
            @param {Function} [cfg.callback] Callback to execute on completion
                @param {String|null} cfg.callback.err Error status or null if succcess
                @param {TinCan.StatementsResult|XHR} cfg.callback.response Receives a StatementsResult argument
        @return {Object} Request result
        */
        moreStatements: function (cfg) {
            this.log("moreStatements: " + cfg.url);
            var requestCfg,
                requestResult,
                callbackWrapper,
                parsedURL,
                serverRoot;

            cfg = cfg || {};

            // to support our interface (to support IE) we need to break apart
            // the more URL query params so that the request can be made properly later
            parsedURL = TinCan.Utils.parseURL(cfg.url);

            //Respect a more URL that is relative to either the server root 
            //or endpoint (though only the former is allowed in the spec)
            serverRoot = TinCan.Utils.getServerRoot(this.endpoint);
            if (parsedURL.path.indexOf("/statements") === 0){
                parsedURL.path = this.endpoint.replace(serverRoot, "") + parsedURL.path;
                this.log("converting non-standard more URL to " + parsedURL.path);
            }

            //The more relative URL might not start with a slash, add it if not
            if (parsedURL.path.indexOf("/") !== 0) {
                parsedURL.path = "/" + parsedURL.path;
            }

            requestCfg = {
                method: "GET",
                //For arbitrary more URLs to work, 
                //we need to make the URL absolute here
                url: serverRoot + parsedURL.path,
                params: parsedURL.params
            };
            if (typeof cfg.callback !== "undefined") {
                callbackWrapper = function (err, xhr) {
                    var result = xhr;

                    if (err === null) {
                        result = TinCan.StatementsResult.fromJSON(xhr.responseText);
                    }

                    cfg.callback(err, result);
                };
                requestCfg.callback = callbackWrapper;
            }

            requestResult = this.sendRequest(requestCfg);
            requestResult.config = requestCfg;

            if (! callbackWrapper) {
                requestResult.statementsResult = null;
                if (requestResult.err === null) {
                    requestResult.statementsResult = TinCan.StatementsResult.fromJSON(requestResult.xhr.responseText);
                }
            }

            return requestResult;
        },

        /**
        Retrieve a state value, when used from a browser sends to the endpoint using the RESTful interface.

        @method retrieveState
        @param {String} key Key of state to retrieve
        @param {Object} cfg Configuration options
            @param {Object} cfg.activity TinCan.Activity
            @param {Object} cfg.agent TinCan.Agent
            @param {String} [cfg.registration] Registration
            @param {Function} [cfg.callback] Callback to execute on completion
                @param {Object|Null} cfg.callback.error
                @param {TinCan.State|null} cfg.callback.result null if state is 404
        @return {Object} TinCan.State retrieved when synchronous, or result from sendRequest
        */
        retrieveState: function (key, cfg) {
            this.log("retrieveState");
            var requestParams = {},
                requestCfg = {},
                requestResult,
                callbackWrapper
            ;

            requestParams = {
                stateId: key,
                activityId: cfg.activity.id
            };
            if (this.version === "0.9") {
                requestParams.actor = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            else {
                requestParams.agent = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            if ((typeof cfg.registration !== "undefined") && (cfg.registration !== null)) {
                if (this.version === "0.9") {
                    requestParams.registrationId = cfg.registration;
                }
                else {
                    requestParams.registration = cfg.registration;
                }
            }

            requestCfg = {
                url: "activities/state",
                method: "GET",
                params: requestParams,
                ignore404: true
            };
            if (typeof cfg.callback !== "undefined") {
                callbackWrapper = function (err, xhr) {
                    var result = xhr;

                    if (err === null) {
                        if (xhr.status === 404) {
                            result = null;
                        }
                        else {
                            result = new TinCan.State(
                                {
                                    id: key,
                                    contents: xhr.responseText
                                }
                            );
                            if (typeof xhr.getResponseHeader !== "undefined" && xhr.getResponseHeader("ETag") !== null && xhr.getResponseHeader("ETag") !== "") {
                                result.etag = xhr.getResponseHeader("ETag");
                            } else {
                                //
                                // either XHR didn't have getResponseHeader (probably cause it is an IE
                                // XDomainRequest object which doesn't) or not populated by LRS so create
                                // the hash ourselves
                                //
                                result.etag = TinCan.Utils.getSHA1String(xhr.responseText);
                            }

                            if (typeof xhr.contentType !== "undefined") {
                                // most likely an XDomainRequest which has .contentType,
                                // for the ones that it supports
                                result.contentType = xhr.contentType;
                            } else if (typeof xhr.getResponseHeader !== "undefined" && xhr.getResponseHeader("Content-Type") !== null && xhr.getResponseHeader("Content-Type") !== "") {
                                result.contentType = xhr.getResponseHeader("Content-Type");
                            }

                            if (TinCan.Utils.isApplicationJSON(result.contentType)) {
                                try {
                                    result.contents = JSON.parse(result.contents);
                                } catch (ex) {
                                    this.log("retrieveState - failed to deserialize JSON: " + ex);
                                }
                            }
                        }
                    }

                    cfg.callback(err, result);
                };
                requestCfg.callback = callbackWrapper;
            }

            requestResult = this.sendRequest(requestCfg);
            if (! callbackWrapper) {
                requestResult.state = null;
                if (requestResult.err === null && requestResult.xhr.status !== 404) {
                    requestResult.state = new TinCan.State(
                        {
                            id: key,
                            contents: requestResult.xhr.responseText
                        }
                    );
                    if (typeof requestResult.xhr.getResponseHeader !== "undefined" && requestResult.xhr.getResponseHeader("ETag") !== null && requestResult.xhr.getResponseHeader("ETag") !== "") {
                        requestResult.state.etag = requestResult.xhr.getResponseHeader("ETag");
                    } else {
                        //
                        // either XHR didn't have getResponseHeader (probably cause it is an IE
                        // XDomainRequest object which doesn't) or not populated by LRS so create
                        // the hash ourselves
                        //
                        requestResult.state.etag = TinCan.Utils.getSHA1String(requestResult.xhr.responseText);
                    }
                    if (typeof requestResult.xhr.contentType !== "undefined") {
                        // most likely an XDomainRequest which has .contentType
                        // for the ones that it supports
                        requestResult.state.contentType = requestResult.xhr.contentType;
                    } else if (typeof requestResult.xhr.getResponseHeader !== "undefined" && requestResult.xhr.getResponseHeader("Content-Type") !== null && requestResult.xhr.getResponseHeader("Content-Type") !== "") {
                        requestResult.state.contentType = requestResult.xhr.getResponseHeader("Content-Type");
                    }
                    if (TinCan.Utils.isApplicationJSON(requestResult.state.contentType)) {
                        try {
                            requestResult.state.contents = JSON.parse(requestResult.state.contents);
                        } catch (ex) {
                            this.log("retrieveState - failed to deserialize JSON: " + ex);
                        }
                    }
                }
            }

            return requestResult;
        },

        /**
        Save a state value, when used from a browser sends to the endpoint using the RESTful interface.

        @method saveState
        @param {String} key Key of state to save
        @param val Value to be stored
        @param {Object} cfg Configuration options
            @param {Object} cfg.activity TinCan.Activity
            @param {Object} cfg.agent TinCan.Agent
            @param {String} [cfg.registration] Registration
            @param {String} [cfg.lastSHA1] SHA1 of the previously seen existing state
            @param {String} [cfg.contentType] Content-Type to specify in headers (defaults to 'application/octet-stream')
            @param {String} [cfg.method] Method to use. Default: PUT
            @param {Function} [cfg.callback] Callback to execute on completion
        */
        saveState: function (key, val, cfg) {
            this.log("saveState");
            var requestParams,
                requestCfg;

            if (typeof cfg.contentType === "undefined") {
                cfg.contentType = "application/octet-stream";
            }

            if (typeof val === "object" && TinCan.Utils.isApplicationJSON(cfg.contentType)) {
                val = JSON.stringify(val);
            }

            if (typeof cfg.method === "undefined" || cfg.method !== "POST") {
                cfg.method = "PUT";
            }

            requestParams = {
                stateId: key,
                activityId: cfg.activity.id
            };
            if (this.version === "0.9") {
                requestParams.actor = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            else {
                requestParams.agent = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            if ((typeof cfg.registration !== "undefined") && (cfg.registration !== null)) {
                if (this.version === "0.9") {
                    requestParams.registrationId = cfg.registration;
                }
                else {
                    requestParams.registration = cfg.registration;
                }
            }

            requestCfg = {
                url: "activities/state",
                method: cfg.method,
                params: requestParams,
                data: val,
                headers: {
                    "Content-Type": cfg.contentType
                }
            };
            if (typeof cfg.callback !== "undefined") {
                requestCfg.callback = cfg.callback;
            }
            if (typeof cfg.lastSHA1 !== "undefined" && cfg.lastSHA1 !== null) {
                requestCfg.headers["If-Match"] = cfg.lastSHA1;
            }

            return this.sendRequest(requestCfg);
        },

        /**
        Drop a state value or all of the state, when used from a browser sends to the endpoint using the RESTful interface.

        @method dropState
        @param {String|null} key Key of state to delete, or null for all
        @param {Object} cfg Configuration options
            @param {Object} [cfg.activity] TinCan.Activity
            @param {Object} [cfg.agent] TinCan.Agent
            @param {String} [cfg.registration] Registration
            @param {Function} [cfg.callback] Callback to execute on completion
        */
        dropState: function (key, cfg) {
            this.log("dropState");
            var requestParams,
                requestCfg
            ;

            requestParams = {
                activityId: cfg.activity.id
            };
            if (this.version === "0.9") {
                requestParams.actor = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            else {
                requestParams.agent = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            if (key !== null) {
                requestParams.stateId = key;
            }
            if ((typeof cfg.registration !== "undefined") && (cfg.registration !== null)) {
                if (this.version === "0.9") {
                    requestParams.registrationId = cfg.registration;
                }
                else {
                    requestParams.registration = cfg.registration;
                }
            }

            requestCfg = {
                url: "activities/state",
                method: "DELETE",
                params: requestParams
            };
            if (typeof cfg.callback !== "undefined") {
                requestCfg.callback = cfg.callback;
            }

            return this.sendRequest(requestCfg);
        },

        /**
        Retrieve an activity profile value, when used from a browser sends to the endpoint using the RESTful interface.

        @method retrieveActivityProfile
        @param {String} key Key of activity profile to retrieve
        @param {Object} cfg Configuration options
            @param {Object} cfg.activity TinCan.Activity
            @param {Function} [cfg.callback] Callback to execute on completion
        @return {Object} Value retrieved
        */
        retrieveActivityProfile: function (key, cfg) {
            this.log("retrieveActivityProfile");
            var requestCfg = {},
                requestResult,
                callbackWrapper
            ;

            requestCfg = {
                url: "activities/profile",
                method: "GET",
                params: {
                    profileId: key,
                    activityId: cfg.activity.id
                },
                ignore404: true
            };
            if (typeof cfg.callback !== "undefined") {
                callbackWrapper = function (err, xhr) {
                    var result = xhr;

                    if (err === null) {
                        if (xhr.status === 404) {
                            result = null;
                        }
                        else {
                            result = new TinCan.ActivityProfile(
                                {
                                    id: key,
                                    activity: cfg.activity,
                                    contents: xhr.responseText
                                }
                            );
                            if (typeof xhr.getResponseHeader !== "undefined" && xhr.getResponseHeader("ETag") !== null && xhr.getResponseHeader("ETag") !== "") {
                                result.etag = xhr.getResponseHeader("ETag");
                            } else {
                                //
                                // either XHR didn't have getResponseHeader (probably cause it is an IE
                                // XDomainRequest object which doesn't) or not populated by LRS so create
                                // the hash ourselves
                                //
                                result.etag = TinCan.Utils.getSHA1String(xhr.responseText);
                            }
                            if (typeof xhr.contentType !== "undefined") {
                                // most likely an XDomainRequest which has .contentType
                                // for the ones that it supports
                                result.contentType = xhr.contentType;
                            } else if (typeof xhr.getResponseHeader !== "undefined" && xhr.getResponseHeader("Content-Type") !== null && xhr.getResponseHeader("Content-Type") !== "") {
                                result.contentType = xhr.getResponseHeader("Content-Type");
                            }
                            if (TinCan.Utils.isApplicationJSON(result.contentType)) {
                                try {
                                    result.contents = JSON.parse(result.contents);
                                } catch (ex) {
                                    this.log("retrieveActivityProfile - failed to deserialize JSON: " + ex);
                                }
                            }
                        }
                    }

                    cfg.callback(err, result);
                };
                requestCfg.callback = callbackWrapper;
            }

            requestResult = this.sendRequest(requestCfg);
            if (! callbackWrapper) {
                requestResult.profile = null;
                if (requestResult.err === null && requestResult.xhr.status !== 404) {
                    requestResult.profile = new TinCan.ActivityProfile(
                        {
                            id: key,
                            activity: cfg.activity,
                            contents: requestResult.xhr.responseText
                        }
                    );
                    if (typeof requestResult.xhr.getResponseHeader !== "undefined" && requestResult.xhr.getResponseHeader("ETag") !== null && requestResult.xhr.getResponseHeader("ETag") !== "") {
                        requestResult.profile.etag = requestResult.xhr.getResponseHeader("ETag");
                    } else {
                        //
                        // either XHR didn't have getResponseHeader (probably cause it is an IE
                        // XDomainRequest object which doesn't) or not populated by LRS so create
                        // the hash ourselves
                        //
                        requestResult.profile.etag = TinCan.Utils.getSHA1String(requestResult.xhr.responseText);
                    }
                    if (typeof requestResult.xhr.contentType !== "undefined") {
                        // most likely an XDomainRequest which has .contentType
                        // for the ones that it supports
                        requestResult.profile.contentType = requestResult.xhr.contentType;
                    } else if (typeof requestResult.xhr.getResponseHeader !== "undefined" && requestResult.xhr.getResponseHeader("Content-Type") !== null && requestResult.xhr.getResponseHeader("Content-Type") !== "") {
                        requestResult.profile.contentType = requestResult.xhr.getResponseHeader("Content-Type");
                    }
                    if (TinCan.Utils.isApplicationJSON(requestResult.profile.contentType)) {
                        try {
                            requestResult.profile.contents = JSON.parse(requestResult.profile.contents);
                        } catch (ex) {
                            this.log("retrieveActivityProfile - failed to deserialize JSON: " + ex);
                        }
                    }
                }
            }

            return requestResult;
        },

        /**
        Save an activity profile value, when used from a browser sends to the endpoint using the RESTful interface.

        @method saveActivityProfile
        @param {String} key Key of activity profile to retrieve
        @param val Value to be stored
        @param {Object} cfg Configuration options
            @param {Object} cfg.activity TinCan.Activity
            @param {String} [cfg.lastSHA1] SHA1 of the previously seen existing profile
            @param {String} [cfg.contentType] Content-Type to specify in headers (defaults to 'application/octet-stream')
            @param {String} [cfg.method] Method to use. Default: PUT
            @param {Function} [cfg.callback] Callback to execute on completion
        */
        saveActivityProfile: function (key, val, cfg) {
            this.log("saveActivityProfile");
            var requestCfg;

            if (typeof cfg.contentType === "undefined") {
                cfg.contentType = "application/octet-stream";
            }

            if (typeof cfg.method === "undefined" || cfg.method !== "POST") {
                cfg.method = "PUT";
            }

            if (typeof val === "object" && TinCan.Utils.isApplicationJSON(cfg.contentType)) {
                val = JSON.stringify(val);
            }

            requestCfg = {
                url: "activities/profile",
                method: cfg.method,
                params: {
                    profileId: key,
                    activityId: cfg.activity.id
                },
                data: val,
                headers: {
                    "Content-Type": cfg.contentType
                }
            };
            if (typeof cfg.callback !== "undefined") {
                requestCfg.callback = cfg.callback;
            }
            if (typeof cfg.lastSHA1 !== "undefined" && cfg.lastSHA1 !== null) {
                requestCfg.headers["If-Match"] = cfg.lastSHA1;
            }
            else {
                requestCfg.headers["If-None-Match"] = "*";
            }

            return this.sendRequest(requestCfg);
        },

        /**
        Drop an activity profile value or all of the activity profile, when used from a browser sends to the endpoint using the RESTful interface.

        @method dropActivityProfile
        @param {String|null} key Key of activity profile to delete, or null for all
        @param {Object} cfg Configuration options
            @param {Object} cfg.activity TinCan.Activity
            @param {Function} [cfg.callback] Callback to execute on completion
        */
        dropActivityProfile: function (key, cfg) {
            this.log("dropActivityProfile");
            var requestParams,
                requestCfg
            ;

            requestParams = {
                profileId: key,
                activityId: cfg.activity.id
            };

            requestCfg = {
                url: "activities/profile",
                method: "DELETE",
                params: requestParams
            };
            if (typeof cfg.callback !== "undefined") {
                requestCfg.callback = cfg.callback;
            }

            return this.sendRequest(requestCfg);
        },

        /**
        Retrieve an agent profile value, when used from a browser sends to the endpoint using the RESTful interface.

        @method retrieveAgentProfile
        @param {String} key Key of agent profile to retrieve
        @param {Object} cfg Configuration options
            @param {Object} cfg.agent TinCan.Agent
            @param {Function} [cfg.callback] Callback to execute on completion
        @return {Object} Value retrieved
        */
        retrieveAgentProfile: function (key, cfg) {
            this.log("retrieveAgentProfile");
            var requestCfg = {},
                requestResult,
                callbackWrapper
            ;

            requestCfg = {
                method: "GET",
                params: {
                    profileId: key
                },
                ignore404: true
            };
            if (this.version === "0.9") {
                requestCfg.url = "actors/profile";
                requestCfg.params.actor = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            else {
                requestCfg.url = "agents/profile";
                requestCfg.params.agent = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            if (typeof cfg.callback !== "undefined") {
                callbackWrapper = function (err, xhr) {
                    var result = xhr;

                    if (err === null) {
                        if (xhr.status === 404) {
                            result = null;
                        }
                        else {
                            result = new TinCan.AgentProfile(
                                {
                                    id: key,
                                    agent: cfg.agent,
                                    contents: xhr.responseText
                                }
                            );
                            if (typeof xhr.getResponseHeader !== "undefined" && xhr.getResponseHeader("ETag") !== null && xhr.getResponseHeader("ETag") !== "") {
                                result.etag = xhr.getResponseHeader("ETag");
                            } else {
                                //
                                // either XHR didn't have getResponseHeader (probably cause it is an IE
                                // XDomainRequest object which doesn't) or not populated by LRS so create
                                // the hash ourselves
                                //
                                result.etag = TinCan.Utils.getSHA1String(xhr.responseText);
                            }
                            if (typeof xhr.contentType !== "undefined") {
                                // most likely an XDomainRequest which has .contentType
                                // for the ones that it supports
                                result.contentType = xhr.contentType;
                            } else if (typeof xhr.getResponseHeader !== "undefined" && xhr.getResponseHeader("Content-Type") !== null && xhr.getResponseHeader("Content-Type") !== "") {
                                result.contentType = xhr.getResponseHeader("Content-Type");
                            }
                            if (TinCan.Utils.isApplicationJSON(result.contentType)) {
                                try {
                                    result.contents = JSON.parse(result.contents);
                                } catch (ex) {
                                    this.log("retrieveAgentProfile - failed to deserialize JSON: " + ex);
                                }
                            }
                        }
                    }

                    cfg.callback(err, result);
                };
                requestCfg.callback = callbackWrapper;
            }

            requestResult = this.sendRequest(requestCfg);
            if (! callbackWrapper) {
                requestResult.profile = null;
                if (requestResult.err === null && requestResult.xhr.status !== 404) {
                    requestResult.profile = new TinCan.AgentProfile(
                        {
                            id: key,
                            agent: cfg.agent,
                            contents: requestResult.xhr.responseText
                        }
                    );
                    if (typeof requestResult.xhr.getResponseHeader !== "undefined" && requestResult.xhr.getResponseHeader("ETag") !== null && requestResult.xhr.getResponseHeader("ETag") !== "") {
                        requestResult.profile.etag = requestResult.xhr.getResponseHeader("ETag");
                    } else {
                        //
                        // either XHR didn't have getResponseHeader (probably cause it is an IE
                        // XDomainRequest object which doesn't) or not populated by LRS so create
                        // the hash ourselves
                        //
                        requestResult.profile.etag = TinCan.Utils.getSHA1String(requestResult.xhr.responseText);
                    }
                    if (typeof requestResult.xhr.contentType !== "undefined") {
                        // most likely an XDomainRequest which has .contentType
                        // for the ones that it supports
                        requestResult.profile.contentType = requestResult.xhr.contentType;
                    } else if (typeof requestResult.xhr.getResponseHeader !== "undefined" && requestResult.xhr.getResponseHeader("Content-Type") !== null && requestResult.xhr.getResponseHeader("Content-Type") !== "") {
                        requestResult.profile.contentType = requestResult.xhr.getResponseHeader("Content-Type");
                    }
                    if (TinCan.Utils.isApplicationJSON(requestResult.profile.contentType)) {
                        try {
                            requestResult.profile.contents = JSON.parse(requestResult.profile.contents);
                        } catch (ex) {
                            this.log("retrieveAgentProfile - failed to deserialize JSON: " + ex);
                        }
                    }
                }
            }

            return requestResult;
        },

        /**
        Save an agent profile value, when used from a browser sends to the endpoint using the RESTful interface.

        @method saveAgentProfile
        @param {String} key Key of agent profile to retrieve
        @param val Value to be stored
        @param {Object} cfg Configuration options
            @param {Object} cfg.agent TinCan.Agent
            @param {String} [cfg.lastSHA1] SHA1 of the previously seen existing profile
            @param {String} [cfg.contentType] Content-Type to specify in headers (defaults to 'application/octet-stream')
            @param {String} [cfg.method] Method to use. Default: PUT
            @param {Function} [cfg.callback] Callback to execute on completion
        */
        saveAgentProfile: function (key, val, cfg) {
            this.log("saveAgentProfile");
            var requestCfg;

            if (typeof cfg.contentType === "undefined") {
                cfg.contentType = "application/octet-stream";
            }

            if (typeof cfg.method === "undefined" || cfg.method !== "POST") {
                cfg.method = "PUT";
            }

            if (typeof val === "object" && TinCan.Utils.isApplicationJSON(cfg.contentType)) {
                val = JSON.stringify(val);
            }

            requestCfg = {
                method: cfg.method,
                params: {
                    profileId: key
                },
                data: val,
                headers: {
                    "Content-Type": cfg.contentType
                }
            };
            if (this.version === "0.9") {
                requestCfg.url = "actors/profile";
                requestCfg.params.actor = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            else {
                requestCfg.url = "agents/profile";
                requestCfg.params.agent = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            if (typeof cfg.callback !== "undefined") {
                requestCfg.callback = cfg.callback;
            }
            if (typeof cfg.lastSHA1 !== "undefined" && cfg.lastSHA1 !== null) {
                requestCfg.headers["If-Match"] = cfg.lastSHA1;
            }
            else {
                requestCfg.headers["If-None-Match"] = "*";
            }

            return this.sendRequest(requestCfg);
        },

        /**
        Drop an agent profile value or all of the agent profile, when used from a browser sends to the endpoint using the RESTful interface.

        @method dropAgentProfile
        @param {String|null} key Key of agent profile to delete, or null for all
        @param {Object} cfg Configuration options
            @param {Object} cfg.agent TinCan.Agent
            @param {Function} [cfg.callback] Callback to execute on completion
        */
        dropAgentProfile: function (key, cfg) {
            this.log("dropAgentProfile");
            var requestParams,
                requestCfg
            ;

            requestParams = {
                profileId: key
            };
            requestCfg = {
                method: "DELETE",
                params: requestParams
            };
            if (this.version === "0.9") {
                requestCfg.url = "actors/profile";
                requestParams.actor = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            else {
                requestCfg.url = "agents/profile";
                requestParams.agent = JSON.stringify(cfg.agent.asVersion(this.version));
            }
            if (typeof cfg.callback !== "undefined") {
                requestCfg.callback = cfg.callback;
            }

            return this.sendRequest(requestCfg);
        }
    };

    /**
    Allows client code to determine whether their environment supports synchronous xhr handling
    @static this is a static property, set by the environment
    */
    LRS.syncEnabled = null;
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.AgentAccount
**/
(function () {
    "use strict";

    /**
    @class TinCan.AgentAccount
    @constructor
    */
    var AgentAccount = TinCan.AgentAccount = function (cfg) {
        this.log("constructor");

        /**
        @property homePage
        @type String
        */
        this.homePage = null;

        /**
        @property name
        @type String
        */
        this.name = null;

        this.init(cfg);
    };
    AgentAccount.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "AgentAccount",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "name",
                    "homePage"
                ];

            cfg = cfg || {};

            // handle .9 name changes
            if (typeof cfg.accountServiceHomePage !== "undefined") {
                cfg.homePage = cfg.accountServiceHomePage;
            }
            if (typeof cfg.accountName !== "undefined") {
                cfg.name = cfg.accountName;
            }

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }
        },

        toString: function () {
            this.log("toString");
            var result = "";

            if (this.name !== null || this.homePage !== null) {
                result += this.name !== null ? this.name : "-";
                result += ":";
                result += this.homePage !== null ? this.homePage : "-";
            }
            else {
                result = "AgentAccount: unidentified";
            }

            return result;
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion: " + version);
            var result = {};

            version = version || TinCan.versions()[0];

            if (version === "0.9") {
                result.accountName = this.name;
                result.accountServiceHomePage = this.homePage;
            } else {
                result.name = this.name;
                result.homePage = this.homePage;
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} AgentAccount
    @static
    */
    AgentAccount.fromJSON = function (acctJSON) {
        AgentAccount.prototype.log("fromJSON");
        var _acct = JSON.parse(acctJSON);

        return new AgentAccount(_acct);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Agent
**/
(function () {
    "use strict";

    /**
    @class TinCan.Agent
    @constructor
    */
    var Agent = TinCan.Agent = function (cfg) {
        this.log("constructor");

        /**
        @property name
        @type String
        */
        this.name = null;

        /**
        @property mbox
        @type String
        */
        this.mbox = null;

        /**
        @property mbox_sha1sum
        @type String
        */
        this.mbox_sha1sum = null;

        /**
        @property openid
        @type String
        */
        this.openid = null;

        /**
        @property account
        @type TinCan.AgentAccount
        */
        this.account = null;

        /**
        @property degraded
        @type Boolean
        @default false
        */
        this.degraded = false;

        this.init(cfg);
    };
    Agent.prototype = {
        /**
        @property objectType
        @type String
        @default Agent
        */
        objectType: "Agent",

        /**
        @property LOG_SRC
        */
        LOG_SRC: "Agent",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "name",
                    "mbox",
                    "mbox_sha1sum",
                    "openid"
                ],
                val
            ;

            cfg = cfg || {};

            // handle .9 split names and array properties into single interface
            if (typeof cfg.lastName !== "undefined" || typeof cfg.firstName !== "undefined") {
                cfg.name = "";
                if (typeof cfg.firstName !== "undefined" && cfg.firstName.length > 0) {
                    cfg.name = cfg.firstName[0];
                    if (cfg.firstName.length > 1) {
                        this.degraded = true;
                    }
                }

                if (cfg.name !== "") {
                    cfg.name += " ";
                }

                if (typeof cfg.lastName !== "undefined" && cfg.lastName.length > 0) {
                    cfg.name += cfg.lastName[0];
                    if (cfg.lastName.length > 1) {
                        this.degraded = true;
                    }
                }
            } else if (typeof cfg.familyName !== "undefined" || typeof cfg.givenName !== "undefined") {
                cfg.name = "";
                if (typeof cfg.givenName !== "undefined" && cfg.givenName.length > 0) {
                    cfg.name = cfg.givenName[0];
                    if (cfg.givenName.length > 1) {
                        this.degraded = true;
                    }
                }

                if (cfg.name !== "") {
                    cfg.name += " ";
                }

                if (typeof cfg.familyName !== "undefined" && cfg.familyName.length > 0) {
                    cfg.name += cfg.familyName[0];
                    if (cfg.familyName.length > 1) {
                        this.degraded = true;
                    }
                }
            }

            if (typeof cfg.name === "object" && cfg.name !== null) {
                if (cfg.name.length > 1) {
                    this.degraded = true;
                }
                cfg.name = cfg.name[0];
            }
            if (typeof cfg.mbox === "object" && cfg.mbox !== null) {
                if (cfg.mbox.length > 1) {
                    this.degraded = true;
                }
                cfg.mbox = cfg.mbox[0];
            }
            if (typeof cfg.mbox_sha1sum === "object" && cfg.mbox_sha1sum !== null) {
                if (cfg.mbox_sha1sum.length > 1) {
                    this.degraded = true;
                }
                cfg.mbox_sha1sum = cfg.mbox_sha1sum[0];
            }
            if (typeof cfg.openid === "object" && cfg.openid !== null) {
                if (cfg.openid.length > 1) {
                    this.degraded = true;
                }
                cfg.openid = cfg.openid[0];
            }
            if (typeof cfg.account === "object" && cfg.account !== null && typeof cfg.account.homePage === "undefined" && typeof cfg.account.name === "undefined") {
                if (cfg.account.length === 0) {
                    delete cfg.account;
                }
                else {
                    if (cfg.account.length > 1) {
                        this.degraded = true;
                    }
                    cfg.account = cfg.account[0];
                }
            }

            if (cfg.hasOwnProperty("account")) {
                if (cfg.account instanceof TinCan.AgentAccount) {
                    this.account = cfg.account;
                }
                else {
                    this.account = new TinCan.AgentAccount (cfg.account);
                }
            }

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    val = cfg[directProps[i]];
                    if (directProps[i] === "mbox" && val.indexOf("mailto:") === -1) {
                        val = "mailto:" + val;
                    }
                    this[directProps[i]] = val;
                }
            }
        },

        toString: function () {
            this.log("toString");

            if (this.name !== null) {
                return this.name;
            }
            if (this.mbox !== null) {
                return this.mbox.replace("mailto:", "");
            }
            if (this.mbox_sha1sum !== null) {
                return this.mbox_sha1sum;
            }
            if (this.openid !== null) {
                return this.openid;
            }
            if (this.account !== null) {
                return this.account.toString();
            }

            return this.objectType + ": unidentified";
        },

        /**
        While a TinCan.Agent instance can store more than one inverse functional identifier
        this method will always only output one to be compliant with the statement sending
        specification. Order of preference is: mbox, mbox_sha1sum, openid, account

        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion: " + version);
            var result = {
                objectType: this.objectType
            };

            version = version || TinCan.versions()[0];

            if (version === "0.9") {
                if (this.mbox !== null) {
                    result.mbox = [ this.mbox ];
                }
                else if (this.mbox_sha1sum !== null) {
                    result.mbox_sha1sum = [ this.mbox_sha1sum ];
                }
                else if (this.openid !== null) {
                    result.openid = [ this.openid ];
                }
                else if (this.account !== null) {
                    result.account = [ this.account.asVersion(version) ];
                }

                if (this.name !== null) {
                    result.name = [ this.name ];
                }
            } else {
                if (this.mbox !== null) {
                    result.mbox = this.mbox;
                }
                else if (this.mbox_sha1sum !== null) {
                    result.mbox_sha1sum = this.mbox_sha1sum;
                }
                else if (this.openid !== null) {
                    result.openid = this.openid;
                }
                else if (this.account !== null) {
                    result.account = this.account.asVersion(version);
                }

                if (this.name !== null) {
                    result.name = this.name;
                }
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} Agent
    @static
    */
    Agent.fromJSON = function (agentJSON) {
        Agent.prototype.log("fromJSON");
        var _agent = JSON.parse(agentJSON);

        return new Agent(_agent);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Group
**/
(function () {
    "use strict";

    /**
    @class TinCan.Group
    @constructor
    */
    var Group = TinCan.Group = function (cfg) {
        this.log("constructor");

        /**
        @property name
        @type String
        */
        this.name = null;

        /**
        @property mbox
        @type String
        */
        this.mbox = null;

        /**
        @property mbox_sha1sum
        @type String
        */
        this.mbox_sha1sum = null;

        /**
        @property openid
        @type String
        */
        this.openid = null;

        /**
        @property account
        @type TinCan.AgentAccount
        */
        this.account = null;

        /**
        @property member
        @type Array
        */
        this.member = [];

        this.init(cfg);
    };
    Group.prototype = {
        /**
        @property objectType
        @type String
        @default "Group"
        @static
        */
        objectType: "Group",

        /**
        @property LOG_SRC
        */
        LOG_SRC: "Group",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");
            var i;

            cfg = cfg || {};

            TinCan.Agent.prototype.init.call(this, cfg);

            if (typeof cfg.member !== "undefined") {
                for (i = 0; i < cfg.member.length; i += 1) {
                    if (cfg.member[i] instanceof TinCan.Agent) {
                        this.member.push(cfg.member[i]);
                    }
                    else {
                        this.member.push(new TinCan.Agent (cfg.member[i]));
                    }
                }
            }
        },

        toString: function (lang) {
            this.log("toString");

            var result = TinCan.Agent.prototype.toString.call(this, lang);
            if (result !== this.objectType + ": unidentified") {
                result = this.objectType + ": " + result;
            }

            return result;
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion: " + version);
            var result,
                i
            ;

            version = version || TinCan.versions()[0];

            result = TinCan.Agent.prototype.asVersion.call(this, version);

            if (this.member.length > 0) {
                result.member = [];
                for (i = 0; i < this.member.length; i += 1) {
                    result.member.push(this.member[i].asVersion(version));
                }
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} Group
    @static
    */
    Group.fromJSON = function (groupJSON) {
        Group.prototype.log("fromJSON");
        var _group = JSON.parse(groupJSON);

        return new Group(_group);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Verb
*/
(function () {
    "use strict";

    //
    // this represents the full set of verb values that were
    // allowed by the .9 spec version, if an object is created with one of
    // the short forms it will be upconverted to the matching long form,
    // for local storage and use and if an object is needed in .9 version
    // consequently down converted
    //
    // hopefully this list will never grow (or change) and only the exact
    // ADL compatible URLs should be matched
    //
    var _downConvertMap = {
        "http://adlnet.gov/expapi/verbs/experienced": "experienced",
        "http://adlnet.gov/expapi/verbs/attended":    "attended",
        "http://adlnet.gov/expapi/verbs/attempted":   "attempted",
        "http://adlnet.gov/expapi/verbs/completed":   "completed",
        "http://adlnet.gov/expapi/verbs/passed":      "passed",
        "http://adlnet.gov/expapi/verbs/failed":      "failed",
        "http://adlnet.gov/expapi/verbs/answered":    "answered",
        "http://adlnet.gov/expapi/verbs/interacted":  "interacted",
        "http://adlnet.gov/expapi/verbs/imported":    "imported",
        "http://adlnet.gov/expapi/verbs/created":     "created",
        "http://adlnet.gov/expapi/verbs/shared":      "shared",
        "http://adlnet.gov/expapi/verbs/voided":      "voided"
    },

    /**
    @class TinCan.Verb
    @constructor
    */
    Verb = TinCan.Verb = function (cfg) {
        this.log("constructor");

        /**
        @property id
        @type String
        */
        this.id = null;

        /**
        @property display
        @type Object
        */
        this.display = null;

        this.init(cfg);
    };
    Verb.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "Verb",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "id",
                    "display"
                ],
                prop
            ;

            if (typeof cfg === "string") {
                this.id = cfg;
                this.display = {
                    und: this.id
                };

                //If simple string like "attempted" was passed in (0.9 verbs), 
                //upconvert the ID to the 0.95 ADL version
                for (prop in _downConvertMap) {
                    if (_downConvertMap.hasOwnProperty(prop) && _downConvertMap[prop] === cfg) {
                        this.id = prop;
                        break;
                    }
                }
            }
            else {
                cfg = cfg || {};

                for (i = 0; i < directProps.length; i += 1) {
                    if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                        this[directProps[i]] = cfg[directProps[i]];
                    }
                }

                if (this.display === null && typeof _downConvertMap[this.id] !== "undefined") {
                    this.display = {
                        "und": _downConvertMap[this.id]
                    };
                }
            }
        },

        /**
        @method toString
        @return {String} String representation of the verb
        */
        toString: function (lang) {
            this.log("toString");

            if (this.display !== null) {
                return this.getLangDictionaryValue("display", lang);
            }

            return this.id;
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result;

            version = version || TinCan.versions()[0];

            if (version === "0.9") {
                result = _downConvertMap[this.id];
            }
            else {
                result = {
                    id: this.id
                };
                if (this.display !== null) {
                    result.display = this.display;
                }
            }

            return result;
        },

        /**
        See {{#crossLink "TinCan.Utils/getLangDictionaryValue"}}{{/crossLink}}

        @method getLangDictionaryValue
        */
        getLangDictionaryValue: TinCan.Utils.getLangDictionaryValue
    };

    /**
    @method fromJSON
    @param {String} verbJSON String of JSON representing the verb
    @return {Object} Verb
    @static
    */
    Verb.fromJSON = function (verbJSON) {
        Verb.prototype.log("fromJSON");
        var _verb = JSON.parse(verbJSON);

        return new Verb(_verb);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Result
**/
(function () {
    "use strict";

    /**
    @class TinCan.Result
    @constructor
    */
    var Result = TinCan.Result = function (cfg) {
        this.log("constructor");

        /**
        @property score
        @type TinCan.Score|null
        */
        this.score = null;

        /**
        @property success
        @type Boolean|null
        */
        this.success = null;

        /**
        @property completion
        @type Boolean|null
        */
        this.completion = null;

        /**
        @property duration
        @type String|null
        */
        this.duration = null;

        /**
        @property response
        @type String|null
        */
        this.response = null;

        /**
        @property extensions
        @type Object|null
        */
        this.extensions = null;

        this.init(cfg);
    };
    Result.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "Result",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");

            var i,
                directProps = [
                    "completion",
                    "duration",
                    "extensions",
                    "response",
                    "success"
                ]
            ;

            cfg = cfg || {};

            if (cfg.hasOwnProperty("score") && cfg.score !== null) {
                if (cfg.score instanceof TinCan.Score) {
                    this.score = cfg.score;
                }
                else {
                    this.score = new TinCan.Score (cfg.score);
                }
            }

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }

            // 0.9 used a string, store it internally as a bool
            if (this.completion === "Completed") {
                this.completion = true;
            }
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result = {},
                optionalDirectProps = [
                    "success",
                    "duration",
                    "response",
                    "extensions"
                ],
                optionalObjProps = [
                    "score"
                ],
                i;

            version = version || TinCan.versions()[0];

            for (i = 0; i < optionalDirectProps.length; i += 1) {
                if (this[optionalDirectProps[i]] !== null) {
                    result[optionalDirectProps[i]] = this[optionalDirectProps[i]];
                }
            }
            for (i = 0; i < optionalObjProps.length; i += 1) {
                if (this[optionalObjProps[i]] !== null) {
                    result[optionalObjProps[i]] = this[optionalObjProps[i]].asVersion(version);
                }
            }
            if (this.completion !== null) {
                if (version === "0.9") {
                    if (this.completion) {
                        result.completion = "Completed";
                    }
                }
                else {
                    result.completion = this.completion;
                }
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} Result
    @static
    */
    Result.fromJSON = function (resultJSON) {
        Result.prototype.log("fromJSON");
        var _result = JSON.parse(resultJSON);

        return new Result(_result);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Score
**/
(function () {
    "use strict";

    /**
    @class TinCan.Score
    @constructor
    */
    var Score = TinCan.Score = function (cfg) {
        this.log("constructor");

        /**
        @property scaled
        @type String
        */
        this.scaled = null;

        /**
        @property raw
        @type String
        */
        this.raw = null;

        /**
        @property min
        @type String
        */
        this.min = null;

        /**
        @property max
        @type String
        */
        this.max = null;

        this.init(cfg);
    };
    Score.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "Score",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");

            var i,
                directProps = [
                    "scaled",
                    "raw",
                    "min",
                    "max"
                ]
            ;

            cfg = cfg || {};

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result = {},
                optionalDirectProps = [
                    "scaled",
                    "raw",
                    "min",
                    "max"
                ],
                i;

            version = version || TinCan.versions()[0];

            for (i = 0; i < optionalDirectProps.length; i += 1) {
                if (this[optionalDirectProps[i]] !== null) {
                    result[optionalDirectProps[i]] = this[optionalDirectProps[i]];
                }
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} Score
    @static
    */
    Score.fromJSON = function (scoreJSON) {
        Score.prototype.log("fromJSON");
        var _score = JSON.parse(scoreJSON);

        return new Score(_score);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.InteractionComponent
**/
(function () {
    "use strict";

    /**
    @class TinCan.InteractionComponent
    @constructor
    */
    var InteractionComponent = TinCan.InteractionComponent = function (cfg) {
        this.log("constructor");

        /**
        @property id
        @type String
        */
        this.id = null;

        /**
        @property description
        @type Object
        */
        this.description = null;

        this.init(cfg);
    };
    InteractionComponent.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "InteractionComponent",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "id",
                    "description"
                ]
            ;

            cfg = cfg || {};

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result = {
                    id: this.id
                },
                optionalDirectProps = [
                    "description"
                ],
                i,
                prop;

            version = version || TinCan.versions()[0];

            for (i = 0; i < optionalDirectProps.length; i += 1) {
                prop = optionalDirectProps[i];
                if (this[prop] !== null) {
                    result[prop] = this[prop];
                }
            }

            return result;
        },

        /**
        See {{#crossLink "TinCan.Utils/getLangDictionaryValue"}}{{/crossLink}}

        @method getLangDictionaryValue
        */
        getLangDictionaryValue: TinCan.Utils.getLangDictionaryValue
    };

    /**
    @method fromJSON
    @return {Object} InteractionComponent
    @static
    */
    InteractionComponent.fromJSON = function (icJSON) {
        InteractionComponent.prototype.log("fromJSON");
        var _ic = JSON.parse(icJSON);

        return new InteractionComponent(_ic);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.ActivityDefinition
**/
(function () {
    "use strict";

    //
    // this represents the full set of activity definition types that were
    // allowed by the .9 spec version, if an object is created with one of
    // the short forms it will be upconverted to the matching long form,
    // for local storage and use and if an object is needed in .9 version
    // consequently down converted
    //
    // hopefully this list will never grow (or change) and only the exact
    // ADL compatible URLs should be matched
    //
    var _downConvertMap = {
        "http://adlnet.gov/expapi/activities/course": "course",
        "http://adlnet.gov/expapi/activities/module": "module",
        "http://adlnet.gov/expapi/activities/meeting": "meeting",
        "http://adlnet.gov/expapi/activities/media": "media",
        "http://adlnet.gov/expapi/activities/performance": "performance",
        "http://adlnet.gov/expapi/activities/simulation": "simulation",
        "http://adlnet.gov/expapi/activities/assessment": "assessment",
        "http://adlnet.gov/expapi/activities/interaction": "interaction",
        "http://adlnet.gov/expapi/activities/cmi.interaction": "cmi.interaction",
        "http://adlnet.gov/expapi/activities/question": "question",
        "http://adlnet.gov/expapi/activities/objective": "objective",
        "http://adlnet.gov/expapi/activities/link": "link"
    },

    /**
    @class TinCan.ActivityDefinition
    @constructor
    */
    ActivityDefinition = TinCan.ActivityDefinition = function (cfg) {
        this.log("constructor");

        /**
        @property name
        @type Object
        */
        this.name = null;

        /**
        @property description
        @type Object
        */
        this.description = null;

        /**
        @property type
        @type String
        */
        this.type = null;

        /**
        @property moreInfo
        @type String
        */
        this.moreInfo = null;

        /**
        @property extensions
        @type Object
        */
        this.extensions = null;

        /**
        @property interactionType
        @type String
        */
        this.interactionType = null;

        /**
        @property correctResponsesPattern
        @type Array
        */
        this.correctResponsesPattern = null;

        /**
        @property choices
        @type Array
        */
        this.choices = null;

        /**
        @property scale
        @type Array
        */
        this.scale = null;

        /**
        @property source
        @type Array
        */
        this.source = null;

        /**
        @property target
        @type Array
        */
        this.target = null;

        /**
        @property steps
        @type Array
        */
        this.steps = null;

        this.init(cfg);
    };
    ActivityDefinition.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "ActivityDefinition",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");

            var i,
                j,
                prop,
                directProps = [
                    "name",
                    "description",
                    "moreInfo",
                    "extensions",
                    "correctResponsesPattern"
                ],
                interactionComponentProps = []
            ;

            cfg = cfg || {};

            if (cfg.hasOwnProperty("type") && cfg.type !== null) {
                // TODO: verify type is URI?
                for (prop in _downConvertMap) {
                    if (_downConvertMap.hasOwnProperty(prop) && _downConvertMap[prop] === cfg.type) {
                        cfg.type = _downConvertMap[prop];
                    }
                }
                this.type = cfg.type;
            }

            if (cfg.hasOwnProperty("interactionType") && cfg.interactionType !== null) {
                // TODO: verify interaction type in acceptable set?
                this.interactionType = cfg.interactionType;
                if (cfg.interactionType === "choice" || cfg.interactionType === "sequencing") {
                    interactionComponentProps.push("choices");
                }
                else if (cfg.interactionType === "likert") {
                    interactionComponentProps.push("scale");
                }
                else if (cfg.interactionType === "matching") {
                    interactionComponentProps.push("source");
                    interactionComponentProps.push("target");
                }
                else if (cfg.interactionType === "performance") {
                    interactionComponentProps.push("steps");
                }

                if (interactionComponentProps.length > 0) {
                    for (i = 0; i < interactionComponentProps.length; i += 1) {
                        prop = interactionComponentProps[i];
                        if (cfg.hasOwnProperty(prop) && cfg[prop] !== null) {
                            this[prop] = [];
                            for (j = 0; j < cfg[prop].length; j += 1) {
                                if (cfg[prop][j] instanceof TinCan.InteractionComponent) {
                                    this[prop].push(cfg[prop][j]);
                                } else {
                                    this[prop].push(
                                        new TinCan.InteractionComponent (
                                            cfg[prop][j]
                                        )
                                    );
                                }
                            }
                        }
                    }
                }
            }

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }
        },

        /**
        @method toString
        @return {String} String representation of the definition
        */
        toString: function (lang) {
            this.log("toString");

            if (this.name !== null) {
                return this.getLangDictionaryValue("name", lang);
            }

            if (this.description !== null) {
                return this.getLangDictionaryValue("description", lang);
            }

            return "";
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result = {},
                directProps = [
                    "name",
                    "description",
                    "interactionType",
                    "correctResponsesPattern",
                    "extensions"
                ],
                interactionComponentProps = [
                    "choices",
                    "scale",
                    "source",
                    "target",
                    "steps"
                ],
                i,
                j,
                prop
            ;

            version = version || TinCan.versions()[0];

            if (this.type !== null) {
                if (version === "0.9") {
                    result.type = _downConvertMap[this.type];
                }
                else {
                    result.type = this.type;
                }
            }

            for (i = 0; i < directProps.length; i += 1) {
                prop = directProps[i];
                if (this[prop] !== null) {
                    result[prop] = this[prop];
                }
            }

            for (i = 0; i < interactionComponentProps.length; i += 1) {
                prop = interactionComponentProps[i];
                if (this[prop] !== null) {
                    result[prop] = [];
                    for (j = 0; j < this[prop].length; j += 1) {
                        result[prop].push(
                            this[prop][j].asVersion(version)
                        );
                    }
                }
            }

            if (version.indexOf("0.9") !== 0) {
                if (this.moreInfo !== null) {
                    result.moreInfo = this.moreInfo;
                }
            }

            return result;
        },

        /**
        See {{#crossLink "TinCan.Utils/getLangDictionaryValue"}}{{/crossLink}}

        @method getLangDictionaryValue
        */
        getLangDictionaryValue: TinCan.Utils.getLangDictionaryValue
    };

    /**
    @method fromJSON
    @return {Object} ActivityDefinition
    @static
    */
    ActivityDefinition.fromJSON = function (definitionJSON) {
        ActivityDefinition.prototype.log("fromJSON");
        var _definition = JSON.parse(definitionJSON);

        return new ActivityDefinition(_definition);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Activity
**/
(function () {
    "use strict";

    /**
    @class TinCan.Activity
    @constructor
    */
    var Activity = TinCan.Activity = function (cfg) {
        this.log("constructor");

        /**
        @property objectType
        @type String
        @default Activity
        */
        this.objectType = "Activity";

        /**
        @property id
        @type String
        */
        this.id = null;

        /**
        @property definition
        @type TinCan.ActivityDefinition
        */
        this.definition = null;

        this.init(cfg);
    };
    Activity.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "Activity",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");

            var i,
                directProps = [
                    "id"
                ]
            ;

            cfg = cfg || {};

            if (cfg.hasOwnProperty("definition")) {
                if (cfg.definition instanceof TinCan.ActivityDefinition) {
                    this.definition = cfg.definition;
                } else {
                    this.definition = new TinCan.ActivityDefinition (cfg.definition);
                }
            }

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }
        },

        /**
        @method toString
        @return {String} String representation of the activity
        */
        toString: function (lang) {
            this.log("toString");
            var defString = "";

            if (this.definition !== null) {
                defString = this.definition.toString(lang);
                if (defString !== "") {
                    return defString;
                }
            }

            if (this.id !== null) {
                return this.id;
            }

            return "Activity: unidentified";
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result = {
                id: this.id,
                objectType: this.objectType
            };

            version = version || TinCan.versions()[0];

            if (this.definition !== null) {
                result.definition = this.definition.asVersion(version);
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} Activity
    @static
    */
    Activity.fromJSON = function (activityJSON) {
        Activity.prototype.log("fromJSON");
        var _activity = JSON.parse(activityJSON);

        return new Activity(_activity);
    };
}());

/*
    Copyright 2013 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.ContextActivities
**/
(function () {
    "use strict";

    /**
    @class TinCan.ContextActivities
    @constructor
    */
    var ContextActivities = TinCan.ContextActivities = function (cfg) {
        this.log("constructor");

        /**
        @property category
        @type Array
        */
        this.category = null;

        /**
        @property parent
        @type Array
        */
        this.parent = null;

        /**
        @property grouping
        @type Array
        */
        this.grouping = null;

        /**
        @property other
        @type Array
        */
        this.other = null;

        this.init(cfg);
    };
    ContextActivities.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "ContextActivities",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");

            var i,
                j,
                objProps = [
                    "category",
                    "parent",
                    "grouping",
                    "other"
                ],
                prop,
                val
            ;

            cfg = cfg || {};

            for (i = 0; i < objProps.length; i += 1) {
                prop = objProps[i];
                if (cfg.hasOwnProperty(prop) && cfg[prop] !== null) {
                    if (Object.prototype.toString.call(cfg[prop]) === "[object Array]") {
                        for (j = 0; j < cfg[prop].length; j += 1) {
                            this.add(prop, cfg[prop][j]);
                        }
                    }
                    else {
                        val = cfg[prop];

                        this.add(prop, val);
                    }
                }
            }
        },

        /**
        @method add
        @param String key Property to add value to one of "category", "parent", "grouping", "other"
        @return Number index where the value was added
        */
        add: function (key, val) {
            if (key !== "category" && key !== "parent" && key !== "grouping" && key !== "other") {
                return;
            }

            if (this[key] === null) {
                this[key] = [];
            }

            if (! (val instanceof TinCan.Activity)) {
                val = typeof val === "string" ? { id: val } : val;
                val = new TinCan.Activity (val);
            }

            this[key].push(val);

            return this[key].length - 1;
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result = {},
                optionalObjProps = [
                    "parent",
                    "grouping",
                    "other"
                ],
                i,
                j;

            version = version || TinCan.versions()[0];

            for (i = 0; i < optionalObjProps.length; i += 1) {
                if (this[optionalObjProps[i]] !== null && this[optionalObjProps[i]].length > 0) {
                    if (version === "0.9" || version === "0.95") {
                        if (this[optionalObjProps[i]].length > 1) {
                            // TODO: exception?
                            this.log("[warning] version does not support multiple values in: " + optionalObjProps[i]);
                        }

                        result[optionalObjProps[i]] = this[optionalObjProps[i]][0].asVersion(version);
                    }
                    else {
                        result[optionalObjProps[i]] = [];
                        for (j = 0; j < this[optionalObjProps[i]].length; j += 1) {
                            result[optionalObjProps[i]].push(
                                this[optionalObjProps[i]][j].asVersion(version)
                            );
                        }
                    }
                }
            }
            if (this.category !== null && this.category.length > 0) {
                if (version === "0.9" || version === "0.95") {
                    this.log("[error] version does not support the 'category' property: " + version);
                    throw new Error(version + " does not support the 'category' property");
                }
                else {
                    result.category = [];
                    for (i = 0; i < this.category.length; i += 1) {
                        result.category.push(this.category[i].asVersion(version));
                    }
                }
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} ContextActivities
    @static
    */
    ContextActivities.fromJSON = function (contextActivitiesJSON) {
        ContextActivities.prototype.log("fromJSON");
        var _contextActivities = JSON.parse(contextActivitiesJSON);

        return new ContextActivities(_contextActivities);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Context
**/
(function () {
    "use strict";

    /**
    @class TinCan.Context
    @constructor
    */
    var Context = TinCan.Context = function (cfg) {
        this.log("constructor");

        /**
        @property registration
        @type String|null
        */
        this.registration = null;

        /**
        @property instructor
        @type TinCan.Agent|TinCan.Group|null
        */
        this.instructor = null;

        /**
        @property team
        @type TinCan.Agent|TinCan.Group|null
        */
        this.team = null;

        /**
        @property contextActivities
        @type ContextActivities|null
        */
        this.contextActivities = null;

        /**
        @property revision
        @type String|null
        */
        this.revision = null;

        /**
        @property platform
        @type Object|null
        */
        this.platform = null;

        /**
        @property language
        @type String|null
        */
        this.language = null;

        /**
        @property statement
        @type StatementRef|null
        */
        this.statement = null;

        /**
        @property extensions
        @type String
        */
        this.extensions = null;

        this.init(cfg);
    };
    Context.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "Context",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");

            var i,
                directProps = [
                    "registration",
                    "revision",
                    "platform",
                    "language",
                    "extensions"
                ],
                agentGroupProps = [
                    "instructor",
                    "team"
                ],
                prop,
                val
            ;

            cfg = cfg || {};

            for (i = 0; i < directProps.length; i += 1) {
                prop = directProps[i];
                if (cfg.hasOwnProperty(prop) && cfg[prop] !== null) {
                    this[prop] = cfg[prop];
                }
            }
            for (i = 0; i < agentGroupProps.length; i += 1) {
                prop = agentGroupProps[i];
                if (cfg.hasOwnProperty(prop) && cfg[prop] !== null) {
                    val = cfg[prop];

                    if (typeof val.objectType === "undefined" || val.objectType === "Person") {
                        val.objectType = "Agent";
                    }

                    if (val.objectType === "Agent" && ! (val instanceof TinCan.Agent)) {
                        val = new TinCan.Agent (val);
                    } else if (val.objectType === "Group" && ! (val instanceof TinCan.Group)) {
                        val = new TinCan.Group (val);
                    }

                    this[prop] = val;
                }
            }

            if (cfg.hasOwnProperty("contextActivities") && cfg.contextActivities !== null) {
                if (cfg.contextActivities instanceof TinCan.ContextActivities) {
                    this.contextActivities = cfg.contextActivities;
                }
                else {
                    this.contextActivities = new TinCan.ContextActivities(cfg.contextActivities);
                }
            }

            if (cfg.hasOwnProperty("statement") && cfg.statement !== null) {
                if (cfg.statement instanceof TinCan.StatementRef) {
                    this.statement = cfg.statement;
                }
                else if (cfg.statement instanceof TinCan.SubStatement) {
                    this.statement = cfg.statement;
                }
                else if (cfg.statement.objectType === "StatementRef") {
                    this.statement = new TinCan.StatementRef(cfg.statement);
                }
                else if (cfg.statement.objectType === "SubStatement") {
                    this.statement = new TinCan.SubStatement(cfg.statement);
                }
                else {
                    this.log("Unable to parse statement.context.statement property.");
                }
            }
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result = {},
                optionalDirectProps = [
                    "registration",
                    "revision",
                    "platform",
                    "language",
                    "extensions"
                ],
                optionalObjProps = [
                    "instructor",
                    "team",
                    "contextActivities",
                    "statement"
                ],
                i;

            version = version || TinCan.versions()[0];

            if (this.statement instanceof TinCan.SubStatement && version !== "0.9" && version !== "0.95") {
                this.log("[error] version does not support SubStatements in the 'statement' property: " + version);
                throw new Error(version + " does not support SubStatements in the 'statement' property");
            }

            for (i = 0; i < optionalDirectProps.length; i += 1) {
                if (this[optionalDirectProps[i]] !== null) {
                    result[optionalDirectProps[i]] = this[optionalDirectProps[i]];
                }
            }
            for (i = 0; i < optionalObjProps.length; i += 1) {
                if (this[optionalObjProps[i]] !== null) {
                    result[optionalObjProps[i]] = this[optionalObjProps[i]].asVersion(version);
                }
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} Context
    @static
    */
    Context.fromJSON = function (contextJSON) {
        Context.prototype.log("fromJSON");
        var _context = JSON.parse(contextJSON);

        return new Context(_context);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.StatementRef
**/
(function () {
    "use strict";

    /**
    @class TinCan.StatementRef
    @constructor
    @param {Object} [cfg] Configuration used to initialize.
        @param {Object} [cfg.id] ID of statement to reference
    **/
    var StatementRef = TinCan.StatementRef = function (cfg) {
        this.log("constructor");

        /**
        @property id
        @type String
        */
        this.id = null;

        this.init(cfg);
    };

    StatementRef.prototype = {
        /**
        @property objectType
        @type String
        @default Agent
        */
        objectType: "StatementRef",

        /**
        @property LOG_SRC
        */
        LOG_SRC: "StatementRef",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize (see constructor)
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "id"
                ];

            cfg = cfg || {};

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }
        },

        /**
        @method toString
        @return {String} String representation of the statement
        */
        toString: function () {
            this.log("toString");
            return this.id;
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result = {
                objectType: this.objectType,
                id: this.id
            };

            if (version === "0.9") {
                result.objectType = "Statement";
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} StatementRef
    @static
    */
    StatementRef.fromJSON = function (stRefJSON) {
        StatementRef.prototype.log("fromJSON");
        var _stRef = JSON.parse(stRefJSON);

        return new StatementRef(_stRef);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.SubStatement
**/
(function () {
    "use strict";

    /**
    @class TinCan.SubStatement
    @constructor
    @param {Object} [cfg] Configuration used to initialize.
        @param {TinCan.Agent} [cfg.actor] Actor of statement
        @param {TinCan.Verb} [cfg.verb] Verb of statement
        @param {TinCan.Activity|TinCan.Agent} [cfg.object] Alias for 'target'
        @param {TinCan.Activity|TinCan.Agent} [cfg.target] Object of statement
        @param {TinCan.Result} [cfg.result] Statement Result
        @param {TinCan.Context} [cfg.context] Statement Context
    **/
    var SubStatement = TinCan.SubStatement = function (cfg) {
        this.log("constructor");

        /**
        @property actor
        @type Object
        */
        this.actor = null;

        /**
        @property verb
        @type Object
        */
        this.verb = null;

        /**
        @property target
        @type Object
        */
        this.target = null;

        /**
        @property result
        @type Object
        */
        this.result = null;

        /**
        @property context
        @type Object
        */
        this.context = null;

        /**
        @property timestamp
        @type Date
        */
        this.timestamp = null;

        this.init(cfg);
    };

    SubStatement.prototype = {
        /**
        @property objectType
        @type String
        @default Agent
        */
        objectType: "SubStatement",

        /**
        @property LOG_SRC
        */
        LOG_SRC: "SubStatement",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize (see constructor)
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "timestamp"
                ];

            cfg = cfg || {};

            if (cfg.hasOwnProperty("object")) {
                cfg.target = cfg.object;
            }

            if (cfg.hasOwnProperty("actor")) {
                if (typeof cfg.actor.objectType === "undefined" || cfg.actor.objectType === "Person") {
                    cfg.actor.objectType = "Agent";
                }

                if (cfg.actor.objectType === "Agent") {
                    if (cfg.actor instanceof TinCan.Agent) {
                        this.actor = cfg.actor;
                    } else {
                        this.actor = new TinCan.Agent (cfg.actor);
                    }
                } else if (cfg.actor.objectType === "Group") {
                    if (cfg.actor instanceof TinCan.Group) {
                        this.actor = cfg.actor;
                    } else {
                        this.actor = new TinCan.Group (cfg.actor);
                    }
                }
            }
            if (cfg.hasOwnProperty("verb")) {
                if (cfg.verb instanceof TinCan.Verb) {
                    this.verb = cfg.verb;
                } else {
                    this.verb = new TinCan.Verb (cfg.verb);
                }
            }
            if (cfg.hasOwnProperty("target")) {
                if (cfg.target instanceof TinCan.Activity ||
                    cfg.target instanceof TinCan.Agent ||
                    cfg.target instanceof TinCan.Group ||
                    cfg.target instanceof TinCan.SubStatement ||
                    cfg.target instanceof TinCan.StatementRef
                ) {
                    this.target = cfg.target;
                } else {
                    if (typeof cfg.target.objectType === "undefined") {
                        cfg.target.objectType = "Activity";
                    }

                    if (cfg.target.objectType === "Activity") {
                        this.target = new TinCan.Activity (cfg.target);
                    } else if (cfg.target.objectType === "Agent") {
                        this.target = new TinCan.Agent (cfg.target);
                    } else if (cfg.target.objectType === "Group") {
                        this.target = new TinCan.Group (cfg.target);
                    } else if (cfg.target.objectType === "SubStatement") {
                        this.target = new TinCan.SubStatement (cfg.target);
                    } else if (cfg.target.objectType === "StatementRef") {
                        this.target = new TinCan.StatementRef (cfg.target);
                    } else {
                        this.log("Unrecognized target type: " + cfg.target.objectType);
                    }
                }
            }
            if (cfg.hasOwnProperty("result")) {
                if (cfg.result instanceof TinCan.Result) {
                    this.result = cfg.result;
                } else {
                    this.result = new TinCan.Result (cfg.result);
                }
            }
            if (cfg.hasOwnProperty("context")) {
                if (cfg.context instanceof TinCan.Context) {
                    this.context = cfg.context;
                } else {
                    this.context = new TinCan.Context (cfg.context);
                }
            }

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }
        },

        /**
        @method toString
        @return {String} String representation of the statement
        */
        toString: function (lang) {
            this.log("toString");
            return (this.actor !== null ? this.actor.toString(lang) : "") +
                    " " +
                    (this.verb !== null ? this.verb.toString(lang) : "") +
                    " " +
                    (this.target !== null ? this.target.toString(lang) : "");
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result,
                optionalDirectProps = [
                    "timestamp"
                ],
                optionalObjProps = [
                    "actor",
                    "verb",
                    "result",
                    "context"
                ],
                i;

            result = {
                objectType: this.objectType
            };
            version = version || TinCan.versions()[0];

            for (i = 0; i < optionalDirectProps.length; i += 1) {
                if (this[optionalDirectProps[i]] !== null) {
                    result[optionalDirectProps[i]] = this[optionalDirectProps[i]];
                }
            }
            for (i = 0; i < optionalObjProps.length; i += 1) {
                if (this[optionalObjProps[i]] !== null) {
                    result[optionalObjProps[i]] = this[optionalObjProps[i]].asVersion(version);
                }
            }
            if (this.target !== null) {
                result.object = this.target.asVersion(version);
            }

            if (version === "0.9") {
                result.objectType = "Statement";
            }

            return result;
        }
    };

    /**
    @method fromJSON
    @return {Object} SubStatement
    @static
    */
    SubStatement.fromJSON = function (subStJSON) {
        SubStatement.prototype.log("fromJSON");
        var _subSt = JSON.parse(subStJSON);

        return new SubStatement(_subSt);
    };
}());

/*
    Copyright 2012-3 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Statement
**/
(function () {
    "use strict";

    /**
    @class TinCan.Statement
    @constructor
    @param {Object} [cfg] Values to set in properties
        @param {String} [cfg.id] Statement ID (UUID)
        @param {TinCan.Agent} [cfg.actor] Actor of statement
        @param {TinCan.Verb} [cfg.verb] Verb of statement
        @param {TinCan.Activity|TinCan.Agent|TinCan.Group|TinCan.StatementRef|TinCan.SubStatement} [cfg.object] Alias for 'target'
        @param {TinCan.Activity|TinCan.Agent|TinCan.Group|TinCan.StatementRef|TinCan.SubStatement} [cfg.target] Object of statement
        @param {TinCan.Result} [cfg.result] Statement Result
        @param {TinCan.Context} [cfg.context] Statement Context
        @param {TinCan.Agent} [cfg.authority] Statement Authority
        @param {String} [cfg.timestamp] ISO8601 Date/time value
        @param {String} [cfg.stored] ISO8601 Date/time value
        @param {String} [cfg.version] Version of the statement (post 0.95)
    @param {Object} [initCfg] Configuration of initialization process
        @param {Integer} [initCfg.storeOriginal] Whether to store a JSON stringified version
            of the original options object, pass number of spaces used for indent
        @param {Boolean} [initCfg.doStamp] Whether to automatically set the 'id' and 'timestamp'
            properties (default: true)
    **/
    var Statement = TinCan.Statement = function (cfg, initCfg) {
        this.log("constructor");

        // check for true value for API backwards compat
        if (typeof initCfg === "number") {
            initCfg = {
                storeOriginal: initCfg
            };
        } else {
            initCfg = initCfg || {};
        }
        if (typeof initCfg.storeOriginal === "undefined") {
            initCfg.storeOriginal = null;
        }
        if (typeof initCfg.doStamp === "undefined") {
            initCfg.doStamp = true;
        }

        /**
        @property id
        @type String
        */
        this.id = null;

        /**
        @property actor
        @type TinCan.Agent|TinCan.Group|null
        */
        this.actor = null;

        /**
        @property verb
        @type TinCan.Verb|null
        */
        this.verb = null;

        /**
        @property target
        @type TinCan.Activity|TinCan.Agent|TinCan.Group|TinCan.StatementRef|TinCan.SubStatement|null
        */
        this.target = null;

        /**
        @property result
        @type Object
        */
        this.result = null;

        /**
        @property context
        @type Object
        */
        this.context = null;

        /**
        @property timestamp
        @type String
        */
        this.timestamp = null;

        /**
        @property stored
        @type String
        */
        this.stored = null;

        /**
        @property authority
        @type TinCan.Agent|null
        */
        this.authority = null;

        /**
        @property version
        @type String
        */
        this.version = null;

        /**
        @property degraded
        @type Boolean
        @default false
        */
        this.degraded = false;

        /**
        @property voided
        @type Boolean
        @default null
        @deprecated
        */
        this.voided = null;

        /**
        @property inProgress
        @type Boolean
        @deprecated
        */
        this.inProgress = null;

        /**
        @property originalJSON
        @type String
        */
        this.originalJSON = null;

        this.init(cfg, initCfg);
    };

    Statement.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "Statement",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [properties] Configuration used to set properties (see constructor)
        @param {Object} [cfg] Configuration used to initialize (see constructor)
        */
        init: function (cfg, initCfg) {
            this.log("init");
            var i,
                directProps = [
                    "id",
                    "stored",
                    "timestamp",
                    "version",
                    "inProgress",
                    "voided"
                ];

            cfg = cfg || {};

            if (initCfg.storeOriginal) {
                this.originalJSON = JSON.stringify(cfg, null, initCfg.storeOriginal);
            }

            if (cfg.hasOwnProperty("object")) {
                cfg.target = cfg.object;
            }

            if (cfg.hasOwnProperty("actor")) {
                if (typeof cfg.actor.objectType === "undefined" || cfg.actor.objectType === "Person") {
                    cfg.actor.objectType = "Agent";
                }

                if (cfg.actor.objectType === "Agent") {
                    if (cfg.actor instanceof TinCan.Agent) {
                        this.actor = cfg.actor;
                    } else {
                        this.actor = new TinCan.Agent (cfg.actor);
                    }
                } else if (cfg.actor.objectType === "Group") {
                    if (cfg.actor instanceof TinCan.Group) {
                        this.actor = cfg.actor;
                    } else {
                        this.actor = new TinCan.Group (cfg.actor);
                    }
                }
            }
            if (cfg.hasOwnProperty("authority")) {
                if (typeof cfg.authority.objectType === "undefined" || cfg.authority.objectType === "Person") {
                    cfg.authority.objectType = "Agent";
                }

                if (cfg.authority.objectType === "Agent") {
                    if (cfg.authority instanceof TinCan.Agent) {
                        this.authority = cfg.authority;
                    } else {
                        this.authority = new TinCan.Agent (cfg.authority);
                    }
                } else if (cfg.authority.objectType === "Group") {
                    if (cfg.actor instanceof TinCan.Group) {
                        this.authority = cfg.authority;
                    } else {
                        this.authority = new TinCan.Group (cfg.authority);
                    }
                }
            }
            if (cfg.hasOwnProperty("verb")) {
                if (cfg.verb instanceof TinCan.Verb) {
                    this.verb = cfg.verb;
                } else {
                    this.verb = new TinCan.Verb (cfg.verb);
                }
            }
            if (cfg.hasOwnProperty("target")) {
                if (cfg.target instanceof TinCan.Activity ||
                    cfg.target instanceof TinCan.Agent ||
                    cfg.target instanceof TinCan.Group ||
                    cfg.target instanceof TinCan.SubStatement ||
                    cfg.target instanceof TinCan.StatementRef
                ) {
                    this.target = cfg.target;
                } else {
                    if (typeof cfg.target.objectType === "undefined") {
                        cfg.target.objectType = "Activity";
                    }

                    if (cfg.target.objectType === "Activity") {
                        this.target = new TinCan.Activity (cfg.target);
                    } else if (cfg.target.objectType === "Agent") {
                        this.target = new TinCan.Agent (cfg.target);
                    } else if (cfg.target.objectType === "Group") {
                        this.target = new TinCan.Group (cfg.target);
                    } else if (cfg.target.objectType === "SubStatement") {
                        this.target = new TinCan.SubStatement (cfg.target);
                    } else if (cfg.target.objectType === "StatementRef") {
                        this.target = new TinCan.StatementRef (cfg.target);
                    } else {
                        this.log("Unrecognized target type: " + cfg.target.objectType);
                    }
                }
            }
            if (cfg.hasOwnProperty("result")) {
                if (cfg.result instanceof TinCan.Result) {
                    this.result = cfg.result;
                } else {
                    this.result = new TinCan.Result (cfg.result);
                }
            }
            if (cfg.hasOwnProperty("context")) {
                if (cfg.context instanceof TinCan.Context) {
                    this.context = cfg.context;
                } else {
                    this.context = new TinCan.Context (cfg.context);
                }
            }

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }

            if (initCfg.doStamp) {
                this.stamp();
            }
        },

        /**
        @method toString
        @return {String} String representation of the statement
        */
        toString: function (lang) {
            this.log("toString");
            return (this.actor !== null ? this.actor.toString(lang) : "") +
                    " " +
                    (this.verb !== null ? this.verb.toString(lang) : "") +
                    " " +
                    (this.target !== null ? this.target.toString(lang) : "");
        },

        /**
        @method asVersion
        @param {String} [version] Version to return (defaults to newest supported)
        */
        asVersion: function (version) {
            this.log("asVersion");
            var result = {},
                optionalDirectProps = [
                    "id",
                    "timestamp"
                ],
                optionalObjProps = [
                    "actor",
                    "verb",
                    "result",
                    "context",
                    "authority"
                ],
                i;

            version = version || TinCan.versions()[0];

            for (i = 0; i < optionalDirectProps.length; i += 1) {
                if (this[optionalDirectProps[i]] !== null) {
                    result[optionalDirectProps[i]] = this[optionalDirectProps[i]];
                }
            }
            for (i = 0; i < optionalObjProps.length; i += 1) {
                if (this[optionalObjProps[i]] !== null) {
                    result[optionalObjProps[i]] = this[optionalObjProps[i]].asVersion(version);
                }
            }
            if (this.target !== null) {
                result.object = this.target.asVersion(version);
            }

            if (version === "0.9" || version === "0.95") {
                if (this.voided !== null) {
                    result.voided = this.voided;
                }
            }
            if (version === "0.9" && this.inProgress !== null) {
                result.inProgress = this.inProgress;
            }

            return result;
        },

        /**
        Sets 'id' and 'timestamp' properties if not already set

        @method stamp
        */
        stamp: function () {
            this.log("stamp");
            if (this.id === null) {
                this.id = TinCan.Utils.getUUID();
            }
            if (this.timestamp === null) {
                this.timestamp = TinCan.Utils.getISODateString(new Date());
            }
        }
    };

    /**
    @method fromJSON
    @return {Object} Statement
    @static
    */
    Statement.fromJSON = function (stJSON) {
        Statement.prototype.log("fromJSON");
        var _st = JSON.parse(stJSON);

        return new Statement(_st);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.StatementsResult
**/
(function () {
    "use strict";

    /**
    @class TinCan.StatementsResult
    @constructor
    @param {Object} options Configuration used to initialize.
        @param {Array} options.statements Actor of statement
        @param {String} options.more URL to fetch more data
    **/
    var StatementsResult = TinCan.StatementsResult = function (cfg) {
        this.log("constructor");

        /**
        @property statements
        @type Array
        */
        this.statements = null;

        /**
        @property more
        @type String
        */
        this.more = null;

        this.init(cfg);
    };

    StatementsResult.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "StatementsResult",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");

            cfg = cfg || {};

            if (cfg.hasOwnProperty("statements")) {
                this.statements = cfg.statements;
            }
            if (cfg.hasOwnProperty("more")) {
                this.more = cfg.more;
            }
        }
    };

    /**
    @method fromJSON
    @return {Object} Agent
    @static
    */
    StatementsResult.fromJSON = function (resultJSON) {
        StatementsResult.prototype.log("fromJSON");
        var _result,
            stmts = [],
            stmt,
            i
        ;

        try {
            _result = JSON.parse(resultJSON);
        } catch (parseError) {
            StatementsResult.prototype.log("fromJSON - JSON.parse error: " + parseError);
        }

        if (_result) {
            for (i = 0; i < _result.statements.length; i += 1) {
                try {
                    stmt = new TinCan.Statement (_result.statements[i], 4);
                } catch (error) {
                    StatementsResult.prototype.log("fromJSON - statement instantiation failed: " + error + " (" + JSON.stringify(_result.statements[i]) + ")");

                    stmt = new TinCan.Statement (
                        {
                            id: _result.statements[i].id
                        },
                        4
                    );
                }

                stmts.push(stmt);
            }
            _result.statements = stmts;
        }

        return new StatementsResult (_result);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.State
**/
(function () {
    "use strict";

    /**
    @class TinCan.State
    @constructor
    */
    var State = TinCan.State = function (cfg) {
        this.log("constructor");

        /**
        @property id
        @type String
        */
        this.id = null;

        /**
        @property updated
        @type Boolean
        */
        this.updated = null;

        /**
        @property contents
        @type String
        */
        this.contents = null;

        /**
        @property etag
        @type String
        */
        this.etag = null;

        /**
        @property contentType
        @type String
        */
        this.contentType = null;

        this.init(cfg);
    };
    State.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "State",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "id",
                    "contents",
                    "etag",
                    "contentType"
                ];

            cfg = cfg || {};

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }

            this.updated = false;
        }
    };

    /**
    @method fromJSON
    @return {Object} State
    @static
    */
    State.fromJSON = function (stateJSON) {
        State.prototype.log("fromJSON");
        var _state = JSON.parse(stateJSON);

        return new State(_state);
    };
}());

/*
    Copyright 2012 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.ActivityProfile
**/
(function () {
    "use strict";

    /**
    @class TinCan.ActivityProfile
    @constructor
    */
    var ActivityProfile = TinCan.ActivityProfile = function (cfg) {
        this.log("constructor");

        /**
        @property id
        @type String
        */
        this.id = null;

        /**
        @property activity
        @type TinCan.Activity
        */
        this.activity = null;

        /**
        @property updated
        @type String
        */
        this.updated = null;

        /**
        @property contents
        @type String
        */
        this.contents = null;

        /**
        SHA1 of contents as provided by the server during last fetch,
        this should be passed through to saveActivityProfile

        @property etag
        @type String
        */
        this.etag = null;

        /**
        @property contentType
        @type String
        */
        this.contentType = null;

        this.init(cfg);
    };
    ActivityProfile.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "ActivityProfile",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "id",
                    "contents",
                    "etag",
                    "contentType"
                ];

            cfg = cfg || {};

            if (cfg.hasOwnProperty("activity")) {
                if (cfg.activity instanceof TinCan.Activity) {
                    this.activity = cfg.activity;
                }
                else {
                    this.activity = new TinCan.Activity (cfg.activity);
                }
            }

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }

            this.updated = false;
        }
    };

    /**
    @method fromJSON
    @return {Object} ActivityProfile
    @static
    */
    ActivityProfile.fromJSON = function (stateJSON) {
        ActivityProfile.prototype.log("fromJSON");
        var _state = JSON.parse(stateJSON);

        return new ActivityProfile(_state);
    };
}());

/*
    Copyright 2013 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.AgentProfile
**/
(function () {
    "use strict";

    /**
    @class TinCan.AgentProfile
    @constructor
    */
    var AgentProfile = TinCan.AgentProfile = function (cfg) {
        this.log("constructor");

        /**
        @property id
        @type String
        */
        this.id = null;

        /**
        @property agent
        @type TinCan.Agent
        */
        this.agent = null;

        /**
        @property updated
        @type String
        */
        this.updated = null;

        /**
        @property contents
        @type String
        */
        this.contents = null;

        /**
        SHA1 of contents as provided by the server during last fetch,
        this should be passed through to saveAgentProfile

        @property etag
        @type String
        */
        this.etag = null;

        /**
        @property contentType
        @type String
        */
        this.contentType = null;

        this.init(cfg);
    };
    AgentProfile.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "AgentProfile",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "id",
                    "contents",
                    "etag",
                    "contentType"
                ];

            cfg = cfg || {};

            if (cfg.hasOwnProperty("agent")) {
                if (cfg.agent instanceof TinCan.Agent) {
                    this.agent = cfg.agent;
                }
                else {
                    this.agent = new TinCan.Agent (cfg.agent);
                }
            }

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }

            this.updated = false;
        }
    };

    /**
    @method fromJSON
    @return {Object} AgentProfile
    @static
    */
    AgentProfile.fromJSON = function (stateJSON) {
        AgentProfile.prototype.log("fromJSON");
        var _state = JSON.parse(stateJSON);

        return new AgentProfile(_state);
    };
}());

/*
    Copyright 2014 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.About
**/
(function () {
    "use strict";

    /**
    @class TinCan.About
    @constructor
    */
    var About = TinCan.About = function (cfg) {
        this.log("constructor");

        /**
        @property version
        @type {String[]}
        */
        this.version = null;

        this.init(cfg);
    };
    About.prototype = {
        /**
        @property LOG_SRC
        */
        LOG_SRC: "About",

        /**
        @method log
        */
        log: TinCan.prototype.log,

        /**
        @method init
        @param {Object} [options] Configuration used to initialize
        */
        init: function (cfg) {
            this.log("init");
            var i,
                directProps = [
                    "version"
                ];

            cfg = cfg || {};

            for (i = 0; i < directProps.length; i += 1) {
                if (cfg.hasOwnProperty(directProps[i]) && cfg[directProps[i]] !== null) {
                    this[directProps[i]] = cfg[directProps[i]];
                }
            }
        }
    };

    /**
    @method fromJSON
    @return {Object} About
    @static
    */
    About.fromJSON = function (aboutJSON) {
        About.prototype.log("fromJSON");
        var _about = JSON.parse(aboutJSON);

        return new About(_about);
    };
}());

/*
    Copyright 2012-2013 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

/**
TinCan client library

@module TinCan
@submodule TinCan.Environment.Node
**/
(function () {
    /* globals require */
    "use strict";
    var LOG_SRC = "Environment.Node",
        log = TinCan.prototype.log,
        querystring = require("querystring"),
        XMLHttpRequest = require("xhr2"),
        requestComplete;

    requestComplete = function (xhr, cfg) {
        log("requestComplete - xhr.status: " + xhr.status, LOG_SRC);
        log("requestComplete - xhr.responseText: " + xhr.responseText, LOG_SRC);
        var requestCompleteResult,
            httpStatus = xhr.status,
            notFoundOk = (cfg.ignore404 && httpStatus === 404);

        if ((httpStatus >= 200 && httpStatus < 400) || notFoundOk) {
            if (cfg.callback) {
                cfg.callback(null, xhr);
                return;
            }

            requestCompleteResult = {
                err: null,
                xhr: xhr
            };
            return requestCompleteResult;
        }

        requestCompleteResult = {
            err: httpStatus,
            xhr: xhr
        };
        if (httpStatus === 0) {
            log("[warning] There was a problem communicating with the Learning Record Store. Aborted, offline, or invalid CORS endpoint (" + httpStatus + ")", LOG_SRC);
        }
        else {
            log("[warning] There was a problem communicating with the Learning Record Store. (" + httpStatus + " | " + xhr.responseText+ ")", LOG_SRC);
        }
        if (cfg.callback) {
            cfg.callback(httpStatus, xhr);
        }
        return requestCompleteResult;
    };

    //
    // Override LRS' init method to set up our request handling
    // capabilities, basically empty implementation here so that
    // we don't get a no-env loaded message
    //
    TinCan.LRS.prototype._initByEnvironment = function () {};

    //
    // use XMLHttpRequest module instead of standard Node.js http/https
    // modules since we have to support both, and because the callbacks
    // provided via the methods calling _makeRequest expect the xhr to
    // have a certain interface, that interface happens to be the browser
    // version of XHR since that's where it started, so rather than
    // changing them to use a different wrapped request/response object
    // set just use a wrapped version of the node objects which is what
    // XMLHttpRequest module provides
    //
    TinCan.LRS.prototype._makeRequest = function (fullUrl, headers, cfg) {
        log("_makeRequest using http/https", LOG_SRC);
        var xhr,
            url = fullUrl,
            async = typeof cfg.callback !== "undefined",
            prop
        ;
        if (typeof cfg.params !== "undefined" && Object.keys(cfg.params).length > 0) {
            url += "?" + querystring.stringify(cfg.params);
        }

        xhr = new XMLHttpRequest();
        xhr.open(cfg.method, url, async);
        for (prop in headers) {
            if (headers.hasOwnProperty(prop)) {
                xhr.setRequestHeader(prop, headers[prop]);
            }
        }

        if (typeof cfg.data !== "undefined") {
            cfg.data += "";
        }

        if (async) {
            xhr.onreadystatechange = function () {
                log("xhr.onreadystatechange - xhr.readyState: " + xhr.readyState, LOG_SRC);
                if (xhr.readyState === 4) {
                    requestComplete(xhr, cfg);
                }
            };
        }

        xhr.send(cfg.data);

        if (async) {
            return xhr;
        }

        return requestComplete(xhr, cfg);
    };

    //
    // Synchronos xhr handling is unsupported in node
    //
    TinCan.LRS.syncEnabled = false;
}());

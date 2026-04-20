/* Parser.js
 * written by Colin Kuebler 2012
 * Part of LDT, dual licensed under GPLv3 and MIT
 * Generates a tokenizer from regular expressions for TextareaDecorator
 */

function Parser( rules, i ){
	/* INIT */
	var api = this;

	// variables used internally
	var i = i ? 'i' : '';
	var parseRE = null;
	var ruleSrc = [];
	var ruleMap = {};

	api.add = function( rules ){
		for( var rule in rules ){
			var s = rules[rule].source;
			ruleSrc.push( s );
			ruleMap[rule] = new RegExp('^('+s+')$', i );
		}
		parseRE = new RegExp( ruleSrc.join('|'), 'g'+i );
	};
	api.tokenize = function(input){
		return input.match(parseRE);
	};
	api.identify = function(token){
		for( var rule in ruleMap ){
			if( ruleMap[rule].test(token) ){
				return rule;
			}
		}
	};

	api.add( rules );

	return api;
};


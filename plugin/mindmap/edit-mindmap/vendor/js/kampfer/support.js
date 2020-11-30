/**
 * 用于检测浏览器的功能特性
 * @module support.js
 * @author l.w.kampfer@gmail.com
 */

kampfer.provide('browser.support');

kampfer.browser.support.deleteExpando = (function() {
	var div = document.createElement('div');
	
	try{
		delete div.test;
		return true;
	} catch(e) {
		return false;
	}
})();
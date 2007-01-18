// Cookie API  v1.0.1
// documentation: http://www.dithered.com/javascript/cookies/index.html
// license: http://creativecommons.org/licenses/by/1.0/
// code (mostly) by Chris Nott (chris[at]dithered[dot]com)


// Write a cookie value
function setCookie(name, value, expires, path, domain, secure) {
	var curCookie = name + '=' + escape(value) + ((expires) ? '; expires=' + expires.toGMTString() : '') + ((path) ? '; path=' + path : '') + ((domain) ? '; domain=' + domain : '') + ((secure) ? '; secure' : '');
	document.cookie = curCookie;
}


// Retrieve a named cookie value
function getCookie(name) {
	var dc = document.cookie;
	
	// find beginning of cookie value in document.cookie
	var prefix = name + "=";
	var begin = dc.indexOf("; " + prefix);
	if (begin == -1) {
		begin = dc.indexOf(prefix);
		if (begin != 0) return null;
	}
	else begin += 2;
	
	// find end of cookie value
	var end = document.cookie.indexOf(";", begin);
	if (end == -1) end = dc.length;
	
	// return cookie value
	return unescape(dc.substring(begin + prefix.length, end));
}


// Delete a named cookie value
function deleteCookie(name, path, domain) {
	var value = getCookie(name);
	if (value != null) document.cookie = name + '=' + ((path) ? '; path=' + path : '') + ((domain) ? '; domain=' + domain : '') + '; expires=Thu, 01-Jan-70 00:00:01 GMT';
	return value;
}


// Fix Netscape 2.x Date bug
function fixDate(date) {
	var workingDate = date;
	var base = new Date(0);
	var skew = base.getTime();
	if (skew > 0) workingDate.setTime(workingDate.getTime() - skew);
	return workingDate;
}


// Test for cookie support
function supportsCookies(rootPath) {
	setCookie('checking_for_cookie_support', 'testing123', '', (rootPath != null ? rootPath : ''));
	if (getCookie('checking_for_cookie_support')) return true;
	else return false;
}
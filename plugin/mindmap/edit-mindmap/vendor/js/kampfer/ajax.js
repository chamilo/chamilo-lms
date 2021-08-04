kampfer.provide('ajax');

/**
 * @Author l.w.kampfer@gmail.com
 * @Name AJAX工具函数
 * @Version 1.0
 */

(function( window, kampfer ) {
	//XHR构造函数
	var _XHRFactories = [
		function() { return new XMLHttpRequest(); },
		function() { return new window.ActiveXObject('Microsoft.XMLHTTP'); }
	];
	
	//默认设置
	var _defaultSettings = {
		url : '',
		method : 'GET',
	    async : true,
		parameters : {},
		timeout : 10000,
		ontimeout : function() {},
		onerror : function() {},
		onsuccess : function() {},
		onbeforesend : function() {},
		context : null,
		headers : {}
	};
	
	var responceTypes = {
		xml : /xml/,
		json : /json/,
		//html : /html/,
		javascript : /javascript/
	};
	
	//将对象序列化为字符串
	function _encodeFormData( data ) {
		var pairs = [],
			regSpace = /%20/g;
		for( var name in data ) {
			var value = data[name].toString();
			//使用encodeURIComponent对名值对进行编码
			var pair = encodeURIComponent( name ).replace( regSpace, '+' ) + '=' +
				encodeURIComponent( value ).replace( regSpace, '+' );
			pairs.push( pair );
		}
		return pairs.join( '&' );
	}
	
	//区别对待返回类型
	function _getResponse( request ) {
		var contentType = request.getResponseHeader('Content-Type');
		if( responceTypes.xml.test( contentType ) ) {
			return request.responseXML; 
		}else if( responceTypes.json.test( contentType ) || responceTypes.javascript.test( contentType ) ) {
			return eval( '(' + request.responseText + ')' );
		}else{
			return request.responseText;
		}
	}
	
	//创建新的XMLHttpRequest对象
	function _createNewXHR() {
		for( var i = 0, l = _XHRFactories.length; i < l; i++ ) {
			try {
				_XHRFactories[i]();
				_createNewXHR = _XHRFactories[i];
				break;
			} catch( e ) {
				//consle.log( ' XHR not supported' );
			}
		}
		return _createNewXHR();
	}
	
	var ajax = function( options ) {
		var newXHR = _createNewXHR(), 
			timer,
			url,
			parameters;
		options = kampfer.extend( {}, _defaultSettings, options );
		//设置执行上下文
		if( !options.context ) {
			options.context = newXHR;
		}
		if( options.ontimeout ) {
			//闭包
			timer = setTimeout( function(){
				newXHR.abort();
				if ( options.ontimeout ) {
					options.ontimeout.call( options.context, options );
				}
			}, options.timeout );
		}
		newXHR.onreadystatechange = function() {
			if( newXHR.readyState === 4 ) {
				if( timer ) {
					clearTimeout( timer );
				}
				if( newXHR.status === 200 ) {
					options.onsuccess.call( options.context, _getResponse( newXHR ) );
				} else {
					if( options.onerror ) {
						options.onerror.call( options.context, newXHR );
					}
				}
			}
		};
		url = options.url;
		parameters = options.parameters && _encodeFormData( options.parameters );
		if( options.method.toUpperCase() === 'GET' ) {
			if( options.parameters ) {
				url += '?' + parameters + '&_=' + ( +new Date() );
			}
			newXHR.open( 'GET', url, options.async );
			options.onbeforesend && options.onbeforesend.call( options.context, newXHR );
			newXHR.send( null );
		} else if( options.method.toUpperCase() === 'POST' ) {
			newXHR.open( 'POST', url, options.async );
			options.onbeforesend && options.onbeforesend.call( options.context, newXHR );
			newXHR.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
			newXHR.send( parameters );
		}
	};
	
	kampfer.extend( kampfer, {
		ajax : ajax 
	});
	
})( window, kampfer );
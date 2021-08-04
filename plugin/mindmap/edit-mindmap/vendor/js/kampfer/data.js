/*global kampfer*/

/**
 * 为对象管理数据
 * @module data
 * https://github.com/jquery/jquery/blob/master/src/data.js
 */

kampfer.require('browser.support');

kampfer.provide('data');

//kampfer的数据缓存
kampfer.data.cache = {};

//用于标记缓存的id
kampfer.data.cacheId = 0;

//不能设置自定义属性的HTML tag名单
kampfer.data.noData = {
	"embed": true,
	//object标签的clsid为以下值时可以设置自定义属性,
	//其他情况object也不能设置自定义属性
	"object": "clsid:D27CDB6E-AE6D-11cf-96B8-444553540000",
	"applet": true
};

/*
 * 判断对象是否能够设置自定义属性。所有plain object都能设置自定义属性，
 * 而html dom中：embed/applet无法设置，obeject只有当clsid为特定值时可以设置。
 * @param {object||html dom}elem
 * @return {boolean}
 */
kampfer.data.acceptData = function(elem) {
	if(kampfer.type(elem) === 'object') {
		if(elem.nodeName) {
			var match = kampfer.data.noData[ elem.nodeName.toLowerCase() ];
			if( match ) {
				return !(match === true || elem.getAttribute('classid') !== match);
			}
		}
		return true;
	}
};

/*
 * 判断数据对象是否为空。必须区分两种情况：
 * 1。用户的数据对象 所有用户的数据都储存在数据对象的data属性中。
 * 2。kampfer的数据对象 kampfer的数据会被直接储存在数据对象中。
 * @param {plain object}obj 这个对象一般取自kampfer.data.cache[expando]
 *	或者elem[expando]
 * @return {boolean}
 */
kampfer.data.isEmptyDataObj = function(obj) {
	for(var name in obj) {
		//检查用户定义的data（即cache.data）
		if( name === 'data' && kampfer.isEmptyObject(obj[name]) ) {
			continue;
		}
		if( name !== 'toJSON' ) {
			return false;
		}
	}
	return true;
};

//判断对象是否储存了数据。此方法先取到elem的数据对象，
//再调用kampfer.data.isEmptyDataObj判断对象是否为空
kampfer.data.hasData = function(elem) {
	elem = elem.nodeType ? 
		kampfer.data.cache[ elem[kampfer.expando] ] :
		elem[kampfer.expando];
	return !!elem && !kampfer.data.isEmptyDataObj(elem);
};

/*
 * 写缓存. 只接受key-value形式的参数.
 * @param {object||html dom}elem
 * @param {string}name
 * @param {*}value
 * @param {boolean}internal
 * @return
 */
kampfer.data.setData = function(elem, name, value, internal) {
	if( !kampfer.data.acceptData(elem)　) {
		return;
	}

	var expando = kampfer.expando,
		isNode = !!elem.nodeType,
		cache = isNode ? kampfer.data.cache : elem,
		cacheId = isNode ? elem[expando] : elem[expando] && expando,
		thisCache;

	// 设置cacheId
	if(!cacheId) {
		if(isNode) {
			elem[expando] = cacheId = ++kampfer.data.cacheId;
		} else {
			cacheId = expando;
		}
	}

	// 取得cache object
	if(!cache[cacheId]) {
		cache[cacheId] = {};
	}
	thisCache = cache[cacheId];
	// 区分内部调用和客户调用时数据的储存位置
	if(!internal) {
		if(!thisCache.data) {
			thisCache.data = {};
		}
		thisCache = thisCache.data;
	}
	
	// 不做判断，直接覆盖旧值
	if(kampfer.type(name) === 'object') {
		thisCache = kampfer.extend(thisCache, name);
	} else if(value !== undefined) {
		thisCache[name] = value;
	}

	return thisCache;
};

/*
 * 读缓存。如果不提供name,直接返回整个cache。
 * @param {object||html dom}elem
 * @param {string}name option
 * @param {boolean}internal option
 * @return {*}
 */
kampfer.data.getData = function(elem, name, internal) {
	if( !kampfer.data.acceptData(elem) ) {
		return;
	}

	var expando = kampfer.expando,
		isNode = !!elem.nodeType,
		cache = isNode ? kampfer.data.cache : elem,
		cacheId = isNode ? elem[expando] : elem[expando] && expando,
		hasName = kampfer.type(name) !== 'boolean' && name !== undefined,
		thisCache, ret;

	if(!cacheId || !cache[cacheId]) {
		return;
	}

	if(!hasName) {
		internal = name;
	}

	thisCache = cache[cacheId];
	if(!internal) {
		thisCache = thisCache.data || {};
	}

	if(hasName) {
		ret = thisCache[name];
	} else {
		ret = thisCache;
	}

	return ret;
};

/*
 * 删除缓存。如果不提供name,不执行任何操作。
 * @param {object||html dom}elem
 * @param {string}name
 * @param {boolean}internal option
 * @return void
 */
kampfer.data.removeData = function(elem, name, internal) {
	if( !kampfer.data.acceptData(elem) ) {
		return;
	}

	var expando = kampfer.expando,
		isNode = !!elem.nodeType,
		cache = isNode ? kampfer.data.cache : elem,
		cacheId = isNode ? elem[expando] : elem[expando] && expando,
		hasName = kampfer.type(name) !== 'bool' && name !== undefined,
		thisCache;

	if(!cacheId || !cache[cacheId] ||!hasName) {
		return;
	}

	thisCache = cache[cacheId];
	if(!internal) {
		thisCache = thisCache.data;
	}

	if(thisCache) {
		if(kampfer.type(name) !== 'array') {
			if(name in thisCache) {
				name = [name];
			}
		}
		for(var i = 0, n; n = name[i]; i++) {
			delete thisCache[n];
		}
		if( !kampfer.data.isEmptyDataObj(thisCache) ) {
			return;
		}
	}

	if(!internal) {
		delete cache[cacheId].data;
		if( !kampfer.data.isEmptyDataObj( cache[cacheId] ) ) {
			return;
		}
	}


	// 清空数据cache
	// as window.nodeType === undefined, so when elem === window isNode === false
	if ( kampfer.browser.support.deleteExpando || !cache.setInterval ) {
		delete cache[cacheId];
	} else {
		cache[cacheId] = null;
	}

	// 清除html dom的expando
	if(isNode) {
		// IE does not allow us to delete expando properties from nodes,
		// nor does it have a removeAttribute function on Document nodes;
		// we must handle all of these cases
		if ( kampfer.browser.support.deleteExpando ) {
			delete elem[ expando ];
		} else if ( elem.removeAttribute ) {
			elem.removeAttribute( expando );
		} else {
			elem[ expando ] = null;
		}
	}
};


//kampfer内部调用
kampfer.data.setDataInternal = function(elem, name, value) {
	kampfer.data.setData(elem, name, value, true);
};

//kampfer内部调用
kampfer.data.getDataInternal = function(elem, name) {
	return kampfer.data.getData(elem, name, true);
};

//kampfer内部调用
kampfer.data.removeDataInternal = function(elem, name) {
	kampfer.data.removeData(elem, name, true);
};
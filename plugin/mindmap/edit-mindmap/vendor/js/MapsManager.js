/*global kampfer*/
kampfer.require('Class');
kampfer.require('store');

kampfer.provide('mindMap.MapsManager');

/*
 * 负责维护localStorage
 * MapsManager提供的查询方法返回的都是对数据的引用，因此它们都是只读的。
 * 绝对不要直接对它们进行写操作。
 * localStorage目前维护两部分内容:
 * 1. map的信息
 * 2. clipboard的信息
 */

kampfer.mindMap.MapsManager = kampfer.Class.extend({

	initializer : function(appName) {
		if(appName) {
			this._appName = appName;
		}
	},
	
	_appName : 'mindMap',
	
	getAppName : function() {
		return this._appName;
	},
	
	//从localStorage中读取mindMap保存的数据。
	//如果没有任何数据，那么就创建一个新的空的数据对象，并将它写入 localStorage。
	getMapStorage : function() {
		var mapStore = kampfer.store.get(this._appName);
		if(!mapStore) {
			mapStore = {};

			mapStore.maps = {};
			mapStore.maps._count = 0;

			kampfer.store.set(this._appName, mapStore);
		}
		return mapStore;
	},
	
	getMapData : function(name) {
		var mapStore = this.getMapStorage();
		if(name) {
			return mapStore.maps[name];
		}
	},
	
	//只接受object作为参数
	saveMapToLocalStorage : function(data) {
		if( kampfer.type(data) === 'object' ) {
			var mapStorage = this.getMapStorage(),
				name = data.name;
			if( !(name in mapStorage.maps) ) {
				mapStorage.maps._count++;
			}
			mapStorage.maps[name] = data;
			kampfer.store.set(this._appName, mapStorage);
		}
	},
	
	getMapCount : function() {
		var mapStorage = this.getMapStorage();
		return mapStorage.maps._count;
	},

	//返回包含所有map名字的数组
	getMapList : function() {
		var mapStorage = this.getMapStorage();
		if(mapStorage.maps._count > 0) {
			var ret = [];
			for(var map in mapStorage.maps) {
				if( map !== '_count') {
					ret.push(mapStorage.maps[map]);
				}
			}
			return ret;
		}
	},

	hasMap : function(mapName) {
		var mapStore = kampfer.store.get(this._appName);
		if(mapStore) {
			if(mapName in mapStore.maps) {
				return true;
			}
		}
		return false;
	},

	removeMap : function(mapName) {
		if( this.hasMap(mapName) ) {
			var mapStore = kampfer.store.get(this._appName);
			delete mapStore.maps[mapName];
			kampfer.store.set(this._appName, mapStore);
		}
	},

	setClipboard : function(data) {
		var mapStore = kampfer.store.get(this._appName);
		if(mapStore) {
			mapStore.clipboard = data;
			kampfer.store.set(this._appName, mapStore);
		}
	},

	getClipboard : function() {
		var mapStore = kampfer.store.get(this._appName);
		if(mapStore && mapStore.clipboard) {
			return mapStore.clipboard;
		}
	},

	removeClipboard : function() {
		var mapStore = kampfer.store.get(this._appName);
		if(mapStore && mapStore.clipboard) {
			delete mapStore.clipboard;
			kampfer.store.set(this._appName, mapStore);
		}
	}

});
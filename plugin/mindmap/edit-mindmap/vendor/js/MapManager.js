/*global kampfer*/
kampfer.require('Class');

kampfer.provide('mindMap.MapManager');

/*
 * MapManager类为mindmap提供数据支持。
 * 注意:1.MapManager依赖的第三方组件store.js依赖JSON，当浏览器不原生支持JSON时，
 *      需要额外引入JSON.js实现兼容。
 *      2.MapManager提供的查询方法返回的都是对数据的引用，因此它们都是只读的，绝对不要直接
 *      对它们进行写操作。
 */

kampfer.mindMap.MapManager = kampfer.Class.extend({
    
    /**
     * 实例化一个MapManager对象。
     * 当传递给构建函数的参数是一个对象，那么这个对象被用作mindmap数据来源
     * @param   name{string|object|null}
     */
    initializer : function(data) {
        this._oriData = data;

        data = data || {};
        this._nodeTree = data.document || [];
        this._mapName = data.name;
        this._lastModified = data.lastModified || +new Date();
        //map用于快速查找
        this._nodeMap = this.parseTree(this._nodeTree);
        this._isModified = false;
    },

    _isModified : null,

    //重命名map的时候先改变_mapName.保存时会检查_mapName和_mapData.name的一致性,
    //两者如果不一致那么会删除localstore中名字叫_mapData.name的数据.并将_mapName
    //和_mapData.name同步. 这个过程需要同时保存新名字和旧名字,_mapName保存新名字
    //_mapData.name是旧名字。
    _mapName : null,

    //解析原始数据, 通过tree生成map
    parseTree : function(data) {
        var map = {};

        for(var i = 0, root; (root = data[i]); i++) {
            this.traverseNode(root, function(node) {
                map[node.id] = node;
            });
        }

        return map;
    },

    isModified : function() {
        return this._isModified;
    },
    
    getMapName : function() {
        return this._mapName;
    },
    
    getNode : function(id) {
        return this._nodeMap[id];
    },
    
    getChildren : function(id) {
        if(id) {
            var node = this.getNode(id);
            return node.children;
        }
    },

    getNodeTree : function() {
        return this._nodeTree;
    },
    
    //如果有同id的node那么新的node不会被添加
    addNode : function(node) {
        if( kampfer.type(node) === 'object' && !this.getNode(node.id) ) {
            var parent = this.getNode(node.parent);

            //add node to nodeTree
            if(parent) {
                if(!parent.children) {
                    parent.children = [];
                }
                parent.children.push(node);
            } else {
                this._nodeTree.push(node);
            }

            //保存node和它的子节点的引用
            this.traverseNode(node, function(node) {
                if( !(node.id in this._nodeMap) ) {
                    this._nodeMap[node.id] = node;
                }
            });
            
            this._isModified = true;
        }
    },
    
    createNode : function(data) {
        var node = {
            id : this.generateUniqueId(),
            parent : null,
            children : null,
            content : 'node',
            offset : {
                x : 100,
                y : 100
            }
        },
        type = kampfer.type(data);

        if( type === 'object' ) {
            for(var attr in node) {
                if(attr in data) {
                    if(attr === 'offset') {
                        node[attr].x = data[attr].x;
                        node[attr].y = data[attr].y;
                    } else {
                        node[attr] = data[attr];
                    }
                }
            }
        } else if( type === 'string' ) {
            node.parent = data;
        }

        return node;
    },
    
    //此方法会立即改变children数组的长度
    deleteNode : function(id) {
        var node = this.getNode(id), parent, nodeList;

        if(!node) {
            return;
        }

        parent = this.getNode(node.parent);
        if(!parent) {
            nodeList = this._nodeTree;
        } else if(parent.children) {
            nodeList = parent.children;
        }
        if(nodeList) {
            //delete node from tree
            for(var i = 0, c; (c = nodeList[i]); i++) {
                if(c.id === id) {
                    nodeList.splice(i, 1);
                    break;
                }
            }
        }
        //delete node from map
        this.traverseNode(node, function(node) {
            delete this._nodeMap[node.id];
        });

        this._isModified = true;

        return node;
    },
    
    setNodeContent : function(id, text) {
        var node = this.getNode(id);
        node.content = text;
        this._isModified = true;
    },

    getNodeContent : function(id) {
        var node = this.getNode(id);
        return node.content;
    },
    
    setNodePosition : function(id, x, y) {
        if(kampfer.type(id) === 'object') {
            id = id.id;
        }
        var node = this.getNode(id);
        node.offset.x = x;
        node.offset.y = y;
        this._isModified = true;
    },

    //传值
    getNodePosition : function(id) {
        var node = this.getNode(id);
        return {
            left : node.offset.x,
            top : node.offset.y
        };
    },
    
    setMapName : function(name) {
        this._mapName = name;
        this._isModified = true;
    },

    getMapData : function() {
        return {
            document : this._nodeTree,
            name : this._mapName,
            lastModified : this._lastModified
        };
    },

    traverseNode : function(node, callback, forward) {
        if(forward) {
            callback.call(this, node);
        }
        if(node.children) {
            for(var i = 0, child; (child = node.children[i]); i++) {
                this.traverseNode(child, callback, forward);
            }
        }
        if(!forward) {
            callback.call(this, node);
        }
    },

    traverse : function(callback) {
        for(var i = 0, node; (node = this._nodeTree[i]); i++) {
            this.traverseNode(node, callback, true);
        }
    },
    
    /*
     * 生成唯一id
     * 直接使用时间戳不可行
     * 以下方法摘自http://www.cnblogs.com/NoRoad/archive/2010/03/12/1684759.html
     */
    generateUniqueId : function() {
        var guid = "";
        for(var i = 1; i <= 32; i++) {
            var n = Math.floor(Math.random() * 16.0).toString(16);
            guid += n;
            if((i == 8) || (i == 12) || (i == 16) || (i == 20)) {
                guid += "-";
            }
        }
        return guid;
    },
    
    dispose : function() {
        delete this._oriData;
        delete this._nodeTree;
        delete this._mapName;
        delete this._lastModified;
        delete this._nodeMap;
        delete this._isModified;
    }
});
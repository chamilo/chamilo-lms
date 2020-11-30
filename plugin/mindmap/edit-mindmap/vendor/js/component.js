/*global kampfer*/
kampfer.require('Composition');
kampfer.require('dom');

kampfer.provide('Component');

kampfer.Component = kampfer.Composition.extend({

	initializer : function() {
		this._wasDecorated = false;
		this._inDocument = false;
	},
	
	_element : null,
	
	_doc : kampfer.global.document,
	
	_wasDecorated : null,
	
	_inDocument : null,
	
	addChild : function(child, render) {
		kampfer.Component.superClass.addChild.apply(this, arguments);
		if(child._inDocument && this._inDocument) {
		//如果父子component都在文档流中，那么将子component剪切到父component
			this._element.appendChild( child.getElement() );
		} else if(render) {
		//如果需要渲染子component，那么先确保父component已经生成了dom对象
		//注意：这是个强制行为
			if(!this._element) {
				this.createDom();
			}
			child.render();
		} else if(!child._inDocument && this._inDocument) {
		//如果子component不在文档流中，而父component在。那么如果子component已生成了dom对象，
		//就将子component插入文档流
			if( child.getElement() ) {
				child.enterDocument();
			}
		}
	},
	
	removeChild : function(child, unrender) {
		child = kampfer.Component.superClass.removeChild.apply(this, arguments);
		if(unrender) {
			child.exitDocument();
			var childElement = child.getElement();
			if(child.childElement) {
				child.childElement.parentNode.removeChild(child.childElement);
			}
		}
	},
	
	isInDocument : function() {
		return this._inDocument;
	},
	
	wasDecorated : function() {
		return this._wasDecorated;
	},
	
	render : function(parent) {
		if(!this._inDocument) {
			if(!this._element) {
				this.createDom();
			}
			
			this.enterDocument(parent);
			
			this.decorate();
			
			this.eachChild(function(child) {
				child.render();
			});
		}
	},
	
	createDom : function() {
		this._element = this._doc.createElement('div');
	},
	
	getElement : function() {
		return this._element;
	},
	
	/* 子类应该重写这个方法 */
	decorate : function() {
		if(!this._inDocument) {
			throw ('component not in document');
		}
		
		this._wasDecorated = true;
	},
	
	enterDocument : function(parent) {
		this._inDocument = true;
		
		if(parent && parent.nodeType) {
			parent.appendChild(this._element);
		} else if( this._parent && this._parent.getElement() ) {
			this._parent.getElement().appendChild(this._element);
		} else {
			this._doc.body.appendChild(this._element);
		}
	},
	
	exitDocument : function() {
		//if( this._parent && this._parent.getElement() ) {
		//	this._parent.getElement().removeChild(this._element);
		//} else {
		//	this._doc.body.removeChild(this._element);
		//}
		this._element.parentNode.removeChild(this._element);

		this._inDocument = false;
	},
	
	getPosition : function() {
		if(this._element) {
			return {
				left : parseInt( kampfer.dom.getStyle(this._element, 'left'), 10 ),
				top : parseInt( kampfer.dom.getStyle(this._element, 'top'), 10 )
			};
		}
	},
	
	setPosition : function(left, top) {
		if(this._element) {
			//kampfer.style.setStyle(this._element, {
			//	left : left + 'px',
			//	top : top + 'px'
			//});
			this._element.style.left = left + 'px';
			this._element.style.top = top + 'px';
		}
	},

	show : function() {
		if(this._element) {
			this.dispatch('beforeshow');
			this._element.style.display = 'block';
		}
	},

	hide : function() {
		if(this._element) {
			this.dispatch('beforehide');
			this._element.style.display = 'none';
		}
	},
	
	getSize : function() {
		if(this._inDocument) {
			return {
				width : this._element.offsetWidth,
				height : this._element.offsetHeight
			};
		}
	},

	dispose : function() {
		kampfer.Component.superClass.dispose.apply(this);
	}
	
});
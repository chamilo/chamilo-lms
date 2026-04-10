
var imgMapSrc= "img/map.svg";
var plancodehtml = "";
var planlayerInfo = "";
var planlayerZoom = "";

var opt1 = 4.5;
var opt2 = 1;
var opt3 = 5;
var opt4 = detectAndroidorIphone()
var opt5 = 1.7;
var opt6 = 1;

var hashMapSrc = window.location.hash;
if(hashMapSrc!=''){
	imgMapSrc = hashMapSrc.replace('#','');
	imgMapSrc = decodeURI(imgMapSrc);
}

document.addEventListener('DOMContentLoaded', function () {
	
	var planview = '../../' + imgMapSrc;

	prepareHtmlLayer();

	document.getElementById('planview').src = planview;
	document.getElementById('planview').setAttribute('draggable', false);

	// var tip = document.querySelector('[data-tip]');

	var minScaleReached = true;
	var maxScaleReached = false;
	
	if (opt4) {
		opt1 = 3;
		opt2 = 2;
		opt3 = 5;
		opt5 = 1.8;
		opt6 = 0.8;
	}

	planlayerZoom = WZoom.create('#planview', {
		minScale : opt6,
		maxScale : opt1,
		currentScale : opt2,
		speed : opt3,
		rescale: function (instance) {

			var minScale = instance.content.minScale;
			var currentScale = instance.content.currentScale;
			var maxScale = instance.content.maxScale;
			
			if (currentScale === minScale) {
				minScaleReached = true;
				if (document.getElementById('layerInfos')&&opt4==false) {
					planlayerInfo.style.display = 'none';
				}
				if (onAlterView==1) {
					closeAlterToScr();
				}
			} else if (currentScale > maxScale - opt5) {
					minScaleReached = false;
					maxScaleReached = true;
					if (document.getElementById('layerInfos')) {
						setTimeout(function(){
							planlayerInfo.style.display = 'block';
						},300);
					}
			} else if (currentScale > 1.2) {
					minScaleReached = false;
					maxScaleReached = false;
			} else {
				minScaleReached = false;
				maxScaleReached = false;
			}

		}
	
	});

	setTimeout(function(){
		let layerInfos = document.createElement('div');
		layerInfos.id = 'layerInfos';
		layerInfos.style.position = 'absolute';
		layerInfos.style.border = '1px solid red';
		document.getElementById('myViewport').append(layerInfos);
		planlayerInfo = document.getElementById('layerInfos');
		planlayerInfo.style.display = 'none';
		planlayerInfo.style['pointer-events']= 'none';
		planlayerInfo.innerHTML = plancodehtml;
		copyCatLayer();
		if (opt4) {
			planlayerZoom._zoom(0.8);
		}
	},450);

});

function detectAndroidorIphone() {
	var mobile = false;
	var ua = navigator.userAgent.toLowerCase();
	var isAndroid = ua.indexOf("android") > -1;
	var isIphone = ua.indexOf("iphone") > -1;

	// detect screen size smartphone
	var screenWidth = window.innerWidth;
	var screenHeight = window.innerHeight;

	if (screenHeight>screenWidth&&screenWidth<768) {
		mobile = true;
	}
	if (isAndroid) {
		mobile = true;
	}
	if (isIphone) {
		mobile = true;
	}
	return mobile;
}

function copyCatLayer() {

	var imgObj = document.getElementById('planview');
	var svgObjWidth = parseInt(imgObj.width);
	var svgObjHeight = parseInt(imgObj.height);
	if (document.getElementById('layerInfos')) {
		planlayerInfo.style.width = svgObjWidth + 'px';
		planlayerInfo.style.height = svgObjHeight + 'px';
		planlayerInfo.style['transition']= imgObj.style['transition'];
		planlayerInfo.style['transform']= imgObj.style['transform'];
	}
	setTimeout(function(){
		copyCatLayer()
	},300);
}

function prepareHtmlLayer() {

	var planviewhtml = '../../' + imgMapSrc.replace('.svg','.html');
	var request = new XMLHttpRequest();
	request.open('GET',planviewhtml,true);
	request.onload = function() {
		if (request.status >= 200 && request.status < 400) {
			var resp = request.responseText;
			plancodehtml = resp;
			plancodehtml += '<div style="pointer-events:auto!important;position:absolute;cursor:pointer!important;" id="infosCloseAlter" class="infosCloseAlter" onClick="closeAlterToScr()" ></div>';
		} else {
			plancodehtml = "";
		}
	};
	request.send();

}

var topPourcCloseView = 0;
var widthPourcView = 0;
var onAlterView = 0;

function mapEventActive(idMap,leftPourc,topPourc) {

	parent.mapEventGlobal(idMap,leftPourc,topPourc);

	if (idMap=='map-alterimg') {
		onAlterView = 1;
		topPourcCloseView = topPourc;
		
		var infosCloseAlter = document.getElementById('infosCloseAlter');
		infosCloseAlter.style.left = leftPourc + '%';
		infosCloseAlter.style.top = topPourcCloseView + '%';
		infosCloseAlter.style.width = '10px';
		infosCloseAlter.style.height ='10px';

		widthPourcView = 40;
		if (GlobalScale>1) {
			widthPourcView = 42;
		}
		if (GlobalScale>1.2) {
			widthPourcView = 36;
		}
		if (GlobalScale>1.4) {
			widthPourcView = 24;
		}

		infosCloseAlter.style.display = 'block';

		if (topPourcCloseView < 90) {
			setTimeout(function(){
				var infosCloseAlter = document.getElementById('infosCloseAlter');
				
				infosCloseAlter.style.top = (topPourcCloseView + 10) + '%';
				infosCloseAlter.style.width = widthPourcView + 'px';
				infosCloseAlter.style.height= widthPourcView + 'px';

			},50);
		}
	}

}

function closeAlterToScr(){
	parent.closeAlterToScr();
	var infosCloseAlter = document.getElementById('infosCloseAlter');
	infosCloseAlter.style.display = 'none';
	onAlterView = 0;
}
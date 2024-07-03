/*!
 * MediaElement.js
 * http://www.mediaelementjs.com/
 *
 * Wrapper that mimics native HTML5 MediaElement (audio and video)
 * using a variety of technologies (pure JavaScript, Flash, iframe)
 *
 * Copyright 2010-2017, John Dyer (http://j.hn/)
 * License: MIT
 *
 */(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(_dereq_,module,exports){
'use strict';

Object.assign(mejs.MepDefaults, {
	markersRollsColor: '#E9BC3D',

	markersRollsWidth: 1,

	markersRolls: {}
});

Object.assign(MediaElementPlayer.prototype, {
	buildmarkersrolls: function buildmarkersrolls(player, controls, layers, media) {
		var _player$options = player.options,
		    markersRollsColor = _player$options.markersRollsColor,
		    markersRollsWidth = _player$options.markersRollsWidth,
		    markersRolls = _player$options.markersRolls,
		    classPrefix = _player$options.classPrefix;

		var controlsTotalTime = controls.querySelector('.' + classPrefix + 'time-total');

		var currentPosition = -1,
		    lastPlayedPosition = -1,
		    lastMarkerRollCallback = -1,
		    markersAreRendered = false;

		var markersCount = Object.keys(markersRolls).length;

		if (!markersCount) {
			return;
		}

		function createIframeLayer() {
			var layer = document.createElement('iframe');

			layer.frameBorder = '0';
			layer.className = classPrefix + 'markersrolls-layer ' + classPrefix + 'overlay ' + classPrefix + 'layer';
			layer.style.display = 'none';
			layer.style.backgroundColor = '#9F9F9F';
			layer.style.border = '0 none';
			layer.style.boxShadow = '#B0B0B0 0px 0px 20px -10px inset';
			layer.style.paddingBottom = '40px';

			return layer;
		}

		function createMarker(_ref) {
			var markerPosition = _ref.markerPosition,
			    duration = _ref.duration;

			var marker = document.createElement('span');

			marker.className = classPrefix + 'time-marker';
			marker.style.width = markersRollsWidth + 'px';
			marker.style.left = 100 * markerPosition / duration + '%';
			marker.style.background = markersRollsColor;

			return marker;
		}

		var markersRollsLayer = createIframeLayer();

		layers.appendChild(markersRollsLayer);

		function tryRenderMarkers() {
			if (markersAreRendered) {
				return;
			}

			var duration = media.getDuration();

			if (!duration) {
				return;
			}

			for (var markerPosition in markersRolls) {
				if (!markersRolls.hasOwnProperty(markerPosition)) {
					continue;
				}

				markerPosition = parseInt(markerPosition);

				if (markerPosition >= duration || markerPosition < 0) {
					continue;
				}

				var marker = createMarker({
					markerPosition: markerPosition,
					duration: duration
				});

				controlsTotalTime.appendChild(marker);
			}

			markersAreRendered = true;
		}

		player.markersRollsLoadedMetadata = function () {
			tryRenderMarkers();
		};
		player.markersRollsTimeUpdate = function () {
			currentPosition = Math.floor(media.currentTime);

			if (lastPlayedPosition > currentPosition) {
				if (lastMarkerRollCallback > currentPosition) {
					lastMarkerRollCallback = -1;
				}
			} else {
				lastPlayedPosition = currentPosition;
			}

			if (0 === markersCount || !markersRolls[currentPosition] || currentPosition === lastMarkerRollCallback) {
				return;
			}

			lastMarkerRollCallback = currentPosition;

			media.pause();

			markersRollsLayer.src = markersRolls[currentPosition];
			markersRollsLayer.style.display = 'block';
		};
		player.markersRollsPlay = function () {
			tryRenderMarkers();

			markersRollsLayer.style.display = 'none';
			markersRollsLayer.src = '';
		};

		media.addEventListener('loadedmetadata', player.markersRollsLoadedMetadata);
		media.addEventListener('timeupdate', player.markersRollsTimeUpdate);
		media.addEventListener('play', player.markersRollsPlay);
	},
	cleanmarkersrolls: function cleanmarkersrolls(player, controls, layers, media) {
		media.removeEventListener('loadedmetadata', player.markersRollsLoadedMetadata);
		media.removeEventListener('timeupdate', player.markersRollsTimeUpdate);
		media.removeEventListener('play', player.markersRollsPlay);
	}
});

},{}]},{},[1]);

var Wami = window.Wami || {};

// Upon a creation of a new Wami.GUI(options), we assume that a WAMI recorder
// has been initialized.
Wami.GUI = function(options) {
	var RECORD_BUTTON = 1;
	var PLAY_BUTTON = 2;

	setOptions(options);
	setupDOM();

	var recordButton, playButton;
	var recordInterval, playInterval;

	function createDiv(id, style) {
		var div = document.createElement("div");
		if (id) {
			div.setAttribute('id', id);
		}
		if (style) {
			div.style.cssText = style;
		}
		return div;
	}

	function setOptions(options) {
		if (!options.buttonUrl) {
			options.buttonUrl = "buttons.png";
		}

		if (typeof options.listen == 'undefined' || options.listen) {
			listen();
		}
	}

	function setupDOM() {
		var guidiv = createDiv(null,
				"position: absolute; width: 214px; height: 137px;");
		document.getElementById(options.id).appendChild(guidiv);

		var rid = Wami.createID();
		var recordDiv = createDiv(rid,
				"position: absolute; left: 40px; top: 25px");
		guidiv.appendChild(recordDiv);

		recordButton = new Button(rid, RECORD_BUTTON, options.buttonUrl);
		recordButton.onstart = startRecording;
		recordButton.onstop = stopRecording;

		recordButton.setEnabled(true);
		
//Chamilo hack single button
		var pid = Wami.createID();
		var playDiv = createDiv(pid,
					"position: absolute; right: 40px; top: 25px");
			guidiv.appendChild(playDiv);
		if (!options.singleButton) {
			playButton = new Button(pid, PLAY_BUTTON, options.buttonUrl);
		} else {
			playButton = new Button(pid, PLAY_BUTTON, options.buttonNoUrl);
		}
		
			playButton.onstart = startPlaying;
			playButton.onstop = stopPlaying;
	}
//end hack single button

	/**
	 * These methods are called on clicks from the GUI.
	 */
	function startRecording() {
		if (!options.recordUrl) {
			alert("No record Url specified!");
		}
		recordButton.setActivity(0);
		playButton.setEnabled(false);
		Wami.startRecording(options.recordUrl,
				Wami.nameCallback(onRecordStart), Wami
						.nameCallback(onRecordFinish), Wami
						.nameCallback(onError));
	}

	function stopRecording() {
		Wami.stopRecording();
		clearInterval(recordInterval);
		recordButton.setEnabled(true);
	}

	function startPlaying() {
		if (!options.playUrl) {
			alert('No play URL specified!');
		}

		playButton.setActivity(0);
		recordButton.setEnabled(false);

		Wami.startPlaying(options.playUrl, Wami.nameCallback(onPlayStart), Wami
				.nameCallback(onPlayFinish), Wami.nameCallback(onError));
	}

	function stopPlaying() {
		Wami.stopPlaying();
	}

	this.setPlayUrl = function(url) {
		options.playUrl = url;
	}

	this.setRecordUrl = function(url) {
		options.recordUrl = url;
	}

	this.setPlayEnabled = function(val) {
		playButton.setEnabled(val);
	}

	this.setRecordEnabled = function(val) {
		recordButton.setEnabled(val);
	}

	/**
	 * Callbacks from the flash indicating certain events
	 */

	function onError(e) {
		alert(e);
	}

	function onRecordStart() {
		recordInterval = setInterval(function() {
			if (recordButton.isActive()) {
				var level = Wami.getRecordingLevel();
				recordButton.setActivity(level);
			}
		}, 200);
		if (options.onRecordStart) {
			options.onRecordStart();
		}
	}

	function onRecordFinish() {
		playButton.setEnabled(true);
		if (options.onRecordFinish) {
			options.onRecordFinish();
		}
	}

	function onPlayStart() {
		playInterval = setInterval(function() {
			if (playButton.isActive()) {
				var level = Wami.getPlayingLevel();
				playButton.setActivity(level);
			}
		}, 200);
		if (options.onPlayStart) {
			options.onPlayStart();
		}
	}

	function onPlayFinish() {
		clearInterval(playInterval);
		recordButton.setEnabled(true);
		playButton.setEnabled(true);
		if (options.onPlayFinish) {
			options.onPlayFinish();
		}
	}

	function listen() {
		Wami.startListening();
		// Continually listening when the window is in focus allows us to
		// buffer a little audio before the users clicks, since sometimes
		// people talk too soon. Without "listening", the audio would record
		// exactly when startRecording() is called.
		window.onfocus = function() {
			Wami.startListening();
		};

		// Note that the use of onfocus and onblur should probably be replaced
		// with a more robust solution (e.g. jQuery's $(window).focus(...)
		window.onblur = function() {
			Wami.stopListening();
		};
	}

	function Button(buttonid, type, url) {
		var self = this;
		self.active = false;
		self.type = type;

		init();

		// Get the background button image position
		// Index: 1) normal 2) pressed 3) mouse-over
		function background(index) {
			if (index == 1)
				return "-56px 0px";
			if (index == 2)
				return "0px 0px";
			if (index == 3)
				return "-112px 0";
			alert("Background not found: " + index);
		}

		// Get the type of meter and its state
		// Index: 1) enabled 2) meter 3) disabled
		function meter(index, offset) {
			var top = 5;
			if (offset)
				top += offset;
			if (self.type == RECORD_BUTTON) {
				if (index == 1)
					return "-169px " + top + "px";
				if (index == 2)
					return "-189px " + top + "px";
				if (index == 3)
					return "-249px " + top + "px";
			} else {
				if (index == 1)
					return "-269px " + top + "px";
				if (index == 2)
					return "-298px " + top + "px";
				if (index == 3)
					return "-327px " + top + "px";
			}
			alert("Meter not found: " + self.type + " " + index);
		}

		function silhouetteWidth() {
			if (self.type == RECORD_BUTTON) {
				return "20px";
			} else {
				return "29px";
			}
		}

		function mouseHandler(e) {
			var rightclick;
			if (!e)
				var e = window.event;
			if (e.which)
				rightclick = (e.which == 3);
			else if (e.button)
				rightclick = (e.button == 2);

			if (!rightclick) {
				if (self.active && self.onstop) {
					self.active = false;
					self.onstop();
				} else if (!self.active && self.onstart) {
					self.active = true;
					self.onstart();
				}
			}
		}

		function init() {
			var div = document.createElement("div");
			var elem = document.getElementById(buttonid);
			if (elem) {
				elem.appendChild(div);
			} else {
				alert('Could not find element on page named ' + buttonid);
			}

			self.guidiv = document.createElement("div");
			self.guidiv.style.width = '56px';
			self.guidiv.style.height = '63px';
			self.guidiv.style.cursor = 'pointer';
			self.guidiv.style.background = "url(" + url + ") no-repeat";
			self.guidiv.style.backgroundPosition = background(1);
			div.appendChild(self.guidiv);

			// margin auto doesn't work in IE quirks mode
			// http://stackoverflow.com/questions/816343/why-will-this-div-img-not-center-in-ie8
			// text-align is a hack to force it to work even if you forget the
			// doctype.
			self.guidiv.style.textAlign = 'center';

			self.meterDiv = document.createElement("div");
			self.meterDiv.style.width = silhouetteWidth();
			self.meterDiv.style.height = '63px';
			self.meterDiv.style.margin = 'auto';
			self.meterDiv.style.cursor = 'pointer';
			self.meterDiv.style.position = 'relative';
			self.meterDiv.style.background = "url(" + url + ") no-repeat";
			self.meterDiv.style.backgroundPosition = meter(2);
			self.guidiv.appendChild(self.meterDiv);

			self.coverDiv = document.createElement("div");
			self.coverDiv.style.width = silhouetteWidth();
			self.coverDiv.style.height = '63px';
			self.coverDiv.style.margin = 'auto';
			self.coverDiv.style.cursor = 'pointer';
			self.coverDiv.style.position = 'relative';
			self.coverDiv.style.background = "url(" + url + ") no-repeat";
			self.coverDiv.style.backgroundPosition = meter(1);
			self.meterDiv.appendChild(self.coverDiv);

			self.active = false;
			self.guidiv.onmousedown = mouseHandler;
		}

		self.isActive = function() {
			return self.active;
		}

		self.setActivity = function(level) {
			self.guidiv.onmouseout = function() {
			};
			self.guidiv.onmouseover = function() {
			};
			self.guidiv.style.backgroundPosition = background(2);
			self.coverDiv.style.backgroundPosition = meter(1, 5);
			self.meterDiv.style.backgroundPosition = meter(2, 5);

			var totalHeight = 31;
			var maxHeight = 9;

			// When volume goes up, the black image loses height,
			// creating the perception of the colored one increasing.
			var height = (maxHeight + totalHeight - Math.floor(level / 100
					* totalHeight));
			self.coverDiv.style.height = height + "px";
		}

		self.setEnabled = function(enable) {
			var guidiv = self.guidiv;
			self.active = false;
			if (enable) {
				self.coverDiv.style.backgroundPosition = meter(1);
				self.meterDiv.style.backgroundPosition = meter(1);
				guidiv.style.backgroundPosition = background(1);
				guidiv.onmousedown = mouseHandler;
				guidiv.onmouseover = function() {
					guidiv.style.backgroundPosition = background(3);
				};
				guidiv.onmouseout = function() {
					guidiv.style.backgroundPosition = background(1);
				};
			} else {
				self.coverDiv.style.backgroundPosition = meter(3);
				self.meterDiv.style.backgroundPosition = meter(3);
				guidiv.style.backgroundPosition = background(1);
				guidiv.onmousedown = null;
				guidiv.onmouseout = function() {
				};
				guidiv.onmouseover = function() {
				};
			}
		}
	}
}

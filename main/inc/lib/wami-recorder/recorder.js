var Wami = window.Wami || {};

// Returns a (very likely) unique string with of random letters and numbers
Wami.createID = function() {
	return "wid" + ("" + 1e10).replace(/[018]/g, function(a) {
		return (a ^ Math.random() * 16 >> a / 4).toString(16)
	});
}

// Creates a named callback in WAMI and returns the name as a string.
Wami.nameCallback = function(cb, cleanup) {
	Wami._callbacks = Wami._callbacks || {};
	var id = Wami.createID();
	Wami._callbacks[id] = function() {
		if (cleanup) {
			Wami._callbacks[id] = null;
		}
		cb.apply(null, arguments);
	};
	var named = "Wami._callbacks['" + id + "']";
	return named;
}

// This method ensures that a WAMI recorder is operational, and that
// the following API is available in the Wami namespace. All functions
// must be named (i.e. cannot be anonymous).
//
// Wami.startPlaying(url, startfn = null, finishedfn = null, failedfn = null);
// Wami.stopPlaying()
//
// Wami.startRecording(url, startfn = null, finishedfn = null, failedfn = null);
// Wami.stopRecording()
//
// Wami.getRecordingLevel() // Returns a number between 0 and 100
// Wami.getPlayingLevel() // Returns a number between 0 and 100
//
// Wami.hide()
// Wami.show()
//
// Manipulate the WAMI recorder's settings. In Flash
// we need to check if the microphone permission has been granted.
// We might also set/return sample rate here, etc.
//
// Wami.getSettings();
// Wami.setSettings(options);
//
// Optional way to set up browser so that it's constantly listening
// This is to prepend audio in case the user starts talking before
// they click-to-talk.
//
// Wami.startListening()
//
Wami.setup = function(options) {
	if (Wami.startRecording) {
		// Wami's already defined.
		if (options.onReady) {
			options.onReady();
		}
		return;
	}

	// Assumes that swfobject.js is included if Wami.swfobject isn't
	// already defined.
	Wami.swfobject = Wami.swfobject || swfobject;

	if (!Wami.swfobject) {
		alert("Unable to find swfobject to help embed the SWF.");
	}

	var _options;
	setOptions(options);
	embedWamiSWF(_options.id, Wami.nameCallback(delegateWamiAPI));

	function supportsTransparency() {
		// Detecting the OS is a big no-no in Javascript programming, but
		// I can't think of a better way to know if wmode is supported or
		// not... since NOT supporting it (like Flash on Ubuntu) is a bug.
		//return (navigator.platform.indexOf("Linux") == -1);
        // Chamilo change
        return true;
	}

	function setOptions(options) {
		// Start with default options
		_options = {
			swfUrl : "Wami.swf",
			onReady : function() {
				Wami.hide();
			},
			onSecurity : checkSecurity,
			onError : function(error) {
				alert(error);
			}
		};

		if (typeof options == 'undefined') {
			alert('Need at least an element ID to place the Flash object.');
		}

		if (typeof options == 'string') {
			_options.id = options;
		} else {
			_options.id = options.id;
		}

		if (options.swfUrl) {
			_options.swfUrl = options.swfUrl;
		}

		if (options.onReady) {
			_options.onReady = options.onReady;
		}

		if (options.onLoaded) {
			_options.onLoaded = options.onLoaded;
		}

		if (options.onSecurity) {
			_options.onSecurity = options.onSecurity;
		}

		if (options.onError) {
			_options.onError = options.onError;
		}

		// Create a DIV for the SWF under _options.id

		var container = document.createElement('div');
		container.style.position = 'absolute';
        container.style.marginLeft = '-107px';
        container.style.left = '50%';
        _options.cid = Wami.createID();
		container.setAttribute('id', _options.cid);

		var swfdiv = document.createElement('div');
		var id = Wami.createID();
		swfdiv.setAttribute('id', id);

		container.appendChild(swfdiv);
		document.getElementById(_options.id).appendChild(container);

		_options.id = id;
	}

	function checkSecurity() {
		var settings = Wami.getSettings();
		if (settings.microphone.granted) {
			_options.onReady();
		} else {
			// Show any Flash settings panel you want:
			// http://help.adobe.com/en_US/FlashPlatform/reference/actionscript/3/flash/system/SecurityPanel.html
			Wami.showSecurity("privacy", "Wami.show", Wami
					.nameCallback(_options.onSecurity), Wami
					.nameCallback(_options.onError));
		}
	}

	// Embed the WAMI SWF and call the named callback function when loaded.
	function embedWamiSWF(id, initfn) {
		var flashVars = {
			visible : false,
			loadedCallback : initfn
		}

		var params = {
			allowScriptAccess : "always",
			wmode: 'transparent'
		}

		if (supportsTransparency()) {
			params.wmode = "transparent";
		}

		if (typeof console !== 'undefined') {
			flashVars.console = true;
		}

		var version = '10.0.0';
		document.getElementById(id).innerHTML = "WAMI requires Flash "
				+ version
				+ " or greater<br />https://get.adobe.com/flashplayer/";

		// This is the minimum size due to the microphone security panel
		Wami.swfobject.embedSWF(_options.swfUrl, id, 214, 137, version, null,
				flashVars, params);

		// Without this line, Firefox has a dotted outline of the flash
		Wami.swfobject.createCSS("#" + id, "outline:none");
	}

	// To check if the microphone settings were 'remembered', we
	// must actually embed an entirely new Wami client and check
	// whether its microphone is granted. If it is, it was remembered.
	function checkRemembered(finishedfn) {
		var id = Wami.createID();
		var div = document.createElement('div');
		div.style.top = '-999px';
		div.style.left = '-999px';
		div.setAttribute('id', id);
		var body = document.getElementsByTagName('body').item(0);
		body.appendChild(div);

		var fn = Wami.nameCallback(function() {
			var swf = document.getElementById(id);
			Wami._remembered = swf.getSettings().microphone.granted;
			Wami.swfobject.removeSWF(id);
			eval(finishedfn + "()");
		});

		embedWamiSWF(id, fn);
	}

	// Attach all the audio methods to the Wami namespace in the callback.
	function delegateWamiAPI() {
		var recorder = document.getElementById(_options.id);

		function delegate(name) {
			Wami[name] = function() {
				return recorder[name].apply(recorder, arguments);
			}
		}
		delegate('startPlaying');
		delegate('stopPlaying');
		delegate('startRecording');
		delegate('stopRecording');
		delegate('startListening');
		delegate('stopListening');
		delegate('getRecordingLevel');
		delegate('getPlayingLevel');
		delegate('setSettings');

		// Append extra information about whether mic settings are sticky
		Wami.getSettings = function() {
			var settings = recorder.getSettings();
			settings.microphone.remembered = Wami._remembered;
			return settings;
		}

		Wami.showSecurity = function(panel, startfn, finishedfn, failfn) {
			// Flash must be on top for this.
			var container = document.getElementById(_options.cid);

			var augmentedfn = Wami.nameCallback(function() {
				checkRemembered(finishedfn);
                container.style.cssText = "position: absolute; left:50%; margin-left:-107px";
			});

			container.style.cssText = "position: absolute; z-index: 99999";

			recorder.showSecurity(panel, startfn, augmentedfn, failfn);
		}

		Wami.show = function() {
			if (!supportsTransparency()) {
				recorder.style.visibility = "visible";
			}
		}

		Wami.hide = function() {
			// Hiding flash in all the browsers is tricky. Please read:
			// https://code.google.com/p/wami-recorder/wiki/HidingFlash
			if (!supportsTransparency()) {
				recorder.style.visibility = "hidden";
			}
		}

		// If we already have permissions, they were previously 'remembered'
		Wami._remembered = recorder.getSettings().microphone.granted;

		if (_options.onLoaded) {
			_options.onLoaded();
		}

		if (!_options.noSecurityCheck) {
			checkSecurity();
		}
	}
}

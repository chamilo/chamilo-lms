var animationName;
var transformName;
var perspectiveName;
var animationStartName;
var animationIterationName;
var animationEndName;

var BringToViewAnimations = ["rotateInLeft", "fadeIn", "whirlIn", "fallFromTop", "slideInSkew", "tumbleIn", "expandIn"];
var RemoveFromViewAnimations = ["rotateOutRight", "fadeOut", "whirlOut", "slideOutSkew", "tumbleOut"];

// Helper for adding an event listener to an element
function addListener(obj, eventName, listener, capture) {
    if (obj.addEventListener) {
        obj.addEventListener(eventName, listener, capture);
    } else {
        obj.attachEvent("on" + eventName, listener, capture);
    }
}

// Simple function used to detect support for properties from a list of strings
var FirstSupportedPropertyName = function(prefixedPropertyNames) {
    var tempDiv = document.createElement("div");
    for (var i = 0; i < prefixedPropertyNames.length; ++i) {
        if (typeof tempDiv.style[prefixedPropertyNames[i]] != 'undefined')
            return prefixedPropertyNames[i];
    }

    return null;
};

var VerifyTransformAnimationSupport = function() {
    if ((animationName != null) && (transformName != null)) {
        return true;
    }
    return false;
};

// Fetches CSS files from the webserver and returns the plaintext
var XHRCSSFiles = function() {
    var request = new XMLHttpRequest();
    request.open("GET", "../css/FullPageAnimationsPrefixed.css", false);
    request.send("");

    return request.responseText;
};

function injectCSS(cssString) {
    var ele = document.createElement("style");
    ele.type = "text/css";
    if (ele.styleSheet) {
        ele.styleSheet.cssText = cssString;
    } else {
        ele.appendChild(document.createTextNode(cssString));
    }
    document.getElementsByTagName("head")[0].appendChild(ele);
}


// Since CSS Animations and Transforms are not always supported in their unprefixed form, we have to perform some feature detection
var DetectPrefixes = function() {

    // First we figure out the attribute names for usage with bracket style attribute access notation
    transformName = FirstSupportedPropertyName(["transform", "msTransform", "MozTransform", "WebkitTransform", "OTransform"]);
    animationName = FirstSupportedPropertyName(["animation", "msAnimation", "MozAnimation", "WebkitAnimation", "OAnimation"]);
    perspectiveName = FirstSupportedPropertyName(["perspective", "msPerspective", "MozPerspective", "WebkitPerspective", "OPerspective"]);
    // The event names are a bit more tricky to handle due to capitalization
    animationEndName = (animationName + "End").replace(/^ms/, "MS").replace(/^Webkit/, "webkit").replace(/^Moz.*/, "animationend").replace(/^animationEnd$/, "animationend");
    animationStartName = (animationName + "Start").replace(/^ms/, "MS").replace(/^Webkit/, "webkit").replace(/^Moz.*/, "animationstart").replace(/^animationStart$/, "animationstart");
    animationIterationName = (animationName + "Iteration").replace(/^ms/, "MS").replace(/^Webkit/, "webkit").replace(/^Moz.*/, "animationiteration").replace(/^animationIteration/, "animationiteration");

    // We also have some declarative markup that we need to patch up (@keyframes rules and various CSS used in our Test Drive Demo)
    var prefix = "";
    // First we detect the proper prefix
    if (animationName == "msAnimation") {
        prefix = "-ms-";
    } else if (animationName == "MozAnimation") {
        prefix = "-moz-";
    } else if (animationName == "WebkitAnimation") {
        prefix = "-webkit-";
    } else if (animationName == "OAnimation") {
        prefix = "-o-";
    }
    // Then we fetch the CSS files (that have been composed using the -ms- prefix)
    var CSSFileString = XHRCSSFiles();
    // Following we do a simple String.replace of -ms- with the actual vendor prefix
    CSSFileString = CSSFileString.replace(/-ms-/gi, prefix);
    // And finally we inject the CSS
    injectCSS(CSSFileString);
};

var ApplyAnimationToElement = function(element, animName) {
    if (element.style[animationName + "Name"] == animName) {
        // If we are reapplying an animation, we need to zero out the value and then reset the property after the function returns.
        element.style[animationName + "Name"] = "";
        setTimeout(function() { element.style[animationName + "Name"] = animName; });
    } else {
        element.style[animationName + "Name"] = animName;
    }
};

var SetupAnimationParameters = function(element) {
    element.style[animationName + "Delay"] = "0.0s";
    element.style[animationName + "Duration"] = "1s";
    element.style[animationName + "IterationCount"] = "1";
    // Setting animation-fill-mode to "forwards" will preserve the to{} keyframe values after the animation
    // is complete. As a result, we do not have to inject a transform on the body element to maintain the post-animation position
    element.style[animationName + "FillMode"] = "forwards";
    element.style[animationName + "TimingFunction"] = "linear";
    element.style[animationName + "PlayState"] = "running";
};

var SetupBodyBringToViewAnimation = function(animName) {
    if (!VerifyTransformAnimationSupport()) return;
    //document.body.style.visibility = "visible";
    if (!animName) {
        animName = GetRandomAnimation(BringToViewAnimations);
    }
    //SetupProjectionOrigin();
    SetupAnimationParameters(document.body);
    ApplyAnimationToElement(document.body, animName);
};

var AnimationEndCallback = function(action) {
    window.location.href = action;
};

var TriggerBodyRemoveFromViewAnimation = function(animName, action) {
    if (!VerifyTransformAnimationSupport()) {
        AnimationEndCallback(action);
        return;
    }
    var ele = document.body;

    // first we add a listener for the animationend event so we can perform a navigation once the transition is complete
    addListener(ele, animationEndName, function() { AnimationEndCallback(action); });

    // If you are not using the CSS wrapper pattern described in our article you may want to uncomment this
    // in order to mitigate visual skewing from perspective projection
    //SetupProjectionOrigin();
    SetupAnimationParameters(document.body);
    ApplyAnimationToElement(ele, animName);
};

DetectPrefixes();
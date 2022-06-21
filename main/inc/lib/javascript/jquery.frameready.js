/**
 * @param {function} callback
 * @param {string} target
 * @param {Array} resources
 * @param {function} conditional
 * @constructor
 */
$.frameReady = function (callback, targetSelector, resources, conditional) {
    /**
     * @type {window}
     */
    var targetWindow = document.querySelector(targetSelector);
    /**
     * @type {Document}
     */
    var targetDocument = null;

    var scripts = resources.filter(function (resource) {
        return resource.type === 'script';
    });
    var stylesheets = resources.filter(function (resource) {
        return resource.type === 'stylesheet';
    });

    var scriptsCount = (function () {
        var count = 0;

        /**
         * @param {Object} parentScript
         */
        function countScripts(parentScript) {
            count++;

            if (!parentScript.hasOwnProperty('deps')) {
                return;
            }

            parentScript.deps.forEach(countScripts);
        }

        scripts.forEach(countScripts);

        return count;
    })();
    var scripsLoadedCount = 0;

    targetWindow.onload = function () {
        scripsLoadedCount = 0;

        if (typeof conditional === 'function' && conditional()) {
            return;
        }

        targetDocument = targetWindow.contentDocument;

        if (!targetDocument) {
            console.log('frameReady: Can\'t access to contentDocument.');
            return;
        }

        scripts.forEach(function (script) {
            createScript(script);
        });

        stylesheets.forEach(function (stylesheet) {
            createStylesheet(stylesheet);
        });
    };

    /**
     * @param {Object} script
     */
    function createScript(script) {
        /**
         * @type {HTMLScriptElement}
         */
        var elParent = targetWindow.contentDocument.createElement('script');
        elParent.async = false;
        elParent.onload = function () {
            scripsLoadedCount++;

            if (!script.hasOwnProperty('deps')) {
                tryExecuteCallback();

                return;
            }

            script.deps.forEach(function (scriptB) {
                createScript(scriptB);
            });
        };
        elParent.setAttribute('src', script.src);

        targetDocument.body.appendChild(elParent);
    }

    /**
     * @param {Object} stylesheet
     */
    function createStylesheet(stylesheet) {
        /**
         * @type {HTMLLinkElement}
         */
        var el = targetWindow.contentDocument.createElement('link');
        el.setAttribute('href', stylesheet.src);
        el.setAttribute('rel', "stylesheet");
        el.setAttribute('type', "text/css");
        if (targetDocument.head) {
            targetDocument.head.appendChild(el);
        }
    }

    function tryExecuteCallback() {
        if (scripsLoadedCount < scriptsCount) {
            return;
        }

        targetWindow.contentWindow.eval('(' + callback.toString() + ')();');
    }
};

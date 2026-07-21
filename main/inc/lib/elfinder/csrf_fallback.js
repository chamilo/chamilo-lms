/* For licensing terms, see /license.txt */

/**
 * elFinder's CSRF protection sends its token as the "X-elFinder-CSRF"
 * request header. Some reverse proxies/gateways in front of a Chamilo
 * portal (VPN web-rewriting proxies, WAFs...) strip custom headers while
 * always forwarding request params untouched, which makes every
 * CSRF-protected command (upload, rm, rename...) fail permanently.
 *
 * This wraps the public $.fn.elfinder entry point (not vendor/studio-42
 * internals, which composer can overwrite at any time) so the token also
 * rides along as a normal request param, matching the fallback added
 * server-side in Chamilo\CoreBundle\Component\Editor\ElFinderConnector.
 *
 * Must be loaded after elfinder.full.js and before any .elfinder({...}) call.
 */
(function ($) {
    if (!$ || !$.fn || typeof $.fn.elfinder !== 'function' || $.fn.elfinder.__chamiloCsrfParamPatched) {
        return;
    }

    var originalPlugin = $.fn.elfinder;
    var csrfParamName = '_csrf';

    function patchInstance(instance) {
        if (!instance || instance.__chamiloCsrfParamPatched || typeof instance.setCsrfToken !== 'function') {
            return;
        }
        instance.__chamiloCsrfParamPatched = true;

        var originalSetCsrfToken = instance.setCsrfToken;
        instance.setCsrfToken = function (token) {
            originalSetCsrfToken(token);
            if (typeof token === 'string' && token) {
                instance.customData[csrfParamName] = token;
            } else {
                delete instance.customData[csrfParamName];
            }
        };
    }

    $.fn.elfinder = function (o, o2) {
        var result = originalPlugin.apply(this, arguments);

        if (o === 'instance') {
            patchInstance(result);
            return result;
        }

        this.each(function () {
            if (this.elfinder) {
                patchInstance(this.elfinder);
            }
        });

        return result;
    };

    $.fn.elfinder.__chamiloCsrfParamPatched = true;
})(jQuery);

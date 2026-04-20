;(function () {
    'use strict'

    var _translations = {}

    // Auto-detect the plugin root URL from this script's own src attribute.
    // Works whether the file is served from editor/jscss/, options/js/, or
    // any other subdirectory, as long as the URL contains /plugin/CStudio/.
    var _marker = '/plugin/CStudio/'
    var _src = (document.currentScript || {}).src || ''
    var _idx = _src.indexOf(_marker)
    var _root = _idx !== -1 ? _src.slice(0, _idx + _marker.length) : '/plugin/CStudio/'

    /**
     * Look up a UI string in the loaded translation table.
     * Falls back to the original English key when no translation is found.
     *
     * @param {string} txt - English source string
     * @returns {string}
     */
    function returnTradTerm(txt) {
        return Object.prototype.hasOwnProperty.call(_translations, txt) ? _translations[txt] : txt
    }

    /**
     * Fetch the per-language JSON file and call the callback when done.
     * Falls back to en.json if the requested language file is missing.
     * The callback is always called, even when both fetches fail.
     *
     * @param {string}   langCode - Chamilo locale code matching a file in lang/json/ (e.g. 'fr_FR')
     * @param {Function} callback - called with no arguments once translations are ready
     */
    function cstudioI18nInit(langCode, callback) {
        var lang = langCode || 'en_US'
        var url  = _root + 'lang/json/' + lang + '.json'

        fetch(url)
            .then(function (r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status) }
                return r.json()
            })
            .then(function (data) {
                _translations = data
                if (callback) { callback() }
            })
            .catch(function () {
                if (lang === 'en_US') {
                    // Nothing more to try — run callback with empty table (identity fallback)
                    if (callback) { callback() }
                    return
                }
                // Try English as fallback
                fetch(_root + 'lang/json/en_US.json')
                    .then(function (r) { return r.ok ? r.json() : {} })
                    .then(function (data) { _translations = data })
                    .catch(function () { /* stay with empty table */ })
                    .finally(function () { if (callback) { callback() } })
            })
    }

    // Expose on window so both oel-teachdoc.js and edit-options-engine.js
    // can call them without any module system.
    window.returnTradTerm   = returnTradTerm
    window.cstudioI18nInit  = cstudioI18nInit
}())

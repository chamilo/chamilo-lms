// set superglobal js var by default 100 for glyph
window.size = 'medium';

var SVG_NS = "http://www.w3.org/2000/svg";

var svgCache = {};
var options = {};

var $badge;
var $badgeRaster;
var $studio = document.getElementById('studio');

var $template = document.getElementById('studio-template');
var $palette = document.getElementById('studio-palette');
var $mask = document.getElementById('studio-mask');
var $glyph = document.getElementById('studio-glyph');

var $glyphSelector;
var $glyphSelectorButton;

window.addEventListener('load', function init() {

    $badgeRaster = new Image();
    $badgeRaster.id = 'raster';
    document.getElementById('output').appendChild($badgeRaster);

    $template.addEventListener('change', updateTemplate);
    $palette.addEventListener('change', updatePalette);
    $mask.addEventListener('change', updateMask);
    $glyph.addEventListener('change', updateGlyph);

    initStudio();
    initPalettes();
    initOptions();
    updateTemplate();

});

// ==[ General Methods ]======================================================

/**
 *
 */
function showError(err) {
    // TO DO - show errors :)
    console.err(err);
}

// ==[ Studio ]===============================================================

/**
 *
 */
function initStudio() {

    initGlyphSelector();

    document.addEventListener('keydown', function (event) {
        if (event.keyCode === 27) { // Escape
            if ($glyphSelector && $glyphSelector.offsetWidth)
                closeGlyphSelector();
        }
    }, true);

    document.addEventListener('focus', function (event) {
        [$glyphSelector].forEach(function ($overlay) {
            if ($overlay && $overlay.offsetWidth && !$overlay.contains(event.target)) {
                event.stopPropagation();
                $overlay.focus();
            }
        });
    }, true);
}

// ==[ Glyph Selector ]=======================================================

/**
 *
 */
function initGlyphSelector() {

    if ($glyphSelector) {
        return false;
    }

    var glyphLog = [];

    $glyphSelectorButton = document.createElement('button');
    $glyphSelectorButton.className = 'btn btn-default pull-right';
    $glyphSelectorButton.id = 'search-glyphs';
    $glyphSelectorButton.type = 'button';
    $glyphSelectorButton.innerHTML = '<em class="fa fa-search"></em> Search';
    $glyphSelectorButton.addEventListener('click', openGlyphSelector);

    $glyph.parentNode.insertBefore($glyphSelectorButton, $glyph.nextSibling);

    var $$options = $glyph.querySelectorAll('option');

    $glyphSelector = importTemplate('glyph-selector', function ($template) {

        var $list = $template.querySelector('ul');

        for (var i = 0, l = $$options.length; i < l; i++) {
            (function ($option, index) {
                var value = $option.value;
                var id = 'glyph-selector-item-' + value;

                var $node = importTemplate('glyph-selector-item', function ($template) {
                    var $input = $template.querySelector('input');
                    $input.id = id;
                    $input.value = index;

                    var checked = $glyph.selectedIndex === index;
                    $input[checked ? 'setAttribute' : 'removeAttribute']('checked', 'checked');

                    var $label = $template.querySelector('label');
                    $label.id = id + '-label';
                    $label.className = 'fa fa-' + value;
                    $label.setAttribute('for', id);
                    $label.setAttribute('title', $option.text);
                }).querySelector('li');

                $list.appendChild($node);

                glyphLog.push({
                    id: id,
                    value: value
                });
            })($$options[i], i);
        }

    }).querySelector('#glyph-selector');

    $glyphSelector.querySelector('.header').appendChild(makeCloseButton(closeGlyphSelector));
    $studio.appendChild($glyphSelector);

    $glyphSelector.addEventListener('change', function (event) {

        event.stopPropagation();
        var index = event.target.value;
        $glyph.selectedIndex = index;

        updateGlyph();
    });

    $glyphSelector.addEventListener('click', function (event) {
        if (event.target.nodeName.toLowerCase() !== 'label') {
            return;
        }

        event.stopPropagation();
        closeGlyphSelector();
    });

    $glyphSelector.addEventListener('keydown', function (event) {

        if (event.keyCode === 13) { // Enter
            if (event.target.name)
                $glyph.selectedIndex = event.target.value;
            return updateGlyph(closeGlyphSelector);
        }

        if (event.keyCode === 38 || event.keyCode === 40) { // Up / Down
            event.preventDefault();

            var $container = event.target.parentNode.parentNode;
            var itemSize = event.target.parentNode.offsetWidth;
            var containerSize = $container.offsetWidth;
            var rowCount = Math.floor(containerSize / itemSize);
            var currentIndex = parseInt(event.target.value);
            var newIndex = currentIndex;
            var altFinder;

            if (event.keyCode === 38) {
                // Move up a row
                newIndex = currentIndex - rowCount;
                altFinder = 'firstElementChild';
            } else {
                // Move down a row
                newIndex = currentIndex + rowCount;
                altFinder = 'lastElementChild';
            }

            var newItem = $container.querySelector('input[value="' + newIndex + '"]') ||
                $container[altFinder].querySelector('input');

            $glyph.selectedIndex = newItem.value;
            newItem.checked = true;
            newItem.focus();
            rasterize();
        }
    });

    $glyphSelector.addEventListener('focus', function (event) {
        if (event.target !== $glyphSelector)
            return;

        event.stopPropagation();
    }, true);

    $glyph.addEventListener('change', function (event) {
        var $selectorItem = document.getElementById('glyph-selector-item-' + this.value);
        if ($selectorItem) {
            $selectorItem.click();
        }
    });
}

/**
 *
 */
function openGlyphSelector() {
    if (!$glyphSelector)
        initGlyphSelector();

    $glyphSelector.classList.remove('hidden');

    if ($glyph.value)
        document.getElementById('glyph-selector-item-' + $glyph.value + '-label').focus();

    $glyphSelector.focus();
}

/**
 *
 */
function closeGlyphSelector() {
    if (!$glyphSelector)
        return;

    $glyphSelector.classList.add('hidden');
    $glyphSelectorButton.focus();
}

// ==[ Templates ]============================================================

/**
 *
 */
function getCurrentTemplate() {
    return $template.value;
}

/**
 *
 */
function updateTemplate(callback) {
    callback = cb(callback);

    var path = $template.dataset.path;
    var shape = getCurrentTemplate();

    loadSVG(path + '/' + shape + '.svg', function (err, $svg) {
        if (err)
            return showError(err);

        $badge = $svg;

        extractOptions();
        setCustomPalette($svg);
        updatePalette(function () {
            updateMask(callback);
        });
    });
}

// ==[ Palettes ]=============================================================

function Palette(colors) {
    this._colors = {};
    if (colors) {
        for (var color in colors) {
            if (colors.hasOwnProperty(color)) {
                this._colors[color] = Palette.parseColor(colors[color]);
            }
        }
    }
    if (!this._colors.hasOwnProperty('glyph'))
        this._colors['glyph'] = '#000000';
}

Palette.prototype.toNode = function (id) {
    var content = [];
    for (var color in this._colors) {
        if (this._colors.hasOwnProperty(color)) {
            content.push('.color-' + color + ' { fill: ' + this._colors[color] + '; }');
        }
    }

    var $node = document.createElement('style');
    $node.type = 'text/css';
    $node.id = id || 'palette';
    $node.textContent = content.join('\n');
    return $node;
}

Palette.prototype.colors = function () {
    return Object.keys(this._colors);
}

Palette.prototype.color = function (name) {
    return this._colors[name] || '#000';
}

Palette.parseColor = function (str) {
    // Should probably be a bit more robust about this!
    if (!/^#[a-f0-9]{3}$/i.test(str))
        return str.toLowerCase();
    return '#' + str.charAt(1) + str.charAt(1)
        + str.charAt(2) + str.charAt(2)
        + str.charAt(3) + str.charAt(3);
}

Palette.fromDataset = function (dataset) {
    var colors = {};
    for (var item in dataset) {
        if (/^color\w+/i.test(item)) {
            var color = item
                .replace(/^color(\w)/i, function (m, c) {
                    return c.toLowerCase();
                })
                .replace(/[A-Z]/, function (m) {
                    return '-' + m.toLowerCase();
                });
            colors[color] = dataset[item];
        }
    }
    return new Palette(colors);
}

Palette.fromSVG = function ($svg) {
    var colors = {};
    var $node = $svg.getElementById('palette');
    if (!$node || $node.nodeName !== 'style')
        return new Palette();

    var $stylesheet = document.createElement('style');
    $stylesheet.setAttribute('media', 'print');
    $stylesheet.appendChild(document.createTextNode($node.textContent));
    document.head.appendChild($stylesheet);
    var sheet = $stylesheet.sheet;
    document.head.removeChild($stylesheet);

    var rules = sheet.rules || sheet.cssRules;
    for (var i = 0, l = rules.length; i < l; i++) {
        var rule = rules[i];
        var selector = rule.selectorText;
        if (/^\.color-/.test(selector)) {
            var key = selector.replace(/^\.color-/, '');
            var value = rule.style.fill || '#000';
            colors[key] = value;
        }
    }

    return new Palette(colors);
}

/**
 *
 */
function initPalettes() {
    var $custom = document.createElement('option');
    $custom.disabled = true;
    $custom.value = 'custom';
    $custom.text = 'Custom';
    $custom.id = 'custom-color-option';
    $palette.options.add($custom);

    var $container = document.getElementById('custom-palette');

    $palette.addEventListener('change', function () {
        var isCustom = (this.options[this.selectedIndex] === $custom);
        $custom.disabled = !isCustom;

        setCustomColors();
        updatePalette();
    });

    var changeTimer;

    $container.addEventListener('change', function (event) {
        var $input = event.target;
        $custom.setAttribute('data-color-' + $input.name, $input.value);
        $custom.disabled = false;
        $palette.selectedIndex = $palette.options.length - 1;

        updatePalette();
    });

    setCustomColors();
}

/**
 *
 */
function getCurrentPalette() {
    var $option = $palette.options[$palette.selectedIndex];
    return Palette.fromDataset($option.dataset);
}

/**
 *
 */
function updatePalette(callback) {
    callback = cb(callback);

    var $oldPalette = $badge.getElementById('palette');
    var $newPalette = getCurrentPalette().toNode();

    if ($oldPalette) {
        $oldPalette.parentNode.insertBefore($newPalette, $oldPalette);
        $oldPalette.parentNode.removeChild($oldPalette);
    } else {
        var $defs = $badge.querySelector('defs') || document.createElement('defs');

        if (!$defs.parentNode)
            $badge.insertBefore($defs, $badge.childNodes[0]);

        $defs.appendChild($newPalette)
    }

    updateGlyph(callback);
}

/**
 *
 */
function setCustomPalette($svg, callback) {
    callback = cb(callback);

    var colors = Palette.fromSVG($svg).colors();

    var $container = document.getElementById('custom-palette');
    var display = $container.style.display;
    $container.innerHTML = '';
    $container.style.display = 'none';
    $container.className = 'item';

    for (var i = 0, l = colors.length; i < l; i++) {
        var name = colors[i];
        var label = name.replace(/(^|-)(\w)/g, function (m, x, c) {
            return (x ? ' ' : '') + c.toUpperCase();
        });

        $container.appendChild(importTemplate('custom-color', function ($template) {
            var $label = $template.querySelector('span');
            $label.textContent = label;
            var $input = $template.querySelector('input');
            $input.name = name;
            $input.id = 'custom-color-picker-' + name;
        }));
    }

    if (colors.length)
        $container.style.display = display;

    setCustomColors();
}

/**
 *
 */
function setCustomColors() {
    var $custom = document.getElementById('custom-color-option');
    var $option = $palette.options[$palette.selectedIndex];
    var palette = Palette.fromDataset($option.dataset);
    var colors = palette.colors();

    for (var i = 0, l = colors.length; i < l; i++) {
        var colorName = colors[i];
        var colorValue = palette.color(colorName);
        $custom.setAttribute('data-color-' + colorName, colorValue);
        var $input = document.getElementById('custom-color-picker-' + colorName);
        if ($input) {
            $input.value = colorValue;
        }
    }
}

// ==[ Masks ]================================================================

function getCurrentMask() {
    return $mask.value;
}

/**
 *
 */
function updateMask(callback) {
    callback = cb(callback);

    var path = $mask.dataset.path;
    var mask = getCurrentMask();

    if (!mask) {
        var $svg = document.createElementNS(SVG_NS, 'svg');
        var $g = document.createElementNS(SVG_NS, 'g');
        $g.id = 'mask';
        $svg.appendChild($g);
        return done(null, $svg);
    }

    loadSVG(path + '/' + mask + '.svg', done);

    function done(err, $svg) {
        if (err)
            return showError(err);

        var $oldMask = $badge.querySelector('#mask');
        var $newMask = $svg.querySelector('#mask');

        $oldMask.parentNode.insertBefore($newMask, $oldMask);
        $oldMask.parentNode.removeChild($oldMask);

        rasterize(callback);
    }
}

// ==[ Options ]==============================================================

/**
 *
 */
function initOptions() {
    if ($badge)
        extractOptions();

    var $options = document.getElementById('options');
    $options.addEventListener('change', function (event) {
        event.stopPropagation();
        var option = event.target.name;
        if (!options.hasOwnProperty(option))
            return;

        options[option] = !!event.target.checked;
        setOptions();
    });
}

/**
 *
 */
function extractOptions() {
    var $options = document.getElementById('options');
    $options.innerHTML = '';

    var $optional = $badge.querySelectorAll('.optional');

    if (!$optional.length) {
        $options.innerHTML = '<i>None</l>';
        return;
    }

    for (var i = 0, l = $optional.length; i < l; i++) {
        var $option = $optional[i];
        var label = $option.getAttribute('title');
        var name = $option.id;
        var enabled = ($option.getAttribute('display') !== 'none');
        if (!options.hasOwnProperty(name))
            options[name] = enabled;

        $option[!!options[name] ? 'removeAttribute' : 'setAttribute']('display', 'none');

        $options.appendChild(importTemplate('option', function ($template) {
            var $checkbox = $template.querySelector('input');
            $checkbox.name = name;
            $checkbox.checked = !!options[name];

            var $label = $template.querySelector('span');
            $label.textContent = label;
        }));
    }
}

/**
 *
 */
function setOptions(callback) {
    callback = cb(callback);

    if (!$badge)
        return callback();

    for (var option in options) {
        if (options.hasOwnProperty(option)) {
            var $node = $badge.getElementById(option);
            var visible = !!options[option];
            $node && ($node[visible ? 'removeAttribute' : 'setAttribute']('display', 'none'));
        }
    }

    rasterize(callback)
}

// ==[ Glyphs ]===============================================================

/**
 *
 */
function getCurrentGlyph() {
    return $glyph.value;
}

/**
 *
 */
function getCurrentGlyphValue() {
    if (!$glyph.value)
        return '';

    var $i = document.createElement('i');
    $i.className = 'fa fa-' + getCurrentGlyph();
    document.body.appendChild($i);
    var chr = window.getComputedStyle($i, ':before').content;
    document.body.removeChild($i);

    chr = chr.replace(/["']/g, '');
    return chr;
}

/**
 *
 */
function updateGlyph(callback) {
    var glyph = getCurrentGlyphValue();

    if (!glyph)
        return setGlyphImage(null, callback);

    var $canvas = document.createElement('canvas');
    $canvas.width = parseInt($badgeRaster.offsetWidth);
    $canvas.height = parseInt($badgeRaster.offsetHeight);

    var ctx = $canvas.getContext('2d');
    ctx.font = parseInt($canvas.width / 3) + "px FontAwesome";
    ctx.fillStyle = getCurrentPalette().color('glyph');
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.shadowColor = "rgba(0,0,0,0.5)";
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 0;
    ctx.shadowBlur = 5;

    ctx.fillText(glyph, $canvas.width / 2, $canvas.height / 2);

    var $image = new Image();
    $image.onload = function () {
        setGlyphImage($image, callback);
    }
    $image.src = $canvas.toDataURL("image/png");
    // $image.src = "./media/images/cheese.jpg";
}

/**
 *
 */
function setGlyphImage($image, callback) {
    callback = cb(callback);

    var $newGlyph = document.createElementNS(SVG_NS, 'g');
    $newGlyph.id = 'glyph';

    if (!$image)
        return done();

    var gxyAdd = 0;
    var whAdd = 0;


    if (window.size == 'big') {
        gxyAdd = 40;
        whAdd = 80
    } else if (window.size == 'small') {
        gxyAdd = -40;
        whAdd = -80
    }

    var iWidth = $image.width;
    var iHeight = $image.height;

    var rWidth = $badgeRaster.width;
    var rHeight = $badgeRaster.height;

    var box = $badge.getAttribute('viewBox').split(' ');

    var bWidth = parseInt(box[2]);
    var bHeight = parseInt(box[3]);

    var cx = bWidth / 2 + parseInt(box[0]);
    var cy = bHeight / 2 + parseInt(box[1]);

    var gWidth = iWidth / (rWidth / bWidth);
    var gHeight = iHeight / (rHeight / bHeight);
    var gx = cx - (gWidth / 2);
    var gy = cy - (gHeight / 2);

    var $glyph = document.createElementNS(SVG_NS, 'image');
    $glyph.setAttribute('x', gx - gxyAdd);
    $glyph.setAttribute('y', gy - gxyAdd);
    $glyph.setAttribute('width', gWidth + whAdd);
    $glyph.setAttribute('height', gHeight + whAdd);
    $glyph.setAttribute('xlink:href', $image.src);
    $newGlyph.appendChild($glyph);

    done();

    function done() {
        var $oldGlyph = $badge.getElementById('glyph');

        $oldGlyph.parentNode.insertBefore($newGlyph, $oldGlyph);
        $oldGlyph.parentNode.removeChild($oldGlyph);

        rasterize(callback);
    }
}

// ==[ Helpers ]==============================================================

/**
 *
 */
function rasterize(callback) {

    callback = cb(callback);

    var $svg = $badge.cloneNode(true);

    var $canvas = document.createElement('canvas');
    $canvas.width = parseInt($svg.getAttribute('width'));
    $canvas.height = parseInt($svg.getAttribute('height'));

    var ctx = $canvas.getContext('2d');
    var svg_xml = (new XMLSerializer()).serializeToString($svg);

    /*
     // This is the 'official' way of doing this. However, Firefox seems to have
     // an issue referencing relative fragment URIs created by `createObjectURL`.
     // So we're using a base64 encoding hack instead :( Worth noting that if
     // there are non-standard unicode characters in the XML, it'll die a death.

     var DOMURL = window.URL || window.webkitURL || window;
     var blob = new Blob([svg_xml], {type: 'image/svg+xml;charset=utf-8'});
     var url = DOMURL.createObjectURL(blob);
     */

    var url = 'data:image/svg+xml;base64,' + btoa(svg_xml);

    var $img = new Image();

    $img.onload = function () {
        ctx.drawImage($img, 0, 0);
        $badgeRaster.src = $canvas.toDataURL("image/png");
        callback();
    }

    $img.src = url;
}

/**
 *
 */
function cb(fn) {
    if (typeof fn === 'function')
        return fn;
    return function () {
    };
}

/**
 *
 */
function load(url, method, callback) {
    var request = new XMLHttpRequest();

    request.onload = function () {
        callback(null, request.responseXML || request.responseText, request);
    }

    request.onerror = function (err) {
        callback(err, null, request);
    }

    request.open(method, url, true);
    request.send();
}

/**
 *
 */
function loadSVG(path, callback) {
    if (svgCache[path])
        return callback(null, svgCache[path].cloneNode(true));

    load(path, 'GET', function (err, $doc, request) {
        if (err)
            return callback(err);

        if (!$doc || typeof $doc === 'string')
            return callback(new Error('Not valid SVG'));

        svgCache[path] = $doc.getElementsByTagName('svg')[0];
        callback(null, svgCache[path].cloneNode(true));
    })
}

/**
 *
 */
function importTemplate(name, builder) {
    var $template = document.getElementById(name + '-template');
    if (typeof builder === 'function')
        builder($template.content);
    return document.importNode($template.content, true);
}

/**
 *
 */
function makeCloseButton(callback) {
    var $template = importTemplate('close-button');
    $template.querySelector('button').addEventListener('click', callback);
    return $template;
}


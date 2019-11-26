/**
 * @file Mapping plugin for CKEditor
 * Copyright (C) 2014 BeezNest Latino S.A.C
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 */
(function () {
    CKEDITOR.plugins.add('mapping', {
        requires: ['dialog'],
        lang: ['es'],
        init: function (editor) {
            var iconPath = this.path + 'images/icon.png';

            CKEDITOR.dialog.add('Mapping', this.path + 'dialogs/mapping.js');

            var mappingCommand = editor.addCommand('Mapping', new CKEDITOR.dialogCommand('Mapping', {
                allowedContent: 'img[usemap];map[id,name];area[alt,coords,shape,target,title,url]'
            }));

            mappingCommand.startDisabled = true;

            editor.ui.addButton('Mapping', {
                label: editor.lang.mapping.toolbar,
                command: 'Mapping',
                icon: iconPath
            });

            editor.on('doubleclick', function (evt) {
                var element = evt.data.element;
                var editor = evt.editor;

                if (element.is('area')) {
                    var map = element.getParent().$;
                    var mapId = map.getAttribute('id');
                    var document = editor.document.$;
                    var selectedImage;

                    if (document.querySelector) {
                        selectedImage = document.querySelector('img[usemap="#' + mapId + '"]');
                    }
                    if (selectedImage) {
                        editor.getSelection().selectElement(new CKEDITOR.dom.element(selectedImage));

                        evt.data.dialog = 'Mapping';
                        return;
                    }
                }

                if (element.is('img') && element.hasAttribute('usemap')) {
                    editor.getSelection().selectElement(element);
                    evt.data.dialog = 'Mapping';
                }
            }, null, null, 20);

            editor.on('selectionChange', CKEDITOR.tools.bind(function (evt) {
                var elementPath = evt.data.path;
                var element = elementPath.lastElement;

                if (!element || !element.is('img')) {
                    this.setState(CKEDITOR.TRISTATE_DISABLED);

                    return;
                }

                this.setState(element.hasAttribute('usemap') ? CKEDITOR.TRISTATE_ON : CKEDITOR.TRISTATE_OFF);
            }, mappingCommand));

            if (CKEDITOR.env.ie)
                return;

            CKEDITOR.on('dialogDefinition', function (e) {
                if (e.data.name !== 'image')
                    return;

                var definition = e.data.definition;

                e.removeListener();

                definition.onOk = CKEDITOR.tools.override(definition.onOk, function (original) {
                    return function () {
                        original.call(this);

                        var selectedImage = this.imageElement;
                        var mapName = selectedImage.getAttribute('usemap');

                        if (!mapName)
                            return;

                        var map = editor.document.getById(mapName.substr(1));

                        if (!map)
                            return;

                        CKEDITOR.plugins.mapping.generate(selectedImage.$, map.$);
                    };
                });
            });

            editor.on('contentDom', function (e) {
                var document = e.editor.document.$;
                var maps = document.getElementsByTagName('map');

                for (var i = 0; i < maps.length; i++) {
                    var map = maps[i];
                    var name = map.name;
                    var imageWithMap = document.querySelector('img[usemap="#' + name + '"]');

                    if (imageWithMap) {
                        CKEDITOR.plugins.mapping.generate(imageWithMap, map);
                    }
                }
            });

            if (!CKEDITOR.plugins.mapping) {
                CKEDITOR.plugins.mapping = {};
            }

            CKEDITOR.plugins.mapping.generate = function (baseImage, map) {
                if (CKEDITOR.env.ie) {
                    return;
                }

                if (!baseImage.width) {
                    baseImage.addEventListener('load', function () {
                        baseImage.removeEventListener('load', onLoad);

                        CKEDITOR.plugins.mapping.generate(baseImage, map);
                    }, false);
                    return;
                }

                var doc = baseImage.ownerDocument;
                var canvas = doc.createElement('canvas');

                canvas.setAttribute('width', baseImage.width);
                canvas.setAttribute('height', baseImage.height);

                var context = canvas.getContext('2d');

                if (baseImage.attributes['data-cke-saved-src']) {
                    var tmpImg = new Image();
                    tmpImg.src = baseImage.attributes['data-cke-saved-src'].nodeValue;
                    tmpImg.width = baseImage.width;
                    tmpImg.height = baseImage.height;

                    context.drawImage(tmpImg, 0, 0, baseImage.width, baseImage.height);
                } else {
                    context.drawImage(baseImage, 0, 0, baseImage.width, baseImage.height);
                }

                context.strokeStyle = "#F00";
                context.lineWidth = 2;
                context.shadowOffsetX = 0;
                context.shadowOffsetY = 0;
                context.shadowBlur = 3;
                context.shadowColor = "#DDD";

                for (var i = 0; i < map.areas.length; i++) {
                    var area = map.areas[i];
                    var coords = area.coords.split(',');

                    switch (area.shape) {
                        case 'circle':
                            context.beginPath();
                            context.arc(coords[0], coords[1], coords[2], 0, Math.PI * 2, true);
                            context.closePath();
                            context.stroke();
                            break;
                        case 'poly':
                            context.beginPath();
                            context.moveTo(coords[0], coords[1]);

                            for (var j = 2; j < coords.length; j += 2) {
                                context.lineTo(coords[j], coords[j + 1]);
                            }

                            context.closePath();
                            context.stroke();
                            break;
                        default:
                            context.strokeRect(coords[0], coords[1], coords[2] - coords[0], coords[3] - coords[1]);
                            break;
                    }
                }

                try {
                    baseImage.src = canvas.toDataURL();
                } catch (e) {
                    console.log(e.message);
                }
            };
        },
        afterInit: function (editor) {
            if (!(CKEDITOR.env.ie && CKEDITOR.env.quirks)) {
                return;
            }

            var dataProcessor = editor.dataProcessor;
            var htmlFilter = dataProcessor && dataProcessor.htmlFilter;

            htmlFilter.addRules({
                elements: {
                    map: function (element) {
                        if (element.attributes.id && !element.attributes.name) {
                            element.attributes.name = element.attributes.id;
                        }

                        return element;
                    }
                }
            });
        }
    });
})();

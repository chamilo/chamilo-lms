(function () {
    var mapping = (function () {
        var currentAreaId = null;

        return  {
            imgMap: null,
            imageSelected: null,
            mapSelected: null,
            previousImageMode: null,
            setMode: function (mode) {
                if (mode === 'pointer') {
                    this.imgMap.is_drawing = 0;
                    this.imgMap.nextShape = '';
                } else {
                    this.imgMap.nextShape = mode;
                }

                this.activateMode(mode);
            },
            activateMode: function (mode) {
                if (mapping.previousImageMode) {
                    mapping.previousImageMode.removeClass('map-button-active');
                }

                if (mode === 'pointer') {
                    mapping.previousImageMode = mapping.dialog.getContentElement('info', 'btnPointer').getElement();
                    mapping.previousImageMode.addClass('map-button-active');
                }
            },
            setCurrentAreaAttributes: function () {
                if (currentAreaId !== null) {
                    mapping.imgMap.areas[currentAreaId].ahref = mapping.dialog.getValueOf('info', 'href');
                    mapping.imgMap.areas[currentAreaId].aalt = mapping.dialog.getValueOf('info', 'alt');
                    mapping.imgMap.areas[currentAreaId].atitle = mapping.dialog.getValueOf('info', 'title');
                }
            },
            onSelectedArea: function (obj) {
                mapping.setPropertiesVisible(true);

                mapping.setCurrentAreaAttributes();

                currentAreaId = obj.aid;

                mapping.dialog.setValueOf('info', 'href', obj.ahref);
                mapping.dialog.setValueOf('info', 'target', obj.atarget || 'notSet');
                mapping.dialog.setValueOf('info', 'alt', obj.aalt);
                mapping.dialog.setValueOf('info', 'title', obj.atitle);
            },
            setAreaProperty: function () {
                var id = currentAreaId;

                if (id !== null) {
                    mapping.imgMap.areas[id][ "a" + this.id ] = this.getValue();
                    mapping.imgMap._recalculate(id);
                }
            },
            setPropertiesVisible: function (shouldShow) {
                var fieldset = mapping.dialog.getContentElement('info', 'areaProperties');
                var fieldsetElement = fieldset.getElement();

                if (shouldShow) {
                    fieldsetElement.setStyle('visibility', '');
                } else {
                    fieldsetElement.setStyle('visibility', 'hidden');
                }
            },
            generateMapHTML: function () {
                var html = '';

                for (var i = 0; i < this.imgMap.areas.length; i++) {
                    html += (function (area) {
                        if (!area || area.shape === '') {
                            return '';
                        }

                        var html = '<area shape="' + area.shape + '"' +
                                ' coords="' + area.lastInput + '"';

                        if (area.aalt) {
                            html += ' alt="' + area.aalt + '"';
                        }

                        if (area.atitle) {
                            html += ' title="' + area.atitle + '"';
                        }

                        if (area.ahref) {
                            html += ' href="' + area.ahref + '" data-cke-saved-href="' + area.ahref + '"';
                        }

                        if (area.atarget && area.atarget !== 'notSet') {
                            html += ' target="' + area.atarget + '"';
                        }

                        html += '/>';

                        return html;
                    })(this.imgMap.areas[i]);
                }

                return html;
            },
            setCurrentAreaId: function (id) {
                currentAreaId = id;
            },
            dialog: null
        };
    })();

    CKEDITOR.dialog.add('Mapping', function (editor) {
        var lang = editor.lang.mapping;
        var canvasDrawer = 'canvasContainer' + CKEDITOR.tools.getNextNumber();
        var statusContainer = 'statusContainer' + CKEDITOR.tools.getNextNumber();
        var dialogReady = false;

        (function () {
            var plugin = editor.plugins.mapping;

            if (CKEDITOR.env.ie && typeof window.CanvasRenderingContext2D === 'undefined') {
                CKEDITOR.scriptLoader.load(plugin.path + "lib/excanvas.js", show);
            }

            if (typeof imgmap === 'undefined') {
                CKEDITOR.scriptLoader.load(plugin.path + "dialogs/imgmap.js", show);
            }

            var cssNode = CKEDITOR.document.getHead().append('link');
            cssNode.setAttribute("rel", "stylesheet");
            cssNode.setAttribute("type", "text/css");
            cssNode.setAttribute("href", plugin.path + "css/mapping.css");
        })();

        var show = function () {
            if (!dialogReady) {
                return;
            }

            if (typeof imgmap === 'undefined') {
                return;
            }

            if (CKEDITOR.env.ie && typeof window.CanvasRenderingContext2D === 'undefined') {
                return;
            }

            mapping.setCurrentAreaId(null);
            mapping.mapSelected = null;

            var elementSelected = editor.getSelection().getSelectedElement();

            if (!elementSelected || !elementSelected.is("img")) {
                alert(lang.youMustSelectAnImageBeforeUsingTheMapEditor);

                mapping.dialog.hide();

                return;
            }

            mapping.imageSelected = elementSelected;

            var src = null;

            if (mapping.imageSelected.data) {
                src = mapping.imageSelected.data("cke-saved-src");
            } else {
                src = mapping.imageSelected.getAttribute("_cke_saved_src");
            }

            mapping.imageSelected = mapping.imageSelected.$;

            mapping.imgMap = new imgmap({
                mode: "editor2",
                label: '%a',
                custom_callbacks: {
                    onSelectArea: mapping.onSelectedArea,
                    onRemoveArea: function () {
                        mapping.setCurrentAreaId(null);
                        mapping.setPropertiesVisible(false);
                    },
                    onStatusMessage: function (str) {
                        document.getElementById(statusContainer).innerHTML = str;
                    },
                    onLoadImage: function (pic) {
                        var ckPic = new CKEDITOR.dom.element(pic);
                        ckPic.on("dragstart", function (e) {
                            e.data.preventDefault();
                        });
                    }
                },
                pic_container: document.getElementById(canvasDrawer),
                bounding_box: false,
                lang: ''
            });

            mapping.imgMap.loadStrings(imgmapStrings);
            mapping.imgMap.loadImage(src, parseInt(mapping.imageSelected.style.width || 0, 10), parseInt(mapping.imageSelected.style.height || 0, 10));

            var mapname = mapping.imageSelected.getAttribute('usemap', 2) || mapping.imageSelected.usemap;

            if (mapname) {
                mapname = mapname.substr(1);
                var maps = editor.document.$.getElementsByTagName('MAP');

                for (var i = 0; i < maps.length; i++) {
                    if (maps[i].name === mapname || maps[i].id === mapname) {
                        mapping.mapSelected = maps[i];
                        mapping.imgMap.setMapHTML(mapping.mapSelected);

                        mapping.dialog.setValueOf('info', 'MapName', mapname);
                        break;
                    }
                }
            }

            mapping.imgMap.config.custom_callbacks.onAddArea = function (id) {
                mapping.setPropertiesVisible(true);
                mapping.setCurrentAreaAttributes();
                mapping.setCurrentAreaId(id);

                mapping.dialog.getContentElement('info', 'href').setValue('', true);
                mapping.dialog.getContentElement('info', 'target').setValue('notSet', true);
                mapping.dialog.getContentElement('info', 'alt').setValue('', true);
                mapping.dialog.getContentElement('info', 'title').setValue('', true);
            };

            if (mapping.mapSelected) {
                mapping.imgMap.selectedId = 0;
                mapping.onSelectedArea(mapping.imgMap.areas[0]);

                mapping.setMode('pointer');
            } else {
                mapping.activateMode('rect');
            }
        };

        var removeMap = function () {
            editor.fire('saveSnapshot');

            if (mapping.imageSelected && mapping.imageSelected.nodeName === "IMG") {
                mapping.imageSelected.removeAttribute('usemap', 0);
            }

            if (mapping.mapSelected) {
                mapping.mapSelected.parentNode.removeChild(mapping.mapSelected);
            }

            mapping.dialog.hide();
        };

        return {
            title: lang.mappingProperties,
            minWidth: 600,
            minHeight: 400,
            buttons: [
                {
                    type: 'button',
                    label: lang.removeMap,
                    onClick: removeMap
                },
                CKEDITOR.dialog.okButton,
                CKEDITOR.dialog.cancelButton
            ],
            contents: [
                {
                    id: 'info',
                    label: editor.lang.common.generalTab,
                    title: editor.lang.common.generalTab,
                    elements: [
                        {
                            id: 'MapName',
                            type: 'text',
                            label: lang.mapName,
                            onChange: function () {
                                mapping.imgMap.mapname = this.getValue();
                            }
                        },
                        {
                            type: 'hbox',
                            label: 'panels',
                            widths: ['30%', '40%'],
                            children: [
                                {
                                    type: 'vbox',
                                    children: [
                                        {
                                            type: 'hbox',
                                            children: [
                                                {
                                                    type: 'button',
                                                    id: 'btnPointer',
                                                    label: lang.selector,
                                                    className: 'map-button map-button-pointer',
                                                    onClick: function () {
                                                        mapping.setMode('pointer');
                                                    }
                                                },
                                                {
                                                    type: 'button',
                                                    id: 'btnRemove',
                                                    label: lang.removeArea,
                                                    className: 'map-button map-button-remove',
                                                    onClick: function () {
                                                        mapping.imgMap.removeArea(mapping.imgMap.currentid);
                                                    }
                                                }
                                            ]
                                        },
                                        {
                                            type: 'hbox',
                                            children: [
                                                {
                                                    type: 'select',
                                                    id: 'slcShape',
                                                    label: lang.shape,
                                                    items: [
                                                        [lang.rectangle, 'rect'],
                                                        [lang.circle, 'circle'],
                                                        [lang.polygon, 'poly']
                                                    ],
                                                    onChange: function () {
                                                        mapping.setMode(this.getValue());
                                                    }
                                                },
                                                {
                                                    type: 'select',
                                                    id: 'slcZoom',
                                                    label: lang.zoom,
                                                    onChange: function () {
                                                        var scale = this.getValue();
                                                        var currentImage = document.getElementById(canvasDrawer).getElementsByTagName('img')[0];

                                                        if (!currentImage) {
                                                            return;
                                                        }

                                                        if (!currentImage.oldwidth) {
                                                            currentImage.oldwidth = currentImage.width;
                                                        }

                                                        if (!currentImage.oldheight) {
                                                            currentImage.oldheight = currentImage.height;
                                                        }

                                                        currentImage.style.width = (currentImage.oldwidth * scale) + "px";
                                                        currentImage.style.height = (currentImage.oldheight * scale) + "px";
                                                        currentImage.style.minWidth = (currentImage.oldwidth * scale) + "px";
                                                        currentImage.style.minHeight = (currentImage.oldheight * scale) + "px";

                                                        mapping.imgMap.scaleAllAreas(scale);
                                                    },
                                                    default: '1',
                                                    items: [
                                                        ['25%', '0.25'],
                                                        ['50%', '0.5'],
                                                        ['100%', '1'],
                                                        ['200%', '2'],
                                                        ['300%', '3']
                                                    ]
                                                }
                                            ]
                                        },
                                        {
                                            type: 'fieldset',
                                            id: 'areaProperties',
                                            label: lang.area,
                                            style: 'visibility:hidden',
                                            children: [
                                                {
                                                    type: 'hbox',
                                                    children: [
                                                        {
                                                            type: 'text',
                                                            id: 'href',
                                                            label: lang.link,
                                                            onChange: mapping.setAreaProperty
                                                        },
                                                        {
                                                            type: 'button',
                                                            id: 'browse',
                                                            label: editor.lang.common.browseServer,
                                                            style: 'margin-top:10px;',
                                                            hidden: 'true',
                                                            filebrowser: 'info:href'
                                                        }
                                                    ]
                                                },
                                                {
                                                    type: 'select',
                                                    id: 'target',
                                                    label: lang.target,
                                                    items: [
                                                        [lang.notSet, 'notSet'],
                                                        [lang.targetSelf, '_self'],
                                                        [lang.targetBlank, '_blank'],
                                                        [lang.targetTop, '_top']
                                                    ],
                                                    onChange: mapping.setAreaProperty
                                                },
                                                {
                                                    type: 'text',
                                                    id: 'title',
                                                    label: lang.title,
                                                    onChange: mapping.setAreaProperty
                                                },
                                                {
                                                    type: 'text',
                                                    id: 'alt',
                                                    label: lang.alternativeText,
                                                    onChange: mapping.setAreaProperty
                                                }
                                            ]
                                        },
                                        {
                                            type: 'html',
                                            html: '<p id="' + statusContainer + '">&nbsp;</p>'
                                        }
                                    ]
                                },
                                {
                                    type: 'html',
                                    html: '<div id="' + canvasDrawer + '" class="map-canvas-drawer"></div>'
                                }
                            ]
                        }
                    ]
                }
            ],
            onLoad: function () {
                mapping.dialog = this;
            },
            onShow: function () {
                dialogReady = true;
                show();
            },
            onHide: function () {
                if (mapping.previousImageMode) {
                    mapping.previousImageMode.removeClass('map-button-active');
                    mapping.previousImageMode = null;
                }
                document.getElementById(canvasDrawer).innerHTML = '';
            },
            onOk: function () {
                mapping.setCurrentAreaAttributes();

                if (mapping.imageSelected && mapping.imageSelected.nodeName === "IMG") {
                    var currentGeneratedMap = mapping.generateMapHTML();

                    if (!currentGeneratedMap) {
                        removeMap();

                        return;
                    }

                    mapping.imgMap.mapid = mapping.imgMap.mapname = mapping.dialog.getValueOf('info', 'MapName');

                    var result = editor.fire('mapping.validate', mapping.imgMap);

                    if (typeof result !== 'object') {
                        return false;
                    }

                    editor.fire('saveSnapshot');

                    if (!mapping.mapSelected) {
                        mapping.mapSelected = editor.document.$.createElement('map');
                        mapping.imageSelected.parentNode.appendChild(mapping.mapSelected);
                    }

                    mapping.mapSelected.innerHTML = currentGeneratedMap;

                    if (mapping.mapSelected.name) {
                        mapping.mapSelected.removeAttribute('name');
                    }

                    mapping.mapSelected.name = mapping.imgMap.getMapName();
                    mapping.mapSelected.id = mapping.imgMap.getMapId();

                    mapping.imageSelected.setAttribute('usemap', "#" + mapping.mapSelected.name, 0);

                    if (CKEDITOR.plugins.mapping && CKEDITOR.plugins.mapping.generate) {
                        CKEDITOR.plugins.mapping.generate(mapping.imageSelected, mapping.mapSelected);
                    }
                }
            }
        };
    });
})();
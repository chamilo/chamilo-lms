/*
* oEmbed Plugin plugin
* Copyright (c) Ingo Herbote
* Licensed under the MIT license
* jQuery Embed Plugin: http://code.google.com/p/jquery-oembed/ (MIT License)
* Plugin for: http://ckeditor.com/license (GPL/LGPL/MPL: http://ckeditor.com/license)
*/

(function() {
    CKEDITOR.plugins.add('oembed', {
        requires: ['dialog'],
        lang: ['de', 'en', 'fr', 'nl', 'pl', 'ru'],
        init: function(editor) {
            // Check if content filter is disabled
            if (CKEDITOR.version >= 4.1) {
                if (editor.config.allowedContent != true) {
                    return;
                }
            }

            // Load jquery?
            if (typeof(jQuery) == 'undefined') {
                CKEDITOR.scriptLoader.load('http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js', function() {
                    if (typeof(jQuery.fn.oembed) == 'undefined') {
                        CKEDITOR.scriptLoader.load(CKEDITOR.getUrl(CKEDITOR.plugins.getPath('oembed') + 'libs/jquery.oembed.min.js'));
                    }
                });

            } else if (typeof(jQuery.fn.oembed) == 'undefined') {
                CKEDITOR.scriptLoader.load(CKEDITOR.getUrl(CKEDITOR.plugins.getPath('oembed') + 'libs/jquery.oembed.min.js'));
            }

            editor.addCommand('oembed', new CKEDITOR.dialogCommand('oembed'));
            editor.ui.addButton('oembed', {
                label: editor.lang.oembed.button,
                command: 'oembed',
                icon: this.path + 'images/icon.png'
            });

            var resizeTypeChanged = function() {
                var dialog = this.getDialog(),
                    resizetype = this.getValue(),
                    maxSizeBox = dialog.getContentElement('general', 'maxSizeBox').getElement(),
                    sizeBox = dialog.getContentElement('general', 'sizeBox').getElement();

                if (resizetype == 'noresize') {
                    maxSizeBox.hide();
                    
                    sizeBox.hide();
                } else if (resizetype == "custom") {
                    maxSizeBox.hide();
                    
                    sizeBox.show();
                } else {
                    maxSizeBox.show();
                    
                    sizeBox.hide();
                }

            };
            
            String.prototype.beginsWith = function (string) {
                return (this.indexOf(string) === 0);
            };
            
            function embedCode(url, instance, closeDialog, wrapperHtml, maxWidth, maxHeight, responsiveResize) {
                jQuery('body').oembed(url, {
                    onEmbed: function(e) {
                        if (typeof e.code === 'string') {
                            instance.insertHtml(wrapperHtml.html());
                            instance.insertHtml(e.code);

                            if (closeDialog) {
                                CKEDITOR.dialog.getCurrent().hide();
                            }
                        } else if (typeof e.code[0].outerHTML === 'string') {
                            instance.insertHtml(wrapperHtml.html());
                            instance.insertHtml(e.code[0].outerHTML);

                            if (closeDialog) {
                                CKEDITOR.dialog.getCurrent().hide();
                            }
                        } else {
                            alert(editor.lang.oembed.noEmbedCode);
                        }
                    },
                    onError: function (externalUrl) {
                        if (externalUrl.indexOf("vimeo.com") > 0) {
                            alert(editor.lang.oembed.noVimeo);
                        } else {
                            alert(editor.lang.oembed.Error);
                        }
                                
                    },
                    maxHeight: maxHeight,
                    maxWidth: maxWidth,
                    useResponsiveResize: responsiveResize,
                    embedMethod: 'editor'
                });
            }

            CKEDITOR.dialog.add('oembed', function(editor) {
                return {
                    title: editor.lang.oembed.title,
                    minWidth: CKEDITOR.env.ie && CKEDITOR.env.quirks ? 568 : 550,
                    minHeight: 155,
                    onShow: function() {
                        var resizetype = this.getContentElement('general', 'resizeType').getValue(),
                            maxSizeBox = this.getContentElement('general', 'maxSizeBox').getElement(),
                            sizeBox = this.getContentElement('general', 'sizeBox').getElement();

                        if (resizetype == 'noresize') {
                            maxSizeBox.hide();
                            sizeBox.hide();
                        } else if (resizetype == "custom") {
                            maxSizeBox.hide();

                            sizeBox.show();
                        } else {
                            maxSizeBox.show();

                            sizeBox.hide();
                        }
                    },
                    onOk: function() {
                        var inputCode = this.getValueOf('general', 'embedCode');
                        if (inputCode.length < 1 || inputCode.indexOf('http') < 0) {
                            alert(editor.lang.oembed.invalidUrl);
                            return false;
                        }
                        var resizetype = this.getContentElement('general', 'resizeType').getValue();
                        var maxWidth = null;
                        var maxHeight = null;
                        var responsiveResize = false;
                        
                        var wrapperHtml = jQuery('<div />').append(editor.config.oembed_WrapperClass != null ? '<div class="' + editor.config.oembed_WrapperClass + '" />' : '<div />');

                        if (resizetype == "noresize") {
                            responsiveResize = false;
                        } else {
                            if (resizetype == "responsive") {
                                maxWidth = this.getContentElement('general', 'maxWidth').getInputElement().getValue();
                                maxHeight = this.getContentElement('general', 'maxHeight').getInputElement().getValue();
                                
                                responsiveResize = true;
                            } else if (resizetype == "custom") {
                                maxWidth = this.getContentElement('general', 'width').getInputElement().getValue();
                                maxHeight = this.getContentElement('general', 'height').getInputElement().getValue();
                                
                                responsiveResize = false;
                            }
                        } 
                        
                        var editorInstance = this.getParentEditor();
                        
                        var closeDialog = this.getContentElement('general', 'autoCloseDialog').getValue();

                        // support for multiple urls
                        if (inputCode.indexOf(";") > 0) {
                            var urls = inputCode.split(";");
                            
                            for (var i = 0; i < urls.length; i++) {
                                var url = urls[i];

                                if (url.length > 1 && url.beginsWith('http')) {
                                    embedCode(url, editorInstance, false, wrapperHtml, maxWidth, maxHeight, responsiveResize);
                                }
                                // close after last
                                if (i == urls.length -1) {
                                    CKEDITOR.dialog.getCurrent().hide();
                                }
                            }
                        } else {
                            // single url
                            embedCode(inputCode, editorInstance, closeDialog, wrapperHtml, maxWidth, maxHeight, responsiveResize);
                        }
                        
                        return false;
                    },
                    contents: [{
                        label: editor.lang.common.generalTab,
                        id: 'general',
                        elements: [{
                                type: 'html',
                                id: 'oembedHeader',
                                html: '<div style="white-space:normal;width:500px;padding-bottom:10px">' + editor.lang.oembed.pasteUrl + '</div>'
                            }, {
                                type: 'text',
                                id: 'embedCode',
                                focus: function() {
                                    this.getElement().focus();
                                },
                                label: editor.lang.oembed.url,
                                title: editor.lang.oembed.pasteUrl
                            }, {
                                type: 'hbox',
                                children: [{
                                        id: 'resizeType',
                                        type: 'select',
                                        label: editor.lang.oembed.resizeType,
                                        'default': 'noresize',
                                        items: [
                                            [editor.lang.oembed.noresize, 'noresize'],
                                            [editor.lang.oembed.responsive, 'responsive'],
                                            [editor.lang.oembed.custom, 'custom']
                                        ],
                                        onChange: resizeTypeChanged
                                    }, {
                                        type: 'hbox',
                                        id: 'maxSizeBox',
                                        children: [{
                                                type: 'text',
                                                id: 'maxWidth',
                                                'default': editor.config.oembed_maxWidth != null ? editor.config.oembed_maxWidth : '560',
                                                label: editor.lang.oembed.maxWidth,
                                                title: editor.lang.oembed.maxWidthTitle,
                                            }, {
                                                type: 'text',
                                                id: 'maxHeight',
                                                'default': editor.config.oembed_maxHeight != null ? editor.config.oembed_maxHeight : '315',
                                                label: editor.lang.oembed.maxHeight,
                                                title: editor.lang.oembed.maxHeightTitle,
                                            }]
                                    }, {
                                        type: 'hbox',
                                        id: 'sizeBox',
                                        children: [{
                                                type: 'text',
                                                id: 'width',
                                                'default': editor.config.oembed_maxWidth != null ? editor.config.oembed_maxWidth : '560',
                                                label: editor.lang.oembed.width,
                                                title: editor.lang.oembed.widthTitle,
                                            }, {
                                                type: 'text',
                                                id: 'height',
                                                'default': editor.config.oembed_maxHeight != null ? editor.config.oembed_maxHeight : '315',
                                                label: editor.lang.oembed.height,
                                                title: editor.lang.oembed.heightTitle,
                                            }]
                                    }]
                            }, {
                                type: 'checkbox',
                                id: 'autoCloseDialog',
                                'default': 'checked',
                                label: editor.lang.oembed.autoClose,
                                title: editor.lang.oembed.autoClose
                            }]
                    }]
                };
            });
        }//
    });
})();
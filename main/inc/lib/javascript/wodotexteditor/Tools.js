/**
 * Copyright (C) 2013 KO GmbH <copyright@kogmbh.com>
 *
 * @licstart
 * This file is part of WebODF.
 *
 * WebODF is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License (GNU AGPL)
 * as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * WebODF is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with WebODF.  If not, see <http://www.gnu.org/licenses/>.
 * @licend
 *
 * @source: http://www.webodf.org/
 * @source: https://github.com/kogmbh/WebODF/
 */

/*global define,document,require,ops */

define("webodf/editor/Tools", [
    "dojo/ready",
    "dijit/MenuItem",
    "dijit/DropDownMenu",
    "dijit/form/Button",
    "dijit/form/DropDownButton",
    "dijit/Toolbar",
    "webodf/editor/widgets/paragraphAlignment",
    "webodf/editor/widgets/simpleStyles",
    "webodf/editor/widgets/undoRedoMenu",
    "webodf/editor/widgets/toolbarWidgets/currentStyle",
    "webodf/editor/widgets/annotation",
    "webodf/editor/widgets/editHyperlinks",
    "webodf/editor/widgets/imageInserter",
    "webodf/editor/widgets/paragraphStylesDialog",
    "webodf/editor/widgets/zoomSlider",
    "webodf/editor/widgets/aboutDialog",
    "webodf/editor/EditorSession"],
    function (ready, MenuItem, DropDownMenu, Button, DropDownButton, Toolbar, ParagraphAlignment, SimpleStyles, UndoRedoMenu, CurrentStyle, AnnotationControl, EditHyperlinks, ImageInserter, ParagraphStylesDialog, ZoomSlider, AboutDialog, EditorSession) {
        "use strict";

        return function Tools(toolbarElementId, args) {
            var tr = runtime.tr,
                onToolDone = args.onToolDone,
                loadOdtFile = args.loadOdtFile,
                saveOdtFile = args.saveOdtFile,
                close = args.close,
                toolbar,
                loadButton, saveButton, closeButton, aboutButton,
                formatDropDownMenu, formatMenuButton,
                paragraphStylesMenuItem, paragraphStylesDialog, simpleStyles, currentStyle,
                zoomSlider,
                undoRedoMenu,
                editorSession,
                paragraphAlignment,
                imageInserter,
                annotationControl,
                editHyperlinks,
                aboutDialog,
                sessionSubscribers = [];

            /**
             * Creates a tool and installs it, if the enabled flag is set to true.
             * Only supports tool classes whose constructor has a single argument which
             * is a callback to pass the created widget object to.
             * @param {!function(new:Object, function(!Object):undefined)} Tool  constructor method of the tool
             * @param {!boolean} enabled
             * @return {?Object}
             */
            function createTool(Tool, enabled) {
                var tool = null;

                if (enabled) {
                    tool = new Tool(function (widget) {
                        widget.placeAt(toolbar);
                        widget.startup();
                    });
                    sessionSubscribers.push(tool);
                    tool.onToolDone = onToolDone;
                }

                return tool;
            }

            function handleCursorMoved(cursor) {
                var disabled = cursor.getSelectionType() === ops.OdtCursor.RegionSelection;
                if (formatMenuButton) {
                    formatMenuButton.setAttribute('disabled', disabled);
                }
            }

            function setEditorSession(session) {
                if (editorSession) {
                    editorSession.unsubscribe(EditorSession.signalCursorMoved, handleCursorMoved);
                }

                editorSession = session;
                if (editorSession) {
                    editorSession.subscribe(EditorSession.signalCursorMoved, handleCursorMoved);
                }

                sessionSubscribers.forEach(function (subscriber) {
                    subscriber.setEditorSession(editorSession);
                });
                if (formatMenuButton) {
                    formatMenuButton.setAttribute('disabled', !editorSession);
                }
            }

            this.setEditorSession = setEditorSession;

            /**
             * @param {!function(!Error=)} callback, passing an error object in case of error
             * @return {undefined}
             */
            this.destroy = function (callback) {
                // TODO:
                // 1. We don't want to use `document`
                // 2. We would like to avoid deleting all widgets
                // under document.body because this might interfere with
                // other apps that use the editor not-in-an-iframe,
                // but dojo always puts its dialogs below the body,
                // so this works for now. Perhaps will be obsoleted
                // once we move to a better widget toolkit
                var widgets = dijit.findWidgets(document.body);
                dojo.forEach(widgets, function(w) {
                    w.destroyRecursive(false);
                });
                callback();
            };

            // init
            ready(function () {
                toolbar = new Toolbar({}, toolbarElementId);

                // About
                if (args.aboutEnabled) {
                    aboutButton = new Button({
                        label: tr('About WebODF Text Editor'),
                        showLabel: false,
                        iconClass: 'webodfeditor-dijitWebODFIcon',
                        style: {
                            float: 'left'
                        }
                    });
                    aboutDialog = new AboutDialog(function (dialog) {
                        aboutButton.onClick = function () {
                            dialog.startup();
                            dialog.show();
                        };
                    });
                    aboutDialog.onToolDone = onToolDone;
                    aboutButton.placeAt(toolbar);
                }

                // Undo/Redo
                undoRedoMenu = createTool(UndoRedoMenu, args.undoRedoEnabled);

                // Add annotation
                annotationControl = createTool(AnnotationControl, args.annotationsEnabled);

                // Simple Style Selector [B, I, U, S]
                simpleStyles = createTool(SimpleStyles, args.directTextStylingEnabled);

                // Paragraph direct alignment buttons
                paragraphAlignment = createTool(ParagraphAlignment, args.directParagraphStylingEnabled);

                // Paragraph Style Selector
                currentStyle = createTool(CurrentStyle, args.paragraphStyleSelectingEnabled);

                // Zoom Level Selector
                zoomSlider = createTool(ZoomSlider, args.zoomingEnabled);

                // Load
                if (loadOdtFile) {
                    loadButton = new Button({
                        label: tr('Open'),
                        showLabel: false,
                        iconClass: 'dijitIcon dijitIconFolderOpen',
                        style: {
                            float: 'left'
                        },
                        onClick: function () {
                            loadOdtFile();
                        }
                    });
                    loadButton.placeAt(toolbar);
                }

                // Save
                if (saveOdtFile) {
                    saveButton = new Button({
                        label: tr('Save'),
                        showLabel: false,
                        iconClass: 'dijitEditorIcon dijitEditorIconSave',
                        style: {
                            float: 'left'
                        },
                        onClick: function () {
                            saveOdtFile();
                            onToolDone();
                        }
                    });
                    saveButton.placeAt(toolbar);
                }

                // Format menu
                if (args.paragraphStyleEditingEnabled) {
                    formatDropDownMenu = new DropDownMenu({});
                    paragraphStylesMenuItem = new MenuItem({
                        label: tr("Paragraph...")
                    });
                    formatDropDownMenu.addChild(paragraphStylesMenuItem);

                    paragraphStylesDialog = new ParagraphStylesDialog(function (dialog) {
                        paragraphStylesMenuItem.onClick = function () {
                            if (editorSession) {
                                dialog.startup();
                                dialog.show();
                            }
                        };
                    });
                    sessionSubscribers.push(paragraphStylesDialog);
                    paragraphStylesDialog.onToolDone = onToolDone;

                    formatMenuButton = new DropDownButton({
                        dropDown: formatDropDownMenu,
                        disabled: true,
                        label: tr('Format'),
                        iconClass: "dijitIconEditTask",
                        style: {
                            float: 'left'
                        }
                    });
                    formatMenuButton.placeAt(toolbar);
                }

                // hyper links
                editHyperlinks = createTool(EditHyperlinks, args.hyperlinkEditingEnabled);

                // image insertion
                imageInserter = createTool(ImageInserter, args.imageInsertingEnabled);

                // close button
                if (close) {
                    closeButton = new Button({
                        label: tr('Close'),
                        showLabel: false,
                        iconClass: 'dijitEditorIcon dijitEditorIconCancel',
                        style: {
                            float: 'right'
                        },
                        onClick: function () {
                            close();
                        }
                    });
                    closeButton.placeAt(toolbar);
                }

                setEditorSession(editorSession);
            });
        };

    });

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

/*global define,require,document,odf */

define("webodf/editor/widgets/editHyperlinks", [
    "webodf/editor/EditorSession",
    "webodf/editor/widgets/dialogWidgets/editHyperlinkPane",
    "dijit/form/Button",
    "dijit/form/DropDownButton",
    "dijit/TooltipDialog"],

    function (EditorSession, EditHyperlinkPane, Button, DropDownButton, TooltipDialog) {
        "use strict";

        runtime.loadClass("odf.OdfUtils");

        var EditHyperlinks = function (callback) {
            var self = this,
                widget = {},
                editorSession,
                hyperlinkController,
                linkEditorContent,
                editHyperlinkButton,
                removeHyperlinkButton,
                odfUtils = new odf.OdfUtils(),
                textSerializer = new odf.TextSerializer(),
                dialog;

            /**
             * @param {!Range} selection
             * @return {!string}
             */
            function getTextContent(selection) {
                var document = selection.startContainer.ownerDocument,
                    fragmentContainer = document.createElement('span');
                fragmentContainer.appendChild(selection.cloneContents());
                return textSerializer.writeToString(fragmentContainer);
            }

            function updateLinkEditorContent() {
                var selection = editorSession.getSelectedRange(),
                    linksInSelection = editorSession.getSelectedHyperlinks(),
                    linkTarget = linksInSelection[0] ? odfUtils.getHyperlinkTarget(linksInSelection[0]) : "http://";

                if (selection && selection.collapsed && linksInSelection.length === 1) {
                    // Selection is collapsed within a single hyperlink. Assume user is modifying the hyperlink
                    linkEditorContent.set({
                        linkDisplayText: textSerializer.writeToString(linksInSelection[0]),
                        linkUrl: linkTarget,
                        isReadOnlyText: true
                    });
                } else if (selection && !selection.collapsed) {
                    // User has selected part of a hyperlink or a block of text. Assume user is attempting to modify the
                    // existing hyperlink, or wants to convert the selection into a hyperlink
                    linkEditorContent.set({
                        linkDisplayText: getTextContent(selection),
                        linkUrl: linkTarget,
                        isReadOnlyText: true
                    });
                } else {
                    // Selection is collapsed and is not in an existing hyperlink
                    linkEditorContent.set({
                        linkDisplayText: "",
                        linkUrl: linkTarget,
                        isReadOnlyText: false
                    });
                }
            }

            function checkHyperlinkButtons() {
                var linksInSelection = editorSession.getSelectedHyperlinks();

                // The 3rd parameter is false to avoid firing onChange when setting the value programmatically.
                removeHyperlinkButton.set('disabled', linksInSelection.length === 0, false);
            }

            function enableHyperlinkButtons(isEnabled) {
                widget.children.forEach(function (element) {
                    element.setAttribute('disabled', !isEnabled);
                });
            }

            function updateSelectedLink(hyperlinkData) {
                var selection = editorSession.getSelectedRange(),
                    selectionController = editorSession.sessionController.getSelectionController(),
                    selectedLinkRange,
                    linksInSelection = editorSession.getSelectedHyperlinks();

                if (hyperlinkData.isReadOnlyText == "true") {
                    if (selection && selection.collapsed && linksInSelection.length === 1) {
                        // Editing the single link the cursor is currently within
                        selectedLinkRange = selection.cloneRange();
                        selectedLinkRange.selectNode(linksInSelection[0]);
                        selectionController.selectRange(selectedLinkRange, true)
                    }
                    hyperlinkController.removeHyperlinks();
                    hyperlinkController.addHyperlink(hyperlinkData.linkUrl);
                } else {
                    hyperlinkController.addHyperlink(hyperlinkData.linkUrl, hyperlinkData.linkDisplayText);
                    linksInSelection = editorSession.getSelectedHyperlinks();
                    selectedLinkRange = selection.cloneRange();
                    selectedLinkRange.selectNode(linksInSelection[0]);
                    selectionController.selectRange(selectedLinkRange, true)
                }
            }

            this.setEditorSession = function (session) {
                if (editorSession) {
                    editorSession.unsubscribe(EditorSession.signalCursorMoved, checkHyperlinkButtons);
                    editorSession.unsubscribe(EditorSession.signalParagraphChanged, checkHyperlinkButtons);
                    editorSession.unsubscribe(EditorSession.signalParagraphStyleModified, checkHyperlinkButtons);
                    hyperlinkController.unsubscribe(gui.HyperlinkController.enabledChanged, enableHyperlinkButtons);
                }

                editorSession = session;
                if (editorSession) {
                    hyperlinkController = editorSession.sessionController.getHyperlinkController();

                    editorSession.subscribe(EditorSession.signalCursorMoved, checkHyperlinkButtons);
                    editorSession.subscribe(EditorSession.signalParagraphChanged, checkHyperlinkButtons);
                    editorSession.subscribe(EditorSession.signalParagraphStyleModified, checkHyperlinkButtons);
                    hyperlinkController.subscribe(gui.HyperlinkController.enabledChanged, enableHyperlinkButtons);

                    enableHyperlinkButtons(hyperlinkController.isEnabled());
                    checkHyperlinkButtons();
                } else {
                    enableHyperlinkButtons( false );
                }
            };

            this.onToolDone = function () {};

            function init() {
                textSerializer.filter = new odf.OdfNodeFilter();

                linkEditorContent = new EditHyperlinkPane();
                dialog = new TooltipDialog({
                    title: runtime.tr("Edit link"),
                    content: linkEditorContent.widget(),
                    onShow: updateLinkEditorContent
                });

                editHyperlinkButton = new DropDownButton({
                    label: runtime.tr('Edit link'),
                    showLabel: false,
                    disabled: true,
                    iconClass: 'dijitEditorIcon dijitEditorIconCreateLink',
                    dropDown: dialog
                });

                removeHyperlinkButton = new Button({
                    label: runtime.tr('Remove link'),
                    showLabel: false,
                    disabled: true,
                    iconClass: 'dijitEditorIcon dijitEditorIconUnlink',
                    onClick: function () {
                        hyperlinkController.removeHyperlinks();
                        self.onToolDone();
                    }
                });

                linkEditorContent.onSave = function () {
                    var hyperlinkData = linkEditorContent.value();
                    editHyperlinkButton.closeDropDown(false);
                    updateSelectedLink(hyperlinkData);
                    self.onToolDone();
                };

                linkEditorContent.onCancel = function () {
                    editHyperlinkButton.closeDropDown(false);
                    self.onToolDone();
                };

                widget.children = [editHyperlinkButton, removeHyperlinkButton];
                widget.startup = function () {
                    widget.children.forEach(function (element) {
                        element.startup();
                    });
                };

                widget.placeAt = function (container) {
                    widget.children.forEach(function (element) {
                        element.placeAt(container);
                    });
                    return widget;
                };
                callback(widget);
            }
            init();
        };

        return EditHyperlinks;
});

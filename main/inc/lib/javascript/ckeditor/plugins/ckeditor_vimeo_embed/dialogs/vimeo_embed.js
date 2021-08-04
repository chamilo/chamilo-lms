/* For licensing terms, see /LICENSE */

(function () {
    CKEDITOR.dialog.add(
        'vimeoEmbedDialog',
        function (editor) {
            var lang = editor.lang.ckeditor_vimeo_embed;

            function post(url, data, callback) {
                var xhr = new XMLHttpRequest();
                xhr.open( 'POST', url, true );
                xhr.onreadystatechange = function() {
                    if ( xhr.readyState == 4 ) {
                        callback(xhr.response);
                        xhr = null;
                    }
                };
                xhr.send(data);

                return xhr;
            }

            function setUploadStatus(status) {
                var elStatus = document.getElementById('ve-upload-status');

                if ('process' === status) {
                    elStatus.textContent = lang.uploadProcess;

                    return;
                }

                if ('end' === status) {
                    elStatus.textContent = lang.uploadEnd;

                    return;
                }

                elStatus.textContent = '';
            }

            var xhrPost = null;
            var stillUploading = false;
            var embedHtml = '';

            return {
                title: 'Vimeo Embed',
                minWidth: 600,
                minHeight: 400,
                contents: [
                    {
                        id: 've-basic',
                        title: lang.upload,
                        label: lang.upload,
                        elements: [
                            {
                                type: 'text',
                                id: 'title',
                                label: lang.title,
                            },
                            {
                                type: 'textarea',
                                id: 'description',
                                label: lang.description
                            },
                            {
                                type: 'html',
                                html: '<input id="ve-file" type="file" accept="video/*">'
                            },
                            {
                                type: 'vbox',
                                children: [
                                    {
                                        type: 'checkbox',
                                        id: 'privacy-download',
                                        label: lang.privacyDownload
                                    },
                                    {
                                        type: 'hbox',
                                        widths: ['65%', '35%'],
                                        children: [
                                            {
                                                type: 'select',
                                                id: 'privacy-embed',
                                                label: lang.privacyEmbed,
                                                items: [
                                                    // [lang.privacyEmbedPrivate, 'private'],
                                                    [lang.privacyEmbedPublic, 'public'],
                                                    [lang.privacyEmbedWhitelist, 'whitelist'],
                                                ],
                                                'default': 'public'
                                            },
                                            {
                                                type: 'text',
                                                id: 'privacy-embed-whitelist',
                                                label: lang.privacyEmbedWhitelistList
                                            },
                                        ]
                                    },
                                    {
                                        type: 'select',
                                        id: 'privacy-view',
                                        label: lang.privacyView,
                                        items: [
                                            [lang.privacyViewAnybody, 'anybody'],
                                            [lang.privacyViewContacts, 'contacts'],
                                            [lang.privacyViewDisable, 'disable'],
                                            [lang.privacyViewNobody, 'nobody'],
                                            [lang.privacyViewUnlisted, 'unlisted'],
                                            // [lang.privacyViewPassword, 'password'],
                                            // [lang.privacyViewUsers, 'users'],
                                        ],
                                        'default': 'unlisted'
                                    }
                                ]
                            },
                            {
                                type: 'hbox',
                                widths: ['1%', '100%'],
                                children: [
                                    {
                                        type: 'button',
                                        id: 'submit',
                                        label: lang.uploadFile,
                                        title: lang.uploadFile,
                                        onClick: function () {
                                            var dialog = this.getDialog();

                                            var title = dialog.getValueOf('ve-basic', 'title').trim();
                                            var description = dialog.getValueOf('ve-basic', 'description').trim();
                                            var privacyDownload = dialog.getValueOf('ve-basic', 'privacy-download');
                                            var privacyEmbed = dialog.getValueOf('ve-basic', 'privacy-embed');
                                            var privacyEmbedWhiteList = dialog.getValueOf('ve-basic', 'privacy-embed-whitelist').trim();
                                            var privacyView = dialog.getValueOf('ve-basic', 'privacy-view');
                                            var files = document.getElementById('ve-file').files;

                                            if (xhrPost) {
                                                return;
                                            }

                                            if (!title || !title.length) {
                                                dialog.getContentElement('ve-basic', 'title').focus();

                                                return;
                                            }

                                            if (!files || files.length !== 1) {
                                                document.getElementById('ve-file').focus();

                                                return;
                                            }

                                            if (privacyEmbed === 'whitelist' && !privacyEmbedWhiteList.length) {
                                                dialog.getContentElement('ve-basic', 'privacy-embed-whitelist').focus();

                                                return;
                                            }

                                            stillUploading = true;
                                            embedHtml = '';

                                            setUploadStatus('process');

                                            var formData = new FormData();
                                            formData.append('title', title);
                                            formData.append('description', description);
                                            formData.append('ve_file', files[0], files[0].name);
                                            formData.append('privacy_download', privacyDownload);
                                            formData.append('privacy_embed', privacyEmbed);
                                            formData.append('privacy_embed_whitelist', privacyEmbedWhiteList);
                                            formData.append('privacy_view', privacyView);

                                            xhrPost = post(
                                                CKEDITOR.plugins.getPath('ckeditor_vimeo_embed') + 'integration/upload.php',
                                                formData,
                                                function (response) {
                                                    xhrPost = null;

                                                    if (!response) {
                                                        return;
                                                    }

                                                    stillUploading = false;

                                                    var json = JSON.parse(response);

                                                    if (json.error) {
                                                        alert(json.message);
                                                        setUploadStatus('');
                                                    } else {
                                                        embedHtml = json.embed;

                                                        setUploadStatus('end');

                                                        dialog.disableButton('cancel');
                                                    }
                                                }
                                            );
                                        }
                                    },
                                    {
                                        type: 'html',
                                        html: '<span id="ve-upload-status" style="font-weight: bold;font-size: 14px;line-height: 28px;vertical-align: middle;height: 28px;display: table-cell;"></span>'
                                    }
                                ]
                            },
                        ]
                    },
                ],
                onShow: function () {
                    xhrPost = null;
                    stillUploading = false;
                    embedHtml = '';

                    setUploadStatus('');

                    document.getElementById('ve-file').value = null;

                    this.enableButton('cancel');
                },
                onOk: function () {
                    if (stillUploading) {
                        alert(lang.alertStillUploading);

                        return false;
                    }

                    if (embedHtml) {
                        editor.insertHtml(embedHtml);
                    }
                },
                onCancel: function (evt) {
                    if (evt.data.hide && xhrPost) {
                        xhrPost.abort();
                        xhrPost = null;
                    }
                }
            };
        }
    );
})();

CKEDITOR.dialog.add( 'video', function ( editor )
{
    var lang = editor.lang.video;

    function commitValue( videoNode, extraStyles )
    {
        var value=this.getValue();

        if ( !value && this.id=='id' )
            value = generateId();

        if (value == '') {
            // return;
        }

        switch (this.id) {
            case '360video':
                if (value) {
                    videoNode.setAttribute( 'data-360video', 'true' );
                } else {
                    videoNode.removeAttribute( 'data-360video' );
                }
                break;
            case '360videostereo':
                if (videoNode.getAttribute( 'data-360video' ) === 'true') {
                    if (!value) {
                        videoNode.setAttribute( 'data-360video-stereo', 'false' );
                    } else {
                        videoNode.removeAttribute( 'data-360video-stereo' );
                    }
                }
                break;
            default:
                videoNode.setAttribute( this.id, value);
        }

        if ( !value )
            return;
        switch( this.id )
        {
            case 'responsive':
                videoNode.addClass('embed-responsive-item');
                break;
            case 'poster':
                extraStyles.backgroundImage = 'url(' + value + ')';
                break;
            case 'width':
                extraStyles.width = value.indexOf('%') > 0 ? value : (parseInt(value) + 'px');
                break;
            case 'height':
                extraStyles.height = value.indexOf('%') > 0 ? value : (parseInt(value) + 'px');
                break;
        }
    }

    function commitSrc( videoNode, extraStyles, videos )
    {
        var match = this.id.match(/(\w+)(\d)/),
            id = match[1],
            number = parseInt(match[2], 10);

        var video = videos[number] || (videos[number]={});
        video[id] = this.getValue();
    }

    function onChangeSrc( event )
    {
        var dialog = this.getDialog(),
            videoEl = document.createElement('video');

        videoEl.onloadedmetadata = function () {
            dialog.setValueOf( 'info', 'width', videoEl.videoWidth );
            dialog.setValueOf( 'info', 'height', videoEl.videoHeight );
        };
        videoEl.src = location.origin + event.data.value;
    }

    function loadValue( videoNode )
    {
        if ( videoNode ) {
            switch (this.id) {
                case '360video':
                    this.setValue(videoNode.getAttribute( 'data-360video' ) === 'true');
                    break;
                case '360videostereo':
                    this.setValue(videoNode.getAttribute( 'data-360video-stereo' ) !== 'false');
                    break;
                default:
                    this.setValue( videoNode.getAttribute( this.id ) );
            }
        }
        else
        {
            if ( this.id == 'id')
                this.setValue( generateId() );
        }
    }

    function loadSrc( videoNode, videos )
    {
        var match = this.id.match(/(\w+)(\d)/),
            id = match[1],
            number = parseInt(match[2], 10);

        var video = videos[number];
        if (!video)
            return;
        this.setValue( video[ id ] );
    }

    function generateId()
    {
        var now = new Date();
        return 'video' + now.getFullYear() + now.getMonth() + now.getDate() + now.getHours() + now.getMinutes() + now.getSeconds();
    }

    // To automatically get the dimensions of the poster image
    var onImgLoadEvent = function()
    {
        // Image is ready.
        var preview = this.previewImage;
        preview.removeListener( 'load', onImgLoadEvent );
        preview.removeListener( 'error', onImgLoadErrorEvent );
        preview.removeListener( 'abort', onImgLoadErrorEvent );

        this.setValueOf( 'info', 'width', preview.$.width );
        this.setValueOf( 'info', 'height', preview.$.height );
    };

    var onImgLoadErrorEvent = function()
    {
        // Error. Image is not loaded.
        var preview = this.previewImage;
        preview.removeListener( 'load', onImgLoadEvent );
        preview.removeListener( 'error', onImgLoadErrorEvent );
        preview.removeListener( 'abort', onImgLoadErrorEvent );
    };

    return {
        title : lang.dialogTitle,
        minWidth : 400,
        minHeight : 200,

        onShow : function()
        {
            // Clear previously saved elements.
            this.fakeImage = this.videoNode = null;
            // To get dimensions of poster image
            this.previewImage = editor.document.createElement( 'img' );

            var fakeImage = this.getSelectedElement();
            if ( fakeImage && fakeImage.data( 'cke-real-element-type' ) && fakeImage.data( 'cke-real-element-type' ) == 'video' )
            {
                this.fakeImage = fakeImage;

                var videoNode = editor.restoreRealElement( fakeImage ),
                    videos = [],
                    sourceList = videoNode.getElementsByTag( 'source', '' );
                if (sourceList.count()==0)
                    sourceList = videoNode.getElementsByTag( 'source', 'cke' );

                for ( var i = 0, length = sourceList.count() ; i < length ; i++ )
                {
                    var item = sourceList.getItem( i );
                    videos.push( {src : item.getAttribute( 'src' ), type: item.getAttribute( 'type' )} );
                }

                this.videoNode = videoNode;

                this.setupContent( videoNode, videos );
            }
            else
                this.setupContent( null, [] );
        },

        onOk : function()
        {
            // If there's no selected element create one. Otherwise, reuse it
            var videoNode = null;
            if ( !this.fakeImage )
            {
                videoNode = CKEDITOR.dom.element.createFromHtml( '<cke:video></cke:video>', editor.document );
                videoNode.setAttributes(
                    {
                        controls : 'controls'
                    } );
            }
            else
            {
                videoNode = this.videoNode;
            }

            var extraStyles = {}, videos = [];
            var responsive = this.getValueOf('info', 'responsive');

            videoNode.removeClass('embed-responsive-item');

            if (responsive) {
                this.setValueOf('info', 'width', '100%');
                this.setValueOf('info', 'height', '100%');
            }

            this.commitContent( videoNode, extraStyles, videos );

            var innerHtml = '', links = '',
                link = lang.linkTemplate || '',
                fallbackTemplate = lang.fallbackTemplate || '';
            for(var i=0; i<videos.length; i++)
            {
                var video = videos[i];
                if ( !video || !video.src )
                    continue;
                innerHtml += '<cke:source src="' + video.src + '" type="' + video.type + '" />';
                links += link.replace('%src%', video.src).replace('%type%', video.type);
            }
            videoNode.setHtml( innerHtml + fallbackTemplate.replace( '%links%', links ) );

            var responsiveParent = null;
            // Refresh the fake image.
            var newFakeImageClass = 'cke_video' + (responsive ? ' embed-responsive-item' : '');
            var newFakeImage = editor.createFakeElement( videoNode, newFakeImageClass, 'video', false );
            newFakeImage.setStyles( extraStyles );
            if ( this.fakeImage )
            {
                newFakeImage.replace( this.fakeImage );
                editor.getSelection().selectElement( newFakeImage );

                if (responsive) {
                    responsiveParent = newFakeImage.getParent();
                    responsiveParent.removeClass('embed-responsive');
                    responsiveParent.removeClass('embed-responsive-16by9');
                    responsiveParent.removeClass('embed-responsive-9by16');
                    responsiveParent.removeClass('embed-responsive-4by3');
                    responsiveParent.removeClass('embed-responsive-3by4');
                }
            }
            else
            {
                // Insert it in a div
                var div = new CKEDITOR.dom.element( 'DIV', editor.document );
                editor.insertElement( div );
                div.append( newFakeImage );

                responsiveParent = div;
            }

            if (responsive) {
                newFakeImage.addClass('embed-responsive-item');
                responsiveParent.addClass('embed-responsive');

                switch (responsive) {
                    case '16by9':
                        responsiveParent.addClass('embed-responsive-16by9');
                        break;
                    case '9by16':
                        responsiveParent.addClass('embed-responsive-9by16');
                        break;
                    case '4by3':
                        responsiveParent.addClass('embed-responsive-4by3');
                        break;
                    case '3by4':
                        responsiveParent.addClass('embed-responsive-3by4');
                        break;
                }
            }
        },
        onHide : function()
        {
            if ( this.previewImage )
            {
                this.previewImage.removeListener( 'load', onImgLoadEvent );
                this.previewImage.removeListener( 'error', onImgLoadErrorEvent );
                this.previewImage.removeListener( 'abort', onImgLoadErrorEvent );
                this.previewImage.remove();
                this.previewImage = null;		// Dialog is closed.
            }
        },

        contents :
            [
                {
                    id : 'info',
                    label: lang.infoLabel,
                    elements :
                        [
                            {
                                type : 'hbox',
                                widths: [ '', '100px', '75px'],
                                children : [
                                    {
                                        type : 'text',
                                        id : 'src0',
                                        label : lang.sourceVideo,
                                        onChange: onChangeSrc,
                                        commit : commitSrc,
                                        setup : loadSrc
                                    },
                                    {
                                        type : 'button',
                                        id : 'browse',
                                        hidden : 'true',
                                        style : 'display:inline-block;margin-top:10px;',
                                        filebrowser :
                                            {
                                                action : 'Browse',
                                                target: 'info:src0',
                                                url: editor.config.filebrowserVideoBrowseUrl || editor.config.filebrowserBrowseUrl
                                            },
                                        label : editor.lang.common.browseServer
                                    },
                                    {
                                        id : 'type0',
                                        label : lang.sourceType,
                                        type : 'select',
                                        'default' : 'video/mp4',
                                        items : editor.config.videoTypes,
                                        onChange: onChangeSrc,
                                        commit : commitSrc,
                                        setup : loadSrc
                                    }]
                            },

                            {
                                type : 'hbox',
                                widths: [ '', '100px', '75px'],
                                children : [
                                    {
                                        type : 'text',
                                        id : 'src1',
                                        label : lang.sourceVideo,
                                        commit : commitSrc,
                                        setup : loadSrc
                                    },
                                    {
                                        type : 'button',
                                        id : 'browse',
                                        hidden : 'true',
                                        style : 'display:inline-block;margin-top:10px;',
                                        filebrowser :
                                            {
                                                action : 'Browse',
                                                target: 'info:src1',
                                                url: editor.config.filebrowserVideoBrowseUrl || editor.config.filebrowserBrowseUrl
                                            },
                                        label : editor.lang.common.browseServer
                                    },
                                    {
                                        id : 'type1',
                                        label : lang.sourceType,
                                        type : 'select',
                                        'default':'video/webm',
                                        items : editor.config.videoTypes,
                                        commit : commitSrc,
                                        setup : loadSrc
                                    }]
                            },
                            {
                                type : 'hbox',
                                widths: [ '', '100px'],
                                children : [
                                    {
                                        type : 'text',
                                        id : 'poster',
                                        label : lang.poster,
                                        commit : commitValue,
                                        setup : loadValue,
                                        onChange : function()
                                        {
                                            var dialog = this.getDialog(),
                                                newUrl = this.getValue();

                                            //Update preview image
                                            if ( newUrl.length > 0 )	//Prevent from load before onShow
                                            {
                                                dialog = this.getDialog();
                                                var preview = dialog.previewImage;

                                                preview.on( 'load', onImgLoadEvent, dialog );
                                                preview.on( 'error', onImgLoadErrorEvent, dialog );
                                                preview.on( 'abort', onImgLoadErrorEvent, dialog );
                                                preview.setAttribute( 'src', newUrl );
                                            }
                                        }
                                    },
                                    {
                                        type : 'button',
                                        id : 'browse',
                                        hidden : 'true',
                                        style : 'display:inline-block;margin-top:10px;',
                                        filebrowser :
                                            {
                                                action : 'Browse',
                                                target: 'info:poster',
                                                url: editor.config.filebrowserImageBrowseUrl || editor.config.filebrowserBrowseUrl
                                            },
                                        label : editor.lang.common.browseServer
                                    }]
                            },
                            {
                                type : 'hbox',
                                widths: [ '33%', '33%', '33%'],
                                children : [
                                    {
                                        type : 'text',
                                        id : 'width',
                                        label : editor.lang.common.width,
                                        'default' : 400,
                                        validate : CKEDITOR.dialog.validate.notEmpty( lang.widthRequired ),
                                        commit : commitValue,
                                        setup : loadValue
                                    },
                                    {
                                        type : 'text',
                                        id : 'height',
                                        label : editor.lang.common.height,
                                        'default' : 300,
                                        //validate : CKEDITOR.dialog.validate.notEmpty(lang.heightRequired ),
                                        commit : commitValue,
                                        setup : loadValue
                                    },
                                    {
                                        type : 'text',
                                        id : 'id',
                                        label : 'Id',
                                        commit : commitValue,
                                        setup : loadValue
                                    }
                                ]
                            },
                            {
                                type: 'radio',
                                id: 'responsive',
                                label: lang.responsive,
                                items: [ [ lang.ratio16by9, '16by9' ],  [ lang.ratio9by16, '9by16' ], [ lang.ratio4by3, '4by3' ], [ lang.ratio3by4, '3by4' ] ],
                                commit : commitValue,
                                setup : loadValue,
                                onChange: function () {
                                    var dialog = this.getDialog();

                                    dialog.setValueOf('info', 'width', '100%');
                                    dialog.setValueOf('info', 'height', '100%');
                                }
                            }
                        ]
                },
                {
                    id: '360',
                    label: '360°',
                    elements: [
                        {
                            type : 'html',
                            html : lang.html360
                        },
                        {
                            type : 'checkbox',
                            id : '360video',
                            label: lang.video360,
                            commit : commitValue,
                            setup : loadValue
                        },
                        {
                            type : 'checkbox',
                            id : '360videostereo',
                            label : lang.video360stereo,
                            'default': 'checked',
                            commit : commitValue,
                            setup : loadValue
                        }
                    ]
                }

            ]
    };
} );

var editorGlobal = '';

function initEditor(){

    $('#tool_image').css('display','none');
    $('#tool_make_link').css('display','none');
    $('#tool_topath').css('display','none');
    $('#tool_blur').css('display','none');
    $('#tool_angle').css('display','none');
    $('#classLabel').css('display','none');
    $('#idLabel').css('display','none');
    $('#cornerRadiusLabel').css('display','none');
    $('#toggle_stroke_tools').css('visibility','hidden');
    $('#group_opacityLabel').css('visibility','hidden');

    $('#tool_imagelib').css('display','none');
    $('#tool_save').css('display','none');

    setTimeout(function(){
        $('#logo').find('img').attr('src','images/import.png');
        $('#tool_make_link').find('img').attr('src','images/import.png');
        $('#tool_imagelib').css('display','none');
        $('#tool_save').css('display','none');
    },300);

    setTimeout(function(){
        $('#tool_imagelib').css('visibility','hidden');
        $('#tool_imagelib').css('display','none');
        $('#tool_export').css('visibility','hidden');
        $('#tool_export').css('display','none');
    },500);

    var toolsave = $('#tool_save').text();

    var btn = '<a id="speedtoolsave" class="saveBtn" style="position:fixed;right:40px;bottom:65px;z-index:1000;" hef="#" >' 
    btn += toolsave + '</a>'

    $('#svg_editor').append(btn);

    var saveloadmask = '<div id="saveloadmask" style="position:fixed;background-color:#E6E6E6;display:none;left:0px;bottom:0px;right:0px;top:0px;z-index:1001;" >' 
    saveloadmask += '<img style="position:absolute;left:50%;top:45%;margin-left:-50px;" src="cube-oe.gif" />'
    saveloadmask += '</div>'

    $('body').append(saveloadmask);
    
    $("#speedtoolsave").click(function() {
        // svgContext
        var svgstr = editorGlobal.canvas.getSvgString();
        saveSvg(svgstr);
    });
    
    window.onbeforeunload = null;

}

function saveContentAndPrefs(opts,svgContext_) {
  // remove the selected outline before serializing
  svgContext_.getCanvas().clearSelection();
  // Update save options if provided
  if (opts) { $.extend(svgContext_.getSvgOption(), opts); }
  svgContext_.setSvgOption('apply', true);
  // no need for doctype, see https://jwatt.org/svg/authoring/#doctype-declaration
  const str = svgContext_.getCanvas().svgCanvasToString();
  // alert(str);
  svgContext_.call('saved', str);
}

function saveSvg(svgCode) {

    $("#saveloadmask").css("display","");

    var htmCode = compilHtml(svgCode);
    
    var formData = {
		id : idPageHtmlTop,
		urlfile : encodeURI(urlfile),
		src : svgCode, htm : htmCode,
	};
    
	$.ajax({
		url :  _p['web_plugin'] + 'adv_oel_tools_teachdoc/ajax/save/ajax.svgtobase.php',
		type : "POST",
		data : formData,
		success: function(data,textStatus,jqXHR) {
			if (data.indexOf("error")==-1) {
                window.onbeforeunload = null;
                document.body.onbeforeunload = null;
                var ur = _p['web_editor'] + '/index.php?id=' + idPage + '&fromsvg=' + returnfilename + '&cotk=' + global_csrf_oel_token;
	            window.location.href = ur;
			}
		},
		error: function (jqXHR, textStatus, errorThrown) {

		}
	});

}

function compilHtml(svgCode) {

    var hLayer = '';

    var svgObj = $(svgCode);

    var svgObjWidth = parseInt(svgObj.attr('width'));
    var svgObjHeight = parseInt(svgObj.attr('height'));

    svgObj.find('a').each(function() {
        var aobj = $(this);
        var xlinkhref = aobj.attr('xlink:href');
        if (xlinkhref.indexOf('input-')!=-1) {
            var rect = aobj.find('rect');
            var left = parseInt(rect.attr('x'));
            var top = parseInt(rect.attr('y'));
            var width = parseInt(rect.attr('width'));
            var height = parseInt(rect.attr('height'));
            var leftPourc = Math.round(((left / svgObjWidth) * 100)*10)/10;
            var topPourc = Math.round(((top / svgObjHeight) * 100)*10)/10;
            var widthPourc = Math.round(((width / svgObjWidth) * 100)*10)/10;
            var heightPourc = Math.round(((height / svgObjHeight) * 100)*10)/10;

            hLayer += '<input id="' + xlinkhref + '" style="position:absolute;';
            hLayer += 'z-index:20;left:' + leftPourc + '%;';
            hLayer += 'width:' + widthPourc + '%;height:' + heightPourc + '%;';
            hLayer += 'top:' + topPourc + '%;" />';

        }
    });

    return hLayer;
    
}

function EditorloadContentAndPrefs(curConfig,editor,defaultPrefs) {

	// alert('loadContentAndPrefs');
	initEditor();

    editorGlobal = editor;

	if (!curConfig.forceStorage &&
		(curConfig.noStorageOnLoad||
		!document.cookie.match(/(?:^|;\s*)svgeditstore=(?:prefsAndContent|prefsOnly)/)
		)
	) {
			// return;
	}
	
	// LOAD SVG CONTENT
	if (editor.storage && // Cookies do not have enough available memory to hold large documents
	(curConfig.forceStorage ||
	  (!curConfig.noStorageOnLoad &&
		document.cookie.match(/(?:^|;\s*)svgeditstore=prefsAndContent/))
	)
	) {
        const name = 'svgedit-' + curConfig.canvasName;
        const cached = editor.storage.getItem(name);
        if (cached) {
            // editor.loadFromString(cached);
        }
	}

    // LOAD PREFS
    Object.keys(defaultPrefs).forEach((key) => {
        const storeKey = 'svg-edit-' + key;
        if (editor.storage) {
            const val = editor.storage.getItem(storeKey);
            if (val) {
                defaultPrefs[key] = String(val); // Convert to string for FF (.value fails in Webkit)
            }
        } else if (window.widget) {
            defaultPrefs[key] = window.widget.preferenceForKey(storeKey);
        } else {
            const result = document.cookie.match(
            new RegExp('(?:^|;\\s*)' + Utils.regexEscape(
                encodeURIComponent(storeKey)
            ) + '=([^;]+)')
            );
            defaultPrefs[key] = result ? decodeURIComponent(result[1]) : '';
        }
    });

    editor.loadFromURL( _p['web_editor'] + '/' + urlfile);


}


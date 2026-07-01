var ludiicon = '/plugin/CStudio/img/base/oeltools32.png';
var ludiiconplus = '/plugin/CStudio/img/base/oeltools32plus.png';
var caneditparamicon = false;
var currentTeachdocLstIdsForCStudio = '';
var lastCStudioLpRouteState = '';
var cstudioLpRouteWatcherStarted = false;
var forcedCStudioStudentViewState = null;

$(document).ready(function($){
    initializeCStudioLpTools();
});

function initializeCStudioLpTools() {
    var teachdocLstIds = $('#teachdocLstIds').html();

    if (typeof teachdocLstIds == 'undefined') {
        teachdocLstIds = '';
    }

    if (teachdocLstIds != 'no') {
        cstudioProcessTeachdocIds(teachdocLstIds);

        if (teachdocLstIds == '') {
            getOelToolsId();
        } else {
            document.addEventListener('chamilo:lp-list-loaded', function () {
                cstudioProcessTeachdocIds(teachdocLstIds);
                getOelToolsId();
            });

            if (window.location.href.indexOf('/main/lp/lp_controller.php') !== -1) {
                getOelToolsId();
            }
        }
    }

    startCStudioLpRouteWatcher();

    setTimeout(function(){processExtraPour();},100);
}


function startCStudioLpRouteWatcher() {
    if (cstudioLpRouteWatcherStarted) {
        return;
    }

    cstudioLpRouteWatcherStarted = true;
    lastCStudioLpRouteState = getCStudioLpRouteState();

    var refresh = function () {
        setTimeout(refreshCStudioLpToolsForCurrentRoute, 50);
    };

    if (window.history) {
        ['pushState', 'replaceState'].forEach(function (methodName) {
            var originalMethod = window.history[methodName];

            if (typeof originalMethod !== 'function' || originalMethod.cstudioWrapped) {
                return;
            }

            var wrappedMethod = function () {
                var result = originalMethod.apply(this, arguments);
                refresh();

                return result;
            };

            wrappedMethod.cstudioWrapped = true;
            window.history[methodName] = wrappedMethod;
        });
    }

    window.addEventListener('popstate', refresh);
    window.addEventListener('hashchange', refresh);
    document.addEventListener('chamilo:lp-list-loaded', refresh);
    document.addEventListener('chamilo:lp-student-view-changed', function (event) {
        forcedCStudioStudentViewState = !!(event.detail && event.detail.isStudentView);

        if (forcedCStudioStudentViewState) {
            removeCStudioEditorButtons();
        }

        refresh();
    });

    document.addEventListener('click', function () {
        setTimeout(refreshCStudioLpToolsForCurrentRoute, 250);
        setTimeout(refreshCStudioLpToolsForCurrentRoute, 750);
    }, true);

    setInterval(refreshCStudioLpToolsForCurrentRoute, 1000);
}

function getCStudioLpRouteState() {
    return [
        window.location.pathname,
        window.location.search,
        cstudioIsStudentViewActive() ? 'student' : 'teacher',
        currentTeachdocLstIdsForCStudio
    ].join('|');
}

function refreshCStudioLpToolsForCurrentRoute() {
    var nextState = getCStudioLpRouteState();

    if (nextState === lastCStudioLpRouteState) {
        if (!cstudioCanUseEditorButtons(currentTeachdocLstIdsForCStudio)) {
            removeCStudioEditorButtons();
        }

        return;
    }

    lastCStudioLpRouteState = nextState;
    cstudioProcessTeachdocIds(currentTeachdocLstIdsForCStudio);
}

function cstudioProcessTeachdocIds(teachdocLstIds) {
    if (typeof teachdocLstIds == 'undefined' || teachdocLstIds === null) {
        teachdocLstIds = '';
    }

    currentTeachdocLstIdsForCStudio = String(teachdocLstIds || '');

    removeDuplicatedCStudioButtons();
    installCStudioPreviewBackButton(teachdocLstIds, 30);

    if (cstudioCanUseEditorButtons(teachdocLstIds)) {
        installCStudioCreateButton(30);
        installExtrasToolsOelTools(teachdocLstIds);
    } else {
        removeCStudioEditorButtons();
    }
}

function cstudioCanUseEditorButtons(teachdocLstIds) {
    return cstudioTeachdocIdsCanEdit(teachdocLstIds) && !cstudioIsStudentViewActive();
}

function cstudioCanShowPreviewBackButton(teachdocLstIds) {
    return cstudioTeachdocIdsCanEdit(teachdocLstIds)
        && getParamValueForOelTools('teachdoc') == 'edit'
        && getParamValueForOelTools('lp_id') != '';
}

function cstudioTeachdocIdsCanEdit(teachdocLstIds) {
    return String(teachdocLstIds || '').split(',').indexOf('canedit') !== -1;
}

function cstudioIsStudentViewActive() {
    if (forcedCStudioStudentViewState !== null) {
        return forcedCStudioStudentViewState;
    }

    var value = String(getParamValueForOelTools('isStudentView') || '').toLowerCase();

    return ['1', 'true', 'yes', 'on'].indexOf(value) !== -1;
}

function removeDuplicatedCStudioButtons() {
    $('#cstudio-lp-create-button').slice(1).remove();
    $('#cstudio-preview-back-button').slice(1).remove();
    $('#cstudio-preview-back-style').slice(1).remove();
}

function removeCStudioEditorButtons() {
    $('#cstudio-lp-create-button').remove();
}

function installCStudioCreateButton(retries) {
    if (!cstudioCanUseEditorButtons(currentTeachdocLstIdsForCStudio)) {
        removeCStudioEditorButtons();

        return false;
    }

    if ($('#cstudio-lp-create-button').length > 0) {
        removeDuplicatedCStudioButtons();
        refreshCStudioCreateButtonUrl();

        return true;
    }

    var actions = $('.section-header__actions').first();

    if (actions.length == 0) {
        if (retries > 0) {
            setTimeout(function () {
                installCStudioCreateButton(retries - 1);
            }, 250);
        }

        return false;
    }

    var cidQueryParams = getChamiloCidQueryParamsForCStudio();

    if (cidQueryParams == '') {
        if (retries > 0) {
            setTimeout(function () {
                installCStudioCreateButton(retries - 1);
            }, 250);
        }

        return false;
    }

    var h = '<a id="cstudio-lp-create-button" href="#" ';
    h += ' class="btn btn--plain-outline" ';
    h += ' alt="Studio Tools" title="Studio Tools">';
    h += '<img id="studioeltools" class="h-6" src="'+ ludiiconplus + '" ';
    h += ' alt="Studio Tools" title="Studio Tools" /> ';
    h += '</a>';

    actions.prepend(h);
    refreshCStudioCreateButtonUrl();

    $('#cstudio-lp-create-button').on('click', function (event) {
        refreshCStudioCreateButtonUrl();

        var href = $(this).attr('href');

        if (typeof href == 'undefined' || href == '' || href == '#') {
            event.preventDefault();
            console.log('CStudio: course context is not ready yet.');
        }
    });

    return true;
}

function refreshCStudioCreateButtonUrl() {
    var cidQueryParams = getChamiloCidQueryParamsForCStudio();

    if (cidQueryParams == '') {
        return false;
    }

    if (cidQueryParams.charAt(0) != '&') {
        cidQueryParams = '&' + cidQueryParams;
    }

    $('#cstudio-lp-create-button').attr(
        'href',
        '/plugin/CStudio/oel_tools_teachdoc_link.php?action=add' + cidQueryParams
    );

    return true;
}

function installCStudioPreviewBackButton(teachdocLstIds, retries) {
    if (!cstudioCanShowPreviewBackButton(teachdocLstIds)) {
        $('#cstudio-preview-back-button').remove();
        $('#cstudio-preview-back-style').remove();

        return false;
    }

    if ($('#cstudio-preview-back-button').length > 0) {
        removeDuplicatedCStudioButtons();

        return true;
    }

    if (!$('body').length) {
        if (retries > 0) {
            setTimeout(function () {
                installCStudioPreviewBackButton(teachdocLstIds, retries - 1);
            }, 250);
        }

        return false;
    }

    var lpId = parseInt(getParamValueForOelTools('lp_id'), 10);

    if (isNaN(lpId) || lpId <= 0) {
        return false;
    }

    var href = '/plugin/CStudio/oel_tools_teachdoc_link.php?action=redir&idLudiLP=' + encodeURIComponent(lpId);
    var label = 'Back';

    var style = '';
    style += '<style id="cstudio-preview-back-style">';
    style += '#cstudio-preview-back-button{position:fixed;top:14px;left:14px;z-index:2147483000;display:inline-flex;align-items:center;gap:6px;padding:8px 13px;border-radius:999px;background:#ffffff;color:#1f2937;border:1px solid #d1d5db;box-shadow:0 4px 14px rgba(0,0,0,.16);font:600 14px/1.2 Arial,sans-serif;text-decoration:none;}';
    style += '#cstudio-preview-back-button:hover{background:#f3f4f6;text-decoration:none;color:#111827;}';
    style += '#cstudio-preview-loading{position:fixed;inset:0;z-index:2147482999;display:none;align-items:center;justify-content:center;background:rgba(255,255,255,.72);font:600 15px/1.4 Arial,sans-serif;color:#111827;}';
    style += '#cstudio-preview-loading span{display:inline-flex;align-items:center;gap:10px;padding:14px 18px;border-radius:14px;background:#fff;border:1px solid #d1d5db;box-shadow:0 5px 18px rgba(0,0,0,.14);}';
    style += '#cstudio-preview-loading i{width:18px;height:18px;border:3px solid #d1d5db;border-top-color:#2563eb;border-radius:50%;display:inline-block;animation:cstudioPreviewSpin 1s linear infinite;}';
    style += '@keyframes cstudioPreviewSpin{to{transform:rotate(360deg)}}';
    style += '</style>';

    $('head').append(style);
    $('body').append('<a id="cstudio-preview-back-button" href="' + href + '" title="' + label + '" aria-label="' + label + '">‹ ' + label + '</a>');
    $('body').append('<div id="cstudio-preview-loading"><span><i></i>Opening CStudio editor...</span></div>');

    $('#cstudio-preview-back-button').on('click', function () {
        $('#cstudio-preview-loading').css('display', 'flex');
    });

    return true;
}

function installExtrasToolsOelTools(teachdocLstIds) {
    if (!cstudioCanUseEditorButtons(teachdocLstIds)) {
        return;
    }

    var action = getParamValueForOelTools('action');
    var lpId = getParamValueForOelTools('lp_id');

    if (action == 'add_item' && lpId != '') {
        if (teachdocLstIds.indexOf(',' + lpId + ',') != -1) {
            $('#doc_form').css('background-color','white').css('padding-top','50px').css('padding-bottom','70px').css('border-radius','20px').css('width','80%').css('margin-left','10%');
            $('#doc_form').html('<center><img style="width:50%;" src="/plugin/CStudio/img/base/oel_tools.jpg" /><br><br><img style="width:128px;margin-top:20px;" src="/plugin/CStudio/img/loadsaveline.gif" /></center>');
            $('#lp_sidebar').html('<center></center>');
            setTimeout(function(){
                window.location.href = '/plugin/CStudio/oel_tools_teachdoc_link.php?action=redir&idLudiLP=' + parseInt(lpId);
            },2000);
        }
    }

    if (
        (action == '' && lpId == '')
        || action == 'switch_view_mode' || action == 'delete' || action == 'move_lp_up'
        || action == 'move_lp_down' || action == 'list' || action == 'switch_attempt_mode'
        || action == 'move_down_category' || action == 'move_up_category'
    ) {
        installExtrasToolsLp(teachdocLstIds);
    }
}

//LP tools
function installExtrasToolsLp(teachdocLstIds) {
    if (!cstudioCanUseEditorButtons(teachdocLstIds)) {
        return;
    }

    caneditparamicon = cstudioTeachdocIdsCanEdit(teachdocLstIds);

    var feedUpdateSplit = teachdocLstIds.split(',');
    var anchors = document.querySelectorAll('.lp-panel a');

    for (var i = 0; i < anchors.length; i++) {
        for (var x = 0; x < feedUpdateSplit.length; x++){
            if (feedUpdateSplit[x] != '') {
                if (feedUpdateSplit[x] != 'canedit') {
                    var idlp = parseInt(feedUpdateSplit[x]);
                    var aObj = anchors[i];
                    var ctrurl = aObj.href + '&';

                    if (ctrurl.indexOf('lp_controller.php') != -1) {
                        if ((ctrurl.indexOf('lp_id=' + idlp + '&') != -1)
                            && ctrurl.indexOf('teachdoc=') == -1) {
                            if (ctrurl.indexOf('action=view') != -1) {
                                var labObj = $(aObj).find('.lp_content_type_label');
                                labObj.html('<em>TeachDoc tools</em>');

                                var iObj = $(aObj).prev();
                                if (iObj.length > 0) {
                                    iObj.attr('src',ludiicon);
                                    iObj.css('height','24px').css('width','24px');
                                }

                                if (caneditparamicon) {
                                    aObj.href = aObj.href + '&teachdoc=edit';
                                }
                            }

                            if (ctrurl.indexOf('action=add_item') != -1) {
                                var iObj = $(aObj).prev();
                                if (iObj.length > 0) {
                                    iObj.attr('src',ludiicon);
                                    iObj.css('height','24px').css('width','24px');
                                }
                                if (caneditparamicon) {
                                    aObj.href = aObj.href + '&teachdoc=edit';
                                }
                            }
                        }
                    }

                    if (ctrurl.indexOf('lp_id=' + idlp) != -1) {
                        if (ctrurl.indexOf('lp_controller.php') != -1) {
                            if (ctrurl.indexOf('action=copy') != -1) {
                                aObj.href = '#';
                                aObj.style.display = 'none';
                            }
                            if (ctrurl.indexOf('action=switch_scorm_debug') != -1) {
                                aObj.href = '#';
                                aObj.style.display = 'none';
                            }
                            if (ctrurl.indexOf('action=export_to_pdf') != -1) {
                                aObj.href = '#';
                                aObj.style.display = 'none';
                            }
                            if (ctrurl.indexOf('action=switch_attempt_mode') != -1) {
                                aObj.href = '#';
                                aObj.style.display = 'none';
                            }
                        }

                        if (ctrurl.indexOf('lp_update_scorm.php?') != -1) {
                            aObj.style.display = 'none';
                        }
                    }
                }
            }
        }
    }
}

function getChamiloCidQueryParamsForCStudio() {
    if (window.chamiloCidReq && window.chamiloCidReq.queryParams) {
        return window.chamiloCidReq.queryParams;
    }

    if (
        window.parent
        && window.parent !== window
        && window.parent.chamiloCidReq
        && window.parent.chamiloCidReq.queryParams
    ) {
        return window.parent.chamiloCidReq.queryParams;
    }

    if (
        window.top
        && window.top !== window
        && window.top.chamiloCidReq
        && window.top.chamiloCidReq.queryParams
    ) {
        return window.top.chamiloCidReq.queryParams;
    }

    var params = new URLSearchParams(window.location.search);
    var cid = params.get('cid');

    if (cid !== null && cid !== '') {
        var cidQueryParams = 'cid=' + encodeURIComponent(cid);
        var sid = params.get('sid');
        var gid = params.get('gid');
        var gradebook = params.get('gradebook');

        if (sid !== null && sid !== '') {
            cidQueryParams += '&sid=' + encodeURIComponent(sid);
        }

        if (gid !== null && gid !== '') {
            cidQueryParams += '&gid=' + encodeURIComponent(gid);
        }

        if (gradebook !== null && gradebook !== '') {
            cidQueryParams += '&gradebook=' + encodeURIComponent(gradebook);
        }

        return cidQueryParams;
    }

    return '';
}

function getParamValueForOelTools(param) {
    var urls = [];

    try { urls.push(window.location.href); } catch (e) {}
    try {
        if (window.parent && window.parent !== window) {
            urls.push(window.parent.location.href);
        }
    } catch (e) {}
    try {
        if (window.top && window.top !== window) {
            urls.push(window.top.location.href);
        }
    } catch (e) {}

    var reg = new RegExp('(\\?|&|^)' + param + '=(.*?)(&|$)');

    for (var i = 0; i < urls.length; i++) {
        var matches = urls[i].match(reg);
        if (matches != null) {
            return matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
        }
    }

    return '';
}

function getOelToolsId(){
    $.ajax({
        url : '/plugin/CStudio/ajax/oel_tools_teachdoc_getids.php',
        type: 'GET',
        success: function(data,textStatus,jqXHR){
            if (data.indexOf('KO') == -1) {
                teachdocLstIds = data;
                window.localStorage.setItem('teachdocLstIds',data);
                cstudioProcessTeachdocIds(teachdocLstIds);
            } else {
                console.log('oel_tools_teachdoc_getids KO');
            }
        },error: function (jqXHR, textStatus, errorThrown){
            console.log('oel_tools_teachdoc_getids KO');
        }
    });
}

function getIdsLocalStorage() {
    var mem_context_data = '';

    if (localStorage) {
        mem_context_data = window.localStorage.getItem('teachdocLstIds');

        if (mem_context_data === null || mem_context_data == 'null'){
            mem_context_data = '';
        }
        if (mem_context_data === undefined) {
            mem_context_data = '';
        }
        if (typeof mem_context_data == 'undefined') {
            mem_context_data = '';
        }
        if (mem_context_data != '' && mem_context_data.indexOf('canedit') === -1) {
            cstudioProcessTeachdocIds(mem_context_data);
        }
    }
}

function processExtraPour() {
    var mem_idstudio = window.localStorage.getItem('idstudio');
    var mem_pourcstudio = window.localStorage.getItem('pourcstudio');

    if (mem_idstudio === null || mem_idstudio == 'null'){
        mem_idstudio = '0';
    }
    if (mem_idstudio === undefined) {
        mem_idstudio = '0';
    }
    if (typeof mem_idstudio == 'undefined') {
        mem_idstudio = '0';
    }
    if (parseInt(mem_idstudio) > 0) {
        installPourcentageToolsLp(mem_idstudio,mem_pourcstudio);

        var lk = '/plugin/CStudio/ajax/sco/scorm-save-location.php';
        $.ajax({
            url: lk + '?loc=0&id=' + mem_idstudio + '&pour=' + mem_pourcstudio + '&' + getChamiloCidQueryParamsForCStudio()
        }).done(function(){
            window.localStorage.setItem('idstudio',0);
            window.localStorage.setItem('pourcstudio',0);
        });
    }
}

function installPourcentageToolsLp(mem_idstudio,mem_pourcstudio) {
    var anchors = document.getElementsByTagName('a');

    for (var i = 0; i < anchors.length; i++) {
        var idlp = parseInt(mem_idstudio);
        var aObj = anchors[i];
        var hrefObj =  aObj.href;
        if (typeof hrefObj == 'undefined') {hrefObj = '';}
        hrefObj = hrefObj + '&';

        if (hrefObj.indexOf('lp_controller.php') != -1) {
            if ((hrefObj.indexOf('lp_id=' + idlp + '&') != -1)) {
                var iObj = $(aObj).prev();
                var trObj = $(iObj).parent().parent();
                var valdefault = trObj.find('td:eq(3)').html();

                if (typeof valdefault == 'undefined') {valdefault = '';}

                if (valdefault.indexOf('>0%') != -1) {
                    trObj.find('td:eq(3)').html('<center><span style="font-weight:bold;color:green;" >' + mem_pourcstudio + '%</span></center>');
                }

                valdefault = trObj.find('td:eq(1)').text();
                if (typeof valdefault == 'undefined') {valdefault = '';}

                if (valdefault.indexOf('%') != -1) {
                    var objProgress = trObj.find('td:eq(1)').find('.progress').find('#progress_bar_value');
                    objProgress.css('width',mem_pourcstudio + '%');
                    objProgress.html(mem_pourcstudio + '%');
                }
            }
        }
    }
}

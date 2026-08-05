
function getCStudioParentUrlForEdit(){
    try {
        if (window.top && window.top.location && window.top.location.href) {
            return window.top.location.href;
        }
    } catch (ignoreTopLocationError) {
        // Keep the legacy fallback below.
    }

    try {
        if (window.parent && window.parent.location && window.parent.location.href) {
            return window.parent.location.href;
        }
    } catch (ignoreParentLocationError) {
        // Keep the local fallback below.
    }

    return window.location.href;
}

function getCStudioQueryParam(url, name){
    var escapedName = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + escapedName + '=([^&#]*)');
    var results = regex.exec(url);

    if (!results) {
        return '';
    }

    return decodeURIComponent(results[1].replace(/\+/g, ' '));
}

function appendCStudioQueryParam(query, name, value){
    if (value === '') {
        return query;
    }

    return query + '&' + encodeURIComponent(name) + '=' + encodeURIComponent(value);
}

function getCStudioQueryParamFromUrls(urls, name, defaultValue){
    for (var i = 0; i < urls.length; i++) {
        if (!urls[i]) {
            continue;
        }

        var value = getCStudioQueryParam(urls[i], name);

        if (value !== '') {
            return value;
        }
    }

    return typeof defaultValue === 'undefined' ? '' : defaultValue;
}

function buildCStudioEditUrl(parentUrl, mainUrl, lpId, fallbackUrl){
    var parsedLpId = parseInt(lpId, 10);

    if (!parsedLpId || parsedLpId < 1) {
        return '';
    }

    var query = '?action=redir';
    var sourceUrls = [parentUrl, fallbackUrl || ''];

    query = appendCStudioQueryParam(query, 'idLudiLP', String(parsedLpId));
    query = appendCStudioQueryParam(query, 'first', '1');
    query = appendCStudioQueryParam(query, 'cid', getCStudioQueryParamFromUrls(sourceUrls, 'cid'));
    query = appendCStudioQueryParam(query, 'sid', getCStudioQueryParamFromUrls(sourceUrls, 'sid', '0'));
    query = appendCStudioQueryParam(query, 'gid', getCStudioQueryParamFromUrls(sourceUrls, 'gid', '0'));
    query = appendCStudioQueryParam(query, 'gradebook', getCStudioQueryParamFromUrls(sourceUrls, 'gradebook', '0'));

    return mainUrl + 'plugin/CStudio/oel_tools_teachdoc_link.php' + query;
}

function getCStudioAccessibleDocuments(){
    var documents = [document];

    try {
        if (window.parent && window.parent.document && window.parent.document !== document) {
            documents.push(window.parent.document);
        }
    } catch (ignoreParentDocumentError) {
        // Keep local document only.
    }

    try {
        if (window.top && window.top.document && documents.indexOf(window.top.document) === -1) {
            documents.push(window.top.document);
        }
    } catch (ignoreTopDocumentError) {
        // Keep already collected documents.
    }

    return documents;
}

function redirectCStudioEditLink(link, parentUrl, mainUrl, defaultEditUrl){
    if (!link) {
        return false;
    }

    var legacyHref = link.getAttribute('href') || link.href || '';

    if (legacyHref.indexOf('oel_tools_teachdoc_link.php') === -1 || legacyHref.indexOf('idLudiLP') === -1) {
        return false;
    }

    var legacyLpId = getCStudioQueryParam(legacyHref, 'idLudiLP');
    var editUrl = defaultEditUrl;

    if (legacyLpId !== '') {
        editUrl = buildCStudioEditUrl(parentUrl, mainUrl, legacyLpId, legacyHref);
    }

    if (editUrl === '') {
        return false;
    }

    link.setAttribute('href', editUrl);
    link.setAttribute('target', '_parent');
    link.setAttribute('data-cstudio-lp-edit-flow-rewritten', '1');
    link.onclick = function (event) {
        event.preventDefault();

        try {
            window.top.location.href = this.href;
        } catch (ignoreTopRedirectError) {
            window.parent.location.href = this.href;
        }

        return false;
    };

    if (!link.getAttribute('title')) {
        link.setAttribute('title', 'Edit');
    }

    return true;
}

function replaceLegacyCStudioEditLinks(parentUrl, mainUrl, defaultEditUrl){
    var documents = getCStudioAccessibleDocuments();

    for (var docIndex = 0; docIndex < documents.length; docIndex++) {
        var currentDocument = documents[docIndex];
        var links = currentDocument.querySelectorAll('a[href*="oel_tools_teachdoc_link.php"][href*="idLudiLP"]');

        for (var i = 0; i < links.length; i++) {
            redirectCStudioEditLink(links[i], parentUrl, mainUrl, defaultEditUrl);
        }
    }
}

function installCStudioLegacyEditLinkInterceptors(parentUrl, mainUrl, defaultEditUrl){
    var documents = getCStudioAccessibleDocuments();

    for (var docIndex = 0; docIndex < documents.length; docIndex++) {
        var currentDocument = documents[docIndex];

        if (currentDocument.__cstudioEditLinkInterceptorInstalled) {
            continue;
        }

        currentDocument.__cstudioEditLinkInterceptorInstalled = true;
        currentDocument.addEventListener('click', function (event) {
            var target = event.target;

            if (!target || !target.closest) {
                return;
            }

            var link = target.closest('a[href*="oel_tools_teachdoc_link.php"][href*="idLudiLP"]');

            if (!link) {
                return;
            }

            if (redirectCStudioEditLink(link, parentUrl, mainUrl, defaultEditUrl)) {
                event.preventDefault();
                event.stopPropagation();

                try {
                    window.top.location.href = link.href;
                } catch (ignoreTopRedirectError) {
                    window.parent.location.href = link.href;
                }
            }
        }, true);
    }
}

function scheduleLegacyCStudioEditLinkRewrite(parentUrl, mainUrl, defaultEditUrl){
    var attempts = 0;
    var maxAttempts = 12;

    replaceLegacyCStudioEditLinks(parentUrl, mainUrl, defaultEditUrl);
    installCStudioLegacyEditLinkInterceptors(parentUrl, mainUrl, defaultEditUrl);

    var interval = window.setInterval(function () {
        attempts++;
        replaceLegacyCStudioEditLinks(parentUrl, mainUrl, defaultEditUrl);

        if (attempts >= maxAttempts) {
            window.clearInterval(interval);
        }
    }, 500);
}

function installEdit(){
    
    var icoAd = 'url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAZCAYAAAArK+5dAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAAE64AABOuAbGhbCcAAARKSURBVEhLrVRraFtlGH7Pl5xzck6TNGmWLjPpWqStOnRM50SxVsqcG3PVX2IVb6CoKJ1uiGOrs6gwKdKOIfhDpPOGE4bu0ipMZeAPhzCEuuLa1PSWNPelaXOSk3M/fudbOqpu68U9/85zHp7ney/fR8ENQu+n/WspCnYhZNuNbKgZU4YsS9/dkIC+/v5bsfEhu93+GEVRyOJUVYVioWCQj/+Dn8+dC/EcdwCb71xsXhIEUBUF2YhqlUjk836fx/MGQuiRfEFwG4Zh0zSNmCuKQjSrDvg2nHBNCEqvDNRzTbW+Ghqh2WQmwwuFAkXMTdOSiasKOBlOeAuGfiReVp9PlzWWwiXU+7xOUFQhnUnjIjQ7likUmIdWPORTkUQgJ2n7sor+qmGajMWJ5TIwwixs5JCpi0VlfHqyVBLFMxqF9q6ogpOReCAraa/nFeNF3TSrLK4gijA0MQ3noykwTETdXFON1rhdv5Ql6eB8SZhadgUDYzPejKLvzqtGp2qYPosrSRL8MT4FsUwONF0HjrZDS6h2+InmQGdVFffrHU1N2rLWdDAcp/Gpn51TjT0L5qqmw4XxaYimLxFzCyxN5+ws3805eWJucUtW8EM07bhUlF9Jy3qfZphEL6sanA9HYDqVIRq8/+B2sLkdtwVfOLjtgVOErOC6FZyOzDjTJeWZrKy/u2AuygoMT+C2pLNEY5l7ODaxObi2i6O4QUIuwjUDvh+L8Zmy3pGXtHdwW9wWJ8oyjEzPwEQygwdqEvNqB5u6pbbmcNDj/PrNh+6+3KtFuGrAYCTFpFTzUUE3uhTDDFlXRsbXPxyLY/MU7j9pL7gcTG5DwPdxQ7Xr6L6tWwRC/gv/CRiYTFA5VWkrqnqvrJsNljl+ArB5AsZiSXyXLpuztF2+py7wWUON+8jbO+7LEfIq+MeQB8Ix26wOLfOqfqakGazFWRtycSoGw5NRorHAM7TU1lj3UU9761sV6pq4UoFpmtS8brYVVP2rBXPrtGMzSRiJxonGAm5L6c6b/F8wFOquUNfFlYDcfHF9XRW73wAIWt8K7vN4IkWGurDnLpYVG9d4jnl4tvv9XS1lQi4BEpBKpfCTYexsdrGbNnt5/EYZMIk3ZTQ6A1Ll2eUZu9Lorz4RcDvf62l/MEXIZYAESKrpw4O8l0aUt9HJQD0oMIpPbu28BTtCxsZ1/rO+Km7Ph+2tMUIuE+Sx0/jA/aORqb3VbqfbSlRmc+Bi7BArSdZvY1MocLa/Y/v23775XLSIlcB2+OgJ6sKfI1tG/pp8eiwyZdN1FfxeN9RyNHAsIzh47ifahp76/fiXJG2lQMX5Ag8mbBAEgR4avgjHT/8IiXRWcrHMUGvQ19vRHHr5k8e3zVX0KwbKzWZrONa+1ck75PrQuhEK0P54Mvskxzte8rn5vofvuj1Z0a4K1GsHPmBy+bn1+XyBDgb8Zb/fn+7p6lzWCi4NgL8BrsYOB26chCcAAAAASUVORK5CYII=")';

    var sty = " style='background-image:"+icoAd+";background-position:center center;background-repeat:no-repeat;";
    sty += " border:1px solid #8dcbf3;background-color:#ffffff;height:32px;width:32px;font-size:16px;line-height:19px;padding:0px;";
    sty += " position:absolute;top:10px;right:5px;text-align:center;border-radius:50%;border:1px solid #8dcbf3;cursor:pointer;' ";
    
    if(window.parent){
        var url = window.parent.location;
        if(url.href.indexOf("teachdoc=edit")!=-1){
            var parentUrl = getCStudioParentUrlForEdit();
            var mainIndex = parentUrl.indexOf('main/');
            var main = mainIndex > -1 ? parentUrl.substring(0, mainIndex) : '/';
            var editUrl = buildCStudioEditUrl(parentUrl, main, localIdTeachdoc);

            if (editUrl === '') {
                return;
            }

            scheduleLegacyCStudioEditLinkRewrite(parentUrl, main, editUrl);

            var renderBtn =  '<a ' + sty + ' class="menu-button fa icons" href="' + editUrl + '" target="_parent" onclick="window.parent.location.href=this.href; return false;" >&nbsp;</a>';
            var panelTools = document.querySelector(".deco-teachdoc");
            if(panelTools){
                panelTools.innerHTML = renderBtn;
            } else {
                panelTools = document.getElementById("nav-bottom");
                if(panelTools){
                    panelTools.innerHTML = panelTools.innerHTML + renderBtn;
                }
            }
            var panel = document.querySelector(".panel-teachdoc-large");
            if(panel){
                panelTools = document.getElementById("nav-bottom");
                if(panelTools){
                    renderBtn = renderBtn.replace("right:5px;","left:200px;");
                    renderBtn = renderBtn.replace("background-color:#ffffff;","background-color:black;");
                    renderBtn = renderBtn.replace("#8dcbf3","black");
                    renderBtn = renderBtn.replace("#8dcbf3","black");
                    renderBtn = renderBtn.replace("32px","38px");
                    renderBtn = renderBtn.replace("32px","38px");
                    panelTools.innerHTML = panelTools.innerHTML + renderBtn;
                }
            }
        }

    }

}

var correctPositionLazy = 1000;
var correctPositionAttempts = 0;
var correctPositionMaxAttempts = 5;

function correctPosition() {

    var panel = document.querySelector(".panel-teachdoc");
    if(!panel){
        panel = document.querySelector(".panel-teachdoc-large");
    }

    if(panel){
        var panelHeight = panel.clientHeight;
        var haut = window.innerHeight-70;

        if(panelHeight<haut){
            panel.style.top = ((haut-panelHeight)/2) + 'px';
            if(correctPositionAttempts < correctPositionMaxAttempts){
                correctPositionAttempts++;
                correctPositionLazy += 2000;
                setTimeout(correctPosition, correctPositionLazy);
            }
        }else{
            panel.style.top = '10px';
            panel.style.marginTop = '50px';
        }
    }

}

setTimeout(function(){
    if (typeof correctPosition === "function") { 
        correctPosition();
    }
    if (typeof applyThemeToColors === "function") { 
        applyThemeToColors();
    }
    if (typeof adaptScoLive === "function") { 
        adaptScoLive();
    }
    setTimeout(function(){
        if (typeof listIndexTable === "function") { 
            listIndexTable();
        }
        if (typeof adaptContent === "function") { 
            adaptContent();
        }
    },350);
},100);

setTimeout(function(){
    installEdit();
},1000);

setTimeout(function(){
    installEdit();
},2500);

setTimeout(function(){
    installEdit();
},5000);

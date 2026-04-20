
var globalCoursePourc = 0;
var globalLevelDoc = 2;

function ctrlpl(i,behavior,pid,pagebehav) {
    
    if (typeof scoPageAPI == 'undefined') {
        // declare global scoPageAPI
        scoPageAPI = 0;
    }
    //pid
    if (scoPageAPI=='') {scoPageAPI=0;}
    scoPageAPI = parseInt(scoPageAPI);
    
    //Main Menu
    if (behavior==-100) {
        if(pid<scoPageAPI||pid==scoPageAPI){
            lpl(i,behavior,pagebehav,pageBindex);
        } else {
            return false;
        }
    }
    
    //If is next page and normal progress
    if (pid==pageBindex+1&&pagebehav==1) {
        setContextData();
        Cxlogs_insert('launch','page' + basePages[pageBindex],0);
        lpl(i,behavior,pagebehav,pageBindex);
        return true;
    }

    //if is next page
    if (pid==scoPageAPI+1&&pageBindex==scoPageAPI) {

    } else {
        if (pid>scoPageAPI&&pid>1&&pagebehav!=0) {
            return false;
        }
    }

    var haveRedir = false;

    var ctrPage = LUDI.pageIsOk();
    if (scoPageAPI>pageBindex||i<pageBindex) {
        ctrPage = true;
    }
    if (pid<scoPageAPI||pid==scoPageAPI) {
        ctrPage = true;
    }
    if (context_data_resolve.indexOf(";"+pageBindex+";")!=-1) {
        ctrPage = true;
    }
    if (pid>pageBindex+1&&pid>scoPageAPI&&pagebehav!=0) {
        ctrPage = false;
    } else {
        if (ctrPage) {
            if (pid>scoPageAPI) {
                context_data_resolve += pageBindex+';';
            }
            setContextData();
            Cxlogs_insert('launch','page' + basePages[pageBindex],0);
            lpl(i,behavior,pagebehav,pageBindex);
            haveRedir = true;
        } else {
            showTopMessage();
        }
    }

    // if is next page
    if (pid==scoPageAPI+1&&pageBindex==scoPageAPI) {
    
    } else {
        if (haveRedir==false) {
            if (pagebehav==0) {
                Cxlogs_insert('launch','page' + basePages[pageBindex],0);
                lpl(i,behavior,-99,pageBindex);
            }
        }
    }

}

function fakeLoadInPanel() {

    var fakeLoad = "<div class=fakeLoadFrame >";
    fakeLoad += "<div class='loadbarre' style='width:340px;' /></div>";
    fakeLoad += "<div class='loadbarre loadbarre1' style='width:240px;display:none;' /></div>";
    fakeLoad += "<div class='loadbarre loadbarre2' style='width:240px;display:none;' /></div>";
    fakeLoad += "<div class='loadbarre loadbarre3' style='width:340px;display:none;' /></div>";
    fakeLoad += "<div class='loadbarre loadbarre4' style='width:140px;display:none;' /></div>";
    fakeLoad += "<div class='loadbarre loadbarre5' style='width:240px;display:none;' /></div>";
    fakeLoad += "<div class='loadbarre loadbarre6' style='width:140px;display:none;' /></div>";
    fakeLoad += "</div>";
    
    var panel = document.querySelector(".panel-teachdoc");
    if(!panel){
        panel = document.querySelector(".panel-teachdoc-large");
    }
    if(panel){
        panel.style.top = '90px';
        panel.style.height = '350px';
        panel.innerHTML = fakeLoad;
    }

    setTimeout(function(){
        $(".loadbarre1").css('display','block');
        setTimeout(function(){
            $(".loadbarre2").css('display','block');
            setTimeout(function(){
                $(".loadbarre3").css('display','block');
                setTimeout(function(){
                    $(".loadbarre4").css('display','block');
                    setTimeout(function(){
                        $(".loadbarre5").css('display','block');
                        setTimeout(function(){
                            $(".loadbarre6").css('display','block');
                        },200);
                    },200);
                },200);
            },200);
        },200);
    },200);

}

function lpl(id,behavior,pagebehav,fromid) {
    
	if(id==0||id==-1){
		return false;
	}
	
    if (localStorage) {
        try {
            window.localStorage.setItem('xlogs_fromid_' + localIdTeachdoc,fromid);
        } catch(err) {
        }
    }

    var ipg = 30;
    for(var ip=0; ip < ipg; ip++){
        $(".NodeLvl" + ip).removeClass("activeli");
    }

    $(".pgh" + id).css("background-color","#A9D0F5");

    fakeLoadInPanel();
    
    $(".fixed-top-nav").css("opacity","0.9");
    $(".div-teachdoc").css("opacity","0.9");
    $(".panel-teachdoc").css("opacity","0.9");
    $(".panel-teachdoc-large").css("opacity","0.9");

    if(window.parent.document) {
        if(window.parent.document.getElementById('content_id')) {
            window.parent.document.getElementById('content_id').style.backgroundColor = window.document.body.style.backgroundColor;
        }
    }
    
    var v = Math.floor(Math.random() * 10000);

    if ((pagebehav+"").indexOf("load=")!=-1) {
        gotToPage("teachdoc-"+id+".html?"+pagebehav+"&v="+v+"&b=" + additional_params);
    }else{
        gotToPage("teachdoc-"+id+".html?v="+v+"&b="+pagebehav + additional_params);
    }
    
}

var timeLoadGoPage = 50;
var urlGoAttemps = "";

function gotToPage(url){
    if (urlGoAttemps!=url) {
        urlGoAttemps = url;
        timeLoadGoPage = timeLoadGoPage + controlLogsToTime();
        setTimeout(function(){
            window.location.href = urlGoAttemps;
        },timeLoadGoPage);    
    }
}

function installProgress() {
    
    if (typeof progressBtop == 'undefined') {
        return false;
    }

    var _percent = 0;
    var progressStep = Math.round(100 / progressBtop) - 1;
    var pageBindexVirtual = pageBindex;
    var forceFinish = false;
    pageBindexVirtual = 0;

    if (typeof getLMSLocation === "function") {
        pageBindexVirtual = parseInt(getLMSLocation());
    }

    if (pageBindex==progressBtop&&pageBindexVirtual==(progressBtop-1)) {
        forceFinish = true;
    }
    
    if (pageBindexVirtual==progressBtop||progressBtop==1||forceFinish) {
        _percent = 100;
        globalCoursePourc = 100;
        setTimeout(function(){
            if (pageBindex==progressBtop) {
                Cxlogs_insert('finish','finish','course' + localIdTeachdoc,0,'');
                CheckLMSFinishFinal();
            }
            $(".barre-ludi-progress").css('background-color','#04B45F');
        },1200);
        showLevelBlocs("E");
        hideLevelBlocs("F");
    } else {
        if(progressBtop>0&&pageBindexVirtual>0){
            _percent = Math.round((pageBindexVirtual/progressBtop)*100);
            if (_percent>100) {_percent=100;}
            globalCoursePourc = _percent;
        }
    }

    var textProgress = document.querySelector(".left-text-progress");
    var panelProgress = document.querySelector(".barre-ludi-progress");
    var leftProgress = document.querySelector(".left-barre-progress");

    if (panelProgress) {
        
        if (progressStep>0) {
            
            panelProgress.style.width = _percent - progressStep + "%";
            leftProgress.style.width = _percent - progressStep + "%";

            if (_percent>2) {
                textProgress.innerHTML = parseInt(_percent-2) + "%";
            } else {
                textProgress.innerHTML = parseInt(_percent) + "%";
            }
            
            setTimeout(function(){
                
                $(".barre-ludi-progress").animate({
                    width: _percent + "%"
                },1000,function(){
                });
                
                $(".left-barre-progress").animate({
                    width: _percent + "%"
                },1000,function(){
                });

            },100);

            if (_percent>2) {
                setTimeout(function(){
                    textProgress.innerHTML = parseInt(_percent-2) + "%";
                    setTimeout(function(){
                        textProgress.innerHTML = parseInt(_percent-1) + "%";
                        setTimeout(function(){
                            textProgress.innerHTML = parseInt(_percent) + "%";
                        },300);
                    },300);
                },300);
            }
            
        } else {
            panelProgress.style.width = _percent + "%";
        }
        
        setTimeout(function(){
            var jid = parseInteger(catPages[pageBindex]);
            collapseOpen(jid);
        },300);
    }

}

function behaviorGetParamVal(param){
	
	var u = document.location.href;
	var reg = new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
	matches = u.match(reg);
	
	if(matches==null){return '';}
	
	var vari=matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
	
	return vari;
	
}

function installVirtualClick(){
    var element = document.querySelectorAll("a.btn-btnTeach");
    if(element&&element.length>0){
        for(let i = 0; i < element.length; i++){
            var aObj = element[i];
            aObj.href="javascript:void(0);";
            aObj.addEventListener("click", function() {
                virtualClick(this);
            });
        }
    }else{
        setTimeout(function(){
            installVirtualClick();
        },300);
    }
}

function virtualClick(obj){
    
    var aclik = $(obj);
    var ascri = obj.parentNode.querySelectorAll("span.datatext4");
    if(ascri.length==1){
        var evalScript = ascri[0].innerHTML;

        if (evalScript.indexOf("url@")!=-1) {
            var linkW = evalScript.replace('url@','');

            fakeLoadInPanel();

            $(".fixed-top-nav").css("opacity","0.9");
            $(".div-teachdoc").css("opacity","0.9");
            $(".panel-teachdoc").css("opacity","0.9");
            $(".panel-teachdoc-large").css("opacity","0.9");
            top.location.href = linkW;

        } else {

            if (evalScript.indexOf("dow@")!=-1) {
                var linkW = evalScript.replace('dow@','');
                top.open(linkW,'_blank')
            }else{
                eval(evalScript);
            }
        }

    }
    
}

function isObjSub(tObj){

    if (tObj.find(".dotSubLudi").length>0) {
        return true;
    } else {
        return false;
    }

}

function applyLifeAnim(){
    applyLifeAnimOne(parseInt(context_data_life)+1);
    applyLifeAnimOne(parseInt(context_data_life)+2);
    applyLifeAnimOne(parseInt(context_data_life)+3);
}

function applyLifeAnimOne(i){

    $("#ui-life-bar").animate({
        right: "2px"
    },50, function() {
        $("#ui-life-bar").animate({
            right: "0px"
        },50, function() {
            $("#ui-life-bar").animate({
                right: "2px"
            },50, function() {
                $("#ui-life-bar").animate({
                    right: "0px"
                },50, function() {
                });
            });
        });
    });

    var objAnim = $("#ui-life-bar").find("#lifeopt"+i);
    objAnim.animate({
        width: "0px"
    },1000, function() {
    });

}

function applyLifeHideOne(i){
    var objAnim = $("#ui-life-bar").find("#lifeopt"+i);
    objAnim.css("width","0px");
}

function cleanDomTexts(){
 
    var oriClass = '';
    var objsClass = '';
    $(".teachdoctext").each(function(index){
        var obj = $(this);
        oriClass = getTableClassOrigine(obj);
        objsClass = getObjClassOrigine(obj);
        obj.removeClass();
        obj.addClass("teachdoctext").addClass(oriClass);
        obj.find('td').addClass(objsClass);
    });
    $(".quizzTextqcm").each(function(index){
        var obj = $(this);
        oriClass = getTableClassOrigine(obj);
        obj.removeClass();
        obj.addClass("quizzTextqcm").addClass(oriClass);
    });
    $(".qcmbarre").each(function(index){
        var obj = $(this);
        oriClass = getTableClassOrigine(obj);
        obj.removeClass();
        obj.addClass("qcmbarre").addClass(oriClass);
    });

}

function getTableClassOrigine(objT){
	var dhC = '';
	if (objT.hasClass("displayhideCondiMA")) {
		dhC = "displayhideCondiMA";
	}
	if (objT.hasClass("displayhideCondiMB")) {
		dhC = "displayhideCondiMB";
	}
	if (objT.hasClass("displayhideCondiMC")) {
		dhC = "displayhideCondiMC";
	}
	if (objT.hasClass("displayhideCondiMD")) {
		dhC = "displayhideCondiMD";
	}
	if (objT.hasClass("displayhideCondiME")) {
		dhC = "displayhideCondiME";
	}
	if (objT.hasClass("displayhideCondiMF")) {
		dhC = "displayhideCondiMF";
	}
	return dhC;
}

function getObjClassOrigine(objT){
	var dhC = '';
	if (objT.hasClass("BoxTxtClean")) {
		dhC = "BoxTxtClean";
	}
	if (objT.hasClass("BoxTxtRound")) {
		dhC = "BoxTxtRound";
	}
	if (objT.hasClass("BoxDashBlue")) {
		dhC = "BoxDashBlue";
	}
	if (objT.hasClass("BoxPostit")) {
		dhC = "BoxPostit";
	}
	if (objT.hasClass("BoxShadowA")) {
		dhC = "BoxShadowA";
	}
	if (objT.hasClass("BoxAzur")) {
		dhC = "BoxAzur";
	}
	if (objT.hasClass("BoxCadre")) {
		dhC = "BoxCadre";
	}
	return dhC;
}

installMenuLocation();
installImgOverviewEvents();
installProgress();
cleanDomTexts();

setTimeout(function(){
    controlLevelDocLocation();
    installVirtualClick();
},200);

setTimeout(function(){
    applyCheckExercices();
    resizeAutoIframe();
    sendLogsToTable();
},1100);

function collapselpl(i) {

    if ($('.mainCatMenu' + i).find('.icon-arrow').hasClass("open")) {
        
        subCatMenuClose(i);

        $('.mainCatMenu' + i).find('.icon-arrow').removeClass("open");
        $('.mainCatMenu' + i).find('.icon-arrow').addClass("close");
    } else {
        
        subCatMenuOpen(i);

        $('.mainCatMenu' + i).find('.icon-arrow').removeClass("close");
        $('.mainCatMenu' + i).find('.icon-arrow').addClass("open");
    }

}

function collapseOpen(i) {

    if (i != 0) {
        speedCatMenuOpen(i);
    }

    $('.mainCatMenu' + i).find('.icon-arrow').removeClass("close");
    $('.mainCatMenu' + i).find('.icon-arrow').addClass("open");
    
    // for each array catPages
    for (var j = 0; j < catPages.length; j++) {
        if (catPages[j] != i) {
            var jid = catPages[j];
            if (jid != 0) {
                if ($('.mainCatMenu' + jid).find('.icon-arrow').hasClass("open")) {
                    collapseClose(jid);
                }
            }
        }
    }

}


function collapseOpenAll() {

 // for each array catPages
 for (var j = 0; j < catPages.length; j++) {
    var jid = catPages[j];
    if (jid != 0) {
        if ($('.mainCatMenu' + jid).find('.icon-arrow').hasClass("open")) {
            speedCatMenuOpen(jid);
        }
    }
}

}

function collapseClose(i) {

    subCatMenuClose(i);
    $('.mainCatMenu' + i).find('.icon-arrow').removeClass("open");
    $('.mainCatMenu' + i).find('.icon-arrow').addClass("close");
    
}

function speedCatMenuOpen(i) {

    $('.subCatMenu' + i).each(function(index){

        var obj = $(this);
        var leveldoc = obj.attr('leveldoc');
        if (leveldoc==1&&globalLevelDoc==1) {
            obj.css("display","block");
        }
        if (leveldoc==2) {
            obj.css("display","block");
        }
        if (leveldoc==3&&globalLevelDoc==3) {
            obj.css("display","block");
        }

	});

}

function subCatMenuOpen(i) {

    $('.subCatMenu' + i).each(function(index){

        var obj = $(this);
        var leveldoc = obj.attr('leveldoc');
        if (leveldoc==1&&globalLevelDoc==1) {
            obj.slideDown(300);
        }
        if (leveldoc==2) {
            obj.slideDown(300);
        }
        if (leveldoc==3&&globalLevelDoc==3) {
            obj.slideDown(300);
        }

	});

}

function subCatMenuClose(i) {
    
    $('.subCatMenu' + i).slideUp(300);

}
var xlogs_colls = new Array();
var xlogs_coll_count = 0;
var stop_sendlogs = false;

function Cxlogs_coll() {
    
	this.id;
	this.title;
	this.definition;
	this.result;
    this.attempts;
    this.answers;
	this.sendtotable;
    this.interactionType;
	
}

var memLogsend = "@"

function Cxlogs_insert(interactionType,title,definition,result,answers) {

    if (typeof scoPageAPI == 'undefined') {
        return false;
    }
    
    if (scoPageAPI=='') {scoPageAPI=0;}
    scoPageAPI = parseInt(scoPageAPI);
    
    var habelog = true;
    var isQuizzLog = false;
    if (typeof localIdTeachdoc != 'undefined') {
        if (title.indexOf('quizz_')!=-1) {
            title = title + '_' + localIdTeachdoc;
            isQuizzLog = true;
        }
        if (title.indexOf('hvp_')!=-1||title.indexOf('h5p_')!=-1) {
            title = title + '_' + localIdTeachdoc;
            isQuizzLog = true;
        }
    }

    if (isQuizzLog) {
        if (scoPageAPI>pageBindex) {
            habelog = false;
        }
    }

    var memID = interactionType + title + definition + '@';
    
    if (typeof memLogsend == 'undefined') {
    
        memLogsend = "@";
    
    } else {

        if (memLogsend.indexOf(memID)!=-1) {
            habelog = false;
        } else {
            memLogsend = memLogsend + memID;
        }

        if (habelog) {
            stop_sendlogs = true;
            var lgBloc = new Cxlogs_coll();
            lgBloc.title = title;
            lgBloc.interactionType = interactionType;
            lgBloc.definition = definition;
            lgBloc.result = result;
            lgBloc.answers = answers;
            lgBloc.sendtotable = 0;
            Cxlogs_colls_Add(lgBloc);
        }
        
    }

}

function Cxlogs_colls_Add(Elem) {
    
    Elem.id = xlogs_coll_count;
    xlogs_colls.push(Elem);
    xlogs_coll_count = xlogs_coll_count + 1;
    setCollDataLogs();

}

var onePushLog = true;
var idPushLog = 0;
var LastIdPushLog = 0;

function sendLogsToTable() {
	
    if (stop_sendlogs==false) {
        
        for (var i = 0; i < xlogs_coll_count; i++) {

            if (onePushLog) {

                var logObj = xlogs_colls[i];

                if (logObj.sendtotable==0) {
                
                    onePushLog = false;
                    idPushLog = i;
                    
                    var idPageRef = localIdTeachdoc;
                    if (typeof(basePages) != 'undefined'){
                        idPageRef = basePages[pageBindex];
                    }

                    var formData = {
                        id : idPageRef,
                        idteach : localIdTeachdoc,
                        title : logObj.title,
                        logs : logObj.definition,
                        result : logObj.result
                    };
                    
                    if (window.location.href.indexOf("endpoint=")!=-1) {

                        tc_sendStatement_Exercices(logObj.title,logObj.definition,logObj.interactionType,0,logObj.result);

                        LastIdPushLog = idPushLog;
                        
                        setTimeout(function(){
                            var logObjAfter = xlogs_colls[LastIdPushLog];
                            xlogs_colls[LastIdPushLog].sendtotable = 1;
                            logObjAfter.sendtotable = 1;
                            onePushLog = true;
                            setCollDataLogs();
                        },900);

                    } else {

                        if (!haveLocalFileUrl()) {
                    
                            if (sendLogsToTableOpt==1) {
                                
                                var lk = getLocUrlEngine() + 'ajax/xapi/log-save-event.php';
                                $.ajax({
                                    url: lk,
                                    type: "POST",data : formData,
                                    success: function(data,textStatus,jqXHR){
                                        if (data.indexOf('OK')!=-1) {
                                            var logObjAfter = xlogs_colls[idPushLog];
                                            xlogs_colls[idPushLog].sendtotable = 1;
                                            logObjAfter.sendtotable = 1;
                                            setCollDataLogs();
                                        }
                                        onePushLog = true;
                                    },
                                });  
                                
                            }
        
                        }

                    }
                
                }

            }
        
        }

        setTimeout(function(){
            controlLogsToTable();
            stop_sendlogs = false;
        },600);

    } else {

        setTimeout(function(){
            controlLogsToTable();
            stop_sendlogs = false;
        },1000);
    }

}

function controlLogsToTime() {

    var timeLogObj = 0;

    if (window.location.href.indexOf("endpoint=")!=-1&&onePushLog==false) {
        timeLogObj = timeLogObj + 800;
    } else {
        if (onePushLog==false) {
            timeLogObj = 250;
        }
    }

    return parseInt(timeLogObj);

}

function controlLogsToTable() {

    var nbLogObj = 0;

    for (var i = 0; i < xlogs_coll_count; i++) {

        var logObj = xlogs_colls[i];
        if (logObj.sendtotable==0) {
            nbLogObj++;
        }

    }

    if (nbLogObj==0) {
        
        if (window.location.href.indexOf("endpoint=")==-1) {
            xlogs_colls = new Array();
            xlogs_coll_count = 0;
        }

        setTimeout(function(){
            sendLogsToTable();
        },1000);
    
    } else {
    
        setTimeout(function(){
            sendLogsToTable();
        },50);
    
    }

}

// Log space 
function displayInterfaceLogsTable() {
    if (window.location.href.indexOf("&tablelogs=1")!=-1) {
        if (!document.getElementById("actionDevWin")) {
            var h = '<div id="actionDevWin" ';
            h += ' style="position:fixed;right:5px;bottom:0px;width:370px;';
            h += 'height:450px;background-color:#4A235A;z-index:120;" ';
            h += ' class="actionDevWin noselect" >';
            h += '<div style="color:white;height:24px;line-height:24px;background-color:black;" >';
            h += '&nbsp;&#9881;&nbsp;interface logs</div>';
            h += '<div class="actionDevWinZone" ';
            h += ' style="position:relative;width:360px;padding:5px;height:410px;';
            h += 'color:white;overflow:auto;" ';
            h += ' ></div></div>';
            $("body").append(h);
            setTimeout(function(){
                loadInterfaceLogsTable();
            },200);
        }
    }
}
displayInterfaceLogsTable();
function loadInterfaceLogsTable() {
    $('.actionDevWinZone').html(logs_params);
    setTimeout(function(){
        loadInterfaceLogsTable();
    },300);
}
// Log space 

function haveLocalFileUrl(){
	var ur=window.location.href;
	if(ur.indexOf("file://")!=-1){
		return true;
	}
	return false;
}

function getLocUrlEngine() {
	var urlOrigin = window.top.location.origin + "/";
	if(urlOrigin.indexOf('://localhost')!=-1){
		urlOrigin = location.protocol + "//" + document.domain + "/" + location.pathname.split('/')[1] + "/";
	}
	return urlOrigin + "plugin/CStudio/";
}

function setCollDataLogs() {

    if (typeof localIdTeachdoc != 'undefined') {
        if (localStorage) {
            try {
                window.localStorage.setItem('xlogs_coll_' + localIdTeachdoc,JSON.stringify(xlogs_colls));
                window.localStorage.setItem('xlogs_coll_count_' + localIdTeachdoc,xlogs_coll_count);
            } catch(err) {
            }
        }
    }
    
}

function getCollDataLogs() {

    if (typeof localIdTeachdoc != 'undefined') {
        if (localStorage) {
            try {
                xlogs_colls = JSON.parse(window.localStorage.getItem('xlogs_coll_' + localIdTeachdoc));
                xlogs_coll_count = parseint(window.localStorage.getItem('xlogs_coll_count_' + localIdTeachdoc));
            } catch(err) {
            }
            if (xlogs_colls=='') {
                xlogs_colls = new Array();}
            if (xlogs_colls === null||xlogs_colls == "null"){
                xlogs_colls = new Array();}
            if (xlogs_colls === undefined) {
                xlogs_colls = new Array();}
            if (typeof xlogs_colls == 'undefined') {
                xlogs_colls = new Array();}
            xlogs_coll_count = xlogs_colls.length;
        }
    }

}
getCollDataLogs();

function parseText(str) {
	if (typeof(str) == 'undefined'){str = '';}
	if (str === null){str = '';}
	return str;
}

function parseTextForLog(str) {
	if (typeof(str) == 'undefined'){str = '';}
	if (str === null){str = '';}
    str = str.replace(/'/g,"-");
    str = str.replace(/"/g,"-");
    str = str.replace(/;/g,"-");
	return str;
}
function getLangTerm(term) {
    
    if (term=='Continue'&&projLang=='fr') {
        term = "Continuer";
    }
    if (term=='Continuer'&&projLang=='en') {
        term = "Continue";
    }
    if ((term=='Continue'||term=='Continuer')
        &&projLang=='es') {
        term = "Continuar";
    }
    return term;
}

var tmpIndexTbl = 200; 

function listIndexTable() {

    if ($(".indextablelist").length>0) {
        // Loop basePages array
        var h = '';
        for (var i = 0; i < basePages.length; i++) {
            var p = basePages[i];
            if (typeof p !== 'undefined') {
                if (p!='') {
                    var chapter = baseChapter[i];
                    if (chapter == 'undefined') {
                        chapter = '';
                    }
                    if (typeof chapter === 'undefined') {
                        chapter = '';
                    }
                    if (chapter!='') {
                        h += '<li class="indextablechapter" >' + chapter + '</li>';
                    }
                    
                    var title = baseTitles[i];
                    if (title == 'undefined') {
                        title = '';
                    }
                    if (typeof title === 'undefined') {
                        title = '';
                    }
                    if (title!='') {
                        h += '<li>&#9675;&nbsp;<a href="javascript:void(0);" ';
                        h += ' onclick="lpl(basePages['+i+'],\'\');" >';
                        h += title + '</a></li>';
                    }
                }
            }
        }
        $(".indextablelist").html(h);
    } else {
        setTimeout(function(){
            listIndexTable();
            tmpIndexTbl = tmpIndexTbl + 200;
        },tmpIndexTbl); 
    }

}

function adaptScoLive(){
    
    // Button bar
    $('.buttonbar').css("border","none");
    $('.buttonblock1').css("border","none");
    $('.buttonblock1').find('.teachdocbtnteach').removeClass().addClass('teachdocbtnteach');
    $('.buttonblock1').find('div').removeClass();

}


var onetimeprocess = 0; 

function Teachscript(){

    this.pageIsOk = function(){
        
        var ctrPage = true;
        ctrPage = pageControlsAllQuizz();
        
        if (ctrPage==false) {
            if (projOptions.indexOf("E")!=-1) {
                showErrorMessages();
                scrollToFirstErrorMessages();
            }
        }

        if (context_data_resolve.indexOf(";"+pageBindex+";")!=-1) {
            ctrPage = true;
        }

        return ctrPage;
        
    };

    this.autoLevel = function(){

        var ctrPage = true;
        ctrPage = pageControlsAllQuizz();
        
        if (ctrPage) {
            globalLevelDoc = 3;
        } else {
            globalLevelDoc = 1;
            if (pourcPageT>49) {
                globalLevelDoc=2;
            }
        }
        if (scorePageT==0) {
            globalLevelDoc = 2;
        }

        showLevelDifficultyAnim();

    };

    this.nextPageIfOK = function(){
        
        if(this.pageIsOk()){
            context_data_resolve += pageBindex+';';
            setContextData();
            this.nextPage();
        }else{
            showTopMessage();
        }

    };

    this.nextPage = function() {

        if(pageBindex<progressBtop){
            var pageBi = pageBindex + 1;
            var lvl = basePages[pageBi];
            ctrlpl(lvl,0,pageBi,behavPages[pageBi]);
        }

    };

    this.prevPage = function(s) {
        if(pageBindex>1){
            var pageBi = pageBindex - 1;
            var lvl = basePages[pageBi];
            ctrlpl(lvl,0,pageBi,behavPages[pageBi]);
        }
    };

    this.checkAll = function(){
        checkAnswers();
    }

    this.nextPageIsOK = function(){

    };

    this.getNumPage = function(){
        
    };

    this.goPage = function(ip){
        
    };
    
    this.wait = function(s){
        
    };
    
    this.waitReset = function() {
        
    };
    
    this.deleteLife = function() {

        if (onetimeprocess==0) {
            onetimeprocess = 1;
            if(context_data_life>0){
                context_data_life = context_data_life - 1;
            }
            applyLifeAnim();
            setContextData();

            setTimeout(function(){
                onetimeprocess = 0;
                if (document.getElementById("ui-life-bar")){
                    if (context_data_life==0) {
                        gameOverProcess();
                    }
                }
            },1000);
        }

    };
    
    this.addLife = function(){
        if (context_data_life<10) {
            context_data_life = context_data_life + 1;
            applyLifeAnim();
            setContextData();
        }
    };

    this.Reset = function(){
        LUDI.goPage(0);
    };
    
    this.randomId = function() {
        function s5() {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
        }
        return s5()+s5()+'-'+s5()+'-'+s5()+'-'+s5()+'-'+s5()+s5()+s5();
    };
    
    this.guid = function() {
        return this.randomId();
    };
  
}

var LUDI = new Teachscript();

var scorePageT = 0;
var scorePageP = 0;
var pourcPageT = 0;

function pageControlsAllQuizz(){

    var ctrPage = true;
    var ctrObject= true;

    scorePageT = 0;
    scorePageP = 0;
    pourcPageT = 0;

    $('span.errorflag').remove();
    
    var allTables = $(".panel-teachdoc").find("table");
        
        allTables.each(function(index){

        var tabG = $(this);
        
        if(tabG.hasClass("qcmbarre")){
            var defAnswer = '';
            var havError = true;
            ctrObject = true;
            tabG.find('.checkboxqcm').each(function(index) {

                scorePageT++;
                
                var objCtr = $(this);
                var srcCtr = objCtr.attr("src");

                if (objCtr.hasClass("xboxqcm")) {
                    if(srcCtr){
                        if(srcCtr.indexOf('atgreen1.png')==-1){
                            if (havError) {
                                tabG.before("<span class='errorflag' >&#9888;</span>");
                                havError = false;
                            }
                            ctrPage = false;
                            ctrObject = false;
                        }
                    }
                } else {
                    if(srcCtr){
                        if(srcCtr.indexOf('atgreen0.png')==-1){
                            if (havError) {
                                tabG.before("<span class='errorflag' >&#9888;</span>");
                                havError = false;
                            }
                            ctrPage = false;
                            ctrObject = false;
                        }
                    }
                }
                
                if(srcCtr.indexOf('atgreen1.png')!=-1){
                    //defAnswer
                    //objCtr
                    var nextTD = objCtr.parent().next();
                    if (defAnswer!='') {defAnswer += ';';}
                    var recupAnswer = nextTD.text();
                    defAnswer += parseTextForLog(recupAnswer);
                }
                
            });

            if (defAnswer!=''&&defAnswer!=';'&&globalCoursePourc<100) {
                var defQuizz = parseTextForLog(tabG.find('.quizzTextqcm').text());
                Cxlogs_insert('quizz','quizz_' + defQuizz.length,defQuizz + '|' + defAnswer,ctrObject,defAnswer);
            }

            if (ctrObject) {
                scorePageP++;
            }

        }
    });
    
    var allIframe = $(".plugteachcontain").find("iframe");

    allIframe.each(function(index){
        var tabG = $(this);
        var indexHvpc = 0;
        if (tabG.hasClass("hvpcontentfram")) {
            indexHvpc++;
            ctrObject = true;
            var titleobject = tabG.parent().attr('typesource');

            var allIframeHVP = tabG.contents().find("iframe");

            if (allIframeHVP.length>0) {
                allIframeHVP.each(function(index){
                    var objI = $(this).contents().find(".h5p-joubelui-score-bar-star");
                    if (objI.length==0) {
                        ctrPage = false;
                        ctrObject = false;
                        tabG.before("<span class='errorflag' >&#9888;</span>");
                    }
                });
            } else {
                var objC = tabG.contents().find(".h5p-joubelui-score-bar-star");
                if (objC.length==0) {
                    ctrPage = false;
                    ctrObject = false;
                    tabG.before("<span class='errorflag' >&#9888;</span>");
                }
            }
            if (globalCoursePourc<100) {
                Cxlogs_insert('h5p' + titleobject,'h5p_' + titleobject + indexHvpc,'',ctrObject,'');
            }
        }
    });
    
    if (scorePageT>0&&scorePageP>0) {
        pourcPageT = parseInt((scorePageP/scorePageT)*100);
    } else {
        pourcPageT = 0;
    }

    $('span.errorflag').css("display","none");

    return ctrPage;

}

function checkAnswers(){

    var ctrPage  = pageControlsAllQuizz();

    if (ctrPage==false) {
        showErrorMessages();
        showTopMessage();
    }

}

function showErrorMessages(){

    $('span.errorflag').css("margin-left","10px");
    $('span.errorflag').css("display","block");
    
    showLevelBlocs("B");
    
    $( "span.errorflag" ).animate({
        "margin-left" : "5px"
    },200,function(){
        $( "span.errorflag" ).animate({
            "margin-left" : "10px"
        },200,function(){
            $( "span.errorflag" ).animate({
                "margin-left" : "5px"
            },200,function(){
              
            });
        });
    });

}

function scrollToFirstErrorMessages() {
    var firstErrorFlag = $('span.errorflag:first');
    if (firstErrorFlag.length > 0) {
        $('html, body').animate({
            scrollTop: firstErrorFlag.offset().top
        }, 500);
    }
}

var onetimeoutShow;

function showTopMessage(){
   
    $(".fixed-top-message").css("right","-350px");
    $(".fixed-top-message").css("display","block");
    $( ".fixed-top-message" ).animate({
        right: "4px"
    },500);

    clearTimeout(onetimeoutShow);
    
    onetimeoutShow = setTimeout(function(){
        hideTopMessage()
    },6000);
    
}

function hideTopMessage(){

    $( ".fixed-top-message" ).animate({
        right: "-350px"
    },400,function(){
        $(".fixed-top-message").css("display","none");     
    });



}

function gameOverProcess() {

    if (pLifeBar>0) {
        
        context_data_life = parseInt(pLifeBar);
        context_data_life_full = parseInt(pLifeBar);
        context_data_quiz = "";
        context_data_resolve = "";

        if (!document.getElementById("gameOverView")) {
            var h = '<div id="gameOverBack" class="overViewBack" ></div>';
            h += '<div id="gameOverView" class="gameOverView" >';
            h += '<img src="img/gameoverscreen.svg" />';
            h += '<a onClick="gameOverNext()" class="btn-btnTeach btnteachblue" ';
            h += ' style="position:absolute;left:50%;';
            h += 'bottom:4%;width:120px;margin-left:-60px;" >';
            h += getLangTerm('Continue') + '</a>';
            h += '</div>';
            $("body").append(h);
        }
        if (typeof setLMSLocationLocalHost === "function") { 
            scoPageAPI = 1;
            setLMSLocationLocalHost(scoPageAPI);
        }
        if (typeof pushLocationToFriendLms === "function") { 
            scoPageAPIpush = 1;
            pushLocationToFriendLms();
        }
        if (typeof resetLMSLocation === "function") { 
            resetLMSLocation();
        }
        
        setContextData();

        $('#gameOverBack,#gameOverView').css('display','block');
    
    }
    
}

function gameOverNext() {
    var v = Math.floor(Math.random() * 20);
    gotToPage("index.html?load=first&v=go"+v);
}

//Menu design Progression
function installMenuLocation() {
    

    if (typeof sendLMSLocation === "function") {

        var beha = behaviorGetParamVal("b");
        var ctPg = 1;

        if (typeof getLMSLocation === "function") {
            ctPg = parseInt(getLMSLocation());
            if (ctPg>parseInt(scoPageAPI)) {scoPageAPI = ctPg;}
            
        }
        if (beha!=-99) {
            if (pageBindex>parseInt(scoPageAPI)) {
                scoPageAPI = pageBindex;
            }
        }
        if (beha==0&&(pageBindex>ctPg+1)) {

            scoPageAPI = ctPg;

        } else {

            if (beha!=-99) {
              
                if(typeof progressBtop != 'undefined') {
                    sendLMSLocation(pageBindex,progressBtop);
                    scoPageAPI = parseInt(scoPageAPI);
                    console.log("scoPageAPI=" + scoPageAPI);
                }

                if(typeof localIdTeachdoc != 'undefined') {
                    if(window.localStorage){
                        var keyPage = 'pageM' + localIdTeachdoc;
                        window.localStorage.setItem(keyPage,scoPageAPI);
                    }
                }  
            }

        }
        
        var sty = ' style="position:absolute;left:3px;top:6px;" ';
        
        if (pageBindex>1) {
            if (isObjSub($('.subMenuSco'+pageBindex))) {
                $('.subMenuSco'+pageBindex).append('<img ' + sty + ' src="img/qcm/sumin.png" />');
            }
        }

        if (scoPageAPI=='') {scoPageAPI=0;}
        scoPageAPI = parseInt(scoPageAPI);
        
        if (scoPageAPI==1) {
            $('.subMenuSco1').append('<img ' + sty + ' src="img/qcm/suminmidle.png" />')
        } else {
            if (scoPageAPI>1) {
                $('.subMenuSco1').append('<img ' + sty + ' src="img/qcm/sumin.png" />')
            }
        }
        
        if(pageBindex>1){
            //$('.subMenuSco1').append('<img ' + sty + ' src="img/qcm/sumin.png" />');
        }

        scoPageAPI = parseInt(scoPageAPI);

        if (scoPageAPI>1) {
            for (let i = 1; i < (scoPageAPI+1);i++){
                if(pageBindex!=i&&i!=1){
                    if(isObjSub($('.subMenuSco'+i))){
                        $('.subMenuSco'+i).append('<img ' + sty + ' src="img/qcm/sumin.png" />');
                    }
                }
            }
        }

        if(typeof progressBtop != 'undefined'){
         
            var firstBulle = true;
            for (let j = (scoPageAPI+1);j<(progressBtop+1);j++) {
                if(j!=pageBindex){
                    if(pageBindex!=j&&j!=1){
                        if(firstBulle){
                            if(isObjSub($('.subMenuSco'+j))){
                                $('.subMenuSco'+j).append('<img ' + sty + ' src="img/qcm/s-lock2.png" />');
                            }
                            firstBulle = false;
                        }else{
                            if(isObjSub($('.subMenuSco'+j))){
                                var pagebehav = $('.subMenuSco'+j).attr('pagebehav');
                                if(pagebehav==0){
                                    $('.subMenuSco'+j).append('<img ' + sty + ' src="img/qcm/s-lock.png" />');
                                }else{
                                    $('.subMenuSco'+j).append('<img ' + sty + ' src="img/qcm/s-lock2.png" />');
                                }
                            }
                        }
                    }
                }
            }

        }

        globalLevelDoc = getLevelDoc();
        showLevelDoc();
        initEventsLevelDoc();
        
    } else {
        setTimeout(function(){
            installMenuLocation();
        },300);
    }

}

function controlLevelDocLocation() {

    var fromid = 0;
    if (localStorage) {
        try {
            fromid = parseInt(window.localStorage.getItem('xlogs_fromid_' + localIdTeachdoc));
        } catch(err) {
        }
    }

    if (typeof fromid === 'undefined') {fromid = 0;}
    if (fromid == '') { fromid = 0; }
    if (isNaN(fromid)) { fromid = 0; }
    if (fromid == null) {fromid = 0;}
    
    if (typeof leveldocPages === 'undefined') {leveldocPages = [];}
    if (typeof leveldocPages[pageBindex] === 'undefined') {leveldocPages[pageBindex] = 2;}
    
    var ctrlvldoc = leveldocPages[pageBindex];
    if (globalLevelDoc!=ctrlvldoc) {
        if (ctrlvldoc==1||ctrlvldoc==3) {
            context_data_resolve += pageBindex+';';
            if (fromid>pageBindex) {
                var pid = pageBindex - 1;
                ctrlpl(basePages[pid],1,pid,1);
            } else {
                var pid = pageBindex + 1;
                ctrlpl(basePages[pid],1,pid,1);
            }
        }
    }

}

setTimeout(function(){
    resizeEventSco(true);
},300);

function resizeEventSco(loopresize) {

    if (homeMenuIsOpen==false) {
        
        // querySelector
        var hdiv = $('.div-teachdoc').height();

        var hlogotop = $(".logotop-teachdoc").height();
        var hprogresstop = $(".progress-teachdoc").height();

        var virtualTop = parseInt(hlogotop + hprogresstop);

        var ltw = $(".list-teachdoc_wrapper");
        var offsetLtw = ltw.offset();

        if (offsetLtw&&offsetLtw.top) {
            var topL =  offsetLtw.top;
            if (topL>virtualTop) {
                topL = virtualTop;
            }
            // console.log("topL:" + topL);
            var finalHeight = parseInt(hdiv - (topL + 65));
            if (finalHeight<200) {
                finalHeight = 200;
            }
            $(".list-teachdoc_wrapper").css("height",finalHeight + 'px');  
        }

    } else {
        resizeEventHome();
    }

    setTimeout(function(){
        resizeEventSco(true);
    },700);

}
function offsetDiv(el) {
    var rect = el.getBoundingClientRect(),
    scrollLeft = window.pageXOffset || document.documentElement.scrollLeft,
    scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    return { top: rect.top + scrollTop, left: rect.left + scrollLeft }
}
var indexAllFrame = 1;

function installPlugTeach(){
    
    getContextData();

    indexAllFrame = pageBindex * 1000;

    installFileTeach();

    $(".plugteachcontain").each(function(index){
        
        var obj = $(this);
        
        indexAllFrame = indexAllFrame +1;

        var typesource = obj.find('span.typesource').html();
        var datatext1 = "";var datatext2 = "";var datatext3 = "";
        if (typesource===undefined) {typesource = '';}
        if (typesource=='') {
            typesource = obj.parent().find('span.typesource').html();
            datatext1 = obj.parent().find('span.datatext1').html();
			datatext2 = obj.parent().find('span.datatext2').html();
            datatext3 = obj.parent().find('span.datatext3').html();
            obj.parent().find('span.datatext1').css("display","none");
            obj.parent().find('span.datatext2').css("display","none");
        } else {
            datatext1 = obj.find('span.datatext1').html();
			datatext2 = obj.find('span.datatext2').html();
            datatext3 = obj.find('span.datatext3').html();
            obj.find('span.datatext1').css("display","none");
            obj.find('span.datatext2').css("display","none");
        }
        if (datatext1===undefined) {datatext1 = '';}
		if (datatext2===undefined) {datatext2 = '';}
        if (datatext3===undefined) {datatext3 = '';}
        if (typesource===undefined) {typesource = '';}

        $(".topinactiveteach").css("display","none");
        
        if (typesource=='blank') {
            var h = '';
            h += '<iframe id="frame'+indexAllFrame+'" class="hvpcontentfram" title="blank" ';
            h += ' style="position:relative;width:100%;height:300px;overflow:hidden;" ';
            h += ' frameBorder="0" ';
            
            //h += ' src="oel-plug/hvpdragthewords/dragthewords.html#'+ datatext1 + '@' + datatext2 +'" ';
            
            h += ' src="oel-plug/oeldragthewords/plugin.html#'+ prevEncodeToHVP(datatext1) + '@' + prevEncodeToHVP(datatext2) + '@' + prevEncodeToHVP(datatext3) +'" ';

            h += '></iframe>';
            h += '<div class=loadcontentfram ></div>';
            obj.html(h);
            obj.addClass('hvpcontentfram');
            obj.attr('typesource','blank');
            obj.parent().css("position","relative");
        }
        if (typesource=='filltext') {
            var h = '';
            h += '<iframe id="frame'+indexAllFrame+'"  class="hvpcontentfram" title="filltext" ';
            h += ' style="position:relative;width:100%;height:300px;overflow:hidden;" ';
            h += ' frameBorder="0" ';

            //h += ' src="oel-plug/hvpfillintheblanks/fillinthemissingwords.html#'+ datatext1 + '@' + datatext2 +'" ';

            h += ' src="oel-plug/oelfilltheblanks/plugin.html#'+ prevEncodeToHVP(datatext1) + '@' + prevEncodeToHVP(datatext2) + '@' + prevEncodeToHVP(datatext3)  +'" ';

            h += '></iframe>';
            h += '<div class=loadcontentfram ></div>';
            obj.html(h);
            obj.addClass('hvpcontentfram');
            obj.attr('typesource','filltext');
            obj.parent().css("position","relative");
        }
        if (typesource=='markwords') {
            var h = '';
            h += '<iframe id="frame'+indexAllFrame+'" class="hvpcontentfram" title="markwords" ';
            h += ' style="position:relative;width:100%;height:300px;overflow:hidden;" ';
            h += ' frameBorder="0" ';
            //h += ' src="oel-plug/hvpmarkthewords/hvpmarkthewords.html#'+ datatext1 + '@' + datatext2 +'" ';

            h += ' src="oel-plug/oelmarkthewords/plugin.html#'+ prevEncodeToHVP(datatext1) + '@' + prevEncodeToHVP(datatext2) + '@' + prevEncodeToHVP(datatext3)  +'" ';

            h += '></iframe>';
            h += '<div class=loadcontentfram ></div>';
            obj.html(h);
            obj.addClass('hvpcontentfram');
            obj.attr('typesource','markwords');
            obj.parent().css("position","relative");
        }
        if (typesource=='findwords') {
            var h = '';
            h += '<iframe id="frame'+indexAllFrame+'" class="hvpcontentfram" title="findwords" ';
            h += ' style="position:relative;width:100%;height:300px;overflow:hidden;" ';
            h += ' frameBorder="0" ';
            h += ' src="oel-plug/oelwordsinlettergrid/plugin.html#'+ prevEncodeToHVP(datatext1) + '@' 
            h += prevEncodeToHVP(datatext2) + '@' + prevEncodeToHVP(datatext3)  +'" ';
            h += '></iframe>';
            h += '<div class=loadcontentfram ></div>';
            obj.html(h);
            obj.addClass('hvpcontentfram');
            obj.attr('typesource','findwords');
            obj.parent().css("position","relative");
        }
        if (typesource=='sorttheparagraphs') {
            var h = '';
            h += '<iframe id="frame'+indexAllFrame+'" class="hvpcontentfram" title="sorttheparagraphs" ';
            h += ' style="position:relative;width:100%;height:300px;overflow:hidden;" ';
            h += ' frameBorder="0" ';
            h += ' src="oel-plug/oelsorttheparagraphs/plugin.html#'+ prevEncodeToHVP(datatext1) + '@' 
            h += prevEncodeToHVP(datatext2) + '@' + prevEncodeToHVP(datatext3)  +'" ';
            h += '></iframe>';
            h += '<div class=loadcontentfram ></div>';
            obj.html(h);
            obj.addClass('hvpcontentfram');
            obj.attr('typesource','sorttheparagraphs');
            obj.parent().css("position","relative");
        }
        
        if (typesource.indexOf("oelcontent")!=-1){
            if (datatext1!='') {
                obj.find(".photo").css('background-image','url('+datatext1+')');
            }
        }
        
        if (typesource.indexOf("txtmathjax")!=-1){
            obj.css("text-align","left");
        }
        
        if (typesource.indexOf("imageactive")!=-1){
            installImageActiveEvents(obj,datatext1,datatext2,datatext3);
        }

        if (typesource.indexOf("schemasvgobj")!=-1){
            if (datatext1.indexOf('.svg')!=-1) {
                var objimg = obj.find("img");
                objimg.css("z-index","9");
                var objcontain = objimg.parent();
                objcontain.css("position","relative");
                //dataschem
                var filenamesvg = datatext1.substring(datatext1.lastIndexOf('/')+1);
                var dataschem = schemRender[filenamesvg];
                if (dataschem===undefined) {dataschem = '';}
                if (dataschem!='') {
                    objcontain.append(dataschem);
                }
                
            }
        }
        //map svg 
        if (typesource.indexOf("mapsvgobj")!=-1){
            var objimg = obj.find("img");
            objimg.css("z-index","9");
            var objcontain = objimg.parent();
            objcontain.css("position","relative");
            objcontain.append('<a>PLAN</a>');
            obj.attr("data-mapsvg",datatext1);
            obj.off('onmousedown');
            var objAlter = '';
            if (datatext2.indexOf('.svg')!=-1) {
                objAlter = datatext2;
            }
            installMapOverviewEvents(objimg,objAlter);
        }

    });

    //Special UI
    if (typeof pLifeBar !== 'undefined') {
        if (pLifeBar>0) {
            if (!document.getElementById("ui-life-bar")) {

                //game over !
                if(context_data_life==0){
                    gameOverProcess();
                }
                if (context_data_life==-1) {
                    context_data_life = parseInt(pLifeBar);
                    context_data_life_full = parseInt(pLifeBar);
                }
                var oh = '';
                var nbLife = parseInt(context_data_life_full);
                for (var i=1;i<(nbLife+1);i++) {
                    oh += '<div id="lifeopt'+i+'" class="onelifeopt" ></div>';
                }
                $("body").append("<div id='ui-life-bar' class='ui-life-bar' >"+ oh + "</div>");
                nbLife = parseInt(context_data_life_full);
                for (var j=1;j<(nbLife+1);j++) {
                    applyLifeHideOne(parseInt(context_data_life)+j);
                }
            }
        }
    }

    var iexpandpanel = 1;

    $(".sectioncollapse").each(function(index){
        var obj = $(this);
        obj.addClass("noselect");
        obj.addClass("sectioncollapse"+iexpandpanel);
        obj.attr("id","sectioncollapse"+iexpandpanel);
        obj.css("background-image","url(img/classique/arrow-collapsen.png)");
        obj.append("<a name='anchor"+iexpandpanel+"' ></a>");
        obj.on("click",function(){
            showCollapsePage(this);
        });
        applyCollapsePage(iexpandpanel);
        iexpandpanel++;
    });
    
    setTimeout(function(){
        $(".loadcontentfram").css("display","none");;
    },1600);
    
}

function prevEncodeToHVP(src){
    src = src.replace(/#/g,'-');
    return src;
}

var iframehvp = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];

function applyCheckExercices(){

    var ctrcontext = ';' + context_data_resolve + ';';
    if(ctrcontext.indexOf(";"+pageBindex+";")==-1){
        return false;
    }

    var attemptCheck = false;

    $(".checkboxqcm").each(function(index){
        var obj = $(this);
        if(obj.hasClass("xboxqcm")){
            obj.attr("src","img/qcm/matgreen1.png");
        }
    });
    $(".checkboxqcm").click(function(){});

    var indexIframe = 0;

    var allIframe = $(".plugteachcontain").find("iframe");
    allIframe.each(function(index){

        if(iframehvp[indexIframe]!=2){
          
            var stepIframe = 0;
            allIframeHVP = $(this).contents().find("iframe");
            allIframeHVP.each(function(index){

                var BtnSS = $(this).contents().find(".h5p-question-show-solution");
                if(BtnSS.length>0){
                    BtnSS.trigger('click');
                    stepIframe = 2;
                    iframehvp[indexIframe] = 2;
                }  else{
                    var BtnCheck = $(this).contents().find(".h5p-question-check-answer");
                    if(BtnCheck.length>0){
                        BtnCheck.trigger('click');
                        stepIframe = 1;
                        iframehvp[indexIframe] = 1;
                    }
                }
        
            });
            
            if(stepIframe!=2){
                attemptCheck = true;
            }
  
        }
        indexIframe++;

    });

    if(attemptCheck) {
        setTimeout(function(){
            applyCheckExercices();
        },500);
    }
    
}

setTimeout(function(){
    $(".fixed-top-nav").css("opacity","1");
    $(".div-teachdoc").css("opacity","1");
    $(".panel-teachdoc").css("opacity","1");
    $(".panel-teachdoc-large").css("opacity","1");
    installPlugTeach();
},300);

function applyCollapsePage(iexpanel){

    var startHidden = false;
    
    $('.panel-teachdoc').children().each(function () {

        var objD = $(this);

        if (startHidden) {

            if( objD.hasClass("sectioncollapse")){
                startHidden = false;
            }else{
                objD.attr("initdisplay",objD.css("display"));
                objD.css("display","none");
                objD.attr("relexpand",iexpanel);
            }
            
        }
        var hCtr = objD.html();
        if ( objD.hasClass("sectioncollapse"+iexpanel)
        ||hCtr.indexOf("sectioncollapse"+iexpanel)!=-1) {
            startHidden = true;
        }
    });

}

function showCollapsePage(Othis){

    var objD = $(Othis);

    var iexpanel = objD.attr("id");
    iexpanel = iexpanel.replace("sectioncollapse","");
    iexpanel = parseInt(iexpanel);

    var backurl = objD.css("background-image");

    if (backurl.indexOf("collapsen")!=-1) {

        objD.css("background-image","url(img/classique/arrow-collapsel.png)");
       
        $('.panel-teachdoc').children().each(function () {

            var objCtr = $(this);
            var numR = parseInt(objCtr.attr("relexpand"));
            if ( numR == iexpanel) {
                var displayR = objCtr.attr("initdisplay");
                if (typeof displayR == 'undefined') {
                    displayR = "";
                }
                objCtr.css("display",displayR);        
            }
        });

        //location.hash = "#anchor" + iexpanel;
        
        scrollTo($("#sectioncollapse" + iexpanel));
        //window.scrollTo(0,document.querySelector(".panel-teachdoc").scrollHeight);
    
    } else {

        objD.css("background-image","url(img/classique/arrow-collapsen.png)");
        $('.panel-teachdoc').children().each(function () {
            var objCtr = $(this);
            var numR = parseInt(objCtr.attr("relexpand"));
            if ( numR == iexpanel) {
                objCtr.css("display","none");
            }
        });
    
    }

}

function scrollTo(target) {
    if( target.length ) {
        $("html, body").stop().animate( { scrollTop: target.offset().top }, 1500);
    }
}

var context_data_quiz = '';
var context_data_life = -1;
var context_data_life_full = -1;
var context_data_resolve = ';';

function getContextData() {
    
    if (typeof scoPageAPI == 'undefined') {
        return false;
    }
    if (scoPageAPI=='') {scoPageAPI=0;}
    scoPageAPI = parseInt(scoPageAPI);
    
    if (window.location.href.indexOf("resetall=")!=-1&&pageBindex==1) {
        return false;
    }

    if (projOptions.indexOf("R")!=-1) {
        return false;
    }

    if (window.location.href.indexOf("load=")!=-1&&pageBindex==1) {
        if (scoPageAPI!=1&&scoPageAPI!=999) {
            // control basePages exist
            if (typeof basePages[scoPageAPI] === 'undefined') {
              
            } else {
                
                if (scoPageAPI>basePages.length) {
                    scoPageAPI = 1;
                }
                if (window.location.href.indexOf("load=save")!=-1) {
                    lpl(basePages[scoPageAPI],"","load=save");
                }else{
                    lpl(basePages[scoPageAPI],"","load=first");
                }
                
            }

        } else {
            if (window.location.href.indexOf("ontw=")!=-1) {
                var pageOntw = encodeURI(getParamValOntw("page"));
                if (pageOntw!=''&&pageOntw!='1') {
                    lpl(basePages[pageOntw],"","load=first");
                }

            }
        }
    }

    if (window.location.href.indexOf("load=first")!=-1) {
        return false;
    }

    try{
        if (localStorage) {
            mem_context_data = window.localStorage.getItem(getContextDataId());
            if (mem_context_data === null||mem_context_data == "null"){
                mem_context_data = "";
            }
            if (mem_context_data === undefined) {
                mem_context_data = "";
            }
            if (typeof mem_context_data == 'undefined') {
                mem_context_data = "";
            }
            if(mem_context_data!=""){
                if (mem_context_data.indexOf("@")!=-1) {
                    mem_context_data = mem_context_data + "@@@@";
                    var final_context_data = mem_context_data.split('@');
                    context_data_quiz = final_context_data[0];
                    if(final_context_data[1]!=""){
                        context_data_life = final_context_data[1];
                        context_data_life_full = final_context_data[2];
                    }
                    context_data_resolve = final_context_data[3];
                }
            }
        }
    }catch(err){}

}

function setContextData() {
    if (localStorage) {
        var context_data = context_data_quiz + '@' +context_data_life + '@'+context_data_life_full+'@'+context_data_resolve+'@';
        try {
            window.localStorage.setItem(getContextDataId(),context_data);
        } catch(err) {
        }
    }
}

//Data
function getContextDataId( ){
    return 'data'+basePages[1];
}

setTimeout(function(){
    searchTermInDom();
},800);    

function searchTermInDom(){
    
    var termVal = encodeURI(getParamValOntw("term"));
    var termVal2 = encodeURI(getParamValOntw("term"));
    if (termVal!='') {

        termVal = termVal.toLowerCase();
        var idobj = LUDI.randomId();

        var findObj = false;
        
        $('h2').each(function () {
            var objD = $(this);
            var searchHtml = objD.html();
            var searchText = objD.text();
            searchText = searchText.toLowerCase();
            if (searchText.indexOf(termVal)!=-1&&findObj==false) {
                objD.attr('id',idobj);
                findObj = true;
            }
        });
        
        $('.dialog-minidia').each(function () {
            var objD = $(this);
            var searchHtml = objD.html();
            var searchText = objD.text();
            searchText = searchText.toLowerCase();
            if (searchText.indexOf(termVal)!=-1&&findObj==false) {
                objD.attr('id',idobj);
                findObj = true;
            }
        });

        $('.teachdoctextContent').each(function () {
            var objD = $(this);
            var searchHtml = objD.html();
            var searchText = objD.text();
            searchText = searchText.toLowerCase();
            if (searchText.indexOf(termVal)!=-1&&findObj==false) {
                objD.attr('id',idobj);
                findObj = true;
            }
        });

        if (findObj) {
            const element = document.getElementById(idobj);
            element.scrollIntoView();
            setTimeout(function() {
                $('.panel-teachdoc').css('top','100px')
            },200);
        }

    }

}
function installImgOverviewEvents() {

    $(".bandeImgOverview").each(function(index){
        var obj = $(this);
		var oriClass = getTableClassOrigine(obj);
		obj.removeClass("bandeImgOverview");
        obj.addClass("bandeImgOverviewOnLive").addClass(oriClass);
        obj.wrap( "<div onClick='disImgToScr(0,\"" + obj.attr('src') +"\",\"black\")'; class='overviewImgOnLive " + oriClass + "' ></div>" );
    });
 
}

var nextIdis = 0;

function disImgToScr(i,pathimg,color){

	nextIdis = 0;

	if (!document.getElementById("imgScrView")) {

		var h = '<div id="overViewBack" class="overViewBack" ></div>';
		h +=  '<div id="imgViewBack" class="imgViewBack" >';
		h += '<div id="closeImgView" class="closeImgViewSrc" onClick="closeImgToScr()" >';
		h += '</div></div>';
		h += '<div id="imgScrView" class="imgScrView" >';
		h += '</div>';

        $("body").append(h);
	}
	
	$('#diaViewBack,#diaScrView').css('display','none');

	var finalPath = pathimg;
	var objAll = $('#imgViewBack,#imgScrView');

	$('#imgScrView').css('background-image','url(\'' + finalPath + '\')');

	objAll.css('display','block');
	$('#overViewBack').css("display","block");

	if (i==0) {

		objAll.css('background-size','contain');
		objAll.css('margin-left','0px').css('margin-top','0px');
		objAll.css('left','40%').css('right','40%');
		objAll.css('top','30%').css('bottom','30%');
		$('#imgViewBack').animate({
			left : "2%",right : "2%"
		}, 300, function() {
			$('#imgViewBack').animate({
				top : "5%",bottom : "3%",
			},250, function() {
				$('#imgScrView').animate({
					left : "6%", right : "6%",
					top : "8%", bottom : "7%",
				},200, function() {
				});
			});
		});
	}

	//best resolution
	if (i==1) {

		objAll.css('left','50%').css('top','50%');
		$('#imgViewBack').css('width','100px').css('height','100px');
		$('#imgViewBack').css('margin-left','-50px').css('margin-top','-50px');

		$('#imgScrView').css('width','60px').css('height','60px');
		$('#imgScrView').css('margin-left','-30px').css('margin-top','-30px');

		var newImg = new Image;
		newImg.onload = function() {
			
			var wImg = this.width ;
			var hImg = this.height;
			var wBac = this.width + 50;
			var hBac = this.height + 50;
			
			if (wBac > (largEcranWidth*zoom)||hBac > (largEcranHeight*zoom)){

				nextIdis = 0;
				objAll.css('margin-left','0px').css('margin-top','0px');
				objAll.css('width','auto').css('height','auto');
				objAll.css('background-size','contain');
				objAll.css('left','40%').css('right','40%');
				objAll.css('top','30%').css('bottom','30%');
				$('#imgViewBack').animate({
					left : "4%",right : "4%"
				}, 300, function() {
					$('#imgViewBack').animate({
						top : "4%",bottom : "4%",
					},250, function() {
						$('#imgScrView').animate({
							left : "7%",right : "7%",
							top : "8%",bottom : "7%",
						},200, function() {
						});
					});
				});

			}else{

				nextIdis = 1;
				objAll.css('background-size','none');
				$('#imgViewBack').css('width',(wBac) + 'px').css('height',hBac + 'px');
				$('#imgViewBack').css('margin-left','-' + (wBac/2) + 'px').css('margin-top','-' + (hBac/2) + 'px');
				$('#imgScrView').css('width',wImg + 'px').css('height',hImg + 'px');
				$('#imgScrView').css('margin-left','-' + (wImg/2) + 'px').css('margin-top','-' + (hImg/2) + 'px');
			
			}

		}
		newImg.src = finalPath;

	}
	
}

function closeImgToScr(){
	
	var objAll = $('#imgViewBack,#imgScrView');

	if (nextIdis==0) {
		objAll.animate({
			top : "45%",
			bottom : "45%",
			left : "45%",
			right : "45%"
		}, 200, function() {
			objAll.css("display","none");
			$('#overViewBack').css("display","none");
		});
	}
	if (nextIdis==1) {
		objAll.animate({
			width : "60px",
			height : "60px",
			marginLeft : "-30px",
			marginRight : "-30px"
		}, 200, function() {
			objAll.css("display","none");
			$('#overViewBack').css("display","none");
		});
	}

}

function showLevelBlocs(let) {

	$(".displayhideCondiM" + let).each(function(index){
		var obj = $(this);
		if (obj.hasClass("row")) {
			obj.css('display','table');
		}else {
			if (obj.hasClass("cell")) {
				obj.css('display','table-cell');
			}else {
				obj.css('display','block');
			}
		}
	});

}
function hideLevelBlocs(let) {

	$(".displayhideCondiM" + let).each(function(index){
		var obj = $(this);
		obj.css('display','none');
	});

}


function installMapOverviewEvents(objSrc,objAlter) {
    objSrc.wrap( "<div onClick='disMapToScr(\"" + objSrc.attr('src') +"\",\"" + objAlter +"\")'; class='overviewImgOnLive' ></div>" );
}

function disMapToScr(pathimg,alterimg){

    imgMapInit = pathimg;
    imgMapAlter = alterimg;

    $('#mapScrView').html('');
    
	if (!document.getElementById("mapScrView")) {
		var h = '<div id="overMapBack" class="overMapBack" ></div>';
		h +=  '<div id="mapViewBack" class="mapViewBack" >';
		h += '<div id="closeMapViewSrc" class="closeMapViewSrc" onClick="closeMapToScr()" ></div>';
        h += '<div id="infosMapView" class="infosMapView" ></div>';
        h += '<div id="infosCloseView" class="infosCloseView" onClick="closeInfosToScr()" ></div>';
        h += '<div id="mapScrView" class="mapScrView" ></div>';
		h += '</div>';
        $("body").append(h);
	}
	
	var finalPath = pathimg;
	var objAll = $('#mapViewBack');

	//$('#mapScrView').css('background-image','url(\'' + finalPath + '\')');

    var hMapPlug = '';
    hMapPlug += '<iframe id="frame'+indexAllFrame+'" ';
    hMapPlug += ' style="position:relative;width:100%;height:100%;';
    hMapPlug += 'min-height:300px;overflow:hidden;" frameBorder="0" ';
    hMapPlug += ' src="oel-plug/oelplanviewer/plugin.html#' + encodeURI(finalPath) + '" ';
    hMapPlug += '></iframe>';

	$('#overMapBack').css("display","block");
    
    objAll.css('display','block');
    objAll.css('background-size','contain');
    objAll.css('margin-left','0px').css('margin-top','0px');
    objAll.css('left','40%').css('right','40%');
    objAll.css('top','30%').css('bottom','30%');
    $('#infosMapView').css('display','none');

    $('#mapViewBack').animate({
        left : "0%", right : "0%"
    }, 300, function() {
        $('#mapViewBack').animate({
            top : "54px", bottom : "0.1%",
        },250, function() {
            $('#mapScrView').html(hMapPlug);
            refMapIframe = $('#frame'+indexAllFrame);
            $('#mapScrView').css("inset","none");
        });
    });

}

function closeMapToScr(){
	
    $('#infosCloseView').css('display','none');
    $('#infosMapView').css('display','none');
    $('#infosCloseAlter').css('display','none');

	var objAll = $('#mapViewBack');

    objAll.animate({
        top : "45%", bottom : "45%",
        left : "45%", right : "45%"
    }, 200, function() {
        objAll.css("display","none");
        $('#overMapBack').css("display","none");
        $('#mapScrView').html('');
    });

}

function closeInfosToScr(){

    $('#infosCloseView').animate({
        left :  "10px"
    }, 400, function() {
        $('#infosCloseView').css('display','none');
        $('#infosMapView').css('display','none');
    });
    $('#infosMapView').animate({
        width : "10px"
    }, 400, function() {
       
    });

}

function closeAlterToScr(){

    var iFrameDOM = refMapIframe.contents();
    iFrameDOM.find("#planview").attr("src",'../../'+imgMapInit);

}

var refMapIframe = null;
var imgMapInit = '';
var imgMapAlter = '';

var memIdMap = 0;
var memMapContent = [];
var memOnlyOneMapRequest = 0;

function mapEventGlobal(idMap,leftPosi,topPosi) {
    
    var bodypw = $('body').outerWidth();
    console.log('mapEventGlobal:' + idMap);

    if (idMap=='map-alterimg') {

        console.log('leftPosi:' + leftPosi + ' topPosi:' + topPosi);

        var iFrameDOM = refMapIframe.contents();
	    iFrameDOM.find("#planview").attr("src",'../../'+imgMapAlter);
        
        return false;
    }

    if (memOnlyOneMapRequest==1) {
        return false;
    }
    
    $('#infosMapView').html('...');

    memIdMap = idMap;
    
    var dataM = memMapContent[idMap];
    
    if (typeof dataM == 'undefined') {
        dataM = '';
    }
    if (dataM == 'undefined') {
        dataM = '';
    }
    
    $('#infosMapView').html('<p style="text-align:center;" ><br><br>...<p>');

    if (dataM!='') {
        $('#infosMapView').html(dataM);
        $('#infosMapView').scrollTop(0);
        applyGlossaryBoxTxt();
        memOnlyOneMapRequest = 0;
    } else {
        $('#infosMapView').scrollTop(0);
        memOnlyOneMapRequest = 1;
        $.ajax({
            url: 'alone-' + idMap.toLowerCase() + '.html',
            type: "GET",
            success: function(data,textStatus,jqXHR){
                memMapContent[idMap] = data;
                $('#infosMapView').html(data);
                applyGlossaryBoxTxt();
                memOnlyOneMapRequest = 0;
            },
            error: function(jqXHR, textStatus, errorThrown){
                $('#infosMapView').html('<p style="text-align:center;" ><br><br>Error<p>');
                memOnlyOneMapRequest = 0;
            }
        });
    }

    var leftPan = 450;

    if (bodypw<900) {
        $('#infosCloseView').css('display','block').css('left','10px');
        $('#infosCloseView').css('top','50%');
        $('#infosCloseView').animate({
            left : "0px", top : "10%"
        }, 600, function() {
        });
    } else {
        $('#infosCloseView').css('top','20%');
        $('#infosCloseView').css('display','block').css('left','10px');
        $('#infosCloseView').animate({
            left : (leftPan - 1) + "px", top : "50%"
        }, 400, function() {
        });
    }
    
    $('#infosMapView').css('display','block').css('width','10px');
    $('#infosMapView').animate({
        width : leftPan + "px"
    }, 400, function() {
       
    });

}

function installFileTeach(){

    if($("#linkdatafile").length==1){
        var linkFileData = $("#linkdatafile").html();
        $("#linkdatafile").css("display","block");

        if (linkFileData.toLowerCase().indexOf('.pdf')!=-1){
            showPdf(linkFileData);
        }
        if (linkFileData.toLowerCase().indexOf('.mp4')!=-1){
            showVideo(linkFileData);
        }

    }
    
}

function showPdf(linkFileData){

    $("#linkdatafile").css("display","block");
    var haut = window.innerHeight-75;
    var panel = document.querySelector(".panel-teachdoc");
    if(!panel){
        panel = document.querySelector(".panel-teachdoc-large");
    }
    panel.style.top = '10px';
    panel.style.marginTop = '40px';
    panel.style.paddingTop = '5px';
    panel.style.paddingBottom = '5px';
    $("#linkdatafile").animate({
        height : haut + "px"
    },500,function(){
        $("#linkdatafile").html("<iframe style='width:100%;height:100%;' src='img_cache/"+linkFileData+"' ></iframe>");
    });

}

function showVideo(linkFileData){
    
    $("#linkdatafile").css("display","block");
   
    resizeShowVideo(linkFileData);

}

function resizeShowVideo(linkFileData){

    var decoLarg = parseInt($(".deco-teachdoc").width()) + 100 ;
    
    if(window.innerWidth>1280) {
        decoLarg = parseInt($(".deco-teachdoc").width()) + 200 ;
    }
    
    if(window.innerWidth<600) {
        decoLarg = 40;
    }

    var panel = $(".panel-teachdoc");
    if(panel.length==0){
        panel = $(".panel-teachdoc-large");
    }
    var larg = window.innerWidth-decoLarg;
    var haut = window.innerHeight-110;
    var finalhaut = parseInt(larg*0.625);
    if (finalhaut>haut) {
        finalhaut = haut;
    }
    var topD = parseInt((haut - finalhaut)/2) + 60;

    panel.css("padding-top",'10px');
    panel.css("padding-bottom",'10px');
    panel.css("top",topD + "px");
    panel.css("max-width",larg + "px");
    
    panel.stop();
    $("#linkdatafile").stop();
    
    panel.animate({
        marginTop : "0px",
        width : larg + "px",
        height : finalhaut + "px"
    },500,function(){
        $("#linkdatafile").animate({
            height : finalhaut + "px"
        },100);
        if(linkFileData!=''){
            $("#linkdatafile").html("<video oncontextmenu='return false;' style='width:100%;height:100%;' src='img_cache/"+linkFileData+"' controls controlsList='nodownload' ></video>");
            $( window ).resize(function() {
                resizeShowVideo('');
            });
        }
    });

}
var colorThemeQuizz = '#FBFCFC';
var borderThemeQuizz = '#dadce0';

var firstResize = 0;

function applyThemeToColors(){
    
    if (pQuizzTheme=='yellow-contrast.css') {
        colorThemeQuizz = '#fff8ea';
        borderThemeQuizz = '#ABB2B9';
    }
    if (pQuizzTheme=='blue-contrast.css') {
        colorThemeQuizz = '#EBF5FB';
        borderThemeQuizz = '#ebf5fbd7';
    }
    
    $(".spaceteach").css("border",'solid 0px transparent');

}

function resizeAutoIframe(){

    var fullLoad = true;

    var allIframeS = $(".plugteachcontain");

    allIframeS.each(function(index){
        var oneIplugS = $(this);
        var plugs = oneIplugS.find("iframe");
        plugs.each(function(index){
            if ($(this).hasClass("hvpcontentfram")) {
                oneIplugS.parent().css("border",'solid 1px ' + borderThemeQuizz);
                oneIplugS.parent().css("border-radius",'8px');
                oneIplugS.parent().css("background-color",colorThemeQuizz);
                var objH = $(this).contents().find("html");
                objH.css('overflow','hidden');
                var objB = $(this).contents().find("body");
                objB.css('overflow','hidden');
            }
        });
    });

    var allIframe = $(".plugteachcontain").find("iframe");
    allIframe.each(function(index){

        var oneIframe = $(this);
        
        if($(this).hasClass("hvpcontentfram")){

            if (firstResize<2) {
                oneIframe.css("height","auto");
                oneIframe.css('overflow','hidden');
            }
 
            var allIframeHVP = oneIframe.contents().find("iframe");
            if (allIframeHVP.length>0) {
                
                allIframeHVP.each(function(index){

                    var objI = $(this).contents().find("body");
                    if(objI.length>0){
                        
                        var objH = $(this).contents().find("html");

                        objH.css('overflow','hidden');
                        objI.css('overflow','hidden');
    
                        decoIobj(objH,objI);

                        var h = oneIframe.height();
                        var hi = objI.height();
                        if (hi>h) {
                        
                            oneIframe.css("height",parseInt(hi + 10) + "px");
                            oneIframe.css('overflow','hidden');
                        
                        } else {

                            if (hi<h+60) {
                                //oneIframe.css("height",parseInt(hi + 50) + "px");
                            } else {
                                if (hi<10) {
                                    fullLoad = false;
                                }
                            }
                        }

                    } else {
                        fullLoad = false;
                    }

                });
    
            } else {

                var objFrameID = oneIframe.attr("id");

                var objC = oneIframe.contents();
                var objI = objC.find("body");

                if(objI.length==0){
                    objC = $('#'+objFrameID).contents();
                    objI = objC.find("body");
                }

                if(objI.length>0){
                    
                    var objH = $(this).contents().find("html");
                    
                    if (firstResize<2) {
                        objH.css('height','auto');
                        objI.css('height','auto');
                        decoIobj(objH,objI);
                    }
                    
                    var h = oneIframe.height();
                    var hi = objH.height();
                    if (hi>h) {
                        
                        oneIframe.css("height",parseInt(hi + 10) + "px");
                        if (firstResize<2) {
                            oneIframe.css('overflow','hidden');
                        }

                    } else {

                        if (hi<h+60) {
                            //oneIframe.css("height",parseInt(hi + 50) + "px");
                        } else {
                            if (hi<10) {
                                fullLoad = false;
                            }
                        }
                    }

                } else {
                    
                    oneIframe.css("height","250px");

                }

            }

        }   
    });
    
    firstResize ++;
    setTimeout(function(){
        resizeAutoIframe();
    },1500);
    
}

function decoIobj(objH,objI){

    objI.css("background-color",colorThemeQuizz);
    objI.find(".h5p-content").css("background-color",colorThemeQuizz);
    objI.find(".h5p-drag-text").css("background-color",colorThemeQuizz);
    objI.find(".h5p-mark-the-words").css("background-color",colorThemeQuizz);
    objI.find(".h5p-blanks").css("background-color",colorThemeQuizz);

}


var txtGameImgActic = [];
var idGameImgActic = [];

function installImageActiveEvents(obj,d1,d2,d3){

    var objZA = obj.find(".plugimageactive");
    
    if (objZA.length==1) {
        
        $(".overViewZAedition").remove();
        
        idGameImgActic = 'frame' + LUDI.randomId();
        objZA.addClass(idGameImgActic);

        objZA.css("position","relative");
        var contZA  = objZA.parent();
        var imgZA  = objZA.find(".imageactive");

        contZA.css("position","relative");
        imgZA.css("width","100%");

        if (d2.indexOf('$')!=-1) {
            
            d3 = d3 + '$$$$$$';

            var ArrayObjects = d2.split('$');
            var ArrayOptions = d3.split('$');
            
            var i = 0;
            for (i = 0;i < ArrayObjects.length;i++) {
            
                var objInfos = ArrayObjects[i];
                var objValues = ArrayOptions[i];

                if (objInfos.indexOf('|')!=-1) {
                    var objdet = objInfos.split('|');
                    addZoneToZoneAFromOpt2(objZA,objdet[1],objdet[2],objValues,i);
                }
                

            }

        }

    }

}

//Install Area from code
function addZoneToZoneAFromOpt2(objZA,l,t,ovals,iobj){
    
    ovals = ovals + '||||||';
    var objparam = ovals.split('|');

    var actZASelect = objparam[1];

    // 0 no action
    // 1 display text
    // 2 nextpage
    // 3 nextpage
    // 4 display image
    // 5 speech-bubble

    var zAtextArea = objparam[2];
    txtGameImgActic[iobj] = zAtextArea;

    var typeZA = objparam[3];
    // 0 transparent
    // 1 areaimgdeco
    // 2 cursor

    var divZoneArea = "<div  ";
    if (actZASelect==1) {
        divZoneArea += " onClick='showTextInWindowsArea("+iobj+",\""+ idGameImgActic +"\");' ";
    }
    if (actZASelect==2) {
        divZoneArea += " onClick='hideAllBubleArea();LUDI.nextPage();' ";
    }
    if (actZASelect==3) {
        divZoneArea += " onClick='hideAllBubleArea();LUDI.prevPage();' ";
    }
    if (actZASelect==5) {
        divZoneArea += " onMouseEnter='showTextInBubleArea("+iobj+",\""+ idGameImgActic +"\"," + l + "," + t + ");'  ";
    }
    divZoneArea += " style='left:" + l + "%;top:" + t + "%;' class='areaZA noselect' >";
    
    if (typeZA==1) {
        divZoneArea += "<div class='areaimgdeco' ></div>";
    }
    if (typeZA==2) {
        divZoneArea += "<div class='areaimgcursor' ></div>";
    }
    if (typeZA==3) {
        divZoneArea += "<div class='areaimgcursorsmall' ></div>";
    }
    if (typeZA==4) {
        divZoneArea += "<div class='areaimgpointingleft' ></div>";
    }

    divZoneArea += "</div>";

    objZA.append(divZoneArea);
    
}

function showTextInWindowsArea(i,idframe){

    $(".centerMessageChalkBoard").remove();
    
    hideAllBubleArea();
    
    $("." + idframe).append("<div class='centerMessageChalkBoard' >...</div>");

    $("." + idframe).css("overflow","hidden");

    $(".centerMessageChalkBoard").html(txtGameImgActic[i]);
    $(".centerMessageChalkBoard").css("display","block");
    $( ".centerMessageChalkBoard" ).animate({
        bottom : "-10%"
    },10, function() {
        $( ".centerMessageChalkBoard" ).animate({
            bottom : "2%"
        },300, function(){
            $("." + idframe).css("overflow","visible");
        });
    });

}

var lastIobj = -1;

function hideAllBubleArea(){
    lastIobj = -1;
    $(".speech-bubble").remove();
}

function hideTextInBubleArea(i){
    if (lastIobj==i) {
        setTimeout(function(){
            lastIobj = -1;
            $(".speech-bubble").remove();
        },300);
    }
}

function showTextInBubleArea(i,idframe,l,t){

    if (lastIobj==i) {
        return false;
    }
    
    lastIobj = i;

    $(".speech-bubble").remove();

    $("." + idframe).append("<div class='speech-bubble' onMouseLeave='hideTextInBubleArea("+i+")' >...</div>");
    
    $(".speech-bubble").html(txtGameImgActic[i]);

    var sh = $(".speech-bubble").height() + 45;

    $(".speech-bubble").parent().css("overflow","visible");

    $(".speech-bubble").css("margin-top",(sh * -1) + "px");
    $(".speech-bubble").css("display","block");
    $(".speech-bubble").css("top",(t - 2) + "%");
    $(".speech-bubble").css("left",(t + 1) + "%");
    $(".speech-bubble").css("position","absolute");
    $(".speech-bubble" ).animate({
        left : (l + 5) + "%",
        top : t + "%"
    },50, function() {
    });

}
var indexGlossDom = 1;
var subBubble = new Array();

function applyGlossaryAllTxt() {

    $(".panel-teachdoc p").each(function(index) {
        var obj = $(this);
        var oriSrc = obj.html();
        var src = applyGlossaryTxt(oriSrc,0);
        if (src!=oriSrc) {
            obj.html(src);
        }
	});

    $(".panel-teachdoc li").each(function(index) {
        var obj = $(this);
        var oriSrc = obj.html();
        var src = applyGlossaryTxt(oriSrc,0);
        if (src!=oriSrc) {
            obj.html(src);
        }
	});

}

function applyGlossaryBoxTxt() {

    $(".panel-teachdoc-box p").each(function(index){
        var obj = $(this);
        var oriSrc = obj.html();
        var src = applyGlossaryTxt(oriSrc,1);
        if (src!=oriSrc) {
            obj.html(src);
        }
	});

}

function applyGlossaryTxt(src,inboxcontent) {

    var oriSrc = src;
    for (var index=0;index<glossaryRender.length;++index) {
        var term = glossaryRender[index];
        var termW = term.w;
        var termD = term.d;
        var termD2 = term.d2;
        if (oriSrc.indexOf(termW)!=-1) {
            var oriDiv = '<a id="agloss'+indexGlossDom+'" class=agloss >';
            oriDiv += '<span onClick="hoverGlossary('+indexGlossDom+',' + inboxcontent + ');"  >' + termW + '</span>';
            oriDiv += '<div id="bubblegloss'+indexGlossDom+'" class="bubblegloss" >';
            oriDiv += '<div class="aglossclose" ';
            oriDiv += ' onClick="closeGlossary('+indexGlossDom+',' + inboxcontent + ');" ></div>';
            oriDiv += '<div class="glossdef1'+indexGlossDom+'" >' + termD + '</div>';
            if (termD2!='') {
                oriDiv += '<div id="glossPlus'+indexGlossDom+'" class="toolsGloss" onClick="showGlossPlus('+indexGlossDom+');" >';
                oriDiv += '<div class="addGloss" >+</div>';
                oriDiv += '</div>';
                oriDiv += '<div class="glossdef2 glossdef2'+indexGlossDom+'" >' + termD2 + '</div>';
            }
            oriDiv += '</div>';
            oriDiv += '</a>';
            oriSrc = oriSrc.replace(termW,oriDiv);
            indexGlossDom++;
        }
    }
    return oriSrc;

}

function hoverGlossary(i,inboxcontent) {
    $("#bubblegloss" + i).css("display","block");
    if (inboxcontent) {
        $("#bubblegloss" + i).css("margin-left","-120px");
    }
}

function closeGlossary(i,inboxcontent) {
    $("#bubblegloss" + i).css("display","none");
}

function showGlossPlus(i) {
    $("#glossPlus" + i).css("display","none");
   // $(".glossdef2" + i).css("display","block");
    $(".glossdef2" + i).slideDown();
}
var homeMenuIsOpen = false;

//startCourse
function startCourse(step) {

	if (homeMenuIsOpen==true) {

		$('.list-teachdoc_wrapper').css("display","none");
		$('.btnlaunch-teachdoc').css("display","none");
		$('.div-teachdoc-full').addClass('div-teachdoc-transi');
		
		$('.vertical-line-load,.vertical-line-load-2').animate({
			marginLeft: '-480px'
		},500, function(){
		});
		
		$('.panel-teachdoc').css("display","");
		$('#nav-bottom').css("display","");
		
		setTimeout(function(){

			$('.div-teachdoc-full').addClass('div-teachdoc');
			$('.div-teachdoc-full').removeClass('div-teachdoc-transi');
			$('.div-teachdoc').removeClass('div-teachdoc-full');
			$(".logotop-teachdoc").removeClass("logotop-teachdoc-cube width");

			$('.list-teachdoc_wrapper').css("display","");
			
			homeMenuIsOpen = false;

			if (step==0) {
				if (typeof localIdTeachdoc != 'undefined') {
					Cxlogs_insert('launch','launch','course' + localIdTeachdoc,0,'');
				}
				loadLinkPage();
				
			} else {
				$('.fixed-top-nav-load').css("display","none");
			}
			
		},500);

	}

}

//startMenu
function startMenu(step) {

	if (homeMenuIsOpen==false) {
		
		if ($('#btnlaunch-teachdoc').length==0) {
			$('.logotop-teachdoc').after("<a id='btnlaunch-teachdoc' onClick='startCourse(1)' class='btnlaunch-teachdoc noselect' >&#9658;</a>")
			$('body').append('<div class="vertical-line-load"></div><div class="vertical-line-load-2"></div>');
			$('body').append('<div class="fixed-top-nav-load"></div>');
			$('.logotop-teachdoc').before("<div id='title-mainmenu' class='title-mainmenu' ><div class='title-mainmenu-core'>" + titleMod + "</div></div>")
		}
		loadLogo();
		$('.list-teachdoc_wrapper').css("display","none");
		
		$('#nav-bottom').css("display","none");

		$('.panel-teachdoc').css("display","none");

		$('.div-teachdoc').addClass('div-teachdoc-full');
		$('.div-teachdoc-full').removeClass('div-teachdoc');
		$('.div-teachdoc-full').removeClass('div-transi');
		
		$('.vertical-line-load,.vertical-line-load-2').animate({
			marginLeft: '0px'
		},500, function(){
		});
		
		setTimeout(function(){
			$('.btnlaunch-teachdoc').css("display","block");
			$('.list-teachdoc_wrapper').css("display","block");
			$('.fixed-top-nav-load').css("display","block");
			homeMenuIsOpen = true;
		},500);
		
	}

}

function resizeEventHome() {

    if (homeMenuIsOpen==true) {
        
        var hdiv = 250;
		if ($('.div-teachdoc').length==1) {
			hdiv = $('.div-teachdoc').height();
		}
		if ($('.div-teachdoc-full').length==1) {
			hdiv = $('.div-teachdoc-full').height();
		}
		$(".list-teachdoc_wrapper").css("height",'auto');
        $(".list-teachdoc_wrapper").css("max-height",parseInt(hdiv - 120) + 'px');
		
		var hlogo = $('.logotop-teachdoc').height();
		$(".title-mainmenu").css("height", parseInt((hdiv - (hlogo + 150))/2) + 'px');
		
		collapseOpenAll();

    }
    
}

setTimeout(function(){
	
	var ur=window.location.href;
	if(ur.indexOf("ontw=")!=-1){
		forceStart();
	}

	$('.logotop-teachdoc').click(function() {
		startMenu(1);
	});
	resizeEventHome();

},200);

function loadLogo(){

	var v = Math.floor(Math.random() * 2000);
    
	var img1 = new Image();

    img1.onload = function() {
        $(".logotop-teachdoc").attr("src","img/classique/oel_back.jpg");
		var realWidth = img1.naturalWidth;
        var realHeight = img1.naturalHeight;
		if (realHeight>realWidth-10) {
			$(".logotop-teachdoc").addClass("logotop-teachdoc-cube width");
			setTimeout(function(){
				resizeEventHome();
			},400);
		}
    };

	img1.src = "img/classique/oel_back.jpg?v="+v;

}
loadLogo();

function forceStart() {
	additional_params = "&ontw=1";
	additional_params += "&page=" + encodeURI(getParamValOntw("page"));
	additional_params += "&term=" + encodeURI(getParamValOntw("term"));
	loadLinkPage();
}

function getParamValOntw(param){
	var u = window.top.location.href;var reg=new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
	matches=u.match(reg);
	if(matches==null){return '';}
	var vari=matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
	return vari;
}

function installCmq() {
    
    // getContextData();
    
    $(".checkboxqcm").each(function(index){

        var obj = $(this);
        var src = obj.attr("src");
        if(src.indexOf("atgreen0r.pn")!=-1){
            obj.addClass("xboxqcm");
        }
        obj.css("cursor","pointer");

        var nearLine = obj.parent().next('td');
        nearLine.css("cursor","pointer");
        
        nearLine.click(
            function(){
                var obj = $(this);
                var objC =obj.prev('td').find('img');
                checkObj(objC);
            }
        );
        nearLine.mouseover(
            function(){
                var obj = $(this);
                nearLine.css("text-decoration","underline");
            }
        );
        nearLine.mouseleave(
            function(){
                var obj = $(this);
                nearLine.css("text-decoration","none");
            }
        );
        
	});
    
    $(".checkboxqcm").click(
		function(){
			var obj = $(this);
            checkObj(obj);
        }
    );
    
    setTimeout(function(){applyGlossaryAllTxt();},500);
}

function checkObj(obj) {

    var qcmTObj = obj.parent().parent().parent().parent();
    var isSolo = true;
    var existing = qcmTObj.find('.xboxqcm');
    
    var mopt = qcmTObj.html();
	if (mopt.indexOf('multiqcmopts')!=-1||existing.length>1) {
		isSolo = false;
	}

    var src = obj.attr("src");
    
    if (src.indexOf("atgreen0")!=-1) {
        var oCh = obj.parent().parent().parent();
        if (isSolo) {
            oCh.find(".checkboxqcm").attr("src","img/qcm/matgreen0.png");
            obj.attr("src","img/qcm/matgreen1.png");
        } else {
            obj.attr("src","img/qcm/catgreen1.png");
        }
    } else {
        if (isSolo) {
            obj.attr("src","img/qcm/matgreen0.png");
        } else {
            obj.attr("src","img/qcm/catgreen0.png");
        }
    }

}

setTimeout(function(){installCmq();},500);

var menuparamsisExpanded = false;

function showScoParamsWindow()
{

    if ($("#scoMenuParams").length==0) {

        var bdDiv = '<div id="scoMenuParams" class="scoMenuParams" >';
        bdDiv += $("#infosfulltxt").html();

        if (projOptions.indexOf("D")!=-1) {
            bdDiv += '<div class="btninfosparams" >';
            bdDiv += '<img src="img/classique/pyramid-colors.png" style="float:left;" width="120" height="120" />';
            bdDiv += '<div onClick="selectLayerA()" class="cursordifficulty cursordifficultyA" style="margin-top:12px!important;" >Easy</div>';
            bdDiv += '<div onClick="selectLayerB()" class="cursordifficulty cursordifficultyB" >Normal</div>';
            bdDiv += '<div onClick="selectLayerC()" class="cursordifficulty cursordifficultyC" >Expert</div>';
            bdDiv += '</div>';
        }
        bdDiv += '<p style="text-align:center;cursor:pointer;" ><a onclick="window.location.reload(true);" ><u>Clear cache</u></a></p>';

        bdDiv += '</div>';
        $('body').append(bdDiv);

    }

    if ($("#scoMenuParams").length==1) {

        if (menuparamsisExpanded==false) {

            menuparamsisExpanded = true;
            $( "#scoMenuParams" ).css("display","block");
            $( "#scoMenuParams" ).css("width","30px").css("height","30px");
            $( "#scoMenuParams" ).animate({
                width : '300px'
            },200,function(){
                $( "#scoMenuParams" ).animate({ 
                    height: "400px"
                },200,function(){
                    colorLayerFull();
                });

            });

            $( ".btninfos" ).animate({
                right : '135px'
            },200,function(){
                $( ".btninfos" ).animate({
                    bottom : '385px'
                },200,function(){
                
                });
            });

        } else {
            
            menuparamsisExpanded = false;

            $( "#scoMenuParams" ).animate({
                height: "30px"
            },200,function(){
                $( "#scoMenuParams" ).animate({
                    width : '30px'
                },200,function(){
                    $( "#scoMenuParams" ).css("display","none");
                });
            });

            $( ".btninfos" ).animate({
                bottom : '2px'
            },200,function(){
                $( ".btninfos" ).animate({
                    right : '4px'
                },200,function(){
                
                });
            });

        }

    }

}

function colorLayerFull() {

    $(".cursordifficultyA").css("background-color","white");
    $(".cursordifficultyB").css("background-color","white");
    $(".cursordifficultyC").css("background-color","white");

    $(".uileveldoc1").css("background-color","");
    $(".uileveldoc2").css("background-color","");
    $(".uileveldoc3").css("background-color","");

    $(".cursordifficultyA").css("color","black");
    $(".cursordifficultyB").css("color","black");
    $(".cursordifficultyC").css("color","black");
    
    if (globalLevelDoc==1) {
        $(".cursordifficultyA").css("background-color","#52BE80");
        $(".uileveldoc1").css("background-color","#52BE80");
        $(".cursordifficultyA").css("color","white");
    }
    if (globalLevelDoc==2) {
        $(".cursordifficultyB").css("background-color","#3b97e3");
        $(".uileveldoc2").css("background-color","#3b97e3");
        $(".cursordifficultyB").css("color","white");
    }
    if (globalLevelDoc==3) {
        $(".cursordifficultyC").css("background-color","#EB984E");
        $(".uileveldoc3").css("background-color","#EB984E");
        $(".cursordifficultyC").css("color","white");
    }

}

function selectLayerA() {
    globalLevelDoc = 1;
    showLevelDoc();
    setLevelDoc();
}
function selectLayerB() {
    globalLevelDoc = 2;
    showLevelDoc();
    setLevelDoc();
}
function selectLayerC() {
    globalLevelDoc = 3;
    showLevelDoc();
    setLevelDoc();
}

function initEventsLevelDoc() {

    $(".uileveldoc1").css("background-color","");
    $(".uileveldoc2").css("background-color","");
    $(".uileveldoc3").css("background-color","");
    
    $(".uileveldoc1").on("click",function(){
        globalLevelDoc = 1;
        showLevelDoc();
        setLevelDoc();
        $(".uileveldoc1").css("background-color","#52BE80");
    });
    $(".uileveldoc2").on("click",function(){
        globalLevelDoc = 2;
        showLevelDoc();
        setLevelDoc();
        $(".uileveldoc2").css("background-color","#3b97e3");
    });
    $(".uileveldoc3").on("click",function(){
        globalLevelDoc = 3;
        showLevelDoc();
        setLevelDoc();
        $(".uileveldoc3").css("background-color","#EB984E");
    });

}

function showLevelDoc() {

    colorLayerFull();

    $("ul.list-teachdoc li").each(function(){
    
        var leveldoc = $(this).attr("leveldoc");

        if (globalLevelDoc==1) {
            if (leveldoc==1) {
                $(this).css("display","block");
            }
            if (leveldoc==3) {
                $(this).css("display","none");
            }
        }
        if (globalLevelDoc==2) {
            if (leveldoc==1||leveldoc==3) {
                $(this).css("display","none");
            }
        }
        if (globalLevelDoc==3) {
            if (leveldoc==3) {
                $(this).css("display","block");
            }
            if (leveldoc==1) {
                $(this).css("display","none");
            }
        }

    });

}

function getLevelDoc() {
    var localLevelDoc = 2;
    if (localStorage) {
        try {
            localLevelDoc = parseInt(window.localStorage.getItem('xlogs_leveldoc_' + localIdTeachdoc));
        } catch(err) {
        }
    }
    if (typeof localLevelDoc === 'undefined') {localLevelDoc = 2;}
    if (localLevelDoc == '') { localLevelDoc = 2; }
    if (isNaN(localLevelDoc)) { localLevelDoc = 2; }
    if (localLevelDoc == null) {localLevelDoc = 2;}
    return localLevelDoc;
}

function setLevelDoc() {
    if (localStorage) {
        if (globalLevelDoc==null) {globalLevelDoc = 2;}
        if (globalLevelDoc == '') { globalLevelDoc = 2; }
        if (isNaN(globalLevelDoc)) { globalLevelDoc = 2; }
        if (globalLevelDoc!=1&&globalLevelDoc!=2&&globalLevelDoc!=3) {globalLevelDoc = 2;}
        try {
            window.localStorage.setItem('xlogs_leveldoc_' + localIdTeachdoc, globalLevelDoc);
        } catch(err) {
        }
    }
}
function showLevelDifficultyAnim()
{

    if ($("#scoLevelDifficultyAnim").length==0) {

        var bdDiv = '<div id="scoLevelDifficultyAnim" class="scoLevelDifficultyAnim" >';

        //scoLevelDifficultyTitle
        bdDiv += '<div class="scoLevelDifficultyTitle" >Niveau de compréhension</div>';

        bdDiv += '<div class="levelinfoscursor" ></div>';
        bdDiv += '<div class="levelinfosparams" >';
        bdDiv += '<div class="barreDifficulty barreDifficultyA" ></div>';
        bdDiv += '<div class="barreDifficulty barreDifficultyB" ></div>';
        bdDiv += '<div class="barreDifficulty barreDifficultyC"  ></div>';
        bdDiv += '</div>';
        bdDiv += '<a id="btnlevelNextPage" title="Valider" name="submit" onClick="levelNextPage()" href="javascript:void(0);" ';
        bdDiv += ' style="position:absolute;left:50%;bottom:11%;margin-left:-28px;" ';
        bdDiv += ' type="button" class="btn-btnTeach btnroundblue"></a>';
        bdDiv += '</div>';

        bdDiv += '<div class="scoLevelDifficultyAnimOpac" ></div>';

        $('body').append(bdDiv);

    }

    if ($("#scoLevelDifficultyAnim").length==1) {

        $( "#scoLevelDifficultyAnim" ).css("display","block");
        if (globalLevelDoc==1) {
            $( ".levelinfoscursor" ).animate({
                left : '61%'
            },500,function(){
            });
        }
        if (globalLevelDoc==2) {
            $( ".levelinfoscursor" ).animate({
                left : '85%'
            },1000,function(){
            });
        }
        if (globalLevelDoc==3) {
            $( ".levelinfoscursor" ).animate({
                left : '109%'
            },1500,function(){
            });
        }

    }

}

function levelNextPage() {
    $("#btnlevelNextPage").css("display","none");
    context_data_resolve += pageBindex+';';
    var pid = pageBindex + 1;
    ctrlpl(basePages[pid],1,pid,1);
}


function adaptContent() {
  
    $('.tcoel-card-number').html('');
    
}

var nbThumbPages = 0;
var largeThumbw = 0;
var menuThShow = 0;

function installBottomThumbPages() {

    var thumbPagesMenu = '<a id="thumbPagesMenu" onClick="showThumbPages()" class="thumbPagesMenu" ></a>';

    // thumbPagesContains
    thumbPagesMenu += '<div id="thumbPagesContains" class="thumbPagesContains" >';

    var h = '';
    for (var i = 0; i < basePages.length; i++) {
        var p = basePages[i];
        if (typeof p !== 'undefined') {
            if (p!='') {
                var title = baseTitles[i];
                var idpage = basePages[i];
                if (title!='') {
                    thumbPagesMenu += '<a style="background-image:url(img_cache/thumbnail-studio-'+idpage+'.png);" ';
                    thumbPagesMenu += ' class="thumbPageBlock" href="javascript:void(0);" ';
                    thumbPagesMenu += ' onclick="clickVirtualPage('+idpage+');" >';
                    thumbPagesMenu += '<div class="thumbPageBlockTitle" >' + title;
                    thumbPagesMenu += '</div></a>';
                    nbThumbPages++;
                }
            }
        }
    }
        
    thumbPagesMenu += '</div>';

    $('body').append(thumbPagesMenu);

    largeThumbw = $('body').width();

    redimThumbPages();
    getThumbPage();

    if (menuThShow==1) {
        $('#thumbPagesContains').css("display","block");
        $('#thumbPagesContains').css("bottom","0px");
        $('#thumbPagesMenu').css("bottom","157px");
        redimThumbPages();
    }

}

function clickVirtualPage(idpage) {
    var obj = $(".pgh"+idpage);
    obj.click();
}

function showThumbPages() {

    if (menuThShow==0) {
        redimThumbPages();
        $('#thumbPagesContains').css("display","block");
        $('#thumbPagesContains').css("bottom","0px");
        $('#thumbPagesMenu').css("bottom","157px");
        menuThShow = 1;
    } else {
        redimThumbPages();
        $('#thumbPagesContains').css("display","block");
        $('#thumbPagesContains').css("bottom","-158px");
        $('#thumbPagesMenu').css("bottom","0px");
        menuThShow = 0;
    }
    setThumbPage();

}

function redimThumbPages() {

    var largeThumbPages = nbThumbPages * 101;
    if (largeThumbPages<380) {
        largeThumbPages = 380;
    }
    if (largeThumbPages<largeThumbw) {
        $('#thumbPagesContains').css("width",largeThumbPages+"px");
    } else {
        $('#thumbPagesContains').css("width","100%");
    }
  
}

setTimeout(function(){
    if (projOptions.indexOf("S")!=-1) {
        installBottomThumbPages();
    }
},100);

function getThumbPage() {
    
    menuThShow = 0;

    try{
        if (localStorage) {
            menuThShow = window.localStorage.getItem('ThumbPage'+getContextDataId());
            if (menuThShow === null||menuThShow == "null"){
                menuThShow = 0;
            }
            if (menuThShow === undefined) {
                menuThShow = 0;
            }
            if (typeof menuThShow == 'undefined') {
                menuThShow = 0;
            }
            
        }
    }catch(err){}

}

function setThumbPage() {
    if (localStorage) {
        try {
            window.localStorage.setItem('ThumbPage'+getContextDataId(),menuThShow);
        } catch(err) {
        }
    }
}


function parseBoolean(str){
    if(str==1){
        return true;
    }
    if(str=='1'){
        return true;
    }
    if(str==0){
        return false;
    }
    if(str=='0'){
        return false;
    }
    if(str=='true'){
        return true;
    }
    if(str=='false'){
        return false;
    }
    return /^true$/i.test(str);
}

function convertToPercentX(pos){
    var ppos=(pos / largEcranWidth)*100;
    return ppos + "%";
}

function convertToPercentY(pos){
    var ppos=(pos / largEcranHeight)*100;
    return ppos + "%";
}

function parseInteger(str){
    if(typeof(str) == 'undefined'){str=0;}
    if(str == null){str=0;}
    if(str==''){str=0;}
    if(typeof str === 'string' || str instanceof String){
    str=str.replace("page","");
    }
    return parseInt(str);
}

function parseFlo(str){
    if(typeof(str) == 'undefined'){str=0;}
    if(str == null){str=0;}
    if(str==''){str=0;}
    if(typeof str == "string"){
    str=str.replace(",",".");
    }
    return parseFloat(str);
}
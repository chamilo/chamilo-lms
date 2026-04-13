
function showTabProcess(i){
    $('.maskpartform').css('display','none');
    $('#changetabload').css('display','block');
    window.location.href = 'edit-options.php?tab='+i + '&cotk=' + $('#cotk').val();
}

function loadTableLogs(){

	$.ajax({
		url : 'logs-options.php?cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
			$('.form-studiotablelogs').html(data);
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			
		}
	});
    
}


// Use to translate the UI elements of the application
var langselectUI = 'en_US';

function detectTraductAll() {

    var _validLangs = ['ar','ast_ES','bg','bs_BA','ca_ES','cs_CZ','da','de','el','en_US',
        'eo','es','eu_ES','fa_IR','fi_FI','fr_FR','gl','he_IL','hi','hr_HR','hu_HU',
        'id_ID','it','ja','ka_GE','ko_KR','lt_LT','lv_LV','ms_MY','nl','nn_NO',
        'pl_PL','pt_BR','pt_PT','ro_RO','ru_RU','sk_SK','sl_SI','sr_RS','sv_SE',
        'th','tr','uk_UA','vi_VN','zh_CN','zh_TW'];
    var langselectUIStore = 'en_US';
    if (localStorage) {
        langselectUIStore = localStorage.getItem("langselectUI");
        if (_validLangs.indexOf(langselectUIStore) === -1) {
            var lang = navigator.language || navigator.userLanguage;
            if      (lang.indexOf('pt-BR')!=-1||lang.indexOf('pt_BR')!=-1) { langselectUIStore = 'pt_BR'; }
            else if (lang.indexOf('pt')   !=-1)                            { langselectUIStore = 'pt_PT'; }
            else if (lang.indexOf('zh-TW')!=-1||lang.indexOf('zh_TW')!=-1) { langselectUIStore = 'zh_TW'; }
            else if (lang.indexOf('zh')   !=-1)                            { langselectUIStore = 'zh_CN'; }
            else if (lang.indexOf('fr')   !=-1)                            { langselectUIStore = 'fr_FR'; }
            else if (lang.indexOf('es')   !=-1)                            { langselectUIStore = 'es'; }
            else if (lang.indexOf('de')   !=-1)                            { langselectUIStore = 'de'; }
            else if (lang.indexOf('nl')   !=-1)                            { langselectUIStore = 'nl'; }
            else if (lang.indexOf('ar')   !=-1)                            { langselectUIStore = 'ar'; }
            else if (lang.indexOf('bg')   !=-1)                            { langselectUIStore = 'bg'; }
            else if (lang.indexOf('bs')   !=-1)                            { langselectUIStore = 'bs_BA'; }
            else if (lang.indexOf('ca')   !=-1)                            { langselectUIStore = 'ca_ES'; }
            else if (lang.indexOf('cs')   !=-1)                            { langselectUIStore = 'cs_CZ'; }
            else if (lang.indexOf('da')   !=-1)                            { langselectUIStore = 'da'; }
            else if (lang.indexOf('el')   !=-1)                            { langselectUIStore = 'el'; }
            else if (lang.indexOf('eo')   !=-1)                            { langselectUIStore = 'eo'; }
            else if (lang.indexOf('eu')   !=-1)                            { langselectUIStore = 'eu_ES'; }
            else if (lang.indexOf('fa')   !=-1)                            { langselectUIStore = 'fa_IR'; }
            else if (lang.indexOf('fi')   !=-1)                            { langselectUIStore = 'fi_FI'; }
            else if (lang.indexOf('gl')   !=-1)                            { langselectUIStore = 'gl'; }
            else if (lang.indexOf('he')   !=-1)                            { langselectUIStore = 'he_IL'; }
            else if (lang.indexOf('hi')   !=-1)                            { langselectUIStore = 'hi'; }
            else if (lang.indexOf('hr')   !=-1)                            { langselectUIStore = 'hr_HR'; }
            else if (lang.indexOf('hu')   !=-1)                            { langselectUIStore = 'hu_HU'; }
            else if (lang.indexOf('id')   !=-1)                            { langselectUIStore = 'id_ID'; }
            else if (lang.indexOf('it')   !=-1)                            { langselectUIStore = 'it'; }
            else if (lang.indexOf('ja')   !=-1)                            { langselectUIStore = 'ja'; }
            else if (lang.indexOf('ka')   !=-1)                            { langselectUIStore = 'ka_GE'; }
            else if (lang.indexOf('ko')   !=-1)                            { langselectUIStore = 'ko_KR'; }
            else if (lang.indexOf('lt')   !=-1)                            { langselectUIStore = 'lt_LT'; }
            else if (lang.indexOf('lv')   !=-1)                            { langselectUIStore = 'lv_LV'; }
            else if (lang.indexOf('ms')   !=-1)                            { langselectUIStore = 'ms_MY'; }
            else if (lang.indexOf('nn')   !=-1||lang.indexOf('no')!=-1)    { langselectUIStore = 'nn_NO'; }
            else if (lang.indexOf('pl')   !=-1)                            { langselectUIStore = 'pl_PL'; }
            else if (lang.indexOf('ro')   !=-1)                            { langselectUIStore = 'ro_RO'; }
            else if (lang.indexOf('ru')   !=-1)                            { langselectUIStore = 'ru_RU'; }
            else if (lang.indexOf('sk')   !=-1)                            { langselectUIStore = 'sk_SK'; }
            else if (lang.indexOf('sl')   !=-1)                            { langselectUIStore = 'sl_SI'; }
            else if (lang.indexOf('sr')   !=-1)                            { langselectUIStore = 'sr_RS'; }
            else if (lang.indexOf('sv')   !=-1)                            { langselectUIStore = 'sv_SE'; }
            else if (lang.indexOf('th')   !=-1)                            { langselectUIStore = 'th'; }
            else if (lang.indexOf('tr')   !=-1)                            { langselectUIStore = 'tr'; }
            else if (lang.indexOf('uk')   !=-1)                            { langselectUIStore = 'uk_UA'; }
            else if (lang.indexOf('vi')   !=-1)                            { langselectUIStore = 'vi_VN'; }
            else                                                            { langselectUIStore = 'en_US'; }
        }
    }
    langselectUI = langselectUIStore;

}

function traductAll() {

    var _validLangs = ['ar','ast_ES','bg','bs_BA','ca_ES','cs_CZ','da','de','el','en_US',
        'eo','es','eu_ES','fa_IR','fi_FI','fr_FR','gl','he_IL','hi','hr_HR','hu_HU',
        'id_ID','it','ja','ka_GE','ko_KR','lt_LT','lv_LV','ms_MY','nl','nn_NO',
        'pl_PL','pt_BR','pt_PT','ro_RO','ru_RU','sk_SK','sl_SI','sr_RS','sv_SE',
        'th','tr','uk_UA','vi_VN','zh_CN','zh_TW'];
    var langselectUIStore = 'en_US';
    
    if (localStorage) {
        langselectUIStore = localStorage.getItem("langselectUI");
        if (_validLangs.indexOf(langselectUIStore) === -1) {
            
            var lang = navigator.language || navigator.userLanguage;
            if      (lang.indexOf('pt-BR')!=-1||lang.indexOf('pt_BR')!=-1) { langselectUIStore = 'pt_BR'; }
            else if (lang.indexOf('pt')   !=-1)                            { langselectUIStore = 'pt_PT'; }
            else if (lang.indexOf('zh-TW')!=-1||lang.indexOf('zh_TW')!=-1) { langselectUIStore = 'zh_TW'; }
            else if (lang.indexOf('zh')   !=-1)                            { langselectUIStore = 'zh_CN'; }
            else if (lang.indexOf('fr')   !=-1)                            { langselectUIStore = 'fr_FR'; }
            else if (lang.indexOf('es')   !=-1)                            { langselectUIStore = 'es'; }
            else if (lang.indexOf('de')   !=-1)                            { langselectUIStore = 'de'; }
            else if (lang.indexOf('nl')   !=-1)                            { langselectUIStore = 'nl'; }
            else if (lang.indexOf('ar')   !=-1)                            { langselectUIStore = 'ar'; }
            else if (lang.indexOf('bg')   !=-1)                            { langselectUIStore = 'bg'; }
            else if (lang.indexOf('bs')   !=-1)                            { langselectUIStore = 'bs_BA'; }
            else if (lang.indexOf('ca')   !=-1)                            { langselectUIStore = 'ca_ES'; }
            else if (lang.indexOf('cs')   !=-1)                            { langselectUIStore = 'cs_CZ'; }
            else if (lang.indexOf('da')   !=-1)                            { langselectUIStore = 'da'; }
            else if (lang.indexOf('el')   !=-1)                            { langselectUIStore = 'el'; }
            else if (lang.indexOf('eo')   !=-1)                            { langselectUIStore = 'eo'; }
            else if (lang.indexOf('eu')   !=-1)                            { langselectUIStore = 'eu_ES'; }
            else if (lang.indexOf('fa')   !=-1)                            { langselectUIStore = 'fa_IR'; }
            else if (lang.indexOf('fi')   !=-1)                            { langselectUIStore = 'fi_FI'; }
            else if (lang.indexOf('gl')   !=-1)                            { langselectUIStore = 'gl'; }
            else if (lang.indexOf('he')   !=-1)                            { langselectUIStore = 'he_IL'; }
            else if (lang.indexOf('hi')   !=-1)                            { langselectUIStore = 'hi'; }
            else if (lang.indexOf('hr')   !=-1)                            { langselectUIStore = 'hr_HR'; }
            else if (lang.indexOf('hu')   !=-1)                            { langselectUIStore = 'hu_HU'; }
            else if (lang.indexOf('id')   !=-1)                            { langselectUIStore = 'id_ID'; }
            else if (lang.indexOf('it')   !=-1)                            { langselectUIStore = 'it'; }
            else if (lang.indexOf('ja')   !=-1)                            { langselectUIStore = 'ja'; }
            else if (lang.indexOf('ka')   !=-1)                            { langselectUIStore = 'ka_GE'; }
            else if (lang.indexOf('ko')   !=-1)                            { langselectUIStore = 'ko_KR'; }
            else if (lang.indexOf('lt')   !=-1)                            { langselectUIStore = 'lt_LT'; }
            else if (lang.indexOf('lv')   !=-1)                            { langselectUIStore = 'lv_LV'; }
            else if (lang.indexOf('ms')   !=-1)                            { langselectUIStore = 'ms_MY'; }
            else if (lang.indexOf('nn')   !=-1||lang.indexOf('no')!=-1)    { langselectUIStore = 'nn_NO'; }
            else if (lang.indexOf('pl')   !=-1)                            { langselectUIStore = 'pl_PL'; }
            else if (lang.indexOf('ro')   !=-1)                            { langselectUIStore = 'ro_RO'; }
            else if (lang.indexOf('ru')   !=-1)                            { langselectUIStore = 'ru_RU'; }
            else if (lang.indexOf('sk')   !=-1)                            { langselectUIStore = 'sk_SK'; }
            else if (lang.indexOf('sl')   !=-1)                            { langselectUIStore = 'sl_SI'; }
            else if (lang.indexOf('sr')   !=-1)                            { langselectUIStore = 'sr_RS'; }
            else if (lang.indexOf('sv')   !=-1)                            { langselectUIStore = 'sv_SE'; }
            else if (lang.indexOf('th')   !=-1)                            { langselectUIStore = 'th'; }
            else if (lang.indexOf('tr')   !=-1)                            { langselectUIStore = 'tr'; }
            else if (lang.indexOf('uk')   !=-1)                            { langselectUIStore = 'uk_UA'; }
            else if (lang.indexOf('vi')   !=-1)                            { langselectUIStore = 'vi_VN'; }
            else                                                            { langselectUIStore = 'en_US'; }

        }
    }

    langselectUI = langselectUIStore;
    
    if (langselectUI=='en_US') { 
        return false;
    }

    $( ".gjs-block-label" ).not('.onetrd').each(function( index ) {
        $(this).addClass("trd");
        $(this).addClass("onetrd");
        if ($(this).parent().hasClass("gjs-block")) {
            var parentNode = $(this).parent();
            var txt1 = parentNode.attr("title");
            var txt2 = returnTradTerm(txt1);
            txt2 = txt2.replace(/&nbsp;/g, ' ');
            if (txt1!=txt2) {
               parentNode.attr("title",txt2);
            }
        }
    });

    //trd
    $( ".trd" ).each(function( index ) {
        
        var isBtn = 0;
        var txt1 = $(this).html();
        if ($(this).is( ":button" )) {
            txt1 = $(this).attr("value");
            isBtn = 1;
        }
        var txt2 = returnTradTerm(txt1);
        if (txt1!=txt2) {
            if (isBtn==1) {
                $(this).attr("value",txt2);
            } else {
                $(this).html(txt2);
            }
        }
        $(this).removeClass("trd");
        
    });

}

detectTraductAll();
cstudioI18nInit(langselectUI, traductAll);






function loadUpdateProcess() {

    $('.buttonProgressSave').css('display','none');
    $('.form-progress-update-bar').css('width','5%');

    $('.buttonProgressSaveLog').css('display','block');
    $('.buttonProgressSaveLog').append('<span style="color:green;font-weight:bold;" >&check;</span>&nbsp;Checking sources <br>');

	$.ajax({
		url : 'update/updateteachdocsteps.php?step=1&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
			if (data.indexOf('OK') >= 0) {
                $('.buttonProgressSaveLog').append('<span style="color:green;font-weight:bold;" >&check;</span>&nbsp;Sources checked <br>');
                $('.form-progress-update-bar').css('width','10%');
                setTimeout(function(){
                    loadUpdateProcess2();
                },500);
            } else {
                showErrorProgress(data);
            }
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
            showErrorProgress('Error');
		}
	});
    
}

function loadUpdateProcess2() {

    $('.buttonProgressSaveLog').append('<span style="color:green;font-weight:bold;" >&check;</span>&nbsp;Updating sources <br>');
    $.ajax({
		url : 'update/updateteachdocsteps.php?step=2&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
			if (data.indexOf('OK') >= 0) {
                $('.buttonProgressSaveLog').append('<span style="color:green;font-weight:bold;" >&check;</span>&nbsp;Sources updated <br>');
                $('.form-progress-update-bar').css('width','45%');
                loadUpdateProcess3();
            } else {
                showErrorProgress(data);
            }
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
            showErrorProgress('Error');
		}
	});

}

var timerShowProgress = 0;
var timerShowProgress2 = 0;

function loadUpdateProcess3() {

    timerShowProgress = setTimeout(function() {
    $('.form-progress-update-bar').css('width','55%'); },1000);
    timerShowProgress2 = setTimeout(function() {
    $('.form-progress-update-bar').css('width','65%'); },2000);
    
    $('.buttonProgressSaveLog').append('<span style="color:green;font-weight:bold;" >&check;</span>&nbsp;Checking medias<br>');
    
    $.ajax({
		url : 'update/updateteachdocsteps.php?step=3&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
			if (data.indexOf('OK') >= 0) {
                clearTimeout(timerShowProgress);
                clearTimeout(timerShowProgress2);
                $('.buttonProgressSaveLog').append('<span style="color:green;font-weight:bold;" >&check;</span>&nbsp;Medias checked <br>');
                $('.form-progress-update-bar').css('width','70%');
                setTimeout(function(){
                    $('.form-progress-update-bar').css('width','75%');
                    loadUpdateProcess4();
                },500);
            } else {
                showErrorProgress(data);
            }
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
            showErrorProgress('Error');
		}
	});

}

function loadUpdateProcess4() {

    $('.buttonProgressSaveLog').append('<span style="color:green;font-weight:bold;" >&check;</span>&nbsp;Updating Medias <br>');
    
    $.ajax({
		url : 'update/updateteachdocsteps.php?step=4&cotk=' + $('#cotk').val(),
		type : "POST",
		success : function(data,textStatus,jqXHR) {
			if (data.indexOf('OK') >= 0) {
                $('.buttonProgressSaveLog').append('<span style="color:green;font-weight:bold;" >&check;</span>&nbsp;Medias updated <br>');
                $('.form-progress-update-bar').css('width','90%');
                setTimeout(function(){
                    $('.form-progress-update-bar').css('width','100%');
                    $('.form-progress-update-bar').css('background-color','#58D68D');
                },600);
            } else {
                showErrorProgress(data);
            }
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
            showErrorProgress('Error');
		}
	});

}

function showErrorProgress(msg) {
    $('.buttonProgressSaveLog').append(msg + '<br>');
    $('.form-progress-update-bar').css('width','50%');
    $('.form-progress-update-bar').css('background-color','#F1948A');
}
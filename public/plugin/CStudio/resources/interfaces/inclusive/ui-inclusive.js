
var termAdapt = "Adapting the display to your needs";
var termAccessible = "Acces";
var termPersonalize = "Customise your display";
var termAccessibility = "Accessibility";
var termRedimFont = "Character resizing";

var termMinFont = "Decrease font size";
var termNorFont = "Reset font size";
var termMaxFont = "Increase font size";

var termAdaptFont = "Adapted font";
var termClassic = "Classic";
var termDys = "Dyslexia";
var termContrast = "Constrast";
var termNone = 'None';
var termContrastStrong = 'High contrast';

function tradAllPanel() {
    
    if (projLang=='fr') {
        termAdapt = "Adaptation de l\'affichage à vos besoins";
        termAccessible = "Acces";
        termPersonalize = "Personnalisez votre affichage";
        termAccessibility = "Access";
        termRedimFont = "Redimensionnement des caractères";
        termMinFont = "Diminuer la taille des caractères";
        termNorFont = "Réinitialiser la taille des caractères";
        termMaxFont = "Augmenter la taille des caractères";
        termAdaptFont = "Police de caractère adaptée";
        termClassic = "Classique";
        termDys = "Dyslexie";
        termContrast = "Constraste";
        termNone = 'Aucun';
        termContrastStrong = 'Contraste fort';
    }
    
    if (projLang=='es') {
        termAdapt = "Adaptar la pantalla a sus necesidades";
        termAccessible = "Acces";
        termPersonalize = "Personalice su pantalla";
        termAccessibility = "Acces";
        termRedimFont = "Redimensionamiento de caracteres";
        termMinFont = "Aumentar el tamaño de letra";
        termNorFont = "Restablecer tamaño de fuente";
        termMaxFont = "Aumentar el tamaño de letra";
        termAdaptFont = "Fuente adecuada";
        termClassic = "Classic";
        termDys = "Dislexia";
        termContrast = "Constraste";
        termNone = 'Sin';
        termContrastStrong = 'Alto contraste';
    }

}

function addAccessPanel() {

    if (!document.getElementById("access_panel_inclusive")){

        var ludiiconplus = 'img/classique/icon_inclusive_35.png';
        
        tradAllPanel();

        var h = '<a id="access_panel_inclusive" onClick="inclusiveShow();" ';
		h += ' style="cursor:pointer;position:fixed;background-color:#21618C;z-index:9999997;';
        h += 'border-top-left-radius:4px;border-bottom-left-radius:4px;';
        h += 'width:36px;height:94px;right:0px;top: calc(50% - 50px);" >';

        h += '<p style="color:white;font-size:11px;line-height:1.3;text-align:center;margin:0px;padding:0px;margin-top:4px;" >' + termAccessible + '</p>';
		h += '<img id="studioeltools" src="'+ ludiiconplus + '" ';
        h += ' alt="'+termAdapt+'" title="'+termAdapt+'" ';
        h += ' style="cursor:pointer;margin-left:1px;" /> ';
		h += '</a>';
        
        var closeiconplus = 'img/classique/cross_mini.png';
        
        h += '<div id="params_close" onCLick="inclusiveHidePanel();" ';
        h += ' style="position:fixed;top:calc(50% - 72px);width:20px;height:20px;';
        h += 'z-index: 9999998;bottom:0px;right:0px;" >';
        h += '<img id="studioeltools" src="'+ closeiconplus + '" ';
		h += ' alt="Masquer" title="Masquer" style="cursor:pointer;" /> ';
        h += '</div>';

        h += '<div id="params_panel_inclusive" ';
        h += ' style="position:fixed;background-color:white;';
        h += 'border-left:solid 2px #21618C;';
        h += 'border-top:solid 2px #21618C;border-bottom:solid 2px #21618C;';
        h += 'border-top-left-radius:4px;border-bottom-left-radius:4px;';
        h += 'z-index: 9999999;width:400px;top:70px;bottom:20px;right:-400px;" >';

        h += '</div>';
        
        $('body').append(h);
        
        addParamsPanel();

    }

}

function addParamsPanel(){

    var crossiconplus = 'img/classique/cross_white.png';
	
    var h = '<header ';
    h += 'style="position:relative;cursor:pointer;border-bottom:solid 2px #21618C;width:100%;height:55px;padding:0px;" ';
    h += ' >';
    
    h += '<a onClick="inclusiveHide();" ';
    h += 'style="position:absolute;display:block;top:10px;right:10px;padding:9px;margin:0px;';
    h += 'border-radius:4px;width:38px;height:34px;margin:0px;text-align:center;background-color:#dc3545!important;" >';
    h += '<img id="studioeltools" src="'+ crossiconplus + '" /> ';
    h += '</a>';
    h += '</header>';
    
    h += '<p ';
    h += 'style="text-align:center;padding:10px;padding-top:13px;margin:0px;font-size:17px;" >';
    h += termPersonalize + '</p>';

    h += '<p ';
    h += 'style="font-weight:bold;text-align:center;padding-top:13px;padding-top:20px;margin:0px;font-size:16px;color:#1A5276;" >';
    h += termAccessibility + '</p>';
    
    var decTitle = 'padding:1px;padding-top:36px;'

    h += '<p ';
    h += 'style="text-align:center;'+decTitle+'margin:0px;font-size:16px;" >';
    h += termRedimFont + '</p>';
    
    h += '<div style="text-align:center;" >';
    

    var hdiv = ' style="height:40px;text-decoration:none;overflow:hidden;" ';
    h += '<div class="btn-group">';
    h += '<a '+hdiv+' title="'+termMinFont+'"  href="#" class="bloc_decrease_font btn btn-default">';
    h += '<em style="font-size:11px;font-weight:bold;text-decoration:none;" >A</em>';
    h += '</a>';
    h += '<a '+hdiv+' title="'+termNorFont+'"  href="#" class="bloc_reset_font btn btn-default">';
    h += '<em style="font-size:15px;font-weight:bold;text-decoration:none;" class="fa fa-font">A</em>';
    h += '</a>';
    h += '<a '+hdiv+' title="'+termMaxFont+'"  href="#" class="bloc_increase_font btn btn-default">';
    h += '<em style="font-size:19px;font-weight:bold;text-decoration:none;" class="fa fa-font">A</em>';
    h += '</a>';
    h += '</div>';

    h += '</div>';

    h += '<p ';
    h += 'style="text-align:center;'+decTitle+'margin:0px;font-size:16px;" >';
    h += termAdaptFont + '</p>';

    h += '<div style="text-align:center;" >';
        h += '<div class="btn-group">';
        h += '<a title="Option"  href="#" class="bloc_option_font_0 btn btn-default">';
        h += termClassic;
        h += '</a>';
        h += '<a title="Option"  href="#" class="bloc_option_font_1 btn btn-default">';
        h += termDys;
        h += '</a>';
        h += '</div>';
    h += '</div>';

    h += '<p ';
    h += 'style="text-align:center;'+decTitle+'margin:0px;font-size:16px;" >';
    h += termContrast + '</p>';

    h += '<div style="text-align:center;" >';
    h += '<div class="btn-group">';
    h += '<a title="Option"  href="#" class="bloc_colors_0 btn btn-default">';
    h += termNone;
    h += '</a>';
    h += '<a title="Option"  href="#" class="bloc_colors_1 btn btn-default">';
    h += termContrastStrong;
    h += '</a>';
    h += '</div>';

    h += '<p ';
    h += 'style="text-align:center;'+decTitle;
    h += 'margin:0px;font-size:16px;" >' + 'Focus line' + '</p>';

    h += '<div style="text-align:center;" ><div class="btn-group">';
    h += '<a title="Option" href="#" class="bloc_focus_0 btn btn-default">';
    h += 'Active Focus';
    h += '</a></div></div>';

    h += '</div>';

    $("#params_panel_inclusive").html(h);

    $(".bloc_reset_font").click(function() {
        storageInclusiveSetParam('value_fontzoom',0);
        applyParamsZoomInclusive();
    });
    $(".bloc_increase_font").click(function(){
        storageInclusiveSetParam('value_fontzoom',1);
        applyParamsZoomInclusive();
    });
    $(".bloc_decrease_font").click(function(){
        storageInclusiveSetParam('value_fontzoom',-1);
        applyParamsZoomInclusive();
    });

    $(".bloc_option_font_0").click(function(){
        storageInclusiveSetParam('value_fontoption',0);
        applyParamsZoomInclusive();
        setTimeout(function(){
            location.reload();
        },100);
    });
    $(".bloc_option_font_1").click(function(){
        storageInclusiveSetParam('value_fontoption',1);
        applyParamsZoomInclusive();
    });

    $(".bloc_colors_0").click(function(){
        storageInclusiveSetParam('value_colorsoption',0);
        applyParamsZoomInclusive();
        setTimeout(function(){
            location.reload();
        },100);
    });
    $(".bloc_colors_1").click(function(){
        storageInclusiveSetParam('value_colorsoption',1);
        applyParamsZoomInclusive();
    });

    $(".bloc_focus_0").click(function(){

        var value_focusoption = storageInclusiveGetParam('value_focusoption');
        if (value_focusoption!=1) {
            value_focusoption = 1;
            $(".bloc_focus_0").css("background-color","white");
            $(".bloc_focus_0").css("color","#626567");
        } else {
            value_focusoption = 0;
            $(".bloc_focus_0").css("background-color","#626567");
            $(".bloc_focus_0").css("color","white");
        }
        storageInclusiveSetParam('value_focusoption',value_focusoption);

        applyFocusLineDiv();
    });

}
var originNavbarFont = "";


function applyFocusLineDiv(){
    
    var value_focusoption = storageInclusiveGetParam('value_focusoption');

    if (value_focusoption!=1) {
        value_focusoption = 0;
        $(".bloc_focus_0").css("background-color","white");
        $(".bloc_focus_0").css("color","#626567");
    } else {
        $(".bloc_focus_0").css("background-color","#626567");
        $(".bloc_focus_0").css("color","white");
    }

    if (value_focusoption==1) {
        if (!document.getElementById("focus_line_div")) {
            var h = '<div id="focus_line_div" style="position:fixed;';
            h += 'top:500px;left:0px;right:0px;bottom:0px;opacity:0.8;';
            h += 'bottom:0px;background-color:black;z-index:9999;" ></div>';
            h += '<div id="focus_line_div_2" style="position:fixed;';
            h += 'top:-1050px;left:0px;right:0px;height:1000px;opacity:0.8;';
            h += 'bottom:0px;background-color:black;z-index:9999;" ></div>';
            $("body").append(h);
            installMouseTrackingDocFocus();
        }
        $('#focus_line_div').show();
        $('#focus_line_div_2').show();
        inclusiveHide();
    } else {
        if (document.getElementById("focus_line_div")) {
            $('#focus_line_div').hide();
            $('#focus_line_div_2').hide();
        }
    }

}

function installMouseTrackingDocFocus() {
    
    $(document).mousemove(function(e){
        var x = e.pageX;
        var y = e.pageY;
        y = y - $(window).scrollTop();
        $('#focus_line_div').css('top',(y+60)+'px');
        $('#focus_line_div_2').css('top',(y-1120)+'px');
    });

    $(document).scroll(function(e){
        var x = e.pageX;
        var y = e.pageY;
        y = y - $(window).scrollTop();
        $('#focus_line_div').css('top',(y+60)+'px');
        $('#focus_line_div_2').css('top',(y-1120)+'px');
    });

}

function applyParamsZoomInclusive() {

    var value_colorsoption = storageInclusiveGetParam('value_colorsoption');

    $(".bloc_colors_0").css("background-color","white");
    $(".bloc_colors_0").css("color","#626567");
    $(".bloc_colors_1").css("background-color","white");
    $(".bloc_colors_1").css("color","#626567");

    if (value_colorsoption==0) {
        $(".bloc_colors_0").css("background-color","#626567");
        $(".bloc_colors_0").css("color","white");
    }

    if (value_colorsoption==1) {

        $(".bloc_colors_1").css("background-color","#626567");
        $(".bloc_colors_1").css("color","white");
        
        $("ul.list-teachdoc li").css("color","black").css("font-weight","bold").css("background-color","white");

        $("h2").css("color","black").css("background-color","white").css("font-weight","bold");
        $("h2").css("box-shadow","none").css("margin-left","-15px");
        $("h2").css("border","solid 1px black").css("padding-top","20px").css("padding-bottom","20px");
        $("h1").css("color","black").css("background-color","white").css("font-weight","bold");
        
        $(".panel-default").css("border","solid 2px black");

        $("body").css("background","white");
        $("body").css("background-color","white!important");

    }

    var value_fontoption = storageInclusiveGetParam('value_fontoption');

    $(".bloc_option_font_0").css("background-color","white");
    $(".bloc_option_font_0").css("color","#626567");
    $(".bloc_option_font_1").css("background-color","white");
    $(".bloc_option_font_1").css("color","#626567");

    if (value_fontoption==0) {
        $("ul.navbar-nav").css("font-family",originNavbarFont);
        $("h1,h2,h3,.menu-content,a,p").css("font-family",originNavbarFont);
        $("ul.navbar-nav").css("zoom","100%");
        $(".panel-heading a").css("zoom","100%");
        $(".bloc_option_font_0").css("background-color","#626567");
        $(".bloc_option_font_0").css("color","white");
    }
    if (value_fontoption==1) {
        $("ul.navbar-nav").css("font-family","OpenDyslexic , OpenDyslexicie");
        $("h1,h2,h3,.menu-content,a,p,.teachdoctextContent,.quizzTextTd").css("font-family","OpenDyslexic , OpenDyslexicie");
        $("ul.list-teachdoc li").css("zoom","115%").css("font-family","OpenDyslexic , OpenDyslexicie");
        $(".bloc_option_font_1").css("background-color","#626567");
        $(".bloc_option_font_1").css("color","white");
    }

    var value_fontzoom = storageInclusiveGetParam('value_fontzoom');

    $(".bloc_decrease_font").css("background-color","white");
    $(".bloc_decrease_font").css("color","#626567");
    $(".bloc_reset_font").css("background-color","white");
    $(".bloc_reset_font").css("color","#626567");
    $(".bloc_increase_font").css("background-color","white");
    $(".bloc_increase_font").css("color","#626567");

    if (value_fontzoom==-1) {
        document.body.style.zoom = "90%";
        $(".bloc_decrease_font").css("background-color","#626567");
        $(".bloc_decrease_font").css("color","white");
    }
    if (value_fontzoom==0) {
        document.body.style.zoom = "100%";
        $(".bloc_reset_font").css("background-color","#626567");
        $(".bloc_reset_font").css("color","white");
    }
    if (value_fontzoom==1) {
        document.body.style.zoom = "110%";
        $(".bloc_increase_font").css("background-color","#626567");
        $(".bloc_increase_font").css("color","white");
    }

    var value_focusoption = storageInclusiveGetParam('value_focusoption');
    if (value_focusoption==1) {
        $(".bloc_focus_0").css("background-color","#626567");
        $(".bloc_focus_0").css("color","white");
        applyFocusLineDiv();
    } else {
        $(".bloc_focus_0").css("background-color","white");
        $(".bloc_focus_0").css("color","#626567");
    }

}

setTimeout(function(){
    addAccessPanel();
    originNavbarFont = $("ul.navbar-nav").css("font-family");
    applyParamsZoomInclusive();
},300);

setTimeout(function(){
    applyParamsZoomInclusive();
},700);

function inclusiveShow(){

    $("#params_panel_inclusive").animate({
        right: "0px"
    },600, function() {

    });
    
}

function inclusiveHidePanel(){

    $("#access_panel_inclusive,#params_close").animate({
        right: "-74px"
    },600, function() {

    });
    
}

function inclusiveHide(){

    $("#params_panel_inclusive").animate({
        right: "-400px"
    },600, function() {

    });
    
}

function storageInclusiveGetParam(name){
    if (localStorage) {
        var getvalue = parseInt(window.localStorage.getItem(name));
        return getvalue;
    }
}

function storageInclusiveSetParam(name,val){
    if (localStorage) {
       window.localStorage.setItem(name,val);
    }
}
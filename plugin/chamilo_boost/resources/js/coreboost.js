
var GlobalScoreStatsTable = 0;

function detectPreloadCards(){

	var urlplug = $('#plugfullpath').html();

	$(".thecard").each(function(index){

		var obj = $(this);
		var type = obj.attr("type");
		var idref = obj.attr("idref");

		if(type=='statstable'){

			var urlpath = urlplug + "ajax/myspace.json.ajax.php?a=lp_global_report";
			var idCache = 'statstableglobal';
			var totalPourG = 0;

			var h = '<div class="easypie-black" ></div>';
			h += '<div id="easypiechart-statstables" ';
			h += ' class="easypiechart-boost" ';
			h += ' data-percent="' + totalPourG + '" >';
			h += '<span class="easypiechartpercent">';
			h += Math.round(totalPourG) + '</span>';
			h += '</div>';

			obj.append(h);

			$.getJSON(urlpath).done(function(result){
				xhrLoadBoostData[idCache] = result;
				var scoreG = '0%';
				$.each(result.data,function(){
					if(this.type=='Global'){
						scoreG = this.score + '';
					}
				});
				scoreG = scoreG.replace('%','');
				scoreG = scoreG.replace(' ','');
				GlobalScoreStatsTable = scoreG;

				setTimeout(function(){updateCirclifulStatsTables(GlobalScoreStatsTable); },500);
				

			});

			showCirclifulStatsTables();

		}

	});
	
}

function fakeLoadStatsTable(){

	var urlplug = $('#plugfullpath').html();

	var TplA = '<img style="position:relative;';
	TplA += 'margin:5px;width:90px;height:20px;" ';
	TplA += ' src="' + urlplug + 'img/rectangle-loader.gif" /><br>';

	return TplA + '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';

}

function installStatsTable(code,idref,typ){

	var urlplug = $('#plugfullpath').html();

    prepareCode = code;
    prepareDir = idref;
	prepareTyp = typ;
	
	$('.thecardview').html(fakeLoadStatsTable());
	
	var idCache = 'statstableglobal';

	if(xhrLoadBoostData[idCache]!== undefined){
	
		displayStatsTable(xhrLoadBoostData[idCache]);
	
	}else{
		
		var urlpath = urlplug + "ajax/myspace.json.ajax.php?a=lp_global_report";

		$.getJSON(urlpath).done(function(result){
			xhrLoadBoostData[idCache] = result;
			displayStatsTable(result)
		});

	}

}

function displayStatsTable(result){

	var totalPourG = '0%';
	var titleG = 'global';

	$.each(result.data,function(){
		if(this.type=='Global'){
			titleG = this.title + '';
			totalPourG = this.score + '';
		}
	});
	
	totalPourG = totalPourG.replace('%','');
	totalPourG = totalPourG.replace(' ','');

	var finalClose = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';

	var divS = '<div class="statstableinner" >';

	var topGlobal = '';

	topGlobal += '<div id="easypiechart-statstables-inner" ';
	topGlobal += ' class="easypiechart-boost-inner" ';
	topGlobal += ' data-percent="' + totalPourG + '" >';
	topGlobal += '<span class="easypiechartpercent">';
	topGlobal += Math.round(totalPourG) + '%</span>';
	topGlobal += '</div>';
	
	topGlobal += '<div class="easypiechart-statstables-title" >';
	topGlobal += titleG;
	topGlobal += '</div>';

	topGlobal += '<div class="statssessionsinner" >';

	$.each(result.data,function(){
		
		if(this.type=='session'){

			var titleS = this.title;

			topGlobal += '<table style="margin-bottom:10px;width:98%;" >';
			topGlobal += '<tbody style="width:100%;">';
			topGlobal += '<tr><td class="statstables-session-title" style="width:39%;" >';
			topGlobal += titleS;
			topGlobal += '</td>';
			topGlobal += '<td style="width:59%;" class="statstables-session-course" >';
			
			topGlobal += '<table class="data_table_charts" >';
			
			$.each(result.data,function(){

				if(this.type=='session_course'&&this.ref==titleS){
					topGlobal += '<tr>';
					topGlobal += '<td>' + this.title + '</td>';
					topGlobal += '<td>' + this.score + '</td>';
					topGlobal += '</tr>';
				}

			});

			topGlobal += '</table>';
			topGlobal += '</td></tr>';
			topGlobal += '</tbody>';
			topGlobal += '</table>';
		}
	});

	topGlobal += '</div>';

	var divE = '</div>';

	$('.thecardview').html(divS + topGlobal + divE + finalClose);

	showCirclifulStatsTablesInner();
	
}

function showCirclifulStatsTables(){
	
	if(jQuery()){
		if(jQuery().easyPieChart){
			$('#easypiechart-statstables').easyPieChart({
				scaleColor: false,
				barColor: '#1ebfae',
				lineWidth:12,
				size: 90,
				trackColor: '#f2f2f2'
			});
		}else{
			var getExtras = _p['web'] + 'web/assets/jquery.easy-pie-chart/dist/jquery.easypiechart.js';
			console.log(getExtras);
			$.getScript(getExtras, function(){
				console.log("easypiechart is include");
			});
			setTimeout(function(){
				showCirclifulStatsTables();
			},300);
		}
	}
	
}

function updateCirclifulStatsTables(scoreG){

	if(isNaN(scoreG)){
		scoreG=0;
	}
	
	$('#easypiechart-statstables').data('easyPieChart').update(Math.round(scoreG));
	$('.easypiechart-boost .easypiechartpercent').html(Math.round(scoreG) + '%');

}

function showCirclifulStatsTablesInner(){
	
	if(jQuery()){
		if(jQuery().easyPieChart){
			$('#easypiechart-statstables-inner').easyPieChart({
				scaleColor: false,
				barColor: '#1ebfae',
				lineWidth:12,
				size: 120,
				trackColor: '#f2f2f2'
			});
		}else{
			var getExtras = _p['web'] + 'web/assets/jquery.easy-pie-chart/dist/jquery.easypiechart.js';
			console.log(getExtras);
			$.getScript(getExtras, function(){
				console.log("easypiechart is include");
			});
			setTimeout(function(){
				showCirclifulStatsTables();
			},300);
		}
	}
	
}

function updateCirclifulStatsTableInner(scoreG){

	if(isNaN(scoreG)){
		scoreG=0;
	}
	
	$('#easypiechart-statstables-inner').data('easyPieChart').update(Math.round(scoreG));
	$('.easypiechart-boost-inner .easypiechartpercent').html(Math.round(scoreG) + '%');

}
function installCourseLarge(code,idref,typ){

    var urlplug = $('#plugfullpath').html();

    prepareCode = code;
    prepareDir = idref;
	prepareTyp = typ;
	
	var objCd = getActiveCard();
	objCd.find('.card-outmore').css('display','none');
    
    var idCache = 'course' + prepareDir;
    
    if(xhrLoadBoostData[idCache]!== undefined){
        
        setTimeout(function(){
            var result = xhrLoadBoostData[idCache];
            $('.thecardview').html(renderCards(prepareCode,prepareDir,prepareTyp,result));
            showCircliful();
            loadExtrasCardsLarge(code,idref,typ);
        },300);
        
    }else{
    
        var urlpath = urlplug + "ajax/getlpdata.php?d=" + prepareDir;
        
        $.getJSON(urlpath).done(function(result){
			
            xhrLoadBoostData[idCache] = result;
            $('.thecardview').html(renderCards(prepareCode,prepareDir,prepareTyp,result));
            showCircliful();
            loadExtrasCardsLarge(code,idref,typ);
        });
    
    }

}

function baseRightContent(){

	var ph = $('.thecardview').outerHeight();
	var pw = $('.thecardview').outerWidth();
	
	var haut = (ph - (140 + 40))/2;
	var haut2 = haut;

	if(haut2>180&&haut2<246){
		var decY = 246 - haut2;
		haut2 = haut2 + decY;
		haut = haut - decY;
	}

	var ht = '';

	if(pw>774){

		var urlplug = $('#plugfullpath').html();

		var TplA = '<img style="position:relative;';
		TplA += 'margin:5px;width:90px;height:20px;" ';
		TplA += ' src="' + urlplug + 'img/rectangle-loader.gif" /><br>';

		var TplB = '<img style="position:relative;';
		TplB += 'margin:5px;width:150px;height:20px;" ';
		TplB += ' src="' + urlplug + 'img/rectangle-loader.gif" /><br>';

		var TplC = '<img style="position:relative;';
		TplC += 'margin:5px;width:146px;height:110px;margin:20px;" ';
		TplC += ' src="' + urlplug + 'img/rectangle-loader.gif" /><br>';

		ht = '<div class="thecardviewzoneRight" >';
		ht += '<div class="descriptZoneRight" style="height:' + haut + 'px;" >';
		ht += TplA + TplB + TplA;
		ht += '</div>';

		ht += '<div class="toolszoneRight" style="height:' + haut2 + 'px;" >';
		ht += TplC;
		ht += '</div>';

		ht += '<div class="timelinezoneRight" >';
		ht += '<div class="timeCubeRight" >..-..-....</div>';
		ht += '<div class="timeCubeLeft" >00:00:00</div>';
		ht += '</div>';
	}
	return ht;

}

function injectBaseLeftContent(idref){
	
	$('.thecardviewzone2Large').css("display","none");

	$('.thecardviewzoneLarge').css("top","230px");

	var mySpace = _p['web'] + 'main/auth/my_progress.php?course=' + idref;

	var cdTyp = "<a id=toolLeft class=toolsCarreLeftStats ";
	cdTyp += " href='"+ mySpace + "'  >";
	cdTyp += "<div class=titleCarreRight >" + $('#tradStats').html() +"</div></a>"

	$('.thecardviewzoneLarge').html(cdTyp);

	var myInbox = _p['web'] + 'main/messages/inbox.php';

	var cdBox = "<a id=toolLeft class=toolsCarreLeftBox ";
	cdBox += " href='"+ myInbox + "'  >";
	cdBox += "<div class=titleCarreRight >" + $('#tradMyInbox').html() +"</div></a>"

	$('.thecardviewzoneLarge').append(cdBox);

}

function loadExtrasCardsLarge(code,idref,typ){
	
	$('.thecardview').append(baseRightContent());

	if(nbFullCourses==0&&nbFullExercises==0){
		injectBaseLeftContent(idref);
	}
	
	var urlplug = $('#plugfullpath').html();
	
	var urlpath = urlplug + "ajax/getCourseSummary.php?f=" + idref;

	refTypExtrasCards = typ;
	refDirExtrasCards = idref;

	if(jsonExtrasCards[urlpath]!== undefined){
	
		installToolsLarge(jsonExtrasCards[urlpath],idref);

	}else{

		$.getJSON(urlpath).done(function(result){

			var noData = false;
			
			if(typeof result === 'undefined') {	
				noData = true;
			}else{
				if(typeof result.tools === 'undefined') {
					noData = true;
				}
			}
			if(!noData){
				
				var skills = new Object();
				skills.name = getTermLangBoost('Skills');
				skills.type = 'skills';
				var imgSkills = _p['web_plugin'] + 'chamilo_boost/resources/img/skills.jpg';
				skills.image = imgSkills;
				result.tools.push(skills);
				
				jsonExtrasCards[urlpath] = result;
				
				installToolsLarge(result,idref);
			}
	
		});
	
	}
	
}

function installToolsLarge(result,idref){

	var pw = $('.toolszoneRight').outerWidth();
	var ph = $('.toolszoneRight').outerHeight();
	
	var nbOnCol = Math.trunc(pw / 152);
	var marginLeft = (pw - (nbOnCol * 148)) / (nbOnCol+1);

	var nbOnline = Math.trunc(ph / 120);
	var marginTop = (ph - (nbOnline * 112)) / (nbOnline+1);
	
	var posX = marginLeft;
	var posY = marginTop;

	var oriX = marginLeft;
	var oriY = marginTop;

	var idObj = 0;
	
	var firstPage = 1;
	
	var imgDown = _p['web_plugin'] + 'chamilo_boost/resources/img/arrow-down.png';

	$('.toolszoneRight').html('');

	$.each(result.tools,function(){
		
		var type = cleanTexteFromJson(this.type);
		var name = cleanTexteFromJson(this.name);
		var link = cleanTexteFromJson(this.link);
		
		var pathimg = '';
		var image = (this.image);

		if(image.indexOf('http')==-1){
			image = image.replace(".gif",".png");
			pathimg = _p['web_main'] + 'img/icons/64/';
		}
		if(link.indexOf('http')==-1){
			link = getUrlByLinkObj(link,type);
		}

		var cardTyp = "<a id=toolR" + idObj + " ";

		if(type=='chat'){
			cardTyp += " onClick=\"loadChatToolsLarge('" + link + "','" + idref + "')\" ";
			link = "javascript:void(0)"
		}

		cardTyp += " href='"+ link + "' class=toolsCarreRight >";
		cardTyp += "<img src='" + pathimg + image + "' />"
		cardTyp += "<div class=titleCarreRight >" + name + "</div></a>"
		
		if(type!='learnpath'&&type!='user'&&type!='stats'
		&&type!='group'&&type!='description'){

			$('.toolszoneRight').append(cardTyp);
			
			$('#toolR' + idObj).css("position","absolute");
			$('#toolR' + idObj).css("left",posX + "px");
			$('#toolR' + idObj).css("top",posY + "px");

			oriX = posX;
			oriY = posY;

			posX = posX + 148 + marginLeft;
			
			if(posX>(pw-5)){

				posX = marginLeft;
				posY = posY + 112 + marginTop;
				

				if(firstPage>2){
					if(posY>(((ph*firstPage)-(marginTop*(firstPage-1)))-5)){
						
						$('#toolR' + idObj).css("left",posX + "px");
						$('#toolR' + idObj).css("top",posY + "px");

						posX = posX + 148 + marginLeft;

						var cFlech = "<a href='javascript:void(0);' ";
						cFlech += " id=toolFleche" + idObj + " ";
						cFlech += " onClick='nextPageUpPageBN(" + firstPage + ");' ";
						cFlech += " class=toolsCarreRight >";
						cFlech += "<img src='" + imgDown + "' /></a>"

						$('.toolszoneRight').append(cFlech);

						$('#toolFleche' + idObj).css("position","absolute");
						$('#toolFleche' + idObj).css("left",oriX + "px");
						$('#toolFleche' + idObj).css("top",oriY + "px");
						
						firstPage = firstPage + 1;

					}	
				}


				if(firstPage==2){
					if(posY>(((ph*2)-marginTop)-5)){
						
						firstPage = 3;
						
						$('#toolR' + idObj).css("left",posX + "px");
						$('#toolR' + idObj).css("top",posY + "px");

						posX = posX + 148 + marginLeft;

						var cFlech = "<a href='javascript:void(0);' id=toolFleche" + idObj + " ";
						cFlech += " onClick='nextPageUpPage2();' class=toolsCarreRight >";
						cFlech += "<img src='" + imgDown + "' /></a>"

						$('.toolszoneRight').append(cFlech);

						$('#toolFleche' + idObj).css("position","absolute");
						$('#toolFleche' + idObj).css("left",oriX + "px");
						$('#toolFleche' + idObj).css("top",oriY + "px");
						
					}	
				}


				if(firstPage==1){
					if(posY>(ph-5)){
						
						firstPage = 2;

						$('#toolR' + idObj).css("left",posX + "px");
						$('#toolR' + idObj).css("top",posY + "px");

						posX = posX + 148 + marginLeft;

						var cardFlech = "<a href='javascript:void(0);' id=toolFleche" + idObj + " ";
						cardFlech += " onClick='nextPageUpPage();' class=toolsCarreRight >";
						cardFlech += "<img src='" + imgDown + "' /></a>"

						$('.toolszoneRight').append(cardFlech);

						$('#toolFleche' + idObj).css("position","absolute");
						$('#toolFleche' + idObj).css("left",oriX + "px");
						$('#toolFleche' + idObj).css("top",oriY + "px");
						
					}	
				}

			}
			
		}else{

			if(type=='learnpath'&&nbFullCourses>0){

				var cardTyp = "<a href='"+ link + "' class=toolsCarreRightJustIcon >";
				cardTyp +=  "<img src='" + pathimg + image + "' />"
				cardTyp += "</a>"

				$('.icon-near-donut').append(cardTyp);
			
			}

			if(type=='stats'){

				if(name.indexOf("@")!=-1){
					var res = name.split("@");
					$('.timeCubeRight').html(res[0]);
					$('.timeCubeLeft').html(res[1]);
				}
			
			}
			
		}
		
		idObj ++;

	});

	var imgUp = _p['web_plugin'] + 'chamilo_boost/resources/img/arrow-down.png';
	
	var cFlech = "<a href='javascript:void(0);' id=toolFleche" + idObj + " ";
	cFlech += " onClick='nextPageUpPageFinal();' class=toolsCarreRight >";
	cFlech += "<img src='" + imgUp + "' /></a>"
	$('.toolszoneRight').append(cFlech);
	$('#toolFleche' + idObj).css("position","absolute");
	$('#toolFleche' + idObj).css("left",posX + "px");
	$('#toolFleche' + idObj).css("top",posY + "px");

	$('.boost-search-result').css("display","none");

	loadDescriptionCourse(idref);

}

function loadChatToolsLarge(link,idref){
	
	window.open(link + '?cidReq=' + idref + '&id_session=' + prepareIdSession +  '&gidReq=0&gradebook=0&origin=','window_chat' + idref ,config='height='+600+', width='+825+', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no');
}

function getUrlByLinkObj(link,typ){
	
	var u = _p['web_main'] + link + '?cidReq=' + refDirExtrasCards + '&id_session=' + prepareIdSession;

	if(typ=='document'){
		u = _p['web_main'] + 'document/document.php?cidReq=' + refDirExtrasCards + '&id_session=' + prepareIdSession;
	}
	if(typ=='quiz'){
		u = 'main/exercise/exercise.php?cidReq=' + refDirExtrasCards + '&id_session=' + prepareIdSession;
	}
	if(typ=='learnpath'){
		u = 'main/lp/lp_controller.php?cidReq=' + refDirExtrasCards + '&id_session=' + prepareIdSession;
	}
	if(typ=='skills'){
		u = 'plugin/chamilo_boost_skill_view/index.php';
	}

	return u;

}

function nextPageUpPage(){

	var ph = $('.toolszoneRight').outerHeight();
	var nbOnline = Math.trunc(ph / 120);
	var marginTop = (ph - (nbOnline * 112)) / (nbOnline+1);

	$(".toolsCarreRight").animate({
		marginTop: "-" + (ph - marginTop) + "px"
	},500,function(){

	});
}

function nextPageUpPage2(){

	var ph = $('.toolszoneRight').outerHeight();
	var nbOnline = Math.trunc(ph / 120);
	var marginTop = (ph - (nbOnline * 112)) / (nbOnline+1);

	$(".toolsCarreRight").animate({
		marginTop: "-" + ((ph * 2) - (marginTop* 2)) + "px"
	},500,function(){

	});
}

function nextPageUpPageBN(n){

	var ph = $('.toolszoneRight').outerHeight();
	var nbOnline = Math.trunc(ph / 120);
	var marginTop = (ph - (nbOnline * 112)) / (nbOnline+1);

	$(".toolsCarreRight").animate({
		marginTop: "-" + ((ph * n) - (marginTop * n)) + "px"
	},500,function(){

	});

}

function nextPageUpPageFinal(){

	var ph = $('.toolszoneRight').outerHeight();
	var nbOnline = Math.trunc(ph / 120);
	var marginTop = (ph - (nbOnline * 112)) / (nbOnline+1);
	
	$(".toolsCarreRight").css("margin-top",ph + "px");

	$(".toolsCarreRight").animate({
		marginTop: "0px"
	},500,function(){

	});
}

function loadDescriptionCourse(idref){

    var urlplug = $('#plugfullpath').html();

    var urlpath = urlplug + "ajax/getlpLarge.php?d=" + idref;

    var idCache = 'over' + idref;

    if(xhrLoadBoostData[idCache]!== undefined){
        
			var finalCtn =  "<div class='innerFlexContent' >";
			finalCtn += xhrLoadBoostData[idCache] + "</div>";
			$('.descriptZoneRight').html(finalCtn);
    }else{

        $.ajax({
            url: urlpath
        }).done(function(result){

			result = result.replace("{namecourse}","");

            xhrLoadBoostData[idCache] = result;
			var finalCtn =  "<div class='innerFlexContent' >";
			finalCtn += result + "</div>";
			$('.descriptZoneRight').html(finalCtn);
        });

    }

}

function loadNearTools(){

	var rh = '';
	
	rh += '<a href="#" onClick="loadPageExtraPage(\'skills\');" >';
	rh += '<div class="thecardOne LPShortcut shortcutskills" type="LPShortcut" >';
	rh += '<div class="card-img-one">';
	rh += '<div id="cardLPShortcut" class="back-skills" ></div>';
	rh += '</div>';
	rh += '<div class="card-caption">';
	rh += '<i id="like-btn" class="fa fa-circle" ></i>';
	rh += '<h2 id="title-skills" >' + getTermLangBoost('Skills') + '</h2></div>';
	rh += '<div class="card-outmore">';
	rh += '<h5>' + getTermLangBoost('Access') + '</h5>';
	rh += '<i id="outmore-icon" class="fa fa-angle-right"></i>';
	rh += '</div>';
	rh += '</div></a>';


	rh += '<a href="#" onClick="loadPageExtraPage(\'document\');" >';
	rh += '<div class="thecardOne LPShortcut shortcutdocument" type="LPShortcut" >';
	rh += '<div class="card-img-one">';
	rh += '<div id="cardLPShortcut" class="back-document" ></div>';
	rh += '</div>';
	rh += '<div class="card-caption">';
	rh += '<i id="like-btn" class="fa fa-circle" ></i>';
	rh += '<h2 id="title-document" >' + " Documents" + '</h2></div>';
	rh += '<div class="card-outmore">';
	rh += '<h5>' + getTermLangBoost('Access') + '</h5>';
	rh += '<i id="outmore-icon" class="fa fa-angle-right"></i>';
	rh += '</div>';
	rh += '</div></a>';

	rh += '<a href="#" onClick="loadPageExtraPage(\'quiz\');" >';
	rh += '<div class="thecardOne LPShortcut shortcutquiz" type="LPShortcut" >';
	rh += '<div class="card-img-one">';
	rh += '<div id="cardLPShortcut" class="back-quiz" ></div>';
	rh += '</div>';
	rh += '<div class="card-caption">';
	rh += '<i id="like-btn" class="fa fa-circle" ></i>';
	rh += '<h2 id="title-quiz" >' + " Tests" + '</h2></div>';
	rh += '<div class="card-outmore">';
	rh += '<h5>' + getTermLangBoost('Access') + '</h5>';
	rh += '<i id="outmore-icon" class="fa fa-angle-right"></i>';
	rh += '</div>';
	rh += '</div></a>';


	rh += '<a href="#" onClick="loadPageExtraPage(\'learnpath\');" >';
	rh += '<div class="thecardOne LPShortcut shortcutlearnpath" type="LPShortcut" >';
	rh += '<div class="card-img-one">';
	rh += '<div id="cardLPShortcut" class="back-learning" ></div>';
	rh += '</div>';
	rh += '<div class="card-caption">';
	rh += '<i id="like-btn" class="fa fa-circle" ></i>';
	rh += '<h2 id="title-learnpath" >' + "Parcours" + '</h2></div>';
	rh += '<div class="card-outmore">';
	rh += '<h5>' + getTermLangBoost('Access') + '</h5>';
	rh += '<i id="outmore-icon" class="fa fa-angle-right"></i>';
	rh += '</div>';
	rh += '</div></a>';


	rh += '<a href="#" onClick="loadPageExtraPage(\'next\');" >';
	rh += '<div class="thecardOne LPShortcut shortcutnext" type="LPShortcut" >';
	rh += '<div class="card-img-one">';
	rh += '<div id="cardLPShortcut" class="back-next" ></div>';
	rh += '</div>';
	rh += '<div class="card-caption">';
	rh += '<i id="like-btn" class="fa fa-circle" ></i>';
	rh += '<h2 id="title-next" >' + "Parcours" + '</h2></div>';
	rh += '<div class="card-outmore">';
	rh += '<h5>' + getTermLangBoost('Next') + '</h5>';
	rh += '<i id="outmore-icon" class="fa fa-angle-right"></i>';
	rh += '</div>';
	rh += '</div></a>';


	return rh;
	
}

function loadMiniToolsRight(){

	var urlplug = $('#plugfullpath').html();
	
	var rh = '';

	rh += '<div class="rapidSpeed" >';

	rh += '<div class="buttonSpeed buttonfake" >';
	rh += '<a href="#" onClick="loadLoginBox();" class="" target="_self" >';
	rh += '<img src="{urlplug}img/rectangle-loader.gif" alt="Forum" title="Forum" ></a>';
	rh += '<h4><a href="#" >&nbsp;</a></h4>';
	rh += '</div>';

	rh += '<div class="buttonSpeed buttonfake" >';
	rh += '<a href="#" onClick="loadLoginBox();" class="" target="_self" >';
	rh += '<img src="{urlplug}img/rectangle-loader.gif" alt="Forum" title="Forum" ></a>';
	rh += '<h4><a href="#" >&nbsp;</a></h4>';
	rh += '</div>';

	rh += '<div class="buttonSpeed buttonfake" >';
	rh += '<a href="#" onClick="loadLoginBox();" class="" target="_self" >';
	rh += '<img src="{urlplug}img/rectangle-loader.gif" alt="Forum" title="Forum" ></a>';
	rh += '<h4><a href="#" >&nbsp;</a></h4>';
	rh += '</div>';

	// Official Icons //

	rh += '<div id="buttonforum" class="buttonSpeed" style="display:none;" >';
	rh += '<a href="#" class="linkimageforum" target="_self" >';
	rh += '<img src="{urlplug}img/forum.png" alt="Forum" title="Forum" ></a>';
	rh += '<h4><a class="linkbuttonforum" href="#" >Forum</a></h4>';
	rh += '</div>';

	rh += '<div id="buttongroup" class="buttonSpeed" style="display:none;" >';
	rh += '<a href="#" class="linkimagegroup" target="_self" >';
	rh += '<img src="{urlplug}img/group.png" alt="group" title="group" ></a>';
	rh += '<h4><a class="linkbuttongroup" href="#" >group</a></h4>';
	rh += '</div>';

	rh += '<div id="buttontravaux" class="buttonSpeed" style="display:none;" >';
	rh += '<a href="#" class="linkimagetravaux" target="_self" >';
	rh += '<img src="{urlplug}img/works.png" alt="Travaux" title="Travaux" ></a>';
	rh += '<h4><a class="linkbuttontravaux" href="#" >Travaux</a></h4>';
	rh += '</div>';

	rh += '<div id="buttongradebook" class="buttonSpeed" style="display:none;" >';
	rh += '<a href="#" class="linkimagegradebook" target="_self" >';
	rh += '<img src="{urlplug}img/gradebook.png" alt="gradebook" title="gradebook" ></a>';
	rh += '<h4><a class="linkbuttongradebook" href="#" >gradebook</a></h4>';
	rh += '</div>';

	rh += '<div id="buttonchat" class="buttonSpeed" style="display:none;" >';
	rh += '<a  onClick="loadChatTools(this);" class="linkimagechat" style="cursor:pointer;" >';
	rh += '<img src="{urlplug}img/chat.png" alt="chat" title="chat" ></a>';
	rh += '<h4><a onClick="loadChatTools(this);" class="linkbuttonchat" style="cursor:pointer;" >chat</a></h4>';
	rh += '</div>';

	rh += '<div id="buttondocuments" class="buttonSpeed" style="display:none;" >';
	rh += '<a href="#" class="linkimagedocuments" target="_self" >';
	rh += '<img src="{urlplug}img/documents.png" alt="documents" title="documents" ></a>';
	rh += '<h4><a class="linkbuttondocuments" href="#" >documents</a></h4>';
	rh += '</div>';

	rh += '</div>';

	rh = rh.replace(/{urlplug}/g,urlplug);

	return rh;

}

function loadChatTools(obj){
	var url = obj.getAttribute('datahref');
	var sessionid = obj.getAttribute('sessionid');
	window.open(url + '?cidReq=FORMATEST&id_session=' + sessionid + '&gidReq=0&gradebook=0&origin=','window_chatFORMATEST',config='height='+600+', width='+825+', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no');
}

function loadPageExtraPage(typ){

	if(refTypExtrasCards!='demo'){

		window.location.href = getUrlExtrasCards(typ);

		$(".thecard").css("display","none");			
		$('.LPShortcut').css("display",'none');
		$('.thecardview').css("display","none");
		$('.shortcut' + typ).css("display",'block');
		$('.shortcut' + typ).animate({
			left : oriX + "px",
			top : oriY + "px"
		},900,function(){
			
		});
			
	}
}

function getUrlExtrasCards(typ){

	var u = 'index.php';
	
	if(refTypExtrasCards=='course'){
		if(typ=='document'){
			u = 'main/document/document.php?cidReq=' + refDirExtrasCards + '&id_session=' + prepareIdSession;
		}
		if(typ=='quiz'){
			u = 'main/exercise/exercise.php?cidReq=' + refDirExtrasCards + '&id_session=' + prepareIdSession;
		}
		if(typ=='learnpath'){
			u = 'main/lp/lp_controller.php?cidReq=' + refDirExtrasCards + '&id_session=' + prepareIdSession;
		}
		if(typ=='skills'){
			u = 'plugin/chamilo_boost_skill_view/index.php';
		}
	}
	
	return u;

}

var jsonExtrasCards = new Array();
var refTypExtrasCards = '';
var refDirExtrasCards = '';

function loadExtrasCards(code,idref,typ){
	
	var urlplug = $('#plugfullpath').html();
	var urlpath = urlplug + "ajax/getCourseSummary.php?f=" + idref;
	refTypExtrasCards = typ;
	refDirExtrasCards = idref;

	if(jsonExtrasCards[urlpath]!== undefined){
	
		installTools(jsonExtrasCards[urlpath]);
		installMiniTools(jsonExtrasCards[urlpath]);
		
	}else{

		$.getJSON(urlpath).done(function(result){
			var noData = false;
			if(typeof result === 'undefined') {	
				noData = true;
			}else{
				if(typeof result.tools === 'undefined') {
					noData = true;
				}
			}
			if(!noData){
				
				var skills = new Object();
				skills.name = getTermLangBoost('Skills');
				skills.type = 'skills';
				result.tools.push(skills);
				
				jsonExtrasCards[urlpath] = result;
				
				installTools(result);
				installMiniTools(result);
			}
	
		});
	
	}
	

}

function loadExtrasDemo(){

	refTypExtrasCards = 'demo';
	refDirExtrasCards = 'demo';

	var result = new Object();
	result.tools = new Object();

	result.tools.learnpath = new Object();
	result.tools.learnpath.name = getTermLangBoost('Parcours');
	result.tools.learnpath.type = 'learnpath';
	
	result.tools.quiz = new Object();
	result.tools.quiz.name = getTermLangBoost('Quizz');
	result.tools.quiz.type = 'quiz';

	result.tools.document = new Object();
	result.tools.document.name = getTermLangBoost('Documents');
	result.tools.document.type = 'document';

	result.tools.skills = new Object();
	result.tools.skills.name = getTermLangBoost('Skills');
	result.tools.skills.type = 'skills';

	installTools(result);

}

function installTools(result){

	var pw = $('.rapidContent').outerWidth();

	var ecartX = 20;

	if(pw<550){
		ecartX = 4;
	}
	
	$('.thecardOne').css("width",(largCards - 5) + "px");

	var posx = largCards + ecartX;
	var posy = 0;
	var timeUp = 200;
	var activeColumn = 1;
	
	if(activeColumn<fullLargeColumn){
		if(installTool(result,'learnpath',posx,posy,timeUp)==true){
			posx = posx + largCards + ecartX;
			timeUp = timeUp + 200;
			activeColumn++;
		}
	}
	
	if(activeColumn<fullLargeColumn){
		if(installTool(result,'quiz',posx,posy,timeUp)==true){
			posx = posx + largCards + ecartX;
			timeUp = timeUp + 200;
			activeColumn++;
		}
	}
	
	if(activeColumn<fullLargeColumn){
		if(installTool(result,'document',posx,posy,timeUp)==true){
			posx = posx + largCards + ecartX;
			timeUp = timeUp + 200;
			activeColumn++;
		}
	}
	
	var exept = false;
	if(activeColumn==4){
		posx = posx - (largCards + ecartX);
		posy = posy + 210;
		exept = true;
	}

	if(document.getElementById("activeSkills")){
		if(exept||activeColumn<fullLargeColumn){
			if(installTool(result,'skills',posx,posy,timeUp)==true){
				posx = posx + largCards + ecartX;
				timeUp = timeUp + 200;
				activeColumn++;
			}
		}
	}

	$('.boost-search-result').css("display","none");

}

function installTool(result,nameRef,decX,decY,timer){

	var b = false;

	$.each(result.tools,function(){
		
		var type = cleanTexteFromJson(this.type);
		var name = cleanTexteFromJson(this.name);

		if(type==nameRef){
			
			$('.shortcut' + nameRef).css("display","block");
			
			$('#title-' + nameRef).html(name);
			
			var lx = oriX + decX;
			var ly = oriY + decY;
			
			b = true;

			$('.shortcut' + nameRef).animate({
				left: lx + "px"
			},timer,function(){

				$('.shortcut' + nameRef).animate({
					top : ly + "px"
				},200,function(){

				});
			
			});

		}

	});

	return b;

}

function installMiniTools(result){

	$(".buttonfake").css("display","none");

	var nbMinis  = 0;

	$.each(result.tools,function(){

		var type = cleanTexteFromJson(this.type);
		var name = cleanTexteFromJson(this.name);
		var link = cleanTexteFromJson(this.link);
		var fld = cleanTexteFromJson(this.folder);

		if(nbMinis<3){

			if(type=='gradebook'){
				
				$('#buttongradebook').css("display","block");

				var lk = _p['web_main'] + link + "?cidReq=" + fld + "&id_session=" + prepareIdSession +  "&gidReq=0&gradebook=0&origin=";
				
				$('.linkimagegradebook').attr("href",lk);
				$('.linkimagegradebook').attr("title",name);

				$('.linkbuttongradebook').attr("href",lk);
				$('.linkbuttongradebook').html(name);
				$('.linkbuttongradebook').attr("title",name);

				nbMinis++;

			}

			if(type=='chat'){
			
				$('#buttonchat').css("display","block");

				var lk = _p['web_main'] + link + "?cidReq=" + fld + "&id_session=" + prepareIdSession +  "&gidReq=0&gradebook=0&origin="

				$('.linkimagechat').attr("datahref",lk);
				$('.linkimagechat').attr("title",name);

				$('.linkbuttonchat').attr("datahref",lk);
				$('.linkbuttonchat').html(name);
				$('.linkbuttonchat').attr("title",name);

				nbMinis++;

			}

			if(type=='forum'){

				$('#buttonforum').css("display","block");

				var lk = _p['web_main'] + link + "?cidReq=" + fld + "&id_session=" + prepareIdSession +  "&gidReq=0&gradebook=0&origin="

				$('.linkimageforum').attr("href",lk);
				$('.linkimageforum').attr("title",name);

				$('.linkbuttonforum').attr("href",lk);
				$('.linkbuttonforum').html(name);
				$('.linkbuttonforum').attr("title",name);

				nbMinis++;

			}

			if(type=='group'){

				$('#buttongroup').css("display","block");

				var lk = _p['web_main'] + link + "?cidReq=" + fld + "&id_session=" + prepareIdSession +  "&gidReq=0&gradebook=0&origin="

				$('.linkimagegroup').attr("href",lk);
				$('.linkimagegroup').attr("title",name);

				$('.linkbuttongroup').attr("href",lk);
				$('.linkbuttongroup').html(name);
				$('.linkbuttongroup').attr("title",name);

				nbMinis++;

			}
	
		}

	});


	$.each(result.tools,function(){

		var type = cleanTexteFromJson(this.type);
		var name = cleanTexteFromJson(this.name);
		var link = cleanTexteFromJson(this.link);
		var fld = cleanTexteFromJson(this.folder);

		if(nbMinis<3){

			if(type=='document'){

				$('#buttondocuments').css("display","block");

				var lk = _p['web_main'] + link + "?cidReq=" + fld + "&id_session=" + prepareIdSession +  "&gidReq=0&gradebook=0&origin=";
				
				$('.linkimagedocuments').attr("href",lk);
				$('.linkimagedocuments').attr("title",name);

				$('.linkbuttondocuments').attr("href",lk);
				$('.linkbuttondocuments').html(name);
				$('.linkbuttondocuments').attr("title",name);

				nbMinis++;

			}

	
		}

	});


}

function cleanTexteFromJson(name){

	if(typeof name === 'undefined'){
		name = "";
	}

	name = name.replace(/\//g,'');
	name = name.replace(/\\/g, '');
	name = name.replace(/\'/g,"&apos;");
	name = name.replace(/\+/g," ");
	try{
		name = decodeURI(name);
	}catch(e){

	}
	name = name.replace(/%C3%A9/g,"é");
	name = name.replace(/%2F/g,"/");

	return name;
}

function searchToggleBoost(obj, evt){

	$('.search-input').focus();

	var container = $(obj).closest('.boost-search-wrapper');
	
	if(!container.hasClass('active')){
		$('.search-input').focus();
		container.addClass('active');
		
		evt.preventDefault();
	}else if(container.hasClass('active') && $(obj).closest('.input-holder').length == 0){
		container.removeClass('active');
		$('.boost-search-result').css("display","none");
		$('.boost-search-result').html("");
		container.find('.search-input').val('');
	}
	
}

function searchLoadResult(){

	var term = $('.search-input').val();
	term = crossSTerm(term);

	$('.boost-search-result').html("");

	if(term==''){
		return false;
	}

	var nbResult = 0;

	$(".thecard").each(function(index){
		
		nbResult++;
		
		$(this).addClass("cardResultSearch" + nbResult);

		var obj = $(this);
		var find = false;
		
		var objS = { type : "card" };
		var title = obj.find("h2").text().toLowerCase();

		if(title==''){
			title = obj.find("h1").text().toLowerCase();
		}

		objS.title = title;

		if(crossSTerm(objS.title).indexOf(term)!=-1){
			find = true;
		}

		if(find){
			
			addBlockSearch(objS,nbResult);
		}

	});
	
	$('.boost-search-result').css("display","block");

	
}

var imp = 0;

function addBlockSearch(obj,iResult){

	var h = "";

	if(imp==0){
		h = "<div style='background-color:#E0ECF8;' ";
		imp = 1;
	}else{
		h = "<div style='background-color:#E6E6E6;' ";
		imp = 0;
	}
	h += " onClick='processSearchObj(" + iResult + ");' ";
	h += " onMouseDown='processSearchObj(" + iResult + ");' ";
	h += " class='block-search' >";
	h += obj.title;
	h += "</div>";

	$('.boost-search-result').append(h);

}

function processSearchObj(i){
	
	$('.boost-search-result').css("display","none");
	
	viewEvents($('.cardResultSearch' + i));

}

function crossSTerm(term){

	term = term.toLowerCase();
	term = term.replace(/ss/g,'s');
	term = term.replace(/tt/g,'t');
	term = term.replace(/é/g,'e');
	term = term.replace(/è/g,'e');
	term = term.replace(/ê/g,'e');
	
	return term;

}

function loadContent(idv){
	
	var urlplug = $('#plugfullpath').html();
	var urlpath = urlplug + idv;
	
	if(idv=='demo.html'){
		setTimeout(function(){loadExtrasDemo();},900);
	}
	
	$.ajax({
		url : urlpath,
		cache : false
	}).done(function(codeHtml){
	
		codeHtml = codeHtml.replace(/{urlplug}/g,urlplug);
		$('.thecardview').html(codeHtml);
		showCircliful();

	}).error(function(xhr, ajaxOptions, thrownError){
			
			var urlpath2 = urlplug + folderTpl + "contents/" + idv;
			$.ajax({
				url:urlpath2,
				cache:true
			}).done(function(codeHtml){
				codeHtml = codeHtml.replace(/{urlplug}/g,urlplug);
				$('.thecardview').html(codeHtml);
				showCircliful();
			});
			
	});
	
	var h = loadTpl.replace('{urlplug}',urlplug);

	return h;

}

var globalIdv = '';

function loadVideo(idv,over){
	
	globalIdv = idv;
	
	var h = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';
	
	var autoplay = 1;
	
	if(over){
		h = '<a href="#" class="cd-close" ></a>';
		autoplay = 0;
	}
	
	h += '<div class="theCardViewVideo" >';
	
	setTimeout(function(){addVideoProcess(over);},500);

	h += '</div>';

	return h;

}

function addVideoProcess(over){

	var autoplay = 1;

	var pw = $('.rapidContent').width();
	var cvw = $('.thecardview').width();
	var cvh = $('.thecardview').height();

	var h = '';

	if(globalIdv.indexOf(".mp4")!=-1){
		h = '<video width="' + (cvw-40) + '" height="' + (cvh-40) + '" controls autoplay controlsList="nodownload" >';
		h += '<source src="' + globalIdv + '" type="video/mp4"></video>';
	}else{
		h = '<iframe width="' + (cvw-40) + '" ';
		h += ' height="' + (cvh-40) + '" ';
		h += ' src="https://www.youtube.com/embed/' + globalIdv + '?autoplay=' + autoplay;
		h += '&rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" ';
		h += ' allow="autoplay; encrypted-media" allowfullscreen>';
		h += '</iframe>';
	}

	$('.theCardViewVideo').html(h);

}

function loadVideoNoAutoplay(idv){
	
	globalIdv = idv;

	var h = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';
	
	var autoplay = 1;
	
	h = '<a href="#" class="cd-close" ></a>';
	autoplay = 0;
	
	h += '<div class="theCardViewVideo" >';
	setTimeout(function(){addVideoProcessAutoplay();},500);
	h += '</div>';

	return h;

}

function addVideoProcessAutoplay(){

	var autoplay = 1;

	var pw = $('.rapidContent').width();
	var cvw = $('.thecardview').width();
	var cvh = $('.thecardview').height();

	var h = '';

	if(globalIdv.indexOf(".mp4")!=-1){
		h = '<video width="' + (cvw-40) + '" height="' + (cvh-40) + '" controls autoplay controlsList="nodownload">';
		h += '<source src="' + globalIdv + '" type="video/mp4"></video>';
	}else{
		var h = '<iframe width="' + (cvw-40) + '" ';
		h += ' height="' + (cvh-40) + '" ';
		h += ' src="https://www.youtube.com/embed/' + globalIdv + '?autoplay=' + autoplay;
		h += '&rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" ';
		h += ' allow="encrypted-media" allowfullscreen></iframe>';
	}

	$('.theCardViewVideo').html(h);

}

function loadStatsLittle(title){

	var h = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';
	
	h += '<div id="chart1card" class="thecardviewzoneStats" ></div>';
	h += '<div id="chart2card" class="thecardviewzoneStats2" >';
	
	h += '<table>';
	h += '<tr><td>';
	h += '<div class="ct-course" ></div>';
	h += '</td><td style="width:50px;text-align:center;" >';
	h += '<div id="timecharts" class="ct-course-label" >?</div>';
	h += '</td></tr>';
	
	h += '<tr><td>';
	h += '<div class="ct-time" ></div>';
	h += '</td><td style="width:50px;text-align:center;" >';
	h += '<div id="nblpcharts" class="ct-course-label" >?</div>';
	h += '</td></tr>';
	
	h += '<table>';

	h += '</div>';

	return h;
	
}

function loadContentFromPage(type,data){

    $('.thecardview').html(baseContentFromPage());

    var contentHome = $('article#home-welcome').html();

    if(contentHome!=''){

        contentHome = '<div class="content-from-page" >' + contentHome + '</div>';
        contentHome = contentHome + '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';
        
        $('.thecardview').html(contentHome);

        var allAudios = $('.thecardview').find("audio");
        allAudios.each(function(index){
            var container = $(this).parent().parent();
            if(container.hasClass("mejs__mediaelement")){
                var injectTopObject = container.parent().parent();
                if(injectTopObject.hasClass("mejs__container")){
                    var src =  $(this).attr("src");

                    injectTopObject.parent().html('<audio controls controlsList="nodownload" ><source src="' + src+ '" /></audio>')
                
                }
            }
        });

    }

}

function baseContentFromPage(){

    var urlplug = $('#plugfullpath').html();

    var TplA = '<img style="position:relative;';
    TplA += 'margin:5px;width:90px;height:20px;" ';
    TplA += ' src="' + urlplug + 'img/rectangle-loader.gif" /><br>';

    var TplB = '<img style="position:relative;';
    TplB += 'margin:5px;width:150px;height:20px;" ';
    TplB += ' src="' + urlplug + 'img/rectangle-loader.gif" /><br>';

    var TplC = '<img style="position:relative;';
    TplC += 'margin:5px;width:146px;height:110px;margin:20px;" ';
    TplC += ' src="' + urlplug + 'img/rectangle-loader.gif" /><br>';

    var ht = TplA + TplB + TplA + TplC;
    ht = ht + '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';
	return ht;

}

function loadLoginBox(){
	
	if(!document.getElementById("overlay-layer")){
		var hcode = '<div id="overlay-layer" onClick="closeLoginBox()" class="overlay-layer" ></div>';
		$('body').append(hcode);
	}
	
	if(!document.getElementById("quick-login")){
		var hcode = '<div id="deco-login" class="deco-login" ></div>';
		hcode += '<div id="quick-login" class="quick-login"></div>';
		$('body').append(hcode);
	}
	
	if(document.getElementById("no-login")
	&&!document.getElementById("lgblckinfo")){
		
		var idlogin = "login_block";
		//Hack v1.11.8
		if(document.getElementById("login-block")){
			idlogin = "login-block";
		}
		//
		var hcode = '';
		hcode += '<div id="lgblckinfo" class="loginblockinfo" >';
		hcode += '<h2 class="infotitle" >Login</h2><br/>';
		hcode += document.getElementById(idlogin).innerHTML;
		hcode +=  '</div>';
		hcode += '<a href="#0" ';
		hcode += 'onClick="closeLoginBox();" ';
		hcode += 'class="cd-close-center" ></a>';
		
		haveLoged = hcode;

		$("#quick-login").html(haveLoged);
		$(".btn-primary").after('<br>');

	}else{
		
		if(haveLoged==''){
			var urlOrigin = window.top.location.origin;
			if(urlOrigin.indexOf('http://localhost')!=-1){
				urlOrigin = location.protocol + "//" + document.domain + "/" + location.pathname.split('/')[1] + "/";
			}
			window.top.location.href = urlOrigin + "/main/social/home.php"; 
		}else{
			$("#quick-login").html(haveLoged);
			$(".btn-primary").after('<br>');
		}
		
	}

	if(document.getElementById("no-login")){

		$("#windows-badges").css("display","none");
		$('.overlay-layer').css('display','block');
		$('.quick-login').css('display','block');
		$('.deco-login').css('display','block');
		$('.thecardview').css("display","none");
		$(".thecard").css("display","none");
		
		if(document.getElementById("side-menu-boost")){

			var bpw = $('body').width();
			if(bpw>1142){
				$('.quick-login').css('margin-left','-60px');
			}else{
				$('.quick-login').css('margin-left','-200px');
			}

		}

	}

}

function recupLoginName(){

	if(!document.getElementById("no-login")){

		var nameTxt = $("a p.name").text();
		if(nameTxt!=''){
			$(".boost-name-user").html('&nbsp;' + nameTxt);
		}
		var imageSrc = $("li div.text-center a img.img-circle").attr("src");
		if(imageSrc!=''){
			$(".boost-circle-user-login").attr("src",imageSrc);
		}
		var messCount = $("#count_message").text();
		if(messCount!=''){
			$(".count_message_boost").html(messCount);
			$(".contain_message_boost").css('opacity','1');
		}else{
			$(".contain_message_boost").css('opacity','0');

			setTimeout(function(){
				recupLoginName();
			},1000);

		}

	}else{
		$(".boost-message").css('display','none');
		$(".boost-logout").css('display','none');
		var nameTxt = $("#form-login_submitAuth").text();
		if(nameTxt!=''){
			
			$(".boost-name-user").html('&nbsp;' + nameTxt);
			$(".boost-name-user").css('cursor','pointer');
			$(".boost-name-user").click(function(){
				loadLoginBox();
			});
			
		}
	}

}
function getTermLangBoost(term){
    
    var lg = boostGetParamLang();

    if(lg=='fr'&&term=='Skills'){
        term = 'Compétences';
    }
    if(lg=='es'&&term=='Skills'){
        term = 'Competencias';
    }
    if(lg=='de'&&term=='Skills'){
        term = 'Competenties';
    }
    
    if(lg=='fr'&&term=='Access'){
        term = 'Accès';
    }
    if(lg=='es'&&term=='Access'){
        term = 'Acceso';
    }
    if(lg=='it'&&term=='Access'){
        term = 'Accesso';
    }
    if(lg=='de'&&term=='Access'){
        term = 'Zugang';
    }
    

    if(lg=='en'&&term=='Voir'){
        term = 'See';
    }
    if(lg=='es'&&term=='Access'){
        term = 'Ver';
    }
    
    if(lg=='en'&&term=='Aperçu'){
        term = 'Overview';
    }
    if(lg=='es'&&term=='Aperçu'){
        term = 'Estudio';
    }
    if(lg=='de'&&term=='Aperçu'){
        term = 'Übersicht';
    }    
    if(lg=='it'&&term=='Access'){
        term = 'Panoramica';
    }


    if(lg=='en'&&term=='Voir vidéo'){
        term = 'See video';
    }
    if(lg=='es'&&term=='Voir vidéo'){
        term = 'Ver video';
    }

    return term;

}

function processAllTradTerm(){
    
    $('.autoTradTerm').each(function(index) {
        var valHtml =  $(this).html();
        $(this).html(getTermLangBoost(valHtml));
    });
    
}

function boostGetParamLang(){
    var langGet = $('html')[0].lang;
    if(langGet==''){
        langGet = 'en'
    }
    return langGet;
}

var nbFullCourses = 0;
var nbFullExercises = 0;

function renderCards(code,dir,typ,data){

	nbFullCourses = 0;
	nbFullExercises = 0;

	var h = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';

	if(typ=='courselarge'){
		h += '<div class="thecardviewzoneLarge" >';
	}else{
		h += '<div class="thecardviewzone" >';
	}
    
	h += '<div class=ContainerProgress >';
	
	var noData = false;

	if(typeof data==='undefined') {
		noData = true;
	}else{
		if(typeof data.progress==='undefined') {
			noData = true;
		}
	}
	
	var countM = 0;
    
	if(noData==false){

		$.each(data.progress,function(){
			
			var title = this.title;
			title = title.replace(/\//g,'');
			title = title.replace(/\\/g, '');
			title = title.replace(/\'/g,"&apos;");
			
			if(this.code=='0'){
				h += renderOneLineCat(title);
			}else{
				if(this.code=='-2'){
					nbFullExercises++;
					h += renderOneLineLp(title,parseInt(this.score),"LegendIconQuizzP",dir);
				}else{
					nbFullCourses++;
					h += renderOneLineLp(title,parseInt(this.score),"LegendIconLearnP",dir);
				}
					
			}
			countM ++;

		});

	}else{
	
		h += renderOneLineLp("...",0,"LegendIconLearnP");
		h += renderOneLineLp("..",0,"LegendIconLearnP");
		h += renderOneLineLp(".",0,"LegendIconLearnP");
		
	}
	
	totalPourG = 0;
	 
	var maxPour = 0;
	var scorePour = 0;
	
	$.each(data.progress,function(){
		if(this.code!='0'){
			maxPour = maxPour + 100;
			scorePour = scorePour + parseInt(this.score);
		}
	});
	
	if(scorePour>0&&maxPour>0){
		totalPourG = (scorePour/maxPour) * 100
	}

	h += '<br/><br/>';
	
	h += '</div>';
	h += '</div>';

	if(typ=='courselarge'){
		h += '<div class="thecardviewzone2Large" >';
	}else{
		h += '<div class="thecardviewzone2" >';
	}
	
	if(countM>0){
		h += '<div class="icon-near-donut" ></div>';
		h += '<div class="easy-donut-near" >';
	}else{
		h += '<div class="easy-donut" >';
	}
	
	h += '<div id="easypiechart-blue" class="easypiechart" data-percent="' + totalPourG + '">';
	h += '<span class="easypiechartpercent">' + Math.floor(totalPourG) + '</span>';
	h += '</div>';
	

	if(typ=='course'){
		
		h += '<div class="easypiechart-link" style="text-align:center;" >';
		h += '<a style="display:none;" class="btn btn-default" >';
		h += getTermLangBoost('Parcours');
		h += '</a>';
		h += '</div>';
	
		h += loadMiniToolsRight()
	}

	h += '</div>';
	
	h += '</div>';

	if(typ=='session'){
		h += '<a href="main/session/index.php?session_id=' + dir + '" class="btnBlue" >Accès</a>';
	}else if(typ=='course'){
		h += '<a href="courses/' + dir + '/index.php?id_session=' + prepareIdSession + '" class="btnBlue" >' + getTermLangBoost('Access') + '</a>';
	}

	return h;

}

function renderOneLineCat(title){

	var h = '<div class="LegendItem">';
	h += '<div class="LegendItem_Text LegendItem_Title">' + title + '</div>';
	h += '</div>';

	return h;

}

function renderOneLineLp(title,score,typeIcon,dir){

	var lik = ' href="javascript:void(0);" ';
	if(typeIcon=='LegendIconLearnP'){
		lik = ' href="' + _p['web_main'] + 'lp/lp_controller.php?cidReq=' + dir + '&id_session=' + prepareIdSession + '&gidReq=0&gradebook=0&origin=" ';
	}
	
	var h = '<div class="LegendItem ' + typeIcon + '" >';
	h += '<div class="LegendItem_Eye"></div>';
	h += '<div class="LegendItem_Text"><a ' + lik + '>' + title + '</a></div>';

	var decoProgress = "";
	if(score>0){decoProgress = "LegendProgress10";}
	if(score>19){decoProgress = "LegendProgress25";}
	if(score>34){decoProgress = "LegendProgress37";}
	if(score>45){decoProgress = "LegendProgress50";}
	if(score>60){decoProgress = "LegendProgress62";}
	if(score>70){decoProgress = "LegendProgress75";}
	if(score>80){decoProgress = "LegendProgress90";}
	if(score>99){decoProgress = "LegendProgress100";}

	if(typeIcon!="LegendIconQuizzP"){
		h += '<a title="' + score+ '%" class="LegendItem_progress ' + decoProgress + '" ></a>';
	}
	
	h += '</div>';

	return h;

}

function renderOneLineNone(title){

	var h = '<div class="LegendItem" style="opacity:0.6;" >';
	h += '<div class="LegendItem_Eye"></div>';
	h += '<div class="LegendItem_Text">' + title + '</div>';
	h += '</div>';

	return h;

}

function renderCardsLoading(obj){
	
	var urlplug = $('#plugfullpath').html();
	var type = obj.attr("type");
	if(type=='courselarge'){
		return renderLargeLoading(obj);
	}
	
	var h = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';

    h += '<div class="thecardviewzone" >';
	h += '<div class=ContainerProgress >';

	h += renderOneLineNone("....");
	h += renderOneLineNone("...");
	h += renderOneLineNone("..");
	h += renderOneLineNone(".");
	
	h += '</div>';
	h += '</div>';


	var loadImgTplA = '<img style="position:absolute;top:30px;left:50%;';
	loadImgTplA += 'margin-left:-32px;" ';
	loadImgTplA += ' src="' + urlplug + 'img/content-loader.gif" />';

	h += '<div class="thecardviewzone2" >';

	h += loadImgTplA;

	h += '</div>';
	
	h += '</div>';
	
	return h;

}

function renderLargeLoading(obj){

	var type = obj.attr("type");

	var urlplug = $('#plugfullpath').html();

	var h = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';

	if(type=='courselarge'){

		h += '<div style="text-align:center;" class="thecardviewzone2Large" >';
		h += '<img style="margin-top:15px;" src="' + urlplug + 'img/content-loader.gif" />';
		h += '</div>';

	}else{

		h += '<div style="position:absolute;left:5px;top:5px;right:5px;bottom:5px;" >';
		h += '<img style="position:absolute;top:50%;';
		h += 'left:50%;margin-top:-32px;margin-left:-32px;" ';
		h += ' src="' + urlplug + 'img/content-loader.gif" />';
		h += '</div>';

	}
	var pw = $('.thecardview').outerWidth();
	if(pw>774){
		h += baseRightContent();
	}
	
	return h;

}

function renderCards_old(code,dir,typ,data){

	var h = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';
	h += '<div class="thecardviewzone" >';
	h += '<ol class="rounded-list">';
	
	var noData = false;

	if(typeof data === 'undefined') {
		noData = true;
	}else{
		if(typeof data.progress === 'undefined') {
			noData = true;
		}
	}
	
	var countM = 0;

	if(noData==false){

		$.each(data.progress,function(){
			
			var title = this.title;
			title = title.replace(/\//g,'');
			title = title.replace(/\\/g, '');
			title = title.replace(/\'/g,"&apos;");

			if(countM==0||countM==1||countM==2||countM==3){
				h += '<li class="olpage1" ><a href="#" >' + title + '</a></li>';
			}else{
				if(countM==4){
					h += '<li onClick="olNavPage(2)" class="olpage1 bottomarrow" >&nbsp;</li>';
				}
				if(countM==4||countM==5||countM==6){
					h += '<li class="olpage2" style="display:none;" ><a href="#" >' + title + '</a></li>';
				}
				if(countM==7){
					h += '<li onClick="olNavPage(3)" style="display:none;" class="olpage2 bottomarrow" >&nbsp;</li>';
				}
				if(countM==7||countM==8||countM==9){
					h += '<li class="olpage3" style="display:none;" ><a href="#" >' + title + '</a></li>';
				}

			}
			countM ++;
		});

	}else{
		h += '<li><a href="#" >Module 1</a></li>';
		h += '<li><a href="#" >Module 2</a></li>';
		h += '<li><a href="#" >...</a></li>';
	}
	
	totalPourG = 0;
	 
	var maxPour = 0;
	var scorePour = 0;
	
	$.each(data.progress,function(){
		maxPour = maxPour + 100;
		scorePour = scorePour + parseInt(this.score);
	});
	
	if(scorePour>0&&maxPour>0){
		totalPourG = (scorePour/maxPour) * 100
	}
	
	h += '</ol>';

	var urlplug = $('#plugfullpath').html();

	h += '</div>';

	h += '<div class="thecardviewzone2" >';

	h += '<div class="easy-donut">';
	h += '<div id="easypiechart-blue" class="easypiechart" data-percent="' + totalPourG + '">';
	h += '<span class="percent">' + Math.floor(totalPourG) + '</span>';
	h += '</div>';
	h += '<div class="easypiechart-link" style="text-align:center;" >';
	h += '<a class="btn btn-default" >';
	h += 'Parcours';
	h += '</a>';
	h += '</div>';
	h += '</div>';
	
	h += '</div>';
	
	if(typ=='session'){
		h += '<a href="main/session/index.php?session_id=' + dir + '" class="btnBlue" >Accès</a>';
	}else if(typ=='course'){
		h += '<a href="courses/' + dir + '/index.php?id_session=' + prepareIdSession + '" class="btnBlue" >Accès</a>';
	}

	return h;

}
var diVw = 0;
var diVh = 0;

var resultJson;

var folderTpl = "templates/localhost/";
var loadImgTpl = '<img style="position:absolute;top:50%;left:50%;margin-left:-32px;margin-top:-32px;" ';
loadImgTpl += ' src="{urlplug}img/content-loader.gif" />';

var loadTpl = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>'+ loadImgTpl;

var prepareCode;
var prepareDir;
var prepareTyp;
var prepareIdSession;
var totalPourG = 0;
var indexPage = 1;
var oldPage = 1;
var nbLargeCard = 2;
var panelYdec = 0;

var isLoged = true;
var versionCham = 1116;
var haveLoged = '';

$(document).ready(function($){
	
	initBoostPage();
	
});

function initBoostPage(){

	if(document.getElementById("folder-tpl")){
		var Tpl = $("#folder-tpl").text();
		folderTpl = "templates/" + Tpl + "/";
	}else{
		return false;
	}
	
	if(document.getElementById("login_block")
	||document.getElementById("login-block")){
		isLoged = false;
		setTimeout(function(){ 
			$('#login').blur();
		 },300);
	}

	var urlplug = $('#plugfullpath').html();
	$('#header-section').css("display","none");
	
	recupLoginName();

	dataDashBoard(urlplug);

}

function dataDashBoard(urlplug){

	if(isLoged==false){
		var addU = '<ul class="nav navbar-nav navbar-right">';
		addU += '<li class="dropdown avatar-user" >';
		addU += '<a href="#" onClick="loadLoginBox();" ';
		addU += ' style="margin-top:6px;border:dotted 0px gray;border-radius:5px;" ';
		addU += ' class="dropdown-toggle" data-toggle="dropdown" role="button" >';
		addU += '&nbsp;<img src="' + urlplug + '/img/user.png" >&nbsp;';
		addU += '<span class="username-movil">Connexion</span>';
		addU += '</a></li></ul>';
		$("#navbar").append(addU);
	}

	var liLogo = '';
	if(_p['boost_logo']!=''&&typeof _p['boost_logo'] != 'undefined'){
		liLogo = '<li class="boostsmart" style="padding:5px;" >';
		liLogo += '<a href="' + _p['web'] + 'index.php" style="padding:5px;" >';
		liLogo += '<img src="' + _p['boost_logo'] + '" /></a></li>';
		$("#navbar").find("ul.navbar-nav").first().prepend(liLogo);
	}
	
	$(".navbar").css("border-radius","0px");

	if(haveSurCouche()){

		$('#home-welcome').css("display","none");
		$('#notice_block').css("display","none");
		$('#help_block').css("display","none");

		$('.sidebar').css("display","none");
		
		$('.sidebar').parent().css("display","none");

		$('body').append('<div class="rapidContent container-fluid" ></div>');
		
		var ht = contentHboost;
		
		if(!document.getElementById("no-login")){
			getContentUser();
		}else{
			ht = ht.replace('<div>###CODE###</div>',ht);
			ht = ht.replace('<div>###CATALOG###</div>',getCatalogLive());
			
			ht = ht.replace('###CODE###','');
			ht = ht.replace('###CATALOG###','');

			var re = new RegExp('{urlplug}', 'g');
			ht = ht.replace(re, urlplug + folderTpl + 'img/');
			$('.rapidContent').html(ht);
			installOverviewEvents();
			reOrdonn();
		}
		setTimeout(function(){reOrdonn();},200);
	}else{
		arrangeMenu();
	}
	
	setTimeout(function(){
		installLinks();	
		detectPreloadCards();
	},200);
	
	preLoadChartsLittle();

	NoTopFunctions();

}

function NoTopFunctions(){

	$('#header-logo').parent().css("display","none");
	$("#cm-header .container .row").css("display","none");
	$('#toolbar-admin').css("display","none");
	$(".hot-courses").css("display","none");
	$(".items-hotcourse").css("display","none");
	$("#carousel-announcement").css("display","none");

}

$(window).resize(function(){
	if(haveSurCouche()){
		reOrdonn();
	}else{
		arrangeMenu();
	}
});

function haveSurCouche(){

	var r = false;
	
	var u = window.top.location.href;

	if(document.getElementById("login_block")
		||document.getElementById("login-block")){
		r = true;
		isLoged = false;
	}
	if(u.indexOf('.php')==-1
	&&u.indexOf('main/admin')==-1
	&&u.indexOf('main/')==-1
	&&u.indexOf('plugin/')==-1){
		r = true;
	}
	
	if(u.indexOf('index.php')!=-1
	&&u.indexOf('main/admin')==-1
	&&u.indexOf('logout')==-1
	&&u.indexOf('dashboard')==-1
	&&u.indexOf('courses/')==-1
	&&u.indexOf('/plugin/')==-1
	&&u.indexOf('main/')==-1
	){
		r = true;
	}
	if(u.indexOf('courses/')!=-1){
		r = false;
	}
	if(u.indexOf("configure_homepage.php")!=-1){
		r = false;
	}
	if(u.indexOf("?include")!=-1){
		r = false;
	}
	if(u.indexOf("?boostoff")!=-1){
		r = false;
	}
	if(u.indexOf('/badge/')!=-1){
		r = false;
	}
	
	if(document.getElementById("no-login")){

		var ur=window.location.href;
		if(ur.indexOf("loginFailed")!=-1
		||ur.indexOf("?language=")!=-1
		||ur.indexOf("#login")!=-1){
			loadLoginBox();
			isLoged = false;
		}
	}
	return r;

}

function haveAdmin(){

	var r = false;
	var u = window.top.location.href;

	if(u.indexOf('admin')!=-1){
		r = true;
	}

	return r;

}

function arrangeMenu(){

	if(document.getElementById("side-menu-boost")){

		var bodypw = $('body').outerWidth();

		var createDecM = false;
		if(bodypw>1142){createDecM = true;}

		if(createDecM){
			
			$('.rapidContent').css("left","260px");
			$('#main').css("margin-left","260px");
			$('#main').css("width",(bodypw - 260) + "px");
			$('.footer').css("width",(bodypw - 260) + "px");
			$('#side-menu-boost').css("left","0px");
			$('#side-menu-boost').css("display","block");
			$('.navbar-toggle-boost').css("display","none");

		}else{

			$('.rapidContent').css("left","0px");
			$('#main').css("margin-left","0px");
			$('#main').css("width","auto");
			$('.footer').css("width","auto");
			
			$('#side-menu-boost').css("left","-260px");
			$('#side-menu-boost').css("display","none");
			$('.navbar-toggle-boost').css("left","0px");
			$('.navbar-toggle-boost').css("display","block");

			if($('.navbar-toggle-boost').length==0){
				$('body').append('<a onClick="showLateralMenu();" href="javascript:return void(0);" class="navbar-toggle-boost" ></a>');
			}

		}
	
	}

}

function showLateralMenu(){

	if($('#side-menu-boost:visible').length == 0){
		$('#side-menu-boost').css("display","block");
		$('.nav-side-menu-boost .menu-list .menu-content').css("display","block");
		
		$( "#side-menu-boost" ).animate({left: "0px"},500,function(){
			$('#side-menu-boost').css("left","0px");
		});
		$( ".navbar-toggle-boost" ).animate({left: "260px"},500,function(){
			$('.navbar-toggle-boost').css("left","260px");
		});
	}else{
		arrangeMenu()
	}

}

var largCards = 200;//px

function reOrdonn(){

	arrangeMenu();
	recupLoginName();
	
	var pw = $('.rapidContent').outerWidth();
	var ph = $('.rapidContent').outerHeight();

	if(pw<550){
		largCards = (pw-30)/2;
	}else{
		largCards = 200;
	}
	
	//680

	$('.thecard').css("width",largCards + "px");

	$('html').css("overflow","hidden");

	var nbw = parseInt((pw-largCards)/largCards);
	
	if(nbw>5){nbw=5;}
	if(nbw==0){nbw=1;}

	fullLargeColumn = 2;

	if(nbw>2){
		fullLargeColumn = 3;
	}
	if(nbw>3){
		fullLargeColumn = 4;
	}
	if(nbw>4){
		fullLargeColumn = 5;
	}

	var nbc = $('.thecard').length;

	var totalrest = parseInt(pw-((largCards + 20)*nbw));

	var plx = totalrest/2;
	var lx = plx;
	var ly = 120;
	var decx = 20;
	var decy = 20;

	var nbColumn = 1;

	if(nbc>nbw){
		nbColumn = 2;
	}
	if(nbc>(nbw*2)){
		nbColumn = 3;
	}
	if(nbColumn==0){
		nbColumn=1;
	}

	//Center height
	if(pw>900&&ph>600){
		if(nbColumn==2){
			var hauT = (nbColumn * largCards) + decy;
			ly = (ph-(hauT))/2;
			ly = ly-20;
			if(ly<120){
				ly = 120;
			}
		}
	}
	
	if(pw<550&&nbc>1&&nbw==1){
		plx = 5;
		lx = plx;
		nbw = 2;
		fullLargeColumn = 2;
		decx = 4;
		decy = 4;
	}
	if(pw<550){
		ly = 75;
		plx = 10;
		lx = plx;
	}else{
		if(ph<700){
			ly = 100;
		}
	}

	oriX = lx;
	oriY = ly;

	var iLargX = 0;

	$(".thecard").each(function(index){

		showCard($(this),lx,ly);
		
		iLargX++;
		lx = lx + largCards + decx;
		if(iLargX==nbw){
			iLargX = 0;
			lx = plx;
			ly = ly + 187 + decy;
		}
	});

	$(".h2-caption").each(function(index){

		var hcapt = $(this).height();
		if(hcapt>54){
			$(this).css("font-size","17px");
		}
		hcapt = $(this).height();
		if(hcapt>54){
			$(this).css("font-size","15px");
		}
		hcapt = $(this).height();
		if(hcapt>54){
			$(this).css("font-size","13px");
		}
	});
	
	$('.LPShortcut').css("display",'none');
	$('.thecardview').css("display","none");

	$('.thecardview').removeClass("thecardviewLT");

	$('.boost-btn-next,.boost-btn-prev').css("display","none");

}

var fullLargeColumn = 2;

var oriX = 0;
var oriY = 0;

var placeX = 0;
var placeY = 0;

function installOverviewEvents(){

	$( ".thecard" ).click(

		function(){
			var obj = $(this);
			obj.removeClass("thecardTransi");
			var type = obj.attr("type");
			if(type=='course'&&$("#stylecourses").html()==1){
				obj.attr('type','courselarge');
			}
			viewEvents(obj);
		}

	);

}

function viewEvents(obj){

	$('.thecardview').html(renderLargeLoading(obj));

	$(".thecard").css("display","none");			
	obj.css("display","block");
	obj.css("transform","scale(1)");
	$('.LPShortcut').css("display",'none');
	$('.LPShortcut').css("left",oriX+'px').css("top",oriY+'px');

	var time = 400;
	var objPosition = obj.position();
	
	if((objPosition.left==oriX&&objPosition.top==oriY)||isVeryLargeContent(obj)){
		
		time = 500;
		var objOriX = oriX;
		var objOriY = oriY;

		if(isVeryLargeContent(obj)){
			objOriX = 20;
			objOriY = 50;
		}
		
		placeX = objOriX;
		placeY = objOriY;

		obj.animate({
			left : objOriX + "px", top : objOriY + 40 + "px"
		},time,function(){
			
			obj.animate({
				left : objOriX + "px", top : objOriY + "px"
			},150,function(){
				
				installInnerOverview(obj);

				setTimeout(() => {
					preloadInnerOverview(obj);
				},400);
				
			});
		});

	}else{

		placeX = oriX;
		placeY = oriY;

		preloadInnerOverview(obj);	
		obj.animate({
			left : oriX + "px", top : oriY + "px"
		},time,function(){
			installInnerOverview(obj);
		});

	}

}

function isVeryLargeContent(obj){
	
	var rWin = false;
	var type = obj.attr("type");
	var pw = $('.rapidContent').outerWidth();
	var ph = $('.rapidContent').outerHeight();

	if(type=="video"&&pw>860&&ph>600){
		rWin = true;
	}
	
	if(type=="overviewcourse"
	||type=="courselarge"
	||type=="statstable"
	||type.indexOf("loadpagecontent@")!=-1){
		if(pw>860&&ph>600){
			rWin = true;
		}
	}

	return rWin;
}

//Size of cardview
function installInnerOverview(obj){

	var type = obj.attr("type");
	
	$('.boost-search-result').css("display","none");
	$('.thecardview').css("margin","0px");
	$('.rapidContent').scrollTop(0);

	if(type!='link'){
		$('.thecardview').css("display","block").css("position","absolute");
	}
	
	var fx = oriX;
	var fy = oriY + 210;
	var fw = 640;
	var fh = 300;
	
	var pw = $('.rapidContent').outerWidth();
	var ph = $('.rapidContent').outerHeight();

	if(pw<640){
		fw = 350;
		fx = ((pw-fw)/2) - 5;
		if(fx<5){
			fx = 5;
		}
		fh = 820;
		$('.thecardview').css("margin-bottom","40px");
	}else{
		$('.thecardview').css("margin-bottom","0px");
		if(type=='courselarge'){
			if(pw<860){
				fh = 820;
			}
			if(pw<800){
				fx = ((pw-fw)/2) - 5;
			}
		}
	}
	
	if(pw<370){
		fw = (pw-2);
		fx = 0;
		fh = 620;
	}
	
	if(isVeryLargeContent(obj)){

		var objPosition = obj.position();
		fh = ph - 70;
		fw = pw - (objPosition.left + 140);
		
		if(pw>1300){
			fw = pw - (objPosition.left + 210);
		}

		if(type=='courselarge'){
			fw = pw - 45;
			obj.css("z-index","2700");
			obj.animate({
				left : (placeX + 30) + "px",
				width : "260px"
			},200);
		}

		if(type!='courselarge'){
			obj.addClass("thecardTransi");
			obj.css("transform","scale(0.5)");
		}

		$('.thecardview').removeClass("thecardviewLT");
		$('.thecardview').removeClass("thecardviewRB");
		$('.thecardview').addClass("thecardviewTopVideo");
		
		if(type=='courselarge'){
			$('.thecardview').css("left",parseInt(20) + "px").css("top",20 + "px");

			$('.thecardview').removeClass("thecardviewTopVideo");
		}else{
			$('.thecardview').css("left",parseInt(145) + "px").css("top",20 + "px");
		}
		$('.thecardview').css("width",(fw/3) + "px").css("height",fh + "px");

		$('.thecardview').css("z-index",2600);
		$('.thecardview').animate({
			width : fw + "px"
		},400,function(){
			
		});

	}else{

		$('.thecardview').removeClass("thecardviewRB");
		$('.thecardview').removeClass("thecardviewTopVideo");
		$('.thecardview').addClass("thecardviewLT");

		if(type=='courselarge'){
			obj.css("z-index","2700");
			
			obj.animate({
				left : ((pw - 250)/2) + "px",width : "240px"
			},200);

			if(pw<550){
				fy = 66;
			}else{
				if(pw<860){
					fy = 100;
				}
			}
			$('.thecardview').removeClass("thecardviewLT");
		}

		if(type=='overviewcourse'){
			fh = (ph - fy) - 20;
			var pw = $('.rapidContent').outerWidth();
			if(pw<790&&pw>640){
				fx = (pw-fw)/2;
			}
		}

		$('.thecardview').css("left",fx + "px").css("top",fy + "px");
		$('.thecardview').css("width",fw + "px").css("height",fh  + "px");
		$('.thecardview').css("z-index",2);
	}

}

function preloadInnerOverview(obj){

	var title = obj.find("h1").html();
	var type = obj.attr("type");
	var data = obj.attr("data");
	prepareIdSession = obj.attr("sessionid");

	var validType = false;

	var coderef = obj.attr("coderef");
	var idref = obj.attr("idref");

	if(type=='overviewcourse'){
		if(isVeryLargeContent(obj)){
			loadNearCatalog(idref,1);
		}else{
			loadNearCatalog(idref,2);
		}
	}

	if(type=="link"){
		$('.thecardview').css("display","none");
		validType = true;
	}

	if(type=="stats"){
		$('.thecardview').html(loadStatsLittle(title));
		loadChartsLittle();
		validType = true;
	}

	if(type=="statstable"){
		$('.thecardview').html(fakeLoadStatsTable());
		installStatsTable(coderef,idref,type);
		validType = true;
	}
	
	if(type=="video"){
		$('.thecardview').html(loadVideo(data,false));
		validType = true;
	}

	if(type=="contentextra"){
		var urlpath2 = folderTpl + "contents-extra/" + data;
		$('.thecardview').html(loadContent(urlpath2));
		validType = true;
	}
	
	if(type=="content"){
		$('.thecardview').html(loadContent(data));
		validType = true;
	}
	
	if(type.indexOf("loadpagecontent@")!=-1){
		$('.thecardview').html(loadContentFromPage(type,data));
		validType = true;
	}

	if(validType==false){
		$('.thecardview').html(loadCards(coderef,idref,type,obj,prepareIdSession));
	}

}

function showCard(card,lx,ly){
	
	$('.LPShortcut').attr("orileft",lx).attr("oritop",ly);

	card.attr("orileft",lx);
	card.attr("oritop",ly);

	card.css("left",lx + 'px').css("top",ly + 'px');
	card.css("opacity","1").css("width",largCards + "px");
	card.css("margin-right","0px").css("display","block");
	card.css("transform","scale(1)");
	card.css("transform-origin","left top");

	
}

function hideCard(card){
	
	card.css("opacity","0.1").css("width","0px");
	card.css("margin-right","200px").css("display","none");

}

function installLinks(){

	if(document.getElementById("no-login")){
		$(".alterhome").click(function(){loadLoginBox()});
		$(".altercourses").click(function(){loadLoginBox()});
		$(".altersuivi").click(function(){loadLoginBox()});
	}

	var urlTick = _p["web"] + "main/ticket/new_ticket.php?project_id=1";
	$('.nav-link-help').attr("href",urlTick);
	if(!document.getElementById("no-login")){
		$('.nav-link-help').css("display","block");
	}

	var statlog = $("#status-login").html();

	if(statlog=='ADMIN'){
		$('.rapidContent').append('<a href="plugin/chamilo_boost/edit-title.php" class="btnEdit" ></a>');
	}
	$('.rapidContent').append(loadNearTools());
	
}

var xhrLoadBoostData = new Array();

function loadCards(code,idref,typ,obj,sessionid){

	var urlplug = $('#plugfullpath').html();

	if(typ=='overviewcourse'){
		
		prepareCode = code;
		prepareDir = idref;
		prepareTyp = typ;
		prepareIdSession = sessionid;

		loadOverviewCatalog(idref);
		
		var h = renderOverviewLoading();

		return h;

	}
	
	if(!document.getElementById("no-login")){

		if(typ=='courselarge'){
			installCourseLarge(code,idref,typ);
		}

		if(typ=='course'){
		
			prepareCode = code;
			prepareDir = idref;
			prepareTyp = typ;
			prepareIdSession = sessionid;

			var idCache = 'course' + prepareDir;

			if(xhrLoadBoostData[idCache]!== undefined){

				setTimeout(function(){
					var result = xhrLoadBoostData[idCache];
					$('.thecardview').html(renderCards(prepareCode,prepareDir,prepareTyp,result));
					showCircliful();
					loadExtrasCards(code,idref,typ);
				},300);

			}else{

				var urlpath = urlplug + "ajax/getlpdata.php?d=" + prepareDir;
				
				$.getJSON(urlpath).done(function(result){

					xhrLoadBoostData[idCache] = result;
					$('.thecardview').html(renderCards(prepareCode,prepareDir,prepareTyp,result));
					showCircliful();
					loadExtrasCards(code,idref,typ);
				});

			}

		}

		if(typ=='session'){

			prepareCode = code;
			prepareDir = idref;
			prepareTyp = typ;
			prepareIdSession = sessionid;

			var idCache = 'session' + prepareDir;

			if(xhrLoadBoostData[idCache]!== undefined){

				setTimeout(function(){
					var result = xhrLoadBoostData[idCache];
					$('.thecardview').html(renderSessions(prepareCode,prepareDir,prepareTyp,result));
					showCircliful();
				},300);

			}else{

				var urlpath = urlplug + "ajax/getsessiondata.php?s=" + prepareDir;

				$.getJSON(urlpath).done(function(result){
					xhrLoadBoostData[idCache] = result;
					$('.thecardview').html(renderSessions(prepareCode,prepareDir,prepareTyp,result));
					showCircliful();
				});

			}

		}
		
		var h = loadTpl.replace('{urlplug}',urlplug);
		h = renderCardsLoading(obj);
		return h;

	}
	
	return renderCards(code,dir,typ);

}

function getActiveCard(){

	var objCard = new Object();

	$(".thecard").each(function(index){
		var obj = $(this);
		var coderef = obj.attr("coderef");
		var idref = obj.attr("idref");
		if(prepareDir==idref||prepareDir==coderef){
			objCard = obj
		}
	});

	return objCard;
}

function olNavPage(idPage){
	$(".olpage1,.olpage2,.olpage3").css("display","none");
	$(".olpage" + idPage ).css("display","block");
}

function renderSessions(code,dir,typ,data){

	var h = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';
	h += '<div class="thecardviewzone" >';
	h += '<ol class="session-list">';

	var noData = false;

	if(typeof data === 'undefined') {
		noData = true;
	}else{
		if(typeof data.courses === 'undefined') {
			noData = true;
		}
	}

	var countM = 0;
	
	if(noData==false){
		$.each(data.courses,function(){
			h += '<li><a href="#" >' + this.title + '</a></li>';
			countM ++;
		});
	}else{
		h += '<li><a href="#" >Parcours 1</a></li>';
		h += '<li><a href="#" >Parcours 2</a></li>';
		h += '<li><a href="#" >...</a></li>';
	}

	h += '</ol>';
	h += '</div>';
	h += '<div class="thecardviewzone2" >';
	h += '</div>';

	if(typ=='session'){
		h += '<a href="main/session/index.php?session_id=' + dir + '" class="btnBlue" >Accès</a>';
	}else if(typ=='course'){
		h += '<a href="courses/' + dir + '/index.php?id_session=' + prepareIdSession + '" class="btnBlue" >Accès</a>';
	}
	return h;
}

function closeLoginBox(){

	$('.overlay-layer').css('display','none');
	$('.quick-login').css('display','none');
	$('.deco-login').css('display','none');
	
	$('.thecardview').css("display","none");
	$('.LPShortcut').css("display",'none');

	$('.theCardViewVideo').html("");

	var objCd = getActiveCard();
	objCd.find('.card-outmore').css('display','inline-table');

	$( ".thecard" ).each(function() {
		
		$(this).css("transform","scale(1)");
		var lx = $(this).attr("orileft");
		var ly = $(this).attr("oritop");
		if (typeof lx !== typeof undefined && lx !== false) {
			$(this).animate({
				left : lx + "px",
				top : ly + "px"
			},200,function(){
				$(this).css("display","block");
				$(this).css("width",largCards + "px");
				$(".thecard").css("display","block");
			});

		}

	});


}

function showExtrasTools(){
	$('.menu-options-big').toggle();
}

function showCircliful(){
	
	if(totalPourG<0||totalPourG>100){
		totalPourG = 0;
	}

	if(jQuery()){
		if(jQuery().easyPieChart){
			$('#easypiechart-blue').easyPieChart({
				scaleColor: false,
				barColor: '#1ebfae',
				lineWidth:12,
				size: 90,
				trackColor: '#f2f2f2'
			});
		}else{
			var getExtras = _p['web'] + 'web/assets/jquery.easy-pie-chart/dist/jquery.easypiechart.js';
			console.log(getExtras);
			$.getScript(getExtras, function(){
				console.log("easypiechart is include");
			});
			setTimeout(function(){
				showCircliful();
			},300);
		}
	}
	
}		

function getContentUser(){
	
	var urlplug = $('#plugfullpath').html();
	
	var rh = '';
	
	resultJson = userHboost;
	if(resultJson.modules.length>0){
		
		var i = 0;
		var err = 0;
		
		$.each(resultJson.modules,function(){

			var urlplugimg = urlplug.replace('plugin/chamilo_boost/resources/','');
			urlplugimg = urlplugimg + 'app/' + this.img;

			if(this.img=='session.jpg'){
				urlplugimg = urlplug.replace('plugin/chamilo_boost/resources/','');
				urlplugimg = urlplugimg + 'app/upload/sessions/16_' + this.idref + '.png';
			}

			if(this.img.indexOf('animated')!=-1&&this.img.indexOf('.gif')!=-1){
				urlplugimg = _p['web_plugin'] + 'chamilo_boost/resources/'+ this.img;
			}
			
			var ty = this.type;
			rh += '<a href="#" >';
			
			if(this.sessionid=='undefined'||typeof this.sessionid==='undefined'){
				this.sessionid = 0;
			}

			rh += '<div class="thecard" type="' + ty + '" '; 
			rh += ' coderef="' + this.code + '" ';
			rh += ' sessionid="' + this.sessionid + '" ';
			rh += ' idref="' + this.idref + '" >';
			
			rh += '<div class="card-img">';
			rh += '<div id="card' + i + '" class="back-img" ></div>';
			rh += '</div>';
			
			rh += '<div class="card-caption">';
			rh += '<i id="like-btn" class="fa fa-circle" ></i>';
			rh += '<h2 class="h2-caption" >' + this.title + '</h2></div>';
			
			rh += '<div class="card-outmore">';
			rh += '<h5>' + getTermLangBoost('Aperçu') + '</h5>';
			rh += '<i id="outmore-icon" class="fa fa-angle-right"></i>';
			rh += '</div>';
			
			rh += '</div></a>';
			
			var imgBack = new Image();
			imgBack.id = "ard" + i;
			imgBack.onload=function(){
				var pagid = "c" + this.id;
				$('#' + pagid).css("background-image", "url('" + this.src + "')");
			};
			imgBack.onerror=function(){
				var pagid = "c" + this.id;
				if(err==8){err=1};
				$('#' + pagid).css("background-image", "url('plugin/chamilo_boost/resources/css/error" + err + ".jpg')");
				err++;
			};
			imgBack.src = urlplugimg;
			
			i++;
			
		});
		
		var h = contentHboost;
		h = h.replace('<div>###CODE###</div>',rh);
		
		h = h.replace('<div>###CODE###</div>','');
		h = h.replace('<div>###CODE###</div>','');
		h = h.replace('<div>###CATALOG###</div>','');
		h = h.replace('<div>###CATALOG###</div>','');

		var re = new RegExp('{urlplug}', 'g');
		h = h.replace(re, urlplug + folderTpl + 'img/');

		$('.rapidContent').html(h);
		installOverviewEvents();
		reOrdonn();

	}else{

		var h = contentHboost;
		h = h.replace('<div>###CODE###</div>',rh);
		
		h = h.replace('<div>###CODE###</div>','');
		h = h.replace('<div>###CODE###</div>','');
		h = h.replace('<div>###CATALOG###</div>','');
		h = h.replace('<div>###CATALOG###</div>','');

		var re = new RegExp('{urlplug}', 'g');
		h = h.replace(re, urlplug + folderTpl + 'img/');
		
		$('.rapidContent').html(h);
		installOverviewEvents();
		reOrdonn();
	}
	
	processAllTradTerm();

}

function getCatalogLive(){
	
	var urlplug = $('#plugfullpath').html();
	
	var rh = '';

	var resultJsonCat = catalogHboost;

	if(resultJsonCat.modules.length>0){
		
		var i = 0;
		var err = 0;
		
		$.each(resultJsonCat.modules,function(){

			var urlplugimg = urlplug.replace('plugin/chamilo_boost/resources/','');
			urlplugimg = urlplugimg + 'app/' + this.img;

			if(this.img=='session.jpg'){
				urlplugimg = urlplug.replace('plugin/chamilo_boost/resources/','');
				urlplugimg = urlplugimg + 'app/upload/sessions/16_' + this.idref + '.png';
			}

			if(this.img.indexOf('animated')!=-1&&this.img.indexOf('.gif')!=-1){
				urlplugimg = _p['web_plugin'] + 'chamilo_boost/resources/'+ this.img;
			}
			
			var ty = this.type;
			rh += '<a href="#" >';
			
			rh += '<div class="thecard" type="' + ty + '" '; 
			rh += ' coderef="' + this.code + '" ';
			rh += ' idref="' + this.idref + '" >';
			
			rh += '<div class="card-img">';
			rh += '<div id="card' + i + '" class="back-img" ></div>';
			rh += '</div>';
			
			rh += '<div class="card-caption">';
			rh += '<i id="like-btn" class="fa fa-circle" ></i>';
			rh += '<h1>' + this.title + '</h1></div>';
			
			rh += '<div class="card-outmore">';
			rh += '<h5>' + getTermLangBoost('Aperçu') + '</h5>';
			rh += '<i id="outmore-icon" class="fa fa-angle-right"></i>';
			rh += '</div>';
			
			rh += '</div></a>';
			
			var imgBack = new Image();
			imgBack.id = "ard" + i;
			imgBack.onload=function(){
				var pagid = "c" + this.id;
				$('#' + pagid).css("background-image", "url('" + this.src + "')");
			};
			imgBack.onerror=function(){
				var pagid = "c" + this.id;
				if(err==8){err=1};
				$('#' + pagid).css("background-image", "url('plugin/chamilo_boost/resources/css/error" + err + ".jpg')");
				err++;
			};
			imgBack.src = urlplugimg;
			
			i++;
			
		});
		
	}
	
	return rh;

}
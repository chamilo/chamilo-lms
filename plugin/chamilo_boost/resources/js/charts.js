

var jsonExtrasCharts = undefined;

function preLoadChartsLittle(){

	var urlplug = $('#plugfullpath').html();
	var urlpath = urlplug + "ajax/getprogress.php";

	$.getJSON(urlpath).done(function(result){
		
		jsonExtrasCharts = result;
		
		if(result.stats.length>0){
			$.each(result.stats,function(){
				if(this.title=='eventCourseId'){
					evidenceTitle(this.value);
				}
			});
		}

	});

}

function evidenceTitle(dirIdref){

	$(".thecard").each(function(index){
		var idref = $(this).attr("idref");
		if(idref==dirIdref){
			$(this).append("<div class='thecardAlarm animated infinite pulse' >!</div>");
		}
	});

}

function loadChartsLittle(){

	var urlplug = $('#plugfullpath').html();
	var urlpath = urlplug + "ajax/getprogress.php";
	loadImgTpl = loadImgTpl.replace('{urlplug}',urlplug);

	$('#chart1card').html("<div id='chartHori' >" + loadImgTpl + "</div>");

	if(jsonExtrasCharts!== undefined){
		
		setTimeout(function(){
			loadChartsData(jsonExtrasCharts);
		},1200);

	}else{
		
		$.getJSON(urlpath).done(function(result){
			loadChartsData(result);
		});
	
	}

}

function loadChartsData(result){

	var HoriLabels = new Array();
	var HoriValues = new Array();
	var HoriValues2 = new Array();
	var HoriNb = 0;
	
	$('#chartHori').html('');

	if(result.stats.length>0){
			
		$.each(result.stats,function(){
		
			if(this.title=='nblp'){
				$('#timecharts').html(this.value);
			}
			if(this.title=='time'){
				$('#nblpcharts').html(this.value);
			}
			if(this.title=='tab'){
				HoriLabels.push(this.name);
				HoriValues.push(this.totaltime);
				HoriValues2.push(this.moyenneExo);
				HoriNb++;
			}
			
		});
			
	}
    
	if(HoriNb==0){
		HoriLabels.push("Project");
		HoriValues.push(10);
		HoriValues2.push(35);
		HoriLabels.push("Code");
		HoriValues.push(20);
		HoriValues2.push(50);
		HoriLabels.push("Management");
		HoriValues.push(12);
		HoriValues2.push(100);
	}

	$('#chartHori').css("width",'400px');
	$('#chartHori').css("height",'240px');

	new Chartist.Bar('#chartHori', {
		labels: HoriLabels, series: [HoriValues2]
	},{
		seriesBarDistance : 10,
		reverseData : true,
		horizontalBars : true,
		axisY : {
			offset: 70
		}
	});

}
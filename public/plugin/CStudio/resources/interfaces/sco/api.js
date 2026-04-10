var API = null;
var callAPI = 0;
var scoPageAPI = 0;
var scoPageAPIpush = 0;
var timerPageAPIpush = setTimeout(function(){},3000);
var LastScore = -1;
var ScormStartTime = (new Date()).getTime();

//Log Console
function logconsole(msg){

	if (typeof console === "undefined" || typeof console.log === "undefined"){
		
	}else{
		console.log(msg)
	}

}

/* Check SCORM API or AlterScorm */
function findAPI(win){

	try{

		while ((win.API == null) && (win.parent != null) && (win.parent != win))
		{
			win = win.parent;

			callAPI = callAPI + 1;

		}
		
		API = win.API;
		
	}catch(exception){
		return false;
		
	}

}

/* initialize the SCORM API */
function initAPI(win){
	
	try{

		/* look for the SCORM API up in the frameset */
		findAPI(win);
		
		/* if we still have not found the API, look at the opener and its frameset */
		if ((API == null) && (win.opener != null))
		{
			findAPI(win.opener);
		}

	}catch(exception){

		logconsole("findAPI error");
		return false;

	}

}

function XapiScoStartComProcess(){
	
	initAPI(window);
	
	if (API != null){
		
		var initOk = false;
		
		//SCORM 1.2
		if (typeof(API.LMSInitialize) != "undefined") {

         if (typeof(API.haveInit) === "undefined") {

			//SCORM 1.2
			if (typeof(API.LMSInitialize) != "undefined") {
				API.LMSInitialize(''); 
				API.countTime = ScormStartTime;
				API.haveInit = true;
				logconsole("Init SCORM");
				API.LMSSetValue('cmi.core.lesson_status', 'incomplete');
				API.LMSSetValue('cmi.core.score.min', 0);
				API.LMSSetValue('cmi.core.score.max', 100);
				API.LMSCommit('');
				logconsole("Initialize ScormStartCom SCORM 1.2");
			}
		
			//SCORM 2004
			if (typeof(API.Initialize) != "undefined"){
				var r = API.Initialize('');
				API.countTime = ScormStartTime;
				API.haveInit = true;
				logconsole("Init SCORM");
				if(r==true||r=='true'){
					API.SetValue('cmi.core.lesson_status', 'incomplete');
					API.SetValue('cmi.core.score.min', 0);
					API.SetValue('cmi.core.score.max', 100);
					API.Commit('');
					API.SetValue('cmi.lesson_status', 'incomplete');
					API.SetValue('cmi.score.min', 0);
					API.SetValue('cmi.score.max', 100);				
					API.Commit('');
					logconsole("Initialize ScormStartCom SCORM 2004");
				}else{
					logconsole("Initialize Error");
				}
				
			}
			
         }
        
      }

	}
	
}

/* getLMSLocation */
function getLMSLocation(){

	if (API != null){
		if(typeof(API.LMSSetValue)!= "undefined"){
			scoPageAPI = API.LMSGetValue("cmi.core.lesson_location");
			if(typeof(API.lessonlocation) != "undefined") {
				if(API.lessonlocation>scoPageAPI){
					scoPageAPI = API.lessonlocation;
				}
			}
		}
	}

	var maxscoPageAPI = getLMSLocationLocalHostValue();

	if (parseInt(maxscoPageAPI)>parseInt(scoPageAPI)) {
		scoPageAPI = maxscoPageAPI;
	}

	if(scoPageAPI===undefined||scoPageAPI==""||scoPageAPI==0){
		getLMSLocationLocalHost();
	}
	if(scoPageAPI===undefined){scoPageAPI = 1;}
	if(scoPageAPI==""){scoPageAPI = 1;}
	if(scoPageAPI=="0"||scoPageAPI==0){scoPageAPI = 1;}
	if(scoPageAPI=="null"){scoPageAPI = 1;}
	if(!scoPageAPI){scoPageAPI = 1;}
	//console.log("getLMSLocation() = scoPageAPI=" + scoPageAPI);
	return parseInt(scoPageAPI);

}
function getLMSLocationLocalHost(){
	if (localStorage) {
        try {
			scoPageAPI = window.localStorage.getItem(getContextActivityId());
        } catch(err) {
        }
	
    }
}
function getLMSLocationLocalHostValue() {
	var returnLoc = 0;
	if (localStorage) {
        try {
			returnLoc = parseInt(window.localStorage.getItem(getContextActivityId()));
        } catch(err) {
        }
    }
	if(returnLoc===undefined){returnLoc = 0;}
	if(returnLoc==""){returnLoc = 0;}
	if (isNaN(returnLoc)) {returnLoc = 0;}
	return parseInt(returnLoc);
}

function setLMSLocationLocalHost(pind){
    if (localStorage) {
        try {
            window.localStorage.setItem(getContextActivityId(),pind);
        } catch(err) {
		}
    }
}
function getContextActivityId() {
	var actId = 'lessonlocationstudio-' + basePages[1] + '-';
	if (API != null) {
		var namU = checkNameLS();
		actId = actId + namU;
	}
    return actId;
}
function checkNameLS() {
	var userN = '';
	if (API != null){
		if (typeof(API.data)!="undefined"){
			if (typeof(API.data.learner_name)!="undefined"){
				userN = API.data.learner_name;
			}
		}
		if(userN==''){
			if (typeof(API.LMSGetValue)!="undefined"){
				userN = API.LMSGetValue("cmi.core.student_name") ;
			}
		}
		if(userN==''){
			if (typeof(API.LMSGetValue)!="undefined"){
				userN = API.LMSGetValue("cmi.student_name");
			}
		}
		if(userN==''){
			if (typeof(API.LMSGetValue)!="undefined"){
				userN = API.LMSGetValue("cmi.core.student_id");
			}
		}
	}
	userN = 'u' + userN.toLowerCase();
	userN = userN.replace(/,/g,'-');
	userN = userN.replace(/e/g,'x');
	userN = userN.replace(/i/g,'y');
	userN = userN.replace(/u/g,'y');
	userN = userN.replace(/o/g,'y');
	userN = userN.replace(/a/g,'q');
	userN = userN.replace(/ /g,'z');
	return userN;
}
function sendLMSLocation(nPage,maxPage){

	if (nPage>parseInt(scoPageAPI)) {
		setLMSLocationLocalHost(nPage);
	} else {
		var locationHost = getLMSLocationLocalHostValue();
		if (nPage>parseInt(locationHost)) {
			setLMSLocationLocalHost(nPage);
		}
	}

	if (API != null){

		if(typeof(API.LMSSetValue)!= "undefined"){

			var NeoScoPageAPI = API.LMSGetValue("cmi.core.lesson_location");
			
			if(NeoScoPageAPI===undefined){NeoScoPageAPI = 1;}
			if(NeoScoPageAPI==""){NeoScoPageAPI = 1;}

			if (nPage>parseInt(NeoScoPageAPI)) {
				NeoScoPageAPI = nPage;
			}
			var locationHost = getLMSLocationLocalHostValue();
			if (locationHost>parseInt(NeoScoPageAPI)) {
				NeoScoPageAPI = parseInt(locationHost);
			}

			if(typeof(API.lessonlocation) === "undefined") {
				API.lessonlocation = NeoScoPageAPI;
			}
			if(typeof(API.lessonlocation) === "") {
				API.lessonlocation = NeoScoPageAPI;
			}

			if (parseInt(NeoScoPageAPI)>parseInt(API.lessonlocation)) {
				API.lessonlocation = NeoScoPageAPI;
			}

			// console.log("sendLMSLocation() = scoPageAPI=" + NeoScoPageAPI);
			scoPageAPIpush = NeoScoPageAPI;
			
			clearTimeout(timerPageAPIpush);
			timerPageAPIpush = setTimeout(function(){pushLocationToFriendLms();},1000);

		}

	}

}
/* getLMSLocation */

function resetLMSLocation() {

	if (API != null) {
		if(typeof(API.LMSSetValue)!= "undefined"){
			API.LMSSetValue("cmi.core.lesson_location",1);
			
		}
		if (API.lessonlocation != null) {
			API.lessonlocation = 1;
		}
	}

}

function pushLocationToFriendLms() {

	//Hack Chamilo LMS
	if (typeof(API.save_asset)!= "undefined") {
		
		var olms = parent.olms;

		if (olms.lms_item_type=='sco') {
			
			if (!haveLocalScoUrl()) {

				var parentHomeCourse = window.top.$("#home-course");

				if (parentHomeCourse.length==1) {
					if (window.parent && window.parent.API) {
						window.localStorage.setItem('idstudio',localIdTeachdoc);
						window.localStorage.setItem('pourcstudio',globalCoursePourc);
					}
				}

				var lk = '/plugin/CStudio/ajax/sco/scorm-save-location.php';
				$.ajax({
					url: lk + "?loc=" + scoPageAPIpush + '&id=' + localIdTeachdoc + '&pour=' + globalCoursePourc + '&' + window.top.chamiloCidReq.queryParams
				}).done(function(){
					logconsole("loc:" + scoPageAPIpush);
				});
			
			}

		}
	}

}

function haveLocalScoUrl(){
	var ur=window.location.href;
	if(ur.indexOf("file://")!=-1){
		return true;
	}
	return false;
}

function CheckLMSFinishFinal(){

	if (API != null){
		
		setLMSLocationLocalHost(progressBtop);

		var score = API.LMSGetValue('cmi.core.score.raw');
		var status = API.LMSGetValue('cmi.core.lesson_status');

		if(typeof(API.haveScormSubmitted) === "undefined") {
			API.haveScormSubmitted = false;
		}
		
		if(API.haveScormSubmitted == false){
			
			API.haveScormSubmitted = true;
			
			if(typeof(API.LMSSetValue)!= "undefined"){
				if(score!=100&&status!='completed'){
					API.LMSSetValue('cmi.core.score.raw', 100);
					API.LMSSetValue('cmi.core.lesson_status','completed');
					API.LMSCommit('');
					if(typeof(API.LMSFinish) != "undefined"&&score!=100){
						API.LMSFinish('');
					}
				}else{
					API.LMSSetValue('cmi.core.session_time', MillisecondsToTime((new Date()).getTime() - API.countTime));
					ScormStartTime = (new Date()).getTime();
					API.countTime = ScormStartTime;
					API.LMSCommit('');
				}
			}
			
		}
	
	}

}

setTimeout(function(){sendTimeToLms();},30000);

function sendTimeToLms(){
	if (API != null){

		if(typeof(API.LMSSetValue)!= "undefined"){
			var sendT = MillisecondsToTime((new Date()).getTime() - API.countTime);
			console.log('sendT:'+sendT);
			API.LMSSetValue('cmi.core.session_time',sendT);
			ScormStartTime = (new Date()).getTime();
			API.countTime = ScormStartTime;
			API.LMSCommit('');
		}
		setTimeout(function(){sendTimeToLms();},60000);
		
	}
}

//TIME RENDERING FUNCTION
function MillisecondsToTime(Seconds){
	Seconds = Math.round(Seconds/1000);
	var S = Seconds % 60;
	Seconds -= S;
	if (S < 10){S = '0' + S;}
	var M = (Seconds / 60) % 60;
	if (M < 10){M = '0' + M;}
	var H = Math.floor(Seconds / 3600);
	if (H < 10){H = '0' + H;}
	return H + ':' + M + ':' + S;
}

XapiScoStartComProcess();
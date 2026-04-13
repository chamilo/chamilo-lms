var API = null;
var callAPI = 0;
var scoPageAPI = 0;
var scoPageAPIpush = 0;
var timerPageAPIpush = setTimeout(function(){},3000);
var LastScore = -1;
var ScormStartTime = (new Date()).getTime();

function logconsole(msg){

	if (typeof console === "undefined" || typeof console.log === "undefined"){
		
	}else{
		console.log(msg)
	}

}

function findAPI(win){
return true;
}

/* initialize the SCORM API */
function initAPI(win){
	return true;
}
function XapiScoStartComProcess(){
	return true;
}
/* getLMSLocation */
function getLMSLocation(){
	scoPageAPI = 999;
	return parseInt(scoPageAPI);
}
function getLMSLocationLocalHost(){
	scoPageAPI = 999;
	return parseInt(scoPageAPI);
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
	return 'offline';
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


}
/* getLMSLocation */
function resetLMSLocation() {
	return true;
}
function pushLocationToFriendLms() {
	return true;
}
function haveLocalScoUrl(){
	return true;
}
function getLocUrl(){
	return ""; 
}
function CheckLMSFinishFinal(){
	return true;
}
function sendTimeToLms(){
	return true;
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
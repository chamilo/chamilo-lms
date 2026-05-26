//TinCan.enableDebug();
var API = null;
var callAPI = 0;
var scoPageAPI = 0;
var LastScore = -1;
var ScormStartTime = (new Date()).getTime();
var globalActivityTC = '';

var ROOT_ACTIVITY_ID = "http://id.tincanapi.com/activity/" + localactivityid,
    tincan = new TinCan (
        {
            url: window.location.href,
            activity: {
                id: ROOT_ACTIVITY_ID
            }
        }
    ),
    TCActive = false,
    actorName = "",
    actorEmail = "",
    docId = "",
    HighScoresActivityProfile = null,
    HighScoresArray;

function XapiScoStartComProcess(){
    
    if (window.location.href.indexOf("endpoint=")==-1) {
        alert('no endpoint !');
        var auth = 'Basic ' + Base64.encode('toto' + ':' + 'toto');
        var join  = '&';
        if (window.location.href.indexOf(".html?")==-1){join = '?';}
        var link = window.location.href + join + "endpoint=" + encodeURIComponent('http://localhost/plugin/XApi/lrs.php?/') +
        "&auth=" + encodeURIComponent(auth) +
        "&Authorization="+ encodeURIComponent(auth) +
        "&actor=" + encodeURIComponent('noactor');
        window.location.href = link;
    } else {
        var endpointStr = tincan.recordStores[0].endpoint;
        var authStr = tincan.recordStores[0].auth;
        var authActor = tincan.recordStores[0].actor;
        var authActorJson =  {mbox:"",name:"",objectType:"Agent"};
        if(authActor===undefined){
            if(tincan.actor){
                authActor = tincan.actor;
            }
        }
        if (authActor.mbox) {
            authActorJson.mbox = authActor.mbox;
            authActorJson.name = authActor.name;
        }
        additional_params = "&endpoint=" + encodeURIComponent(endpointStr) +
        "&auth=" + encodeURIComponent(authStr) + 
        "&Authorization="+ encodeURIComponent(authStr) +
        "&actor=" + encodeURIComponent(JSON.stringify(authActorJson));
        if (window.location.href.indexOf("&tablelogs=1")!=-1) {
            additional_params += "&tablelogs=1";
        }

    }

    TCActive = true;
    tc_sendStatement_Initialize();
    if (typeof tincan !== "undefined" && tincan.actor !== null) {
        actor = tincan.actor;
        actorName = actor.name;
        actorEmail = actor.mbox;
    }

}

/* getLMSLocation */
function getLMSLocation(){

	if(scoPageAPI===undefined||scoPageAPI==""||scoPageAPI==0){
		getLMSLocationLocalHost();
	}
	if(scoPageAPI===undefined){scoPageAPI = 1;}
	if(scoPageAPI==""){scoPageAPI = 1;}
	if(scoPageAPI=="0"||scoPageAPI==0){scoPageAPI = 1;}
	return scoPageAPI;

}
function getLMSLocationLocalHost(){
	if (localStorage) {
        try {
			scoPageAPI = window.localStorage.getItem(getContextActivityId());
        } catch(err) {
        }
	
    }
}
function setLMSLocationLocalHost(pind){
    if (localStorage) {
        try {
            window.localStorage.setItem(getContextActivityId(),pind);
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
	if (tincan != null) {
        if (tincan.actor) {
            userN = tincan.actor.toString();
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

	if(nPage>scoPageAPI){
		setLMSLocationLocalHost(nPage);
	} else {
		var locationHost = getLMSLocationLocalHostValue();
		if (nPage>parseInt(locationHost)) {
			setLMSLocationLocalHost(nPage);
		}
	}

}
/* getLMSLocation */

// Redirect
function CheckLMSFinishFinal(){TerminateAPI();}

function TerminateAPI(){

    tc_sendStatement_Terminate();

    tincan.actor = new TinCan.Agent(
        {
            name: actorName,
            mbox: actorEmail
        }
    );
    
    tc_sendStatement_Initialize();

}

function tc_getContext (extensions, parent) {
    var context = {
        contextActivities: {
            grouping: [
                {
                    id: "http://id.tincanapi.com/activity/prototypes" + '/' + globalActivityTC
                },
                {
                    id: ROOT_ACTIVITY_ID + '/' + globalActivityTC
                }
            ]
            /*,
            category: [
                 {
                    id: "http://id.tincanapi.com/recipe/prototypes/studiodoc/1",
                    definition: {
                        type: "http://id.tincanapi.com/activitytype/recipe"
                    }
                }
            ]*/
        }
    };
    if (typeof (extensions) !== "undefined") {
        context.extensions = extensions;
    }
    if (typeof (parent) !== "undefined") {
        console.log(parent);
        context.contextActivities.parent = [parent];
    }
    return context
}

function tc_getContextExtensions () {
    return {
        "http://id.tincanapi.com/extension/attemptid": docId
    };
}

function tc_sendStatementWithContext (stmt, extensions, parent) {

    stmt.context = tc_getContext(extensions, parent);

    tincan.sendStatement(stmt, function () {});

}

//For Elearning Only
function tc_sendStatement_Initialize () {

    if (actorEmail=='') {
        return false;
    }

    tc_sendStatementWithContext(
        {
            actor: {
                name : actorName,
                mbox : actorEmail,
                objectType : "Agent"
            },
            verb: {
                id: "http://adlnet.gov/expapi/verbs/initialized",
                display: {
                    "en-US": "initialized"
                }
            },
            object: {
                id: ROOT_ACTIVITY_ID,
                definition: {
                    type: "http://activitystrea.ms/schema/1/studio",
                    name: {
                        "en-US": ROOT_ACTIVITY_ID + "/" + globalActivityTC
                    },
                    description: {
                        "en-US": ROOT_ACTIVITY_ID + "/" + globalActivityTC
                    }
                }
            }
        }
    );
}

function tc_sendStatement_Terminate () {

    if (actorEmail=='') {
        return false;
    }

    tc_sendStatementWithContext(
        {
            actor:
            {
                name : actorName,
                mbox : actorEmail,
                objectType : "Agent"
            },
            verb: {
                id: "http://adlnet.gov/expapi/verbs/terminated",
                display: {
                    "en-US": "terminated"
                }
            },
            object: {
                id: ROOT_ACTIVITY_ID,
                definition: {
                    type: "StudioDoc",
                    name: {
                        "en-US": "studiodoc"
                    },
                    description: {
                        "en-US": "studiodoc"
                    }
                }
            }
        }
    );
}

var memSend = "@";

function tc_sendStatement_Exercices(title,description,interactionType, attempts , result) {
    
    var extensions = {};

    if (! TCActive) {
        return;
    }
    
    var scorecalc = 0;
    var resulttostring  = false;
    if (result) {scorecalc = 100;resulttostring = true;}

    var verb = 'answered';

    var response = '';
    if(typeof response === 'undefined'){
        response = '';
    }

    if (interactionType=='quizz') {
        response = description + "";
    }

    if (interactionType.indexOf('h5p')!=-1) {
        response = description + "h5pobject";
    }
    
    if (interactionType=='launch') {
        response = title + "";
        verb = 'experienced';
        scorecalc = 100;
        resulttostring = true;
    }

    if (actorEmail=='') {
        return false;
    }

    globalActivityTC = title;

    if (globalActivityTC=='') {
        globalActivityTC = 'error';
    }

    // extensions["http://id.tincanapi.com/xapi/extensions/interactionType"] = interactionType;

    var memID = title + response + resulttostring + '@';

    if (memSend.indexOf(memID)==-1) {
        memSend = memSend + memID;
        
        //new TinCan.Statement(
        tc_sendStatementWithContext(
            {
                actor:
                {
                    name : actorName,
                    mbox : actorEmail,
                    objectType : "Agent"
                },
                verb: {
                    id: "http://adlnet.gov/expapi/verbs/" + verb,
                    display: {
                        "en-US": "completed"
                    }
                },
                object: {
                    objectType : interactionType,
                    id: ROOT_ACTIVITY_ID + "/" + title,
                    definition: {
                        name: {
                            "en-US": "studiodoc" + title
                        },
                        description: {
                            "en-US": description
                        }
                    }
                },
                result: {
                    "success" : resulttostring,
                    "response" : response,
                    extensions: extensions,
                    score: {
                        raw: scorecalc,
                        min: 0,
                        max : 100
                    },
                    "completion" : resulttostring
                }
            },
            tc_getContextExtensions(),
            {id: ROOT_ACTIVITY_ID + "/" + title}
        );

        //stmt.result.response = response;
        //stmt.result.success = resulttostring;
        //stmt.result.score.raw = scorecalc;
        //stmt.result.score.min = 0;
        //stmt.result.score.max = 100;
        
        var objectActivity = new TinCan.Activity();
        objectActivity.objectType = interactionType;
        objectActivity.id = title;
        objectActivity.definition = description;
        
        //stmt.target = objectActivity;
        
        //tincan.sendStatement(stmt, function () {});
        
        //tc_sendStatementWithContext(stmt);

    }

}
//For Elearning Only
XapiScoStartComProcess();

//For Game Only
function tc_sendStatement_StartNewGame () {
    
    if (! TCActive) {
        return;
    }

    docId = TinCan.Utils.getUUID();

    tc_sendStatementWithContext(
        {
            actor:
            {
                name : actorName,
                mbox : actorEmail,
                objectType : "Agent"
            },
            verb: {
                id: "http://adlnet.gov/expapi/verbs/attempted",
                display: {
                    "en-US": "attempted"
                }
            },
            object: {
                id: ROOT_ACTIVITY_ID,
                definition: {
                    type: "http://activitystrea.ms/schema/1.0/studio",
                    name: {
                        "en-US": "studiodoc - Tin Can Prototype"
                    },
                    description: {
                        "en-US": "studiodoc"
                    }
                }
            },
            result: {
                duration: "PT0S"
            }
        },
        tc_getContextExtensions()
    );
}

function tc_sendStatement_FinishLevel (level, time, apm, lines, score) {
    var extensions = {};

    if (! TCActive) {
        return;
    }

    extensions["http://id.tincanapi.com/extension/apm"] = apm;
    extensions["http://id.tincanapi.com/extension/studiodoclines"] = lines;
    
    tc_sendStatementWithContext(
        {
            actor:
            {
                name : actorName,
                mbox : actorEmail,
                objectType : "Agent"
            },
            verb: {
                id: "http://adlnet.gov/expapi/verbs/completed",
                display: {
                    "en-US": "completed"
                }
            },
            object: {
                id: ROOT_ACTIVITY_ID + "/levels/" + level,
                definition: {
                    type: "http://curatr3.com/define/type/level",
                    name: {
                        "en-US": "studiodoc " + level
                    },
                    description: {
                        "en-US": "Starting at 1."
                    }
                }
            },
            result: {
                extensions: extensions,
                score: {
                    raw: score,
                    min: 0
                },
                duration: TinCan.Utils.convertMillisecondsToISO8601Duration(time*1000)
            }
        },
        tc_getContextExtensions(),
        {id: ROOT_ACTIVITY_ID}
    );
}

function tc_sendStatement_EndGame (level, time, apm, lines, score) {
    var extensions = {};

    if (! TCActive) {
        return;
    }

    extensions["http://id.tincanapi.com/extension/level"] = level;
    extensions["http://id.tincanapi.com/extension/apm"] = apm;
    extensions["http://id.tincanapi.com/extension/studiodoclines"] = lines;

    tc_sendStatementWithContext(
        {
            verb: {
                id: "http://adlnet.gov/expapi/verbs/completed",
                display: {
                    "en-US": "completed"
                }
            },
            object: {
                id: ROOT_ACTIVITY_ID,
                definition: {
                    type: "http://activitystrea.ms/schema/1/studio",
                    name: {
                        "en-US": "studiodoc - Tin Can Prototype"
                    },
                    description: {
                        "en-US": "studiodoc."
                    }
                }
            },
            result: {
                score: {
                    raw: score,
                    min: 0
                },
                duration: TinCan.Utils.convertMillisecondsToISO8601Duration(time*1000),
                extensions: extensions
            }
        },
        tc_getContextExtensions()
    );

    // update high score
    tc_addScoreToLeaderBoard(
        {
            actor: {
                name: tincan.actor.toString(),
            },
            score: score,
            date: TinCan.Utils.getISODateString(new Date())
        },
        0
    );
}

function tc_addScoreToLeaderBoard (newScoreObj, attemptCount) {
    
    var highScorePos;

    if (typeof attemptCount === "undefined" || attemptCount === null){
        attemptCount = 0;
    }
    if (attemptCount > 3) {
        throw new Error("Could not update leader board");
    }

    tc_InitHighScoresObject();

    highScorePos = tc_findScorePosition(HighScoresArray, 0, HighScoresArray.length-1, newScoreObj.score);
    if (highScorePos < 15) {
        HighScoresArray.splice(highScorePos, 0, newScoreObj);

        if (HighScoresArray.length > 15) {
            HighScoresArray.pop();
        }

        tincan.setActivityProfile(
            "highscores",
            JSON.stringify(HighScoresArray),
            {
                lastSHA1: (HighScoresActivityProfile !== null ? HighScoresActivityProfile.etag : null),
                callback: function (err, xhr) {
                    // If we hit a conflict just try this whole thing again...
                    if (xhr.status === 409 || xhr.status === 412) {
                        tc_addScoreToLeaderBoard(newScoreObj, attemptCount + 1);
                    }
                }
            }
        );
    }
}

function tc_InitHighScoresObject () {
    var result = tincan.getActivityProfile("highscores");

    if (result.err === null && result.profile !== null && result.profile.contents !== null && result.profile.contents !== "") {
        HighScoresActivityProfile = result.profile;
        HighScoresArray = JSON.parse(result.profile.contents);
    }
    else {
        HighScoresArray = [];
    }
}

function tc_findScorePosition (hsArray, start, end , val) {
    var insert = 1,
        keepsearching = true
    ;
    if (hsArray.length === 0) {
        return 0;
    }

    while (keepsearching) {
        if (end - start === 0){
            insert = (val <= parseInt(hsArray[start].score)) ? start + 1 : start;
            keepsearching = false;
        }
        else if (end - start === 1) {
            if (val > parseInt(hsArray[start].score)) {
                insert = start;
            }
            else if (val > parseInt(hsArray[end].score)) {
                insert = end;
            }
            else {
                insert = end + 1;
            }
            keepsearching = false;
        }
        else {
            var mid = start + Math.ceil( (end - start) / 2 );
            if (val <= parseInt(hsArray[mid].score)) {
                start = mid;
            }
            else {
                end = mid;
            }
        }
    }
    return insert;
}
//For Game Only


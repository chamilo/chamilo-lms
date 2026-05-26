
//this.getJSON

function thisgetJSON(link){
    
	alert(link);
	
    if(link.indexOf("h5p.json")!=-1){
        return {"title":"Game SOUDURE","language":"und","mainLibrary":"H5P.FindTheWords","embedTypes":["div"],"license":"U","defaultLanguage":"en","preloadedDependencies":[{"machineName":"H5P.FindTheWords","majorVersion":"1","minorVersion":"4"},{"machineName":"FontAwesome","majorVersion":"4","minorVersion":"5"},{"machineName":"H5P.Timer","majorVersion":"0","minorVersion":"4"},{"machineName":"H5P.JoubelUI","majorVersion":"1","minorVersion":"3"},{"machineName":"H5P.Transition","majorVersion":"1","minorVersion":"0"},{"machineName":"Drop","majorVersion":"1","minorVersion":"0"},{"machineName":"Tether","majorVersion":"1","minorVersion":"0"},{"machineName":"H5P.FontIcons","majorVersion":"1","minorVersion":"0"}]};
    }
    if(link.indexOf("content.json")!=-1){
        return {"taskDescription":"&nbsp;","wordList":"arc,testa,test","behaviour":{"orientations":{"horizontal":true,"horizontalBack":true,"vertical":true,"verticalUp":true,"diagonal":true,"diagonalBack":true,"diagonalUp":true,"diagonalUpBack":true},"fillPool":"abcdefghijklmnopqrstuvwxyz","preferOverlap":true,"showVocabulary":true,"enableShowSolution":true,"enableRetry":true},"l10n":{"check":"Check","tryAgain":"Retry","showSolution":"Show Solution","found":"@found of @totalWords found","timeSpent":"Time Spent","score":"You got @score of @total points","wordListHeader":"Find the words"}};
    }

}
//this.libraryPath
function thislibraryPath(e){
   
    return "";

}

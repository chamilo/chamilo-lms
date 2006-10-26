var startDate;
var exitPageStatus;

function loadPage()
{
   var result = doLMSInitialize();

   var status = doLMSGetValue( "cmi.core.lesson_status" );

   if (status == "not attempted")
   {
	  // the student is now attempting the lesson
	  doLMSSetValue( "cmi.core.lesson_status", "incomplete" );
   }

   exitPageStatus = false;
   startTimer();
}


function startTimer()
{
   startDate = new Date().getTime();
}

function computeTime()
{
   if ( startDate != 0 )
   {
      var currentDate = new Date().getTime();
      var elapsedSeconds = ( (currentDate - startDate) / 1000 );
      var formattedTime = convertTotalSeconds( elapsedSeconds );
   }
   else
   {
      formattedTime = "00:00:00.0";
   }

   doLMSSetValue( "cmi.core.session_time", formattedTime );
}

function doBack()  //darkden:this function is never called (not present in any of the files only in this and in this file, only here)
{
   doLMSSetValue( "cmi.core.exit", "suspend" );

   computeTime();
   exitPageStatus = true;
   
   var result;

   result = doLMSCommit();

	// NOTE: LMSFinish will unload the current SCO.  All processing
	//       relative to the current page must be performed prior
	//		 to calling LMSFinish.   
   
   result = doLMSFinish();

}

function doContinue( status )  //darkden:this function is never called (not present in any of the files only in this and in this file, only here)
{
   // Reinitialize Exit to blank
   doLMSSetValue( "cmi.core.exit", "" );

   var mode = doLMSGetValue( "cmi.core.lesson_mode" );

   if ( mode != "review"  &&  mode != "browse" )
   {
      doLMSSetValue( "cmi.core.lesson_status", status );
   }
 
   computeTime();
   exitPageStatus = true;
   
   var result;
   result = doLMSCommit();
	// NOTE: LMSFinish will unload the current SCO.  All processing
	//       relative to the current page must be performed prior
	//		 to calling LMSFinish.   

   result = doLMSFinish();

}

function doQuit( status )
{
   computeTime();
   exitPageStatus = true;
   
   var result;

   result = doLMSCommit();

   result = doLMSSetValue("cmi.core.lesson_status", status);
   
	// NOTE: LMSFinish will unload the current SCO.  All processing
	//       relative to the current page must be performed prior
	//		 to calling LMSFinish.   

   result = doLMSFinish();
}

/*******************************************************************************
** The purpose of this function is to handle cases where the current SCO may be 
** unloaded via some user action other than using the navigation controls 
** embedded in the content.   This function will be called every time an SCO
** is unloaded.  If the user has caused the page to be unloaded through the
** preferred SCO control mechanisms, the value of the "exitPageStatus" var
** will be true so we'll just allow the page to be unloaded.   If the value
** of "exitPageStatus" is false, we know the user caused to the page to be
** unloaded through use of some other mechanism... most likely the back
** button on the browser.  We'll handle this situation the same way we 
** would handle a "quit" - as in the user pressing the SCO's quit button.
*******************************************************************************/
function unloadPage( status )
{

	if (exitPageStatus != true)
	{
		doQuit( status );
	}

	// NOTE:  don't return anything that resembles a javascript
	//		  string from this function or IE will take the
	//		  liberty of displaying a confirm message box.
	
}

/*******************************************************************************
** this function will convert seconds into hours, minutes, and seconds in
** CMITimespan type format - HHHH:MM:SS.SS (Hours has a max of 4 digits &
** Min of 2 digits
*******************************************************************************/
function convertTotalSeconds(ts)
{
   var sec = (ts % 60);

   ts -= sec;
   var tmp = (ts % 3600);  //# of seconds in the total # of minutes
   ts -= tmp;              //# of seconds in the total # of hours

   // convert seconds to conform to CMITimespan type (e.g. SS.00)
   sec = Math.round(sec*100)/100;
   
   var strSec = new String(sec);
   var strWholeSec = strSec;
   var strFractionSec = "";

   if (strSec.indexOf(".") != -1)
   {
      strWholeSec =  strSec.substring(0, strSec.indexOf("."));
      strFractionSec = strSec.substring(strSec.indexOf(".")+1, strSec.length);
   }
   
   if (strWholeSec.length < 2)
   {
      strWholeSec = "0" + strWholeSec;
   }
   strSec = strWholeSec;
   
   if (strFractionSec.length)
   {
      strSec = strSec+ "." + strFractionSec;
   }


   if ((ts % 3600) != 0 )
      var hour = 0;
   else var hour = (ts / 3600);
   if ( (tmp % 60) != 0 )
      var min = 0;
   else var min = (tmp / 60);

   if ((new String(hour)).length < 2)
      hour = "0"+hour;
   if ((new String(min)).length < 2)
      min = "0"+min;

   var rtnVal = hour+":"+min+":"+strSec;

   return rtnVal;
}


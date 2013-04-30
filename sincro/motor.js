<!-- 
var start_time = 0;

function Timer()
{
	this.startTime = 0;
	this.endTime = 0;

	Timer.prototype.Reset = function()
	{
		this.startTime = new Date().getTime();
		this.endTime = 0;
	}

	Timer.prototype.Stop = function()
	{
		this.endTime = new Date().getTime();
		return this.endTime - this.startTime;
	}

	Timer.prototype.Value = function()
	{
		var end = this.endTime;
		if( end == 0 && this.startTime != 0 )
			end = new Date().getTime();
		return end - this.startTime;
	}

	this.Reset();
}

Number.prototype.timeString = function()
{
	if( this >= 1000 )
		return roundNumber( this / 1000, 2 ).toString() + " s";
	return this.toString() + " ms";
}

Number.prototype.speedString = function()
{
    //if( this >= 1000 )
    //    return roundNumber( this / 1000, 1 ).toString() + " Mbit/s";
    return this.toString() + " kbit/s";
}

function WebClientRequest( method, url )
{
	this.timer = new Timer;
	this.url = url;
	this.data = "";
	this.method = method;

	this.GetResponse = function( readData )
	{
		var response = new WebClientResponse( this, readData );
		this.timer.Reset();
		$.ajax( {
			url: this.url,
			cache: false,
			async: false,
			processData: false,
			data: this.data,
			type: this.method,
			dataType: "text",
			contentType: "text/plain; charset=x-user-defined",
			complete: response.InternalRequestCompleted(),
			error: function( e ) 
			{
				if( e.status == 200 )
					return; // We do get here in IE7 on Vista, even though the request performed well..
				if( e.status == 405 )
					alert( "Su servidor no permite el uso de HTTP POST hacia este archivo HTML.\nPor favor, revise su configuración." );
			} 
		} );

		return response;
	};
}

function WebClientResponse( request, readData )
{
	this.request = request;
	this.readData = readData;
	this.data = "";
	this.status = "";
	this.statusText = "";
	this.contentLength = 0;
	this.bytesTransferred = 0;
	this.GetTime = function() { return this.request.timer.Value(); }
	this.GetSpeed = function() { return roundNumber( ( this.bytesTransferred * 0.008 ) / ( this.GetTime() / 1000 ), 0 ); }

	this.InternalRequestCompleted = function() 
	{
		var response = this;
		return function( xhr )
		{
			response.status = xhr.status;
			response.statusText = xhr.statusText;
			var contentLengthHeader = xhr.getResponseHeader( "Content-Length" );
			
			if( null != contentLengthHeader && contentLengthHeader.length > 0 )
				response.contentLength = parseInt( contentLengthHeader );
			else if( null != xhr.responseText )
				response.contentLength = xhr.responseText.length;
			else
				response.contentLength = 0;
			
			if( response.request.method == "POST" )
				response.contentLength = request.data.length;
			if( response.request.method != "HEAD" )
				response.bytesTransferred = response.contentLength;
			if( response.readData )
				response.data = xhr.responseText;
			response.request.timer.Stop();
		}
    };
}

function WebClient()
{
	WebClient.prototype.Download = function( url, readData )
	{
		return new WebClientRequest( "GET", url ).GetResponse( readData );
	};

	WebClient.prototype.Upload = function( url, data )
	{
        if( url.indexOf( "?" ) == -1 )
            qs = '?sid=' + Math.random();
        else
            qs = '&sid=' + Math.random();

		var request = new WebClientRequest( "POST", url + qs );
		request.data = data;
		return request.GetResponse();
	};

	WebClient.prototype.Ping = function( url )
	{
		return new WebClientRequest( "HEAD", url ).GetResponse();
	};
}

Test.SmallFilePath = "data_100k.txt";
Test.LargeFilePath = "data_1600k.txt";
Test.Download = "DOWNLOAD";
Test.Upload = "UPLOAD";

function Test()
{
	this.timer = new Timer;
	this.webClient = new WebClient;
	this.progressCallback = function() {};
	this.completionCallback = function() {};
	this.progressValue = 0;
	this.maxProgressValue = 0;
	this.path = "";

	this.last = null;
	this.ping = null;

	Test.prototype.Start = function()
	{
		var test = this;
		setTimeout( function()
		{
			test.timer.Reset();
			test.Run( function() 
			{ 
				test.progressValue++;
				if( null != test.progressCallback )
				{
					test.progressCallback();
				}
			},
			function()
			{
				test.timer.Stop();
				if( null != test.completionCallback )
					test.completionCallback();
			} );
		}, 10 );
	}

	Test.prototype.Run = function( updateProgress, completed ) {}

	Test.prototype.MakeRequest = function()
	{
		if( this.path == null || this.path.length == 0 )
			return;
		if( this.method == Test.Upload )
		{
			if( this.payload == null ) 
			{
				var dl = this.webClient.Ping( this.path );
				this.payload = this.GenerateData( dl.contentLength );
			}

			this.last = this.webClient.Upload( location.search, this.payload );
		}
		else if( this.method == Test.Download )
		{
			this.last = this.webClient.Download( this.path );
		}
        this.ping = this.webClient.Ping( "?" );
	}

	Test.prototype.GenerateData = function( length )
	{
		var data = "";
		while( data.length < length )
		{
			data = data + Math.random();
		}
		return data;
	}
			

	Test.prototype.OnProgress = function( progressCallback )
    {
        this.progressCallback = progressCallback;
		return this;
	}

	Test.prototype.OnComplete = function( completionCallback )
	{
		this.completionCallback = completionCallback;
		return this;
	}

    Test.prototype.Path = function( path )
    {
        this.path = path;
        return this;
    }

	Test.prototype.Method = function( method )
	{
		this.method = method;
		return this;
	}
}

AverageTest.prototype = new Test;
function AverageTest() 
{
	this.iterations = 0;
	this.iteration = 0;
	this.totalBytes = 0;
	this.totalSpeed = 0;
	this.totalTime = 0;
	this.totalPing = 0;

	this.iterationCallback = function() {};

	Test.apply( this, arguments );

	AverageTest.prototype.Run = function( incrementProgress, completed )
	{ 
		this.maxProgressValue = this.iterations;
		if( !this.IsCompleted() )
		{
			this.Iterate();
			if( this.last == null )
				return;
			this.iteration++;
			incrementProgress();
			var test = this;
			setTimeout( function() { test.Run( incrementProgress, completed ); }, 1 );
		}
		else
			completed();
	}

	AverageTest.prototype.IsCompleted = function()
	{
		return this.iterations == this.iteration;
	}

	AverageTest.prototype.GetAverageTime = function()
	{
		return roundNumber( this.totalTime / this.iteration, 2 );
	}

	AverageTest.prototype.GetAveragePing = function()
	{
		return roundNumber( this.totalPing / this.iteration, 2 );
	}

	AverageTest.prototype.GetAverageSpeed = function()
	{
		return roundNumber( this.totalSpeed / this.iteration, 1 );
	}

	AverageTest.prototype.GetTotalTime = function()
	{
		return this.totalTime;
	}

	AverageTest.prototype.Iterate = function()
	{
		this.MakeRequest();
		if( this.last != null )
		{
			this.totalBytes += this.last.contentLength;
			this.totalSpeed += this.last.GetSpeed();
			this.totalTime += this.last.GetTime();
			this.totalPing += this.ping.GetTime();
		}
	}

	AverageTest.prototype.Iterations = function( number )
	{
		this.iterations = number;
		return this;
	}

	AverageTest.prototype.AfterIteration = function( iterationCallback )
	{
		this.iterationCallback = iterationCallback;
		return this;
	} 
}


function roundNumber(num, dec) 
{
  var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
  return result;
}

function EndTest( upload, download )
{
    var totalTime = upload.GetTotalTime() + download.GetTotalTime();
    var avgTime = roundNumber( ( upload.GetAveragePing() + download.GetAveragePing() ) / 2, 2 );
    var tbl = $( "#tblSample" );
    var rows = $( "tr", tbl );
    row = $( "<tr/>" ).appendTo( tbl ).css( "background-color", "#cfcfcf" );
 
    $( row ).append( "<td colspan=8>Total time " + totalTime.timeString() + ", average response time " + avgTime.timeString() + "</td>" );

    $( "#startTestButton" ).removeAttr( "disabled" ).val( Strings.StartTest );
    $( "#testInProgress" ).hide( "normal" );

    var avgUlSpeed = upload.GetAverageSpeed();
    var avgDlSpeed = download.GetAverageSpeed();
    $('#dwn_speed_input').val(avgDlSpeed);
    $('#up_speed_input').val(avgUlSpeed);
    $('#submit_url_button').removeClass('hidden');

    SetInfo( avgUlSpeed.speedString(), avgTime.timeString(), upload.method );
    SetInfo( avgDlSpeed.speedString(), avgTime.timeString(), download.method );

    var title = "";
    var message = "";
    if( avgUlSpeed < Settings.MinimumUlSpeed || isNaN( avgUlSpeed ) || 
        avgDlSpeed < Settings.MinimumDlSpeed || isNaN( avgDlSpeed ) || 
        avgTime > Settings.MaximumTime || isNaN( avgTime )  )
    {
        title = Strings.TestFailed.Title;
        message = Strings.TestFailed.Message;
    }
    else
    {
        title = Strings.TestPassed.Title;
        message = Strings.TestPassed.Message;
    }
	
    var res = $( "#resultMessage" );
    res.children( ".result-title" ).text( title );
    res.children( ".result-message" ).text( message );
    res.show( "fast" );
}

function StartTest( count )
{
    $( "#resultMessage" ).hide( "fast" );
	$( "#startTestButton" ).attr( "disabled", "disabled" );
	UpdateProgress( 0 );
    $( "#testInProgress" ).show( "normal", function() { TestStarted( count ); } );  
}

function TestStarted( count )
{
	if( "" != $.query.get("details" ))
		Settings.Debug = true;
	if( Settings.Debug )
        $( "#technicalDetails" ).show( "fast" );
	var tbl = $( "#tblSample" );
	$( "tr", tbl ).remove();

	var upload = new AverageTest()
		.Iterations( count / 2 )
		.Path( Test.SmallFilePath )
		.Method( Test.Upload )
		.OnProgress( function() 
			{
				var progressPercentage = this.progressValue / ( this.maxProgressValue / 100 ) / 2;
				UpdateProgress( progressPercentage );
				AppendDetailsRow( this ); 
			} );

    var download = new AverageTest()
        .Iterations( count / 2 )
        .Path( Test.SmallFilePath )
        .Method( Test.Download )
        .OnProgress( function()
            {
				var progressPercentage = this.progressValue / (this.maxProgressValue / 100 ) / 2 + 50;
				UpdateProgress( progressPercentage );
				AppendDetailsRow( this ); 
			} );
    
	download.OnComplete( function() { EndTest( upload, download ); } );
	upload.OnComplete( function() { download.Start(); } ).Start();
}

function UpdateProgress( value )
{
	$( "#progressbar" ).children( ".ui-progressbar-value" ).css( "width", value + "%" );
}

function AppendDetailsRow( test )
{
	var tbl = $( "#tblSample" );
    var rows = $( "tr", tbl );

	row = $( "<tr/>" ).appendTo( tbl ).css( "background-color", ( test.iteration % 2 ) ? "#eeeeee" : "#ffffff" );

	var speed = test.last.GetSpeed().speedString();
	var time = test.last.GetTime().timeString();
    var addCol = function( text ) { $("<td/>" ).append( text ).appendTo( row ); };
    addCol( test.method + " " + test.iteration )
    addCol( time );
	addCol( test.ping.GetTime().timeString() );
    addCol( window.location.host );
    addCol( test.last.request.url );
    addCol( test.last.contentLength );
    addCol( test.last.status );
    addCol( speed );
    SetInfo( speed, test.ping.GetTime(), test.method );
}

var ulSpeed = $.query.get( "ul" );
if( "" != ulSpeed )
{
    Settings.MinimumUlSpeed = parseInt( ulSpeed );
}
var dlSpeed = $.query.get( "dl" );
if( "" != dlSpeed )
{
    Settings.MinimumDlSpeed = parseInt( dlSpeed );
}
var qsTime = $.query.get( "time" );
if( "" != qsTime )
{
    Settings.MaximumTime = parseInt( qsTime );
}

function SetInfo( speedtext, timetext, testMethod )
{
    $( ( testMethod == Test.Download ) ? "#currentdlspeed" : "#currentulspeed" ).text( speedtext );
	$( "#currenttime" ).text( timetext );
        $( "#delay_input" ).val(timetext);
}

var count = $.query.get( "count" );
if( count == "" || count > 100 )
	count = Settings.RequestCount; 

$( function()
{
    $( "#startTestButton" ).removeAttr( "disabled" ).val( Strings.StartTest ).click( function() { StartTest( count ) } );
    $( "#requiredulspeed" ).text( Settings.MinimumUlSpeed.speedString() );
    $( "#requireddlspeed" ).text( Settings.MinimumDlSpeed.speedString() );
	$( "#requiredtime" ).text( Settings.MaximumTime.timeString() );
	$( "#progressbar" ).progressbar();
	
	document.title = Strings.Title;
	$( "#title" ).text( Strings.Title );
	$( "#subtitle" ).text( Strings.SubTitle );
	$( "#testInProgressLabel" ).text( Strings.TestInProgress ); 
	$( "#logo" ).attr( "src", Settings.Logo );
});
-->

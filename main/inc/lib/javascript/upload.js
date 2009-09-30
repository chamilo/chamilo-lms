/**
 * JavaScript library to deal with file uploads
 * @author	Yannick Warnier <yannick.warnier@dokeos.com>
 */
/**
 * Upload class. Used to pack functions into one practical object.
 * Call like this: var myUpload = new upload(5);
 */
function upload(latency){
	/**
	 * Starts the timer
	 * Call like this:
	 * @param	string	Name of the DOM element we need to update
	 * @param	string	Loading image to display
	 * @return	true
	 */
	function start(domid,img,text,formid){
		__progress_bar_domid = domid;
		__progress_bar_img   = img;
		__progress_bar_text  = text;
		__progress_bar_interval = setTimeout(__display_progress_bar,latency,__progress_bar_text);
		__upload_form_domid  = formid;
	}

	/**
	 * Starts the timer of the real upload progress
	 */
	function startRealUpload(domid, upload_id, formid, waitAfterUpload){
		__progress_bar_domid = domid;
		__progress_bar_uploadid = upload_id;
		__progress_bar_waitAfterUpload = waitAfterUpload;
		__upload_form_domid  = formid;
		__progress_bar_interval = setInterval(__refreshUpload,latency);
		document.getElementById(domid+'_container').style.display = 'block';
		if(waitAfterUpload){
			document.getElementById(domid+'_waiter_container').style.display = 'block';
		}


	}

	/**
	 * Function called by a timer to update every x seconds the progress bar
	 */
	function __refreshUpload(){
		xajax_updateProgress(__progress_bar_domid, __progress_bar_uploadid, __progress_bar_waitAfterUpload);
	}

	/**
	 * Displays the progress bar in the given DOM element
	 */
	function __display_progress_bar(){
		var my_html ='<span style="font-style:italic;">'+ __progress_bar_text+'</span><br/><img src="'+__progress_bar_img+'" alt="'+__progress_bar_text+'"  title="'+__progress_bar_text+'"/>';
		document.getElementById(__progress_bar_domid).innerHTML = my_html;
		if(__upload_form_domid != ''){
			document.getElementById(__upload_form_domid).style.display = 'none';
		}
	}
	this.start = start;
	this.startRealUpload = startRealUpload;
	var __progress_bar_domid = '';
	var __progress_bar_img = '../img/progress_bar.gif';
	var __progress_bar_text = 'Uploading... Please wait';
	var __progress_bar_interval = 1;
	var __upload_form_domid = '';
}
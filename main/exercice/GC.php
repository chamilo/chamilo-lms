<?php
/* For licensing terms, see /license.txt */
/**
*	Code library for HotPotatoes integration.
*	@package chamilo.exercise
* 	@author Istvan Mandak
* 	@version $Id: GC.php 20451 2009-05-10 12:02:22Z ivantcholakov $
*/
/**
 * Code
 */

// usage:  HotPotGC($_configuration['root_sys'],$flag);
// working recursively, flag[0,1] print or delete the HotPotatoes temp files (.t.html)

echo "Garbage Collector<BR>";
HotPotGC($_configuration['root_sys'],1,1);


/**
 * functions
 */
/**
 * Garbage collector
 */
function HotPotGC($root_sys,$flag,$userID) {
	// flag[0,1] - print or delete the HotPotatoes temp files (.t.html)
	$documentPath = $root_sys."courses";
	require_once(api_get_path(LIBRARY_PATH)."fileManage.lib.php");
	HotPotGCt($documentPath,$flag,$userID);
}

function HotPotGCt($folder,$flag,$userID) { // Garbage Collector
	$filelist = array();
	    if ($dir = @opendir($folder)) {
        while (($file = readdir($dir)) !== false) {
            if ( $file != ".") {
            	if ($file != "..")
      		{
            	 $full_name = $folder."/".$file;
        	  if (is_dir($full_name))
							{
								HotPotGCt($folder."/".$file,$flag);
							}
						else
						 {
                $filelist[] = $file;
                 }
               }
            }
        }
      	closedir($dir);
    	}
	while (list ($key, $val) = each ($filelist))
	{
		 if (stristr($val,$userID.".t.html"))
		 { if ($flag == 1)
		 		{
						my_delete($folder."/".$val);
				}
			 else
			  {
			  	echo $folder."/".$val."<br />";
			  }
		 }
	}
}

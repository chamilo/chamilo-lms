<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


/**
*	Code library for HotPotatoes integration.
*	@package dokeos.exercise
* 	@author Istvan Mandak
* 	@version $Id: GC.php 20451 2009-05-10 12:02:22Z ivantcholakov $
*/


		// usage:  HotPotGC($_configuration['root_sys'],$flag);
		// working recursively, flag[0,1] print or delete the HotPotatoes temp files (.t.html)

		echo "Garbage Collector<BR>";
		HotPotGC($_configuration['root_sys'],1,1);


		// functions

		function HotPotGC($root_sys,$flag,$userID)
		{
			// flag[0,1] - print or delete the HotPotatoes temp files (.t.html)
			$documentPath = $root_sys."courses";
			require_once(api_get_path(LIBRARY_PATH)."fileManage.lib.php");
			HotPotGCt($documentPath,$flag,$userID);
		}

		function HotPotGCt($folder,$flag,$userID)
		{ // Garbage Collector
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


?>

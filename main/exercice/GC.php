<?php // $Id: GC.php 9246 2006-09-25 13:24:53Z bmol $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Istvan Mandak
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
*	Code library for HotPotatoes integration.
*
*	@author Istvan Mandak
*	@package dokeos.exercise
============================================================================== 
*/	
		
		// usage:  HotPotGC($rootSys,$flag);
		// working recursively, flag[0,1] print or delete the HotPotatoes temp files (.t.html)

		$rootSys = "C:/Program Files/EasyPHP1-7/www/dokeos_new/";
		echo "Garbage Collector<BR>";
		HotPotGC($rootSys,1,1);
		
		
		// functions 
	
		function HotPotGC($rootSys,$flag,$userID)
		{	// flag[0,1] - print or delete the HotPotatoes temp files (.t.html)
			$documentPath = $rootSys."courses";
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
					  	echo $folder."/".$val."<BR>";
					  }
				 }
			}
		}


?>
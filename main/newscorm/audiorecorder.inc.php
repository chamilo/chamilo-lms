<?php
/**
 * Created on 27.09.2006
 * Include the lazlo file necessary to use the audiorecorder  
 * @author Sebastian Wagner <seba.wagner@gmail.com>
 * @author Eric Marguin <e.marguin@elixir-interactive.com>
 * @author Arnaud Ligot <arnaud@cblue.be>
 */
 
 



list($width, $height) = $audio_recorder_studentview =='true' ? array(220, 28) : array(220,140);

$path_to_lzx = api_get_setting('service_ppt2lp','path_to_lzx');


if(!empty($path_to_lzx)){
	printf("<object type=\"application/x-shockwave-flash\" data=\"%s\" 
			width='$width' height='$height'>
	         <param name=\"movie\" value=\"%s\">
		 <param name=\"quality\" value=\"high\">
		 <param name=\"scale\" value=\"noscale\">
		 <param name=\"salign\" value=\"LT\">
		 <param name=\"menu\" value=\"false\"></object>",$path_to_lzx,$path_to_lzx);
}

?>

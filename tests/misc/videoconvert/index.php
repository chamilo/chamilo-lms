<?php
/**
 * SCRIPT UNDER DEVELOPMENT - DO NOT USE IN PRODUCTION
 * This script converts the uploaded file to webm or ogv and stores it
 * into the underlying upload/ directory. The objective of this script is to
 * serve as a base to create a video converter inside the Chamilo interface
 * as a plugin (because it is likely to require considerable CPU time to process
 * the uploaded videos)
 * @todo integrate as library and generate video tags to play the result
 * @package chamilo.misc.dev.video-converter
 * @requires ffmpeg installed on the server
 * @requires GNU/Linux
 * @requires Possibly requires the existence of specific codecs on the system
 */
/**
 * Initialization - removing all limits possibly affecting this process
 */
ini_set('memory_limit',0);
ini_set('max_execution_time',0);
ini_set('upload_max_filesize',0);
ini_set('post_max_size',0);
/**
 * Print form
 */
?>
<html>
<body>
<p>
<form method="post" action="" enctype="multipart/form-data">
<table>
<tr><td><label for="video"><?php echo get_lang('VideoToConvert');?></label></td><td><input type="file" name="video"/></td></tr>
<tr><td><label for="codec"><?php echo get_lang('VideoCodecWantedForConversionDest');?></td>
  <td>
    <select name="codec">
      <option value="webm" selected>WebM</option>
      <option value="ogv">OGV</option>
    </select>
  </td>
</tr>
<tr><td colspan="2"><input type="submit" name="convert" value="<?php echo get_lang('Convert'); ?>"></tr>
</table>
</form>
</p>
<p>
<?php
/**
 * Deal with uploaded files
 */
if (!empty($_FILES['video'])) {
  error_log($_FILES['video']['name']);
  $orig = dirname(__FILE__).'/upload/'.md5(uniqid(rand(),true)).'-'.$_FILES['video']['name'];
  $dest = dirname(__FILE__).'/upload/'.md5(uniqid(rand(),true)).'-'.substr($_FILES['video']['name'],0,-3).(($_POST['codec']!='ogv')?'webm':'ogv');
  error_log($dest);
  $res = @move_uploaded_file($_FILES['video']['tmp_name'],$orig);
  if ($res === false) { error_log("Error al mover el video: ".$php_error_msg); }
  error_log('Calling '.'ffmpeg -i '.$orig.' -acodec libvorbis -ac 2 -ab 96k -ar 44100 -b 345k -v quiet -s 1080x720 '.$dest);
  $ffmpeg = @exec('ffmpeg -i '.$orig.' -acodec libvorbis -ac 2 -ab 96k -ar 44100 -b 345k -v quiet -s 1080x720 '.$dest);
  if ($ffmpeg === false) { error_log('no'); }
}
?>
</p>
<p>
<?php
/**
 * Prints a list of files inside the upload directory (with links)
 */
echo get_lang('VideoFilesInFolder')."<br />";
$list = scandir(dirname(__FILE__).'/upload');
if (is_array($list)) {
  foreach ($list as $file) {
    if (substr($file,0,1) == '.') {
      continue;
    } else {
      echo '<a href="http://virtualdes.icpna.edu.pe/video/upload/'.$file.'">'.$file.'</a><br />'."\n";
    }
  }
}
?>
</p>
</body>
</html>

<?php
/* For license terms, see /license.txt */
/**
 * Script to convert videos to several formats
 * Put a comment before the exit(); line to enable.
 * Load in your browser with http(s)://[yourcampus]/tests/video/index.php
 * @todo Add security filtering for filenames
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
exit();
ini_set('memory_limit',0);
ini_set('max_execution_time',0);
ini_set('upload_max_filesize',0);
ini_set('post_max_size',0);
?>
<html>
<body>
<p>
<form method="post" action="" enctype="multipart/form-data">
<table>
<tr><td>Video to convert:</td><td><input type="file" name="video"/></td></tr>
<tr><td>Desired Codec:</td>
  <td>
    <select name="codec">
      <option value="webm" selected>WebM</option>
      <option value="ogv">OGV</option>
    </select>
  </td>
</tr>
<tr><td colspan="2"><input type="submit" name="Convert" value="Convert"></tr>
</table>
</form>
</p>
<p>
<?php
if (!empty($_FILES['video'])) {
  error_log($_FILES['video']['name']);
  $orig = __DIR__.'/upload/'.md5(uniqid(rand(),true)).'-'.$_FILES['video']['name'];
  $dest = __DIR__.'/upload/'.md5(uniqid(rand(),true)).'-'.substr($_FILES['video']['name'],0,-3).(($_POST['codec']!='ogv')?'webm':'ogv');
  error_log($dest);
  $res = @move_uploaded_file($_FILES['video']['tmp_name'],$orig);
  if ($res === false) { error_log("Error moving video file: ".$php_error_msg); }
  error_log('Calling '.'ffmpeg -i '.$orig.' -acodec libvorbis -ac 2 -ab 96k -ar 44100 -b 345k -v quiet -s 1080x720 '.$dest);
  $ffmpeg = @exec('ffmpeg -i '.$orig.' -acodec libvorbis -ac 2 -ab 96k -ar 44100 -b 345k -v quiet -s 1080x720 '.$dest);
  if ($ffmpeg === false) { error_log('no'); }
}
?>
</p>
<p>
<?php
echo "Files on server:<br />";
$list = scandir(__DIR__.'/upload');
if (is_array($list)) {
  foreach ($list as $file) {
    if (substr($file,0,1) == '.') {
      continue;
    } else {
      echo '<a href="upload/'.$file.'">'.$file.'</a><br />'."\n";
    }
  }
}
?>
</p>
</body>
</html>

<?php 
$extension = "csv";
if ($_POST['upload'])
{ 
	//process uploaded file
	$file = $_FILES['uploadfile']['name'];
	if(ltrim(strtolower(strrchr($file, ".")),".") == $extension)
	{
		$uploadDir = '../tmp/'; //file upload path 
    	$uploadFile = $uploadDir . $_FILES['uploadfile']['name']; 
    	if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $uploadFile) ) 
    	{ 
        	$row = 1;
			$handle = fopen($uploadFile, "r");
?>
<html>
<body>
<table border="1">
<?php
			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE)
			{
?>
<tr>
<?php
   				$num = count($data);
   				$row++;
   				for ($c=0; $c < $num; $c++)
				{
       				if($data[$c] == "")
					{
						
					}
					else
					{
						echo "<td>" . $data[$c] . "</td>";
					}
   				}
?>
</tr>
<?php
			}
?>
</table>
</body></html>
<?php
			fclose($handle);        	
        } 
    	else 
    	{ 
     		echo "ERROR!  Here's some debugging info:\n"; 
        	echo "remember to check valid path, max filesize\n"; 
    	}     
	}
	else
	{
		echo "The file has not the correct extension";
?>
<br><a href="upload.php">Back</a>
<?php
	}	  
}
else
{
?>
<html><body>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']?>" method="post"> 
    <input type="hidden" name="MAX_FILE_SIZE" value="1024000"> 
    <br> 
  <input name="uploadfile" type="file"> 
    <br> 
    <input name= "upload" type="submit" value="Upload File"> 
</form> 
</body></html>
<?php
}
?>

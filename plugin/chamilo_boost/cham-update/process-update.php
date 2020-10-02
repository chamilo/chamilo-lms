<?php

require_once __DIR__.'/../../../main/inc/global.inc.php';

echo date(DATE_RFC2822).'<br/>';

api_protect_admin_script();

if(!api_is_anonymous()){
  
    echo 'Is connected<br/>';
    
    $urlFold = base64_decode('aHR0cHM6Ly93d3cubHVkaXNjYXBlLmNvbS9jaGFtaWxvL3BsdWdpbnMv');

    if(isset($_GET["update"])){

        $url = $urlFold.'boost-depo/pathboost.zip'; 
        
        $ch = curl_init($url); 
        $dir = '../params/'; 
        $file_name = basename($url); 
        $save_zip_loc = $dir.$file_name; 
        $fp = fopen($save_zip_loc, 'wb'); 
        curl_setopt($ch, CURLOPT_FILE, $fp); 
        curl_setopt($ch, CURLOPT_HEADER, 0); 
        curl_exec($ch); 
        curl_close($ch); 
        fclose($fp);

        $Bversion = $_GET["update"];
        echo "<br/><span style='color:green;' >Update : Files are download !</span><br/><br/>";
        echo "<a href='process-update.php?install=".$Bversion."' style='color:green;' >Install all files</span></a><br/>";
        
    }else{

        if(isset($_GET["install"])){
            
            $urlPack = '../params/pathboost.zip';

            $zip = new ZipArchive;
            if ($zip->open($urlPack) === TRUE){
                $zip->extractTo('../');
                $zip->close();
                echo '<span style="color:green;font-weight:bold;" >Install is Finish</span>';
            } else {
                echo '<br/><span style="color:red;font-weight:bold;" >Install is KO</span><br/><br/>';
                echo "<a class='btn btn-primary' href='process-update.php?reset=1' style='color:red;' >RETRY ?</span></a><br/>";
            }

        }else{

            $url = $urlFold.'boost-depo/version.txt'; 
            
            $ch = curl_init($url); 
            
            $dir = '../params/'; 
    
            $file_name = basename($url); 
            
            $save_file_loc = $dir.$file_name; 
            
            if (file_exists($save_file_loc)) {
                unlink($save_file_loc);
            }

            $fp = fopen($save_file_loc, 'wb'); 
            curl_setopt($ch, CURLOPT_FILE, $fp); 
            curl_setopt($ch, CURLOPT_HEADER, 0); 
            curl_exec($ch); 
            curl_close($ch); 
            fclose($fp); 
            
            $fileNameParams = $save_file_loc;
            
            if(file_exists($fileNameParams)){

                try{

                    $xml = file_get_contents($fileNameParams);
                    if(trim($xml)==''){
                        echo "<a class='btn btn-primary' href='process-update.php?reset=1' style='color:red;' >RETRY</span></a><br/>";
                        die('No content<br/>');
                    }
                    $Bversion = $xml;
                    echo "<span style='color:orange;' ><br/>UPDATE : Version&nbsp;".$Bversion."</span><br/>";
                    echo "<a class='btn btn-primary' href='process-update.php?update=".$Bversion."' style='color:green;' >UPDATE NOW</span></a><br/>";
                
                }catch (exception $e){

                    echo "<a class='btn btn-primary' href='process-update.php?reset=1' style='color:red;' >RETRY !</span></a><br/>";
                    
                }
              
            }else{

                echo "<a class='btn btn-primary' href='process-update.php?reset=1' style='color:red;' >RETRY :-(</span></a><br/>";
            
            }

        }

    }

}else{
    echo 'Not connected<br/>';
}


?>
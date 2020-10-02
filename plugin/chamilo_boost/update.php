<?php

    $fileUrlId = __DIR__.'/params/params_url_id.html';
    
    if(!file_exists($fileUrlId)){
        $fd = fopen($fileUrlId,'w');	
        fwrite($fd,'2020');
        fclose($fd);
        $inSql = "ALTER TABLE boostTitle ADD url_id INT NOT NULL DEFAULT '1' AFTER idContent;";
        Database::query($inSql);
    }

?>
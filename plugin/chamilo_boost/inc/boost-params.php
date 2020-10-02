<?php
    
    $Btitle = $optionTitle;
	$Blogo = '';
	$BlogoTop = '';
	$Bextracode1 = '';
	$Bextracode2 = '';

	$BbtnSuscribe = 0;
	$BbtnBuy = 0;
	$BlinkBuy = '';
	$BlabelSuscribe = '';
	$BlabelBuy = '';
    $BactiveSearch = 0;
    $BactiveSkills = 0;
    $Bstylecourses = 0;
    $Btopnavigationoff = 0;

    $BColor1 = '#23282e';
    $BColor2 = '#2e353d';
    $BColorText = '#e1ffff';
    $BtopnavigationColor = 0;

    if(!isset($_SESSION['Btitle'.$urlIdFinal])
    ||!isset($_SESSION['Blogo'.$urlIdFinal])
    ||!isset($_SESSION['BlogoTop'.$urlIdFinal])){

        $urlIdFinal = api_get_current_access_url_id();
        if($urlIdFinal==1){$urlIdFinal = '';}

        $fileNameParams = __DIR__.'/../params/params'.$urlIdFinal.'.xml';
        
        if(file_exists($fileNameParams)){
            
            $xml = simplexml_load_file($fileNameParams);
            
            $config = ['rows' => '10'];
            
            $Btitle = $xml->param[0]->title ;
            $_SESSION['Btitle'.$urlIdFinal] = (string)$Btitle;

            $Blogo = $xml->param[0]->logo ;
            $BlogoTop =  $xml->param[0]->logotop ;
            
            $_SESSION['Blogo'.$urlIdFinal] = (string)$Blogo;
            $_SESSION['BlogoTop'.$urlIdFinal] = (string)$BlogoTop;

            $Bextracode1 =  $xml->param[0]->extracode1;
            $Bextracode2 =  $xml->param[0]->extracode2;

            $_SESSION['Bextracode1'.$urlIdFinal] = (string)$Bextracode1;
            $_SESSION['Bextracode2'.$urlIdFinal] = (string)$Bextracode2;

            $BactiveSearch = intVal($xml->param[0]->activeSearch);
            $BactiveSkills = intVal($xml->param[0]->activeSkills);

            $_SESSION['BactiveSearch'.$urlIdFinal] = (string)$BactiveSearch;
            $_SESSION['BactiveSkills'.$urlIdFinal] = (string)$BactiveSkills;

            $BbtnSuscribe = intVal($xml->param[0]->btnSuscribe);
            $BlabelSuscribe = $xml->param[0]->labelSuscribe;
            
            $_SESSION['BbtnSuscribe'.$urlIdFinal] = (string)$BlabelSuscribe;
            $_SESSION['BlabelSuscribe'.$urlIdFinal] = (string)$BlabelSuscribe;

            $BbtnBuy = intVal($xml->param[0]->btnBuy);
            $BlabelBuy = $xml->param[0]->labelBuy;

            $_SESSION['BbtnBuy'.$urlIdFinal] = (string)$BbtnBuy;
            $_SESSION['BlabelBuy'.$urlIdFinal] = (string)$BlabelBuy;

            $BlinkBuy =  $xml->param[0]->linkBuy;
            
            $_SESSION['BlinkBuy'.$urlIdFinal] = (string)$BlinkBuy;

            $BlateralMenu = intVal($xml->param[0]->lateralMenu);
            $_SESSION['BlateralMenu'.$urlIdFinal] = (string)$BlateralMenu;

            $Bstylecourses = intVal($xml->param[0]->stylecourses);
            $_SESSION['Bstylecourses'.$urlIdFinal] = (string)$Bstylecourses;

            //Elements du menu

            $fileNameMenu = __DIR__.'/../params/menu'.$urlIdFinal.'.xml';
            $xmlMenu = simplexml_load_file($fileNameMenu);
            
            $Btopnavigationoff = intVal($xmlMenu->param[0]->topnavigationoff);
            $_SESSION['Btopnavigationoff'.$urlIdFinal] = (string)$Btopnavigationoff;

            $BColor1 = $xmlMenu->param[0]->Color1;
            $_SESSION['BColor1'.$urlIdFinal] = (string)$BColor1;
            $BColor2 = $xmlMenu->param[0]->Color2;
            $_SESSION['BColor2'.$urlIdFinal] = (string)$BColor2;

            $BColorText = $xmlMenu->param[0]->ColorText;
            $_SESSION['BColorText'.$urlIdFinal] = (string)$BColorText;

            $BtopnavigationColor = intVal($xmlMenu->param[0]->topnavigationColor);
            $_SESSION['BtopnavigationColor'.$urlIdFinal] = (string)$BtopnavigationColor;

        }

    }else{

        if(isset($_SESSION['Btitle'.$urlIdFinal])){
            $Btitle = $_SESSION['Btitle'.$urlIdFinal];
        }else{
            unset($_SESSION['Btitle'.$urlIdFinal]);
        }

        if(isset($_SESSION['Blogo'.$urlIdFinal])){
            $Blogo = $_SESSION['Blogo'.$urlIdFinal];
        }else{
            unset($_SESSION['Btitle'.$urlIdFinal]);
        }

        if(isset($_SESSION['BlogoTop'.$urlIdFinal])){
            $BlogoTop = $_SESSION['BlogoTop'.$urlIdFinal];
        }else{
            unset($_SESSION['Btitle'.$urlIdFinal]);
        }

        if(isset($_SESSION['Bextracode1'.$urlIdFinal])){ $Bextracode1 =  $_SESSION['Bextracode1'.$urlIdFinal];}
        if(isset($_SESSION['Bextracode2'.$urlIdFinal])){$Bextracode2 =  $_SESSION['Bextracode2'.$urlIdFinal];}

        if(isset($_SESSION['BactiveSearch'.$urlIdFinal])){$BactiveSearch =  $_SESSION['BactiveSearch'.$urlIdFinal];}
        if(isset($_SESSION['BactiveSkills'.$urlIdFinal])){$BactiveSkills = $_SESSION['BactiveSkills'.$urlIdFinal];}
        if(isset($_SESSION['BbtnSuscribe'.$urlIdFinal])){$BlabelSuscribe = $_SESSION['BbtnSuscribe'.$urlIdFinal];}
        if(isset($_SESSION['BlabelSuscribe'.$urlIdFinal])){$BlabelSuscribe = $_SESSION['BlabelSuscribe'.$urlIdFinal];}
        if(isset($_SESSION['BbtnBuy'.$urlIdFinal])){$BbtnBuy = $_SESSION['BbtnBuy'.$urlIdFinal];}
        if(isset($_SESSION['BlabelBuy'.$urlIdFinal])){$BlabelBuy = $_SESSION['BlabelBuy'.$urlIdFinal];}
        if(isset($_SESSION['BlinkBuy'.$urlIdFinal])){$BlinkBuy = $_SESSION['BlinkBuy'.$urlIdFinal];}
        if(isset($_SESSION['BlateralMenu'.$urlIdFinal])){$BlateralMenu = $_SESSION['BlateralMenu'.$urlIdFinal];}
        if(isset($_SESSION['Bstylecourses'.$urlIdFinal])){$Bstylecourses = $_SESSION['Bstylecourses'.$urlIdFinal];}

        if(isset($_SESSION['Btopnavigationoff'.$urlIdFinal])){$Btopnavigationoff = $_SESSION['Btopnavigationoff'.$urlIdFinal];}

        if(isset($_SESSION['BColor1'.$urlIdFinal])){$BColor1 = $_SESSION['BColor1'.$urlIdFinal];}
        if(isset($_SESSION['BColor2'.$urlIdFinal])){$BColor2 = $_SESSION['BColor2'.$urlIdFinal];}
        if(isset($_SESSION['BColorText'.$urlIdFinal])){$BColorText = $_SESSION['BColorText'.$urlIdFinal];}
        if(isset($_SESSION['BtopnavigationColor'.$urlIdFinal])){$BtopnavigationColor = $_SESSION['BtopnavigationColor'.$urlIdFinal];}

    }
<?php

function saveRenderMenu($topTitle,$itemsList,$HavetopLogin,$HavetopLogo,$fileLogo,$messageBottom,$urlIdFinal)
{
    $logs = '';
    $mBodyOffline = '';
    $mBodyOnline = '';
    $mBodyAdmin = '';
    $mBodyTeacher = '';

    $head = '';

    $messageBody = '';
    if($messageBottom!=''){
        $messageBody = '<div class="nav-side-message-boost" >'.$messageBottom.'</div>';
    }

    if($HavetopLogo){
        $head .= '<div class="brand" style="padding-top:5px;" >';
        if($fileLogo==''){
            $head .= '<img src="'.api_get_path(WEB_PATH).'plugin/chamilo_boost/resources/img/logo-128.png">'."\n";
        }else{
            $head .= '<img src="'.api_get_path(WEB_PATH).'plugin/chamilo_boost/img/'.$fileLogo.'">'."\n";
        }
        $head .= '</div>';
    }

    if($topTitle!=''){
        $head .= '<div class="brand">'.$topTitle.'</div>'."\n";
    }
    
    if($HavetopLogin){

        $head .= '<div class="brand-login" >';
        $head .= '<a class="contain_message_boost" href="'.api_get_path(WEB_PATH).'main/messages/inbox.php" >';
        $head .= '<span class="count_message_boost badge badge-warning boost-message">&nbsp;&nbsp;</span>';
        $head .= '</a>';

        $head .= '<img class="boost-circle-user-login" ';
        $head .= ' src="'.api_get_path(WEB_PATH).'plugin/chamilo_boost/resources/img/unknown.png">'."\n";
        $head .= '<div class="boost-name-user" >Login</div>';

        $head .= '<a class="boost-logout" ';
        $head .= ' href="'.api_get_path(WEB_PATH).'index.php?logout=logout" >';
        $head .= '<em class="fa fa-sign-out"></em></a>';

        $head .= '</div>';
        
    }

    $head .= $messageBody;

    $head .= '<div class="menu-list" >'."\n";
    $head .= '<ul id="menu-content" class="menu-content collapse out">'."\n";

    $mBodyOffline = $head;
    $mBodyOnline = $head;
    $mBodyAdmin = $head;
    $mBodyTeacher = $head;

    $collMenu = prepareMenusItems($itemsList);
    $indexCtn = 1;

    foreach ($collMenu as &$menuItem){

        $valIcon = $menuItem['icon'];
        $valName = $menuItem['name'];
        $valLink =  $menuItem['link'];
        $valParams = $menuItem['params'];
        $isTopMenu = $menuItem['istopmenu'];
        $isSubMenu = $menuItem['issubmenu'];
        $isCloseMenu = $menuItem['closemenu'];

        $logs .= $valName.'istopmenu='.$isTopMenu.'issubmenu='.$isSubMenu.'closemenu='.$isCloseMenu."\n"; 

        $mBlock = '';

        if($isCloseMenu==1){
            $mBlock .=  '</ul>'."\n";
        }

        if($isTopMenu==0||$isSubMenu==1){

            $mBlock .= '<li>'."\n";
               
        }else{ 
      
            $valLink = '#';
            $mBlock .= '<li data-toggle="collapse" data-target="#service'.$indexCtn.'" class="collapsed" >'."\n";

        }

        $mBlock .= '<a href="'.$valLink.' " >'."\n";
        
        if($valIcon!=''&&$isSubMenu==0){
            $mBlock .= '<i class="fa '.$valIcon.' fa-lg"></i>'."\n";
        }
        
        $mBlock .= ' '.$valName;
        
        if($isTopMenu==1){
            $mBlock .= '<span class="arrow"></span>';
        }

        $mBlock .= '</a>'."\n";
        $mBlock .= '</li>'."\n";

        if($isTopMenu==1){
            $mBlock .= ' <ul class="sub-menu collapse" id="service'.$indexCtn.'" aria-expanded="false" >'."\n";
        }

        if($valLink=='#course-list'){
            $mBlock = '<course-list-data-load>';
        }
        if($valLink=='#session-list'){
            $mBlock = '<session-list-data-load>';
        }
        
        if($valLink=='#final-close'||$valName=='#final-close'){
            $mBlock = '</ul>';
        }
        
        if($valParams==''||rightOfParams($valParams,'nologin')){
            $mBodyOffline .= $mBlock;
        }
        if($valParams==''||rightOfParams($valParams,'havelogin')){
            $mBodyOnline .= $mBlock;
        }
        if($valParams==''||rightOfParams($valParams,'adminonly')||rightOfParams($valParams,'havelogin')){
            $mBodyAdmin .= $mBlock;
        }
        if($valParams==''||rightOfParams($valParams,'teacheronly')||rightOfParams($valParams,'havelogin')){
            $mBodyTeacher .= $mBlock;
        }
        $indexCtn = $indexCtn + 1;
        
    }

    $mBodyOffline .= '</ul></div>';
    $mBodyOnline .= '</ul></div>';
    $mBodyAdmin .= '</ul></div>';
    $mBodyTeacher .= '</ul></div>';
    
    $CacheMenu = api_get_path(SYS_PLUGIN_PATH).'/chamilo_boost/params/menu'.$urlIdFinal.'.html';
    $fd = fopen($CacheMenu,'w');	
    fwrite($fd,$mBodyOffline);
    fclose($fd);

    $CacheMenu = api_get_path(SYS_PLUGIN_PATH).'/chamilo_boost/params/menuonline'.$urlIdFinal.'.html';
    $fd = fopen($CacheMenu,'w');	
    fwrite($fd,$mBodyOnline);
    fclose($fd);

    $CacheMenu = api_get_path(SYS_PLUGIN_PATH).'/chamilo_boost/params/menuonadmin'.$urlIdFinal.'.html';
    $fd = fopen($CacheMenu,'w');	
    fwrite($fd,$mBodyAdmin);
    fclose($fd);

    $CacheMenu = api_get_path(SYS_PLUGIN_PATH).'/chamilo_boost/params/menuonteacher'.$urlIdFinal.'.html';
    $fd = fopen($CacheMenu,'w');	
    fwrite($fd,$mBodyTeacher);
    fclose($fd);

    $CacheMenu = api_get_path(SYS_PLUGIN_PATH).'/chamilo_boost/params/logs'.$urlIdFinal.'.txt';
    $fd = fopen($CacheMenu,'w');	
    fwrite($fd,$logs);
    fclose($fd);

    unset($_SESSION['RenderMenuBoost'.$urlIdFinal]);

}

function prepareMenusItems($itemsList){
    
    $result = array();

    $levelChild = 0;
    $itemsList = $itemsList.'||';
    $elements = explode('|',$itemsList);
    
    $cnt = count($elements);
    
    $indexCtn = 0;

    $prevIssubmenu = 0;

    for ($i=0;$i<$cnt;$i++) {

        $ItemsStr = $elements[$i];

        if($ItemsStr!=''){

            $valObj = explode('@',$ItemsStr);

            $valIcon = $valObj[0];
            $valName = $valObj[1];
            $valLink = correctUrlLink($valObj[2]);
            $valParams = $valObj[3];

            if($valName!=''){

                $result[$indexCtn] = array(
                    'icon' =>  $valIcon,
                    'name' => $valName,
                    'link' => $valLink,
                    'params' => $valParams,
                    'istopmenu' => 0,
                    'issubmenu' => 0,
                    'closemenu' => 0
                );

                $posIsSubMenu= strrpos($valName,"--");
                if($posIsSubMenu===false){
                    $result[$indexCtn]['issubmenu'] = 0;
                }else{
                    $result[$indexCtn]['issubmenu'] = 1;
                }

                $valName2 = '';
                
                $NextItemsStr = $elements[$i+1];

                if($NextItemsStr!=''){
                    
                    $valObj2 = explode('@',$NextItemsStr);
                    $valName2 = $valObj2[1];

                    if($valName2!=''){
                        
                        $NextposCtr = strrpos($valName2,"--");

                        if($NextposCtr===false){
                            
                        }else{

                            if($result[$indexCtn]['issubmenu']==0){
                                $result[$indexCtn]['istopmenu'] = 1;
                            }

                        }

                    }

                }

                if($result[$indexCtn]['issubmenu']==0&&$prevIssubmenu==1){
                    $result[$indexCtn]['closemenu'] = 1;
                }

                $valName = str_replace("-- ",'', $valName);
                $valName = str_replace("--",'', $valName);
                
                $result[$indexCtn]['name'] = $valName;
                
                $prevIssubmenu = $result[$indexCtn]['issubmenu'];

                $indexCtn = $indexCtn + 1;

            }
           
        }

    }

    $result[$indexCtn] = array(
        'icon' =>  '#final-close',
        'name' => '#final-close',
        'link' => '#final-close',
        'params' => '',
        'istopmenu' => 0,'issubmenu' => 0,'closemenu' => 0
    );

    return $result;

}

function rightOfParams($mystring,$search){
	$pos = strrpos($mystring,$search);
	if($pos=== false){
		return false;
	}else{
		return true;
	}
}

function correctUrlLink($valLink){
    
    if($valLink=='#home'){
        $valLink = api_get_path(WEB_PATH).'index.php';
    }
    if($valLink=='#course'||$valLink=='#courses'){
        $valLink = api_get_path(WEB_PATH).'user_portal.php?nosession=true';
    }
    if($valLink=='#stat'||$valLink=='#stats'){
        $valLink = api_get_path(WEB_PATH).'main/mySpace/';
    }

    if($valLink=='#admin-userslist'){
        $valLink = api_get_path(WEB_PATH).'main/admin/user_list.php';
    }
    if($valLink=='#admin-courseslist'){
        $valLink = api_get_path(WEB_PATH).'main/admin/course_list.php';
    }
    if($valLink=='#admin-skillviewer'){
        $valLink = api_get_path(WEB_PATH).'plugin/chamilo_boost_skill_view/index.php';
    }
    
    return $valLink;
}

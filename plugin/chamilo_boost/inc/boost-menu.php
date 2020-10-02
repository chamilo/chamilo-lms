<?php

    $mBody = '<div id="side-menu-boost" class="nav-side-menu-boost" >';
    
    if(isset($_SESSION['RenderMenuBoost'.$urlIdFinal])){

        $mBody .= $_SESSION['RenderMenuBoost'.$urlIdFinal];
        
    }else{

        $fileNameMenu = __DIR__.'/../params/menu'.$urlIdFinal.'.html';

        if(!api_is_anonymous()){
            
            $fileNameMenu = __DIR__.'/../params/menuonline'.$urlIdFinal.'.html';

            if($userStatus=='ADMIN'){
                $fileNameMenu = __DIR__.'/../params/menuonadmin'.$urlIdFinal.'.html';
            }
            if($userStatus=='TEACHER'){
                $fileNameMenu = __DIR__.'/../params/menuonteacher'.$urlIdFinal.'.html';
            }

        }

        if(file_exists($fileNameMenu)){
            
            $RenderMenu = file_get_contents($fileNameMenu);
            
            if(isset($_SESSION['CoursesProgressList'.$urlIdFinal])){

                $progress = $_SESSION['CoursesProgressList'.$urlIdFinal];

                $listMenu = '';

                foreach ($progress as &$row){
                    
                    if($row['code']!=''){
                        $posCtr = strrpos($row['code'],"SESSION-");
                        if($posCtr===false){
                            $Folder = $row['directory'];
                            $sessionid = $row['sessionid'];
                            if(!isset($sessionid)){$sessionid = 0;}
                            if($sessionid==''){$sessionid = 0;}
                            if($sessionid==null){$sessionid = 0;}
                            $valLink = api_get_path(WEB_PATH).'courses/'.$Folder.'/index.php?id_session='.$sessionid;
                            $listMenu .= '<li><a href="'.$valLink.' " >'.$row['title'].'</a></li>';  
                        }
                    }

                }

                $RenderMenu = str_replace("<course-list-data-load>",$listMenu, $RenderMenu);
                
                $listMenu = '';

                foreach ($progress as &$row){

                    if($row['code']!=''){
                        $posCtr = strrpos($row['code'],"SESSION-");
                        if($posCtr===false){}else{
                            $Folder = $row['directory'];
                            $valLink = api_get_path(WEB_PATH).'main/session/index.php?session_id='.$Folder;
                            $listMenu .= '<li><a href="'.$valLink.' " >'.$row['title'].'</a>';
                            $listMenu .= getsubSessionCourseList($progress,$Folder);
                            $listMenu .= '</li>';
                        }
                    }

                }

                $RenderMenu = str_replace("<session-list-data-load>",$listMenu, $RenderMenu);

            }else{
                $RenderMenu = str_replace("<course-list-data-load>",'', $RenderMenu);
                $RenderMenu = str_replace("<session-list-data-load>",'', $RenderMenu);
            }
            
            $_SESSION['RenderMenuBoost'.$urlIdFinal] = (string)$RenderMenu;
            $mBody .= $RenderMenu;
    
        }else{
    
            $mBody .= '<div class="brand">Brand Logo</div>';
    
            $mBody .= '<i class="fa fa-bars fa-2x toggle-btn" data-toggle="collapse" data-target="#menu-content"></i>';
            
            $mBody .= '<div class="menu-list">';
            
            $mBody .= '<ul id="menu-content" class="menu-content collapse out">';
        
            $mBody .= '<li>';
            $mBody .= '<a href="#"><i class="fa fa-dashboard fa-lg"></i> Dashboard</a>';
            $mBody .= '</li>';
        
            $mBody .= '</ul>';
        
            $mBody .= '</div>';
            
        }

    }

    $mBody .= '</div>';

?>
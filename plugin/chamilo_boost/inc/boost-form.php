<?php

    function getFormTitle($action,$id,$interface,$plugin){

        $pluginTrad = chamilo_boost::create();

        $form = new FormValidator('dictionary','post',api_get_self().'?action='.$action.'&id='.$id);
        
        if(empty($defaults)){
            $defaults =array(
              "imagePic" => "defaut.jpg",
              "typeCard" => "demo.html",
              "acces"  => "onc",
            );
        }
        
        //Liste of content
        $optionsPages = array(
            'cards'  => $pluginTrad->get_lang('typeCard'),
            'catalog'  => $pluginTrad->get_lang('catalog'),
            'video'  => $pluginTrad->get_lang('videoCard'),
            'stats'  => $pluginTrad->get_lang('statCard'),
            'statstable'  => $pluginTrad->get_lang('statTable'),
            'link'  => $pluginTrad->get_lang('linkCard'),
            'loadpagecontent@home'  => $pluginTrad->get_lang('homepagecontent'),
            'texthtml' => $pluginTrad->get_lang('htmlCard')
        );
        
        //Liste of publish
        $optionsPublish = array(
            'onc' => $pluginTrad->get_lang('onlynoconnection'),
            'oc' => $pluginTrad->get_lang('onlyconnection'),
            'both' =>  $pluginTrad->get_lang('allconnection')
        );
        

        $dir = opendir('resources/templates/'.$interface.'/contents'); 
        while($file = readdir($dir)){
            if(!is_dir($dir.$file)){
                $nam = $file;
                if(indexOf($nam,'.html')){
                    $optionsPages[$nam] = $nam;
                }
            }
        }

        $select = $form->addElement('select','typeCard',$pluginTrad->get_lang('selectTypeCard').'<br><a target="_blank" href="https://chamilo-lms.com/documentation/chamilo-boost-ecran-daccueil" >Aide</a>', $optionsPages);

        if($defaults['typeCard']==''){
            $select->setSelected($defaults['typeCard']);
        }else{
            $select->setSelected('demo.html');
        }

        $form->addText('title',get_lang('Title'),true);
        $form->addText('subTitle',get_lang('SubTitle'),false);

        $form->addText('indexTitle','Index',false);

        $options = array('defaut.jpg'=>'defaut.jpg',);
        
        //Liste des rapports
        $folderInterface = 'resources/templates/'.$interface.'/img';
        $dir = opendir($folderInterface); 
        
        while($file = readdir($dir)){
            if(!is_dir($dir.$file)){
                $nam = $file;
                if(indexOf($nam,'.jpg')){
                    $options[$nam] = $nam;
                }
                if(indexOf($nam,'.png')){
                    $options[$nam] = $nam;
                }
                if(indexOf($nam,'.gif')){
                    $options[$nam] = $nam;
                }
            }
        }
        
        $form->addText('imagePic','imagePic',false);
        $form->addText('imageUrl','imageUrl',false);
        
        //overview
        $cH = '<div class="thecard" data="ec5U4eSb8MQ" type="video" ';
        $cH .= ' style="position:relative;margin-left:0px;margin-top:13px;" >';
            
            $cH .= '<div class="card-img">';
                
                $cH .= '<div class="back-img" id="o-backimg" ';
                $cH .= 'style="background-image: url(resources/templates/localhost/img/defaut.jpg);" >';
                $cH .= '</div>';
            
            $cH .= '</div>';

            $cH .= '<div class="card-caption"> ';
                $cH .= '<i id="like-btn" class="fa fa-bars"></i> ';
                $cH .= '<h2 id="o-title" >Présentation</h2>';
            $cH .= '</div>';
    
                $cH .= '<div class="card-outmore"> ';
                    $cH .= '<h5 id="o-subtitle" >Voir</h5> ';
                    $cH .= '<i id="outmore-icon" class="fa fa-angle-right"></i> ';
                $cH .= '</div>';
            
        $cH .= '</div>';
        
        //RIGHT EDIT
        $cH .= '<div style="position:absolute;left:230px;top:5px;';
        $cH .= 'width:240px;height:185px;border-left:solid 1px gray;" >';
        
        $cH .= '<table style="width:90%;margin:5px;" >';
        
        $cH .= '<tr style="border-bottom:solid 3px white;"  >';
        $cH .= '<td style="text-align:right;padding-right:5px;" >Position</td><td>';
        $cH .= '<input onChange="matchOuput()" onKeyPress="matchOuput()" id="rel_indexTitle" min=0 max=100 type="number" ';
        $cH .= ' style="padding:5px;width:80px;text-align:center;" />';
        $cH .= '</td></tr>';
        
        $cH .= '<tr>';
        $cH .= '<td style="text-align:right;padding-right:5px;" >Image</td><td>';
        $cH .= '<img style="cursor:pointer;" onClick="showOverviewSelect()" src="resources/css/select-img.png" />';
        $cH .= '</td></tr>';

        $cH .= '</table>';
        
        $cH .= '</div>';

        $cH .= '<div class="thecardview thecardviewLT theCardViewOver"';
        $cH .= ' style="margin-left:-30px;margin-top:210px;display:block;position:relative;width:657px; height: 289px;">';
        $cH .= '</div>';

        //Image Windows for selection
        $cH .= '<div class="imagesOverviewSelect" >';
       
        $cH .= '<div class="imagesOverviewMain" >';
        foreach ($options as &$img){
            $cH .= '<div class="imagesOverviewOne" onClick="selectImgP(\''.$img.'\')" ';
            $cH .= ' style="background-image:url(\''.$folderInterface.'/'.$img.'\')" ></div>';
        }
        $cH .= '</div>';
        
        $cH .= '<div class="imagesOverviewClose" onClick="closeOverviewSelect()" ></div>';

        $cH .= '</div>';


        $form->addElement('static', '', '', $cH);	
        
        //Where is publish	
       
        $select = $form->addElement('select','acces',$pluginTrad->get_lang('Visiblecard'), $optionsPublish);
        if($defaults['acces']==''){
            $select->setSelected($defaults['acces']);
        }else{
            $select->setSelected('onc');
        }
        
        $form->addText('idContent','Id Youtube',false);
        
        $form->addHtmlEditor('leftContent',$pluginTrad->get_lang('left'), false, false, ['ToolbarSet' => 'Work'], true);
        
        $form->addHtmlEditor('rightContent', $pluginTrad->get_lang('right'), false, false, ['ToolbarSet' => 'Minimal'], true);
        
        $form->addFile('picture','Ajout Jpg 250*250',
            array('id' => 'picture', 'class' => 'picture-form')
        );
        
        $form->addProgress();
        
        $allowed_picture_types = api_get_supported_image_extensions(false);
        
        $form->addRule(
            'picture',
            get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowed_picture_types).')',
            'filetype',
            $allowed_picture_types
        );
        
        $form->addButtonSave(get_lang('Save'));
        
        return $form;

    }


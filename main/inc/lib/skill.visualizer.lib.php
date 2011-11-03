<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

 class SkillVisualizer {

    private $offsetX = 400;
    private $offsetY = 0;
   
    private $html    = '';
    private $type    = 'read';
    private $js      = '';
       
    function __construct($skills, $type = 'read') {
        $this->skills = $skills;       
        $this->type = $type; 
    }
    
    function prepare_skill_box($skill, $position, $class) {
        $block_id = $skill['id'];
        $this->html .= '<div id="block_'.$block_id.'" class="open_block window '.$class.'" style="top:' . $position['y'] . 'px; left:' . $position['x'] . 'px;">';
        $gradebook_string = '';
        if (!empty($skill['gradebooks'])) {
            foreach($skill['gradebooks'] as $gradebook) {
                $gradebook_string .= Display::span($gradebook['name'], array('class'=>'label_tag notice','style'=>'width:50px')).'<br />';    
            }
        }        
        $skill['name'] = Display::url($skill['name'], '#', array('id'=>'edit_block_'.$block_id, 'class'=>'edit_block'));
        
        $this->html .= $skill['name'].' '.$gradebook_string;
        
        if ($this->type == 'edit' && $skill['parent_id'] != 0) {
            //$this->html .= Display::url(Display::return_icon('edit.png', get_lang('Edit'), array(), 22), '#', array('id'=>'edit_block_'.$block_id,'class'=>'edit_block'));
            //$this->html .= Display::url(Display::return_icon('add.png', get_lang('Add'), array(), 22), '#', array('id'=>'edit_block_'.$block_id,'class'=>'edit_block'));
            //$this->html .= Display::url(Display::return_icon('delete.png', get_lang('Delete'), array(), 22), '#', array('id=>"edit_block_'.$block_id,'class'=>'edit_block'));            
            //$this->html .= Display::url(Display::return_icon('up.png', get_lang('Close'), array(), 22), '#', array('id'=>'close_block_'.$block_id,'class'=>'close_block'));
            
            //$this->html .= Display::url(Display::return_icon('down.png', get_lang('Open'), array(), 22), '#', array('id'=>'open_block_'.$block_id,'class'=>'open_block'));
        }
        $this->html .= '</div>';   
    }
    
    /**
     * Adds a node using jplumb
     */
    private function add_item($skill, $position) {
        $block_id = $skill['id'];                        
       
        $end_point = 'readEndpoint';
        //var_dump($skill);
        $class = 'default_window';
        
        if ($this->type == 'edit') {
            $end_point = 'editEndpoint';
            $class = 'edit_window';
        } else {
            if ($skill['done_by_user'] == 1) {
                $end_point = 'doneEndpoint';
                $class = 'done_window';
            } else {
                $end_point = 'defaultEndpoint';
            }
        } 
        $this->prepare_skill_box($skill, $position, $class);
        
        if ($skill['parent_id'] == 0) {
            return;
        }

       //default_arrow_color
        
       $this->js .= 'var e'.$block_id.' = prepare("block_' . $block_id.'",  '.$end_point.');'."\n";
       $this->js .= 'var e'.$skill['parent_id'].' = prepare("block_' . $skill['parent_id'].'",  '.$end_point.');'."\n";;
                        
       $this->js .= 'jsPlumb.connect({source: e'.$block_id.', target:e'.$skill['parent_id'].'});'."\n";;
    }
    
    /**
     * Displays the HTMl part of jplumb
     */
    public function display_html() {
        if (empty($this->skills)) {
            return '';
        }
        $skill_count = sizeof($this->skills);
        //$corner = 360 / $skill_count;   
        $count = 0;
        
        $brothers = array();
        
        $constant = 100;
        
        $canvas = 1000;
        
        //$this->add_item($skill, array('x' => $x + $this->offsetX, 'y' => $y + $this->offsetY));        
        foreach ($this->skills as &$skill) {
            if (!in_array($skill['parent_id'], array(0,1))) {
                continue;
            }
            $childs = isset($skill['children']) ? count($skill['children']) : 0 ;   
            
              
            //$x = round($this->offsetX * sin(deg2rad($corner * $count)));
            //$y = round($this->offsetY * cos(deg2rad($corner * $count)));
            
            /*if (isset($brothers[$skill['parent_id']])) {
                $brothers[$skill['parent_id']] +=2;
            } else {
                $brothers[$skill['parent_id']] = 1;
            }*/
           $my_count = 0;
           $brother_count = $brothers[$skill['id']];
           
            $parent_x = 0; 
            if ($skill['parent_id'] == 0) {
                
                $x = $constant*$childs/2;
                //$this->offsetX = $constant*$childs;     
            } else {
                
                $max = isset($this->skills[$skill['parent_id']]['children']) ? count($this->skills[$skill['parent_id']]['children']) : 0;
                foreach($this->skills[$skill['parent_id']]['children'] as  $id => $sk) {                    
                    if ($skill['id'] == $sk['id']) {                                                
                        break;
                    }
                    $my_count++;                        
                }
                $parent_x = isset($this->skills[$skill['parent_id']]['x']) ? $this->skills[$skill['parent_id']]['x'] : 0;
                $x = $my_count*150 + $parent_x - (150*$max/2) ;
                //$x = $my_count*150 + $parent_x - (150*$max/2) -  20*$childs;
                //$x = $my_count*150 + $parent_x - 100*$childs;
            }
                    
            $y = $skill['level']*150;   
              
            $skill['x'] = $x;
            $skill['y'] = $y;
            
                 
        //    var_dump($skill);                   
            //$skill['description']  = "{$brothers[$skill['parent_id']]} $x - $y";
            //$skill['name']  =  $skill['name']."  |  $x = $my_count * 150  +  $parent_x - (150* $max/2) - 10*$childs ";
            $this->add_item($skill, array('x' => $this->offsetX + $x, 'y' => $this->offsetY +$y));            
        }
                
        echo $this->get_html();
        
    }
    
    /**
     * Displays the Javascript part of jplumb
     */
    public function display_js() {
        echo $this->get_js();
    }  
   

    private function get_html() {
        return $this->html;
    }
   
    private function get_js() {
        return $this->js;
    }
}
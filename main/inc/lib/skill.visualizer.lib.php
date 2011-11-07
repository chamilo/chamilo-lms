<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

 class SkillVisualizer {
    
    public $block_size = 80; //see CSS window class
    public $canvas_x   = 1024;
    public $canvas_y   = 800;
    
    public $offset_x   = 0;
    public $offset_y   = 50;
    
    public $space_between_blocks_x = 100;
    public $space_between_blocks_y = 150;
    
    public $center_x = null;
   
    private $html    = '';
    private $type    = 'read';
    private $js      = '';
       
    function __construct($skills, $type = 'read') {
        $this->skills   = $skills;       
        $this->type     = $type; 
        $this->center_x = intval($offset_x + $this->canvas_x/2 - $this->block_size/2); 
    }
    
    function prepare_skill_box($skill, $position, $class) {
        $block_id = $skill['id'];
        
        $extra_class = 'third_window';
        if ($skill['parent_id'] == 0) {
            $extra_class = 'second_window';
        }
        
        $this->html .= '<div id="block_'.$block_id.'" class = " open_block window '.$extra_class.'  '.$class.'" style = "top:' . $position['y'] . 'px; left:' . $position['x'] . 'px;">';
        $gradebook_string = '';
        if (!empty($skill['gradebooks'])) {
            foreach ($skill['gradebooks'] as $gradebook) {
                //uncomment this to show the gradebook tags
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
        $class = 'default_window';        
        if ($this->type == 'edit') {
            $class = 'edit_window';
            $end_point = 'editEndpoint';            
        } else {
            if ($skill['done_by_user'] == 1) {
                $class = 'done_window';
                $end_point = 'doneEndpoint';                
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
        $this->js .= 'var e'.$skill['parent_id'].' = prepare("block_' . $skill['parent_id'].'",  '.$end_point.');'."\n";                        
        $this->js .= 'jsPlumb.connect({source: e'.$block_id.', target:e'.$skill['parent_id'].'});'."\n";;
    }
    
    /**
     * Displays the HTMl part of jplumb
     */
    public function display_html() {                     
        echo $this->return_html();        
    }
    
    /**
     * Displays the Javascript part of jplumb
     */
    public function display_js() {
        echo $this->return_js();
    }
    
    public function return_js() {
        return $this->get_js();
    }
    
    public function return_html() {
         if (empty($this->skills)) {
            return '';
        }
        $skill_count = sizeof($this->skills);
        //$corner = 360 / $skill_count;   
        $count = 0;
        
        $brothers = array();
        
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
            $brother_count = $brothers[$skill['id']];            
            $my_count = 0;
            $parent_x = 0; 
            if ($skill['parent_id'] == 0) {                
                //$x = 130*$childs/2;     
                //$x = $this->space_between_blocks_x*$childs/2;
                $x = $this->canvas_x/2  - $this->block_size/2;
            } else {                
                $max = isset($this->skills[$skill['parent_id']]['children']) ? count($this->skills[$skill['parent_id']]['children']) : 0;
                foreach($this->skills[$skill['parent_id']]['children'] as  $id => $sk) {                    
                    if ($skill['id'] == $sk['id']) {                                                
                        break;
                    }
                    $my_count++;                        
                }
                $parent_x = isset($this->skills[$skill['parent_id']]['x']) ? $this->skills[$skill['parent_id']]['x'] : 0;
                //$x = $my_count*$this->space_between_blocks_x + $parent_x  + $this->block_size - ($this->space_between_blocks_x*$max/2) ;
                $x = $my_count*$this->space_between_blocks_x + $parent_x  + $this->block_size - ($this->canvas_x/2 ) ;
            }
                    
            $y = $skill['level']*$this->space_between_blocks_y;   
              
            $skill['x'] = $x;
            $skill['y'] = $y;
            
                 
        //    var_dump($skill);                   
            //$skill['description']  = "{$brothers[$skill['parent_id']]} $x - $y";
            //$skill['name']  =  $skill['name']."  |  $x = $my_count * 150  +  $parent_x - (150* $max/2) - 10*$childs ";
            $this->add_item($skill, array('x' => $this->offset_x + $x, 'y' => $this->offset_y +$y));            
        }
        return $this->get_html();        
    }
    

    private function get_html() {
        return $this->html;
    }
   
    private function get_js() {
        return $this->js;
    }
}
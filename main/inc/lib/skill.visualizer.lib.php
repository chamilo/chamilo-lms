<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

 class SkillVisualizer {

    private $offsetX = 100;
    private $offsetY = 50;
   
    private $html    = '';
    private $type    = 'read';
    private $js      = '';
       
    function __construct($skills, $type = 'read') {
        $this->skills = $skills;       
        $this->type = $type; 
    }
    
    function prepare_skill_box($skill, $position, $class) {
        $block_id = $skill['id'];
        $this->html .= '<div id="block_'.$block_id.'" class="window '.$class.'" style="top:' . $position['y'] . 'px; left:' . $position['x'] . 'px;">';
        $gradebook_string = '';
        if (!empty($skill['gradebooks'])) {
            foreach($skill['gradebooks'] as $gradebook) {
                $gradebook_string .= Display::span($gradebook['name'], array('class'=>'label_tag notice','style'=>'width:50px'));    
            }
        }        
        $this->html .= $skill['name'].' '.$gradebook_string;
        
        if ($this->type == 'edit' && $skill['parent_id'] != 0) {
            $this->html .= Display::url(get_lang('Edit'), '#', array('id=>"edit_block_'.$block_id,'class'=>'edit_block'));
            $this->html .= Display::url(get_lang('Add'), '#', array('id=>"edit_block_'.$block_id,'class'=>'edit_block'));
            $this->html .= Display::url(get_lang('Delete'), '#', array('id=>"edit_block_'.$block_id,'class'=>'edit_block'));
            
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
        
        
        //$this->add_item($skill, array('x' => $x + $this->offsetX, 'y' => $y + $this->offsetY));        
        foreach ($this->skills as $skill) {            
            if (isset($brothers[$skill['parent_id']])) {
                $brothers[$skill['parent_id']] +=2;
            } else {
                $brothers[$skill['parent_id']] = 1;
            }            
            //$x = round($this->offsetX * sin(deg2rad($corner * $count)));
            //$y = round($this->offsetY * cos(deg2rad($corner * $count)));
           
            $x = $brothers[$skill['parent_id']]*100;            
            $y = $skill['level']*120;                        
            //$skill['description']  = "{$brothers[$skill['parent_id']]} $x - $y";
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
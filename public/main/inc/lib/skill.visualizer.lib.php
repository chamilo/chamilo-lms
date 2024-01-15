<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;

/**
 * @todo lib not use. Only the class variables not the functions
 */
class SkillVisualizer
{
    public $block_size = 120; //see CSS window class
    public $canvas_x = 1024;
    public $canvas_y = 800;

    public $offset_x = 0;
    public $offset_y = 50;

    public $space_between_blocks_x = 100;
    public $space_between_blocks_y = 150;

    public $center_x = null;

    private $html = '';
    private $type = 'read';
    private $js = '';

    /**
     * SkillVisualizer constructor.
     *
     * @param $skills
     * @param string $type
     */
    public function __construct($skills, $type = 'read')
    {
        $this->skills = $skills;
        $this->type = $type;
        $this->center_x = intval($this->offset_x + $this->canvas_x / 2 - $this->block_size / 2);
    }

    /**
     * @param $skill
     * @param $position
     * @param $class
     */
    public function prepare_skill_box($skill, $position, $class)
    {
        $block_id = $skill['id'];

        $extra_class = 'third_window';
        if (0 == $skill['parent_id']) {
            $extra_class = 'second_window';
        }

        $this->html .= '<div id="block_'.$block_id.'" class = "open_block window '.$extra_class.'  '.$class.'" style = "top:'.$position['y'].'px; left:'.$position['x'].'px;">';

        $content = $skill['name'];
        $content .= '<div class="btn-group">';
        $content .= Display::url(get_lang('Edit'), '#', ['id' => 'edit_block_'.$block_id, 'class' => 'edit_block btn']);
        $content .= Display::url('+', '#', ['id' => 'edit_block_'.$block_id, 'class' => 'edit_block btn']);
        $content .= '</div>';

        $this->html .= $content;

        if ('edit' == $this->type && 0 != $skill['parent_id']) {
            //$this->html .= Display::url(Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')), '#', array('id'=>'edit_block_'.$block_id,'class'=>'edit_block'));
            //$this->html .= Display::url(Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Add')), '#', array('id'=>'edit_block_'.$block_id,'class'=>'edit_block'));
            //$this->html .= Display::url(Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')), '#', array('id=>"edit_block_'.$block_id,'class'=>'edit_block'));
            //$this->html .= Display::url(Display::getMdiIcon(ActionIcon::UP, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Close')), '#', array('id'=>'close_block_'.$block_id,'class'=>'close_block'));
            //$this->html .= Display::url(Display::getMdiIcon(ActionIcon::DOWN, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Open')), '#', array('id'=>'open_block_'.$block_id,'class'=>'open_block'));
        }
        $this->html .= '</div>';
    }

    /**
     * Displays the HTMl part of jplumb.
     */
    public function display_html()
    {
        echo $this->return_html();
    }

    /**
     * Displays the Javascript part of jplumb.
     */
    public function display_js()
    {
        echo $this->return_js();
    }

    public function return_js()
    {
        return $this->get_js();
    }

    public function return_html()
    {
        if (empty($this->skills)) {
            return '';
        }
        $skill_count = sizeof($this->skills);
        //$corner = 360 / $skill_count;
        $count = 0;
        $brothers = [];
        foreach ($this->skills as &$skill) {
            if (!in_array($skill['parent_id'], [0, 1])) {
                continue;
            }
            $childs = isset($skill['children']) ? count($skill['children']) : 0;

            //$x = round($this->offsetX * sin(deg2rad($corner * $count)));
            //$y = round($this->offsetY * cos(deg2rad($corner * $count)));

            /*if (isset($brothers[$skill['parent_id']])) {
                $brothers[$skill['parent_id']] +=2;
            } else {
                $brothers[$skill['parent_id']] = 1;
            }*/
            //$brother_count = $brothers[$skill['id']];
            $my_count = 0;
            $parent_x = 0;
            if (0 == $skill['parent_id']) {
                //$x = 130*$childs/2;
                //$x = $this->space_between_blocks_x*$childs/2;
                $x = $this->canvas_x / 2 - $this->block_size / 2;
            } else {
                $max = isset($this->skills[$skill['parent_id']]['children']) ? count($this->skills[$skill['parent_id']]['children']) : 0;
                foreach ($this->skills[$skill['parent_id']]['children'] as $id => $sk) {
                    if ($skill['id'] == $sk['id']) {
                        break;
                    }
                    $my_count++;
                }
                $parent_x = isset($this->skills[$skill['parent_id']]['x']) ? $this->skills[$skill['parent_id']]['x'] : 0;
                //$x = $my_count*$this->space_between_blocks_x + $parent_x  + $this->block_size - ($this->space_between_blocks_x*$max/2) ;
                $x = $my_count * $this->space_between_blocks_x + $parent_x + $this->block_size - ($this->canvas_x / 2);
            }

            $y = $skill['level'] * $this->space_between_blocks_y;

            $skill['x'] = $x;
            $skill['y'] = $y;

            //$skill['description']  = "{$brothers[$skill['parent_id']]} $x - $y";
            //$skill['name']  =  $skill['name']."  |  $x = $my_count * 150  +  $parent_x - (150* $max/2) - 10*$childs ";
            $this->add_item($skill, ['x' => $this->offset_x + $x, 'y' => $this->offset_y + $y]);
        }

        return $this->get_html();
    }

    /**
     * Adds a node using jplumb.
     */
    private function add_item($skill, $position)
    {
        $block_id = $skill['id'];
        $end_point = 'readEndpoint';
        $class = 'default_window';
        if ('edit' == $this->type) {
            $class = 'edit_window';
            $end_point = 'editEndpoint';
        } else {
            if (1 == $skill['done_by_user']) {
                $class = 'done_window';
                $end_point = 'doneEndpoint';
            } else {
                $end_point = 'defaultEndpoint';
            }
        }
        $this->prepare_skill_box($skill, $position, $class);

        if (0 == $skill['parent_id']) {
            return;
        }
        //default_arrow_color

        $this->js .= 'var e'.$block_id.' = prepare("block_'.$block_id.'",  '.$end_point.');'."\n";
        $this->js .= 'var e'.$skill['parent_id'].' = prepare("block_'.$skill['parent_id'].'",  '.$end_point.');'."\n";
        $this->js .= 'jsPlumb.connect({source: e'.$block_id.', target:e'.$skill['parent_id'].'});'."\n";
    }

    private function get_html()
    {
        return $this->html;
    }

    private function get_js()
    {
        return $this->js;
    }
}

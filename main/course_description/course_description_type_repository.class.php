<?php

namespace CourseDescription;

/**
 * Description of CourseDescriptionTypeRepository
 *
 * @author Laurent Opprecht <laurent@opprecht.info> for the University of Geneva
 * @licence /license.txt
 */
class CourseDescriptionTypeRepository
{
    
    
    /**
     * Return the instance of the repository.
     * 
     * @return \CourseDescription\CourseDescriptionTypeRepository 
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }
    
    /**
     * All available course description types.
     * 
     * @return array 
     */
    public function all()
    {
        static $result = null;
        if (!is_null($result)) {
            return $result;
        }

        $data = (object) array();
        $data->id = 1;
        $data->name = 'general';
        $data->title = get_lang('GeneralDescription');
        $data->is_editable = true;
        $data->icon = 'info.png';
        $data->question = get_lang('GeneralDescriptionQuestions');
        $data->info = get_lang('GeneralDescriptionInformation');
        $result[$data->id] = new CourseDescriptionType($data);

        $data = (object) array();
        $data->id = 2;
        $data->name = 'objectives';
        $data->title = get_lang('Objectives');
        $data->is_editable = true;
        $data->icon = 'objective.png';
        $data->question = get_lang('ObjectivesQuestions');
        $data->info = get_lang('ObjectivesInformation');
        $result[$data->id] = new CourseDescriptionType($data);

        $data = (object) array();
        $data->id = 3;
        $data->name = 'topics';
        $data->title = get_lang('Topics');
        $data->is_editable = true;
        $data->icon = 'topics.png';
        $data->question = get_lang('TopicsQuestions');
        $data->info = get_lang('TopicsInformation');
        $result[$data->id] = new CourseDescriptionType($data);

        $data = (object) array();
        $data->id = 4;
        $data->name = 'methodology';
        $data->title = get_lang('Methodology');
        $data->is_editable = true;
        $data->icon = 'strategy.png';
        $data->question = get_lang('MethodologyQuestions');
        $data->info = get_lang('MethodologyInformation');
        $result[$data->id] = new CourseDescriptionType($data);

        $data = (object) array();
        $data->id = 5;
        $data->name = 'material';
        $data->title = get_lang('CourseMaterial');
        $data->is_editable = true;
        $data->icon = 'laptop.png';
        $data->question = get_lang('CourseMaterialQuestions');
        $data->info = get_lang('CourseMaterialInformation');
        $result[$data->id] = new CourseDescriptionType($data);

        $data = (object) array();
        $data->id = 6;
        $data->name = 'hr';
        $data->title = get_lang('HumanAndTechnicalResources');
        $data->is_editable = true;
        $data->icon = 'teacher.png';
        $data->question = get_lang('HumanAndTechnicalResourcesQuestions');
        $data->info = get_lang('HumanAndTechnicalResourcesInformation');
        $result[$data->id] = new CourseDescriptionType($data);

        $data = (object) array();
        $data->id = 7;
        $data->name = 'assessment';
        $data->title = get_lang('Assessment');
        $data->is_editable = true;
        $data->icon = 'assessment.png';
        $data->question = get_lang('AssessmentQuestions');
        $data->info = get_lang('AssessmentInformation');
        $result[$data->id] = new CourseDescriptionType($data);

        $data = (object) array();
        $data->id = 8;
        $data->name = 'other';
        $data->title = get_lang('Other');
        $data->is_editable = true;
        $data->icon = 'wizard.png';
        $data->question = get_lang('AssessmentQuestions');
        $data->info = get_lang('AssessmentInformation');
        $result[$data->id] = new CourseDescriptionType($data);

        return $result;
    }

    /**
     * Retrieve once course description type from its id.
     * 
     * @param int $id
     * @return \CourseDescription\CourseDescriptionType 
     */
    public function find_one_by_id($id)
    {
        $all = $this->all();
        $result = isset($all[$id]) ? $all[$id] : null;
        return $result;
    }
    
    /**
     * Retrieve once course description type from its name.
     * 
     * @param string $name
     * @return \CourseDescription\CourseDescriptionType 
     */
    public function find_one_by_name($name)
    {
        $name = strtolower($name);
        $items = $this->all();
        foreach($items as $item){
            if(strtolower($item->name) == $name){
                return $item;
            }
        }
        return null;
    }

    /**
     * Retrieve once course description type from its id.
     * 
     * @param int $id
     * @return \CourseDescription\CourseDescriptionType 
     */
    public function get($id)
    {
        return self::find_one_by_id($id);
    }

}
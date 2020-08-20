<?php

/* For licensing terms, see /license.txt */

/**
 * Class GradeModel.
 */
class GradeModel extends Model
{
    public $table;
    public $columns = ['id', 'name', 'description'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = Database::get_main_table(TABLE_GRADE_MODEL);
    }

    /**
     * @param array $where_conditions
     *
     * @return array
     */
    public function get_all($where_conditions = [])
    {
        return Database::select(
            '*',
            $this->table,
            ['where' => $where_conditions, 'order' => 'name ASC']
        );
    }

    /**
     * @return mixed
     */
    public function get_count()
    {
        $row = Database::select(
            'count(*) as count',
            $this->table,
            [],
            'first'
        );

        return $row['count'];
    }

    /**
     * Displays the title + grid.
     */
    public function display()
    {
        // action links
        echo '<div class="actions" style="margin-bottom:20px">';
        echo '<a href="grade_models.php">'.
                Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
        echo '<a href="'.api_get_self().'?action=add">'.
                Display::return_icon('add.png', get_lang('Add'), '', ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';
        echo Display::grid_html('grade_model');
    }

    /**
     * Returns a Form validator Obj.
     *
     * @todo the form should be auto generated
     *
     * @param string $url
     * @param string $action add, edit
     *
     * @return FormValidator form validator obj
     */
    public function return_form($url, $action)
    {
        $form = new FormValidator('grades', 'post', $url);

        // Setting the form elements
        $header = get_lang('Add');

        if ($action === 'edit') {
            $header = get_lang('Modify');
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : '';

        $form->addHeader($header);
        $form->addHidden('id', $id);
        $form->addText('name', get_lang('Name'));
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            [
                'ToolbarSet' => 'careers',
                'Width' => '100%',
                'Height' => '250',
            ]
        );

        $form->addLabel(get_lang('Components'), '');

        // Get components
        $nr_items = 2;
        $max = 20;
        // Setting the defaults
        $defaults = $this->get($id);
        if ($defaults) {
            $components = $this->get_components($defaults['id']);
        }

        if ('edit' === $action) {
            if (!empty($components)) {
                $nr_items = count($components);
            }
        }

        $form->addElement('hidden', 'maxvalue', '100');
        $form->addElement('hidden', 'minvalue', '0');
        $renderer = &$form->defaultRenderer();

        $component_array = [];
        for ($i = 0; $i <= $max; $i++) {
            $counter = $i;
            $form->addElement('text', 'components['.$i.'][percentage]', null);
            $form->addElement('text', 'components['.$i.'][acronym]', null);
            $form->addElement('text', 'components['.$i.'][title]', null);
            $form->addElement('hidden', 'components['.$i.'][id]', null);

            $template_percentage =
            '<div id='.$i.' style="display: '.(($i <= $nr_items) ? 'inline' : 'none').';" class="form-group">
                <label for="" class="col-sm-2 control-label">
                    {label}
                </label>
                <div class="col-sm-8">
                    <!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->
                    {element} <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --> % = ';

            $template_acronym = '
            <!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->
            {element} {label} <!-- BEGIN error --><span class="form_error">{error}</span> <!-- END error -->';

            $template_title =
            '&nbsp{element} <!-- BEGIN error --> <span class="form_error">{error}</span><!-- END error -->
             <a href="javascript:plusItem('.($counter + 1).')">
                '.Display::return_icon('add.png', get_lang('Add'), ['id' => 'plus-'.($counter + 1), 'style' => 'display: '.(($counter >= $nr_items) ? 'inline' : 'none')]).'
            </a>
            <a href="javascript:minItem('.($counter).')">
                '.Display::return_icon('delete.png', get_lang('Delete'), ['id' => 'min-'.($counter), 'style' => 'display: '.(($counter >= $nr_items) ? 'inline' : 'none')]).'
            </a>
            </div></div>';
            $renderer->setElementTemplate($template_title, 'components['.$i.'][title]');
            $renderer->setElementTemplate($template_percentage, 'components['.$i.'][percentage]');
            $renderer->setElementTemplate($template_acronym, 'components['.$i.'][acronym]');

            if ($i == 0) {
                $form->addRule('components['.$i.'][percentage]', get_lang('ThisFieldIsRequired'), 'required');
                $form->addRule('components['.$i.'][acronym]', get_lang('ThisFieldIsRequired'), 'required');
                $form->addRule('components['.$i.'][title]', get_lang('ThisFieldIsRequired'), 'required');
            }
            $form->addRule('components['.$i.'][percentage]', get_lang('OnlyNumbers'), 'numeric');
            $form->addRule(['components['.$i.'][percentage]', 'maxvalue'], get_lang('Over100'), 'compare', '<=');
            $form->addRule(['components['.$i.'][percentage]', 'minvalue'], get_lang('UnderMin'), 'compare', '>=');

            $component_array[] = 'components['.$i.'][percentage]';
        }

        // New rule added in the formvalidator compare_fields that filters a group of fields in order to compare with the wanted value
        $form->addRule($component_array, get_lang('AllMustWeight100'), 'compare_fields', '==@100');
        $form->addElement('label', '', get_lang('AllMustWeight100'));

        if ($action === 'edit') {
            $form->addButtonUpdate(get_lang('Modify'));
        } else {
            $form->addButtonCreate(get_lang('Add'));
        }

        if (!empty($components)) {
            $counter = 0;
            foreach ($components as $component) {
                foreach ($component as $key => $value) {
                    $defaults['components['.$counter.']['.$key.']'] = $value;
                }
                $counter++;
            }
        }
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * @param $id
     *
     * @return array|null
     */
    public function get_components($id)
    {
        $obj = new GradeModelComponents();
        if (!empty($id)) {
            return $obj->get_all(['where' => ['grade_model_id = ?' => $id]]);
        }

        return null;
    }

    /**
     * @param array $params
     * @param bool  $show_query
     *
     * @return bool
     */
    public function save($params, $show_query = false)
    {
        $id = parent::save($params, $show_query);
        if (!empty($id)) {
            foreach ($params['components'] as $component) {
                if (!empty($component['title']) && !empty($component['percentage']) && !empty($component['acronym'])) {
                    $obj = new GradeModelComponents();
                    $component['grade_model_id'] = $id;
                    $obj->save($component);
                }
            }
        }

        //event_system(LOG_CAREER_CREATE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function update($params, $showQuery = false)
    {
        parent::update($params, $showQuery);

        if (!empty($params['id'])) {
            foreach ($params['components'] as $component) {
                $obj = new GradeModelComponents();
                $component['grade_model_id'] = $params['id'];
                if (empty($component['title']) && empty($component['percentage']) && empty($component['acronym'])) {
                    $obj->delete($component['id']);
                } else {
                    $obj->update($component);
                }
            }
        }
    }

    /**
     * @param int $id
     */
    public function delete($id)
    {
        parent::delete($id);
    }

    /**
     * @param $form
     * @param string $name
     * @param null   $default_value
     *
     * @return bool
     */
    public function fill_grade_model_select_in_form(&$form, $name = 'gradebook_model_id', $default_value = null)
    {
        if (api_get_setting('gradebook_enable_grade_model') === 'false') {
            return false;
        }

        if (api_get_setting('teachers_can_change_grade_model_settings') === 'true' || api_is_platform_admin()) {
            $grade_models = $this->get_all();
            $grade_model_options = ['-1' => get_lang('None')];
            if (!empty($grade_models)) {
                foreach ($grade_models as $item) {
                    $grade_model_options[$item['id']] = $item['name'];
                }
            }
            $form->addElement('select', $name, get_lang('GradeModel'), $grade_model_options);
            $default_platform_setting = api_get_setting('gradebook_default_grade_model_id');
            $default = -1;

            if ($default_platform_setting == -1) {
                if (!empty($default_value)) {
                    $default = $default_value;
                }
            } else {
                $default = $default_platform_setting;
            }

            if (!empty($default) && $default != '-1') {
                $form->setDefaults([$name => $default]);
            }
        }
    }
}

/**
 * Class GradeModelComponents.
 */
class GradeModelComponents extends Model
{
    public $table;
    public $columns = ['id', 'title', 'percentage', 'acronym', 'grade_model_id'];

    /**
     * GradeModelComponents constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = Database::get_main_table(TABLE_GRADE_MODEL_COMPONENTS);
    }

    /**
     * @param array $params
     * @param bool  $showQuery
     *
     * @return bool
     */
    public function save($params, $showQuery = false)
    {
        return parent::save($params, $showQuery);
    }
}

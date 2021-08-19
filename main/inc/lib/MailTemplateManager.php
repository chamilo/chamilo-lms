<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Finder\Finder;

/**
 * Class MailTemplateManager.
 */
class MailTemplateManager extends Model
{
    public $columns = [
        'id',
        'name',
        'template',
        'type',
        'system',
        'url_id',
        'default_template',
        'created_at',
        'updated_at',
        'author_id',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->table = 'mail_template';
    }

    /**
     * @return int
     */
    public function get_count()
    {
        $row = Database::select(
            'count(*) as count',
            $this->table,
            ['where' => ['url_id = ? ' => api_get_current_access_url_id()]],
            'first'
        );

        return $row['count'];
    }

    /**
     * Displays the title + grid.
     *
     * @return string html code
     */
    public function display()
    {
        // Action links
        $html = '<div class="actions" style="margin-bottom:20px">';
        $html .= '<a href="'.api_get_path(WEB_CODE_PATH).'admin">'.
            Display::return_icon(
                'back.png',
                get_lang('Back'),
                '',
                '32'
            )
            .'</a>';
        $html .= '<a href="'.api_get_self().'?action=add">'.
            Display::return_icon(
                'add.png',
                get_lang('Add'),
                '',
                '32'
            ).'</a>';
        $html .= '</div>';
        $html .= Display::grid_html('mail_template');

        return $html;
    }

    /**
     * Returns a Form validator Obj.
     *
     * @param string $url
     * @param string $action
     *
     * @return FormValidator
     */
    public function returnForm($url, $action = 'add')
    {
        $form = new FormValidator('template', 'post', $url);
        // Setting the form elements
        $header = get_lang('Add');
        if ($action === 'edit') {
            $header = get_lang('Modify');
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : '';

        $form->addElement('header', '', $header);
        $form->addElement('hidden', 'id', $id);
        $form->addElement(
            'text',
            'name',
            get_lang('Name'),
            ['size' => '70', 'id' => 'name']
        );

        /*$form->addHtmlEditor(
            'email_template',
            get_lang('Template'),
            false,
            false,
            [
                'ToolbarSet' => 'Careers',
                'Width' => '100%',
                'Height' => '250',
            ]
        );*/
        $form->addTextarea(
            'email_template',
            get_lang('Template')
        );

        $finder = new Finder();
        $files = $finder
            ->files()
            ->in(api_get_path(SYS_CODE_PATH).'template/default/mail')
            ->sort(
                function ($a, $b) {
                    return strcmp($a->getRealpath(), $b->getRealpath());
                }
            );

        $options = [];
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $options[$file->getFilename()] = $file->getFilename();
        }

        $form->addSelect(
            'type',
            get_lang('Type'),
            $options
        );

        $defaults = $this->get($id);

        if ($action === 'edit') {
            $form->addLabel(get_lang('CreatedAt'), Display::dateToStringAgoAndLongDate($defaults['created_at']));
            $form->addLabel(get_lang('UpdatedAt'), Display::dateToStringAgoAndLongDate($defaults['updated_at']));
            $form->addButtonSave(get_lang('Modify'), 'submit');
        } else {
            $form->addButtonCreate(get_lang('Add'), 'submit');
        }

        // Setting the defaults
        if (!empty($defaults)) {
            $defaults['email_template'] = $defaults['template'];
        }
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function setDefault($id)
    {
        $template = $this->get($id);
        if (empty($template)) {
            return false;
        }
        $type = $template['type'];
        $urlId = api_get_current_access_url_id();
        $sql = "UPDATE {$this->table} SET default_template = 0
                WHERE type = '$type' AND url_id = $urlId";
        Database::query($sql);

        $sql = "UPDATE {$this->table} SET default_template = 1
                WHERE id = $id";
        Database::query($sql);

        return true;
    }

    /**
     * @param int   $templateId
     * @param array $userInfo
     *
     * @return string|false
     */
    public function parseTemplate($templateId, $userInfo)
    {
        $templateInfo = $this->get($templateId);
        if (!empty($templateInfo)) {
            $emailTemplate = nl2br($templateInfo['template']);

            $keys = array_keys($userInfo);
            foreach ($keys as $key) {
                $emailTemplate = str_replace("{{user.$key}}", $userInfo[$key], $emailTemplate);
            }
            $template = new Template();
            $template->twig->setLoader(new \Twig_Loader_String());
            $emailBody = $template->twig->render($emailTemplate);

            return $emailBody;
        }

        return false;
    }

    /**
     * Gets a custom mail template by the name of the template it replaces.
     *
     * @param string $templateType Name of the template file it replaces
     *
     * @return string
     */
    public function getTemplateByType($templateType)
    {
        if (empty($templateType)) {
            return '';
        }
        $result = Database::select(
            'template',
            $this->table,
            ['where' => ['type = ? ' => $templateType, ' AND url_id = ? ' => api_get_current_access_url_id()]],
            'first'
        );
        if (empty($result)) {
            return '';
        }

        return $result['template'];
    }
}

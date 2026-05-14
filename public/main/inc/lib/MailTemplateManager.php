<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Symfony\Component\Finder\Finder;

/**
 * Class MailTemplateManager.
 */
class MailTemplateManager extends Model
{
    public $columns = [
        'id',
        'title',
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
     * Displays the mail template list.
     *
     * @return string html code
     */
    public function display()
    {
        $backUrl = api_get_path(WEB_CODE_PATH).'admin';
        $addUrl = api_get_self().'?action=add';
        $token = Security::get_existing_token();
        $confirm = addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES));
        $templates = $this->getTemplatesForCurrentUrl();

        $html = '<section class="w-full px-4 py-6">';
        $html .= '<div class="mb-4 flex items-center gap-2">';
        $html .= '<a class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-25 bg-white text-primary shadow-sm transition hover:bg-gray-15" href="'.$backUrl.'" title="'.api_htmlentities(get_lang('Back')).'">'.
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')).
            '</a>';
        $html .= '<a class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-sm transition hover:bg-primary/90" href="'.$addUrl.'" title="'.api_htmlentities(get_lang('Add')).'">'.
            '<span class="mdi mdi-plus-box text-xl" aria-hidden="true"></span>'.
            '</a>';
        $html .= '</div>';
        $html .= '<div class="mb-5">';
        $html .= '<h1 class="text-2xl font-semibold text-gray-90">'.get_lang('Mail templates').'</h1>';
        $html .= '</div>';
        $html .= '<div class="w-full overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">';

        if (empty($templates)) {
            $html .= '<div class="p-6 text-sm text-gray-70">'.get_lang('No data available').'</div>';
            $html .= '</div></section>';

            return $html;
        }

        $html .= '<div class="w-full overflow-x-auto">';
        $html .= '<table class="w-full min-w-full table-auto">';
        $html .= '<thead class="border-b border-gray-25 bg-gray-15">';
        $html .= '<tr>';
        $html .= '<th class="px-4 py-3 text-left text-sm font-semibold text-gray-90">'.get_lang('Name').'</th>';
        $html .= '<th class="px-4 py-3 text-left text-sm font-semibold text-gray-90">'.get_lang('Type').'</th>';
        $html .= '<th class="w-40 px-4 py-3 text-left text-sm font-semibold text-gray-90">'.get_lang('Actions').'</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-25">';

        foreach ($templates as $template) {
            $id = (int) $template['id'];
            $title = api_htmlentities((string) $template['title']);
            $type = api_htmlentities((string) $template['type']);
            $isDefault = 1 === (int) $template['default_template'];
            $defaultIcon = $isDefault ? 'mdi-check-circle' : 'mdi-circle-outline';
            $defaultTitle = api_htmlentities(get_lang('Default'));

            $html .= '<tr class="transition hover:bg-gray-15">';
            $html .= '<td class="px-4 py-3 text-sm text-gray-90">'.$title.'</td>';
            $html .= '<td class="max-w-xl break-all px-4 py-3 text-sm text-gray-90">'.$type.'</td>';
            $html .= '<td class="px-4 py-3 text-sm">';
            $html .= '<div class="flex items-center gap-3">';
            $html .= '<a class="inline-flex items-center justify-center" href="?action=edit&id='.$id.'" title="'.api_htmlentities(get_lang('Edit')).'"><span class="mdi mdi-pencil ch-tool-icon"></span></a>';
            $html .= '<a class="inline-flex items-center justify-center" href="?sec_token='.$token.'&action=set_default&id='.$id.'" title="'.$defaultTitle.'"><span class="mdi '.$defaultIcon.' ch-tool-icon"></span></a>';
            $html .= '<a class="inline-flex items-center justify-center" onclick="javascript:if(!confirm(\''.$confirm.'\')) return false;" href="?sec_token='.$token.'&action=delete&id='.$id.'" title="'.api_htmlentities(get_lang('Delete')).'"><span class="mdi mdi-delete ch-tool-icon"></span></a>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * Returns mail templates for the current access URL.
     *
     * @return array
     */
    public function getTemplatesForCurrentUrl()
    {
        return Database::select(
            'id, title, type, default_template',
            $this->table,
            [
                'where' => ['url_id = ? ' => api_get_current_access_url_id()],
                'order' => 'title ASC',
            ]
        );
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
        if ('edit' === $action) {
            $header = get_lang('Edit');
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : '';

        $form->addElement('header', '', $header);
        if (!empty($id)) {
            $form->addElement('hidden', 'id', $id);
        }
        $form->addElement('hidden', 'default_template', 0);
        $form->addElement(
            'text',
            'title',
            get_lang('Title'),
            ['size' => '70', 'id' => 'title']
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

        if ('edit' === $action) {
            $form->addLabel(get_lang('Created at'), Display::dateToStringAgoAndLongDate($defaults['created_at']));
            $form->addLabel(get_lang('Updated at'), Display::dateToStringAgoAndLongDate($defaults['updated_at']));
            $form->addButtonSave(get_lang('Edit'), 'submit');
        } else {
            $form->addButtonCreate(get_lang('Add'), 'submit');
        }

        // Setting the defaults
        if (!empty($defaults)) {
            $defaults['email_template'] = $defaults['template'];
        }
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('title', get_lang('Required field'), 'required');
        $form->addRule('type', get_lang('Required field'), 'required');

        return $form;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function setDefault($id)
    {
        $id = (int) $id;
        $template = $this->get($id);
        if (empty($template)) {
            return false;
        }

        $type = (string) $template['type'];
        $urlId = api_get_current_access_url_id();

        Database::update(
            $this->table,
            ['default_template' => 0],
            ['type = ? AND url_id = ?' => [$type, $urlId]]
        );

        $result = Database::update(
            $this->table,
            ['default_template' => 1],
            ['id = ?' => $id]
        );

        return false !== $result;
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
            $emailTemplate = $templateInfo['template'];

            $keys = array_keys($userInfo);
            foreach ($keys as $key) {
                $emailTemplate = str_replace("{{user.$key}}", $userInfo[$key], $emailTemplate);
            }
            $template = new Template();
            //$template->twig->setLoader(new \Twig_Loader_String());
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

<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Promotion as PromotionEntity;
use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\ObjectIcon;

/**
 * Class Promotion
 * This class provides methods for the promotion management.
 * Include/require it in your code to use its features.
 */
class Promotion extends Model
{
    public $table;
    public $columns = [
        'id',
        'name',
        'description',
        'career_id',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = Database::get_main_table(TABLE_PROMOTION);
    }

    /**
     * Get the count of elements.
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
     * Copies the promotion to a new one.
     *
     * @param   int     Promotion ID
     * @param   int     Career ID, in case we want to change it
     * @param   bool     Whether or not to copy the sessions inside
     *
     * @return int New promotion ID on success, false on failure
     */
    public function copy($id, $career_id = null, $copy_sessions = false)
    {
        $pid = false;
        $promotion = $this->get($id);
        if (!empty($promotion)) {
            $new = [];
            foreach ($promotion as $key => $val) {
                switch ($key) {
                    case 'id':
                    case 'updated_at':
                        break;
                    case 'name':
                        $val .= ' '.get_lang('Copy');
                        $new[$key] = $val;
                        break;
                    case 'created_at':
                        $val = api_get_utc_datetime();
                        $new[$key] = $val;
                        break;
                    case 'career_id':
                        if (!empty($career_id)) {
                            $val = (int) $career_id;
                        }
                        $new[$key] = $val;
                        break;
                    default:
                        $new[$key] = $val;
                        break;
                }
            }

            if ($copy_sessions) {
                /**
                 * When copying a session we do:
                 * 1. Copy a new session from the source
                 * 2. Copy all courses from the session (no user data, no user list)
                 * 3. Create the promotion.
                 */
                $session_list = SessionManager::get_all_sessions_by_promotion($id);

                if (!empty($session_list)) {
                    $pid = $this->save($new);
                    if (!empty($pid)) {
                        $new_session_list = [];

                        foreach ($session_list as $item) {
                            $sid = SessionManager::copy(
                                (int) $item['id'],
                                true,
                                false,
                                false,
                                true
                            );
                            $new_session_list[] = $sid;
                        }

                        if (!empty($new_session_list)) {
                            SessionManager::subscribe_sessions_to_promotion(
                                $pid,
                                $new_session_list
                            );
                        }
                    }
                }
            } else {
                $pid = $this->save($new);
            }
        }

        return $pid;
    }

    /**
     * Gets all promotions by career id.
     *
     * @param   int     career id
     * @param bool $order
     *
     * @return array results
     */
    public function get_all_promotions_by_career_id($career_id, $order = false)
    {
        return Database::select(
            '*',
            $this->table,
            [
                'where' => ['career_id = ?' => $career_id],
                'order' => $order,
            ]
        );
    }

    public function get_status_list(): array
    {
        return [
            PromotionEntity::PROMOTION_STATUS_ACTIVE => get_lang('active'),
            PromotionEntity::PROMOTION_STATUS_INACTIVE => get_lang('inactive'),
        ];
    }

    /**
     * Displays the title + grid.
     *
     * @return string html code
     */
    public function display()
    {
        $actions = '<a href="career_dashboard.php">'.
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back'))
            .'</a>';
        $actions .= '<a href="'.api_get_self().'?action=add">'.
            Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add')).'</a>';
        $actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'session/session_add.php">'.
            Display::getMdiIcon(ObjectIcon::SESSION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add a training session')).'</a>';

        echo Display::toolbarAction('promotion_actions', [$actions]);
        echo Display::grid_html('promotions');
    }

    /**
     * Update all session status by promotion.
     *
     * @param int $promotion_id
     * @param int $status       (1, 0)
     */
    public function update_all_sessions_status_by_promotion_id(
        $promotion_id,
        $status
    ) {
        $sessionList = SessionManager::get_all_sessions_by_promotion($promotion_id);
        if (!empty($sessionList)) {
            foreach ($sessionList as $item) {
                SessionManager::set_session_status($item['id'], $status);
            }
        }
    }

    /**
     * Returns a Form validator Obj.
     *
     * @param string $url
     * @param string $action
     *
     * @return FormValidator
     */
    public function return_form($url, $action = 'add')
    {
        $form = new FormValidator('promotion', 'post', $url);
        // Setting the form elements
        $header = get_lang('Add');
        if ('edit' == $action) {
            $header = get_lang('Edit');
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : '';

        $form->addHeader($header);
        $form->addHidden('id', $id);
        $form->addText('name', get_lang('Name'), true, ['size' => '70', 'id' => 'name']);
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            [
                'ToolbarSet' => 'Careers',
                'Width' => '100%',
                'Height' => '250',
            ]
        );
        $career = new Career();
        $careers = $career->get_all();
        $career_list = [];
        foreach ($careers as $item) {
            $career_list[$item['id']] = $item['name'];
        }
        $form->addSelect(
            'career_id',
            get_lang('Career'),
            $career_list,
            ['id' => 'career_id']
        );
        $status_list = $this->get_status_list();
        $form->addSelect('status', get_lang('Status'), $status_list);
        if ('edit' == $action) {
            $form->addElement('text', 'created_at', get_lang('Created at'));
            $form->freeze('created_at');
        }
        if ('edit' == $action) {
            $form->addButtonSave(get_lang('Edit'), 'submit');
        } else {
            $form->addButtonCreate(get_lang('Add'), 'submit');
        }

        // Setting the defaults
        $defaults = $this->get($id);
        if (!empty($defaults['created_at'])) {
            $defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
            $defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('name', get_lang('Required field'), 'required');

        return $form;
    }

    /**
     * @param array $params
     * @param bool  $showQuery
     *
     * @return bool
     */
    public function save($params, $showQuery = false)
    {
        $promotion = new \Chamilo\CoreBundle\Entity\Promotion();

        $em = Database::getManager();
        $repo = $em->getRepository(\Chamilo\CoreBundle\Entity\Career::class);
        $promotion
            ->setTitle($params['name'])
            ->setStatus($params['status'])
            ->setDescription($params['description'])
            ->setCareer($repo->find($params['career_id']))
        ;

        $em->persist($promotion);
        $em->flush();

        if (!empty($promotion->getId())) {
            Event::addEvent(
                LOG_PROMOTION_CREATE,
                LOG_PROMOTION_ID,
                $promotion->getId(),
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $promotion->getId();
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete($id)
    {
        if (parent::delete($id)) {
            SessionManager::clear_session_ref_promotion($id);
            Event::addEvent(
                LOG_PROMOTION_DELETE,
                LOG_PROMOTION_ID,
                $id,
                api_get_utc_datetime(),
                api_get_user_id()
            );
        } else {
            return false;
        }
    }
}

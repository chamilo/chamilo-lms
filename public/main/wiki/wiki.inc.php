<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiCategory;
use Chamilo\CourseBundle\Entity\CWikiConf;
use Chamilo\CourseBundle\Entity\CWikiDiscuss;
use Chamilo\CourseBundle\Entity\CWikiMailcue;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectRepository;
use ChamiloSession as Session;

final class WikiManager
{
    /** Legacy compat: set from index.php */
    private readonly CWikiRepository $wikiRepo;
    public string $page = 'index';
    public string $action = 'showpage';
    public ?string $charset = null;
    protected ?string $baseUrl = null;
    public ?string $url = null;

    /** Optional in-memory preload for view */
    private array $wikiData = [];
    public ?string $tbl_wiki = null;
    public ?string $tbl_wiki_mailcue = null;

    public function __construct(?CWikiRepository $wikiRepo = null)
    {
        if ($wikiRepo instanceof CWikiRepository) {
            $this->wikiRepo = $wikiRepo;
        } else {
            $em = \Database::getManager();
            /** @var CWikiRepository $repo */
            $repo = $em->getRepository(CWiki::class);
            $this->wikiRepo = $repo;
        }
        $this->baseUrl = $this->computeBaseUrl();
    }

    /** DBAL connection (plain SQL with Doctrine). */
    private function conn(): Connection
    {
        return Container::getEntityManager()->getConnection();
    }

    /** Table names (shortcuts) */
    private function tblWikiMailcue(): string
    {
        return 'c_wiki_mailcue';
    }
    private function tblWiki(): string
    {
        return 'c_wiki';
    }

    /**
     * Set the base URL to be used by the wiki for links.
     * Keeps backward compatibility by also setting $this->url.
     */
    public function setBaseUrl(string $url): void
    {
        $this->baseUrl = $url;
        // compat: some sites use $this->url as base string
        $this->url = $url;
    }

    /**
     * Get the base URL. If not previously set, compute a safe default.
     */
    public function getBaseUrl(): string
    {
        if (!empty($this->baseUrl)) {
            return $this->baseUrl;
        }
        $computed = api_get_self().'?'.api_get_cidreq();
        $this->setBaseUrl($computed);

        return $this->baseUrl;
    }

    /**
     * Helper to build URLs with additional parameters based on the current one.
     */
    public function buildUrl(array $params = []): string
    {
        $base = $this->getBaseUrl();
        return $params ? $base.'&'.http_build_query($params) : $base;
    }

    /**
     * Detects at runtime which column links mailcue to the page:
     * - 'publication_id' (some installations)
     * - 'id' (legacy)
     * - null if none exists (disables the feature)
     */
    private function mailcueLinkColumn(): ?string
    {
        static $col = null;
        if ($col !== null) {
            return $col;
        }

        $sm     = $this->conn()->createSchemaManager();
        $cols   = array_map(fn($c) => $c->getName(), $sm->listTableColumns($this->tblWikiMailcue()));
        $col    = in_array('publication_id', $cols, true) ? 'publication_id'
            : (in_array('id', $cols, true) ? 'id' : null);

        return $col;
    }

    /**
     * Returns the ID (c_wiki's `id` column) of the **first version** of the page
     * in the current context. This is the anchor used by the discussion/notify.
     */
    private function firstVersionIdByReflink(string $reflink): ?int
    {
        $ctx = self::ctx();
        $sql = 'SELECT MIN(id) AS id
            FROM '.$this->tblWiki().'
            WHERE c_id = :cid
              AND reflink = :ref
              AND COALESCE(group_id,0) = :gid
              AND COALESCE(session_id,0) = :sid';

        $id = $this->conn()->fetchOne($sql, [
            'cid' => (int)$ctx['courseId'],
            'ref' => html_entity_decode($reflink),
            'gid' => (int)$ctx['groupId'],
            'sid' => (int)$ctx['sessionId'],
        ]);

        return $id ? (int)$id : null;
    }

    /**
     * Load wiki data (iid or reflink) into $this->wikiData for view compatibility.
     * @param int|string|bool $wikiId iid of CWiki row or a reflink. Falsy => []
     */
    public function setWikiData($wikiId): void
    {
        $this->wikiData = self::getWikiDataFromDb($wikiId);
    }

    /** Query DB and return a flat array with latest-version fields in context. */
    private static function getWikiDataFromDb($wikiId): array
    {
        $ctx  = self::ctx();
        $em   = Container::getEntityManager();
        $repo = self::repo();

        $last = null;
        $pageId = 0;

        if (is_numeric($wikiId)) {
            /** @var CWiki|null $row */
            $row = $em->find(CWiki::class, (int)$wikiId);
            if (!$row) { return []; }
            $pageId = (int)($row->getPageId() ?: $row->getIid());
        } elseif (is_string($wikiId) && $wikiId !== '') {
            /** @var CWiki|null $first */
            $first = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($wikiId))
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->orderBy('w.version', 'ASC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            if (!$first) { return []; }
            $pageId = (int)$first->getPageId();
        } else {
            return [];
        }

        if ($pageId > 0) {
            $qb = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.pageId = :pid')->setParameter('pid', $pageId)
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->orderBy('w.version', 'DESC')
                ->setMaxResults(1);

            if ($ctx['sessionId'] > 0) {
                $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
            } else {
                $qb->andWhere('COALESCE(w.sessionId,0) = 0');
            }

            /** @var CWiki|null $last */
            $last = $qb->getQuery()->getOneOrNullResult();
        }

        if (!$last) {
            return [];
        }

        return [
            'iid'             => (int)$last->getIid(),
            'page_id'         => (int)$last->getPageId(),
            'title'           => (string)$last->getTitle(),
            'reflink'         => (string)$last->getReflink(),
            'content'         => (string)$last->getContent(),
            'user_id'         => (int)$last->getUserId(),
            'dtime'           => $last->getDtime(),
            'version'         => (int)$last->getVersion(),
            'visibility'      => (int)$last->getVisibility(),
            'visibility_disc' => (int)$last->getVisibilityDisc(),
            'assignment'      => (int)$last->getAssignment(),
            'progress'        => (string)$last->getProgress(),
            'score'           => (int)$last->getScore(),
        ];
    }

    /** Build request context (course/session/group + URLs). */
    private static function ctx(?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): array
    {
        $courseId  = $courseId  ?? api_get_course_int_id();
        $sessionId = $sessionId ?? api_get_session_id();
        $groupId   = $groupId   ?? api_get_group_id();

        return [
            'courseId'   => $courseId,
            'course'     => api_get_course_entity($courseId),
            'courseInfo' => api_get_course_info($courseId),
            'courseCode' => api_get_course_id(),

            'sessionId'  => $sessionId,
            'session'    => api_get_session_entity($sessionId),

            'groupId'    => $groupId,

            'baseUrl'    => api_get_path(WEB_CODE_PATH).'wiki/index.php?'.api_get_cidreq(),
        ];
    }

    /** @return CWikiRepository */
    private static function repo(): CWikiRepository
    {
        return Container::getEntityManager()->getRepository(CWiki::class);
    }

    /** @return ObjectRepository */
    private static function confRepo(): ObjectRepository
    {
        return Container::getEntityManager()->getRepository(CWikiConf::class);
    }

    /** Feature switch for categories. */
    private static function categoriesEnabled(): bool
    {
        return api_get_configuration_value('wiki_categories_enabled') === true
            || api_get_setting('wiki.wiki_categories_enabled') === 'true';
    }

    /** True if a reflink is available in current context (course/session/group). */
    public static function checktitle(
        string $title,
        ?int $courseId = null,
        ?int $sessionId = null,
        ?int $groupId = null
    ): bool {
        // Use same criterion as the whole module
        return self::existsByReflink($title, $courseId, $sessionId, $groupId);
    }

    public function editPage(): void
    {
        $ctx    = self::ctx();
        $em     = Container::getEntityManager();
        $repo   = self::repo();
        $userId = (int) api_get_user_id();

        // Sessions: only users allowed to edit inside the session
        if ($ctx['sessionId'] !== 0 && api_is_allowed_to_session_edit(false, true) === false) {
            api_not_allowed();
            return;
        }

        $page = self::normalizeReflink($this->page);
        $row  = [];
        $canEdit = false;
        $iconAssignment = '';
        $conf = null;

        self::dbg('enter editPage title='.$this->page.' normalized='.$page);
        self::dbg('ctx cid='.$ctx['courseId'].' gid='.$ctx['groupId'].' sid='.$ctx['sessionId'].' user='.$userId);

        // Historic rule: outside groups, home (index) is editable only by teacher/admin
        if (self::isMain($page) && (int)$ctx['groupId'] === 0
            && !api_is_allowed_to_edit(false, true) && !api_is_platform_admin()
        ) {
            Display::addFlash(Display::return_message('Only course managers can edit the home page (index) outside groups.', 'error'));
            self::dbg('block: home edit not allowed (student)');
            return;
        }

        // ---- FIRST (oldest row for this reflink in context) ----
        $qbFirst = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', $page)
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1);

        if ($ctx['sessionId'] > 0) {
            $qbFirst->andWhere('(COALESCE(w.sessionId,0) = 0 OR w.sessionId = :sid)')
                ->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbFirst->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        /** @var CWiki|null $first */
        $first = $qbFirst->getQuery()->getOneOrNullResult();
        self::dbg('$first '.($first ? 'HIT pid='.$first->getPageId() : 'MISS'));

        // ---- LAST (latest version in same context) ----
        $last = null;
        if ($first && $first->getPageId()) {
            $qbLast = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.pageId = :pid')->setParameter('pid', (int)$first->getPageId())
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->orderBy('w.version', 'DESC')
                ->setMaxResults(1);

            if ($ctx['sessionId'] > 0) {
                $qbLast->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
            } else {
                $qbLast->andWhere('COALESCE(w.sessionId,0) = 0');
            }

            /** @var CWiki|null $last */
            $last = $qbLast->getQuery()->getOneOrNullResult();
        }
        self::dbg('$last '.($last ? 'HIT iid='.$last->getIid().' ver='.$last->getVersion() : 'MISS'));

        // ---- Defaults (when page does not exist yet) ----
        $content = '<div class="wiki-placeholder">'.sprintf(get_lang('To begin, edit this page and remove this text'), api_get_path(WEB_IMG_PATH)).'</div>';
        $title   = self::displayTitleFor($page, null);
        $pageId  = 0;

        // ---- Base permissions ----
        if (!empty($ctx['groupId'])) {
            $groupInfo = GroupManager::get_group_properties((int)$ctx['groupId']);
            $canEdit = api_is_allowed_to_edit(false, true)
                || api_is_platform_admin()
                || GroupManager::is_user_in_group($userId, $groupInfo);
            if (!$canEdit) {
                Display::addFlash(Display::return_message('Only group members can edit this page.', 'warning'));
                self::dbg('block: not group member');
                return;
            }
        } else {
            // Outside groups: if not home, let users reach editor; hard locks/config will gate below.
            $canEdit = true;
        }

        if ($last) {
            if ($last->getContent() === '' && $last->getTitle() === '' && $page === '') {
                Display::addFlash(Display::return_message('You must select a page.', 'error'));
                self::dbg('block: empty page selection');
                return;
            }

            $content = api_html_entity_decode($last->getContent());
            $title   = api_html_entity_decode($last->getTitle());
            $pageId  = (int)$last->getPageId();

            // Assignment rules
            if ((int)$last->getAssignment() === 1) {
                Display::addFlash(Display::return_message('This is an assignment page. Be careful when editing.'));
                $iconAssignment = Display::getMdiIcon(ActionIcon::WIKI_ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, 'Assignment');
            } elseif ((int)$last->getAssignment() === 2) {
                $iconAssignment = Display::getMdiIcon(ActionIcon::WIKI_WORK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, 'Work');
                if ($userId !== (int)$last->getUserId()
                    && !api_is_allowed_to_edit(false, true) && !api_is_platform_admin()
                ) {
                    Display::addFlash(Display::return_message('This page is locked by the teacher for other users.', 'warning'));
                    self::dbg('block: assignment=2 and not owner');
                    return;
                }
            }

            // Hard lock by teacher
            if ((int)$last->getEditlock() === 1
                && !api_is_allowed_to_edit(false, true) && !api_is_platform_admin()
            ) {
                Display::addFlash(Display::return_message('This page is locked by the teacher.', 'warning'));
                self::dbg('block: teacher hard lock');
                return;
            }

            // Conf row (limits/dates)
            $conf = self::confRepo()->findOneBy(['cId' => $ctx['courseId'], 'pageId' => (int)$last->getPageId()]);
        }

        // ---- Config constraints (no redirects; just show and stop) ----
        if ($conf) {
            if ($conf->getStartdateAssig() && time() < api_strtotime($conf->getStartdateAssig())) {
                $msg = 'The task does not begin until: '.api_get_local_time($conf->getStartdateAssig());
                Display::addFlash(Display::return_message($msg, 'warning'));
                self::dbg('block: before start date');
                return;
            }

            if ($conf->getEnddateAssig() && time() > strtotime($conf->getEnddateAssig()) && (int)$conf->getDelayedsubmit() === 0) {
                $msg = 'The deadline has passed: '.api_get_local_time($conf->getEnddateAssig());
                Display::addFlash(Display::return_message($msg, 'warning'));
                self::dbg('block: after end date (no delayed submit)');
                return;
            }

            if ((int)$conf->getMaxVersion() > 0 && $last && (int)$last->getVersion() >= (int)$conf->getMaxVersion()) {
                Display::addFlash(Display::return_message('You have reached the maximum number of versions.', 'warning'));
                self::dbg('block: max versions reached');
                return;
            }

            if ((int)$conf->getMaxText() > 0 && $last && $conf->getMaxText() <= self::word_count($last->getContent())) {
                Display::addFlash(Display::return_message('You have reached the maximum number of words.', 'warning'));
                self::dbg('block: max words reached');
                return;
            }

            // Informative task block (non-blocking)
            if ($conf->getTask()) {
                $msgTask  = '<b>'.get_lang('Description of the assignment').'</b><p>'.$conf->getTask().'</p><hr>';
                $msgTask .= '<p>'.get_lang('Start date').': '.($conf->getStartdateAssig() ? api_get_local_time($conf->getStartdateAssig()) : get_lang('No')).'</p>';
                $msgTask .= '<p>'.get_lang('End date').': '.($conf->getEnddateAssig() ? api_get_local_time($conf->getEnddateAssig()) : get_lang('No'));
                $msgTask .= ' ('.get_lang('Allow delayed sending').') '.(((int)$conf->getDelayedsubmit() === 0) ? get_lang('No') : get_lang('Yes')).'</p>';
                $msgTask .= '<p>'.get_lang('Other requirements').': '.get_lang('Maximum number of versions').': '.((int)$conf->getMaxVersion() ?: get_lang('No'));
                $msgTask .= ' '.get_lang('Maximum number of words').': '.((int)$conf->getMaxText() ?: get_lang('No')).'</p>';
                Display::addFlash(Display::return_message($msgTask));
            }
        }

        // ---- Concurrency / editing lock (quiet admin override; show only on expiry) ----
        if ($last) {
            $lockBy = (int) $last->getIsEditing();
            $timeoutSec = 1200; // 20 minutes
            $ts = $last->getTimeEdit() ? self::toTimestamp($last->getTimeEdit()) : 0;
            $elapsed = time() - $ts;
            $expired = ($ts === 0) || ($elapsed >= $timeoutSec);
            $canOverride = api_is_allowed_to_edit(false, true) || api_is_platform_admin();

            self::dbg('lock check: lockBy='.$lockBy.' ts=' . ($ts ? date('c',$ts) : 'NULL') .
                ' elapsed='.$elapsed.' expired=' . ($expired?'1':'0') .
                ' canOverride=' . ($canOverride?'1':'0'));

            if ($lockBy !== 0 && $lockBy !== $userId) {
                if ($expired || $canOverride) {
                    // Take over the lock
                    $last->setIsEditing($userId);
                    $last->setTimeEdit(new \DateTime('now', new \DateTimeZone('UTC')));
                    $em->flush();

                    // Only notify if the previous lock actually expired; silent on teacher/admin override
                    if ($expired) {
                        Display::addFlash(
                            Display::return_message('The previous editing lock expired. You now have the lock.', 'normal', false)
                        );
                    }

                    self::dbg('lock takeover by user='.$userId.' (expired=' . ($expired?'1':'0') . ')');
                } else {
                    // Active lock and cannot override → inform and stop (no redirect)
                    $rest = max(0, $timeoutSec - $elapsed);
                    $info = api_get_user_info($lockBy);
                    if ($info) {
                        $msg = get_lang('At this time, this page is being edited by').PHP_EOL
                            .UserManager::getUserProfileLink($info).PHP_EOL
                            .get_lang('Please try again later. If the user who is currently editing the page does not save it, this page will be available to you around').PHP_EOL
                            .date('i', $rest).PHP_EOL
                            .get_lang('minutes');
                        Display::addFlash(Display::return_message($msg, 'normal', false));
                    } else {
                        Display::addFlash(Display::return_message('This page is currently being edited by another user.', 'normal', false));
                    }
                    self::dbg('stop: lock active and not override-able');
                    return;
                }
            }

            // If no lock, set it now (best-effort)
            if ($lockBy === 0) {
                Display::addFlash(Display::return_message(get_lang('You have 20 minutes to edit this page. After this time, if you have not saved the page, another user will be able to edit it, and you might lose your changes')));
                $last->setIsEditing($userId);
                $last->setTimeEdit(new \DateTime('now', new \DateTimeZone('UTC')));
                $em->flush();
                self::dbg('lock set by user='.$userId);
            }
        }

        // ------- FORM -------
        $url  = $ctx['baseUrl'].'&'.http_build_query(['action' => 'edit', 'title' => $page]);
        $form = new FormValidator('wiki', 'post', $url);
        $form->addElement('header', $iconAssignment.str_repeat('&nbsp;', 3).api_htmlentities($title));

        // Default values
        $row = [
            'id'              => (int)($last?->getIid() ?? 0),
            'page_id'         => (int)($last?->getPageId() ?? $pageId),
            'reflink'         => $page,
            'title'           => $title,
            'content'         => $content,
            'version'         => (int)($last?->getVersion() ?? 0),
            'progress'        => (string)($last?->getProgress() ?? ''),
            'comment'         => '',
            'assignment'      => (int)($last?->getAssignment() ?? 0),
        ];

        // Preselect categories
        if ($last && true === api_get_configuration_value('wiki_categories_enabled')) {
            /** @var CWiki $wikiRow */
            $wikiRow = $em->find(CWiki::class, (int)$last->getIid());
            foreach ($wikiRow->getCategories() as $category) {
                $row['category'][] = $category->getId();
            }
        }

        // Version guard in session
        Session::write('_version', (int)($row['version'] ?? 0));

        self::dbg('rendering edit form for page='.$page);
        self::setForm($form, $row);
        $form->addElement('hidden', 'title');
        $form->addButtonSave(get_lang('Save'), 'SaveWikiChange');

        $form->setDefaults($row);
        $form->display();

        // -------- SAVE ----------
        if ($form->validate()) {
            $values = $form->exportValues();

            if (empty($values['title'])) {
                Display::addFlash(Display::return_message(get_lang('Your changes have been saved. You still have to give a name to the page'), 'error'));
            } elseif (!self::double_post($values['wpost_id'])) {
                // ignore duplicate post
            } elseif (!empty($values['version'])
                && (int)Session::read('_version') !== 0
                && (int)$values['version'] !== (int)Session::read('_version')
            ) {
                Display::addFlash(Display::return_message(get_lang('Your changes will not be saved because another user has modified and saved the page while you were editing it yourself'), 'error'));
            } else {
                $returnMessage = self::saveWiki($values);
                Display::addFlash(Display::return_message($returnMessage, 'confirmation'));

                // Best-effort: clear lock after save
                if ($last) {
                    $last->setIsEditing(0);
                    $last->setTimeEdit(null);
                    $em->flush();
                }
            }

            $wikiData = $this->getWikiData();
            $redirectUrl = $ctx['baseUrl'].'&action=showpage&title='.urlencode(self::normalizeReflink($wikiData['reflink'] ?? $page));
            header('Location: '.$redirectUrl);
            exit;
        }
    }

    /** Public getter for the “view preload”. */
    public function getWikiData(): array
    {
        return $this->wikiData ?? [];
    }

    /** Very simple anti double-post using session. */
    public static function double_post($wpost_id): bool
    {
        $key = '_wiki_wpost_seen';
        $seen = (array) (Session::read($key) ?? []);
        if (in_array($wpost_id, $seen, true)) {
            return false;
        }
        $seen[] = $wpost_id;
        Session::write($key, $seen);
        return true;
    }

    /** Redirect helper to the main page. */
    private function redirectHome(): void
    {
        $ctx = self::ctx();
        $target = $ctx['baseUrl'].'&action=showpage&title='.urlencode($this->page ?: 'index');
        header('Location: '.$target);
        exit;
    }

    public static function setForm(FormValidator $form, array $row = []): void
    {
        // Toolbar by permissions
        $toolBar = api_is_allowed_to_edit(null, true)
            ? ['ToolbarSet' => 'Wiki', 'Width' => '100%', 'Height' => '400']
            : ['ToolbarSet' => 'WikiStudent', 'Width' => '100%', 'Height' => '400', 'UserStatus' => 'student'];

        // Content + comment
        $form->addHtmlEditor('content', get_lang('Content'), false, false, $toolBar);
        $form->addElement('text', 'comment', get_lang('Comments'));

        // Progress select (values 0..100 step 10)
        $progressValues = ['' => ''];
        for ($i = 10; $i <= 100; $i += 10) { $progressValues[(string)$i] = (string)$i; }
        // 5th parameter: attributes as array
        $form->addElement('select', 'progress', get_lang('Progress'), $progressValues, []);

        // Categories
        $catsEnabled = api_get_configuration_value('wiki_categories_enabled') === true
            || api_get_setting('wiki.wiki_categories_enabled') === 'true';

        if ($catsEnabled) {
            $em = Container::getEntityManager();
            $categories = $em->getRepository(CWikiCategory::class)->findByCourse(api_get_course_entity());

            $form->addSelectFromCollection(
                'category',
                get_lang('Categories'),
                $categories,
                ['multiple' => 'multiple'],
                false,
                'getNodeName'
            );
        }

        // Advanced params (only for teachers/admin and not on index)
        if ((api_is_allowed_to_edit(false, true) || api_is_platform_admin())
            && isset($row['reflink']) && $row['reflink'] !== 'index'
        ) {
            $form->addElement('advanced_settings', 'advanced_params', get_lang('Advanced settings'));
            $form->addElement('html', '<div id="advanced_params_options" style="display:none">');

            // Task description
            $form->addHtmlEditor(
                'task',
                get_lang('Description of the assignment'),
                false,
                false,
                ['ToolbarSet' => 'wiki_task', 'Width' => '100%', 'Height' => '200']
            );

            // Feedbacks + progress goals
            $form->addElement('label', null, get_lang('Add guidance messages associated with the progress on the page'));

            $form->addElement('textarea', 'feedback1', get_lang('First message'));
            $form->addElement('select', 'fprogress1', get_lang('Progress'), $progressValues, []);

            $form->addElement('textarea', 'feedback2', get_lang('Second message'));
            $form->addElement('select', 'fprogress2', get_lang('Progress'), $progressValues, []);

            $form->addElement('textarea', 'feedback3', get_lang('Third message'));
            $form->addElement('select', 'fprogress3', get_lang('Progress'), $progressValues, []);

            // Dates (toggles)
            $form->addElement('checkbox', 'initstartdate', null, get_lang('Start date'), ['id' => 'start_date_toggle']);
            $row['initstartdate'] = empty($row['startdate_assig']) ? null : 1;
            $style = empty($row['startdate_assig']) ? 'display:none' : 'display:block';
            $form->addElement('html', '<div id="start_date" style="'.$style.'">');
            $form->addDatePicker('startdate_assig', '');
            $form->addElement('html', '</div>');

            $form->addElement('checkbox', 'initenddate', null, get_lang('End date'), ['id' => 'end_date_toggle']);
            $row['initenddate'] = empty($row['enddate_assig']) ? null : 1;
            $style = empty($row['enddate_assig']) ? 'display:none' : 'display:block';
            $form->addElement('html', '<div id="end_date" style="'.$style.'">');
            $form->addDatePicker('enddate_assig', '');
            $form->addElement('html', '</div>');

            // Limits & flags
            $form->addElement('checkbox', 'delayedsubmit', null, get_lang('Allow delayed sending'));
            $form->addElement('text', 'max_text', get_lang('Maximum number of words'));
            $form->addElement('text', 'max_version', get_lang('Maximum number of versions'));
            $form->addElement('checkbox', 'assignment', null, get_lang('This will create a special wiki page in which the teacher can describe the task and which will be automatically linked to the wiki pages where learners perform the task. Both the teacher\'s and the learners\' pages are created automatically. In these tasks, learners can only edit and view theirs pages, but this can be changed easily if you need to.'));

            $form->addElement('html', '</div>');
        }

        // Hidden fields
        $form->addElement('hidden', 'page_id');
        $form->addElement('hidden', 'reflink');
        $form->addElement('hidden', 'version');
        $form->addElement('hidden', 'wpost_id', api_get_unique_id());
    }


    /** Return all rows being edited (is_editing != 0) respecting session condition. */
    public static function getAllWiki(?int $courseId = null, ?int $sessionId = null): array
    {
        $ctx  = self::ctx($courseId, $sessionId, null);
        $repo = self::repo();

        $qb = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.isEditing, 0) <> 0')
            ->orderBy('w.timeEdit', 'DESC');

        if ($ctx['sessionId'] > 0) {
            $qb->andWhere('(COALESCE(w.sessionId,0) = 0 OR w.sessionId = :sid)')
                ->setParameter('sid', $ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        return $qb->getQuery()->getArrayResult();
    }

    /** If "view" is an old version, show a notice against latest. */
    public function checkLastVersion($viewId): void
    {
        if (empty($viewId)) {
            return;
        }
        $ctx = self::ctx();
        $em  = Container::getEntityManager();

        /** @var CWiki|null $row */
        $row = $em->getRepository(CWiki::class)->find((int)$viewId);
        if (!$row) {
            return;
        }

        $qb = $em->getRepository(CWiki::class)->createQueryBuilder('w')
            ->select('w.iid')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.pageId = :pid')->setParameter('pid', (int)$row->getPageId())
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'DESC')
            ->setMaxResults(1);

        if ($ctx['sessionId'] > 0) {
            $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        $latest = $qb->getQuery()->getOneOrNullResult();
        if ($latest && (int)($latest['iid'] ?? 0) !== (int)$viewId) {
            Display::addFlash(
                Display::return_message(get_lang('You are not viewing the most recent version'), 'warning', false)
            );
        }
    }

    /** Top action bar (classic look). */
    public function showActionBar(): void
    {
        $ctx   = self::ctx();
        $page  = (string) $this->page;
        $left  = '';

        $left .= Display::url(
            Display::getMdiIcon(ActionIcon::HOME, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Home')),
            $ctx['baseUrl'].'&'.http_build_query(['action' => 'showpage', 'title' => 'index'])
        );

        if (api_is_allowed_to_session_edit(false, true) && api_is_allowed_to_edit()) {
            $left .= Display::url(
                Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add new page')),
                $ctx['baseUrl'].'&action=addnew'
            );
        }

        if (self::categoriesEnabled() && (api_is_allowed_to_edit(false, true) || api_is_platform_admin())) {
            $left .= Display::url(
                Display::getMdiIcon(ActionIcon::CREATE_CATEGORY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Categories')),
                $ctx['baseUrl'].'&action=category'
            );

            $addNewStatus = (int) self::check_addnewpagelock();
            if ($addNewStatus === 0) {
                $left .= Display::url(
                    Display::getMdiIcon(ActionIcon::LOCK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('The Add option has been protected. Trainers only can add pages to this Wiki. But learners and group members can still edit them')),
                    $ctx['baseUrl'].'&'.http_build_query([
                        'action'     => 'showpage',
                        'title'      => api_htmlentities('index'),
                        'actionpage' => 'unlockaddnew',
                    ])
                );
            } else {
                $left .= Display::url(
                    Display::getMdiIcon(ActionIcon::UNLOCK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('The add option has been enabled for all course users and group members')),
                    $ctx['baseUrl'].'&'.http_build_query([
                        'action'     => 'showpage',
                        'title'      => api_htmlentities('index'),
                        'actionpage' => 'lockaddnew',
                    ])
                );
            }
        }

        $left .= Display::url(
            Display::getMdiIcon(ActionIcon::SEARCH, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Search')),
            $ctx['baseUrl'].'&action=searchpages'
        );

        $left .= Display::url(
            Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Statistics')),
            $ctx['baseUrl'].'&'.http_build_query(['action' => 'more', 'title' => api_htmlentities(urlencode($page))])
        );

        $left .= Display::url(
            Display::getMdiIcon(ActionIcon::LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('All pages')),
            $ctx['baseUrl'].'&action=allpages'
        );

        $left .= Display::url(
            Display::getMdiIcon(ActionIcon::HISTORY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Latest changes')),
            $ctx['baseUrl'].'&action=recentchanges'
        );

        $frm = new FormValidator('wiki_search', 'get', $ctx['baseUrl'], '', [], FormValidator::LAYOUT_INLINE);
        $frm->addText('search_term', get_lang('Search term'), false);
        $frm->addHidden('cid',     $ctx['courseId']);
        $frm->addHidden('sid', $ctx['sessionId']);
        $frm->addHidden('gid',     $ctx['groupId']);
        $frm->addHidden('gradebook',  '0');
        $frm->addHidden('origin',     '');
        $frm->addHidden('action',     'searchpages');
        $frm->addButtonSearch(get_lang('Search'));
        $right = $frm->returnForm();

        echo self::twToolbarHtml($left, $right);
    }

    /** Concurrency guard: mark/unmark is_editing for current page. */
    public function blockConcurrentEditions(int $userId, string $action): void
    {
        try {
            $ctx = self::ctx();
            $em  = Container::getEntityManager();

            if ($action === 'edit' && !empty($this->page)) {
                $em->createQuery('UPDATE Chamilo\CourseBundle\Entity\CWiki w
                    SET w.isEditing = 1, w.timeEdit = :now
                    WHERE w.cId = :cid AND w.reflink = :r AND COALESCE(w.groupId,0) = :gid')
                    ->setParameter('now', api_get_utc_datetime(null, false, true))
                    ->setParameter('cid', $ctx['courseId'])
                    ->setParameter('r', html_entity_decode($this->page))
                    ->setParameter('gid', (int)$ctx['groupId'])
                    ->execute();
            } else {
                $em->createQuery('UPDATE Chamilo\CourseBundle\Entity\CWiki w
                    SET w.isEditing = 0
                    WHERE w.cId = :cid AND COALESCE(w.groupId,0) = :gid AND COALESCE(w.sessionId,0) = :sid')
                    ->setParameter('cid', $ctx['courseId'])
                    ->setParameter('gid', (int)$ctx['groupId'])
                    ->setParameter('sid', (int)$ctx['sessionId'])
                    ->execute();
            }
        } catch (\Throwable $e) {
            // silent best-effort
        }
    }

    public static function delete_wiki(?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): string
    {

        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $em   = Container::getEntityManager();
        $conn = $em->getConnection();

        $cid = (int) $ctx['courseId'];
        $gid = (int) $ctx['groupId'];
        $sid = (int) $ctx['sessionId'];

        $predGroup   = $gid === 0 ? '(group_id IS NULL OR group_id = 0)'     : 'group_id = :gid';
        $predSession = $sid === 0 ? '(session_id IS NULL OR session_id = 0)' : 'session_id = :sid';

        $pre = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM c_wiki WHERE c_id = :cid AND $predGroup AND $predSession",
            array_filter(['cid'=>$cid,'gid'=>$gid,'sid'=>$sid], static fn($v)=>true),
        );

        if ($pre === 0) {
            return get_lang('Your Wiki has been deleted').' (0 rows in this context)';
        }

        $conn->beginTransaction();
        try {
            $deletedDiscuss = $conn->executeStatement(
                "DELETE d FROM c_wiki_discuss d
             WHERE d.c_id = :cid
               AND d.publication_id IN (
                    SELECT DISTINCT w.page_id
                    FROM c_wiki w
                    WHERE w.c_id = :cid
                      AND $predGroup
                      AND $predSession
               )",
                array_filter(['cid'=>$cid,'gid'=>$gid,'sid'=>$sid], static fn($v)=>true),
            );

            $deletedConf = $conn->executeStatement(
                "DELETE c FROM c_wiki_conf c
             WHERE c.c_id = :cid
               AND c.page_id IN (
                    SELECT DISTINCT w.page_id
                    FROM c_wiki w
                    WHERE w.c_id = :cid
                      AND $predGroup
                      AND $predSession
               )",
                array_filter(['cid'=>$cid,'gid'=>$gid,'sid'=>$sid], static fn($v)=>true),
            );

            $deletedRelCat = $conn->executeStatement(
                "DELETE rc FROM c_wiki_rel_category rc
             WHERE rc.wiki_id IN (
                SELECT w.iid
                FROM c_wiki w
                WHERE w.c_id = :cid
                  AND $predGroup
                  AND $predSession
             )",
                array_filter(['cid'=>$cid,'gid'=>$gid,'sid'=>$sid], static fn($v)=>true),
            );

            $deletedMailcue = $conn->executeStatement(
                "DELETE m FROM c_wiki_mailcue m
             WHERE m.c_id = :cid
               AND ".($gid === 0 ? '(m.group_id IS NULL OR m.group_id = 0)' : 'm.group_id = :gid')."
               AND ".($sid === 0 ? '(m.session_id IS NULL OR m.session_id = 0)' : 'm.session_id = :sid'),
                array_filter(['cid'=>$cid,'gid'=>$gid,'sid'=>$sid], static fn($v)=>true),
            );

            $deletedWiki = $conn->executeStatement(
                "DELETE w FROM c_wiki w
             WHERE w.c_id = :cid
               AND $predGroup
               AND $predSession",
                array_filter(['cid'=>$cid,'gid'=>$gid,'sid'=>$sid], static fn($v)=>true),
            );

            $conn->commit();

            return get_lang('Your Wiki has been deleted')." (versions=$deletedWiki, comments=$deletedDiscuss, conf=$deletedConf, catRel=$deletedRelCat, watchers=$deletedMailcue)";
        } catch (\Throwable $e) {
            $conn->rollBack();
            // Short and clear message
            return get_lang('Delete error');
        }
    }

    /** Returns true if there is at least one version of a page (reflink) in the given context */
    private static function existsByReflink(string $reflink, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): bool
    {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $qb   = self::repo()->createQueryBuilder('w')
            ->select('COUNT(w.iid)')
            ->andWhere('w.cId = :cid')->setParameter('cid', (int)$ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);

        if ((int)$ctx['sessionId'] > 0) {
            $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        return ((int)$qb->getQuery()->getSingleScalarResult()) > 0;
    }

    /**
     * Core save (new page or new version). Single source of truth.
     */
    public static function saveWiki(array $values, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): string
    {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $em   = Container::getEntityManager();
        $repo = self::repo();
        $conn = $em->getConnection();

        $userId = api_get_user_id();
        $now    = new \DateTime('now', new \DateTimeZone('UTC'));

        // --- sanitize + normalize ---
        $rawTitle = trim((string)($values['title'] ?? ''));
        if ($rawTitle === '') {
            return get_lang('Your changes have been saved. You still have to give a name to the page');
        }

        // Prepare safe strings (emoji-safe)
        $values['title']   = self::utf8mb4_safe_entities((string) ($values['title']   ?? ''));
        $values['content'] = self::utf8mb4_safe_entities((string) ($values['content'] ?? ''));
        $values['comment'] = self::utf8mb4_safe_entities((string) ($values['comment'] ?? ''));

        $content = $values['content'] ?? '';
        if ($content === '') {
            $content = '<p>&nbsp;</p>'; // minimal content
        }
        if (api_get_setting('htmlpurifier_wiki') === 'true') {
            $content = Security::remove_XSS($content);
        }

        // Extract link tokens ([[...]])
        $linkTo = self::links_to($content);

        // Create vs update
        $incomingPageId = (int)($values['page_id'] ?? 0);
        $isNewPage      = ($incomingPageId === 0);

        // ---------- Determine reflink (KEY FIX) ----------
        // Prefer an explicit 'reflink' if provided; else derive from the typed title.
        $explicitRef = trim((string)($values['reflink'] ?? ''));
        $candidate   = $explicitRef !== '' ? $explicitRef : $rawTitle;

        if ($isNewPage) {
            // For NEW pages, build the reflink from what the user typed, NOT from any outer GET param.
            // Normalize but only collapse to 'index' if the user explicitly typed an alias of Home.
            $reflink = self::normalizeToken($candidate);

            $homeAliases = array_filter([
                'index',
                self::normalizeToken((string) (get_lang('Home') ?: 'Home')),
            ]);

            if (in_array($reflink, $homeAliases, true)) {
                $reflink = 'index';
            }
        } else {
            // For existing pages, keep behavior consistent with previous code
            $reflink = self::normalizeReflink($candidate);
        }

        if (method_exists(__CLASS__, 'dbg')) {
            self::dbg('[SAVE] isNewPage=' . ($isNewPage ? '1' : '0')
                . ' | rawTitle=' . $rawTitle
                . ' | explicitRef=' . ($explicitRef === '' ? '(empty)' : $explicitRef)
                . ' | computedReflink=' . $reflink
                . ' | cid='.(int)$ctx['courseId'].' gid='.(int)$ctx['groupId'].' sid='.(int)$ctx['sessionId']);
        }

        // --- If NEW page: abort if reflink already exists in this context ---
        if ($isNewPage) {
            $qbExists = $repo->createQueryBuilder('w')
                ->select('w.iid')
                ->andWhere('w.cId = :cid')->setParameter('cid', (int)$ctx['courseId'])
                ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);

            if ((int)$ctx['sessionId'] > 0) {
                $qbExists->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
            } else {
                $qbExists->andWhere('COALESCE(w.sessionId,0) = 0');
            }

            $qbExists->orderBy('w.version', 'DESC')->setMaxResults(1);

            if (method_exists(__CLASS__, 'dbg')) {
                $dql = $qbExists->getDQL();
                $sql = $qbExists->getQuery()->getSQL();
                $params = $qbExists->getQuery()->getParameters();
                $types  = [];
                foreach ($params as $p) { $types[$p->getName()] = $p->getType(); }
                self::dbg('[EXISTS DQL] '.$dql);
                self::dbg('[EXISTS SQL] '.$sql);
                self::dbg('[EXISTS PARAMS] '.json_encode(array_reduce(iterator_to_array($params), function($a,$p){$a[$p->getName()]=$p->getValue();return $a;}, [])));
                self::dbg('[EXISTS TYPES] '.json_encode($types));
            }

            $exists = (bool) $qbExists->getQuery()->getOneOrNullResult();
            if ($exists) {
                return get_lang('The page already exists.');
            }
        }

        // --- Find latest version if NOT new (by page_id) ---
        $last = null;
        if (!$isNewPage) {
            $qb = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', (int)$ctx['courseId'])
                ->andWhere('w.pageId = :pid')->setParameter('pid', $incomingPageId)
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->orderBy('w.version', 'DESC')
                ->setMaxResults(1);

            if ((int)$ctx['sessionId'] > 0) {
                $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
            } else {
                $qb->andWhere('COALESCE(w.sessionId,0) = 0');
            }

            /** @var CWiki|null $last */
            $last = $qb->getQuery()->getOneOrNullResult();
        }

        // base version and pageId
        $version = $last ? ((int) $last->getVersion() + 1) : 1;
        $pageId  = (int) $last?->getPageId();

        $w = new CWiki();
        $w->setCId((int) $ctx['courseId']);
        $w->setPageId($pageId);
        $w->setReflink($reflink);
        $w->setTitle($values['title']);
        $w->setContent($content);
        $w->setUserId($userId);

        // group/session as ints (0 = none)
        $w->setGroupId((int) $ctx['groupId']);
        $w->setSessionId((int) $ctx['sessionId']);

        $w->setDtime($now);

        // inherit flags or defaults
        $w->setAddlock(       $last ? $last->getAddlock()       : 1);
        $w->setEditlock(      $last ? (int) $last->getEditlock()      : 0);
        $w->setVisibility(    $last ? $last->getVisibility() : 1);
        $w->setAddlockDisc(   $last ? $last->getAddlockDisc() : 1);
        $w->setVisibilityDisc($last ? $last->getVisibilityDisc() : 1);
        $w->setRatinglockDisc($last ? $last->getRatinglockDisc() : 1);

        $w->setAssignment((int) ($values['assignment'] ?? ($last ? (int) $last->getAssignment() : 0)));
        $w->setComment((string) ($values['comment']  ?? ''));
        $w->setProgress((string) ($values['progress'] ?? ''));
        $w->setScore($last ? ((int) $last->getScore() ?: 0) : 0);

        $w->setVersion($version);
        $w->setIsEditing(0);
        $w->setTimeEdit(null);
        $w->setHits($last ? ((int) $last->getHits() ?: 0) : 0);

        $w->setLinksto($linkTo);
        $w->setTag('');
        $w->setUserIp(api_get_real_ip());

        $w->setParent($ctx['course']);
        $w->setCreator(api_get_user_entity());
        $groupEntity = $ctx['groupId'] ? api_get_group_entity((int)$ctx['groupId']) : null;
        $w->addCourseLink($ctx['course'], $ctx['session'], $groupEntity);

        // Categories
        if (true === api_get_configuration_value('wiki_categories_enabled')) {
            $catIds = (array)($values['category'] ?? []);
            if (!empty($catIds)) {
                $catRepo = $em->getRepository(CWikiCategory::class);
                foreach ($catIds as $catId) {
                    $cat = $catRepo->find((int) $catId);
                    if ($cat) { $w->addCategory($cat); }
                }
            }
        }

        $em->persist($w);
        $em->flush();

        if (method_exists(__CLASS__, 'dbg')) {
            self::dbg('[SAVE] after first flush iid='.(int)$w->getIid().' pageId='.(int)$w->getPageId().' reflink='.$reflink);
        }

        // If FIRST version of a new page, set page_id = iid
        if ($isNewPage) {
            $w->setPageId((int) $w->getIid());
            $em->flush();
            if (method_exists(__CLASS__, 'dbg')) {
                self::dbg('[SAVE] after setPageId flush iid='.(int)$w->getIid().' pageId='.(int)$w->getPageId());
            }
            $pageId = (int) $w->getPageId();
        } else {
            $pageId = (int)$incomingPageId;
        }

        // DB sanity check
        $check = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM c_wiki
         WHERE c_id = :cid
           AND reflink = :r
           AND COALESCE(group_id,0) = :gid
           AND '.((int)$ctx['sessionId'] > 0 ? '(COALESCE(session_id,0) IN (0,:sid))' : 'COALESCE(session_id,0) = 0'),
            [
                'cid' => (int)$ctx['courseId'],
                'r'   => $reflink,
                'gid' => (int)$ctx['groupId'],
                'sid' => (int)$ctx['sessionId'],
            ]
        );

        if (method_exists(__CLASS__, 'dbg')) {
            self::dbg('[SAVE] db count after save='.$check.' (reflink='.$reflink.')');
        }

        if ($check === 0) {
            throw new \RuntimeException('Wiki save failed: no row inserted (cid='.$ctx['courseId'].', reflink='.$reflink.', gid='.$ctx['groupId'].', sid='.$ctx['sessionId'].')');
        }

        // ---- CWikiConf ----
        $hasConfFields = isset($values['task']) || isset($values['feedback1']) || isset($values['feedback2'])
            || isset($values['feedback3']) || isset($values['fprogress1']) || isset($values['fprogress2'])
            || isset($values['fprogress3']) || isset($values['max_text']) || isset($values['max_version'])
            || array_key_exists('startdate_assig', $values) || array_key_exists('enddate_assig', $values)
            || isset($values['delayedsubmit']);

        if ($version === 1 && $hasConfFields) {
            $conf = new CWikiConf();
            $conf->setCId((int) $ctx['courseId']);
            $conf->setPageId($pageId);
            $conf->setTask((string) ($values['task'] ?? ''));
            $conf->setFeedback1((string) ($values['feedback1'] ?? ''));
            $conf->setFeedback2((string) ($values['feedback2'] ?? ''));
            $conf->setFeedback3((string) ($values['feedback3'] ?? ''));
            $conf->setFprogress1((string) ($values['fprogress1'] ?? ''));
            $conf->setFprogress2((string) ($values['fprogress2'] ?? ''));
            $conf->setFprogress3((string) ($values['fprogress3'] ?? ''));
            $conf->setMaxText((int) ($values['max_text'] ?? 0));
            $conf->setMaxVersion((int) ($values['max_version'] ?? 0));
            $conf->setStartdateAssig(self::toDateTime($values['startdate_assig'] ?? null));
            $conf->setEnddateAssig(self::toDateTime($values['enddate_assig'] ?? null));
            $conf->setDelayedsubmit((int) ($values['delayedsubmit'] ?? 0));
            $em->persist($conf);
            $em->flush();
        } elseif ($hasConfFields) {
            /** @var CWikiConf|null $conf */
            $conf = self::confRepo()->findOneBy(['cId' => (int) $ctx['courseId'], 'pageId' => $pageId]);
            if ($conf) {
                $conf->setTask((string) ($values['task'] ?? $conf->getTask()));
                $conf->setFeedback1((string) ($values['feedback1'] ?? $conf->getFeedback1()));
                $conf->setFeedback2((string) ($values['feedback2'] ?? $conf->getFeedback2()));
                $conf->setFeedback3((string) ($values['feedback3'] ?? $conf->getFeedback3()));
                $conf->setFprogress1((string) ($values['fprogress1'] ?? $conf->getFprogress1()));
                $conf->setFprogress2((string) ($values['fprogress2'] ?? $conf->getFprogress2()));
                $conf->setFprogress3((string) ($values['fprogress3'] ?? $conf->getFprogress3()));
                if (isset($values['max_text']))    { $conf->setMaxText((int) $values['max_text']); }
                if (isset($values['max_version'])) { $conf->setMaxVersion((int) $values['max_version']); }
                if (array_key_exists('startdate_assig', $values)) { $conf->setStartdateAssig(self::toDateTime($values['startdate_assig'])); }
                if (array_key_exists('enddate_assig',   $values)) { $conf->setEnddateAssig(self::toDateTime($values['enddate_assig'])); }
                if (isset($values['delayedsubmit'])) { $conf->setDelayedsubmit((int) $values['delayedsubmit']); }
                $em->flush();
            }
        }

        // Notify watchers (legacy: 'P' = page change)
        self::check_emailcue($reflink, 'P', $now, $userId);

        return $isNewPage ? get_lang('The new page has been created.') : get_lang('Saved');
    }


    /**
     * Compat wrappers (to avoid breaking old calls).
     */
    public static function save_wiki(array $values, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): string
    {
        return self::saveWiki($values, $courseId, $sessionId, $groupId);
    }

    public static function save_new_wiki(array $values, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): string|false
    {
        $msg = self::saveWiki($values, $courseId, $sessionId, $groupId);

        return $msg === get_lang('Your changes have been saved. You still have to give a name to the page') ? false : $msg;
    }

    /**
     * Send email notifications to watchers.
     * @param int|string $id_or_ref  'P' => reflink | 'D' => iid of CWiki row | 'A'/'E' => 0
     */
    public static function check_emailcue($id_or_ref, string $type, $lastime = '', $lastuser = ''): void
    {
        $ctx = self::ctx(api_get_course_int_id(), api_get_session_id(), api_get_group_id());
        $em  = Container::getEntityManager();

        $allowSend        = false;
        $emailAssignment  = null;
        $emailPageName    = '';
        $emailDateChanges = '';
        $emailText        = '';
        $watchKey         = null;
        $pageReflink      = null;

        // When timestamp provided
        if ($lastime instanceof \DateTimeInterface) {
            $emailDateChanges = $lastime->format('Y-m-d H:i:s');
        } elseif (is_string($lastime) && $lastime !== '') {
            $emailDateChanges = $lastime;
        }

        // Author line
        $emailUserAuthor = '';
        if ($lastuser) {
            $ui = api_get_user_info((int) $lastuser);
            $emailUserAuthor = ($type === 'P' || $type === 'D')
                ? get_lang('edited by').': '.($ui['complete_name'] ?? '')
                : get_lang('added by').': '.($ui['complete_name'] ?? '');
        } else {
            $ui = api_get_user_info(api_get_user_id());
            $emailUserAuthor = ($type === 'E')
                ? get_lang('deleted by').': '.($ui['complete_name'] ?? '')
                : get_lang('edited by').': '.($ui['complete_name'] ?? '');
        }

        $repoWiki = $em->getRepository(CWiki::class);
        $repoCue  = $em->getRepository(CWikiMailcue::class);

        // --- Resolve page + message according to event type ---
        if ($type === 'P') {
            // Page modified -> $id_or_ref is a reflink
            /** @var CWiki|null $first */
            $first = $repoWiki->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode((string)$id_or_ref))
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->orderBy('w.version', 'ASC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();

            if ($first) {
                $emailPageName = (string) $first->getTitle();
                $pageReflink   = (string) $first->getReflink();
                if ((int) $first->getVisibility() === 1) {
                    $allowSend = true;
                    $emailText = get_lang('It has modified the page').' <strong>'.$emailPageName.'</strong> '.get_lang('Wiki');
                    $watchKey  = 'watch:'.$pageReflink;
                }
            }
        } elseif ($type === 'D') {
            // New discussion comment -> $id_or_ref is publication_id (page_id)
            /** @var CWiki|null $row */
            $row = $repoWiki->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.pageId = :pid')->setParameter('pid', (int)$id_or_ref)
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->orderBy('w.version', 'DESC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();

            if ($row) {
                $emailPageName = (string) $row->getTitle();
                $pageReflink   = (string) $row->getReflink();
                if ((int) $row->getVisibilityDisc() === 1) {
                    $allowSend = true;
                    $emailText = get_lang('New comment in the discussion of the page').' <strong>'.$emailPageName.'</strong> '.get_lang('Wiki');
                    $watchKey  = 'watchdisc:'.$pageReflink;
                }
            }
        } elseif ($type === 'A') {
            // New page added (find latest row in this context)
            /** @var CWiki|null $row */
            $row = $repoWiki->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId'])
                ->orderBy('w.iid', 'DESC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();

            if ($row) {
                $emailPageName    = (string) $row->getTitle();
                $pageReflink      = (string) $row->getReflink();
                $emailDateChanges = $row->getDtime() ? $row->getDtime()->format('Y-m-d H:i:s') : $emailDateChanges;

                if ((int) $row->getAssignment() === 0) {
                    $allowSend = true;
                } elseif ((int) $row->getAssignment() === 1) {
                    $emailAssignment = get_lang('This page is an assignment proposed by a trainer').' ('.get_lang('Individual assignment mode').')';
                    $allowSend = true;
                } elseif ((int) $row->getAssignment() === 2) {
                    $allowSend = false; // teacher-locked work page
                }

                $emailText = get_lang('Page was added').' <strong>'.$emailPageName.'</strong> '.get_lang('in').' '.get_lang('Wiki');
                // If someone subscribed after creation, use the same key as page watchers
                $watchKey  = 'watch:'.$pageReflink;
            }
        } elseif ($type === 'E') {
            // Page deleted (generic)
            $allowSend = true;
            $emailText = get_lang('One page has been deleted in the Wiki');
            if ($emailDateChanges === '') {
                $emailDateChanges = date('Y-m-d H:i:s');
            }
        }

        if (!$allowSend) {
            return;
        }

        $courseInfo  = $ctx['courseInfo'] ?: (api_get_course_info_by_id((int)$ctx['courseId']) ?: []);
        $courseTitle = $courseInfo['title'] ?? ($courseInfo['name'] ?? '');
        $courseName  = $courseInfo['name']  ?? $courseTitle;

        // Group/session labels
        $grpName = '';
        if ((int)$ctx['groupId'] > 0) {
            $g = GroupManager::get_group_properties((int)$ctx['groupId']);
            $grpName = $g['name'] ?? '';
        }
        $sessionName = ((int)$ctx['sessionId'] > 0) ? api_get_session_name((int)$ctx['sessionId']) : '';

        // --- Fetch watchers filtered by type (when available) ---
        $qb = $repoCue->createQueryBuilder('m')
            ->andWhere('m.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(m.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->andWhere('COALESCE(m.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);

        // Only mail the relevant subscribers
        if (!empty($watchKey)) {
            $qb->andWhere('m.type = :t')->setParameter('t', $watchKey);
        }

        $watchers = $qb->getQuery()->getArrayResult();
        if (empty($watchers)) {
            return;
        }

        // Optional logo
        $extraParams = [];
        if (api_get_configuration_value('mail_header_from_custom_course_logo') === true) {
            $extraParams = ['logo' => CourseManager::getCourseEmailPicture($courseInfo)];
        }

        foreach ($watchers as $w) {
            $uid = (int) ($w['userId'] ?? 0);
            if ($uid === 0) {
                continue;
            }
            // Do not email the actor themself
            if ($lastuser && (int)$lastuser === $uid) {
                continue;
            }

            $uInfo = api_get_user_info($uid);
            if (!$uInfo || empty($uInfo['email'])) {
                continue;
            }

            $nameTo  = $uInfo['complete_name'];
            $emailTo = $uInfo['email'];
            $from    = (string) api_get_setting('emailAdministrator');

            $subject = get_lang('Notify Wiki changes').' - '.$courseTitle;

            $body  = get_lang('Dear user').' '.api_get_person_name($uInfo['firstname'] ?? '', $uInfo['lastname'] ?? '').',<br /><br />';
            if ((int)$ctx['sessionId'] === 0) {
                $body .= $emailText.' <strong>'.$courseName.($grpName ? ' - '.$grpName : '').'</strong><br /><br /><br />';
            } else {
                $body .= $emailText.' <strong>'.$courseName.' ('.$sessionName.')'.($grpName ? ' - '.$grpName : '').'</strong><br /><br /><br />';
            }
            if ($emailUserAuthor) {
                $body .= $emailUserAuthor.($emailDateChanges ? ' ('.$emailDateChanges.')' : '').'<br /><br /><br />';
            }
            if ($emailAssignment) {
                $body .= $emailAssignment.'<br /><br /><br />';
            }
            $body .= '<span style="font-size:70%;">'.get_lang('This notification has been made in accordance with their desire to monitor changes in the Wiki. This option means you have activated the button').': <strong>'.get_lang('Notify me of changes').'</strong><br />';
            $body .= get_lang('If you want to stop being notified of changes in the Wiki, select the tabs<strong> Recent Changes</ strong>, <strong>Current page</ strong>, <strong>Talk</ strong> as appropriate and then push the button').': <strong>'.get_lang('Do not notify me of changes').'</strong></span><br />';

            @api_mail_html(
                $nameTo,
                $emailTo,
                $subject,
                $body,
                $from,
                $from,
                [],
                [],
                false,
                $extraParams,
                ''
            );
        }
    }

    /** Full view (classic structure + modern toolbar wrapper) */
    public static function display_wiki_entry(
        string $newtitle,
        ?string $page = null,
        ?int $courseId = null,
        ?int $sessionId = null,
        ?int $groupId = null
    ): ?string {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $em   = Container::getEntityManager();
        $repo = self::repo();

        // Resolve the page key we will work with
        $pageKey = self::normalizeReflink($newtitle !== '' ? $newtitle : ($page ?? null));

        // --- ONE toggle block (lock/visible/notify) with PRG redirect ---
        $actionPage = $_GET['actionpage'] ?? null;
        if ($actionPage !== null) {
            $allowed = ['lock','unlock','visible','invisible','locknotify','unlocknotify'];

            if (in_array($actionPage, $allowed, true)) {
                $conn = $em->getConnection();
                $cid  = (int)$ctx['courseId'];
                $gid  = (int)$ctx['groupId'];
                $sid  = (int)$ctx['sessionId'];
                $uid  = (int)api_get_user_id();

                $predG = 'COALESCE(group_id,0) = :gid';
                $predS = 'COALESCE(session_id,0) = :sid';

                switch ($actionPage) {
                    case 'lock':
                    case 'unlock':
                        // Only teachers/admins can toggle lock
                        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
                            Display::addFlash(Display::return_message('Not allowed to lock/unlock this page.', 'error', false));
                            break;
                        }
                        $newVal = ($actionPage === 'lock') ? 1 : 0;
                        $conn->executeStatement(
                            "UPDATE c_wiki SET editlock = :v
                         WHERE c_id = :cid AND reflink = :r AND $predG AND $predS",
                            ['v'=>$newVal, 'cid'=>$cid, 'r'=>$pageKey, 'gid'=>$gid, 'sid'=>$sid]
                        );
                        break;

                    case 'visible':
                    case 'invisible':
                        // Only teachers/admins can toggle visibility
                        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
                            Display::addFlash(Display::return_message('Not allowed to change visibility.', 'error', false));
                            break;
                        }
                        $newVal = ($actionPage === 'visible') ? 1 : 0;
                        $conn->executeStatement(
                            "UPDATE c_wiki SET visibility = :v
                         WHERE c_id = :cid AND reflink = :r AND $predG AND $predS",
                            ['v'=>$newVal, 'cid'=>$cid, 'r'=>$pageKey, 'gid'=>$gid, 'sid'=>$sid]
                        );
                        break;

                    case 'locknotify':
                    case 'unlocknotify':
                        // Session editors can subscribe/unsubscribe
                        if (!api_is_allowed_to_session_edit()) {
                            Display::addFlash(Display::return_message('Not allowed to (un)subscribe notifications.', 'error', false));
                            break;
                        }
                        $watchKey = 'watch:'.$pageKey;

                        if ($actionPage === 'locknotify') {
                            // Insert if not exists
                            $conn->executeStatement(
                                "INSERT INTO c_wiki_mailcue (c_id, group_id, session_id, user_id, type)
                             SELECT :cid, :gid, :sid, :uid, :t
                               FROM DUAL
                              WHERE NOT EXISTS (
                                  SELECT 1 FROM c_wiki_mailcue
                                   WHERE c_id = :cid AND $predG AND $predS
                                     AND user_id = :uid AND type = :t
                              )",
                                ['cid'=>$cid, 'gid'=>$gid, 'sid'=>$sid, 'uid'=>$uid, 't'=>$watchKey]
                            );
                        } else { // unlocknotify
                            $conn->executeStatement(
                                "DELETE FROM c_wiki_mailcue
                              WHERE c_id = :cid AND $predG AND $predS
                                AND user_id = :uid AND type = :t",
                                ['cid'=>$cid, 'gid'=>$gid, 'sid'=>$sid, 'uid'=>$uid, 't'=>$watchKey]
                            );
                        }
                        break;
                }

                // PRG redirect so icons reflect the change immediately
                header('Location: '.$ctx['baseUrl'].'&action=showpage&title='.urlencode($pageKey));
                exit;
            }
        }

        // Resolve FIRST row in the right scope (session-aware)
        $effectiveSid = (int) $ctx['sessionId'];

        $firstQb = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :reflink')->setParameter('reflink', $pageKey)
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int) $ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1);

        if ($effectiveSid > 0) {
            $firstQb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $effectiveSid);
        } else {
            $firstQb->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        /** @var CWiki|null $first */
        $first = $firstQb->getQuery()->getOneOrNullResult();

        // if we are in session and no session page exists, show base course wiki
        if (!$first && $effectiveSid > 0) {
            $effectiveSid = 0;

            $first = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.reflink = :reflink')->setParameter('reflink', $pageKey)
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int) $ctx['groupId'])
                ->andWhere('COALESCE(w.sessionId,0) = 0')
                ->orderBy('w.version', 'ASC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
        }

        $keyVisibility = $first?->getVisibility();
        $pageId = $first?->getPageId() ?? 0;

        // When loading LAST version, use the same effective scope we used above
        $last = null;
        if ($pageId) {
            $qb = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.pageId = :pid')->setParameter('pid', $pageId)
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int) $ctx['groupId'])
                ->orderBy('w.version', 'DESC')
                ->setMaxResults(1);

            if ($effectiveSid > 0) {
                $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $effectiveSid);
            } else {
                $qb->andWhere('COALESCE(w.sessionId,0) = 0');
            }

            $last = $qb->getQuery()->getOneOrNullResult();
        }

        if ($last && $last->getPageId()) {
            Event::addEvent(LOG_WIKI_ACCESS, LOG_WIKI_PAGE_ID, (int)$last->getPageId());
            $last->setHits(((int)$last->getHits()) + 1);
            $em->flush();
        }

        $content = '';
        $title   = '';

        if (!$last || ($last->getContent() === '' && $last->getTitle() === '' && $pageKey === 'index')) {
            $groupInfo = GroupManager::get_group_properties((int)$ctx['groupId']);
            if (api_is_allowed_to_edit(false, true)
                || api_is_platform_admin()
                || GroupManager::is_user_in_group(api_get_user_id(), $groupInfo)
                || api_is_allowed_in_course()
            ) {
                $content = '<div class="text-center">'
                    .sprintf(get_lang('To begin, edit this page and remove this text'), api_get_path(WEB_IMG_PATH))
                    .'</div>';
                $title = get_lang('Home');
            } else {
                Display::addFlash(Display::return_message(get_lang('This Wiki is frozen so far. A trainer must start it.'), 'normal', false));
                return null;
            }
        } else {
            if (true === api_get_configuration_value('wiki_html_strict_filtering')) {
                $content = Security::remove_XSS($last->getContent(), COURSEMANAGERLOWSECURITY);
            } else {
                $content = Security::remove_XSS($last->getContent());
            }
            $title = htmlspecialchars_decode(Security::remove_XSS($last->getTitle()));
        }

        // Badges next to title
        $pageTitleText = self::displayTitleFor($pageKey, $last ? $last->getTitle() : null);
        $pageTitle     = api_htmlentities($pageTitleText);
        if ($last) {
            $badges  = '';
            $assign  = (int) $last->getAssignment();

            if ($assign === 1) {
                $badges .= Display::getMdiIcon(
                    ActionIcon::WIKI_ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('This page is an assignment proposed by a trainer')
                );
            } elseif ($assign === 2) {
                $badges .= Display::getMdiIcon(
                    ActionIcon::WIKI_WORK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Learner paper')
                );
            }

            // Task badge (if any)
            $hasTask = self::confRepo()->findOneBy([
                'cId'    => $ctx['courseId'],
                'pageId' => (int) $last->getPageId(),
            ]);
            if ($hasTask && $hasTask->getTask()) {
                $badges .= Display::getMdiIcon(
                    ActionIcon::WIKI_TASK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Standard Task')
                );
            }

            if ($badges !== '') {
                $pageTitle = $badges.'&nbsp;'.$pageTitle;
            }
        }

        // Visibility gate
        if ($keyVisibility != "1"
            && !api_is_allowed_to_edit(false, true)
            && !api_is_platform_admin()
            && ($last?->getAssignment() != 2 || $keyVisibility != "0" || api_get_user_id() != $last?->getUserId())
            && !api_is_allowed_in_course()
        ) {
            return null;
        }

        // Actions (left/right)
        $actionsLeft  = '';
        $actionsRight = '';

        // Edit
        $editLink = '<a href="'.$ctx['baseUrl'].'&action=edit&title='.api_htmlentities(urlencode($pageKey)).'"'
            .self::is_active_navigation_tab('edit').'>'
            .Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit')).'</a>';

        $groupInfo = GroupManager::get_group_properties((int)$ctx['groupId']);
        if (api_is_allowed_to_edit(false, true)
            || api_is_allowed_in_course()
            || GroupManager::is_user_in_group(api_get_user_id(), $groupInfo)
        ) {
            $actionsLeft .= $editLink;
        }

        $pageProgress = (int)$last?->getProgress() * 10;
        $pageScore    = (int)$last?->getScore();

        if ($last) {
            // Lock / Unlock
            if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                $isLocked   = (self::check_protect_page($pageKey, $ctx['courseId'], $ctx['sessionId'], $ctx['groupId']) == 1);
                $lockAction = $isLocked ? 'unlock' : 'lock';
                $lockIcon   = $isLocked ? ActionIcon::LOCK : ActionIcon::UNLOCK;
                $actionsRight .= '<a href="'.$ctx['baseUrl'].'&action=showpage&actionpage='.$lockAction
                    .'&title='.api_htmlentities(urlencode($pageKey)).'">'
                    .Display::getMdiIcon($lockIcon, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, $isLocked ? get_lang('Locked: students can no longer post new messages in this forum category, forum or thread but they can still read the messages that were already posted') : get_lang('Unlocked: learners can post new messages in this forum category, forum or thread'))
                    .'</a>';
            }

            // Visibility
            if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                $isVisible = (self::check_visibility_page($pageKey, $ctx['courseId'], $ctx['sessionId'], $ctx['groupId']) == 1);
                $visAction = $isVisible ? 'invisible' : 'visible';
                $visIcon   = $isVisible ? ActionIcon::VISIBLE : ActionIcon::INVISIBLE;
                $actionsRight .= '<a href="'.$ctx['baseUrl'].'&action=showpage&actionpage='.$visAction
                    .'&title='.api_htmlentities(urlencode($pageKey)).'">'
                    .Display::getMdiIcon($visIcon, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, $isVisible ? get_lang('Hide') : get_lang('Show'))
                    .'</a>';
            }

            // Notify
            if (api_is_allowed_to_session_edit()) {
                $isWatching   = (self::check_notify_page($pageKey) == 1);
                $notifyAction = $isWatching ? 'unlocknotify' : 'locknotify';
                $notifyIcon   = $isWatching ? ActionIcon::SEND_SINGLE_EMAIL : ActionIcon::NOTIFY_OFF;
                $notifyTitle  = $isWatching ? get_lang('Stop notifying me') : get_lang('Notify me');
                $actionsRight .= '<a href="'.$ctx['baseUrl'].'&action=showpage&actionpage='.$notifyAction
                    .'&title='.api_htmlentities(urlencode($pageKey)).'">'
                    .Display::getMdiIcon($notifyIcon, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, $notifyTitle)
                    .'</a>';
            }

            // Discuss
            if ((api_is_allowed_to_session_edit(false, true) && api_is_allowed_to_edit())
                || GroupManager::is_user_in_group(api_get_user_id(), $groupInfo)
            ) {
                $actionsRight .= '<a href="'.$ctx['baseUrl'].'&action=discuss&title='
                    .api_htmlentities(urlencode($pageKey)).'" '.self::is_active_navigation_tab('discuss').'>'
                    .Display::getMdiIcon(ActionIcon::COMMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Discuss this page'))
                    .'</a>';
            }

            // History
            $actionsRight .= '<a href="'.$ctx['baseUrl'].'&action=history&title='
                .api_htmlentities(urlencode($pageKey)).'" '.self::is_active_navigation_tab('history').'>'
                .Display::getMdiIcon(ActionIcon::HISTORY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('History'))
                .'</a>';

            // Links
            $actionsRight .= '<a href="'.$ctx['baseUrl'].'&action=links&title='
                .api_htmlentities(urlencode($pageKey)).'" '.self::is_active_navigation_tab('links').'>'
                .Display::getMdiIcon(ActionIcon::LINKS, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('What links here'))
                .'</a>';

            // Delete
            if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                $actionsRight .= '<a href="'.$ctx['baseUrl'].'&action=delete&title='
                    .api_htmlentities(urlencode($pageKey)).'"'.self::is_active_navigation_tab('delete').'>'
                    .Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete'))
                    .'</a>';
            }

            // Export
            if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                $actionsRight .= '<a href="'.$ctx['baseUrl'].'&action=export2doc&wiki_id='.$last->getIid().'">'
                    .Display::getMdiIcon(ActionIcon::EXPORT_DOC, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export'))
                    .'</a>';
            }
            $actionsRight .= '<a href="'.$ctx['baseUrl'].'&action=export_to_pdf&wiki_id='.$last->getIid().'">'
                .Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export'))
                .'</a>';
            if (api_get_configuration_value('unoconv.binaries')) {
                $actionsRight .= '<a href="'.$ctx['baseUrl'].'&'.http_build_query(['action' => 'export_to_doc_file', 'id' => $last->getIid()]).'">'
                    .Display::getMdiIcon(ActionIcon::EXPORT_DOC, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export'))
                    .'</a>';
            }

            // Print
            $actionsRight .= '<a href="#" onclick="javascript:(function(){var a=window.open(\'\',\'\',\'width=800,height=600\');a.document.open(\'text/html\');a.document.write($(\'#wikititle\').prop(\'outerHTML\'));a.document.write($(\'#wikicontent\').prop(\'outerHTML\'));a.document.close();a.print();})(); return false;">'
                .Display::getMdiIcon(ActionIcon::PRINT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Print'))
                .'</a>';
        }

        // Classic top bar
        $contentHtml = self::v1ToolbarHtml($actionsLeft, $actionsRight);

        // Link post-processing
        $pageWiki = self::detect_news_link($content);
        $pageWiki = self::detect_irc_link($pageWiki);
        $pageWiki = self::detect_ftp_link($pageWiki);
        $pageWiki = self::detect_mail_link($pageWiki);
        $pageWiki = self::detect_anchor_link($pageWiki);
        $pageWiki = self::detect_external_link($pageWiki);
        $pageWiki = self::make_wiki_link_clickable($pageWiki, $ctx['baseUrl']);

        // Footer meta + categories
        $footerMeta =
            '<span>'.get_lang('Progress').': '.$pageProgress.'%</span> '.
            '<span>'.get_lang('Rating').': '.$pageScore.'</span> '.
            '<span>'.get_lang('Words').': '.self::word_count($content).'</span>';

        $categories = self::returnCategoriesBlock(
            (int)($last?->getIid() ?? 0),
            '<div class="wiki-catwrap">',
            '</div>'
        );

        // Classic shell + new helper classes
        $contentHtml .=
            '<div id="tool-wiki" class="wiki-root">'.
            '<div id="mainwiki" class="wiki-wrap">'.
            '  <div id="wikititle" class="wiki-card wiki-title"><h1>'.$pageTitle.'</h1></div>'.
            '  <div id="wikicontent" class="wiki-card wiki-prose">'.$pageWiki.'</div>'.
            '  <div id="wikifooter" class="wiki-card wiki-footer">'.
            '       <div class="meta">'.$footerMeta.'</div>'.$categories.
            '  </div>'.
            '</div>'.
            '</div>';

        return $contentHtml;
    }

    private static function v1ToolbarHtml(string $left, string $right): string
    {
        if ($left === '' && $right === '') {
            return '';
        }

        return
            '<div class="wiki-actions" style="display:flex;align-items:center;gap:6px;padding:6px 8px;border:1px solid #ddd;border-radius:4px;background:#fff">'.
            '  <div class="wiki-actions-left" style="display:inline-flex;gap:6px">'.$left.'</div>'.
            '  <div class="wiki-actions-right" style="display:inline-flex;gap:6px;margin-left:auto">'.$right.'</div>'.
            '</div>';
    }

    /** Render category links of a page as search filters. */
    private static function returnCategoriesBlock(int $wikiId, string $tagStart = '<div>', string $tagEnd = '</div>'): string
    {
        if (!self::categoriesEnabled() || $wikiId <= 0) {
            return '';
        }

        try {
            $em = Container::getEntityManager();
            /** @var CWiki|null $wiki */
            $wiki = $em->find(CWiki::class, $wikiId);
            if (!$wiki) { return ''; }
        } catch (\Throwable $e) {
            return '';
        }

        $baseUrl = self::ctx()['baseUrl'];

        $links = [];
        foreach ($wiki->getCategories()->getValues() as $category) {
            /** @var CWikiCategory $category */
            $urlParams = [
                'search_term'      => isset($_GET['search_term']) ? Security::remove_XSS($_GET['search_term']) : '',
                'SubmitWikiSearch' => '',
                '_qf__wiki_search' => '',
                'action'           => 'searchpages',
                'categories'       => ['' => $category->getId()],
            ];
            $href  = $baseUrl.'&'.http_build_query($urlParams);
            $label = api_htmlentities($category->getName());
            $links[] = self::twCategoryPill($href, $label);
        }

        if (empty($links)) {
            return '';
        }

        return $tagStart.implode('', $links).$tagEnd;
    }

    /** Active class helper for toolbar tabs. */
    public static function is_active_navigation_tab($paramwk)
    {
        if (isset($_GET['action']) && $_GET['action'] == $paramwk) {
            return ' class="active"';
        }
        return '';
    }

    /** Return 1 if current user is subscribed to page notifications, else 0 (also processes toggles). */
    public static function check_notify_page(string $reflink, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): int
    {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $conn = Container::getEntityManager()->getConnection();

        $cid = (int)$ctx['courseId'];
        $gid = (int)$ctx['groupId'];
        $sid = (int)$ctx['sessionId'];
        $uid = (int)api_get_user_id();

        $watchKey = 'watch:'.self::normalizeReflink($reflink);

        $count = (int)$conn->fetchOne(
            'SELECT COUNT(*)
           FROM c_wiki_mailcue
          WHERE c_id = :cid
            AND COALESCE(group_id,0) = :gid
            AND '.($sid > 0 ? 'COALESCE(session_id,0) = :sid' : 'COALESCE(session_id,0) = 0').'
            AND user_id = :uid
            AND type = :t',
            ['cid'=>$cid,'gid'=>$gid,'sid'=>$sid,'uid'=>$uid,'t'=>$watchKey]
        );

        return $count > 0 ? 1 : 0;
    }

    /** Word count from HTML (UTF-8 safe). */
    private static function word_count(string $html): int
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));
        if ($text === '') {
            return 0;
        }
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($tokens) ? count($tokens) : 0;
    }

    /** True if any row with this title exists in the context. */
    public static function wiki_exist(
        string $reflink,
        ?int $courseId = null,
        ?int $sessionId = null,
        ?int $groupId = null
    ): bool {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $repo = self::repo();

        // Ensure canonicalization (Home/Main_Page → index, lowercase, etc.)
        $reflink = self::normalizeReflink($reflink);

        $qb = $repo->createQueryBuilder('w')
            ->select('w.iid')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->setMaxResults(1);

        if ($ctx['sessionId'] > 0) {
            // In a session: it may exist in 0 (global) or in the current session
            $qb->andWhere('(COALESCE(w.sessionId,0) = 0 OR w.sessionId = :sid)')
                ->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        return !empty($qb->getQuery()->getArrayResult());
    }

    /** Read/toggle global addlock; returns current value or null. */
    public static function check_addnewpagelock(?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): ?int
    {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $em   = Container::getEntityManager();
        $repo = self::repo();

        /** @var CWiki|null $row */
        $row = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $status = $row ? (int)$row->getAddlock() : null;

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            if (isset($_GET['actionpage'])) {
                if ($_GET['actionpage'] === 'lockaddnew' && $status === 1) {
                    $status = 0;
                } elseif ($_GET['actionpage'] === 'unlockaddnew' && $status === 0) {
                    $status = 1;
                }

                $em->createQuery('UPDATE Chamilo\CourseBundle\Entity\CWiki w SET w.addlock = :v WHERE w.cId = :cid AND COALESCE(w.groupId,0) = :gid AND COALESCE(w.sessionId,0) = :sid')
                    ->setParameter('v', $status)
                    ->setParameter('cid', $ctx['courseId'])
                    ->setParameter('gid', (int)$ctx['groupId'])
                    ->setParameter('sid', (int)$ctx['sessionId'])
                    ->execute();

                $row = $repo->createQueryBuilder('w')
                    ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                    ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                    ->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId'])
                    ->orderBy('w.version', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery()->getOneOrNullResult();

                return $row ? (int)$row->getAddlock() : null;
            }
        }

        return $status;
    }

    /** Read/toggle editlock by page (reflink); returns current status (0/1). */
    public static function check_protect_page(string $page, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): int
    {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $em   = Container::getEntityManager();
        $repo = self::repo();

        /** @var CWiki|null $row */
        $row = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$row) {
            return 0;
        }

        $status = (int)$row->getEditlock();
        $pid    = (int)$row->getPageId();

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            if (!empty($_GET['actionpage'])) {
                if ($_GET['actionpage'] === 'lock' && $status === 0) {
                    $status = 1;
                } elseif ($_GET['actionpage'] === 'unlock' && $status === 1) {
                    $status = 0;
                }

                $em->createQuery('UPDATE Chamilo\CourseBundle\Entity\CWiki w SET w.editlock = :v WHERE w.cId = :cid AND w.pageId = :pid')
                    ->setParameter('v', $status)
                    ->setParameter('cid', $ctx['courseId'])
                    ->setParameter('pid', $pid)
                    ->execute();

                $row = $repo->createQueryBuilder('w')
                    ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                    ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
                    ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                    ->orderBy('w.version', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery()->getOneOrNullResult();
            }
        }

        return (int)($row?->getEditlock() ?? 0);
    }

    /** Read/toggle visibility by page (reflink); returns current status (0/1). */
    public static function check_visibility_page(string $page, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): int
    {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $em   = Container::getEntityManager();
        $repo = self::repo();

        /** @var CWiki|null $row */
        $row = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$row) {
            return 0;
        }

        $status = (int)$row->getVisibility();

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            if (!empty($_GET['actionpage'])) {
                if ($_GET['actionpage'] === 'visible' && $status === 0) {
                    $status = 1;
                } elseif ($_GET['actionpage'] === 'invisible' && $status === 1) {
                    $status = 0;
                }

                $em->createQuery('UPDATE Chamilo\CourseBundle\Entity\CWiki w SET w.visibility = :v WHERE w.cId = :cid AND w.reflink = :r AND COALESCE(w.groupId,0) = :gid')
                    ->setParameter('v', $status)
                    ->setParameter('cid', $ctx['courseId'])
                    ->setParameter('r', html_entity_decode($page))
                    ->setParameter('gid', (int)$ctx['groupId'])
                    ->execute();

                $row = $repo->createQueryBuilder('w')
                    ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                    ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
                    ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                    ->orderBy('w.version', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery()->getOneOrNullResult();
            }
        }

        return (int)($row?->getVisibility() ?? 1);
    }

    private static function toDateTime(null|string|\DateTime $v): ?\DateTime
    {
        if ($v instanceof \DateTime) {
            return $v;
        }
        if (is_string($v) && $v !== '') {
            try { return new \DateTime($v); } catch (\Throwable) {}
        }
        return null;
    }

    /** Extract [[wikilinks]] → space-separated normalized reflinks. */
    private static function links_to(string $input): string
    {
        $parts = preg_split("/(\[\[|]])/", $input, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = [];
        foreach ($parts as $k => $v) {
            if (($parts[$k-1] ?? null) === '[[' && ($parts[$k+1] ?? null) === ']]') {
                if (api_strpos($v, '|') !== false) {
                    [$link] = explode('|', $v, 2);
                    $link = trim($link);
                } else {
                    $link = trim($v);
                }
                $out[] = Database::escape_string(str_replace(' ', '_', $link)).' ';
            }
        }
        return implode($out);
    }

    private static function detect_external_link(string $input): string
    {
        return str_replace('href=', 'class="wiki_link_ext" href=', $input);
    }
    private static function detect_anchor_link(string $input): string
    {
        return str_replace('href="#', 'class="wiki_anchor_link" href="#', $input);
    }
    private static function detect_mail_link(string $input): string
    {
        return str_replace('href="mailto', 'class="wiki_mail_link" href="mailto', $input);
    }
    private static function detect_ftp_link(string $input): string
    {
        return str_replace('href="ftp', 'class="wiki_ftp_link" href="ftp', $input);
    }
    private static function detect_news_link(string $input): string
    {
        return str_replace('href="news', 'class="wiki_news_link" href="news', $input);
    }
    private static function detect_irc_link(string $input): string
    {
        return str_replace('href="irc', 'class="wiki_irc_link" href="irc', $input);
    }

    /** Convert [[Page|Title]] to <a> depending on existence. */
    private static function make_wiki_link_clickable(string $input, string $baseUrl): string
    {
        $parts = preg_split("/(\[\[|]])/", $input, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $k => $v) {
            if (($parts[$k-1] ?? null) === '[[' && ($parts[$k+1] ?? null) === ']]') {
                if (api_strpos($v, '|') !== false) {
                    [$rawLink, $title] = explode('|', $v, 2);
                    $rawLink = trim(strip_tags($rawLink));
                    $title   = trim($title);
                } else {
                    $rawLink = trim(strip_tags($v));
                    $title   = trim($v);
                }

                $reflink = self::normalizeReflink($rawLink);
                if (self::isMain($reflink)) {
                    $title = self::displayTitleFor('index');
                }

                if (self::checktitle($reflink)) {
                    $href = $baseUrl.'&action=showpage&title='.urlencode($reflink);
                    $parts[$k] = '<a href="'.$href.'" class="wiki_link">'.$title.'</a>';
                } else {
                    $href = $baseUrl.'&action=addnew&title='.Security::remove_XSS(urlencode($reflink));
                    $parts[$k] = '<a href="'.$href.'" class="new_wiki_link">'.$title.'</a>';
                }

                unset($parts[$k-1], $parts[$k+1]);
            }
        }
        return implode('', $parts);
    }

    private static function assignCategoriesToWiki(CWiki $wiki, array $categoriesIdList): void
    {
        if (!self::categoriesEnabled()) {
            return;
        }

        $em = Container::getEntityManager();

        foreach ($categoriesIdList as $categoryId) {
            if (!$categoryId) {
                continue;
            }
            /** @var CWikiCategory|null $category */
            $category = $em->find(CWikiCategory::class, (int)$categoryId);
            if ($category) {
                if (method_exists($wiki, 'getCategories') && !$wiki->getCategories()->contains($category)) {
                    $wiki->addCategory($category);
                } else {
                    $wiki->addCategory($category);
                }
            }
        }

        $em->flush();
    }

    private static function twToolbarHtml(string $leftHtml, string $rightHtml = ''): string
    {
        $wrap = 'flex items-center gap-2 [&_a]:inline-flex [&_a]:items-center [&_a]:gap-2 [&_a]:rounded-lg [&_a]:border [&_a]:border-slate-200 [&_a]:bg-white [&_a]:px-3 [&_a]:py-1.5 [&_a]:text-sm [&_a]:font-medium [&_a]:text-slate-700 [&_a]:shadow-sm hover:[&_a]:shadow';
        return '<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">'.
            '<div class="'.$wrap.'">'.$leftHtml.'</div>'.
            '<div class="'.$wrap.'">'.$rightHtml.'</div>'.
            '</div>';
    }

    private static function twPanel(string $body, string $title = '', string $footer = ''): string
    {
        $html = '<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">';
        if ($title !== '') {
            $html .= '<div class="border-b border-slate-200 px-5 py-4 text-lg font-semibold text-slate-800">'.$title.'</div>';
        }
        $html .= '<div class="px-5 py-6 leading-relaxed text-slate-700">'.$body.'</div>';
        if ($footer !== '') {
            $html .= '<div class="border-t border-slate-200 px-5 py-3 text-sm text-slate-600">'.$footer.'</div>';
        }
        $html .= '</div>';
        return $html;
    }

    /** Category pill link */
    private static function twCategoryPill(string $href, string $label): string
    {
        return '<a href="'.$href.'" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">'.$label.'</a>';
    }

    /** Convert DateTime|string|int|null to timestamp. */
    private static function toTimestamp($v): int
    {
        if ($v instanceof \DateTimeInterface) {
            return $v->getTimestamp();
        }
        if (is_int($v)) {
            return $v;
        }
        if (is_string($v) && $v !== '') {
            $t = strtotime($v);
            if ($t !== false) {
                return $t;
            }
        }
        return time();
    }

    public function display_new_wiki_form(): void
    {
        $ctx  = self::ctx();
        $url  = $ctx['baseUrl'].'&'.http_build_query(['action' => 'addnew']);
        $form = new FormValidator('wiki_new', 'post', $url);

        // Required title
        $form->addElement('text', 'title', get_lang('Title'));
        $form->addRule('title', get_lang('Required field'), 'required');

        // Editor and advanced fields (adds a hidden wpost_id inside if your setForm doesn’t)
        self::setForm($form);

        // Ensure there is a wpost_id for double_post()
        if (!$form->elementExists('wpost_id')) {
            $form->addElement('hidden', 'wpost_id', api_get_unique_id());
        }

        // Prefill if ?title= is present
        $titleFromGet = isset($_GET['title']) ? htmlspecialchars_decode(Security::remove_XSS((string) $_GET['title'])) : '';
        $form->setDefaults(['title' => $titleFromGet]);

        // --- Process first (don’t output yet) ---
        if ($form->validate()) {
            $values = $form->exportValues();

            // Consistent dates (if provided)
            $toTs = static function ($v): ?int {
                if ($v instanceof \DateTimeInterface) { return $v->getTimestamp(); }
                if (is_string($v) && $v !== '')      { return strtotime($v); }
                return null;
            };
            $startTs = isset($values['startdate_assig']) ? $toTs($values['startdate_assig']) : null;
            $endTs   = isset($values['enddate_assig'])   ? $toTs($values['enddate_assig'])   : null;

            if ($startTs && $endTs && $startTs > $endTs) {
                Display::addFlash(Display::return_message(get_lang('The end date cannot be before the start date'), 'error', false));
                // show the form again
                $form->display();
                return;
            }

            // Anti double-post (if wpost is missing, don’t block)
            if (isset($values['wpost_id']) && !self::double_post($values['wpost_id'])) {
                // Duplicate: go back without saving
                Display::addFlash(Display::return_message(get_lang('Duplicate submission ignored'), 'warning', false));
                $form->display();
                return;
            }

            // If “assignment for all” => generate per user (if needed)
            if (!empty($values['assignment']) && (int)$values['assignment'] === 1) {
                // If your implementation needs it, keep it; otherwise omit for now.
                // self::auto_add_page_users($values);
            }

            // Save: use our robust helper
            $msg = self::save_new_wiki($values);
            if ($msg === false) {
                Display::addFlash(Display::return_message(get_lang('Your changes have been saved. You still have to give a name to the page'), 'error', false));
                $form->display();
                return;
            }

            Display::addFlash(Display::return_message($msg, 'confirmation', false));

            // Redirect to the created page (no output beforehand)
            $wikiData    = self::getWikiData();
            $redirRef    = self::normalizeReflink($wikiData['reflink'] ?? self::normalizeReflink($values['title'] ?? 'index'));
            $redirectUrl = $ctx['baseUrl'].'&'.http_build_query(['action' => 'showpage', 'title' => $redirRef]);
            header('Location: '.$redirectUrl);
            exit;
        }

        // --- Show form (GET or invalid POST) ---
        $form->addButtonSave(get_lang('Save'), 'Save page');
        $form->display();
    }

    public function getHistory(): void
    {
        $page = (string) $this->page;

        if (empty($_GET['title'])) {
            Display::addFlash(Display::return_message(get_lang('You must select a page first'), 'error', false));
            return;
        }

        $ctx  = self::ctx();
        $repo = self::repo();

        // Latest version (for visibility/ownership)
        $qbLast = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'DESC')->setMaxResults(1);

        if ($ctx['sessionId'] > 0) {
            $qbLast->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbLast->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        /** @var CWiki|null $last */
        $last = $qbLast->getQuery()->getOneOrNullResult();

        $keyVisibility = $last?->getVisibility();
        $keyAssignment = $last?->getAssignment();
        $keyTitle      = $last?->getTitle();
        $keyUserId     = $last?->getUserId();

        // Permissions
        $userId = api_get_user_id();
        $canSee =
            $keyVisibility == 1 ||
            api_is_allowed_to_edit(false, true) ||
            api_is_platform_admin() ||
            ($keyAssignment == 2 && $keyVisibility == 0 && $userId == $keyUserId);

        if (!$canSee) {
            Display::addFlash(Display::return_message(get_lang('Not allowed'), 'error', false));
            return;
        }

        // All versions (DESC)
        $qbAll = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'DESC');

        if ($ctx['sessionId'] > 0) {
            $qbAll->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbAll->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        /** @var CWiki[] $versions */
        $versions = $qbAll->getQuery()->getResult();

        // Assignment icon
        $icon = null;
        if ((int)$keyAssignment === 1) {
            $icon = Display::return_icon('wiki_assignment.png', get_lang('This page is an assignment proposed by a trainer'), '', ICON_SIZE_SMALL);
        } elseif ((int)$keyAssignment === 2) {
            $icon = Display::return_icon('wiki_work.png', get_lang('This page is a learner work'), '', ICON_SIZE_SMALL);
        }

        // View 1: pick two versions
        if (!isset($_POST['HistoryDifferences']) && !isset($_POST['HistoryDifferences2'])) {
            $title = (string) $_GET['title'];

            echo '<div id="wikititle">'.($icon ? $icon.'&nbsp;&nbsp;&nbsp;' : '').api_htmlentities($keyTitle ?? '').'</div>';

            $actionUrl = self::ctx()['baseUrl'].'&'.http_build_query(['action' => 'history', 'title' => api_htmlentities($title)]);
            echo '<form id="differences" method="POST" action="'.$actionUrl.'">';
            echo '<ul style="list-style-type:none">';
            echo '<br />';
            echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('Compare selected versions').' '.get_lang('line by line').'</button> ';
            echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('Compare selected versions').' '.get_lang('word by word').'</button>';
            echo '<br /><br />';

            $total = count($versions);
            foreach ($versions as $i => $w) {
                $ui = api_get_user_info((int)$w->getUserId());
                $username = $ui ? api_htmlentities(sprintf(get_lang('Login: %s'), $ui['username']), ENT_QUOTES) : get_lang('Anonymous');

                $oldStyle   = ($i === 0)         ? 'style="visibility:hidden;"' : '';
                $newChecked = ($i === 0)         ? ' checked' : '';
                $newStyle   = ($i === $total -1) ? 'style="visibility:hidden;"' : '';
                $oldChecked = ($i === 1)         ? ' checked' : '';

                $dtime = $w->getDtime() ? $w->getDtime()->format('Y-m-d H:i:s') : '';
                $comment = (string) $w->getComment();
                $commentShort = $comment !== '' ? api_htmlentities(api_substr($comment, 0, 100)) : '---';
                $needsDots    = (api_strlen($comment) > 100) ? '...' : '';

                echo '<li style="margin-bottom:5px">';
                echo '<input name="old" value="'.$w->getIid().'" type="radio" '.$oldStyle.' '.$oldChecked.'/> ';
                echo '<input name="new" value="'.$w->getIid().'" type="radio" '.$newStyle.' '.$newChecked.'/> ';
                echo '<a href="'.self::ctx()['baseUrl'].'&action=showpage&title='.api_htmlentities(urlencode($page)).'&view='.$w->getIid().'">'.$dtime.'</a> ';
                echo '('.get_lang('Version').' '.(int)$w->getVersion().') ';
                echo get_lang('By').' ';
                if ($ui !== false) {
                    echo UserManager::getUserProfileLink($ui);
                } else {
                    echo $username.' ('.api_htmlentities((string)$w->getUserIp()).')';
                }
                echo ' ( '.get_lang('Progress').': '.api_htmlentities((string)$w->getProgress()).'%, ';
                echo get_lang('Comments').': '.$commentShort.$needsDots.' )';
                echo '</li>';
            }

            echo '<br />';
            echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('Compare selected versions').' '.get_lang('line by line').'</button> ';
            echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('Compare selected versions').' '.get_lang('word by word').'</button>';
            echo '</ul></form>';

            return;
        }

        // View 2: differences between two versions
        $versionOld = null;
        if (!empty($_POST['old'])) {
            $versionOld = $repo->find((int) $_POST['old']);
        }
        $versionNew = $repo->find((int) $_POST['new']);

        $oldTime    = $versionOld?->getDtime()?->format('Y-m-d H:i:s');
        $oldContent = $versionOld?->getContent();

        if (isset($_POST['HistoryDifferences'])) {
            include 'diff.inc.php';

            echo '<div id="wikititle">'.api_htmlentities((string)$versionNew->getTitle()).'
        <font size="-2"><i>('.get_lang('Changes in version').'</i>
        <font style="background-color:#aaaaaa">'.$versionNew->getDtime()?->format('Y-m-d H:i:s').'</font>
        <i>'.get_lang('old version of').'</i>
        <font style="background-color:#aaaaaa">'.$oldTime.'</font>)
        '.get_lang('Legend').':
        <span class="diffAdded">'.get_lang('A line has been added').'</span>
        <span class="diffDeleted">'.get_lang('A line has been deleted').'</span>
        <span class="diffMoved">'.get_lang('A line has been moved').'</span></font>
    </div>';

            echo '<table>'.diff((string)$oldContent, (string)$versionNew->getContent(), true, 'format_table_line').'</table>';
            echo '<br /><strong>'.get_lang('Legend').'</strong><div class="diff">';
            echo '<table><tr><td></td><td>';
            echo '<span class="diffEqual">'.get_lang('Line without changes').'</span><br />';
            echo '<span class="diffAdded">'.get_lang('A line has been added').'</span><br />';
            echo '<span class="diffDeleted">'.get_lang('A line has been deleted').'</span><br />';
            echo '<span class="diffMoved">'.get_lang('A line has been moved').'</span><br />';
            echo '</td></tr></table>';
        }

        if (isset($_POST['HistoryDifferences2'])) {
            $lines1   = [strip_tags((string)$oldContent)];
            $lines2   = [strip_tags((string)$versionNew->getContent())];
            $diff     = new Text_Diff($lines1, $lines2);
            $renderer = new Text_Diff_Renderer_inline();

            echo '<style>del{background:#fcc}ins{background:#cfc}</style>'.$renderer->render($diff);
            echo '<br /><strong>'.get_lang('Legend').'</strong><div class="diff">';
            echo '<table><tr><td></td><td>';
            echo '<span class="diffAddedTex">'.get_lang('Text added').'</span><br />';
            echo '<span class="diffDeletedTex">'.get_lang('Text deleted').'</span><br />';
            echo '</td></tr></table>';
        }
    }

    public function getLastWikiData($refLink): array
    {
        $ctx  = self::ctx();
        $em   = Container::getEntityManager();
        $repo = $em->getRepository(CWiki::class);

        $qb = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', (int)$ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode((string)$refLink))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'DESC')
            ->setMaxResults(1);

        if ((int)$ctx['sessionId'] > 0) {
            $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        /** @var CWiki|null $w */
        $w = $qb->getQuery()->getOneOrNullResult();
        if (!$w) {
            return [];
        }

        // Map to legacy-like keys
        return [
            'iid'        => $w->getIid(),
            'page_id'    => $w->getPageId(),
            'reflink'    => $w->getReflink(),
            'title'      => $w->getTitle(),
            'content'    => $w->getContent(),
            'user_id'    => $w->getUserId(),
            'group_id'   => $w->getGroupId(),
            'dtime'      => $w->getDtime(),
            'addlock'    => $w->getAddlock(),
            'editlock'   => $w->getEditlock(),
            'visibility' => $w->getVisibility(),
            'assignment' => $w->getAssignment(),
            'comment'    => $w->getComment(),
            'progress'   => $w->getProgress(),
            'score'      => $w->getScore(),
            'version'    => $w->getVersion(),
            'is_editing' => $w->getIsEditing(),
            'time_edit'  => $w->getTimeEdit(),
            'hits'       => $w->getHits(),
            'linksto'    => $w->getLinksto(),
            'tag'        => $w->getTag(),
            'user_ip'    => $w->getUserIp(),
            'session_id' => $w->getSessionId(),
        ];
    }

    public function auto_add_page_users($values): void
    {
        $ctx            = self::ctx();
        $assignmentType = (int)($values['assignment'] ?? 0);

        $courseInfo = $ctx['courseInfo'] ?: api_get_course_info_by_id((int)$ctx['courseId']);
        $courseCode = $courseInfo['code'] ?? '';

        $groupId   = (int)$ctx['groupId'];
        $groupInfo = $groupId ? GroupManager::get_group_properties($groupId) : null;

        // Target users: course vs group
        if ($groupId === 0) {
            $users = (int)$ctx['sessionId'] > 0
                ? CourseManager::get_user_list_from_course_code($courseCode, (int)$ctx['sessionId'])
                : CourseManager::get_user_list_from_course_code($courseCode);
        } else {
            $subs   = GroupManager::get_subscribed_users($groupInfo) ?: [];
            $tutors = GroupManager::get_subscribed_tutors($groupInfo) ?: [];
            $byId = [];
            foreach (array_merge($subs, $tutors) as $u) {
                if (!isset($u['user_id'])) { continue; }
                $byId[(int)$u['user_id']] = $u;
            }
            $users = array_values($byId);
        }

        // Teacher data
        $teacherId  = api_get_user_id();
        $tInfo      = api_get_user_info($teacherId);
        $tLogin     = api_htmlentities(sprintf(get_lang('Login: %s'), $tInfo['username']), ENT_QUOTES);
        $tName      = $tInfo['complete_name'].' - '.$tLogin;
        $tPhotoUrl  = $tInfo['avatar'] ?? UserManager::getUserPicture($teacherId);
        $tPhoto     = '<img src="'.$tPhotoUrl.'" alt="'.$tName.'" width="40" height="50" align="top" title="'.$tName.'" />';

        $titleOrig    = (string)($values['title'] ?? '');
        $link2teacher = $titleOrig.'_uass'.$teacherId;

        $contentA =
            '<div align="center" style="background-color:#F5F8FB;border:solid;border-color:#E6E6E6">'.
            '<table border="0">'.
            '<tr><td style="font-size:24px">'.get_lang('Assignment proposed by the trainer').'</td></tr>'.
            '<tr><td>'.$tPhoto.'<br />'.Display::tag(
                'span',
                api_get_person_name($tInfo['firstname'], $tInfo['lastname']),
                ['title' => $tLogin]
            ).'</td></tr>'.
            '</table></div>';

        $postedContent = isset($_POST['content']) ? Security::remove_XSS((string)$_POST['content']) : '';
        $contentB = '<br/><div align="center" style="font-size:24px">'.
            get_lang('Assignment description').': '.$titleOrig.'</div><br/>'.
            $postedContent;

        $allStudentsItems = [];
        $postedTitleSafe  = isset($_POST['title']) ? Security::remove_XSS((string)$_POST['title']) : $titleOrig;

        // Create student pages (assignment = 2)
        foreach ($users as $u) {
            $uid = (int)($u['user_id'] ?? 0);
            if ($uid === 0 || $uid === $teacherId) { continue; }

            $uPic   = UserManager::getUserPicture($uid);
            $uLogin = api_htmlentities(sprintf(get_lang('Login: %s'), (string)$u['username']), ENT_QUOTES);
            $uName  = api_get_person_name((string)$u['firstname'], (string)$u['lastname']).' . '.$uLogin;
            $uPhoto = '<img src="'.$uPic.'" alt="'.$uName.'" width="40" height="50" align="bottom" title="'.$uName.'" />';

            $isTutor  = $groupInfo && GroupManager::is_tutor_of_group($uid, $groupInfo);
            $isMember = $groupInfo && GroupManager::is_subscribed($uid, $groupInfo);
            $status   = ($isTutor && $isMember) ? get_lang('Coach and group member')
                : ($isTutor ? get_lang('Group tutor') : ' ');

            if ($assignmentType === 1) {
                $studentValues               = $values;
                $studentValues['title']      = $titleOrig;
                $studentValues['assignment'] = 2;
                $studentValues['content']    =
                    '<div align="center" style="background-color:#F5F8FB;border:solid;border-color:#E6E6E6">'.
                    '<table border="0">'.
                    '<tr><td style="font-size:24px">'.get_lang('Learner paper').'</td></tr>'.
                    '<tr><td>'.$uPhoto.'<br />'.$uName.'</td></tr>'.
                    '</table></div>'.
                    '[[ '.$link2teacher.' | '.get_lang('Access teacher page').' ]] ';

                $allStudentsItems[] =
                    '<li>'.
                    Display::tag('span', strtoupper((string)$u['lastname']).', '.(string)$u['firstname'], ['title' => $uLogin]).
                    ' [[ '.$postedTitleSafe.'_uass'.$uid.' | '.$uPhoto.' ]] '.
                    $status.
                    '</li>';

                // Pass the student uid to save_new_wiki so the author is the student
                $this->save_new_wiki($studentValues, $uid);
            }
        }

        // Teacher page (assignment = 1) listing student works
        foreach ($users as $u) {
            if ((int)($u['user_id'] ?? 0) !== $teacherId) { continue; }

            if ($assignmentType === 1) {
                $teacherValues               = $values;
                $teacherValues['title']      = $titleOrig;
                $teacherValues['comment']    = get_lang('Assignment proposed by the trainer');
                sort($allStudentsItems);

                $teacherValues['content'] =
                    $contentA.$contentB.'<br/>'.
                    '<div align="center" style="font-size:18px;background-color:#F5F8FB;border:solid;border-color:#E6E6E6">'.
                    get_lang('Access to the papers written by learners').'</div><br/>'.
                    '<div style="background-color:#F5F8FB;border:solid;border-color:#E6E6E6">'.
                    '<ol>'.implode('', $allStudentsItems).'</ol>'.
                    '</div><br/>';

                $teacherValues['assignment'] = 1;

                // Pass the teacher id so the reflink ends with _uass<teacherId>
                $this->save_new_wiki($teacherValues, $teacherId);
            }
        }
    }

    public function restore_wikipage(
        $r_page_id,
        $r_reflink,
        $r_title,
        $r_content,
        $r_group_id,
        $r_assignment,
        $r_progress,
        $c_version,
        $r_version,
        $r_linksto
    ) {
        $ctx        = self::ctx();
        $_course    = $ctx['courseInfo'];
        $r_user_id  = api_get_user_id();
        $r_dtime    = api_get_utc_datetime(); // string for mail
        $dTime      = api_get_utc_datetime(null, false, true); // DateTime (entity)

        $r_version = ((int)$r_version) + 1;
        $r_comment = get_lang('Restored from version').': '.$c_version;
        $groupInfo = GroupManager::get_group_properties((int)$r_group_id);

        $em = Container::getEntityManager();

        $newWiki = (new CWiki())
            ->setCId((int)$ctx['courseId'])
            ->setPageId((int)$r_page_id)
            ->setReflink((string)$r_reflink)
            ->setTitle((string)$r_title)
            ->setContent((string)$r_content)
            ->setUserId((int)$r_user_id)
            ->setGroupId((int)$r_group_id)
            ->setDtime($dTime)
            ->setAssignment((int)$r_assignment)
            ->setComment((string)$r_comment)
            ->setProgress((int)$r_progress)
            ->setVersion((int)$r_version)
            ->setLinksto((string)$r_linksto)
            ->setUserIp(api_get_real_ip())
            ->setSessionId((int)$ctx['sessionId'])
            ->setAddlock(0)->setEditlock(0)->setVisibility(0)
            ->setAddlockDisc(0)->setVisibilityDisc(0)->setRatinglockDisc(0)
            ->setIsEditing(0)->setTag('');

        $newWiki->setParent($ctx['course']);
        $newWiki->setCreator(api_get_user_entity());
        $groupEntity = $ctx['groupId'] ? api_get_group_entity($ctx['groupId']) : null;
        $newWiki->addCourseLink($ctx['course'], $ctx['session'], $groupEntity);

        $em->persist($newWiki);
        $em->flush();

        api_item_property_update($_course, 'wiki', $newWiki->getIid(), 'WikiAdded', api_get_user_id(), $groupInfo);
        self::check_emailcue((string)$r_reflink, 'P', $r_dtime, (int)$r_user_id);

        return get_lang('The page has been restored. You can view it by clicking');
    }

    public function restorePage()
    {
        $ctx         = self::ctx();
        $userId      = api_get_user_id();
        $current_row = $this->getWikiData();
        $last_row    = $this->getLastWikiData($this->page);

        if (empty($last_row)) {
            return false;
        }

        $PassEdit = false;

        // Only teacher/admin can edit index or assignment-teacher pages
        if (
            (($current_row['reflink'] ?? '') === 'index' ||
                ($current_row['reflink'] ?? '') === '' ||
                ((int)$current_row['assignment'] === 1)) &&
            (!api_is_allowed_to_edit(false, true) && (int)$ctx['groupId'] === 0)
        ) {
            Display::addFlash(Display::return_message(get_lang('The Main Page can be edited by a teacher only'), 'normal', false));
            return false;
        }

        // Group wiki
        if ((int)($current_row['group_id'] ?? 0) !== 0) {
            $groupInfo = GroupManager::get_group_properties((int)$ctx['groupId']);
            if (api_is_allowed_to_edit(false, true) ||
                api_is_platform_admin() ||
                GroupManager::is_user_in_group($userId, $groupInfo) ||
                api_is_allowed_in_course()
            ) {
                $PassEdit = true;
            } else {
                Display::addFlash(Display::return_message(get_lang('Trainers and group members only can edit pages of the group Wiki'), 'normal', false));
                $PassEdit = false;
            }
        } else {
            $PassEdit = true;
        }

        // Assignment rules
        if ((int)$current_row['assignment'] === 1) {
            Display::addFlash(Display::return_message(get_lang('You can edit this page, but the pages of learners will not be modified'), 'normal', false));
        } elseif ((int)$current_row['assignment'] === 2) {
            if ((int)$userId !== (int)($current_row['user_id'] ?? 0)) {
                if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                    $PassEdit = true;
                } else {
                    Display::addFlash(Display::return_message(get_lang('This page is protected. Trainers only can change it'), 'normal', false));
                    $PassEdit = false;
                }
            }
        }

        if (!$PassEdit) {
            return false;
        }

        // Edit lock
        if ((int)($current_row['editlock'] ?? 0) === 1 &&
            (!api_is_allowed_to_edit(false, true) || !api_is_platform_admin())
        ) {
            Display::addFlash(Display::return_message(get_lang('Page protected'), 'normal', false));
            return false;
        }

        // Concurrency
        $isEditing  = (int)($last_row['is_editing'] ?? 0);
        if ($isEditing !== 0 && $isEditing !== (int)$userId) {
            $timeVal = $last_row['time_edit'] ?? null;
            $ts = $timeVal instanceof \DateTimeInterface ? $timeVal->getTimestamp() : (is_string($timeVal) ? strtotime($timeVal) : time());
            $elapsed = time() - $ts;
            $rest    = max(0, 1200 - $elapsed); // 20 min

            $userinfo = api_get_user_info($isEditing);
            $msg = get_lang('At this time, this page is being edited by').' <a href='.$userinfo['profile_url'].'>'.
                Display::tag('span', $userinfo['complete_name_with_username']).'</a> '.
                get_lang('Please try again later. If the user who is currently editing the page does not save it, this page will be available to you around').' '.date("i", $rest).' '.get_lang('minutes');

            Display::addFlash(Display::return_message($msg, 'normal', false));
            return false;
        }

        // Restore (create new version with previous content)
        Display::addFlash(
            Display::return_message(
                self::restore_wikipage(
                    (int)$current_row['page_id'],
                    (string)$current_row['reflink'],
                    (string)$current_row['title'],
                    (string)$current_row['content'],
                    (int)$current_row['group_id'],
                    (int)$current_row['assignment'],
                    (int)$current_row['progress'],
                    (int)$current_row['version'],
                    (int)$last_row['version'],
                    (string)$current_row['linksto']
                ).': '.Display::url(
                    api_htmlentities((string)$last_row['title']),
                    $ctx['baseUrl'].'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities((string)$last_row['reflink'])])
                ),
                'confirmation',
                false
            )
        );

        return true;
    }

    public function handleAction(string $action): void
    {
        $page = $this->page;
        $ctx  = self::ctx();
        $url  = $ctx['baseUrl'];

        // Local renderer for breadcrumb + stylish pills (uniform look)
        $renderStatsHeader = function (string $activeKey) use ($url) {
            static $wikiHdrCssInjected = false;

            // Labels (use existing lang keys)
            $items = [
                'mactiveusers' => get_lang('Most active users'),
                'mvisited'     => get_lang('Most visited pages'),
                'mostchanged'  => get_lang('Most changed pages'),
                'orphaned'     => get_lang('Orphaned pages'),
                'wanted'       => get_lang('Wanted pages'),
                'mostlinked'   => get_lang('Pages most linked'),
                'statistics'   => get_lang('Statistics'),
            ];

            // Simple icon map
            $icons = [
                'mactiveusers' => ActionIcon::STAR,
                'mvisited'     => ActionIcon::HISTORY,
                'mostchanged'  => ActionIcon::REFRESH,
                'orphaned'     => ActionIcon::LINKS,
                'wanted'       => ActionIcon::SEARCH,
                'mostlinked'   => ActionIcon::LINKS,
                'statistics'   => ActionIcon::INFORMATION,
            ];

            if (!$wikiHdrCssInjected) {
                $wikiHdrCssInjected = true;
            }

            $activeLabel = api_htmlentities($items[$activeKey] ?? '');

            echo '<div class="wiki-pills">';
            foreach ($items as $key => $label) {
                $isActive = ($key === $activeKey);
                $href     = $url.'&action='.$key;
                $icon     = Display::getMdiIcon($icons[$key] ?? ActionIcon::VIEW_DETAILS,
                    'mdi-inline', null, ICON_SIZE_SMALL, $label);
                echo '<a class="pill'.($isActive ? ' active' : '').'" href="'.$href.'"'.
                    ($isActive ? ' aria-current="page"' : '').'>'.$icon.'<span>'.api_htmlentities($label).'</span></a>';
            }
            echo '</div>';
        };

        switch ($action) {
            case 'export_to_pdf':
                if (isset($_GET['wiki_id'])) {
                    self::export_to_pdf($_GET['wiki_id'], api_get_course_id());
                    break;
                }
                break;

            case 'export2doc':
                if (isset($_GET['wiki_id'])) {
                    $export2doc = self::export2doc($_GET['wiki_id']);
                    if ($export2doc) {
                        Display::addFlash(
                            Display::return_message(
                                get_lang('The page has been exported to the document tool'),
                                'confirmation',
                                false
                            )
                        );
                    }
                }
                break;

            case 'restorepage':
                self::restorePage();
                break;

            case 'more':
                self::getStatsTable();
                break;

            case 'statistics':
                $renderStatsHeader('statistics');
                self::getStats();
                break;

            case 'mactiveusers':
                $renderStatsHeader('mactiveusers');
                self::getActiveUsers($action);
                break;

            case 'usercontrib':
                self::getUserContributions((int)($_GET['user_id'] ?? 0), $action);
                break;

            case 'mostchanged':
                $renderStatsHeader('mostchanged');
                $this->getMostChangedPages($action);
                break;

            case 'mvisited':
                $renderStatsHeader('mvisited');
                self::getMostVisited();
                break;

            case 'wanted':
                $renderStatsHeader('wanted');
                $this->getWantedPages();
                break;

            case 'orphaned':
                $renderStatsHeader('orphaned');
                self::getOrphaned();
                break;

            case 'mostlinked':
                $renderStatsHeader('mostlinked');
                self::getMostLinked();
                break;

            case 'delete':
                $this->deletePageWarning();
                break;

            case 'deletewiki':
                echo '<nav aria-label="breadcrumb" class="wiki-breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="'.
                        $this->url(['action' => 'showpage', 'title' => 'index']).'">'.get_lang('Wiki').'</a></li>
                  <li class="breadcrumb-item active" aria-current="page">'.get_lang('Delete').'</li>
                </ol>
              </nav>';

                echo '<div class="actions">'.get_lang('Delete all').'</div>';

                $canDelete     = api_is_allowed_to_edit(false, true) || api_is_platform_admin();
                $confirmedPost = isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === '1';

                if (!$canDelete) {
                    echo Display::return_message(get_lang('Trainers only can delete the Wiki'), 'error', false);
                    break;
                }

                if (!$confirmedPost) {
                    $actionUrl = $this->url(['action' => 'deletewiki']);
                    $msg  = '<p>'.get_lang('Are you sure you want to delete this Wiki?').'</p>';
                    $msg .= '<form method="post" action="'.Security::remove_XSS($actionUrl).'" style="display:inline-block;margin-right:1rem;">';
                    $msg .= '<input type="hidden" name="confirm_delete" value="1">';
                    $msg .= '<button type="submit" class="btn btn-danger">'.get_lang('Yes').'</button>';
                    $msg .= '&nbsp;&nbsp;<a class="btn btn-default" href="'.$this->url().'">'.get_lang('No').'</a>';
                    $msg .= '</form>';

                    echo Display::return_message($msg, 'warning', false);
                    break;
                }

                $summary = self::delete_wiki();

                Display::addFlash(Display::return_message($summary, 'confirmation', false));
                header('Location: '.$this->url());
                exit;

            case 'searchpages':
                self::getSearchPages($action);
                break;

            case 'links':
                self::getLinks($page);
                break;

            case 'addnew':
                if (0 != api_get_session_id() && api_is_allowed_to_session_edit(false, true) == false) {
                    api_not_allowed();
                }

                echo '<div class="actions">'.get_lang('Add new page').'</div>';
                echo '<br/>';

                // Show the tip ONLY if "index" is missing or has no real content
                try {
                    $ctx  = self::ctx();
                    $repo = self::repo();

                    $qb = $repo->createQueryBuilder('w')
                        ->andWhere('w.cId = :cid')->setParameter('cid', (int)$ctx['courseId'])
                        ->andWhere('w.reflink = :r')->setParameter('r', 'index')
                        ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                        ->orderBy('w.version', 'DESC')
                        ->setMaxResults(1);

                    if ((int)$ctx['sessionId'] > 0) {
                        $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
                    } else {
                        $qb->andWhere('COALESCE(w.sessionId,0) = 0');
                    }

                    /** @var CWiki|null $indexRow */
                    $indexRow = $qb->getQuery()->getOneOrNullResult();

                    $indexIsEmpty = true;
                    if ($indexRow) {
                        // Decode entities, strip HTML, normalize NBSP and whitespace
                        $raw   = (string)$indexRow->getContent();
                        $text  = api_html_entity_decode($raw, ENT_QUOTES, api_get_system_encoding());
                        $text  = strip_tags($text);
                        $text  = preg_replace('/\xC2\xA0/u', ' ', $text); // NBSP
                        $text  = trim(preg_replace('/\s+/u', ' ', $text));

                        // Consider empty if no letters/digits (handles <p>&nbsp;</p>, placeholders, etc.)
                        $indexIsEmpty = ($text === '' || !preg_match('/[\p{L}\p{N}]/u', $text));
                    }

                    if ($indexIsEmpty && (api_is_allowed_to_edit(false, true) || api_is_platform_admin() || api_is_allowed_in_course())) {
                        Display::addFlash(
                            Display::return_message(get_lang('To start Wiki go and edit Main page'), 'normal', false)
                        );
                    }
                } catch (\Throwable $e) {
                    // If something goes wrong checking content, fail-safe to *not* nag the user.
                }

                // Lock for creating new pages (only affects NON-editors)
                if (self::check_addnewpagelock() == 0
                    && (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin())
                ) {
                    Display::addFlash(
                        Display::return_message(get_lang('The add option has been temporarily disabled by the trainer'), 'error', false)
                    );
                    break;
                }

                self::display_new_wiki_form();
                break;

            case 'show':
            case 'showpage':
                $requested = self::normalizeReflink($_GET['title'] ?? null);
                echo self::display_wiki_entry($requested, $requested);
                break;

            case 'edit':
                self::editPage();
                break;

            case 'history':
                self::getHistory();
                break;

            case 'recentchanges':
                self::recentChanges($page, $action);
                break;

            case 'allpages':
                self::allPages($action);
                break;

            case 'discuss':
                self::getDiscuss($page);
                break;

            case 'export_to_doc_file':
                self::exportTo($_GET['id'], 'odt');
                exit;
                break;

            case 'category':
                $this->addCategory();
                break;

            case 'delete_category':
                $this->deleteCategory();
                break;
        }
    }

    public function showLinks(string $page): void
    {
        $ctx  = self::ctx();
        $repo = self::repo();

        // Basic guard: this action expects a title in the request (legacy behavior)
        if (empty($_GET['title'])) {
            Display::addFlash(Display::return_message(get_lang('You must select a page first'), 'error', false));
            return;
        }

        // Canonical reflink for the requested page (logic uses 'index' as main page)
        $reflink = self::normalizeReflink($page);

        // Token used inside "linksto" (UI-friendly, localized with underscores when index)
        $needleToken = self::displayTokenFor($reflink);

        // --- Header block: title + assignment icon (use first version as anchor) ---
        /** @var CWiki|null $first */
        $first = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$first) {
            Display::addFlash(Display::return_message(get_lang('You must select a page first'), 'error', false));
            return;
        }

        $assignIcon = '';
        if ((int)$first->getAssignment() === 1) {
            $assignIcon = Display::getMdiIcon(ActionIcon::WIKI_ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Assignment proposed by the trainer'));
        } elseif ((int)$first->getAssignment() === 2) {
            $assignIcon = Display::getMdiIcon(ActionIcon::WIKI_WORK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Learner paper'));
        }

        echo '<div id="wikititle">'.get_lang('Pages that link to this page').": $assignIcon ".
            Display::url(
                api_htmlentities($first->getTitle()),
                $ctx['baseUrl'].'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($reflink)])
            ).'</div>';

        // --- Query: latest version per page that *may* link to $needleToken ---
        $qb = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->andWhere($ctx['sessionId'] > 0 ? '(COALESCE(w.sessionId,0) IN (0, :sid))' : 'COALESCE(w.sessionId,0) = 0')
            ->setParameter('sid', (int)$ctx['sessionId'])
            ->andWhere('w.linksto LIKE :needle')->setParameter('needle', '%'.$needleToken.'%')
            ->andWhere('w.version = (
        SELECT MAX(w2.version) FROM '.CWiki::class.' w2
        WHERE w2.cId = w.cId
          AND w2.pageId = w.pageId
          AND COALESCE(w2.groupId,0) = :gid2
          AND '.($ctx['sessionId'] > 0
                    ? '(COALESCE(w2.sessionId,0) IN (0, :sid2))'
                    : 'COALESCE(w2.sessionId,0) = 0').'
    )')
            ->setParameter('gid2', (int)$ctx['groupId'])
            ->setParameter('sid2', (int)$ctx['sessionId']);

        // Visibility gate for students
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            $qb->andWhere('w.visibility = 1');
        }

        /** @var CWiki[] $candidates */
        $candidates = $qb->getQuery()->getResult();

        // --- Precise token filter: ensure space-delimited match in "linksto" ---
        $items = [];
        foreach ($candidates as $obj) {
            $tokens = preg_split('/\s+/', trim((string)$obj->getLinksto())) ?: [];
            if (in_array($needleToken, $tokens, true)) {
                $items[] = $obj;
            }
        }

        if (!$items) {
            echo self::twPanel('<em>'.get_lang('No results found').'</em>', get_lang('What links here'));
            return;
        }

        // --- Render simple table ---
        $rowsHtml = '';
        foreach ($items as $obj) {
            $ui = api_get_user_info((int)$obj->getUserId());
            $authorCell = $ui
                ? UserManager::getUserProfileLink($ui)
                : get_lang('Anonymous').' ('.$obj->getUserIp().')';

            $icon = '';
            if ((int)$obj->getAssignment() === 1) {
                $icon = Display::getMdiIcon(ActionIcon::WIKI_ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Assignment proposed by the trainer'));
            } elseif ((int)$obj->getAssignment() === 2) {
                $icon = Display::getMdiIcon(ActionIcon::WIKI_WORK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Learner paper'));
            }

            $when = $obj->getDtime() ? api_get_local_time($obj->getDtime()) : '';
            $rowsHtml .= '<tr>'.
                '<td style="width:30px">'.$icon.'</td>'.
                '<td>'.Display::url(
                    api_htmlentities($obj->getTitle()),
                    $ctx['baseUrl'].'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($obj->getReflink())])
                ).'</td>'.
                '<td>'.$authorCell.'</td>'.
                '<td>'.$when.'</td>'.
                '</tr>';
        }

        $table =
            '<table class="table table-striped">'.
            '<thead><tr>'.
            '<th>'.get_lang('Type').'</th>'.
            '<th>'.get_lang('Title').'</th>'.
            '<th>'.get_lang('Author').'</th>'.
            '<th>'.get_lang('Date').'</th>'.
            '</tr></thead>'.
            '<tbody>'.$rowsHtml.'</tbody>'.
            '</table>';

        echo self::twPanel($table, get_lang('What links here'));
    }

    public function showDiscuss(string $page): void
    {
        $ctx  = self::ctx();
        $em   = Container::getEntityManager();
        $repo = self::repo();

        if ($ctx['sessionId'] !== 0 && api_is_allowed_to_session_edit(false, true) === false) {
            api_not_allowed();
        }

        if (empty($_GET['title'])) {
            Display::addFlash(Display::return_message(get_lang('You must select a page first'), 'error', false));
            return;
        }

        // FIRST and LAST version (to get properties and page_id)
        /** @var CWiki|null $first */
        $first = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$first) {
            Display::addFlash(Display::return_message(get_lang('Discuss not available'), 'normal', false));
            return;
        }

        $qbLast = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.pageId = :pid')->setParameter('pid', (int)$first->getPageId())
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'DESC')
            ->setMaxResults(1);

        if ($ctx['sessionId'] > 0) {
            $qbLast->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbLast->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        /** @var CWiki|null $last */
        $last = $qbLast->getQuery()->getOneOrNullResult();
        if (!$last) {
            Display::addFlash(Display::return_message(get_lang('Discuss not available'), 'normal', false));
            return;
        }

        // Visibility gate for discussions (like legacy)
        $canSeeDiscuss =
            ((int)$last->getVisibilityDisc() === 1) ||
            api_is_allowed_to_edit(false, true) ||
            api_is_platform_admin() ||
            ((int)$last->getAssignment() === 2 && (int)$last->getVisibilityDisc() === 0 && api_get_user_id() === (int)$last->getUserId());

        if (!$canSeeDiscuss) {
            Display::addFlash(Display::return_message(get_lang('Disabled by trainer'), 'warning', false));
            return;
        }

        // Process toggles (lock/unlock/visibility/rating/notify)
        $lockLabel = '';
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            $discLocked = (self::check_addlock_discuss($page) === 1);
            $lockLabel  = $discLocked
                ? Display::getMdiIcon(ActionIcon::UNLOCK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Unlock'))
                : Display::getMdiIcon(ActionIcon::LOCK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Lock'));

            $visIcon = (self::check_visibility_discuss($page) === 1)
                ? Display::getMdiIcon(ActionIcon::VISIBLE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Hide'))
                : Display::getMdiIcon(ActionIcon::INVISIBLE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Show'));

            $rateIcon = (self::check_ratinglock_discuss($page) === 1)
                ? Display::getMdiIcon(ActionIcon::STAR, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Unlock'))
                : Display::getMdiIcon(ActionIcon::STAR_OUTLINE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Lock'));

            echo '<div class="flex gap-2 justify-end">'.
                '<a href="'.$ctx['baseUrl'].'&action=discuss&actionpage='.( $discLocked ? 'unlockdisc' : 'lockdisc' ).'&title='.api_htmlentities(urlencode($page)).'">'.$lockLabel.'</a>'.
                '<a href="'.$ctx['baseUrl'].'&action=discuss&actionpage='.( self::check_visibility_discuss($page) ? 'hidedisc' : 'showdisc' ).'&title='.api_htmlentities(urlencode($page)).'">'.$visIcon.'</a>'.
                '<a href="'.$ctx['baseUrl'].'&action=discuss&actionpage='.( self::check_ratinglock_discuss($page) ? 'unlockrating' : 'lockrating' ).'&title='.api_htmlentities(urlencode($page)).'">'.$rateIcon.'</a>'.
                '</div>';
        }

        // Notify toggle (course-scope watchers; reuses page-level method)
        $isWatching = (self::check_notify_page($page) === 1);
        $notifyIcon = $isWatching
            ? Display::getMdiIcon(ActionIcon::SEND_SINGLE_EMAIL, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Cancel'))
            : Display::getMdiIcon(ActionIcon::NOTIFY_OFF, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Notify me'));
        echo '<div class="flex gap-2 justify-end">'.
            '<a href="'.$ctx['baseUrl'].'&action=discuss&actionpage='.( $isWatching ? 'unlocknotify' : 'locknotify' ).'&title='.api_htmlentities(urlencode($page)).'">'.$notifyIcon.'</a>'.
            '</div>';

        // Header (title + last editor/time)
        $lastInfo  = $last->getUserId() ? api_get_user_info((int)$last->getUserId()) : false;
        $metaRight = '';
        if ($lastInfo !== false) {
            $metaRight = ' ('.get_lang('The latest version was edited by').' '.UserManager::getUserProfileLink($lastInfo).' '.api_get_local_time($last->getDtime()?->format('Y-m-d H:i:s')).')';
        }

        $assignIcon = '';
        if ((int)$last->getAssignment() === 1) {
            $assignIcon = Display::getMdiIcon(ActionIcon::WIKI_ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Assignment proposed by the trainer'));
        } elseif ((int)$last->getAssignment() === 2) {
            $assignIcon = Display::getMdiIcon(ActionIcon::WIKI_WORK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Learner paper'));
        }

        echo '<div id="wikititle">'.$assignIcon.'&nbsp;&nbsp;&nbsp;'.api_htmlentities($last->getTitle()).$metaRight.'</div>';

        // Comment form (only if not locked or user is teacher/admin)
        $discLocked = ((int)$last->getAddlockDisc() === 1);
        $canPost = !$discLocked || api_is_allowed_to_edit(false, true) || api_is_platform_admin();

        if ($canPost) {
            $ratingAllowed = ((int)$last->getRatinglockDisc() === 1) || api_is_allowed_to_edit(false, true) || api_is_platform_admin();
            $ratingSelect = $ratingAllowed
                ? '<select name="rating" id="rating" class="form-control">'.
                '<option value="-" selected>-</option>'.
                implode('', array_map(static fn($n) => '<option value="'.$n.'">'.$n.'</option>', range(0,10))).
                '</select>'
                : '<input type="hidden" name="rating" value="-">';

            $actionUrl = $ctx['baseUrl'].'&action=discuss&title='.api_htmlentities(urlencode($page));
            echo '<div class="panel panel-default"><div class="panel-body">'.
                '<form method="post" action="'.$actionUrl.'" class="form-horizontal">'.
                '<input type="hidden" name="wpost_id" value="'.api_get_unique_id().'">'.
                '<div class="form-group">'.
                '<label class="col-sm-2 control-label">'.get_lang('Comments').':</label>'.
                '<div class="col-sm-10"><textarea class="form-control" name="comment" cols="80" rows="5" id="comment"></textarea></div>'.
                '</div>'.
                '<div class="form-group">'.
                '<label class="col-sm-2 control-label">'.get_lang('Rating').':</label>'.
                '<div class="col-sm-10">'.$ratingSelect.'</div>'.
                '</div>'.
                '<div class="form-group">'.
                '<div class="col-sm-offset-2 col-sm-10">'.
                '<button class="btn btn--primary" type="submit" name="Submit">'.get_lang('Send').'</button>'.
                '</div>'.
                '</div>'.
                '</form>'.
                '</div></div>';
        }

        // Handle POST (add comment)
        if (isset($_POST['Submit']) && self::double_post($_POST['wpost_id'] ?? '')) {
            $comment = (string)($_POST['comment'] ?? '');
            $scoreIn = (string)($_POST['rating'] ?? '-');
            $score   = $scoreIn !== '-' ? max(0, min(10, (int)$scoreIn)) : null;

            $disc = new CWikiDiscuss();
            $disc
                ->setCId($ctx['courseId'])
                ->setPublicationId((int)$last->getPageId())
                ->setUsercId(api_get_user_id())
                ->setComment($comment)
                ->setPScore($scoreIn !== '-' ? $score : null)
                ->setDtime(api_get_utc_datetime(null, false, true));

            $em->persist($disc);
            $em->flush();

            self::check_emailcue((int)$last->getIid(), 'D', api_get_utc_datetime(), api_get_user_id());

            header('Location: '.$ctx['baseUrl'].'&action=discuss&title='.api_htmlentities(urlencode($page)));
            exit;
        }

        echo '<hr noshade size="1">';

        // Load comments
        $discRepo = $em->getRepository(CWikiDiscuss::class);
        $reviews  = $discRepo->createQueryBuilder('d')
            ->andWhere('d.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('d.publicationId = :pid')->setParameter('pid', (int)$last->getPageId())
            ->orderBy('d.iid', 'DESC')
            ->getQuery()->getResult();

        $countAll   = count($reviews);
        $scored     = array_values(array_filter($reviews, static fn($r) => $r->getPScore() !== null));
        $countScore = count($scored);
        $avg        = $countScore > 0 ? round(array_sum(array_map(static fn($r) => (int)$r->getPScore(), $scored)) / $countScore, 2) : 0.0;

        echo get_lang('Comments on this page').': '.$countAll.' - '.get_lang('Number of comments scored:').': '.$countScore.' - '.get_lang('The average rating for the page is').': '.$avg;

        // Persist average into wiki.score (fits integer nullable; we save rounded int)
        $last->setScore((int)round($avg));
        $em->flush();

        echo '<hr noshade size="1">';

        foreach ($reviews as $r) {
            $ui = api_get_user_info((int)$r->getUsercId());
            $role = ($ui && (string)$ui['status'] === '5') ? get_lang('Learner') : get_lang('Teacher');
            $name = $ui ? $ui['complete_name'] : get_lang('Anonymous');
            $avatar = $ui && !empty($ui['avatar']) ? $ui['avatar'] : UserManager::getUserPicture((int)$r->getUsercId());
            $profile = $ui ? UserManager::getUserProfileLink($ui) : api_htmlentities($name);

            $score = $r->getPScore();
            $scoreText = ($score === null) ? '-' : (string)$score;

            echo '<p><table>'.
                '<tr>'.
                '<td rowspan="2"><img src="'.api_htmlentities($avatar).'" alt="'.api_htmlentities($name).'" width="40" height="50" /></td>'.
                '<td style="color:#999">'.$profile.' ('.$role.') '.api_get_local_time($r->getDtime()?->format('Y-m-d H:i:s')).' - '.get_lang('Rating').': '.$scoreText.'</td>'.
                '</tr>'.
                '<tr>'.
                '<td>'.api_htmlentities((string)$r->getComment()).'</td>'.
                '</tr>'.
                '</table></p>';
        }
    }

    public static function check_addlock_discuss(string $page, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): int
    {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $em   = Container::getEntityManager();
        $repo = self::repo();

        /** @var CWiki|null $row */
        $row = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$row) { return 0; }

        $status = (int)$row->getAddlockDisc();
        $pid    = (int)$row->getPageId();

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            $act = (string)($_GET['actionpage'] ?? '');
            if ($act === 'lockdisc' && $status === 0) { $status = 1; }
            if ($act === 'unlockdisc' && $status === 1) { $status = 0; }

            $em->createQuery('UPDATE '.CWiki::class.' w SET w.addlockDisc = :v WHERE w.cId = :cid AND w.pageId = :pid')
                ->setParameter('v', $status)
                ->setParameter('cid', $ctx['courseId'])
                ->setParameter('pid', $pid)
                ->execute();

            $row = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->orderBy('w.version', 'ASC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
        }

        return (int)($row?->getAddlockDisc() ?? 0);
    }

    public static function check_visibility_discuss(string $page, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): int
    {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $em   = Container::getEntityManager();
        $repo = self::repo();

        /** @var CWiki|null $row */
        $row = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$row) { return 0; }

        $status = (int)$row->getVisibilityDisc();
        $pid    = (int)$row->getPageId();

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            $act = (string)($_GET['actionpage'] ?? '');
            if ($act === 'showdisc' && $status === 0) { $status = 1; }
            if ($act === 'hidedisc' && $status === 1) { $status = 0; }

            $em->createQuery('UPDATE '.CWiki::class.' w SET w.visibilityDisc = :v WHERE w.cId = :cid AND w.pageId = :pid')
                ->setParameter('v', $status)
                ->setParameter('cid', $ctx['courseId'])
                ->setParameter('pid', $pid)
                ->execute();

            $row = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->orderBy('w.version', 'ASC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
        }

        return (int)($row?->getVisibilityDisc() ?? 1);
    }

    public static function check_ratinglock_discuss(string $page, ?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): int
    {
        $ctx  = self::ctx($courseId, $sessionId, $groupId);
        $em   = Container::getEntityManager();
        $repo = self::repo();

        /** @var CWiki|null $row */
        $row = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$row) { return 0; }

        $status = (int)$row->getRatinglockDisc();
        $pid    = (int)$row->getPageId();

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            $act = (string)($_GET['actionpage'] ?? '');
            if ($act === 'lockrating' && $status === 1) { $status = 0; }
            if ($act === 'unlockrating' && $status === 0) { $status = 1; }

            $em->createQuery('UPDATE '.CWiki::class.' w SET w.ratinglockDisc = :v WHERE w.cId = :cid AND w.pageId = :pid')
                ->setParameter('v', $status)
                ->setParameter('cid', $ctx['courseId'])
                ->setParameter('pid', $pid)
                ->execute();

            $row = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
                ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($page))
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
                ->orderBy('w.version', 'ASC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
        }

        return (int)($row?->getRatinglockDisc() ?? 1);
    }

    public function deletePageWarning(): void
    {
        $ctx  = self::ctx();
        $repo = self::repo();
        $em   = Container::getEntityManager();

        // Permissions
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            Display::addFlash(Display::return_message(get_lang('Trainers only can delete a page'), 'normal', false));
            return;
        }

        // Page to delete
        $pageRaw = $_GET['title'] ?? '';
        $page    = self::normalizeReflink($pageRaw);
        if ($page === '') {
            Display::addFlash(Display::return_message(get_lang('You must select a page first'), 'error', false));
            header('Location: '.$ctx['baseUrl'].'&action=allpages');
            exit;
        }

        // Resolve first version (to get page_id) within this context
        $qbFirst = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', (int)$ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', $page)
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1);

        if ((int)$ctx['sessionId'] > 0) {
            $qbFirst->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbFirst->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        /** @var CWiki|null $first */
        $first = $qbFirst->getQuery()->getOneOrNullResult();
        if (!$first || !(int)$first->getPageId()) {
            Display::addFlash(Display::return_message(get_lang('Not found'), 'error', false));
            header('Location: '.$ctx['baseUrl'].'&action=allpages');
            exit;
        }

        $niceName = self::displayTitleFor($page, $first->getTitle());

        // Warn if deleting the main (index) page
        if ($page === 'index') {
            Display::addFlash(Display::return_message(get_lang('Deleting the homepage of the Wiki is not recommended because it is the main access to the wiki.<br />If, however, you need to do so, do not forget to re-create this Homepage. Until then, other users will not be able to add new pages.'), 'warning', false));
        }

        // Confirmation?
        $confirmed =
            (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === '1') ||
            (isset($_GET['delete']) && ($_GET['delete'] === 'yes' || $_GET['delete'] === '1'));

        if ($confirmed) {
            // Delete by reflink inside current context
            $ok = $this->deletePageByReflink($page, (int)$ctx['courseId'], (int)$ctx['sessionId'], (int)$ctx['groupId']);

            if ($ok) {
                Display::addFlash(
                    Display::return_message(get_lang('The page and its history have been deleted.'), 'confirmation', false)
                );
            } else {
                Display::addFlash(
                    Display::return_message(get_lang('Deletion failed'), 'error', false)
                );
            }

            header('Location: '.$ctx['baseUrl'].'&action=allpages');
            exit;
        }

        $postUrl = $this->url(['action' => 'delete', 'title' => $page]);

        $msg  = '<p>'.sprintf(get_lang('Are you sure you want to delete this page and its history?'), '<b>'.api_htmlentities($niceName).'</b>').'</p>';
        $msg .= '<form method="post" action="'.Security::remove_XSS($postUrl).'" style="display:inline-block;margin-right:1rem;">';
        $msg .= '<input type="hidden" name="confirm_delete" value="1">';
        $msg .= '<button type="submit" class="btn btn-danger">'.get_lang('Yes').'</button>';
        $msg .= '&nbsp;&nbsp;<a class="btn btn-default" href="'.$ctx['baseUrl'].'&action=allpages">'.get_lang('No').'</a>';
        $msg .= '</form>';

        echo Display::return_message($msg, 'warning', false);
    }

    private function deletePageByReflink(
        string $reflink,
        ?int $courseId = null,
        ?int $sessionId = null,
        ?int $groupId = null
    ): bool {
        $ctx = self::ctx($courseId, $sessionId, $groupId);
        $em  = Container::getEntityManager();
        $repo = self::repo();

        /** @var CWiki|null $first */
        $first = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :r')->setParameter('r', html_entity_decode($reflink))
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->orderBy('w.version', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$first) {
            return false;
        }

        $pageId = (int)$first->getPageId();

        // Delete Conf for this pageId
        $em->createQuery('DELETE FROM '.CWikiConf::class.' c WHERE c.cId = :cid AND c.pageId = :pid')
            ->setParameter('cid', $ctx['courseId'])
            ->setParameter('pid', $pageId)
            ->execute();

        // Delete Discuss for this pageId
        $em->createQuery('DELETE FROM '.CWikiDiscuss::class.' d WHERE d.cId = :cid AND d.publicationId = :pid')
            ->setParameter('cid', $ctx['courseId'])
            ->setParameter('pid', $pageId)
            ->execute();

        // Delete all versions (respect group/session)
        $qb = $em->createQuery('DELETE FROM '.CWiki::class.' w WHERE w.cId = :cid AND w.pageId = :pid AND COALESCE(w.groupId,0) = :gid AND '.(
            $ctx['sessionId'] > 0 ? '(COALESCE(w.sessionId,0) = 0 OR w.sessionId = :sid)' : 'COALESCE(w.sessionId,0) = 0'
            ));
        $qb->setParameter('cid', $ctx['courseId'])
            ->setParameter('pid', $pageId)
            ->setParameter('gid', (int)$ctx['groupId']);
        if ($ctx['sessionId'] > 0) { $qb->setParameter('sid', (int)$ctx['sessionId']); }
        $qb->execute();

        self::check_emailcue(0, 'E');

        return true;
    }

    public function allPages(string $action): void
    {
        $ctx = self::ctx(); // ['courseId','groupId','sessionId','baseUrl','courseCode']
        $em  = Container::getEntityManager();

        // Header + "Delete whole wiki" (only teachers/admin)
        echo '<div class="actions">'.get_lang('All pages');
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            echo ' <a href="'.$ctx['baseUrl'].'&action=deletewiki">'.
                Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete all')).
                '</a>';
        }
        echo '</div>';

        // Latest version per page (by reflink) in current context
        $qb = $em->createQueryBuilder()
            ->select('w')
            ->from(CWiki::class, 'w')
            ->andWhere('w.cId = :cid')->setParameter('cid', (int)$ctx['courseId'])
            ->andWhere('COALESCE(w.groupId, 0) = :gid')->setParameter('gid', (int)$ctx['groupId']);

        if ((int)$ctx['sessionId'] > 0) {
            $qb->andWhere('COALESCE(w.sessionId, 0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId, 0) = 0');
        }

        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            $qb->andWhere('w.visibility = 1');
        }

        // Subquery: max version for each reflink
        $sub = $em->createQueryBuilder()
            ->select('MAX(w2.version)')
            ->from(CWiki::class, 'w2')
            ->andWhere('w2.cId = :cid')
            ->andWhere('w2.reflink = w.reflink')
            ->andWhere('COALESCE(w2.groupId, 0) = :gid');

        if ((int)$ctx['sessionId'] > 0) {
            $sub->andWhere('COALESCE(w2.sessionId, 0) = :sid');
        } else {
            $sub->andWhere('COALESCE(w2.sessionId, 0) = 0');
        }

        $qb->andWhere('w.version = ('.$sub->getDQL().')')
            ->orderBy('w.title', 'ASC');

        /** @var CWiki[] $pages */
        $pages = $qb->getQuery()->getResult();

        // Prefetch Conf->task (avoid N+1)
        $pageIds = array_values(array_unique(array_filter(
            array_map(static fn(CWiki $w) => $w->getPageId(), $pages),
            static fn($v) => $v !== null
        )));
        $taskByPageId = [];
        if ($pageIds) {
            $confQb = self::confRepo()->createQueryBuilder('c');
            $confs = $confQb
                ->select('c.pageId, c.task')
                ->andWhere('c.cId = :cid')->setParameter('cid', (int)$ctx['courseId'])
                ->andWhere($confQb->expr()->in('c.pageId', ':pids'))->setParameter('pids', $pageIds)
                ->getQuery()->getArrayResult();

            foreach ($confs as $c) {
                if (!empty($c['task'])) {
                    $taskByPageId[(int)$c['pageId']] = true;
                }
            }
        }

        // Build rows: ALWAYS strings so TableSort can safely run strip_tags()
        $rows = [];
        foreach ($pages as $w) {
            $hasTask = !empty($taskByPageId[(int)$w->getPageId()]);
            $titlePack = json_encode([
                'title'   => (string) $w->getTitle(),
                'reflink' => (string) $w->getReflink(),
                'iid'     => (int) $w->getIid(),
                'hasTask' => (bool) $hasTask,
            ], JSON_UNESCAPED_UNICODE);

            $authorPack = json_encode([
                'userId' => (int) $w->getUserId(),
                'ip'     => (string) $w->getUserIp(),
            ], JSON_UNESCAPED_UNICODE);

            $rows[] = [
                (string) $w->getAssignment(),                                       // 0: type (iconified)
                $titlePack,                                                         // 1: title data (JSON string)
                $authorPack,                                                        // 2: author data (JSON string)
                $w->getDtime() ? $w->getDtime()->format('Y-m-d H:i:s') : '',        // 3: date string
                (string) $w->getReflink(),                                          // 4: actions (needs reflink)
            ];
        }

        $table = new SortableTableFromArrayConfig(
            $rows,
            1,
            25,
            'AllPages_table',
            '',
            '',
            'ASC'
        );

        $table->set_additional_parameters([
            'cid'     => $ctx['courseId'],
            'gid'     => $ctx['groupId'],
            'sid' => $ctx['sessionId'],
            'action'     => Security::remove_XSS($action),
        ]);

        $table->set_header(0, get_lang('Type'), true, ['style' => 'width:48px;']);
        $table->set_header(1, get_lang('Title'), true);
        $table->set_header(2, get_lang('Author').' <small>'.get_lang('Latest version').'</small>');
        $table->set_header(3, get_lang('Date').' <small>'.get_lang('Latest version').'</small>');

        if (api_is_allowed_to_session_edit(false, true)) {
            $table->set_header(4, get_lang('Actions'), false, ['style' => 'width: 280px;']);
        }

        // Column 0: icons (type + task badge)
        $table->set_column_filter(0, function ($value, string $urlParams, array $row) {
            $icons = self::assignmentIcon((int)$value);
            $packed = json_decode((string)$row[1], true) ?: [];
            if (!empty($packed['hasTask'])) {
                $icons .= Display::getMdiIcon(
                    ActionIcon::WIKI_TASK,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('Standard Task')
                );
            }
            return $icons;
        });

        // Column 1: title link + categories
        $table->set_column_filter(1, function ($value) use ($ctx) {
            $data = json_decode((string)$value, true) ?: [];
            $ref  = (string)($data['reflink'] ?? '');
            $rawTitle = (string)($data['title'] ?? '');
            $iid  = (int)($data['iid'] ?? 0);

            // Show "Home" for index if DB title is empty
            $display = self::displayTitleFor($ref, $rawTitle);

            $href = $ctx['baseUrl'].'&'.http_build_query([
                    'action' => 'showpage',
                    'title'  => api_htmlentities($ref),
                ]);

            return Display::url(api_htmlentities($display), $href)
                . self::returnCategoriesBlock($iid, '<div><small>', '</small></div>');
        });

        // Column 2: author
        $table->set_column_filter(2, function ($value) {
            $data = json_decode((string)$value, true) ?: [];
            $uid  = (int)($data['userId'] ?? 0);
            $ip   = (string)($data['ip'] ?? '');
            return self::authorLink($uid, $ip);
        });

        // Column 3: local time
        $table->set_column_filter(3, function ($value) {
            return !empty($value) ? api_get_local_time($value) : '';
        });

        // Column 4: actions
        $table->set_column_filter(4, function ($value) use ($ctx) {
            if (!api_is_allowed_to_session_edit(false, true)) {
                return '';
            }
            $ref = (string)$value;

            $actions  = Display::url(
                Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit')),
                $ctx['baseUrl'].'&'.http_build_query(['action' => 'edit', 'title' => api_htmlentities($ref)])
            );
            $actions .= Display::url(
                Display::getMdiIcon(ActionIcon::COMMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Discuss')),
                $ctx['baseUrl'].'&'.http_build_query(['action' => 'discuss', 'title' => api_htmlentities($ref)])
            );
            $actions .= Display::url(
                Display::getMdiIcon(ActionIcon::HISTORY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('History')),
                $ctx['baseUrl'].'&'.http_build_query(['action' => 'history', 'title' => api_htmlentities($ref)])
            );
            $actions .= Display::url(
                Display::getMdiIcon(ActionIcon::LINKS, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('What links here')),
                $ctx['baseUrl'].'&'.http_build_query(['action' => 'links', 'title' => api_htmlentities($ref)])
            );

            if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                $actions .= Display::url(
                    Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete')),
                    $ctx['baseUrl'].'&'.http_build_query(['action' => 'delete', 'title' => api_htmlentities($ref)])
                );
            }
            return $actions;
        });

        $table->display();
    }

    public function getSearchPages(string $action): void
    {
        $ctx = self::ctx();
        $url = $ctx['baseUrl'].'&'.http_build_query(['action' => api_htmlentities($action), 'mode_table' => 'yes1']);

        echo '<div class="actions">'.get_lang('Search').'</div>';

        if (isset($_GET['mode_table'])) {
            if (!isset($_GET['SearchPages_table_page_nr'])) {
                $_GET['search_term']         = $_POST['search_term'] ?? '';
                $_GET['search_content']      = $_POST['search_content'] ?? '';
                $_GET['all_vers']            = $_POST['all_vers'] ?? '';
                $_GET['categories']          = $_POST['categories'] ?? [];
                $_GET['match_all_categories']= !empty($_POST['match_all_categories']);
            }
            $this->display_wiki_search_results(
                (string) $_GET['search_term'],
                (int)    $_GET['search_content'],
                (int)    $_GET['all_vers'],
                (array)  $_GET['categories'],
                (bool)   $_GET['match_all_categories']
            );
            return;
        }

        // Build form
        $form = new FormValidator('wiki_search', 'get', $url);
        $form->addHidden('cid',      $ctx['courseId']);
        $form->addHidden('sid',  $ctx['sessionId']);
        $form->addHidden('gid',      $ctx['groupId']);
        $form->addHidden('gradebook',   '0');
        $form->addHidden('origin',      '');
        $form->addHidden('action',      'searchpages');

        $form->addText('search_term', get_lang('Search term'), false, ['autofocus' => 'autofocus']);
        $form->addCheckBox('search_content', '', get_lang('Search also in content'));
        $form->addCheckbox('all_vers', '', get_lang('Also search in older versions of each page'));

        if (self::categoriesEnabled()) {
            $categories = Container::getEntityManager()
                ->getRepository(CWikiCategory::class)
                ->findByCourse(api_get_course_entity());
            $form->addSelectFromCollection(
                'categories',
                get_lang('Categories'),
                $categories,
                ['multiple' => 'multiple'],
                false,
                'getNodeName'
            );
            $form->addCheckBox('match_all_categories', '', get_lang('Must be in ALL the selected categories'));
        }

        $form->addButtonSearch(get_lang('Search'), 'SubmitWikiSearch');
        $form->addRule('search_term', get_lang('Too short'), 'minlength', 3);

        if ($form->validate()) {
            $form->display();
            $values = $form->exportValues();
            $this->display_wiki_search_results(
                (string)$values['search_term'],
                (int)($values['search_content'] ?? 0),
                (int)($values['all_vers'] ?? 0),
                (array)($values['categories'] ?? []),
                !empty($values['match_all_categories'])
            );
        } else {
            $form->display();
        }
    }

    public function display_wiki_search_results(
        string $searchTerm,
        int $searchContent = 0,
        int $allVersions = 0,
        array $categoryIdList = [],
        bool $matchAllCategories = false
    ): void {
        $ctx  = self::ctx();
        $em   = Container::getEntityManager();
        $repo = self::repo();
        $url  = $ctx['baseUrl'];

        $categoryIdList = array_map('intval', $categoryIdList);

        echo '<legend>'.get_lang('Wiki Search Results').': '.Security::remove_XSS($searchTerm).'</legend>';

        $qb = $repo->createQueryBuilder('wp');
        $qb->andWhere('wp.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(wp.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);

        if ($ctx['sessionId'] > 0) {
            $qb->andWhere('COALESCE(wp.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(wp.sessionId,0) = 0');
        }

        // Visibility for students
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            $qb->andWhere('wp.visibility = 1');
        }

        // Search by title (+content if requested)
        $likeTerm = '%'.$searchTerm.'%';
        $or = $qb->expr()->orX(
            $qb->expr()->like('wp.title', ':term')
        );
        if ($searchContent === 1) {
            $or->add($qb->expr()->like('wp.content', ':term'));
        }
        $qb->andWhere($or)->setParameter('term', $likeTerm);

        // Categories filter
        if (!empty($categoryIdList)) {
            if ($matchAllCategories) {
                $i = 0;
                foreach ($categoryIdList as $catId) {
                    ++$i;
                    $aliasC = 'wc'.$i;
                    $qb->innerJoin('wp.categories', $aliasC, 'WITH', $qb->expr()->eq($aliasC.'.id', ':cid'.$i))
                        ->setParameter('cid'.$i, $catId);
                }
            } else {
                $qb->innerJoin('wp.categories', 'wc')
                    ->andWhere('wc.id IN (:cids)')
                    ->setParameter('cids', $categoryIdList);
            }
        }

        // Only latest per page unless allVersions=1
        if ($allVersions !== 1) {
            $sub = $em->createQueryBuilder()
                ->select('MAX(s2.version)')
                ->from(\Chamilo\CourseBundle\Entity\CWiki::class, 's2')
                ->andWhere('s2.cId = :cid')
                ->andWhere('s2.reflink = wp.reflink')
                ->andWhere('COALESCE(s2.groupId,0) = :gid');

            if ($ctx['sessionId'] > 0) {
                $sub->andWhere('COALESCE(s2.sessionId,0) = :sid');
            } else {
                $sub->andWhere('COALESCE(s2.sessionId,0) = 0');
            }
            $qb->andWhere($qb->expr()->eq('wp.version', '(' . $sub->getDQL() . ')'));
        }

        $qb->orderBy('wp.dtime', 'DESC');

        /** @var \Chamilo\CourseBundle\Entity\CWiki[] $rows */
        $rows = $qb->getQuery()->getResult();

        if (!$rows) {
            echo get_lang('No search results');
            return;
        }

        // Icons
        $iconEdit    = Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit'));
        $iconDiscuss = Display::getMdiIcon(ActionIcon::COMMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Discuss'));
        $iconHistory = Display::getMdiIcon(ActionIcon::HISTORY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('History'));
        $iconLinks   = Display::getMdiIcon(ActionIcon::LINKS, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('What links here'));
        $iconDelete  = Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete'));

        $data = [];
        foreach ($rows as $w) {
            $assignIcon = self::assignmentIcon((int)$w->getAssignment());

            $wikiLinkParams = ['action' => 'showpage', 'title' => $w->getReflink()];
            if ($allVersions === 1) {
                $wikiLinkParams['view'] = $w->getIid();
            }

            $titleLink = Display::url(
                    api_htmlentities($w->getTitle()),
                    $url.'&'.http_build_query($wikiLinkParams)
                ).self::returnCategoriesBlock((int)$w->getIid(), '<div><small>', '</small></div>');

            $author = self::authorLink((int)$w->getUserId(), (string)$w->getUserIp());
            $date   = api_convert_and_format_date($w->getDtime());

            if ($allVersions === 1) {
                $data[] = [$assignIcon, $titleLink, $author, $date, (int)$w->getVersion()];
            } else {
                $actions  = '';
                $actions .= Display::url($iconEdit,    $url.'&'.http_build_query(['action' => 'edit',    'title' => $w->getReflink()]));
                $actions .= Display::url($iconDiscuss, $url.'&'.http_build_query(['action' => 'discuss', 'title' => $w->getReflink()]));
                $actions .= Display::url($iconHistory, $url.'&'.http_build_query(['action' => 'history', 'title' => $w->getReflink()]));
                $actions .= Display::url($iconLinks,   $url.'&'.http_build_query(['action' => 'links',   'title' => $w->getReflink()]));
                if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                    $actions .= Display::url($iconDelete, $url.'&'.http_build_query(['action' => 'delete', 'title' => $w->getReflink()]));
                }

                $data[] = [$assignIcon, $titleLink, $author, $date, $actions];
            }
        }

        $table = new SortableTableFromArrayConfig(
            $data,
            1,   // default sort by title
            10,
            'SearchPages_table',
            '',
            '',
            'ASC'
        );

        $extra = [
            'cid'                  => (int)$ctx['courseId'],
            'gid'                  => (int)$ctx['groupId'],
            'sid'                  => (int)$ctx['sessionId'],
            'action'               => $_GET['action'] ?? 'searchpages',
            'mode_table'           => 'yes2',
            'search_term'          => (string)$searchTerm,
            'search_content'       => (int)$searchContent,
            'all_vers'             => (int)$allVersions,
            'match_all_categories' => $matchAllCategories ? 1 : 0,
        ];

        foreach ($categoryIdList as $i => $cidVal) {
            $extra['categories['.$i.']'] = (int)$cidVal;
        }
        $table->set_additional_parameters($extra);

        $table->set_header(0, get_lang('Type'), true, ['style' => 'width:48px;']);
        $table->set_header(1, get_lang('Title'));
        if ($allVersions === 1) {
            $table->set_header(2, get_lang('Author'));
            $table->set_header(3, get_lang('Date'));
            $table->set_header(4, get_lang('Version'));
        } else {
            $table->set_header(2, get_lang('Author').' <small>'.get_lang('Latest version').'</small>');
            $table->set_header(3, get_lang('Date').' <small>'.get_lang('Latest version').'</small>');
            $table->set_header(4, get_lang('Actions'), false, ['style' => 'width:280px;']);
        }
        $table->display();
    }

    public function recentChanges(string $page, string $action): void
    {
        $ctx = self::ctx();
        $url = $ctx['baseUrl'];

        // Top bar: notify-all toggle (only if user can session-edit)
        $notifyBlock = '';
        if (api_is_allowed_to_session_edit(false, true)) {
            if (self::check_notify_all() === 1) {
                $notifyBlock = Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Do not notify me by e-mail when this page is edited'))
                    .' '.get_lang('Do not notify me of changes');
                $act = 'unlocknotifyall';
            } else {
                $notifyBlock = Display::getMdiIcon(ActionIcon::SEND_SINGLE_EMAIL, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Notify me by e-mail when somebody replies'))
                    .' '.get_lang('Notify me of changes');
                $act = 'locknotifyall';
            }

            echo '<div class="actions"><span style="float:right;">'.
                '<a href="'.$url.'&action=recentchanges&actionpage='.$act.'&title='.api_htmlentities(urlencode($page)).'">'.$notifyBlock.'</a>'.
                '</span>'.get_lang('Latest changes').'</div>';
        } else {
            echo '<div class="actions">'.get_lang('Latest changes').'</div>';
        }

        $repo = self::repo();
        $em   = Container::getEntityManager();

        $qb = $repo->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);

        if ($ctx['sessionId'] > 0) {
            $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        // Students only see visible pages
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            $qb->andWhere('w.visibility = 1');
        }

        $qb->orderBy('w.dtime', 'DESC');

        /** @var CWiki[] $list */
        $list = $qb->getQuery()->getResult();

        if (empty($list)) {
            return;
        }

        $rows = [];
        foreach ($list as $w) {
            $assignIcon = self::assignmentIcon((int)$w->getAssignment());

            // Task icon?
            $iconTask = '';
            $conf = self::confRepo()->findOneBy(['cId' => $ctx['courseId'], 'pageId' => (int)$w->getPageId()]);
            if ($conf && $conf->getTask()) {
                $iconTask = Display::getMdiIcon(ActionIcon::WIKI_TASK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Standard Task'));
            }

            $titleLink = Display::url(
                api_htmlentities($w->getTitle()),
                $url.'&'.http_build_query([
                    'action' => 'showpage',
                    'title'  => api_htmlentities($w->getReflink()),
                    'view'   => (int)$w->getIid(), // jump to that version
                ])
            );

            $actionText = ((int)$w->getVersion() > 1) ? get_lang('edited by') : get_lang('added by');
            $authorLink = self::authorLink((int)$w->getUserId(), (string)$w->getUserIp());

            $rows[] = [
                api_get_local_time($w->getDtime()),
                $assignIcon.$iconTask,
                $titleLink,
                $actionText,
                $authorLink,
            ];
        }

        $table = new SortableTableFromArrayConfig(
            $rows,
            0,
            10,
            'RecentPages_table',
            '',
            '',
            'DESC'
        );
        $table->set_additional_parameters([
            'cid'     => $ctx['courseId'],
            'gid'     => $ctx['groupId'],
            'sid' => $ctx['sessionId'],
            'action'     => Security::remove_XSS($action),
        ]);

        $table->set_header(0, get_lang('Date'), true, ['style' => 'width:200px;']);
        $table->set_header(1, get_lang('Type'), true, ['style' => 'width:48px;']);
        $table->set_header(2, get_lang('Title'), true);
        $table->set_header(3, get_lang('Actions'), true, ['style' => 'width:120px;']);
        $table->set_header(4, get_lang('Author'), true);
        $table->display();
    }

    private static function assignmentIcon(int $assignment): string
    {
        return match ($assignment) {
            1       => Display::getMdiIcon(ActionIcon::WIKI_ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Assignment proposed by the trainer')),
            2       => Display::getMdiIcon(ActionIcon::WIKI_WORK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Learner paper')),
            default => '',
        };
    }

    private static function authorLink(int $userId, string $userIp): string
    {
        $ui = $userId ? api_get_user_info($userId) : false;
        if ($ui !== false) {
            return UserManager::getUserProfileLink($ui);
        }
        return get_lang('Anonymous').' ('.api_htmlentities($userIp).')';
    }

    /** Course-wide watchers toggle for "Recent Changes". Returns 1 if subscribed, else 0 (and processes GET toggles). */
    public static function check_notify_all(?int $courseId = null, ?int $sessionId = null, ?int $groupId = null): int
    {
        $ctx = self::ctx($courseId, $sessionId, $groupId);
        $em  = Container::getEntityManager();

        $userId   = api_get_user_id();
        $repoMail = $em->getRepository(CWikiMailcue::class);

        /** @var CWikiMailcue|null $existing */
        $existing = $repoMail->findOneBy([
            'cId'       => $ctx['courseId'],
            'groupId'   => (int)$ctx['groupId'],
            'sessionId' => (int)$ctx['sessionId'],
            'userId'    => $userId,
        ]);

        if (api_is_allowed_to_session_edit() && !empty($_GET['actionpage'])) {
            $act = (string) $_GET['actionpage'];

            if ('locknotifyall' === $act && !$existing) {
                $cue = new CWikiMailcue();
                $cue->setCId($ctx['courseId'])
                    ->setUserId($userId)
                    ->setGroupId((int)$ctx['groupId'])
                    ->setSessionId((int)$ctx['sessionId'])
                    ->setType('wiki');
                $em->persist($cue);
                $em->flush();
                $existing = $cue;
            }

            if ('unlocknotifyall' === $act && $existing) {
                $em->remove($existing);
                $em->flush();
                $existing = null;
            }
        }

        return $existing ? 1 : 0;
    }

    public function getUserContributions(int $userId, string $action): void
    {
        $ctx = self::ctx();
        $url = $ctx['baseUrl'];
        $userId = (int) $userId;

        $userinfo = api_get_user_info($userId);
        if ($userinfo !== false) {
            echo '<div class="actions">'.
                Display::url(
                    get_lang('User contributions').': '.$userinfo['complete_name_with_username'],
                    $url.'&'.http_build_query(['action' => 'usercontrib', 'user_id' => $userId])
                ).
                '</div>';
        }

        $qb = self::repo()->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.userId = :uid')->setParameter('uid', $userId)
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);

        if ($ctx['sessionId'] > 0) {
            $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId,0) = 0');
        }

        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            $qb->andWhere('w.visibility = 1');
        }

        $qb->orderBy('w.dtime', 'DESC');

        /** @var CWiki[] $list */
        $list = $qb->getQuery()->getResult();

        if (empty($list)) {
            return;
        }

        $rows = [];
        foreach ($list as $w) {
            $rows[] = [
                api_get_local_time($w->getDtime()),
                self::assignmentIcon((int)$w->getAssignment()),
                Display::url(
                    api_htmlentities($w->getTitle()),
                    $url.'&'.http_build_query([
                        'action' => 'showpage',
                        'title'  => api_htmlentities($w->getReflink()),
                        'view'   => (int)$w->getIid(),
                    ])
                ),
                Security::remove_XSS((string)$w->getVersion()),
                Security::remove_XSS((string)$w->getComment()),
                Security::remove_XSS((string)$w->getProgress()).' %',
                Security::remove_XSS((string)$w->getScore()),
            ];
        }

        $table = new SortableTableFromArrayConfig($rows, 2, 10, 'UsersContributions_table', '', '', 'ASC');
        $table->set_additional_parameters([
            'cid'     => $ctx['courseId'],
            'gid'     => $ctx['groupId'],
            'sid' => $ctx['sessionId'],
            'action'     => Security::remove_XSS($action),
            'user_id'    => (int)$userId,
        ]);
        $table->set_header(0, get_lang('Date'),    true, ['style' => 'width:200px;']);
        $table->set_header(1, get_lang('Type'),    true, ['style' => 'width:48px;']);
        $table->set_header(2, get_lang('Title'),   true, ['style' => 'width:200px;']);
        $table->set_header(3, get_lang('Version'), true, ['style' => 'width:60px;']);
        $table->set_header(4, get_lang('Comment'), true, ['style' => 'width:200px;']);
        $table->set_header(5, get_lang('Progress'),true, ['style' => 'width:80px;']);
        $table->set_header(6, get_lang('Rating'),  true, ['style' => 'width:80px;']);
        $table->display();
    }

    public function getMostChangedPages(string $action): void
    {
        $ctx = self::ctx();
        $url = $ctx['baseUrl'];

        echo '<div class="actions">'.get_lang('Most changed pages').'</div>';

        // Aggregate: max(version) per reflink with context gates
        $qb = self::repo()->createQueryBuilder('w')
            ->select('w.reflink AS reflink, MAX(w.version) AS changes')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);

        if ($ctx['sessionId'] > 0) {
            $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId,0) = 0');
        }
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            $qb->andWhere('w.visibility = 1');
        }
        $qb->groupBy('w.reflink');

        $raw = $qb->getQuery()->getArrayResult();
        if (empty($raw)) {
            return;
        }

        $rows = [];
        foreach ($raw as $r) {
            $reflink = (string)$r['reflink'];
            // Fetch latest page for title + assignment
            $latest = self::repo()->findOneBy(
                ['cId' => $ctx['courseId'], 'reflink' => $reflink, 'groupId' => (int)$ctx['groupId'], 'sessionId' => (int)$ctx['sessionId']],
                ['version' => 'DESC', 'dtime' => 'DESC']
            ) ?? self::repo()->findOneBy(['cId' => $ctx['courseId'], 'reflink' => $reflink], ['version' => 'DESC']);

            if (!$latest) {
                continue;
            }

            $rows[] = [
                self::assignmentIcon((int)$latest->getAssignment()),
                Display::url(
                    api_htmlentities($latest->getTitle()),
                    $url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($reflink)])
                ),
                (int)$r['changes'],
            ];
        }

        $table = new SortableTableFromArrayConfig($rows, 2, 10, 'MostChangedPages_table', '', '', 'DESC');
        $table->set_additional_parameters([
            'cid'     => $ctx['courseId'],
            'gid'     => $ctx['groupId'],
            'sid' => $ctx['sessionId'],
            'action'     => Security::remove_XSS($action),
        ]);
        $table->set_header(0, get_lang('Type'),   true, ['style' => 'width:48px;']);
        $table->set_header(1, get_lang('Title'),  true);
        $table->set_header(2, get_lang('Changes'),true, ['style' => 'width:100px;']);
        $table->display();
    }

    public function getMostVisited(): void
    {
        $ctx = self::ctx();
        $url = $ctx['baseUrl'];

        echo '<div class="actions">'.get_lang('Most visited pages').'</div>';

        // Aggregate: sum(hits) per reflink
        $qb = self::repo()->createQueryBuilder('w')
            ->select('w.reflink AS reflink, SUM(COALESCE(w.hits,0)) AS totalHits')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);

        if ($ctx['sessionId'] > 0) {
            $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qb->andWhere('COALESCE(w.sessionId,0) = 0');
        }
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            $qb->andWhere('w.visibility = 1');
        }
        $qb->groupBy('w.reflink');

        $raw = $qb->getQuery()->getArrayResult();
        if (empty($raw)) {
            return;
        }

        $rows = [];
        foreach ($raw as $r) {
            $reflink = (string)$r['reflink'];
            $latest = self::repo()->findOneBy(
                ['cId' => $ctx['courseId'], 'reflink' => $reflink, 'groupId' => (int)$ctx['groupId'], 'sessionId' => (int)$ctx['sessionId']],
                ['version' => 'DESC', 'dtime' => 'DESC']
            ) ?? self::repo()->findOneBy(['cId' => $ctx['courseId'], 'reflink' => $reflink], ['version' => 'DESC']);

            if (!$latest) {
                continue;
            }

            $rows[] = [
                self::assignmentIcon((int)$latest->getAssignment()),
                Display::url(
                    api_htmlentities($latest->getTitle()),
                    $url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($reflink)])
                ),
                (int)$r['totalHits'],
            ];
        }

        $table = new SortableTableFromArrayConfig($rows, 2, 10, 'MostVisitedPages_table', '', '', 'DESC');
        $table->set_additional_parameters([
            'cid'     => $ctx['courseId'],
            'gid'     => $ctx['groupId'],
            'sid' => $ctx['sessionId'],
            'action'     => Security::remove_XSS($this->action ?? 'mvisited'),
        ]);
        $table->set_header(0, get_lang('Type'),   true, ['style' => 'width:48px;']);
        $table->set_header(1, get_lang('Title'),  true);
        $table->set_header(2, get_lang('Visits'), true, ['style' => 'width:100px;']);
        $table->display();
    }

    public function getMostLinked(): void
    {
        $ctx = self::ctx();
        $url = $ctx['baseUrl'];

        echo '<div class="actions">'.get_lang('Pages most linked').'</div>';

        // All existing page reflinks in context
        $qbPages = self::repo()->createQueryBuilder('w')
            ->select('DISTINCT w.reflink AS reflink')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);
        if ($ctx['sessionId'] > 0) {
            $qbPages->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbPages->andWhere('COALESCE(w.sessionId,0) = 0');
        }
        $pages = array_map(fn($r) => (string)$r['reflink'], $qbPages->getQuery()->getArrayResult());

        // Latest version of every page in context
        $latestList = $this->getLatestPagesForContext();

        // Collect "linksto" tokens pointing to existing pages (excluding self)
        $linked = [];
        foreach ($latestList as $w) {
            $selfRef = $w->getReflink();
            $tokens  = preg_split('/\s+/', trim((string)$w->getLinksto())) ?: [];
            foreach ($tokens as $t) {
                $t = trim($t);
                if ($t === '' || $t === $selfRef) {
                    continue;
                }
                if (in_array($t, $pages, true)) {
                    $linked[] = $t;
                }
            }
        }

        $linked = array_values(array_unique($linked));
        $rows = [];
        foreach ($linked as $ref) {
            $rows[] = [
                Display::url(
                    str_replace('_', ' ', $ref),
                    $url.'&'.http_build_query(['action' => 'showpage', 'title' => str_replace('_', ' ', $ref)])
                ),
            ];
        }

        $table = new SortableTableFromArrayConfig($rows, 0, 10, 'LinkedPages_table', '', '', 'ASC');
        $table->set_additional_parameters([
            'cid'     => $ctx['courseId'],
            'gid'     => $ctx['groupId'],
            'sid' => $ctx['sessionId'],
            'action'     => Security::remove_XSS($this->action ?? 'mostlinked'),
        ]);
        $table->set_header(0, get_lang('Title'), true);
        $table->display();
    }

    public function getOrphaned(): void
    {
        $ctx = self::ctx();
        $url = $ctx['baseUrl'];

        echo '<div class="actions">'.get_lang('Orphaned pages').'</div>';

        // All page reflinks in context
        $qbPages = self::repo()->createQueryBuilder('w')
            ->select('DISTINCT w.reflink AS reflink')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);
        if ($ctx['sessionId'] > 0) {
            $qbPages->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbPages->andWhere('COALESCE(w.sessionId,0) = 0');
        }
        $pages = array_map(fn($r) => (string)$r['reflink'], $qbPages->getQuery()->getArrayResult());

        // Latest version per reflink
        $latestList = $this->getLatestPagesForContext();

        // Gather all linksto tokens across latest versions
        $linkedTokens = [];
        foreach ($latestList as $w) {
            $self = $w->getReflink();
            $tokens = preg_split('/\s+/', trim((string)$w->getLinksto())) ?: [];
            foreach ($tokens as $t) {
                $t = trim($t);
                if ($t === '' || $t === $self) {
                    continue;
                }
                $linkedTokens[] = $t;
            }
        }
        $linkedTokens = array_values(array_unique($linkedTokens));

        // Orphaned = pages not referenced by any token
        $orphaned = array_values(array_diff($pages, $linkedTokens));

        $rows = [];
        foreach ($orphaned as $ref) {
            // Fetch one latest entity to check visibility/assignment/title
            $latest = self::repo()->findOneBy(
                ['cId' => $ctx['courseId'], 'reflink' => $ref, 'groupId' => (int)$ctx['groupId'], 'sessionId' => (int)$ctx['sessionId']],
                ['version' => 'DESC', 'dtime' => 'DESC']
            ) ?? self::repo()->findOneBy(['cId' => $ctx['courseId'], 'reflink' => $ref], ['version' => 'DESC']);

            if (!$latest) {
                continue;
            }
            if ((!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) && (int)$latest->getVisibility() === 0) {
                continue;
            }

            $rows[] = [
                self::assignmentIcon((int)$latest->getAssignment()),
                Display::url(
                    api_htmlentities($latest->getTitle()),
                    $url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($ref)])
                ),
            ];
        }

        $table = new SortableTableFromArrayConfig($rows, 1, 10, 'OrphanedPages_table', '', '', 'ASC');
        $table->set_additional_parameters([
            'cid'     => $ctx['courseId'],
            'gid'     => $ctx['groupId'],
            'sid' => $ctx['sessionId'],
            'action'     => Security::remove_XSS($this->action ?? 'orphaned'),
        ]);
        $table->set_header(0, get_lang('Type'),  true, ['style' => 'width:48px;']);
        $table->set_header(1, get_lang('Title'), true);
        $table->display();
    }

    public function getWantedPages(): void
    {
        $ctx = self::ctx();
        $url = $ctx['baseUrl'];

        echo '<div class="actions">'.get_lang('Wanted pages').'</div>';

        // Existing page names in context
        $qbPages = self::repo()->createQueryBuilder('w')
            ->select('DISTINCT w.reflink AS reflink')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);
        if ($ctx['sessionId'] > 0) {
            $qbPages->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbPages->andWhere('COALESCE(w.sessionId,0) = 0');
        }
        $pages = array_map(fn($r) => (string)$r['reflink'], $qbPages->getQuery()->getArrayResult());

        // Latest pages
        $latestList = $this->getLatestPagesForContext();

        // Any token in linksto that is not an existing page -> wanted
        $wanted = [];
        foreach ($latestList as $w) {
            $tokens = preg_split('/\s+/', trim((string)$w->getLinksto())) ?: [];
            foreach ($tokens as $t) {
                $t = trim($t);
                if ($t === '') {
                    continue;
                }
                if (!in_array($t, $pages, true)) {
                    $wanted[] = $t;
                }
            }
        }
        $wanted = array_values(array_unique($wanted));

        $rows = [];
        foreach ($wanted as $token) {
            $token = Security::remove_XSS($token);
            $rows[] = [
                Display::url(
                    str_replace('_', ' ', $token),
                    $url.'&'.http_build_query(['action' => 'addnew', 'title' => str_replace('_', ' ', $token)]),
                    ['class' => 'new_wiki_link']
                ),
            ];
        }

        $table = new SortableTableFromArrayConfig($rows, 0, 10, 'WantedPages_table', '', '', 'ASC');
        $table->set_additional_parameters([
            'cid'     => $ctx['courseId'],
            'gid'     => $ctx['groupId'],
            'sid' => $ctx['sessionId'],
            'action'     => Security::remove_XSS($this->action ?? 'wanted'),
        ]);
        $table->set_header(0, get_lang('Title'), true);
        $table->display();
    }

    public function getStats(): bool
    {
        if (!api_is_allowed_to_edit(false, true)) {
            return false;
        }

        $ctx = self::ctx();
        echo '<div class="actions">'.get_lang('Statistics').'</div>';

        // Pull ALL versions in context (group/session/course) – no visibility filter (teachers)
        $qbAll = self::repo()->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);
        if ($ctx['sessionId'] > 0) {
            $qbAll->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbAll->andWhere('COALESCE(w.sessionId,0) = 0');
        }
        /** @var CWiki[] $allVersions */
        $allVersions = $qbAll->getQuery()->getResult();

        // Latest version per reflink
        $latestList = $this->getLatestPagesForContext();

        // ---- Aggregates across all versions ----
        $total_versions          = count($allVersions);
        $total_visits            = 0;
        $total_words             = 0;
        $total_links             = 0;
        $total_links_anchors     = 0;
        $total_links_mail        = 0;
        $total_links_ftp         = 0;
        $total_links_irc         = 0;
        $total_links_news        = 0;
        $total_wlinks            = 0;
        $total_images            = 0;
        $total_flash             = 0;
        $total_mp3               = 0;
        $total_flv               = 0;
        $total_youtube           = 0;
        $total_multimedia        = 0;
        $total_tables            = 0;
        $total_empty_content     = 0;
        $total_comment_version   = 0;

        foreach ($allVersions as $w) {
            $content = (string)$w->getContent();
            $total_visits += (int)($w->getHits() ?? 0);
            $total_words  += (int) self::word_count($content);
            $total_links  += substr_count($content, 'href=');
            $total_links_anchors += substr_count($content, 'href="#');
            $total_links_mail    += substr_count($content, 'href="mailto');
            $total_links_ftp     += substr_count($content, 'href="ftp');
            $total_links_irc     += substr_count($content, 'href="irc');
            $total_links_news    += substr_count($content, 'href="news');
            $total_wlinks        += substr_count($content, '[[');
            $total_images        += substr_count($content, '<img');
            $clean_total_flash    = preg_replace('/player\.swf/', ' ', $content);
            $total_flash         += substr_count((string)$clean_total_flash, '.swf"');
            $total_mp3           += substr_count($content, '.mp3');
            $total_flv           += (int) (substr_count($content, '.flv') / 5);
            $total_youtube       += substr_count($content, 'http://www.youtube.com');
            $total_multimedia    += substr_count($content, 'video/x-msvideo');
            $total_tables        += substr_count($content, '<table');
            if ($content === '') {
                $total_empty_content++;
            }
            if ((string)$w->getComment() !== '') {
                $total_comment_version++;
            }
        }

        // ---- Aggregates across latest version per page ----
        $total_pages     = count($latestList);
        $total_visits_lv = 0;
        $total_words_lv  = 0;
        $total_links_lv  = 0;
        $total_links_anchors_lv = 0;
        $total_links_mail_lv    = 0;
        $total_links_ftp_lv     = 0;
        $total_links_irc_lv     = 0;
        $total_links_news_lv    = 0;
        $total_wlinks_lv        = 0;
        $total_images_lv        = 0;
        $total_flash_lv         = 0;
        $total_mp3_lv           = 0;
        $total_flv_lv           = 0;
        $total_youtube_lv       = 0;
        $total_multimedia_lv    = 0;
        $total_tables_lv        = 0;
        $total_empty_content_lv = 0;

        $total_editing_now = 0;
        $total_hidden      = 0;
        $total_protected   = 0;
        $total_lock_disc   = 0;
        $total_hidden_disc = 0;
        $total_only_teachers_rating = 0;
        $total_task = 0;
        $total_teacher_assignment = 0;
        $total_student_assignment = 0;

        $score_sum = 0;
        $progress_sum = 0;

        foreach ($latestList as $w) {
            $content = (string)$w->getContent();

            $total_visits_lv += (int)($w->getHits() ?? 0);

            $total_words_lv  += (int) self::word_count($content);
            $total_links_lv  += substr_count($content, 'href=');
            $total_links_anchors_lv += substr_count($content, 'href="#');
            $total_links_mail_lv    += substr_count($content, 'href="mailto');
            $total_links_ftp_lv     += substr_count($content, 'href="ftp');
            $total_links_irc_lv     += substr_count($content, 'href="irc');
            $total_links_news_lv    += substr_count($content, 'href="news');
            $total_wlinks_lv        += substr_count($content, '[[');
            $total_images_lv        += substr_count($content, '<img');
            $clean_total_flash      = preg_replace('/player\.swf/', ' ', $content);
            $total_flash_lv         += substr_count((string)$clean_total_flash, '.swf"');
            $total_mp3_lv           += substr_count($content, '.mp3');
            $total_flv_lv           += (int) (substr_count($content, '.flv') / 5);
            $total_youtube_lv       += substr_count($content, 'http://www.youtube.com');
            $total_multimedia_lv    += substr_count($content, 'video/x-msvideo');
            $total_tables_lv        += substr_count($content, '<table');
            if ($content === '') {
                $total_empty_content_lv++;
            }

            // flags/counters from entity fields (latest only)
            if ((int)$w->getIsEditing() !== 0) {
                $total_editing_now++;
            }
            if ((int)$w->getVisibility() === 0) {
                $total_hidden++;
            }
            if ((int)$w->getEditlock() === 1) {
                $total_protected++;
            }
            if ((int)$w->getAddlockDisc() === 0) {
                $total_lock_disc++;
            }
            if ((int)$w->getVisibilityDisc() === 0) {
                $total_hidden_disc++;
            }
            if ((int)$w->getRatinglockDisc() === 0) {
                $total_only_teachers_rating++;
            }
            if ((int)$w->getAssignment() === 1) {
                $total_teacher_assignment++;
            }
            if ((int)$w->getAssignment() === 2) {
                $total_student_assignment++;
            }

            $conf = self::confRepo()->findOneBy(['cId' => $ctx['courseId'], 'pageId' => (int)$w->getPageId()]);
            if ($conf && (string)$conf->getTask() !== '') {
                $total_task++;
            }

            $score_sum    += (int)($w->getScore() ?? 0);
            $progress_sum += (int)($w->getProgress() ?? 0);
        }

        $media_score    = $total_pages > 0 ? ($score_sum / $total_pages) : 0;
        $media_progress = $total_pages > 0 ? ($progress_sum / $total_pages) : 0;

        // Student add new pages status (from any latest – addlock is uniform)
        $wiki_add_lock = 0;
        if (!empty($latestList)) {
            $wiki_add_lock = (int)$latestList[0]->getAddlock();
        }
        $status_add_new_pag = $wiki_add_lock === 1 ? get_lang('Yes') : get_lang('No');

        // First and last wiki dates
        $first_wiki_date = '';
        $last_wiki_date  = '';
        if (!empty($allVersions)) {
            usort($allVersions, fn($a,$b) => $a->getDtime() <=> $b->getDtime());
            $first_wiki_date = api_get_local_time($allVersions[0]->getDtime());
            $last_wiki_date  = api_get_local_time($allVersions[count($allVersions)-1]->getDtime());
        }

        // Total users / total IPs (across all versions)
        $usersSet = [];
        $ipSet    = [];
        foreach ($allVersions as $w) {
            $usersSet[(int)$w->getUserId()] = true;
            $ipSet[(string)$w->getUserIp()] = true;
        }
        $total_users = count($usersSet);
        $total_ip    = count($ipSet);

        // ---- Render tables ----

        echo '<table class="table table-hover table-striped data_table">';
        echo '<thead><tr><th colspan="2">'.get_lang('General').'</th></tr></thead>';
        echo '<tr><td>'.get_lang('Learners can add new pages to the Wiki').'</td><td>'.$status_add_new_pag.'</td></tr>';
        echo '<tr><td>'.get_lang('Creation date of the oldest Wiki page').'</td><td>'.$first_wiki_date.'</td></tr>';
        echo '<tr><td>'.get_lang('Date of most recent edition of Wiki').'</td><td>'.$last_wiki_date.'</td></tr>';
        echo '<tr><td>'.get_lang('Average rating of all pages').'</td><td>'.$media_score.' %</td></tr>';
        echo '<tr><td>'.get_lang('Mean estimated progress by users on their pages').'</td><td>'.$media_progress.' %</td></tr>';
        echo '<tr><td>'.get_lang('Total users that have participated in this Wiki').'</td><td>'.$total_users.'</td></tr>';
        echo '<tr><td>'.get_lang('Total different IP addresses that have contributed to Wiki').'</td><td>'.$total_ip.'</td></tr>';
        echo '</table><br/>';

        echo '<table class="table table-hover table-striped data_table">';
        echo '<thead><tr><th colspan="2">'.get_lang('Pages').' '.get_lang('and').' '.get_lang('Versions').'</th></tr></thead>';
        echo '<tr><td>'.get_lang('Pages').' - '.get_lang('Number of contributions').'</td><td>'.$total_pages.' ('.get_lang('Versions').': '.$total_versions.')</td></tr>';
        echo '<tr><td>'.get_lang('Total of empty pages').'</td><td>'.$total_empty_content_lv.' ('.get_lang('Versions').': '.$total_empty_content.')</td></tr>';
        echo '<tr><td>'.get_lang('Number of visits').'</td><td>'.$total_visits_lv.' ('.get_lang('Versions').': '.$total_visits.')</td></tr>';
        echo '<tr><td>'.get_lang('Total pages edited at this time').'</td><td>'.$total_editing_now.'</td></tr>';
        echo '<tr><td>'.get_lang('Total hidden pages').'</td><td>'.$total_hidden.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of protected pages').'</td><td>'.$total_protected.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of discussion pages blocked').'</td><td>'.$total_lock_disc.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of discussion pages hidden').'</td><td>'.$total_hidden_disc.'</td></tr>';
        echo '<tr><td>'.get_lang('Total comments on various versions of the pages').'</td><td>'.$total_comment_version.'</td></tr>';
        echo '<tr><td>'.get_lang('Total pages can only be scored by a teacher').'</td><td>'.$total_only_teachers_rating.'</td></tr>';
        echo '<tr><td>'.get_lang('Total pages that can be scored by other learners').'</td><td>'.max(0, $total_pages - $total_only_teachers_rating).'</td></tr>';
        echo '<tr><td>'.get_lang('Number of assignments pages proposed by a teacher').' - '.get_lang('Portfolio mode').'</td><td>'.$total_teacher_assignment.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of individual assignments learner pages').' - '.get_lang('Portfolio mode').'</td><td>'.$total_student_assignment.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of tasks').' - '.get_lang('Standard Task mode').'</td><td>'.$total_task.'</td></tr>';
        echo '</table><br/>';

        echo '<table class="table table-hover table-striped data_table">';
        echo '<thead>';
        echo '<tr><th colspan="3">'.get_lang('Information about the content of the pages').'</th></tr>';
        echo '<tr><td></td><td>'.get_lang('In the last version').'</td><td>'.get_lang('In all versions').'</td></tr>';
        echo '</thead>';
        echo '<tr><td>'.get_lang('Number of words').'</td><td>'.$total_words_lv.'</td><td>'.$total_words.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of external html links inserted (text, images, ...).').'</td>'.
            '<td>'.$total_links_lv.' ('.get_lang('Anchors').':'.$total_links_anchors_lv.', Mail:'.$total_links_mail_lv.', FTP:'.$total_links_ftp_lv.' IRC:'.$total_links_irc_lv.', News:'.$total_links_news_lv.')</td>'.
            '<td>'.$total_links.' ('.get_lang('Anchors').':'.$total_links_anchors.', Mail:'.$total_links_mail.', FTP:'.$total_links_ftp.' IRC:'.$total_links_irc.', News:'.$total_links_news.')</td></tr>';
        echo '<tr><td>'.get_lang('Number of wiki links').'</td><td>'.$total_wlinks_lv.'</td><td>'.$total_wlinks.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of inserted images').'</td><td>'.$total_images_lv.'</td><td>'.$total_images.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of inserted flash files').'</td><td>'.$total_flash_lv.'</td><td>'.$total_flash.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of mp3 audio files inserted').'</td><td>'.$total_mp3_lv.'</td><td>'.$total_mp3.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of FLV video files inserted').'</td><td>'.$total_flv_lv.'</td><td>'.$total_flv.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of Youtube video embedded').'</td><td>'.$total_youtube_lv.'</td><td>'.$total_youtube.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of audio and video files inserted (except mp3 and flv)').'</td><td>'.$total_multimedia_lv.'</td><td>'.$total_multimedia.'</td></tr>';
        echo '<tr><td>'.get_lang('Number of tables inserted').'</td><td>'.$total_tables_lv.'</td><td>'.$total_tables.'</td></tr>';
        echo '</table>';

        return true;
    }

    public function getStatsTable(): void
    {
        $ctx = self::ctx();
        $url = $ctx['baseUrl'];

        // Breadcrumb
        echo '<div class="wiki-bc-wrap">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb--wiki">
              <li class="breadcrumb-item">
                <a href="'.
                    $this->url(['action'=>'showpage','title'=>'index']).'">'.
                    Display::getMdiIcon(ActionIcon::HOME, 'mdi-inline', null, ICON_SIZE_SMALL, get_lang('Home')).
                    '<span>'.get_lang('Wiki').'</span>
                </a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">'.
                    Display::getMdiIcon(ActionIcon::VIEW_MORE, 'mdi-inline', null, ICON_SIZE_SMALL, get_lang('More')).
                    '<span>'.get_lang('More').'</span>
              </li>

              <div class="breadcrumb-actions">
                <a class="btn btn-default btn-xs" href="'.$this->url().'">'.
                    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Back')).
                    ' '.get_lang('Back').'
                </a>
              </div>
            </ol>
          </nav>
        </div>';

        echo '<div class="row wiki-stats-grid">';

        // Column: “More”
        echo '<div class="col-sm-6 col-md-4">
            <div class="panel panel-default">
              <div class="panel-heading"><strong>'.get_lang('More').'</strong></div>
              <div class="panel-body">'.

            Display::url(
                Display::getMdiIcon(ActionIcon::ADD_USER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM)
                .' '.get_lang('Most active users'),
                $url.'&action=mactiveusers'
            ).
            Display::url(
                Display::getMdiIcon(ActionIcon::HISTORY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM)
                .' '.get_lang('Most visited pages'),
                $url.'&action=mvisited'
            ).
            Display::url(
                Display::getMdiIcon(ActionIcon::REFRESH, 'ch-tool-icon', null, ICON_SIZE_MEDIUM)
                .' '.get_lang('Most changed pages'),
                $url.'&action=mostchanged'
            ).

            '</div>
            </div>
          </div>';

        // Column: “Pages”
        echo '<div class="col-sm-6 col-md-4">
            <div class="panel panel-default">
              <div class="panel-heading"><strong>'.get_lang('Pages').'</strong></div>
              <div class="panel-body">'.

            Display::url(
                Display::getMdiIcon(ActionIcon::CLOSE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM)
                .' '.get_lang('Orphaned pages'),
                $url.'&action=orphaned'
            ).
            Display::url(
                Display::getMdiIcon(ActionIcon::STAR_OUTLINE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM)
                .' '.get_lang('Wanted pages'),
                $url.'&action=wanted'
            ).
            Display::url(
                Display::getMdiIcon(ActionIcon::LINKS, 'ch-tool-icon', null, ICON_SIZE_MEDIUM)
                .' '.get_lang('Pages most linked'),
                $url.'&action=mostlinked'
            ).

            '</div>
            </div>
          </div>';

        // Column: “Statistics” (admins/teachers)
        echo '<div class="col-sm-12 col-md-4">
            <div class="panel panel-default">
              <div class="panel-heading"><strong>'.get_lang('Statistics').'</strong></div>
              <div class="panel-body">';
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            echo Display::url(
                Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM)
                .' '.get_lang('Statistics'),
                $url.'&action=statistics'
            );
        } else {
            echo '<span class="text-muted">'.get_lang('No data available').'</span>';
        }
        echo       '</div>
            </div>
          </div>';

        echo '</div>'; // row
    }

    public function getLinks(string $page): void
    {
        $ctx   = self::ctx();
        $url   = $ctx['baseUrl'];
        $titleInGet = $_GET['title'] ?? null;

        if (!$titleInGet) {
            Display::addFlash(Display::return_message(get_lang('You must select a page first'), 'error', false));
            return;
        }

        // Normalize incoming page key (handle "Main page" ↔ index ↔ underscored)
        $raw = html_entity_decode($page, ENT_QUOTES);
        $reflink = WikiManager::normalizeReflink((string) $page);
        $displayTitleLink = WikiManager::displayTokenFor($reflink);
        if ($reflink === 'index') {
            $displayTitleLink = str_replace(' ', '_', get_lang('Home'));
        }

        // Load the target page (latest) to show its title and assignment icon
        $target = self::repo()->findOneBy(
            ['cId' => $ctx['courseId'], 'reflink' => $reflink, 'groupId' => (int)$ctx['groupId'], 'sessionId' => (int)$ctx['sessionId']],
            ['version' => 'DESC', 'dtime' => 'DESC']
        ) ?? self::repo()->findOneBy(['cId' => $ctx['courseId'], 'reflink' => $reflink], ['version' => 'DESC']);
        if (!$target) {
            Display::addFlash(Display::return_message(get_lang('You must select a page first'), 'error', false));
            return;
        }

        $assignmentIcon = self::assignmentIcon((int)$target->getAssignment());

        echo '<div id="wikititle">'
            .get_lang('Pages that link to this page').": {$assignmentIcon} "
            .Display::url(
                api_htmlentities($target->getTitle()),
                $url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($reflink)])
            )
            .'</div>';

        // Build list of latest pages in context, then filter those whose linksto contains the token
        $latestList = $this->getLatestPagesForContext();
        $token = (string)$displayTitleLink; // tokens in linksto are space-separated

        $rows = [];
        foreach ($latestList as $w) {
            // Visibility gate for students
            if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
                if ((int)$w->getVisibility() !== 1) {
                    continue;
                }
            }

            // match token in linksto (space-separated)
            $tokens = preg_split('/\s+/', trim((string)$w->getLinksto())) ?: [];
            if (!in_array($token, $tokens, true)) {
                continue;
            }

            // Row build
            $userinfo = api_get_user_info($w->getUserId());
            $author   = $userinfo !== false
                ? UserManager::getUserProfileLink($userinfo)
                : get_lang('Anonymous').' ('.api_htmlentities((string)$w->getUserIp()).')';

            $rows[] = [
                self::assignmentIcon((int)$w->getAssignment()),
                Display::url(
                    api_htmlentities($w->getTitle()),
                    $url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($w->getReflink())])
                ),
                $author,
                api_get_local_time($w->getDtime()),
            ];
        }

        if (empty($rows)) {
            return;
        }

        $table = new SortableTableFromArrayConfig($rows, 1, 10, 'AllPages_table', '', '', 'ASC');
        $table->set_additional_parameters([
            'cid'     => $ctx['courseId'],
            'gid'     => $ctx['groupId'],
            'sid' => $ctx['sessionId'],
            'action'     => Security::remove_XSS($this->action ?? 'links'),
        ]);
        $table->set_header(0, get_lang('Type'),   true, ['style' => 'width:30px;']);
        $table->set_header(1, get_lang('Title'),  true);
        $table->set_header(2, get_lang('Author'), true);
        $table->set_header(3, get_lang('Date'),   true);
        $table->display();
    }

    public function exportTo(int $id, string $format = 'doc'): bool
    {
        $page = self::repo()->findOneBy(['iid' => $id]);
        if ($page instanceof CWiki) {
            $content = (string)$page->getContent();
            $name    = (string)$page->getReflink();
            if ($content !== '') {
                Export::htmlToOdt($content, $name, $format);
                return true;
            }
            return false;
        }

        if (method_exists($this, 'getWikiDataFromDb')) {
            $data = self::getWikiDataFromDb($id);
            if (!empty($data['content'])) {
                Export::htmlToOdt($data['content'], (string)$data['reflink'], $format);
                return true;
            }
        }

        return false;
    }

    public function export_to_pdf(int $id, string $course_code): bool
    {
        if (!api_is_platform_admin() && api_get_setting('students_export2pdf') !== 'true') {
            Display::addFlash(
                Display::return_message(get_lang('PDF download is not allowed for students'), 'error', false)
            );
            return false;
        }

        $page = self::repo()->findOneBy(['iid' => $id]);
        $titleRaw   = '';
        $contentRaw = '';

        if ($page instanceof CWiki) {
            $titleRaw   = (string) $page->getTitle();
            $contentRaw = (string) $page->getContent();
        } elseif (method_exists($this, 'getWikiDataFromDb')) {
            $data = (array) self::getWikiDataFromDb($id);
            $titleRaw   = (string) ($data['title']   ?? '');
            $contentRaw = (string) ($data['content'] ?? '');
        }

        if ($titleRaw === '' && $contentRaw === '') {
            Display::addFlash(Display::return_message(get_lang('No search results'), 'error', false));
            return false;
        }

        $this->renderPdfFromHtmlDirect($titleRaw, $contentRaw, $course_code);
        return true;
    }

    /**
     * Render PDF directly using mPDF (preferred) or Dompdf (fallback).
     * If neither is installed, fall back to direct HTML download.
     */
    private function renderPdfFromHtmlDirect(string $title, string $content, string $courseCode): void
    {
        // Minimal safe print CSS (UTF-8, supports DejaVu Sans for wide Unicode)
        $css = '
        body{font-family:"DejaVu Sans",Arial,Helvetica,sans-serif;font-size:12pt;line-height:1.45;color:#222;margin:16px;}
        h1,h2,h3{margin:0 0 10px;}
        h1{font-size:20pt} h2{font-size:16pt} h3{font-size:14pt}
        p{margin:0 0 8px;} img{max-width:100%;height:auto;}
        .wiki-title{font-weight:bold;margin-bottom:12px;border-bottom:1px solid #ddd;padding-bottom:6px;}
        .wiki-content{margin-top:8px;}
        table{border-collapse:collapse} td,th{border:1px solid #ddd;padding:4px}
        pre,code{font-family:Menlo,Consolas,monospace;font-size:10pt;white-space:pre-wrap;word-wrap:break-word}
    ';

        // Fix relative course media inside content to absolute URLs
        if (defined('REL_COURSE_PATH') && defined('WEB_COURSE_PATH')) {
            if (api_strpos($content, '../..'.api_get_path(REL_COURSE_PATH)) !== false) {
                $content = str_replace('../..'.api_get_path(REL_COURSE_PATH), api_get_path(WEB_COURSE_PATH), $content);
            }
        }

        // Sanitize title for document/file names
        $safeTitle = trim($title) !== '' ? $title : 'wiki_page';
        $downloadName = preg_replace('/\s+/', '_', (string) api_replace_dangerous_char($safeTitle)).'.pdf';

        // Wrap content (keep structure simple for HTML→PDF engines)
        $html = '<!DOCTYPE html><html lang="'.htmlspecialchars(api_get_language_isocode()).'"><head>'
            .'<meta charset="'.htmlspecialchars(api_get_system_encoding()).'">'
            .'<title>'.htmlspecialchars($safeTitle).'</title>'
            .'<style>'.$css.'</style>'
            .'</head><body>'
            .'<div class="wiki-title"><h1>'.htmlspecialchars($safeTitle).'</h1></div>'
            .'<div class="wiki-content">'.$content.'</div>'
            .'</body></html>';

        // --- Try mPDF first ---
        if (class_exists('\\Mpdf\\Mpdf')) {
            // Use mPDF directly
            try {
                $mpdf = new \Mpdf\Mpdf([
                    'tempDir' => sys_get_temp_dir(),
                    'mode'    => 'utf-8',
                    'format'  => 'A4',
                    'margin_left'   => 12,
                    'margin_right'  => 12,
                    'margin_top'    => 12,
                    'margin_bottom' => 12,
                ]);
                $mpdf->SetTitle($safeTitle);
                $mpdf->WriteHTML($html);
                // Force download
                $mpdf->Output($downloadName, 'D');
                exit;
            } catch (\Throwable $e) {
                // Continue to next engine
            }
        }

        // --- Try Dompdf fallback ---
        if (class_exists('\\Dompdf\\Dompdf')) {
            try {
                $dompdf = new \Dompdf\Dompdf([
                    'chroot' => realpath(__DIR__.'/../../..'),
                    'isRemoteEnabled' => true,
                ]);
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $dompdf->stream($downloadName, ['Attachment' => true]);
                exit;
            } catch (\Throwable $e) {
                // Continue to final fallback
            }
        }

        // --- Final fallback: deliver HTML as download (not PDF) ---
        // Clean buffers to avoid header issues
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) { @ob_end_clean(); }
        }
        $htmlName = preg_replace('/\.pdf$/i', '.html', $downloadName);
        header('Content-Type: text/html; charset='.api_get_system_encoding());
        header('Content-Disposition: attachment; filename="'.$htmlName.'"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo $html;
        exit;
    }

    public function getActiveUsers(string $action): void
    {
        echo '<div class="actions">'.get_lang('Most active users').'</div>';

        $courseId  = $this->currentCourseId();
        $groupId   = $this->currentGroupId();
        $sessionId = $this->currentSessionId();

        $data = $this->wikiRepo->countEditsByUser($courseId, $groupId, $sessionId);

        if (!$data) {
            return;
        }

        $rows = [];
        foreach ($data as $row) {
            $userId   = (int) $row['userId'];
            $userIp   = (string) ($row['userIp'] ?? '');
            $numEdits = (int) $row['numEdits'];

            $userInfo = $userId > 0 ? api_get_user_info($userId) : false;

            $authorCell = ($userId !== 0 && $userInfo !== false)
                ? Display::url(
                    $userInfo['complete_name_with_username'],
                    $this->wikiUrl(['action' => 'usercontrib', 'user_id' => $userId])
                )
                : get_lang('Anonymous').' ('.api_htmlentities($userIp).')';

            $rows[] = [
                $authorCell,
                Display::url(
                    (string) $numEdits,
                    $this->wikiUrl(['action' => 'usercontrib', 'user_id' => $userId])
                ),
            ];
        }

        $table = new SortableTableFromArrayConfig(
            $rows,
            1,
            10,
            'MostActiveUsersA_table',
            '',
            '',
            'DESC'
        );
        $table->set_additional_parameters([
            'cid'     => $_GET['cid']     ?? null,
            'gid'     => $_GET['gid']     ?? null,
            'sid' => $_GET['sid'] ?? null,
            'action'     => Security::remove_XSS($action),
        ]);
        $table->set_header(0, get_lang('Author'), true);
        $table->set_header(1, get_lang('Contributions'), true, ['style' => 'width:30px;']);
        $table->display();
    }

    /**
     * Check & toggle “notify me by email” for a discussion.
     * Returns current status: 0 (off) / 1 (on).
     */
    public function checkNotifyDiscuss(string $reflink): int
    {
        $ctx           = self::ctx();
        $conn          = $this->conn();
        $tblMailcue    = $this->tblWikiMailcue();
        $linkCol       = $this->mailcueLinkColumn();
        $userId        = (int) api_get_user_id();
        $versionId     = $this->firstVersionIdByReflink($reflink);

        if (!$versionId) {
            // If the page has no versions yet, there is nothing to toggle
            return 0;
        }

        // Read current status
        $count = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM '.$tblMailcue.'
     WHERE c_id = :cid
       AND '.$linkCol.' = :vid
       AND user_id = :uid
       AND type = :type
       AND COALESCE(group_id,0)   = :gid
       AND COALESCE(session_id,0) = :sid',
            [
                'cid'  => (int)$ctx['courseId'],
                'vid'  => $versionId,
                'uid'  => $userId,
                'type' => 'D',
                'gid'  => (int)$ctx['groupId'],
                'sid'  => (int)$ctx['sessionId'],
            ]
        );

        $status = $count > 0 ? 1 : 0;

        // Toggle based on actionpage
        $actionPage = $_GET['actionpage'] ?? null;

        if ($actionPage === 'locknotifydisc' && $status === 0) {
            // Turn ON
            $conn->insert($tblMailcue, [
                'c_id'       => (int)$ctx['courseId'],
                $linkCol     => $versionId,
                'user_id'    => $userId,
                'type'       => 'D',
                'group_id'   => (int)$ctx['groupId'],
                'session_id' => (int)$ctx['sessionId'],
            ]);
            $status = 1;
        } elseif ($actionPage === 'unlocknotifydisc' && $status === 1) {
            // Turn OFF
            $conn->executeStatement(
                'DELETE FROM '.$tblMailcue.'
         WHERE c_id = :cid
           AND '.$linkCol.' = :vid
           AND user_id = :uid
           AND type = :type
           AND COALESCE(group_id,0)   = :gid
           AND COALESCE(session_id,0) = :sid',
                [
                    'cid'  => (int)$ctx['courseId'],
                    'vid'  => $versionId,
                    'uid'  => $userId,
                    'type' => 'D',
                    'gid'  => (int)$ctx['groupId'],
                    'sid'  => (int)$ctx['sessionId'],
                ]
            );
            $status = 0;
        }

        return $status;
    }

    /**
     * Build the Category create/edit form (Doctrine, Chamilo FormValidator).
     */
    private function createCategoryForm(?CWikiCategory $category = null): FormValidator
    {
        $em           = Container::getEntityManager();
        $categoryRepo = $em->getRepository(CWikiCategory::class);

        $course  = api_get_course_entity();
        $session = api_get_session_entity();

        // List of categories available in this course/session
        $categories = $categoryRepo->findByCourse($course, $session);

        // Action URL using our url() helper (adds cidreq safely)
        $form = new FormValidator(
            'category',
            'post',
            $this->url(['action' => 'category', 'id' => $category ? $category->getId() : null])
        );

        $form->addHeader(get_lang('Add category'));
        // attributes array MUST be provided (empty array ok)
        $form->addSelectFromCollection('parent', get_lang('Parent'), $categories, [], true, 'getNodeName');
        $form->addText('name', get_lang('Name'));

        if ($category) {
            $form->addButtonUpdate(get_lang('Update'));
        } else {
            $form->addButtonSave(get_lang('Save'));
        }

        if ($form->validate()) {
            $values = $form->exportValues();
            $parent = !empty($values['parent']) ? $categoryRepo->find((int)$values['parent']) : null;

            if (!$category) {
                $category = (new CWikiCategory())
                    ->setCourse($course)
                    ->setSession($session);
                $em->persist($category);

                Display::addFlash(Display::return_message(get_lang('The category has been added'), 'success'));
            } else {
                Display::addFlash(Display::return_message(get_lang('The forum category has been modified'), 'success'));
            }

            $category
                ->setName((string)$values['name'])
                ->setParent($parent);

            $em->flush();

            header('Location: '.$this->url(['action' => 'category']));
            exit;
        }

        if ($category) {
            $form->setDefaults([
                'parent' => $category->getParent() ? $category->getParent()->getId() : 0,
                'name'   => $category->getName(),
            ]);
        }

        return $form;
    }

    /**
     * Discussion screen for a wiki page (Doctrine/DBAL).
     */
    public function getDiscuss(string $page): void
    {
        $ctx  = self::ctx(api_get_course_int_id(), api_get_session_id(), api_get_group_id());
        $em   = Container::getEntityManager();
        $conn = $em->getConnection();

        // Session restriction
        if ($ctx['sessionId'] !== 0 && api_is_allowed_to_session_edit(false, true) === false) {
            api_not_allowed();
            return;
        }

        if (empty($_GET['title'])) {
            Display::addFlash(Display::return_message(get_lang('You must select a page first'), 'error', false));
            return;
        }

        $pageKey    = self::normalizeReflink($page);
        $actionPage = $_GET['actionpage'] ?? null;

        // --- Inline toggles (PRG) ---
        if ($actionPage) {
            $cid  = (int)$ctx['courseId'];
            $gid  = (int)$ctx['groupId'];
            $sid  = (int)$ctx['sessionId'];
            $uid  = (int)api_get_user_id();

            $predG = 'COALESCE(group_id,0) = :gid';
            $predS = 'COALESCE(session_id,0) = :sid';

            switch ($actionPage) {
                case 'lockdisc':
                    $conn->executeStatement(
                        "UPDATE c_wiki SET addlock_disc = 0
                     WHERE c_id = :cid AND reflink = :r AND $predG AND $predS",
                        ['cid'=>$cid,'r'=>$pageKey,'gid'=>$gid,'sid'=>$sid]
                    );
                    break;
                case 'unlockdisc':
                    $conn->executeStatement(
                        "UPDATE c_wiki SET addlock_disc = 1
                     WHERE c_id = :cid AND reflink = :r AND $predG AND $predS",
                        ['cid'=>$cid,'r'=>$pageKey,'gid'=>$gid,'sid'=>$sid]
                    );
                    break;
                case 'hidedisc':
                    $conn->executeStatement(
                        "UPDATE c_wiki SET visibility_disc = 0
                     WHERE c_id = :cid AND reflink = :r AND $predG AND $predS",
                        ['cid'=>$cid,'r'=>$pageKey,'gid'=>$gid,'sid'=>$sid]
                    );
                    break;
                case 'showdisc':
                    $conn->executeStatement(
                        "UPDATE c_wiki SET visibility_disc = 1
                     WHERE c_id = :cid AND reflink = :r AND $predG AND $predS",
                        ['cid'=>$cid,'r'=>$pageKey,'gid'=>$gid,'sid'=>$sid]
                    );
                    break;
                case 'lockrating':
                    $conn->executeStatement(
                        "UPDATE c_wiki SET ratinglock_disc = 0
                     WHERE c_id = :cid AND reflink = :r AND $predG AND $predS",
                        ['cid'=>$cid,'r'=>$pageKey,'gid'=>$gid,'sid'=>$sid]
                    );
                    break;
                case 'unlockrating':
                    $conn->executeStatement(
                        "UPDATE c_wiki SET ratinglock_disc = 1
                     WHERE c_id = :cid AND reflink = :r AND $predG AND $predS",
                        ['cid'=>$cid,'r'=>$pageKey,'gid'=>$gid,'sid'=>$sid]
                    );
                    break;
                case 'locknotifydisc':
                    if (api_is_allowed_to_session_edit()) {
                        $t = 'watchdisc:'.$pageKey;
                        $conn->executeStatement(
                            "INSERT INTO c_wiki_mailcue (c_id, group_id, session_id, user_id, type)
                         SELECT :cid, :gid, :sid, :uid, :t FROM DUAL
                         WHERE NOT EXISTS (
                           SELECT 1 FROM c_wiki_mailcue
                           WHERE c_id=:cid AND $predG AND $predS AND user_id=:uid AND type=:t
                         )",
                            ['cid'=>$cid,'gid'=>$gid,'sid'=>$sid,'uid'=>$uid,'t'=>$t]
                        );
                    }
                    break;
                case 'unlocknotifydisc':
                    if (api_is_allowed_to_session_edit()) {
                        $t = 'watchdisc:'.$pageKey;
                        $conn->executeStatement(
                            "DELETE FROM c_wiki_mailcue
                         WHERE c_id=:cid AND $predG AND $predS AND user_id=:uid AND type=:t",
                            ['cid'=>$cid,'gid'=>$gid,'sid'=>$sid,'uid'=>$uid,'t'=>$t]
                        );
                    }
                    break;
            }

            header('Location: '.$this->url(['action' => 'discuss', 'title' => $pageKey]));
            exit;
        }

        /** @var CWiki|null $last */
        $last = self::repo()->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :reflink')->setParameter('reflink', $pageKey)
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId'])
            ->orderBy('w.version', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        /** @var CWiki|null $first */
        $first = self::repo()->createQueryBuilder('w')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('w.reflink = :reflink')->setParameter('reflink', $pageKey)
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId'])
            ->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId'])
            ->orderBy('w.version', 'ASC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$last || !$first) {
            Display::addFlash(Display::return_message(get_lang('Discuss not available'), 'normal', false));
            return;
        }

        $publicationId   = $first->getPageId() ?: (int)$first->getIid();
        $lastVersionDate = api_get_local_time($last->getDtime());
        $lastUserInfo    = api_get_user_info($last->getUserId());

        // New comment (PRG)
        if (isset($_POST['Submit']) && self::double_post($_POST['wpost_id'] ?? '')) {
            $nowUtc   = api_get_utc_datetime();
            $authorId = (int) api_get_user_id();

            $conn->insert('c_wiki_discuss', [
                'c_id'           => $ctx['courseId'],
                'publication_id' => $publicationId,
                'userc_id'       => $authorId,
                'comment'        => (string)($_POST['comment'] ?? ''),
                'p_score'        => (string)($_POST['rating'] ?? '-'),
                'dtime'          => $nowUtc,
            ]);

            self::check_emailcue($publicationId, 'D', $nowUtc, $authorId);

            header('Location: '.$this->url(['action' => 'discuss', 'title' => $pageKey]));
            exit;
        }

        // Assignment badge
        $iconAssignment = null;
        if ($last->getAssignment() === 1) {
            $iconAssignment = Display::getMdiIcon(
                ActionIcon::WIKI_ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('This page is an assignment proposed by a trainer')
            );
        } elseif ($last->getAssignment() === 2) {
            $iconAssignment = Display::getMdiIcon(
                ActionIcon::WIKI_WORK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('This page is a learner work')
            );
        }

        echo '<div class="wiki-discuss"><div class="wd-wrap">';

        // Header
        echo '<div class="wd-header">';
        echo   '<div class="wd-titlebox">';
        echo     '<h3 class="wd-title">'.$iconAssignment.'&nbsp;'.api_htmlentities($last->getTitle()).'</h3>';
        if ($lastUserInfo !== false) {
            echo   '<div class="wd-meta">'.get_lang('The latest version was edited by').' '
                .UserManager::getUserProfileLink($lastUserInfo).' • '.$lastVersionDate.'</div>';
        }
        echo   '</div>';

        // Toolbar
        echo   '<div class="wd-toolbar">';
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            $addOpen   = (self::check_addlock_discuss($pageKey) === 1);
            $lockIcon  = $addOpen
                ? Display::getMdiIcon(ActionIcon::LOCK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Now only trainers can add comments to this discussion'))
                : Display::getMdiIcon(ActionIcon::UNLOCK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Now all members can add comments to this discussion'));
            $lockAction = $addOpen ? 'lockdisc' : 'unlockdisc';
            echo Display::url($lockIcon, $this->url(['action'=>'discuss','actionpage'=>$lockAction,'title'=>$pageKey]));
        }
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            $isVisible = (self::check_visibility_discuss($pageKey) === 1);
            $visIcon   = $isVisible
                ? Display::getMdiIcon(ActionIcon::VISIBLE,   'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Hide'))
                : Display::getMdiIcon(ActionIcon::INVISIBLE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Show'));
            $visAction = $isVisible ? 'hidedisc' : 'showdisc';
            echo Display::url($visIcon, $this->url(['action'=>'discuss','actionpage'=>$visAction,'title'=>$pageKey]));
        }
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            $ratingOn  = (self::check_ratinglock_discuss($pageKey) === 1);
            $starIcon  = $ratingOn
                ? Display::getMdiIcon(ActionIcon::STAR,         'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Now only trainers can rate this page'))
                : Display::getMdiIcon(ActionIcon::STAR_OUTLINE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Now all members can rate this page'));
            $rateAction = $ratingOn ? 'lockrating' : 'unlockrating';
            echo Display::url($starIcon, $this->url(['action'=>'discuss','actionpage'=>$rateAction,'title'=>$pageKey]));
        }
        if ($this->mailcueLinkColumn() !== null) {
            $notifyOn   = ($this->checkNotifyDiscuss($pageKey) === 1);
            $notifyIcon = $notifyOn
                ? Display::getMdiIcon(ActionIcon::EMAIL_ON,  'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Stop notifying me'))
                : Display::getMdiIcon(ActionIcon::EMAIL_OFF, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Notify me'));
            $notifyAction = $notifyOn ? 'unlocknotifydisc' : 'locknotifydisc';
            echo Display::url($notifyIcon, $this->url(['action'=>'discuss','actionpage'=>$notifyAction,'title'=>$pageKey]));
        }
        echo   '</div>'; // wd-toolbar
        echo '</div>';    // wd-header

        // Form
        if ((int)$last->getAddlockDisc() === 1 || api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            echo '<div class="panel panel-default wd-card"><div class="panel-body">';
            echo   '<form method="post" action="" class="form-horizontal wd-form">';
            echo     '<input type="hidden" name="wpost_id" value="'.api_get_unique_id().'">';

            echo     '<div class="form-group">';
            echo       '<label class="col-sm-2 control-label">'.get_lang('Comments').'</label>';
            echo       '<div class="col-sm-10"><textarea class="form-control" name="comment" rows="4" placeholder="'.api_htmlentities(get_lang('Comments')).'"></textarea></div>';
            echo     '</div>';

            echo     '<div class="form-group">';
            if ((int)$last->getRatinglockDisc() === 1 || api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                echo   '<label class="col-sm-2 control-label">'.get_lang('Rating').'</label>';
                echo   '<div class="col-sm-10"><select name="rating" class="form-control wd-rating">';
                echo     '<option value="-" selected>-</option>';
                for ($i=0; $i<=10; $i++) { echo '<option value="'.$i.'">'.$i.'</option>'; }
                echo   '</select></div>';
            } else {
                echo   '<input type="hidden" name="rating" value="-">';
                // Select disabled para mantener alineación
                echo   '<label class="col-sm-2 control-label">'.get_lang('Rating').'</label>';
                echo   '<div class="col-sm-10"><select class="form-control wd-rating" disabled><option>-</option></select></div>';
            }
            echo     '</div>';

            echo     '<div class="form-group"><div class="col-sm-offset-2 col-sm-10">';
            echo       '<button class="btn btn--primary" type="submit" name="Submit">'.get_lang('Send').'</button>';
            echo     '</div></div>';

            echo   '</form>';
            echo '</div></div>';
        }

        // Stats
        $comments = $conn->executeQuery(
            "SELECT d.* FROM c_wiki_discuss d
         WHERE d.c_id = :cid AND d.publication_id = :pid
         ORDER BY d.iid DESC",
            ['cid'=>$ctx['courseId'], 'pid'=>$publicationId]
        )->fetchAllAssociative();

        $countAll   = count($comments);
        $scoredRows = (int)$conn->fetchOne(
            "SELECT COUNT(*) FROM c_wiki_discuss
         WHERE c_id = :cid AND publication_id = :pid AND p_score <> '-'",
            ['cid'=>$ctx['courseId'], 'pid'=>$publicationId]
        );
        $sumRow = $conn->fetchAssociative(
            "SELECT SUM(CASE WHEN p_score <> '-' THEN p_score END) AS sumWPost
         FROM c_wiki_discuss WHERE c_id = :cid AND publication_id = :pid",
            ['cid'=>$ctx['courseId'], 'pid'=>$publicationId]
        );
        $avgNumeric = ($scoredRows > 0) ? (float)$sumRow['sumWPost'] / $scoredRows : 0.0;

        echo '<div class="wd-stats">';
        echo   '<span class="label label-default">'.get_lang('Comments on this page').': '.$countAll.'</span>';
        echo   '<span class="label label-default">'.get_lang('Number of comments scored:').' '.$scoredRows.'</span>';
        echo   '<span class="label label-default">'.get_lang('The average rating for the page is').': '.number_format($avgNumeric, 2).' / 10</span>';
        echo '</div>';

        // Persist score on wiki rows
        $conn->executeStatement(
            "UPDATE c_wiki SET score = :score
         WHERE c_id = :cid AND reflink = :reflink
           AND COALESCE(group_id,0) = :gid
           AND COALESCE(session_id,0) = :sid",
            [
                'score'   => $avgNumeric,
                'cid'     => $ctx['courseId'],
                'reflink' => $pageKey,
                'gid'     => (int)$ctx['groupId'],
                'sid'     => (int)$ctx['sessionId'],
            ]
        );

        // Comments list
        if ($countAll === 0) {
            echo '<div class="well wd-empty">'.get_lang('No search results found').'</div>';
        } else {
            foreach ($comments as $c) {
                $uInfo  = api_get_user_info((int)$c['userc_id']);
                $name   = $uInfo ? $uInfo['complete_name'] : get_lang('Anonymous');
                $status = ($uInfo && (string)$uInfo['status'] === '5') ? get_lang('Learner') : get_lang('Teacher');

                $photo  = ($uInfo && !empty($uInfo['avatar']))
                    ? '<img class="wd-avatar" src="'.$uInfo['avatar'].'" alt="'.api_htmlentities($name).'">'
                    : '<div class="wd-avatar wd-avatar--ph"></div>';

                $score = (string)$c['p_score'];
                $stars = '';
                if ($score !== '-' && ctype_digit($score)) {
                    $map = [
                        0=>'rating/stars_0.gif', 1=>'rating/stars_5.gif', 2=>'rating/stars_10.gif',
                        3=>'rating/stars_15.gif',4=>'rating/stars_20.gif',5=>'rating/stars_25.gif',
                        6=>'rating/stars_30.gif',7=>'rating/stars_35.gif',8=>'rating/stars_40.gif',
                        9=>'rating/stars_45.gif',10=>'rating/stars_50.gif',
                    ];
                    $stars = Display::return_icon($map[(int)$score]);
                }

                echo '<div class="wd-comment">';
                echo   $photo;
                echo   '<div class="wd-comment-body">';
                $profileLink = $uInfo ? UserManager::getUserProfileLink($uInfo) : api_htmlentities($name);
                echo   '<div class="wd-comment-meta">'.$profileLink.' <span class="wd-dot">•</span> '.$status.' <span class="wd-dot">•</span> '
                    . api_get_local_time($c['dtime']).' <span class="wd-dot">•</span> '
                    . get_lang('Rating').': '.$score.' '.$stars.'</div>';
                echo   '<div class="wd-comment-text">'.api_htmlentities((string)$c['comment']).'</div>';
                echo   '</div>';
                echo '</div>';
            }
        }

        echo '</div></div>';
    }

    public function export2doc(int $docId)
    {
        // Course & group context
        $_course   = api_get_course_info();
        $groupInfo = GroupManager::get_group_properties(api_get_group_id());

        // Try to get the wiki page
        $page = self::repo()->findOneBy(['iid' => $docId]);
        $data = [];
        if ($page instanceof CWiki) {
            $data = [
                'title'   => (string) $page->getTitle(),
                'content' => (string) $page->getContent(),
            ];
        } elseif (method_exists($this, 'getWikiDataFromDb')) {
            // Backward-compat accessor
            $data = (array) self::getWikiDataFromDb($docId);
        }

        if (empty($data) || trim((string)($data['title'] ?? '')) === '') {
            // Nothing to export
            return false;
        }

        $wikiTitle    = (string) $data['title'];
        $wikiContents = (string) $data['content'];

        // XHTML wrapper (kept for old styles and Math support)
        $template = <<<'HTML'
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{LANGUAGE}" lang="{LANGUAGE}">
<head>
<title>{TITLE}</title>
<meta http-equiv="Content-Type" content="text/html; charset={ENCODING}" />
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
{CSS}
/*]]>*/
</style>
{ASCIIMATHML_SCRIPT}
</head>
<body dir="{TEXT_DIRECTION}">
{CONTENT}
</body>
</html>
HTML;

        // Resolve visual theme (avoid api_get_setting('stylesheets'))
        $theme = 'chamilo';
        if (function_exists('api_get_visual_theme')) {
            $t = (string) api_get_visual_theme();
            if ($t !== '') {
                $theme = $t;
            }
        }

        // Load theme CSS (best-effort)
        $cssFile = api_get_path(SYS_CSS_PATH).'themes/'.$theme.'/default.css';
        $css     = file_exists($cssFile) ? (string) @file_get_contents($cssFile) : '';
        if ($css === '') {
            // Minimal fallback CSS to avoid a blank export
            $css = 'body{font:14px/1.5 Arial,Helvetica,sans-serif;color:#222;padding:16px;}
#wikititle h1{font-size:22px;margin:0 0 10px;}
#wikicontent{margin-top:8px}
img{max-width:100%;height:auto;}';
        }

        // Fix paths in CSS so exported HTML works out of LMS
        $rootRel = api_get_path(REL_PATH);
        $css     = str_replace('behavior:url("/main/css/csshover3.htc");', '', $css);
        $css     = str_replace('main/', $rootRel.'main/', $css);
        $css     = str_replace('images/', $rootRel.'main/css/themes/'.$theme.'/images/', $css);
        $css     = str_replace('../../img/', $rootRel.'main/img/', $css);

        // Math support if present in content
        $asciiScript = (api_contains_asciimathml($wikiContents) || api_contains_asciisvg($wikiContents))
            ? '<script src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/asciimath/ASCIIMathML.js" type="text/javascript"></script>'."\n"
            : '';

        // Clean wiki links [[...]] → visible text only
        $wikiContents = trim((string) preg_replace('/\[[\[]?([^\]|]*)[|]?([^|\]]*)\][\]]?/', '$1', $wikiContents));

        // Build final HTML
        $html = str_replace(
            ['{LANGUAGE}','{ENCODING}','{TEXT_DIRECTION}','{TITLE}','{CSS}','{ASCIIMATHML_SCRIPT}','{CONTENT}'],
            [
                api_get_language_isocode(),
                api_get_system_encoding(),
                api_get_text_direction(),
                $wikiTitle,
                $css,
                $asciiScript,
                $wikiContents
            ],
            $template
        );

        // Replace relative course paths with absolute URLs (guard in case constant differs)
        if (defined('REL_COURSE_PATH') && defined('WEB_COURSE_PATH')) {
            if (api_strpos($html, '../..'.api_get_path(REL_COURSE_PATH)) !== false) {
                $html = str_replace('../..'.api_get_path(REL_COURSE_PATH), api_get_path(WEB_COURSE_PATH), $html);
            }
        }

        // Compute a safe filename
        $baseName = preg_replace('/\s+/', '_', (string) api_replace_dangerous_char($wikiTitle));
        $downloadName = $baseName !== '' ? $baseName : 'wiki_page';
        $downloadName .= '.html';

        // --- MODE A: Register in Document tool when SYS_COURSE_PATH exists ---
        if (defined('SYS_COURSE_PATH')) {
            $exportDir = rtrim(
                api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document'.($groupInfo['directory'] ?? ''),
                '/'
            );

            if (!is_dir($exportDir)) {
                @mkdir($exportDir, 0775, true);
            }

            // Ensure unique filename on disk
            $i = 1;
            do {
                $fileName   = $baseName.'_'. $i .'.html';
                $exportPath = $exportDir .'/'. $fileName;
                $i++;
            } while (file_exists($exportPath));

            file_put_contents($exportPath, $html);

            // Register in Document tool
            $relativeDocPath = ($groupInfo['directory'] ?? '').'/'.$fileName;
            $docId = add_document(
                $_course,
                $relativeDocPath,
                'file',
                (int) filesize($exportPath),
                $wikiTitle
            );

            api_item_property_update(
                $_course,
                TOOL_DOCUMENT,
                $docId,
                'DocumentAdded',
                api_get_user_id(),
                $groupInfo
            );

            // Return doc id so caller can flash a confirmation
            return $docId;
        }

        // --- MODE B (fallback): Direct download (no Document registration) ---
        // Clean existing buffers to avoid header issues
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
        }

        header('Content-Type: text/html; charset='.api_get_system_encoding());
        header('Content-Disposition: attachment; filename="'.$downloadName.'"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');


        echo $html;
        exit;
    }

    /**
     * Internal helper to render wiki HTML to PDF with headers/footers and wiki-link cleanup.
     */
    private function renderPdfFromHtml(string $titleRaw, string $contentRaw, string $courseCode): void
    {
        // Decode entities using platform encoding
        $contentPdf = api_html_entity_decode($contentRaw, ENT_QUOTES, api_get_system_encoding());

        // Clean wiki links [[...]] -> visible text only (keep first capture)
        $contentPdf = trim(preg_replace('/\[[\[]?([^\]|]*)[|]?([^|\]]*)\][\]]?/', '$1', $contentPdf));

        $titlePdf = api_html_entity_decode($titleRaw, ENT_QUOTES, api_get_system_encoding());

        // Ensure UTF-8 for mPDF pipeline
        $titlePdf   = api_utf8_encode($titlePdf,   api_get_system_encoding());
        $contentPdf = api_utf8_encode($contentPdf, api_get_system_encoding());

        $html = '
<!-- defines the headers/footers - this must occur before the headers/footers are set -->
<!--mpdf
<pageheader name="odds" content-left="'.htmlspecialchars($titlePdf, ENT_QUOTES).'" header-style-left="color: #880000; font-style: italic;" line="1" />
<pagefooter name="odds" content-right="{PAGENO}/{nb}" line="1" />
<setpageheader name="odds" page="odd" value="on" show-this-page="1" />
<setpagefooter name="odds" page="O" value="on" />
mpdf-->'.$contentPdf;

        $css = api_get_print_css();

        $pdf = new PDF();
        $pdf->content_to_pdf($html, $css, $titlePdf, $courseCode);
        exit;
    }

    /**
     * Helper: latest version of each page (respecting course/group/session; no visibility gate).
     * @return CWiki[]
     */
    private function getLatestPagesForContext(): array
    {
        $ctx = self::ctx();
        $em  = Container::getEntityManager();
        $repo = self::repo();

        // Fetch distinct reflinks in context
        $qbRef = $repo->createQueryBuilder('w')
            ->select('DISTINCT w.reflink AS reflink')
            ->andWhere('w.cId = :cid')->setParameter('cid', $ctx['courseId'])
            ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', (int)$ctx['groupId']);
        if ($ctx['sessionId'] > 0) {
            $qbRef->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', (int)$ctx['sessionId']);
        } else {
            $qbRef->andWhere('COALESCE(w.sessionId,0) = 0');
        }
        $reflinks = array_map(fn($r) => (string)$r['reflink'], $qbRef->getQuery()->getArrayResult());

        $latest = [];
        foreach ($reflinks as $ref) {
            $page = $repo->findOneBy(
                ['cId' => $ctx['courseId'], 'reflink' => $ref, 'groupId' => (int)$ctx['groupId'], 'sessionId' => (int)$ctx['sessionId']],
                ['version' => 'DESC', 'dtime' => 'DESC']
            ) ?? $repo->findOneBy(['cId' => $ctx['courseId'], 'reflink' => $ref], ['version' => 'DESC', 'dtime' => 'DESC']);
            if ($page) {
                $latest[] = $page;
            }
        }
        return $latest;
    }

    private function currentCourseId(): int
    {
        return (int) ( $_GET['cid'] ?? api_get_course_int_id() );
    }

    private function currentGroupId(): ?int
    {
        $gid = $_GET['gid'] ?? api_get_group_id();
        return $gid === null ? null : (int) $gid;
    }

    private function currentSessionId(): ?int
    {
        $sid = $_GET['sid'] ?? api_get_session_id();
        return $sid === null ? null : (int) $sid;
    }

    private function wikiUrl(array $extra = []): string
    {
        $base = api_get_self();
        $params = array_merge([
            'cid' => $_GET['cid'] ?? null,
            'gid' => $_GET['gid'] ?? null,
            'sid' => $_GET['sid'] ?? null,
        ], $extra);

        $params = array_filter($params, static fn($v) => $v !== null && $v !== '');

        return $base.'?'.http_build_query($params);
    }

    /** Build base URL with current context (cid, gid, sid) */
    private function computeBaseUrl(): string
    {
        $base = api_get_self();
        $params = [
            'cid'     => api_get_course_id(),
            'gid'     => api_get_group_id(),
            'sid' => api_get_session_id(),
        ];

        return $base.'?'.http_build_query($params);
    }

    /** Helper to create Wiki tool URLs */
    private function url(array $params = []): string
    {
        return $this->baseUrl.($params ? '&'.http_build_query($params) : '');
    }

    private function addCategory(): void
    {
        // --- Permissions & feature flag ---
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            api_not_allowed(true);
        }
        if ('true' !== api_get_setting('wiki.wiki_categories_enabled')) {
            api_not_allowed(true);
        }

        // --- Repositories / context ---
        $em           = Container::getEntityManager();
        $categoryRepo = $em->getRepository(CWikiCategory::class);

        $course  = api_get_course_entity();
        $session = api_get_session_entity();

        // --- If editing, make sure the category exists and belongs to the current course/session ---
        $categoryToEdit = null;
        if (isset($_GET['id'])) {
            $categoryToEdit = $categoryRepo->find((int) $_GET['id']);
            if (!$categoryToEdit) {
                // English dev msg: Category not found in current repository
                api_not_allowed(true);
            }
            if ($course !== $categoryToEdit->getCourse() || $session !== $categoryToEdit->getSession()) {
                // English dev msg: Cross-course/session edition is not allowed
                api_not_allowed(true);
            }
        }

        // --- Fetch categories for list ---
        $categories = $categoryRepo->findByCourse($course, $session);

        // --- Action icons (MDI) ---
        $iconEdit   = Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit'));
        $iconDelete = Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete'));

        // --- Build rows for the table ---
        $rows = array_map(function (CWikiCategory $category) use ($iconEdit, $iconDelete) {
            $actions  = [];
            $actions[] = Display::url(
                $iconEdit,
                $this->url(['action' => 'category', 'id' => $category->getId()])
            );
            $actions[] = Display::url(
                $iconDelete,
                $this->url(['action' => 'delete_category', 'id' => $category->getId()])
            );

            return [
                $category->getNodeName(),
                implode(PHP_EOL, $actions),
            ];
        }, $categories);

        // --- Render form (create or edit) ---
        $form = $this->createCategoryForm($categoryToEdit);
        $form->display();

        echo '<hr/>';

        // --- Render table (name + actions) ---
        $table = new SortableTableFromArrayConfig(
            $rows,
            0,
            25,
            'WikiCategories_table'
        );
        $table->set_header(0, get_lang('Name'), false);
        $table->set_header(1, get_lang('Actions'), false, ['class' => 'text-right'], ['class' => 'text-right']);
        $table->display();
    }

    private function deleteCategory(): void
    {
        // --- Permissions & feature flag ---
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            api_not_allowed(true);
        }
        if ('true' !== api_get_setting('wiki.wiki_categories_enabled')) {
            api_not_allowed(true);
        }

        $em = Container::getEntityManager();

        if (!isset($_GET['id'])) {
            // English dev msg: Missing category id
            api_not_allowed(true);
        }

        /** @var CWikiCategory|null $category */
        $category = $em->find(CWikiCategory::class, (int) $_GET['id']);
        if (!$category) {
            // English dev msg: Category not found
            api_not_allowed(true);
        }

        // --- Security: only allow removing categories in the current course/session ---
        $course  = api_get_course_entity();
        $session = api_get_session_entity();
        if ($course !== $category->getCourse() || $session !== $category->getSession()) {
            // English dev msg: Cross-course/session deletion is not allowed
            api_not_allowed(true);
        }

        // --- Delete and flush ---
        $em->remove($category);
        $em->flush();

        // --- UX feedback + redirect ---
        Display::addFlash(
            Display::return_message(get_lang('Category deleted'), 'success')
        );

        header('Location: '.$this->url(['action' => 'category']));
        exit;
    }

    /** Normalize a reflink into a stable key. Only 'index' is the main page. */
    public static function normalizeReflink(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return 'index';
        }
        $s = self::normalizeToken($raw);

        // Build aliases for the main page (both keys; fallback-safe)
        $tHome         = (string) (get_lang('Home') ?: '');
        $tDefaultTitle = (string) (get_lang('Home') ?: '');

        $aliases = array_filter([
            'index',
            self::normalizeToken($tHome),
            self::normalizeToken($tDefaultTitle),
        ]);

        if (in_array($s, $aliases, true)) {
            return 'index';
        }
        return $s;
    }

    /** Internal: apply the same normalization that we use for comparisons. */
    private static function normalizeToken(string $t): string
    {
        $t = html_entity_decode($t, ENT_QUOTES);
        $t = strip_tags(trim($t));
        $t = mb_strtolower($t);
        $t = strtr($t, [' ' => '_', '-' => '_']);
        $t = preg_replace('/_+/', '_', $t);
        return $t;
    }

    /** True if the reflink is the main page. */
    private static function isMain(string $reflink): bool
    {
        return $reflink === 'index';
    }

    public static function displayTitleFor(string $reflink, ?string $dbTitle = null): string
    {
        if (self::isMain($reflink)) {
            return get_lang('Home');
        }
        return $dbTitle !== null && $dbTitle !== '' ? $dbTitle : str_replace('_', ' ', $reflink);
    }

    public static function displayTokenFor(string $reflink): string
    {
        if (self::isMain($reflink)) {
            $label = get_lang('Home');
            return str_replace(' ', '_', $label);
        }
        return $reflink;
    }

    private static function dbg(string $msg): void
    {
        if (1) {
            echo '<!-- WIKI DEBUG: '.htmlspecialchars($msg, ENT_QUOTES).' -->' . PHP_EOL;
            error_log('[WIKI DEBUG] '.$msg);
        }
    }

    private static function utf8mb4_safe_entities(string $s): string {
        return preg_replace_callback('/[\x{10000}-\x{10FFFF}]/u', static function($m) {
            $cp = self::uniord($m[0]);
            return sprintf('&#%d;', $cp);
        }, $s);
    }
    private static function uniord(string $c): int {
        $u = mb_convert_encoding($c, 'UCS-4BE', 'UTF-8');
        $u = unpack('N', $u);
        return $u[1];
    }
}

/** Backwards-compat shim so index.php can still `new Wiki()` */
if (!class_exists('Wiki')) {
    class_alias(WikiManager::class, 'Wiki');
}

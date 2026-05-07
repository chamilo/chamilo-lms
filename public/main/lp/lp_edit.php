<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Search\Xapian\LpXapianIndexer;
use Chamilo\CourseBundle\Entity\CLp;
use ChamiloSession as Session;

/*
 * Script allowing simple edition of learnpath information (title, description, etc).
 *
 * @author  Yannick Warnier <ywarnier@beeznest.org>
 */
api_protect_course_script();

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');
$lpRepo = Container::getLpRepository();

$request = Container::getRequest();
$lpId = $request->query->getInt('lp_id');
if (empty($lpId)) {
    api_not_allowed(true);
}

/** @var CLp $lp */
$lp = $lpRepo->find($lpId);

$nameTools = get_lang('Document');
$this_section = SECTION_COURSES;
Event::event_access_tool(TOOL_LEARNPATH);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}
$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self().'?action=add_item&lp_id='.$lpId.'&'.api_get_cidreq(),
    'name' => $learnPath->getNameNoTags(),
];

$htmlHeadXtra[] = '<script>
function activate_start_date() {
	if(document.getElementById(\'start_date_div\').style.display == \'none\') {
		document.getElementById(\'start_date_div\').style.display = \'block\';
	} else {
		document.getElementById(\'start_date_div\').style.display = \'none\';
	}
}

function activate_end_date() {
    if(document.getElementById(\'end_date_div\').style.display == \'none\') {
        document.getElementById(\'end_date_div\').style.display = \'block\';
    } else {
        document.getElementById(\'end_date_div\').style.display = \'none\';
    }
}

</script>';

$defaults = [];
$form = new FormValidator(
    'form1',
    'post',
    'lp_controller.php?'.api_get_cidreq().'&lp_id='.$lpId
);

$form->addHeader(get_lang('Edit'));

// Title
if ('true' === api_get_setting('editor.save_titles_as_html')) {
    $form->addHtmlEditor(
        'lp_name',
        get_lang('Learning path name'),
        true,
        false,
        ['ToolbarSet' => 'TitleAsHtml']
    );
} else {
    $form->addElement('text', 'lp_name', api_ucfirst(get_lang('Title')), ['size' => 43]);
}
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('Required field'), 'required');
$form->addElement('hidden', 'lp_encoding');
$items = learnpath::getCategoryFromCourseIntoSelect(api_get_course_int_id(), true);
$form->addSelect('category_id', get_lang('Category'), $items);

// Hide toc frame
$form->addElement(
    'checkbox',
    'hide_toc_frame',
    null,
    get_lang('Hide table of contents frame')
);

if ('true' === api_get_setting('allow_course_theme')) {
    $mycourselptheme = api_get_course_setting('allow_learning_path_theme');
    if (!empty($mycourselptheme) && -1 != $mycourselptheme && 1 == $mycourselptheme) {
        // LP theme picker
        $themeSelect = $form->addSelectTheme('lp_theme', get_lang('Graphical theme'));
        $form->applyFilter('lp_theme', 'trim');
        $s_theme = $learnPath->get_theme();
        $themeSelect->setSelected($s_theme); // default
    }
}

// Author
$form->addHtmlEditor(
    'lp_author',
    get_lang('Author'),
    false,
    false,
    ['ToolbarSet' => 'LearningPathAuthor', 'Width' => '100%', 'Height' => '200px']
);
$form->applyFilter('lp_author', 'html_filter');

// LP image
$label = get_lang('Add image');
if ($lp->getResourceNode()->hasResourceFile()) {
    $label = get_lang('Update Image');
    $imageUrl = $lpRepo->getResourceFileUrl($lp);
    $image = '<div class="flex gap-2 mb-2">
                <label class="control-label">'.get_lang('Image').'</label>
                <div class="w-20 h-20 rounded-xl overflow-hidden">
                    <img class="w-full h-full object-cover" src="'.$imageUrl.'" />
                </div>
            </div>';
    $form->addElement('html', $image);
    $form->addElement('checkbox', 'remove_picture', null, get_lang('Remove picture'));
}

$form->addFile('lp_preview_image', [$label, get_lang('Trainer picture will resize if needed')]);
$form->addRule(
    'lp_preview_image',
    get_lang('Only PNG, JPG or GIF images allowed'),
    'filetype',
    ['jpg', 'jpeg', 'png', 'gif']
);

// Search toggle (only if global search is enabled).
if ('true' === api_get_setting('search_enabled')) {
    $form->addElement(
        'checkbox',
        'search_index_enabled',
        null,
        get_lang('Include this learning path in the global search results')
    );

    // Default: enabled
    $defaults['search_index_enabled'] = 1;
}

$hideTableOfContents = (int) $lp->getHideTocFrame();
$defaults['lp_encoding'] = Security::remove_XSS($learnPath->encoding);
$defaults['lp_name'] = Security::remove_XSS($learnPath->get_name());
$defaults['lp_author'] = Security::remove_XSS($lp->getAuthor());
$defaults['hide_toc_frame'] = $hideTableOfContents;
$defaults['category_id'] = $learnPath->getCategoryId();
$defaults['accumulate_scorm_time'] = $learnPath->getAccumulateScormTime();

$expired_on = $learnPath->expired_on;
$published_on = $learnPath->published_on;

// Prerequisites
$learnPath->display_lp_prerequisites_list($form);

$form->addHtml(
    '<div class="mt-2 mb-4 text-sm text-gray-50">'.
    get_lang(
        'Selecting another learning path as a prerequisite will hide the current prerequisite until the one in prerequisite is fully completed (100%)'
    ).
    '</div>'
);

// Time Control
if (Tracking::minimumTimeAvailable(api_get_session_id(), api_get_course_int_id())) {
    $form->addText(
        'accumulate_work_time',
        [
            get_lang('Minimum time (minutes)'),
            get_lang('Minimum time (in minutes) a student must remain in the learning path to get access to the next one.'),
        ]
    );
    $defaults['accumulate_work_time'] = $lp->getAccumulateWorkTime();
}

if ('true' === api_get_setting('lp.lp_enable_flow')) {
    $lpTable = Database::get_course_table(TABLE_LP_MAIN);
    $resourceNodeTable = 'resource_node';

    $currentLpId = (int) $lp->getIid();

    $sql = "
        SELECT DISTINCT candidate_lp.iid, candidate_lp.title
        FROM $lpTable current_lp
        INNER JOIN $resourceNodeTable current_rn
            ON current_rn.id = current_lp.resource_node_id
        INNER JOIN $resourceNodeTable candidate_rn
            ON candidate_rn.parent_id = current_rn.parent_id
        INNER JOIN $lpTable candidate_lp
            ON candidate_lp.resource_node_id = candidate_rn.id
        WHERE current_lp.iid = $currentLpId
            AND candidate_lp.iid <> $currentLpId
        ORDER BY candidate_lp.title ASC
    ";

    $result = Database::query($sql);
    $nextLpOptions = [0 => get_lang('None')];

    while ($row = Database::fetch_assoc($result)) {
        $nextLpOptions[(int) $row['iid']] = $row['title'];
    }

    if (count($nextLpOptions) > 1) {
        $selectedNextLpId = (int) $lp->getNextLpId();

        $nextLpHtml = '
    <div class="my-4">
        <div class="mb-2 text-sm font-semibold text-gray-90">'.
            get_lang('Next learning path').'
        </div>
        <div class="mb-2 text-sm text-gray-600">'.
            get_lang('Select the learning path that will be available after this one.').'
        </div>
        <div class="space-y-2">
';

        foreach ($nextLpOptions as $nextLpId => $nextLpTitle) {
            $nextLpId = (int) $nextLpId;
            $checked = $selectedNextLpId === $nextLpId ? ' checked="checked"' : '';

            $nextLpHtml .= '
        <label class="flex items-center gap-2 rounded-lg border border-gray-25 p-2 text-sm">
            <input type="radio" name="next_lp_id" value="'.$nextLpId.'"'.$checked.'>
            <span>'.Security::remove_XSS((string) $nextLpTitle).'</span>
        </label>
    ';
        }

        $nextLpHtml .= '
        </div>
    </div>
';

        $form->addHtml($nextLpHtml);
        $defaults['next_lp_id'] = $selectedNextLpId;
    } else {
        $form->addHtml(
            '<div class="my-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800">'.
            get_lang('Create another learning path in this course to enable learning path flow.').
            '</div>'
        );

        $defaults['next_lp_id'] = 0;
    }
}

// Start date
$form->addElement(
    'checkbox',
    'activate_start_date_check',
    null,
    get_lang('Enable start time'),
    ['onclick' => 'activate_start_date()']
);

$display_date = 'none';
if (!empty($published_on) && '0000-00-00 00:00:00' !== $published_on) {
    $display_date = 'block';
    $defaults['activate_start_date_check'] = 1;
}

$form->addElement('html', '<div id="start_date_div" style="display:'.$display_date.';">');
$form->addDateTimePicker('published_on', get_lang('Publication date'));
$form->addElement('html', '</div>');

// End date
$form->addCheckBox(
    'activate_end_date_check',
    null,
    get_lang('Enable end time'),
    ['onclick' => 'activate_end_date()']
);
$display_date = 'none';
if (!empty($expired_on)) {
    $display_date = 'block';
    $defaults['activate_end_date_check'] = 1;
}

$form->addElement('html', '<div id="end_date_div" style="display:'.$display_date.';">');
$form->addDateTimePicker('expired_on', get_lang('Expiration date'));
$form->addElement('html', '</div>');

if (api_is_platform_admin()) {
    $form->addElement('checkbox', 'use_max_score', null, get_lang('Use default maximum score of 100'));
    $defaults['use_max_score'] = $learnPath->use_max_score;
}

$subscriptionSettings = learnpath::getSubscriptionSettings();
if ($subscriptionSettings['allow_add_users_to_lp']) {
    $form->addElement(
        'checkbox',
        'subscribe_users',
        null,
        get_lang('Subscribe users to learning path')
    );
}

// accumulate_scorm_time
$form->addElement(
    'checkbox',
    'accumulate_scorm_time',
    [
        null,
        get_lang('When enabled, the session time for SCORM Learning Paths will be cumulative, otherwise, it will only be counted from the last update time.'),
    ],
    get_lang('Accumulate SCORM session time')
);

$scoreAsProgressSetting = ('true' === api_get_setting('lp.lp_score_as_progress_enable'));
$countItems = $learnPath->get_total_items_count();
$lpType = $learnPath->get_type();
// This option is only usable for SCORM, if there is only 1 item, otherwise
// using the score as progress would not work anymore (we would have to divide
// between the two without knowing if the second has any score at all)
// TODO: automatically cancel this setting if items >= 2
if ($scoreAsProgressSetting && $countItems < 2 && 2 == $lpType) {
    $scoreAsProgress = $learnPath->getUseScoreAsProgress();
    $form->addElement(
        'checkbox',
        'extra_use_score_as_progress',
        [null, get_lang('Use the score returned, by the only SCO in this learning path, as the progress indicator in the progress bar. This modifies the SCORM behaviour in the strict sense, but improves visual feedback to the learner.')],
        get_lang('Use score as progress')
    );
    $defaults['extra_use_score_as_progress'] = $scoreAsProgress;
}

$options = learnpath::getIconSelect();

if (!empty($options)) {
    $form->addSelect(
        'extra_lp_icon',
        get_lang('Icon'),
        $options
    );
    $defaults['extra_lp_icon'] = learnpath::getSelectedIcon($lpId);
}

$extraField = new ExtraField('lp');
$extra = $extraField->addElements(
    $form,
    $lpId,
    ['lp_icon', 'use_score_as_progress']
);

$skillList = SkillModel::addSkillsToForm($form, ITEM_TYPE_LEARNPATH, $lpId);

// Submit button
$form->addButtonSave(get_lang('Save course settings'));

// Hidden fields
$form->addHidden('action', 'edit');
$form->addHidden('lp_id', $lpId);

$htmlHeadXtra[] = '<script>
$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array().'</script>';

$defaults['published_on'] = !empty($published_on) && '0000-00-00 00:00:00' !== $published_on
    ? api_get_local_time($published_on)
    : null;
$defaults['expired_on'] = (!empty($expired_on))
    ? api_get_local_time($expired_on)
    : date('Y-m-d 12:00:00', time() + 84600);
$defaults['subscribe_users'] = $learnPath->getSubscribeUsers();
$defaults['skills'] = array_keys($skillList);
$form->setDefaults($defaults);

if ($form->validate()) {
    $em = Database::getManager();
    $hide_toc_frame = 1 === $request->request->getInt('hide_toc_frame');

    $published_on = null;
    if (1 === $request->request->getInt('activate_start_date_check')) {
        $published_on = $request->request->get('published_on');
    }

    $expired_on = null;
    if (1 === $request->request->getInt('activate_end_date_check')) {
        $expired_on = $request->request->get('expired_on');
    }

    if ($request->request->get('remove_picture')) {
        $resourceFiles = $lp->getResourceNode()->getResourceFiles();

        foreach ($resourceFiles as $resourceFile) {
            $em->remove($resourceFile);
            $em->flush();
        }
    }

    $lpCategoryRepo = Container::getLpCategoryRepository();
    $category = null;
    $categoryId = $request->request->getInt('category_id');
    if ($categoryId) {
        $category = $lpCategoryRepo->find($categoryId);
    }

    $nextLpId = 0;

    if ('true' === api_get_setting('lp.lp_enable_flow')) {
        $candidateNextLpId = max(0, $request->request->getInt('next_lp_id'));

        if (learnpath::isValidFlowNextLp((int) $lp->getIid(), $candidateNextLpId)) {
            $nextLpId = $candidateNextLpId;
        }
    }

    $lp
        ->setTitle($request->request->get('lp_name'))
        ->setAuthor($request->request->get('lp_author', ''))
        ->setTheme($request->request->get('lp_theme', ''))
        ->setHideTocFrame($hide_toc_frame)
        ->setPrerequisite($request->request->getInt('prerequisites'))
        ->setAccumulateWorkTime($request->request->getInt('accumulate_work_time'))
        ->setNextLpId($nextLpId)
        ->setContentMaker($request->request->get('lp_maker', ''))
        ->setContentLocal($request->request->get('lp_proximity', ''))
        ->setUseMaxScore((int) (null !== $request->request->get('use_max_score')))
        ->setDefaultEncoding($request->request->get('lp_encoding'))
        ->setAccumulateScormTime((int) (null !== $request->request->get('accumulate_scorm_time')))
        ->setPublishedOn(api_get_utc_datetime($published_on, true, true))
        ->setExpiredOn(api_get_utc_datetime($expired_on, true, true))
        ->setCategory($category)
        ->setSubscribeUsers((int) (null !== $request->request->get('subscribe_users')))
    ;

    $extraFieldValue = new ExtraFieldValue('lp');
    $requestData = array_merge($request->request->all(), ['item_id' => $lpId]);
    $extraFieldValue->saveFieldValues($requestData);

    if ($request->files->has('lp_preview_image')) {
        $file = $request->files->get('lp_preview_image');
        if (!empty($file)) {
            $lpRepo->addFile($lp, $file);
        }
    }

    $lpRepo->update($lp);

    // Optional: trigger Xapian index based on checkbox value
    if ('true' === api_get_setting('search_enabled')) {
        try {
            /** @var LpXapianIndexer $lpIndexer */
            $lpIndexer = Container::$container->get('chamilo_core.search.lp_xapian_indexer');

            if ($request->request->get('search_index_enabled')) {
                $lpIndexer->indexLp($lp);
            } else {
                $lpIndexer->deleteLpIndex($lp);
            }
        } catch (Throwable $e) {
            // Best-effort: do not break form save if search service fails
        }
    }

    $form = new FormValidator('form1');
    $form->addSelect('skills', 'skills');
    SkillModel::saveSkills($form, ITEM_TYPE_LEARNPATH, $lpId);

    Display::addFlash(Display::return_message(get_lang('Update successful')));
    $url = api_get_self().'?action=add_item&type=step&lp_id='.$lpId.'&'.api_get_cidreq();
    header('Location: '.$url);

    exit;
}

Display::display_header(get_lang('Course settings'), 'Path');

echo $learnPath->build_action_menu(false, false, true, false);
echo '<div class="row">';
echo '<div class="'.($hideTableOfContents ? 'col-md-12' : 'col-md-8').'" id="pnl-frm">';
$form->display();
echo '</div>';
echo '<div class="'.($hideTableOfContents ? 'hide' : 'col-md-4').' text-right" id="pnl-toc">';
echo Display::return_icon('course_setting_layout.png');
echo '</div>';
echo '</div>';
echo "
<script>
    $(function() {
        $('[name=\\'hide_toc_frame\\']').on('change', function() {
            $('#pnl-frm').toggleClass('col-md-8').toggleClass('col-sm-12');
            $('#pnl-toc').toggleClass('col-md-4').toggleClass('hide');
        });
    });
</script>
";
Display::display_footer();

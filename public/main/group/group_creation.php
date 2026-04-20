<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;

api_protect_course_script(true);

if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

/**
 * Escape HTML output.
 */
function group_creation_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Render HTML options for a select field.
 */
function group_creation_render_options(array $options, ?int $selected = null): string
{
    $html = '';

    foreach ($options as $value => $label) {
        $isSelected = null !== $selected && (int) $value === $selected ? ' selected' : '';
        $html .= '<option value="'.(int) $value.'"'.$isSelected.'>'.group_creation_escape($label).'</option>';
    }

    return $html;
}

$currentUrl = api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq();
$allowGroupCategories = 'true' === api_get_setting('allow_group_categories');

$categories = [];
$numberOfGroups = GroupManager::get_number_of_groups() + 1;

if ($allowGroupCategories) {
    $groupCategories = GroupManager::get_categories();
    foreach ($groupCategories as $category) {
        $categories[(int) $category['iid']] = $category['title'];
    }
}

/* Create the groups */
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_groups':
            $groups = [];
            $useOnlyFirstCategory = false;
            $firstCategory = isset($_POST['group_0_category']) ? (int) $_POST['group_0_category'] : 0;

            if (!empty($_POST['same_category'])) {
                $useOnlyFirstCategory = true;
            }

            $totalGroups = isset($_POST['number_of_groups']) ? (int) $_POST['number_of_groups'] : 0;

            for ($i = 0; $i < $totalGroups; $i++) {
                $group = [];
                $group['name'] = empty($_POST['group_'.$i.'_name'])
                    ? get_lang('Group').' '.$i
                    : trim((string) $_POST['group_'.$i.'_name']);
                $group['category'] = isset($_POST['group_'.$i.'_category']) ? (int) $_POST['group_'.$i.'_category'] : null;

                if ($useOnlyFirstCategory) {
                    $group['category'] = $firstCategory;
                }

                $group['tutor'] = isset($_POST['group_'.$i.'_tutor']) ? $_POST['group_'.$i.'_tutor'] : null;
                $group['places'] = isset($_POST['group_'.$i.'_places']) && '' !== trim((string) $_POST['group_'.$i.'_places'])
                    ? (int) $_POST['group_'.$i.'_places']
                    : null;

                if (!empty($_POST['same_places']) && isset($_POST['group_0_places']) && '' !== trim((string) $_POST['group_0_places'])) {
                    $group['places'] = (int) $_POST['group_0_places'];
                }

                $groups[] = $group;
            }

            foreach ($groups as $group) {
                GroupManager::create_group(
                    $group['name'],
                    $group['category'],
                    $group['tutor'],
                    $group['places']
                );
            }

            Display::addFlash(Display::return_message(get_lang('group(s) has (have) been added')));
            header('Location: '.$currentUrl);
            exit;

        case 'create_subgroups':
            GroupManager::create_subgroups(
                $_POST['base_group'],
                $_POST['number_of_groups']
            );
            Display::addFlash(Display::return_message(get_lang('group(s) has (have) been added')));
            header('Location: '.$currentUrl);
            exit;

        case 'create_class_groups':
            $classIds = [];
            foreach (array_keys($_POST) as $key) {
                if (str_starts_with($key, 'checkbox_class_id_')) {
                    $classId = str_replace('checkbox_class_id_', '', $key);
                    $classIds[] = (int) $classId;
                }
            }

            if (isset($_POST['is_consistent_link'])) {
                GroupManager::create_usergroup_consistent_groups((int) $_POST['group_category'], $classIds);
            } else {
                GroupManager::create_class_groups((int) $_POST['group_category'], $classIds);
            }

            Display::addFlash(Display::return_message(get_lang('group(s) has (have) been added')));
            header('Location: '.$currentUrl);
            exit;
    }
}

$nameTools = get_lang('New groups creation');

if ($allowGroupCategories && isset($_GET['category_id']) && isset($categories[(int) $_GET['category_id']])) {
    $nameTools = sprintf(
        get_lang('New groups creation in category %s'),
        $categories[(int) $_GET['category_id']]
    );
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
    'name' => get_lang('Groups'),
];

Display::display_header($nameTools, 'Group');

$containerClass = 'mx-auto w-full px-4 sm:px-6 lg:px-8';
$cardClass = 'rounded-lg border border-gray-25 bg-white p-6 shadow-sm';
$inputClass = 'mt-1 block w-full rounded-md border-gray-25 bg-white text-gray-90 shadow-sm focus:border-primary focus:ring-primary';
$checkboxClass = 'h-4 w-4 cursor-pointer';
$primaryButtonClass = 'inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90';
$secondaryButtonClass = 'inline-flex items-center justify-center rounded-md border border-gray-25 bg-white px-4 py-2 text-sm font-medium text-gray-90 shadow-sm transition hover:bg-gray-15';

$hasFixedCategory = $allowGroupCategories && isset($_GET['category_id']) && isset($categories[(int) $_GET['category_id']]);
$fixedCategoryId = $hasFixedCategory ? (int) $_GET['category_id'] : null;
$baseCreationUrl = api_get_self().'?'.api_get_cidreq().($hasFixedCategory ? '&category_id='.$fixedCategoryId : '');

if (isset($_POST['number_of_groups'])) {
    $requestedGroups = (int) $_POST['number_of_groups'];

    if (!is_numeric($_POST['number_of_groups']) || $requestedGroups < 1) {
        echo '<div class="'.$containerClass.'">';
        echo '<div class="'.$cardClass.'">';
        echo '<div class="rounded-md border border-danger bg-white p-4 text-sm text-gray-90">';
        echo group_creation_escape(get_lang('Please enter the desired number of groups'));
        echo '</div>';
        echo '<div class="mt-6">';
        echo '<a class="'.$secondaryButtonClass.'" href="group_creation.php?'.group_creation_escape(api_get_cidreq()).'">';
        echo '&laquo; '.group_creation_escape(get_lang('Back'));
        echo '</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="'.$containerClass.' space-y-6">';
        echo '<div>';
        echo '<h1 class="text-2xl font-semibold text-gray-90">'.group_creation_escape($nameTools).'</h1>';
        echo '<p class="mt-1 text-sm text-gray-50">'.group_creation_escape(get_lang('Define the properties for each group before creating them.')).'</p>';
        echo '</div>';

        echo '<form method="post" action="'.group_creation_escape($baseCreationUrl).'" class="space-y-6">';
        echo '<input type="hidden" name="action" value="create_groups">';
        echo '<input type="hidden" name="number_of_groups" value="'.$requestedGroups.'">';

        if ($requestedGroups > 1) {
            echo '<div class="'.$cardClass.'">';
            echo '<h2 class="text-base font-semibold text-gray-90">'.group_creation_escape(get_lang('Apply the first row values')).'</h2>';
            echo '<div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">';

            if ($allowGroupCategories && !$hasFixedCategory) {
                echo '<label class="flex items-center gap-3 text-sm text-gray-90 cursor-pointer">';
                echo '<input class="'.$checkboxClass.'" type="checkbox" id="same_category" name="same_category" value="1">';
                echo '<span>'.group_creation_escape(get_lang('same for all')).' - '.group_creation_escape(get_lang('Group category')).'</span>';
                echo '</label>';
            }

            echo '<label class="flex items-center gap-3 text-sm text-gray-90 cursor-pointer">';
            echo '<input class="'.$checkboxClass.'" type="checkbox" id="same_places" name="same_places" value="1">';
            echo '<span>'.group_creation_escape(get_lang('same for all')).' - '.group_creation_escape(get_lang('seats (optional)')).'</span>';
            echo '</label>';

            echo '</div>';
            echo '</div>';
        }

        echo '<div class="'.$cardClass.'">';
        echo '<div class="overflow-x-auto">';
        echo '<table class="min-w-full divide-y divide-gray-25">';
        echo '<thead class="bg-gray-15">';
        echo '<tr>';
        echo '<th class="px-4 py-3 text-left text-sm font-semibold text-gray-90">'.group_creation_escape(get_lang('Group name')).'</th>';

        if ($allowGroupCategories) {
            echo '<th class="px-4 py-3 text-left text-sm font-semibold text-gray-90">'.group_creation_escape(get_lang('Group category')).'</th>';
        }

        echo '<th class="px-4 py-3 text-left text-sm font-semibold text-gray-90">'.group_creation_escape(get_lang('seats (optional)')).'</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody class="divide-y divide-gray-25 bg-white">';

        for ($groupNumber = 0; $groupNumber < $requestedGroups; $groupNumber++) {
            $prefix = '';

            if ($requestedGroups < 100 && $numberOfGroups < 10) {
                $prefix = '0';
            }

            $defaultName = get_lang('Group').' '.$prefix.$numberOfGroups++;
            echo '<tr class="align-top">';
            echo '<td class="px-4 py-4">';
            echo '<input class="'.$inputClass.'" type="text" name="group_'.$groupNumber.'_name" value="'.group_creation_escape($defaultName).'">';
            echo '</td>';

            if ($allowGroupCategories) {
                echo '<td class="px-4 py-4">';

                if ($hasFixedCategory && null !== $fixedCategoryId) {
                    echo '<div class="rounded-md bg-gray-15 px-3 py-2 text-sm text-gray-90">';
                    echo group_creation_escape($categories[$fixedCategoryId]);
                    echo '</div>';
                    echo '<input type="hidden" name="group_'.$groupNumber.'_category" value="'.$fixedCategoryId.'">';
                } else {
                    echo '<select class="'.$inputClass.'" name="group_'.$groupNumber.'_category" id="category_'.$groupNumber.'">';
                    echo group_creation_render_options($categories);
                    echo '</select>';
                }

                echo '</td>';
            } else {
                echo '<input type="hidden" name="group_'.$groupNumber.'_category" value="0">';
            }

            echo '<td class="px-4 py-4">';
            echo '<input class="'.$inputClass.'" type="number" min="0" name="group_'.$groupNumber.'_places" id="places_'.$groupNumber.'">';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        echo '<div class="mt-6 flex justify-end">';
        echo '<button type="submit" class="'.$primaryButtonClass.'">'.group_creation_escape(get_lang('Create group(s)')).'</button>';
        echo '</div>';

        echo '</div>';
        echo '</form>';
        echo '</div>';

        if ($requestedGroups > 1) {
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const numberOfGroups = <?php echo $requestedGroups; ?>;
                    const sameCategory = document.getElementById('same_category');
                    const samePlaces = document.getElementById('same_places');

                    function syncField(key, checked) {
                        const source = document.getElementById(key + '_0');
                        if (!source) {
                            return;
                        }

                        for (let i = 1; i < numberOfGroups; i++) {
                            const target = document.getElementById(key + '_' + i);
                            if (!target) {
                                continue;
                            }

                            if (checked) {
                                target.value = source.value;
                                target.disabled = true;
                                target.classList.add('bg-gray-15');
                            } else {
                                target.disabled = false;
                                target.classList.remove('bg-gray-15');
                            }
                        }
                    }

                    if (sameCategory) {
                        const categorySource = document.getElementById('category_0');

                        sameCategory.addEventListener('change', function () {
                            syncField('category', sameCategory.checked);
                        });

                        if (categorySource) {
                            categorySource.addEventListener('change', function () {
                                if (sameCategory.checked) {
                                    syncField('category', true);
                                }
                            });
                        }
                    }

                    if (samePlaces) {
                        const placesSource = document.getElementById('places_0');

                        samePlaces.addEventListener('change', function () {
                            syncField('places', samePlaces.checked);
                        });

                        if (placesSource) {
                            placesSource.addEventListener('input', function () {
                                if (samePlaces.checked) {
                                    syncField('places', true);
                                }
                            });
                        }
                    }
                });
            </script>
            <?php
        }
    }
} else {
    echo '<div class="'.$containerClass.' space-y-6">';
    echo '<div>';
    echo '<h1 class="text-2xl font-semibold text-gray-90">'.group_creation_escape($nameTools).'</h1>';
    echo '<p class="mt-1 text-sm text-gray-50">'.group_creation_escape(get_lang('Create groups manually or generate them from the classes subscribed to this course.')).'</p>';
    echo '</div>';

    echo '<div class="'.$cardClass.'">';
    echo '<h2 class="text-base font-semibold text-gray-90">'.group_creation_escape($nameTools).'</h2>';
    echo '<form method="post" action="'.group_creation_escape($baseCreationUrl).'" class="mt-6 space-y-6">';
    echo '<div>';
    echo '<label class="block text-sm font-medium text-gray-90" for="number_of_groups">'.group_creation_escape(get_lang('Number of groups to create')).'</label>';
    echo '<input class="'.$inputClass.'" type="number" min="1" id="number_of_groups" name="number_of_groups" value="1">';
    echo '</div>';
    echo '<div class="flex justify-end">';
    echo '<button type="submit" class="'.$primaryButtonClass.'">'.group_creation_escape(get_lang('Proceed to create group(s)')).'</button>';
    echo '</div>';
    echo '</form>';
    echo '</div>';

    /*
     * Show form to generate subgroups
     */
    if ($allowGroupCategories) {
        $groups = GroupManager::get_group_list();
        if (!empty($groups)) {
            $baseGroupOptions = [];
            foreach ($groups as $group) {
                $groupId = $group['iid'];
                $numberOfStudents = GroupManager::number_of_students($groupId);
                if ($numberOfStudents > 0) {
                    $baseGroupOptions[$groupId] = $group['name'].' ('.$numberOfStudents.' '.get_lang('Users').')';
                }
            }
        }
    }

    /*
     * Show form to generate groups from classes subscribed to the course
     */
    $options['where'] = [' usergroup.course_id = ? ' => api_get_course_int_id()];
    $obj = new UserGroupModel();
    $classes = $obj->getUserGroupInCourse($options);

    if (count($classes) > 0) {
        echo '<div class="'.$cardClass.'">';
        echo '<h2 class="text-base font-semibold text-gray-90">'.group_creation_escape(get_lang('Groups from classes')).'</h2>';
        echo '<p class="mt-2 text-sm text-gray-50">'.group_creation_escape(get_lang('Using this option, you can create groups based on the classes subscribed to your course.')).'</p>';

        echo '<form method="post" action="'.group_creation_escape(api_get_self().'?'.api_get_cidreq()).'" class="mt-6 space-y-6">';
        echo '<input type="hidden" name="action" value="create_class_groups">';

        echo '<div class="grid grid-cols-1 gap-3 md:grid-cols-2">';
        foreach ($classes as $class) {
            $numberOfUsers = count($obj->get_users_by_usergroup($class['id']));
            $checkboxName = 'checkbox_class_id_'.$class['id'];

            echo '<label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-25 bg-gray-10 p-4 text-sm text-gray-90">';
            echo '<input class="'.$checkboxClass.' mt-0.5" type="checkbox" name="'.group_creation_escape($checkboxName).'" value="1">';
            echo '<span>'.group_creation_escape($class['title'].' ('.$numberOfUsers.' '.get_lang('Users').')').'</span>';
            echo '</label>';
        }
        echo '</div>';

        if ($allowGroupCategories) {
            echo '<div>';

            if ($hasFixedCategory && null !== $fixedCategoryId) {
                echo '<label class="block text-sm font-medium text-gray-90">'.group_creation_escape(get_lang('Group category')).'</label>';
                echo '<div class="mt-1 rounded-md bg-gray-15 px-3 py-2 text-sm text-gray-90">';
                echo group_creation_escape($categories[$fixedCategoryId]);
                echo '</div>';
                echo '<input type="hidden" name="group_category" value="'.$fixedCategoryId.'">';
            } else {
                echo '<label class="block text-sm font-medium text-gray-90" for="group_category">'.group_creation_escape(get_lang('Group category')).'</label>';
                echo '<select class="'.$inputClass.'" id="group_category" name="group_category">';
                echo group_creation_render_options($categories);
                echo '</select>';
            }

            echo '</div>';
        } else {
            echo '<input type="hidden" name="group_category" value="0">';
        }

        echo '<div class="h-px w-full rounded bg-gray-25"></div>';

        echo '<label class="flex cursor-pointer items-start gap-3 text-sm text-gray-90">';
        echo '<input class="'.$checkboxClass.' mt-0.5" type="checkbox" name="is_consistent_link" value="1">';
        echo '<span>'.group_creation_escape(get_lang('Link classes to created groups ?')).'</span>';
        echo '</label>';

        echo '<div class="rounded-md border border-info bg-support-2 p-4 text-sm text-gray-90">';
        echo group_creation_escape(get_lang('If a class is linked to a group, users added to or removed from the class are automatically added to or removed from the group.'));
        echo '</div>';

        echo '<div class="flex justify-end">';
        echo '<button type="submit" class="'.$primaryButtonClass.'">'.group_creation_escape(get_lang('Validate')).'</button>';
        echo '</div>';

        echo '</form>';
        echo '</div>';
    }

    echo '</div>';
}

Display::display_footer();

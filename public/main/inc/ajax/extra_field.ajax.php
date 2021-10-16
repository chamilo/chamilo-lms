<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : '';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
$fieldId = isset($_REQUEST['field_id']) ? (int) $_REQUEST['field_id'] : 0;

switch ($action) {
    case 'delete_file':
        api_protect_admin_script();

        $itemId = $_REQUEST['item_id'] ?? null;
        $extraFieldValue = new ExtraFieldValue($type);
        $data = $extraFieldValue->get_values_by_handler_and_field_id($itemId, $fieldId);
        if (!empty($data) && isset($data['id']) && !empty($data['value'])) {
            $extraFieldValue->deleteValuesByHandlerAndFieldAndValue($itemId, $data['field_id'], $data['value']);
            echo 1;
            break;
        }
        echo 0;
        break;
    case 'get_second_select_options':
        $option_value_id = isset($_REQUEST['option_value_id']) ? $_REQUEST['option_value_id'] : null;
        if (!empty($type) && !empty($fieldId) && !empty($option_value_id)) {
            $field_options = new ExtraFieldOption($type);
            echo $field_options->get_second_select_field_options_by_field(
                $option_value_id,
                true
            );
        }
        break;
    case 'search_tags':
        header('Content-Type: application/json');
        $tag = isset($_REQUEST['q']) ? (string) $_REQUEST['q'] : '';

        $extraFieldRepo = Container::getExtraFieldRepository();
        $field = $extraFieldRepo->find($fieldId);

        if (empty($tag || null === $field)) {
            echo json_encode(['items' => []]);
            exit;
        }

        $tagRepo = Container::getTagRepository();
        $tags = $tagRepo->findTagsByField($tag, $field);
        $result = [];
        foreach ($tags as $tag) {
            $result[] = [
                'id' => $tag->getTag(),
                'text' => $tag->getTag(),
            ];
        }

        echo json_encode(['items' => $result]);
        break;
    case 'search_options_from_tags':
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
        $fieldId = isset($_REQUEST['field_id']) ? $_REQUEST['field_id'] : null;
        $tag = isset($_REQUEST['tag']) ? $_REQUEST['tag'] : null;
        $extraFieldOption = new ExtraFieldOption($type);

        $from = isset($_REQUEST['from']) ? $_REQUEST['from'] : '';
        $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
        $options = isset($_REQUEST['options']) ? json_decode($_REQUEST['options']) : '';

        $extraField = new ExtraField('session');
        $result = $extraField->searchOptionsFromTags($from, $search, $options);
        $options = [];
        $groups = [];

        foreach ($result as $data) {
            // Try to get the translation
            $displayText = $data['display_text'];
            $valueToTranslate = str_replace('-', '', $data['value']);
            $valueTranslated = str_replace(['[=', '=]'], '', get_lang($valueToTranslate));
            if ($valueToTranslate != $valueTranslated) {
                $displayText = $valueTranslated;
            }
            $groups[$displayText][] = [
                'id' => $data['id'],
                'text' => $data['tag'],
            ];
        }

        foreach ($groups as $key => $data) {
            $options[] = [
                'text' => $key,
                'children' => $groups[$key],
            ];
        }
        echo json_encode($options);
        break;
    case 'order':
        $variable = isset($_REQUEST['field_variable']) ? $_REQUEST['field_variable'] : '';
        $save = isset($_REQUEST['save']) ? $_REQUEST['save'] : '';
        $values = isset($_REQUEST['values']) ? json_decode($_REQUEST['values']) : '';
        $extraField = new ExtraField('session');
        $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable(str_replace('extra_', '', $variable));

        $em = Database::getManager();

        $search = [
            'user' => api_get_user_id(),
            'field' => $extraFieldInfo['id'],
        ];

        $extraFieldSavedSearch = $em->getRepository(ExtraFieldSavedSearch::class)->findOneBy($search);

        if ($save) {
            $extraField = new ExtraFieldSavedSearch('session');
            if ($extraFieldSavedSearch) {
                $extraFieldSavedSearch->setValue($values);
                $em->persist($extraFieldSavedSearch);
                $em->flush();
            }
        }

        if ($extraFieldInfo) {
            /** @var ExtraFieldSavedSearch $extraFieldSavedSearch */
            $extraFieldSavedSearch = $em->getRepository(ExtraFieldSavedSearch::class)->findOneBy($search);
            $values = $extraFieldSavedSearch->getValue();
            $url = api_get_self().'?a=order&save=1&field_variable='.$variable;

            $html = '
            <script>
                $(function() {
                    $( "#sortable" ).sortable();
                    $( "#sortable" ).disableSelection();

                    $( "#link_'.$variable.'" ).on("click", function() {
                        var newList = [];
                        $("#sortable").find("li").each(function(){
                            newList.push($(this).text());
                        });

                        var save = JSON.stringify(newList);
                        $.ajax({
                            url: "'.$url.'",
                            dataType: "json",
                            data: "values="+save,
                            success: function(data) {
                            }
                        });

                        alert("'.get_lang('Saved.').'");

                        location.reload();

                        return false;

                    });
                });
            </script>';

            $html .= '<ul id="sortable">';
            foreach ($values as $value) {
                $html .= '<li class="ui-state-default">';
                $html .= $value;
                $html .= '</li>';
            }
            $html .= '</ul>';
            $html .= Display::url(get_lang('Save'), '#', ['id' => 'link_'.$variable, 'class' => 'btn btn-primary']);
            echo $html;
        }
        break;
    default:
        exit;
        break;
}
exit;

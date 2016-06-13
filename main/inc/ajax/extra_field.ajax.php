<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Tag;

require_once '../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : '';

switch ($action) {
    case 'get_second_select_options':
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
        $field_id = isset($_REQUEST['field_id']) ? $_REQUEST['field_id'] : null;
        $option_value_id = isset($_REQUEST['option_value_id']) ? $_REQUEST['option_value_id'] : null;

        if (!empty($type) && !empty($field_id) && !empty($option_value_id)) {
            $field_options = new ExtraFieldOption($type);
            echo $field_options->get_second_select_field_options_by_field(
                $field_id,
                $option_value_id,
                true
            );
        }
        break;
    case 'search_tags':
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
        $fieldId = isset($_REQUEST['field_id']) ? $_REQUEST['field_id'] : null;
        $tag = isset($_REQUEST['tag']) ? $_REQUEST['tag'] : null;
        $extraFieldOption = new ExtraFieldOption($type);

        $result = [];
        $tags = Database::getManager()
            ->getRepository('ChamiloCoreBundle:Tag')
            ->createQueryBuilder('t')
            ->where("t.tag LIKE :tag")
            ->andWhere('t.fieldId = :field')
            ->setParameter('field', $fieldId)
            ->setParameter('tag', "$tag%")
            ->getQuery()
            ->getResult();

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $result[] = [
                'key' => $tag->getTag(),
                'value' => $tag->getTag()
            ];
        }

        echo json_encode($result);
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
        $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable(str_replace('extra_', '', $from));
        $id = $extraFieldInfo['id'];

        $extraFieldInfoTag = $extraField->get_handler_field_info_by_field_variable(str_replace('extra_', '', $search));
        $tagId = $extraFieldInfoTag['id'];

        $table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $tagRelExtraTable = Database::get_main_table(TABLE_MAIN_EXTRA_FIELD_REL_TAG);
        $tagTable = Database::get_main_table(TABLE_MAIN_TAG);
        $optionsTable = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);

        /*$sql = "SELECT DISTINCT t.* FROM $tagRelExtraTable te INNER JOIN $tagTable t
                ON (t.id = te.tag_id)
                WHERE te.field_id = $tagId AND te.item_id IN (
                    SELECT DISTINCT item_id
                    FROM $table
                    WHERE
                        field_id = $id AND
                        value IN ('".implode("','", $options)."')
               )
               ";*/

        $sql = "SELECT DISTINCT t.*, v.value, o.display_text
                FROM $tagRelExtraTable te 
                INNER JOIN $tagTable t
                ON (t.id = te.tag_id AND te.field_id = t.field_id AND te.field_id = $tagId) 
                INNER JOIN $table v
                ON (te.item_id = v.item_id AND v.field_id = $id)
                INNER JOIN $optionsTable o
                ON (o.option_value = v.value)
                WHERE v.value IN ('".implode("','", $options)."')                           
                ORDER BY o.option_order, t.tag
               ";

        $result = Database::query($sql);
        $result = Database::store_result($result);
        $options = [];
        $groups = [];
        foreach ($result as $data) {
            $groups[$data['display_text']][] = [
                'id' => $data['id'],
                'text' => $data['tag']
            ];

        }
        foreach ($groups as $key => $data) {
            $options[] = [
                'text' => $key,
                'children' => $groups[$key]
            ];
        }
        echo json_encode($options);
        break;
    default:
        exit;
        break;
}
exit;

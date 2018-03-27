<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Tag;

require_once __DIR__.'/../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : '';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
$fieldId = isset($_REQUEST['field_id']) ? $_REQUEST['field_id'] : null;

switch ($action) {
    case 'delete_file':
        api_protect_admin_script();

        $itemId = isset($_REQUEST['item_id']) ? $_REQUEST['item_id'] : null;
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
        $tag = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;
        $result = [];

        if (empty($tag)) {
            echo json_encode(['items' => $result]);
            exit;
        }

        $extraFieldOption = new ExtraFieldOption($type);

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
                'id' => $tag->getTag(),
                'text' => $tag->getTag(),
            ];
        }

        echo json_encode(['items' => $result]);
        break;
    default:
        exit;
        break;
}
exit;

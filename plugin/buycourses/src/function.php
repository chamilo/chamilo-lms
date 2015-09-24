<?php
/* For license terms, see /license.txt */
/**
 * Functions for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../config.php';

$itemTable = Database::get_main_table(BuyCoursesPlugin::TABLE_ITEM);

$plugin = BuyCoursesPlugin::create();
$currency = $plugin->getSelectedCurrency();

if ($_REQUEST['tab'] == 'save_mod') {
    if (isset($_REQUEST['course_id'])) {
        $productId = $_REQUEST['course_id'];
        $productType = BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
    } else {
        $productId = $_REQUEST['session_id'];
        $productType = BuyCoursesPlugin::PRODUCT_TYPE_SESSION;
    }

    $affectedRows = false;
    $item = $plugin->getItemByProduct($productId, $productType);

    if ($_POST['visible'] == 1) {
        if (!empty($item)) {
            $affectedRows = $plugin->updateItem(
                ['price' => floatval($_POST['price'])],
                $productId,
                $productType
            );
        } else {
            $affectedRows = $plugin->registerItem([
                'currency_id' => $currency['id'],
                'product_type' => $productType,
                'product_id' => intval($productId),
                'price' => floatval($_POST['price'])
            ]);
        }
    } else {
        $affectedRows = $plugin->deleteItem($item['id']);
    }

    if ($affectedRows > 0) {
        $jsonResult = [
            "status" => true,
            "itemId" => $productId
        ];
    } else {
        $jsonResult = [
            "status" => false,
            "content" => $plugin->get_lang('ItemNotSaved')
        ];
    }

    echo json_encode($jsonResult);
    exit;
}

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

$itemTable = Database::get_main_table(BuyCoursesUtils::TABLE_ITEM);

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

    if ($_POST['visible'] == 1) {
        $item = Database::select(
            'COUNT(1) AS qty',
            $itemTable,
            [
                'where' => [
                    'product_id = ? AND ' => intval($productId),
                    'product_type = ?' => $productType
                ]
            ],
            'first'
        );

        if ($item['qty'] > 0) {
            $affectedRows = Database::update(
                $itemTable,
                ['price' => floatval($_POST['price'])],
                [
                    'product_id = ? AND ' => intval($productId),
                    'product_type' => $productType
                ]
            );
        } else {
            $affectedRows = Database::insert(
                $itemTable,
                [
                    'currency_id' => $currency['id'],
                    'product_type' => $productType,
                    'product_id' => intval($productId),
                    'price' => floatval($_POST['price'])
                ]
            );
        }
    } else {
        $affectedRows = Database::delete(
            $itemTable,
            [
                'product_id = ? AND ' => intval($productId),
                'product_type = ?' => $productType
            ]
        );
    }

    if ($affectedRows > 0) {
        $jsonResult = [
            "status" => true,
            "itemId" => $productId
        ];
    } else {
        $jsonResult = [
            "status" => false,
            "content" => $plugin->get_lang('ProblemToSaveTheMessage')
        ];
    }

    echo json_encode($jsonResult);
    exit;
}

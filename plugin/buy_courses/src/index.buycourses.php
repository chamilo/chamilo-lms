<?php
/**
 * @package chamilo.plugin.themeselect
 */

$plugin = Buy_CoursesPlugin::create();
$guess_enable = $plugin->get('unregistered_users_enable');

if ($guess_enable == "true" || isset($_SESSION['_user'])) {
    $title = $plugin->get_lang('CourseListOnSale');

    echo '<div class="well sidebar-nav static">';
    echo '<h4>' . $title . '</h4>';
    echo '<ul class="nav nav-list">';
    echo '<li>';
    echo '<a href="src/list.php">' . $plugin->get_lang('BuyCourses') . '</a>';
    echo '</li>';
    if (api_is_platform_admin()) {
        echo '<li>';
        echo '<a href="src/configuration.php">' . $plugin->get_lang('ConfigurationOfCoursesAndPrices') . '</a>';
        echo '</li>';
        echo '<li>';
        echo '<a href="src/paymentsetup.php">' . $plugin->get_lang('ConfigurationOfPayments') . '</a>';
        echo '</li>';
        echo '<li>';
        echo '<a href="src/pending_orders.php">' . $plugin->get_lang('OrdersPendingOfPayment') . '</a>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
}
 
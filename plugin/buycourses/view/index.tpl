<div class="well sidebar-nav static normal-message">
    <h4> {{title}} </h4>
    <ul class="nav nav-list">
        <li>
            <a href="src/list.php"> {{ BuyCourses }} </a>
        </li>
        {% if isAdmin == 'true' %}
        <li>
            <a href="src/configuration.php"> {{ ConfigurationOfCoursesAndPrices }} </a>
        </li>
        <li>
            <a href="src/paymentsetup.php"> {{ ConfigurationOfPayments }} </a>
        </li>
        <li>
            <a href="src/pending_orders.php"> {{ OrdersPendingOfPayment }} </a>
        </li>
        {% endif %}
    </ul>
</div>

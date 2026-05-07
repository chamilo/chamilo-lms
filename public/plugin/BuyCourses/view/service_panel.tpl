{% autoescape false %}
<style>
    .bc-panel {width: 100%; max-width: 1500px; margin: 0 auto; padding: 28px 24px;}
    .bc-tabs {display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 28px; padding: 16px; border: 1px solid #e6edf3; border-radius: 24px; background: #fff; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);}
    .bc-tabs__links {display: flex; flex-wrap: wrap; gap: 10px;}
    .bc-tab {display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 10px 18px; border-radius: 16px; background: #f3f6f9; color: #1f2937; font-size: 14px; font-weight: 700; text-decoration: none; transition: all 0.15s ease;}
    .bc-tab:hover {background: #e8f1f8; color: #1f6fa9; text-decoration: none;}
    .bc-tab--active {background: #2f80b7; color: #fff; box-shadow: 0 4px 12px rgba(47, 128, 183, 0.24);}
    .bc-tab--active:hover {background: #2f80b7; color: #fff;}
    .bc-button {display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 10px 18px; border-radius: 16px; border: 1px solid transparent; font-size: 14px; font-weight: 700; text-decoration: none; transition: all 0.15s ease; white-space: nowrap;}
    .bc-button:hover {text-decoration: none; opacity: 0.9;}
    .bc-button--primary {background: #2f80b7; color: #fff;}
    .bc-button--info {background: #1677ff; color: #fff;}
    .bc-button--danger {background: #fff; border-color: #dc3545; color: #dc3545;}
    .bc-button--danger:hover {background: #dc3545; color: #fff;}
    .bc-button--plain {background: #fff; border-color: #dbe5ee; color: #1f2937;}
    .bc-section {margin-bottom: 28px; padding: 28px; border: 1px solid #e6edf3; border-radius: 28px; background: #fff; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);}
    .bc-section__header {margin-bottom: 22px;}
    .bc-section__title {margin: 0; color: #111827; font-size: 24px; font-weight: 800; line-height: 1.25;}
    .bc-section__description {margin: 6px 0 0; color: #8a96a8; font-size: 14px;}
    .bc-empty {padding: 20px; border: 1px dashed #dbe5ee; border-radius: 20px; background: #f7fafc; color: #8a96a8; font-size: 14px;}
    .bc-service-list {display: grid; gap: 18px;}
    .bc-service-card {display: grid; grid-template-columns: 240px minmax(0, 1fr); overflow: hidden; border: 1px solid #e6edf3; border-radius: 24px; background: #fff; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);}
    .bc-service-card__image-wrap {width: 240px; min-height: 100%; background: #f3f6f9; border-right: 1px solid #e6edf3;}
    .bc-service-card__image {display: block; width: 100%; height: 100%; min-height: 230px; object-fit: cover;}
    .bc-service-card__body {min-width: 0; padding: 22px;}
    .bc-service-card__top {display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;}
    .bc-badge {display: inline-flex; align-items: center; min-height: 26px; padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; line-height: 1.2;}
    .bc-badge--blue {background: #eef6fb; color: #1f6fa9;}
    .bc-badge--green {background: #edf8e8; color: #5b970f;}
    .bc-badge--info {background: #eef6ff; color: #1677ff;}
    .bc-service-card__title {margin: 0; color: #111827; font-size: 20px; font-weight: 800; line-height: 1.3;}
    .bc-service-card__description {max-width: 900px; margin: 6px 0 0; color: #8a96a8; font-size: 14px; line-height: 1.5;}
    .bc-service-card__reference {margin: 10px 0 0; color: #8a96a8; font-size: 14px;}
    .bc-service-card__reference strong {color: #111827;}
    .bc-meta-grid {display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 18px;}
    .bc-meta-box {min-width: 0; padding: 14px 16px; border: 1px solid #e6edf3; border-radius: 18px; background: #f7fafc;}
    .bc-meta-box__label {color: #1f6fa9; font-size: 11px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase;}
    .bc-meta-box__value {margin-top: 4px; color: #111827; font-size: 14px; font-weight: 700; line-height: 1.35;}
    .bc-benefits {margin-top: 18px;}
    .bc-benefits__title {margin: 0 0 10px; color: #111827; font-size: 14px; font-weight: 800;}
    .bc-benefits__grid {display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px;}
    .bc-benefit {padding: 12px 14px; border: 1px solid #dbeaf4; border-radius: 16px; background: #f1f7fb; color: #111827; font-size: 13px; line-height: 1.35;}
    .bc-recurring-box {display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-top: 18px; padding: 16px; border: 1px solid #e6edf3; border-radius: 18px; background: #f7fafc;}
    .bc-recurring-box__title {margin: 0; color: #111827; font-size: 14px; font-weight: 800;}
    .bc-recurring-box__status {margin: 4px 0 0; color: #8a96a8; font-size: 13px;}
    .bc-recurring-box__extra {margin: 4px 0 0; color: #1f6fa9; font-size: 12px; font-weight: 600;}
    .bc-actions {display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px;}
    .bc-table-wrap {overflow-x: auto;}
    .bc-table {width: 100%; border-collapse: collapse; font-size: 14px;}
    .bc-table th {padding: 14px 16px; background: #f7fafc; color: #8a96a8; font-weight: 800; text-align: left; white-space: nowrap;}
    .bc-table td {padding: 14px 16px; border-top: 1px solid #e6edf3; color: #111827; vertical-align: top;}
    .bc-table td.bc-muted {color: #8a96a8;}
    @media (max-width: 992px) {.bc-service-card {grid-template-columns: 1fr;} .bc-service-card__image-wrap {width: 100%; border-right: 0; border-bottom: 1px solid #e6edf3;} .bc-service-card__image {height: 220px; min-height: 220px;} .bc-benefits__grid {grid-template-columns: 1fr;} .bc-recurring-box {align-items: flex-start; flex-direction: column;}}
    @media (max-width: 640px) {.bc-panel {padding: 20px 12px;} .bc-section {padding: 20px; border-radius: 22px;} .bc-meta-grid {grid-template-columns: 1fr;} .bc-tabs {align-items: stretch;} .bc-tabs__links, .bc-tabs .bc-button {width: 100%;} .bc-tab, .bc-button {width: 100%;}}
</style>

<div class="bc-panel">
    <div class="bc-tabs">
        <div class="bc-tabs__links">
            <a href="course_panel.php" class="bc-tab">{{ 'MyCourses'|get_lang }}</a>
            {% if sessions_are_included %}<a href="session_panel.php" class="bc-tab">{{ 'MySessions'|get_lang }}</a>{% endif %}
            <a href="service_panel.php" class="bc-tab bc-tab--active">{{ 'MyServices'|get_plugin_lang('BuyCoursesPlugin') }}</a>
            <a href="payout_panel.php" class="bc-tab">{{ 'MyPayouts'|get_plugin_lang('BuyCoursesPlugin') }}</a>
        </div>
        <a href="service_catalog.php" class="bc-button bc-button--primary">{{ 'ListOfServicesOnSale'|get_plugin_lang('BuyCoursesPlugin') }}</a>
    </div>

    <section class="bc-section">
        <div class="bc-section__header">
            <h2 class="bc-section__title">{{ 'ActiveServices'|get_plugin_lang('BuyCoursesPlugin') }}</h2>
            <p class="bc-section__description">{{ 'ActiveServicesDescription'|get_plugin_lang('BuyCoursesPlugin') }}</p>
        </div>

        {% if active_service_list is empty %}
            <div class="bc-empty">{{ 'NoActiveServicesYet'|get_plugin_lang('BuyCoursesPlugin') }}</div>
        {% else %}
            <div class="bc-service-list">
                {% for sale in active_service_list %}
                    <article class="bc-service-card">
                        <div class="bc-service-card__image-wrap"><img src="{{ sale.image|e }}" alt="{{ sale.name|e }}" class="bc-service-card__image"></div>
                        <div class="bc-service-card__body">
                            <div class="bc-service-card__top">
                                <span class="bc-badge bc-badge--blue">{{ sale.service_type|e }}</span>
                                <span class="bc-badge bc-badge--green">{{ 'Active'|get_lang }}</span>
                                {% if sale.is_renewable %}<span class="bc-badge bc-badge--info">Recurring: {{ sale.recurring_status_label|e }}</span>{% endif %}
                            </div>
                            <h3 class="bc-service-card__title">{{ sale.name|e }}</h3>
                            {% if sale.description %}<p class="bc-service-card__description">{{ sale.description|striptags|e }}</p>{% endif %}
                            <p class="bc-service-card__reference">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}: <strong>{{ sale.reference|e }}</strong></p>
                            <div class="bc-meta-grid">
                                <div class="bc-meta-box"><div class="bc-meta-box__label">{{ 'StartDate'|get_lang }}</div><div class="bc-meta-box__value">{{ sale.date_start|e }}</div></div>
                                <div class="bc-meta-box"><div class="bc-meta-box__label">{{ 'EndDate'|get_lang }}</div><div class="bc-meta-box__value">{{ sale.date_end|e }}</div></div>
                            </div>
                            {% if sale.benefit_summaries is not empty %}
                                <div class="bc-benefits"><h4 class="bc-benefits__title">{{ 'GrantedBenefits'|get_plugin_lang('BuyCoursesPlugin') }}</h4><div class="bc-benefits__grid">{% for benefit in sale.benefit_summaries %}<div class="bc-benefit">{{ benefit|e }}</div>{% endfor %}</div></div>
                            {% endif %}
                            {% if sale.is_renewable %}
                                <div class="bc-recurring-box">
                                    <div>
                                        <p class="bc-recurring-box__title">Recurring payments</p>
                                        <p class="bc-recurring-box__status">{{ sale.recurring_status_label|e }}</p>
                                        {% if sale.next_charge_date %}<p class="bc-recurring-box__extra">Next charge: {{ sale.next_charge_date|e }}</p>{% endif %}
                                        {% if sale.recurring_profile_id %}<p class="bc-recurring-box__status">Profile: {{ sale.recurring_profile_id|e }}</p>{% endif %}
                                    </div>
                                    <div class="bc-actions" style="margin-top: 0;">
                                        {% if sale.can_enable_recurring %}<a href="{{ sale.enable_recurring_url|e }}" class="bc-button bc-button--primary">Enable auto billing</a>{% endif %}
                                        {% if sale.can_cancel_recurring %}<a href="{{ sale.cancel_recurring_url|e }}" class="bc-button bc-button--danger" onclick="return confirm('Cancel auto billing for this service?');">Cancel auto billing</a>{% endif %}
                                    </div>
                                </div>
                            {% endif %}
                            <div class="bc-actions">
                                <a href="{{ sale.info_url|e }}" class="bc-button bc-button--info">{{ 'Info'|get_lang }}</a>
                                {% if sale.receipt_url %}<a href="{{ sale.receipt_url|e }}" target="_blank" rel="noopener" class="bc-button bc-button--plain">{{ 'Receipt'|get_plugin_lang('BuyCoursesPlugin') }}</a>{% endif %}
                            </div>
                        </div>
                    </article>
                {% endfor %}
            </div>
        {% endif %}
    </section>

    <section class="bc-section">
        <div class="bc-section__header"><h2 class="bc-section__title">{{ 'PurchaseHistory'|get_plugin_lang('BuyCoursesPlugin') }}</h2><p class="bc-section__description">{{ 'PurchaseHistoryDescription'|get_plugin_lang('BuyCoursesPlugin') }}</p></div>
        {% if purchase_history is empty %}
            <div class="bc-empty">{{ 'NoPurchaseHistoryYet'|get_plugin_lang('BuyCoursesPlugin') }}</div>
        {% else %}
            <div class="bc-table-wrap"><table class="bc-table"><thead><tr><th>{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th><th>{{ 'ProductType'|get_plugin_lang('BuyCoursesPlugin') }}</th><th>{{ 'ProductName'|get_plugin_lang('BuyCoursesPlugin') }}</th><th>{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th><th>{{ 'SalePrice'|get_plugin_lang('BuyCoursesPlugin') }}</th><th>{{ 'Receipt'|get_plugin_lang('BuyCoursesPlugin') }}</th></tr></thead><tbody>{% for purchase in purchase_history %}<tr><td class="bc-muted">{{ purchase.date|e }}</td><td>{{ purchase.type|e }}</td><td>{{ purchase.product_name|e }}</td><td class="bc-muted">{{ purchase.reference|e }}</td><td><strong>{{ purchase.amount|e }}</strong></td><td>{% if purchase.receipt_url %}<a href="{{ purchase.receipt_url|e }}" target="_blank" rel="noopener">{{ 'Download'|get_lang }}</a>{% else %}<span class="bc-muted">—</span>{% endif %}</td></tr>{% endfor %}</tbody></table></div>
        {% endif %}
    </section>
</div>
{% endautoescape %}

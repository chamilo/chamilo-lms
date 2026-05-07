{% autoescape false %}
<style>
    .bc-info-page {width: 100%; max-width: 1500px; margin: 0 auto; padding: 28px 24px;}
    .bc-info-actions {display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px;}
    .bc-info-actions__right {display: flex; flex-wrap: wrap; gap: 10px;}
    .bc-info-button {display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 10px 18px; border-radius: 14px; border: 1px solid #dbe5ee; background: #fff; color: #111827; font-size: 14px; font-weight: 700; text-decoration: none; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); transition: all 0.15s ease;}
    .bc-info-button:hover {border-color: #2f80b7; color: #2f80b7; text-decoration: none;}
    .bc-info-button--success {border-color: transparent; background: #5ea80f; color: #fff;}
    .bc-info-button--success:hover {background: #5ea80f; color: #fff; opacity: 0.9;}
    .bc-info-hero {display: grid; grid-template-columns: 300px minmax(0, 1fr); overflow: hidden; border: 1px solid #e6edf3; border-radius: 28px; background: #fff; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);}
    .bc-info-hero__image-wrap {width: 300px; background: #f3f6f9; border-right: 1px solid #e6edf3;}
    .bc-info-hero__image {display: block; width: 100%; height: 100%; min-height: 260px; object-fit: cover;}
    .bc-info-hero__body {padding: 30px;}
    .bc-info-badges {display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;}
    .bc-info-badge {display: inline-flex; align-items: center; min-height: 26px; padding: 5px 10px; border-radius: 999px; background: #eef6fb; color: #1f6fa9; font-size: 11px; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; line-height: 1.2;}
    .bc-info-badge--blue {background: #eef6ff; color: #1677ff;}
    .bc-info-title {margin: 0; color: #111827; font-size: 28px; font-weight: 800; line-height: 1.25;}
    .bc-info-description {max-width: 900px; margin: 10px 0 0; color: #8a96a8; font-size: 14px; line-height: 1.6;}
    .bc-info-stats {display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 24px;}
    .bc-info-stat {min-width: 0; padding: 16px; border-radius: 18px; background: #f7fafc;}
    .bc-info-stat__label {color: #8a96a8; font-size: 11px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase;}
    .bc-info-stat__value {margin-top: 8px; color: #111827; font-size: 17px; font-weight: 800; line-height: 1.3;}
    .bc-info-grid {display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 24px; margin-top: 24px;}
    .bc-info-card {padding: 26px; border: 1px solid #e6edf3; border-radius: 24px; background: #fff; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);}
    .bc-info-card__title {margin: 0 0 18px; color: #111827; font-size: 20px; font-weight: 800;}
    .bc-info-card__content {color: #8a96a8; font-size: 14px; line-height: 1.7;}
    .bc-summary-row {display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; border-radius: 16px; background: #f7fafc; color: #111827; font-size: 14px; font-weight: 700;}
    .bc-summary-pill {display: inline-flex; align-items: center; border-radius: 999px; background: #2f80b7; color: #fff; padding: 5px 12px; font-size: 13px; font-weight: 800; white-space: nowrap;}
    .bc-summary-box {margin-top: 14px; padding: 14px 16px; border: 1px solid #e6edf3; border-radius: 16px;}
    .bc-summary-box__label {color: #111827; font-size: 14px; font-weight: 800;}
    .bc-summary-box__value {margin-top: 6px; color: #8a96a8; font-size: 14px;}
    @media (max-width: 1100px) {.bc-info-hero {grid-template-columns: 1fr;} .bc-info-hero__image-wrap {width: 100%; border-right: 0; border-bottom: 1px solid #e6edf3;} .bc-info-hero__image {height: 240px; min-height: 240px;} .bc-info-stats {grid-template-columns: repeat(2, minmax(0, 1fr));} .bc-info-grid {grid-template-columns: 1fr;}}
    @media (max-width: 640px) {.bc-info-page {padding: 20px 12px;} .bc-info-actions {align-items: stretch; flex-direction: column;} .bc-info-actions__right, .bc-info-button {width: 100%;} .bc-info-hero__body {padding: 22px;} .bc-info-stats {grid-template-columns: 1fr;} .bc-info-title {font-size: 24px;}}
</style>

<div class="bc-info-page">
    <div class="bc-info-actions">
        <a href="{{ back_url|e }}" class="bc-info-button">← {{ 'Back'|get_lang }}</a>
        <div class="bc-info-actions__right">
            {% if is_purchased_context %}
                <a href="service_panel.php" class="bc-info-button">{{ 'MyServices'|get_plugin_lang('BuyCoursesPlugin') }}</a>
            {% else %}
                <a href="service_catalog.php" class="bc-info-button">{{ 'ListOfServicesOnSale'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                <a href="service_process.php?i={{ service.id }}&t={{ service.applies_to|default(0) }}" class="bc-info-button bc-info-button--success">{{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}</a>
            {% endif %}
        </div>
    </div>

    <article class="bc-info-hero">
        <div class="bc-info-hero__image-wrap"><img src="{{ service_image|e }}" alt="{{ service.name|e }}" class="bc-info-hero__image"></div>
        <div class="bc-info-hero__body">
            <div class="bc-info-badges">
                {% if applies_to_label %}<span class="bc-info-badge">{{ applies_to_label|e }}</span>{% endif %}
                {% if service.renewable is defined and service.renewable %}<span class="bc-info-badge bc-info-badge--blue">Recurring payments</span>{% endif %}
                {% if is_purchased_context %}<span class="bc-info-badge">Purchased</span>{% endif %}
            </div>
            <h1 class="bc-info-title">{{ service.name|e }}</h1>
            {% if service_description %}<p class="bc-info-description">{{ service_description|e }}</p>{% endif %}
            <div class="bc-info-stats">
                <div class="bc-info-stat"><div class="bc-info-stat__label">{{ 'Price'|get_lang }}</div><div class="bc-info-stat__value">{{ total_price_formatted|e ?: '—' }}</div></div>
                <div class="bc-info-stat"><div class="bc-info-stat__label">{{ 'Duration'|get_lang }}</div><div class="bc-info-stat__value">{{ duration_label|e }}</div></div>
                <div class="bc-info-stat"><div class="bc-info-stat__label">{{ 'Visible'|get_lang }}</div><div class="bc-info-stat__value">{% if service.visibility %}{{ 'Yes'|get_lang }}{% else %}{{ 'No'|get_lang }}{% endif %}</div></div>
                <div class="bc-info-stat"><div class="bc-info-stat__label">Tax rate</div><div class="bc-info-stat__value">{{ service.tax_perc is defined ? service.tax_perc ~ '%' : '0%' }}</div></div>
            </div>
        </div>
    </article>

    <section class="bc-info-grid">
        <article class="bc-info-card"><h2 class="bc-info-card__title">{{ 'ServiceInformation'|get_plugin_lang('BuyCoursesPlugin') }}</h2><div class="bc-info-card__content">{% if service_details_html %}{{ service_details_html|raw }}{% else %}<p>{{ 'NoDescription'|get_lang }}</p>{% endif %}</div></article>
        <aside class="bc-info-card">
            <h2 class="bc-info-card__title">{{ 'Summary'|get_lang }}</h2>
            <div class="bc-summary-row"><span>{{ 'Total'|get_lang }}</span><span class="bc-summary-pill">{{ total_price_formatted|e ?: '—' }}</span></div>
            {% if applies_to_label %}<div class="bc-summary-box"><div class="bc-summary-box__label">{{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }}</div><div class="bc-summary-box__value">{{ applies_to_label|e }}</div></div>{% endif %}
            <div class="bc-summary-box"><div class="bc-summary-box__label">{{ 'Duration'|get_lang }}</div><div class="bc-summary-box__value">{{ duration_label|e }}</div></div>
            {% if is_purchased_context %}
                <div class="bc-summary-box"><div class="bc-summary-box__label">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</div><div class="bc-summary-box__value">{{ service_sale.reference|default('')|e }}</div></div>
            {% else %}
                <a href="service_process.php?i={{ service.id }}&t={{ service.applies_to|default(0) }}" class="bc-info-button bc-info-button--success" style="width: 100%; margin-top: 16px;">{{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}</a>
            {% endif %}
        </aside>
    </section>
</div>
{% endautoescape %}

<h2 class="page-header">{{ "SystemAnnouncements" | get_lang }}</h2>

{% if not announcement is empty %}
    <article id="announcement-{{ announcement.id }}}" title="{{ announcement.title }}">
        <div class="page-header">
            {{ announcement.content }}
        </div>
    </article>
{% else %}
    <div class="alert alert-danger" role="alert">
        {{ "NoResults" | get_lang }}
    </div>
{% endif %}

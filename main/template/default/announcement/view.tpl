<div class="page-header">
    <h4>{{ "SystemAnnouncements" | get_lang }}</h4>
</div>

{% if not announcement is empty %}
    <article id="announcement-{{ announcement.id }}}">
        <div class="page-header">
            <h3>{{ announcement.title }}</h3>
            {{ announcement.content }}
        </div>
    </article>
{% else %}
    <div class="alert alert-danger" role="alert">
        {{ "NoResults" | get_lang }}
    </div>
{% endif %}

<h2 class="page-header">{{ "SystemAnnouncements" | get_lang }}</h2>

{% if not announcement is empty %}
    <article id="announcement-{{ announcement.id }}}">
        <header class="page-header">
            <h3>{{ announcement.title }}</h3>
            {{ announcement.content }}
        </header>
    </article>
{% else %}
    <div class="alert alert-danger" role="alert">
        {{ "NoResults" | get_lang }}
    </div>
{% endif %}

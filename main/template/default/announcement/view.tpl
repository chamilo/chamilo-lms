<h2 class="page-header">{{ "SystemAnnouncements" | get_lang }}</h2>

<article id="announcement-{{ announcement.id }}}">
    <header class="page-header">
        <h3>{{ announcement.title }}</h3>
        {{ announcement.content }}
    </header>
</article>

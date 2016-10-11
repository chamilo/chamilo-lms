<div id="navigation" class="notification-panel">
    {{ help_content }}
    {{ bug_notification }}
</div>
{% block topbar %}
    {% include template ~ "/layout/topbar.tpl" %}
{% endblock %}
<div class="extra-header">{{ header_extra_content }}</div>
<header id="header-section">
<section>
    <div class="container">
	<div class="row">
	    <div class="col-md-3">
	    	<div class="logo">
                {{ logo }}
            </div>
	    </div>
            <div class="col-md-9">
                <div class="col-sm-4">
                    {% if plugin_header_left is not null %}
                    <div id="plugin_header_left">
                        {{ plugin_header_left }}
                    </div>
                    {% endif %}
                </div>
                <div class="col-sm-4">
                    {% if plugin_header_center is not null %}
                    <div id="plugin_header_center">
                        {{ plugin_header_center }}
                    </div>
                    {% endif %}
                </div>
                <div class="col-sm-4">
                    {% if plugin_header_right is not null %}
                    <div id="plugin_header_right">
                        {{ plugin_header_right }}
                    </div>
                    {% endif %}
                    <div class="section-notifications">
                        <ul id="notifications" class="nav nav-pills pull-right">
                            {{ notification_menu }}
                        </ul>
                    </div>
                    {{ accessibility }}
                </div>
            </div>
        </div>
    </div>
</section>
{% block menu %}
    {% include template ~ "/layout/menu.tpl" %}
{% endblock %}
</header>
{% include template ~ "/layout/course_navigation.tpl" %}

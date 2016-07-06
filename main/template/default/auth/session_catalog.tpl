{% extends template ~ "/layout/page.tpl" %}

{% block body %}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#date').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });
    </script>
<div class="col-md-12">
<h3>{{ 'Sessions'|get_lang }}</h3>
    <div class="search-session">
        <div class="row">
            <div class="col-md-{{ show_courses ? '4' : '6' }}">
                <form class="form-inline" method="post" action="{{ _p.web_self }}?action=display_sessions">
                <div class="form-group">
                    <label>{{ "ByDate"|get_lang }}</label>
                <div class="input-group">
                    <input type="date" name="date" id="date" title="{{ 'Date'|get_lang }}" class="form-control" value="{{ search_date }}" readonly>
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="submit"><em class="fa fa-search"></em> {{ 'Search'|get_lang }}</button>
                    </span>
                </div>
                </div>
                </form>
            </div>
            <div class="col-md-{{ show_courses ? '4' : '6' }}">
                <form class="form-inline" method="post" action="{{ _p.web_self }}?action=search_tag">
                <label>{{ "ByTag"|get_lang }}</label>
                <div class="input-group">
                    <input type="text" name="search_tag" title="{{ 'ByTag'|get_lang }}" class="form-control" value="{{ search_tag }}" />
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="submit"><em class="fa fa-search"></em> {{ 'Search'|get_lang }}</button>
                        </span>
                </div>     
                </form>    
            </div>
            
            {% if show_courses %}
            <div class="col-md-4">
                <a class="btn btn-default btn-block btn-catalog" href="{{ _p.web_self }}">
                    {{ "CourseManagement"|get_lang }}
                </a>
            </div>
            {% endif %}
                        
        </div>
    </div>
</div>
<!-- new view session grib -->
<div class="grid-courses col-md-12">
    <div class="row">
        {% for item in sessions %}
        <div class="col-md-3 col-sm-6 col-xs-6">
            <div id="session-{{ item.id }}" class="items">
                <div class="image">
                    <img class="img-responsive" src="{{ item.image ? _p.web_upload ~ item.image : 'session_default.png'|icon() }}">
                    {% if item.category != '' %}
                    <span class="category">{{ item.category }}</span>
                    <div class="cribbon"></div>
                    {% endif %}
                    <div class="black-shadow">
                        {% if show_tutor %}
                        <div class="author-card">
                            <a href="{{ item.coach_url }}" class="ajax" data-title="{{ item.coach_name }}">
                                <img src="{{ item.coach_avatar }}"/>
                            </a>
                            <div class="teachers-details">
                             <h5>
                                <a href="{{ item.coach_url }}" class="ajax" data-title="{{ item.coach_name }}">
                                    {{ item.coach_name }}
                                </a>
                             </h5>
                        </div> 
                        </div>
                        {% endif %}
                        <div class="session-date">
                            <i class="fa fa-calendar-o" aria-hidden="true"></i> {{ item.date }}
                        </div>
                    </div>
                    <div class="admin-actions">
                        <div class="btn-group" role="group">
                            {% if item.edit_actions != '' %}
                            <a class="btn btn-default btn-sm" href="{{ item.edit_actions }}">
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                            </a>
                            {% endif %}
                            {% if item.is_subscribed %}
                                {{ already_subscribed_label }}
                            {% endif %}
                        </div> 
                    </div>
                </div>
                <div class="description">
                    <h4 class="title">
                        <a href="{{ _p.web ~ 'session/' ~ item.id ~ '/about/' }}" title="{{ item.name }}">
                            {{ item.name }}
                        </a>
                    </h4>
                    <div class="toolbar">
                        <div class="left">
                            {% if item.price %}
                                {{ item.price }}
                            {% endif %}
                        </div>
                        <br />
                        <div class="info">
                            <span><i class="fa fa-book" aria-hidden="true"></i> {{ item.nbr_courses }} {{ 'Courses'|get_lang }}</span>
                            <span><i class="fa fa-user" aria-hidden="true"></i> {{ item.nbr_users }} {{ 'NbUsers'|get_lang }} </span>
                        </div>
                        {% if not _u.logged %}
                            &nbsp;
                        {% else %}
                        <div class="btn-group btn-group-sm" role="group">
                            {% if item.sequences is empty %}
                                &nbsp;
                            {% else %}
                                <a class="btn btn-default btn-sm" role="button" title="{{ 'SeeSequences'|get_lang }}" data-toggle="popover" id="session-{{ item.id }}-sequences"><i class="fa fa-sitemap" aria-hidden="true"></i></a>
                            {% endif %}
                            {% if item.is_subscribed == false %}
                                {{ item.subscribe_button }}
                            {% endif %}
                        </div>        
                        {% endif %}
                    </div>
                </div>
                {% if _u.logged %}
                                <script>
                                    $('#session-{{ item.id }}-sequences').popover({
                                        placement: 'bottom',
                                        html: true,
                                        trigger: 'click',
                                        content: function () {
                                            var content = '';

                                            {% if item.sequences %}
                                                {% for sequence in item.sequences %}
                                                    content += '<p class="lead">{{ sequence.name }}</p>';

                                                    {% if sequence.requirements %}
                                                        content += '<p><em class="fa fa-sort-amount-desc"></em> {{ 'RequiredSessions'|get_lang }}</p>';
                                                        content += '<ul>';

                                                        {% for requirement in sequence.requirements %}
                                                            content += '<li>';
                                                            content += '<a href="{{ _p.web ~ 'session/' ~ requirement.id ~ '/about/' }}">{{ requirement.name }}</a>';
                                                            content += '</li>';
                                                        {% endfor %}

                                                        content += '</ul>';
                                                    {% endif %}

                                                    {% if sequence.dependencies %}
                                                        content += '<p><em class="fa fa-sort-amount-desc"></em> {{ 'DependentSessions'|get_lang }}</p>';
                                                        content += '<ul>';

                                                        {% for dependency in sequence.dependencies %}
                                                            content += '<li>';
                                                            content += '<a href="{{ _p.web ~ 'session/' ~ dependency.id ~ '/about/' }}">{{ dependency.name }}</a>';
                                                            content += '</li>';
                                                        {% endfor %}

                                                        content += '</ul>';
                                                    {% endif %}

                                                    {% if item.sequences|length > 1 %}
                                                        content += '<hr>';
                                                    {% endif %}
                                                {% endfor %}
                                            {% else %}
                                                content = "{{ 'NoDependencies'|get_lang }}";
                                            {% endif %}
                                            return content;
                                        }
                                    });
                                </script>
                {% endif %}
            </div>
        </div>
        {% else %}
        <div class="col-xs-12">
            <div class="alert alert-warning">
                {{ 'NoResults'|get_lang }}
            </div>
        </div>   
        {% endfor %}
    </div>
</div>
<!-- end view session grib -->
{{ catalog_pagination }}
{% endblock %}
{% extends app.template_style ~ "/layout/layout_2_col.tpl" %}
{% block left_column %}
    <script>
        $(function() {
            $('.load_categories li').on('click', 'a', function() {
                event.preventDefault();
                var url = $(this).attr('href');
                var id = url.replace(/[^\d\.]/g, '');

                var questionContentUrl = '{{ app.url_generator.generate('admin_get_questions_by_category', {categoryId : ':s'}) }}';
                questionContentUrl = questionContentUrl.replace(':s', id);
                $('.questions').load(questionContentUrl);
                var parent = $(this).parent();
                if (parent.find('span#'+id).length == 0) {

                    var span = document.createElement('span');
                    $(span).attr('id', id);
                    $(span).load(url);
                    parent.append(span);
                }
                return false;
            });
        });
    </script>
    <div class="well sidebar-nav load_categories">
        <li class="nav-header">{{ "QuestionCategories" | get_lang }}</li>
        {{ category_tree }}
        <li class="nav-header">{{ "GlobalQuestionCategories" | get_lang }}</li>
        {{ global_category_tree }}
    </div>
{% endblock %}
{% block right_column %}

    <div class="questions">
        {{ pagerfanta(pagination, 'twitter_bootstrap', { 'proximity': 3 } ) }}
    </div>
{% endblock %}
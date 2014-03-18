{% extends app.template_style ~ "/layout/layout_2_col.tpl" %}
{% block left_column %}
    <script>
        function loadQuestions(id) {
            var categoryUrl = '{{ url('admin_questions_get_categories', {id : ':s'}) }}';
            categoryUrl = categoryUrl.replace(':s', id);
            var questionContentUrl = '{{ url('admin_get_questions_by_category', {categoryId : ':s'}) }}';
            questionContentUrl = questionContentUrl.replace(':s', id);
            $('.questions').load(questionContentUrl);
            var parent = $('.load_categories li #'+id).parent();
            if (parent.find('span#'+id).length == 0) {
                var span = document.createElement('span');
                $(span).attr('id', id);
                $(span).load(categoryUrl);
                parent.append(span);
            }
            return false;
        }

        $(function() {
            {% if app.request.get('categoryId') %}
                var categoryId = '{{ app.request.get('categoryId') }}';
                loadQuestions(categoryId);
            {% endif %}

            $('.load_categories li').on('click', 'a', function() {

                $('.load_categories li').each(function() {
                    $(this).removeClass('active');
                });
                $(this).parent().addClass('active');
                var url = $(this).attr('href');
                var id = url.replace(/[^\d\.]/g, '');
                loadQuestions(id);
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
    </div>
{% endblock %}

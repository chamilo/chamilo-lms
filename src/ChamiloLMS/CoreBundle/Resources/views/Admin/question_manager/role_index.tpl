<div class="col-md-6">
    <div class="well_border">
        <h4>{{ 'Questions' | trans }}</h4>
        <ul>
            <li>
                <a href="{{ url('question_manager.controller:getQuestionsAction') }}">
                {{ 'Questions' | trans }}</a>
            </li>
            <li>
                <a href="{{ url('question_manager.controller:newCategoryAction')}}">
                    {{ 'AddACategory' | trans }}
                </a>
            </li>
            <li>
                <a href="{{ _p.web }}main/exercice/tests_category.php?type=global">
                    {{ 'ManageQuestionCategories' | trans }}
                </a>
            </li>
        </ul>
    </div>
</div>

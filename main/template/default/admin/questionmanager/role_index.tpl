<div class="col-md-6">
    <div class="well_border">
        <h4>{{ 'Questions'  | get_lang }}</h4>
        <ul>
            <li>
                <a href="{{ url('admin_questions') }}">
                {{ 'Questions' | get_lang }}</a>
            </li>
            <li><a href="{{ url('admin_category_new')}}">{{ 'AddACategory' | get_lang }}</a></li>
            <li><a href="{{ _p.web }}main/exercice/tests_category.php?type=global">{{ 'ManageQuestionCategories' | get_lang }}</a></li>
        </ul>
    </div>
</div>

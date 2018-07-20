<div id="email-message" style="padding-top:10px; padding-bottom:10px;">
    <p><strong>{{ 'DearStudentEmailIntroduction'|get_lang }}</strong> {{ 'AttemptVCC'|get_lang }} </p>
    <div style="border-color: #bce8f1; padding: 15px; background-color: #d9edf7; margin-bottom: 15px; font-size: 16px; color:#31708f;">
        <p>
            <strong>{{ 'CourseName'|get_lang }}: </strong>
            {{ course_title }}
        <br>
            <strong>{{ 'Exercise'|get_lang }}: </strong>
            {{ test_title }}
        </p>
    </div>

    <p>{{ 'ClickLinkToViewComment'|get_lang }}</p>
    <p><a style="font-weight: bold; color: #2BA6CB;" href="{{ url }}">{{ url }}</a></p>
    <div style="text-align: right;">
        <p><strong>{{ 'Regards'|get_lang }}</strong><br>
        {{ teacher_name }}</p>
    </div>
</div>
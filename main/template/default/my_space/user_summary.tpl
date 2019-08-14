<script>
    $(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
<div class="summary-legend">
    <ul class="list-legend">
        <li>
            <span class="cube student-progress">
            </span>
            {{ 'AvgStudentsProgress'|get_lang }}
        </li>
        <li>
            <span class="cube student-score">
            </span>
            {{ 'AvgCourseScore'|get_lang }}
        </li>
        <li>
            <span class="cube student-message">
            </span>
            {{ 'TotalNumberOfMessages'|get_lang }}
        </li>
        <li>
            <span class="cube student-assignments">
            </span>
            {{ 'TotalNumberOfAssignments'|get_lang }}
        </li>
        <li>
            <span class="cube student-exercises">
            </span>
            {{ 'TotalExercisesScoreObtained'|get_lang }}
        </li>
        <li>
            <span class="cube questions-answered">
            </span>
            {{ 'TotalExercisesAnswered'|get_lang }}
        </li>
        <li>
            <span class="cube last-connection">
            </span>
            {{ 'LatestLogin'|get_lang }}
        </li>
    </ul>
</div>

{{ table }}
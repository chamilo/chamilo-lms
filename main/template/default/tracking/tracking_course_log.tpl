<!-- tracking course log -->
<script>
$(function () {
    var scoreStudent = document.getElementById("chart-score").getContext('2d');
    var lastAccess = document.getElementById("chart-access").getContext('2d');
    var jsonfile = {{ json_time_student }};
    var labels = [];
    var times = [];

   Object.keys(jsonfile).forEach(function(key) {
       //Names
       labels.push(jsonfile[key].fullname);
       //Time plataform total
       times.push(jsonfile[key].total_time);
   });

    var myChartAccess = new Chart(lastAccess, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: times,
                borderColor: "#3ba557",
                backgroundColor: "#3ba557",
                borderWidth: 1,
                fill: false,
                label: '{{ "Minutes"|get_lang|e('js') }}',
            }]
        },
        options: {
            legend:{
                display: false
            },
            scales: {
                xAxes:[{
                    position: "bottom",
                    scaleLabel: {
                        display: true,
                        labelString: '{{ "Students"|get_lang|e('js') }}',
                    },
                    ticks: {
                        display: false
                    }
                }],
                yAxes: [{
                    position: "left",
                    scaleLabel: {
                        display: true,
                        labelString: '{{ "Minutes"|get_lang|e('js') }}',
                    }
                }]
            }
        }
    });

    var myChartScore = new Chart(scoreStudent, {
        type: 'bar',
        data: {
            labels: ["0-9%", "10-19%", "20-29%", "30-39%", "40-49%", "50-59%", "60-69%", "70-79%", "80-89%", "90-100%"],
            datasets: [{
                label: '{{ "NumberOfUsers"|get_lang|e('js') }}',
                data: {{ score_distribution }},
                backgroundColor: {{ chart_colors }},
                borderColor: {{ chart_colors }},
                borderWidth: 1,
                fill: false
            }]
        },
        options: {
            legend:{
              display: false
            },
            scales: {
                yAxes: [{
                    position: "left",
                    scaleLabel: {
                        display: true,
                        labelString: '{{ "NumberOfUsers"|get_lang|e('js') }}',
                    },
                    ticks: {
                        display: true,
                        min: 0,
                        stepSize: 1
                    }
                }],
                xAxes:[{
                    position: "bottom",
                    scaleLabel: {
                        display: true,
                        labelString: "{{ 'PercentileScoresDistribution'|get_lang|e('js') }}",
                    },
                    gridLines: {
                      display: true
                    },
                    ticks: {
                        display: false,
                    }
                }],
            }
        }
    });
});
</script>

<div class="tracking-course-summary">
    <div class="row">
        <div class="col-lg-3 col-sm-3">
            <div class="panel panel-default tracking tracking-student">
                <div class="panel-body">
                    <span class="tracking-icon">
                        <i class="fa fa-graduation-cap" aria-hidden="true"></i>
                    </span>
                    <div class="tracking-info">
                        <div class="tracking-text"> {{ "NumberOfUsers"|get_lang }}</div>
                        <div class="tracking-number">
                            {{ number_students }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="panel panel-default tracking tracking-lessons">
                <div class="panel-body">
                    <span class="tracking-icon">
                        <i class="fa fa-book" aria-hidden="true"></i>
                    </span>
                    <div class="tracking-info">
                        <div class="tracking-text"> {{ "CourseProgress"|get_lang }}</div>
                        <div class="tracking-number">
                            {{ students_completed_lp }}/{{ number_students }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="panel panel-default tracking tracking-exercise">
                <div class="panel-body">
                    <span class="tracking-icon">
                        <i class="fa fa-heartbeat" aria-hidden="true"></i>
                    </span>
                    <div class="tracking-info">
                        <div class="tracking-text"> {{ "ExerciseAverage"|get_lang }}</div>
                        <div class="tracking-number">
                            {{ students_test_score }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="panel panel-default tracking tracking-certificate">
                <div class="panel-body">
                    <span class="tracking-icon">
                        <i class="fa fa-id-card-o" aria-hidden="true"></i>
                    </span>
                    <div class="tracking-info">
                        <div class="tracking-text"> {{ "CountCertificates"|get_lang }}</div>
                        <div class="tracking-number">
                            {{ certificate_count }}/{{ number_students }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="tracking-chart">
                        <h4 class="tracking-box-title">{{ 'PercentileScoresDistribution'|get_lang }}</h4>
                        <canvas id="chart-score"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default tracking-top-student">
                <div class="panel-body">
                    <h4 class="tracking-box-title">{{ 'OutstandingStudents'|get_lang }}</h4>
                    <ul class="list-top">
                        {% for student in top_students.0 %}
                            <li>
                                <div class="avatar">
                                    <span class="round">
                                        <img
                                                title="{{ student.fullname }}"
                                                alt="{{ student.fullname }}"
                                                src="{{ student.avatar }}"
                                                width="40px">
                                    </span>
                                </div>
                                <div class="info">
                                    <h3 class="name">{{ student.fullname }}</h3>
                                    <div class="progress">
                                        <div
                                            class="progress-bar progress-bar-success progress-bar-striped"
                                            role="progressbar"
                                            aria-valuenow="{{ student.score }}" aria-valuemin="0"
                                            aria-valuemax="100" style="width: {{ student.score }}%;">
                                            {{ student.score }}%
                                        </div>
                                    </div>
                                </div>
                            </li>
                        {% endfor %}
                    </ul>
                   <span class="tracking-box-legend">
                       {{ 'ProgressObtainedFromLPProgressAndTestsAverage'|get_lang }}
                   </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-body">
                    <h4 class="tracking-box-title">{{ "TotalTimeSpentInTheCourse"|get_lang }}</h4>
                    <canvas id="chart-access"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

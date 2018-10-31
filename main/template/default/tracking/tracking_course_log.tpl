<!-- tracking course log -->
<script type="text/javascript">
    window.onload = function() {
    var ctx = document.getElementById("chart-score").getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["0-9", "10-19", "20-29", "30-39", "40-49", "50-59", "60-69", "70-79", "80-89", "90-100"],
            datasets: [{
                label: 'Número de alumnos',
                data: {{ score_distribution }},
                backgroundColor: 'rgba(17, 125, 198, 0.8)',
                borderColor: 'rgba(4,90,149,1)',
                borderWidth: 1
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
                        labelString: "Número Alumnos",
                    }
                }],
                xAxes:[{
                    position: "bottom",
                    scaleLabel: {
                        display: true,
                        labelString: "Rango de porcentaje %",
                    }
                }],
            }
        }
    });
};
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
                        <div class="tracking-text"> Estudiantes inscritos</div>
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
                        <div class="tracking-text"> Estudiantes que completaron las lecciones</div>
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
                        <div class="tracking-text"> Media de ejercicios del total de estudiantess</div>
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
                        <div class="tracking-text"> Cantidad de certificados entregados</div>
                        <div class="tracking-number">
                            1/3
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
                        <h4>Distribución porcentual de notas</h4>
                        <canvas id="chart-score"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default tracking-top-student">
                <div class="panel-body">
                    <h4>Alumnos destacados</h4>
                    <ul class="list-top">
                        <li>
                            <div class="avatar">
                                <span class="round">
                                    <img src="https://wrappixel.com/demos/admin-templates/material-pro/assets/images/users/2.jpg" width="50px">
                                </span>
                            </div>
                            <div class="info">
                                <h3 class="name">Andrea Costea</h3>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 80%;">
                                        80%
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="avatar">
                                <span class="round">B</span>
                            </div>
                            <div class="info">
                                <h3 class="name">Beatriz Merino</h3>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;">
                                        60%
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="avatar">
                                <span class="round">B</span>
                            </div>
                            <div class="info">
                                <h3 class="name">Garcia Rupli</h3>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 30%;">
                                        30%
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-body">
                    .
                </div>
            </div>
        </div>
    </div>
</div>


<!-- tracking course log -->
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
        <div class="col-md-6">
            <div class="tracking-chart">

            </div>
        </div>
        <div class="col-md-6">
            .
        </div>
    </div>
</div>


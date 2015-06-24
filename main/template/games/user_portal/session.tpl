

            <!-- Courrent Courses -->

            {% if not session.show_simple_session_info %}
            <div class="title-course">
                {% if session.show_link_to_session %}
                    <i class="fa fa-square"></i>
                    <a href="{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}" alt="{{ session.title }}" title="{{ session.title }}">
                        {{ session.title }}
                    </a>
                {% else %}
                    <i class="fa fa-square"></i>
                        {{ session.title }}

                {% endif %}
                {% if session.show_actions %}
                <div class="pull-right">
                    <a href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ session.id }}">
                        <img src="{{ "edit.png"|icon(22) }}" alt="{{ "Edit"|get_lang }}" title="{{ "Edit"|get_lang }}">
                    </a>
                </div>
                {% endif %}
            </div>
            {% endif %}

            <div class="current-item">
                {% if session.show_simple_session_info %}

                {% else %}
                <div class="row">
                    <div class="col-md-4">Imagen</div>
                    <div class="col-md-8">
                        {% if session.show_description %}
                        <div class="description-course">
                            {{ session.description }}
                        </div>
                        {% endif %}
                        <div class="teachers-course">
                            <i class="fa fa-pencil-square"></i>
                            <span><i class="fa fa-square"></i> Ronald Avila</span>
                            <span><i class="fa fa-square"></i> Luis Rosales</span>
                            <span><i class="fa fa-square"></i> Daniel Falcón</span>
                            <span><i class="fa fa-square"></i> Martín Farfan</span>
                        </div>
                        {% if session.subtitle %}
                        <div class="time-course">
                            <i class="fa fa-clock-o"></i>
                            <span class="text-uppercase"> {{ session.subtitle }} </span>
                        </div>
                        {% endif %}
                        <div class="row">
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <a class="btn btn-press" href="{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}" role="button">Continuar</a>
                            </div>
                        </div>
                    </div>
                </div>
                {% endif %}
            </div>


            <!-- End courrent Courses -->




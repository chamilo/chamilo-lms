<div class="question-result">
    <div class="panel panel-default">
        <div class="panel-body">
            <h3>{{ data.title }}</h3>
            <div class="row">
                <div class="col-md-3">
                    <div class="user-avatar">
                        <img src="{{ data.avatar }}">
                    </div>
                    <div class="user-info">
                        <strong>{{ data.name_url }}</strong><br>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="group-data">
                        <div class="list-data username">
                            <span class="item">{{ 'Username'|get_lang }}</span>
                            <i class="fa fa-user" aria-hidden="true"></i> {{ data.username }}
                        </div>
                        <div class="list-data start-date">
                            <span class="item">{{ 'StartDate'|get_lang }}</span>
                            <i class="fa fa-calendar" aria-hidden="true"></i> {{ data.start_date }}
                        </div>
                        <div class="list-data duration">
                            <span class="item">{{ 'Duration'|get_lang }}</span>
                            <i class="fa fa-clock-o" aria-hidden="true"></i> {{ data.duration }}
                        </div>
                        <div class="list-data ip">
                            <span class="item">{{ 'IP'|get_lang }}</span>
                            <i class="fa fa-laptop" aria-hidden="true"></i> {{ data.ip }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

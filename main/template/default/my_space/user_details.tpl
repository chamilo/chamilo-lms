<div class="row">
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="user">
                    <div class="avatar">
                        <img width="128px" src="{{ user.avatar }}" class="img-responsive" >
                    </div>
                    <div class="name">
                        <h3>{{ user.complete_name_link }}</h3>
                        <p class="email">{{ user.email }}</p>
                    </div>
                    <div class="parameters">
                        <dl class="dl-horizontal">
                            <dt>{{ 'Tel'|get_lang }}</dt>
                            <dd>{{ user.phone == '' ? 'NoTel'|get_lang : user.phone }}</dd>
                            <dt>{{ 'OfficialCode'|get_lang }}</dt>
                            <dd>{{ user.code == '' ? 'NoOfficialCode'|get_lang : user.code }}</dd>
                            <dt>{{ 'OnLine'|get_lang }}</dt>
                            <dd>{{ user.online }}</dd>

                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">

    </div>
</div>
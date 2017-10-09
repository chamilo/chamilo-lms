{{ 'ImsLtiDescription'|get_plugin_lang('ImsLtiPlugin') }}
<div class="btn-toolbar">
    <a href="{{ _p.web_plugin }}ims_lti/create.php" class="btn btn-primary">
        <span class="fa fa-plus fa-fw" aria-hidden="true"></span> {{ 'AddExternalTool'|get_plugin_lang('ImsLtiPlugin') }}
    </a>
</div>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>{{ 'Name'|get_lang }}</th>
                <th>{{ 'LaunchUrl'|get_plugin_lang('ImsLtiPlugin') }}</th>
                <th>{{ 'Actions'|get_lang }}</th>
            </tr>
        </thead>
        <tbody>
            {% for tool in tools %}
                <tr>
                    <td>{{ tool.name }}</td>
                    <td>{{ tool.launch_url }}</td>
                    <td>
                        <a href="{{ _p.web_plugin }}ims_lti/edit.php?{{ {'id': tool.id}|url_encode() }}" class="btn btn-success">
                            <span class="fa fa-edit fa-fw" aria-hidden="true"></span> {{ 'Edit'|get_lang }}
                        </a>
                        <a href="{{ _p.web_plugin }}ims_lti/delete.php?{{ {'id': tool.id}|url_encode() }}" class="btn btn-danger">
                            <span class="fa fa-times fa-fw" aria-hidden="true"></span> {{ 'Delete'|get_lang }}
                        </a>
                    </td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
</div>

<div class="space-y-6">
    <div class="text-base leading-6 text-gray-800">
        {{ 'LtiProviderDescription'|get_plugin_lang('LtiProviderPlugin') }}
    </div>

    <div class="flex flex-wrap items-center gap-3 pt-2">
        <a
                href="{{ url('index') }}plugin/LtiProvider/provider_settings.php"
                class="btn btn--plain ajax"
                data-title="{{ 'ConnectionDetails'|get_plugin_lang('LtiProviderPlugin') }}"
                title="{{ 'ConnectionDetails'|get_plugin_lang('LtiProviderPlugin') }}"
        >
            <i class="mdi mdi-cog-outline"></i>
            {{ 'ConnectionDetails'|get_plugin_lang('LtiProviderPlugin') }}
        </a>

        <a
                href="{{ url('index') }}plugin/LtiProvider/create.php"
                class="btn btn--primary"
                title="{{ 'AddPlatform'|get_plugin_lang('LtiProviderPlugin') }}"
        >
            <i class="mdi mdi-plus"></i>
            {{ 'AddPlatform'|get_plugin_lang('LtiProviderPlugin') }}
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-hover admin-tools mb-0">
                <thead>
                <tr>
                    <th>{{ 'PlatformName'|get_plugin_lang('LtiProviderPlugin') }}</th>
                    <th>{{ 'ClientId'|get_plugin_lang('LtiProviderPlugin') }}</th>
                    <th class="text-center">{{ 'DeploymentId'|get_plugin_lang('LtiProviderPlugin') }}</th>
                    <th class="text-center">{{ 'URLs'|get_plugin_lang('LtiProviderPlugin') }}</th>
                    <th class="text-center">{{ 'ToolProvider'|get_plugin_lang('LtiProviderPlugin') }}</th>
                    <th class="text-right">{{ 'Actions'|get_lang }}</th>
                </tr>
                </thead>
                <tbody>
                {% for platform in platforms %}
                {% set url_params = {'id': platform.getId}|url_encode %}
                <tr>
                    <td class="align-middle">{{ platform.getIssuer }}</td>
                    <td class="align-middle">{{ platform.getClientId }}</td>
                    <td class="align-middle text-center">{{ platform.getDeploymentId }}</td>
                    <td>
                        <div class="space-y-3">
                            <p class="mb-0">
                                <strong>{{ 'AuthLoginUrl'|get_plugin_lang('LtiProviderPlugin') }}:</strong><br>
                                {{ platform.getAuthLoginUrl }}
                            </p>
                            <p class="mb-0">
                                <strong>{{ 'AuthTokenUrl'|get_plugin_lang('LtiProviderPlugin') }}:</strong><br>
                                {{ platform.getAuthTokenUrl }}
                            </p>
                            <p class="mb-0">
                                <strong>{{ 'KeySetUrl'|get_plugin_lang('LtiProviderPlugin') }}:</strong><br>
                                {{ platform.getKeySetUrl }}
                            </p>
                        </div>
                    </td>
                    <td class="align-middle text-center">{{ platform.getToolProvider }}</td>
                    <td class="align-middle text-right whitespace-nowrap">
                        <div class="flex justify-end gap-2">
                            <a
                                    href="{{ url('index') }}plugin/LtiProvider/edit.php?{{ url_params }}"
                                    class="btn btn--plain btn--sm"
                                    title="{{ 'Edit'|get_lang }}"
                            >
                                <i class="mdi mdi-pencil-outline"></i>
                                {{ 'Edit'|get_lang }}
                            </a>

                            <a
                                    href="{{ url('index') }}plugin/LtiProvider/delete.php?{{ url_params }}"
                                    class="btn btn--danger btn--sm"
                                    title="{{ 'Delete'|get_lang }}"
                            >
                                <i class="mdi mdi-trash-can-outline"></i>
                                {{ 'Delete'|get_lang }}
                            </a>
                        </div>
                    </td>
                </tr>
                {% else %}
                <tr>
                    <td colspan="6" class="py-4 text-center text-muted">
                        {{ 'NoData'|get_lang }}
                    </td>
                </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>
    </div>
</div>

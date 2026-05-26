<section class="w-full space-y-6">
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-90">{{ 'UserJustifications'|get_plugin_lang('Justification') }}</h2>
                <p class="text-sm text-gray-50">{{ 'SearchUserJustificationsHelp'|get_plugin_lang('Justification') }}</p>
            </div>
            {% if user_info %}
                <span class="inline-flex w-fit rounded-full bg-primary/10 px-3 py-1 text-sm font-medium text-primary">
                    {{ user_info.complete_name }}
                </span>
            {% endif %}
        </div>
    </div>

    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        {{ form|raw }}
    </div>

    {% set document_list = list|default([]) %}

    {% if user_info %}
        <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-gray-90">
                        {{ 'DocumentListForUserX'|get_plugin_lang('Justification')|format(user_info.complete_name) }}
                    </h3>
                    <p class="text-sm text-gray-50">{{ 'UserJustificationListHelp'|get_plugin_lang('Justification') }}</p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-primary/10 px-3 py-1 text-sm font-medium text-primary">
                    {{ document_list|length }} {{ 'Documents'|get_lang }}
                </span>
            </div>
        </div>

        {% if document_list|length > 0 %}
            <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="bg-gray-15 text-gray-70">
                        <tr>
                            <th class="px-4 py-3 font-semibold">{{ 'Justification'|get_plugin_lang('Justification') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ 'File'|get_lang }}</th>
                            <th class="px-4 py-3 font-semibold">{{ 'ValidityDate'|get_plugin_lang('Justification') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ 'Actions'|get_lang }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-25">
                        {% for item in document_list %}
                            <tr class="hover:bg-gray-15/60">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-90">{{ item.justification.name }}</div>
                                    <div class="text-xs text-gray-50">{{ item.justification.code }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-70">{{ item.file_path|raw }}</td>
                                <td class="px-4 py-3 text-gray-70">{{ item.date_validity|raw }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a
                                            href="{{ url('index') }}plugin/Justification/justification_by_user.php?a=edit&user_id={{ user_id }}&id={{ item.id }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-primary hover:bg-primary/10"
                                            title="{{ 'Edit'|get_lang }}"
                                            aria-label="{{ 'Edit'|get_lang }}"
                                        >
                                            <span class="mdi mdi-pencil"></span>
                                        </a>
                                        <a
                                            href="{{ url('index') }}plugin/Justification/justification_by_user.php?a=delete&user_id={{ user_id }}&id={{ item.id }}&sec_token={{ token }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-danger hover:bg-danger/10"
                                            title="{{ 'Delete'|get_lang }}"
                                            aria-label="{{ 'Delete'|get_lang }}"
                                            onclick="return confirm('{{ 'Are you sure?'|get_lang|e('js') }}');"
                                        >
                                            <span class="mdi mdi-delete"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        {% else %}
            <div class="rounded-2xl border border-gray-25 bg-gray-15/60 p-10 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-primary shadow-sm">
                    <span class="mdi mdi-file-document-outline text-2xl"></span>
                </div>
                <p class="text-base font-semibold text-gray-90">{{ 'NoJustificationFound'|get_plugin_lang('Justification') }}</p>
                <p class="text-sm text-gray-50">{{ 'NoUserJustificationHelp'|get_plugin_lang('Justification') }}</p>
            </div>
        {% endif %}
    {% else %}
        <div class="rounded-2xl border border-gray-25 bg-gray-15/60 p-10 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-primary shadow-sm">
                <span class="mdi mdi-account-search text-2xl"></span>
            </div>
            <p class="text-base font-semibold text-gray-90">{{ 'SelectUser'|get_plugin_lang('Justification') }}</p>
            <p class="text-sm text-gray-50">{{ 'SelectUserJustificationHelp'|get_plugin_lang('Justification') }}</p>
        </div>
    {% endif %}
</section>

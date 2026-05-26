<section class="w-full space-y-6">
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <span class="mdi mdi-file-check-outline text-2xl"></span>
                </div>
                <div>
                    <h2 class="text-2xl font-semibold text-gray-90">{{ 'Justification'|get_plugin_lang('Justification') }}</h2>
                    <p class="text-sm text-gray-50">{{ 'RequiredDocuments'|get_plugin_lang('Justification') }}</p>
                </div>
            </div>
            <span class="inline-flex w-fit rounded-full bg-primary/10 px-3 py-1 text-sm font-medium text-primary">
                {{ list|length }} {{ 'Documents'|get_lang }}
            </span>
        </div>
    </div>

    {% if list|length > 0 %}
        <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
            <table class="w-full border-collapse text-left text-sm">
                <thead class="bg-gray-15 text-gray-70">
                    <tr>
                        <th class="px-4 py-3 font-semibold">{{ 'Name'|get_lang }}</th>
                        <th class="px-4 py-3 font-semibold">{{ 'JustificationCode'|get_plugin_lang('Justification') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ 'ValidityDuration'|get_plugin_lang('Justification') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ 'DateManualOn'|get_plugin_lang('Justification') }}</th>
                        {% if can_manage_documents %}
                            <th class="px-4 py-3 text-right font-semibold">{{ 'Actions'|get_lang }}</th>
                        {% endif %}
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-25">
                    {% for item in list %}
                        <tr class="hover:bg-gray-15/60">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-90">{{ item.name }}</div>
                                {% if item.comment %}
                                    <div class="text-xs text-gray-50">{{ item.comment }}</div>
                                {% endif %}
                            </td>
                            <td class="px-4 py-3 text-gray-70">{{ item.code }}</td>
                            <td class="px-4 py-3 text-gray-70">{{ item.validity_duration }}</td>
                            <td class="px-4 py-3 text-gray-70">
                                {% if item.date_manual_on %}
                                    <span class="inline-flex rounded-full bg-success/10 px-2 py-1 text-xs font-medium text-success">{{ 'Yes'|get_lang }}</span>
                                {% else %}
                                    <span class="inline-flex rounded-full bg-gray-15 px-2 py-1 text-xs font-medium text-gray-70">{{ 'No'|get_lang }}</span>
                                {% endif %}
                            </td>
                            {% if can_manage_documents %}
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a
                                            href="{{ url('index') }}plugin/Justification/edit.php?id={{ item.id }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-primary hover:bg-primary/10"
                                            title="{{ 'Edit'|get_lang }}"
                                            aria-label="{{ 'Edit'|get_lang }}"
                                        >
                                            <span class="mdi mdi-pencil"></span>
                                        </a>
                                        <a
                                            href="{{ url('index') }}plugin/Justification/list.php?a=delete&id={{ item.id }}&sec_token={{ token }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-danger hover:bg-danger/10"
                                            title="{{ 'Delete'|get_lang }}"
                                            aria-label="{{ 'Delete'|get_lang }}"
                                            onclick="return confirm('{{ 'Are you sure?'|get_lang|e('js') }}');"
                                        >
                                            <span class="mdi mdi-delete"></span>
                                        </a>
                                    </div>
                                </td>
                            {% endif %}
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    {% else %}
        <div class="rounded-2xl border border-gray-25 bg-gray-15/60 p-10 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-primary shadow-sm">
                <span class="mdi mdi-file-document-plus-outline text-2xl"></span>
            </div>
            <p class="text-base font-semibold text-gray-90">{{ 'NoJustificationFound'|get_plugin_lang('Justification') }}</p>
            <p class="text-sm text-gray-50">{{ 'CreateFirstJustificationHelp'|get_plugin_lang('Justification') }}</p>
        </div>
    {% endif %}
</section>

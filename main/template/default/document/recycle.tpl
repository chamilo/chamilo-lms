{% macro bytesToSize(bytes) %}
{% spaceless %}
{% set kilobyte = 1024 %}
{% set megabyte = kilobyte * 1024 %}
{% set gigabyte = megabyte * 1024 %}
{% set terabyte = gigabyte * 1024 %}

{% if bytes < kilobyte %}
{{ bytes ~ ' B' }}
{% elseif bytes < megabyte %}
{{ (bytes / kilobyte)|number_format(2, '.') ~ ' KB' }}
{% elseif bytes < gigabyte %}
{{ (bytes / megabyte)|number_format(2, '.') ~ ' MB' }}
{% elseif bytes < terabyte %}
{{ (bytes / gigabyte)|number_format(2, '.') ~ ' GB' }}
{% else %}
{{ (bytes / terabyte)|number_format(2, '.') ~ ' TB' }}
{% endif %}
{% endspaceless %}
{% endmacro %}

{% if files %}
    <table class="table">
        <tr>
            <th>{{ 'Path' | get_lang }}</th>
            <th>{{ 'Size' | get_lang }}</th>
            <th>{{ 'Actions' | get_lang }}</th>
        </tr>
    {% for file in files %}
        <tr>
            <td>
                {{ file.path }}
            </td>
            <td>
                {{ _self.bytesToSize(file.size) }}
            </td>

            <td>
                <a href="{{ web_self }}?{{web_cid_query}}&action=download&id={{ file.id }}" class="btn btn-default">{{ 'Download' | get_lang }}</a>
                <a href="{{ web_self }}?{{web_cid_query}}&action=delete&id={{ file.id }}" class="btn btn-danger">{{ 'Delete' | get_lang }}</a>
            </td>
        </tr>
    {% endfor %}
    </table>
{% else %}
    {{ 'NoData' | get_lang }}
{% endif %}


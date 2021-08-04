<h3>
    {{ 'MyLps' | get_lang }}
</h3>
<table class="table">
    <tr>
        <th>
        {{ 'Title' | get_lang }}
        </th>
    </tr>
    {% for lp in lps %}
        <tr>
            <td>
                {{ lp.icon }}
                <a href="{{ lp.link }}" target="_blank">
                    {{ lp.name }}
                </a>
            </td>
        </tr>
    {% endfor %}
</table>
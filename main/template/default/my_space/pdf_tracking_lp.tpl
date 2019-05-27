
<h3 style="text-align: center; text-transform: uppercase; font-size: 20px; font-weight: bold;">{{ data.name }}</h3>
<p>{{ 'Candidate' | get_lang }} : {{ data.candidate }}</p>

<table  style="width: 100%; font-size: 12px; font-weight: normal;">
    <tr>
        <th style="text-align: center;  background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Attempt'|get_lang }}
        </th>
        <th style="text-align: center; background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Score'|get_lang }}
        </th>
        <th style="text-align: center; background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Duration'|get_lang }}
        </th>
        <th style="text-align: center; background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'StartTime'|get_lang }}
        </th>
        <th style="text-align: center; background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'EndTime'|get_lang }}
        </th>
    </tr>
    <tr>
        <td style="text-align: center; height: 50px; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">

            {{ data.attempt }}

        </td>
        <td style="text-align: center; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">

            {{ data.score }}

        </td>
        <td style="text-align: center; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">

            {{ data.duration }}

        </td>
        <td style="text-align: center; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">

            {{ data.start_time }}

        </td>
        <td style="text-align: center; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">

            {{ data.end_time }}

        </td>
    </tr>
</table>
<br>

<table style="width: 100%; font-size: 12px; font-weight: normal;">
    <tr>
        <th style="text-align: center;  background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'CategoryName'|get_lang }}
        </th>
        <th style="text-align: center;  background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Score'|get_lang }}
        </th>
        <th style="text-align: center;  background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Percentage'|get_lang }}
        </th>
    </tr>
</table>
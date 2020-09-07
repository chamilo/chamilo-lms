{{ tables }}
{{ form }}

<table class="table table-hover table-striped data_table">
	<thead>
		<tr>
			<th>
			   {{ 'Title'|get_lang }}
			</th>
			<th>
			   {{ 'Date'|get_lang }}
			</th>
			<th></th>
			<th>
				{{ 'Actions' | get_lang }}
			</th>
		</tr>
	</thead>
	<tbody>
{% for term in terms %}
    <tr>
        <td style="width:20%" >
			{{ term.title }}
		</td>
		<td style="width:25%" >
            {{ term.creation_date }}
        </td>

		<td style="width:15%" >
			<a href="{{ _p.web_plugin }}h5p/node_process.php?action=edit&{{ {'id': term.id}|url_encode() }}&{{ {'node_type': term.node_type}|url_encode() }}"
			style="border:solid 1px #086A87;background:#086A87!important;color:white!important;" class="btn">
            <span class="fa fa-eye fa-fw" aria-hidden="true"></span></a>
 		</td>

        <td style="width:15%" >
            
			<a href="{{ _p.web_plugin }}h5p/list.php?action=edit&{{ {'id': term.id}|url_encode() }}&{{ {'node_type': term.node_type}|url_encode() }}"
			class="btn btn-success">
            <span class="fa fa-edit fa-fw" aria-hidden="true"></span></a>
            
			<a onclick="return confirm('Are you sure?')"
			href="{{ _p.web_plugin }}h5p/list.php?action=delete&{{ {'id': term.id}|url_encode() }}"
			class="btn btn-danger">
            <span class="fa fa-times fa-fw" aria-hidden="true"></span></a>
        
		</td>
    </tr>
{% endfor %}
	</tbody>
</table>

<script>
	function installMenuInteractions(){
		$('div.logo').parent().parent().css('display','none');
		$('#header-section').css('display','none');
		$('.tab-homepage').find('a').html('&#8592;');	
	}
	installMenuInteractions();
</script>

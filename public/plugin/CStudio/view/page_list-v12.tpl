
{{ form }}

<table class="data_table">
	<thead>
		<tr>
			<th>
			   Titre
			</th>
			<th>
			   Date
			</th>
			<th>
			   &nbsp;
			</th>
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
		 <td style="width:35%" >
            {{ term.date_create }}
        </td>
        <td style="width:20%" >
			<a target="_blank" href="{{ _p.web_plugin }}CStudio/teachdoc-render.php?{{ {'id': term.id}|url_encode() }}" >
            <img src="img/eyes.png" /></a>
        </td>
        <td style="width:15%" >

			<a href="{{ _p.web_plugin }}CStudio/editor/index.php?action=edit&{{ {'id': term.id}|url_encode() }}"
			class="btn btn-success">
            <span class="fa fa-edit fa-fw" aria-hidden="true"></span></a>    
			<a onclick="return confirm('Etes-vous sure ?')" 
			href="{{ _p.web_plugin }}CStudio/oel_tools_teachdoc_list.php?action=delete&{{ {'id': term.id}|url_encode() }}"
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
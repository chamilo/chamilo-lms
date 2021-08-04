{% if form %}
	{{ form }}
{% endif %}
{% if terms %}
<div class="table-responsive">
<table class="table table table-hover table-striped data_table">
	<thead>
		<tr>
			<th>
			   {{ 'Title' | get_lang }}
			</th>
			<th>
			   {{ 'Description' | get_lang }}
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

			<td style="width:60%" >
				{{ term.description }}
			</td>

			<td style="width:20%" >
				<a href="{{ _p.web_plugin }}mindmap/edit-mindmap/index.php?action=edit&{{ {'id': term.id}|url_encode() }}&{{ {'typenode': term.typenode}|url_encode() }}&{{ {'cid': term.c_id}|url_encode() }}&{{ {'sid': term.session_id}|url_encode()}}"
				style="border:solid 1px #086A87;background:#086A87!important;color:white!important;" class="btn">
				<span class="fa fa-eye fa-fw" aria-hidden="true"></span></a>
				{% if term.typeedit=='mind' %}

					<a href="{{ _p.web_plugin }}mindmap/list.php?action=edit&{{ {'id': term.id}|url_encode() }}&{{ {'typenode': term.typenode}|url_encode() }}&{{ {'cid': term.c_id}|url_encode() }}&{{ {'sid': term.session_id}|url_encode()}}"
					class="btn btn-success">
					<span class="fa fa-edit fa-fw" aria-hidden="true"></span></a>

					<a onclick="return confirm('{{ 'AreYouSure' | get_lang }}')"
					href="{{ _p.web_plugin }}mindmap/list.php?action=delete&{{ {'id': term.id}|url_encode() }}&{{ {'cid': term.c_id}|url_encode() }}&{{ {'sid': term.session_id}|url_encode()}}"
					class="btn btn-danger">
					<span class="fa fa-times fa-fw" aria-hidden="true"></span></a>

				{% endif %}
			</td>
		</tr>
	{% endfor %}
	</tbody>
</table>
</div>
{% endif %}
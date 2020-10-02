
<div id="actions" class="actions">
	<a href="edit-title.php" >
		<img src="resources/img/home.png" alt="Liste des tuilles" title="Liste des tuilles" >
	</a>
	<a href="edit-params.php" >
		<img src="resources/img/editboost.png" alt="Options" title="Options" >
	</a>
	<a href="edit-menu.php" >
		<img src="resources/img/menu.png" alt="Options" title="Menu" >
	</a>
	<a href="edit-upt.php" >
		<img src="resources/img/maj.png" alt="Update" title="Update" >
	</a>
</div>

{{ form }}

<table class="data_table">
	<thead>
		<tr>
			<th>
				{{ 'Index' }}
			</th>
			<th>
				{{ 'Titre' }}
			</th>
			<th>
				{{ 'Image' }}
			</th>
			<th>
				{{ 'Actions' | get_lang }}
			</th>
		</tr>
	</thead>
	<tbody>
		{% for term in terms %}
			<tr>
				<td>
					{{ term.indexTitle }}
				</td>
				<td>
					{{ term.title }} 
				</td>
				<td>
					<img src="{{ term.imageUrl }}" style="width:40px;height:25px;" />
				</td>
				<td>
					<a href="{{ _p.web_plugin }}chamilo_boost/edit-title.php?action=edit&{{ {'id': term.id}|url_encode() }}" class="btn btn-success">
						<span class="fa fa-edit fa-fw" aria-hidden="true"></span></a>
					<a href="{{ _p.web_plugin }}chamilo_boost/edit-title.php?action=delete&{{ {'id': term.id}|url_encode() }}" class="btn btn-danger">
						<span class="fa fa-times fa-fw" aria-hidden="true"></span></a>
				</td>
			</tr>
		{% endfor %}
	</tbody>
</table>
<br><br>

				

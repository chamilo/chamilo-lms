<div class="vchamilo-title">
<h2>{{title}}</h2>
</div>
<div class="vchamilo-host-list">
	<ul>
	{% for host in hosts %}
	    <li class="vchamilo-host"> <a href="{{host.url}}">{{host.name}}</a></li>
	{% endfor %}
	</ul>
</div>
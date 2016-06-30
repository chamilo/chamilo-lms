<header id="header-section">
<section>
    <div class="container">
	<div class="row">
	    <div class="col-md-3">
	    	<div class="logo">
                    {{ logo }}
                </div>
	    </div>
            <div class="col-md-9">
	    					
            </div>
	</div>
    </div>
</section>
{% block menu %}
    {% include template ~ "/layout/menu.tpl" %}
{% endblock %}
</header>
{% set alertTypeAvaillable = [ 'info', 'success', 'warning', 'error', 'notice'] %}
{% for alert in alertTypeAvaillable %}
    {% for message in app.session.getFlashBag.get(alert) %}
        <div class="alert alert-{{ alert }}" >
            <button class="close" data-dismiss="alert">×</button>
            {{ message|trans }}
        </div>
    {% endfor %}
{% endfor %}

{% if pagination != '' %}
    {{ pagerfanta(pagination, 'twitter_bootstrap', { 'proximity': 3 } ) }}
{% endif %}
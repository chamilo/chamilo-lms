
{{javascript}}

<div class = "actions" >
    <a href = "{{root}}&amp;action=listing" class = "announce btn back"></a>
</div>

{% for message in messages %}
    {{ message }}
{% endfor %}

{{form.return_form()}}
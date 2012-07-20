{{javascript}}

{% for message in messages %}
    {{ message }}
{% endfor %}

<div class="actions" >
    <a href = "{{root}}&amp;action=listing" class = "course_description btn back"></a>
</div>

{{form.return_form()}}
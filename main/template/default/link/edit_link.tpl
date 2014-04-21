
{{javascript}}

<div class="btn-toolbar actions-bar" >
    <div class="btn-group">
        <a href="{{root}}&amp;action=listing" class="btn">
            <i class="size-32 icon-back"></i>
        </a>
    </div>
</div>

{% for message in messages %}
    {{ message }}
{% endfor %}

{{form.return_form()}}
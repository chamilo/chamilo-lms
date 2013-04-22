{{javascript}}

{% for message in messages %}
    {{ message }}
{% endfor %}

<div class="btn-toolbar actions-bar" >
    <div class="btn-group">
        <a href="{{root}}&amp;action=listing" class="btn" title="{{'ImportCSV'|get_lang}}">
            <i class="size-32 icon-back"></i>
        </a>
    </div>
</div>

{{form.return_form()}}
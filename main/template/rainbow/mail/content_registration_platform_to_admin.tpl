<p>{{ 'HelloComma'|get_lang }}</p>
<p>{{ 'UserXWithLangXRegisteredTheSite'| get_lang | format(user_added.completeName, user_added.language) }} </p>
<div class="row">
    <div class="col-xs-12 col-md-12">
        <div class="form-horizontal">
            {{ form }}
        </div>
    </div>
</div>

<p>{{ 'YouCanAssignATutorInThisLinkX'|get_lang | format(link)}} </p>

<p>{{ 'SignatureFormula'|get_lang }}</p>
<p>{{ 'ThePlatformTeam'|get_lang }}<br>

{{ 'Dear' | get_lang}} {{ mailCompleteName }},
</br>
{{ 'YouAreRegisterToSessionX' | get_lang | format(mailSessionName)}}
</br>
{{ 'Address' | get_lang }}  {{ mailSiteName }} {{ 'Is' | get_lang }} : {{ mailWebPath }}</br>
{{ 'Problem' | get_lang}}</br>
{{ 'SignatureFormula' | get_lang }}</br>
{{ mailAdministratorName }} {{ mailAdministratorSurname }}
{{ 'Manager' | get_lang }} {{ mailSiteName }}
T. {{ mailAdministratorTelephone}}
</br>
{{ 'Email' | get_lang }} : {{ emailAdministrator }}
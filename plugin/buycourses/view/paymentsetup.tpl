<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>
<div class="row">
    <div class="span12">
        <p class="normal-message">{{ 'PluginInstruction'|get_plugin_lang('BuyCoursesPlugin') }}<a href="{{ server }}main/admin/configure_plugin.php?name=buycourses">{{ 'ClickHere'|get_plugin_lang('BuyCoursesPlugin') }}</a></p>
    </div>
</div>
<div class="row">
    <div class="span6">
        <div class="info">
            

            <h3>{{ 'CurrencyType'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
            <p>{{ 'InfoCurrency'|get_plugin_lang('BuyCoursesPlugin') }}</p>

        </div>
        <div class="input-content">
            <select id="currency_type">
            <option value="" selected="selected">{{ 'SelectACurrency'|get_plugin_lang('BuyCoursesPlugin') }}</option>
            {% for currency in currencies %}
                {% if currency.status == 1 %}
                    <option value="{{ currency.country_id }}" selected="selected">{{ currency.country_name }} => {{ currency.currency_code }}
                    </option>
                {% else %}
                    <option value="{{ currency.country_id }}">{{ currency.country_name }} => {{ currency.currency_code }}</option>
                {% endif %}
            {% endfor %}
        </select>
        <input type="button" id="save_currency" class="btn btn-primary" value="{{ 'Save'|get_lang }}" />
        </div>
    </div>
    {% if paypal_enable == "true" %}
    <div class="span6">
        <div class="info">
            <h3>{{ 'PayPalConfig'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        </div>
         <div class="input-content">
            
            <p>{{ 'InfoApiCredentials'|get_plugin_lang('BuyCoursesPlugin') }}</p>
            <ul>
                <li>{{ 'InfoApiStepOne'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                <li>{{ 'InfoApiStepTwo'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                <li>{{ 'InfoApiStepThree'|get_plugin_lang('BuyCoursesPlugin') }}</li>
            </ul>
            <label class="control-label">API_UserName: </label><input type="text" id="username" value="{{ paypal.username | e}}" />
            <label class="control-label">API_Password: </label><input type="text" id="password" value="{{ paypal.password | e }}"/>
            <label class="control-label">API_Signature: </label> <input type="text" id="signature" value="{{ paypal.signature | e }}"/>
           
            {% if paypal.sandbox == "YES" %}
                    <label class="checkbox">
                        <input type="checkbox" id="sandbox" value="YES" checked="checked"> {{ 'Sandbox'|get_plugin_lang('BuyCoursesPlugin') }}
                    </label>
                {% else %}
                    <label class="checkbox">
                        <input type="checkbox" id="sandbox" value="YES" />{{ 'Sandbox'|get_plugin_lang('BuyCoursesPlugin') }}
                    </label>
                     
            {% endif %}
            
            <input type="button" id="save_paypal" class="btn btn-primary" value="{{ 'Save'|get_lang }}"/>
         </div>
    </div>
    {% endif %}
</div>

<div class="row">
    <div class="span12">
        
        {% if transfer_enable == "true" %}
            <hr />
            <h3>{{ 'TransfersConfig'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
            <table id="transfer_table" class="data_table">
                <tr class="row_odd">
                <th class="bg-color">{{ 'Name'|get_lang }}</th>
                <th class="bg-color">{{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="bg-color">{{ 'SWIFT'|get_lang }}</th>
                <th class="span1 ta-center bg-color">{{ 'Option'|get_lang }}</th>
                </tr>
                {% set i = 0 %}

                {% for transf in transfer %}
                {{ i%2==0 ? '
                <tr class="row_even">' : '
                <tr class="row_odd">' }}
                    {% set i = i + 1 %}
                    <td>{{ transf.name | e }}</td>
                    <td>{{ transf.account | e }}</td>
                    <td>{{ transf.swift | e }}</td>
                    <td class="ta-center" id="account{{ transf.id }}">
                        <img src="{{ delete_img }}" class="cursor delete_account" alt="ok"/>
                        <input type="hidden" id="id_account{{ transf.id }}" name="id_account{{ transf.id }}" value="{{ transf.id }}" />
                    </td>
                </tr>
                {% endfor %}
                {{ i%2==0 ? '
                <tr class="row_even">' : '
                <tr class="row_odd">' }}
                    <td><input class="span4" type="text" id="tname"/></td>
                    <td><input type="text" id="taccount"/></td>
                    <td><input class="span2" type="text" id="tswift"</td>
                    <td class="ta-center">
                        <img class="cursor" id="add_account" src="{{ more_img }}" alt="add account"/>
                    </td>
                </tr>
            </table>
        {% endif %}
</div>
<div class="cleared"></div>
</div>

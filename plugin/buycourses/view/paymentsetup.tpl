<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>
<div class="row">
    <div class="col-md-12">
        <p class="alert alert-success">{{ 'PluginInstruction'|get_plugin_lang('BuyCoursesPlugin') }} <a href="{{ server }}main/admin/configure_plugin.php?name=buycourses">{{ 'ClickHere'|get_plugin_lang('BuyCoursesPlugin') }}</a></p>
    </div>
</div>
<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                {{ 'CurrencyType'|get_plugin_lang('BuyCoursesPlugin') }}
            </div>
            <div class="panel-body">
                <p>{{ 'InfoCurrency'|get_plugin_lang('BuyCoursesPlugin') }}</p>
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
        <div class="panel panel-default">
            <div class="panel-body">
                <p>{{ 'InfoApiCredentials'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                <ul>
                    <li>{{ 'InfoApiStepOne'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                    <li>{{ 'InfoApiStepTwo'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                    <li>{{ 'InfoApiStepThree'|get_plugin_lang('BuyCoursesPlugin') }}</li>
                </ul>
            </div>
        </div>
    </div>
    {% if paypal_enable == "true" %}
    <div class="col-xs-12 col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                {{ 'PayPalConfig'|get_plugin_lang('BuyCoursesPlugin') }}
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>API_UserName:</label>
                    <input type="text" id="username" value="{{ paypal.username | e}}" class="form-control"/>
                </div>
                <div class="form-group">
                    <label>API_Password: </label>
                    <input type="text" id="password" value="{{ paypal.password | e }}" class="form-control"/>
                </div>
                <div class="form-group">
                    <label>API_Signature: </label>
                    <input type="text" id="signature" value="{{ paypal.signature | e }}" class="form-control"/>
                </div>
                <div class="checkbox">
                    {% if paypal.sandbox == "YES" %}
                    <label>
                        <input type="checkbox" id="sandbox" value="YES" checked="checked"> {{ 'Sandbox'|get_plugin_lang('BuyCoursesPlugin') }}
                    </label>
                    {% else %}
                    <label>
                        <input type="checkbox" id="sandbox" value="YES" />{{ 'Sandbox'|get_plugin_lang('BuyCoursesPlugin') }}
                    </label>
                    {% endif %}
                </div>
                <input type="button" id="save_paypal" class="btn btn-success btn-block" value="{{ 'Save'|get_lang }}"/>
            </div>
        </div>
    </div>
    {% endif %}
</div>

<div class="row">
    <div class="col-md-12">
        
        {% if transfer_enable == "true" %}
            <hr />
            <h3>{{ 'TransfersConfig'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
        <div class="table-responsive">
            <table id="transfer_table" class="table">
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
                    <td><input class="form-control" type="text" id="tname"/></td>
                    <td><input class="form-control" type="text" id="taccount"/></td>
                    <td><input class="form-control" type="text" id="tswift"</td>
                    <td class="ta-center">
                        <img class="cursor" id="add_account" src="{{ more_img }}" alt="add account"/>
                    </td>
                </tr>
            </table>
        </div>
        {% endif %}
</div>
<div class="cleared"></div>
</div>

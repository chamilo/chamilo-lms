<div>
    <p>{{ 'DearUser'|get_lang }}</p>
    <p>{{ 'PurchaseDetailsIntro'|get_plugin_lang('BuyCoursesPlugin') }}</p>
    <dl>
        <dt>Fecha</dt>
        <dd>{{ sale.date }}</dd>
        <dt>Usuario</dt>
        <dd>{{ user.complete_name }}</dd>
        <dt>Curso</dt>
        <dd>{{ sale.product }}</dd>
        <dt>Precio</dt>
        <dd>{{ sale.currency ~ ' ' ~ sale.price }}</dd>
    </dl>
    <p>{{ 'BankAccountIntro'|get_plugin_lang('BuyCoursesPlugin')|format(sale.product) }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ 'Name'|get_lang }}</th>
                <th>{{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'SWIFT'|get_lang }}</th>
            </tr>
        </thead>
        <tbody>
            {% for account in transfer_accounts %}
                <tr>
                    <td>{{ account.name }}</td>
                    <td>{{ account.account }}</td>
                    <td>{{ account.swift }}</td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
</div>

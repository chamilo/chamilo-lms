{% if show_footer == true %}
    </div>
    </section>
    {% include template ~ "/layout/page_footer.tpl" %}
{% else %}
    {% include template ~ '/layout/footer.js.tpl' %}
{% endif %}
    </div>
</body>
</html>
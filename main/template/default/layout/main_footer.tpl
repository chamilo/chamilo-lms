{% if show_footer == true %}
    {% include template ~ "/layout/footer.tpl" %}
{% endif %}

    {# Global modal, load content by AJAX call to href attribute on anchor tag with 'ajax' class #}
    <div class="modal fade" id="global-modal" tabindex="-1" role="dialog" aria-labelledby="global-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ "Close" | get_lang }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="global-modal-title">&nbsp;</h4>
                </div>
                <div class="modal-body">
                </div>
            </div>
        </div>
    </div>
</body>
</html>

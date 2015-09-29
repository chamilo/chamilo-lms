<footer id="footer">
    <div class="subfooter">
        <div class="container">
            <div class="row">
                <div class="col-xs-6 col-sm-3 col-md-3 col-lg-3">
                    <ul class="links-footer">
                        <li><a href="#">¿Quiénes somos?</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Cursos</a></li>
                    </ul>

                </div>
                <div class="col-xs-6 col-sm-3 col-md-3 col-lg-3">
                    <ul class="links-footer">
                        <li><a href="#">Políticas de privadidad</a></li>
                        <li><a href="#">Términos y condiciones</a></li>
                        <li><a href="#">Contacto</a></li>
                    </ul>
                </div>
                <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                    <div class="red-social">
                        <h3 class="social-footer">¡Síguienos en redes sociales!</h3>
                        <a class="media" href="https://www.facebook.com/tademiperu?fref=ts"><img src="{{ _p.web_css_theme }}images/facebook.png"></a>
                        <a class="media" href="https://twitter.com/tademi_pe"><img src="{{ _p.web_css_theme }}images/twitter.png"></a>
                        <a class="media" href="https://www.youtube.com/channel/UCWPiBeRKzt97-6RMDslP7YQ"><img src="{{ _p.web_css_theme }}images/youtube.png"></a>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                    <div class="direction">
                        <p>Rio de la plata 167 Of.503<br>
                            San Isidro - Lima Perú
                            (511) 221 - 2721<br>
                            contacto@tademi.com
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

{# Extra footer configured in admin section, only shown to non-admins #}
{{ footer_extra_content }}

<div class="modal fade" id="expand-image-modal" tabindex="-1" role="dialog" aria-labelledby="expand-image-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ "Close" | get_lang }}"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="expand-image-modal-title">&nbsp;</h4>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

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
{% include template ~ '/layout/footer.js.tpl' %}
{{ execution_stats }}

    </div>
    </section>
    <!-- END CONTENT -->

    {% if show_footer == true %}
    <!-- START FOOTER -->
    <footer class="footer">
        {% include 'layout/page_footer.tpl'|get_template %}
    </footer>
    <!-- END FOOTER -->
    {% endif %}

    </main>
    <!-- END MAIN -->

    {% include 'layout/modals.tpl'|get_template %}
</body>
</html>
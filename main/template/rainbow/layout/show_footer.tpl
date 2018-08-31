    </div>
    </section>
    <!-- END CONTENT -->

    <!-- START FOOTER -->
    <footer class="footer">
        {% if show_footer == true %}
            {% include 'layout/page_footer.tpl'|get_template %}
        {% endif %}
    </footer>
    <!-- END FOOTER -->

    </main>
    <!-- END MAIN -->
</body>
</html>
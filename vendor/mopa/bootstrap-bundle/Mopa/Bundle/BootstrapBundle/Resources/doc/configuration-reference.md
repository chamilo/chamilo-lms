Mopa Bootstrap Configuration Reference
=========================================

Default configuration for extension with alias: "mopa_bootstrap"

```yaml
mopa_bootstrap:
    form:
        templating:           MopaBootstrapBundle:Form:fields.html.twig
        horizontal_label_class:  col-lg-3 control-label
        horizontal_label_offset_class:  col-lg-offset-3
        horizontal_input_wrapper_class:  col-lg-9
        render_fieldset:      true
        render_collection_item:  true
        show_legend:          true
        show_child_legend:    false
        checkbox_label:       both
        render_optional_text:  true
        render_required_asterisk:  false
        error_type:           ~
        tabs:
            class:                nav nav-tabs
        help_widget:
            popover:
                title:                ~
                content:              ~
                trigger:              hover
                toggle:               popover
                placement:            right
        help_label:
            tooltip:
                title:                ~
                text:                 ~
                icon:                 info-sign
                placement:            top
            popover:
                title:                ~
                content:              ~
                text:                 ~
                icon:                 info-sign
                placement:            top
        collection:
            widget_remove_btn:
                attr:
                    class:                btn btn-default
                label:                remove_item
                icon:                 ~
                icon_color:           ~
            widget_add_btn:
                attr:
                    class:                btn btn-default
                label:                add_item
                icon:                 ~
                icon_color:           ~
    icons:

        # Icon set to use
        icon_set:             glyphicons

        # Alias for mopa_bootstrap_icon()
        shortcut:             icon
    navbar:
        enabled:              false

        # Menu template to use when rendering
        template:             MopaBootstrapBundle:Menu:menu.html.twig
    initializr:
        meta:
            title:                MopaBootstrapBundle
            description:          MopaBootstrapBundle
            keywords:             MopaBootstrapBundle, Twitter Bootstrap, HTML5 Boilerplate
            author_name:          My name
            author_url:           #
            feed_atom:            ~
            feed_rss:             ~
            sitemap:              ~
            nofollow:             false
            noindex:              false
        dns_prefetch:

            # Default:
            - //ajax.googleapis.com
        google:
            wt:                   ~
            analytics:            ~
        diagnostic_mode:      false
```

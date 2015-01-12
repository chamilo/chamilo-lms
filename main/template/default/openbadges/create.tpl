{% extends "default/layout/main.tpl" %}

{% block body %}
    <script>
        (function () {
            var designer = null;

            $(document).on('ready', function () {
                $('#btn-open-designer').on('click', function (e) {
                    e.preventDefault();

                    var designerUrl = 'https://www.openbadges.me/designer.html?origin={{ _p.web }}';
                    designerUrl = designerUrl + '&email={{ platformAdminEmail }}';
                    designerUrl = designerUrl + '&close=true';
                    designerUrl = designerUrl + '&hidePublish=true';

                    var windowOptions = 'width=1200,height=680,location=0,menubar=0,status=0,toolbar=0';
                    designer = window.open(designerUrl, '', windowOptions);
                });

                $('#image').on('change', function () {
                    var self = this;

                    if (self.files.length > 0) {
                        var image = self.files[0];

                        if (!image.type.match(/image.*/)) {
                            return;
                        }

                        var fileReader = new FileReader();
                        fileReader.onload = function (e) {
                            $('#badge-preview').attr('src', e.target.result);
                            $('#badge-container').removeClass('hide');
                        };
                        fileReader.readAsDataURL(image);
                    }
                });
            });
        })();
    </script>
    <div class="span12">
        <h1 class="page-header">{{ 'Badges' | get_lang }}</h1>
        <ul class="nav nav-tabs">
            <li>
                <a href="{{ _p.web_main }}admin/openbadges/index.php">{{ 'Home' | get_lang }}</a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/openbadges/issuer.php">{{ 'IssuerInfo' | get_lang }}</a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/openbadges/list.php">{{ 'Skills' | get_lang }}</a>
            </li>
            <li class="active">
                <a href="{{ _p.web_main }}admin/openbadges/create.php">{{ 'Edit' | get_lang }}</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active">
                <div class="row">
                    <div class="span3">
                        <p>Design a new badge. Download from the design tool. And Upload in your platform.</p>
                        <p>
                            <button id="btn-open-designer" class="btn btn-info btn-large btn-block" type="button">{{ 'DesignNewBadge' | get_lang }}</button>
                        </p>
                        <hr>
                        <div class="well well-small {{ skill.icon ? '' : 'hide' }}" id="badge-container">
                            <img id="badge-preview" alt="{{ 'BadgePreview' | get_lang }}" src="{{ skill.icon ? [_p.web_main, skill.icon] | join('') : '' }}">
                        </div>
                    </div>
                    <div class="span9">
                        <form action="{{ _p.web_self_query_vars }}" class="form-horizontal" method="post" enctype="multipart/form-data">
                            <fieldset>
                                <legend>{{ 'SkillInfo' | get_lang }}</legend>
                                <div class="control-group">
                                    <label class="control-label" for="name">{{ 'Name' | get_lang }}</label>
                                    <div class="controls">
                                        <input type="text" name="name" id="name" class="input-xxlarge" value="{{ skill.name }}">
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label" for="name">{{ 'Description' | get_lang }}</label>
                                    <div class="controls">
                                        <textarea name="description" id="description" class="input-xxlarge" rows="4">{{ skill.description }}</textarea>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label" for="image">{{ 'Image' | get_lang }}</label>
                                    <div class="controls">
                                        <input type="file" name="image" id="image" class="input-xxlarge" accept="image/*">
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label" for="criteria">{{ 'Criteria' | get_lang }}</label>
                                    <div class="controls">
                                        <textarea name="criteria" id="criteria" class="input-xxlarge" rows="10">{{ skill.criteria }}</textarea>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">{{ 'Create'| get_lang }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endblock %}

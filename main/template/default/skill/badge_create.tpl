<script>
    (function () {
        var designer = null;
        $(document).on('ready', function () {
            $('.help-badges').tooltip();
            $('.help-badges-img').tooltip();
        });

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
<div class="col-md-12">

    <div class="openbadges-tabs">
        <ul class="nav nav-tabs">
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge.php">{{ 'Home' | get_lang }}</a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge_list.php">{{ "CurrentBadges" | get_lang }}</a>
            </li>
            <li class="active">
                <a href="#">{{ 'Edit' | get_lang }}</a>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane active">
            <div class="openbadges-introduction">
                <div class="row">
                    <div class="col-md-12">
                        <div class="block-edit">
                            <div class="block-title">{{ 'SkillInfo' | get_lang }}</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-9">
                        <form action="{{ _p.web_self_query_vars }}" class="form-horizontal" method="post" enctype="multipart/form-data">
                            <fieldset>

                                <div class="form-group">
                                    <label class="col-sm-2 control-label" for="name">{{ 'Name' | get_lang }}</label>
                                    <div class="col-sm-10">
                                        <input type="text" name="name" id="name" class="form-control" value="{{ skill.name }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" for="name">{{ 'Description' | get_lang }}</label>
                                    <div class="col-sm-10">
                                        <textarea name="description" id="description" class="form-control" rows="4">{{ skill.description }}</textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" for="image">{{ 'Image' | get_lang }}</label>
                                    <div class="col-sm-10">
                                        <input data-placement="left" data-toggle="tooltip" title="{{ "BadgeMeasuresXPixelsInPNG" | get_lang }}" type="file" name="image" id="image" class="help-badges-img" accept="image/*">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" for="criteria">{{ 'CriteriaToEarnTheBadge' | get_lang }}</label>
                                    <div class="col-sm-10">
                                        <textarea name="criteria" id="criteria" class="form-control" rows="10">{{ skill.criteria }}</textarea>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <button type="submit" class="btn btn-primary btn-large"><em class="fa fa-floppy-o"></em> {{ 'SaveBadge'| get_lang }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <div class="create-openbadges">
                            <button id="btn-open-designer" class="help-badges btn btn-primary btn-large btn-block" data-toggle="tooltip" data-placement="bottom" title="{{ 'DesignANewBadgeComment' | get_lang }}" type="button">
                                <em class="fa fa-plus"></em> {{ 'DesignNewBadge' | get_lang }}
                            </button>
                        </div>
                        <p class="openbadges-text">{{'BadgePreview' | get_lang }}</p>
                        <div class="openbadges-img {{ skill.icon ? '' : 'hide' }}" id="badge-container">
                            <img id="badge-preview" alt="{{ 'BadgePreview' | get_lang }}" src="{{ skill.icon ? skill.web_icon_path : '' }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

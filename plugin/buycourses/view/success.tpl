<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="span12">
        <div id="course_category_well" class="well span3">
            <ul class="nav nav-list">
                <li class="nav-header"><h4>{{ 'UserInformation'|get_plugin_lang('BuyCoursesPlugin') }}:</h4></li>
                <li class="nav-header">{{ 'Name'|get_lang }}:</li>
                <li><h5>{{ name }}</h5></li>
                <li class="nav-header">{{ 'User'|get_lang }}:</li>
                <li><h5>{{ user }}</h5></li>
                <li class="nav-header">{{ 'Email'|get_lang }}:</li>
                <li><h5>{{ email }}</h5></li>
                <br/>
            </ul>
        </div>

        <br/><br/>

        <div class="well_border span8">
            <div class="row">
                <div class="span">
                    <div class="thumbnail">
                        <a class="ajax" rel="gb_page_center[778]" title=""
                           href="{{ server }}plugin/buycourses/function/ajax.php?code={{ course.code }}">
                            <img alt="" src="{{ server }}{{ course.course_img }}">
                        </a>
                    </div>
                </div>
                <div class="span4">
                    <div class="categories-course-description">
                        <h3>{{ course.title }}</h3>
                        <h5>{{ 'Teacher'|get_lang }}: {{ course.teacher }}</h5>
                    </div>
                </div>
                <div class="span right">
                    <div class="sprice right">{{ course.price }} {{ currency }}</div>
                    <div class="cleared"></div>
                    <div class="btn-toolbar right">
                        <a class="ajax btn btn-primary" title=""
                           href="{{ server }}plugin/buycourses/function/ajax.php?code={{ course.code }}">{{'Description'|get_lang }}</a>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cleared"></div>
    <hr/>
    <div align="center">
        <div class="confirmation-message">{{ 'PayPalPaymentOKPleaseConfirm'|get_plugin_lang('BuyCoursesPlugin') }}</div>
        <br />
        <form method="post" name="frmConfirm" action="../src/success.php">
            <input type="hidden" name="paymentOption" value="PayPal"/>
            <div class="btn_next">
                <input class="btn btn-success" type="submit" name="Confirm" value="{{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}"/>
                <input class="btn btn-danger" type="button" name="Cancel" value="{{ 'CancelOrder'|get_plugin_lang('BuyCoursesPlugin') }}" id="cancel_order"/>
            </div>
        </form>
    </div>
    <div class="cleared"></div>
</div>

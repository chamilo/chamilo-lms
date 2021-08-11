<link href="https://fonts.googleapis.com/css?family=Gugi" rel="stylesheet">
{% if launch.isDeepLinkLaunch %}
    <div class="dl-config">
        <h1>Pick a Difficulty</h1>
        <ul>
            <li><a href="{{ _p.web_plugin }}lti_provider/web/configure.php?diff=easy&launch_id={{ launch.getLaunchId }}">Easy</a></li>
            <li><a href="{{ _p.web_plugin }}lti_provider/web/configure.php?diff=normal&launch_id={{ launch.getLaunchId }}">Normal</a></li>
            <li><a href="{{ _p.web_plugin }}lti_provider/web/configure.php?diff=hard&launch_id={{ launch.getLaunchId }}">Hard</a></li>
        </ul>
    </div>
{% else %}

    <div id="game-screen">
        <div style="position:absolute;width:1000px;margin-left:-500px;left:50%; display:block">
            <div id="scoreboard" style="position:absolute; right:0; width:200px; height:486px">
                <h3 style="margin-left:12px;">Scoreboard - {{ courseCode }}</h3>
                <table id="leadertable" style="margin-left:12px;">
                </table>
            </div>
            <canvas id="breakoutbg" width="800" height="500" style="position:absolute;left:0;border:0;">
            </canvas>
            <canvas id="breakout" width="800" height="500" style="position:absolute;left:0;">
            </canvas>
        </div>
    </div>
    <div class="clearfix"></div>
    <button onclick="location.reload()" class="btn btn-sm" id="btn-again">Play Again</button>
    <script>
      var curr_diff = "{{ diff }}";
      var curr_user_name = "{{ username }}";
      var launch_id = "{{ launch.getLaunchId }}";
    </script>
    <script type="text/javascript" src="{{ _p.web_plugin }}lti_provider/web/static/breakout.js" charset="utf-8"></script>

{% endif %}
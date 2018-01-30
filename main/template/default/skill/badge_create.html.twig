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
<div class="row">
    <div class="col-md-9">
        <form action="{{ _p.web_self_query_vars }}" class="form-horizontal" method="post" enctype="multipart/form-data">
            <legend>
                {{ skill.name }}
            </legend>
            <fieldset>
                <div class="form-group">
                    <label class="col-sm-2 control-label" for="image">{{ 'Image' | get_lang }}</label>
                    <div class="col-sm-10">
                        <input data-placement="left" data-toggle="tooltip" title="{{ "BadgeMeasuresXPixelsInPNG" | get_lang }}" type="file" name="image" id="image" class="help-badges-img" accept="image/*">
                    </div>
                </div>
                <div class="form-group collapse" id="badge-studio-frame">
                    <label class="col-sm-2 control-label" for="criteria"></label>
                    <div class="col-sm-10">
                        <h1 class="title">Badge Studio</h1>
                        <div class="" id="studio">
                            <div id="input">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h3 class="label"><label for="studio-mask">{{ "Templates" | get_lang }}</label></h3>
                                        <select name="template" class="form-control" id="studio-template" data-path="{{ badge_studio.templates }}">
                                            <option value="template-1">{{ "Template" | get_lang }} 1</option>
                                            <option value="template-2">{{ "Template" | get_lang }} 2</option>
                                            <option value="template-3">{{ "Template" | get_lang }} 3</option>
                                        </select>
                                        <h3 class="label"><label for="studio-mask">{{ "Palettes" | get_lang }}</label></h3>
                                        <select name="palette" class="form-control" id="studio-palette">
                                            <option value="palette-1"
                                                    data-color-background="#CE001F"
                                                    data-color-stitching="#FFF"
                                                    data-color-border="#4C4F53"
                                                    data-color-detail="#999"
                                                    data-color-glyph="#FFF">{{ "Palette" | get_lang }} 1</option>
                                            <option value="palette-2"
                                                    data-color-background="#04A"
                                                    data-color-stitching="#0AE"
                                                    data-color-border="#0AE"
                                                    data-color-detail="#FFF"
                                                    data-color-glyph="#FFF">{{ "Palette" | get_lang }} 2</option>
                                            <option value="palette-3"
                                                    data-color-background="#11458B"
                                                    data-color-stitching="#3EB48D"
                                                    data-color-border="#3EB48D"
                                                    data-color-detail="#FFF"
                                                    data-color-glyph="#FFF">{{ "Palette" | get_lang }} 3</option>
                                        </select>
                                        <br />
                                        <h3 class="label"><label for="studio-mask">{{ "Colors" | get_lang }}</label></h3>
                                        <div id="custom-palette"></div>
                                    </div>
                                    <div class="col-md-8">
                                        <div id="output"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <h3 class="label"><label for="studio-mask">{{ "Mask" | get_lang }}</label></h3>
                                        <p class="item">
                                            <select name="mask" class="form-control" id="studio-mask" data-path="{{ badge_studio.masks }}">
                                                <option value="">{{ "None" | get_lang }}</option>
                                                <option value="lines">{{ "Lines" | get_lang }}</option>
                                            </select>
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <h3 class="label"><label for="studio-options">{{ "Options" | get_lang }}</label></h3>
                                        <p class="item" id="options">
                                            <i>None</i>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h3 class="label"><label for="studio-glyph">{{ "Icon" | get_lang }}</label></h3>
                                        <p class="item">
                                            <select name="glyph" class="form-control" id="studio-glyph">
                                                <option value="">None</option>
                                                <option value="ambulance">Ambulance</option>
                                                <option value="anchor">Anchor</option>
                                                <option value="android">Android</option>
                                                <option value="angle-down">Angle: Down</option>
                                                <option value="angle-double-down">Angle: Down (Double)</option>
                                                <option value="angle-left">Angle: Left</option>
                                                <option value="angle-double-left">Angle: Left (Double)</option>
                                                <option value="angle-right">Angle: Right</option>
                                                <option value="angle-double-right">Angle: Right (Double)</option>
                                                <option value="angle-up">Angle: Up</option>
                                                <option value="angle-double-up">Angle: Up (Double)</option>
                                                <option value="apple">Apple</option>
                                                <option value="archive">Archive</option>
                                                <option value="arrow-down">Arrow: Down</option>
                                                <option value="arrow-circle-down">Arrow: Down (Circle)</option>
                                                <option value="arrow-circle-o-down">Arrow: Down (Circle-O)</option>
                                                <option value="arrow-left">Arrow (Left)</option>
                                                <option value="arrow-circle-left">Arrow: Left (Circle)</option>
                                                <option value="arrow-circle-o-left">Arrow: Left (Circle-O)</option>
                                                <option value="arrow-right">Arrow: Right</option>
                                                <option value="arrow-circle-right">Arrow: Right (Circle)</option>
                                                <option value="arrow-circle-o-right">Arrow: Right (Circle-O)</option>
                                                <option value="arrow-up">Arrow: Up</option>
                                                <option value="arrow-circle-up">Arrow: Up (Circle)</option>
                                                <option value="arrow-circle-o-up">Arrow: Up (Circle-O)</option>
                                                <option value="arrows">Arrows</option>
                                                <option value="arrows-alt">Arrows (Alt)</option>
                                                <option value="arrows-h">Arrows: Horizontal</option>
                                                <option value="arrows-v">Arrows: Vertical</option>
                                                <option value="asterisk">Asterisk</option>
                                                <option value="automobile">Automobile</option>
                                                <option value="backward">Backward</option>
                                                <option value="ban">Ban</option>
                                                <option value="bank">Bank</option>
                                                <option value="bar-chart-o">Bar Chart (O)</option>
                                                <option value="barcode">Barcode</option>
                                                <option value="bars">Bars</option>
                                                <option value="beer">Beer</option>
                                                <option value="behance">Behance</option>
                                                <option value="behance-square">Behance (Square)</option>
                                                <option value="bell">Bell</option>
                                                <option value="bell-o">Bell (O)</option>
                                                <option value="bitbucket">BitBucket</option>
                                                <option value="bitbucket-square">BitBucket (Square)</option>
                                                <option value="bitcoin">Bitcoin</option>
                                                <option value="bold">Bold</option>
                                                <option value="bolt">Bolt</option>
                                                <option value="bomb">Bomb</option>
                                                <option value="book">Book</option>
                                                <option value="bookmark">Bookmark</option>
                                                <option value="bookmark-o">Bookmark (O)</option>
                                                <option value="briefcase">Briefcase</option>
                                                <option value="btc">BTC</option>
                                                <option value="bug">Bug</option>
                                                <option value="building">Building</option>
                                                <option value="building-o">Building (O)</option>
                                                <option value="bullhorn">Bullhorn</option>
                                                <option value="bullseye">Bullseye</option>
                                                <option value="cab">Cab</option>
                                                <option value="calendar">Calendar</option>
                                                <option value="calendar-o">Calendar (O)</option>
                                                <option value="camera">Camera</option>
                                                <option value="camera-retro">Camera (Retro)</option>
                                                <option value="car">Car</option>
                                                <option value="caret-down">Caret: Down</option>
                                                <option value="caret-square-o-down">Caret: Down (Square-O)</option>
                                                <option value="caret-left">Caret: Left</option>
                                                <option value="caret-square-o-left">Caret: Left (Square-O)</option>
                                                <option value="caret-right">Caret: Right</option>
                                                <option value="caret-square-o-right">Caret: Right (Square-O)</option>
                                                <option value="caret-up">Caret: Up</option>
                                                <option value="caret-square-o-up">Caret: Up (Square-O)</option>
                                                <option value="certificate">Certificate</option>
                                                <option value="chain">Chain</option>
                                                <option value="chain-broken">Chain (Broken)</option>
                                                <option value="check">Check</option>
                                                <option value="check-circle">Check (Circle)</option>
                                                <option value="check-circle-o">Check (Circle-O)</option>
                                                <option value="check-square">Check (Square)</option>
                                                <option value="check-square-o">Check (Square-O)</option>
                                                <option value="chevron-down">Chevron: Down</option>
                                                <option value="chevron-circle-down">Chevron: Down (Circle)</option>
                                                <option value="chevron-left">Chevron: Left</option>
                                                <option value="chevron-circle-left">Chevron: Left (Circle)</option>
                                                <option value="chevron-right">Chevron: Right</option>
                                                <option value="chevron-circle-right">Chevron: Right (Circle)</option>
                                                <option value="chevron-up">Chevron: Up</option>
                                                <option value="chevron-circle-up">Chevron: Up (Circle)</option>
                                                <option value="child">Child</option>
                                                <option value="circle">Circle</option>
                                                <option value="circle-o">Circle (O)</option>
                                                <option value="circle-o-notch">Circle (O Notch)</option>
                                                <option value="circle-thin">Circle (Thin)</option>
                                                <option value="clipboard">Clipboard</option>
                                                <option value="clock-o">Clock (O)</option>
                                                <option value="cloud">Cloud</option>
                                                <option value="cloud-download">Cloud: Download</option>
                                                <option value="cloud-upload">Cloud: Upload</option>
                                                <option value="cny">CNY</option>
                                                <option value="code">Code</option>
                                                <option value="code-fork">Code (Fork)</option>
                                                <option value="codepen">Codepen</option>
                                                <option value="coffee">Coffee</option>
                                                <option value="cog">Cog</option>
                                                <option value="cogs">Cogs</option>
                                                <option value="columns">Columns</option>
                                                <option value="comment">Comment</option>
                                                <option value="comment-o">Comment (O)</option>
                                                <option value="comments">Comments</option>
                                                <option value="comments-o">Comments (O)</option>
                                                <option value="compass">Compass</option>
                                                <option value="compress">Compress</option>
                                                <option value="copy">Copy</option>
                                                <option value="credit-card">Credit (Card)</option>
                                                <option value="crop">Crop</option>
                                                <option value="crosshairs">Crosshairs</option>
                                                <option value="css3">CSS3</option>
                                                <option value="cube">Cube</option>
                                                <option value="cubes">Cubes</option>
                                                <option value="cut">Cut</option>
                                                <option value="cutlery">Cutlery</option>
                                                <option value="dashboard">Dashboard</option>
                                                <option value="database">Database</option>
                                                <option value="dedent">Dedent</option>
                                                <option value="delicious">Delicious</option>
                                                <option value="desktop">Desktop</option>
                                                <option value="deviantart">Deviantart</option>
                                                <option value="digg">Digg</option>
                                                <option value="dollar">Dollar</option>
                                                <option value="dot-circle-o">Dot (Circle-O)</option>
                                                <option value="download">Download</option>
                                                <option value="dribbble">Dribbble</option>
                                                <option value="dropbox">Dropbox</option>
                                                <option value="drupal">Drupal</option>
                                                <option value="edit">Edit</option>
                                                <option value="eject">Eject</option>
                                                <option value="ellipsis-h">Ellipsis (Horizontal)</option>
                                                <option value="ellipsis-v">Ellipsis (Vertical)</option>
                                                <option value="empire">Empire</option>
                                                <option value="envelope">Envelope</option>
                                                <option value="envelope-o">Envelope (O)</option>
                                                <option value="envelope-square">Envelope (Square)</option>
                                                <option value="eraser">Eraser</option>
                                                <option value="eur">EUR</option>
                                                <option value="euro">Euro</option>
                                                <option value="exchange">Exchange</option>
                                                <option value="exclamation">Exclamation</option>
                                                <option value="exclamation-circle">Exclamation (Circle)</option>
                                                <option value="exclamation-triangle">Exclamation (Triangle)</option>
                                                <option value="expand">Expand</option>
                                                <option value="external-link">External Link</option>
                                                <option value="external-link-square">External Link (Square)</option>
                                                <option value="eye">Eye</option>
                                                <option value="eye-slash">Eye (Slash)</option>
                                                <option value="facebook">Facebook</option>
                                                <option value="facebook-square">Facebook (Square)</option>
                                                <option value="fast-backward">Fast Rewind</option>
                                                <option value="fast-forward">Fast Forward</option>
                                                <option value="fax">Fax</option>
                                                <option value="female">Female</option>
                                                <option value="fighter-jet">Fighter Jet</option>
                                                <option value="file">File</option>
                                                <option value="file-o">File (O)</option>
                                                <option value="files-o">Files (O)</option>
                                                <option value="file-archive-o">File: Archive (O)</option>
                                                <option value="file-audio-o">File: Audio (O)</option>
                                                <option value="file-code-o">File: Code (O)</option>
                                                <option value="file-excel-o">File: Excel (O)</option>
                                                <option value="file-image-o">File: Image (O)</option>
                                                <option value="file-movie-o">File: Movie (O)</option>
                                                <option value="file-pdf-o">File: PDF (O)</option>
                                                <option value="file-photo-o">File: Photo (O)</option>
                                                <option value="file-picture-o">File: Picture (O)</option>
                                                <option value="file-powerpoint-o">File: Powerpoint (O)</option>
                                                <option value="file-sound-o">File: Sound (O)</option>
                                                <option value="file-text">File: Text</option>
                                                <option value="file-text-o">File: Text (O)</option>
                                                <option value="file-video-o">File: Video (O)</option>
                                                <option value="file-word-o">File: Word (O)</option>
                                                <option value="file-zip-o">File: Zip (O)</option>
                                                <option value="film">Film</option>
                                                <option value="filter">Filter</option>
                                                <option value="fire">Fire</option>
                                                <option value="fire-extinguisher">Fire Extinguisher</option>
                                                <option value="flag">Flag</option>
                                                <option value="flag-o">Flag (O)</option>
                                                <option value="flag-checkered">Flag: Checkered</option>
                                                <option value="flash">Flash</option>
                                                <option value="flask">Flask</option>
                                                <option value="flickr">Flickr</option>
                                                <option value="floppy-o">Floppy (O)</option>
                                                <option value="folder">Folder</option>
                                                <option value="folder-o">Folder (O)</option>
                                                <option value="folder-open">Folder: Open</option>
                                                <option value="folder-open-o">Folder: Open (O)</option>
                                                <option value="font">Font</option>
                                                <option value="forward">Forward</option>
                                                <option value="foursquare">Foursquare</option>
                                                <option value="frown-o">Frown (O)</option>
                                                <option value="gamepad">Gamepad</option>
                                                <option value="gavel">Gavel</option>
                                                <option value="gbp">GBP</option>
                                                <option value="ge">Ge</option>
                                                <option value="gear">Gear</option>
                                                <option value="gears">Gears</option>
                                                <option value="gift">Gift</option>
                                                <option value="git">Git</option>
                                                <option value="git-square">Git (Square)</option>
                                                <option value="github">GitHub</option>
                                                <option value="github-alt">GitHub (Alt)</option>
                                                <option value="github-square">GitHub (Square)</option>
                                                <option value="gittip">GitTip</option>
                                                <option value="glass">Glass</option>
                                                <option value="globe">Globe</option>
                                                <option value="google">Google</option>
                                                <option value="google-plus">Google Plus</option>
                                                <option value="google-plus-square">Google Plus (Square)</option>
                                                <option value="graduation-cap">Graduation (Cap)</option>
                                                <option value="group">Group</option>
                                                <option value="h-square">H (Square)</option>
                                                <option value="hacker-news">Hacker News</option>
                                                <option value="hand-o-down">Hand: Down (O)</option>
                                                <option value="hand-o-left">Hand: Left (O)</option>
                                                <option value="hand-o-right">Hand: Right (O)</option>
                                                <option value="hand-o-up">Hand: Up (O)</option>
                                                <option value="hdd-o">HDD (O)</option>
                                                <option value="header">Header</option>
                                                <option value="headphones">Headphones</option>
                                                <option value="heart">Heart</option>
                                                <option value="heart-o">Heart (O)</option>
                                                <option value="history">History</option>
                                                <option value="home">Home</option>
                                                <option value="hospital-o">Hospital (O)</option>
                                                <option value="html5">HTML5</option>
                                                <option value="image">Image</option>
                                                <option value="inbox">Inbox</option>
                                                <option value="indent">Indent</option>
                                                <option value="info">Info</option>
                                                <option value="info-circle">Info (Circle)</option>
                                                <option value="inr">Inr</option>
                                                <option value="instagram">Instagram</option>
                                                <option value="institution">Institution</option>
                                                <option value="italic">Italic</option>
                                                <option value="joomla">Joomla</option>
                                                <option value="jpy">JPY</option>
                                                <option value="jsfiddle">JSFiddle</option>
                                                <option value="key">Key</option>
                                                <option value="keyboard-o">Keyboard (O)</option>
                                                <option value="krw">KRW</option>
                                                <option value="language">Language</option>
                                                <option value="laptop">Laptop</option>
                                                <option value="leaf">Leaf</option>
                                                <option value="legal">Legal</option>
                                                <option value="lemon-o">Lemon (O)</option>
                                                <option value="level-down">Level Down</option>
                                                <option value="level-up">Level Up</option>
                                                <option value="life-bouy">Life Bouy</option>
                                                <option value="life-ring">Life Ring</option>
                                                <option value="life-saver">Life Saver</option>
                                                <option value="lightbulb-o">Lightbulb (O)</option>
                                                <option value="link">Link</option>
                                                <option value="linkedin">LinkedIn</option>
                                                <option value="linkedin-square">LinkedIn (Square)</option>
                                                <option value="linux">Linux</option>
                                                <option value="list">List</option>
                                                <option value="list-alt">List (Alt)</option>
                                                <option value="list-ol">List (Ol)</option>
                                                <option value="list-ul">List (Ul)</option>
                                                <option value="location-arrow">Location (Arrow)</option>
                                                <option value="lock">Lock</option>
                                                <option value="long-arrow-down">Long Arrow Down</option>
                                                <option value="long-arrow-left">Long Arrow Left</option>
                                                <option value="long-arrow-right">Long Arrow Right</option>
                                                <option value="long-arrow-up">Long Arrow Up</option>
                                                <option value="magic">Magic</option>
                                                <option value="magnet">Magnet</option>
                                                <option value="mail-forward">Mail: Forward</option>
                                                <option value="mail-reply">Mail: Reply</option>
                                                <option value="mail-reply-all">Mail: Reply All</option>
                                                <option value="male">Male</option>
                                                <option value="map-marker">Map Marker</option>
                                                <option value="maxcdn">MaxCDN</option>
                                                <option value="medkit">Medkit</option>
                                                <option value="meh-o">Meh (O)</option>
                                                <option value="microphone">Microphone</option>
                                                <option value="microphone-slash">Microphone (Slash)</option>
                                                <option value="minus">Minus</option>
                                                <option value="minus-circle">Minus (Circle)</option>
                                                <option value="minus-square">Minus (Square)</option>
                                                <option value="minus-square-o">Minus (Square O)</option>
                                                <option value="mobile">Mobile</option>
                                                <option value="mobile-phone">Mobile Phone</option>
                                                <option value="money">Money</option>
                                                <option value="moon-o">Moon (O)</option>
                                                <option value="mortar-board">Mortar Board</option>
                                                <option value="music">Music</option>
                                                <option value="navicon">Navicon</option>
                                                <option value="openid">Openid</option>
                                                <option value="outdent">Outdent</option>
                                                <option value="pagelines">Pagelines</option>
                                                <option value="paper-plane">Paper Plane</option>
                                                <option value="paper-plane-o">Paper Plane (O)</option>
                                                <option value="paperclip">Paper Clip</option>
                                                <option value="paragraph">Paragraph</option>
                                                <option value="paste">Paste</option>
                                                <option value="pause">Pause</option>
                                                <option value="paw">Paw</option>
                                                <option value="pencil">Pencil</option>
                                                <option value="pencil-square">Pencil (Square)</option>
                                                <option value="pencil-square-o">Pencil (Square-O)</option>
                                                <option value="phone">Phone</option>
                                                <option value="phone-square">Phone (Square)</option>
                                                <option value="photo">Photo</option>
                                                <option value="picture-o">Picture (O)</option>
                                                <option value="pied-piper">Pied Piper</option>
                                                <option value="pied-piper-alt">Pied Piper (Alt)</option>
                                                <option value="pied-piper-square">Pied Piper (Square)</option>
                                                <option value="pinterest">Pinterest</option>
                                                <option value="pinterest-square">Pinterest (Square)</option>
                                                <option value="plane">Plane</option>
                                                <option value="play">Play</option>
                                                <option value="play-circle">Play (Circle)</option>
                                                <option value="play-circle-o">Play (Circle-O)</option>
                                                <option value="plus">Plus</option>
                                                <option value="plus-circle">Plus (Circle)</option>
                                                <option value="plus-square">Plus (Square)</option>
                                                <option value="plus-square-o">Plus (Square-O)</option>
                                                <option value="power-off">Power Off</option>
                                                <option value="print">Print</option>
                                                <option value="puzzle-piece">Puzzle Piece</option>
                                                <option value="qq">Qq</option>
                                                <option value="qrcode">QR Code</option>
                                                <option value="question">Question</option>
                                                <option value="question-circle">Question (Circle)</option>
                                                <option value="quote-left">Quote: Left</option>
                                                <option value="quote-right">Quote: Right</option>
                                                <option value="ra">Ra</option>
                                                <option value="random">Random</option>
                                                <option value="rebel">Rebel</option>
                                                <option value="recycle">Recycle</option>
                                                <option value="reddit">Reddit</option>
                                                <option value="reddit-square">Reddit (Square)</option>
                                                <option value="refresh">Refresh</option>
                                                <option value="renren">Renren</option>
                                                <option value="reorder">Reorder</option>
                                                <option value="repeat">Repeat</option>
                                                <option value="reply">Reply</option>
                                                <option value="reply-all">Reply All</option>
                                                <option value="retweet">Retweet</option>
                                                <option value="rmb">Rmb</option>
                                                <option value="road">Road</option>
                                                <option value="rocket">Rocket</option>
                                                <option value="rotate-left">Rotate Left</option>
                                                <option value="rotate-right">Rotate Right</option>
                                                <option value="rouble">Rouble</option>
                                                <option value="rss">RSS</option>
                                                <option value="rss-square">RSS (Square)</option>
                                                <option value="rub">Rub</option>
                                                <option value="ruble">Ruble</option>
                                                <option value="rupee">Rupee</option>
                                                <option value="save">Save</option>
                                                <option value="scissors">Scissors</option>
                                                <option value="search">Search</option>
                                                <option value="search-minus">Search: Minus</option>
                                                <option value="search-plus">Search: Plus</option>
                                                <option value="send">Send</option>
                                                <option value="send-o">Send (O)</option>
                                                <option value="share">Share</option>
                                                <option value="share-alt">Share (Alt)</option>
                                                <option value="share-alt-square">Share (Alt Square)</option>
                                                <option value="share-square">Share (Square)</option>
                                                <option value="share-square-o">Share (Square-O)</option>
                                                <option value="shield">Shield</option>
                                                <option value="shopping-cart">Shopping Cart</option>
                                                <option value="sign-in">Sign In</option>
                                                <option value="sign-out">Sign Out</option>
                                                <option value="signal">Signal</option>
                                                <option value="sitemap">Sitemap</option>
                                                <option value="skype">Skype</option>
                                                <option value="slack">Slack</option>
                                                <option value="sliders">Sliders</option>
                                                <option value="smile-o">Smile (O)</option>
                                                <option value="sort">Sort</option>
                                                <option value="sort-asc">Sort: Asc</option>
                                                <option value="sort-desc">Sort: Desc</option>
                                                <option value="sort-down">Sort: Down</option>
                                                <option value="sort-up">Sort: Up</option>
                                                <option value="sort-alpha-asc">Sort: Alpha Asc</option>
                                                <option value="sort-alpha-desc">Sort: Alpha Desc</option>
                                                <option value="sort-amount-asc">Sort: Amount Asc</option>
                                                <option value="sort-amount-desc">Sort: Amount Desc</option>
                                                <option value="sort-numeric-asc">Sort: Numeric Asc</option>
                                                <option value="sort-numeric-desc">Sort: Numeric Desc</option>
                                                <option value="soundcloud">Soundcloud</option>
                                                <option value="space-shuttle">Space Shuttle</option>
                                                <option value="spinner">Spinner</option>
                                                <option value="spoon">Spoon</option>
                                                <option value="spotify">Spotify</option>
                                                <option value="square">Square</option>
                                                <option value="square-o">Square (O)</option>
                                                <option value="stack-exchange">Stack Exchange</option>
                                                <option value="stack-overflow">Stack Overflow</option>
                                                <option value="star">Star</option>
                                                <option value="star-half">Star (Half)</option>
                                                <option value="star-half-empty">Star (Half Empty)</option>
                                                <option value="star-half-full">Star (Half Full)</option>
                                                <option value="star-half-o">Star (Half O)</option>
                                                <option value="star-o">Star (O)</option>
                                                <option value="steam">Steam</option>
                                                <option value="steam-square">Steam (Square)</option>
                                                <option value="step-backward">Step Backward</option>
                                                <option value="step-forward">Step Forward</option>
                                                <option value="stethoscope">Stethoscope</option>
                                                <option value="stop">Stop</option>
                                                <option value="strikethrough">Strikethrough</option>
                                                <option value="stumbleupon">Stumbleupon</option>
                                                <option value="stumbleupon-circle">Stumbleupon (Circle)</option>
                                                <option value="subscript">Subscript</option>
                                                <option value="suitcase">Suitcase</option>
                                                <option value="sun-o">Sun (O)</option>
                                                <option value="superscript">Superscript</option>
                                                <option value="support">Support</option>
                                                <option value="table">Table</option>
                                                <option value="tablet">Tablet</option>
                                                <option value="tachometer">Tachometer</option>
                                                <option value="tag">Tag</option>
                                                <option value="tags">Tags</option>
                                                <option value="tasks">Tasks</option>
                                                <option value="taxi">Taxi</option>
                                                <option value="tencent-weibo">Tencent Weibo</option>
                                                <option value="terminal">Terminal</option>
                                                <option value="text-height">Text Height</option>
                                                <option value="text-width">Text Width</option>
                                                <option value="th">Th</option>
                                                <option value="th-large">Th (Large)</option>
                                                <option value="th-list">Th (List)</option>
                                                <option value="thumb-tack">Thumb Tack</option>
                                                <option value="thumbs-down">Thumbs Down</option>
                                                <option value="thumbs-o-down">Thumbs Down (O)</option>
                                                <option value="thumbs-up">Thumbs Up</option>
                                                <option value="thumbs-o-up">Thumbs Up (O)</option>
                                                <option value="ticket">Ticket</option>
                                                <option value="times">Times</option>
                                                <option value="times-circle">Times (Circle)</option>
                                                <option value="times-circle-o">Times (Circle O)</option>
                                                <option value="tint">Tint</option>
                                                <option value="toggle-down">Toggle Down</option>
                                                <option value="toggle-left">Toggle Left</option>
                                                <option value="toggle-right">Toggle Right</option>
                                                <option value="toggle-up">Toggle Up</option>
                                                <option value="trash-o">Trash (O)</option>
                                                <option value="tree">Tree</option>
                                                <option value="trello">Trello</option>
                                                <option value="trophy">Trophy</option>
                                                <option value="truck">Truck</option>
                                                <option value="try">Try</option>
                                                <option value="tumblr">Tumblr</option>
                                                <option value="tumblr-square">Tumblr (Square)</option>
                                                <option value="turkish-lira">Turkish Lira</option>
                                                <option value="twitter">Twitter</option>
                                                <option value="twitter-square">Twitter (Square)</option>
                                                <option value="umbrella">Umbrella</option>
                                                <option value="underline">Underline</option>
                                                <option value="undo">Undo</option>
                                                <option value="university">University</option>
                                                <option value="unlink">Unlink</option>
                                                <option value="unlock">Unlock</option>
                                                <option value="unlock-alt">Unlock (Alt)</option>
                                                <option value="unsorted">Unsorted</option>
                                                <option value="upload">Upload</option>
                                                <option value="usd">USD</option>
                                                <option value="user">User</option>
                                                <option value="user-md">User (MD)</option>
                                                <option value="users">Users</option>
                                                <option value="video-camera">Video (Camera)</option>
                                                <option value="vimeo-square">Vimeo (Square)</option>
                                                <option value="vine">Vine</option>
                                                <option value="vk">Vk</option>
                                                <option value="volume-up">Volume Up</option>
                                                <option value="volume-down">Volume Down</option>
                                                <option value="volume-off">Volume Off</option>
                                                <option value="warning">Warning</option>
                                                <option value="wechat">Wechat</option>
                                                <option value="weibo">Weibo</option>
                                                <option value="weixin">Weixin</option>
                                                <option value="wheelchair">Wheelchair</option>
                                                <option value="windows">Windows</option>
                                                <option value="won">Won</option>
                                                <option value="wordpress">Wordpress</option>
                                                <option value="wrench">Wrench</option>
                                                <option value="xing">Xing</option>
                                                <option value="xing-square">Xing (Square)</option>
                                                <option value="yahoo">Yahoo</option>
                                                <option value="yen">Yen</option>
                                                <option value="youtube">Youtube</option>
                                                <option value="youtube-play">Youtube (Play)</option>
                                                <option value="youtube-square">Youtube (Square)</option>
                                            </select>
                                        </p>
                                        <h3 class="label"><label for="size-glyph">{{ "Size" | get_lang }}</label></h3>
                                        <p class="item">
                                            <select name="size-glyph" class="form-control" id="size-glyph">
                                                <option value="big">{{ "Big" | get_lang }}</option>
                                                <option value="medium" selected>{{ "Medium" | get_lang }}</option>
                                                <option value="small">{{ "Small" | get_lang }}</option>
                                            </select>
                                        </p>
                                    </div>
                                    <div class="col-md-12 text-center">
                                        <a id="set-custom-badge" class="btn btn-primary"><em class="fa fa-check"></em> {{ 'UseThisBadge' | get_lang }}</a>
                                        <input type="hidden" id="badge_studio_image" name="badge_studio_image" >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <template id="glyph-selector-template">
                        <div id="glyph-selector" role="dialog" class="overlay hidden" aria-label="Select a glyph" tabIndex="0">
                            <div class="header">
                                <label class="title"></label>
                            </div>
                            <div class="panel">
                                <ul>
                                </ul>
                            </div>
                        </div>
                    </template>

                    <template id="glyph-selector-item-template">
                        <li>
                            <input type="radio" name="glyph-selector-item" class="hidden">
                            <label></label>
                        </li>
                    </template>

                    <template id="option-template">
                        <label>
                            <input type="checkbox">
                            <span>Label</span>
                        </label>
                    </template>

                    <template id="close-button-template">
                        <button type="button" class="close fa fa-times-circle-o" aria-label="Close"></button>
                    </template>

                    <template id="custom-color-template">
                        <label>
                            <input type="color">
                            <span>Label</span>
                        </label>
                    </template>
                </div>
            </fieldset>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-primary btn-large"><em class="fa fa-plus"></em>
                        {{ 'SaveBadge'| get_lang }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-md-3">
        <div class="openbadges-img" id="badge-container">
            <img id="badge-preview" class="img-responsive" alt="{{ 'BadgePreview' | get_lang }}" src="{{ skill.icon_big }}">
        </div>

        <div class="create-openbadges">
            <button id="btn-open-designer" class="help-badges btn btn-primary btn-large btn-block" data-toggle="tooltip" data-placement="bottom" title="{{ 'DesignANewBadgeComment' | get_lang }}" type="button">
                <em class="fa fa-pencil"></em> {{ 'DesignNewBadge' | get_lang }}
            </button>
        </div>
        <div class="create-openbadges">
            <button id="btn-open-badge-studio" class="help-badges btn btn-default btn-large btn-block" data-toggle="collapse" data-target="#badge-studio-frame" aria-expanded="false" aria-controls="badge-studio-frame" title="{{ 'DesignWithBadgeStudioComment' | get_lang }}" type="button">
                <em class="fa fa-cogs"></em> {{ 'DesignWithBadgeStudio' | get_lang }}
            </button>
        </div>

    </div>
</div>
{{ badge_studio.script_js }}
<script>
    $(document).ready(function() {
        $('#set-custom-badge').click(function () {
            var data = $('#raster').attr('src');
            $('#badge_studio_image').val(data);
            $('#badge-preview').attr('src', data);
            $('#badge-container').removeClass('hide');
        });

        $('#size-glyph').change(function () {
            window.size = $(this).val();
            updateGlyph();
        });
    })
</script>

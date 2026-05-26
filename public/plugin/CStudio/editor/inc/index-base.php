<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>C-Studio Editor</title>
    <link href="dist/css/grapes.min.css" rel="stylesheet">
    <link href="dist/grapesjs-preset-webpage.min.css" rel="stylesheet">
    <script src="dist/js/filestack-0.1.10.js"></script>
    <script src="dist/js/grapes.js"></script>
    <script src="dist/grapesjs-preset-webpage.min.js"></script>
    <script src="js/jquery.js"></script>
	  <script src="js/amplify.min.js"></script>
    <?php
      if (isset($_GET['id'])) {
          $idPage = $_GET['id'];
          echo '<script>var idPageHtml = '.$idPage.';</script>';
      }
    ?>
    <style>
      body,html {
        height: 100%;
        margin: 0;
      }
    </style>
  </head>
  <body style="background-color:#D8D8D8;" >
	
    <div id="gjs" style="height:0px; overflow:hidden">
      
      <div class="panel">
        
        <h1 class="welcome">Formation</h1>
        
        <div class="big-title">
         <img src="img/logomouse.svg" style="margin-bottom:-20px;" />
          <span>Titre de la formation</span>
        </div>
        
        <div class="description">
          This is a demo content from index.html. For the development, you shouldn't edit this file, instead you can
          copy and rename it to _index.html, on next server start the new file will be served, and it will be ignored by git.
        </div>

        <div class="row">
          <div class="cell">
            <img class="bandeImg" src="img/p2.jpg" />
          </div>
        </div>

        <div class="description">

            <h3>Public visé par la formation et prérequis</h3>
            <ul>
              <li>À qui s’adresse cette formation ?</li>
              <li>À qui s’adresse cette formation ?</li>
            </ul>
            
            <h3>Admission</h3>
            <ul>
              <li>Pour suivre la formation dans de bonnes conditions.</li>
            </ul>

            <h3>Objectifs de la formation </h3>
            <ul>
              <li>Lire et analyser les documents comptables et financiers</li>
              <li>Conduire un projet pour réaliser des travaux</li>
              <li>Maîtriser les outils</li>
            </ul>
           


          </div>

      </div>
      <style>
        .panel {
          width: 90%;
          max-width: 800px;
          border-radius: 3px;
          padding: 30px 20px;
          margin: 50px auto 0px;
		      margin-bottom:30px;
          background-color: white;
		      border:solid 1px gray;
          box-shadow: 0px 3px 10px 0px rgba(0,0,0,0.25);
          color: black;
          font: caption;
          font-weight: 100;
        }
	
        .welcome {
          text-align: center;
          font-weight: 100;
          margin: 0px;
        }

        .logo {
          width: 70px;
          height: 70px;
          vertical-align: middle;
        }

        .logo path {
          pointer-events: none;
          fill: none;
          stroke-linecap: round;
          stroke-width: 7;
          stroke: #fff
        }
        .bandeImg{
          width: 100%;
        }
        .big-title {
          text-align: center;
          font-size: 3.5rem;
          margin: 15px 0;
        }

        .description {
          text-align: justify;
          font-size: 1rem;
          line-height: 1.5rem;
        }
      </style>
      
    </div>
	
    <script type="text/javascript">
      var editor = grapesjs.init({
        height: '100%',
        showOffsets: 1,
        noticeOnUnload: 0,
        storageManager: { autoload: 0 },
        container: '#gjs',
        fromElement: true,

        plugins: ['gjs-preset-webpage'],
        pluginsOpts: {
          'gjs-preset-webpage': {}
        }
      });
    </script>

    <script src="js/events.js"></script>

	
  </body>
</html>

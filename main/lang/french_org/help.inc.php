<?php
/*
      +----------------------------------------------------------------------+
      | DOKEOS version 1.5.0 $Revision: 7366 $                                |      |
      +----------------------------------------------------------------------+
      |   $Id: help.inc.php 7366 2005-12-11 11:38:20Z d13tr1ch $       |
      +----------------------------------------------------------------------+
      |   This program is free software; you can redistribute it and/or      |
      |   modify it under the terms of the GNU General Public License        |
      |   as published by the Free Software Foundation; either version 2     |
      |   of the License, or (at your option) any later version.             |
      |                                                                      |
      |   This program is distributed in the hope that it will be useful,    |
      |   but WITHOUT ANY WARRANTY; without even the implied warranty of     |
      |   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
      |   GNU General Public License for more details.                       |
      |                                                                      |
      |   You should have received a copy of the GNU General Public License  |
      |   along with this program; if not, write to the Free Software        |
      |   Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA          |
      |   02111-1307, USA. The GNU GPL license is also available through     |
      |   the world-wide-web at http://www.gnu.org/copyleft/gpl.html         |
      +----------------------------------------------------------------------+
      | Authors: Thomas Depraetere <thomas.depraetere@dokeos.com>                |
      |          Hugues Peeters    <peeters@ipm.ucl.ac.be>                   |
      |          Christophe Gesché <gesche@ipm.ucl.ac.be>                    |
      +----------------------------------------------------------------------+
 */

// HELP

// help.php?open=For

$langHFor="Aide forums";
$langClose="Fermer la fenêtre";



// help.php?open=For

$langForContent="<p>Le forum est un outil de discussion asynchrone par écrit.
 A la différence de l'email, le forum situe la discussion dans un espace
 public ou semi-public (à plusieurs).</p><p>Pour utiliser l'outil de forum
 de Dokeos, les membres n'ont besoin que d'un simple navigateur web
 (Netscape, Explorer...), pas besoin d'outil de courriel (Eudora,
 Outlook...).</P>
 <p>Pour organiser les forums, cliquez sur 'administrer'.
 Les échanges sont organisés de façon hiérarchique selon l'arborescence
 suivante:</p><p><b>Catégorie > Forum > Sujet > Réponse</b></p>Pour
 permettre aux membres de discuter de façon structurée, il est
 indispensable d'organiser les échanges préalablement en catégories et
 forums (à eux de créer sujets et réponses). Par défaut, le forum contient
 uniquement la catégorie Public, un sujet d'exemple et un message exemple.
 Vous pouvez ajouter des forums dans la catégorie public, ou bien modifier
 son intitulé ou encore créer d'autres catégories dans lesquelles il vous
 faudra alors créer de nouveaux forums. Une catégorie qui ne contient aucun
 forum ne s'affiche pas et est inutilisable.</p>
 <b>Forums de groupes</b>
 <p>Pour créer des forums de groupes, utilisez l'outil Groupes et non l'outil Forums. Cela vous permettra de créer des forums privatifs (non accessibles aux membres des autres groupes) et de fournir simultanément un espace de documents aux groupes.</p>
 <p><b>Astuces pédagogiques</b></p>
 Un forum d'apprentissage n'est pas identique aux forums que l'on trouve habituellement sur internet. D'une part il n'est pas possible pour les étudiants/stagaires de modifier leurs messages une fois publiés car l'espace suit une logique d'archivage et peut être utilisé pour vérifier ce qui a été dit dans le passé. Par ailleurs, les forums Dokeos permettent certains usages particulièrement pertinents dans un contexte d'apprentissage. Ainsi certains responsables/responsables publient directement dans les forums leurs corrections:
 <ul><li>Un étudiant/stagiaire est invité à publier un rapport directement dans le forum,</li>
 <li>L'responsable le corrige en cliquant sur Editer (crayon jaune) puis introduit ses corrections à l'aide de l'éditeur graphique : couleur, soulignage etc.,</li>
 <li>Finalement, les autres étudiants/stagiaires profitent de la correction qui a été faite sur la production de l'un d'entre eux,</li>
 <li>Notez que le même principe peut être utilisé d'un étudiant à l'autre, mais il faudra alors copier/coller le message de son consdisciple car les étudiants/stagiaires ne peuvent éditer les messages des autres étudiants/stagiaires.<.li></ul>";



// help.php?open=Home

$langHHome="Aide Page d'accueil";

$langHomeContent="<p>La page d'accueil de votre espace présente une série d'outils : un texte d'introduction, une Description de l'espace, un outil de publication de Documents, etc. Cette page est modulaire. Vous pouvez masquer ou afficher chacun des outils.</p>
<b>Navigation</b>
<p>La navigation se fait soit au moyen du menu en arborescence situé sous la bannière de couleur, dans le coin supérieur gauche, soit au moyen des icônes permettant un accès direct aux outils et situées dans le coin supérieur droit. Que vous cliquiez à droite sur la maison ou à gauche sur le code de l'espace (toujours en majuscules), vous retournerez à la page d'accueil.</p>
<b>Méthodologie</b>
<p>Il importe de rendre votre espace dynamique afin de montrer aux participants qu'il y a quelqu'un derrière l'écran. Ainsi vous pouvez modifier régulièrement le texte d'introduction (en cliquant sur le crayon jaune) pour y signaler des événements ou rappeler des étapes de l'espace.</p>
<p>Pour construire votre espace, une manière classique de travailler est de procéder come suit:
<ol><li>Dans Propriétés de l'espace, cochez Accès : privé et Inscription : refusé afin d'interdire toute visite pendant la phase de fabrication de l'espace,</li>
<li>Affichez tous les outils en cliquant sur le lien gris 'Afficher' sous le nom des outils masqués dans le bas de l'écran,</li>
<li>Utilisez les outils pour 'remplir' votre espace de contenus, d'événements, de groupes, etc.,</li>
<li>Désactivez tous les outils,</li>
<li>Utilisez l'outil Parcours pour construire un itinéraire à travers les autres outils</li>
li>Rendez le parcours ainsi créé visible : il s'affichera sur la page d'accueil</li>
<li> Votre espace est terminé. Il présente un texte d'introduction suivi d'un lien portant le titre du parcours que vous avez créé. Cliquez sur 'Vue membre' pour voir l'espace du point de vue de celui qui le suit.<I></I></li></ol>";

$langHClar="Aide Dokeos";

$langClarContent="<p><b>Responsable, responsable</b></p>
<p>Dokeos est un système de gestion de la formation et de la connaissance. Il permet à des formateurs, des responsables de formation d'organiser des parcours d'apprentissage, de gérer des interactions avec des apprenants et de construire des contenus sans quitter le navigateur web.</p>
<p>Pour utiliser Dokeos en tant que formateur/responsable, vous devez disposer d'un identifiant et d'un mot de passe. Ceux-ci pourront être obtenus soit par auto-inscription (si votre portail le permet, un lien 'Inscription' apparaît sur sa page d'accueil) soit par votre administration si l'inscription est gérée de façon centralisée. Une fois en possession de votre identifiant et de votre mot de passe, introduisez-les dans le système, créez un espace (ou utilisez celui qui a été créé pour vous par votre administration) et familiarisez-vous avec les outils en déposant des documents, en composant des textes de description etc.</p>
<p>Dans votre espace, commencez par ouvrir Paramètres de l'espace et fermez-en l'accès le temps de concevoir le dispositif. Vous pouvez, si vous le souhaitez, inscrire un collègue comme co-responsable de votre espace pendant cette période, pour cela, si votre collègue n'est pas encore inscrit dans le portail, rendez-vous dans la rubrique Membres et inscrivez-le en cochant : 'Responsable'. S'il est déjà inscrit dans le système, ouvrez l'accès à l'inscription (dans Paramètres de l'espace) et demandez-lui de s'inscrire lui-même puis modifiez ses droits dans 'Membres' pour le rendre responsable au même titre que vous puis refermez l'accès à l'inscription. Si votre organisation le permet, vous pouvez aussi lui demander d'associer votre collègue à votre espace.</p><p>Chaque outil est muni d'une aide contextuelle (signalée par la bouée) qui vous en explique le fonctionnement. Si vous ne trouvez pas l'information voulue, consultez la page de documentation: <a href=\"http://www.dokeos.com/documentation.php\">http://www.dokeos.com/documentation.php</a> et téléchargez éventuellement le Manuel du responsable.</p>
<p><b>Stagiaire, apprenant</b></p>
<p>Ce portail vous permet de suivre des formations et d'y participer. Le logiciel Dokeos a été spécialement conçu pour favoriser les scénarios d'apprentissage actifs : par la collaboration, par le projet, le problème, etc. Vos responsables/responsables ont conçu pour vous des espacess d'apprentissage qui peuvent prendre la forme de simples répertoires de documents ou bien de parcours sophistiqués impliquant une chronologie et des épreuves à surmonter, seul ou en groupe.</p>
<p>Selon les décisions qui ont été prises par votre organisation, votre école, votre université, les modes d'inscription et de participation aux espace peuvent varier sensiblement. Dans certains portail, vous pouvez vous auto-inscrire dans le système, vous auto-inscrire dans les espace. Dans d'autres, un système d'administration centralisée gère l'inscription et vous recevrez par courriel ou par la poste votre identifiant et votre mot de passe.</p>";


// help.php?open=Online

$langHOnline="Aide Système de conférence en direct";
$langOnlineContent="<br><span style=\"font-weight: bold;\">Introduction </span><br>
      <br>
      <div style=\"margin-left: 40px;\">L'outil de conférence en direct vous permet de former, d'informer ou de consulter jusqu'à 500 personnes distantes simultanément de façon simple et rapide à l'aide de:<br>
      </div>
      <ul>
        <ul>
          <li><b>audio en direct :</b> la voix do responsable/conférencier est diffusée en direct aux participants sous forme de fichier mp3,<br>
          </li>
          <li><b>transparents :</b> les participants suivent la présentation sur des transparents PowerPoint, un fichier PDF ou tout autre type de support,<br>
          </li>
          <li><b>interaction :</b> les participants posent des questions par chat.</li>
        </ul>
      </ul>
      <span style=\"font-weight: bold;\"></span><span
 style=\"font-weight: bold;\"><br>
Stagiaire/participant</span><br>
      <br>
      <div style=\"margin-left: 40px;\">Pour assiter à une conférence, vous devez diposer de:<br>
      </div>
      <br>
      <div style=\"margin-left: 40px;\">1. Des haut-parleurs (ou un casque) connectés à votre PC<br>
      <br>
      <a href=\"http://www.logitech.com\"><img
 style=\"border: 0px solid ; width: 87px; height: 58px;\" alt=\"speakers\"
 src=\"../img/speaker.gif\"></a><br>
      <br>
2. Winamp Media player (ou tout autre logiciel permettant de lire du mp3 en streaming)<br>
      <br>
      <a href=\"http://www.winamp.com\"><img
 style=\"border: 0px solid ; width: 87px; height: 27px;\" alt=\"Winamp\"
 src=\"../img/winamp.gif\"></a><br>
      <br>
Mac : utilisez <a href=\"http://www.quicktime.com\">Quicktime</a><br>
Linux : utilisez <a href=\"http://www.xmms.org/\">XMMS</a> <br>
      <br>
&nbsp; 3. Acrobat PDF reader ou Word oo PowerPoint, en fonction du choix opéré par le responsable/conférencier pour la diffusion de ses transparents.>br>
      <br>
      <a href=\"http://www.acrobat.com\"><img
 style=\"border: 0px solid ; width: 87px; height: 31px;\"
 alt=\"acrobat reader\" src=\"../img/acroread.gif\"></a><br>
      </div>
      <br>
      <span style=\"font-weight: bold;\"><br>
Responsable/conférencier</span><br>
      <br>
      <div style=\"margin-left: 40px;\">Pour donner une conférence, vous devez disposer de:<br>
      </div>
      <br>
      <div style=\"margin-left: 40px;\">1. Un casque avec microphone<br>
      <br>
      <a href=\"http://www.logitech.com\"><img
 style=\"border: 0px solid ; width: 87px; height: 87px;\" alt=\"Headset\"
 src=\"../img/headset.gif\"></a><br>
Nous vous recommandons d'utiliser un casque <a href=\"http://www.logitech.com/\">Logitech</a>
avec prise USB pour une qualité de diffusion audio optimale et garantie.<br>
      <br>
2. Winamp<br>
      <br>
      <a href=\"http://www.winamp.com\"><img
 style=\"border: 0px solid ; width: 87px; height: 27px;\" alt=\"Winamp\"
 src=\"../img/winamp.gif\"></a><br>
      <br>
3. Le plugin SHOUTcast DSP pour Winamp 2.x <br>
      <br>
      <a href=\"http://www.shoutcast.com\"><img
 style=\"border: 0px solid ; width: 87px; height: 24px;\" alt=\"Shoutcast\"
 src=\"../img/shoutcast.gif\"></a><br>
      <br>
Suivez les instructions sur <a href=\"http://www.shoutcast.com\">www.shoutcast.com</a>
pour installer et paramétrer le plugin Shoutcast Winamp.<br>
      </div>
      <br>
      <span style=\"font-weight: bold;\"><br>
Comment donner une conférence?<br>
      <br>
      </span>
      <div style=\"margin-left: 40px;\">Créez un espace Dokeos &gt; Entrez dedans &gt; Affichez puis entrez dans l'outil Conférence &gt; Editez (icône du crayon jaune en haut à gauche) les paramètres &gt; envoyez vos transparents (PDF, PowerPoint ou quelque document que ce soit) et votre photo (de préférence pas trop grande)&gt; tapez un texte d'introduction qui pourra si vous voulez renvoyer par des liens à d'autres sites ou d'autres documents
&gt; tapez l'URL de votre streaming audio en fonction des informations qui vous ont été communiquées par votre responsable informatique (Dokeos fournit, à titre payant, un tel service : info@dokeos.com) et lancez la diffusion par Winamp tout en mettant votre casque.<br><span style=\"font-weight: bold;\"></span><br>
      <span style=\"font-weight: bold;\"></span></div>
      <div style=\"margin-left: 40px;\"><img
 style=\"width: 256px; height: 182px;\" alt=\"conference config\"
 src=\"../img/conf_screen_conf.gif\"><br>
N'oubliez pas de fournir à vos futurs participants une date et une heure de rendez-vous précise et de vous assurer que cvhacun possède identifiant et mot de passe pour accéder à votre espace. Une fois la conférence commencée, il sera trop tard pour régler les problèmes techniques ou d'accès).<br>
      <br>
      <span style=\"font-weight: bold;\">Astuce</span> : 10 minutes avant la conférence, tapez un court message dans le chat pour informer les participants de votre présence et aider ceux qui auraient éventuellement des problèmes audio. Il est important aussi que vous soyez le premier connecté et que vous diffusiez l'audio quelques minutes à l'avance, sinon vous devrez demander à vos participants de relancer leur lecteur audio. <br>
      </div>
      <br>
      <br>
      <span style=\"font-weight: bold;\">Serveur de streaming</span><br>
      <br>
      <div style=\"margin-left: 40px;\">Il ne faut pas confondre la conférence en direct (de 1 à plusieurs) avec la téléphonie par internet (de 1 à 1). Pour donner une conférence en direct, vous avez nécessairement besoin d'un serveur de streaming et probablement d'un responsable technique pour vous aider à configurer le flux audio (la vidéo fonctionne aussi, mais nous ne la recommandons pas). Cette personne vous communiquera l'URL de votre flux audio et vous devrez taper cette URL dans la configuration de votre conférence. 
	 <br>
      <br>
      <small><a href=\"http://www.dokeos.com/hosting.php#streaming\"><img
 style=\"border: 0px solid ; width: 258px; height: 103px;\"
 alt=\"dokeos streaming\" src=\"../img/streaming.jpg\"><br>
dokeos streaming</a></small><br>
      <br>
faites-le vous-même ou faites-le faire par un de vos proches : installez, configurez et administrez <a
 href=\"http://www.shoutcast.com\">Shoutcast</a> ou <a
 href=\"http://developer.apple.com/darwin/projects/streaming/\">Apple
Darwin</a>. <br>
      <br>
Ou contactez Dokeos. Nous pouvons vous aider à organiser votre conférence et vous assister dans sa mise en oeuvre vous louant un espace de streaming sur nos serveurs et en vous guidant dans son utilisation: <a
 href=\"http://www.dokeos.com/hosting.php#streaming\">http://www.dokeos.com/hosting.php</a><br>
      <br>
      <br>";



// help.php?open=Doc

$langHDoc="Aide documents";

$langDocContent="<p>Le module de gestion de document fonctionne de
 mani&egrave;re semblable &agrave; la gestion de vos documents sur un
 ordinateur. </p><p>Vous pouvez y créer des pages web simples et y d&eacute;poser des documents de tous type
 (HTML, Word, Powerpoint, Excel, Acrobat, Flash, Quicktime, etc.).</p>
 <p>Vous pouvez également envoyer des sites web complexes, sous forme de fichiers ZIP qui se décompresseront à 'arrivée (cochez 'dézipper').</p>Soyez
 attentifs &agrave; ce que les membres disposent des
 outils n&eacute;cessaires &agrave; leur consultation. Soyez
 &eacute;galement vigilants &agrave; ne pas envoyer
  des documents infect&eacute;s par des virus. Il est prudent de soumettre
 son document &agrave; un logiciel antivirus &agrave; jour avant de le
 d&eacute;poser
  sur le portail.</p>
<p>Les documents sont pr&eacute;sent&eacute;s par ordre
 alphab&eacute;tique.<br><br>
  <b>Astuces:</b> si vous souhaitez que les documents soient class&eacute;s
 de
  mani&egrave;re diff&eacute;rente, vous pouvez les faire
 pr&eacute;c&eacute;der
  d'un num&eacute;ro, le classement se fera d&egrave;s lors sur cette base.
 </p>
<p>Vous pouvez :</p>
<H4>Créer un document</H4>
<p>Cliquez sur 'Créer un document' > donnez-lui un titre (ni espaces ni accents) > tapez votre texte > utilisez les boutons de l'éditeur WYSIWYG (What You See Is What You Get) pour structurer l'information, créer des tables, des styles, des listes à puces etc. </p>
<p>Pour produire des pages web acceptables, vous devrez apprendre à maîtriser 3 concepts : les Liens, l'insertion d'images par URL et la disposition dans l'espace à l'aide des Tables.</p>
<p>Ne perdez pas de vue qu'une page web n'est pas un document Word et qu'elle est soumise à des contraintes et des limitations plus importantes (taille du fichier, limites de mise en page, garantie d'affichage d'un navigateur et d'un ordinateur à l'autre).</p>
<p>Une façon rapide de produire du contenu à l'aide de l'éditeur est de copier/coller le contenu de vos pages Word ou de pages web. Vous perdrez certains éléments de mise en page et parfois les liens vers les images, mais vous obtiendrez rapidement un résultat.
</p>
<ul><li><b>Pour ajouter un lien</b>, vous devez préalablement copier la cible de votre lien. Nous vous conseillons d'ouvrir simultanément deux fenêtres de votre navigateur, l'une avec votre espace Dokeos et l'autre pour partir à la recherche de la page vers laquelle vous voulez pointer (cette page peut d'ailleurs se trouver à l'intérieur de votre espace Dokeos).<br><br>Une fois la page cible obtenue, copiez son URL (sélectionnez son URL dans la barre d'URL et tapez CTRL+C ou POMME+C), retournez dans la fenêtre où vous tapez votre texte, sélectionnez le mot qui servira de lien et cliquez dans l'éditeur Wysiwyg sur l'icône représentant un maillon de chaine. Collez alors (CTRL+V ou POMME+V) l'URL dans le champ d'URL et validez.<br><br>Le mot sélectionné est devenu bleu et constitue un lien. Il ne sera utilisable qu'une fois la page enregistrée. Testez-le > enregistrez la page, ouvrez-la en mode navigation (et non édition) et cliquez sur le lien pour observer le résultat. Notez que vous pouvez décider si le lien s'ouvrira dans la même fenêtre (écrasant possiblement votre espace ou le faisant disparaître) ou dans une nouvelle fenêtre.</li>


<li><b>Pour ajouter une image</b>, le principe est similaire: parcourez le web à l'aide d'une deuxième fenêtre de navigateur, trouvez l'image (si cette image se trouve dans votr répertoire de documents, cliquez sur 'Sans cadres' pour afficher l'image seule), copiez son URL (CTRL+C ou POMME+C) depuis la barre d'URL et retournez dans la fenêtre où vous tapez votre texte.<br><br>Positionnez votre curseur dans le champ de saisie à l'endroit où vous voulez voir apparaître l'image et cliquez sur l'icône représentant un arbre. Copiez l'URL (CTRL+V ou POMME+V) dans le chapp URL, affichez 'Preview' puis validez.
<br><br>Notez que dans une page web, vous ne pouvez ni redimensionner ni déplacer une image à votre guise comme dans une page Word. De manière générale dans le web, il n'y a pas moyen de glisser/déposer quoi que ce soit.</li>

<li><b>Pour ajouter une table</b> (ce qui est une des seules façons de disposer les parties de texte et les images dans l'espace), positionnez votre curseur dans le champ de saisie à l'endroit où vous voulez voir apparaître le tableau, sélectionnez l'icône représentant un tableau dans l'éditeur Wysiwyg, décidez d'un nombre de lignes et de colonnes et validez. Nous vous recommandons aussi de choisir les valeurs width=600 border=1, cellspacing=0 et cellpadding=4 pour obtenir de beaux tableaux. Notez que vous ne pourrez ni redimensionner ni modifier la structure de vos tableaux une fois créés.</li>
</ul>

<h4>Transférer un document</h4>
<ul>
  <li>S&eacute;lectionnez le document sur votre ordinateur &agrave; l'aide
 du
	bouton &quot;Parcourir...&quot;
	<input type=\"button\" value=\"Parcourir...\">
	&agrave; droite de votre &eacute;cran.</li>
  <li>Ex&eacute;cutez le transfert &agrave; l'aide du
 bouton &quot;Transférer&quot;
	<input type=\"button\" value=\"Transférer\">
	.</li>
</ul>
<h4>Renommer un document (ou un r&eacute;pertoire)</h4>
<ul>
  <li>cliquez sur le bouton <img src=\"../img/edit.gif\" width=\"20\" height=\"20\" align=\"absmiddle\">
	dans la colonne &quot;Modifier&quot;.</li>
  <li>Tapez le nouveau nom dans la zone pr&eacute;vue &agrave; cet effet.</li>
  <li>Valider en cliquant sur &quot;Valider&quot;
	<input type=\"button\" value=\"Valider\">
	.
</ul>
	<h4>Supprimer un document (ou un r&eacute;pertoire)</h4>
	<ul>

  <li>Cliquer sur le bouton <img src=\"../img/delete.gif\" width=\"20\" height=\"20\" align=\"absmiddle\">
	dans la colonne &quot;Effacer&quot;.</li>
	</ul>
	<h4>Rendre un document (ou un
 r&eacute;pertoire) invisible aux membres</h4>
	<ul>

  <li>Cliquez sur le bouton <img src=\"../img/visible.gif\" width=\"20\" height=\"20\" align=\"absmiddle\">dans
	la colonne &quot;Visible/invisible&quot;.</li>
	  <li>Le document (ou le r&eacute;pertoire) existe toujours, mais il n'est

		plus visible pour les membres.</li>
	</ul>
	<ul>

  <li> Si vous souhaitez rendre cet &eacute;l&eacute;ment &agrave; nouveau
 visible,
	cliquez sur le bouton <img src=\"../img/invisible.gif\" width=24 height=20 align=\"absmiddle\">
	dans la colonne Visible/invisible</li>
	</ul>
	<h4>Ajouter ou modifier un commentaire au document (ou au
 r&eacute;pertoire)</h4>
	<ul>

  <li>Cliquez sur le bouton <img
 src=\"../img/comment.gif\" width=\"20\" height=\"20\" align=\"absmiddle\">
	dans la colonne &quot;Modifier&quot;</li>
	  <li>Tapez le nouveau commentaire dans la zone pr&eacute;vue &agrave; cet

		effet.</li>
	  <li>Validez en cliquant sur &quot;Valider&quot;
		<input type=\"button\" value=\"Valider\">
		.</li>
	</ul>
	<p>Si vous souhaitez supprimer un commentaire, cliquez sur le bouton <img
 src=\"../img/comment.gif\" width=\"20\" height=\"20\" align=\"absmiddle\">,
	  effacez l'ancien commentaire de la zone et validez en cliquant
 &quot;Valider&quot;
	  <input type=\"button\" value=\"Valider\">
	  .
	<hr>
	<p>Vous pouvez aussi organiser le contenu du module de document en
 rangeant
	  les documents dans des r&eacute;pertoires. Pour ce faire vous devez :</p>
	<h4><b>Cr&eacute;er un r&eacute;pertoire</b></h4>
	<ul>
	  <li>Cliquez sur le lien &quot;<img
 src=\"../img/file.gif\" width=\"20\" height=\"20\" align=\"absmiddle\">Cr&eacute;er
		un r&eacute;pertoire&quot; en haut de la liste des fichiers</li>
	  <li>Tapez le nom de votre nouveau r&eacute;pertoire dans la zone
 pr&eacute;vue
		&agrave; cet effet en haut &agrave; gauche de l'&eacute;cran.</li>
	  <li>Validez en cliquant &quot;Valider&quot;
		<input type=\"button\" value=\"Valider\">
		.</li>
	</ul>
	<h4>D&eacute;placer un document (ou un r&eacute;pertoire)</h4>
	<ul>
	  <li>Cliquez sur le bouton <img src=\"../img/move.gif\" width=\"34\" height=\"16\" align=\"absmiddle\">
		dans la colonne &quot;D&eacute;placer&quot;</li>
	  <li>Choisissez le r&eacute;pertoire dans lequel vous souhaitez
 d&eacute;placer
		le document ou le r&eacute;pertoire dans le menu d&eacute;roulant
 pr&eacute;vu
		&agrave; cet effet qui appara&icirc;tra en haut &agrave; gauche (note:
		le mot &quot;root&quot; dans ce menu repr&eacute;sente la racine de
		votre module document).</li>
	  <li>Validez en cliquant &quot;Valider&quot;
		<input type=\"button\" value=\"Valider\">.</li>
	</ul>
<h4>Créer un Parcours d'apprentissage</h4>L'outil de Parcours vous permet de construire des itinéraires dans le contenu et les activités. Le résultat ressemblera à une Table des matières mais offrira bien plus de possiblités qu'une Table des matières ordinaires. Voir l'aide de l'outil Parcours.</p>";



// help.php?open=User

$langHUser="Aide membres";
$langUserContent="L'outil Membres fournit la liste des personnes inscrites au espace. Elle offre en outre les fonctionnalités suivantes:
<ul><li><b>Nom et prénom</b> : pour accéder à la fiche de l'utilisateur contenant sa photo, son adresse email et d'autres informations, cliquez sur son nom</li>
<li><b>Description</b> : remplissez ce champ pour donner informer les autres membres du rôle joué par l'un d'entre eux dans votre dispositif</li>
<li><b>Editer (crayon jaune)</b> : permet d'attribuer des droits supplémentaires, comme celui de partager avec vous la responsabilité d'administrer cet espace ou bien celui, plus modeste, de modérer les échanges dans les groupes</li>
<li><b>Suivi</b> : vous renseigne sur l'utilisation de l'espace par le membre/le membre. Combien de fois il/elle est venu(e), combien de points il/elle a obtenu aux tests, combien de temps il (elle) a passé dans les modules d'espaces Scorm, quels documents il/elle a déposés dans l'outil Travaux, etc.</li>
</ul>
Vous pouvez aussi, dans la page Membres, inscrire des membres à votre espace (ne le faites que si ils/elles ne sont pas encore inscrits dans le portail), gérer les espaces des groupes ou définir des intitulés qui permettront aux étudiants de se décrire ou de se présenter aux autres : numéro de téléphone, curriculum vitae etc.


<p><b>Co-responsabilité d'un espace</b>
<p>Pour permettre à un co-responsable de votre espace de l'administrer avec vous, vous devez préalablement
 lui demander de s'inscrire à votre espace ou vous assurer qu'il est inscrit puis modifier
 ses droits en cliquant sur l'icône d'édition puis sur 'Responsable'.</P>
<p>Pour faire figurer le nom de ce co-responsable dans l'en-tête de votre
 espace, utilisez la page 'Propriétés de cet espace' (dans les outils orange
 sur la page d'accueil de votre espace). Cette modification de l'en-tête
 de l'espace n'inscrit pas automatiquement ce co-responsable comme membre de l'espace. Ce sont deux actions distinctes.</p>";



// Help Group

$langGroupManagement="Gestion des groupes";
$langGroupContent="<p><b>Introduction</b></p>
	<p>Cet outil permet de créer et de gérer des groupes de travail.
	A la création, les groupes sont vides. Le responsable dispose de
	plusieurs façons de les remplir:
	<ul><li>automatique ('Remplir les groupes'),</li>
	<li>à la pièce ('Editer'),</li>
	<li>par les membres (Propriétés: 'Membres autorisés ...').</li></ul>
	Ces modes de remplissage sont combinables entre eux. Ainsi, on peut demander aux membres
	de s'inscrire eux-mêmes puis constater que certains d'entre eux ont oublié de s'inscrire
	et choisir alors de remplir les groupes, ce qui aura pour effet de les compléter. On peut
	aussi (via la fonction 'Editer') modifier manuellement la composition de chacun des groupes
	après remplissage automatique ou après auto-inscription par les membres.</p>
	<p>Le remplissage des groupes, qu'il soit automatique ou manuel, ne fonctionne que
	si les membres sont déjà inscrits au espace, ce qui peut être vérifié via l'outil
	'Membres'.</p><hr noshade size=1>
	<p><b>Créer des groupes</b></p>
	<p>Pour créer de nouveaux groupes, cliquez sur 'Créer nouveau(x) groupe(s)' et déterminez
	le nombre de groupes à créer. Le nombre maximum de participants est facultatif. Si
	vous laissez ce champ inchangé, la taille des groupes sera illimitée.</p><hr noshade size=1>
	<p><b>Propriétés des groupes</b></p>
	<p>Vous pouvez déterminer de façon globale les propriétés des groupes.
	<ul><li><b>Membres autorisés à s'inscrire eux-mêmes dans les groupes</b>:
	vous créez des groupes vides et laissez les membres s'y ajouter eux-mêmes.
	Si vous avez défini un nombre de places maximum
	par groupe, les groupes complets n'acceptent plus de nouveaux membres.
	Cette méthode convient particulièrement au responsable qui ne connaît pas la
	liste des membres au moment de créer les groupes.</li>
	<li><b>Accès aux groupes réservé uniquement à leurs membres</b>: les groupes n'accèdent
	pas aux forums et documents partagés des autres groupes. Cette propriété n'exclut pas
	la publication de documents par les groupes hors de leur espace privé.</li>
	<li><b>Outils</b>: chaque groupe dispose soit d'un forum, soit d'un répertoire partagé associé
	à un gestionnaire de documents, soit (cas le plus fréquent) les deux.</li></ul>
	<hr noshade size=1>
	<p><b>Edition manuelle</b></p>
	<p>Une fois des groupes crées, vous voyez apparaître leur liste assortie d'une série d'informations
	et de fonctions. <ul><li><b>Editer</b> permet de modifier manuellement la composition du groupe.</li>
	<li><b>Supprimer</b> détruit un groupe.</li></ul>
	<hr noshade size=1>";


// help.php?open=Exercise

$langHExercise="Aide Tests";

$langExerciseContent="<p>Le module de tests vous permet de créer des tests d'auto-évaluation pouvant contenir un nombre quelconque de questions.<br><br>
Il existe différents types de réponses disponibles pour la création de vos questions :<br><br>
<ul>
  <li>Choix multiple (Réponse unique)</li>
  <li>Choix multiple (Réponses multiples)</li>
  <li>Correspondance</li>
  <li>Remplissage de blancs</li>
</ul>
Un test rassemble un certain nombre de questions sous un thème commun.</p>
<hr>
<b>Création d'un test</b>
<p>Pour créer un test, cliquez sur le lien &quot;Nouveau test&quot;.<br><br>
Introduisez l'intitulé de votre test, ainsi qu'une éventuelle description de celui-ci.<br><br>
Vous pouvez également choisir entre 2 types de tests :<br><br>
<ul>
  <li>Questions sur une seule page</li>
  <li>Une question par page (séquentiel)</li>
</ul>
et préciser si vous souhaitez ou non que les questions soient triées aléatoirement lors de l'exécution du test par le membre.<br><br>
Enregistrez ensuite votre test. Vous arriverez à la gestion des questions de ce test.</p>
<hr>
<b>Ajout d'une question</b>
<p>Vous pouvez à présent ajouter une question au test précédemment créé. La description est facultative, de même que l'image que vous avez la possibilité d'associer à votre question.</p>
<hr>
<b>Choix multiple</b>
<p>Il s'agit du classique QRM (question à réponse multiple) / QCM (question à choix multiple).<br><br>
Pour créer un QRM / QCM :<br><br>
<ul>
  <li>Définissez les réponses à votre question. Vous pouvez ajouter ou supprimer une réponse en cliquant sur le bouton adéquat</li>
  <li>Cochez grâce aux cases de gauche la ou les réponses exactes</li>
  <li>Ajoutez un éventuel commentaire. Celui-ci ne sera vu par le membre qu'une fois qu'il aura répondu à la question</li>
  <li>Donnez une pondération à chaque réponse. La pondération peut être n'importe quel nombre entier, positif, négatif ou nul</li>
  <li>Enregistrez vos réponses</li>
</ul></p>
<hr>
<b>Remplissage de blancs</b>
<p>Il s'agit du texte à trous. Le but est de faire trouver par le membre des mots que vous avez préalablement retirés du texte.<br><br>
Pour retirer un mot du texte, et donc créer un blanc, placez ce mot entre crochets [comme ceci].<br><br>
Une fois le texte introduit et les blancs définis, vous pouvez éventuellement ajouter un commentaire qui sera vu par le membre lorsqu'il aura répondu à la question.<br><br>
Enregistrez votre texte, et vous arriverez à l'étape suivante qui vous permettra d'attribuer une pondération à chacun des blancs. Par exemple si la question est sur 10 points et que vous avez 5 blancs, vous pouvez donner une pondération de 2 points à chaque blanc.</p>
<hr>
<b>Correspondance</b>
<p>Ce type de réponse peut être choisi pour créer une question où le membre devra relier des éléments d'un ensemble E1 avec les éléments d'un ensemble E2.<br><br>
Il peut également être utilisé pour demander au membre de trier des éléments dans un certain ordre.<br><br>
Commencez par définir les options parmi lesquelles le membre pourra choisir la bonne réponse. Ensuite, définissez les questions qui devront être reliées à une des options définies précédemment. Enfin, établissez les correspondances via les menus déroulants.<br><br>
Remarque : Plusieurs éléments du premier ensemble peuvent pointer vers le même élément du deuxième ensemble.<br><br>
Donnez une pondération à chaque correspondance correctement établie, et enregistrez votre réponse.</p>
<hr>
<b>Modification d'un test</b>
<p>Pour modifier un test, le principe est le même que pour la création. Cliquez simplement sur l'image <img src=\"../img/edit.gif\" border=\"0\" align=\"absmiddle\"> à côté du test à modifier, et suivez les instructions ci-dessus.</p>
<hr>
<b>Suppression d'un test</b>
<p>Pour supprimer un test, cliquez sur l'image <img src=\"../img/delete.gif\" border=\"0\" align=\"absmiddle\"> à côté du test à supprimer.</p>
<hr>
<b>Activation d'un test</b>
<p>Avant qu'un test ne puisse être utilisé par un membre, vous devez l'activer en cliquant sur l'image <img src=\"../img/invisible.gif\" border=\"0\" align=\"absmiddle\"> à côté du test à activer.</p>
<hr>
<b>Exécution d'un test</b>
<p>Vous pouvez tester votre test en cliquant sur son nom dans la liste des tests.</p>
<hr>
<b>Tests aléatoires</b>
<p>Lors de la création / modification d'un test, vous avez la possibilité de préciser si vous souhaitez que les questions soient tirées dans un ordre aléatoire parmi toutes les questions du test.<br><br>
Cela signifie qu'en activant cette option, les questions seront à chaque fois dans un ordre différent lorsque les membres exécuteront le test.<br><br>
Si vous avez un grand nombre de questions, vous pouvez aussi choisir de ne prendre aléatoirement que X questions sur l'ensemble des questions disponibles dans ce test.</p>
<hr>
<b>Banque de questions</b>
<p>Lorsque vous supprimez un test, les questions qu'il contenait ne le sont pas et peuvent être réutilisées dans un nouveau test, via la banque de questions.<br><br>
La banque de questions permet également de réutiliser une même question dans plusieurs test.<br><br>
Par défaut, toutes les questions de votre formation sont affichées. Vous pouvez afficher les questions relatives à un test en particulier, en choisissant celui-ci dans le menu déroulant &quot;Filtre&quot;.<br><br>
Des questions orphelines sont des questions n'appartenant à aucun test.</p>";

// help.php?open=Dropbox

$langHDropbox="Dropbox";

$langDropboxContent="L'outil de partage affiche les fichiers qui vous ont été envoyés
(dossier Recu) et les fichiers que vous avez communiqués à d'autres membres
(dossier Envoyé). Si vous envoyez deux fois un fichier du même nom, vous pouvez choisir d'écraser
le premier envoi par le second.
<br>
<br>
Comme membre, vous pouvez seulement envoyer un fichier au responsable de l'espace,
à moins que le gestionnaire système ait activé le partage entre les membres.
<br>
<br>
Un responsable peut choisir d'envoyer un fichier à tous les membres de l'espace.
<br><br>
L'administrateur système peut activer l'envoi de fichiers sans destinataire.
<br><br>
Si la liste des fichiers devient trop longue, vous pouvez supprimer certains fichiers ou
tous les fichiers. Le fichier lui-même n'est toutefois pas supprimé pour les autres membres
qui y ont accès à moins que tous le suppriment.
<br>";




$langHPath="Aide outil Parcours";

$langPathContent="<br>L'outil Parcours a deux fonctions :
<ul><li>Créer un parcours</li>
<li>Importer un parcours au format Scorm ou IMS</li></ul>
<img src=\"../img/path_help.gif\">

<p><b>
Qu'est-ce qu'un parcours?</b>
</p><p>Un parcours est une séquence d'apprentissage découpée en modules eux-mêmes découpés en étapes. Il peut être organisé en fonction d'un contenu, il constituera alors une sorte de Table des matières, ou bien en fonction d'activités, il s'apparentera alors à un Agenda de 'choses à faire' pour acquérir la maîtrise d'un savoir, d'une compétence. Il vous appartient de baptiser les modules successifs de votre parcours 'chapitres', 'semaines', 'modules', 'séquences' ou toute autre appellation répondant à la nature de votre scénario pédagogique.</p><p>En plus d'être structuré, un parcours peut être séquencé. Cela signifie que certaines étapes peuvent constituer des pré-requis pour d'autres ('Vous ne pouvez aller à l'étape 2 avant d'avoir parcouru l'étape 1'). Votre séquence peut être suggestive (vous montrez les étapes l'une après l'autre) ou contraignante (le membre est obligé de suivre les étapes dans un ordre imposé).
</p>
<p><b>Comment créer un parcours?</b></p>
<p>
Cliquez sur Créer un parcours > Créer un nouveau parcours > Créer un module > Ajouter une étape (=un document, une activité, un outil etc.). Pour ajouter des étapes, il vous suffit ensuite de parcourir les outils dans le menu de gauche puis d'ajouter les documents, les activités, forums, travaux etc. Cliquez sur Retour à 'nom du parcours' pour revenir au parcours désormais rempli d'étapes et cliquez sur 'Vue étudiant' pour un aperçu du parcours (pour revenir à la vue de l'responsable, cliquez sur la maison dans le coin supérieur droit puis sur Vue responsable).</p><p>Ensuite paramétrez plus finement votre parcours pour:
<ul><li>renommer le titre des documents, des outils, des liens etc. afin de constituer une véritable 'table des matière pour le membre</li>
<li>réordonner les étapes en fonction de votre scénario d'espaces : icônes en triangle blanc vers le haut et vers le bas</li>
<li>établir une séquence en ajoutant des prérequis: à l'aide de l'icône grise représentant deux documents, définissez quelle étape est prérequise pour l'étape courante</li>
<li>définir si le parcours est visible ou invisible : si vous sélectionnez visible, le parcours appraîtra sur la page d'accueil de l'espace</li>
</ul>
Il est important de comprendre qu'un parcours est plus que le découpage d'une matière : il est un itinéraire à travers le savoir qui inclut potentiellement des épreuves, des temps de discussion, d'évaluation, d'expérimentation, de publication, de regard-croisé... C'est pourquoi l'outil de parcours de Dokeos constitue une sorte de méta-outil permettant de puiser dans l'ensemble des autres outils pour séquencer:
<ul>
<li>événements de l'agenda</li>
<li>documents de toute nature : pages web, images, fichiers Word, PowerPoint etc.</li>
<li>Annonces</li>
<li>Forums</li>
<li>Sujets dans les forums</li>
<li>Messages dans les forums</li>
<li>Liens (ils s'ouvriront dans une fenêtre séparée)</li>
<li>Tests (n'oubliez pas de les rendre visibles dans l'outil de tests)</li>
<li>Page de travaux (où les étudiants peuvent envoyer leur copie)</li>
<li>Partage de fichiers (pour échanger des brouillons, travailler à plusieurs voix...)</li>
</ul>
</p><p><b>
Qu'est-ce qu'un parcours Scorm ou IMS et comment l'importer?</b>
</p>
<p>Outre la possibilité qu'il vous offre de CONSTRUIRE des parcours, l'outil Parcours ACCUEILLE vos contenus e-Learning conformes à la norme Scorm. Ceux-ci peuvent être importés sous forme de fichiers compressés au format ZIP (seul ce format est accepté). Vous avez peut-être acquis des licences sur de tels espace ou bien vous préférez construire vos parcours localement sur votre disque dur plutôt que directement en ligne sur Dokeos. Dans ce cas, lisez ce qui suit.</p>
<p>SCORM (<i>Sharable Content Object Reference Model</i>) est un standard public respecté par les acteurs majeurs du e-Learning: NETg, Macromedia, Microsoft, Skillsoft, etc. Ce standard agit à trois niveaux:
</p>
<ul>
<li><b>Economique</b> : grâce au principe de séparation du contenu et du contexte, Scorm permet de réutiliser des espaces entiers ou des morceaux d'espaces dans différents <i>Learning Management Systems</i> (LMS),</li>
<li><b>P&eacute;dagogie</b> : Scorm intègre la notion de pré-requis ou de <i>séquence</i> (p.ex. \"Vous ne pouvez pas entrer dans le chapitre 2 tant que vous n'avez pas passé le Quiz 1\"),</li>
<li><b>Technologie</b> : Scorm génère une table des matières indépendante tant du contenu que du LMS. Ceci permet de faire communiquer contenu et LMS pour sauvegarder entre autres : la <i>progression</i> de l'apprenant (\"A quel chapitre de l'espace Jean est-il arrivé?\"), les résultats</i> (\"Quel est le résultat de Jean au Quiz 1?\") et le <i>temps</i> (\"Combien de temps Jean a-t-il passé dans le chapitre 4?\").</li>
</ul>
<b>Comment générer localement (sur votre disque dur) un espace compatible Scorm?</b><br>
<br>
Utilisez des outils auteurs comme Dreamweaver, Lectora et/ou Reload puis sauvegardez votre parcours comme un fichier ZIP et téléchargez-le dans l'outil \"Parcours\".<br>
<br>
<b>Liens utiles</b><br>
<ul>
<li>Adlnet : autorit&eacute; responsable de la norme Scorm, <a
href=\"http://www.adlnet.org/\">http://www.adlnet.org</a></li>
<li>Reload : Editeur et player Scorm Open Source et gratuits, <a
href=\"http://www.reload.ac.uk/\">http://www.reload.ac.uk</a></li>
<li>Lectora : Logiciel auteur permettant d'exporter au format Scorm, <a
href=\"http://www.trivantis.com/\">http://www.trivantis.com</a><br>
</li>
</ul><b>
</p>";



$langHDescription="Aide outil Description";

$langDescriptionContent="<p>L'outil Description de l'espace vous invite à décrire votre espace de manière synthétique et globale dans une logique de cahier des charges. Cette description pourra servir à donner aux étudiants ou aux participants un avant-goût de ce qui les attend. Pour décrire l'espace chronologiquement étape par étape, préférez l'Agenda ou le Parcours.</p>Les rubriques sont proposées à titre de suggestion. Si vous souhaitez rédiger une description de l'espace qui ne tienne aucun compte de nos propositions, il vous suffit de ne créer que des rubriques 'Autre'.</p>
<p>Pour remplir la Description de l'espace, cliquez sur Créer et éditer une description... > Déroulez le menu déroulant et sélectionnez la rubrique de votre choix puis validez. Remplissez ensuite les champs. Il vous sera à tout moment possible de détruire ou de modifier une rubrique en cliquant sur le crayon ou sur la croix rouge.</p>"; 



$langHLinks="Aide outil Liens";

$langLinksContent="<p>L'outil Liens vous permet de constituer une bibliothèque de ressources pour vos étudiants et en particulier de ressources que vous n'avez pas produites vous-même.</p>
<p>Lorsque la liste s'allonge, il peut être utile d'organiser les liens en catégories afin de faciliter la recherche d'information par vos étudiants. Veillez é vérifier de temps en temps si les liens sont toujours valides.</p>
<p>Le champ description peut être utilisé de manière pédagogiquement dynamique en y ajoutant non pas nécessairement la description des documents ou des sites eux-mêmes, mais la description de l'activité que vous attendez de vos étudiants par rapport aux ressources. Si vous pointez, par exemple, vers une page sur Aristote, le champ Description peut inviter à étudier la différence entre synthèse et analyse. "; 

$langHAgenda="Aide Agenda";

$langAgendaContent="<p>L'agenda est un outil qui prend place à la fois dans chaque espace et comme outil de synthèse pour le membre ('Mon agenda') reprenant l'ensemble des événements relatifs aux espace dans lesquels il est inscrit.</p>Depuis Dokeos 1.5.4 il est possible d'ajouter des annexes aux événements : documents, liens divers. Ceci permet de traiter l'agenda comme un outil de programmation de l'apprentissage jour après jour ou semaine après semaine qui renvoie aux contenus et aux activités.</p>Toutefois, si l'on souhaite organiser les activités dans le temps de façon structurée, il peut être préférable d'utiliser l'outil Parcours qui permettra de construire de véritables séquences à travers le temps, les activités ou le contenu en présentant l'espace selon une logique formelle de table des matières.</p>"; 

$langHGroups="Aide Groupes";

$langGroupsContent="<p>L'outil de groupes vous permet de fournir à des groupes d'étudiants des espacess privatifs pour échanger des documents et discuter dans un forum. L'outil de documents des groupes leur permet, en outre, de publier un document dans 'Travaux' une fois ce document jugé définitif. On peut ainsi passer d'une logique de travail confiné à une logique de diffusion vers l'formateur/responsable ou vers les membres des autres groupes.</p>
<b>Remplir les groupes</b>
<p>Il existe 3 manières de remplir les groupes:
<ol><li>soit les participants s'auto-inscrivent dans les groupes dans la limite des places disponibles</li>
<li>soit ils sont inscrits manuellement un à un par le responsable,</li>
<li>soit les groupes sont remplis de façon automatique au hasard</li></ol>
Pour 1 : il faut éditer les Paramètres des groupes (milieu de la page) pour vérifier que la case 'auto-inscription' est cochée. Pour 2 : il faut créer des groupes (coin supérieur gauche) puis éditer chacun des groupes et le remplir en faisant passer les personnes du menu de gauche vers le menu de droite (CTRL+ clic ou POMME+ clic pour sélectionner plusieurs personnes en même temps). Pour 3 : il faut cliquer sur 'Remplir les groupes au hasard'. Attention : 2 et 3 ne fonctionnent que si les participants sont déjà inscrits au espace préalablement.</p>
<b>Editer les groupes</b>
<p>Editer les espaces des groupes (crayon jaune) permet de les renommer, de leur ajouter un descriptif (tâches du groupe, numéro de téléphone du coach...), de modifier leurs paramètres et de modifier leur composition, de leur ajouter un modérateur (ou coach). Pour créer un groupe uniquement pour les modérateurs, créer un groupe dont le nombre maximum de participants est zéro (car les modérateurs ont tous accès à tous les groupes par défaut).";


$langHAnnouncements="Aide Annonces";

$langAnnouncementsContent="<p>L'outil d'Annonces vous permet d'envoyer un message par courriel aux étudiants/apprenants. Que ce soit pour leur signaler que vous avez déposé un nouveau documents, que la date de remise des rapports approche ou qu'untel a réalisé un travail de qualité, l'envoi de courriels, s'il est utilisé avec modération, permet d'aller chercher les participants et peut-être de les ramener au site web s'il est déserté.</p>
<b>Message pour certains membres</b>
<p>Outre l'envoi d'un courriel à l'ensemble des membres de l'espace, vous pouvez envoyer un courriel à une ou plusieurs personnes et/ou un ou plusieurs groupes. Dans ce nouvel outil, utilisez CTRL+clic pour sélectionner plusieurs éléments dna le menu de gauche puis cliquez sur la flèche droite pour les amener dans le menu de droite. Tapez ensuite votre message dans le champ de saisie situé en bas de la page.";

$langHChat="Aide Discussion";

$langChatContent="<p>L'outil de discussion est un 'chat' ou 'clavardage' qui vous permet de discuter en direct avec vos étudiants/participants.</p>
<p>A la différence des outils de chat que l'on trouve sur le marché, ce 'chat' fonctionne dans une page web et non à l'aide d'un client additionnel à télécharger : Microsoft Messenger&reg;, Yahoo! Messenger&reg; etc. L'avantage de cette solution est l'universalité garantie de son utilisation sur tous ordinateurs et sans délai. L'inconvénient est que la liste des messages ne se rafraichit pas instantamément mais peut prendre de 5 à 10 secondes.</p>
<p>Si les étudiants/participants ont envoyé leur photo dans l'outil 'Mon profil', celle-ci apparaîtra en réduction à côté de leurs messages. Sinon, ce sera une photo par défaut en noir sur fond blanc.</p>
<p>Il appartient au responsable d'effacer les discussions quand il/elle le juge pertinent. Par ailleurs, ces discussions sont archivées automatiquement dans l'outil 'Documents'.</p>
<b>Usages pédagogiques</b>
<p>Si l'ajout d'un 'chat' dans l'espace n'apporte pas nécessairement une valeur ajoutée dans les processus d'apprentissage, une utilisation méthodique de celui-ci peut apporter une réelle contribution. Ainsi, vous pouvez fixer des rendez-vous de questions-réponses à vos membres et désactiver l'outil le reste du temps, ou bien exploiter l'archivage des discussions pour revenir en classe sur un sujet abordé dans le passé.";





$langHWork="Aide Travaux";

$langWorkContent="<p>L'outil Travaux est un outil très simple permettant à vos étudiants/participants d'envoyer des documents vers l'espace. Il peut servir à réceptionner des rapports individuels ou collectifs, des réponses à des questions ouvertes ou toute autre forme de document.</p>
<p>Beaucoup de responsables/d'responsables masquent l'outil Travaux jusqu'à la date de remise des rapports. Vous pouvez aussi pointer vers cet outil par un lien depuis le texte d'introduction de votre espace ou l'agenda. L'outil Travaux dispose lui aussi d'un texte d'introduction qui pourra vous servir à formuler une question ouverte, à préciser les consignes pour la remise de rapports ou toute autre information.</p>
<p>Les travaux sont soit publics soit à destination du seul responsable. Publics, ils serviront un dispositif de regard croisé dans lequel vous invitez les participants à commenter mutuellement leurs productions selon un scénario et des critères éventuellement formulés dans le Texte d'intruduction. Privés, ils seront comme une boîte aux lettres du responsable/ de l'responsable.";



$langHTracking="Aide Suivi statistique";

$langTrackingContent="<p>L'outil de suivi statistique vous permet de suivre l'évolution de l'espace à deux niveaux:
<ul><li><b>Globalement</b>: quelles sont les pages les plus visitées, quel est le taux de connection par semaine...?</li>
<li><b>Nominativement</b>: quelles pages Jean Dupont a vues et quand, quels résultats a-t-il obtenu aux exercices, combien de temps est-il resté dans chaque chapitre d'un espace Scorm, quels traavaux a-t-il déposé et à quelle date?</li></ul>
Pour obtenir les statistiques nominatives, cliquez sur 'Membres'. Pour les statistiques globales, cliquez sur 'Montrer tout'.</p>
<p>";


$langHSettings="Aide Propriétés de l'espace";

$langSettingsContent="<p>L'outil 'Propriétés de l'espace' vous permet de modifier le comportement global de votre espace.</p>
<p>La partie supérieure de la page permet de modifier les rubriques qui apparaissent dans l'entête de votre espace: nom du responsable/de l'responsable (n'hésitez pas à en introduire plusieurs), intitulé de l'espace, code, langue. Le département est facultatif et peut représenter un sous-ensemble de votre organisation : cellule, groupe de travail etc.</p>
<p>La partie médiane de la page vous permet de déterminer les paramètrs de confidentialité. Une utilisation classique consiste à fermer tout accès au espace pendant la période de fabrication (pas d'accès, pas d'inscription), d'ouvrir ensuite à l'inscription mais non à la visibilité publique, et ce le temps nécessaire pour que chacun des participants s'inscrive, puis de refermer l'inscription et d'aller dans Membres chasser les éventuels intrus. Certaines organisations préfèrent ne pas utiliser cette méthode et recourir à une inscription administrative centralisée. Dans ce cas, les participants n'ont pas même l'opportunité de s'inscrire à votre espace, quand bien même vous, en tant que formateur/responsable, leur en donneriez l'accès. Observez donc la page d'accueil de votre portail (non celle de votre espace) pour voir si le lien 'S'inscrire' est présent.</p>
<p>La partie inférieure de le page permet d'effectuer une sauvegarde de l'espace et/ou de supprimer celui-ci. La sauvegarde copiera une archive ZIP de votre espace sur le serveur et vous permettra en outre de la récupérer sur votre ordinateur local par téléchargement. C'est une façon commode de récupérer l'ensemble des documents qui se trouvent dans votre espace. Il vous faudra utiliser un outil de décompression genre Winzip&reg; pour ouvrir l'archive une fois récupérée.";



$langHExternal="Aide Ajouter un lien";

$langExternalContent="<p>Dokeos est un outil modulaire. Il vous permet de masquer et d'afficher les outils à votre guise. Poussant plus loin cette logique, Dokeos vous permet aussi d'ajouter des liens sur votre page d'accueil.</p>
Ces liens peuvent être de deux types:
<ul><li><b>Lien externe</b> : par exemple vous renvoyez ver le site Google, http://www.google.be. Choisissez alors comme destination du lien : Dans une autre fenêtre,</li>
<li><b>Lien interne</b> : vous pouvez créer un raccourci sur votre page d'accueil qui pointe directement vers n'importe quelle page ou outil situé à l'intérieur de votre espace. Pour ce faire, rendez-vous sur cette page ou dans cet outil, copiez (CTRL+C) l'URL de la page, revenez sur la page d'accueil, ouvrez Ajouter un lien et collez (CTRL+V) l'URL de la page dans le champ URL puis donnez-lui le nom de votre choix. Dans ce cas, vous choisirez préférablement comme destination du lien : Dans la même fenêtre.</li></ul>
Remarque : une fois créés, les liens sur page d'accueil ne peuvent pas être modifiés. Il vous faudra les masquer, puis les détruire, puis recommencer en partant de zéro.</p>";




$langHMycourses="Aide Ma page d'accueil";

$langMycoursesContent="<p>Une fois identifié dans le système, vous êtes ici sur <i>votre</i> page. Vous voyez:
<ul><li><b>Mes espaces</b> au milieu de la page, ainsi que la possibilité de créer de nouveaux espace (bouton dans le menu de droite),</li>
<li>Dans l'entête, <b>Mon profil</b>: vous pouvez modifier là votre mot de passe, importer votre photo dans le système, modifier votre nom d'utilisateur,</li>
<li><b>Mon agenda</b>: il contient les événements des espaces auxquels vous êtes inscrit,</li>

<li>Dans le menu de droite : <b>Modifier ma liste d'espaces</b> qui vous permet de vous inscrire à des espaces comme apprenant, si le responsable/l'responsable a autorisé l'inscription. C'est là aussi que vous pourrez vous désinscrire d'un espace,</li>
<li>Les liens <b>Forum de Support</b> et <b>Documentation</b> vous renvoient vers le site central de Dokeos, où vous pourrez poser des questions ou trouver des compléments d'information.</li></ul>
Pour entrer dans un espace (partie gauche de l'écran), cliquez sur son intitulé. Votre profil peut varier d'un espace à l'autre. Il se pourrait que vous soyez responsable dans tel espace et apprenant dans un autre. Dans les espacesoù vous êtes responsable, vous disposez d'outils d'édition, dans les espacesoù vous êtes apprenant, vous accédez aux outils sur un mode plus passif.</p>
<p>La disposition de <i>votre</i> page peut varier d'une organisation à l'autre selon les options qui ont été activées par l'administrateur système. Ainsi il est possible que vous n'ayez pas accès à la fonction de création d'espaces, même en tant que responsable, parce que cette fonction est gérée par une administration centrale.";





?>
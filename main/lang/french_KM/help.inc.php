<?php
/*
      +----------------------------------------------------------------------+
      | DOKEOS version 1.5.0 $Revision: 7370 $                                |      |
      +----------------------------------------------------------------------+
      |   $Id: help.inc.php 7370 2005-12-12 12:27:15Z d13tr1ch $       |
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

$langForContent="Le forum est un outil de discussion asynchrone par écrit.
 A la différence de l'email, le forum situe la discussion dans un espace
 public ou semi-public (à plusieurs).</p><p>Pour utiliser l'outil de forum
 de Dokeos, les membres n'ont besoin que d'un simple navigateur web
 (Netscape, Explorer...), pas besoin d'outil de courriel (Eudora,
 Outlook...).</P><p>Pour organiser les forums, cliquez sur 'administrer'.
 Les échanges sont organisés de façon hiérarchique selon l'arborescence
 suivante:</p><p><b>Catégorie > Forum > Sujet > Réponse</b></p>Pour
 permettre aux membres de discuter de façon structurée, il est
 indispensable d'organiser les échanges préalablement en catégories et
 forums (à eux de créer sujets et réponses). Par défaut, le forum contient
 uniquement la catégorie Public, un sujet d'exemple et un message exemple.
 Vous pouvez ajouter des forums dans la catégorie public, ou bien modifier
 son intitulé ou encore créer d'autres catégories dans lesquelles il vous
 faudra alors créer de nouveaux forums. Une catégorie qui ne contient aucun
 forum ne s'affiche pas et est inutilisable. Si par exemple vous créez une
 catégorie 'discussions par petits groupes', il vous faudra créer une série
 de forums dans cette seconde catégorie, par exemple 'groupe 1', 'groupe
 2', 'groupe 3', etc.</p><p>La description d'un forum de groupe peut être
 la liste des personnes qui sont conviées à y discuter, mais aussi une
 explication sur sa raison d'être. Si vous créez, pour quelque raison que
 ce soit, un forum 'Appels à l'aide', vous pouvez y ajouter comme
 description: 'Signaler des difficultés par rapport au contenu ou par
 rapport au dispositif'.";

// help.php?open=Dropbox

$langHDropbox="Partage de fichiers";

$langDropboxContent="L'outil de partage affiche les fichiers qui vous ont été envoyés (la boîte de réception)
et les fichiers que vous envoyez aux autres membres de ce cours (la boîte d'envoi).
Si vous envoyez un fichier avec deux fois le même nom, vous pouvez écraser la version précédente avec la nouvelle.
<br><br>
En tant qu'étudiant, vous pouvez seulement envoyer un fichier à un enseignant du cours,
à moins que l'administrateur ait activé la communication étudiant-vers-étudiant.
<br><br>
Un enseignant peut envoyer un fichier à n'importe quel membre du cours.
<br><br>
L'administrateur peut activer l'upload de fichiers sans spécifier de destinataires.
<br><br>
Si la liste des fichiers reçus ou envoyés devient trop longue, vous pouvez supprimer tous les fichiers de la liste,
ou juste une partie. Le fichier lui-même n'est pas supprimé tant que l'autre partie peut le voir.
<br>
<h5>Informations supplémentaires pour les enseignants</h5>
Si l'envoi de mail est activé, vous pouvez uploader un mailing compressé au format ZIP en sélectionnant
---Mailing--- comme destination. Ensuite, vous devriez examiner le contenu du fichier ZIP, et s'il est correct,
l'envoyer à vos destinataires.
Les 2 opérations sont effectuées en cliquant sur les icones à la droite du nom du fichier ZIP, près de l'icone de suppression.
<br>
L'opération d'examen reporte l'erreur si un fichier incorrect est uploadé avec
---Mailing--- comme destination. Le fichier est automatiquement supprimé.
<br><br>
Le nom du fichier ZIP doit correspondre aux noms des fichiers qu'il contient.
Par exemple, si les fichiers se nomment mailing_19970001_20040229.doc,
mailing_19981234_20040229.pdf, ..., où 19970001 et 19981234 sont les codes officiels du cours,
alors donnez au fichier ZIP le nom mailing_STUDENTID_20040229.zip. Si les fichiers à l'intérieur du ZIP utilisent le nom d'identification du destinataire,
alors remplacez STUDENTID par LOGINNAME dans le nom du fichier ZIP.
<br><br>
Après avoir envoyé les fichiers, vous pouvez cliquer sur l'icone représentant un dossier de mailing, à la gauche du nom du fichier ZIP
pour obtenir la liste des fichiers avec leurs destinataires. Comme avec les autres fichiers gérés par l'outil de partage, la suppression des éléments de la liste ou du fichier ZIP lui-même
ne supprime pas le fichier de la boite de réception du destinataire.
<br><br>
Si l'écrasement de fichiers est activé, alors vous pouvez réenvoyer un fichier en lui donnant un nom existant.
Si le fichier ZIP contient des anciens fichiers, ils sont à nouveau envoyés.
<br><br>
Ne mettez aucun espace dans le nom des fichiers ZIP.
<br>";

// help.php?open=Home

$langHHome="Aide Page d'accueil";

$langHomeContent="La plupart des rubriques de votre espace Dokeos sont déjà remplies
 d'un texte ou d'un lien donnés par défaut ou pour l'exemple. Il vous
 revient de les modifier.</p><p>Ainsi un petit texte est là, bien visible,
 en en-tête de votre espace. Il commence par 'Ceci est le texte
 d'introduction de votre site...' Modifiez-le et profitez-en pour décrire
 votre espace, vos objectifs, votre dispositif. Il y va de la bonne
 visibilité de votre travail.</p><p>A la création de votre espace, de
 nombreux outils (Agenda, documents, tests...) sont activés pour vous
 par défaut. Il vous est conseillé de désactiver ceux que vous n'utilisez
 pas afin de ne pas faire perdre du temps à vos membres ou à vos
 visiteurs.</p><p>Il vous revient aussi
 de décider si votre espace doit apparaître dans la liste des espaces sur la page d'accueil du portail. Il est
 souhaitable qu'un espace à l'essai ou 'en chantier' n'apparaisse pas dans
 la liste (voir la fonction 'Propriétés de l'espace') et demeure privé sans
 possibilité d'inscription le temps de sa conception.</p>";

// help.php?open=Online

$langHOnline="Aide Conférence";
$langOnlineContent="Le système de conférence de Dokeos vous permet d'enseigner,
 informer ou rassembler jusqu'à 200 personnes d'une façon simple et rapide.<br>
 <ul>
   <li><b>audio :</b> la voix du présentateur est émise en direct vers les participants
   dans une qualité de radio FM grâce au streaming MP3</li>
   <li><b>contenu :</b> les participants suivent la présentation sur un fichier de type Power Point
   ou n'importe quel autre document</li>
   <li><b>interaction :</b> les participants posent leurs questions au présentateur à traver
   un chat web textuel</li>
 </ul>
 Le système de conférence de Dokeos représente le meilleur compromis entre faibles conditions
 techniques (impliquant bas prix, interopérabilité, grand nombre de participants potentiels)
 et interaction riche (question/réponses en temps réel).<br><br>
 Le présentateur a besoin de :<br>
 <ul>
   <li>n'importe quel ordinateur connecté à Internet</li>
   <li>n'importe quel microphone main libre bas prix</li>
   <li>n'importe quel navigateur web</li>
   <li>si Windows : le logiciel Winamp (<a href=\"http://www.winamp.com\" target=\"_blank\">www.winamp.com</a>)</li>
   <li>si Mac : le logiciel Quicktime broadcaster (<a href=\"http://www.quicktime.com\" target=\"_blank\">www.quicktime.com</a>)</li>
 </ul>
 Les participants ont besoin de :<br>
 <ul>
   <li>n'importe quel ordinateur connecté à Internet</li>
   <li>haut-parleurs ou écouteurs</li>
   <li>n'importe quel navigateur web</li>
   <li>si Windows ou Mac : Quicktime (<a href=\"http://www.quicktime.com\" target=\"_blank\">www.quicktime.com</a>)</li>
   <li>si Linux : XMMS</li>
 </ul>
 En cas de problème, n'hésitez pas à contacter Dokeos : <a href=\"http://www.dokeos.com\" target=\"_blank\">http://www.dokeos.com</a>.";

// help.php?open=Clar

$langHClar="Aide au démarrage";

$langClarContent="<br><p><b>Membre</b></p>
<p>
Pour visiter les espaces accessibles depuis la page d'accueil du portail,
il suffit de cliquer sur le code de l'espace dans la liste, sans inscription préalable.</p>
<p>Pour accéder aux espaces non accessibles depuis la page d'accueil du portail, il
est nécessaire de s'inscrire. Inscription > Entrez vos paramètres personnels >
Action: M'inscrire à des espaces > Cochez les espaces et validez.</p>
<p>Un courriel vous sera envoyé
pour vous rappeler pseudo et mot de passe à introduire lors de votre prochaine visite.</p>
<hr noshade size=1>
<p><b>Responsable</b></p>
<p><b>Créer un espace</b></p>
<p>Procédez come suit. Inscription > Remplissez tous les champs et choissez 'Créer des espaces' comme action > Validez > Entrer le nom de l'espace, sélectionnez une catégorie, entrez le code de l'espace (inventez-en un au besoin > Validez. Et vous voici dans la lsite de vos espaces. Cliquez sur l'intitulé de l'espace que vous venez de créer. Vous voici dans un espace vide à l'exception de quelques contenus factices destinés à vous éviter l'angoisse de la page blanche. A l'inscription, un courriel vous a été envoyé pour vous rappeler le pseudo et le mot de passe que vous devrez taper lors de votre prochaine visite.</p>
<p>En cas de problème, contactez votre responsable réseau ou le responsable de ce portail. Vous pouvez aussi publier un message sur le forum de support de <a href=http://www.dokeos.net>http://www.dokeos.net</a>.
</p>";




// help.php?open=Doc

$langHDoc="Aide documents";

$langDocContent="<p>Le module de gestion de document fonctionne de
 mani&egrave;re semblable &agrave; la gestion de vos documents sur un
 ordinateur. </p><p>Vous pouvez y d&eacute;poser des documents de tout type
 (HTML, Word, Powerpoint, Excel, Acrobat, Flash, Quicktime, etc.). Soyez
 attentifs cependant &agrave; ce que les membres disposent des
 outils n&eacute;cessaires &agrave; leur consultation. Soyez
 &eacute;galement vigilants &agrave; ne pas envoyer
  des documents infect&eacute;s par des virus. Il est prudent de soumettre
 son
  document &agrave; un logiciel antivirus &agrave; jour avant de le
 d&eacute;poser
  sur le portail.</p>
<p>Les documents sont pr&eacute;sent&eacute;s par ordre
 alphab&eacute;tique.<br>
  <b>Astuces:</b> si vous souhaitez que les documents soient class&eacute;s
 de
  mani&egrave;re diff&eacute;rente, vous pouvez les faire
 pr&eacute;c&eacute;der
  d'un num&eacute;ro, le classement se fera d&egrave;s lors sur cette base.
 </p>
<p>Vous pouvez :</p>
<h4>T&eacute;l&eacute;charger un document dans ce module</h4>
<ul>
  <li>S&eacute;lectionnez le document sur votre ordinateur &agrave; l'aide
 du
	bouton &quot;Parcourir&quot;
	<input type=submit value=Parcourir name=submit2>
	&agrave; droite de votre &eacute;cran.</li>
  <li>Ex&eacute;cutez le t&eacute;l&eacute;chargement &agrave; l'aide du
 bouton&quot;
	t&eacute;lecharger&quot;
	<input type=submit value=t&eacute;l&eacute;charger name=submit2>
	.</li>
</ul>
<h4>Renommer un document (ou un r&eacute;pertoire)</h4>
<ul>
  <li>cliquez sur le bouton <img src=../img/rename.gif width=20
 height=20 align=baseline>
	dans la colonne &quot;Renommer&quot;.</li>
  <li>Tapez le nouveau nom dans la zone pr&eacute;vue &agrave; cet effet
 qui appara&icirc;t
	en haut &agrave; gauche</li>
  <li>Valider en cliquant sur &quot;OK&quot;
	<input type=submit value=OK name=submit24>
	.
</ul>
	<h4>Supprimer un document (ou un r&eacute;pertoire)</h4>
	<ul>

  <li>Cliquer sur le bouton <img src=../img/delete.gif width=20
 height=20>
	dans la colonne &quot;Supprimer&quot;.</li>
	</ul>
	<h4>Rendre un document (ou un
 r&eacute;pertoire) invisible aux membres</h4>
	<ul>

  <li>Cliquez sur le bouton <img src=../img/visible.gif width=20
 height=20>dans
	la colonne &quot;Visible/invisible&quot;.</li>
	  <li>Le document (ou le r&eacute;pertoire) existe toujours, mais il n'est

		plus visible pour les membres.</li>
	</ul>
	<ul>

  <li> Si vous souhaitez rendre cet &eacute;l&eacute;ment &agrave; nouveau
 visible,
	cliquez sur le bouton <img src=../document/../img/invisible.gif
 width=24 height=20>
	dans la colonne Visible/invisible</li>
	</ul>
	<h4>Ajouter ou modifier un commentaire au document (ou au
 r&eacute;pertoire)</h4>
	<ul>

  <li>Cliquez sur le bouton <img
 src=../document/../img/comment.gif width=20 height=20>
	dans la colonne &quot;Commentaire&quot;</li>
	  <li>Tapez le nouveau commentaire dans la zone pr&eacute;vue &agrave; cet

		effet qui appara&icirc;tra en haut &agrave; gauche.</li>
	  <li>Validez en cliquant sur &quot;OK&quot;
		<input type=submit value=OK name=submit2>
		.</li>
	</ul>
	<p>Si vous souhaitez supprimer un commentaire, cliquez sur le bouton <img
 src=../document/../img/comment.gif width=20 height=20>,
	  effacez l'ancien commentaire de la zonne et validez en cliquant
 &quot;OK&quot;
	  <input type=submit value=OK name=submit22>
	  .
	<hr>
	<p>Vous pouvez aussi organiser le contenu du module de document en
 rangeant
	  les documents dans de r&eacute;pertoires. Pour ce faire vous devez :</p>
	<h4><b>Cr&eacute;er un r&eacute;pertoire</b></h4>
	<ul>
	  <li>Cliquez sur la commande &quot;<img
 src=../document/../img/file.gif width=20
 height=20>cr&eacute;er
		un r&eacute;pertoire&quot; en haut &agrave; gauche de l'&eacute;cran</li>
	  <li>Tapez le nom de votre nouveau r&eacute;pertoire dans la zone
 pr&eacute;vue
		&agrave; cet effet en haut &agrave; gauche de l'&eacute;cran.</li>
	  <li>Validez en cliquant &quot;OK&quot;
		<input type=submit value=OK name=submit23>
		.</li>
	</ul>
	<h4>D&eacute;placer un document (ou un r&eacute;pertoire)</h4>
	<ul>
	  <li>Cliquez sur le bouton <img
 src=../document/../img/move.gif width=34 height=16>
		dans la colonne d&eacute;placer</li>
	  <li>Choisissez le r&eacute;pertoire dans lequel vous souhaitez
 d&eacute;placer
		le document ou le r&eacute;pertoire dans le menu d&eacute;roulant
 pr&eacute;vu
		&agrave; cet effet qui appara&icirc;tra en haut &agrave; gauche.(note:
		le mot &quot;racine&quot; dans ce menu repr&eacute;sente la racine de
		votre module document).</li>
	  <li>Validez en cliquant &quot;OK&quot;
		<input type=submit value=OK name=submit232>
		.</li>
	</ul>
	<center>
	  <p>";



// help.php?open=User

$langHUser="Aide membres";
$langUserContent="<b>Droits d'administration</b>
<p>Pour permettre à un co-responsable, un chef de projet ou qui que ce
 soit de co-administrer l'espace avec vous, vous devez préalablement
 l'inscrire à votre espace ou vous assurer qu'il est inscrit puis modifier
 ses droits en cochant 'modifier' sous 'droits d'admin.' puis
 'tous'.</P><hr>
<b>Co-responsables</b>
<p>Pour faire figurer le nom d'un co-responsable dans l'en-tête de votre
 esace, utilisez la page 'Modifier info espace' (dans les outils orange
 sur la page d'accueil de votre formation). Cette modification de l'en-tête
 de l'espace n'inscrit pas automatiquement ce co-responsable comme membre de l'espace. Ce sont deux actions distinctes.</p><hr>
<b>Ajouter un membre</b>
<p>Pour ajouter un membre à votre espace, remplissez les champs
et validez. La personne recevra un courriel de confirmation de son
inscription contenant son pseudo et son mot de passe, sauf si
vous n'avez pas introduit son email.</p>";



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
	si les membres sont déjà inscrits à l'espace, ce qui peut être vérifié via l'outil
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
?>

<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Tour guidé';
$strings['plugin_comment'] = 'Ce plugin montre aux utilisateurs comment utiliser votre portail Chamilo. Vous devez activer une région (p.ex. "header-right") afin d\'afficher le bouton qui permet de démarrer le processus.';

/* Strings for settings */
$strings['show_tour'] = 'Activer le tour guidé';

$showTourHelpLine01 = 'La configuration nécessaire à l\'affichage du bloc d\'aide, au format JSON, se situe dans le fichier %splugin/tour/config/tour.json%s.';
$showTourHelpLine02 = 'Voir fichier README pour plus d\'info.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", '<strong>', '</strong>', '<br>');

$strings['theme'] = 'Thème';
$strings['theme_help'] = 'Choisissez entre <i>nassim</i>, <i>nazanin</i> et <i>royal</i>. Vide pour utiliser le thème par défaut.';

/* Strings for plugin UI */
$strings['Skip'] = 'Passer';
$strings['Next'] = 'Suivant';
$strings['Prev'] = 'Précédent';
$strings['Done'] = 'Terminé';
$strings['StartButtonText'] = 'Tour guidé';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Bienvenu(e) dans <b>Chamilo LMS</b>';
$strings['TheNavbarStep'] = 'Barre de menu, reprenant les sections principales.';
$strings['TheRightPanelStep'] = 'Panneau latéral de menus';
$strings['TheUserImageBlock'] = 'Votre photo de profil utilisateur';
$strings['TheProfileBlock'] = 'Vos outils perso: <i>Boîte de messages</i>, <i>Composer des messages</i>, <i>Invitations en attente</i>, <i>Édition du profil</i>.';
$strings['TheHomePageStep'] = 'Ceci est la page d\'accueil du portail. On y retrouve les annonces du portail, une section d\'introduction, des liens, etc, selon ce que l\'équipe d\'administration a préparé';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Cette zone affiche les différents cours (ou sessions) auxquels vous avez accès. Si aucun cours ne s\'affiche, rendez-vous sur le catalogue de cours (voir menu) ou parlez-en à votre administrateur de portail';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'L\'outil d\'agenda vous permet de voir les événements qui sont programmés pour les prochains jours, semaines ou mois.';
$strings['AgendaTheActionBar'] = 'Vous pouvez décider de montrer les événements sous forme de liste, plutôt qu\'en vue calendrier, en utilisant les icônes d\'action fournis';
$strings['AgendaTodayButton'] = 'Cliquez sur le bouton "Aujourd\'hui pour voir seulement le programme d\'aujourd\'hui';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'Le mois actuel est toujours mis en évidence dans la vue calendrier';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Vous pouvez changer la vue à quotidien, semanal ou mensuel en cliquant sur l\'un de ces boutons';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Cette zone vous permet de vérifier votre progrès si vous êtes étudiant, ou le progrès de vos étudiants si vous êtes enseignant';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Les rapports fournis sur cet écran sont extensibles et peuvent vous fournir un détail intéressant sur votre apprentissage ou la façon dont vous enseignez.';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'La zone sociale vous permet de vous maintenir au courant de ce que font les autres utilisateurs de la plateforme';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'Le menu vous donne accès à une série d\'écrans vous permettant de participer à de la messagerie privée, du chat, des groupes d\'intérêt, etc';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'The Dashboard allows you to get very specific information in an illustrated and condensed format. Only administrators have access to this feature at this time';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'To enable Dashboard panels, you must first activate the possible panels in the admin section for plugins, then come back here and choose which panels *you* want to see on your dashboard';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'The administration panel allows you to manage all resources in your Chamilo portal';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'The users block allows you to manage all things related to users.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'The courses block gives you access to course creation, edition, etc. Other blocks are dedicated to specific uses as well.';

$strings['tour_home_featured_courses_title'] = 'Cours mis en avant';
$strings['tour_home_featured_courses_content'] = 'Cette section affiche les cours mis en avant disponibles sur votre page d’accueil.';
$strings['tour_home_course_card_title'] = 'Carte du cours';
$strings['tour_home_course_card_content'] = 'Chaque carte résume un cours et vous donne un accès rapide à ses informations principales.';
$strings['tour_home_course_title_title'] = 'Titre du cours';
$strings['tour_home_course_title_content'] = 'Le titre du cours vous aide à l’identifier rapidement et peut aussi ouvrir plus d’informations selon la configuration de la plateforme.';
$strings['tour_home_teachers_title'] = 'Enseignants';
$strings['tour_home_teachers_content'] = 'Cette zone affiche les enseignants ou les utilisateurs associés au cours.';
$strings['tour_home_rating_title'] = 'Évaluation et avis';
$strings['tour_home_rating_content'] = 'Ici, vous pouvez consulter l’évaluation du cours et, lorsque cela est autorisé, envoyer votre propre note.';
$strings['tour_home_main_action_title'] = 'Action principale du cours';
$strings['tour_home_main_action_content'] = 'Utilisez ce bouton pour entrer dans le cours, vous inscrire ou consulter les restrictions d’accès selon l’état du cours.';
$strings['tour_home_show_more_title'] = 'Afficher plus de cours';
$strings['tour_home_show_more_content'] = 'Utilisez ce bouton pour charger davantage de cours et continuer à explorer le catalogue depuis la page d’accueil.';
$strings['tour_my_courses_cards_title'] = 'Vos cartes de cours';
$strings['tour_my_courses_cards_content'] = 'Cette page liste les cours auxquels vous êtes inscrit. Chaque carte vous donne un accès rapide au cours et à son état actuel.';
$strings['tour_my_courses_image_title'] = 'Image du cours';
$strings['tour_my_courses_image_content'] = 'L’image du cours vous aide à l’identifier rapidement. Dans la plupart des cas, un clic dessus ouvre le cours.';
$strings['tour_my_courses_title_title'] = 'Titre du cours et de la session';
$strings['tour_my_courses_title_content'] = 'Ici, vous pouvez voir le titre du cours et, le cas échéant, le nom de la session associée à ce cours.';
$strings['tour_my_courses_progress_title'] = 'Progression d’apprentissage';
$strings['tour_my_courses_progress_content'] = 'Cette barre de progression montre quelle partie du cours vous avez déjà complétée.';
$strings['tour_my_courses_notifications_title'] = 'Notifications de nouveau contenu';
$strings['tour_my_courses_notifications_content'] = 'Utilisez ce bouton en forme de cloche pour vérifier si le cours contient du nouveau contenu ou des mises à jour récentes. Lorsqu’il est mis en évidence, il vous aide à repérer rapidement les changements depuis votre dernier accès.';
$strings['tour_my_courses_footer_title'] = 'Enseignants et détails du cours';
$strings['tour_my_courses_footer_content'] = 'Le pied de carte peut afficher les enseignants, la langue et d’autres informations utiles liées au cours.';
$strings['tour_my_courses_create_course_title'] = 'Créer un cours';
$strings['tour_my_courses_create_course_content'] = 'Si vous avez la permission de créer des cours, utilisez ce bouton pour ouvrir directement le formulaire de création de cours depuis cette page.';
$strings['tour_course_home_header_title'] = 'En-tête du cours';
$strings['tour_course_home_header_content'] = 'Cet en-tête affiche le titre du cours et, le cas échéant, la session active. Il regroupe également les principales actions de l’enseignant disponibles sur cette page.';
$strings['tour_course_home_title_title'] = 'Titre du cours';
$strings['tour_course_home_title_content'] = 'Ici, vous pouvez identifier rapidement le cours actuel. Si le cours appartient à une session, le titre de la session est affiché à côté.';
$strings['tour_course_home_teacher_tools_title'] = 'Outils de l’enseignant';
$strings['tour_course_home_teacher_tools_content'] = 'Selon vos permissions, cette zone peut inclure le basculement vers la vue étudiant, la modification de l’introduction, l’accès aux rapports et d’autres actions de gestion du cours.';
$strings['tour_course_home_intro_title'] = 'Introduction du cours';
$strings['tour_course_home_intro_content'] = 'Cette section affiche l’introduction du cours. Les enseignants peuvent l’utiliser pour présenter les objectifs, les consignes, les liens ou les informations importantes pour les apprenants.';
$strings['tour_course_home_tools_controls_title'] = 'Contrôles des outils';
$strings['tour_course_home_tools_controls_content'] = 'Les enseignants peuvent utiliser ces contrôles pour afficher ou masquer tous les outils à la fois, ou activer le mode de tri afin de réorganiser les outils du cours.';
$strings['tour_course_home_tools_title'] = 'Outils du cours';
$strings['tour_course_home_tools_content'] = 'Cette zone contient les principaux outils du cours, tels que les documents, parcours d’apprentissage, exercices, forums et autres ressources disponibles dans le cours.';
$strings['tour_course_home_tool_card_title'] = 'Carte de l’outil';
$strings['tour_course_home_tool_card_content'] = 'Chaque carte donne accès à un outil du cours. Utilisez-la pour entrer rapidement dans la zone sélectionnée du cours.';
$strings['tour_course_home_tool_shortcut_title'] = 'Raccourci de l’outil';
$strings['tour_course_home_tool_shortcut_content'] = 'Cliquez sur la zone de l’icône pour ouvrir directement l’outil de cours sélectionné.';
$strings['tour_course_home_tool_name_title'] = 'Nom de l’outil';
$strings['tour_course_home_tool_name_content'] = 'Le titre identifie l’outil et fonctionne également comme un lien d’accès direct.';
$strings['tour_course_home_tool_visibility_title'] = 'Visibilité de l’outil';
$strings['tour_course_home_tool_visibility_content'] = 'Si vous modifiez le cours, ce bouton vous permet de changer rapidement la visibilité de l’outil pour les apprenants.';
$strings['tour_admin_overview_title'] = 'Tableau de bord d’administration';
$strings['tour_admin_overview_content'] = 'Cette page centralise les principales zones d’administration de la plateforme, regroupées par thème de gestion.';
$strings['tour_admin_user_management_title'] = 'Gestion des utilisateurs';
$strings['tour_admin_user_management_content'] = 'Depuis ce bloc, vous pouvez gérer les utilisateurs enregistrés, créer des comptes, importer ou exporter des listes d’utilisateurs, modifier des utilisateurs, anonymiser des données et gérer les classes.';
$strings['tour_admin_course_management_title'] = 'Gestion des cours';
$strings['tour_admin_course_management_content'] = 'Ce bloc vous permet de créer et gérer des cours, importer ou exporter des listes de cours, organiser les catégories, affecter des utilisateurs aux cours et configurer les champs et outils liés aux cours.';
$strings['tour_admin_sessions_management_title'] = 'Gestion des sessions';
$strings['tour_admin_sessions_management_content'] = 'Ici, vous pouvez gérer les sessions de formation, les catégories de session, les imports et exports, les responsables RH, les carrières, les promotions et les champs liés aux sessions.';
$strings['tour_admin_platform_management_title'] = 'Gestion de la plateforme';
$strings['tour_admin_platform_management_content'] = 'Utilisez ce bloc pour configurer la plateforme globalement, ajuster les paramètres, gérer les annonces, les langues et d’autres options centrales d’administration.';
$strings['tour_admin_tracking_title'] = 'Suivi';
$strings['tour_admin_tracking_content'] = 'Cette zone donne accès aux rapports, statistiques globales, analyses d’apprentissage et autres données de suivi de la plateforme.';
$strings['tour_admin_assessments_title'] = 'Évaluations';
$strings['tour_admin_assessments_content'] = 'Ce bloc donne accès aux fonctionnalités administratives liées aux évaluations disponibles sur la plateforme.';
$strings['tour_admin_skills_title'] = 'Compétences';
$strings['tour_admin_skills_content'] = 'Ce bloc vous permet de gérer les compétences des utilisateurs, les imports de compétences, les classements, les niveaux et les évaluations liées aux compétences.';
$strings['tour_admin_system_title'] = 'Système';
$strings['tour_admin_system_content'] = 'Ici, vous pouvez accéder aux outils de maintenance du serveur et de la plateforme, comme l’état du système, le nettoyage des fichiers temporaires, le remplissage de données, les tests e-mail et d’autres utilitaires techniques.';
$strings['tour_admin_rooms_title'] = 'Salles';
$strings['tour_admin_rooms_content'] = 'Ce bloc donne accès aux fonctionnalités de gestion des salles, y compris les branches, les salles et la recherche de disponibilité des salles.';
$strings['tour_admin_security_title'] = 'Sécurité';
$strings['tour_admin_security_content'] = 'Utilisez cette zone pour consulter les tentatives de connexion, les rapports liés à la sécurité et d’autres outils de sécurité disponibles sur la plateforme.';
$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Ce bloc fournit les références officielles de Chamilo, les guides utilisateur, les forums, les ressources d’installation et les liens vers les fournisseurs de services et les informations du projet.';
$strings['tour_admin_health_check_title'] = 'Contrôle de santé';
$strings['tour_admin_health_check_content'] = 'Cette zone vous aide à vérifier la santé technique de la plateforme en affichant les contrôles d’environnement, les chemins accessibles en écriture et les avertissements importants d’installation.';
$strings['tour_admin_version_check_title'] = 'Vérification de version';
$strings['tour_admin_version_check_content'] = 'Utilisez ce bloc pour enregistrer votre portail et activer les fonctions de vérification de version ainsi que les options de liste publique de la plateforme.';
$strings['tour_admin_professional_support_title'] = 'Support professionnel';
$strings['tour_admin_professional_support_content'] = 'Ce bloc explique comment contacter les fournisseurs officiels de Chamilo pour le conseil, l’hébergement, la formation et le support de développements personnalisés.';
$strings['tour_admin_news_title'] = 'Actualités de Chamilo';
$strings['tour_admin_news_content'] = 'Cette section affiche les dernières nouvelles et annonces du projet Chamilo.';

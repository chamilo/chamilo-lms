<?php
/* For licensing terms, see /license.txt */
/**
 * Strings para o português brasileiro L10n.
 *
 * @author Igor Oliveira Souza <igor@igoroliveira.eng.br>
 *
 * @package chamilo.plugin.tour
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'Este plugin demonstra aos usuários como usar o ambiente Chamilo LMS. Você deve ativar uma região (por exemplo, "header_right") para mostrar um botão que permita ao usuário começar o tour.';

/* Strings for settings */
$strings['show_tour'] = 'Mostrar o tour';

$showTourHelpLine01 = 'As configurações necessárias para mostrar o bloco de ajuda, no formato JSON, está localizada no arquivo %plugin/tour/config/tour.json%s.';
$showTourHelpLine02 = 'Veja o arquivo README para mais informações.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", "<strong>", "</strong>", "<br>");

$strings['theme'] = 'Tema';
$strings['theme_help'] = 'Escolha <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Deixe em branco para usar o tema padrão.';

/* Strings for plugin UI */
$strings['Skip'] = 'Pular';
$strings['Next'] = 'Próximo';
$strings['Prev'] = 'Anterior';
$strings['Done'] = 'Finalizar';
$strings['StartButtonText'] = 'Começar o tour';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Bem vindo ao <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Barra de menus com os links das seções principais do portal';
$strings['TheRightPanelStep'] = 'Painel lateral';
$strings['TheUserImageBlock'] = 'Sua foto de perfil';
$strings['TheProfileBlock'] = 'Suas ferramentas de perfil: <i>Caixa de entrada</i>, <i>Escrever mensagem</i>, <i>Convites pendentes</i>, <i>Editar perfil</i>.';
$strings['TheHomePageStep'] = 'Esta é a página principal, onde você encontrará anúncios importantes, links e qualquer outra informação que a equipe administrativa julgar importante.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Esta área mostra os diferentes cursos (ou sessões) que você se inscreveu. Se não aparecer nenhum curso, vá para o catálogo de cursos (no menu de Cursos mais abaixo) ou entre em contato com o administrador do portal';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'A agenda pessoal permite que você veja quais eventos estão agendados para os próximos dias, semanas ou meses.';
$strings['AgendaTheActionBar'] = 'Você pode escolher se quer mostrar os eventos como uma lista, ou situados na visão do calendário, usando os botões de ações.';
$strings['AgendaTodayButton'] = 'Clique no botão "hoje" para retornar ao mês atual e ver suas tarefas mais recentes';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'O mês atual é sempre colocado em evidência na visão do calendário';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Você pode alternar entre ver tarefas diárias, semanais ou mensais clicando nestes botões';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Esta área permite que você acompanhe seu progresso se você for um estudante, ou o progresso dos seus estudantes se você for um professor';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Os relatórios fornecidos nesta tela são extensíveis e podem providenciar informações valiosas para acompanhar o seu aprendizado ou o seu ensino';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'A área de Rede Social permite que você entre em contato com outros usuários da plataforma';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'O menu te permite acessar uma série de telas que te permitirão conversar em privado, chat, grupos de interesse, etc.';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'O painel te permite obter informações muito específicas em um formato condensado e ilustrado. No momento, apenas administradores possuem acesso a este recurso';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'Para habilitar os blocos do painel, você primeiro precisa ativá-los no menu de extensões da Área Restrita. Após ativados, volte a esta tela e escolha quais blocos você quer visualizar no seu painel';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'A Área Restrita te permite gerenciar todos os recursos no seu portal Chamilo';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'O bloco de Usuários te permite gerenciar tudo o que estiver relacionado aos usuários.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'O bloco de Cursos te dá acesso à criação de cursos, edição, etc. Outros blocos também são dedicados a usos específicos.';

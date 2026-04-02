<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'Este plugin mostra às pessoas como usar o seu Chamilo LMS. Deve ativar uma região (ex: "header-right") para mostrar o botão que permite iniciar o tour.';

/* Strings for settings */
$strings['show_tour'] = 'Mostrar o tour';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'A configuração necessária para mostrar os blocos de ajuda, em formato JSON, está localizada no ficheiro <strong>plugin/tour/config/tour.json</strong>. <br> Consulte o ficheiro README para mais informações.';

$strings['theme'] = 'Tema';
$strings['theme_help'] = 'Escolha <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Deixe vazio para usar o tema predefinido.';

/* Strings for plugin UI */
$strings['Skip'] = 'Saltar';
$strings['Next'] = 'Seguinte';
$strings['Prev'] = 'Anterior';
$strings['Done'] = 'Concluído';
$strings['StartButtonText'] = 'Iniciar o tour';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Bem-vindo ao <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Barra de menu com ligações para as secções principais do portal';
$strings['TheRightPanelStep'] = 'Painel lateral';
$strings['TheUserImageBlock'] = 'A sua foto de perfil';
$strings['TheProfileBlock'] = 'As suas ferramentas de perfil: <i>Caixa de entrada</i>, <i>redator de mensagens</i>, <i>convites pendentes</i>, <i>edição de perfil</i>.';
$strings['TheHomePageStep'] = 'Esta é a página inicial onde encontra os anúncios do portal, ligações e qualquer informação configurada pela equipa de administração.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Esta área mostra os diferentes cursos (ou sessões) aos quais está inscrito. Se nenhum curso aparecer, vá ao catálogo de cursos (ver menu) ou discuta com o administrador do portal';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'A ferramenta agenda permite ver os eventos agendados para os próximos dias, semanas ou meses.';
$strings['AgendaTheActionBar'] = 'Pode optar por mostrar os eventos como lista, em vez de vista de calendário, usando os ícones de ação fornecidos';
$strings['AgendaTodayButton'] = 'Clique no botão "hoje" para ver apenas a agenda de hoje';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'O mês atual é sempre destacado na vista de calendário';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Pode alternar a vista para diária, semanal ou mensal clicando num destes botões';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Esta área permite verificar o seu progresso se for aluno, ou o progresso dos seus alunos se for professor';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Os relatórios fornecidos nesta página são extensíveis e podem fornecer-lhe informações valiosas sobre a sua aprendizagem ou ensino';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'A área social permite contactar outros utilizadores da plataforma';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'O menu dá acesso a uma série de páginas que permitem participar em mensagens privadas, chat, grupos de interesse, etc';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'O Dashboard permite obter informações específicas num formato ilustrado e condensado. Apenas administradores têm acesso a esta funcionalidade por agora';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'Para ativar painéis do Dashboard, deve primeiro ativar os painéis possíveis na secção de administração para plugins, depois volte aqui e escolha quais painéis *você* quer ver no seu dashboard';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'O painel de administração permite gerir todos os recursos no seu portal Chamilo';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'O bloco de utilizadores permite gerir tudo relacionado com utilizadores.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'O bloco de cursos dá acesso à criação, edição de cursos, etc. Outros blocos são dedicados a usos específicos também.';


$strings['tour_home_featured_courses_title'] = 'Cursos em destaque';
$strings['tour_home_featured_courses_content'] = 'Esta secção mostra os cursos em destaque disponíveis na sua página inicial.';

$strings['tour_home_course_card_title'] = 'Cartão de curso';
$strings['tour_home_course_card_content'] = 'Cada cartão resume um curso e dá acesso rápido às suas informações principais.';

$strings['tour_home_course_title_title'] = 'Título do curso';
$strings['tour_home_course_title_content'] = 'O título do curso ajuda a identificá-lo rapidamente e pode abrir mais informações dependendo das configurações da plataforma.';

$strings['tour_home_teachers_title'] = 'Professores';
$strings['tour_home_teachers_content'] = 'Esta área mostra os professores ou utilizadores associados ao curso.';

$strings['tour_home_rating_title'] = 'Classificação e feedback';
$strings['tour_home_rating_content'] = 'Aqui pode rever a classificação do curso e, quando permitido, submeter o seu voto.';

$strings['tour_home_main_action_title'] = 'Ação principal do curso';
$strings['tour_home_main_action_content'] = 'Use este botão para entrar no curso, inscrever-se ou rever restrições de acesso dependendo do estado do curso.';

$strings['tour_home_show_more_title'] = 'Mostrar mais cursos';
$strings['tour_home_show_more_content'] = 'Use este botão para carregar mais cursos e continuar a explorar o catálogo a partir da página inicial.';

$strings['tour_my_courses_cards_title'] = 'Os seus cartões de curso';
$strings['tour_my_courses_cards_content'] = 'Esta página lista os cursos aos quais está inscrito. Cada cartão dá acesso rápido ao curso e ao seu estado atual.';

$strings['tour_my_courses_image_title'] = 'Imagem do curso';
$strings['tour_my_courses_image_content'] = 'A imagem do curso ajuda a identificá-lo rapidamente. Na maioria dos casos, clicar nela abre o curso.';

$strings['tour_my_courses_title_title'] = 'Título do curso e sessão';
$strings['tour_my_courses_title_content'] = 'Aqui pode ver o título do curso e, quando aplicável, o nome da sessão associada a esse curso.';

$strings['tour_my_courses_progress_title'] = 'Progresso de aprendizagem';
$strings['tour_my_courses_progress_content'] = 'Esta barra de progresso mostra quanto do curso completou.';

$strings['tour_my_courses_notifications_title'] = 'Notificações de novo conteúdo';
$strings['tour_my_courses_notifications_content'] = 'Use este botão de sino para verificar se o curso tem novo conteúdo ou atualizações recentes. Quando destacado, ajuda-o a detetar rapidamente as alterações desde o último acesso.';

$strings['tour_my_courses_footer_title'] = 'Professores e detalhes do curso';
$strings['tour_my_courses_footer_content'] = 'O rodapé pode mostrar professores, idioma e outra informação útil relacionada com o curso.';

$strings['tour_my_courses_create_course_title'] = 'Criar um curso';
$strings['tour_my_courses_create_course_content'] = 'Se tiver permissão para criar cursos, use este botão para abrir o formulário de criação de curso diretamente desta página.';

$strings['tour_course_home_header_title'] = 'Cabeçalho do curso';
$strings['tour_course_home_header_content'] = 'Este cabeçalho mostra o título do curso e, quando aplicável, a sessão ativa. Também agrupa as principais ações do professor disponíveis nesta página.';

$strings['tour_course_home_title_title'] = 'Título do curso';
$strings['tour_course_home_title_content'] = 'Aqui pode identificar rapidamente o curso atual. Se o curso pertencer a uma sessão, o título da sessão é exibido ao lado.';

$strings['tour_course_home_teacher_tools_title'] = 'Ferramentas do professor';
$strings['tour_course_home_teacher_tools_content'] = 'Dependendo das suas permissões, esta área pode incluir a alternância para a vista do aluno, edição da introdução, acesso a relatórios e ações adicionais de gestão do curso.';

$strings['tour_course_home_intro_title'] = 'Introdução ao curso';
$strings['tour_course_home_intro_content'] = 'Esta secção exibe a introdução do curso. Os professores podem usá-la para apresentar objetivos, orientação, ligações ou informação chave para os formandos.';

$strings['tour_course_home_tools_controls_title'] = 'Controlos das ferramentas';
$strings['tour_course_home_tools_controls_content'] = 'Os professores podem usar estes controlos para mostrar ou ocultar todas as ferramentas de uma vez, ou ativar o modo de ordenação para reorganizar as ferramentas do curso.';

$strings['tour_course_home_tools_title'] = 'Ferramentas do curso';
$strings['tour_course_home_tools_content'] = 'Esta área contém as principais ferramentas do curso, como documentos, percursos de aprendizagem, exercícios, fóruns e outros recursos disponíveis no curso.';

$strings['tour_course_home_tool_card_title'] = 'Cartão da ferramenta';
$strings['tour_course_home_tool_card_content'] = 'Cada cartão de ferramenta dá acesso a uma ferramenta do curso. Use-o para entrar rapidamente na área selecionada do curso.';

$strings['tour_course_home_tool_shortcut_title'] = 'Atalho da ferramenta';
$strings['tour_course_home_tool_shortcut_content'] = 'Clique na área do ícone para abrir diretamente a ferramenta do curso selecionada.';

$strings['tour_course_home_tool_name_title'] = 'Nome da ferramenta';
$strings['tour_course_home_tool_name_content'] = 'O título identifica a ferramenta e também funciona como uma ligação de acesso direto.';

$strings['tour_course_home_tool_visibility_title'] = 'Visibilidade da ferramenta';
$strings['tour_course_home_tool_visibility_content'] = 'Se estiver a editar o curso, este botão permite-lhe alterar rapidamente a visibilidade da ferramenta para os formandos.';
$strings['tour_admin_overview_title'] = 'Painel de administração';
$strings['tour_admin_overview_content'] = 'Esta página centraliza as principais áreas de administração da plataforma, agrupadas por tema de gestão.';

$strings['tour_admin_user_management_title'] = 'Gestão de utilizadores';
$strings['tour_admin_user_management_content'] = 'A partir deste bloco pode gerir utilizadores registados, criar contas, importar ou exportar listas de utilizadores, editar utilizadores, anonimizar dados e gerir turmas.';

$strings['tour_admin_course_management_title'] = 'Gestão de cursos';
$strings['tour_admin_course_management_content'] = 'Este bloco permite-lhe criar e gerir cursos, importar ou exportar listas de cursos, organizar categorias, atribuir utilizadores a cursos e configurar campos e ferramentas relacionados com cursos.';

$strings['tour_admin_sessions_management_title'] = 'Gestão de sessões';
$strings['tour_admin_sessions_management_content'] = 'Aqui pode gerir sessões de formação, categorias de sessão, importações e exportações, diretores de RH, carreiras, promoções e campos relacionados com sessões.';

$strings['tour_admin_platform_management_title'] = 'Gestão da plataforma';
$strings['tour_admin_platform_management_content'] = 'Use este bloco para configurar a plataforma globalmente, ajustar definições, gerir anúncios, idiomas e outras opções de administração central.';

$strings['tour_admin_tracking_title'] = 'Acompanhamento';
$strings['tour_admin_tracking_content'] = 'Esta área dá acesso a relatórios, estatísticas globais, análises de aprendizagem e outros dados de acompanhamento em toda a plataforma.';

$strings['tour_admin_assessments_title'] = 'Avaliações';
$strings['tour_admin_assessments_content'] = 'Este bloco fornece acesso às funcionalidades de administração relacionadas com avaliações disponíveis na plataforma.';
$strings['tour_admin_skills_title'] = 'Competências';
$strings['tour_admin_skills_content'] = 'Este bloco permite-lhe gerir competências de utilizador, importações de competências, classificações, níveis e avaliações relacionadas com competências.';

$strings['tour_admin_system_title'] = 'Sistema';
$strings['tour_admin_system_content'] = 'Aqui pode aceder a ferramentas de manutenção do servidor e da plataforma, como estado do sistema, limpeza de ficheiros temporários, preenchimento de dados, testes de e-mail e utilitários técnicos.';

$strings['tour_admin_rooms_title'] = 'Salas';
$strings['tour_admin_rooms_content'] = 'Este bloco dá acesso às funcionalidades de gestão de salas, incluindo filiais, salas e pesquisa de disponibilidade de salas.';

$strings['tour_admin_security_title'] = 'Segurança';
$strings['tour_admin_security_content'] = 'Utilize esta área para rever tentativas de login, relatórios relacionados com segurança e ferramentas adicionais de segurança disponíveis na plataforma.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Este bloco fornece referências oficiais do Chamilo, guias do utilizador, fóruns, recursos de instalação e ligações para fornecedores de serviços e informação do projeto.';

$strings['tour_admin_health_check_title'] = 'Verificação de saúde';
$strings['tour_admin_health_check_content'] = 'Esta área ajuda-o a rever a saúde técnica da plataforma listando verificações de ambiente, caminhos graváveis e avisos importantes de instalação.';

$strings['tour_admin_version_check_title'] = 'Verificação de versão';
$strings['tour_admin_version_check_content'] = 'Utilize este bloco para registar o seu portal e ativar funcionalidades de verificação de versão e opções de listagem pública da plataforma.';

$strings['tour_admin_professional_support_title'] = 'Suporte profissional';
$strings['tour_admin_professional_support_content'] = 'Este bloco explica como contactar fornecedores oficiais do Chamilo para consultoria, alojamento, formação e suporte de desenvolvimento personalizado.';

$strings['tour_admin_news_title'] = 'Notícias do Chamilo';
$strings['tour_admin_news_content'] = 'Esta secção apresenta notícias e anúncios recentes do projeto Chamilo.';

$strings['tour_home_topbar_logo_title'] = 'Logótipo da plataforma';
$strings['tour_home_topbar_logo_content'] = 'Este logótipo leva-o de volta à página inicial da plataforma.';
$strings['tour_home_topbar_actions_title'] = 'Ações rápidas';
$strings['tour_home_topbar_actions_content'] = 'Aqui encontra ícones de atalho, como criação de cursos, ajuda guiada, tickets e mensagens, consoante o seu papel.';
$strings['tour_home_menu_button_title'] = 'Botão do menu';
$strings['tour_home_menu_button_content'] = 'Use este botão para abrir ou fechar rapidamente o menu lateral.';
$strings['tour_home_sidebar_title'] = 'Menu principal';
$strings['tour_home_sidebar_content'] = 'Este menu lateral dá acesso às principais secções da plataforma, consoante as suas permissões.';
$strings['tour_home_user_area_title'] = 'Área do utilizador';
$strings['tour_home_user_area_content'] = 'Aqui pode aceder ao seu perfil, opções pessoais e terminar a sessão.';

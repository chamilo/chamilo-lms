<?php

/* For licensing terms, see /license.txt */
/**
 * Strings para o português brasileiro L10n.
 *
 * @author Igor Oliveira Souza <igor@igoroliveira.eng.br>
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'Este plugin demonstra aos usuários como usar o ambiente Chamilo LMS. Você deve ativar uma região (por exemplo, "header_right") para mostrar um botão que permita ao usuário começar o tour.';

/* Strings for settings */
$strings['show_tour'] = 'Mostrar o tour';

$showTourHelpLine01 = 'As configurações necessárias para mostrar o bloco de ajuda, no formato JSON, está localizada no arquivo %plugin/tour/config/tour.json%s.';
$showTourHelpLine02 = 'Veja o arquivo README para mais informações.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", '<strong>', '</strong>', '<br>');

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

$strings['tour_home_featured_courses_title'] = 'Cursos em destaque';
$strings['tour_home_featured_courses_content'] = 'Esta seção mostra os cursos em destaque disponíveis na sua página inicial.';
$strings['tour_home_course_card_title'] = 'Cartão do curso';
$strings['tour_home_course_card_content'] = 'Cada cartão resume um curso e oferece acesso rápido às suas informações principais.';
$strings['tour_home_course_title_title'] = 'Título do curso';
$strings['tour_home_course_title_content'] = 'O título do curso ajuda você a identificá-lo rapidamente e também pode abrir mais informações, dependendo das configurações da plataforma.';
$strings['tour_home_teachers_title'] = 'Professores';
$strings['tour_home_teachers_content'] = 'Esta área mostra os professores ou usuários associados ao curso.';
$strings['tour_home_rating_title'] = 'Avaliação e feedback';
$strings['tour_home_rating_content'] = 'Aqui você pode ver a avaliação do curso e, quando permitido, enviar seu próprio voto.';
$strings['tour_home_main_action_title'] = 'Ação principal do curso';
$strings['tour_home_main_action_content'] = 'Use este botão para entrar no curso, inscrever-se ou revisar restrições de acesso, dependendo do status do curso.';
$strings['tour_home_show_more_title'] = 'Mostrar mais cursos';
$strings['tour_home_show_more_content'] = 'Use este botão para carregar mais cursos e continuar explorando o catálogo a partir da página inicial.';
$strings['tour_my_courses_cards_title'] = 'Seus cartões de curso';
$strings['tour_my_courses_cards_content'] = 'Esta página lista os cursos em que você está inscrito. Cada cartão oferece acesso rápido ao curso e ao seu status atual.';
$strings['tour_my_courses_image_title'] = 'Imagem do curso';
$strings['tour_my_courses_image_content'] = 'A imagem do curso ajuda você a identificá-lo rapidamente. Na maioria dos casos, clicar nela abre o curso.';
$strings['tour_my_courses_title_title'] = 'Título do curso e da sessão';
$strings['tour_my_courses_title_content'] = 'Aqui você pode ver o título do curso e, quando aplicável, o nome da sessão associada a esse curso.';
$strings['tour_my_courses_progress_title'] = 'Progresso de aprendizagem';
$strings['tour_my_courses_progress_content'] = 'Esta barra de progresso mostra quanto do curso você já concluiu.';
$strings['tour_my_courses_notifications_title'] = 'Notificações de novo conteúdo';
$strings['tour_my_courses_notifications_content'] = 'Use este botão de sino para verificar se o curso tem novo conteúdo ou atualizações recentes. Quando destacado, ele ajuda você a identificar rapidamente mudanças desde o seu último acesso.';
$strings['tour_my_courses_footer_title'] = 'Professores e detalhes do curso';
$strings['tour_my_courses_footer_content'] = 'O rodapé pode mostrar professores, idioma e outras informações úteis relacionadas ao curso.';
$strings['tour_my_courses_create_course_title'] = 'Criar um curso';
$strings['tour_my_courses_create_course_content'] = 'Se você tiver permissão para criar cursos, use este botão para abrir diretamente o formulário de criação de curso a partir desta página.';
$strings['tour_course_home_header_title'] = 'Cabeçalho do curso';
$strings['tour_course_home_header_content'] = 'Este cabeçalho mostra o título do curso e, quando aplicável, a sessão ativa. Ele também reúne as principais ações do professor disponíveis nesta página.';
$strings['tour_course_home_title_title'] = 'Título do curso';
$strings['tour_course_home_title_content'] = 'Aqui você pode identificar rapidamente o curso atual. Se o curso pertencer a uma sessão, o título da sessão será exibido ao lado dele.';
$strings['tour_course_home_teacher_tools_title'] = 'Ferramentas do professor';
$strings['tour_course_home_teacher_tools_content'] = 'Dependendo das suas permissões, esta área pode incluir a mudança para a visualização do aluno, a edição da introdução, o acesso a relatórios e outras ações de gerenciamento do curso.';
$strings['tour_course_home_intro_title'] = 'Introdução do curso';
$strings['tour_course_home_intro_content'] = 'Esta seção mostra a introdução do curso. Os professores podem usá-la para apresentar objetivos, orientações, links ou informações importantes para os alunos.';
$strings['tour_course_home_tools_controls_title'] = 'Controles das ferramentas';
$strings['tour_course_home_tools_controls_content'] = 'Os professores podem usar estes controles para mostrar ou ocultar todas as ferramentas de uma vez, ou ativar o modo de ordenação para reorganizar as ferramentas do curso.';
$strings['tour_course_home_tools_title'] = 'Ferramentas do curso';
$strings['tour_course_home_tools_content'] = 'Esta área contém as principais ferramentas do curso, como documentos, trilhas de aprendizagem, exercícios, fóruns e outros recursos disponíveis no curso.';
$strings['tour_course_home_tool_card_title'] = 'Cartão da ferramenta';
$strings['tour_course_home_tool_card_content'] = 'Cada cartão dá acesso a uma ferramenta do curso. Use-o para entrar rapidamente na área selecionada do curso.';
$strings['tour_course_home_tool_shortcut_title'] = 'Atalho da ferramenta';
$strings['tour_course_home_tool_shortcut_content'] = 'Clique na área do ícone para abrir diretamente a ferramenta selecionada do curso.';
$strings['tour_course_home_tool_name_title'] = 'Nome da ferramenta';
$strings['tour_course_home_tool_name_content'] = 'O título identifica a ferramenta e também funciona como um link de acesso direto.';
$strings['tour_course_home_tool_visibility_title'] = 'Visibilidade da ferramenta';
$strings['tour_course_home_tool_visibility_content'] = 'Se você estiver editando o curso, este botão permite alterar rapidamente a visibilidade da ferramenta para os alunos.';
$strings['tour_admin_overview_title'] = 'Painel de administração';
$strings['tour_admin_overview_content'] = 'Esta página centraliza as principais áreas de administração da plataforma, agrupadas por tema de gestão.';
$strings['tour_admin_user_management_title'] = 'Gerenciamento de usuários';
$strings['tour_admin_user_management_content'] = 'Neste bloco você pode gerenciar os usuários registrados, criar contas, importar ou exportar listas de usuários, editar usuários, anonimizar dados e gerenciar classes.';
$strings['tour_admin_course_management_title'] = 'Gerenciamento de cursos';
$strings['tour_admin_course_management_content'] = 'Este bloco permite criar e gerenciar cursos, importar ou exportar listas de cursos, organizar categorias, atribuir usuários aos cursos e configurar campos e ferramentas relacionados aos cursos.';
$strings['tour_admin_sessions_management_title'] = 'Gerenciamento de sessões';
$strings['tour_admin_sessions_management_content'] = 'Aqui você pode gerenciar sessões de treinamento, categorias de sessão, importações e exportações, diretores de RH, carreiras, promoções e campos relacionados às sessões.';
$strings['tour_admin_platform_management_title'] = 'Gerenciamento da plataforma';
$strings['tour_admin_platform_management_content'] = 'Use este bloco para configurar a plataforma globalmente, ajustar configurações, gerenciar anúncios, idiomas e outras opções centrais de administração.';
$strings['tour_admin_tracking_title'] = 'Acompanhamento';
$strings['tour_admin_tracking_content'] = 'Esta área dá acesso a relatórios, estatísticas globais, análises de aprendizagem e outros dados de acompanhamento em toda a plataforma.';
$strings['tour_admin_assessments_title'] = 'Avaliações';
$strings['tour_admin_assessments_content'] = 'Este bloco fornece acesso aos recursos administrativos relacionados às avaliações disponíveis na plataforma.';
$strings['tour_admin_skills_title'] = 'Competências';
$strings['tour_admin_skills_content'] = 'Este bloco permite gerenciar as competências dos usuários, importações de competências, classificações, níveis e avaliações relacionadas às competências.';
$strings['tour_admin_system_title'] = 'Sistema';
$strings['tour_admin_system_content'] = 'Aqui você pode acessar ferramentas de manutenção do servidor e da plataforma, como status do sistema, limpeza de arquivos temporários, preenchimento de dados, testes de e-mail e utilitários técnicos.';
$strings['tour_admin_rooms_title'] = 'Salas';
$strings['tour_admin_rooms_content'] = 'Este bloco dá acesso aos recursos de gerenciamento de salas, incluindo filiais, salas e busca de disponibilidade de salas.';
$strings['tour_admin_security_title'] = 'Segurança';
$strings['tour_admin_security_content'] = 'Use esta área para revisar tentativas de login, relatórios relacionados à segurança e outras ferramentas de segurança disponíveis na plataforma.';
$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Este bloco fornece referências oficiais do Chamilo, guias do usuário, fóruns, recursos de instalação e links para provedores de serviços e informações do projeto.';
$strings['tour_admin_health_check_title'] = 'Verificação de saúde';
$strings['tour_admin_health_check_content'] = 'Esta área ajuda a revisar a saúde técnica da plataforma listando verificações do ambiente, caminhos graváveis e avisos importantes de instalação.';
$strings['tour_admin_version_check_title'] = 'Verificação de versão';
$strings['tour_admin_version_check_content'] = 'Use este bloco para registrar seu portal e habilitar recursos de verificação de versão e opções de listagem pública da plataforma.';
$strings['tour_admin_professional_support_title'] = 'Suporte profissional';
$strings['tour_admin_professional_support_content'] = 'Este bloco explica como entrar em contato com provedores oficiais do Chamilo para consultoria, hospedagem, treinamento e suporte para desenvolvimentos personalizados.';
$strings['tour_admin_news_title'] = 'Notícias do Chamilo';
$strings['tour_admin_news_content'] = 'Esta seção mostra notícias e anúncios recentes do projeto Chamilo.';

$strings['tour_home_topbar_logo_title'] = 'Logo da plataforma';
$strings['tour_home_topbar_logo_content'] = 'Este logo leva você de volta à página inicial da plataforma.';
$strings['tour_home_topbar_actions_title'] = 'Ações rápidas';
$strings['tour_home_topbar_actions_content'] = 'Aqui você encontra ícones de atalho, como criação de cursos, ajuda guiada, tickets e mensagens, de acordo com o seu perfil.';
$strings['tour_home_menu_button_title'] = 'Botão do menu';
$strings['tour_home_menu_button_content'] = 'Use este botão para abrir ou fechar rapidamente o menu lateral.';
$strings['tour_home_sidebar_title'] = 'Menu principal';
$strings['tour_home_sidebar_content'] = 'Este menu lateral dá acesso às principais seções da plataforma, de acordo com suas permissões.';
$strings['tour_home_user_area_title'] = 'Área do usuário';
$strings['tour_home_user_area_content'] = 'Aqui você pode acessar seu perfil, opções pessoais e sair da plataforma.';

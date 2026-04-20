<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videoconferência';
$strings['plugin_comment'] = 'Adicionar uma sala de videoconferência num curso Chamilo usando BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videoconferência';
$strings['MeetingOpened'] = 'Reunião aberta';
$strings['MeetingClosed'] = 'Reunião fechada';
$strings['MeetingClosedComment'] = 'Se pediu para gravar as suas sessões, a gravação estará disponível na lista abaixo quando estiver completamente gerada.';
$strings['CloseMeeting'] = 'Fechar reunião';

$strings['VideoConferenceXCourseX'] = 'Videoconferência #%s curso %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videoconferência adicionada ao calendário';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videoconferência adicionada à ferramenta ligações';

$strings['GoToTheVideoConference'] = 'Ir para a videoconferência';

$strings['Records'] = 'Gravação';
$strings['Meeting'] = 'Reunião';

$strings['ViewRecord'] = 'Ver gravação';
$strings['CopyToLinkTool'] = 'Copiar para ferramenta ligações';

$strings['EnterConference'] = 'Entrar na videoconferência';
$strings['RecordList'] = 'Lista de gravações';
$strings['ServerIsNotRunning'] = 'Servidor de videoconferência não está a funcionar';
$strings['ServerIsNotConfigured'] = 'Servidor de videoconferência não está configurado';

$strings['XUsersOnLine'] = '%s utilizador(es) em linha';

$strings['host'] = 'Servidor BigBlueButton';
$strings['host_help'] = 'Este é o nome do servidor onde o seu servidor BigBlueButton está a funcionar.
Pode ser localhost, um endereço IP (ex: http://192.168.13.54) ou um nome de domínio (ex: http://my.video.com).';

$strings['salt'] = 'Salt do BigBlueButton';
$strings['salt_help'] = 'Esta é a chave de segurança do seu servidor BigBlueButton, que permitirá ao seu servidor autenticar a instalação Chamilo. Consulte a documentação do BigBlueButton para localizá-la. Tente bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Mensagem de boas-vindas';
$strings['enable_global_conference'] = 'Ativar conferência global';
$strings['enable_global_conference_per_user'] = 'Ativar conferência global por utilizador';
$strings['enable_conference_in_course_groups'] = 'Ativar conferência em grupos de curso';
$strings['enable_global_conference_link'] = 'Ativar a ligação para a conferência global na página principal';
$strings['disable_download_conference_link'] = 'Desativar descarga de conferência';
$strings['big_blue_button_record_and_store'] = 'Gravar e armazenar sessões';
$strings['bbb_enable_conference_in_groups'] = 'Permitir conferência em grupos';
$strings['plugin_tool_bbb'] = 'Vídeo';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Não existem gravações para as sessões da reunião';
$strings['NoRecording'] = 'Sem gravação';
$strings['ClickToContinue'] = 'Clique para continuar';
$strings['NoGroup'] = 'Sem grupo';
$strings['UrlMeetingToShare'] = 'URL para partilhar';
$strings['AdminView'] = 'Vista para administradores';
$strings['max_users_limit'] = 'Limite máximo de utilizadores';
$strings['max_users_limit_help'] = 'Defina este valor para o número máximo de utilizadores que deseja permitir por curso ou sessão-curso. Deixe vazio ou defina para 0 para desativar este limite.';
$strings['MaxXUsersWarning'] = 'Esta sala de conferência tem um número máximo de %s utilizadores simultâneos.';
$strings['MaxXUsersReached'] = 'O limite de %s utilizadores simultâneos foi atingido para esta sala de conferência. Por favor, aguarde que um lugar fique livre ou que outra conferência inicie para poder entrar.';
$strings['MaxXUsersReachedManager'] = 'O limite de %s utilizadores simultâneos foi atingido para esta sala de conferência. Para aumentar este limite, por favor contacte o administrador da plataforma.';
$strings['MaxUsersInConferenceRoom'] = 'Máximo de utilizadores simultâneos numa sala de conferência';
$strings['global_conference_allow_roles'] = 'Ligação de conferência global apenas visível para estes papéis de utilizador';
$strings['CreatedAt'] = 'Criado em';
$strings['allow_regenerate_recording'] = 'Permitir regenerar gravação';
$strings['bbb_force_record_generation'] = 'Forçar geração de gravação no final da reunião';
$strings['disable_course_settings'] = 'Desativar configurações do curso';
$strings['UpdateAllCourses'] = 'Atualizar todos os cursos';
$strings['UpdateAllCourseSettings'] = 'Atualizar todas as configurações do curso';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Isto atualizará de uma só vez todas as configurações do seu curso.';
$strings['ThereIsNoVideoConferenceActive'] = 'Não há videoconferência ativa no momento';
$strings['RoomClosed'] = 'Sala fechada';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Duração da reunião (em minutos)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Permitir que os alunos iniciem a conferência nos seus grupos.';
$strings['hide_conference_link'] = 'Ocultar ligação da conferência na ferramenta do curso';
$strings['hide_conference_link_comment'] = 'Mostrar ou ocultar um bloco com uma ligação para a videoconferência ao lado do botão de participação, para permitir que os utilizadores a copiem e coloquem noutra janela do navegador ou convidem outros. A autenticação ainda será necessária para aceder a conferências não públicas.';
$strings['delete_recordings_on_course_delete'] = 'Eliminar gravações quando o curso for removido';
$strings['defaultVisibilityInCourseHomepage'] = 'Visibilidade predefinida na página inicial do curso';
$strings['ViewActivityDashboard'] = 'Ver painel de atividades';
$strings['Participants'] = 'Participantes';
$strings['CountUsers'] = 'Contar utilizadores';

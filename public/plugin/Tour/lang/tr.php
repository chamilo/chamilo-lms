<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Tanıtım Turu';
$strings['plugin_comment'] = "Bu eklenti, Chamilo LMS'in nasıl kullanılacağını kullanıcılara gösterir. Turu başlatan butonu göstermek için bir bölge (ör. \"header-right\") etkinleştirmeniz gerekir.";

/* Strings for settings */
$strings['show_tour'] = 'Turu göster';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'Yardım bloklarını göstermek için gerekli JSON yapılandırması <strong>plugin/tour/config/tour.json</strong> dosyasında bulunur. <br> Daha fazla bilgi için README dosyasına bakın.';

$strings['theme'] = 'Tema';
$strings['theme_help'] = '<i>nassim</i>, <i>nazanin</i>, <i>royal</i> seçin. Varsayılan temayı kullanmak için boş bırakın.';

/* Strings for plugin UI */
$strings['Skip'] = 'Atla';
$strings['Next'] = 'Sonraki';
$strings['Prev'] = 'Önceki';
$strings['Done'] = 'Tamamlandı';
$strings['StartButtonText'] = 'Turu başlat';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = '<b>Chamilo LMS 1.9.x</b> Hoş Geldiniz';
$strings['TheNavbarStep'] = 'Portalın ana bölümlerine bağlantılar içeren menü çubuğu';
$strings['TheRightPanelStep'] = 'Kenar çubuğu paneli';
$strings['TheUserImageBlock'] = 'Profil fotoğrafınız';
$strings['TheProfileBlock'] = 'Profil araçlarınız: <i>Gelen Kutusu</i>, <i>mesaj oluşturucu</i>, <i>bekleyen davetler</i>, <i>profil düzenleme</i>.';
$strings['TheHomePageStep'] = 'Bu, portal duyurularını, bağlantıları ve yönetim ekibinin yapılandırdığı diğer bilgileri bulacağınız ana sayfadır.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Bu alan, abone olduğunuz farklı kursları (veya oturumları) gösterir. Hiçbir kurs görünmüyorsa, kurs kataloğuna gidin (menüye bakın) veya portal yöneticinizle görüşün.';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'Ajanda aracı, önümüzdeki günlerde, haftalarda veya aylarda planlanan etkinlikleri görmenizi sağlar.';
$strings['AgendaTheActionBar'] = 'Sağlanan eylem simgelerini kullanarak etkinlikleri takvim görünümü yerine liste olarak gösterebilirsiniz.';
$strings['AgendaTodayButton'] = 'Sadece bugünün programını görmek için "bugün" butonuna tıklayın';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'Mevcut ay, takvim görünümünde her zaman öne çıkarılır';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Bu butonlardan birine tıklayarak görünümü günlük, haftalık veya aylık olarak değiştirebilirsiniz';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Bu alan, öğrenciyseniz ilerlemenizi, öğretmen iseniz öğrencilerinizin ilerlemesini kontrol etmenizi sağlar';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Bu ekranda sunulan raporlar genişletilebilir ve öğrenme veya öğretme süreciniz hakkında çok değerli bilgiler sağlayabilir';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'Sosyal alan, platformdaki diğer kullanıcılarla iletişim kurmanızı sağlar';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'Menü, özel mesajlaşma, sohbet, ilgi grupları vb. alanlara katılmanızı sağlayan ekranlara erişim sağlar';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'Kontrol Paneli, size resimli ve özetlenmiş biçimde çok özel bilgiler sunar. Şu anda bu özelliğe yalnızca yöneticiler erişebilir';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'Kontrol Paneli panellerini etkinleştirmek için önce yönetici bölümündeki eklentiler kısmından olası panelleri etkinleştirmeli, ardından buraya dönüp kontrol panelinizde görmek istediğiniz panelleri seçmelisiniz';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'Yönetim paneli, Chamilo portalınızdaki tüm kaynakları yönetmenizi sağlar';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'Kullanıcılar bloğu, kullanıcılarla ilgili tüm işlemleri yönetmenizi sağlar.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'Kurslar bloğu, kurs oluşturma, düzenleme vb. işlemlere erişim sağlar. Diğer bloklar da belirli kullanımlar için ayrılmıştır.';


$strings['tour_home_featured_courses_title'] = 'Öne çıkan kurslar';
$strings['tour_home_featured_courses_content'] = 'Bu bölüm, ana sayfanızda bulunan öne çıkan kursları gösterir.';

$strings['tour_home_course_card_title'] = 'Kurs kartı';
$strings['tour_home_course_card_content'] = 'Her kart bir kursu özetler ve ana bilgilerine hızlı erişim sağlar.';

$strings['tour_home_course_title_title'] = 'Kurs başlığı';
$strings['tour_home_course_title_content'] = 'Kurs başlığı, kursu hızlıca tanımlamanıza yardımcı olur ve platform ayarlarına bağlı olarak daha fazla bilgi açabilir.';

$strings['tour_home_teachers_title'] = 'Öğretmenler';
$strings['tour_home_teachers_content'] = 'Bu alan, kursla ilişkili öğretmenleri veya kullanıcıları gösterir.';

$strings['tour_home_rating_title'] = 'Değerlendirme ve geri bildirim';
$strings['tour_home_rating_content'] = 'Burada kurs değerlendirmesini inceleyebilir ve izin veriliyorsa kendi oyunuzu gönderebilirsiniz.';

$strings['tour_home_main_action_title'] = 'Ana kurs eylemi';
$strings['tour_home_main_action_content'] = 'Bu butonu kullanarak kursa girebilir, abone olabilir veya kurs durumuna göre erişim kısıtlamalarını inceleyebilirsiniz.';

$strings['tour_home_show_more_title'] = 'Daha fazla kurs göster';
$strings['tour_home_show_more_content'] = 'Bu butonu kullanarak daha fazla kurs yükleyebilir ve ana sayfadan kataloğu keşfetmeye devam edebilirsiniz.';

$strings['tour_my_courses_cards_title'] = 'Kurs kartlarınız';
$strings['tour_my_courses_cards_content'] = 'Bu sayfa, abone olduğunuz kursları listeler. Her kart size kursa ve mevcut durumuna hızlı erişim sağlar.';

$strings['tour_my_courses_image_title'] = 'Kurs görseli';
$strings['tour_my_courses_image_content'] = 'Kurs görseli, kursu hızlıca tanımlamanıza yardımcı olur. Çoğu durumda üzerine tıklamak kursu açar.';

$strings['tour_my_courses_title_title'] = 'Ders ve oturum başlığı';
$strings['tour_my_courses_title_content'] = 'Burada ders başlığını ve varsa o dersle ilişkili oturum adını görebilirsiniz.';

$strings['tour_my_courses_progress_title'] = 'Öğrenme ilerlemesi';
$strings['tour_my_courses_progress_content'] = 'Bu ilerleme çubuğu dersin ne kadarını tamamladığınızı gösterir.';

$strings['tour_my_courses_notifications_title'] = 'Yeni içerik bildirimleri';
$strings['tour_my_courses_notifications_content'] = 'Bu zil düğmesini kullanarak derste yeni içerik veya son güncellemeleri kontrol edebilirsiniz. Vurgulandığında, son erişiminizden beri olan değişiklikleri hızlıca fark etmenize yardımcı olur.';

$strings['tour_my_courses_footer_title'] = 'Öğretmenler ve ders detayları';
$strings['tour_my_courses_footer_content'] = 'Alt kısım öğretmenleri, dili ve dersle ilgili diğer faydalı bilgileri gösterebilir.';

$strings['tour_my_courses_create_course_title'] = 'Ders oluştur';
$strings['tour_my_courses_create_course_content'] = 'Ders oluşturma izniniz varsa, bu sayfadan doğrudan ders oluşturma formunu açmak için bu düğmeyi kullanın.';

$strings['tour_course_home_header_title'] = 'Ders başlığı';
$strings['tour_course_home_header_content'] = 'Bu başlık ders başlığını ve varsa aktif oturumu gösterir. Ayrıca bu sayfadaki ana öğretmen eylemlerini gruplar.';

$strings['tour_course_home_title_title'] = 'Ders başlığı';
$strings['tour_course_home_title_content'] = 'Burada mevcut dersi hızlıca tanıyabilirsiniz. Ders bir oturuma aitse, oturum başlığı yanında görüntülenir.';

$strings['tour_course_home_teacher_tools_title'] = 'Öğretmen araçları';
$strings['tour_course_home_teacher_tools_content'] = 'İzinlerinize bağlı olarak bu alan öğrenci görünümü değiştiricisi, giriş düzenleme, raporlama erişimi ve ek ders yönetimi eylemlerini içerebilir.';

$strings['tour_course_home_intro_title'] = 'Ders girişi';
$strings['tour_course_home_intro_content'] = 'Bu bölüm ders girişini görüntüler. Öğretmenler bunu öğrenenler için hedefleri, rehberliği, bağlantıları veya ana bilgileri sunmak için kullanabilir.';

$strings['tour_course_home_tools_controls_title'] = 'Araç kontrolleri';
$strings['tour_course_home_tools_controls_content'] = 'Öğretmenler bu kontrolleri tüm araçları bir kerede gösterip gizlemek veya ders araçlarını yeniden düzenlemek için sıralama modunu etkinleştirmek için kullanabilir.';

$strings['tour_course_home_tools_title'] = 'Ders araçları';
$strings['tour_course_home_tools_content'] = 'Bu alan belgeler, öğrenme yolları, alıştırmalar, forumlar ve derste mevcut diğer kaynaklar gibi ana ders araçlarını içerir.';

$strings['tour_course_home_tool_card_title'] = 'Araç kartı';
$strings['tour_course_home_tool_card_content'] = 'Her araç kartı bir ders aracına erişim sağlar. Seçili ders alanına hızlıca girmek için kullanın.';

$strings['tour_course_home_tool_shortcut_title'] = 'Araç kısayolu';
$strings['tour_course_home_tool_shortcut_content'] = 'Seçili ders aracını doğrudan açmak için simge alanına tıklayın.';

$strings['tour_course_home_tool_name_title'] = 'Araç adı';
$strings['tour_course_home_tool_name_content'] = 'Başlık aracı tanımlar ve aynı zamanda doğrudan erişim bağlantısı olarak çalışır.';

$strings['tour_course_home_tool_visibility_title'] = 'Araç görünürlüğü';
$strings['tour_course_home_tool_visibility_content'] = 'Dersi düzenliyorsanız, bu düğme aracın öğrenenler için görünürlüğünü hızlıca değiştirmenizi sağlar.';
$strings['tour_admin_overview_title'] = 'Yönetim panosu';
$strings['tour_admin_overview_content'] = 'Bu sayfa platformun ana yönetim alanlarını yönetim konusuna göre gruplandırarak merkezileştirir.';

$strings['tour_admin_user_management_title'] = 'Kullanıcı yönetimi';
$strings['tour_admin_user_management_content'] = 'Bu bloktan kayıtlı kullanıcıları yönetebilir, hesaplar oluşturabilir, kullanıcı listelerini içe/dışa aktarabilir, kullanıcıları düzenleyebilir, verileri anonimleştirebilir ve sınıfları yönetebilirsiniz.';

$strings['tour_admin_course_management_title'] = 'Ders yönetimi';
$strings['tour_admin_course_management_content'] = 'Bu blok dersleri oluşturup yönetmenizi, ders listelerini içe/dışa aktarmanızı, kategorileri düzenlemenizi, kullanıcıları derslere atamanızı ve dersle ilgili alanları ve araçları yapılandırmanızı sağlar.';

$strings['tour_admin_sessions_management_title'] = 'Oturum yönetimi';
$strings['tour_admin_sessions_management_content'] = 'Burada eğitim oturumlarını, oturum kategorilerini, içe/dışa aktarmaları, İK direktörlerini, kariyerleri, terfileri ve oturumla ilgili alanları yönetebilirsiniz.';

$strings['tour_admin_platform_management_title'] = 'Platform yönetimi';
$strings['tour_admin_platform_management_content'] = 'Platformu genel olarak yapılandırmak, ayarları düzenlemek, duyuruları, dilleri ve diğer merkezi yönetim seçeneklerini yönetmek için bu bloğu kullanın.';

$strings['tour_admin_tracking_title'] = 'İzleme';
$strings['tour_admin_tracking_content'] = 'Bu alan raporlara, genel istatistiklere, öğrenme analizlerine ve platform genelindeki diğer izleme verilerine erişim sağlar.';

$strings['tour_admin_assessments_title'] = 'Değerlendirmeler';
$strings['tour_admin_assessments_content'] = 'Bu blok platformda mevcut değerlendirme ile ilgili yönetim özelliklerine erişim sağlar.';
$strings['tour_admin_skills_title'] = 'Yeterlilikler';
$strings['tour_admin_skills_content'] = 'Bu blok kullanıcı yeterliliklerini, yeterlilik içe aktarmalarını, sıralamaları, seviyeleri ve yeterliliklerle ilgili değerlendirmeleri yönetmenizi sağlar.';

$strings['tour_admin_system_title'] = 'Sistem';
$strings['tour_admin_system_content'] = 'Burada sunucu ve platform bakım araçlarına erişebilirsiniz; sistem durumu, geçici dosya temizliği, veri doldurucu, e-posta testleri ve teknik yardımcılar gibi.';

$strings['tour_admin_rooms_title'] = 'Odalar';
$strings['tour_admin_rooms_content'] = 'Bu blok şubeler, odalar ve oda kullanılabilirlik araması dahil oda yönetimi özelliklerine erişim sağlar.';

$strings['tour_admin_security_title'] = 'Güvenlik';
$strings['tour_admin_security_content'] = 'Bu alanı giriş denemelerini incelemek, güvenlik raporlarını gözden geçirmek ve platformda mevcut ek güvenlik araçlarını kullanmak için kullanın.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Bu blok resmi Chamilo referanslarını, kullanıcı kılavuzlarını, forumları, kurulum kaynaklarını ve hizmet sağlayıcılara ve proje bilgilerine bağlantıları sağlar.';

$strings['tour_admin_health_check_title'] = 'Sağlık kontrolü';
$strings['tour_admin_health_check_content'] = 'Bu alan, ortam kontrollerini, yazılabilir yolları ve önemli kurulum uyarılarını listeleyerek platformun teknik sağlığını incelemenize yardımcı olur.';

$strings['tour_admin_version_check_title'] = 'Sürüm kontrolü';
$strings['tour_admin_version_check_content'] = 'Bu bloğu portalınızı kaydetmek ve sürüm kontrolü özelliklerini ve genel platform listeleme seçeneklerini etkinleştirmek için kullanın.';

$strings['tour_admin_professional_support_title'] = 'Profesyonel destek';
$strings['tour_admin_professional_support_content'] = 'Bu blok, danışmanlık, barındırma, eğitim ve özel geliştirme desteği için resmi Chamilo sağlayıcılarıyla nasıl iletişime geçileceğini açıklar.';

$strings['tour_admin_news_title'] = "Chamilo'dan haberler";
$strings['tour_admin_news_content'] = 'Bu bölüm Chamilo projesinden son haberleri ve duyuruları görüntüler.';

$strings['tour_home_topbar_logo_title'] = 'Platform logosu';
$strings['tour_home_topbar_logo_content'] = 'Bu logo sizi platformun ana sayfasına geri götürür.';
$strings['tour_home_topbar_actions_title'] = 'Hızlı işlemler';
$strings['tour_home_topbar_actions_content'] = 'Burada rolünüze bağlı olarak kurs oluşturma, rehberli yardım, talepler ve mesajlar gibi kısayol simgelerini bulabilirsiniz.';
$strings['tour_home_menu_button_title'] = 'Menü düğmesi';
$strings['tour_home_menu_button_content'] = 'Yan menüyü hızlıca açmak veya kapatmak için bu düğmeyi kullanın.';
$strings['tour_home_sidebar_title'] = 'Ana menü';
$strings['tour_home_sidebar_content'] = 'Bu yan menü, yetkilerinize bağlı olarak platformun ana bölümlerine erişim sağlar.';
$strings['tour_home_user_area_title'] = 'Kullanıcı alanı';
$strings['tour_home_user_area_content'] = 'Burada profilinize, kişisel seçeneklere erişebilir ve çıkış yapabilirsiniz.';

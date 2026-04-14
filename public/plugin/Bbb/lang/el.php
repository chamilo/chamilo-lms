<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Βιντεοδιάσκεψη';
$strings['plugin_comment'] = 'Προσθήκη αίθουσας βιντεοδιάσκεψης σε μάθημα Chamilo χρησιμοποιώντας BigBlueButton (BBB)';

$strings['Videoconference'] = 'Βιντεοδιάσκεψη';
$strings['MeetingOpened'] = 'Συνάντηση ανοιχτή';
$strings['MeetingClosed'] = 'Συνάντηση κλειστή';
$strings['MeetingClosedComment'] = 'Αν έχετε ζητήσει η συνεδρία σας να καταγραφεί, η εγγραφή θα είναι διαθέσιμη στη λίστα παρακάτω όταν ολοκληρωθεί πλήρως η παραγωγή της.';
$strings['CloseMeeting'] = 'Κλείσιμο συνάντησης';

$strings['VideoConferenceXCourseX'] = 'Βιντεοδιάσκεψη #%s μάθημα %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Η βιντεοδιάσκεψη προστέθηκε στο ημερολόγιο';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Η βιντεοδιάσκεψη προστέθηκε στο εργαλείο συνδέσμων';

$strings['GoToTheVideoConference'] = 'Πήγαινε στη βιντεοδιάσκεψη';

$strings['Records'] = 'Εγγραφή';
$strings['Meeting'] = 'Συνάντηση';

$strings['ViewRecord'] = 'Προβολή εγγραφής';
$strings['CopyToLinkTool'] = 'Αντιγραφή στο εργαλείο συνδέσμων';

$strings['EnterConference'] = 'Είσοδος στη βιντεοδιάσκεψη';
$strings['RecordList'] = 'Λίστα εγγραφών';
$strings['ServerIsNotRunning'] = 'Ο διακομιστής βιντεοδιάσκεψης δεν λειτουργεί';
$strings['ServerIsNotConfigured'] = 'Ο διακομιστής βιντεοδιάσκεψης δεν είναι ρυθμισμένος';

$strings['XUsersOnLine'] = '%s χρήστη(δες) συνδεδεμένοι';

$strings['host'] = 'Διακομιστής BigBlueButton';
$strings['host_help'] = 'Αυτό είναι το όνομα του διακομιστή όπου λειτουργεί ο διακομιστής BigBlueButton σας.
Μπορεί να είναι localhost, μια διεύθυνση IP (π.χ. http://192.168.13.54) ή ένα όνομα τομέα (π.χ. http://my.video.com).';

$strings['salt'] = 'Salt BigBlueButton';
$strings['salt_help'] = 'Αυτό είναι το κλειδί ασφαλείας του διακομιστή BigBlueButton σας, το οποίο θα επιτρέψει στον διακομιστή σας να πιστοποιήσει την εγκατάσταση Chamilo. Ανατρέξτε στη τεκμηρίωση BigBlueButton για να το εντοπίσετε. Δοκιμάστε bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Μήνυμα καλωσορίσματος';
$strings['enable_global_conference'] = 'Ενεργοποίηση γενικής διάσκεψης';
$strings['enable_global_conference_per_user'] = 'Ενεργοποίηση γενικής διάσκεψης ανά χρήστη';
$strings['enable_conference_in_course_groups'] = 'Ενεργοποίηση διάσκεψης σε ομάδες μαθήματος';
$strings['enable_global_conference_link'] = 'Εμφάνιση συνδέσμου γενικής διάσκεψης στην αρχική σελίδα';
$strings['disable_download_conference_link'] = 'Απενεργοποίηση λήψης διάσκεψης';
$strings['big_blue_button_record_and_store'] = 'Καταγραφή και αποθήκευση συνεδριών';
$strings['bbb_enable_conference_in_groups'] = 'Επιτρέψτε διάσκεψη σε ομάδες';
$strings['plugin_tool_bbb'] = 'Βίντεο';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Δεν υπάρχουν εγγραφές για τις συνεδρίες της συνάντησης';
$strings['No recording'] = 'Καμία εγγραφή';
$strings['ClickToContinue'] = 'Κάντε κλικ για συνέχεια';
$strings['NoGroup'] = 'Καμία ομάδα';
$strings['UrlMeetingToShare'] = 'URL προς κοινή χρήση';
$strings['AdminView'] = 'Προβολή για διαχειριστές';
$strings['max_users_limit'] = 'Όριο μέγιστου αριθμού χρηστών';
$strings['max_users_limit_help'] = 'Ορίστε αυτό στον μέγιστο αριθμό χρηστών που θέλετε να επιτρέψετε ανά μάθημα ή συνεδρία-μάθημα. Αφήστε κενό ή ορίστε σε 0 για απενεργοποίηση του ορίου.';
$strings['MaxXUsersWarning'] = 'Αυτή η αίθουσα διάσκεψης έχει μέγιστο αριθμό %s ταυτόχρονων χρηστών.';
$strings['MaxXUsersReached'] = 'Το όριο %s ταυτόχρονων χρηστών έχει επιτευχθεί για αυτή την αίθουσα διάσκεψης. Παρακαλούμε περιμένετε να απελευθερωθεί μία θέση ή να ξεκινήσει άλλη διάσκεψη για να συμμετάσχετε.';
$strings['MaxXUsersReachedManager'] = 'Το όριο %s ταυτόχρονων χρηστών έχει επιτευχθεί για αυτή την αίθουσα διάσκεψης. Για αύξηση του ορίου, επικοινωνήστε με τον διαχειριστή της πλατφόρμας.';
$strings['MaxUsersInConferenceRoom'] = 'Μέγιστος αριθμός ταυτόχρονων χρηστών σε αίθουσα διάσκεψης';
$strings['global_conference_allow_roles'] = 'Σύνδεσμος γενικής διάσκεψης ορατός μόνο για αυτούς τους ρόλους χρηστών';
$strings['CreatedAt'] = 'Δημιουργήθηκε στις';
$strings['allow_regenerate_recording'] = 'Επιτρέψτε επανεγγραφή';
$strings['bbb_force_record_generation'] = 'Αναγκαστική παραγωγή εγγραφής στο τέλος της συνάντησης';
$strings['disable_course_settings'] = 'Απενεργοποίηση ρυθμίσεων μαθήματος';
$strings['UpdateAllCourses'] = 'Ενημέρωση όλων των μαθημάτων';
$strings['UpdateAllCourseSettings'] = 'Ενημέρωση όλων των ρυθμίσεων μαθήματος';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Αυτό θα ενημερώσει ταυτόχρονα όλες τις ρυθμίσεις του μαθήματός σας.';
$strings['ThereIsNoVideoConferenceActive'] = 'Δεν υπάρχει ενεργή τηλεδιάσκεψη αυτή τη στιγμή';
$strings['RoomClosed'] = 'Ο χώρος έκλεισε';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Διάρκεια συνάντησης (σε λεπτά)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Επιτρέψτε στους φοιτητές να ξεκινούν τη διάσκεψη στις ομάδες τους.';
$strings['hide_conference_link'] = 'Απόκρυψη συνδέσμου διάσκεψης στο εργαλείο μαθήματος';
$strings['hide_conference_link_comment'] = 'Εμφάνιση ή απόκρυψη ενός μπλοκ με σύνδεσμο στη τηλεδιάσκεψη δίπλα στο κουμπί συμμετοχής, για να επιτρέψετε στους χρήστες να τον αντιγράψουν και να τον επικολλήσουν σε άλλο παράθυρο προγράμματος περιήγησης ή να προσκαλέσουν άλλους. Η πιστοποίηση θα είναι ακόμα απαραίτητη για πρόσβαση σε μη δημόσιες διασκέψεις.';
$strings['delete_recordings_on_course_delete'] = 'Διαγραφή εγγραφών όταν αφαιρείται το μάθημα';
$strings['defaultVisibilityInCourseHomepage'] = 'Προεπιλεγμένη ορατότητα στην αρχική σελίδα μαθήματος';
$strings['ViewActivityDashboard'] = 'Προβολή πίνακα ελέγχου δραστηριότητας';
$strings['Participants'] = 'Συνεργάτες';
$strings['CountUsers'] = 'Μέτρηση χρηστών';

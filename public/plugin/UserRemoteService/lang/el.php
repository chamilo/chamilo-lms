<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Υπηρεσίες Απομακρυσμένων Χρηστών';
$strings['plugin_comment'] = 'Προσθέτει συνδέσμους iframe-στόχευσης ειδικούς για τον ιστότοπο, ταυτοποιητικούς χρήστη, στη γραμμή μενού.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'Μυστική συμβολοσειρά χαρακτήρων, που χρησιμοποιείται για τη δημιουργία της παραμέτρου URL <em>hash</em>. Όσο μεγαλύτερη, τόσο καλύτερα.
<br/>Οι απομακρυσμένες υπηρεσίες χρηστών μπορούν να ελέγξουν την εγκυρότητα της δημιουργημένης διεύθυνσης URL με την εξής έκφραση PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Όπου
<br/><code>$salt</code> είναι αυτή η τιμή εισόδου,
<br/><code>$userId</code> είναι ο αριθμός του χρήστη που αναφέρεται στην τιμή της παραμέτρου URL <em>username</em> και
<br/><code>$hash</code> περιέχει την τιμή της παραμέτρου URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'απόκρυψη συνδέσμων από το μενού';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Προσθήκη υπηρεσίας στη γραμμή μενού';
$strings['DeleteServices'] = 'Κατάργηση υπηρεσιών από τη γραμμή μενού';
$strings['ServicesToDelete'] = 'Υπηρεσίες προς κατάργηση από τη γραμμή μενού';
$strings['ServiceTitle'] = 'Τίτλος υπηρεσίας';
$strings['ServiceURL'] = 'Τοποθεσία ιστοχώρου υπηρεσίας (URL)';
$strings['RedirectAccessURL'] = 'URL χρήσης στο Chamilo για ανακατεύθυνση χρήστη στην υπηρεσία (URL)';
$strings['Actions'] = 'Ενέργειες';
$strings['AddRemoteService'] = 'Προσθήκη απομακρυσμένης υπηρεσίας';
$strings['CurrentServices'] = 'Τρέχουσες υπηρεσίες';
$strings['DeleteService'] = 'Διαγραφή υπηρεσίας';
$strings['InvalidSecurityToken'] = 'Μη έγκυρο διακριτικό ασφαλείας.';
$strings['InvalidServiceTitle'] = 'Παρακαλούμε εισάγετε έναν τίτλο υπηρεσίας.';
$strings['InvalidServiceUrl'] = 'Παρακαλούμε εισάγετε ένα έγκυρο URL HTTP ή HTTPS.';
$strings['MissingSaltWarning'] = 'Διαμορφώστε ένα salt πριν εκθέσετε συνδέσμους απομακρυσμένων υπηρεσιών. Το salt είναι απαραίτητο για τη δημιουργία υπογεγραμμένων URL χρηστών.';
$strings['NoServicesConfigured'] = 'Δεν έχουν ρυθμιστεί ακόμη απομακρυσμένες υπηρεσίες.';
$strings['OpenInIframe'] = 'Άνοιγμα σε iframe';
$strings['OpenRedirect'] = 'Άνοιγμα URL ανακατεύθυνσης';
$strings['RemoteServicesDescription'] = 'Διαχείριση εξωτερικών υπηρεσιών που λαμβάνουν υπογεγραμμένα URL χρηστών από το Chamilo. Μόνο πιστοποιημένοι χρήστες μπορούν να ανοίξουν αυτούς τους συνδέσμους.';
$strings['ServiceCreated'] = 'Η απομακρυσμένη υπηρεσία δημιουργήθηκε.';
$strings['ServiceDeleted'] = 'Η απομακρυσμένη υπηρεσία διαγράφηκε.';
$strings['ServiceManagement'] = 'Διαχείριση απομακρυσμένων υπηρεσιών';
$strings['ServiceUnavailable'] = 'Αυτή η απομακρυσμένη υπηρεσία δεν είναι διαθέσιμη. Ελέγξτε ότι το πρόσθετο είναι ενεργοποιημένο, το salt έχει ρυθμιστεί και το URL είναι έγκυρο.';

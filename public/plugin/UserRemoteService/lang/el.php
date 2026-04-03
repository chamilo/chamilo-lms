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

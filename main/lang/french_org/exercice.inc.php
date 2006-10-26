<?php
/*
      +----------------------------------------------------------------------+
      | DOKEOS 1.5 $Revision: 3594 $                                          |
      +----------------------------------------------------------------------+
      | Copyright (c) 2004 The Dokeos Company                                |
      +----------------------------------------------------------------------+
      |   $Id: exercice.inc.php 3594 2005-03-03 12:06:49Z olivierb78 $   |
      +----------------------------------------------------------------------+
      |   This program is free software; you can redistribute it and/or      |
      |   modify it under the terms of the GNU General Public License        |
      |   as published by the Free Software Foundation; either version 2     |
      |   of the License, or (at your option) any later version.             |
      +----------------------------------------------------------------------+
      | Authors: Thomas Depraetere <thomas.depraetere@dokeos.com>            |
      |          Hugues Peeters    <peeters@ipm.ucl.ac.be>                   |
      |          Christophe Gesché <gesche@ipm.ucl.ac.be>                    |
      |          Olivier Brouckaert <oli.brouckaert@skynet.be>               |
      +----------------------------------------------------------------------+
*/

/***************************************************************
*                   Language translation
****************************************************************
GOAL
****
Translate the interface in chosen language

*****************************************************************/

// general

$langExercice="Test";
$langExercices="Tests";
$langQuestion="Question";
$langQuestions="Questions";
$langAnswer="Réponse";
$langAnswers="Réponses";
$langActivate="Activer";
$langDeactivate="Désactiver";
$langComment="Commentaire";
$langUser="Utilisateur";
$langOk="Valider";


// exercice.php

$langNoEx="Il n'y a aucun test actuellement";
$langNoResult="Il n'y a pas encore de résultats";
$langNewEx="Nouveau test";
$langYourResults="Vos résultats";
$langStudentResults="Résultats de vos étudiants";


// exercise_admin.inc.php

$langExerciseType="Type de test";
$langExerciseName="Intitulé du test";
$langExerciseDescription="Description du test";
$langSimpleExercise="Questions sur une seule page";
$langSequentialExercise="Une question par page (séquentiel)";
$langRandomQuestions="Questions aléatoires";
$langGiveExerciseName="Veuillez introduire l'intitulé du test";
$langSound="Fichier audio ou vidéo";
$langDeleteSound="Supprimer le fichier audio ou vidéo";


// question_admin.inc.php

$langNoAnswer="Il n'y a aucune réponse actuellement";
$langGoBackToQuestionPool="Retour à la banque de questions";
$langGoBackToQuestionList="Retour à la liste des questions";
$langQuestionAnswers="Réponses à la question";
$langUsedInSeveralExercises="Attention ! Cette question et ses réponses sont utilisées dans plusieurs tests. Souhaitez-vous les modifier";
$langModifyInAllExercises="pour l'ensemble des tests";
$langModifyInThisExercise="uniquement pour le test courant";


// statement_admin.inc.php

$langAnswerType="Type de réponse";
$langUniqueSelect="Choix multiple (Réponse unique)";
$langMultipleSelect="Choix multiple (Réponses multiples)";
$langFillBlanks="Remplissage de blancs";
$langMatching="Correspondance";
$langAddPicture="Ajouter une image";
$langReplacePicture="Remplacer l'image";
$langDeletePicture="Supprimer l'image";
$langQuestionDescription="Commentaire facultatif";
$langGiveQuestion="Veuillez introduire la question";


// answer_admin.inc.php

$langWeightingForEachBlank="Veuillez donner une pondération à chacun des blancs";
$langUseTagForBlank="utilisez des crochets [...] pour créer un ou des blancs";
$langQuestionWeighting="Pondération";
$langTrue="Vrai";
$langMoreAnswers="+rép";
$langLessAnswers="-rép";
$langMoreElements="+élem";
$langLessElements="-élem";
$langTypeTextBelow="Veuillez introduire votre texte ci-dessous";
$langDefaultTextInBlanks="Les [anglais] vivent en [Angleterre].";
$langDefaultMatchingOptA="Royaume Uni";
$langDefaultMatchingOptB="Japon";
$langDefaultMakeCorrespond1="Les anglais vivent au";
$langDefaultMakeCorrespond2="Les japonais vivent au";
$langDefineOptions="Définissez la liste des options";
$langMakeCorrespond="Faites correspondre";
$langFillLists="Veuillez remplir les deux listes ci-dessous";
$langGiveText="Veuillez introduire le texte";
$langDefineBlanks="Veuillez définir au moins un blanc en utilisant les crochets [...]";
$langGiveAnswers="Veuillez fournir les réponses de cette question";
$langChooseGoodAnswer="Veuillez choisir une bonne réponse";
$langChooseGoodAnswers="Veuillez choisir une ou plusieurs bonnes réponses";


// question_list_admin.inc.php

$langNewQu="Créer une question";
$langQuestionList="Liste des questions de l'exercice";
$langMoveUp="Déplacer vers le haut";
$langMoveDown="Déplacer vers le bas";
$langGetExistingQuestion="Récupérer une question dans la base";
$langFinishTest="Terminer le Test";


// question_pool.php

$langQuestionPool="Banque de questions";
$langOrphanQuestions="Questions orphelines";
$langNoQuestion="Il n'y a aucune question actuellement";
$langAllExercises="Tous les exercices";
$langFilter="Filtre";
$langGoBackToEx="Retour au test";
$langReuse="Récupérer";


// admin.php

$langExerciseManagement="Administration d'un test";
$langQuestionManagement="Administration des questions / réponses";
$langQuestionNotFound="Question introuvable";


// exercice_submit.php

$langExerciseNotFound="Test introuvable";
$langAlreadyAnswered="Vous avez déjà répondu à la question";


// exercise_result.php

$langElementList="Liste des éléments";
$langResult="Résultat";
$langScore="Points";
$langCorrespondsTo="Correspond à";
$langExpectedChoice="Choix attendu";
$langYourTotalScore="Vous avez obtenu un total de";
?>

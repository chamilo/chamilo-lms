<?php
/* For licensing terms, see /license.txt */
/*
 * Copy user.user_id to extra field ScormStudentId
*/
exit;
$extraFieldName = 'ScormStudentId';

use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ExtraField;
use Doctrine\ORM\OptimisticLockException;

if (php_sapi_name() !== 'cli') {
    die("this script is supposed to be run from the command-line\n");
}

require __DIR__.'/../../cli-config.php';
require_once __DIR__.'/../../main/inc/lib/api.lib.php';
require_once __DIR__.'/../../main/inc/lib/database.constants.inc.php';

$debug = false;

ini_set('memory_limit', -1);


// Read extra field definition
$extraFieldRepository = Database::getManager()->getRepository('ChamiloCoreBundle:ExtraField');
$extraField = $extraFieldRepository->findOneBy(
    [
        'extraFieldType' => ExtraField::USER_FIELD_TYPE,
        'variable' => $extraFieldName,
    ]
) or die("Cannot find user extra field '$extraFieldName'\n");


// Read all values in this extra field
$extraFieldValueRepository = Database::getManager()->getRepository('ChamiloCoreBundle:ExtraFieldValues');
foreach ($extraFieldValueRepository->findBy(['field' => $extraField]) as $extraFieldValue) {
    $extraFieldValues[$extraFieldValue->getItemId()] = $extraFieldValue;
}


// for each known user
$userRepository = Database::getManager()->getRepository('ChamiloUserBundle:User');
foreach ($userRepository->findAll() as $user) {
    $value = $user->getUserId();
    if (array_key_exists($user->getId(), $extraFieldValues)) {
        // user already has a value in the extra field
        if ($extraFieldValues[$user->getId()]->getValue() === $value) {
            // value is already ok, nothing to do
        } else {
            // wrong value, fix it
            $extraFieldValue->setValue($value);
            Database::getManager()->persist($extraFieldValue);
            if ($debug) {
                echo 'Updated ' . $user->getUsername() . ' extra field value to ' . $value . "\n";
            }
        }
    } else {
        // user does not have a value in the extra field
        $extraFieldValue = new ExtraFieldValues();
        $extraFieldValue->setValue($value);
        $extraFieldValue->setField($extraField);
        $extraFieldValue->setItemId($user->getId());
        Database::getManager()->persist($extraFieldValue);
        $extraFieldValues[$user->getId()] = $extraFieldValue;
        if ($debug) {
            echo 'Created ' . $user->getUsername() . ' extra field with value ' . $value . "\n";
        }
    }
}
try {
    Database::getManager()->flush();
} catch (OptimisticLockException $exception) {
    die($exception->getMessage()."\n");
}

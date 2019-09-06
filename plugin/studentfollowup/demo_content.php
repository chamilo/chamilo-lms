<?php

exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

$em = Database::getManager();
$insertUserInfo = api_get_user_entity(api_get_user_id());
$userInfo = api_get_user_entity(16);

$title = uniqid('title', true);

$post = new \Chamilo\PluginBundle\Entity\StudentFollowUp\CarePost();
$post
    ->setTitle($title)
    ->setContent($title)
    ->setExternalCareId(2)
    ->setCreatedAt(new DateTime())
    ->setUpdatedAt(new DateTime())
    ->setPrivate(false)
    ->setInsertUser($insertUserInfo)
    ->setExternalSource(0)
    //->setParent($parent)
    ->setTags(['php', 'react'])
    ->setUser($userInfo)
;
$em->persist($post);
$em->flush();

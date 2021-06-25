<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BaseResourceFileAction
{
    public static function setLinks(AbstractResource $resource, $em): void
    {
        $resourceNode = $resource->getResourceNode();
        $links = $resource->getResourceLinkArray();
        if ($links) {
            $groupRepo = $em->getRepository(CGroup::class);
            $courseRepo = $em->getRepository(Course::class);
            $sessionRepo = $em->getRepository(Session::class);
            $userRepo = $em->getRepository(User::class);

            foreach ($links as $link) {
                $resourceLink = new ResourceLink();
                $linkSet = false;
                if (isset($link['cid']) && !empty($link['cid'])) {
                    $course = $courseRepo->find($link['cid']);
                    if (null !== $course) {
                        $linkSet = true;
                        $resourceLink->setCourse($course);
                    } else {
                        throw new InvalidArgumentException(sprintf('Course #%s does not exists', $link['cid']));
                    }
                }

                if (isset($link['sid']) && !empty($link['sid'])) {
                    $session = $sessionRepo->find($link['sid']);
                    if (null !== $session) {
                        $linkSet = true;
                        $resourceLink->setSession($session);
                    } else {
                        throw new InvalidArgumentException(sprintf('Session #%s does not exists', $link['sid']));
                    }
                }

                if (isset($link['gid']) && !empty($link['gid'])) {
                    $group = $groupRepo->find($link['gid']);
                    if (null !== $group) {
                        $linkSet = true;
                        $resourceLink->setGroup($group);
                    } else {
                        throw new InvalidArgumentException(sprintf('Group #%s does not exists', $link['gid']));
                    }
                }

                if (isset($link['uid']) && !empty($link['uid'])) {
                    $user = $userRepo->find($link['uid']);
                    if (null !== $user) {
                        $linkSet = true;
                        $resourceLink->setUser($user);
                    } else {
                        throw new InvalidArgumentException(sprintf('User #%s does not exists', $link['uid']));
                    }
                }

                if (isset($link['visibility'])) {
                    $resourceLink->setVisibility((int) $link['visibility']);
                } else {
                    throw new InvalidArgumentException('Link needs a visibility key');
                }

                if ($linkSet) {
                    $em->persist($resourceLink);
                    $resourceNode->addResourceLink($resourceLink);
                    //$em->persist($resourceNode);
                    //$em->persist($resource->getResourceNode());
                }
            }
        }

        // Use by Chamilo not api platform.
        /*$links = $resource->getResourceLinkEntityList();
        if ($links) {
            error_log('$resource->getResourceLinkEntityList()');
            foreach ($links as $link) {
                $rights = [];
                switch ($link->getVisibility()) {
                    case ResourceLink::VISIBILITY_PENDING:
                    case ResourceLink::VISIBILITY_DRAFT:
                        $editorMask = ResourceNodeVoter::getEditorMask();
                        $resourceRight = new ResourceRight();
                        $resourceRight
                            ->setMask($editorMask)
                            ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                        ;
                        $rights[] = $resourceRight;

                        break;
                }

                if (!empty($rights)) {
                    foreach ($rights as $right) {
                        $link->addResourceRight($right);
                    }
                }
                //error_log('link adding to node: '.$resource->getResourceNode()->getId());
                //error_log('link with user : '.$link->getUser()->getUsername());
                $resource->getResourceNode()->addResourceLink($link);

                $em->persist($link);
            }
        }*/
    }
    /**
     * Function loaded when creating a resource using the api, then the ResourceListener is executed.
     */
    protected function handleCreateRequest(AbstractResource $resource, Request $request, EntityManager $em): array
    {
        //error_log('handleCreateRequest');
        $contentData = $request->getContent();
        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);
            $title = $contentData['title'] ?? '';
            $comment = $contentData['comment'] ?? '';
            $parentResourceNodeId = $contentData['parentResourceNodeId'] ?? 0;
            $fileType = $contentData['filetype'] ?? '';
            $resourceLinkList = $contentData['resourceLinkList'] ?? [];
        } else {
            $title = $request->get('title');
            $comment = $request->get('comment');
            $parentResourceNodeId = (int) $request->get('parentResourceNodeId');
            $fileType = $request->get('filetype');
            $resourceLinkList = $request->get('resourceLinkList', []);
            if (!empty($resourceLinkList)) {
                $resourceLinkList = false === strpos($resourceLinkList, '[') ? json_decode('['.$resourceLinkList.']', true) : json_decode($resourceLinkList, true);
                if (empty($resourceLinkList)) {
                    $message = 'resourceLinkList is not a valid json. Use for example: [{"cid":1, "visibility":1}]';

                    throw new InvalidArgumentException($message);
                }
            }
        }

        if (empty($fileType)) {
            throw new Exception('filetype needed: folder or file');
        }

        if (0 === $parentResourceNodeId) {
            throw new Exception('parentResourceNodeId int value needed');
        }

        $resource->setParentResourceNode($parentResourceNodeId);

        switch ($fileType) {
            case 'file':
                $content = '';
                if ($request->request->has('contentFile')) {
                    $content = $request->request->get('contentFile');
                }
                $fileParsed = false;
                // File upload.
                if ($request->files->count() > 0) {
                    if (!$request->files->has('uploadFile')) {
                        throw new BadRequestHttpException('"uploadFile" is required');
                    }

                    /** @var UploadedFile $uploadedFile */
                    $uploadedFile = $request->files->get('uploadFile');
                    $title = $uploadedFile->getClientOriginalName();
                    $resource->setUploadFile($uploadedFile);
                    $fileParsed = true;
                }

                // Get data in content and create a HTML file.
                if (!$fileParsed && $content) {
                    $handle = tmpfile();
                    fwrite($handle, $content);
                    $meta = stream_get_meta_data($handle);
                    $file = new UploadedFile($meta['uri'], $title.'.html', 'text/html', null, true);
                    $resource->setUploadFile($file);
                    $fileParsed = true;
                }

                if (!$fileParsed) {
                    throw new InvalidArgumentException('filetype was set to "file" but not upload file found');
                }

                break;
            case 'folder':
                break;
        }

        if (empty($title)) {
            throw new InvalidArgumentException('title is required');
        }

        $resource->setResourceName($title);

        // Set resource link list if exists.
        if (!empty($resourceLinkList)) {
            $resource->setResourceLinkArray($resourceLinkList);
        }

        return [
            'filetype' => $fileType,
            'comment' => $comment,
        ];
    }

    protected function handleUpdateRequest(AbstractResource $resource, ResourceRepository $repo, Request $request, EntityManager $em)
    {
        $contentData = $request->getContent();
        $resourceLinkList = [];
        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);
            $title = $contentData['title'] ?? '';
            $content = $contentData['contentFile'] ?? '';
            $resourceLinkList = $contentData['resourceLinkListFromEntity'] ?? [];
        } else {
            $title = $request->get('title');
            $content = $request->request->get('contentFile');
            //$comment = $request->request->get('comment');
        }

        $repo->setResourceName($resource, $title);

        $hasFile = $resource->getResourceNode()->hasResourceFile();

        $resourceNode = $resource->getResourceNode();

        if ($hasFile && !empty($content)) {
            if ($resourceNode->hasResourceFile()) {
                // The content is updated by the ResourceNodeListener.php
                $resourceNode->setContent($content);
                $resourceNode->getResourceFile()->setSize(\strlen($content));
            }
            $resourceNode->getResourceFile()->setUpdatedAt(new DateTime());
            $resource->setResourceNode($resourceNode);
        }

        $link = null;
        if (!empty($resourceLinkList)) {
            foreach ($resourceLinkList as $linkArray) {
                // Find the exact link.
                $linkId = $linkArray['id'] ?? 0;
                if (!empty($linkId)) {
                    /** @var ResourceLink $link */
                    $link = $resourceNode->getResourceLinks()->filter(fn ($link) => $link->getId() === $linkId)->first();

                    if (null !== $link) {
                        $link->setVisibility((int) $linkArray['visibility']);

                        break;
                    }
                }
            }

            $resource->setResourceLinkArray($resourceLinkList);
            self::setLinks($resource, $em);
            /*

            $groupRepo = $em->getRepository(CGroup::class);
            $courseRepo = $em->getRepository(Course::class);
            $sessionRepo = $em->getRepository(Session::class);
            $userRepo = $em->getRepository(User::class);

            foreach ($resourceLinkList as $link) {
                $resourceLink = new ResourceLink();
                $linkSet = false;
                if (isset($link['cid']) && !empty($link['cid'])) {
                    $course = $courseRepo->find($link['cid']);
                    if (null !== $course) {
                        $linkSet = true;
                        $resourceLink->setCourse($course);
                    } else {
                        throw new InvalidArgumentException(sprintf('Course #%s does not exists', $link['cid']));
                    }
                }

                if (isset($link['sid']) && !empty($link['sid'])) {
                    $session = $sessionRepo->find($link['sid']);
                    if (null !== $session) {
                        $linkSet = true;
                        $resourceLink->setSession($session);
                    } else {
                        throw new InvalidArgumentException(sprintf('Session #%s does not exists', $link['sid']));
                    }
                }

                if (isset($link['gid']) && !empty($link['gid'])) {
                    $group = $groupRepo->find($link['gid']);
                    if (null !== $group) {
                        $linkSet = true;
                        $resourceLink->setGroup($group);
                    } else {
                        throw new InvalidArgumentException(sprintf('Group #%s does not exists', $link['gid']));
                    }
                }

                if (isset($link['uid']) && !empty($link['uid'])) {
                    $user = $userRepo->find($link['uid']);
                    if (null !== $user) {
                        $linkSet = true;
                        $resourceLink->setUser($user);
                    } else {
                        throw new InvalidArgumentException(sprintf('User #%s does not exists', $link['uid']));
                    }
                }

                if (isset($link['visibility'])) {
                    $resourceLink->setVisibility((int) $link['visibility']);
                } else {
                    throw new InvalidArgumentException('Link needs a visibility key');
                }

                if ($linkSet) {
                    error_log('link added');
                    $em->persist($resourceLink);
                    //$resourceLink->setResourceNode($resource->getResourceNode());
                    $resource->getResourceNode()->addResourceLink($resourceLink);
                    $em->persist($resource->getResourceNode());
                }
            }

            */
        }

        $isRecursive = !$hasFile;
        // If it's a folder then change the visibility to the children (That have the same link).
        if ($isRecursive && null !== $link) {
            $repo->copyVisibilityToChildren($resource->getResourceNode(), $link);
        }

        $resourceNode->setUpdatedAt(new DateTime());

        error_log('Finish update resource node file action');

        return $resource;
    }
}

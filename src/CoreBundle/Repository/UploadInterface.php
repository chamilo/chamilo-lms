<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface UploadInterface
{
    public function saveUpload(UploadedFile $file): ResourceInterface;
}

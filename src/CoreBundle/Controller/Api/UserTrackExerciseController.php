<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UserTrackExerciseController extends AbstractController
{

    public function __invoke(?int $user_id, ?int $quiz_id, ?string $extra_field_name, ?string $extra_field_value): TrackEExercise
    {
        $criteria = [];
        if (isset($user_id)) {
            $criteria = [
                'exeExoId' => $quiz_id,
                'user' => api_get_user_entity($user_id),
                'status' => ''
            ];
        } else {
            $extraFieldValues = new \ExtraFieldValue('user');
            $item = $extraFieldValues->get_item_id_from_field_variable_and_field_value($extra_field_name, $extra_field_value);
            $userId = (int) $item['item_id'];
            $criteria = [
                'exeExoId' => $quiz_id,
                'user' => api_get_user_entity($userId),
                'status' => ''
            ];
        }

        /** @var TrackEExercise $tExercise */
        $tExercise = Container::getTrackEExerciseRepository()->findOneBy($criteria, ['exeId' => 'DESC']);

        return $tExercise;
    }
}

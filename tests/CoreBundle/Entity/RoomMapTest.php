<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\Branch;
use Chamilo\CoreBundle\Entity\Room;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class RoomMapTest extends TestCase
{
    /**
     * The polygon is in image-relative coordinates, not GPS coordinates.
     * This prevents the rendered areas from moving as the image is resized.
     */
    public function testValidNormalizedPolygon(): void
    {
        $room = $this->newRoom();
        $points = [[0.1, 0.2], [0.8, 0.2], [0.8, 0.9]];
        $room->setRoomMap(['type' => 'polygon', 'points' => $points]);

        self::assertSame($points, $room->getRoomMap()['points']);
        self::assertSame([], $this->roomMapViolations($room));
    }

    public function testNullMapIsAllowed(): void
    {
        self::assertSame([], $this->roomMapViolations($this->newRoom()));
    }

    /**
     * @dataProvider invalidMaps
     */
    public function testInvalidMapIsRejected(array $map): void
    {
        $room = $this->newRoom()->setRoomMap($map);

        self::assertNotEmpty($this->roomMapViolations($room));
    }

    public static function invalidMaps(): iterable
    {
        yield 'unsupported type' => [['type' => 'rectangle', 'points' => [[0, 0], [1, 0], [1, 1]]]];

        yield 'too few points' => [['type' => 'polygon', 'points' => [[0, 0], [1, 1]]]];

        yield 'out of bounds' => [['type' => 'polygon', 'points' => [[0, 0], [1.01, 0], [0, 1]]]];

        yield 'non numeric' => [['type' => 'polygon', 'points' => [['0', 0], [1, 0], [0, 1]]]];

        yield 'extra fields' => [['type' => 'polygon', 'points' => [[0, 0], [1, 0], [0, 1]], 'html' => '<script>']];

        yield 'nested coordinates' => [['type' => 'polygon', 'points' => [[[0, 0]], [1, 0], [0, 1]]]];

        yield 'empty array' => [[]];
    }

    private function newRoom(): Room
    {
        return (new Room())
            ->setTitle('Test room')
            ->setBranch((new Branch())->setTitle('Test branch'))
        ;
    }

    /**
     * @return list<string>
     */
    private function roomMapViolations(Room $room): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;
        $errors = [];
        foreach ($validator->validate($room) as $violation) {
            if ('roomMap' === $violation->getPropertyPath()) {
                $errors[] = $violation->getMessage();
            }
        }

        return $errors;
    }
}

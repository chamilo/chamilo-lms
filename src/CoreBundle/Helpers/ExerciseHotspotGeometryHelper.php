<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

/**
 * The plane geometry behind hotspot and delineation questions.
 *
 * Pure arithmetic: no Doctrine, no request, no entities. It answers whether a learner's click
 * landed inside the teacher's shape and how far two polygons overlap, which is what turns an
 * answer into a score — so it must exist once and only once.
 */
class ExerciseHotspotGeometryHelper
{
    /**
     * @param array{x: float, y: float, ...} $point
     */
    public function isPointInHotspot(array $point, string $hotspotType, string $coordinates): bool
    {
        return match ($hotspotType) {
            'square' => $this->isPointInSquare($point, $coordinates),
            'circle' => $this->isPointInEllipse($point, $coordinates),
            'poly' => $this->isPointInPolygon($point, $coordinates),
            default => false,
        };
    }

    /**
     * @param array{x: float, y: float, ...} $point
     */
    public function isPointInSquare(array $point, string $coordinates): bool
    {
        [$origin, $width, $height] = $this->parseBoxCoordinates($coordinates);
        if (null === $origin) {
            return false;
        }

        return $point['x'] >= $origin['x']
            && $point['x'] <= $origin['x'] + $width
            && $point['y'] >= $origin['y']
            && $point['y'] <= $origin['y'] + $height;
    }

    /**
     * @param array{x: float, y: float, ...} $point
     */
    public function isPointInEllipse(array $point, string $coordinates): bool
    {
        [$origin, $width, $height] = $this->parseBoxCoordinates($coordinates);
        if (null === $origin || $width <= 0.0 || $height <= 0.0) {
            return false;
        }

        $radiusX = $width / 2;
        $radiusY = $height / 2;
        $centerX = $origin['x'] + $radiusX;
        $centerY = $origin['y'] + $radiusY;

        return ((($point['x'] - $centerX) ** 2) / ($radiusX ** 2))
            + ((($point['y'] - $centerY) ** 2) / ($radiusY ** 2)) <= 1.0;
    }

    /**
     * @param array{x: float, y: float, ...} $point
     */
    public function isPointInPolygon(array $point, string $coordinates): bool
    {
        $vertices = [];
        foreach (explode('|', $coordinates) as $coordinate) {
            $decoded = $this->decodeHotspotPoint($coordinate);
            if (null !== $decoded) {
                $vertices[] = $decoded;
            }
        }

        $count = \count($vertices);
        if ($count < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $vertices[$i]['x'];
            $yi = $vertices[$i]['y'];
            $xj = $vertices[$j]['x'];
            $yj = $vertices[$j]['y'];

            $intersects = (($yi > $point['y']) !== ($yj > $point['y']))
                && ($point['x'] < ($xj - $xi) * ($point['y'] - $yi) / (($yj - $yi) ?: 0.000001) + $xi);
            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * @return array{x: float, y: float, answerId?: int}|null
     */
    public function decodeHotspotPoint(string $coordinate): ?array
    {
        $answerId = 0;
        $coordinateValue = trim($coordinate);
        if (str_contains($coordinateValue, ':')) {
            [$answerIdValue, $coordinateValue] = explode(':', $coordinateValue, 2);
            $answerId = (int) $answerIdValue;
        }

        $parts = array_map('trim', explode(';', $coordinateValue));
        if (\count($parts) < 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return null;
        }

        $point = ['x' => (float) $parts[0], 'y' => (float) $parts[1]];
        if ($answerId > 0) {
            $point['answerId'] = $answerId;
        }

        return $point;
    }

    /**
     * @return array{0: array{x: float, y: float}|null, 1: float, 2: float}
     */
    public function parseBoxCoordinates(string $coordinates): array
    {
        $parts = explode('|', $coordinates);
        $origin = $this->decodeHotspotPoint((string) ($parts[0] ?? ''));
        $width = isset($parts[1]) && is_numeric($parts[1]) ? (float) $parts[1] : 0.0;
        $height = isset($parts[2]) && is_numeric($parts[2]) ? (float) $parts[2] : 0.0;

        return [$origin, $width, $height];
    }

    /**
     * @return array<int, array{x: float, y: float}>
     */
    public function parseDelineationPolygon(string $coordinates): array
    {
        $normalizedCoordinates = str_replace('/', '|', trim($coordinates));
        $points = [];
        foreach (explode('|', $normalizedCoordinates) as $coordinate) {
            $point = $this->decodeHotspotPoint($coordinate);
            if (null !== $point) {
                $points[] = ['x' => $point['x'], 'y' => $point['y']];
            }
        }

        return $this->removeDuplicateClosingPoint($points);
    }

    /**
     * @param array<int, array{x: float, y: float}> $points
     *
     * @return array<int, array{x: float, y: float}>
     */
    public function removeDuplicateClosingPoint(array $points): array
    {
        $count = \count($points);
        if ($count < 4) {
            return $points;
        }

        $first = $points[0];
        $last = $points[$count - 1];
        if (abs($first['x'] - $last['x']) < 0.0001 && abs($first['y'] - $last['y']) < 0.0001) {
            array_pop($points);
        }

        return $points;
    }

    /**
     * @param array<int, array{x: float, y: float}> $polygon
     */
    public function encodePolygonCoordinates(array $polygon): string
    {
        return implode('|', array_map(static fn (array $point): string => $point['x'].';'.$point['y'], $polygon));
    }

    /**
     * @param array<int, array{x: float, y: float}> $firstPolygon
     * @param array<int, array{x: float, y: float}> $secondPolygon
     *
     * @return array{minX: float, maxX: float, minY: float, maxY: float}|null
     */
    public function getPolygonUnionBounds(array $firstPolygon, array $secondPolygon): ?array
    {
        $points = [...$firstPolygon, ...$secondPolygon];
        if ([] === $points) {
            return null;
        }

        $xValues = array_map(static fn (array $point): float => $point['x'], $points);
        $yValues = array_map(static fn (array $point): float => $point['y'], $points);

        return [
            'minX' => min($xValues),
            'maxX' => max($xValues),
            'minY' => min($yValues),
            'maxY' => max($yValues),
        ];
    }

    /**
     * @param array<int, array{x: float, y: float}> $firstPolygon
     * @param array<int, array{x: float, y: float}> $secondPolygon
     */
    public function polygonsOverlap(array $firstPolygon, array $secondPolygon): bool
    {
        $metrics = $this->getDelineationOverlapMetrics($secondPolygon, $firstPolygon);

        return $metrics['overlap'] > 0.0;
    }

    /**
     * @param array<int, array{x: float, y: float}> $expectedPolygon
     * @param array<int, array{x: float, y: float}> $studentPolygon
     *
     * @return array{overlap: float, missing: float, excess: float}
     */
    public function getDelineationOverlapMetrics(array $expectedPolygon, array $studentPolygon): array
    {
        $bounds = $this->getPolygonUnionBounds($expectedPolygon, $studentPolygon);
        if (null === $bounds) {
            return ['overlap' => 0.0, 'missing' => 100.0, 'excess' => 100.0];
        }

        $maxDimension = max($bounds['maxX'] - $bounds['minX'], $bounds['maxY'] - $bounds['minY']);
        $step = max(1.0, ceil($maxDimension / 500.0));
        $expectedArea = 0;
        $studentArea = 0;
        $overlapArea = 0;
        $expectedCoordinates = $this->encodePolygonCoordinates($expectedPolygon);
        $studentCoordinates = $this->encodePolygonCoordinates($studentPolygon);

        for ($x = $bounds['minX']; $x <= $bounds['maxX']; $x += $step) {
            for ($y = $bounds['minY']; $y <= $bounds['maxY']; $y += $step) {
                $point = ['x' => $x + ($step / 2), 'y' => $y + ($step / 2)];
                $insideExpected = $this->isPointInPolygon($point, $expectedCoordinates);
                $insideStudent = $this->isPointInPolygon($point, $studentCoordinates);

                if ($insideExpected) {
                    ++$expectedArea;
                }
                if ($insideStudent) {
                    ++$studentArea;
                }
                if ($insideExpected && $insideStudent) {
                    ++$overlapArea;
                }
            }
        }

        if ($expectedArea <= 0) {
            return ['overlap' => 0.0, 'missing' => 100.0, 'excess' => 100.0];
        }

        $overlap = round(($overlapArea / $expectedArea) * 100.0, 2);
        $missing = max(0.0, 100.0 - $overlap);
        $excess = round((max(0, $studentArea - $overlapArea) / $expectedArea) * 100.0, 2);

        return [
            'overlap' => min(100.0, $overlap),
            'missing' => min(100.0, $missing),
            'excess' => min(100.0, $excess),
        ];
    }
}

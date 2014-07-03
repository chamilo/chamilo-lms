function foo($a, $b)
{
    if ($a === $b) {
        throw new \InvalidArgumentException('$a is not allowed to be the same as $b.');
    }

    return $b;
}
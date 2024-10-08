<?php

interface IProvider
{
    public function __construct(string $url, string $key, string $filePath);

    public function convert(string $text): string;
}

<?php

declare(strict_types=1);

namespace Ljfreelancer88\Transpose\Interfaces;

interface TransposeInterface
{
    public function __construct(?string $song = null);

    public function setKey(string $key): void;

    public function loadSong(?string $song = null): void;

    public function transpose(string $from, string $to): ?string;
}

<?php

declare(strict_types=1);

namespace Ljfreelancer88\TransposeInterface;

interface TransposeInterface
{
    public function __construct(?string $song = null);

    public function setKey(string $key): void;

    public function getNotesInScale(string $scale): array;

    public function loadSong(?string $song = null);

    public function transpose(string $from, string $to): string;

    private function setSectionsToBold(string $song): string

    private function isKeyBelongToFlatChords(object $object): bool

    private function isKeyBelongToSharpChords(object $object): bool

    private function transposeNote(string $note, string $from, string $to): void
}

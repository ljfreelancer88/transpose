<?php

declare(strict_types=1);

namespace Ljfreelancer88\Transpose;

use Ljfreelancer88\Exceptions\TransposeException;
use Ljfreelancer88\TransposeInterface;

final class Transpose implements TransposeInterface
{
    private static $chromaticScale = ['C', 'C#', 'Db', 'D', 'D#', 'Eb', 'E', 'F', 'F#', 'Gb', 'G', 'G#', 'Ab', 'A', 'A#', 'Bb', 'B'];

    private $chromaticScaleForSharp = [
        'C',
        ['C#', 'Db'],
        'D',
        ['D#', 'Eb'],
        'E',
        'F',
        ['F#', 'Gb'],
        'G',
        ['G#', 'Ab'],
        'A',
        ['A#', 'Bb'],
        'B'
    ];

    private $chromaticScaleForFlat = [
        'C',
        ['Db', 'C#'],
        'D',
        ['Eb', 'D#'],
        'E',
        'F',
        ['Gb', 'F#'],
        'G',
        ['Ab', 'G#'],
        'A',
        ['Bb', 'A#'],
        'B'
    ];

    // C is excempted because there is no accidentals
    private $sharpChords = ['C', 'C#', 'D', 'D#', 'E', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    private $flatChords = ['C', 'Db', 'Eb', 'F', 'Gb', 'Ab', 'Bb'];

    private $search = '`([A-G][b#]?(?=\s?(?![a-z])|(?=(2|5|6|7|9|11|13|6\/9|7\-5|7\-9|7\#5|7\#9|7\+5|7\+9|7b5|7b9|7sus2|7sus4|add2|add4|add9|m7b11|m#5|mbb5|m\(maj9\)|m\(add9\)|m6add9|mmaj7|m\(maj(7|11|13)\)|mmaj9|aug(7|9)?|aug\(maj[79]\)|dim|dim7|m\|maj7|m6|min(6|7|9|11|13)?|m7|m7[b#]5|m9|m11|m13|maj7\([b#]5\)|maj#11|maj7sus4#5|maj7sus2sus4|maj7|maj9|maj9\(#11\)|maj11|maj13|maj13\(#11\)|mb5|m|M7|maj|maj7b5|majb5|maj7sus(2|4)|sus(2|4)?[#b]?5?|sus2sus4|\))(?=(\s|\/)))|(?=(\/|\.|-|\(|\)))))`';
    private $song;
    private $formattedChords = [];
    private $replacementChords = [];
    private $key;

    public function __construct(?string $song = null)
    {
        if ($song !== null) {
            $this->song = $song;
        }
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getNotesInScale(string $scale): array
    {
        $notesInScale = [];
        $canCount = false;

        $scales = $this->isKeyBelongToSharpChords($this) ? $this->chromaticScaleForSharp : $this->chromaticScaleForFlat;
        while (count($notesInScale) < count($scales)) {
            foreach ($scales as $note) {
                if (is_array($note) && 
                    isset($note[0]) && $note[0] === $scale || 
                    isset($note[1]) && $note[1] === $scale) {
                    $canCount = true;
                } else {
                    if ($note === $scale) {
                        $canCount = true;
                    }
                }

                if ($canCount && in_array($note, $notesInScale) === false) {
                    $notesInScale[] = $note;
                }
            }
        }

        return $notesInScale;
    }    

    public function loadSong(?string $song = null)
    {
        if ($song !== null) {
            $this->song = $song;
        }

        if ($this->song === null) {
            throw new \TransposeException("No Song Loaded");
        }

        $song_chords = [];
        preg_match_all($this->search, $this->song, $song_chords);

        $u = array_unique($song_chords[0]);

        foreach ($u as $chord) {
            if (strlen($chord) > 1 && ($chord{1} == "b" || $chord{1} == "#")) {
                array_push($this->formattedChords, substr($chord, 0, 2));
            } else {
                array_push($this->formattedChords, substr($chord, 0, 1));
            }
        }

        $this->song = preg_replace($this->search, '|$1|', $this->song);
    }

    public function transpose(string $from, string $to): string
    {
        if (!in_array($from, self::$chromaticScale) || !in_array($to, self::$chromaticScale)) {
            throw new \TransposeException("Key is invalid!");
        }

        if ($this->song === '') {
            throw new \TransposeException("No Song Loaded");
        }

        foreach ($this->formattedChords as $note) {
            $this->transposeNote($note, $from, $to);
        }

        foreach ($this->formattedChords as &$note) {
            $note = "/\|" . $note . "\|/";
        }
        
        $this->song = preg_replace($this->formattedChords, $this->replacementChords, $this->song);

        $this->song = $this->setSectionsToBold($this->song);

        return preg_replace($this->search, '<span class="c">${1}</span>', $this->song);
    }

    private function setSectionsToBold(string $song): string
    {        
        return preg_replace('/(\[.+?\])/', '<strong>${1}</strong>', $song);
    }

    private function isKeyBelongToFlatChords(object $object): bool
    {
        return in_array($object->key, $this->flatChords);
    }

    private function isKeyBelongToSharpChords(object $object): bool
    {
        return in_array($object->key, $this->sharpChords);
    }

    private function transposeNote(string $note, string $from, string $to): void
    {
        $fromScale = $this->getNotesInScale($from);
        $toScale = $this->getNotesInScale($to);

        $noteCount = 0;
        $notePos = -1;
        foreach ($fromScale as $fromNote) {
            if (is_array($fromNote)) {
                if (isset($fromNote[0]) && $fromNote[0] === $note || 
                    isset($fromNote[1]) && $fromNote[1] === $note) {
                    $notePos = $noteCount;
                    break;
                }
            } else {
                if ($fromNote === $note) {
                    $notePos = $noteCount;
                    break;
                }
            }

            $noteCount++;
        }

        if ($notePos !== -1) {
            if (is_array($toScale[$notePos])) {
                if ($fromScale[$notePos][0] === $note) {
                    array_push($this->replacementChords, $toScale[$notePos][0]);
                } else {
                    array_push($this->replacementChords, $toScale[$notePos][1]);
                }
            } else {
                array_push($this->replacementChords, $toScale[$notePos]);
            }
        } else {
            throw new \TransposeException("Note Not Found: " . $note);
        }
    }    
}

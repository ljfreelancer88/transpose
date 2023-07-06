<?php

declare(strict_types=1);

namespace Ljfreelancer88\Transpose;

use Ljfreelancer88\Transpose\Exceptions\TransposeException;
use Ljfreelancer88\Transpose\Interfaces\TransposeInterface;

class Transpose implements TransposeInterface
{
    private static $chromaticScale = ['C', 'C#', 'Db', 'D', 'D#', 'Eb', 'E', 'F', 'F#', 'Gb', 'G', 'G#', 'Ab', 'A', 'A#', 'Bb', 'B'];

    private array $chromaticScaleForSharp = [
        #'C',
        ['C#', 'Db'],
        'D',
        ['D#', 'Eb'],
        'E',
        ['F', 'E#'],
        ['F#', 'Gb'],
        'G',
        ['G#', 'Ab'],
        'A',
        ['A#', 'Bb'],
        ['B', 'Cb'],
        ['B#', 'C'],
    ];

    private array $chromaticScaleForFlat = [
        'C',
        ['Db', 'C#'],
        'D',
        ['Eb', 'D#'],
        ['Fb', 'E'],
        'F',
        ['Gb', 'F#'],
        'G',
        ['Ab', 'G#'],
        'A',
        ['Bb', 'A#'],
        ['Cb', 'B'],
    ];

    // C is excempted because there is no accidentals
    private array $sharpChords = ['C', 'C#', 'D', 'D#', 'E', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    private $search = '`([A-G][b#]?(?=\s?(?![a-z])|(?=(2|5|6|7|9|11|13|6\/9|7\-5|7\-9|7\#5|7\#9|7\+5|7\+9|7b5|7b9|7sus2|7sus4|add2|add4|add9|m7b11|m#5|mbb5|m\(maj9\)|m\(add9\)|m6add9|mmaj7|m\(maj(7|11|13)\)|mmaj9|aug(7|9)?|aug\(maj[79]\)|dim|dim7|m\|maj7|m6|min(6|7|9|11|13)?|m7|m7[b#]5|m9|m11|m13|maj7\([b#]5\)|maj#11|maj7sus4#5|maj7sus2sus4|maj7|maj9|maj9\(#11\)|maj11|maj13|maj13\(#11\)|mb5|m|M7|maj|maj7b5|majb5|maj7sus(2|4)|sus(2|4)?[#b]?5?|sus2sus4|\))(?=(\s|\/)))|(?=(\/|\.|-|\(|\)))))`';
    
    private string $song;
    private array $formattedChords = [];
    private array $replacementChords = [];
    private string $key;

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
    
    public function loadSong(?string $song = null): void
    {
        if ($song !== null) {
            $this->song = $song;
        }

        if ($this->song === null) {
            throw new TransposeException("No song loaded");
        }        
                
        $this->formattedChords();

        $this->song = preg_replace($this->search, '|$1|', $this->song);
    }    

    public function transpose(string $from, string $to): string
    {
        if (!in_array($from, self::$chromaticScale, true) || !in_array($to, self::$chromaticScale, true)) {
            throw new TransposeException("Key is invalid!");
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

    private function formattedChords(): void
    {
        $song_chords = [];
        preg_match_all($this->search, $this->song, $song_chords);

        $chords = array_unique($song_chords[0]);        
        foreach ($chords as $chord) {
            if (strlen($chord) > 1 && ($chord[1] === 'b' || $chord[1] === '#')) {
                $this->formattedChords[] = substr($chord, 0, 2);
            } else {
                $this->formattedChords[] = substr($chord, 0, 1);
            }
        }
    }

    private function getNotesInScale(string $scale): array
    {
        $scale = ucfirst($scale);
        $scales = $this->isKeyBelongToSharpChords()
            ? $this->chromaticScaleForSharp 
            : $this->chromaticScaleForFlat;

        $notesInScale = [];
        $canCount = false;        
        while (count($notesInScale) < count($scales)) {            
            foreach ($scales as $note) {
                if ($this->hasEnharmonicEquivalent($note) && 
                    isset($note[0]) && $note[0] === $scale || 
                    isset($note[1]) && $note[1] === $scale) {
                    $canCount = true;
                } elseif (!$this->hasEnharmonicEquivalent($note) && $note === $scale) {
                    $canCount = true;
                }

                if ($canCount && !in_array($note, $notesInScale, true)) {
                    $notesInScale[] = $note;
                }
            }
        }

        return $notesInScale;
    }

    private function hasEnharmonicEquivalent(array $scale): bool
    {
        return is_array($scale);
    }

    private function setSectionsToBold(string $song): ?string
    {        
        return preg_replace('/(\[.+?\])/', '<strong>${1}</strong>', $song);
    }

    private function isKeyBelongToSharpChords(): bool
    {
        return in_array($this->key, $this->sharpChords, true);
    }
    
    private function transposeNote(string $note, string $from, string $to): void
    {
        $fromScale = $this->getNotesInScale($from);
        $toScale = $this->getNotesInScale($to);

        $notePos = -1;
        foreach ($fromScale as $noteCount => $fromNote) {
            if ($this->hasEnharmonicEquivalent($fromNote) && in_array($note, $fromNote, true)) {
                $notePos = $noteCount;
                break;
            } elseif ($fromNote === $note) {
                $notePos = $noteCount;
                break;
            }
        }

        /*
        If the $notePos value is not -1 (indicating that a matching note was found in the "from" key's scale), 
        the method proceeds to transpose the note to the corresponding note in the "to" key's scale.
        */
        if ($notePos !== -1) {
            $isKeySharp = $this->isKeyBelongToSharpChords();

            $transposedNote = is_array($toScale[$notePos])
                ? ($isKeySharp ? $toScale[$notePos][0] : $toScale[$notePos][1])
                : ($toScale[$notePos] ?? null);
    
            if (!$isKeySharp && is_array($toScale[$notePos] ?? null)) {
                // Convert flats to sharps for better enharmonic representation
                $transposedNote = $toScale[$notePos][0];
            }
            
            # Convert to enharmonic spelling
            if ($transposedNote === 'Fb') {
                $transposedNote = 'E';
            }

            if ($transposedNote === 'Cb') {
                $transposedNote = 'B';
            }

            if ($transposedNote === 'B#') {
                $transposedNote = 'C';
            }

            if ($transposedNote === 'E#') {
                $transposedNote = 'F';
            }

            $this->replacementChords[] = $transposedNote;
        } else {
            throw new TransposeException("Chord not found " . $note);
        }
    }
}
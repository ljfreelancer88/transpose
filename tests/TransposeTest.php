<?php

use PHPUnit\Framework\TestCase;
use Ljfreelancer88\Transpose\Transpose;
use Ljfreelancer88\Transpose\Exceptions\TransposeException;

class TransposeTest extends TestCase
{
    public function testTransposeInvalidKey(): void
    {
        $song = <<<EOD
[Verse]
G   G   Am    F
Example of string
spanning multiple lines
using heredoc syntax.
EOD;

        $transposer = new Transpose($song);
        $transposer->setKey('C');
        $transposer->loadSong();

        $this->expectException(TransposeException::class);
        $transposer->transpose('Z', 'A'); // Invalid key
    }
}

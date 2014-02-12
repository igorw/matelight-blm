<?php

namespace igorw\blm;

use iter;

class Frame {
    public $buffer;
    public $duration;

    function __construct($buffer, $duration) {
        $this->buffer = $buffer;
        $this->duration = $duration;
    }
}

function parse($blm) {
    $lines = explode("\n", trim(str_replace("\r", '', $blm)));
    $lines = iter\filter(function ($line) { return strlen($line) > 0; }, $lines);
    $lines = iter\filter(function ($line) { return $line[0] !== '#'; }, $lines);

    $buffer = [];
    $duration = null;
    foreach ($lines as $line) {
        if ($line[0] === '@') {
            if (count($buffer) > 0) {
                $frame = new Frame($buffer, $duration);
                yield $frame;
            }
            $buffer = [];
            $duration = substr($line, 1);
            continue;
        }

        $buffer[] = $line;
    }

    $frame = new Frame($buffer, $duration);
    yield $frame;
}

function render(Frame $frame, $render_row) {
    $rendered = '';
    foreach ($frame->buffer as $row) {
        $rendered .= $render_row($row);
    }
    // fake checksum
    $rendered .= "\x00\x00\x00\x00";
    return $rendered;
}

function render_row_centered($row) {
    $rendered = '';
    for ($i = 0; $i < 2; $i++) {
        for ($i = 0; $i < 2; $i++) {
            $rendered .= str_repeat("\x00\x00\x00", 2);
            $rendered .= str_repeat("\x00\x00\x00", 9);
            foreach (str_split($row) as $pixel) {
                $rendered .= $pixel ? "\xd0\xd0\xd0" : "\x00\x00\x00";
            }
            $rendered .= str_repeat("\x00\x00\x00", 9);
            $rendered .= str_repeat("\x00\x00\x00", 2);
        }
    }
    return $rendered;
}

function render_row_wide($row) {
    $rendered = '';
    for ($i = 0; $i < 2; $i++) {
        $rendered .= str_repeat("\x00\x00\x00", 2);
        foreach (str_split($row) as $pixel) {
            if ($pixel) {
                $rendered .= str_repeat("\xd0\xd0\xd0", 2);
            } else {
                $rendered .= str_repeat("\x00\x00\x00", 2);
            }
        }
        $rendered .= str_repeat("\x00\x00\x00", 2);
    }
    return $rendered;
}

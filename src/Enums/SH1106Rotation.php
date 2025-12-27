<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Enums;

use ScrapyardIO\Displays\Monochrome\SH1106\Enums\SH1106Command;

enum SH1106Rotation: int
{
    case LANDSCAPE = 0;
    case INVERTED_LANDSCAPE = 2;
    case PORTRAIT = 3;
    case INVERTED_PORTRAIT = 4;

    public function toRemap(): array
    {
        return match ($this) {
            self::LANDSCAPE => [
                SH1106Command::NO_HORIZONTAL_INVERSION->value,
                SH1106Command::NO_VERTICAL_INVERSION->value,
            ],
            self::INVERTED_LANDSCAPE => [
                SH1106Command::INVERT_DISPLAY_HORIZONTALLY->value,
                SH1106Command::NO_VERTICAL_INVERSION->value,
            ],
            self::PORTRAIT => [
                SH1106Command::NO_HORIZONTAL_INVERSION->value,
                SH1106Command::INVERT_DISPLAY_VERTICALLY->value,
            ],
            self::INVERTED_PORTRAIT => [
                SH1106Command::INVERT_DISPLAY_HORIZONTALLY->value,
                SH1106Command::INVERT_DISPLAY_VERTICALLY->value,
            ],
        };
    }
}


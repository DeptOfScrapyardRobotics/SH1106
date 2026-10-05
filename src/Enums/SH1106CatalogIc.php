<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Enums;

enum SH1106CatalogIc: string
{
    case SH1106 = 'sh1106';

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }
}

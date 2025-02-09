<?php

namespace Biostate\FilamentMenuBuilder\Enums;

enum MenuItemType: string
{
    case Link = 'link';
    case Model = 'model';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Link => 'Link',
            self::Model => 'Model',
        };
    }

    public static function fromValue(string $value): self
    {
        return match ($value) {
            'model' => self::Model,
            default => self::Link,
        };
    }
}

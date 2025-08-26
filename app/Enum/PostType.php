<?php

namespace App\Enum;

use App\Contracts\Iconable;

enum PostType: string implements Iconable
{
    case Text = 'text';
    case Media = 'media';

    /**
     * Defines the lucide-vue-next icon names to be used for each PostTYpe
     */
    public function icon(): string
    {
        return match ($this) {
            self::Text => 'FileText',
            self::Media => 'Image',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Media => 'Media',
        };
    }
}

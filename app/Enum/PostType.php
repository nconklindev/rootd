<?php

namespace App\Enum;

use App\Contracts\Iconable;

enum PostType: string implements Iconable
{
    case Code = 'code';
    case File = 'file';
    case Image = 'image';
    case Article = 'article';
    case Link = 'link';

    /**
     * Defines the lucide-vue-next icon names to be used for each PostTYpe
     *
     * @return string
     */
    public function icon(): string
    {
        return match ($this) {
            self::Code => 'Code2',
            self::File => 'FileTerminal',
            self::Image => 'Image',
            self::Article => 'FileText',
            self::Link => 'Link2'
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Code => 'Code',
            self::File => 'File',
            self::Image => 'Image',
            self::Article => 'Article',
            self::Link => 'Link'
        };
    }
}


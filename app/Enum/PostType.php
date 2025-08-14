<?php

namespace App\Enum;

enum PostType: string
{
    case Article = 'article';
    case Video = 'video';
    case Link = 'link';
    case Note = 'note';
}

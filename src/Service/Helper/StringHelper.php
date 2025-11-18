<?php

namespace App\Service\Helper;

use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\AsciiSlugger;

abstract class StringHelper
{
    public static function slugify(string $text): AbstractUnicodeString
    {
        return (new AsciiSlugger())->slug($text);
    }
}

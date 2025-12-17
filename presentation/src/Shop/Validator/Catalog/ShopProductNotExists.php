<?php

declare(strict_types=1);

namespace App\Presentation\Shop\Validator\Catalog;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class ShopProductNotExists extends Constraint
{
    public string $message = 'A product already has this title.';

    public const string SAME_TITLE_ERROR = '81b2f5d9-768b-4549-80ef-2ca581cf9372';

    protected const array ERROR_NAMES = [
        self::SAME_TITLE_ERROR => 'SAME_TITLE_ERROR',
    ];
}

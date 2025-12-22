<?php

declare(strict_types=1);

namespace App\Domain\Shop\Catalog\Exception;

use Throwable;

final class ProductNotFoundException extends CatalogDomainException
{
    public function __construct(
        string $message = 'Product not found.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

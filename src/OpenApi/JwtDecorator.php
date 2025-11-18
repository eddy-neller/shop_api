<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

class JwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        if (!$schemas instanceof ArrayObject) {
            return $openApi;
        }

        $schemas = $openApi->getComponents()->getSecuritySchemes() ?? new ArrayObject([]);
        $schemas['ApiKeyAuth'] = new ArrayObject([
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'Authorization',
        ]);

        return $openApi;
    }
}

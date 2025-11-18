<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\Dto\SendMailInput;
use App\Dto\SendMailOutput;
use App\State\SendMailProcessor;
use ArrayObject;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/util/send-mail',
            status: 200,
            openapi: new Model\Operation(
                summary: 'Send an email through the API',
                description: "Allows sending an email by providing the sender's details and message content.",
                requestBody: new Model\RequestBody(
                    description: 'Payload containing the sender information and message details',
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string', 'example' => 'John Doe'],
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                                    'subject' => ['type' => 'string', 'example' => 'Hello'],
                                    'message' => ['type' => 'string', 'example' => 'This is a test message'],
                                ],
                                'required' => ['name', 'email', 'subject', 'message'],
                            ],
                        ],
                    ])
                ),
            ),
            normalizationContext: ['groups' => ['send_mail:read']],
            denormalizationContext: ['groups' => ['send_mail:write']],
            input: SendMailInput::class,
            output: SendMailOutput::class,
            name: 'send-mail',
            processor: SendMailProcessor::class,
        ),
    ],
)]
class SendMail
{
    // Vide ou juste des constantes, si besoin
}

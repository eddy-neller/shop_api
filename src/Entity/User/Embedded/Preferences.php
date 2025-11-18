<?php

namespace App\Entity\User\Embedded;

use JsonSerializable;

final readonly class Preferences implements JsonSerializable
{
    public function __construct(
        public string $lang = 'fr',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            lang: $data['lang'] ?? 'fr',
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'lang' => $this->lang,
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    public function withLang(string $lang): self
    {
        return new self(lang: $lang);
    }
}

<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class SendMailOutput
{
    #[Groups(['send_mail:read'])]
    public string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}

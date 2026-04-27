<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class PosKantinException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message = 'Integrasi POS Kantin gagal.', protected array $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}

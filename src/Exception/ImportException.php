<?php

namespace App\Exception;

class ImportException extends \RuntimeException
{
    public function __construct(
        private readonly ImportErrorType $type,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getType(): ImportErrorType
    {
        return $this->type;
    }
}

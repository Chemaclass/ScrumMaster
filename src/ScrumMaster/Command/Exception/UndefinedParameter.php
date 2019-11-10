<?php

declare(strict_types=1);

namespace App\ScrumMaster\Command\Exception;

final class UndefinedParameter extends \Exception
{
    public function __construct(array $parameters)
    {
        parent::__construct('Undefined parameter: ' . implode(', ', $parameters));
    }
}

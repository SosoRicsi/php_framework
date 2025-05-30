<?php

namespace Tests\Stubs;

use Radiant\Http\Request\Request;

class CustomRequest extends Request
{
    public function customValue(): string
    {
        return 'from subclass';
    }
}

<?php

namespace Zuno\Http\Validation\Contracts;

interface ValidatesWhenResolved
{
    /**
     * Validate the given class instance.
     *
     * @return void
     */
    public function resolvedFormRequestValidation();
}

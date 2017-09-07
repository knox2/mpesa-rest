<?php

namespace Knox\MPESA\Facades;


use Illuminate\Support\Facades\Facade;

class MPESA extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'MPESA';
    }
}
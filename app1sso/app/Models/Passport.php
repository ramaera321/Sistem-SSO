<?php

namespace App\Models;

use Laravel\Passport\Client;

class Passport extends Client
{
    public function skipsAuthorization()
    {
        // dd($this);
        // false : All client should stop for authorization
        return false;
    }
}

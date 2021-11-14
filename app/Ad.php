<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    // content_type = 1 => product
    // content_type = 2 => offers
    // content_type = 0 => out link
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactImage extends Model
{
    protected $fillable = ['image', 'contact_id'];
}
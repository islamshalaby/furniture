<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    protected $fillable = ['phone', 'message', 'seen'];

    public function images() {
        return $this->hasMany('App\ContactImage', 'contact_id');
    }
    //
    // protected $appendes = ['custom'];
    // public function getCustomAttribute(){
    //     $unread_messages_count = ContactUs::where('seen' , 0)->count();
    //     return $unread_messages_count;
    // }
}

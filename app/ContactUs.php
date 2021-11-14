<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    protected $fillable = ['phone', 'message', 'seen', 'user_id'];

    public function images() {
        return $this->hasMany('App\ContactImage', 'contact_id');
    }

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }
    //
    // protected $appendes = ['custom'];
    // public function getCustomAttribute(){
    //     $unread_messages_count = ContactUs::where('seen' , 0)->count();
    //     return $unread_messages_count;
    // }
}

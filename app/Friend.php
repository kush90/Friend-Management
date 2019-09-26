<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Friend extends Model
{
    //
    protected $fillable = [
        'first_user', 'second_user','friend',
    ];

    function firstUser()
    {
        return $this->belongsTo(User::class, 'first_user');
    }
    function secondUser()
    {
        return $this->belongsTo(User::class, 'second_user');
    }
    

}

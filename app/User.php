<?php

namespace App;

use Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Default_line;

class User extends Authenticatable
{
    use Notifiable;

    protected static function boot() { //cascade on soft delete
        parent::boot();

        static::created(function($user) {
            $defaultLine = new Default_line;
            $defaultLine->user_id = $user->id;
            $defaultLine->line_id = ( isset($user->default_line) )? $user->default_line : 1;
            $defaultLine->save();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Automatically creates hash for the user password.
     *
     * @param  string  $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function default_line(){
        // return Default_line::all();
        return $this->hasOne('App\Default_line', 'user_id');
    }

}

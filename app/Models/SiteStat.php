<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteStat extends Model
{
    protected $table = 'sitestats';
    public $timestamps = false;

    protected $fillable = [
        'url', 'method', 'ip', 'device', 'platform', 'browser', 'country', 'visited_at'
    ];
}
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Txn extends Model
{
     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'eth_txn', 'eth', 'eth_addr' , 'ar_txn', 'ar','status', 'data', 'ar_addr'
    ];
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    public function StockistToTerminal()
    {
        return $this->hasMany('App\Model\StockistToTerminal','terminal_id');
    }


}

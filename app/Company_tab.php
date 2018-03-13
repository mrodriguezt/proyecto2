<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company_tab extends Model
{
    protected $table ="COMPANY_TAB";
    protected $connection = 'oracle';
    protected $fillable =["COMPANY","NAME"];
}

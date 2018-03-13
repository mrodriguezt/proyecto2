<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Instant_invoice extends Model
{
    protected $table ="INSTANT_INVOICE";
    protected $connection = 'oracle';
    protected $fillable =["COMPANY","INVOICE_ID"];
}

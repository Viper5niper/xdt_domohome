<?php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evento extends Model {
    use SoftDeletes;
    protected $table = 'eventos';
    protected $fillable = ['tabla', 'dkey','orden','payload','hora']; // allow mass assignment
    protected $hidden = ['payload','tabla','deleted_at']; // hidden columns from select results
    protected $dates = ['hora','deleted_at']; // the attributes that should be mutated to dates

}
<?php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Luz extends Model {
    use SoftDeletes;
    protected $table = 'luces';
    protected $fillable = ['dkey', 'name','encendida']; // allow mass assignment
    protected $hidden = ['deleted_at']; // hidden columns from select results
    protected $dates = ['deleted_at']; // the attributes that should be mutated to dates
    
    public function toggle(){
        $viejo = $this->attributes['encendida'];
        $nuevo = !$viejo;
        $this->attributes['encendida'] = $nuevo;
    }
}
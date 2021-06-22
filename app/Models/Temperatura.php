<?php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Temperatura extends Model {
    use SoftDeletes;
    protected $table = 'temperaturas';
    protected $fillable = ['id_temp', 'temp','hora','fecha','created_at']; // allow mass assignment
    
    public function toggle(){
        $viejo = $this->attributes['encendida'];
        $nuevo = !$viejo;
        $this->attributes['encendida'] = $nuevo;
    }
}
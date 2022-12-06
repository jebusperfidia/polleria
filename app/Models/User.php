<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    //protected $table = 'users';
    //Esta propiedad sirva para especificar que tabla esta mapeada a este modelo
    
    /* 
    protected $primaryKey = 'un_pk_diferente_de_id'; 
    */

    //Esto significa que es una pk no incremental(NO AUTO_INCREMENT)
    //public $incrementing = false;
    
    //Establecer el data type de la pk
    //protected $keyType = 'string';

    //Significa que Eloquent no manipulará created_at y updated_at
    //Indicando que no tienes esas columnas o que si las tienes pero no quieres que Eloquent
    //Las llene automáticamente
    //public $timestamps = false;
    
    //Determina el formato de como se almacenan los atributos de fecha en la base de datos o cuando el modelo se serialize a array o JSON
    //Sin embargo, $dateFormat solo es para ponerle formato a los TIMESTAPM de created_at y updated_at
    //protected $dateFormat = 'y-m-d';

    //Utiliza otra conexión a base de datos que no es la que está por defecto
    //protected $connection = 'sqlsrv;

    //Se asigna un valor por defecto para una columna de la tabla, al momento de crear un modelo
    /* protected $attributes = [
        'ss' => '2022-05-02 14:20:22'
    ]; */
   
    //Indicamos que datos serán llenados de forma masiva o mass assigment
    protected $fillable = [
        'nombre',
        'usuario',
        'password'
    ];

    //Significa que todas mis columnas de la tabla serán mass assigment
    //protected $guarded = []; 

 //Indica qué datos no serán mostrados como atributo en la generación de datos desde una colección
    protected $hidden = [
        'password'
    ];


     /*
     Puede personalizar el formato de serialización de los atributos de fecha individuales de 
     loquent especificando el formato de fecha en las declaraciones de conversión del modelo:
     */
     /*   protected $casts = [
        'email_verified_at' => 'datetime',
    ]; */
}

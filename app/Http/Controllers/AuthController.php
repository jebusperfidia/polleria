<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Validator;

class AuthController extends Controller
{
    public function store(Request $request)
    {
        //Validaciones
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'usuario' => 'required|max:50|unique:users',
            'password' => 'required|min:8|confirmed'
        ]);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Generamos un valor hash para el campo password, desde el objeto $request
        $request->merge(['password' => Hash::make($request->password)]);


        // Si las validaciones son correctas, se da de alta el usuario
        $user = User::create($request->all());
        
       /*  $user = User::create([
            'nombre' => $request->nombre,
            'usuario' => $request->usuario,
            'password' => Hash::make($request->password)
        ]); */


        //Posteriormente, enviamos una respuesta, en formato JSON de la alta exitosa del usuario
        return response()->json([
            "status" => 1,
            "message" => "Alta de usuario exitosa",
            "usuario" => $user->nombre
        ], 201);
    }


    public function login(Request $request)
    {
        //Validaciones
        $validate = Validator::make($request->all(), [
            'usuario' => 'required',
            'password' => 'required|min:8'
        ]);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }


        //Generamos una variable para comprobar si existe algún usuario registrado con los valores recibidos
        $user = User::where("usuario", "=", $request->usuario)->first();

        //Si se encuentra alguna coincidencia, entramos al if
        if ($user) {
            //Validamos que el password enviado, coincida con el del usuario en la base de datos
            if (Hash::check($request->password, $user->password)) {
                //Generamos el token para el usuario
                $token = $user->createToken('auth_token')->plainTextToken;

                //Devolvemos una respuesta con el token de autenticación para futuras peticiones
                return response()->json([
                    "status" => 1,
                    "message" => "Usuario Logueado",
                    "auth_token" => $token
                ], 201);
                //Si el password no coincide, devolvemos una respuesta con un mensaje de error
            } else {
                return response()->json([
                    "status" => 0,
                    "message" => "Password incorrecto",
                ], 404);
            }
        }
        //Si el usuario no está registrado, devolvemos una respuesta con un mensaje de error
        else {
            return response()->json([
                "status" => 0,
                "message" => "Usuario no registrado"
            ], 404);
        }

    }

    public function index(){
        //Obtenemos los datos 
        return response()->json([
            "status" => 1,
            "usuario" => User::all()
        ], 201);
    }


    public function logout()
    {   
        
        //Eliminamos el token de autenticación generado para el usuario
        auth()->user()->tokens()->delete();

        //Enviamos una respuesta exitosa
        return response()->json([
            "status" => 1,
            "message" => "Sesión cerrada exitosamente"
        ], 201);
    }

    public function show($id){


        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);

        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos el usuario mediante el id y generamos una colección
        $user = User::find($id);

        //Validamos si el id recibido, es un usuario válido
        if ($user) {
            return response()->json([
                "status" => 1,
                "message" => "Datos encontrados con exito",
                "usuario" => $user
            ], 201);
        }
        //Si el usuario no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => 0,
                "message" => "Usuario no encontrado"
            ], 404);
        }

        //Obtenemos los datos del usuario autenticado
      /*   return response()->json([
            "status" => 1,
            "message" => "Acerca del perfil del usuario",
            "data" => [
                "id" => auth()->user()->id,
                "nombre" => auth()->user()->nombre,
                "usuario" => auth()->user()->usuario,
                "created_at" => auth()->user()->created_at
                ]
        ], 201); */
    }

    public function update(Request $request, $id)
    {   

        
        //Validaciones
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'usuario' => 'required|max:50|unique:users'
        ]);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);

        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }
        
        //Buscamos el usuario mediante el id y generamos una colección
        $user = User::find($id);

        //Validamos si el id recibido, es un usuario válido
        if($user){
            //Si el usuario es válido, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta
            if(!$user->update($request->all())) {
                return response()->json([
                    "status" => 0,
                    "message" => "No fue posible actualizar el usuario"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta
            else {
                return response()->json([
                    "status" => 1,
                    "message" => "Usuario actualizado con éxito",
                    "usuario" => $user
                ], 201);
            }
        } 
        //Si el usuario no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => 0,
                "message" => "Usuario no encontrado"
            ], 404);
        }


    }

    public function destroy($id){
        
        $validate = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);
        

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Buscamos el usuario mediante el id y generamos una colección
        $user = User::find($id);
        //Validamos si el id recibido, es un usuario válido
        if($user) {
        //dd($user);
         //Si el usuario es válido, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta
            if(!$user->delete()) {
                return response()->json([
                    "status" => 0,
                    "message" => "No fue posible eliminar al usuario"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta
            else {
                return response()->json([
                    "status" => 1,
                    "message" => "Usuario eliminado con éxito",
                    "usuario" => $user
                ], 201);
            }
        } 
        //Si el usuario no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => 0,
                "message" => "Usuario no encontrado"
            ], 404);
        }


    }

}
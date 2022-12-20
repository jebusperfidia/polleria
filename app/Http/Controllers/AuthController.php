<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Validator;

class AuthController extends Controller
{


    public function login(Request $request)
    {
        //Validaciones
        $validate = Validator::make($request->all(), [
            'usuario' => 'required|string',
            'password' => 'required|string|min:8'
        ]);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }


        //Generamos una colección para comprobar si existe algún usuario registrado con los valores recibidos
        $user = User::where("usuario", "=", $request->usuario)->first();

        //Si se encuentra alguna coincidencia, entramos al if
        if ($user) {
            //Validamos que el password enviado, coincida con el del usuario en la base de datos
            if (Hash::check($request->password, $user->password)) {
                //Generamos el token para el usuario
                $token = $user->createToken('auth_token')->plainTextToken;

                //Devolvemos una respuesta con el token de autenticación para futuras peticiones
                return response()->json([
                    "status" => true,
                    "message" => "Usuario Logueado",
                    "auth_token" => $token
                ], 201);
                //Si el password no coincide, devolvemos una respuesta con un mensaje de error
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Password incorrecto",
                ], 404);
            }
        }
        //Si no se existe coincidencia con el nombre de usuario, devolvemos una respuesta con un mensaje de error
        else {
            return response()->json([
                "status" => false,
                "message" => "Usuario no registrado"
            ], 404);
        }

    }

    public function logout()
    {

        //Eliminamos el token de autenticación generado para el usuario
        auth()->user()->tokens()->delete();

        //Enviamos una respuesta exitosa, en formato JSON
        return response()->json([
            "status" => true,
            "message" => "Sesión cerrada exitosamente"
        ], 201);
    }

    public function index()
    {
        //Obtenemos los datos y los mandamos en una respuesta, en formato JSON
        return response()->json([
            "status" => true,
            "usuario" => User::all()
        ], 201);
    }


    public function show($id)
    {

        //Validamos el formato del id del usuario
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

        //Validamos si se encontró alguna coincidencia
        if ($user) {
            //Si los datos fueron encontrados, enviamos una respuesta, en formato JSON
            return response()->json([
                "status" => true,
                "message" => "Datos encontrados con exito",
                "usuario" => $user
            ], 201);
        }
        //Si el usuario no fue encontrado, enviamos una respuesta en formato JSON
        else {
            return response()->json([
                "status" => false,
                "message" => "Usuario no encontrado"
            ], 404);
        }

        //Obtenemos los datos del usuario autenticado
        /*   return response()->json([
        "status" => true,
        "message" => "Acerca del perfil del usuario",
        "data" => [
        "id" => auth()->user()->id,
        "nombre" => auth()->user()->nombre,
        "usuario" => auth()->user()->usuario,
        "created_at" => auth()->user()->created_at
        ]
        ], 201); */
    }

    public function store(Request $request)
    {
        //Validaciones
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'usuario' => 'required|string|max:50|unique:users',
            'password' => 'required|string|min:8|confirmed'
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
            "status" => true,
            "message" => "Alta de usuario exitosa",
            "usuario" => $user->nombre
        ], 201);
    }


    public function update(Request $request, $id)
    {

        //Validamos el formato del id del usuario
        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);

        //Si hay algún error de validación, enviamos una respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos el usuario mediante el id y generamos una colección
        $user = User::find($id);

        //Validamos si el id recibido, es un usuario válido
        if ($user) {

            //Realizamos una validación en los datos recibidos en el body
            $validate = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'usuario' => [
                    'required',
                    'max:50',
                    'string',
                    //Validamos que el usuario no este tomado por otro usuario valga la redundancia, ignorando el usuario seleccionado
                    Rule::unique('users')->ignore($user->id)
                ]
            ]);

            //Si hay algún error de validación, enviamos una respuesta, en formato JSON
            if ($validate->fails()) {
                return response()->json([
                    "errors" => $validate->errors()
                ]);
            }

            //Si el usuario es válido, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta en formato JSON
            if (!$user->update($request->all())) {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible actualizar el usuario"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta en formato JSON
            else {
                return response()->json([
                    "status" => true,
                    "message" => "Usuario actualizado con éxito",
                    "usuario" => $user
                ], 201);
            }
        }
        //Si el usuario no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => false,
                "message" => "Usuario no encontrado"
            ], 404);
        }


    }

    public function destroy($id)
    {

        //Validamos el formato del id del usuario
        $validate = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);


        //Si hay algún error de validación, enviamos una respuesta, en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Buscamos el usuario mediante el id y generamos una colección
        $user = User::find($id);

        //Validamos si el id recibido, es un usuario válido
        if ($user) {
            //Si el usuario es válido, intentamos eliminar
            //Si algo falla en el proceso, enviamos una respuesta, en formato JSON
            if (!$user->delete()) {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible eliminar al usuario"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta, en formato JSON
            else {
                return response()->json([
                    "status" => true,
                    "message" => "Usuario eliminado con éxito",
                    "usuario" => $user
                ], 201);
            }
        }
        //Si el usuario no fue encontrado, enviamos una respuesta, en formato JSON
        else {
            return response()->json([
                "status" => false,
                "message" => "Usuario no encontrado"
            ], 404);
        }

    }


    public function validToken()
    {
        //Obtenemos la data del usuario, en formato JSON
        return response()->json([
            "status" => true,
            "message" => "Token validado con éxito",
            "usuario" => auth()->user()->usuario
        ], 201);
    }

    public function validUser($user)
    {

        //Validaciones
        $validateid = Validator::make(['usuario' => $user], [
            'usuario' => 'required|string|max:50|unique:users',
        ]);

        //Si existe algún error de validacion, enviamos una respuesta en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "status" => true,
                "errors" => $validateid->errors()
            ]);
        }

        //Usuario no tomado regresamos respuesta de exito con estatus en false
        return response()->json([
            "status" => false,
            "message" => "Usuario no tomado",
        ], 201);
    }

}
<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class ProviderController extends Controller
{


    public function index()
    {
        return response()->json([
            "status" => 1,
            "providers" => Provider::all()
        ], 201);
    }


    public function store(Request $request)
    {
        //Validaciones
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'rfc' => 'required|min:12|unique:providers'
        ]);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        // Si las validaciones son correctas, se da de alta el proveedor
        $provider = Provider::create($request->all());
        /* $provider = Provider::create([
        'nombre' => $request->nombre,
        'rfc' => $request->rfc,
        ]);*/

        //Posteriormente, enviamos una respuesta, en formato JSON de la alta exitosa del proveedor
        return response()->json([
            "status" => 1,
            "message" => "Alta de proveedor exitosa",
            "provider" => $provider->nombre
        ], 201);

    }
    public function show($id)
    {


        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);

        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos el proveedor mediante el id y generamos una colección
        $provider = Provider::find($id);

        //Validamos si el id recibido, es un proveedor válido
        if ($provider) {
            return response()->json([
                "status" => 1,
                "message" => "Datos encontrados con exito",
                "provider" => $provider
            ], 201);
        }
        //proveedor no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => 0,
                "message" => "proveedor no encontrado"
            ], 404);
        }

    }

    public function update(Request $request, $id)
    {

        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);

        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos el proveedor mediante el id y generamos una colección
        $provider = Provider::find($id);

        //Validamos si el id recibido, es un proveedor válido
        if ($provider) {

            //Validaciones
            $validate = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'rfc' => [
                    'required',
                    'max:50',
                    Rule::unique('providers')->ignore($provider->id)]
            ]);

            //Si hay algún error de validación, enviar en formato JSON
            if ($validate->fails()) {
                return response()->json([
                    "errors" => $validate->errors()
                ]);
            }

            //Si el proveedor es válido, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta
            if (!$provider->update($request->all())) {
                return response()->json([
                    "status" => 0,
                    "message" => "No fue posible actualizar al proveedor"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta
            else {
                return response()->json([
                    "status" => 1,
                    "message" => "Proveedor actualizado con éxito",
                    "provider" => $provider
                ], 201);
            }
        }
        //Si el proveedor no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => 0,
                "message" => "Proveedor no encontrado"
            ], 404);
        }


    }

    public function destroy($id)
    {

        $validate = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);


        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Buscamos el proveedor mediante el id y generamos una colección
        $provider = Provider::find($id);
        //Validamos si el id recibido, es un proveedor válido
        if ($provider) {
            //dd($user);
            //Si el proveedor es válido, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta
            if (!$provider->delete()) {
                return response()->json([
                    "status" => 0,
                    "message" => "No fue posible eliminar al proveedor"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta
            else {
                return response()->json([
                    "status" => 1,
                    "message" => "Proveedor eliminado con éxito",
                    "provider" => $provider
                ], 201);
            }
        }
        //Si el proveedor no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => 0,
                "message" => "Proveedor no encontrado"
            ], 404);
        }


    }
}
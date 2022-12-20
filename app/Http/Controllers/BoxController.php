<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class BoxController extends Controller
{
    public function index()
    {
        //Enviamos una respuesta de todos los productos registrados, en formato JSON
        return response()->json([
            "status" => true,
            "cajas" => box::all()
        ], 201);
    }


    public function store(Request $request)
    {
        //Validaciones
        $validate = Validator::make($request->all(), [
            'barcode' => [
                'required',
                'string',
                Rule::unique('boxes'),
                Rule::unique('products')
            ],
            //Validamos que exista el id del proveedor para el registro del producto
            'producto_id' => 'required|numeric|exists:products,id'
        ]);

        //Si hay algún error de validación, enviamos una respuesta, en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        // Si las validaciones son correctas, damos de alta la caja
        $box = Box::create($request->all());


        //Posteriormente, enviamos una respuesta, en formato JSON
        return response()->json([
            "status" => true,
            "message" => "Alta de caja exitosa",
            "caja" => $box->barcode
        ], 201);

    }

    public function update(Request $request, $id)
    {

        //Validamos el formato del id de la caja
        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);

        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos la caja mediante el id y generamos una colección
        $box = Box::find($id);

        //Validamos si el id recibido, es una caja registrada válida
        if ($box) {

            //Si la caja existe, validamos la información recibida en el body
            $validate = Validator::make($request->all(), [
                'barcode' => [
                    'required',
                    'string',
                    //Validamos que el barcode no este tomado por otro producto o caja, ignorando la caja seleccionada
                    Rule::unique('boxes')->ignore($box->barcode),
                    Rule::unique('products')->ignore($box->barcode)
                ],
                //Validamos que exista el id del producto para el registro de la caja
                'producto_id' => 'required|numeric|exists:products,id'

            ]);

            //Si hay algún error de validación, enviamos una respuesta, en formato JSON
            if ($validate->fails()) {
                return response()->json([
                    "errors" => $validate->errors()
                ]);
            }

            //Si la caja es válida, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta, en formato JSON
            if (!$box->update($request->all())) {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible actualizar la caja"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta, en formato JSON
            else {
                return response()->json([
                    "status" => true,
                    "message" => "Caja actualizado con éxito",
                    "caja" => $box
                ], 201);
            }
        }
        //Si la caja no fue encontrada, enviamos una respuesta, en formato JSON
        else {
            return response()->json([
                "status" => false,
                "message" => "Caja no encontrada"
            ], 404);
        }

    }



    public function show($id)
    {

        //Validamos el formato del id de la caja
        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);

        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos la caja mediante el id y generamos una colección
        $box = Box::find($id);

        //Validamos si existe alguna coincidencia de caja, con el id recibido
        if ($box) {
            //Si se obtuvo la información de la caja, enviamos una respuesta, en formato JSON
            return response()->json([
                "status" => true,
                "message" => "Datos encontrados con exito",
                "caja" => $box
            ], 201);
        }
        //Si la caja no fue encontrada, enviamos una respuesta, en formato JSON
        else {
            return response()->json([
                "status" => false,
                "message" => "La caja no fue encontrada"
            ], 404);
        }

    }


    public function destroy($id)
    {
        //Validamos el formato del id de la caja
        $validate = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);


        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Buscamos la caja es válida mediante el id y generamos una colección
        $box = Box::find($id);
        //Validamos si el id recibido, es una caja válida
        if ($box) {
            //Si la caja es válida, intentamos generar el delete
            //Si algo falla en el proceso, enviamos una respuesta, en formato JSON
            if (!$box->delete()) {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible eliminar la caja"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta, en formato JSON
            else {
                return response()->json([
                    "status" => true,
                    "message" => "La caja fue eliminada con éxito",
                    "caja" => $box
                ], 201);
            }
        }
        //Si la caja no fue encontrado, enviamos una respuesta, en formato JSON
        else {
            return response()->json([
                "status" => false,
                "message" => "La caja no fue encontrada"
            ], 404);
        }
    }
}
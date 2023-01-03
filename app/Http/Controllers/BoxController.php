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
            'id' => 'required|numeric|integer|exists:boxes,id'
        ]);

        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos la caja mediante el id y generamos una colección
        $box = Box::find($id);

            //Si la caja existe, validamos la información recibida en el body
            $validate = Validator::make($request->all(), [
                'barcode' => [
                    'required',
                    'string',
                    //Validamos que el barcode no este tomado por otro producto o caja, ignorando la caja seleccionada
                    Rule::unique('boxes')->ignore($box->id),
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
                    "message" => "Caja actualizada con éxito",
                    "caja" => $box
                ], 201);
            }

    }



    public function show($id)
    {

        //Validamos el formato del id de la caja
        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer|exists:boxes,id'
        ]);

        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos la caja mediante el id y generamos una colección
        $box = Box::find($id);

            //Si se obtuvo la información de la caja, enviamos una respuesta, en formato JSON
            return response()->json([
                "status" => true,
                "message" => "Datos encontrados con exito",
                "caja" => $box
            ], 201);


    }


    public function destroy($id)
    {
        //Validamos el formato del id de la caja
        $validate = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer|exists:boxes,id'
        ]);


        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Buscamos la caja es válida mediante el id y generamos una colección
        $box = Box::find($id);

        try {
            $box->delete();
            return response()->json([
                "status" => true,
                "message" => "Caja eliminada con éxito",
                "caja" => $box
            ], 201);
        } catch (\Exception $e) {
            if ($e->getCode() == "23000") {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible eliminar la caja, tiene productos registrados o movimientos generados"
                ], 404);
            }
        }
            
    }

    public function validBarcode($barcode){

        //Validamos el formato del barcode del producto
        $validateid = Validator::make(['barcode' => $barcode], [
            'barcode' => [
                'required',
                'string',
                Rule::unique('boxes'),
                Rule::unique('products')
            ],
        ]);

        //Si hubo algún error de validación, enviamos una respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "status" => true,
                "errors" => $validateid->errors()
            ]);
        }

        //Usuario no tomado regresamos respuesta de exito con estatus en false, en formato JSON
            return response()->json([
                "status" => false,
                "message" => "Barcode no tomado",
            ], 201);
    }

    public function validBarcodeUpdate($barcode, $id){

        //Buscamos la caja mediante el id y generamos una colección
        $box = Box::find($id);

            //Si la caja existe, validamos el barcode
            $validate = Validator::make(['barcode' => $barcode], [
                'barcode' => [
                    'required',
                    'string',
                    //Validamos que el barcode no este tomado por otro producto o caja, ignorando la caja seleccionada
                    Rule::unique('boxes')->ignore($id),
                    Rule::unique('products')->ignore($box->barcode)
                ],
            ]);

        //Si hubo algún error de validación, enviamos una respuesta, en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "status" => true,
                "errors" => $validate->errors()
            ]);
        }

        //Usuario no tomado regresamos respuesta de exito con estatus en false, en formato JSON
            return response()->json([
                "status" => false,
                "message" => "Barcode no tomado",
            ], 201);
    }

    public function showBarcode($barcode)
    {

        //Validamos el formato del barcode de la caja
        $validateid = Validator::make(['barcode' => $barcode], [
            'barcode' => 'required|string|exists:boxes,barcode'
        ]);

        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "status" => false,
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos la caja mediante el barcode y generamos una colección
        $box = Box::where('barcode', $barcode)->first();

            //Si se obtuvo la información de la caja, enviamos una respuesta, en formato JSON
            return response()->json([
                "status" => true,
                "message" => "Datos encontrados con exito",
                "caja" => $box
            ], 201);


    }

}

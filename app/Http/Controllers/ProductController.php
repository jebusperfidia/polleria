<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json([
            "status" => true,
            "products" => Product::all()
        ], 201);
    }


    public function store(Request $request)
    {
        //Validaciones
        $validate = Validator::make($request->all(), [
            'barcode' => 'required|string|unique:products',
            'nombre' => 'required|string|max:100',
            'tipo' => 'required|numeric',
            'stock' => 'required|numeric',
            'proveedor_id' => 'required|exists:providers,id'            
        ]);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        // Si las validaciones son correctas, se da de alta el producto
        $product = Product::create($request->all());
        /* $provider = Provider::create([
        'nombre' => $request->nombre,
        'rfc' => $request->rfc,
        ]);*/

        //Posteriormente, enviamos una respuesta, en formato JSON de la alta exitosa del producto
        return response()->json([
            "status" => true,
            "message" => "Alta de producto exitosa",
            "product" => $product->nombre
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

        //Buscamos el producto mediante el id y generamos una colección
        $product = Product::find($id);

        //Validamos si el id recibido, es un producto válido
        if ($product) {
            return response()->json([
                "status" => true,
                "message" => "Datos encontrados con exito",
                "product" => $product
            ], 201);
        }
        //Producto no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => false,
                "message" => "Producto no encontrado"
            ], 404);
        }

    }

    

    public function update(Request $request, $id)
    {

        //Valiodamos el id del producto
        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);

        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos el producto mediante el id y generamos una colección
        $product = Product::find($id);

        //Validamos si el id recibido, es un producto válido
        if ($product) {

            //Si el producto existe, validamos la información recibida en el body
            $validate = Validator::make($request->all(), [
                'barcode' => [
                    'required','string',
                    //Validamos que el barcode no este tomado por otro producto, ignorando el producto seleccionado
                    Rule::unique('products')->ignore($product->id)
                ],
                'nombre' => 'required|string|max:100',
                'tipo' => 'required|numeric',
                'stock' => 'required|numeric',
                'producto_id' => 'required|exists:providers,id'

            ]);

            //Si hay algún error de validación, enviar en formato JSON
            if ($validate->fails()) {
                return response()->json([
                    "errors" => $validate->errors()
                ]);
            }

            //Si el producto es válido, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta
            if (!$product->update($request->all())) {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible actualizar el producto"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta
            else {
                return response()->json([
                    "status" => true,
                    "message" => "Producto actualizado con éxito",
                    "producto" => $product
                ], 201);
            }
        }
        //Si el producto no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => false,
                "message" => "Producto no encontrado"
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

        //Buscamos el producto mediante el id y generamos una colección
        $product = Product::find($id);
        //Validamos si el id recibido, es un producto válido
        if ($product) {
            //dd($user);
            //Si el producto es válido, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta
            if (!$product->delete()) {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible eliminar al producto"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta
            else {
                return response()->json([
                    "status" => true,
                    "message" => "producto eliminado con éxito",
                    "product" => $product
                ], 201);
            }
        }
        //Si el producto no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => false,
                "message" => "producto no encontrado"
            ], 404);
        }


    }

    public function validBarcode($barcode){


        $validateid = Validator::make(['barcode' => $barcode], [
            'barcode' => 'required|string|unique:products',
        ]);

        if ($validateid->fails()) {
            return response()->json([
                "status" => true,
                "errors" => $validateid->errors()
            ]);
        }

        //Usuario no tomado regresamos respuesta de exito con estatus en false
            return response()->json([
                "status" => false,
                "message" => "Barcode no tomado",
            ], 201);
    }


}

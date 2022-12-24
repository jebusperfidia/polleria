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
        //Enviamos una respuesta de todos los productos registrados, en formato JSON
        return response()->json([
            "status" => true,
            "products" => Product::all()
        ], 201);
    }


    public function store(Request $request)
    {
        //Validaciones
        $validate = Validator::make($request->all(), [
            'barcode' => 
            [
                'required',
                'string',
                //Validamos que el barcode no esté tomado por algún otro producto o caja, ya que debe ser un campo único
                Rule::unique('products'),
                Rule::unique('boxes')
            ],
            'nombre' => 'required|string|max:100',
            'costo_kilo' => 'required|numeric',
            //Validamos que exista el id del proveedor para el registro del producto
            'proveedor_id' => 'required|numeric|exists:providers,id'
        ]);

        //Si hay algún error de validación, enviamos una respuesta, en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        // Si las validaciones son correctas, damos de alta el producto
        $product = Product::create($request->all());
        /* $provider = Provider::create([
        'nombre' => $request->nombre,
        'rfc' => $request->rfc,
        ]);*/

        //Posteriormente, enviamos una respuesta, en formato JSON
        return response()->json([
            "status" => true,
            "message" => "Alta de producto exitosa",
            "product" => $product->nombre
        ], 201);

    }

    public function show($id)
    {
        //Validamos el formato del id del producto
        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer|exists:products,id'
        ]);


        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos el producto mediante el id y generamos una colección
        $product = Product::find($id);

        //Si se obtuvo la información del producto, enviamos una respuesta, en formato JSON
        return response()->json([
            "status" => true,
            "message" => "Datos encontrados con exito",
            "product" => $product
        ], 201);
    }

    

    public function update(Request $request, $id)
    {

        //Validamos el formato del id del producto
        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer|exists:products,id'
        ]);

        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos el producto mediante el id y generamos una colección
        $product = Product::find($id);

            //Si el producto existe, validamos la información recibida en el body
            $validate = Validator::make($request->all(), [
                'barcode' => [
                    'required','string',
                    //Validamos que el barcode no este tomado por otro producto, ignorando el producto seleccionado
                    Rule::unique('products')->ignore($product->id),
                    Rule::unique('boxes')->ignore($product->id)
                ],
                'nombre' => 'required|string|max:100'

            ]);

            //Si hay algún error de validación enviamos una respuesta, en formato JSON
            if ($validate->fails()) {
                return response()->json([
                    "errors" => $validate->errors()
                ]);
            }

            //Si el producto es válido, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta, en formato JSON
            if (!$product->update($request->all())) {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible actualizar el producto"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta, en formato JSON
            else {
                return response()->json([
                    "status" => true,
                    "message" => "Producto actualizado con éxito",
                    "producto" => $product
                ], 201);
            }
   

    }


    public function destroy($id)
    {
        //Validamos el formato del id del producto
        $validate = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer|exists:products,id'
        ]);


        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Buscamos el producto mediante el id y generamos una colección
        $product = Product::find($id);
            //Si el producto es válido, intentamos generar el update
            //Si algo falla en el proceso, enviamos una respuesta, en formato JSON
            if (!$product->delete()) {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible eliminar al producto"
                ], 404);
            }
            //Si el update tuvo éxito, enviamos una respuesta, en formato JSON
            else {
                return response()->json([
                    "status" => true,
                    "message" => "producto eliminado con éxito",
                    "product" => $product
                ], 201);
            }
    

    }

    public function validBarcode($barcode){

        //Validamos el formato del id del producto
        $validateid = Validator::make(['barcode' => $barcode], [
            'barcode' => 'required|string|unique:products',
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


}

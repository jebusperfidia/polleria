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

        //Generamos una variable global para poder hacer la validación personalizada
        global $idPr;
        //Almacenamos el valor del id del producto, en la variable global
        $idPr = $request->proveedor_id;


        //Generamos las reglas para validar todos los valores enviados en el body
        $rules = [
            'barcode' => 'required|string|unique:products',
            'nombre' => 'required|string|max:100',
            'costo_kilo' => 'required|numeric',
            'proveedor_id' => 'required|numeric|exists:providers,id',
            'codigo_proveedor' => [
                'required',
                //Mediante una validación personalizada, verificamos si existe algún producto, del mismo proveedor
                //que tenga el mismo código de proveedor, para evitar duplicados
                function ($attribute, $value, $fail) {
                    $product = Product::where('codigo_proveedor', $value)->where('proveedor_id', $GLOBALS['idPr'])->first();
                    //Si se encuentra alguna coincidencia, enviamos un error
                    if ($product) {
                        return $fail('El código del proveedor ya está en uso');
                    }
                }

            ],
        ];

        //Hacemos las validaciones con los datos obtenidos desde el objeto request, y las reglas generadas anteriormente
        $validate = Validator::make($request->all(), $rules);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        // Si las validaciones son correctas, damos de alta el producto
        $product = Product::create($request->all());

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

        global $idPr, $idP;
        $idPr = $request->proveedor_id;
        $idP = (int )$id;
        //dd($idP);


        //Validamos la información recibida en el body
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'proveedor_id' => 'required|numeric|exists:providers,id',
            'barcode' => [
                'required',
                'string',
                //Validamos que el barcode no este tomado por otro producto, ignorando el producto seleccionado
                Rule::unique('products')->ignore($product->id),
            ],
            'codigo_proveedor' => [
                'required',
                //Mediante una validación personalizada, verificamos si existe algún producto con el id del
                //proveedor, así como el código del proveedor
                function ($attribute, $value, $fail) {
                    $productV = Product::where('codigo_proveedor', $value)->where('proveedor_id', $GLOBALS['idPr'])->first();
                    if ($productV) {
                        //Si el producto a actualizar, tiene el mismo código de proveedor de otro producto
                        //Enviamos un mensaje de error. Este error solo aplica con un producto diferente
                        //Del mismo proveedor
                        if($productV->id !== $GLOBALS['idP']) {
                            return $fail('El código del proveedor ya está en uso');
                        }
                    }
                }

            ],
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

        try {
            $product->delete();

            return response()->json([
                "status" => true,
                "message" => "Producto eliminado con éxito",
                "producto" => $product
            ], 201);
        } catch (\Exception $e) {
            if ($e->getCode() == "23000") {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible eliminar el producto, tiene movimientos generados"
                ], 404);
            }
        }

    }

    public function validBarcode($barcode)
    {

        //Validamos el formato del id del producto
        $validate = Validator::make(['barcode' => $barcode], [
            'barcode' => 'required|string|unique:products',
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

    public function validBarcodeUpdate($barcode, $id)
    {

        //Buscamos el producto mediante el id y generamos una colección
        $product = Product::find($id);

        //Si el producto existe, validamos el barcode
        $validate = Validator::make(['barcode' => $barcode], [
            'barcode' => [
                'required',
                'string',
                //Validamos que el barcode no este tomado por otro producto, ignorando el producto seleccionado
                Rule::unique('products')->ignore($product->id),
                Rule::unique('boxes')->ignore($product->barcode)
            ]
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
        //Validamos el formato del barcode del producto
        $validateid = Validator::make(['barcode' => $barcode], [
            'barcode' => 'required|string'
        ]);


        //Si hubo algún error de validación enviamos un respuesta, en formato JSON
        if ($validateid->fails()) {
            return response()->json([
                "status" => false,
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos el producto mediante el barcode y generamos una colección
        $product = Product::where('barcode', $barcode)->first();


        //Si no se encontró el producto mediante el barcode, ingresamos al if
        //Desde el cual validaremos el formato del barcode
        if (!$product) {
            
            //Mediante un Switch Case, dependiendo el largo del barcode
            //Ingresaremos a cada case, referente a uno o más proveedores
            switch (strlen($barcode)) {

                case 20: //Nutry o St Clara

                    //Variables relativas a Nutry Pollo

                    //Obtenemos los kilos del barcode
                    $kNP = substr($barcode, 4, -14) . '.' . substr($barcode, 6, -12);
                    //Convertimos el valor a float
                    $kilosNP = floatval($kNP);
                    //Obtenemos el código del proveedor, del barcode
                    $cPNP = substr($barcode, 0, -17);
                    
                    
                    //Obtenemos los kilos del barcode
                    $kSC = substr($barcode, 3, -15) . '.' . substr($barcode, 5, -13);
                    //Convertimos el valor a float
                    $kilosSC = floatval($kSC);
                    //Obtenemos el código del proveedor, del barcode
                    $cPSC = substr($barcode, 1, -17);
                    

                    //Primero verificamos si el código del proveedor, pertenece a un producto de St Clara
                    $product = Product::where('codigo_proveedor', $cPSC)->first();
                    
                    //Si no se obtuvo ningún resultado, ahora verificamos el código del proveedor de Nutry Pollo
                    if(!$product) {
                        $product = Product::where('codigo_proveedor', $cPNP)->first();
                         
                        //Si no se encontró ninguna coincidencia de ambos proveedores, enviamos una respuesta
                        if(!$product){
                            return response()->json([
                            "status" => false,
                            "message" => "Producto no encontrado",
                            ], 201);
                        } 
                            //Si se encontró un producto de Nutry pollo, enviamos una respuesta en formato JSON
                            return response()->json([
                            "status" => true,
                            "message" => "Datos encontrados con exito",
                            "type" => "product",
                            "data" => $product,
                            "kilos_caja" => $kilosNP
                        ], 201);
                        
                    } else {

                        //Si se encontró un producto de St Clara, enviamos una respuesta en formato JSON
                          return response()->json([
                            "status" => true,
                            "message" => "Datos encontrados con exito",
                            "type" => "product",
                            "data" => $product,
                            "kilos_caja" => $kilosSC
                        ], 201);
                    }


                    break;



                case 21: //Sabro Pollo

                    //Obtenemos los kilos del barcode
                    $k = substr($barcode, 10, -9) . '.' . substr($barcode, 12, -7);
                    //Convertimos el valor a float
                    $kilos = floatval($k);
                     //Obtenemos el código del proveedor, del barcode
                    $cP = substr($barcode, 7, -12);

                    //Primero verificamos si el código del proveedor, pertenece a un producto de Sabro Pollo
                    $product = Product::where('codigo_proveedor', $cP)->first();

                     //Si no se encontró ninguna coincidencia, enviamos una respuesta
                    if (!$product) {
                        return response()->json([
                            "status" => false,
                            "message" => "Producto no encontrado",
                        ], 201);
                    } 
                    
                    //Si se encontró un producto de Sabro Pollo, enviamos una respuesta en formato JSON
                    else {
                        return response()->json([
                            "status" => true,
                            "message" => "Datos encontrados con exito",
                            "type" => "product",
                            "data" => $product,
                            "kilos_caja" => $kilos
                        ], 201);
                    }

                    break;



                case 15: //Grupo pecuario

                    //Obtenemos los kilos del barcode
                    $k = substr($barcode, 10, -3) . '.' . substr($barcode, 12, -1);
                    //Convertimos el valor a float
                    $kilos = floatval($k);
                        
                    //Enviamos el total de kilos, así como el proveedor al que pertenece
                    return response()->json([
                        "status" => true,
                        "provider" => "Grupo Pecuario"
                        "type" => "product",
                        "kilos" => $kilos
                    ], 201);
                    

                    break;


                case 25: //Bachoco

                    //Obtenemos los kilos del barcode
                    $k = substr($barcode, 12, -11) . '.' . substr($barcode, 14, -9);
                    //Convertimos el valor a float
                    $kilos = floatval($k);
                    //Obtenemos el código del proveedor, del barcode
                    $cP = substr($barcode, 5, -19);

                    //Primero verificamos si el código del proveedor, pertenece a un producto de Bachoco
                    $product = Product::where('codigo_proveedor', $cP)->first();

                    //Si no se encontró ninguna coincidencia, enviamos una respuesta
                    if (!$product) {
                        return response()->json([
                            "status" => false,
                            "message" => "Producto no encontrado",
                        ], 201);
                    } 
                    
                    //Si se encontró un producto de Bachoco, enviamos una respuesta en formato JSON
                    else {
                        return response()->json([
                            "status" => true,
                            "message" => "Datos encontrados con exito",
                            "type" => "product",
                            "data" => $product,
                            "kilos_caja" => $kilos
                        ], 201);
                    }

                    //echo 'Soy bachoco';

                    break;
                
                    //Si no se encontró ninguna coincidencia en el largo del barcode, enviamos una respuesta, en formato JSON
                    default:
                    
                        return response()->json([
                            "status" => false,
                            "message" => "El código no coincide con ningún producto",
                        ], 201);

            }

        } else {

            //Si se obtuvo la información del producto, enviamos una respuesta, en formato JSON
            return response()->json([
                "status" => true,
                "message" => "Datos encontrados con exito",
                'type' => "barcode",
                "data" => $product
            ], 201);
        }

    }


    public function search($search)
    {
        $products = Product::where('nombre', 'LIKE', "%{$search}%")->get();
        return response()->json($products, 201);
    }
}
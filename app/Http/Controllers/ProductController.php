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

        //dd($request->proveedor_id);
        global $idPr;
        $idPr = $request->proveedor_id;
        //dd($idPr);

          //Generamos las reglas para validar todos los campos dentro del objeto details
        $rules = [
            'barcode' => 'required|string|unique:products',
            'nombre' => 'required|string|max:100',
            'costo_kilo' => 'required|numeric',
            'proveedor_id' => 'required|numeric|exists:providers,id',
            'codigo_proveedor' => [
                'required',
                function ($attribute, $value, $fail) {
                    $product = Product::where('codigo_proveedor', $value)->where('proveedor_id', $GLOBALS['idPr'])->first();
                    //dd($product);
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
                function ($attribute, $value, $fail) {
                    $productV = Product::where('codigo_proveedor', $value)->where('proveedor_id', $GLOBALS['idPr'])->first();
                    if ($productV) {
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

        if (!$product) {
            
            switch (strlen($barcode)) {


                case 20: //Nutry o St Clara

                    //Variables relativas a Nutry Pollo
                    $kNP = substr($barcode, 4, -14) . '.' . substr($barcode, 6, -12);
                    $kilosNP = floatval($kNP);
                    $cPNP = substr($barcode, 0, -17);
                    
                    
                    //Variables relativas a St Clara
                    $kSC = substr($barcode, 3, -15) . '.' . substr($barcode, 5, -13);
                    $kilosSC = floatval($kSC);
                    $cPSC = substr($barcode, 1, -17);
                    
                    //dd($kilosSC, $cPSC);


                    $product = Product::where('codigo_proveedor', $cPSC)->first();
                    
                    if(!$product) {
                        $product = Product::where('codigo_proveedor', $cPNP)->first();
                         
                        //No se encontró ningún producto que coincida
                        if(!$product){
                            return response()->json([
                            "status" => false,
                            "message" => "Producto no encontrado",
                            ], 201);
                        } 
                            //Se encontró un producto de Nutry pollo
                            return response()->json([
                            "status" => true,
                            "message" => "Datos encontrados con exito",
                            "type" => "product",
                            "data" => $product,
                            "kilos_caja" => $kilosNP
                        ], 201);
                        
                    } else {

                        //Se encontró un producto de Rastro Santa Clara
                          return response()->json([
                            "status" => true,
                            "message" => "Datos encontrados con exito",
                            "type" => "product",
                            "data" => $product,
                            "kilos_caja" => $kilosSC
                        ], 201);
                    }

                    echo 'Soy Nutry o Rancho St Clara';

                    break;



                case 21: //Sabro Pollo

                    $k = substr($barcode, 10, -9) . '.' . substr($barcode, 12, -7);
                    $kilos = floatval($k);

                    $cP = substr($barcode, 7, -12);

                    $product = Product::where('codigo_proveedor', $cP)->first();

                    if (!$product) {
                        return response()->json([
                            "status" => false,
                            "message" => "Producto no encontrado",
                        ], 201);
                    } 
                    
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

                    $k = substr($barcode, 10, -3) . '.' . substr($barcode, 12, -1);
                    $kilos = floatval($k);

                        return response()->json([
                            "status" => true,
                            "type" => "product",
                            "kilos" => $kilos
                        ], 201);
                    

                    break;



                case 25: //Bachoco


                    $k = substr($barcode, 12, -11) . '.' . substr($barcode, 14, -9);
                    $kilos = floatval($k);

                    $cP = substr($barcode, 5, -19);

                    //dd($kilos, $cP);

                    $product = Product::where('codigo_proveedor', $cP)->first();

                    if (!$product) {
                        return response()->json([
                            "status" => false,
                            "message" => "Producto no encontrado",
                        ], 201);
                    } 
                    
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

                default:
                    
                        return response()->json([
                            "status" => false,
                            "message" => "El código no coincide con ningún producto",
                        ], 201);
                     
                //echo 'No se encontró código de barras';

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


        //dd($product);

    }

    public function search($search)
    {
        $products = Product::where('nombre', 'LIKE', "%{$search}%")->get();
        return response()->json($products, 201);
    }
}
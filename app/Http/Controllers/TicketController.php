<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Box;
use App\Models\Ticket;
use App\Models\TicketDetail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class TicketController extends Controller
{


    public function index()
    {
        return response()->json([
            "status" => true,
            "message" => 'Datos obtenidos con éxito',
            "ticket" => Ticket::all()
        ], 201);
    }


    public function store(Request $request)
    {

        //Validamos que se enviaron datos
        if (!$request->all()) {
            return response()->json([
                "status" => false,
                "message" => "No se enviaron datos"
            ], 404);
        }

        //Generamos las reglas para validar todos los campos dentro del objeto details
        $rules = [
            'tipo' => 'required|numeric',
            'total' => 'required|numeric',
            'details.*.barcode' => [
                'required',
                //validamos que exista el barcode, verificando que se cuente con al menos un registro en la tabla de productos o cajas
                function ($attribute, $value, $fail) {
                    $barcodeProduct = Product::where('barcode', $value)->first();
                    $barcodeBox = Box::where('barcode', $value)->first();
                    if (!($barcodeProduct) && !($barcodeBox)) {
                        return $fail($attribute . ' not exist.');
                    }
                }
            ],
            'details.*.product_id' => 'required|numeric|exists:products,id',
            'details.*.box_id' => 'numeric|exists:boxes,id',
            'details.*.kilos' => 'required|numeric',
            'details.*.costo_kilo' => 'required|numeric',
            'details.*.subtotal' => 'required|numeric',
            'details.*.total_cajas' => 'required|numeric',
            'details.*.total_tapas' => 'required|numeric'
        ];


        //Hacemos las validaciones con los datos obtenidos desde el objeto request, y las reglas generadas anteriormente
        $validate = Validator::make($request->all(), $rules);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }




        //Generamos una variable para obtener el total de kilos por producto, mismo que usaremos para la validación de stock, las entradas y salidas
        $stockKilos = [];
        //Generamos un conjunto de arreglos para sumar el total de kilos por producto
        foreach ($request->details as $detail) {
            foreach ($detail as $key => $value) {
                if ($key === 'product_id') {
                    if (array_key_exists($value, $stockKilos)) {
                        $stockKilos[$value]['kilos'] = $stockKilos[$value]['kilos'] + $detail['kilos'];
                    } else if ($key == 'product_id') {
                        $stockKilos[$value] = $detail;
                    }
                }
            }
        }

        //Refactorizamos la variable de $stockKilos para poder iterarlo
        $sk = [];
        foreach ($stockKilos as $stockKilo) {
            array_push($sk, $stockKilo);
        }


        //Verificamos si el tipo de ticket, es una salida de inventario
        if ($request->tipo === 2) {
            //Validamos que existe stock suficiente del producto en las salidas
            //dd($sk);
            //Generamos una variable para almacenar los posibles problemas de stock
            $stocksValidation = [];

            //Validamos la cantidad de kilos
            foreach ($sk as $key => $value) {
                
                //Obtenemos el producto, a partir del código de barras, para obtener el total de kilos a comparar
                $product = Product::where('barcode', $value['barcode'])->first();
                //Primero validamos si hay stock en el inventario
                if ($product->stock_kilos === 0.0) {
                    array_push(
                        $stocksValidation,
                        array(
                            "product_id" => $product->id,
                            "barcode" => $product->barcode,
                            "product" => $product->nombre,
                            "message" => "El stock de este producto es 0",
                            "faltante" => $value['kilos'] - $product->stock_kilos . ' kilos'
                        )
                    );
                }
                //Si el stock es mayor que 0, validamos que se cuente con stock suficiente del producto
                else if ($product->stock_kilos < $value['kilos']) {
                    //dd($product->stock_kilos);
                    //dd($product->stock, $value['kilos']);
                    array_push(
                        $stocksValidation,
                        array(
                            "product_id" => $product->id,
                            "barcode" => $product->barcode,
                            "product" => $product->nombre,
                            "message" => "No hay stock suficiente",
                            "faltante" =>  $value['kilos'] - $product->stock_kilos  . ' de ' . $value['kilos'] . ' kilos'
                        )
                    );
                }
            }

            //Lo siguiente consiste en validar si se cuenta con cajas suficientes para el ticket a generar
            foreach ($request->details as $detail) {
                $box = Box::where('barcode', $detail['barcode'])->first();
                if ($box) {
                    //Primero validamos que el stock no sea igual a 0
                    if ($box->stock_cajas === 0) {
                        array_push(
                            $stocksValidation,
                            array(
                                "box_id" => $box->id,
                                "barcode" => $box->barcode,
                                "message" => "El stock de cajas es de 0",
                                "faltante" => $detail['total_cajas'] - $box->stock_cajas
                            )
                        );
                    }

                    //Si el stock es mayor que 0, validamos que se cuente con stock suficiente para la petición
                    else if ($box->stock_cajas < $detail['total_cajas']) {
                        //dd($box->stock_cajas, $detail['total_cajas']);
                        array_push(
                            $stocksValidation,
                            array(
                                "box_id" => $box->id,
                                "barcode" => $box->barcode,
                                "message" => "No hay stock de cajas suficiente",
                                "faltante" => $detail['total_cajas'] - $box->stock_cajas
                            )
                        );
                    }

                    //Finalmente, validaremos que se cuente con las tapas suficientes en el inventario
                    //Primero validamos que el stock no sea igual a 0
                    if ($box->stock_tapas === 0) {
                        //return 'no hay cajas mano';
                        array_push(
                            $stocksValidation,
                            array(
                                "box_id" => $box->id,
                                "barcode" => $box->barcode,
                                "message" => "El stock de tapas es de 0",
                                "faltante" => $detail['total_tapas'] - $product->stock_tapas
                            )
                        );
                    }

                    //Si el stock es mayor que 0, validamos que se cuente con stock suficiente para la petición
                    else if ($box->stock_tapas < $detail['total_tapas']) {
                        array_push(
                            $stocksValidation,
                            array(
                                "box_id" => $box->id,
                                "barcode" => $box->barcode,
                                "message" => "No hay tapas suficiente",
                                "faltante" => $detail['total_tapas'] - $box->stock_tapas
                            )
                        );
                    }
                }

            }

            //Si existió algún problema con el stock de alguno de los productos, enviamos una respuesta en formato JSON
            if (count($stocksValidation) > 0) {
                return response()->json([
                    "status" => false,
                    "errors" => $stocksValidation
                ], 404);
            }

        }

        //Finalizamos la validación de datos

        //Una vez obtenidos los datos, primero damos de alta el ticket
        $ticket = Ticket::create([
            'tipo' => $request->tipo,
            'total' => $request->total,
            'usuario_id' => auth()->user()->id
        ]);


        //Si el ticket fue dado de alta, lo validamos y generamos los detalles del ticket
        if ($ticket) {
        
        //Validamos el tipo de ticket para verificar si es un ticket de entrada o salida de almacen
        
        //Si es una entrada de almacen, realizaremos el siguiente procedimiento
        if($request->tipo === 1) {
            //Utilizamos el arreglo con el total de kilos por producto, para sumarlos al stock actual    
            foreach ($sk as $key => $value) {
                    //Generamos una colección, usando el barcode del producto
                    $product = Product::where('barcode', $value['barcode'])->first();
                    //Sumamos los kilos recibidos en el ticket, al stock actual de la colección generada
                    $product->stock_kilos = $product->stock_kilos + $value['kilos'];
                    //Finalmente, actualizamos la colección, para reflejar los cambios en el registro de la base de datos
                    $product->save();
                }
                
                //Mediante un foreach, recorreremos los datos recibidos desde la petición para registrar los detalles del ticket
                //Aprovecharemos esta iteración de elementos, para actualzar los datos del stock de las cajas, de los barcode que sean pertenecientes a una caja
                foreach($request->details as $detail) {
                    //Lo primero será intentar generar una colección con el barcode recibido
                    $box = Box::where('barcode', $detail['barcode'])->first();
                    //Si el barcode, pertenece a una caja, actualizaremos los datos del stock de cajas y tapas
                    if($box){
                        //Actualizamos los datos del stock, de la colección generada, a partir de los datos recibidos en la petición
                        $box->stock_cajas = $box->stock_cajas + $detail['total_cajas'];
                        $box->stock_tapas = $box->stock_tapas + $detail['total_tapas'];
                        //Finalmente, actualizamos la colección, para reflejar los cambios en el registro de la base de datos
                        $box->save();
                    }

                    //Ahora, generaremos los datos necesarios para enviar los datos de los detalles del ticket
                    $detail['ticket_id'] = $ticket->id;
                    $detail['created_at'] = now();
                    $detail['updated_at'] = now();
                    //Insertamos el valor en la base de datos, en la tabla de ticketDetails
                    TicketDetail::insert($detail);

                }

        }
        else if ($request->tipo === 2) {
            foreach ($sk as $key => $value) {
                $product = Product::where('barcode', $value['barcode'])->first();
                //dd('acá no hay error');
                    $product->stock_kilos = $product->stock_kilos - $value['kilos'];
                    $product->save();
                }

                foreach ($request->details as $detail) {
                    $box = Box::where('barcode', $detail['barcode'])->first();
                    if ($box) {
                        $box->stock_cajas = $box->stock_cajas - $detail['total_cajas'];
                        $box->stock_tapas = $box->stock_tapas - $detail['total_tapas'];
                        $box->save();
                    }

                    //Ahora, generaremos los datos necesarios para enviar los datos de los detalles del ticket
                    $detail['ticket_id'] = $ticket->id;
                    $detail['created_at'] = now();
                    $detail['updated_at'] = now();
                    //Insertamos el valor en la base de datos, en la tabla de ticketDetails
                    TicketDetail::insert($detail);
                }
                //dd("hasta aquí");
        }

            //Obtenemos los datos detalles del ticket, registrados en la base de datos, a partir del id del ticket
            //Validando que los datos están registrados en la base de datos
            $getTicketDetails = TicketDetail::where("ticket_id", "=", $ticket->id)->get();

            //Enviamos una respuesta con los datos obtenidos, confirmando que fueron registrados de manera correcta
            return response()->json([
                "status" => true,
                "message" => "Datos registrados correctamente",
                "type" => $ticket->tipo === 1 ? 'Entrada' : 'Salida',
                "ticket_id" => $ticket->id,
                "usuario" => auth()->user(),
                'details' => json_decode($getTicketDetails)
            ], 201);

            //Si algo falló al dar de alta el ticket, enviar una respuesta en formato JSON   
        } else {
            return response()->json([
                "status" => false,
                "message" => "No se pudieron registrar los datos"
            ], 404);
        }

    }

    public function show($id)
    {

        //Validamos el id enviado desde la URL
        $validateid = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);

        if ($validateid->fails()) {
            return response()->json([
                "errors" => $validateid->errors()
            ]);
        }

        //Buscamos el Ticket mediante el id y generamos una colección
        $ticket = Ticket::find($id);

        //Obtenemos la información de detalles del ticket
        $getTicketDetails = TicketDetail::where("ticket_id", "=", $ticket->id)->get();


        //Validamos si el id recibido, es un Ticket válido
        if ($ticket) {
            return response()->json([
                "status" => true,
                "message" => "Datos encontrados con exito",
                "ticket" => $ticket,
                "details" => $getTicketDetails
            ], 201);
        }
        //Ticket no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => false,
                "message" => "Producto no encontrado"
            ], 404);
        }

    }

    public function detect($barcode)
    {
        //Validamos que el barcode tenga un formato válido
        $validate = Validator::make(['barcode' => $barcode], [
            'barcode' => 'required'
        ]);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Buscamos el Ticket mediante el id y generamos una colección
        $product = Product::where('barcode', $barcode)->first();


        //Validamos si el id recibido, es un Ticket válido
        if ($product) {
            return response()->json([
                "status" => true,
                "message" => "Datos encontrados con exito",
                "ticket" => $product,
            ], 201);
        }
        //Ticket no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => false,
                "message" => "Producto no encontrado"
            ], 404);
        }

    }





    public function destroy($id)
    {

        //Validamos el formato del id
        $validate = Validator::make(['id' => $id], [
            'id' => 'required|numeric|integer'
        ]);


        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Buscamos el Ticket mediante el id y generamos una colección
        $ticket = Ticket::find($id);

        //Validamos si el id recibido, es un Ticket válido
        if ($ticket) {
            //Obtenemos los detalles del ticket
            $getTicketDetails = TicketDetail::where("ticket_id", "=", $ticket->id)->get();

            //Generamos una variable para almacenar los posibles problemas de stock
            $stocksValidation = [];

            //Si el ticket fue una entrada de almacen, validamos si al borrarlo, y eliminar los productos
            //datos de alta, no hay algún problema de stock
            if ($ticket->tipo === 1) {
                foreach ($getTicketDetails as $detail) {
                    $product = Product::find($detail['producto_id']);

                    //Ahora validamos que se cuente con el stock suficiente
                    if ($product->stock < $detail['cantidad']) {
                        array_push(
                            $stocksValidation,
                            array(
                                "id" => $detail['producto_id'],
                                "product" => $product->nombre,
                                "message" => "No hay stock suficiente",
                                "faltante" => $detail['cantidad'] - $product->stock
                            )
                        );
                    }
                }

                //Si existió algún problema con el stock de alguno de los productos, enviamos una respuesta en formato JSON
                if (count($stocksValidation) > 0) {
                    return response()->json([
                        "errors" => $stocksValidation
                    ]);
                }

            }

            //Una vez finalizamos las validaciones, devolvemos o eliminamos los productos del stock, del ticket en cuestión
            foreach ($getTicketDetails as $detail) {

                $product = Product::find($detail['producto_id']);

                //Mediante un if, validamos el tipo de ticket y actualizamos los datos del stock del producto
                if ($ticket->tipo === 1) {
                    $product->stock = $product->stock - $detail['cantidad'];
                    $product->save();
                } else {
                    $product->stock = $product->stock + $detail['cantidad'];
                    $product->save();
                }

                //Eliminamos el registro de la tabla detalle tickets
                TicketDetail::find($detail['id'])->delete();
            }

            //Una vez devueltos los valores del stock, eliminamos el ticket en cuestión
            $ticketDelete = $ticket->delete();

            //Si todo fue correcto, enviamos una respuesta, en formato JSON
            if ($ticketDelete) {
                return response()->json([
                    "status" => true,
                    "message" => "Ticket Eliminado con éxito",
                ], 201);
                //Si no fue posible eliminar el ticket, enviamos una respuesta, en formato JSON
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "No fue posible eliminar el ticket"
                ], 404);
            }

        }
        //Si el ticket no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => false,
                "message" => "ticket no encontrado"
            ], 404);
        }
    }

}
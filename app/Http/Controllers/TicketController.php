<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Box;
use App\Models\Ticket;
use App\Models\TicketDetail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Response;
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
            'details.*.product_id' => 'required|numeric|exists:products,id',
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


        //dd($request->details);

        //Verificamos si el tipo de ticket, es una salida de inventario
        if ($request->tipo === 2) {
            //Validamos que existe stock suficiente del producto en las salidas
            //Generamos una variable para almacenar los posibles problemas de stock
            $stocksValidation = [];

            //Validamos la cantidad de kilos
            //foreach ($sk as $key => $value) {
            foreach ($request->details as $detail) {

                //Obtenemos el producto, a partir del código de barras, para obtener el total de kilos a comparar
                //$product = Product::where('barcode', $value['barcode'])->first();
                $product = Product::find($detail['product_id']);
                //Primero validamos si hay stock en el inventario
                //dd($product);

                if($product) {
                    if ($product->stock_kilos === 0.0) {
                        array_push(
                            $stocksValidation,
                            array(
                                "product_id" => $product->id,
                                "product" => $product->nombre,
                                "message" => "El stock de este producto es 0",
                                "faltante" => $detail['kilos'] - $product->stock_kilos . ' kilos'
                            )
                        );
                    }
                    //Si el stock es mayor que 0, validamos que se cuente con stock suficiente del producto
                    else if ($product->stock_kilos < $detail['kilos']) {
                        //dd($product->stock_kilos);
                        //dd($product->stock, $value['kilos']);
                        array_push(
                            $stocksValidation,
                            array(
                                "product_id" => $product->id,
                                "product" => $product->nombre,
                                "message" => "No hay stock suficiente",
                                "faltante" => $detail['kilos'] - $product->stock_kilos . ' de ' . $detail['kilos'] . ' kilos'
                            )
                        );
                    }

                    //Lo siguiente consiste en validar si se cuenta con cajas suficientes para el ticket a generar
                     //Primero validamos que el stock no sea igual a 0
                    if ($product->stock_cajas === 0) {
                        array_push(
                            $stocksValidation,
                            array(
                                "product_id" => $product->id,
                                "product" => $product->nombre,
                                "message" => "El stock de cajas es de 0",
                                "faltante" => $detail['total_cajas'] - $product->stock_cajas
                            )
                        );
                    
                    }
                    //Si el stock es mayor que 0, validamos que se cuente con stock suficiente para la petición
                    else if ($product->stock_cajas < $detail['total_cajas']) {
                        //dd($box->stock_cajas, $detail['total_cajas']);
                        array_push(
                            $stocksValidation,
                            array(
                                "product_id" => $product->id,
                                "product" => $product->nombre,
                                "message" => "No hay stock de cajas suficiente",
                                "faltante" => $detail['total_cajas'] - $product->stock_cajas
                            )
                        );
                    }

                    //Finalmente, validaremos que se cuente con las tapas suficientes en el inventario
                    //Primero validamos que el stock no sea igual a 0
                    if ($product->stock_tapas === 0) {
                        //return 'no hay cajas mano';
                        array_push(
                            $stocksValidation,
                            array(
                                "product_id" => $product->id,
                                "product" => $product->nombre,
                                "message" => "El stock de tapas es de 0",
                                "faltante" => $detail['total_tapas'] - $product->stock_tapas
                            )
                        );
                    }

                    //Si el stock es mayor que 0, validamos que se cuente con stock suficiente para la petición
                    else if ($product->stock_tapas < $detail['total_tapas']) {
                        array_push(
                            $stocksValidation,
                            array(
                                "product_id" => $product->id,
                                "product" => $product->nombre,
                                "message" => "No hay tapas suficiente",
                                "faltante" => $detail['total_tapas'] - $product->stock_tapas
                            )
                        );
                    }
                }
                

            }

       
            //Si existió algún problema con el stock de alguno de los productos, enviamos una respuesta en formato JSON
            if (count($stocksValidation) > 0) {

                json_encode($stocksValidation);

                return Response::make($stocksValidation, 404);

                /* return response()->json([
                    "status" => false,
                    "errors" => $stocksValidation
                ], 404); */
                return Response::make($stocksValidation, 404);
                // return response()->json([
                //     "status" => false,
                //     "errors" => json_encode($stocksValidation)
                // ], 404);
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
            if ($request->tipo === 1) {
                //Utilizamos el arreglo con el total de kilos por producto, para sumarlos al stock actual
                foreach ($request->details as $detail) {
                    $product = Product::find($detail['product_id']);

                    //dd($detail);

                    if($product) {
                        //dd('ola',$product->stock_kilos + $value['kilos']);
                        $product->stock_kilos = $product->stock_kilos + $detail['kilos'];
                        $product->stock_cajas = $product->stock_cajas + $detail['total_cajas'];
                        $product->stock_tapas = $product->stock_tapas + $detail['total_tapas'];
                        $product->save();
                        //dd($product);
                    }

                    //Ahora, generaremos los datos necesarios para enviar los datos de los detalles del ticket
                    $detail['ticket_id'] = $ticket->id;
                    $detail['created_at'] = now();
                    $detail['updated_at'] = now();
                    //Insertamos el valor en la base de datos, en la tabla de ticketDetails
                    //dd($request->details);
                    TicketDetail::insert($detail);

                }

                } else if ($request->tipo === 2) {
                //dd($sk);
                    foreach ($request->details as $detail) {
                    $product = Product::find($detail['product_id']);

                    //dd($detail);

                    if($product) {
                        //dd('ola',$product->stock_kilos + $value['kilos']);
                        $product->stock_kilos = $product->stock_kilos - $detail['kilos'];
                        $product->stock_cajas = $product->stock_cajas - $detail['total_cajas'];
                        $product->stock_tapas = $product->stock_tapas - $detail['total_tapas'];
                        $product->save();
                        //dd($product);
                    }

                    //Ahora, generaremos los datos necesarios para enviar los datos de los detalles del ticket
                    $detail['ticket_id'] = $ticket->id;
                    $detail['created_at'] = now();
                    $detail['updated_at'] = now();
                    //Insertamos el valor en la base de datos, en la tabla de ticketDetails
                    //dd($request->details);
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
            'id' => 'required|numeric|integer|exists:tickets,id'
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

        return response()->json([
            "status" => true,
            "message" => "Datos encontrados con exito",
            "ticket" => $ticket,
            "details" => $getTicketDetails
        ], 201);


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
            'id' => 'required|numeric|integer|exists:tickets,id'
        ]);


        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Buscamos el Ticket mediante el id y generamos una colección
        $ticket = Ticket::find($id);

        //Obtenemos los detalles del ticket
        $ticketDetails = TicketDetail::where("ticket_id", "=", $ticket->id)->get();

        //Generamos una variable para almacenar los posibles problemas de stock
        $stocksValidation = [];

        //Si el ticket fue una entrada de almacen, validamos si al borrarlo, y eliminar los productos
        //datos de alta, no hay algún problema de stock
        if ($ticket->tipo === 1) {
            foreach ($ticketDetails as $detail) {
                $product = Product::find($detail['product_id']);
                //dd($product);
                //Ahora validamos que se cuente con el stock suficiente
                if ($product->stock_kilos < $detail['kilos']) {
                    array_push(
                        $stocksValidation,
                        array(
                            "id" => $product->id,
                            "product" => $product->nombre,
                            "message" => "No hay stock suficiente",
                            "faltante" => $detail['kilos'] - $product->stock_kilos
                        )
                    );
                }

                if ($product->stock_cajas < $detail['total_cajas']) {
                        array_push(
                            $stocksValidation,
                            array(
                                "product_id" => $product->id,
                                "nombre" => $product->nombre,
                                "message" => "No hay stock de cajas suficiente",
                                "faltante" => $detail['total_cajas'] - $product->stock_cajas
                            )
                        );
                    }

                    if ($product->stock_tapas < $detail['total_tapas']) {
                        array_push(
                            $stocksValidation,
                            array(
                                "product_id" => $product->id,
                                "nombre" => $product->nombre,
                                "message" => "No hay tapas suficiente",
                                "faltante" => $detail['total_tapas'] - $product->stock_tapas
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


        //Si es una entrada de almacen, realizaremos el siguiente procedimiento
        if ($ticket->tipo === 1) {
            //Utilizamos el arreglo con el total de kilos por producto, para sumarlos al stock actual
            foreach ($ticketDetails as $detail) {
                //Generamos una colección, usando el barcode del producto
                //$product = Product::where('barcode', $detail['barcode'])->first();

                $product = Product::find($detail['product_id']);
                //Sumamos los kilos recibidos en el ticket, al stock actual de la colección generada
                $product->stock_kilos = $product->stock_kilos - $detail['kilos'];
                $product->stock_cajas = $product->stock_cajas - $detail['total_cajas'];
                $product->stock_tapas = $product->stock_tapas - $detail['total_tapas'];
                //Finalmente, actualizamos la colección, para reflejar los cambios en el registro de la base de datos
                $product->save();

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


        } else if ($ticket->tipo === 2) {
            //dd("hola");
            foreach ($ticketDetails as $detail) {
                //$product = Product::where('barcode', $sk['barcode'])->first();
                $product = Product::find($detail['product_id']);
                $product->stock_kilos = $product->stock_kilos + $detail['kilos'];
                $product->stock_cajas = $product->stock_cajas + $detail['total_cajas'];
                $product->stock_tapas = $product->stock_tapas + $detail['total_tapas'];
                $product->save();

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

    }

}

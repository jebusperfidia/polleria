<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Ticket;
use App\Models\TicketDetail;
use Illuminate\Http\Request;
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
            'details.*.producto_id' => 'required|numeric|exists:products,id',
            'details.*.cantidad' => 'required|numeric'
        ];


        //Hacemos las validaciones con los datos obtenidos desde el objeto request, y las reglas generadas anteriormente
        $validate = Validator::make($request->all(), $rules);

        //Si hay algún error de validación, enviar en formato JSON
        if ($validate->fails()) {
            return response()->json([
                "errors" => $validate->errors()
            ]);
        }

        //Generamos una variable para almacenar los posibles problemas de stock
        $stocksValidation = [];

        //Validamos que existe stock suficiente del producto en las salidas
        if ($request->tipo === 2) {
            foreach ($request->details as $detail) {
                $product = Product::find($detail['producto_id']);

                //Primero validamos que el stock no sea igual a 0
                if ($product->stock === 0.0) {
                    array_push(
                        $stocksValidation,
                        array(
                            "id" => $detail['producto_id'],
                            "product" => $product->nombre,
                            "message" => "El stock para este producto es 0",
                            "faltante" => $detail['cantidad'] - $product->stock
                            )
                        );
                }
                
                //Si el stock es mayor que 0, validamos que se cuente con stock suficiente para la petición
                else if ($product->stock < $detail['cantidad']) {
                    array_push($stocksValidation, array(
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

        //Finalizamos la validación de datos

        //Una vez obtenidos los datos, primero damos de alta el ticket
        $ticket = Ticket::create([
            'tipo' => $request->tipo,
            'usuario_id' => auth()->user()->id
        ]);

        //Si el ticket fue dado de alta, lo validamos y generamos los detalles del ticket
        if ($ticket) {
            //Iteramos cada uno de los valores obtenidos en details
            foreach ($request->details as $detail) {
                //Generamos los datos restantes para la inserción en la base de datos
                $detail['ticket_id'] = $ticket->id;
                $detail['created_at'] = now();
                $detail['updated_at'] = now();

                //Obtenemos los datos del producto
                $product = Product::find($detail['producto_id']);

                //Mediante un if, validamos el tipo de ticket y actualizamos los datos del stock del producto
                if ($ticket->tipo === 1) {
                    $product->stock = $product->stock + $detail['cantidad'];
                    $product->save();
                } else {
                    $product->stock = $product->stock - $detail['cantidad'];
                    $product->save();
                }

                //Insertamos el valor en la base de datos, en la tabla de ticketDetails
                TicketDetail::insert($detail);
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
                "status" => 1,
                "message" => "Datos encontrados con exito",
                "ticket" => $product,
            ], 201);
        }
        //Ticket no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => 0,
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
            if($ticketDelete){
                return response()->json([
                    "status" => 1,
                    "message" => "Ticket Eliminado con éxito",
                ], 201);
            //Si no fue posible eliminar el ticket, enviamos una respuesta, en formato JSON
            } else {
                return response()->json([
                    "status" => 0,
                    "message" => "No fue posible eliminar el ticket"
                ], 404);
            }

        }
        //Si el ticket no fue encontrado, enviamos una respuesta
        else {
            return response()->json([
                "status" => 0,
                "message" => "ticket no encontrado"
            ], 404);
        }
    }

}
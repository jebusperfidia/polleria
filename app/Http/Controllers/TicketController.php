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

// require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;

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



        //Verificamos si el tipo de ticket, es una salida de inventario
        if ($request->tipo === 2) {
            //Validamos que existe stock suficiente del producto en las salidas
            //Generamos una variable para almacenar los posibles problemas de stock
            $stocksValidation = [];

            //Recorremos todos los detalles para verificar si hay stock suficiente de cada producto
            foreach ($request->details as $detail) {

                //Obtenemos el producto, a partir de su id, para obtener el total de kilos a comparar
                $product = Product::find($detail['product_id']);
                //Primero validamos si hay stock en el inventario
                if($product) {
                    //Si el stock para ese producto es de 0, generamos un elemento de error en el arreglo de validaciones
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
                    //Si no se cuenta con suficiente inventario, generamos un elemento de error en el arreglo de valicaciones
                    else if ($product->stock_kilos < $detail['kilos']) {
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
                //Recorremos cada elemento de los detalles para aumentar los kilos en el inventario, por cada producto
                foreach ($request->details as $detail) {
                    //Buscamos el producto del detalle
                    $product = Product::find($detail['product_id']);
                    //Si se encontró el producto, aumentamos los kilos, cajas y tapas por cada elemento
                    if($product) {
                        $product->stock_kilos = $product->stock_kilos + $detail['kilos'];
                        $product->stock_cajas = $product->stock_cajas + $detail['total_cajas'];
                        $product->stock_tapas = $product->stock_tapas + $detail['total_tapas'];
                        //Finalmente, actualizamos la colección, para reflejar los cambios en el registro de la base de datos
                        $product->save();
                    }

                    //Ahora, generaremos los datos necesarios para enviar los datos de los detalles del ticket
                    $detail['ticket_id'] = $ticket->id;
                    $detail['created_at'] = now();
                    $detail['updated_at'] = now();
                    //Insertamos el valor en la base de datos, en la tabla de ticketDetails
                    TicketDetail::insert($detail);
                }


                //Si es un ticket de salida, realizamos el mismo procedimiento que en las entradas
                //Pero, restamos los kilos, las cajas y tapas, de cada elemento de los detalles
                } else if ($request->tipo === 2) {
                    foreach ($request->details as $detail) {
                    $product = Product::find($detail['product_id']);

                    if($product) {
                        $product->stock_kilos = $product->stock_kilos - $detail['kilos'];
                        $product->stock_cajas = $product->stock_cajas - $detail['total_cajas'];
                        $product->stock_tapas = $product->stock_tapas - $detail['total_tapas'];
                        //Finalmente, actualizamos la colección, para reflejar los cambios en el registro de la base de datos
                        $product->save();
                    }

                    //Ahora, generaremos los datos necesarios para enviar los datos de los detalles del ticket
                    $detail['ticket_id'] = $ticket->id;
                    $detail['created_at'] = now();
                    $detail['updated_at'] = now();
                    //Insertamos el valor en la base de datos, en la tabla de ticketDetails
                    TicketDetail::insert($detail);
                }
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

        //Si el ticket fue una entrada de almacen, validamos si al borrarlo, y eliminar los movimientos
        //hay suficiente stock en el inventario para poder realizar la eliminación
        if ($ticket->tipo === 1) {
            foreach ($ticketDetails as $detail) {
                $product = Product::find($detail['product_id']);
                //Ahora validamos que se cuente con el stock suficiente de kilos
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

                //Ahora validamos que se cuente con el stock suficiente de cajas
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

                //Ahora validamos que se cuente con el stock suficiente de tapas
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
            //Recorremos los detalles del ticket, que obtuvimos anteriormente
            foreach ($ticketDetails as $detail) {

                //Buscamos el producto de cada detalle y eliminamos del inventario los kilos, cajas y tapas
                //Que se cargaron al inventario al generar la alta
                $product = Product::find($detail['product_id']);
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

        }

        //Si el ticket fue de salida, recorremos todos los elementos obtenidos de los detalles
        //Y sumamos al invenatario de cada producto, los kilos, cajas y tapas que se eliminaron
        else if ($ticket->tipo === 2) {
            foreach ($ticketDetails as $detail) {
                $product = Product::find($detail['product_id']);
                $product->stock_kilos = $product->stock_kilos + $detail['kilos'];
                $product->stock_cajas = $product->stock_cajas + $detail['total_cajas'];
                $product->stock_tapas = $product->stock_tapas + $detail['total_tapas'];
                 //Finalmente, actualizamos la colección, para reflejar los cambios en el registro de la base de datos
                $product->save();

                //Eliminamos el registro del detalle de la tabla detalle ticketsDetails
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

    public function ticketPrint(Request $request)
    {
        $connector = new NetworkPrintConnector("192.168.100.55", 9100);
        // dd($connector);
        
        /* Information for the receipt */
        $titles = new item1('PRODUCTO', 'KILOS', 'COSTO', 'IMPORTE');
        
        // $items = [];
        
        //foreach ($request->details as $value) {
            //  array_push(
                //    $items,
                //  new item2(wordwrap($value['nombreProducto'], 15, "\n", false), $value['kilos'], $value['costo_kilo'], $value['subtotal'])
                // );
                // }
                
                $tipo = $request->ticket['tipo'] == 1 ? 'Entrada' : 'Salida';
                
                $total = new item3('Total', $request->ticket['total'], true);
                /* Date is kept the same for testing */
                // $date = date('l jS \of F Y h:i:s A');
                date_default_timezone_set('America/Mexico_City');
                $date = date("d-m-Y h:i:s");
                // $date = "Monday 6th of April 2015 02:56:25 PM";
                /* Print a "Hello world" receipt" */
                $printer = new Printer($connector);
                
                
                /* Start the printer */
                
                /* Name of shop */
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
                $printer->text("POLLERIA GARCIA.\n");
                $printer->selectPrintMode();
                $printer->text("PONTIFICIA, LEÓN GTO.\n");
                $printer->feed();
                
            

        /* Title of receipt */
        $printer->setEmphasis(true);
        $printer->text("$tipo\n");
        $printer->feed();
        $printer->setEmphasis(false);

        /* Title of receipt */
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(false);
        $printer->text($titles->getAsTitles(32));
        $printer->feed();


        /* Items */
        foreach ($request->details as $value) {
            $producto = $value['nombreProducto'];
            $printer -> text("$producto\n");
            $line = sprintf('%-13.40s %3.40s %-3.40s %9.40s %-2.40s %10.40s', '',$value['kilos'], 'x', $value['costo_kilo'], '=', $value['subtotal']);
            //dd($line);
            $printer -> text("$line\n");
        }

        $printer->feed();

        /* Tax and total */
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text($total->getAsString(32));
        $printer->selectPrintMode();

        /* Footer */
        $printer->feed(2);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->feed(2);
        $printer->text($date . "\n");


        $printer -> cut();

        /* Close printer */
        $printer -> close();

        return response()->json([
            "status" => true,
            "message" => "TicketPrint",
        ], 201);

    }

}

/* A wrapper to do organise item names & prices into columns */
class item1
{
    private $PRODUCTO;
    private $KILOS;
    private $COSTO;
    private $IMPORTE;


    public function __construct($PRODUCTO = '', $KILOS = '', $COSTO = '', $IMPORTE = '',)
    {
        $this->PRODUCTO = $PRODUCTO;
        $this->KILOS = $KILOS;
        $this->COSTO = $COSTO;
        $this->IMPORTE = $IMPORTE;

    }

    public function getAsTitles($width = 48)
    {
        $leftCols = 12;
        $left = str_pad($this->PRODUCTO, $leftCols);
        $right = str_pad($this->KILOS, 12, ' ', STR_PAD_LEFT);
        $right2 = str_pad($this->COSTO, 10, ' ', STR_PAD_LEFT);
        $right3 = str_pad($this->IMPORTE, 12, ' ', STR_PAD_LEFT);

        return "$left$right$right2$right3\n";
    }

    public function __toString()
    {
        return $this->getAsTitles();
    }

}

class item2
{
    private $PRODUCTO;
    private $KILOS;
    private $COSTO;
    private $IMPORTE;

    public function __construct($PRODUCTO = '', $KILOS = '', $COSTO = '', $IMPORTE = '')
    {
        $this->PRODUCTO = $PRODUCTO;
        $this->KILOS = $KILOS;
        $this->COSTO = $COSTO;
        $this->IMPORTE = $IMPORTE;
    }

    public function getAsStringProducto($width = 48)
    {


        $leftCols = 12;
        $left = str_pad($this->PRODUCTO, $leftCols);


        $sign = ('$ ');
        $sign2 = ('Kg ');
        $right = str_pad($this->KILOS  . $sign2, 12, ' ', STR_PAD_LEFT);
        $right1 = str_pad($sign . $this->COSTO, 10, ' ', STR_PAD_LEFT);
        $right2 = str_pad($sign . $this->IMPORTE, 10, ' ', STR_PAD_LEFT);


        return "$left$right$right1$right2\n";
    }

    public function __toString()
    {
        return $this->getAsStringProducto();
    }

}

class item3
{
    private $name;
    private $price;
    private $dollarSign;

    public function __construct($name = '', $price = '', $dollarSign = false)
    {
        $this->name = $name;
        $this->price = $price;
        $this->dollarSign = $dollarSign;
    }

    public function getAsString($width = 48)
    {
        $rightCols = 10;
        $leftCols = 8;
        if ($this->dollarSign) {
            $leftCols = $leftCols / 2 - $rightCols / 2;
        }
        $left = str_pad(' ', $leftCols);


        $sign = ($this->dollarSign ? '$ ' : '$ ');
        $right2 = str_pad($this->name, $rightCols, ' ', STR_PAD_LEFT);
        $right = str_pad($sign . $this->price, $rightCols, ' ', STR_PAD_LEFT);

        return "$left$right2$right\n";
    }

    public function __toString()
    {
        return $this->getAsString();
    }

}

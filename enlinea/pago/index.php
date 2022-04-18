<?php
include_once '../../includes/configuracion.php';
$propietario = new propietario();
if (!isset($_GET['id'])) {    
    $propietario->esPropietarioLogueado();
    $session = $_SESSION;
}
$bitacora = new bitacora();

$accion = isset($_GET['accion']) ? $_GET['accion'] : "listar";


switch ($accion) {
    
    case "cancelacion":
        $titulo  = $_GET['id'] . ".pdf";
        $content = 'Content-type: application/pdf';
        $url     = ROOT."cancelacion.gastos/" . $_GET['id'] . ".pdf";
        header('Content-Disposition: inline; filename="' . $titulo . '"');
        header($content);
        readfile($url,false);
        break;
    
    case "historico":
        $propiedad = new propiedades();
        $inmuebles = new inmueble();
        $pagos = new pago();

        $propiedades = $propiedad->propiedadesPropietario($_SESSION['usuario']['cedula']);
        $cuenta = [];
        
        if ($propiedades['suceed'] == true) {

            foreach ($propiedades['data'] as $propiedad) {

                $inmueble = $inmuebles->ver($propiedad['id_inmueble']);
                $pago = $pagos->listarCancelacionDeGastos($propiedad['id_inmueble'], $propiedad['apto']);
                
                if ($pago['suceed'] === true) {
                    
                    // $bitacora->insertar([ 'id_sesion'   => $session['id_sesion'],
                    //                       'id_accion'   => ACTION::CONSULTA_RECIBOS_CANCELADOS,
                    //                       'descripcion' => count($pago['data'])." recibos(s) registrado(s).",]);
                    
                    for ($index = 0; $index < count($pago['data']); $index++) {
                        
                        $filename = "../../cancelacion.gastos/" . $pago['data'][$index]['numero_factura'] . ".pdf";
                        $pago['data'][$index]['recibo'] = file_exists($filename);
                        
                    }
                    
                    $cuenta[] = ['inmueble'    => $inmueble['data'][0],
                                 'propiedades' => $propiedad,
                                 'cuentas'     => $pago['data']];
                }
                
            }
        }
        $params = [
            'seccion' => 'Recibos Cancelados',
            'icon'    => 'th-list',
            'cuentas' => $cuenta,
            'session' => $session,
        ];
        echo $twig->render('enlinea/pago/cancelacion.gastos.twig', $params);
        break; 
    
    case "ver":
        $propiedad = new propiedades();
        $inmuebles = new inmueble();
        $pagos = new pago();

        $propiedades = $propiedad->propiedadesPropietario($_SESSION['usuario']['cedula']);
        $cuenta = [];
        
        if ($propiedades['suceed'] === true) {
            
            foreach ($propiedades['data'] as $propiedad) {
                
                $legal = $propiedad['meses_pendiente'] > MESES_COBRANZA;
                
                $inmueble = $inmuebles->ver($propiedad['id_inmueble']);
                $pago = $pagos->listarPagosProcesados($propiedad['id_inmueble'], $propiedad['apto'], 5);
                
                if ($pago['suceed'] === true) {

                    $bitacora->insertar( [ "id_sesion"   => $session['id_sesion'],
                                          "id_accion"   => ACTION::VER_CANCELACION_GASTOS,
                                          "descripcion" => 'Lista ('.count($pago['data']).') pago(s)' ] );

                    for ($index = 0; $index < count($pago['data']); $index++) {
                        $filename = "../../cancelacion.gastos/" . $pago['data'][$index]['id_factura'] . ".pdf";
                        $pago['data'][$index]['recibo'] = file_exists($filename);
                    }

                    $cuenta[] = [ "inmueble"    => $inmueble['data'][0],
                                  "propiedades" => $propiedad,
                                  "cuentas"     => $pago['data'],
                                  "legal"       => $legal ];
                }
            }
        }

        echo $twig->render( 'enlinea/pago/cancelacion.html.twig', ["session" => $session,"cuentas" => $cuenta] );


        break; 
    
    case "guardar":
        $pago = new pago();
        $data = $_POST;
        if (isset($_FILES['soporte'])) {
            $file = explode(".",$_FILES['soporte']['name']);
            $extension = strtolower(end($file));
            $data['soporte']=$data['tipo_pago'].$data['numero_documento'].'_'.$data['id_inmueble'][0].'_'.$data['id_apto'][0].'.'.$extension;
            $tempFile = $_FILES['soporte']['tmp_name'];
            $mainFile = $data['soporte'];
            move_uploaded_file($tempFile,"soportes/".$mainFile);
        }
        if (count($data) > 0) {
            unset($data['registrar']);
            $data['fecha']=date("Y-m-d H:i:00 ", time());
            $exito = $pago->registrarPago($data);
            $bitacora->insertar(Array(
                "id_sesion"=>$session['id_sesion'],
                "id_accion"=> 9,
                "descripcion"=>$data['numero_documento']." >".$exito['mensaje'],
            ));
        } else {
            header("location:" . URL_SISTEMA . "/pago/registrar");
            return;
        }
        
        echo json_encode($exito);
        
        break;
    
    case "registrar":
    default :
        $propiedad  = new propiedades();
        $facturas   = new factura();
        $inmuebles  = new inmueble();
        $resultado  = [];
        $cuenta     = [];
        $bancos     = [];

        if ($accion == 'guardar') {
            $resultado = $exito;
        }
        $result = $inmuebles->listarBancosActivos();
        
        if ($result['suceed']) {
            $bancos = $result['data'];
        }

        $propiedades = $propiedad->propiedadesPropietario($_SESSION['usuario']['cedula']);

        
        $bitacora->insertar([
            'descripcion' => 'Inicio del proceso',
            'id_accion'   => 8,
            'id_sesion'   => $session['id_sesion'],
        ]);
        
        if ($propiedades['suceed'] == true) {

            foreach ($propiedades['data'] as $propiedad) {

                $inmueble = $inmuebles->ver($propiedad['id_inmueble']);
                
                $factura = $facturas->estadoDeCuenta($propiedad['id_inmueble'], $propiedad['apto']);
                
                if ($factura['suceed'] == true) {
                    
                    for ($index = 0; $index < count($factura['data']); $index++) {
                        
                        $filename = "../avisos/".$factura['data'][$index]['numero_factura'].".pdf";
                        
                        $factura['data'][$index]['aviso'] = file_exists($filename);
                        
                        $r = pago::facturaPendientePorProcesar(
                                $factura['data'][$index]['periodo'], 
                                $factura['data'][$index]['id_inmueble'], 
                                $factura['data'][$index]['apto']
                            );
                        
                        if ($r['suceed'] && count($r['data'])>0) {

                            $factura['data'][$index]['pagado'] = 1;
                            $factura['data'][$index]['pagado_detalle']= "<i class='fal fa-calendar color-warning fw-700'></i> ".
                                Misc::date_format($r['data'][0]['fecha'])."<br>".
                                strtoupper($r['data'][0]['tipo_pago']." - Ref: ".
                                $r['data'][0]['numero_documento']."<br>Monto: ".
                                number_format($r['data'][0]['monto'],2,",","."));

                        } else {
                            $factura['data'][$index]['pagado'] = 0;
                            $factura['data'][$index]['pagado_detalle']='';
                        }
                    }
                    
                    $banco = $inmuebles->obtenerCuentasBancariasPorInmueble($propiedad['id_inmueble']);
                    
                    $inmueble['data'][0]['cuentas_bancarias'] = $banco['data'];
                    

                    $cuenta[] = [
                        'inmueble'    => $inmueble['data'][0],
                        'propiedades' => $propiedad,
                        'cuentas'     => $factura['data'],
                        'resultado'   => $resultado
                    ];
                }
            }
        }
        
        $params = [
            'accion'       => $accion,
            'bancos'       => $bancos,
            'cuentas'      => $cuenta,
            'propiedades'  => $propiedades['data'],
            'session'      => $session,
            'usuario'      => $session['usuario'],
            'seccion'      => 'Reportar pago de condominio',
            'icon'         => 'money-bill-wave',
        ];
        
        echo $twig->render('enlinea/pago/registrar.twig', $params);
        break; 

    case "listaPagosDetalle":
        $pagos = new pago();
        $pago_detalle = $pagos->detalleTodosPagosPendientes();
        if ($pago_detalle['suceed'] && count($pago_detalle['data']) > 0) {
            echo "id_pago,id_inmueble,id_apto,monto,id_factura<br>";
            foreach ($pago_detalle['data'] as $value) {
                echo $value['id_pago'] . ",";
                echo "\"".$value['id_inmueble']."\",";
                echo "\"".$value['id_apto'] . "\",";
                echo $value['monto'] * 100 . ",";
                echo "\"".$value['id_factura']."\"";
                echo "<br>";
            }
        }
        break; 

    case "listaPagosMaestros":
        $pagos = new pago();
        $pagos_maestro = $pagos->listarPagosPendientes();

        if ($pagos_maestro['suceed'] && count($pagos_maestro['data']) > 0) {
            echo "id,fecha,tipo_pago,numero_documento,fecha_documento,monto,banco_origen,";
            echo "banco_destino,numero_cuenta,estatus,email,enviado,telefono<br>";
            foreach ($pagos_maestro['data'] as $pago) {
                echo $pago['id'] . ",";
                echo Misc::date_format($pago['fecha']) . ",";
                echo strtoupper($pago['tipo_pago']) . ","; 
                echo $pago["numero_documento"] . ",";
                echo Misc::date_format($pago["fecha_documento"]) . ",";
                echo $pago["monto"] * 100 . ",";
                echo $pago["banco_origen"] . ",";
                echo $pago["banco_destino"] . ",";
                echo str_replace("-", "", "#".$pago["numero_cuenta"]) . ",";
                echo strtoupper($pago["estatus"]) . ",";
                echo $pago["email"] . ",";
                echo $pago["enviado"] . ",";
                echo $pago["telefono"];
                echo "<br>";
            }
        }
        break; 

    case "listarPagosPendientes":
        $pagos = new pago();
        $pagos_maestro = $pagos->listarPagosPendientes();
        
        if ($pagos_maestro['suceed'] && count($pagos_maestro['data']) > 0) {
            
            foreach ($pagos_maestro['data'] as $pago) {

                $pago_detalle = $pagos->detallePagoPendiente($pago['id']);
                
                if ($pago_detalle['suceed'] && count($pago_detalle['data']) > 0) {
                    $enviado = $pago["enviado"] == 0 ? "False" : "True";
                    echo "|" . $pago['id'] . "|";
                    echo Misc::date_format($pago['fecha']) . "|";
                    echo strtoupper($pago['tipo_pago']) . "|";
                    echo $pago["numero_documento"] . "|";
                    echo Misc::date_format($pago["fecha_documento"]) . "|";
                    echo Misc::number_format($pago["monto"]) . "|";
                    echo $pago["banco_origen"] . "|";
                    echo $pago["banco_destino"] . "|";
                    echo $pago["numero_cuenta"] . "|";
                    echo strtoupper($pago["estatus"]) . "|";
                    echo $pago["email"] . "|";
                    echo $enviado . "|";
                    echo $pago["telefono"] . "|";
                    // --
                    foreach ($pago_detalle['data'] as $value) {
                        echo $value['id_inmueble'] . "|";
                        echo $value['id_apto'] . "|";
                        echo Misc::number_format($value['monto']) . "|";
                        echo $value['id_factura'] . "|";
                        echo $value['periodo'] . "|";
                    }
                    echo "<br>";
                
                }
            
            }
        

        } else {
            echo "0";
        }


        break; // </editor-fold>

    case "confirmar":

        $pago = new pago();
        $id = $_GET['id'];
        $estatus = $_GET['estatus'];
        $r = $pago->procesarPago($id, $estatus);
        echo $r;
        break; // </editor-fold>
   
    case "reenviarEmailRegistroPago":
        $pago = new pago();
        $id = $_GET['id'];
        $pago->enviarEmailPagoRegistrado($id);
        break;
    
    case "enviarEmailRegistroPagoNoEnviado":
        $pago = new pago();
        $lista = $pago->listarPagosEmailRegisroNoEnviado();
        if ($lista['suceed'] && count($lista['data']) > 0) {
            foreach ($lista['data'] as $registro) {
                $pago->enviarEmailPagoRegistrado($registro['id']);
                echo '\n';
            }
        } else {
            echo 'No hay email que reenviar en este momento';
        }
        break;
        
    case "mantenimiento-soportes":
        $path = getcwd();
        $path .= '/soportes';
        $dir = opendir($path);
        $e = 0;
        $n = 0;
        // Leo todos los ficheros de la carpeta
        while ($elemento = readdir($dir)) {
            // Tratamos los elementos . y .. que tienen todas las carpetas
            if ($elemento != "." && $elemento != ".." && $elemento != "mantenimiento.php" && $elemento != "index.php") {
                // Si es una carpeta
                if (!is_dir($path . $elemento)) {
                    $f_archivo = date('Y-m-d', filemtime($path . "/" . $elemento));
                    $fecha1 = date_create($f_archivo);
                    $fecha2 = date_create(date('Y-m-d'));
                    $diff = date_diff($fecha1, $fecha2);
                    $dias = $diff->format('%a');
                    if ($dias > 45) {
                        unlink($path . "/" . $elemento);
                        $e++;
                    }
                    $n++;
                    // Muestro el fichero
                    while (ob_get_level()) {
                        ob_end_flush();
                    }
                    // start output buffering
                    if (ob_get_length() === false) {
                        ob_start();
                    }
                    //echo $elemento.'-> '.$f_archivo.'-> '.$dias.'<br>';
                }
            }
        }
        echo "$n archivos en este directorio.<br>";
        echo "$e archivos eliminados";
        break; 

    case "listaRecibosCanceladosNoPublicados":
        $pagos = new pago();
        $pago = $pagos->listaRecibosCancelados();
        if ($pago['suceed']) {
            $n = 0;
            foreach ($pago['data'] as $r) {
                $filename = "../../cancelacion.gastos/" . $r['id_factura'] . ".pdf";
                if (!file_exists($filename)) {
                    echo $r['id_factura'] . "|" . $r['id_inmueble'] . "|" . $r['id_apto'] . "<br>";
                    $n++;
                }
            }
            echo "Total Recibos: " . $n;
        }
        break; 

    case "movimientocaja":
        $propiedad = new propiedades();
        $pagos = new pago();
        $inmuebles = new inmueble();

        $propiedades = $propiedad->propiedadesPropietario( $_SESSION['usuario']['cedula'] );


        $historico = Array();
        if ($propiedades['suceed'] == true) {


            foreach ($propiedades['data'] as $propiedad) {
                $bitacora->insertar(Array(
                    "descripcion" => $propiedad['id_inmueble']." - ".$propiedad['apto'],
                    "id_accion" => 16,
                    "id_sesion" => $session['id_sesion'],
                ));

                $inmueble = $inmuebles->verDatosInmueble( $propiedad['id_inmueble'] );

                $p = $pagos->estadoDeCuenta( $propiedad['id_inmueble'], $propiedad['apto'] );
                
                if ($p['suceed'] == true) {
                    for ($index = 0; $index < count($p['data']); $index++) {
                        $filename = "../../cancelacion.gastos/" . $p['data'][$index]['numero_recibo'] . ".pdf";
                        $p['data'][$index]['recibo'] = file_exists($filename);
                    }
                    $historico[] = [

                        'inmueble'  => $inmueble['data'][0],
                        'pagos'     => $p['data'],
                        'propiedad' => $propiedad,
                        
                    ];
                }
            }
        }
        $params = [ 
                    'historicos'   => $historico, 
                    'icon'         => 'clipboard-list-check',
                    'propiedades'  => $propiedad,
                    'seccion'      => 'Listado de pagos',
                    'session'      => $session,
                ];

        echo $twig->render('enlinea/pago/historico.twig', $params);
        break; 


    }
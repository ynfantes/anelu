<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of pago
 *
 * @author emessia
 */
class pago extends db implements crud {
    const tabla = "pagos";

    public function actualizar($id, $data){
        return $this->update(self::tabla, $data, array("id" => $id));
    }

    public function borrar($id){
        return $this->delete(self::tabla, array("id" => $id));
    }

    /**
     * Inserta el contenido en la tabla pagos
     *
     * @param	Array	$data	Arreglo con la data
     * 
     * @return	Array	Retorna arreglo con parámetos del resultado
     * @since	1.0
     */
    public function insertar($data){
        return $this->insert(self::tabla, $data);
    }
    public function insertarDetallePago($data) {
        return $this->insert("detalle_pago", $data);
    }
    public function listar($condicion = null){
       return $this->select("*", self::tabla, $condicion);
    }
    
    public function ver($id){
        return $this->select("*",self::tabla,array("id"=>$id));
    }

    public function borrarTodo() {
        return $this->delete(self::tabla);
    }
    
    public function pagoYaRegistrado($data) {
        return $this->select("*", self::tabla, Array(
            "tipo_pago"=>$data['tipo_pago'],
            "numero_documento"=>$data['numero_documento'],
            "numero_cuenta"=>$data['numero_cuenta'],
            "banco_destino"=>$data['banco_destino']));
    }

    public function listarPagosPendientes($inmueble = 'sac'){
        
        $condicion = "pagos.estatus='p'";
        if ($inmueble != 'sac') $condicion.= " and pago_detalle.id_inmueble='".$inmueble."'";
        $query = "select distinct pagos.* from pagos inner join pago_detalle on pagos.id = pago_detalle.id_pago ";
        $query.= "where ".$condicion." ";
        $query.= "order by pago_detalle.id_inmueble, pago_detalle.id_apto, pagos.fecha desc LIMIT 0 , 600";
                
        return $this->dame_query($query);
    }
    
    public function detallePagoPendiente($id_pago) {
        return $this ->select("*", "pago_detalle", ["id_pago"=>$id_pago]);
    }

    public function detalleTodosPagosPendientes() {
        $query = "select * from pago_detalle where id_pago in 
            (select id from pagos where estatus='p')";
        return $this->dame_query($query);
        
    }
    
    public function registrarPago($data) {
        $resultado = [];
        $this->exec_query("START TRANSACTION");
        try {
            $res = $this->pagoYaRegistrado($data);
            
            if ($res['suceed'] && !count($res['data']) > 0 ) {
                
                $pago_detalle = [];
                $pago_detalle['id_factura'] = $data['facturas'];
                $pago_detalle['id_inmueble'] = $data['id_inmueble'];
                $pago_detalle['apto'] = $data['id_apto'];
                $pago_detalle['monto'] = $data['montos'];
                $pago_detalle['periodo']= $data['periodo'];
                $pago_detalle['id']=$data['id'];
                
                unset($data['facturas'], $data['montos'], $data['id_inmueble'], $data['id_apto'],$data['periodo'],$data['id']);
                
                $data['fecha_documento'] = Misc::format_mysql_date($data['fecha_documento']);
                $data['monto'] = Misc::format_mysql_number($data['monto']);
                $data['tipo_pago'] = strtoupper($data['tipo_pago']);
                $data['banco_destino'] = strtoupper($data['banco_destino']);
                
                if (isset($data['banco_origen'])) {
                    $data['banco_origen'] = strtoupper($data['banco_origen']);
                }
                
                $data['numero_cuenta'] = str_replace(" ", "", $data['numero_cuenta']);
                
                $resultado['pago'] = $this->insertar($data);

                if ($resultado['pago']['suceed']) {

                    $id_pago = $resultado['pago']['insert_id'];
                    $resultado['pago_detalle'] = [];
                    
                    for ($i = 0; $i < count($pago_detalle['id']); $i++) {
                        
                        $j = (int)$pago_detalle['id'][$i];
                        
                        $resultado_detalle = $this->insert("pago_detalle", Array(
                            "id_pago" => $id_pago, 
                            "id_factura" => $pago_detalle['id_factura'][$j],
                            "id_inmueble" => $pago_detalle['id_inmueble'][$j],
                            "id_apto" => $pago_detalle['apto'][$j],
                            "monto" => $pago_detalle['monto'][$j],
                            "periodo"=>  Misc::format_mysql_date($pago_detalle['periodo'][$j])));

                        array_push($resultado['pago_detalle'], $resultado_detalle);

                        $resultado['suceed'] = $resultado_detalle['suceed'];
                        
                        if (!$resultado_detalle['suceed']) {
                            $resultado['mensaje'] = "Ha ocurrido un error al procesar el pago";
                        } else {
                            $this->exec_query("COMMIT");
                            $resultado['suceed'] = true;
                            $resultado['mensaje'] = "Pago proceso con éxito! En un plazo máximo de 48 horas será aplicado a su cuenta de condominio.";
                            // se envia el email de confirmación
                            $ini = parse_ini_file('emails.ini');
                            $mail = new mailto(SMTP);
                            if (isset($_SESSION['usuario']['directorio'])) {
                                $propietario = 'Propietario(a)';
                            } else {
                                $propietario = $_SESSION['usuario']['nombre'];
                            }
                            $forma_pago = $data['tipo_pago']=='d'? 'DEPOSITO':'TRANSFERENCIA';
                            $inmueble   = new inmueble();
                            // datos del inmueble
                            $datos_inmueble = $inmueble->ver($pago_detalle['id_inmueble'][0]);

                            $nombre_inmueble    = '';
                            $rif                = '';
                            $moneda             = 'Bs';
                            if ($datos_inmueble['suceed'] && count($datos_inmueble['data']) > 0) {

                                $nombre_inmueble    = $datos_inmueble['data'][0]['nombre_inmueble'];
                                $rif                = $datos_inmueble['data'][0]['RIF'];
                                $moneda             = $datos_inmueble['data'][0]['moneda'];

                            }
                            $payDate = new DateTime($data['fecha']);

                            $mensaje = sprintf($ini['CUERPO_MENSAJE_PAGO_RECEPCION_CONFIRMACION'], 
                                    $propietario,
                                    $forma_pago,
                                    $data['numero_documento'],
                                    $data['banco_destino'],
                                    $data['numero_cuenta'],
                                    $moneda,
                                    Misc::number_format($data['monto']),
                                    Misc::date_format($data['fecha_documento']),
                                    $data['email'],
                                    $data['telefono'],
                                    $propietario,
                                    $id_pago,
                                    $payDate->format('d/m/y h:i A')
                                    ); 
                            $mensaje.=$ini['PIE_MENSAJE_PAGO'];
                            
                            $r = $mail->enviar_email("Pago de Condominio", $mensaje, "", $data['email']);
                            
                            if ($r=="") {
                                $this->actualizar($id_pago, Array("enviado"=>1));
                            } else {
                                echo($r);
                            }
                            
                        }
                    }
                } else {
                    $resultado = $resultado['pago'];
                    $this->exec_query("ROLLBACK");
                    $resultado['mensaje'] = "Error mientras se procesaba el pago maestro.";
                }
            } else {
                $resultado['suceed'] = false;
                $resultado['mensaje'] = "Estimado propietario:\n\nEste pago ya fue registrado con anterioridad, en fecha ".  Misc::date_format($res['data'][0]['fecha'].".");
                if ($res['data'][0]['estatus']=='p') {
                    $resultado['mensaje'].= "\nActualmente está pendiente de ser aplicado a su cuenta.";
                }
                if ($res['data'][0]['estatus']=='a') {
                    $resultado['mensaje'].= "\nEL pago ya fue aplicado a su cuenta.";
                }
                if ($res['data'][0]['estatus']=='a') {
                    $resultado['mensaje'].= "\nEl pago ya fue rechazado. Si considera que es un error nuestro, escríbanos a adm.aneluca@gmail.com";
                }
            }
        } catch (Exception $exc) {
            $resultado['suceed'] = false;
            $this->exec_query("ROLLBACK");
            $resultado['mensaje'] = "Error inesperado, contacte con el administrador del sistema";
            echo $exc->getTraceAsString();
        }
        return $resultado;
     }

    public function procesarPago($id,$estatus) {
        
        $this->actualizar($id, array("estatus"=>$estatus));
        $r = $this->ver($id);
        if ($r['suceed']==true) {
            if (count($r['data'])>0) {
                $tipo_pago = strtoupper($r['data'][0]['tipo_pago']) == 'D' ? "DEPOSITO" : "TRANSFERENCIA";
                $data = Array(
                    'administradora'    => TITULO,
                    'forma_pago'        => $tipo_pago,
                    'numero_documento'  => $r['data'][0]['numero_documento'],
                    'banco'             => $r['data'][0]['banco_destino'],
                    'cuenta'            => $r['data'][0]['numero_cuenta'],
                    'monto'             => $r['data'][0]['monto'],
                    'fecha'             => $r['data'][0]['fecha'],
                    'email'             => $r['data'][0]['email']
                    );
                return $this->enviarEmailPagoProcesado($id, $estatus,$data);
            }
            
        }
        return "Falló";
        
    }
    
    public function enviarEmailPagoRegistrado($id) {
        $data = $this->ver($id);
        
        if ($data['suceed'] == TRUE && count($data['data'])>0) {

            $mail = new mailto(SMTP);
            $inmueble = new inmueble();
            $ini = parse_ini_file('emails.ini');
            $isPropietario = isset($_SESSION['usuario']['nombre']);
            $propietario =  $isPropietario ? $_SESSION['usuario']['nombre'] : 'Propietario(a)';
            $forma_pago = strtoupper($data['data'][0]['tipo_pago'])=='D'? 'DEPOSITO':'TRANSFERENCIA';
            $pago_detalle = $this->detallePagoPendiente($id);
            
            $datos_inmueble = $inmueble->ver($pago_detalle['data'][0]['id_inmueble']);
            
            if ($datos_inmueble['suceed'] && count( $datos_inmueble['data']) >0 ) {
                
                $nombre_inmueble = $datos_inmueble['data'][0]['nombre_inmueble'];
                $rif = $datos_inmueble['data'][0]['RIF'];
                $moneda = $datos_inmueble['data'][0]['moneda'];

            }
            $payDate = new DateTime($data['data'][0]['fecha']);
            $mensaje = sprintf($ini['CUERPO_MENSAJE_PAGO_RECEPCION_CONFIRMACION'], 
                    $propietario,
                    $forma_pago,
                    $data['data'][0]['numero_documento'],
                    $data['data'][0]['banco_destino'],
                    $data['data'][0]['numero_cuenta'],
                    $moneda,
                    Misc::number_format($data['data'][0]['monto']),
                    Misc::date_format($data['data'][0]['fecha_documento']),
                    $data['data'][0]['email'],
                    $data['data'][0]['telefono'],
                    $propietario,
                    $id,
                    $payDate->format('d/m/y h:i A'));
                    $mensaje.=$ini['PIE_MENSAJE_PAGO'];
            
            $r = $mail->enviar_email("Pago de Condominio", $mensaje, "", $data['data'][0]['email']);

            if ($r=="") {
                $this->actualizar($id, Array("enviado"=>1));
                echo "Email enviado a ".$data['data'][0]['email']." Ok!";
            } else {
                echo($r);
            }
        } else {
            echo 'No se consigue la informaci&oacute;n del pago ID: '.$id;
        }
    }
    
    public function enviarEmailPagoProcesado($id,$estatus,$data) {
        $ini = parse_ini_file('emails.ini');
        $mail = new mailto(SMTP);
        
        $s = $estatus=='A' ? "CONFIRMACION" : "RECHAZO";
        $m = $estatus=='A' ? "CONFIRMACION" : "RECHAZO";
        $destinatario = $data['email'];
        $subject = sprintf($ini['ASUNTO_MENSAJE_PAGO_PROCESADO_'.$s]);
        $mensaje = sprintf($ini['CUERPO_MENSAJE_PAGO_PROCESADO_'.$m],
                $data['administradora'],
                $data['forma_pago'],
                $data['numero_documento'],
                $data['banco'],
                $data['cuenta'],
                Misc::number_format($data['monto']),
                Misc::date_format($data['fecha']),
                $ini['CUENTA_PAGOS']
                );
        
        //$adjunto="";
        $can = array();
        
        // <editor-fold defaultstate="collapsed" desc="si el pago es aplicado">
        if ($estatus == 'A') {

            $r = $this->detallePagoPendiente($id);
            
            if ($r['suceed'] == true) {

                if (count($r['data']) > 0) {
                    $n = 0;
                    foreach ($r['data'] as $factura) {
                        
                        //$con = $adjunto == "" ? "" : ",";
                        $factura = '../../cancelacion.gastos/' . $factura["id_factura"] . '.pdf';
                        $factura = realpath($factura);
                        if ($factura != "") {
                            $n = $n + 1;
                            $can[] = $factura;
                        }
                        //$adjunto.= $con . $factura;
                    }
                    $mensaje.= "Hemos adjuntado " . $n . " factura(s).";
                    if ($n < count($r['data']) ) {
                        $mensaje.= "-<br>Falta(ron) factura(s) por adjuntar.";
                    }
                }
            }
        }// </editor-fold>
        
        $mensaje.= $ini['PIE_MENSAJE_PAGO'];
        
        $r = $mail->enviar_email($subject, $mensaje, "", $destinatario,"",$can);
        
        if ($r=="") {
            $this->actualizar($id, [ "confirmacion" => 1 ] );
            return "Ok";
        }
        return "Falló";
        
    }
    
    public static function listarPagosProcesados($inmueble, $apartamento, $n) {
        $consulta = "select p.fecha, d.* from pagos p join pago_detalle d on 
            p.id = d.id_pago where d.id_inmueble='".$inmueble."' and id_apto='".$apartamento."' 
               and p.estatus='a' order by 1 desc LIMIT 0,".$n;
        return db::query($consulta);
    }

    public static function detalleCancelacionDeGastos($id_factura) {
         $consulta = "select f.*, d.*, i.nombre_inmueble, pro.nombre, p.alicuota
            from facturas f join factura_detalle d on f.numero_factura =
            d.id_factura join inmueble i on i.id = f.id_inmueble 
            JOIN propiedades p ON p.id_inmueble = i.id
            AND p.apto = f.apto
            JOIN propietarios pro ON pro.cedula = p.cedula
            where f.numero_factura ='".$id_factura."' order by d.codigo_gasto ";
        
        return db::query($consulta);
    }
    
    public static function numeroRecibosCanceladosPorPropitario($cedula) {
        $sql = "select count(p.id) as cantidad from pagos as p join pago_detalle as d on d.id_pago = p.id
            join propiedades as pro on pro.id_inmueble = d.id_inmueble and pro.apto = d.idapto
            where estatus='A' and pro.cedula=".$cedula;
        
        return db::query($sql);
    }
    
    public static function facturaPendientePorProcesar($periodo,$inmueble,$apto) {
        $sql = "SELECT * FROM pagos p join pago_detalle pd on p.id = pd.id_pago where p.estatus='p' and pd.periodo='".$periodo."' and id_inmueble='".$inmueble."' and id_apto='".$apto."'";
        
        $r = db::query($sql);
        
        return $r;
        
    }
    
    public function listarCancelacionDeGastos($inmueble, $apartamento) {
        $r = $this->select("*,CONCAT('01-', periodo ) AS fPeriodo", 
                "cancelacion_gastos",
                ["id_inmueble"=>$inmueble,"id_apto"=>$apartamento],
                null,
                Array("fecha_movimiento"=>"DESC","STR_TO_DATE(CONCAT('01-', periodo), '%d-%m-%Y')"=>"DESC"));
        return $r;
    }
    
    public function insertarCancelacionDeGastos($data) {
        return db::insert("cancelacion_gastos",$data);
    }
    
    public function cancelacionExisteEnBaseDeDatos($cancelacion) {
        $cancelacion = str_replace(".pdf","",$cancelacion);
        $query = "select numero_factura from cancelacion_gastos where numero_factura='".$cancelacion."'";
        $r=0;
        $result = $this->dame_query($query);
        if ($result['suceed']==true) {
            if (count($result['data'])>0) {
                $r=1;
            }
        }
        return $r;       
    }
    
    public function listarPagosEmailRegisroNoEnviado() {
        return $this->select("*", self::tabla, Array("estatus"=>'p',"enviado"=>0));
    }

    public static function listarPagosProcesadosWeb() {
        $consulta = "select d.id_factura,d.id_inmueble,d.id_apto from pagos p join pago_detalle d on p.id = d.id_pago "
                . "where p.estatus='a' and (left(d.id_factura,1)='0' or left(d.id_factura,1)='1') and d.id_inmueble in "
                . "(select id from inmueble) order by d.id_factura";
        return db::query($consulta);
    }
    
    public static function listaRecibosCancelados() {
        $consulta = "select numero_factura id_factura, id_inmueble, id_apto from cancelacion_gastos";
        return db::query($consulta);
    }

    public function estadoDeCuenta( $inmueble, $apto ) {
        $consulta = "select * from movimiento_caja "
                ."where id_inmueble='$inmueble' and id_apto='$apto' "
                ."order by fecha_movimiento DESC";
        
        return db::query($consulta);
    }

    public function listarCancelacionDeGastosConNumeroFatura() {
        return db::query('select * from cancelacion_gastos where numero_factura <> ""');
    }

    public function insertarActualizarCancelacionDeGastos($data) {
        return db::insertUpdate("cancelacion_gastos",$data,$data);
    }

    public function listarPropietariosCuotaExcedida($cuota) {

        $sql = "select id_inmueble,id_apto,count(*) as regs 
            from cancelacion_gastos 
            group by id_inmueble,id_apto 
            having count(*) > $cuota
            order by count(*) DESC";
        
        return db::query($sql);

    }
    
    public function eliminarCancelacionDeGastos($id) {
        return db::delete('cancelacion_gastos',['id'=>$id]);
    }
}
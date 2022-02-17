<?php
include_once '../includes/configuracion.php';
include_once '../includes/file.php';

$f            = new factura();
$p            = new pago();
$mensajes     = new mensajes();
$notificacion = new notificacion();
$inmuebles    = new inmueble();
$propiedad    = new propiedades();
$facturas     = new factura();

propietario::esPropietarioLogueado();

$archivo = '../'.ACTUALIZ.ARCHIVO_ACTUALIZACION;
$fecha_actualizacion = JFile::read($archivo);

$session = $_SESSION;

$recibos = 0;
$cancela = 0;
$cuenta  = [];
$factura = null;
$cobro   = null;
$monto   = 0;
$total   = 0;
$promedio_facturacion = 0;

$monto_c = 0;
$total_c = 0;
$promedio_cobranza = 0;
$i       = 0;
$n       = 0;
$direccion_facturacion = "right";
$direccion_cobranza = "right";
$moneda  = 'Bs.';

$propiedades = $propiedad->propiedadesPropietario($_SESSION['usuario']['cedula']);

if ($propiedades['suceed']) {
    
    $inmueble = $inmuebles->ver($propiedades['data'][0]['id_inmueble']);

    if($inmueble['suceed']) {
        $moneda = $inmueble['data'][0]['moneda'];
    }

    $facturacion = $inmuebles->movimientoFacturacionMensual($propiedades['data'][0]['id_inmueble']);
    $cobranza = $inmuebles->movimientoCobranzaMensual($propiedades['data'][0]['id_inmueble']);
    
    if ($facturacion['suceed']) {

        foreach ($facturacion['data'] as $r) {
            $i++;
            $direccion_facturacion = $r['facturado'] > $monto ? "up" : "down";
            $monto = $r['facturado'];
            $total += $monto;
            $factura .= $factura != '' ? ',' : '';
            $factura .= (int) $r['facturado'];
        }
        if($i>0) { $promedio_facturacion = Round(($total / $i),2); };
    }
    if ($cobranza['suceed']) {
        
        foreach ($cobranza['data'] as $c) {
            $n++;
            $direccion_cobranza = $c['monto'] > $monto_c ? "up" : "down";
            $monto_c = $c['monto'];
            $total_c += $monto_c;
            $cobro .= $cobro != '' ? ',' : '';
            $cobro .= (int)$c['monto'];
        }
        if ($n>0) {$promedio_cobranza = Round(($total_c / $n),2);}
        
    }
    
}

$cuenta = $f->numeroRecibosPendientesPropitario($session['usuario']['cedula']);

if ($cuenta['suceed']) {
    if (count($cuenta['data']) > 0) {
        $recibos = $cuenta['data'][0]['cantidad'];
    }
}

$pagos = $p->numeroRecibosCanceladosPorPropitario($session['usuario']['cedula']);
if ($pagos['suceed']) {
    if (count($pagos['data']) > 0) {
        $cancela = $pagos['data'][0]['cantidad'];
    }
}

$m = $mensajes->cantidadMensajesSinLeerPorPropietario($session['usuario']['cedula'],$session['id_sesion']);

$n = $notificacion->obtenerNumeroNuevasNotificacionesPropietario($session['usuario']['cedula']);

$o = $notificacion->obtenerNumeroNotificacionesPropietario($session['usuario']['cedula']);

$sesiones = propietario::obtenerInfoUltimasSesiones($session['usuario']['cedula'],$session['id_sesion']);
$historico = null;

if ($sesiones['suceed']) {
    $historico = $sesiones['data'];
}

echo $twig->render('enlinea/index.twig', [
    'cancelacion'           => $cancela,
    'fecha_actualizacion'   => $fecha_actualizacion,
    'historico'             => $historico,
    'inmuebles'             => $propiedades['data'],
    'mensajes'              => $m,
    'moneda'                => $moneda,
    'movimiento_cobranza'   => $cobro,
    'notificaciones'        => $n,
    'promedio_cobranza'     => $promedio_cobranza,
    'promedio_facturacion'  => $promedio_facturacion,
    'recibos'               => $recibos,
    'seccion'               => 'Tablero',
    'session'               => $session,
    'total_notificaciones'  => $o,
]);
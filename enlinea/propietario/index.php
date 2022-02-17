<?php
include_once '../../includes/configuracion.php';
if (isset($_GET['logout'])) {
    die();
}
$propietario = new propietario();
$bitacora = new bitacora();

$accion = isset($_GET['accion']) ? $_GET['accion'] : "ver";
$id = isset($_GET['id']) ? $_GET['id'] : "perfil";
if ($id!= 'sac' && !is_numeric($id)) {
    $propietario->esPropietarioLogueado();
    $session = $_SESSION;
}

switch ($accion) {
    
    case "ver":
    case "cambiar-clave":

        $datos_personales = $propietario->ver($session['usuario']['id']);
        
        $bitacora->insertar(
            [
                'descripcion' => $_SESSION['usuario']['nombre'],
                'id_accion'   => 3,
                'id_sesion'   => $session['id_sesion'],
            ]
        );
        $params = [
            'accion'      => $accion,
            'id'          => $id,
            'propietario' => $datos_personales['data'][0],
            'session'     => $session,
            'seccion'     => $accion = 'ver' ? 'Perfil' : 'Cambiar Contraseña',
            'icon'        => $accion = 'ver' ?'address-card': 'shield-check',
        ];
        
        echo $twig->render('enlinea/propietario/datos-personales.twig', $params);
        break; 

    case "modificar":
        $data = $_POST;
        unset($data['actualizar']);
        
        if ($_GET['id'] == 'perfil') {
            
            $exito = $propietario->actualizar($session['usuario']['id'], $data);
            $mensaje = $exito['suceed'] ? 
                "Ha actualizado sus datos de contacto con éxito!" :
                "Ha ocurrido un error interno al intentar actualizar su datos.";
            
            $bitacora->insertar([

                'descripcion'   => '',
                'id_accion'     => 14,
                'id_sesion'     => $session['id_sesion']

            ]);

        } else {

            $exito = $propietario->ver($session['usuario']['id']);
            
            if ($exito['suceed'] && count($exito['data']) > 0) {

                if ($exito['data'][0]['clave'] == $data['clave_actual']) {

                    unset($data['clave_actual']);
                    unset($data['clave_confirm']);
                    $data['modificado'] = -1;
                    $exito = $propietario->actualizar($session['usuario']['id'], $data);
                    
                    $mensaje = "Su cambio de contraseña se ha procesado con éxito!";
                    $bitacora->insertar([

                        'descripcion' => '',
                        'id_accion'   => 7,
                        'id_sesion'   => $session['id_sesion'],

                    ]);

                } else {
                    
                    $mensaje = "No hemos podido cambiar su contraseña. La contraseña actual no corresponde con la contraseña registrada";
                    $exito['suceed'] = false;

                }
            } else {

                $mensaje = "Ha ocurrido un error durante el procesamiento de su solicitud.";

            }
        
        }
        if ($exito['suceed']) {
            $exito['mensaje'] = $mensaje;
        } else {
            if ($mensaje == "") {

                $mensaje = "Ha ocurrido un error durante el procesamiento de su solicitud.";
            }
            $exito['mensaje'] = $mensaje;
        }
        unset($exito['query']);
        echo json_encode($exito);
        break;
    
    case "clavesActualizadas":
        $listado = $propietario->listarPropietariosClavesActualizadas();


        if ($listado['suceed'] && count($listado['data'] > 0)) {
            foreach ($listado['data'] as $clave) {


                $propietario->actualizar($clave["id"], Array("cambio_clave" => 0));


                echo $clave["id_inmueble"] . "|";
                echo $clave["apto"] . "|";
                echo $clave["clave"] . "[EOL]";
            
                
            }
        }
        break; 
       
    case "actualizados":
        $resultado = $propietario->obtenerPropietariosActualizados();
        if ($resultado['suceed'] && count($resultado['data']) > 0) {
            foreach ($resultado['data'] as $actualizado) {
                echo "|" . $actualizado['cedula'] . "|" . $actualizado['id_inmueble'];
                echo "|" . $actualizado['apto'] . "|" . $actualizado['clave'] . "|" . $actualizado['direccion'];
                echo "|" . $actualizado['telefono1'] . "|" . $actualizado['telefono2'];
                echo "|" . $actualizado['telefono3'] . "|" . $actualizado['email'] . "|" . $actualizado['email_alternativo']."[EOL]";
            }
        }
        break;
    

    case "clave-servicio":
        $propietario = new propietario();
        
        if (!is_numeric($id))
            $id = null;
        $propietario->envioMasivoEmail(NOMBRE_APLICACION, '../plantillas/clave-servicio.html', $id);
        break; 
}

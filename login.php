<?php

include_once 'includes/configuracion.php';

//echo $twig->render('mantenimiento.html.twig');
//die();

$propietario = new propietario(); 
$result = [];

$password = '';
$usuario  = '';

if (isset($_POST['usuario']) && isset($_POST['password'])) {

    $usuario  = $_POST['usuario'];
    $password = $_POST['password'];
    $result   = $propietario->login( $usuario, $password, 0);
    
    if ($result['suceed']=='true') {
        
        if ( $_SESSION['status'] == 'logueado' ) {

            $result['mensaje'] = 'Lo hemos validado correctamente. Lo estamos redirigiendo al área de propietarios.';
            
        }
        
    } else {
        
        $result['mensaje'] = $result['error'];
    }
    unset($result['data']);
    echo json_encode($result);

} else {
    if (isset($_POST['email'])) {

        $result = $propietario->recuperarContraSena($_POST['email']);

    }
}

//echo $twig->render('index.twig', ['mensaje' => $result, 'usuario' => $usuario,'password' => $password]);
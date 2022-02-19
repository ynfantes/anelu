<?php

require_once 'twig/lib/Twig/Autoloader.php';

date_default_timezone_set("America/La_Paz");

define('HOSTNAME', $_SERVER['SERVER_NAME']);

$produccion     = HOSTNAME == "[www.nombredominiio]" | HOSTNAME == "[nombredominio]";
$debug          = false;
$sistema        = "/";
$email_error    = true;
$mostrar_error  = true;
$protocolo      = 'http';
if ($produccion) {

    $user        = "[usuario]";
    $password    = "[password]";
    $db          = "[nombre base de datos]";
    $mostrar_error = false;
    $debug       = false;
    $protocolo   = '[http | https]';

} else {

    $user        = "[usuario]";
    $password    = "";
    $db          = "[base de datos]";

}

// acceso a la base de datos
define("HOST", "localhost");
define("USER", $user);
define("PASSWORD", $password);
define("DB", $db);

// configuracion ficheros del sistema
define("PROTOCOLO", $protocolo);
define("SISTEMA", $sistema);
define("EMAIL_ERROR", $email_error);
define("EMAIL_CONTACTO", "[email contacto desarrollador]");
define("EMAIL_TITULO", "error");
define("MOSTRAR_ERROR", $mostrar_error);
define("DEBUG", $debug);
define("PRODUCCION", $produccion);
define("TITULO", "[titulo aplicacion]");

define("ROOT", $protocolo.'://'.HOSTNAME.SISTEMA);
define("URL_SISTEMA", ROOT."enlinea/");
define("URL_INTRANET", ROOT."intranet/");
define("SERVER_ROOT", $_SERVER['DOCUMENT_ROOT'].SISTEMA);

define("TEMPLATE", SERVER_ROOT . 'template');

/****correo electronico****/
const MAIL_PHP = 0;
const SEND_MAIL = 1;
const SMTP = 2;
define("PROGRAMA_CORREO",SMTP);

/* Configuracion App y Sincronizacion */
define("NOMBRE_APLICACION","Anelu en Línea");
define("ACTUALIZ","data/");
define("ARCHIVO_INMUEBLE","INMUEBLE.txt");
define("ARCHIVO_CUENTAS","CUENTAS.txt");
define("ARCHIVO_FACTURA","FACTURA.txt");
define("ARCHIVO_FACTURA_DETALLE","FACTURA_DETALLE.txt");
define("ARCHIVO_JUNTA_CONDOMINIO","JUNTA_CONDOMINIO.txt");
define("ARCHIVO_PROPIEDADES","PROPIEDADES.txt");
define("ARCHIVO_PROPIETARIOS","PROPIETARIOS.txt");
define("ARCHIVO_EDO_CTA_INM","EDO_CUENTA_INMUEBLE.txt");
define("ARCHIVO_CUENTAS_DE_FONDO","CUENTAS_FONDO.txt");
define("ARCHIVO_MOVIMIENTOS_DE_FONDO","MOVIMIENTO_FONDO.txt");
define("ARCHIVO_ACTUALIZACION","ACTUALIZACION.txt");
define("SMTP_SERVER","mail.administradoranelu.com");
define("PORT",25);
define("USER_MAIL","[correo electronico]");
define("PASS_MAIL","[password correo electronico]");
define("MESES_COBRANZA",2);
define("GRAFICO_FACTURACION",1);
define("GRAFICO_COBRANZA",1);
define("DEMO",0);
define("MOVIMIENTO_FONDO",0);
define("CORREO_CONTACTO",'[correo contacto app]');

/* Configuracion Twig */
Twig_Autoloader::register();
include_once SERVER_ROOT.'includes/extensiones.php';
$loader = new Twig_Loader_Filesystem(TEMPLATE);

$twig = new Twig_Environment($loader, array(
    'debug'       => true,
    'cache'       => /* SERVER_ROOT.'/cache' */ false,
    'auto_reload' => true)
);

$tasa = json_decode(file_get_contents('[url api tasa de cambio]'));
$twig->addGlobal('tasa_bcv',$tasa);

if (isset($_SESSION)) {
    $twig->addGlobal("session", $_SESSION);    
}
$twig->addExtension(new extensiones());
$twig->addExtension(new Twig_Extension_Debug());


spl_autoload_register( function($class) {
    include_once SERVER_ROOT.'/includes/'.$class.'.php';
});


if (isset($_GET['logout']) && $_GET['logout'] == true) {
    $user_logout = new propietario();
    $user_logout->logout();
}

/*
<li class="nav-title">Junta de Condominio</li>
    
<li>

    <a href="{{constant('URL_SISTEMA')}}inmueble/junta-condominio" title="Integrantes Junta de Condominio" class=" " data-filter-tags="integrantes junta condominio">
        <i class="fal fa-users-class"></i>
        <span class="nav-link-text" data-i18n="nav.integrantes">Integrantes</span>
    </a>

</li>

*/
$MENU_SIDEBAR = [
    'section' => [
        'title' => '',
        'nav_key' => [[
            'display'       => true,
            'filter_tags'   => 'cartelera, tablero',
            'href'          => URL_SISTEMA,
            'icon'          => 'chalkboard',
            'tile'          => 'Tablero',
            'label'         => 'Tablero'
        ]]
    ],
    [ 
        'title'     => 'JUNTA DE CONDOMINIO',
        'nav_key'   => [[
            'display'     => true,
            'filter_tags' => 'junta de condominio, junta, integrantes',
            'href'        => URL_SISTEMA.'inmueble/junta-condominio',
            'icon'        => 'users-class',
            'label'       => 'Integrantes',
            'title'       => 'Integrantes Junta de Condominio',
        ],
        [
            'display'     => false,
            'filter_tags' => 'estado de cuenta general, estado de cuenta condominio, inmueble, condominio, junta',
            'href'        => URL_SISTEMA.'inmueble/estado-cuenta',
            'icon'        => 'abacus',
            'label'       => 'Estado de Cuenta General',
            'title'       => 'Estado de Cuenta General',
        ]]
    ],
    [
        'title'     => 'PROPIETARIO',
        'nav_key'   => [[
            'display'     => true,
            'filter_tags' => 'perfil, propietario, mis datos, datos de contacto',
            'href'        => URL_SISTEMA.'propietario/ver',
            'icon'        => 'address-card',
            'label'       => 'Datos de contacto',
            'title'       => 'Datos de contacto',
        ],
        [
            'display'     => true,
            'filter_tags' => 'cambiar contraseña, contraseña, password, clave',
            'href'        => URL_SISTEMA.'propietario/cambiar-clave',
            'icon'        => 'shield-check',
            'label'       => 'Cambiar Contraseña',
            'title'       => 'Cambiar Contraseña',
        ],
        [
            'display'     => true,
            'filter_tags' => 'estado de cuenta, recibos, recibos pendientes, por pagar',
            'href'        => URL_SISTEMA.'cuenta/estado-de-cuenta',
            'icon'        => 'clipboard-list',
            'label'       => 'Recibos Pendientes',
            'title'       => 'Recibos Pendientes',
            'ext'         => '<span class="dl-ref bg-danger-500 hidden-nav-function-minify hidden-nav-function-top">%s</span>',
        ],
        [
            'display'     => true,
            'filter_tags' => 'pago condominio, reportar pago, pagar',
            'href'        => URL_SISTEMA.'pago/registrar',
            'icon'        => 'money-bill-wave',
            'label'       => 'Reportar Pago Condominio',
            'title'       => 'Reportar Pago Condominio',
        ],
        [
            'display'     => true,
            'filter_tags' => 'recibos pagados, recibos cancelados',
            'href'        => URL_SISTEMA.'pago/historico',
            'icon'        => 'th-list',
            'label'       => 'Lista Recibos Pagados',
            'title'       => 'Lista Recibos Pagados',
        ],
        [
            'display'     => false,
            'filter_tags' => 'histórico operaciones, operaciones',
            'href'        => URL_SISTEMA,
            'icon'        => 'clipboard-list-check',
            'label'       => 'Histórico Operaciones',
            'title'       => 'Histórico Operaciones',
        ],
        [
            'display'     => true,
            'filter_tags' => 'cerrar sesion, cerrar',
            'href'        => URL_SISTEMA.'?logout=true',
            'icon'        => 'sign-out-alt',
            'label'       => 'Cerrar Sesión',
            'title'       => 'Cerrar Sesión',
        ]]
    ]
        
];
define("MENU_SIDEBAR",$MENU_SIDEBAR);
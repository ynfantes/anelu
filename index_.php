<?php

include_once 'includes/configuracion.php';

echo $twig->render('index.twig', ['nombre' => "edgar"]);
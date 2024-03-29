<?php

class prerecibo extends db implements crud {
    const tabla = "prerecibo";
    
    public function actualizar($id, $data) {
        return db::update(self::tabla, $data, array("id" => $id));
    }

    public function borrar($id) {
        return db::delete(self::tabla, array("id" => $id));
    }

    public function borrarTodo() {
        return db::delete(self::tabla);
    }

    public function insertar($data) {
        return db::insert(self::tabla, $data);
    }

    public function listar() {
        return db::select("*", self::tabla);
    }

    public function ver($id) {
        return db::select("*",self::tabla,array("id"=>$id));
    }
    
    public function listarPorInmueble($id_inmueble, $cant_reg=0) {
        
        $condition  = ["id_inmueble"=>"'{$id_inmueble}'"];
        $sort       = ["periodo"=>"DESC"];
        return db::select("*",self::tabla, $condition, '', $sort, '', $cant_reg);

    }
    
    public function prereciboYaRegistrado($id_inmueble, $periodo) {
        $condition = [ "id_inmueble" => "'{$id_inmueble}'", "periodo" => $periodo ];
        $resultado = db::select("*",self::tabla, $condition);
        return ($resultado['suceed'] && count($resultado['data'])>0);
    }
}
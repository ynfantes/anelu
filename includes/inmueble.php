<?php

/**
 * Clase que mantiene la tabla inmueble
 *
 * @autor   Edgar Messia
 * @static  
 * @package     Valoriza2.Framework
 * @subpackage	FileSystem
 * @since	1.0
 */

class inmueble extends db implements crud {

    const tabla = "inmueble";

    public function actualizar($id, $data){
        return db::update(self::tabla, $data, array("id" => $id));
    }

    public function borrar($id){
        return db::delete(self::tabla, array("id" => $id));
    }

    /**
     * Inserta el contenido en la tabla propietarios
     *
     * @param	Array	$data	Arreglo con la data
     * 
     * @return	Array	Retorna arreglo con parámetos del resultado
     * @since	1.0
     */
    public function insertar($data){
        return db::insert(self::tabla, $data);
    }

    public function listar(){
       return db::select("*", self::tabla);
    }
    
    public function ver($id){
        return db::select("*",self::tabla,array("id"=>"'$id'"));
    }

    public function borrarTodo() {
        return db::delete(self::tabla);
    }
    
    public function estadoDeCuenta($id) {
        return db::select("*","inmueble_deuda_confidencial",Array("id_inmueble"=>"'$id'"));
    }
    
    public function insertarEstadoDeCuentaInmueble($data) {
        return db::insert("inmueble_deuda_confidencial", $data,"IGNORE");
    }
    
    public function movimientoFacturacionMensual($inmueble) {
        $query = "select facturacion_mensual.*, inmueble.nombre_inmueble from facturacion_mensual join inmueble ON facturacion_mensual.id_inmueble = inmueble.id where id_inmueble='$inmueble' and periodo >= date_add((select max(periodo) from facturacion_mensual where id_inmueble='$inmueble'), INTERVAL -7 MONTH) order by periodo ASC";
        return db::query($query);
    }
    
    public function movimientoCobranzaMensual($inmueble) {
        $query = "select cobranza_mensual.*,inmueble.nombre_inmueble from cobranza_mensual join inmueble on cobranza_mensual.id_inmueble = inmueble.id where id_inmueble='$inmueble' and periodo >= date_add((select max(periodo) from facturacion_mensual where id_inmueble='$inmueble'), INTERVAL -7 MONTH) order by periodo ASC";
        return db::query($query);
    }
    
    public function insertarFacturacionMensual($data) {
        return db::insertUpdate('facturacion_mensual',$data, ['facturado' => $data['facturado']]);
    }
    
    public function insertarCobranzaMensual($data) {
        return db::insertUpdate('cobranza_mensual',$data, ['monto' => $data['monto']]);
    }
    
    public function listarInmueblesPorPropietario($cedula) {
        $consulta = "select * from inmueble i join propiedades p on i.id = p.id_inmueble where p.cedula=".$cedula;
        return db::query($consulta);
        
    }

    public function agregarCuentaInmueble($data) {
        return db::insertUpdate("inmueble_cuenta", $data, $data);
    }
    public function obtenerCuentasBancariasPorInmueble($inmueble) {
        return db::select("*","inmueble_cuenta",["id_inmueble" => "'$inmueble'"]);
    }
    
    public function listarCuentasBancariasPorInmuebleBanco($inmueble,$banco){
        return db::select("*","inmueble_cuenta",["id_inmueble"=>"'$inmueble'","banco"=>$banco]);
    }
    
    public function obtenerNombreBancoPorNumeroCuenta($inmueble,$numero_cuenta){
        return db::select("*","inmueble_cuenta",["id_inmueble"=>"'$inmueble'","numero_cuenta"=>$numero_cuenta]);
    }
    
    public function obtenerNumeroCuentaPorInmuebleBanco($inmueble,$banco) {
        return db::select("*","inmueble_cuenta",["id_inmueble"=>"'$inmueble'","banco"=>$banco]);
    }
    
    public function actualizarCuentaDeBanco($id,$data){
        return db::update(self::tabla, $data, ["id" => $id]);
    }
    
    public function verDatosInmueble($id){
        return db::select("*",self::tabla,["id"=>"'$id'"]);
    }
    
    public function listarBancosActivos(){
        return db::select("*","bancos",["inactivo"=>0],[],['nombre' => 'ASC']);
    }

    public function borrarGrupo($condicion) {
        db::delete('grupo_propietario',$condicion);
        return db::delete('grupo',$condicion);
    }

    public function insertarGrupo($data) {
        return db::insertUpdate("grupo", $data,['descripcion'=>$data['descripcion']]);
    }

    public function insertarGrupoPropietario($data) {
        return db::insert("grupo_propietario", $data,"IGNORE");
    }

    public function insertarActualizar($data) {
        $act = $data;
        unset($act['id']);
        return db::insertUpdate(self::tabla,$data,$act);
    }
    
    public function insertarActualizarEstadoDeCuentaInmueble($data) {
        $act = $data;
        unset($act['id_inmueble'],$act['apto']);
        return db::insertUpdate("inmueble_deuda_confidencial", $data,$act);
    }

    public function borrarCuentaBancaria($inmueble,$num_cuenta) {
        return db::delete('inmueble_cuenta',['id_inmueble'=>$inmueble, 'numero_cuenta'=>$num_cuenta]);
    }
}
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Pago extends CI_Model
{
    function __construct(){
        parent::__construct();
    }

    public function listarTodosCantidad (){
        /*$this->db->select('concepto');
        $this->db->from('pago');
        $this->db->group_by('concepto');*/
        $query = $this->db->query('SELECT concepto, COUNT(concepto) AS cantidad FROM pago GROUP BY pago.concepto');
        $data = $query->result_array();
        $array_out = array('labels'=>array(),'datasets'=>array());
        $dataset = array('label'=>'transacciones','data'=>array());
        foreach ($data as $concepto) {
            $array_out['labels'][] = $concepto['concepto'];
            $dataset['data'][] = $concepto['cantidad'];
        }
        $array_out['datasets'][] = $dataset;
        return $array_out;
    }

    public function listarTodosImporte (){
        $query = $this->db->query('SELECT concepto, SUM(importe) AS cantidad FROM pago GROUP BY pago.concepto');
        $data = $query->result_array();
        $array_out = array('labels'=>array(),'datasets'=>array());
        $dataset = array('label'=>'Importe','data'=>array());
        foreach ($data as $concepto) {
            $array_out['labels'][] = $concepto['concepto'];
            $dataset['data'][] = $concepto['cantidad'];
        }
        $array_out['datasets'][] = $dataset;
        return $array_out;
    }

    public function listarLeyendaDemanda (){
        $query = $this->db->query('SELECT sigla_programa, nom_programa FROM programa GROUP BY n_prioridad, sigla_programa, nom_programa ORDER BY n_prioridad, nom_programa;');
        $data = $query->result_array();
        return $data;
    }

    public function listarPorFechasCantidad($fecha_inicio, $fecha_fin, $conceptos){
        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }

        $query = $this->db->query("SELECT c.concepto AS concepto, COUNT(r.id_concepto) AS cantidad
        FROM public.recaudaciones r
        INNER JOIN public.concepto c ON (r.id_concepto = c.id_concepto)
        INNER JOIN public.clase_pagos p ON (p.id_clase_pagos = c.id_clase_pagos)
        WHERE (
            extract(epoch FROM r.fecha) >= ".$fecha_inicio."
            AND extract(epoch FROM r.fecha) <= ".$fecha_fin."
            AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
             ".$condicional."
        )
        GROUP BY r.id_concepto,c.concepto
        ORDER BY c.concepto");
        $data = $query->result_array();
        $array_out = $this->formatoGrafico($data,'Importes');
        return $array_out;
    }

    public function listarPorFechasImporte($fecha_inicio, $fecha_fin, $conceptos){
        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }

        $query = $this->db->query("SELECT c.concepto AS concepto, SUM(r.importe) AS cantidad
        FROM public.recaudaciones r
        INNER JOIN public.concepto c ON (r.id_concepto = c.id_concepto)
        INNER JOIN public.clase_pagos p ON (p.id_clase_pagos = c.id_clase_pagos)
        WHERE (
            extract(epoch FROM r.fecha) >= ".$fecha_inicio."
            AND extract(epoch FROM r.fecha) <= ".$fecha_fin."
            AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
             ".$condicional."
        )
        GROUP BY r.id_concepto,c.concepto
        ORDER BY c.concepto");
        $data = $query->result_array();
        $array_out = $this->formatoGrafico($data,'Monto');
        return $array_out;
    }

    public function listarAnioCantidad($year, $conceptos){
        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }

        $query = $this->db->query(
            "SELECT date_part('month',r.fecha) AS concepto,
                    COUNT(r.importe) AS cantidad
            FROM public.recaudaciones r
            INNER JOIN public.concepto c ON (r.id_concepto = c.id_concepto)
            INNER JOIN public.clase_pagos p ON (p.id_clase_pagos = c.id_clase_pagos)
            WHERE (
                date_part('year',fecha) = ".$year."
                AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
                 ".$condicional."
            )
            GROUP BY date_part('month',r.fecha)"
        );
        $data = $query->result_array();
        $array_out = $this->formatoGrafico($data,'Importes');
        $f_array_out = $this->formatoFecha($array_out);
        return $f_array_out;
    }
    public function test(){
        return "hola";
    }

    public function listarAnioImporte($year, $conceptos){
        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }

        $query = $this->db->query(
            "SELECT date_part('month',r.fecha) AS concepto,
                    SUM(importe) AS cantidad
            FROM public.recaudaciones r
            INNER JOIN public.concepto c ON (r.id_concepto = c.id_concepto)
            INNER JOIN public.clase_pagos p ON (p.id_clase_pagos = c.id_clase_pagos)
            WHERE (
                date_part('year',fecha) = ".$year."
                AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
                 ".$condicional."
            )
            GROUP BY date_part('month',r.fecha)"
        );
        $data = $query->result_array();
        $array_out = $this->formatoGrafico($data,'Monto');
        $f_array_out = $this->formatoFecha($array_out);
        return $f_array_out;
    }

    public function registrosPorFechas($fecha_inicio, $fecha_fin,$conceptos){
        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }
        $query = $this->db->query("SELECT c.concepto AS concepto, r.importe AS importe, trim(a.codigo) AS codigoAlumno, a.ape_nom AS nombreAlumno, to_char(r.fecha,'DD-MM-YYYY') AS fecha
            FROM public.recaudaciones r
                INNER JOIN public.concepto c
                    ON (r.id_concepto = c.id_concepto)
                INNER JOIN public.alumno a
                    ON (r.id_alum = a.id_alum)
                INNER JOIN public.clase_pagos p
                    ON (p.id_clase_pagos = c.id_clase_pagos)
            WHERE (
                extract(epoch FROM r.fecha) >= ".$fecha_inicio."
                AND extract(epoch FROM r.fecha) <= ".$fecha_fin."
                AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
                 ".$condicional."
            )
            ORDER BY to_char(r.fecha,'YYYY-MM-DD')");
        $data = $query->result_array();
        $array_out = $this->formatoTabla($data);
        return $array_out;
    }

    public function registrosPorAnio($yearStart, $yearEnd ,$conceptos){
        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }
        $query=$this->db->query("SELECT c.concepto AS concepto, r.importe AS importe, trim(a.codigo) AS codigoAlumno, a.ape_nom AS nombreAlumno, to_char(r.fecha,'DD-MM-YYYY') AS fecha
            FROM public.recaudaciones r
                INNER JOIN public.concepto c
                    ON (r.id_concepto = c.id_concepto)
                INNER JOIN public.alumno a
                    ON (r.id_alum = a.id_alum)
                INNER JOIN public.clase_pagos p
                    ON (p.id_clase_pagos = c.id_clase_pagos)
            WHERE (
                date_part('year',r.fecha) between ".$yearStart." and ".$yearEnd."
                AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
                 ".$condicional."
            )
            ORDER BY to_char(r.fecha,'YYYY-MM-DD')");
        $data = $query->result_array();
        $array_out = $this->formatoTabla($data);
        return $array_out;
    }
    public function registrosPorMes ($year,$startMonth,$endMonth, $conceptos){
        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }

        $query = $this->db->query(
        "SELECT c.concepto AS concepto,r.importe AS importe, trim(a.codigo) AS codigoAlumno, a.ape_nom AS nombreAlumno, to_char(r.fecha,'DD-MM-YYYY') AS fecha
        FROM public.recaudaciones r
        INNER JOIN public.concepto c ON (r.id_concepto = c.id_concepto)
        INNER JOIN public.clase_pagos p ON (p.id_clase_pagos = c.id_clase_pagos)
        INNER JOIN public.alumno a ON (r.id_alum = a.id_alum)
        WHERE (
            date_part('year',r.fecha) = ".$year."
            AND date_part('month',r.fecha) between ".$startMonth." and ".$endMonth."
            AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
             ".$condicional."
        )
        ORDER BY to_char(r.fecha,'YYYY-MM-DD')");

        $data = $query->result_array();
        $array_out = $this->formatoTabla($data);
        return $array_out;
    }

    //DE AÑO A OTRO A AÑO CANTIDAD/TOTAL
    public function listarCantidadPeriodoAnual($yearStart, $yearEnd, $conceptos){

        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }
        $query = $this->db->query(
        "SELECT date_part('year',r.fecha) AS concepto,COUNT(r.importe) AS cantidad
        FROM public.recaudaciones r
        INNER JOIN public.concepto c ON (r.id_concepto = c.id_concepto)
        INNER JOIN public.clase_pagos p ON (p.id_clase_pagos = c.id_clase_pagos)
        WHERE (
            date_part('year',r.fecha) between ".$yearStart." and ".$yearEnd."
            AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
             ".$condicional."
        )
        GROUP BY date_part('year',r.fecha);"
        );
        $data = $query->result_array();
        $array_out = $this->formatoGrafico($data,'Cantidad');
        return $array_out;

    }
    public function listarTotalPeriodoAnual($yearStart, $yearEnd, $conceptos){

        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }

        $query = $this->db->query(
        "SELECT date_part('year',r.fecha) AS concepto,SUM(r.importe) AS cantidad
        FROM public.recaudaciones r
        INNER JOIN public.concepto c ON (r.id_concepto = c.id_concepto)
        INNER JOIN public.clase_pagos p ON (p.id_clase_pagos = c.id_clase_pagos)
        WHERE (
            date_part('year',r.fecha) between ".$yearStart." and ".$yearEnd."
            AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
             ".$condicional."
        )
        GROUP BY date_part('year',r.fecha);"
        );
        $data = $query->result_array();
        $array_out = $this->formatoGrafico($data,'Monto');
        return $array_out;

    }

    //AÑO->mes inicial y fina
    public function listarCantidadPeriodoMensual($year,$startMonth,$endMonth, $conceptos){
        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }

        $query = $this->db->query(
        "SELECT date_part('month',r.fecha) AS concepto,
                COUNT(r.importe) AS cantidad
        FROM public.recaudaciones r
        INNER JOIN public.concepto c ON (r.id_concepto = c.id_concepto)
        INNER JOIN public.clase_pagos p ON (p.id_clase_pagos = c.id_clase_pagos)
        WHERE (
            date_part('year',r.fecha) = ".$year."
            AND date_part('month',r.fecha) between ".$startMonth." and ".$endMonth."
            AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
             ".$condicional."
        )
        GROUP BY date_part('month',r.fecha);"
        );
        $data = $query->result_array();
        $array_out = $this->formatoGrafico($data,'Cantidad');
        $f_array_out = $this->formatoFecha($array_out);
        return $f_array_out;

    }

    public function listarTotalPeriodoMensual($year,$startMonth,$endMonth, $conceptos){
        if (trim($conceptos) != ""){
            $condicional = "AND c.concepto::integer in (".str_replace ("|",",",$conceptos).")";
        }
        else{
            $condicional = "";
        }

        $query = $this->db->query(
        "SELECT date_part('month',r.fecha) AS concepto,
                SUM(r.importe) AS cantidad
        FROM public.recaudaciones r
        INNER JOIN public.concepto c ON (r.id_concepto = c.id_concepto)
        INNER JOIN public.clase_pagos p ON (p.id_clase_pagos = c.id_clase_pagos)
        WHERE (
            date_part('year',r.fecha) = ".$year."
            AND date_part('month',r.fecha) between ".$startMonth." and ".$endMonth."
            AND p.id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S')
             ".$condicional."
        )
        GROUP BY date_part('month',r.fecha);"
        );
        $data = $query->result_array();
        $array_out = $this->formatoGrafico($data,'Cantidad');
        $f_array_out = $this->formatoFecha($array_out);
        return $f_array_out;

    }

    public function listarConceptos(){
        $query = $this->db->query(
            "select concepto from public.concepto where id_clase_pagos in (SELECT distinct (id_clase_pagos) FROM configuracion where estado = 'S' )"
        );
        $data = $query->result_array();
        return $this->formatoConceptos($data);
    }

    private function formatoGrafico($data,$etiqueta){
        $array_out = array('labels'=>array(),'datasets'=>array());
        $dataset = array('label'=>$etiqueta,'data'=>array());
        if(count($data)>0){
            foreach ($data as $concepto) {

                $array_out['labels'][] = $concepto['concepto'];
                $dataset['data'][] = $concepto['cantidad'];
            }

        }
        $array_out['datasets'][] = $dataset;
        return $array_out;
    }

    private function formatoTabla($data){
        $array_out = array();
        if(count($data)>0){
            foreach ($data as $registro) {
                $array_out[] = $registro;
            }
        }
        return $array_out;
    }

    private function formatoFecha($data){
        if(count($data)>0){
            foreach($data["labels"] as $clave => $mes){
                if($mes == 1){
                    $data["labels"][$clave] = "Enero";
                } elseif($mes == 2){
                    $data["labels"][$clave] = "Febrero";
                }elseif($mes == 3){
                    $data["labels"][$clave] = "Marzo";
                }elseif($mes == 4){
                    $data["labels"][$clave] = "Abril";
                }elseif($mes == 5){
                    $data["labels"][$clave] = "Mayo";
                }elseif($mes == 6){
                    $data["labels"][$clave] = "Junio";
                }elseif($mes == 7){
                    $data["labels"][$clave] = "Julio";
                }elseif($mes == 8){
                    $data["labels"][$clave] = "Agosto";
                }elseif($mes == 9){
                    $data["labels"][$clave] = "Septiembre";
                }elseif($mes == 10){
                    $data["labels"][$clave] = "Octubre";
                }elseif($mes == 11){
                    $data["labels"][$clave] = "Noviembre";
                }elseif($mes == 12){
                    $data["labels"][$clave] = "Diciembre";
                }
            }
        }
        return $data;
    }

    private function formatoConceptos($data){
        $array_out = array("conceptos"=>array());
        if(count($data)>0){
            foreach ($data as $concepto) {
                $array_out['conceptos'][] = $concepto['concepto'];
            }
        }
        return $array_out;
    }

    public function listarBeneficio($fecha_inicio, $fecha_fin) {
        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;

        $query = $this->db->query("SELECT alumno_programa_beneficio.id_beneficio as id ,tipo,extract(year from fecha), COUNT(cod_alumno) FROM alumno_programa_beneficio INNER JOIN beneficio ON beneficio.id_beneficio=alumno_programa_beneficio.id_beneficio WHERE extract(year from fecha)>='".$fecha_inicio."' AND extract(year from fecha)<='".$fecha_fin."' GROUP BY alumno_programa_beneficio.id_beneficio,tipo,extract(year from fecha) ORDER BY alumno_programa_beneficio.id_beneficio,tipo,extract(year from fecha)"
        );
        $data = $query->result_array();
        if(count($data)>0){

            $tipo = $data[0]["tipo"];
            $id = $data[0]["id"];
            $resultado = [];
            for($i=$fecha_inicio;$i<=$fecha_fin;$i++){
                $nuevoArray["$i"] = 0;
                
            }
            foreach ($data as $key =>$linea ) {
                if($tipo!=$linea["tipo"]){
                    $resultado[] = array("tipo"=>$tipo,"anios"=>$nuevoArray,"id"=>$id);
                    for($i=$fecha_inicio;$i<=$fecha_fin;$i++){
                        $nuevoArray["$i"] = 0;
                    }
                    $tipo=$linea["tipo"];
                    $id = $linea["id"];

                }
                $nuevoArray["".$linea["date_part"]] = $linea["count"];
            }
            $resultado[] = array("tipo"=>$tipo,"anios"=>$nuevoArray,"id"=>$id);
            return $resultado;
        }
        
        return $data;
    }

    public function listarBeneficioGrafica($fecha_inicio, $fecha_fin){
        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;

        $query = $this->db->query("SELECT extract(year from fecha),tipo, COUNT(cod_alumno) FROM alumno_programa_beneficio INNER JOIN beneficio ON beneficio.id_beneficio=alumno_programa_beneficio.id_beneficio WHERE extract(year from fecha)>='".$fecha_inicio."' AND extract(year from fecha)<='".$fecha_fin."' GROUP BY extract(year from fecha),tipo ORDER BY extract(year from fecha),tipo"
        );
        $data = $query->result_array();

        $query2 = $this->db->query("SELECT tipo from beneficio");
        $data2 = $query2->result_array();        

        $datoAnio = array();

        foreach($data2 as $fila){
            $datoAnio[$fila['tipo']] = 0;
        }        

        $respuesta = array();
        if($data){
            $anio = $data[0]['date_part'];
        
            foreach($data as $fila){
                if($fila['date_part']!=$anio){
                    $datoFila = array();
                    foreach($datoAnio as $nombre => $filaAnio){
                        $datoFila[] = array("label"=>strtoupper(substr($nombre,0,3)),"y"=>$filaAnio);
                    }
                    $respuesta[]=array("type"=>'column',"anio"=>$anio,"dataPoints"=>$datoFila);
                    foreach($data2 as $extra){
                        $datoAnio[$extra['tipo']] = 0;
                    }
                    $anio = $fila['date_part'];
                }
                $datoAnio[$fila['tipo']]=(int)$fila['count'];
            }

            $datoFila = array();
            foreach($datoAnio as $nombre => $filaAnio){
                $datoFila[] = array("label"=>strtoupper(substr($nombre,0,3)),"y"=>$filaAnio);
            }
            $respuesta[]=array("type"=>'column',"anio"=>$anio,"dataPoints"=>$datoFila);
            foreach($data2 as $extra){
                $datoAnio[$extra['tipo']] = 0;
            }
        }
        
        return $respuesta;
    }

    public function listarBeneficioExtendido($beneficado_id, $fecha_inicio, $fecha_fin) {
        $beneficado_id = (int)$beneficado_id;
        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;

        $query = $this->db->query("SELECT tipo, alumno_programa.cod_alumno, ape_paterno, ape_materno, nom_alumno, dni_m, correo, correo_personal, telefono_movil FROM alumno_programa_beneficio INNER JOIN beneficio ON beneficio.id_beneficio=alumno_programa_beneficio.id_beneficio INNER JOIN alumno_programa ON alumno_programa.cod_alumno=alumno_programa_beneficio.cod_alumno WHERE beneficio.id_beneficio='".$beneficado_id."' AND extract(year from fecha)>='".$fecha_inicio."' AND extract(year from fecha)<='".$fecha_fin."' GROUP BY tipo, alumno_programa.cod_alumno, ape_paterno, ape_materno, nom_alumno, dni_m, correo, correo_personal, telefono_movil ORDER BY tipo "
        );
        $data = $query->result_array();
        return $data;
    }

    public function listarEstadoAlumno($fecha_inicio, $fecha_fin) { 
        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;
        $query = $this->db->query("SELECT ecivil_desc as tipo, substring(anio_ingreso,1,4) as anio_ingreso,COUNT(cod_alumno) 
        FROM alumno_programa INNER JOIN estado_civil ON alumno_programa.ecivil_id=estado_civil.ecivil_id 
        WHERE (anio_ingreso!='null')  AND substring(anio_ingreso,1,4)>='".$fecha_inicio."' 
        AND substring(anio_ingreso,1,4)<= '".$fecha_fin."' GROUP BY ecivil_desc, anio_ingreso 
        ORDER BY ecivil_desc, substring(anio_ingreso,1,4)");
        $data = $query->result_array();

        if(count($data)>0){

            $tipo = $data[0]["tipo"];
            $resultado = [];
            for($i=$fecha_inicio;$i<=$fecha_fin;$i++){
                $nuevoArray["$i"] = 0;
                
            }
            foreach ($data as $key =>$linea ) {
                if($tipo!=$linea["tipo"]){
                    $resultado[] = array("tipo"=>$tipo,"anios"=>$nuevoArray);
                    for($i=$fecha_inicio;$i<=$fecha_fin;$i++){
                        $nuevoArray["$i"] = 0;
                    }
                    $tipo=$linea["tipo"];

                }
                $nuevoArray["".$linea["anio_ingreso"]] = $linea["count"];
            }
            $resultado[] = array("tipo"=>$tipo,"anios"=>$nuevoArray);
            return $resultado;
        }
        
        return $data;

    }

    public function listarEstadoAlumnoGrafica($fecha_inicio, $fecha_fin){
        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;

        $query = $this->db->query(
        "SELECT  substring(anio_ingreso,1,4) as anio_ingreso,
        ecivil_desc as tipo,COUNT(cod_alumno) 
        FROM alumno_programa INNER JOIN estado_civil 
        ON alumno_programa.ecivil_id=estado_civil.ecivil_id 
        WHERE (anio_ingreso!='null') 
        AND substring(anio_ingreso,1,4)>='".$fecha_inicio."' 
        AND substring(anio_ingreso,1,4)<= '".$fecha_fin."' 
        GROUP BY ecivil_desc, substring(anio_ingreso,1,4) 
        ORDER BY  substring(anio_ingreso,1,4),ecivil_desc");
        $data = $query->result_array();

        $query2 = $this->db->query("SELECT ecivil_desc as tipo from estado_civil");
        $data2 = $query2->result_array();        

        $datoAnio = array();

        foreach($data2 as $fila){
            $datoAnio[$fila['tipo']] = 0;
        }        

        $respuesta = array();
        if($data){
            $anio = $data[0]['anio_ingreso'];
        
            foreach($data as $fila){
                if($fila['anio_ingreso']!=$anio){
                    $datoFila = array();
                    foreach($datoAnio as $nombre => $filaAnio){
                        $datoFila[] = array("label"=>strtoupper(substr($nombre,0,3)),"y"=>$filaAnio);
                    }
                    $respuesta[]=array("type"=>'column',"anio"=>$anio,"dataPoints"=>$datoFila);
                    foreach($data2 as $extra){
                        $datoAnio[$extra['tipo']] = 0;
                    }
                    $anio = $fila['anio_ingreso'];
                }
                $datoAnio[$fila['tipo']]=(int)$fila['count'];
            }

            $datoFila = array();
            foreach($datoAnio as $nombre => $filaAnio){
                $datoFila[] = array("label"=>strtoupper(substr($nombre,0,3)),"y"=>$filaAnio);
            }
            $respuesta[]=array("type"=>'column',"anio"=>$anio,"dataPoints"=>$datoFila);
            foreach($data2 as $extra){
                $datoAnio[$extra['tipo']] = 0;
            }
        }
        
        return $respuesta;
    }

    public function listarEstadoAlumnoFallecido($fecha_inicio, $fecha_fin) {
        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;

        $query = $this->db->query(
            "SELECT substring(anio_ingreso,1,4) 
            as anio_ingreso, alumno_programa.cod_alumno, 
            ape_paterno, ape_materno, nom_alumno, dni_m, correo, 
            correo_personal, telefono_movil FROM alumno_programa 
            INNER JOIN estado_civil 
            ON alumno_programa.ecivil_id=estado_civil.ecivil_id
            WHERE estado_civil.ecivil_id='4' 
            AND substring(anio_ingreso,1,4)>='".$fecha_inicio."' 
            AND substring(anio_ingreso,1,4)<='".$fecha_fin."' "
        );
        $data = $query->result_array();
        return $data;
    }

    public function listarProgramaXAnios($fecha_inicio, $fecha_fin){
        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;

        $query = $this->db->query(
        "SELECT substring(anio_ingreso,1,4) 
        as anio_ingreso,programa.sigla_programa, 
        count(*) FROM alumno_programa inner join programa 
        on alumno_programa.id_programa=programa.id_programa 
        WHERE substring(anio_ingreso,1,4)>='".$fecha_inicio."' 
        AND substring(anio_ingreso,1,4)<='".$fecha_fin."' 
        GROUP BY substring(anio_ingreso,1,4),programa.id_programa 
        ORDER BY substring(anio_ingreso,1,4),programa.id_programa;"
        );
        $data = $query->result_array();

        $query2 = $this->db->query("SELECT sigla_programa FROM programa");
        $data2 = $query2->result_array();
        

        $datoAnio = array();

        foreach($data2 as $fila){
            $datoAnio[$fila['sigla_programa']] = 0;
        }        

        $respuesta = array();
        if($data){
            $anio = $data[0]['anio_ingreso'];
        
            foreach($data as $fila){
                if($fila['anio_ingreso']!=$anio){
                    $datoFila = array();
                    foreach($datoAnio as $nombre => $filaAnio){
                        $datoFila[] = array("label"=>$nombre,"y"=>$filaAnio);
                    }
                    $respuesta[]=array("type"=>'column',"dataPoints"=>$datoFila);
                    foreach($data2 as $extra){
                        $datoAnio[$extra['sigla_programa']] = 0;
                    }
                    $anio = $fila['anio_ingreso'];
                }
                $datoAnio[$fila['sigla_programa']]=(int)$fila['count'];
            }

            $datoFila = array();
            foreach($datoAnio as $nombre => $filaAnio){
                $datoFila[] = array("label"=>$nombre,"y"=>$filaAnio);
            }
            $respuesta[]=array("type"=>'column',"dataPoints"=>$datoFila);
            foreach($data2 as $extra){
                $datoAnio[$extra['sigla_programa']] = 0;
            }

            // $array_out = array("conceptos"=>array());
            // if(count($data)>0){
            //     foreach ($data as $concepto) {
            //         $array_out['conceptos'][] = $concepto['sigla_programa'];
            //     }
            // }
        }
        
        return $respuesta;
        //return $this->formatoConceptos($data);
    }

    public function listarDemandaSocial($fecha_inicio, $fecha_fin){

        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;
        
      /*  $query = $this->db->query("SELECT programa.sigla_programa, 
      anio_ingreso, 
      COUNT(cod_alumno) as cantidad 
      FROM alumno_programa INNER JOIN programa 
      ON programa.id_programa = alumno_programa.id_programa 
      WHERE CHAR_LENGTH(anio_ingreso) <=4 AND anio_ingreso>='".$fecha_inicio."' 
      AND anio_ingreso<='".$fecha_fin."' 
      GROUP BY anio_ingreso, programa.sigla_programa 
      ORDER BY programa.sigla_programa,anio_ingreso"
        );
*/

        $query=$this->db->query(
            "SELECT programa.sigla_programa,
            substring(anio_ingreso,1,4) as anio_ingreso,
            COUNT(cod_alumno) as cantidad
            FROM alumno_programa INNER JOIN programa
            ON programa.id_programa = alumno_programa.id_programa
            WHERE substring(anio_ingreso,1,4)>='".$fecha_inicio."'
            AND substring(anio_ingreso,1,4)<='".$fecha_fin."'
            GROUP BY substring(anio_ingreso,1,4), programa.sigla_programa
            ORDER BY programa.sigla_programa,substring(anio_ingreso,1,4)"
        );


        $data = $query->result_array();
        $programaArray = [];

        if($data){
            $nombre = $data[0]["sigla_programa"];
            

            for($i=$fecha_inicio;$i<=$fecha_fin;$i++){
                $nuevoArray["$i"] = 0;
                
            }

            foreach($data as $fila){
                if($fila["sigla_programa"] == $nombre){

                    $nuevoArray[$fila["anio_ingreso"]] = (int)$fila["cantidad"];
                    
                }else{
                    $programaArray[$nombre] = $nuevoArray;
                    for($i=$fecha_inicio;$i<=$fecha_fin;$i++){
                        $nuevoArray["$i"] = 0;
                    }
                    $nombre = $fila["sigla_programa"];
                    $nuevoArray[$fila["anio_ingreso"]] = (int)$fila["cantidad"];
                }
            }
            $programaArray[$nombre] = $nuevoArray;
        }
        

        return $programaArray;                

   }

   public function listarRelacionAlumnos($fecha_inicio, $fecha_fin){
        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;

        $query = $this->db->query(
        "SELECT n_prioridad, count(*) 
        from alumno_programa INNER JOIN programa 
        ON programa.id_programa = alumno_programa.id_programa 
        AND substring(anio_ingreso,1,4)>='".$fecha_inicio."' 
        AND substring(anio_ingreso,1,4)<='".$fecha_fin."'  
        GROUP BY(n_prioridad) ORDER BY(n_prioridad)");

        $data = $query->result_array();
        $arreglo = [];
        
        $query2 = $this->db->query("SELECT n_prioridad, sigla_programa from programa");
        $data2 = $query2->result_array();
        if($data2){
            foreach($data2 as $fila2){
                $arreglo[$fila2["n_prioridad"]]=$fila2["sigla_programa"];
            }
        }
        $resultado = array();
        if($data){
            foreach($data as $fila){
                $datos = [];
                $datos["cod_perm"] = $arreglo[$fila["n_prioridad"]];
                $datos["count"] = $fila["count"];
                array_push($resultado,$datos);
            }
        }
        return $resultado;
   }

   
   public function listarPoblacionEstudiantil($fecha_inicio, $fecha_fin){
        $fecha_inicio = (int)$fecha_inicio;
        $fecha_fin = (int)$fecha_fin;

        $query = $this->db->query(
        "SELECT substring(anio_ingreso,1,4),
        count(*) from alumno_programa 
        WHERE substring(anio_ingreso,1,4)>='".$fecha_inicio."' 
        AND substring(anio_ingreso,1,4)<='".$fecha_fin."' 
        GROUP BY(substring(anio_ingreso,1,4)) 
        ORDER BY(substring(anio_ingreso,1,4))");
        $data = $query->result_array();
        return $data;
   }
   public function listarPoblacionDocente(){
    //SELECT cod_perm, count(*) from alumno_programa GROUP BY(cod_perm);
        $query = $this->db->query("SELECT EXTRACT(YEAR FROM TO_DATE(fech_ingreso,'%d/%m/%Y')),count(*) from docente where fech_ingreso IS NOT NULL AND fech_ingreso !='a numa' GROUP BY(EXTRACT(YEAR FROM TO_DATE(fech_ingreso,'%d/%m/%Y'))) ORDER BY(EXTRACT(YEAR FROM TO_DATE(fech_ingreso,'%d/%m/%Y')))");
        $data = $query->result_array();
        return $data;
   }
   public function listarProgramaAlumnos($fecha_inicio, $fecha_fin){
        $query = $this->db->query(
        "SELECT programa.sigla_programa, cod_perm, 
        substring(anio_ingreso,1,4) as anio_ingreso, 
        COUNT(cod_alumno) as cantidad 
        FROM alumno_programa INNER JOIN programa 
        ON programa.id_programa = alumno_programa.id_programa 
        WHERE  substring(anio_ingreso,1,4)>='".$fecha_inicio."' 
        AND substring(anio_ingreso,1,4)<='".$fecha_fin."'
        GROUP BY substring(anio_ingreso,1,4), cod_perm, 
        programa.sigla_programa, n_prioridad 
        ORDER BY n_prioridad, cod_perm, substring(anio_ingreso,1,4);");
        $data = $query->result_array();

        $contador = 1;
        $resultado=[];
        if($data){
            $resultado = array();
            $nombre = $data[0]['sigla_programa'];
            $arregloTipo = array();
            $tipo = $data[0]['cod_perm'];

            $arregloAnio = array();
            for($i=(int)$fecha_inicio;$i<=(int)$fecha_fin;$i++){
                $arregloAnio[''.$i] = 0;
            }
            $anio = $data[0]['anio_ingreso'];

            $cantidad =$data[0]['cantidad'];

            
            foreach($data as $fila){

                if($anio!=$fila['anio_ingreso'] || $tipo!=$fila['cod_perm'] || $fila["sigla_programa"] != $nombre){
                    $contador++;
                    $arregloAnio[$anio] = (int)$cantidad;
                    $anio = $fila['anio_ingreso'];
                    $cantidad = $fila['cantidad'];
                }

                if($tipo!=$fila['cod_perm'] || $fila["sigla_programa"] != $nombre){
                    $arregloTipo[$tipo] = $arregloAnio;
                    $arregloAnio = array();
                    for($i=(int)$fecha_inicio;$i<=(int)$fecha_fin;$i++){
                        $arregloAnio[''.$i] = 0;
                    }

                    $tipo = $fila['cod_perm'];
                }

                if($fila["sigla_programa"] != $nombre){
                    $resultado[$nombre] = $arregloTipo;
                    $arregloTipo = array();

                    $nombre = $fila["sigla_programa"];
                }              
                
            }
            $arregloAnio[$anio] = (int)$cantidad;
            $arregloTipo[$tipo] = $arregloAnio;
            $resultado[$nombre] = $arregloTipo;
        }
        return $resultado;
        return array('contador'=>$contador);
        return $data;
   }
   public function listarProgramaAlumnosInverso($fecha_inicio, $fecha_fin){
        $query = $this->db->query(
        "SELECT substring(anio_ingreso,1,4)as anio_ingreso , 
        cod_perm, programa.sigla_programa, 
        COUNT(cod_alumno) as cantidad 
        FROM alumno_programa INNER JOIN programa 
        ON programa.id_programa = alumno_programa.id_programa 
        WHERE substring(anio_ingreso,1,4)>='".$fecha_inicio."' 
        AND substring(anio_ingreso,1,4)<='".$fecha_fin."' 
        GROUP BY substring(anio_ingreso,1,4), cod_perm, 
        programa.sigla_programa, n_prioridad 
        ORDER BY substring(anio_ingreso,1,4),  cod_perm ,n_prioridad  ;");
        $data = $query->result_array();

        $contador = 1;
        $resultado=[];
        if($data){
            $resultado = array();
            $nombre = $data[0]['sigla_programa'];
            $arregloTipo = array();
            $tipo = $data[0]['cod_perm'];

            $arregloNombre = array();
            
            $anio = $data[0]['anio_ingreso'];

            $cantidad =$data[0]['cantidad'];

            
            foreach($data as $fila){

                if($nombre!=$fila['sigla_programa'] || $tipo!=$fila['cod_perm'] || $fila["anio_ingreso"] != $anio){
                    $arregloNombre[$nombre] = (int)$cantidad;
                    $nombre = $fila['sigla_programa'];
                    $cantidad = $fila['cantidad'];
                }

                if($tipo!=$fila['cod_perm'] || $fila["anio_ingreso"] != $anio){
                    $arregloTipo[$tipo] = $arregloNombre;
                    $arregloNombre = array();

                    $tipo = $fila['cod_perm'];
                }

                if($fila["anio_ingreso"] != $anio){
                    $resultado[$anio] = $arregloTipo;
                    $arregloTipo = array();

                    $anio = $fila['anio_ingreso'];
                }              
                
            }
            $arregloNombre[$nombre] = (int)$cantidad;
            $arregloTipo[$tipo] = $arregloNombre;
            $resultado[$anio] = $arregloTipo;
        }
        return $resultado;
        return array('contador'=>$contador);
        return $data;
   }


   public function listarDemandaInversa($fecha_inicio, $fecha_fin){
        // $query = $this->db->query("SELECT anio_ingreso , cod_perm, programa.sigla_programa, COUNT(cod_alumno) as cantidad FROM alumno_programa INNER JOIN programa ON programa.id_programa = alumno_programa.id_programa WHERE CHAR_LENGTH(anio_ingreso) <=4 AND anio_ingreso>='".$fecha_inicio."' AND anio_ingreso<='".$fecha_fin."' GROUP BY anio_ingreso, cod_perm, programa.sigla_programa, n_prioridad ORDER BY anio_ingreso,  cod_perm ,n_prioridad  ;");
        $query = $this->db->query(
            "SELECT  'total' as anio_ingreso ,cod_perm, 
            programa.sigla_programa, 
            COUNT(cod_alumno) as cantidad FROM alumno_programa 
            INNER JOIN programa 
            ON programa.id_programa = alumno_programa.id_programa 
            WHERE substring(anio_ingreso,1,4)>='".$fecha_inicio."' 
            AND substring(anio_ingreso,1,4)<='".$fecha_fin."' 
            GROUP BY  cod_perm, programa.sigla_programa, n_prioridad 
            ORDER BY   cod_perm ,n_prioridad  ;");
        $data = $query->result_array();

        $contador = 1;
        $resultado=[];
        if($data){
            $resultado = array();
            $nombre = $data[0]['sigla_programa'];
            $arregloTipo = array();
            $tipo = $data[0]['cod_perm'];

            $arregloNombre = array();
            
            $anio = $data[0]['anio_ingreso'];

            $cantidad =$data[0]['cantidad'];

            
            foreach($data as $fila){

                if($nombre!=$fila['sigla_programa'] || $tipo!=$fila['cod_perm'] || $fila["anio_ingreso"] != $anio){
                    $arregloNombre[$nombre] = (int)$cantidad;
                    $nombre = $fila['sigla_programa'];
                    $cantidad = $fila['cantidad'];
                }

                if($tipo!=$fila['cod_perm'] || $fila["anio_ingreso"] != $anio){
                    $arregloTipo[$tipo] = $arregloNombre;
                    $arregloNombre = array();

                    $tipo = $fila['cod_perm'];
                }

                if($fila["anio_ingreso"] != $anio){
                    $resultado[$anio] = $arregloTipo;
                    $arregloTipo = array();

                    $anio = $fila['anio_ingreso'];
                }              
                
            }
            $arregloNombre[$nombre] = (int)$cantidad;
            $arregloTipo[$tipo] = $arregloNombre;
            $resultado[$anio] = $arregloTipo;
        }
        return $resultado;
        return array('contador'=>$contador);
        return $data;
   }

}



 ?>

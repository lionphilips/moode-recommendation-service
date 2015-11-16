<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        //checa se existe sessão ativa
        if(!$this->session->userdata('auth')){ redirect("login"); }    
    }

    public function index()
    {
        echo "oi";
    }

    public function return_all_courses()
    {
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA
        $db1->select('pmk_int_curso as id, cur_str_nome as nome');
        $db1->where('fok_int_ava', 1);

        $query = $db1->get('tbl_curso');
        if($query->num_rows() > 0) {
            echo json_encode($query->result());
        } else {
            echo json_encode(false);
        }
    }

    public function return_all_categories($id)
    {
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA

        $db1->select('pmk_int_disciplina as id, dis_str_nome as nome');
        $db1->where('fok_int_curso', $id);

        $query = $db1->get('tbl_disciplina');
        if($query->num_rows() > 0) {
            echo json_encode($query->result());
        } else {
            echo json_encode(false);
        }
    }

    public function return_all_documents($id, $status)
    {
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA

        $db1->select('pmk_int_documento as id, doc_str_titulo as titulo, doc_str_titulo_classificacao as titulo_classificacao, ava_str_nome as avaliacao');
        $db1->join('tbl_avaliacao', 'tbl_documento.fok_int_avaliacao=tbl_avaliacao.pmk_int_avaliacao', 'left');
        $db1->where('doc_str_type', 'file');
        $db1->where('tbl_documento.fok_int_disciplina', $id);
        $db1->where('doc_bool_classificado', $status);
        $db1->order_by('pmk_int_documento', 'desc');

        $query = $db1->get('tbl_documento');
        if($query->num_rows() > 0) {
            echo json_encode($query->result());
        } else {
            echo json_encode(false);
        }
    }

    public function return_document_info($id)
    {
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA

        $db1->select('pmk_int_documento as id, doc_str_titulo as titulo, doc_str_titulo_classificacao as titulo_classificacao, fok_int_avaliacao as avaliacao, doc_str_path as path');
        $db1->where('doc_str_type', 'file');
        $db1->where('pmk_int_documento', $id);
    
        $query = $db1->get('tbl_documento');
        if($query->num_rows() > 0) {
            echo json_encode($query->result());
        } else {
            echo json_encode(false);
        }
    }

    public function return_assignments_course($id)
    {
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA

        $db1->select('pmk_int_avaliacao as id, ava_str_nome as nome');
        $db1->where('fok_int_disciplina', $id);
        $db1->order_by('ava_str_nome');

        $query = $db1->get('tbl_avaliacao');
        if($query->num_rows() > 0) {
            echo json_encode($query->result());
        } else {
            echo json_encode(false);
        }
    }

    public function save_document_classification($id)
    {
        $this->load->helper('security');
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA

        $avaliacao = intval(anti_injection($this->input->post('avaliacao')));
        $str_titulo = anti_injection($this->input->post('str_titulo'));

        if($avaliacao > 0 && !empty($str_titulo))
        {
            $campos = array(
                'fok_int_avaliacao' => $avaliacao,
                'doc_str_titulo_classificacao' => $str_titulo,
                'doc_bool_classificado' => 1
            );
            
            //salva dados
            $db1->where('pmk_int_documento', $id);
            if($db1->update('tbl_documento', $campos)) {
                echo "true";
            } else {
                echo "false";
            }
        } else {
            echo "false";
        }
    }
}
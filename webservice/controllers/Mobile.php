<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Mobile extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function auth()
    {
        $this->load->helper('security');
        
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA
        
        /* apenas requisições POST serão aceitas */
        if($this->input->post())
        {
            $email = anti_injection($this->input->post('email'));
            $senha = anti_injection($this->input->post('senha'));
            
            $db1->select('pmk_int_usuario AS id, usu_str_nome AS nome, usu_str_email AS email');
            $db1->where('usu_str_email', $email);
            $db1->where('usu_str_senha', sha1($senha));
            $db1->where('usu_bol_ativo', 1);
            $query = $db1->get('tbl_usuario', 1);
            
            if($query->num_rows() > 0)
            {    
                //cria sessão no servidor
                $this->session->set_userdata($query->row());    
                echo json_encode($query->row());
            } 
            else
            {
                echo "false";
            }
        }
        else
        {
            echo "<h1>Access forbiden</h1>";
        }
    }
    
    public function cadastro()
    {
        $this->load->helper('security');
        
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA
        
        $nome = anti_injection($this->input->post('nome'));
        $email = anti_injection($this->input->post('email'));
        $senha = anti_injection($this->input->post('senha'));
        $senha2 = anti_injection($this->input->post('senha2'));
        
        $db1->where('usu_str_email', $email);
        $query = $db1->get('tbl_usuario', 1);
        
        if($query->num_rows() == 1)
        {
            $hash = sha1('usu_str_email'. microtime(true));
            
            $campos = array(
                'usu_str_nome' => $nome,
                'usu_str_senha' => sha1($senha),
                'usu_str_hash_cadastro' => $hash
            );
            
            $db1->where('usu_str_email', $email);
            if($db1->update('tbl_usuario', $campos)){
                
                $this->Mail($email, "Cadastro U-AVA", 
                        "<h1>Você se cadastrou em U-AVA Aluno!</h1><br /><br />"
                        . "Para confirmar o cadastro, acesse este <a href=\"http://lionphilips.tk/CI/index.php/mobile/confirm/".$hash."\">link</a>");
                
                echo "true";
            } else {
                echo "false";
            }
        }
        else
        {
            echo "Email não encontrado. Você deve fornecer o mesmo email utilizado no AVA";
        }
    }
    
    public function notificacoes($id)
    {
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA
        
        $db1->select('dis_str_nome as disciplina, ava_str_nome as avaliacao, not_str_conteudo as content');
        $db1->join('tbl_avaliacao', 'tbl_notificacao_mobile.fok_int_avaliacao=tbl_avaliacao.pmk_int_avaliacao', 'left');
        $db1->join('tbl_disciplina', 'tbl_avaliacao.fok_int_disciplina=tbl_disciplina.pmk_int_disciplina');
        $db1->where('fok_int_usuario', $id);
        $db1->order_by('pmk_int_notificacao_mobile', 'DESC');
        $db1->distinct();
        
        $query = $db1->get('tbl_notificacao_mobile');
        echo json_encode($query->result());
    }
    
    public function confirm($hash)
    {
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA
        
        $db1->where('usu_str_hash_cadastro', $hash);
        $query = $db1->get('tbl_usuario');
        
        if($query->num_rows() == 1)
        {
            $db1->where('usu_str_hash_cadastro', $hash);
            if($db1->update('tbl_usuario', array('usu_bol_ativo' => 1, 'usu_str_hash_cadastro' => NULL))){
                echo "<h1>Conta ativada com sucesso!</h1>";
            } else {
                echo "Link inválido";
            }
        }
    }
    
    private function Mail($dest, $assunto, $conteudo)
    {
        $headers = "MIME-Version: 1.1\r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        $headers .= "From: contato@lionphilips.tk\r\n";
        $headers .= "Return-Path: admin@lionphilips.tk\r\n";
        $envio = mail($dest, $assunto, $conteudo, $headers);

        if($envio){
            return true;
        } else {
            return false;
        }
    }
            
    function logout()
    {
        $this->session->sess_destroy();
    }
    
}
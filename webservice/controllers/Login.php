<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function index()
    {
        echo "oi";
    }
    
    public function auth()
    {
        $this->load->helper('security');
        
        $passwordsaltmain = 'allahu akbar';
        
        /* apenas requisições POST serão aceitas */
        if($this->input->post())
        {
            $username = anti_injection($this->input->post('username'));
            $password = anti_injection($this->input->post('password'));
            
            $this->db->select('mdl_user.id, username, firstname, lastname, email');
            $this->db->join('mdl_role_assignments', 'mdl_user.id=mdl_role_assignments.userid');
            $this->db->where('username', $username);
            $this->db->where('password', md5($password.$passwordsaltmain));
            $this->db->where('username', $username);
            $this->db->where('roleid', 3);
            $this->db->limit(1);
            
            $query = $this->db->get('mdl_user');
            
            if($query->num_rows() == 1) {
                
                //salva dados do usuário em sessão
                $dados = $query->row();
                $this->session->set_userdata($query->row());
                $this->session->set_userdata('id', $dados->id);
                $this->session->set_userdata('username', $dados->username);
                $this->session->set_userdata('email', $dados->email);
                $this->session->set_userdata('auth', true);
                
                echo json_encode($query->row());
            } else {
                echo json_encode(false);
            }
        }
    }
    
    function logout()
    {
        $this->session->sess_destroy();
    }
    
}

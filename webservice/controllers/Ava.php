<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Ava extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    /*
     *  Sincronização Total de Dados U-AVA e Ativa serviço de recomendação
     *  Segurança: Apenas pode ser ativada via POST e Token correto
     */

    public function index()
    {
        if($this->input->post())
        {
            if($this->input->post('token') == "df4df444e31e063236fc1999b6c0bae2b49b1969")
            {
                //sincroniza o Banco de Dados
                $this->Sync();

                //ativa serviço U-AVA
                $this->ActivateService();
            }
        }
    }

    /*
     *  Sincronização do Banco de Dados do AVA com a plataforma U-AVA
     *  Recupera dados do Moodle para Inserção/Atualização de Dados no U-AVA
    */

    private function Sync()
    {
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA
        $db2 = $this->load->database('default', TRUE); //conexão do Moodle
        
        //recebe todos os AVAs ativos
        $q = $db1->get('tbl_ava');
        $ava = $q->result();

        foreach ($ava as $r)
        {
            $db1->where('fok_int_ava', $r->pmk_int_avaid);
            $db1->where('ava_bol_ativo', 1);
            $query = $db1->get('tbl_configuracao', 1);
            $configuracao = $query->result();

            //caso exista configurações criada para aquele Moodle
            if(count($configuracao))
            {
                $db2->select("id AS usu_int_external_user_id, TRIM(CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, ''))) AS usu_str_nome, email AS usu_str_email, 1 AS fok_int_ava");
                $db2->where('id > ', $configuracao[0]->con_int_last_user_id);
                $query = $db2->get('mdl_user');
                $usuarios = $query->result();

                $db2->select('id AS cur_int_external_id, name AS cur_str_nome, 1 AS fok_int_ava');
                $db2->where('mdl_course_categories.id > ', $configuracao[0]->con_int_last_category_id);
                $query = $db2->get('mdl_course_categories');
                $cursos = $query->result();

                $db2->select("mdl_course.id AS dis_int_external_id, fullname AS dis_str_nome, mdl_course_categories.id AS fok_int_curso");
                $db2->join('mdl_course_categories', 'mdl_course.category=mdl_course_categories.id');
                $db2->where('mdl_course.id > ', $configuracao[0]->con_int_last_course_id);
                $query = $db2->get('mdl_course');
                $disciplinas = $query->result();

                $db2->select('id AS ava_int_external_assignment_id, course AS fok_int_disciplina, name AS ava_str_nome, grade AS ava_dbl_valor, timeavailable AS ava_dta_avaliacao');
                $db2->where('mdl_assignment.id > ', $configuracao[0]->con_int_last_assignment_id);
                $query = $db2->get('mdl_assignment');
                $avaliacoes = $query->result();

                $db2->select('id AS doc_int_external_id, course AS fok_int_disciplina, name AS doc_str_titulo, type AS doc_str_type, reference AS doc_str_path');
                $db2->where('mdl_resource.id > ', $configuracao[0]->con_int_last_resource_id);
                $query = $db2->get('mdl_resource');
                $documentos = $query->result();

                $db2->select('id AS avu_int_external_id, assignment AS fok_int_avaliacao, userid AS fok_int_usuario, grade AS avu_dbl_nota');
                $db2->where('mdl_assignment_submissions.id > ', $configuracao[0]->con_int_last_grade_id);
                $query = $db2->get('mdl_assignment_submissions');
                $notas = $query->result();

                //atualiza base de dados do U-AVA com usuários recuperados do Moodle
                $con_int_last_user_id=0; 
                $con_int_last_course_id=0; 
                $con_int_last_category_id=0;
                $con_int_last_assignment_id=0;
                $con_int_last_resource_id=0;
                $con_int_last_grade_id = 0;

                if(count($usuarios)) { 
                    $db1->insert_batch('tbl_usuario', $usuarios); 
                    $con_int_last_user_id = $usuarios[count($usuarios)-1]->usu_int_external_user_id; 

                    //atualiza configurações do AVA
                    $db1->where('fok_int_ava', 1);
                    $db1->update('tbl_configuracao', array('con_int_last_user_id' => $con_int_last_user_id));
                }

                if(count($cursos)) { 
                    $db1->insert_batch('tbl_curso', $cursos); 
                    $con_int_last_category_id = $cursos[count($cursos)-1]->cur_int_external_id;

                    //atualiza configurações do AVA
                    $db1->where('fok_int_ava', 1);
                    $db1->update('tbl_configuracao', array('con_int_last_category_id' => $con_int_last_category_id));
                }

                if(count($disciplinas)) { 
                    $db1->insert_batch('tbl_disciplina', $disciplinas); 
                    $con_int_last_course_id = $disciplinas[count($disciplinas)-1]->dis_int_external_id; 

                    //atualizar ID do Curso do U-AVA nas disciplinas, ao invés do ID do Moodle
                    for($i=0; $i<count($disciplinas); $i++)
                    {
                        $db1->where('cur_int_external_id', $disciplinas[$i]->fok_int_curso);
                        $query = $db1->get('tbl_curso', 1);
                        $row = $query->row();

                        $db1->where('fok_int_curso', $disciplinas[$i]->fok_int_curso);
                        $db1->update('tbl_disciplina', array('fok_int_curso' => $row->pmk_int_curso));
                    }

                    //atualiza configurações do AVA
                    $db1->where('fok_int_ava', 1);
                    $db1->update('tbl_configuracao', array('con_int_last_course_id' => $con_int_last_course_id));
                }

                if(count($avaliacoes)) { 
                    $db1->insert_batch('tbl_avaliacao', $avaliacoes); 
                    $con_int_last_assignment_id = $avaliacoes[count($avaliacoes)-1]->ava_int_external_assignment_id; 

                    //atualizar ID do Curso do U-AVA nas disciplinas, ao invés do ID do Moodle
                    for($i=0; $i<count($avaliacoes); $i++)
                    {
                        $db1->where('dis_int_external_id', $avaliacoes[$i]->fok_int_disciplina);
                        $query = $db1->get('tbl_disciplina', 1);
                        $row = $query->row();

                        $db1->where('fok_int_disciplina', $avaliacoes[$i]->fok_int_disciplina);
                        $db1->update('tbl_avaliacao', array('fok_int_disciplina' => $row->pmk_int_disciplina));
                    }

                    //atualiza configurações do AVA
                    $db1->where('fok_int_ava', 1);
                    $db1->update('tbl_configuracao', array('con_int_last_assignment_id' => $con_int_last_assignment_id));
                }

                if(count($documentos)) { 
                    $db1->insert_batch('tbl_documento', $documentos); 
                    $con_int_last_resource_id = $documentos[count($documentos)-1]->doc_int_external_id;

                    //atualizar ID da disciplina do U-AVA nos documentos, ao invés do ID do Moodle
                    for($i=0; $i<count($documentos); $i++)
                    {
                        $db1->where('dis_int_external_id', $documentos[$i]->fok_int_disciplina);
                        $query = $db1->get('tbl_disciplina', 1);
                        $row = $query->row();

                        $db1->where('fok_int_disciplina', $documentos[$i]->fok_int_disciplina);
                        $db1->update('tbl_documento', array('fok_int_disciplina' => $row->pmk_int_disciplina));
                    }

                    //atualiza configurações do AVA
                    $db1->where('fok_int_ava', 1);
                    $db1->update('tbl_configuracao', array('con_int_last_resource_id' => $con_int_last_resource_id));
                }

                if(count($notas)) {
                    $db1->insert_batch('tbl_avaliacao_usuario', $notas); 
                    $con_int_last_grade_id = $notas[count($notas)-1]->avu_int_external_id;

                    //atualizar ID da disciplina do U-AVA nos documentos, ao invés do ID do Moodle
                    for($i=0; $i<count($notas); $i++)
                    {
                        $db1->where('ava_int_external_assignment_id', $notas[$i]->fok_int_avaliacao);
                        $query = $db1->get('tbl_avaliacao', 1);
                        $row = $query->row();

                        $db1->where('fok_int_avaliacao', $notas[$i]->fok_int_avaliacao);
                        $db1->update('tbl_avaliacao_usuario', array('fok_int_avaliacao' => $row->pmk_int_avaliacao));
                    }

                    //atualizar ID do aluno do U-AVA, ao invés do ID do Moodle
                    for($i=0; $i<count($notas); $i++)
                    {
                        $db1->where('usu_int_external_user_id', $notas[$i]->fok_int_usuario);
                        $query = $db1->get('tbl_usuario', 1);
                        $row = $query->row();

                        $db1->where('fok_int_usuario', $notas[$i]->fok_int_usuario);
                        $db1->update('tbl_avaliacao_usuario', array('fok_int_usuario' => $row->pmk_int_usuario));
                    }

                    //atualiza configurações do AVA
                    $db1->where('fok_int_ava', 1);
                    $db1->update('tbl_configuracao', array('con_int_last_grade_id' => $con_int_last_grade_id));
                }
                echo "true";
            }

            //caso contrário, esta será a primeira configuração do Moodle no U-AVA
            else
            {
                //recebe todos os usuários
                $db2->select("id AS usu_int_external_user_id, TRIM(CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, ''))) AS usu_str_nome, email AS usu_str_email, 1 AS fok_int_ava");
                $query = $db2->get('mdl_user');
                $usuarios = $query->result();

                //recebe todos os cursos
                $db2->select('id AS cur_int_external_id, name AS cur_str_nome, 1 AS fok_int_ava');
                $query = $db2->get('mdl_course_categories');
                $cursos = $query->result();

                //recebe todas as disciplinas
                $db2->select("mdl_course.id AS dis_int_external_id, fullname AS dis_str_nome, mdl_course_categories.id AS fok_int_curso");
                $db2->join('mdl_course_categories', 'mdl_course.category=mdl_course_categories.id');
                $query = $db2->get('mdl_course');
                $disciplinas = $query->result();

                //recebe todos as atividades
                $db2->select('id AS ava_int_external_assignment_id, course AS fok_int_disciplina, name AS ava_str_nome, grade AS ava_dbl_valor, timeavailable AS ava_dta_avaliacao');
                $query = $db2->get('mdl_assignment');
                $avaliacoes = $query->result();

                $db2->select('id AS doc_int_external_id, course AS fok_int_disciplina, name AS doc_str_titulo, type AS doc_str_type, reference AS doc_str_path');
                $query = $db2->get('mdl_resource');
                $documentos = $query->result();

                $db2->select('id AS avu_int_external_id, assignment AS fok_int_avaliacao, userid AS fok_int_usuario, grade AS avu_dbl_nota');
                $query = $db2->get('mdl_assignment_submissions');
                $notas = $query->result();

                //atualiza base de dados do U-AVA com usuários recuperados do Moodle
                $con_int_last_user_id=0; 
                $con_int_last_course_id=0; 
                $con_int_last_category_id=0; 
                $con_int_last_assignment_id=0;
                $con_int_last_resource_id=0;
                $con_int_last_grade_id = 0;

                if(count($usuarios)) {
                    $db1->insert_batch('tbl_usuario', $usuarios); 
                    $con_int_last_user_id = $usuarios[count($usuarios)-1]->usu_int_external_user_id; 
                }

                if(count($cursos)) { 
                    $db1->insert_batch('tbl_curso', $cursos); 
                   $con_int_last_category_id = $cursos[count($cursos)-1]->cur_int_external_id;

                }

                if(count($disciplinas)) { 
                    $db1->insert_batch('tbl_disciplina', $disciplinas); 
                    $con_int_last_course_id = $disciplinas[count($disciplinas)-1]->dis_int_external_id; 

                    //atualizar ID do Curso do U-AVA nas disciplinas, ao invés do ID do Moodle
                    for($i=0; $i<count($disciplinas); $i++)
                    {
                        $db1->where('cur_int_external_id', $disciplinas[$i]->fok_int_curso);
                        $query = $db1->get('tbl_curso', 1);
                        $row = $query->row();

                        $db1->where('fok_int_curso', $disciplinas[$i]->fok_int_curso);
                        $db1->update('tbl_disciplina', array('fok_int_curso' => $row->pmk_int_curso));
                    }
                }

                if(count($avaliacoes)) { 
                    $db1->insert_batch('tbl_avaliacao', $avaliacoes); 
                    $con_int_last_assignment_id = $avaliacoes[count($avaliacoes)-1]->ava_int_external_assignment_id; 

                    //atualizar ID do Curso do U-AVA nas disciplinas, ao invés do ID do Moodle
                    for($i=0; $i<count($avaliacoes); $i++)
                    {
                        $db1->where('dis_int_external_id', $avaliacoes[$i]->fok_int_disciplina);
                        $query = $db1->get('tbl_disciplina', 1);
                        $row = $query->row();

                        $db1->where('fok_int_disciplina', $avaliacoes[$i]->fok_int_disciplina);
                        $db1->update('tbl_avaliacao', array('fok_int_disciplina' => $row->pmk_int_disciplina));
                    }
                }

                if(count($documentos)) { 
                    $db1->insert_batch('tbl_documento', $documentos); 
                    $con_int_last_resource_id = $documentos[count($documentos)-1]->doc_int_external_id;

                    //atualizar ID da disciplina do U-AVA nos documentos, ao invés do ID do Moodle
                    for($i=0; $i<count($documentos); $i++)
                    {
                        $db1->where('dis_int_external_id', $documentos[$i]->fok_int_disciplina);
                        $query = $db1->get('tbl_disciplina', 1);
                        $row = $query->row();

                        $db1->where('fok_int_disciplina', $documentos[$i]->fok_int_disciplina);
                        $db1->update('tbl_documento', array('fok_int_disciplina' => $row->pmk_int_disciplina));
                    }
                }

                if(count($notas)) { 
                    $db1->insert_batch('tbl_avaliacao_usuario', $notas); 
                    $con_int_last_grade_id = $notas[count($notas)-1]->avu_int_external_id;

                    //atualizar ID da avaliação do U-AVA, ao invés do ID do Moodle
                    for($i=0; $i<count($notas); $i++)
                    {
                        $db1->where('ava_int_external_assignment_id', $notas[$i]->fok_int_avaliacao);
                        $query = $db1->get('tbl_avaliacao', 1);
                        $row = $query->row();

                        $db1->where('fok_int_avaliacao', $notas[$i]->fok_int_avaliacao);
                        $db1->update('tbl_avaliacao_usuario', array('fok_int_avaliacao' => $row->pmk_int_avaliacao));
                    }

                    //atualizar ID do aluno do U-AVA, ao invés do ID do Moodle
                    for($i=0; $i<count($notas); $i++)
                    {
                        $db1->where('usu_int_external_user_id', $notas[$i]->fok_int_usuario);
                        $query = $db1->get('tbl_usuario', 1);
                        $row = $query->row();

                        $db1->where('fok_int_usuario', $notas[$i]->fok_int_usuario);
                        $db1->update('tbl_avaliacao_usuario', array('fok_int_usuario' => $row->pmk_int_usuario));
                    }
                }

                //atualiza configurações do AVA
                $db1->insert('tbl_configuracao', array(
                    'fok_int_ava' => 1,'con_int_last_user_id' => $con_int_last_user_id,
                    'con_int_last_course_id' => $con_int_last_course_id, 
                    'con_int_last_category_id' => $con_int_last_category_id,
                    'con_int_last_assignment_id' => $con_int_last_assignment_id,
                    'con_int_last_resource_id' => $con_int_last_resource_id,
                    'con_int_last_grade_id' => $con_int_last_grade_id)
                );

                echo "true";
            }
        }
    }

    /*
     * Objetivo: Realiza a análise das notas de avaliações e ativa o serviço de recomendação. 
     * Condição: Aluno não consiga atingir uma nota satisfatória
     */    
    private function ActivateService(
    {
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA
    

        $db1->where('avu_bol_recomendacao', 0);
        $db1->where('avu_dbl_nota > 0', null);
        $query = $db1->get('tbl_avaliacao_usuario');
        $avaliacoes = $query->result();
        
        foreach ($avaliacoes as $row)
        {
            //recebe os dados da avaliacao
            $db1->where('pmk_int_avaliacao', $row->fok_int_avaliacao);
            $query2 = $db1->get('tbl_avaliacao', 1);
            $avaliacao = $query2->row();

            //caso o aluno obtenha nota menor que 70% do valor da avaliação
            //Ex.: Nota da avaliacao = 10 .  O aluno deve obter igual ou superior a 7
            if($row->avu_dbl_nota < ($avaliacao->ava_dbl_valor * 0.7))
            {
                //recebe os documentos referentes a aquela avaliacao
                $db1->where('fok_int_avaliacao', $row->fok_int_avaliacao);
                $query3 = $db1->get('tbl_documento');
                $documentos = $query3->result();

                //recebe dados do usuário
                $db1->where('pmk_int_usuario', $row->fok_int_usuario);
                $query4 = $db1->get('tbl_usuario', 1);
                $usuario = $query4->row();
                
                foreach ($documentos as $row2)
                {
                    $titulo = "";
                    if(empty($row2->doc_str_titulo_classificacao)) {
                        $titulo = $row2->doc_str_titulo;
                    } else {
                        $titulo = $row2->doc_str_titulo_classificacao;
                    }

                    //realiza a recomendação de conteúdo a partir do documento vinculado à avaliação
                    $this->SearchOnWeb($titulo, $usuario->usu_str_email, $row->fok_int_usuario, $row->fok_int_avaliacao);
                }
            }
    
            //atualiza status da avaliacao para recomendado
            $db1->where('pmk_int_avaliacao_aluno', $row->pmk_int_avaliacao_aluno);
            $db1->update('tbl_avaliacao_usuario', array('avu_bol_recomendacao' => 1));
        }
    }

    /*
     *  Realiza a busca de conteúdo na Internet, utilizando a API do Google
     *  e encaminha o conteúdo por email para os alunos em questão.
     */
    private function SearchOnWeb($title, $email, $usuario, $avaliacao)
    {
        $this->load->library('google');
        $db1 = $this->load->database('ava', TRUE); //conexão do U-AVA

        //busca conteudo utilizando a API do Google
        $pdf = $this->google->web($title . ' pdf')->results;
        $ebook = $this->google->books($title)->results;
        $videos = $this->google->video($title)->results;

        if(count($pdf) > 0)
        {
            $conteudo = $this->MakeEmailUAVA($pdf, $ebook, $videos);

            if($this->Mail($email, "U-AVA Moodle", $conteudo))
            {
                //envia notificações mobile
                $campos = array(
                    'fok_int_usuario' => $usuario,
                    'fok_int_avaliacao' => $avaliacao,
                    'not_str_conteudo' => json_encode(array($pdf, $ebook, $videos))
                );
                
                $db1->insert('tbl_notificacao_mobile', $campos);
                return true;
            } 
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    /*
     *  Realiza a criação do Email com o conteúdo educacional,
     *  a partir de dados de entrada (array de documentos e ebooks)
     */
    private function MakeEmailUAVA($pdf, $ebook, $videos)
    {
        $html = "<h1>Estamos aqui para ajudá-lo!</h1>";
        $html .= "<h3>Percebemos que você não se saiu bem na última avaliação. <br>";
        $html .= "Complemente seus estudos com os conteúdos abaixo:</h3>";
        
        if(count($pdf) > 0) {
            $html .= "<br /><h3><center>Documentos e Páginas</center></h3>";
            $html .= "<table style=\"width:100%; border: 1px solid gray;\">";
            $count=1;

            foreach ($pdf as $row) {
                $color = '';
                if($count%2==1){ $color = "style=\"background-color: #f1f1f1;\""; }
                $html .= "<tr $color><td>" . $row->title . "</td><td>" . $row->unescapedUrl . "</td></tr>";
                $count++;
                if($count == 8){ break; } //recomenda 7 links
            }
            $html .= "</table>";
        }
        
        if(count($ebook) > 0) {
            $html .= "<br /><h3><center>E-books</center></h3>";
            $html .= "<table style=\"width:100%; border: 1px solid gray;\">";
            $count=1;

            foreach ($ebook as $row) {
                $color = '';
                if($count%2==1){ $color = "style=\"background-color: #f1f1f1;\""; }
                $html .= "<tr $color><td>" . $row->title . "</td><td>" . $row->unescapedUrl . "</td></tr>";
                $count++;
                if($count == 4){ break; } //recomenda 3 ebooks
            }

            $html .= "</table>";
        }

        if(count($videos) > 0) {
            $html .= "<br /><h3><center>Vídeos</center></h3>";
            $html .= "<table style=\"width:100%; border: 1px solid gray;\">";
            $count=1;

            foreach ($videos as $row) {
                $color = '';
                if($count%2==1){ $color = "style=\"background-color: #f1f1f1;\""; }
                $html .= "<tr $color><td>" . $row->title . "</td><td>" . $row->url . "</td></tr>";
                $count++;

                if($count == 4){ break; } //recomenda 3 vídeos
            }
            $html .= "</table>";
        }

        return $html;
    }

    /*
     *  Função simples de envio de Email utilizando o servidor TK
     */
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
}
/* código JS index */

$(document).ready(function () {

    $("#a_username").html("<i class=\"fa fa-user\"></i> " + sessionStorage.getItem('firstname') + " <b class=\"caret\"></b>");

    //recebe todos os cursos do usuário logado
    $.ajax({
        dataType: "json",
        url: "../CI/index.php/main/return_all_courses",
        beforeSend: function () {
            $("#ajax-loader").show();
        },
        success: function (data) {
            $("#select-cursos").html("<option value=\"0\">Selecione um curso</option>");
            $.each(data, function (key, val) {
                $("#select-cursos").append("<option value='" + val.id + "'>" + val.nome + "</option>");
            });
            $("#ajax-loader").hide();
        },
        error: function () {
            showError("login-alert", "Ocorreu um erro na obtenção de dados. Verifique sua conexão!");
        }
    });
    
    $("#select-cursos").change(function(){
        var int_curso = parseInt($(this).val());
        
        if(int_curso > 0)
        {
            $.ajax({
                dataType: "json",
                url: "../CI/index.php/main/return_all_categories/"+int_curso,
                beforeSend: function () {
                    $("#ajax-loader").show();
                },
                success: function (data) {
                    $("#select-disciplinas").html("<option value=\"0\">Selecione uma Disciplina</option>");
                    $.each(data, function (key, val) {
                        $("#select-disciplinas").append("<option value='" + val.id + "'>" + val.nome + "</option>");
                    });
                    $("#ajax-loader").hide();
                },
                error: function () {
                    showError("login-alert", "Ocorreu um erro na obtenção de dados. Verifique sua conexão!");
                }
            });
        }
    });
    
    $("#select-disciplinas, #select-filtro").change(function(){
        carrega_documentos();
    });

});



//carrega o datatable de cada curso
//$("#select-cursos, #select-status").change(function () {
//    carrega_documentos();
//});

var carrega_documentos = function()
{
    var disciplina = parseInt($("#select-disciplinas").val());
    var filtro = $("#select-filtro").val();
        
    if (disciplina > 0)
    {        
        $("#table").show();
        $.ajax({
            dataType: "json",
            url: "../CI/index.php/main/return_all_documents/" + disciplina + "/" + filtro,
            beforeSend: function () {
                $("#ajax-loader").show();
            },
            success: function (data) {
                $("#table tbody").html("");
                $.each(data, function (key, val) {
                    
                    var titulo_classificacao = "", avaliacao = "";
                    if(val.titulo_classificacao == null){ titulo_classificacao = "A definir"; } else{ titulo_classificacao = val.titulo_classificacao; }
                    if(val.avaliacao == null){ avaliacao = "A definir"; } else{ avaliacao = val.avaliacao; }
                    
                    $("#table tbody").append(
                            "<tr>" +
                            "<td>" + val.titulo + "</td>" +
                            "<td>" + avaliacao + "</td>" +
                            "<td>" + titulo_classificacao + "</td>" +
                            "<td>" +
                            "<a href=\"#\" onclick=\"classificar_documento(" + val.id + ")\"><i class=\"fa fa-fw fa-edit\"></i></a>" +
                            "</td>" +
                            "</tr>"
                            );
                });
                
                $("#table").removeClass("hide").addClass("display").DataTable();
                $("#ajax-loader").hide();
            },
            error: function () {
                showError("login-alert", "Ocorreu um erro na obtenção de dados. Verifique sua conexão!");
            }
        });
    }
}

var classificar_documento = function (id)
{
    bootbox.dialog({
        title: "Classificar Documento #" + id,
        message: '<div id="ajax-loader2" style="margin-bottom: 10px; text-align: center; display:none"> <img src="img/ajax-loader.gif" /> </div>' +
                '<div style="display:none" id="login-alert2" class="alert alert-success col-sm-12"></div>' +
                '<div class="row">' +
                '<div class="col-md-12"> ' +
                    '<form class="form-horizontal"> ' +
                    '<div class="form-group"> ' +
                    '<label class="col-md-4 control-label" for="name">Documento referente</label> ' +
                    '<div class="col-md-8"> ' +
                    '<input id="str_titulo" name="str_titulo" type="text" placeholder="Título do Documento" class="form-control input-md" disabled> ' +
                    '<span id="a_doc" style="color: gray"></span>'+
                    '</div> ' +
                    '</div>' + 
                    '<div class="form-group"> ' +
                    '<label class="col-md-4 control-label" for="name">Avaliação</label> ' +
                    '<div class="col-md-8"> ' +
                    '<select id="int_avaliacao" class="form-control">'+
                    '</select>'+
                    '</div> ' +
                    '</div>' + 
                    '<div class="form-group"> ' +
                    '<label class="col-md-4 control-label" for="name">Título</label> ' +
                    '<div class="col-md-8"> ' +
                    '<input id="str_titulo2" type="text" class="form-control input-md" />' +
                    '<span style="color: blue; font-size: 11px">O título do documento será utilizado para recomendações de conteúdo.</span>'+
                    '</div> ' +
                    '</div>' +
                    '<div style="text-align: right; margin-right: 10px">' +
                    '<button type="button" class="btn btn-success" onclick="salvar_classificacao('+id+')">Salvar</button>' +
                    '</div>' +
                    '</form>' +
                '</div>' +
                '</div>'
    });
    
    $.ajax({
        dataType: "json",
        url: "../CI/index.php/main/return_document_info/"+id,
        beforeSend: function () {
            $("#ajax-loader2").show();
        },
        success: function (data) {
            $.each(data, function (key, val) {
                
                var avaliacao = val.avaliacao;
                
                $("#str_titulo").val(val.titulo);
                $("#str_titulo2").val(val.titulo_classificacao);
                $("#a_doc").html("Arquivo:  " + val.path);
                
                if(avaliacao != null)
                {
                    $.ajax({
                        dataType: "json",
                        url: "../CI/index.php/main/return_assignments_course/"+$("#select-disciplinas").val(),
                        beforeSend: function () {
                        },
                        success: function (data) {
                            $.each(data, function (key, val2) {
                                var selected = '';
                                if(avaliacao == val2.id){ selected = 'selected="selected"'; }
                                $("#int_avaliacao").append('<option value="'+val2.id+'" '+selected+'>'+val2.nome+'</option>');
                            });
                            $("#ajax-loader2").hide();
                        },
                        error: function () {
                            showError("login-alert2", "Ocorreu um erro na obtenção de dados. Verifique sua conexão!");
                            $("#ajax-loader2").hide();
                        }
                    });
                }
                else
                {
                    $.ajax({
                        dataType: "json",
                        url: "../CI/index.php/main/return_assignments_course/"+$("#select-disciplinas").val(),
                        beforeSend: function () {
                        },
                        success: function (data) {
                            $("#int_avaliacao").append('<option value="0" selected="selected">Selecione uma Avaliação</option>');
                            $.each(data, function (key, val2) {
                                $("#int_avaliacao").append('<option value="'+val2.id+'">'+val2.nome+'</option>');
                            });
                            $("#ajax-loader2").hide();
                        },
                        error: function () {
                            showError("login-alert2", "Ocorreu um erro na obtenção de dados. Verifique sua conexão!");
                            $("#ajax-loader2").hide();
                        }
                    });
                }
            });
        },
        error: function () {
            showError("login-alert2", "Ocorreu um erro na obtenção de dados. Verifique sua conexão!");
            $("#ajax-loader2").hide();
        }
    });
}

var salvar_classificacao = function (id)
{
    var avaliacao = parseInt($("#int_avaliacao").val());
    var str_titulo = $("#str_titulo2").val().trim();

    if (avaliacao > 0)
    {
        if (str_titulo.length > 0)
        {
            $("#ajax-loader2").show();
            $.ajax({
                type: "POST",
                url: "../CI/index.php/main/save_document_classification/"+id,
                data: { avaliacao: avaliacao, str_titulo: str_titulo },
                success: function(data) {
                    $("#ajax-loader2").hide();

                    if(data == "true") {
                        showError("login-alert2", "Informações salvas com sucesso!");
                        setTimeout(function(){ $(".close").click(); carrega_documentos(); }, 2000);
                    } else {
                        showError("login-alert2", "Ocorreu um erro ao salvar os dados. Tente novamente!");
                    }
                }
            });
        }
        else
        {
            showError("login-alert2", "As tags devem ser preenchidas");
        }
    }
    else
    {
        showError("login-alert2", "O título deve ser preenchido");
    }
}

var logout = function ()
{
    //limpa sessão do browser
    sessionStorage.clear();

    //fecha sessão do Web Service
    $("#ajax-loader").show();
    $.get("../CI/index.php/login/logout", function(){
        location.href = "login.html";
    });
}

function showError(div, msg)
{
    $("#" + div).html(msg).show();
    setTimeout(function () { $("#" + div).fadeOut("slow"); }, 1500);
}
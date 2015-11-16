/* código JS login */

$(document).ready(function(){ 
    //caso usuário tenha salvo suas configurações de login
    if(readCookie("uava_form_login") != null && readCookie("uava_form_password") != null) {
        $("#login-username").val(readCookie("uava_form_login"));
        $("#login-password").val(readCookie("uava_form_password"));
        $("#login-remember").attr("checked", true);
    }
    
    //ativa formulário pela tecla ENTER
    $("#login-username, #login-password").keyup(function(e){
        if(e.which==13) {
            $("#btn-login").click();
        }
    });
});



$("#btn-login").click(function(){
    var login = $("#login-username").val().trim();
    var password = $("#login-password").val().trim();
    var rememberme = $("#login-remember").is(":checked");
    
    if(login.length > 0)
    {
        if(password.length > 0)
        {
            $("#ajax-loader").show();
            $.ajax({
                type: "POST",
                url: "../CI/index.php/login/auth",
                data: { username: login, password:password },
                success: function(data){
                    $("#ajax-loader").hide();
                    
                    //checa se navegador tem suporte a Session HTML5
                    if(typeof(Storage) !== "undefined")
                    {
                        if(data != "false")
                        {   
                            var user_data = jQuery.parseJSON(data);
                            
                            //salva dados do usuário em sessão do browser
                            sessionStorage.setItem('id', user_data.id);
                            sessionStorage.setItem('username', user_data.username);
                            sessionStorage.setItem('firstname', user_data.firstname);
                            sessionStorage.setItem('lastname', user_data.lastname);
                            sessionStorage.setItem('email', user_data.email);
                            sessionStorage.setItem('auth', true);
                            
                            if(rememberme) { // cria ou atualiza cookies para 15 dias
                                createCookie("uava_form_login", login, 15);
                                createCookie("uava_form_password", password, 15);
                            } else { //limpa cookies (caso usuário tenha desativado o checkbox)
                                eraseCookie("uava_form_login");
                                eraseCookie("uava_form_password");
                            }
                            
                            $("#login-success").html("Login efetuado com sucesso!").show();
                            //redireciona para a página inicial
                            setTimeout(function(){ location.href = "index.html" }, 1000);
                        }
                        else
                        {
                            showError("Usuário e/ou Senha inválidos. Tente novamente!");
                        }
                    }
                    else
                    {
                        alert('Seu navegador Web não tem suporte para esta aplicação. Por favor atualize-o ou, de preferência, utilize navegadores como Google Chrome ou Firefox');
                    }
                },
                error: function(data){
                    $("#ajax-loader").hide();
                    showError("Ocorreu um erro. Verifique sua conexão com a internet.");
                }
            });
        }
        else {
            showError("Senha inválida");
        }
    }
    else {
        showError("Login inválido");
    }
});

function showError(msg)
{
    $("#login-alert").html(msg).show();
    setTimeout(function(){ $("#login-alert").fadeOut("slow"); }, 1500);
}


/* cookies */
function createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name,"",-1);
}
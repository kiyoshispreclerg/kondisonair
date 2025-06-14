<?php 
// No language filter, but keep session check for access control
if (!isset($_SESSION['KondisonairUzatorIDX']) || $_SESSION['KondisonairUzatorNivle'] < 100) {
    echo '<script>window.location = "index.php";</script>';
    exit;
}
?>
<input type="hidden" id="idUsuario" value="0" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t('Usuários')?></a></li>
                    </ol>
                </h2>
            </div>
        </div>
    </div>
</div>
<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="row row-deckx row-cards">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="col">
                            <input type="text" class="form-control" id="filtroUsuarios" placeholder="<?=_t('Filtrar usuários')?>" onkeyup="filtrarUsuarios()">
                        </div>
                        <div class="card-actions">
                            <a onclick="novoUsuario()" class="btn btn-primary d-none d-sm-inline-block">
                                <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                <?=_t('Novo usuário')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-bodyx">
                        <div class="list-group list-group-flush overflow-auto" id="usuariosTable" style="max-height: 35rem">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Informações')?></h3>
                        <div class="card-actions">
                            <a id="btnSalvar" onclick="gravarUsuario()" class="btn btn-primary" style="display: none;">
                                Salvar
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Username')?></label>
                            <input type="text" class="form-control" id="username" onchange="showGravarUsuario()">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Nome completo')?></label>
                            <input type="text" class="form-control" id="nome_completo" onchange="showGravarUsuario()">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Descrição')?></label>
                            <textarea class="form-control" id="descricao" rows="8" onchange="showGravarUsuario()"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Data de cadastro')?></label>
                            <p class="form-control-static" id="data_cadastro"><?=_t('N/A')?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Idioma nativo')?></label>
                            <p class="form-control-static" id="id_idioma_nativo"><?=_t('N/A')?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Email')?></label>
                            <p class="form-control-static" id="email"><?=_t('N/A')?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Perfil')?></label>
                            <p class="form-control-static" id="publico"><?=_t('N/A')?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function gravarUsuario(){
    if ($('#username').val()=='') return;
    if ($('#nome_completo').val()=='') return;

    $.post("?action=ajaxGravarUsuario&uid="+$('#idUsuario').val(), 
    {
        username: $('#username').val(),
        nome_completo: $('#nome_completo').val(),
        descricao: $('#descricao').val()
    }, function(data){
        if ($.trim(data) > 0){
            $('#idUsuario').val($.trim(data));
            $("#usuariosTable").load("?action=listUsuarios");
            $('#btnSalvar').hide();
        }else{
            alert(data);
        }
    });
}

function abrirUsuario(uid){
    $(".list-group-item").removeClass("card card-active bg-primary-lt");        
    $("#row_"+uid).addClass("card card-active bg-primary-lt");
    $('#idUsuario').val(uid); 
    $.getJSON("?action=getDetalhesUsuario&uid="+uid, function(data){
        $.each(data, function(key, val){
            $('#username').val(data[0].username); 
            $('#nome_completo').val(data[0].nome_completo); 
            $('#descricao').val(data[0].descricao); 
            $('#data_cadastro').text(data[0].data_cadastro || '<?=_t('N/A')?>');
            var idiomas = <?php echo json_encode($idiomas_sistema); ?>;
            $('#id_idioma_nativo').text(idiomas[data[0].id_idioma_nativo] || '<?=_t('N/A')?>');
            $('#email').text(data[0].email || '<?=_t('N/A')?>');
            $('#publico').text(data[0].publico == 1 ? '<?=_t('Público')?>' : '<?=_t('Privado')?>');
            $('#btnSalvar').hide();
        });
    });
}

function showGravarUsuario(){
    $('#btnSalvar').show(); 
}

function novoUsuario(){
    $(".list-group-item").removeClass("card card-active bg-primary-lt");   
    $('#idUsuario').val(0); 
    $('#username').val(''); 
    $('#nome_completo').val(''); 
    $('#descricao').val(''); 
    $('#data_cadastro').text('<?=_t('N/A')?>');
    $('#id_idioma_nativo').text('<?=_t('N/A')?>');
    $('#email').text('<?=_t('N/A')?>');
    $('#publico').text('<?=_t('N/A')?>');
    $('#btnSalvar').hide(); 
    $("#usuariosTable").load("?action=listUsuarios");
}

function delUsuario(uid){ 
    if (confirm("<?=_t('Apagar este usuário?')?>"))
        $.get("?action=ajaxDelUsuario&uid="+uid, function(data){
            if ($.trim(data) == 'ok'){
                $("#usuariosTable").load("?action=listUsuarios");
                novoUsuario();
            }else{
                alert(data);
            }
        });
}

function filtrarUsuarios(){
    var filtro = $('#filtroUsuarios').val().toLowerCase();
    $('#usuariosTable .list-group-item').each(function(){
        var texto = $(this).find('.col').text().toLowerCase();
        $(this).toggle(texto.includes(filtro));
    });
}

$(document).ready(function(){
    $("#usuariosTable").load("?action=listUsuarios");
    $('#filtroUsuarios').focus();
});
</script>
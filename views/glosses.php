<?php 
if ($_SESSION['KondisonairUzatorNivle'] < 100 ) {
    echo '<script>window.location = "index.php";</script>';
    exit;
}
?>
<input type="hidden" id="codigo" value="" />
<input type="hidden" id="idGloss" value="0" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t('Glosses')?></a></li>
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
                            <input type="text" class="form-control" id="filtroGlosses" placeholder="<?=_t('Filtrar glosses')?>" onkeyup="filtrarGlosses()">
                        </div>
                        <div class="card-actions">
                            <a onclick="novoGloss()" class="btn btn-primary d-none d-sm-inline-block">
                                <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                <?=_t('Novo gloss')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-bodyx">
                        <div class="list-group list-group-flush overflow-auto" id="glossesTable" style="max-height: 35rem">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Informações')?></h3>
                        <div class="card-actions">
                            <a id="btnSalvar" onclick="gravarGloss()" class="btn btn-primary" style="display: none;">
                                Salvar
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Gloss')?></label>
                            <input type="text" class="form-control" id="gloss" onkeyup="showGravarGloss()" placeholder="<?=_t('Ex.: NOM')?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Descrição')?></label>
                            <input type="text" class="form-control" id="descricao" onkeyup="showGravarGloss()" placeholder="<?=_t('Opcional')?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function gravarGloss(){
    if ($('#gloss').val()=='') return;
    if ($('#descricao').val()=='') return;

    $.post("?action=ajaxGravarGloss&gid="+$('#idGloss').val(), 
    {
        gloss: $('#gloss').val(),
        descricao: $('#descricao').val()
    }, function(data){
        if ($.trim(data) > 0){
            $('#idGloss').val($.trim(data));
            $("#glossesTable").load("?action=listGlosses");
            $('#btnSalvar').hide();
        }else{
            alert(data);
        }
    });
}

function abrirGloss(gid){
    $(".list-group-item").removeClass("card card-active bg-primary-lt");        
    $("#row_"+gid).addClass("card card-active bg-primary-lt");
    $('#idGloss').val(gid); 
    $.getJSON("?action=getDetalhesGloss&gid="+gid, function(data){
        $.each(data, function(key, val){
            $('#gloss').val(data[0].gloss); 
            $('#descricao').val(data[0].descricao); 
            $('#btnSalvar').hide();
        });
    });
}

function showGravarGloss(){
    $('#btnSalvar').show(); 
}

function novoGloss(){
    $(".list-group-item").removeClass("card card-active bg-primary-lt");   
    $('#idGloss').val(0); 
    $('#gloss').val(''); 
    $('#descricao').val(''); 
    $('#btnSalvar').hide(); 
    $("#glossesTable").load("?action=listGlosses");
}

function delGloss(gid){ 
    if (confirm("<?=_t('Apagar este gloss?')?>"))
        $.get("?action=ajaxDelGloss&gid="+gid, function(data){
            if ($.trim(data) == 'ok'){
                $("#glossesTable").load("?action=listGlosses");
                novoGloss();
            }else{
                alert(data);
            }
        });
}

function filtrarGlosses(){
    var filtro = $('#filtroGlosses').val().toLowerCase();
    $('#glossesTable .list-group-item').each(function(){
        var texto = $(this).find('.col').text().toLowerCase();
        $(this).toggle(texto.includes(filtro));
    });
}

$(document).ready(function(){
    $("#glossesTable").load("?action=listGlosses");
    $('#filtroGlosses').focus();
});
</script>
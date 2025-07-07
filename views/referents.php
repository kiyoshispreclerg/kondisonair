<?php 
if ($_SESSION['KondisonairUzatorNivle'] < 100 ) {
    echo '<script>window.location = "index.php";</script>';
    exit;
}
?>
<input type="hidden" id="codigo" value="" />
<input type="hidden" id="idReferente" value="0" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t('Referentes')?></a></li>
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
            <div class="col-3">
                <div class="card">
                    <div class="card-header">
                        <div class="col">
                            <input type="text" class="form-control" id="filtroReferentes" placeholder="<?=_t('Filtrar')?>" onkeyup="filtrarReferentes()">
                        </div>
                        <div class="card-actions">
                            <a onclick="novoReferente()" class="btn btn-primary d-none d-sm-inline-block">
                                <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                <?=_t('Novo')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-bodyx">
                        <div class="list-group list-group-flush overflow-auto" id="referentesTable" style="max-height: 35rem">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Descrição')?></h3>
                        <div class="card-actions">
                            <a id="btnSalvar" onclick="gravarReferente()" class="btn btn-primary" style="display: none;">
                                Salvar
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php foreach($idiomas_sistema as $id => $nome){ ?>
                        <div class="mb-3">
                            <label class="form-label"><?=$nome?></label>
                            <div class="row">
                            <div class="col-4"><input type="text" class="form-control descricao" id="descricao<?=$id?>" onchange="showGravarReferente()" placeholder="<?=_t('Descrição curta (Ex.: homem)')?>"></div>
                            <div class="col-8"><input type="text" class="form-control descricao" id="detalhes<?=$id?>" onchange="showGravarReferente()" placeholder="<?=_t('Mais detalhes (Ex.: humano adulto masculino)')?>"></div>
                        </div> </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function gravarReferente(){
    <?php foreach($idiomas_sistema as $id => $nome){ ?>
        if ($('#descricao<?=$id?>').val()=='') return;
    <?php } ?>

    $.post("?action=ajaxGravarReferente&rid="+$('#idReferente').val()+"&iid=<?=$id_idioma?>", 
    {  <?php foreach($idiomas_sistema as $id => $nome){ ?>
        d<?=$id?>: $('#descricao<?=$id?>').val(),
        m<?=$id?>: $('#detalhes<?=$id?>').val(),
        <?php } ?>
    }, function(data){
        if ($.trim(data) > 0){
            $('#idReferente').val($.trim(data));
            $("#referentesTable").load("?action=listReferentes&iid=<?=$id_idioma?>");
            $('#btnSalvar').hide();
        }else{
            alert(data);
        }
    });
}

function abrirReferente(rid){
    $(".list-group-item").removeClass("card card-active bg-primary-lt");        
    $("#row_"+rid).addClass("card card-active bg-primary-lt");
    $('#idReferente').val(rid); 
    $('.descricao').val(''); 
    $.getJSON("?action=getDetalhesReferente&rid="+rid, function(data){
        $.each(data, function(key, val){
            <?php foreach($idiomas_sistema as $id => $nome){ ?>
            $('#descricao<?=$id?>').val(data.d<?=$id?>); 
            $('#detalhes<?=$id?>').val(data.m<?=$id?>); 
            <?php } ?>
            $('#btnSalvar').hide();
        });
    });
}

function showGravarReferente(){
    $('#btnSalvar').show(); 
}

function novoReferente(){
    $(".list-group-item").removeClass("card card-active bg-primary-lt");   
    $('#idReferente').val(0); 
    $('.descricao').val('');
    $('#btnSalvar').hide(); 
    $("#referentesTable").load("?action=listReferentes&iid=<?=$id_idioma?>");
}

function delReferente(rid){ 
    if (confirm("<?=_t('Apagar este referente?')?>"))
        $.get("?action=ajaxDelReferente&rid="+rid, function(data){
            if ($.trim(data) == 'ok'){
                $("#referentesTable").load("?action=listReferentes&iid=<?=$id_idioma?>");
                novoReferente();
            }else{
                alert(data);
            }
        });
}


function filtrarReferentes(){
    var filtro = $('#filtroReferentes').val().toLowerCase();
    $('#referentesTable .list-group-item').each(function(){
        var texto = $(this).find('.col').text().toLowerCase();
        $(this).toggle(texto.includes(filtro));
    });
}

$(document).ready(function(){
    novoReferente();
});
</script>
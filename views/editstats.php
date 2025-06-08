<?php
$id_realidade = $_GET['rid'];
$sid = $_GET['sid'] ?? 0;

$realidade = array();
$result = mysqli_query($GLOBALS['dblink'], "SELECT *,
    (SELECT id FROM collabs_realidades WHERE id_realidade = r.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
    FROM realidades r WHERE id = '".$id_realidade."';") or die(mysqli_error($GLOBALS['dblink']));
while ($r = mysqli_fetch_assoc($result)) {
    $realidade = $r;
}

if ($realidade['titulo'] == '' || ($realidade['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$realidade['collab'] > 0)) {
    echo '<script>window.location = "dash.php";</script>';
    exit;
}

if ($sid > 0) {
    $result = mysqli_query($GLOBALS['dblink'], "SELECT id FROM stats WHERE id = $sid AND id_realidade = $id_realidade LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    if (!mysqli_fetch_assoc($result)) {
        echo '<script>window.location = "dash.php";</script>';
        exit;
    }
}

$rule = 'other';
$ruleTitle = 'Entidades';
$ruleAll = 'Todas as entidades';
$ruleTipos = 'Tipos de entidades';

?>

<input type="hidden" id="codigo" value="<?=$id_realidade?>" />
<input type="hidden" id="idStat" value="<?=$sid?>" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item"><a href="?page=editworld&rid=<?=$id_realidade?>"><?=$realidade['titulo']?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t('Tipos de Desenvolvimentos')?></a></li>
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
                        <h3 class="card-title"><?=_t('Tipos de Desenvolvimentos')?></h3>
                        <div class="card-actions">
                            <a onclick="novoStat()" class="btn btn-primary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                <?=_t('Novo tipo')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-bodyx">
                        <div class="list-group list-group-flush overflow-auto" id="statsTable" style="max-height: 35rem"></div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Informações')?></h3>
                        <div class="card-actions">
                            <a id="btnSalvar" onclick="gravarStat()" class="btn btn-primary" style="display:none">
                                <?=_t('Salvar')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Nome')?>*</label>
                            <input type="text" class="form-control" id="nome" onchange="showGravarStat()" placeholder="<?=_t('Ex.: População, Temperatura')?>">
                        </div>
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label"><?=_t('Tipo de Dado')?>*</label>
                                    <select id="tipo_dado" onchange="showGravarStat()" class="form-select">
                                        <option value="integer"><?=_t('Inteiro (ex.: 1000)')?></option>
                                        <option value="decimal"><?=_t('Decimal (ex.: 23.5)')?></option>
                                        <option value="text"><?=_t('Texto (ex.: Ativo)')?></option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label"><?=_t('Tipo de Entidade')?>*</label>
                                    <select id="tipo_entidade" onchange="showGravarStat()" class="form-select">
                                        <option value="0"><?=_t('Qualquer tipo')?></option>
                                        <?php 
                                        $tipos = mysqli_query($GLOBALS['dblink'], "SELECT * FROM entidades_tipos WHERE id_realidade = ".$id_realidade.";") or die(mysqli_error($GLOBALS['dblink']));
                                        while ($tipo = mysqli_fetch_assoc($tipos)) {
                                            echo '<option value="'.$tipo['id'].'"';
                                            echo '>'.$tipo['nome'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Descrição')?></label>
                            <textarea class="form-control" id="descricao" rows="5" onchange="showGravarStat()" placeholder="<?=_t('Ex.: Número de habitantes de uma entidade')?>"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function gravarStat() {
    if ($('#nome').val() == '') {
        $('#nome').addClass('is-invalid');
        return;
    }
    $('#nome').removeClass('is-invalid');

    $.post("?action=ajaxGravarStat&sid=" + $('#idStat').val() + "&rid=<?=$id_realidade?>", {
        nome: $('#nome').val(),
        tipo_dado: $('#tipo_dado').val(),
        tipo_entidade: $('#tipo_entidade').val(),
        descricao: $('#descricao').val()
    }, function(data) {
        if ($.trim(data) > 0) {
            $('#idStat').val($.trim(data));
            $("#statsTable").load("?action=listStats&rid=<?=$id_realidade?>");
            $('#btnSalvar').hide();
            abrirStat($.trim(data));
        } else {
            alert(data);
        }
    });
}

function abrirStat(sid) {
    $(".list-group-item").removeClass("card card-active bg-primary-lt");
    $("#row_" + sid).addClass("card card-active bg-primary-lt");
    $('#idStat').val(sid);
    $.getJSON("?action=getDetalhesStat&sid=" + sid, function(data) {
        $.each(data, function(key, val) {
            $('#nome').val(data[0].nome);
            $('#tipo_dado').val(data[0].tipo_dado);
            $('#tipo_entidade').val(data[0].tipo_entidade);
            $('#descricao').val(data[0].descricao);
            updateTablerSelect('tipo_dado', data[0].tipo_dado);
            updateTablerSelect('tipo_entidade', data[0].tipo_entidade);
            $('#btnSalvar').hide();
        });
    });
}

function showGravarStat() {
    $('#btnSalvar').show();
}

function novoStat() {
    $('#idStat').val(0);
    $('#nome').val('');
    $('#tipo_dado').val('integer');
    $('#tipo_entidade').val('0');
    $('#descricao').val('');
    updateTablerSelect('tipo_dado', 'integer');
    updateTablerSelect('tipo_entidade', '0');
    $('#btnSalvar').hide();
    $("#statsTable").load("?action=listStats&rid=<?=$id_realidade?>");
}

function delStat(sid) {
    if (confirm("<?=_t('Apagar este tipo de desenvolvimento? Isso removerá todos os valores associados!')?>")) {
        $.get("?action=ajaxDelStat&sid=" + sid, function(data) {
            if ($.trim(data) == 'ok') {
                $("#statsTable").load("?action=listStats&rid=<?=$id_realidade?>");
                if ($('#idStat').val() == sid) {
                    novoStat();
                }
            } else {
                alert(data);
            }
        });
    }
}

$(document).ready(function() {
    <?php if ($sid > 0) echo "abrirStat($sid);"; else echo "novoStat();"; ?>
});

formatarTablerSelect('tipo_dado');
formatarTablerSelect('tipo_entidade');
</script>
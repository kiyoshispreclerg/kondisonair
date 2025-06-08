<!-- PANEL START -->
<?php 
$id_realidade = $_GET['rid'];
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

$rule = 'other';
$ruleTitle = 'Entidades';
$ruleAll = 'Todas as entidades';
$ruleTipos = 'Tipos de entidades';

if ($_GET['et']=='character') {
    $rule = 'character';
    $ruleTitle = 'Personagens';
    $ruleAll = 'Todos os personagens';
    $ruleTipos = 'Tipos de personagens';
} else if ($_GET['et']=='place') {
    $rule = 'place';
    $ruleTitle = 'Lugares';
    $ruleAll = 'Todos os lugares';
    $ruleTipos = 'Tipos de lugares';
} else if ($_GET['et']=='item') {
    $rule = 'item';
    $ruleTitle = 'Itens';
    $ruleAll = 'Todos os itens';
    $ruleTipos = 'Tipos de itens';
}

?>
<input type="hidden" id="codigo" value="<?=$id_realidade?>" />
<input type="hidden" id="idTipoEntidade" value="0" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item"><a href="?page=editworld&rid=<?=$id_realidade?>"><?=$realidade['titulo']?></a></li>
                        <li class="breadcrumb-item"><a href="?page=editentities&rid=<?=$id_realidade?>&et=<?=$rule?>"><?=_t($ruleTitle)?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t($ruleTipos)?></a></li>
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
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t($ruleTipos)?></h3>
                        <div class="card-actions">
                            <a onclick="novoTipo()" class="btn btn-primary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                <?=_t('Novo tipo')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-bodyx">
                        <div class="list-group list-group-flush overflow-auto" id="entityTypesTable" style="max-height: 35rem"></div>
                    </div>
                </div>
            </div>

            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Informações')?></h3>
                        <div class="card-actions">
                            <a id="btnSalvar" onclick="gravarTipo()" class="btn btn-primary" style="display:none">
                                Salvar
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-6">
                                <label class="form-label"><?=_t('Nome')?></label>
                                <input type="text" class="form-control" id="nome" onchange="showGravarTipo()" placeholder="<?=_t('Ex.: Local')?>">
                            </div>
                            <div class="mb-3 col-6">
                                <label class="form-label"><?=_t('Tipo pai')?></label>
                                <select id="superior" onchange="showGravarTipo()" type="text" class="form-select" value="">
                                    <option value="0" selected><?=_t('Nenhum')?></option>
                                    <?php 
                                    $tipos = mysqli_query($GLOBALS['dblink'], "SELECT * FROM entidades_tipos WHERE id_realidade = $id_realidade AND rule = '$rule';") or die(mysqli_error($GLOBALS['dblink']));
                                    while ($tipo = mysqli_fetch_assoc($tipos)) {
                                        echo '<option value="'.$tipo['id'].'"';
                                        echo '>'.$tipo['nome'].'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Descrição')?></label>
                            <textarea class="form-control" id="descricao" rows="5" onchange="showGravarTipo()" placeholder="<?=_t('Ex.: Entidades que representam lugares físicos')?>"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Estatísticas Permitidas')?></label>
                            <div class="card" style="max-height: 200px; overflow-y: auto;">
                                <div class="card-body p-3">
                                    <div id="statsList" class="row">
                                        <!-- Checkboxes serão carregados via AJAX -->
                                    </div>
                                </div>
                            </div>
                            <button id="btnSalvarStats" class="btn btn-primary mt-2" style="display:none;" onclick="gravarStatsTipo()"><?=_t('Salvar Estatísticas')?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function gravarTipo() {
    if ($('#nome').val() == '') return;
    
    $.post("?action=ajaxGravarEntityType&tid=" + $('#idTipoEntidade').val() + "&rule=<?=$rule?>&rid=<?=$id_realidade?>", {
        nome: $('#nome').val(),
        superior: $('#superior').val(),
        descricao: $('#descricao').val()
    }, function(data) {
        if ($.trim(data) > 0) {
            $('#idTipoEntidade').val($.trim(data));
            $("#entityTypesTable").load("?action=listEntityTypes&rid=<?=$id_realidade?>&rule=<?=$rule?>");
            $('#btnSalvar').hide();
            carregarStatsTipo($.trim(data));
        } else {
            alert(data);
        }
    });
}

function abrirTipo(tid,superior) {
    $(".list-group-item").removeClass("card card-active bg-primary-lt");        
    $("#row_" + tid).addClass("card card-active bg-primary-lt");
    $('#idTipoEntidade').val(tid); 
    $.getJSON("?action=getDetalhesEntityType&tid=" + tid, function(data) {
        $.each(data, function(key, val) {
            $('#nome').val(data[0].nome); 
            $('#descricao').val(data[0].descricao); 
            $('#superior').val(data[0].superior); 
            updateTablerSelect('superior', data[0].id_superior);
            $('#btnSalvar').hide();
        });
    });
    carregarStatsTipo(tid);
}

function showGravarTipo() {
    $('#btnSalvar').show(); 
}

function novoTipo() {
    $('#idTipoEntidade').val(0); 
    $('#nome').val(''); 
    $('#descricao').val(''); 
    $('#superior').val(0); 
    updateTablerSelect('superior', 0);
    $('#statsList').html('<p><?=_t('Salve o tipo de entidade primeiro.')?></p>');
    $('#btnSalvar').hide(); 
    $("#entityTypesTable").load("?action=listEntityTypes&rid=<?=$id_realidade?>&rule=<?=$rule?>");
}

function carregarStatsTipo(tid) {
    if (tid == 0) {
        $('#statsList').html('<p><?=_t('Salve o tipo de entidade primeiro.')?></p>');
        $('#btnSalvarStats').hide();
        return;
    }
    $.getJSON("?action=getStatsTipo&tid=" + tid + "&rid=<?=$id_realidade?>", function(data) {
        let html = '';
        $.each(data.stats, function(i, stat) {
            //let checked = data.associados.includes(stat.id) ? 'checked' : '';
            let checked = data.associados.includes(parseInt(stat.id)) ? 'checked' : '';
            html += `
                <div class="form-check col-md-6">
                    <input class="form-check-input stat-checkbox" type="checkbox" value="${stat.id}" id="stat_${stat.id}" ${checked} onchange="showGravarStats()">
                    <label class="form-check-label" for="stat_${stat.id}">
                        ${stat.nome} <small>(${stat.tipo_dado == 'integer' ? '<?=_t('Inteiro')?>' : stat.tipo_dado == 'decimal' ? '<?=_t('Decimal')?>' : '<?=_t('Texto')?>'}) | ${stat.entidade}</small>
                    </label>
                </div>`;
        });
        $('#statsList').html(html || '<p><?=_t('Nenhuma estatística disponível.')?></p>');
        $('#btnSalvarStats').toggle(data.stats.length > 0);
    });
}

function showGravarStats() {
    $('#btnSalvarStats').show();
}

function gravarStatsTipo() {
    let tid = $('#idTipoEntidade').val();
    if (tid == 0) return;
    
    let statsSelecionados = [];
    $('.stat-checkbox:checked').each(function() {
        statsSelecionados.push($(this).val());
    });

    $.post("?action=ajaxGravarStatsTipo&tid=" + tid + "&rid=<?=$id_realidade?>", {
        stats: statsSelecionados
    }, function(data) {
        if ($.trim(data) == 'ok') {
            $('#btnSalvarStats').hide();
            carregarStatsTipo(tid); // Recarregar para confirmar
        } else {
            alert(data);
        }
    });
}

function delTipo(tid) {
    if (confirm("<?=_t('Apagar este tipo de entidade?')?>")) {
        $.get("?action=ajaxDelEntityType&tid=" + tid, function(data) {
            if ($.trim(data) == 'ok') {
                $("#entityTypesTable").load("?action=listEntityTypes&rid=<?=$id_realidade?>&rule=<?=$rule?>");
            } else {
                alert(data);
            }
        });
    }
}

$(document).ready(function() {
    novoTipo();
});

formatarTablerSelect('superior');
</script>
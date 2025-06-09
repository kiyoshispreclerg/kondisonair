<!-- PANEL START -->
<?php 
$id_realidade = $_GET['rid'];
$filtro = 'all';
if (isset($_GET['t']) && $_GET['t'] != '') $filtro = $_GET['t'];

$rule = 'other';
$ruleTitle = 'Entidades';
$ruleAll = 'Todas as entidades';
$ruleGet = 'entities';
$ruleManage = 'Tipos de entidades';

if ($_GET['et']=='character') {
    $rule = 'character';
    $ruleTitle = 'Personagens';
    $ruleAll = 'Todos os personagens';
    $ruleGet = 'characters';
    $ruleManage = 'Tipos de personagens';
} else if ($_GET['et']=='place') {
    $rule = 'place';
    $ruleTitle = 'Lugares';
    $ruleAll = 'Todos os lugares';
    $ruleGet = 'places';
    $ruleManage = 'Tipos de lugares';
} else if ($_GET['et']=='item') {
    $rule = 'item';
    $ruleTitle = 'Itens';
    $ruleAll = 'Todos os itens';
    $ruleGet = 'items';
    $ruleManage = 'Tipos de itens';
}

if (!$_GET['eid'] > 0) $_GET['eid'] = 0;
$realidade = array();   
$result = mysqli_query($GLOBALS['dblink'], "SELECT r.*, 
        (SELECT nome_legivel FROM idiomas d WHERE d.id = r.id_idioma_descricao LIMIT 1) as desc_idioma,
        (SELECT id FROM collabs_realidades WHERE id_realidade = r.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab 
        FROM realidades r WHERE id = '".$id_realidade."';") or die(mysqli_error($GLOBALS['dblink']));
while ($r = mysqli_fetch_assoc($result)) { 
    $realidade = $r;
}
if ($realidade['titulo'] == '' || ($realidade['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$realidade['collab'] > 0)) {
    echo '<script>window.location = "index.php";</script>';
    exit;
}
?>

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item"><a href="?page=editworld&rid=<?=$id_realidade?>"><?=$realidade['titulo']?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t($ruleTitle)?></a></li>
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
            <div class="col-md-3">
                <div class="card sticky-top">
                    <div class="card-body"> 
                        <div class="mb-3" id="filter-div">
                            <label class="form-label"><?=_t('Buscar')?></label>
                            <input type="text" class="form-control" id="testFilter" onkeyup="filtrarEntidades(false)" placeholder="<?=_t('Pesquisar nome/descrição')?>">
                        </div>

                        <div class="mb-4">
                            <div class="form-label"><?=_t('Filtrar')?></div>
                            <select class="form-select" id="filtro" title="<?=_t('Filtrar por')?>..." type="text" value="" onchange="filtrarEntidades(true)">
                                <option value="all" title="<?=_t($ruleAll)?>" <?php if($filtro=='all') echo 'selected'; ?>><?=_t($ruleAll)?></option>
                                <option disabled><?=_t('Tipos de entidades')?></option>
                                <?php 
                                $tipos = mysqli_query($GLOBALS['dblink'], "SELECT * FROM entidades_tipos WHERE id_realidade = $id_realidade AND rule = '$rule';") or die(mysqli_error($GLOBALS['dblink']));
                                while ($tipo = mysqli_fetch_assoc($tipos)) {
                                    echo '<option value="'.$tipo['id'].'" title="'.$tipo['descricao'].'"';
                                    if ($filtro == $tipo['id']) echo ' selected';
                                    echo '>'.$tipo['nome'].'</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mt-5">
                            <a class="btn btn-primary w-100 mt-2" title="<?=_t($ruleManage)?>" href="?page=editentitytypes&rid=<?=$id_realidade?>&et=<?=$rule?>"><?=_t($ruleManage)?></a>
                        </div>

                        <div class="mt-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" onchange="$('#entityTable').toggleClass('listaLonga');">
                                <span class="form-check-label"><?=_t('Página curta')?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Entidades')?></h3>
                        <div class="card-actions">
                            <div class="row">
                                <div class="col">
                                    <a href="index.php?page=editentity&rid=<?=$id_realidade?>&et=<?=$rule?>" class="btn btn-primary d-none d-sm-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                        <?=_t('Nova entidade')?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-bodyx">
                        <div class="list-group list-group-flush overflow-auto" id="entityTable"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>.listaLonga{max-height: 35rem}</style>

<script>
function delEntity(eid) {
    if (confirm('<?=_t('Apagar esta entidade permanentemente?')?>')) {
        $.get("api.php?action=ajaxApagarEntidade&eid=" + eid, function(data) {
            if ($.trim(data) == '1') {
                window.location.reload(true);
            } else {
                alert(data);
            }
        });
    }
}

function listFormat(json) {
    let html = "";
    data = JSON.parse(json);
    $.each(data, function(key, val) {
        html += `<div data-search="` + val.search +
            `" class="list-group-item divEntity" ` + val.indexdata + `><div class="row">
              <div class="col-auto">
                <a href="?page=editentity&rid=<?=$id_realidade?>&eid=` + val.id + `" >` + val.nome + `</a>
              </div>
              <div class="col text-truncate">
                <a href="?page=editentity&rid=<?=$id_realidade?>&et=<?=$rule?>&eid=` + val.id + `" class="text-body d-block">` + val.descricao + `</a>
                <div class="text-secondary text-truncate">` + val.tipo_nome + `</div>
              </div>
              <div class="col-auto"><a class="btn btn-sm btn-danger" onclick="delEntity(` + val.id + `)">Del</a></div>
          </div></div>`;
    });
    return html;
}

function loadEntidades(index = 0, forceReload = false) {
    $("#testFilter").val('');
    $("#filtro").val('all');
    $("#filter-div").show();
    
    $.get("api.php?action=getLastChange&data=<?=$ruleGet?>&rid=<?=$id_realidade?>", function(data) {
        if (forceReload || data > localStorage.getItem("k_<?=$ruleGet?>_<?=$id_realidade?>_updated")) {
            console.log('local <?=$ruleGet?> outdated > update');
            $.get("api.php?action=listEntities&et=<?=$rule?>&rid=<?=$id_realidade?>&t=all&i=" + index, function(entities) {
                $("#entityTable").html(listFormat(entities));
                localStorage.setItem("k_<?=$ruleGet?>_<?=$id_realidade?>", entities);
                localStorage.setItem("k_<?=$ruleGet?>_<?=$id_realidade?>_updated", data);
                $('[data-bs-toggle="tooltip"]').tooltip();
            });
        } else {
            console.log('local <?=$ruleGet?> load');
            $("#entityTable").html(listFormat(localStorage.getItem("k_<?=$ruleGet?>_<?=$id_realidade?>")));
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
}

function filtrarEntidades(reset = false) {
    if (!reset) {
        if ($("#filtro").val() > 0) testFilter('divEntity', "testFilter", $("#filtro").val());
        else testFilter('divEntity', "testFilter");
    } else if ($("#filtro").val() == 'all') {
        loadEntidades();
        $("#testFilter").val("");
    } else if ($("#filtro").val() > 0) {
        $.get("api.php?action=listEntities&rid=<?=$id_realidade?>&t=" + $("#filtro").val() + "&i=0", function(entities) {
            $("#entityTable").html(listFormat(entities));
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
        $("#testFilter").val("");
    }
}

$(document).ready(function() {
    loadEntidades();
});
</script>
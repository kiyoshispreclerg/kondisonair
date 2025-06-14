<?php
$id_realidade = $_GET['rid'];
$hid = $_GET['hid'] ?? 0;
$superior = $_GET['superior'] ?? 0;

$realidade = array();
$result = mysqli_query($GLOBALS['dblink'], "SELECT *,
    (SELECT id FROM collabs_realidades WHERE id_realidade = r.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
    FROM realidades r WHERE id = '".$id_realidade."';") or die(mysqli_error($GLOBALS['dblink']));
while ($r = mysqli_fetch_assoc($result)) {
    $realidade = $r;
}

if ($realidade['titulo'] == '' || ($realidade['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$realidade['collab'] > 0)) {
    echo '<script>window.location = "index.php";</script>';
    exit;
}

if ($hid > 0) {
    $result = mysqli_query($GLOBALS['dblink'], "SELECT id FROM historias WHERE id = $hid AND id_realidade = $id_realidade LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    if (!mysqli_fetch_assoc($result)) {
        echo '<script>window.location = "index.php";</script>';
        exit;
    }
}
?>

<input type="hidden" id="codigo" value="<?=$id_realidade?>" />
<input type="hidden" id="idHistoria" value="<?=$hid?>" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item"><a href="?page=editworld&rid=<?=$id_realidade?>"><?=$realidade['titulo']?></a></li>
                        <?php
                        $breadcrumbs = [];
                        $current_superior = $superior;
                        
                        while ($current_superior > 0) {
                            $result = mysqli_query($GLOBALS['dblink'], "SELECT id, titulo, id_superior 
                                FROM historias 
                                WHERE id = $current_superior AND id_realidade = $id_realidade LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
                            if ($h = mysqli_fetch_assoc($result)) {
                                $breadcrumbs[] = [
                                    'id' => $h['id_superior'],
                                    'titulo' => htmlspecialchars($h['titulo'])
                                ];
                                $current_superior = (int)$h['id_superior'];
                            } else {
                                break; // Evitar loop infinito se id_superior inválido
                            }
                        }
                        
                        // Exibir breadcrumbs em ordem inversa (da raiz para o nível atual)
                        foreach (array_reverse($breadcrumbs) as $b) {
                            echo '<li class="breadcrumb-item"><a href="?page=editstories&rid='.$id_realidade.'&superior='.$b['id'].'">'.$b['titulo'].'</a></li>';
                        }
                        ?>
                        <li class="breadcrumb-item active"><a><?=_t('Histórias')?></a></li>
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
                        <h3 class="card-title"><?=_t('Histórias')?></h3>
                        <div class="card-actions">
                            <a onclick="novaHistoria()" class="btn btn-primary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                <?=_t('Nova história')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-bodyx">
                        <div class="list-group list-group-flush overflow-auto" id="storiesTable" style="max-height: 35rem"></div>
                    </div>
                </div>
            </div>

            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Informações')?></h3>
                        <div class="card-actions">
                            <a id="btnSalvar" onclick="gravarHistoria()" class="btn btn-primary" style="display:none">
                                <?=_t('Salvar')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-6">
                                <label class="form-label"><?=_t('Título')?>*</label>
                                <input type="text" class="form-control" id="titulo" onchange="showGravarHistoria()" placeholder="<?=_t('Ex.: A Batalha de Eldoria')?>">
                            </div>
                            <div class="mb-3 col-3">
                                <label class="form-label"><?=_t('Status')?>*</label>
                                <select id="status" onchange="showGravarHistoria()" class="form-select">
                                    <option value="rascunho"><?=_t('Rascunho')?></option>
                                    <option value="publicado"><?=_t('Publicado')?></option>
                                    <option value="arquivado"><?=_t('Arquivado')?></option>
                                </select>
                            </div>
                            <div class="mb-3 col-3">
                                <label class="form-label"><?=_t('Tipo de História')?></label>
                                <select id="id_tipo" onchange="showGravarHistoria()" class="form-select">
                                    <option value="0" selected><?=_t('Nenhum')?></option>
                                    <?php
                                    $tipos = mysqli_query($GLOBALS['dblink'], "SELECT id, titulo as nome FROM historias_tipos WHERE id_realidade = $id_realidade;") or die(mysqli_error($GLOBALS['dblink']));
                                    while ($t = mysqli_fetch_assoc($tipos)) {
                                        echo '<option value="'.$t['id'].'">'.$t['nome'].'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?=_t('Descrição')?></label>
                            <textarea class="form-control" id="descricao" rows="5" onchange="showGravarHistoria()" placeholder="<?=_t('Ex.: Resumo da história')?>"></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="row">

                                <div class="mb-3 col-3">

                                    <label class="form-label"><?=_t('Filtrar entidades')?></label>
                                    <input type="text" class="form-control mb-3" id="searchEntidades" placeholder="<?=_t('Pesquisar entidades...')?>" onkeyup="filterEntidades()">

                                    <label class="form-label"><?=_t('Momento')?> <a class="btn btn-sm" onclick="$('#dateNovoModal').modal('show');updateTablerSelect('m-id_superior', 0);">Novo</a></label>
                                    <select id="id_momento" onchange="showGravarHistoria()" class="form-select">
                                        <option value="0" selected><?=_t('Nenhum')?></option>
                                        <?php
                                        $momentos = mysqli_query($GLOBALS['dblink'], "SELECT id, nome, time_value, data_calendario FROM momentos WHERE id_realidade = $id_realidade ORDER BY time_value, ordem;") or die(mysqli_error($GLOBALS['dblink']));
                                        while ($m = mysqli_fetch_assoc($momentos)) {
                                            echo '<option value="'.$m['id'].'" data-date="'.$m['data_calendario'].'">'.$m['nome'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-9">
                                    <label class="form-label"><?=_t('Entidades relacionadas')?></label>
                                    <!-- Lista de entidades -->
                                    <div id="entidadesContainer">
                                        <?php
                                        // Query entities with their types and stats
                                        $entidades = mysqli_query($GLOBALS['dblink'], "SELECT e.id, e.nome_legivel AS nome, e.id_tipo, et.nome AS tipo_nome
                                            FROM entidades e
                                            LEFT JOIN entidades_tipos et ON et.id = e.id_tipo AND et.id_realidade = $id_realidade
                                            WHERE e.id_realidade = $id_realidade
                                            GROUP BY e.id, e.nome_legivel, e.id_tipo, et.nome
                                            ORDER BY et.nome, e.nome_legivel;") or die(mysqli_error($GLOBALS['dblink']));

                                        // Group entities by type
                                        $entidades_por_tipo = [];
                                        while ($e = mysqli_fetch_assoc($entidades)) {
                                            $tipo = $e['tipo_nome'] ?: _t('Sem Tipo'); // Fallback for entities with no type
                                            $entidades_por_tipo[$tipo][] = $e;
                                        }

                                        // Render entities grouped by type
                                        if (empty($entidades_por_tipo)) {
                                            echo '<p class="text-muted">' . _t('Nenhuma entidade encontrada.') . '</p>';
                                        } else {
                                            foreach ($entidades_por_tipo as $tipo => $entidades) {
                                                echo '<div class="tipo-grupo" data-tipo="' . htmlspecialchars($tipo) . '">';
                                                echo '<h6 class="mt-3">' . htmlspecialchars($tipo) . '</h6>';
                                                echo '<hr class="my-2">';
                                                echo '<div class="row">';
                                                foreach ($entidades as $e) {
                                                    $stats = $e['stats'] ? ' (' . htmlspecialchars($e['stats']) . ')' : '';
                                                    echo '<div class="col-4 entidade-item" data-nome="' . htmlspecialchars(strtolower($e['nome'])) . '">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="entidades[]" value="' . $e['id'] . '" id="entidade_' . $e['id'] . '">
                                                            <label class="form-check-label" for="entidade_' . $e['id'] . '">' . htmlspecialchars($e['nome']) . $stats . '</label>
                                                        </div>
                                                    </div>';
                                                }
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function gravarHistoria() {
    if ($('#titulo').val() == '') {
        $('#titulo').addClass('is-invalid');
        return;
    }
    $('#titulo').removeClass('is-invalid');

    // Coletar entidades selecionadas
    let entidades = [];
    $('input[name="entidades[]"]:checked').each(function() {
        entidades.push($(this).val());
    });

    $.post("?action=ajaxGravarHistoria&hid=" + $('#idHistoria').val() + "&update=1&rid=<?=$id_realidade?>", {
        titulo: $('#titulo').val(),
        status: $('#status').val(),
        id_superior: '<?=$superior?>',
        id_tipo: $('#id_tipo').val(),
        id_momento: $('#id_momento').val(),
        descricao: $('#descricao').val(),
        entidades: entidades
    }, function(data) {
        if ($.trim(data) > 0) {
            $('#idHistoria').val($.trim(data));
            $("#storiesTable").load("?action=listStories&rid=<?=$id_realidade?>&superior=<?=$superior?>");
            $('#btnSalvar').hide();
            abrirHistoria($.trim(data));
        } else {
            alert(data);
        }
    });
}

function abrirHistoria(hid) {
    $("#storiesTable").load("?action=listStories&rid=<?=$id_realidade?>&superior=<?=$superior?>");
    $(".list-group-item").removeClass("card card-active bg-primary-lt");
    $('#idHistoria').val(hid);
    $.getJSON("?action=getDetalhesHistoria&hid=" + hid, function(data) {
        $.each(data, function(key, val) {
            $('#titulo').val(data[0].titulo);
            $('#status').val(data[0].status);
            $('#id_tipo').val(data[0].id_tipo);
            $('#id_momento').val(data[0].id_momento);
            $('#descricao').val(data[0].descricao);
            updateTablerSelect('status', data[0].status);
            updateTablerSelect('id_tipo', data[0].id_tipo);
            updateTablerSelect('id_momento', data[0].id_momento);
            $('#btnSalvar').hide();
            // Marcar entidades selecionadas
            $('input[name="entidades[]"]').prop('checked', false);
            if (data[0].entidades) {
                data[0].entidades.forEach(function(eid) {
                    $('#entidade_' + eid).prop('checked', true);
                });
            }
        });
        $("#row_" + hid).addClass("card card-active bg-primary-lt");
    });
}

function showGravarHistoria() {
    $('#btnSalvar').show();
}

function novaHistoria() {
    $('#idHistoria').val(0);
    $('#titulo').val('');
    $('#status').val('rascunho');
    $('#id_tipo').val(0);
    $('#id_momento').val(0);
    $('#descricao').val('');
    $('input[name="entidades[]"]').prop('checked', false); // Desmarcar todas as entidades
    $("#storiesTable").load("?action=listStories&rid=<?=$id_realidade?>&superior=<?=$superior?>");
    updateTablerSelect('status', 'rascunho');
    updateTablerSelect('id_tipo', 0);
    updateTablerSelect('id_momento', 0); // m-id_superior
    $('#btnSalvar').hide();
}

function delHistoria(hid) {
    if (confirm("<?=_t('Apagar esta história?')?>")) {
        $.get("?action=ajaxDelHistoria&hid=" + hid, function(data) {
            if ($.trim(data) == 'ok') {
                $("#storiesTable").load("?action=listStories&rid=<?=$id_realidade?>&superior=<?=$superior?>");
                if ($('#idHistoria').val() == hid) {
                    novaHistoria();
                }
            } else {
                alert(data);
            }
        });
    }
}

$(document).ready(function() {

    loadDefaultTimeSystem();

    <?php if ($hid > 0) echo "abrirHistoria($hid);"; else echo "novaHistoria();"; ?>
    
    // Mostrar botão Salvar quando entidades forem alteradas
    $('input[name="entidades[]"]').change(function() {
        showGravarHistoria();
    });
});
formatarTablerSelect('status');
formatarTablerSelect('id_tipo');
formatarTablerMomentsSelect('id_momento');
formatarTablerMomentsSelect('m-id_superior',null);


function filterEntidades() {
    // Obtém o valor da caixa de pesquisa e converte para minúsculas
    const searchText = document.getElementById('searchEntidades').value.toLowerCase();
    
    // Seleciona todos os grupos de tipo e itens de entidade
    const tipoGrupos = document.querySelectorAll('.tipo-grupo');
    const entidadeItems = document.querySelectorAll('.entidade-item');

    // Filtra entidades
    entidadeItems.forEach(item => {
        const nome = item.getAttribute('data-nome');
        // Mostra ou esconde o item com base no texto de pesquisa
        item.style.display = nome.includes(searchText) ? 'block' : 'none';
    });

    // Verifica se cada grupo de tipo tem entidades visíveis e mostra/esconde o grupo
    tipoGrupos.forEach(grupo => {
        const entidadesVisiveis = grupo.querySelectorAll('.entidade-item:not([style*="none"])');
        grupo.style.display = entidadesVisiveis.length > 0 ? 'block' : 'none';
    });
}






function loadDefaultTimeSystem() {
    $.getJSON(`?action=getDefaultTimeSystem&rid=<?=$id_realidade?>`, function(system) {
        if (system.id) {
            //$('#id_time_system').val(system.id);
            carregarCalendario(system.id)
        } else {
            $('#time-value').val('<?=_t('Nenhum sistema de tempo padrão definido.')?>');
            $('#dateInputs').html('<p><?=_t('Nenhum sistema de tempo padrão definido.')?></p>');
        }
    });
}

function carregarCalendario(sid) {
    let html = `<div class="d-flex mb-3 align-items-center">
            <div class="input-group me-2">
                <button class="btn btn-icon btn-outline-secondary" onclick="decrementYear('')">-</button>
                <input type="number" class="form-control" id="c-year" value="0" placeholder="Ano" min="-1000000" max="1000000">
                <button class="btn btn-icon btn-outline-secondary" onclick="incrementYear('')">+</button>
            </div>
            <input type="hidden" id="time-value">
            <input type="hidden" id="data-calendario">
            <div class="input-group">
                <button class="btn btn-icon btn-outline-secondary" onclick="decrementMonth('')">-</button>
                <select class="form-select" id="c-month"></select>
                <button class="btn btn-icon btn-outline-secondary" onclick="incrementMonth('')">+</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" id="c-calendar-table">
                <thead>
                    <tr id="c-calendar-days"></tr>
                </thead>
                <tbody id="c-calendar-body"></tbody>
            </table>
            <div id="c-calendar-warnings"></div>
        </div>`;
    $("#dateInputs").html(html);
    loadCalendar(
        'c-calendar',
        'c-year',
        'c-month',
        'c-calendar-days',
        'c-calendar-body',
        null,
        sid, 0, 0,
        'time-value',
        'data-calendario', '<?=$id_realidade?>'
    );
}


function saveDateSelection() {

    $('#m-time_value').val( $('#time-value').val() );
    $('#m-data_calendario').val( $('#data-calendario').val() );
    gravarMomento();
    $('#dateNovoModal').modal('hide');
}

function gravarMomento() {
    if ($('#m-nome').val() == '' || $('#m-data_calendario').val() == '') {
        $('#m-nome').addClass('is-invalid');
        $('#m-data_calendario').addClass('is-invalid');
        // $('#ordem').addClass('is-invalid');
        return;
    }
    $('#m-nome').removeClass('is-invalid');
    $('#m-data_calendario').removeClass('is-invalid');

    $.post("?action=ajaxGravarMomento&mid=" + $('#m-mid').val() + "&rid=<?=$id_realidade?>", {
        nome: $('#m-nome').val(),
        descricao: $('#m-descricao').val(),
        superior: $('#m-id_superior').val(),
        data_calendario: $('#m-data_calendario').val(),
        time_system: $('#m-id_time_system').val(),
        time_value: $('#m-time_value').val(),
        ordem: $('#m-ordem').val()
    }, function(data) {
        if ($.trim(data) > 0) {
            addMomentTablerSelect($.trim(data), $('#m-time_value').val(), $('#m-nome').val(), 'id_momento');
        } else {
            alert(data);
        }
    });
}

function setDateClicked(timeValue, message){
    //$('#time_value').val( $('#time-value').val() );
    //$('#dateNovoModal').modal('hide');
    //alert('data clicada '+timeValue);
    // gravarMomento();
}

</script>


<!-- Modal de Seleção de Data -->
<div class="modal modal-blur" id="dateNovoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=_t('Novo momento')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <input type="hidden" id="m-mid"  value="0" />
                <input type="hidden" id="m-ordem">


                <input type="hidden" id="m-id_time_system">
                <input type="hidden" id="m-time_value">
                <input type="hidden" id="m-data_calendario">
                <input type="hidden" id="m-unit_values" value="{}">

                <div class="mb-3">
                    <label class="form-label"><?=_t('Nome')?>*</label>
                    <input type="text" class="form-control" id="m-nome" placeholder="<?=_t('Ex.: Batalha de Eldoria')?>">
                </div> 
                <div class="mb-3">
                    <label class="form-label"><?=_t('Momento pai')?>*</label>
                    <select id="m-id_superior" class="form-select">
                        <option value="0" selected><?=_t('Nenhum')?></option>
                        <?php
                        $momentos = mysqli_query($GLOBALS['dblink'], "SELECT id, nome, time_value, data_calendario FROM momentos WHERE id_realidade = $id_realidade ORDER BY time_value, ordem;") or die(mysqli_error($GLOBALS['dblink']));
                        while ($m = mysqli_fetch_assoc($momentos)) {
                            echo '<option value="'.$m['id'].'" data-date="'.$m['data_calendario'].'">'.$m['nome'].'</option>';
                        }
                        ?>
                    </select>
                </div> 
                <div class="mb-3">
                    <label class="form-label"><?=_t('Descrição')?></label>
                    <textarea class="form-control" id="m-descricao" rows="5" placeholder="<?=_t('Ex.: Descrição do momento')?>"></textarea>
                </div>
                
                <div id="dateInputs"></div>
                <button type="button" class="btn btn-primary mt-3" onclick="saveDateSelection()"><?=_t('Confirmar')?></button>
            </div>
        </div>
    </div>
</div>
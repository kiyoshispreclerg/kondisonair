<?php
$id_realidade = $_GET['rid'];
$eid = $_GET['eid'] ?? 0;

$realidade = [];
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

if ($eid > 0) {
    $result = mysqli_query($GLOBALS['dblink'], "SELECT id, rule FROM entidades WHERE id = $eid AND id_realidade = $id_realidade LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    $e = mysqli_fetch_assoc($result);
    if (!$e['id']>0) {
        echo '<script>window.location = "index.php";</script>';
        exit;
    }
}

$rule = 'other';
$ruleTitle = 'Entidades';
$ruleAll = 'Todas as entidades';
$ruleTipos = 'Tipos de entidades';
$ruleSingle = 'Entidade';

if ($_GET['et']=='character' || $e['rule']=='character') {
    $rule = 'character';
    $ruleTitle = 'Personagens';
    $ruleAll = 'Todos os personagens';
    $ruleTipos = 'Tipos de personagens';
    $ruleSingle = 'Personagem';
} else if ($_GET['et']=='place' || $e['rule']=='place') {
    $rule = 'place';
    $ruleTitle = 'Lugares';
    $ruleAll = 'Todos os lugares';
    $ruleTipos = 'Tipos de lugares';
    $ruleSingle = 'Lugar';
} else if ($_GET['et']=='item' || $e['rule']=='item') {
    $rule = 'item';
    $ruleTitle = 'Itens';
    $ruleAll = 'Todos os itens';
    $ruleTipos = 'Tipos de itens';
    $ruleSingle = 'Item';
}

?>

<input type="hidden" id="codigo" value="<?=$id_realidade?>" />
<input type="hidden" id="idEntidade" value="<?=$eid?>" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item"><a href="?page=editworld&rid=<?=$id_realidade?>"><?=$realidade['titulo']?></a></li>
                        <li class="breadcrumb-item"><a href="?page=editentities&et=<?=$rule?>&rid=<?=$id_realidade?>"><?=_t($ruleTitle)?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t($ruleSingle)?></a></li>
                    </ol>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="?page=editentity&rid=<?=$id_realidade?>&et=<?=$rule?>" class="btn btn-primary d-none d-sm-inline-block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                        <?=_t('Nova')?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl appholder">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card placeholder-glow">
                    <div class="card-body">
                        <div class="placeholder col-9 mb-3"></div>
                        <div class="placeholder placeholder-xs col-10"></div>
                        <div class="placeholder placeholder-xs col-11"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xl appLoad">
        <div class="row row-deck row-cards">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Informações básicas')?></h3>
                        <div class="card-actions">
                            <a href="#" onclick="gravarEntidade()" id="saveBtn" class="btn btn-primary"><?=_t('Salvar')?></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label"><?=_t('Nome legível')?>*</label>
                                    <input type="text" class="form-control" id="nome" autofocus
                                        onkeyup="editarEntidade()" placeholder="<?=_t('Nome da entidade')?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label"><?=_t('Tipo de entidade')?>*</label>
                                    <select id="id_tipo" class="form-select" onchange="editarEntidade()">
                                        <option value="0" selected><?=_t('Nenhum')?></option>
                                        <?php
                                        $tipos = mysqli_query($GLOBALS['dblink'], "SELECT id, nome FROM entidades_tipos WHERE id_realidade = $id_realidade AND rule = '$rule' ORDER BY nome;") or die(mysqli_error($GLOBALS['dblink']));
                                        while ($tipo = mysqli_fetch_assoc($tipos)) {
                                            echo '<option value="'.$tipo['id'].'">'.$tipo['nome'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row" id="nomesIdiomas">
                            </div>
                            <a class="btn btn-sm btn-primary" onClick='$("#modalAdSigIid").modal("show")'><?=_t('Outros nomes')?></a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Descrição curta')?>*</label>
                            <input type="text" class="form-control" id="descricao_curta"
                                onkeyup="editarEntidade()" placeholder="<?=_t('Descrição sucinta (ex.: Planeta desértico)')?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Descrição')?></label>
                            <textarea id="descricao" onkeyup="editarEntidade()"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Informações privadas')?></label>
                            <textarea class="form-control" id="privado" rows="3"
                                onkeyup="editarEntidade()"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Tags')?></label>
                            <select id="id_tags" multiple class="form-select" onchange="editarEntidade()">
                                <?php
                                if ($eid > 0) {
                                    $sql = "SELECT tag FROM tags WHERE tipo_dest = 'entity' AND id_dest = $eid;";
                                    $result = mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
                                    while ($r = mysqli_fetch_assoc($result)) {
                                        echo '<option value="'.$r['tag'].'" selected>'.$r['tag'].'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="row row-cards">
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title"><?=_t('Relações')?></h3>
                                <div class="card-actions">
                                    <a href="#" class="btn btn-primary" onclick="addRelacao(0, 0, '', '', 0, 0)"><?=_t('Adicionar')?></a>
                                </div>
                            </div>
                            <div class="card-body" id="relacoes" style="max-height:25rem;overflow:auto;">
                                <div class="list-group list-group-flush list-group-hoverable">
                                    <a class="btn btn-primary" onclick="carregarRelacoes()"><i class="fa fa-refresh"></i> <?=_t('Carregar')?></a>
                                </div>
                            </div>
                        </div>
                        <div class="card"  >
                            <div class="card-header">
                                <h3 class="card-title"><?=_t('Estatísticas')?></h3>
                                <!--div class="card-actions">
                                    <a href="#" class="btn btn-primary" onclick="addStat(0,0,0,'')"><?=_t('Adicionar')?></a>
                                </div-->
                            </div>
                            <div class="card-body">
                                <div id="stats">
                                </div>
                                <div id="statlist">
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
$(document).ready(function() {
    createTablerSelect('id_tipo');
    createTablerSelect('id_tags', null, true);
    <?php if ($eid > 0) echo "loadEntity(\"$eid\");"; ?>
    setTimeout(() => { 
        createTablerSelect('id_idsig',null);
        appLoad(); },
    300);

    createTablerSelect('id_entidade2',null);
});
formatarTablerMomentsSelect('id_momento_inicio',null);
formatarTablerMomentsSelect('id_momento_fim',null);

function editarEntidade() {
    if ($('#nome').val() === '' || $('#descricao_curta').val() === '') {
        $('#nome').addClass('is-invalid');
        $('#descricao_curta').addClass('is-invalid');
        return;
    }
    $('#nome').removeClass('is-invalid');
    $('#descricao_curta').removeClass('is-invalid');
    $('#saveBtn').show();
}

function gravarEntidade() {
    if ($('#nome').val() === '') {
        $('#nome').addClass('is-invalid');
        return;
    }
    if ($('#descricao_curta').val() === '') {
        $('#descricao_curta').addClass('is-invalid');
        return;
    }
    $('#nome').removeClass('is-invalid');
    $('#descricao_curta').removeClass('is-invalid');

    var oiids = new Array(); var i = 0;
    $(".sigoutros").each(function() {
        oiids.push( { name : $(this).val(), iid : $("#enome_iid_" + i ).val(), info : $("#siginfo_" + i++ ).val() } ); 
    });

    $.post("api.php?action=ajaxGravarEntidade&eid="+$('#idEntidade').val()+"&rid=<?=$id_realidade?>&et=<?=$rule?>", {
        nome: $('#nome').val(),
        id_tipo: $('#id_tipo').val(),
        descricao_curta: $('#descricao_curta').val(),
        descricao: tinymce.get('descricao').getContent(),
        privado: $('#privado').val(),
        oiids:oiids,
        tags: $('#id_tags').val()
    }, function(data) {
        if ($.trim(data) > 0) {
            $('#idEntidade').val($.trim(data));
            $('#saveBtn').hide();
            abrirEntidade($.trim(data));
        } else {
            alert(data);
        }
    });
}

function abrirEntidade(eid) {
    window.location = "index.php?page=editentity&rid=<?=$id_realidade?>&et=<?=$rule?>&eid="+eid;
}

function loadEntity(eid) {
    $.getJSON("api.php?action=ajaxGetDetalhesEntidade&eid="+eid, function(data) {
        //$.each(data, function(key, val) {
            $('#nome').val(data.nome_legivel);
            $('#id_tipo').val(data.id_tipo);
            $('#descricao_curta').val(data.descricao_curta);
            $('#privado').val(data.privado);
            if (data.tags.length > 0) {
                $.each(data.tags.split(","), function(i, e) {
                    $("#id_tags option[value='" + e + "']").prop("selected", true);
                });
            }
            $.each(data.sigiids, function(i,e){
                addSigIidTela(e.iid,e.nome,e.niid,e.eid,e.info);
            });
            
            updateTablerSelect('id_tipo', data.id_tipo);
            //createTablerSelect('id_tags', null, true);
            updateTablerSelect('id_tags', data.tags.split(","));
            $('#saveBtn').hide();
            carregarRelacoes();
            //carregarStats();
            carregarGrafStats();
            setTimeout(() => { tinymce.get('descricao').setContent(data.descricao); }, 350);
            
        //});
    });
}

function carregarRelacoes() {
    $.post("api.php?action=ajaxLoadRelacoes&eid="+$('#idEntidade').val(), function(data) {
        $('#relacoes').html(data);
    });
}

function addRelacao(rid = 0, id_entidade2 = 0, tipo_relacao = '', descricao = '', inicio = 0, fim = 0) {
    $('#rid').val(rid);
    $('#id_entidade2').val(id_entidade2);
    $('#tipo_relacao').val(tipo_relacao);
    updateTablerSelect('id_momento_inicio', inicio);
    updateTablerSelect('id_momento_fim', fim);
    $('#descricao_relacao').val(descricao);
    $('#modal-add-relacao').modal('show');
}

function execAddRelacao() {
    var rid = $('#rid').val();
    var id_entidade2 = $('#id_entidade2').val();
    var tipo_relacao = $('#tipo_relacao').val();
    var descricao = $('#descricao_relacao').val();
    if (id_entidade2 == '0' || tipo_relacao == '') {
        $.alert('<?=_t('Selecione uma entidade e especifique o tipo de relação!')?>');
        return false;
    }
    $.post("api.php?action=ajaxAddRelacao&eid="+$('#idEntidade').val()+"&rid="+rid, {
        id_entidade2: id_entidade2,
        tipo_relacao: tipo_relacao,
        inicio: $('#id_momento_inicio').val(),
        fim: $('#id_momento_fim').val(),
        descricao: descricao
    }, function(data) {
        if (data > 0) {
            carregarRelacoes();
            $('#modal-add-relacao').modal('hide');
        } else {
            alert(data);
        }
    });
}

function apagarStatsPorEntidade(eid, id_stat) {
    if (confirm('Deseja remover todas as estatísticas deste stat para esta entidade?')) {
        $.post('api.php?action=deleteStatsByEntityAndStat', { eid: eid, id_stat: id_stat }, function(response) {
            if (response.success) {
                carregarStats(); // Recarrega a lista após a remoção
            } else {
                alert('Erro ao remover estatísticas: ' + (response.error || 'Erro desconhecido'));
            }
        }, 'json');
    }
}

function apagarRelacao(rid) {
    if (confirm("<?=_t('Deseja mesmo apagar esta relação?')?>")) {
        $.get("api.php?action=ajaxDeleteRelacao&rid="+rid, function(data) {
            if ($.trim(data) == 'ok') {
                carregarRelacoes();
            } else {
                alert(data);
            }
        });
    }
}

function carregarStats() { carregarGrafStats(); return;
    $.post("api.php?action=ajaxLoadStats&eid="+$('#idEntidade').val(), function(data) {
        $('#stats').html(data);
    });
}

function addStat(sid = 0, id_stat = 0, id_momento = 0, valor = '') {
    $('#sid').val(sid);
    $('#id_stat').val(id_stat);
    //updateTablerSelect('id_stat', id_stat);
    //$('#id_momento').val(id_momento);
    updateTablerSelect('id_momento', id_momento);
    $('#valor_stat').val(valor);
    $('#modal-add-stat').modal('show');
}

function execAddStat() {
    var sid = $('#sid').val();
    var id_stat = $('#id_stat').val();
    var id_momento = $('#id_momento').val();
    var valor = $('#valor_stat').val();
    if (id_stat == '0' || id_momento == '0' || valor == '') {
        $.alert('<?=_t('Selecione uma estatística, um momento e insira um valor!')?>');
        return false;
    }
    $.post("api.php?action=ajaxAddStat&eid="+$('#idEntidade').val()+"&sid="+sid, {
        id_stat: id_stat,
        id_momento: id_momento,
        valor: valor
    }, function(data) {
        if (data > 0) {
            carregarStats();
            $('#modal-add-stat').modal('hide');
        } else {
            alert(data);
        }
    });
}

function apagarStat(sid) {
    if (confirm("<?=_t('Deseja mesmo apagar esta estatística?')?>")) {
        $.get("api.php?action=ajaxDeleteStat&sid="+sid, function(data) {
            if ($.trim(data) == 'ok') {
                carregarStats();
            } else {
                alert(data);
            }
        });
    }
}
</script>

<!-- Modal para Adicionar/Editar Relação -->
<div class="modal modal-blur" id="modal-add-relacao" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=_t('Relação')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rid" />
                <div class="mb-3">
                    <label class="form-label"><?=_t('Entidade relacionada')?></label>
                    <select class="form-select" id="id_entidade2">
                        <option value="0" selected><?=_t('Selecione uma entidade')?></option>
                        <?php
                        $entidades = mysqli_query($GLOBALS['dblink'], "SELECT id, nome_legivel as nome FROM entidades WHERE id_realidade = $id_realidade AND id != $eid;") or die(mysqli_error($GLOBALS['dblink']));
                        while ($e = mysqli_fetch_assoc($entidades)) {
                            echo '<option value="'.$e['id'].'">'.$e['nome'].'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label class="form-label"><?=_t('Início')?></label>
                        <select class="form-select" id="id_momento_inicio">
                            <option value="0" selected><?=_t('Desde sempre')?></option>
                            <?php
                            $entidades = mysqli_query($GLOBALS['dblink'], "SELECT *, time_value FROM momentos WHERE id_realidade = $id_realidade ORDER BY time_value, ordem;") or die(mysqli_error($GLOBALS['dblink']));
                            while ($e = mysqli_fetch_assoc($entidades)) {
                                echo '<option value="'.$e['id'].'" data-date="'.$e['data_calendario'].'">'.$e['nome'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label"><?=_t('Fim')?></label>
                        <select class="form-select" id="id_momento_fim">
                            <option value="0" selected><?=_t('Para sempre')?></option>
                            <?php
                            $entidades = mysqli_query($GLOBALS['dblink'], "SELECT *, time_value FROM momentos WHERE id_realidade = $id_realidade ORDER BY time_value, ordem;") or die(mysqli_error($GLOBALS['dblink']));
                            while ($e = mysqli_fetch_assoc($entidades)) {
                                echo '<option value="'.$e['id'].'" data-date="'.$e['data_calendario'].'">'.$e['nome'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?=_t('Tipo de relação')?></label>
                    <input type="text" class="form-control" id="tipo_relacao" placeholder="<?=_t('Ex.: Orbita, Governado por')?>" />
                </div>
                <div class="mb-3">
                    <label class="form-label"><?=_t('Descrição')?></label>
                    <textarea class="form-control" id="descricao_relacao" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="execAddRelacao()"><?=_t('Salvar')?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar/Editar Stat -->
<div class="modal modal-blur" id="modal-add-stat" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=_t('Estatística')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sid" />
                <input type="hidden" id="id_stat" />
                <!--div class="mb-3">
                    <label class="form-label"><?=_t('Estatística')?></label>
                    <select class="form-select" id="id_stat">
                        <option value="0" selected><?=_t('Selecione uma estatística')?></option>
                        <?php
                        $stats = mysqli_query($GLOBALS['dblink'], "SELECT id, titulo as nome FROM stats WHERE id_realidade = $id_realidade ;") or die(mysqli_error($GLOBALS['dblink']));
                        while ($s = mysqli_fetch_assoc($stats)) {
                            echo '<option value="'.$s['id'].'">'.$s['nome'].'</option>';
                        }
                        ?>
                    </select>
                </div-->
                <div class="mb-3">
                    <label class="form-label"><?=_t('Momento')?></label>
                    <select class="form-select" id="id_momento">
                        <option value="0" selected><?=_t('Selecione um momento')?></option>
                        <?php
                        $momentos = mysqli_query($GLOBALS['dblink'], "SELECT id, nome, time_value, data_calendario FROM momentos WHERE id_realidade = $id_realidade ORDER BY time_value, ordem;") or die(mysqli_error($GLOBALS['dblink']));
                        while ($m = mysqli_fetch_assoc($momentos)) {
                            echo '<option value="'.$m['id'].'" data-date="'.$m['data_calendario'].'">'.$m['nome'].'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?=_t('Valor')?></label>
                    <input type="text" class="form-control" id="valor_stat" placeholder="<?=_t('Ex.: 1000000 para população')?>" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="execAddStat()"><?=_t('Salvar')?></button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let options = {
        selector: '#descricao',
        height: 300,
        menubar: false,
        statusbar: false,
        setup: (editor) => {
            editor.on('keyup', () => { editarEntidade(); });
        },
        plugins: ['advlist', 'autolink', 'lists', 'link', 'paste'],
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link unlink | removeformat',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }'
    };
    if (localStorage.getItem("tabler-theme") === 'dark') {
        options.skin = 'oxide-dark';
        options.content_css = 'dark';
    }
    tinyMCE.init(options);
});

//formatarTablerSelect('id_stat',null);
formatarTablerMomentsSelect('id_momento',null);

function carregarGrafStats() { //alert('new apexchart'); return;
    document.getElementById('stats').innerHtml = '';
    $.post("api.php?action=ajaxGetJsonStats&eid=" + $('#idEntidade').val(), function(data) {
        var response = JSON.parse(data);
        var series = response.series;
        var labels = response.labels;
        $('#statlist').html(response.html);

        // Configuração do ApexCharts
        new ApexCharts(document.getElementById('stats'), {
            chart: {
                type: "line",
                fontFamily: 'inherit',
                height: 288,
                parentHeightOffset: 0,
                toolbar: {
                    show: true, // Habilita a barra de ferramentas para zoom
                    tools: {
                        download: false,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                },
                animations: {
                    enabled: false
                },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        // Obtém os dados do ponto clicado
                        var seriesIndex = config.seriesIndex;
                        var dataPointIndex = config.dataPointIndex;
                        var serie = series[seriesIndex];
                        var id = serie.ids[dataPointIndex];
                        var id_stat = serie.id_stat || seriesIndex; // Ajuste conforme necessário
                        var id_momento = serie.momentos[dataPointIndex];
                        var valor = serie.data[dataPointIndex].y;
                        //addStat(id, id, id_momento, valor);
                    }
                }
            },
            fill: {
                opacity: 1,
            },
            stroke: {
                width: 2,
                lineCap: "round",
                curve: "smooth",
            },
            series: series,
            tooltip: {
                theme: 'dark'
            },
            grid: {
                padding: {
                    top: -20,
                    right: 0,
                    left: -4,
                    bottom: -4
                },
                strokeDashArray: 4,
            },
            xaxis: {
                categories: labels,
                labels: {
                    padding: 0,
                },
                tooltip: {
                    enabled: false
                }
            },
            yaxis: {
                labels: {
                    padding: 4
                },
            },
            // colors: ["#FF0000", "#00FF00", "#0000FF", "#FFA500", "#800080"], // Cores para as linhas
            legend: {
                show: true,
                position: 'bottom',
                offsetY: 12,
                markers: {
                    width: 10,
                    height: 10,
                    radius: 100,
                },
                itemMargin: {
                    horizontal: 8,
                    vertical: 8
                },
            }
        }).render();
    });
}




function addSigIidTela(iid,nome,niid,eid = 0,info){
    let i = $(".sigoutros").length;
    $('#nomesIdiomas').append(`<div><label class="form-label">Nome (em `+niid+`)</label><div class="row"><input type="hidden" id="enome_iid_`+i+`" value="`+iid+`">
        <div class="col-6"><input type="text" class="form-control sigoutros custom-font-`+eid+`" id="sigoutro_`+i+`" onkeyup="editarEntidade()" value="`+nome+`"></div>
        <div class="col-6"><input type="text" class="form-control" id="siginfo_`+i+`" onkeyup="editarEntidade()" value="`+info+`" placeholder="Info"></div>
    </div></div>`);
};

function entradaSigIdioma(){

    var idl = $('#id_idsig').val();
    if(!idl>0){alert('<?=_t('Selecione o idioma')?>!');
        return false;}
    var idn = $('#id_idsig option:selected').attr('data-n');
    var seid = $('#id_idsig option:selected').attr('data-e');
    addSigIidTela(idl,'',idn,seid);
    $("#modalAdSigIid").modal("hide");

}
</script>



<div class="modal modal-blur" id="modalAdSigIid" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-sm modal-dialog-centered" role="document" >
		<div class="modal-content"  >
			<div class="modal-header">
				<h5 class="modal-title" id="modaltitle"><?=_t('Adicionar nome em')?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body panel-body">
			<select class="form-select" id="id_idsig"><option value="0" selected><?=_t('Selecione um idioma')?></option><?php 
					$oiids = mysqli_query($GLOBALS['dblink'],
					"SELECT i.nome_legivel, i.id as iid, e.id as eid FROM idiomas i
					LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
					WHERE i.publico = 1 OR i.id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink'])); // AND buscavel = 1
				while($oid = mysqli_fetch_assoc($oiids)) {
					echo '<option value="'.$oid['iid'].'" data-e="'.$oid['eid'].'" data-n="'.$oid['nome_legivel'].'">'.$oid['nome_legivel'].'</option>';
				};
				?></select>
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-primary" onClick="entradaSigIdioma();"><?=_t('Adicionar')?></button>
			</div>
		</div>
	</div>
</div>
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
    echo '<script>window.location = "index.php";</script>';
    exit;
}

$contents = '';
$script = '';

$sistemas = mysqli_query($GLOBALS['dblink'], "SELECT * FROM time_systems WHERE id_realidade = ".$id_realidade." ORDER BY padrao DESC;") or die(mysqli_error($GLOBALS['dblink']));
while ($s = mysqli_fetch_assoc($sistemas)) {
    $script .= 'carregarTabelaUnidades(\''.$s['id'].'\');carregarCalendario(\''.$s['id'].'\');';

    $contents .= '<div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title" onclick="window.history.replaceState({}, \'\', \'index.php?page=edittimesystems&rid='.$id_realidade.'&sid='.$s['id'].'\');$(\'.cb_'.$s['id'].'\').toggle()">'.$s['nome'].($s['padrao']==1?' ('._t('Padrão').')':'').'</div>
                <div class="card-actions btn-actions">
                    <a href="#" onclick="apagarSistema(\''.$s['id'].'\')" class="btn btn-danger">'._t('Apagar').'</a>
                    <a href="#" onclick="execSalvarSistema(\''.$s['id'].'\')" id="btnSalvar'.$s['id'].'" class="btn btn-primary" style="display:none">'._t('Salvar').'</a>
                </div>
            </div>
            <div class="card-body cb_'.$s['id'].'" '.($_GET['sid']==$s['id']?'':'style="display:none"').'>
                <div class="row g-5">
                    <!-- Informações Gerais -->
                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label class="form-label">'._t('Nome').'</label>
                            <input type="text" class="form-control" id="nome'.$s['id'].'" value="'.$s['nome'].'" onchange="salvarSistema(\''.$s['id'].'\')">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">'._t('Descrição').'</label>
                            <textarea class="form-control" id="descricao'.$s['id'].'" rows="4" onchange="salvarSistema(\''.$s['id'].'\')">'.$s['descricao'].'</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">'._t('Data padrão').'</label>
                            <input type="date" class="form-control" id="data_padrao'.$s['id'].'" value="'.$s['data_padrao'].'" onchange="salvarSistema(\''.$s['id'].'\')">
                        </div>
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="padrao'.$s['id'].'" '.($s['padrao']==1?'checked':'').' onchange="setPadrao(\''.$s['id'].'\')">
                                <span class="form-check-label">'._t('Sistema padrão').'</span>
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="publico'.$s['id'].'" '.($s['publico']==1?'checked':'').' onchange="salvarSistema(\''.$s['id'].'\')">
                                <span class="form-check-label">'._t('Público').'</span>
                            </label>
                        </div>
                    </div>
                    <!-- Unidades de Tempo -->
                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label class="form-label">'._t('Unidades de tempo').'</label>
                        </div>
                        <div class="mb-3 overflow-auto" style="max-height: 45rem">
                            <div class="col-12">
                                <a class="btn btn-primary" onclick="addUnidade('.$s['id'].')">+</a>
                            </div>
                            <div id="unidades'.$s['id'].'" class="list-group list-group-flush list-group-hoverable">
                                <a class="btn btn-primary" onClick="carregarTabelaUnidades(\''.$s['id'].'\')"><i class="fa fa-refresh"></i>'._t('Carregar').'</a>
                            </div>
                        </div>
                    </div>
                    <!-- Calendário -->
                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label class="form-label">'._t('Calendário').'</label>
                        </div>
                        <div class="mb-3 overflow-auto" style="max-height: 45rem">
                            <div id="calendario'.$s['id'].'" class="list-group list-group-flush list-group-hoverable">
                                <a class="btn btn-primary" onClick="carregarCalendario(\''.$s['id'].'\')"><i class="fa fa-refresh"></i>'._t('Carregar').'</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}
?>

<input type="hidden" id="codigo" value="<?=$id_realidade?>" />
<input type="hidden" id="idSistema" value="0" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item"><a href="?page=editworld&rid=<?=$id_realidade?>"><?=$realidade['titulo']?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t('Sistemas de tempo')?></a></li>
                    </ol>
                </h2>
            </div>
            <!-- Page title actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-novo">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                        <?=_t('Novo')?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            <?=$contents?>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    <?php echo $script; ?>
});

function salvarSistema(id) {
    $('#btnSalvar'+id).show();
}

function execSalvarSistema(id) {
    $.post("api.php?action=ajaxSalvarSistemaTempo&sid="+id, {
        nome: $('#nome'+id).val(),
        descricao: $('#descricao'+id).val(),
        data_padrao: $('#data_padrao'+id).val(),
        publico: document.getElementById('publico'+id).checked ? 1 : 0,
        rid: <?=$id_realidade?>
    }, function(data) {
        if ($.trim(data) > 0) {
            $('#btnSalvar'+id).hide();
            location.reload(true);
        } else {
            alert(data);
        }
    });
}

function setPadrao(sid) {
    if ($('#btnSalvar'+sid).css('display') == 'none') {
        $.get("api.php?action=ajaxSetSistemaPadrao&sid="+sid+"&rid=<?=$id_realidade?>", function(data) {
            if (data == 'ok') {
                location.reload(true);
            } else {
                alert(data);
            }
        });
    } else {
        alert('Salve primeiro!');
    }
}

function apagarSistema(sid) {
    if (confirm("<?=_t('Deseja mesmo apagar este sistema de tempo?')?>")) {
        $.get("api.php?action=ajaxDeleteSistemaTempo&sid="+sid, function(data) {
            if ($.trim(data) == 'ok') {
                location.reload(true);
            } else {
                alert(data);
            }
        });
    }
}

function novoSistema() {
    var nome = $('#snome').val();
    if (nome == '') {
        $.alert('Insira um nome para o sistema!');
        return false;
    }
    $.get("api.php?action=ajaxNovoSistemaTempo&rid=<?=$id_realidade?>&n="+nome, function(data) {
        if (data > 0) {
            location.reload(true);
        } else {
            alert(data);
        }
    });
}

function addUnidade(sid, uid = 0, nome = '', duracao = '', ref = 0, quantidade = '', equivalente = '', subNames = '[]', refsub = 0, quant_sub = 0) {
    $('#unome').val(nome);
    $('#uduracao').val(duracao);
    $('#uref').val(ref);
    $('#urefsub').val(refsub);
    $('#uquantidade').val(quantidade);
    $('#uquantidadesub').val(quant_sub);
    $('#uequivalente').val(equivalente);
    $('#uid').val(uid);
    $('#sid').val(sid);

    // Configurar nomeação de subunidades
    const subNamesArray = JSON.parse(subNames);
    $('#namesub').val(subNamesArray.length > 0 ? '1' : '0');
    if (subNamesArray.length > 0) {
        $('#namesub').val('1');
        updateSubNames(subNamesArray); // Gerar inputs de subunidades
        updateDuracaoSubs();
    }else{
        $('#namesub').val('0');
        updateDuracao();
    }
    

    $('#modal-add-unidade').modal('show');
}

function updateDuracao() {
    var ref = $('#uref').val();
    var quantidade = $('#uquantidade').val();
    if (ref > 0 && quantidade > 0) {
        $.get("api.php?action=ajaxGetUnidadeDuracao&uid="+ref, function(data) {
            if (data > 0) {
                $('#uduracao').val(data * quantidade);
            }
        });
    }
    // set subunidades pra não - toggle change
    //$('#namesub').val(0);
    updateSubNames();
}


function updateDuracaoSubs() {
    var quantidade = $('#uquantidade').val();
    var nameSub = $('#namesub').val();

    var ref = $('#urefsub').val(); // id da subunidade da subunidade urefsub
    var defaultSubQuantidade = $('#uquantidadesub').val(); // dias no mes

    if (ref > 0 && quantidade > 0) {
        $.get("api.php?action=ajaxGetUnidadeDuracao&uid="+ref, function(defaultSubDuracao) { // segundos no dia (subunidade da subunidade)
            if (defaultSubDuracao > 0) {

                let totalSubQuantidade = 0;

                for (let i = 1; i <= quantidade; i++) {
                    const subQuantidade = parseFloat($(`#subquantidade-${i}`).val()) || parseFloat(defaultSubQuantidade);
                    totalSubQuantidade += subQuantidade;
                }

                const duracaoTotal = totalSubQuantidade * parseFloat(defaultSubDuracao);
                $('#uduracao').val(duracaoTotal.toFixed(2));

            }
        });
    }
}

function verificarCiclos(sid, uid) {
    $.get(`api.php?action=ajaxVerificarCiclos&sid=${sid}&uid=${uid}`, function(data) {
        if (data != 'ok') {
            var res = JSON.parse(data);
            const mensagem = `<div class="alert alert-warning">${res.mensagem}</div>`;
            let sugestoes = '';
            if (res.sugestoes.criar_unidade) {
                sugestoes += `<button class="btn btn-primary me-2" onclick="criarUnidadeExtra('${sid}',${res.sugestoes.criar_unidade.duracao})"><?=_t('Criar unidade extra')?></button>`;
            }
            if (res.sugestoes.redistribuir) {
                sugestoes += `<button class="btn btn-primary me-2" onclick="redistribuirSobras('${sid}',${res.sugestoes.redistribuir.unidade},${res.sugestoes.redistribuir.quantidade})"><?=_t('Redistribuir sobras')?></button>`;
            }
            sugestoes += `<button class="btn btn-secondary" onclick="ignorarAjuste('${sid}')"><?=_t('Ignorar')?></button>`;

            $('#ajuste-mensagem').html(mensagem);
            $('#ajuste-sugestoes').html(sugestoes);
            $('#modal-ajuste-ciclos').modal('show');
        }
    });
}

/* antigas*/

function criarUnidadeExtra(sid, duracao) {
    $.post("api.php?action=ajaxAddUnidadeTempo&sid="+sid, {
        nome: 'Unidade Extra',
        duracao: duracao,
        ref: 0,
        quantidade: 0
    }, function(data) {
        if (data > 0) {
            carregarTabelaUnidades(sid);
            alert('Unidade extra criada!');
        } else {
            alert(data);
        }
    });
}

function redistribuirSobras(sid, uid, quantidade) {
    $.post("api.php?action=ajaxAddCicloTempo&sid="+sid, {
        id_unidade: uid,
        id_unidade_ref: 0,
        quantidade: quantidade
    }, function(data) {
        if (data > 0) {
            carregarTabelaUnidades(sid);
            alert('Sobras redistribuídas!');
        } else {
            alert(data);
        }
    });
}

function ignorarAjuste(sid) {
    carregarTabelaUnidades(sid);
}

/*fim das antigas - novas teste */
function criarUnidadeExtra(sid, duracao) {
    $.post(`api.php?action=ajaxAddUnidadeTempo&sid=${sid}`, {
        nome: 'Unidade Extra',
        duracao: duracao,
        ref: 0,
        quantidade: 0
    }, function(data) {
        if (data > 0) {
            carregarTabelaUnidades(sid);
            $('#modal-ajuste-ciclos').modal('hide');
            $.alert('Unidade extra criada!');
        } else {
            alert(data);
        }
    });
}

function redistribuirSobras(sid, uid, quantidade) {
    $.post(`api.php?action=ajaxAddCicloTempo&sid=${sid}`, {
        id_unidade: uid,
        id_unidade_ref: 0,
        quantidade: quantidade
    }, function(data) {
        if (data > 0) {
            carregarTabelaUnidades(sid);
            $('#modal-ajuste-ciclos').modal('hide');
            alert('Sobras redistribuídas!');
        } else {
            alert(data);
        }
    });
}

function ignorarAjuste(sid) {
    carregarTabelaUnidades(sid);
    $('#modal-ajuste-ciclos').modal('hide');
}

/* fim das novas */

function carregarTabelaUnidades(sid) {
    $.post("api.php?action=ajaxLoadUnidadesTempo&sid="+sid, function(data) {
        $('#unidades'+sid).html(data);
    });
}

function apagarUnidade(uid, sid) {
    if (confirm("<?=_t('Deseja mesmo apagar esta unidade de tempo?')?>")) {
        $.get("api.php?action=ajaxDeleteUnidadeTempo&uid="+uid, function(data) {
            if ($.trim(data) == 'ok') {
                carregarTabelaUnidades(sid);
            } else {
                alert(data);
            }
        });
    }
}

</script>

<!-- Modal para Novo Sistema -->
<div class="modal modal-blur" id="modal-novo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=_t('Novo sistema de tempo')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?=_t('Nome')?></label>
                    <input type="text" class="form-control" placeholder="<?=_t('Nome legível')?>" id="snome" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="novoSistema()"><?=_t('Salvar')?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar/Editar Unidade de Tempo -->
<div class="modal modal-blur" id="modal-add-unidade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=_t('Unidade de tempo')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sid" />
                <input type="hidden" id="uid" />
                <input type="hidden" id="urefsub" />
                <input type="hidden" id="uquantidadesub" />
                <input type="hidden" id="subDuracaoPadrao" /> <!-- Novo input oculto -->
                <div class="row">
                    <div class="mb-3 col-md-9">
                        <label class="form-label"><?=_t('Nome')?></label>
                        <input type="text" class="form-control" id="unome" placeholder="<?=_t('Ex.: Dia')?>" />
                    </div>
                    <div class="mb-3 col-md-3">
                        <label class="form-label"><?=_t('Duração (segundos)')?></label>
                        <input type="number" class="form-control" id="uduracao" placeholder="<?=_t('Ex.: 86400 para um dia')?>" step="1.0" readonly />
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label class="form-label"><?=_t('Unidade de referência')?></label>
                        <select class="form-select" id="uref" onchange="updateDuracao()">
                            <option value="0" selected><?=_t('Nenhuma')?></option>
                            <?php 
                            $unidades = mysqli_query($GLOBALS['dblink'], "SELECT id, nome FROM time_units WHERE id_realidade = ".$id_realidade.";");
                            while ($u = mysqli_fetch_assoc($unidades)) {
                                echo '<option value="'.$u['id'].'">'.$u['nome'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3 col-md-3">
                        <label class="form-label"><?=_t('Quantidade')?></label>
                        <input type="number" class="form-control" id="uquantidade" placeholder="<?=_t('Ex.: 12 para 12 meses por ano')?>" step="1.0" oninput="updateDuracao()" />
                    </div>
                    <div class="mb-3 col-md-3">
                        <label class="form-label"><?=_t('Equivalente visual')?></label>
                        <select class="form-select" id="uequivalente" onchange="updateDuracao()">
                            <option value="" selected><?=_t('Nenhuma')?></option>
                            <option value="minuto"><?=_t('Minuto')?></option>
                            <option value="hora"><?=_t('Hora')?></option>
                            <option value="dia"><?=_t('Dia')?></option>
                            <option value="mes"><?=_t('Mês')?></option>
                            <option value="ano"><?=_t('Ano')?></option>
                            <option value="semana"><?=_t('Semana')?></option>
                            <option value="decada"><?=_t('Década')?></option>
                            <option value="seculo"><?=_t('Século')?></option>
                            <option value="milenio"><?=_t('Milênio')?></option>
                        </select>
                    </div>
                    <div class="mb-3 col-md-3">
                        <label class="form-label"><?=_t('Nomear subunidades?')?></label>
                        <select class="form-select" id="namesub" onchange="updateSubNames()">
                            <option value="0" selected><?=_t('Não')?></option>
                            <option value="1"><?=_t('Sim')?></option>
                        </select>
                    </div>
                </div>
                <div class="mb-3" id="subNames"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="execAddUnidade()"><?=_t('Salvar')?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ajuste de Ciclos -->
<div class="modal modal-blur" id="modal-ajuste-ciclos" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=_t('Ajuste de ciclos')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ajuste-mensagem"></div>
                <div class="mt-3">
                    <p><?=_t('Sugestões:')?></p>
                    <div id="ajuste-sugestoes"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=_t('Fechar')?></button>
            </div>
        </div>
    </div>
</div>

<script>
function updateSubNames(subNamesArray = []) {
    const nameSub = document.getElementById('namesub').value;
    const quantidade = parseInt(document.getElementById('uquantidade').value) || 0;
    const subNamesDiv = document.getElementById('subNames');
    subNamesDiv.innerHTML = '';
    const e = document.getElementById('uref');
    const refName = e.options[e.selectedIndex].text;


    if (nameSub === '1' && quantidade > 0) {
        for (let i = 1; i <= quantidade; i++) {
            subNamesDiv.innerHTML += `
                <div class="mb-3 row">
                    <div class="col-9">
                        <label class="form-label">${refName} ${i}</label>
                        <input type="text" class="form-control" id="subname-${i}" placeholder="Nome da subunidade ${i}">
                    </div>
                    <div class="col-3">
                        <label class="form-label">Dias</label>
                        <input type="number" class="form-control" id="subquantidade-${i}" onchange="updateDuracaoSubs()" placeholder="Ex.: 30" step="1.0">
                    </div>
                </div>
            `;
        }
        setTimeout(() => {
            subNamesArray.forEach(sub => {
                $(`#subname-${sub.posicao}`).val(sub.nome);
                $(`#subquantidade-${sub.posicao}`).val(sub.quantidade_subunidade || '');
            });
        }, 0);
    }
}
function execAddUnidade() {
    var sid = $('#sid').val();
    var uid = $('#uid').val();
    var nome = $('#unome').val();
    var duracao = $('#uduracao').val();
    var ref = $('#uref').val();
    var equivalente = $('#uequivalente').val();
    var quantidade = $('#uquantidade').val();
    var nameSub = $('#namesub').val();

    if (nome == '' || duracao == '') {
        $.alert('Insira nome e duração da unidade!');
        return false;
    }

    // Coletar nomes das subunidades
    var subNames = [];
    if (nameSub === '1' && quantidade > 0) {
        for (let i = 1; i <= parseInt(quantidade); i++) {
            const subName = $(`#subname-${i}`).val();
            const subQuantidade = $(`#subquantidade-${i}`).val();
            //if (subName) {
                subNames.push({ posicao: i, nome: subName, quantidade_subunidade: subQuantidade || null });
            //}
        }
    }

    $.post("api.php?action=ajaxAddUnidadeTempo&rid=<?=$id_realidade?>&sid="+sid+"&uid="+uid, {
        nome: nome,
        duracao: duracao,
        equivalente: equivalente,
        ref: ref,
        quantidade: quantidade,
        subNames: JSON.stringify(subNames)
    }, function(data) {
        if (data > 0) {
            carregarTabelaUnidades(sid);
            $('#modal-add-unidade').modal('hide');
            verificarCiclos(sid, data);
        } else {
            alert(data);
        }
    });
}

function carregarCalendario(sid) {
    /*
    let html = `<div class="d-flex mb-3">
        <input type="number" class="form-control me-2" id="c-year${sid}" value="0" placeholder="Ano" min="-1000000" max="1000000">
        <input type="hidden" id="time-value${sid}">
        <select class="form-select" id="c-month${sid}"></select>
        </div>
        <div class="table-responsive">
        <table class="table table-bordered" id="c-calendar-table${sid}">
            <thead>
            <tr id="c-calendar-days${sid}"></tr>
            </thead>
            <tbody id="c-calendar-body${sid}"></tbody>
        </table>
        <div id="c-calendar-warnings${sid}"></div>
        </div>`;
        */
    let html = `<div class="d-flex mb-3 align-items-center">
            <div class="input-group me-2">
                <button class="btn btn-icon btn-outline-secondary" onclick="decrementYear('${sid}')">-</button>
                <input type="number" class="form-control" id="c-year${sid}" value="0" placeholder="Ano" min="-1000000" max="1000000">
                <button class="btn btn-icon btn-outline-secondary" onclick="incrementYear('${sid}')">+</button>
            </div>
            <input type="hidden" id="time-value${sid}">
            <div class="input-group">
                <button class="btn btn-icon btn-outline-secondary" onclick="decrementMonth('${sid}')">-</button>
                <select class="form-select" id="c-month${sid}"></select>
                <button class="btn btn-icon btn-outline-secondary" onclick="incrementMonth('${sid}')">+</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" id="c-calendar-table${sid}">
                <thead>
                    <tr id="c-calendar-days${sid}"></tr>
                </thead>
                <tbody id="c-calendar-body${sid}"></tbody>
            </table>
            <div id="c-calendar-warnings${sid}"></div>
        </div>`;
    $("#calendario"+sid).html(html);
    loadCalendar(
        'c-calendar'+sid,
        'c-year'+sid,
        'c-month'+sid,
        'c-calendar-days'+sid,
        'c-calendar-body'+sid,
        'c-calendar-warnings'+sid,
        sid, 0, 0,
        'time-value'+sid,
        null,
        <?=$id_realidade?>
    );
}

function setDateClicked(timeValue, message){
    $('#offcanvasMoments').offcanvas('show');
    $('#divMoments').html(message.replace(/\n/g, '<br>'));
}
</script>


<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMoments" aria-labelledby="offcanvasStartLabel">
	<div class="offcanvas-header">
		<h2 class="offcanvas-title" id="offcanvasStartLabel"><?=_t('Momentos')?></h2>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3" id="divMoments"></div>
	</div>
</div>
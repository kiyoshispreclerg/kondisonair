<?php
$id_realidade = $_GET['rid'];
$hid = $_GET['hid'] ?? 0;

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
    $result = mysqli_query($GLOBALS['dblink'], "SELECT h.*, m.time_value FROM historias h 
        LEFT JOIN momentos m ON m.id = h.id_momento 
        WHERE h.id = $hid AND h.id_realidade = $id_realidade LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    $historia = mysqli_fetch_assoc($result);
    if (!$historia['id']>0) {
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
                        <li class="breadcrumb-item"><a href="?page=editstories&rid=<?=$id_realidade?>"><?=_t('Histórias')?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t('Editar História')?></a></li>
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
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Texto da História')?></h3>
                        <div class="card-actions">
                            <a id="btnSalvar" onclick="gravarHistoria()" class="btn btn-primary" style="display:none">
                                <?=_t('Salvar')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <textarea id="texto" onchange="showGravarHistoria()"><?=$historia['texto']?></textarea>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="card">
                  <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                      <li class="nav-item">
                        <a href="#tabs-1" class="nav-link active" data-bs-toggle="tab">Informações</a>
                      </li>
                      <li class="nav-item">
                        <a href="#tabs-2" class="nav-link" data-bs-toggle="tab">Histórias</a>
                      </li>
                      <li class="nav-item">
                        <a href="#tabs-3" class="nav-link" data-bs-toggle="tab">Entidades</a>
                      </li>
                    </ul>
                  </div>
                  <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active show" id="tabs-1">
                            <div class="mb-3">
                                <label class="form-label"><?=_t('Título')?>*</label>
                                <input type="text" class="form-control" id="titulo" value="<?=$historia['titulo']?>" onchange="showGravarHistoria()" placeholder="<?=_t('Ex.: A Batalha de Eldoria')?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?=_t('Descrição')?></label>
                                <textarea class="form-control" id="descricao" rows="5" onchange="showGravarHistoria()" placeholder="<?=_t('Ex.: Resumo da história')?>"><?=$historia['descricao']?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label"><?=_t('Status')?>*</label>
                                    <select id="status" onchange="showGravarHistoria()" class="form-select">
                                        <option value="rascunho"><?=_t('Rascunho')?></option>
                                        <option value="publicado" <?php if($historia['status']=='publicado') echo 'selected'; ?>><?=_t('Publicado')?></option>
                                        <option value="arquivado" <?php if($historia['status']=='arquivado') echo 'selected'; ?>><?=_t('Arquivado')?></option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?=_t('Momento')?></label>
                                    <select id="id_momento" onchange="showGravarHistoria()" class="form-select">
                                        <option value="0" selected><?=_t('Nenhum')?></option>
                                        <?php
                                        $momentos = mysqli_query($GLOBALS['dblink'], "SELECT id, nome, time_value, data_calendario FROM momentos WHERE id_realidade = $id_realidade ORDER BY time_value, ordem;") or die(mysqli_error($GLOBALS['dblink']));
                                        while ($m = mysqli_fetch_assoc($momentos)) {
                                            echo '<option value="'.$m['id'].'" data-date="'.$m['data_calendario'].'" ';
                                            if ($m['id'] == $historia['id_momento']) echo 'selected';
                                            echo '>'.$m['nome'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="tabs-2">
                            <div class="accordion-body pt-0">
                                <?php if($historia['id']>0){ ?>
                                    <div class="mb-3">
                                        <?php
                                        $historiasDoNivel = mysqli_query($GLOBALS['dblink'], "SELECT *
                                            FROM historias
                                            WHERE id_superior = ".$historia['id_superior']." 
                                            ORDER BY id;") or die(mysqli_error($GLOBALS['dblink']));
                                        while ($hn = mysqli_fetch_assoc($historiasDoNivel)) {
                                            if ($hn['id'] == $hid) echo '<div class="mb-2">
                                                    <h3><a>'.$hn['titulo'].'</a></h3>
                                                    <div class="blockquote">'.$hn['descricao'].'</div>
                                                </div>';
                                            else echo '<div class="mb-2">
                                                    <h3><a href="?page=editstory&rid='.$hn['id_realidade'].'&hid='.$hn['id'].'">'.$hn['titulo'].'</a></h3>
                                                    <div class="blockquote">'.$hn['descricao'].'</div>
                                                </div>';
                                        }
                                        ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="tab-pane" id="tabs-3">
                            <div class="mb-3">
                                <input type="text" class="form-control mb-3" id="searchEntidades" placeholder="<?=_t('Filtrar')?>" onkeyup="filterEntidades()">
                                <!--a class="btn btn-sm">Adicionar</a> <a class="btn btn-sm">Criar</a-->
                                <div id="entidadesContainer">
                                    <?php
                                    //xxxxx mudar caixa de input pra só texto e link pra mudar valor no momento da historia, tipo na tela timeline?
                                    $entidades = mysqli_query($GLOBALS['dblink'], "SELECT e.id, e.nome_legivel as nome, e.id_tipo, et.nome as tipo, e.descricao_curta
                                        FROM historias_entidades he
                                        LEFT JOIN entidades e ON e.id = he.id_entidade
                                        LEFT JOIN entidades_tipos et ON e.id_tipo = et.id
                                        WHERE he.id_historia = ".$historia['id']." 
                                        GROUP BY e.id, e.nome_legivel 
                                        ORDER BY et.nome, e.nome_legivel;") or die(mysqli_error($GLOBALS['dblink']));
                                    while ($e = mysqli_fetch_assoc($entidades)) {
                                        echo '<div class="mb-3 entidade-item" data-nome="' . htmlspecialchars(strtolower($e['nome'])) . '">
                                            <h3>' . htmlspecialchars($e['nome']) . ' </h3> <!-- add/criar stat ? -->
                                            <div class="blockquote"><span class="text-secondary">'.$e['descricao_curta'].'</span>';
                                        $stats = mysqli_query($GLOBALS['dblink'], "SELECT s.id, s.titulo, se.valor, m.nome as momento, m.time_value, m.id as id_momento, se.id as se_id
                                            FROM stats_entidades se
                                            LEFT JOIN stats s ON se.id_stat = s.id
                                            LEFT JOIN momentos m ON m.id = se.id_momento
                                            WHERE se.id_entidade = {$e['id']}
                                            ".($historia['time_value'] ? "AND m.time_value <= {$historia['time_value']}" : "")."
                                            GROUP BY s.id 
                                            ORDER BY s.titulo;") or die(mysqli_error($GLOBALS['dblink']));
                                        while ($s = mysqli_fetch_assoc($stats)) {
                                            $valString = '<div class="mt-2">'.$s['titulo'].': <a href="#" onclick="">'.$s['valor'].'</a>';
                                            $inflsRes = mysqli_query($GLOBALS['dblink'], "
                                                SELECT s.*, e.nome_legivel
                                                FROM entidades_influencias s
                                                LEFT JOIN entidades e ON s.id_infl = e.id
                                                WHERE s.id_ent_stat = ".$s['se_id']."
                                            ") or die(mysqli_error($GLOBALS['dblink']));
                                            $inflString = mysqli_num_rows($inflsRes) > 0 ? 'Influências<br>' : '';
                                            while ($ir = mysqli_fetch_assoc($inflsRes)) {
                                                $inflString .= $ir['nome_legivel'].' ('.$ir['descricao'].')<br>';
                                                $infls[] = [
                                                    'id' => $ir['id_infl'],
                                                    'nome' => $ir['nome_legivel'],
                                                    'descricao' => $ir['descricao']
                                                ];
                                            }
                                            if ($historia['id_momento']!=$s['id_momento']) $valString .= '<br>Desde '.$s['momento'].'<br>';
                                            echo '<div><span>' . $valString . '</span>
                                                <span class="text-secondary">' . $inflString . '</span>
                                            </div></div>';
                                        }
                                        echo '</div>
                                        </div>';
                                    }
                                    if (mysqli_num_rows($entidades) == 0) {
                                        echo '<p class="text-muted">' . _t('Nenhuma entidade relacionada.') . ' <a>Criar</a></p>';
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

    // Coletar valores dos stats
    let stats_valores = [];
    $('.stat-valor').each(function() {
        let valor = $(this).val();
        if (valor !== '') {
            stats_valores.push({
                id_entidade: $(this).data('entidade'),
                id_stat: $(this).data('stat'),
                valor: parseFloat(valor)
            });
        }
    });

    $.post("?action=ajaxGravarHistoria&hid=" + $('#idHistoria').val() + "&rid=<?=$id_realidade?>", {
        titulo: $('#titulo').val(),
        status: $('#status').val(),
        id_momento: $('#id_momento').val(),
        id_entidade_relacionada: $('#id_entidade_relacionada').val(),
        descricao: $('#descricao').val(),
        texto: tinymce.get('texto').getContent(),
        stats_valores: stats_valores
    }, function(data) {
        if ($.trim(data) > 0) {
            $('#idHistoria').val($.trim(data));
            $('#btnSalvar').hide();
            carregarHistoria($.trim(data));
            const toast = new window.TablerToast({
                message: '<?=_t('História salva com sucesso!')?>',
                type: 'success'
            });
            toast.show();
        } else {
            alert(data);
        }
    });
}

function carregarHistoria(hid) {
    $('#idHistoria').val(hid);
    $.getJSON("?action=ajaxCarregarHistoriaStats&hid=" + hid + "&rid=<?=$id_realidade?>", function(data) {
        $('#titulo').val(data.titulo);
        $('#status').val(data.status);
        $('#id_momento').val(data.id_momento);
        $('#descricao').val(data.descricao);
        tinymce.get('texto').setContent(data.texto || '');
        updateTablerSelect('status', data.status);
        updateTablerSelect('id_momento', data.id_momento);
        $('#btnSalvar').hide();

        // Preencher valores dos stats e avisos
        $('.stat-valor').each(function() {
            let id_entidade = $(this).data('entidade');
            let id_stat = $(this).data('stat');
            let stat_data = data.stats.find(s => s.id_entidade == id_entidade && s.id_stat == id_stat);
            if (stat_data) {
                $(this).val(stat_data.valor);
                if (stat_data.aviso) {
                    $(this).next('.stat-aviso').text('<?=_t('Valor desde o momento')?> ' + stat_data.aviso);
                } else {
                    $(this).next('.stat-aviso').text('');
                }
            } else {
                $(this).val('');
                $(this).next('.stat-aviso').text('');
            }
        });
    });
}

function showGravarHistoria() {
    $('#btnSalvar').show();
}

$(document).ready(function() {
    if ($('#idHistoria').val() > 0) {
        carregarHistoria($('#idHistoria').val());
    } else {
        $('#titulo').val('');
        $('#status').val('rascunho');
        $('#id_momento').val(0);
        $('#descricao').val('');
        tinymce.get('texto').setContent('');
        updateTablerSelect('status', 'rascunho');
        updateTablerSelect('id_momento', 0);
        $('.stat-valor').val('');
        $('.stat-aviso').text('');
        $('#btnSalvar').hide();
    }

    // Mostrar botão Salvar quando stats forem alterados
    $('.stat-valor').on('input', function() {
        showGravarHistoria();
    });
});
    formatarTablerSelect('status');
    formatarTablerMomentsSelect('id_momento');

document.addEventListener("DOMContentLoaded", function() {
    let options = {
        selector: '#texto',
        height: 600,
        menubar: false,
        statusbar: false,
        setup: (editor) => {
            editor.on('change keyup', () => { showGravarHistoria(); });
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
</script>
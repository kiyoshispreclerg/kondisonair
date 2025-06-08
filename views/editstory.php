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
    $result = mysqli_query($GLOBALS['dblink'], "SELECT * FROM historias WHERE id = $hid AND id_realidade = $id_realidade LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
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
                    <div class="card-boy">
                        <div class="accordion" id="accordion-example">

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-1" aria-expanded="true">
                                    Informações
                                </button>
                                </h2>
                                <div id="collapse-1" class="accordion-collapse collapse show" data-bs-parent="#accordion-example" style="">
                                    <div class="accordion-body pt-0">
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
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-2">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-2" aria-expanded="true">
                                    Histórias
                                </button>
                                </h2>
                                <div id="collapse-2" class="accordion-collapse collapse" data-bs-parent="#accordion-example" style="">
                                    <div class="accordion-body pt-0">
                                    <?php if($historia['id']>0){ ?>
                                    <div class="mb-3">
                                            <?php
                                            //xxxxx mudar caixa de input pra só texto e link pra mudar valor no momento da historia, tipo na tela timeline?
                                            $historiasDoNivel = mysqli_query($GLOBALS['dblink'], "SELECT *
                                                FROM historias
                                                WHERE id_superior = ".$historia['id_superior']." 
                                                ORDER BY id;") or die(mysqli_error($GLOBALS['dblink']));
                                            while ($hn = mysqli_fetch_assoc($historiasDoNivel)) {
                                                if ($hn['id'] == $hid) echo '<div class="mb-2">
                                                        <label class="form-label"><a>'.$hn['titulo'].'</a></label>
                                                    </div>';
                                                else echo '<div class="mb-2">
                                                        <label class="form-label"><a href="?page=editstory&rid='.$hn['id_realidade'].'&hid='.$hn['id'].'">'.$hn['titulo'].'</a></label>
                                                    </div>';
                                            }
                                            ?>
                                    </div>
                                    <?php } ?>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-3">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-3" aria-expanded="true">
                                    <?=_t('Entidades')?>
                                </button>
                                </h2>
                                <div id="collapse-3" class="accordion-collapse collapse" data-bs-parent="#accordion-example" style="">
                                    <div class="accordion-body pt-0">
                                       
                                    <?php if(true || $historia['id_momento']>0){ // vai salvar no momento 0 - padrão ?>
                                    <div class="mb-3">
                                        <!--a class="btn btn-sm">Adicionar</a> <a class="btn btn-sm">Criar</a-->
                                        <div id="entidades-relacionadas">
                                            <?php
                                            //xxxxx mudar caixa de input pra só texto e link pra mudar valor no momento da historia, tipo na tela timeline?
                                            $entidades = mysqli_query($GLOBALS['dblink'], "SELECT e.id, e.nome_legivel as nome, e.id_tipo
                                                FROM historias_entidades he
                                                JOIN entidades e ON e.id = he.id_entidade
                                                WHERE he.id_historia = ".$historia['id']." 
                                                GROUP BY e.id, e.nome_legivel, e.id_tipo
                                                ORDER BY e.nome_legivel;") or die(mysqli_error($GLOBALS['dblink']));
                                            while ($e = mysqli_fetch_assoc($entidades)) {
                                                echo '<div class="mb-3">
                                                    <strong>' . htmlspecialchars($e['nome']) . ' </strong> <!-- add/criar stat ? -->
                                                    <div class="ms-2">';
                                                // Stats para o tipo da entidade
                                                $stats = mysqli_query($GLOBALS['dblink'], "SELECT ets.id, s.titulo, ets.id_stat
                                                    FROM entidades_tipos_stats ets
                                                    LEFT JOIN stats s ON s.id = ets.id_stat
                                                    WHERE ets.id_entidade_tipo = {$e['id_tipo']}
                                                    ORDER BY s.titulo;") or die(mysqli_error($GLOBALS['dblink']));
                                                while ($s = mysqli_fetch_assoc($stats)) {
                                                    echo '<div class="mb-2">
                                                        <label class="form-label">' . htmlspecialchars($s['titulo']) . ' <!-- link abre grafico lateral desse stat --></label>
                                                        <input type="number" class="form-control stat-valor" 
                                                            data-entidade="' . $e['id'] . '" 
                                                            data-stat="' . $s['id_stat'] . '" 
                                                            placeholder="' . _t('Valor') . '">
                                                        <small class="text-muted stat-aviso"></small>
                                                    </div>';
                                                }
                                                if (mysqli_num_rows($stats) == 0) {
                                                    echo '<p class="text-muted">' . _t('Nenhum stat para este tipo.') . '</p>';
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
                                    <?php } ?>
                                    
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
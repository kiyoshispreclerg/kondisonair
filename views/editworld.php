<!-- PANEL START -->
<?php 
$id_universo = $_GET['rid'];

$collab = false;

if ($id_universo > 0) {
    $universo = array();  
    $result = mysqli_query($GLOBALS['dblink'], "SELECT u.*, 
                (SELECT COUNT(*) FROM time_systems WHERE id_realidade = u.id) as numTimeSystems,
                (SELECT COUNT(*) FROM momentos WHERE id_realidade = u.id) as numEventos,
                (SELECT COUNT(*) FROM historias WHERE id_realidade = u.id) as numHistorias,
                (SELECT COUNT(*) FROM entidades WHERE id_realidade = u.id AND rule = 'other') as numEntidades,
                (SELECT COUNT(*) FROM entidades WHERE id_realidade = u.id AND rule = 'item') as numItens,
                (SELECT COUNT(*) FROM entidades_tipos WHERE id_realidade = u.id) as numTipos,
                (SELECT COUNT(*) FROM entidades WHERE id_realidade = u.id AND rule = 'character') as numPersonagens,
                (SELECT COUNT(*) FROM entidades WHERE id_realidade = u.id AND rule = 'place') as numLocais,
                (SELECT COUNT(*) FROM stats WHERE id_realidade = u.id) as numStats,
                -- (SELECT COUNT(*) FROM artygs WHERE id_realidade = u.id) as numArtigos,
                -- (SELECT COUNT(*) FROM textos WHERE id_realidade = u.id) as numTextos,
                (SELECT COUNT(*) FROM collabs_realidades WHERE id_realidade = u.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
                FROM realidades u
                WHERE u.id = '".$id_universo."';") or die(mysqli_error($GLOBALS['dblink']));
    while ($r = mysqli_fetch_assoc($result)) { 
        $universo = $r;
    };

    if (empty($universo['titulo'])) {
        echo '<script>window.location = "index.php";</script>';
        exit;
    } else if ($universo['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$universo['collab'] > 0) {
        echo '<script>window.location = "index.php";</script>';
        exit;
    }
    if ($universo['collab'] > 0) $collab = true;
} else {
    // NOVO UNIVERSO:
    $id_universo = 0;
    $universo['titulo'] = '';
    $universo['id_nome_nativo'] = 0;
}
?>

<input type="hidden" id="codigo" value="<?=$id_universo?>" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item active"><a><?=$id_universo > 0 ? $universo['titulo'] : _t('Novo universo')?></a></li>
                    </ol>
                </h2>
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body appLoad">
    <div class="container-xl">
        <div class="row row-deckx row-cards">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"><?=_t('Sobre o universo')?></h4>
                        <div class="card-actions">
                            <a href="#" id="btnSalvar" onclick="gravarDados()" style="display:none" class="btn btn-primary">
                                <?=_t('Salvar')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-5">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label"><?=_t('Nome legível')?></label>
                                    <input type="text" class="form-control" id="nome_legivel" placeholder="Terra Média" value="<?=$universo['titulo']?>" onchange="showGravarDados()">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><?=_t('Descrição')?></label>
                                    <textarea id="descricao"><?=$universo['descricao']?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-3">
                                        <div class="mb-3">
                                            <label class="form-label"><?=_t('Nome nativo')?></label>
                                            <input type="text" class="form-control" id="nome_nativo" value="<?=$universo['nome_nativo']?>" onchange="showGravarDados()">
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="mb-3">
                                            <div class="form-label"><?=_t('Idioma da descrição')?></div>
                                            <select type="text" class="form-select" id="idioma_descricao" onchange="showGravarDados()">
                                                <?php 
                                                $langs = mysqli_query($GLOBALS['dblink'], "SELECT * FROM idiomas WHERE status > 7 AND buscavel = 1;") or die(mysqli_error($GLOBALS['dblink']));
                                                $l = $universo['id_idioma_descricao'] > 0 ? $universo['id_idioma_descricao'] : $_SESSION['KondisonairUzatorDiom'];
                                                while ($lang = mysqli_fetch_assoc($langs)) {
                                                    if ($lang['id'] > 0) {
                                                        echo '<option value="'.$lang['id'].'"';
                                                        if ($l == $lang['id']) echo ' selected';
                                                        echo '>'.$lang['nome_legivel'].'</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="mb-3">
                                            <div class="form-label"><?=_t('Status')?></div>
                                            <select class="form-select" id="status" onchange="showGravarDados()">
                                                <option value="0" <?php if ($universo['status'] == 0) echo 'selected'; ?>><?=_t('Rascunho')?></option>
                                                <option value="1" <?php if ($universo['status'] == 1) echo 'selected'; ?>><?=_t('Em construção')?></option>
                                                <option value="3" <?php if ($universo['status'] == 3) echo 'selected'; ?>><?=_t('Básico')?></option>
                                                <option value="7" <?php if ($universo['status'] == 7) echo 'selected'; ?>><?=_t('Funcional')?></option>
                                                <option value="8" <?php if ($universo['status'] == 8) echo 'selected'; ?>><?=_t('Quase completo')?></option>
                                                <option value="9" <?php if ($universo['status'] == 9) echo 'selected'; ?>><?=_t('Completo')?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="mb-3">
                                            <div class="form-label"><?=_t('Visibilidade')?></div>
                                            <label class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" <?php if ($universo['publico'] == '1') echo 'checked'; ?> id="publico" onchange="showGravarDados()">
                                                <span class="form-check-label"><?=_t('Deixar público')?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!$collab || $universo['id_usuario'] == $_SESSION['KondisonairUzatorIDX']) {  
                                    $edC = ($universo['id_usuario'] == $_SESSION['KondisonairUzatorIDX']);
                                ?>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label"><?=_t('Colaboradores')?></label>
                                            <select type="text" multiple class="form-select" id="collabs" <?php if (!$edC) echo 'disabled readonly'; ?> onchange="showGravarDados()">
                                                <?php 
                                                $cs = mysqli_query($GLOBALS['dblink'], "SELECT u.username FROM collabs_realidades c LEFT JOIN usuarios u ON u.id = c.id_usuario WHERE c.id_realidade = ".$id_universo.";") or die(mysqli_error($GLOBALS['dblink']));
                                                while ($s = mysqli_fetch_assoc($cs)) {
                                                    if ($s['id'] == $_SESSION['KondisonairUzatorIDX']) continue;
                                                    echo '<option value="'.$s['username'].'" selected>'.$s['username'].'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Mais detalhes')?></h3>
                        <?php if ($id_universo > 0) { ?>
                        <div class="card-actions">
                            <a href="#" onclick="openConfigs()" class="btn btn-primary">
                                <?=_t('Configurações')?>
                            </a>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="list-group list-group-flush list-group-hoverable">
                        <?php if ($id_universo > 0) { ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=edittimesystems&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Sistemas de tempo')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numTimeSystems']?> <?=_t('calendários')?></div>
                                </div>
                            </div>
                        </div>


                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=editentities&et=character&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Personagens')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numPersonagens']?> <?=_t('personagens')?></div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=editentities&et=place&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Locais')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numLocais']?> <?=_t('locais')?></div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=editentities&et=item&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Itens')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numItens']?> <?=_t('objetos')?></div>
                                </div>
                            </div>
                        </div>
                        <!--div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=editentitytypes&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Tipos de entidades')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numTipos']?> <?=_t('tipos')?></div>
                                </div>
                            </div>
                        </div-->
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=editentities&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Mais entidades')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numEntidades']?> <?=_t('entidades')?></div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=editstats&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Desenvolvimentos')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numStats']?> <?=_t('parâmetros')?></div>
                                </div>
                            </div>
                        </div>


                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=editmoments&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Linha do tempo')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numEventos']?> <?=_t('eventos')?></div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=editstories&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Histórias')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numHistorias']?> <?=_t('histórias')?></div>
                                </div>
                            </div>
                        </div>
                        <!--div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=myarticles&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Artigos')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numArtigos']?> <?=_t('artigos publicados')?></div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col text-truncate">
                                    <a href="?page=texts&rid=<?=$id_universo?>" class="text-reset d-block"><?=_t('Textos')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$universo['numTextos']?> <?=_t('textos publicados')?></div>
                                </div>
                            </div>
                        </div-->


                        <?php } else { ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="d-block text-secondary mt-n1"><?=_t('Aqui estarão mais detalhes que você poderá incluir após salvar o seu universo')?></div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checarAcronimo() {
    showGravarDados();
}

<?php if (!$collab || $universo['id_usuario'] == $_SESSION['KondisonairUzatorIDX']) { ?>
function showGravarDados()    {
    $("#btnSalvar").show();
}

function gravarDados() {
    $.post("?action=ajaxGravarRealidade&rid=" + $('#codigo').val(), {
        titulo: $('#nome_legivel').val(),
        descricao: tinymce.get('descricao').getContent(),
        publico: document.getElementById('publico').checked ? 1 : 0,
        nome_nativo: $('#nome_nativo').val(),
        id_categoria: $('#id_categoria').val(),
        id_genero: $('#id_genero').val(),
        idioma_descricao: $('#idioma_descricao').val(),
        status: $('#status').val(),
        acronimo: $('#acronimo').val(),
        collabs: $('#collabs').val()
    }, function(data) {
        if ($.trim(data) > 0) {
            if ($('#codigo').val() == 0) window.location = "?page=editworld&rid=" + $.trim(data);
            $("#btnSalvar").hide();
        } else {
            alert(data);
        }
    });
}
<?php } ?>

function selectCategoria() {
    if ($('#id_categoria').val() == '-1') {
        $.confirm({
            title: 'Nova categoria',
            type: 'green',
            typeAnimated: true,
            content: '<input type="text" class="form-control" id="nome_categoria" placeholder="Nome da Categoria">',
            containerFluid: true,
            buttons: {
                "Salvar": function() {
                    var nome = this.$content.find('#nome_categoria').val();
                    if (nome == '') {
                        $.alert('Nome vazio!');
                        return false;
                    }
                    $.get("?action=ajaxNovaCategoria&nome=" + nome, function(data) {
                        window.location = "?page=editworld&rid=<?=$id_universo?>";
                    });
                },
                Cancelar: function() {}
            }
        });
    } else {
        gravarDados();
    }
}

$(document).ready(function() {
    appLoad();
});

formatarTablerSelect('collabs', 'body', true);
formatarTablerSelect('id_categoria');
formatarTablerSelect('id_genero');
formatarTablerSelect('idioma_descricao');
formatarTablerSelect('status');

function openConfigs() {
    $("#modal-configs").modal('show');
}

document.addEventListener("DOMContentLoaded", function() {
    let options = {
        selector: '#descricao',
        height: 300,
        menubar: false,
        statusbar: false,
        setup: (editor) => {
            editor.on('keyup', (e) => {
                showGravarDados();
            });
        },
        plugins: ['advlist', 'autolink', 'lists', 'link', 'paste'],
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link unlink removeformat',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }'
    };
    if (localStorage.getItem("tabler-theme") === 'dark') {
        options.skin = 'oxide-dark';
        options.content_css = 'dark';
    }
    tinyMCE.init(options);
});
</script>

<div class="modal modal-blur fade" id="modal-configs" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mais configurações</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apagar cache local<br>
                Exportar universo para arquivo local
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    Excluir universo!
                </a>
                <a href="#" class="btn btn-primary ms-auto" data-bs-dismiss="modal">Fechar</a>
            </div>
        </div>
    </div>
</div>
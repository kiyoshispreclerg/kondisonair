<?php
$id_realidade = $_GET['rid'];
$superior = $_GET['superior'] ?? 0;

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
?>

<input type="hidden" id="codigo" value="<?=$id_realidade?>" />

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
                            $result = mysqli_query($GLOBALS['dblink'], "SELECT id, nome, id_superior 
                                FROM momentos 
                                WHERE id = $current_superior AND id_realidade = $id_realidade LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
                            if ($moment = mysqli_fetch_assoc($result)) {
                                $breadcrumbs[] = '<li class="breadcrumb-item"><a href="?page=editmoments&rid=' . $id_realidade . '&superior=' . $moment['id_superior'] . '">' . htmlspecialchars($moment['nome']) . '</a></li>';
                                $current_superior = (int)$moment['id_superior'];
                            } else {
                                break; // Prevent infinite loop if id_superior is invalid
                            }
                        }
                        echo implode('', array_reverse($breadcrumbs));
                        ?>
                        <li class="breadcrumb-item active"><a><?=_t('Momentos')?></a></li>
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
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title" id="timelineTitle"><?=_t('Linha do Tempo')?></h3>
                        <div class="card-actions">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <?=_t('Filtrar Momentos')?>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="timelineContainer" style="position: relative; height: 400px; overflow: auto;">
                            <div id="timeline" class="timeline">
                                <!-- Botão no topo da linha do tempo -->
                                <div class="timeline-new-moment">
                                    <button class="btn btn-icon btn-primary timeline-new-btn" onclick="novoMomento('inicio')" title="<?=_t('Novo momento no início')?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                    </button>
                                </div>
                                <!-- Momentos serão inseridos aqui -->
                                <div id="timelineMoments"></div>
                                <!-- Botão no fim da linha do tempo -->
                                <div class="timeline-new-moment">
                                    <button class="btn btn-icon btn-primary timeline-new-btn" onclick="novoMomento('fim')" title="<?=_t('Novo momento no fim')?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card sticky-top">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Editar momento')?></h3>

                        <div class="card-actions">
                            <button class="btn btn-primary" onclick="novoMomento('fim')">
                                <?=_t('Novo')?>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="mid" />
                        <input type="hidden" id="ordem">
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Nome')?>*</label>
                            <input type="text" class="form-control" id="nome" placeholder="<?=_t('Ex.: Batalha de Eldoria')?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Data no Calendário')?>*</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="data_calendario" readonly placeholder="<?=_t('Nenhuma data selecionada')?>">
                                <input type="hidden" id="id_time_system">
                                <input type="hidden" id="time_value">
                                <input type="hidden" id="unit_values" value="{}">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dateModal"><?=_t('Selecionar Data')?></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Descrição')?></label>
                            <textarea class="form-control" id="descricao" rows="5" placeholder="<?=_t('Ex.: Descrição do momento')?>"></textarea>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="gravarMomento()"><?=_t('Salvar')?></button>
                        <button type="button" class="btn btn-danger" id="btnExcluir" style="display: none;" onclick="delMomento()"><?=_t('Excluir')?></button>
                        <div class="mt-3" id="momentStats">
                            <p><?=_t('Selecione um momento para ver estatísticas.')?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Seleção de Data -->
<div class="modal modal-blur fade" id="dateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=_t('Selecionar data')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="dateInputs"></div>
                <button type="button" class="btn btn-primary mt-3" onclick="saveDateSelection()"><?=_t('Confirmar')?></button>
            </div>
        </div>
    </div>
</div>


<!-- Modal de Filtros -->
<div class="modal modal-blur fade" id="filterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=_t('Filtrar Momentos')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?=_t('Tipo de Filtro')?></label>
                    <select id="filterType" class="form-select">
                        <option value="current" selected><?=_t('Apenas nível do momento atual')?></option>
                        <option value="all"><?=_t('Todos os momentos a partir do nível atual')?></option>
                        <option value="stories"><?=_t('Filtrar por histórias')?></option>
                        <option value="moments"><?=_t('Filtrar por momentos')?></option>
                        <option value="entities"><?=_t('Filtrar por entidades')?></option>
                    </select>
                </div>
                <div id="storiesFilter" style="display: none;">
                    <label class="form-label"><?=_t('Selecionar Histórias')?></label>
                    <div class="tree-view" id="storiesTree">
                        <?php
                        $stories = mysqli_query($GLOBALS['dblink'], "SELECT h.id, h.titulo, h.id_superior,
                            (SELECT COUNT(*) FROM historias sub WHERE sub.id_superior = h.id) as has_subs
                            FROM historias h 
                            WHERE h.id_realidade = $id_realidade 
                            ORDER BY h.id_superior, h.titulo;") or die(mysqli_error($GLOBALS['dblink']));
                        
                        $stories_tree = [];
                        $story_map = [];
                        while ($s = mysqli_fetch_assoc($stories)) {
                            $story_map[$s['id']] = $s;
                            $story_map[$s['id']]['children'] = [];
                        }
                        foreach ($story_map as $id => $story) {
                            if ($story['id_superior'] > 0 && isset($story_map[$story['id_superior']])) {
                                $story_map[$story['id_superior']]['children'][] = &$story_map[$id];
                            } else {
                                $stories_tree[$id] = &$story_map[$id];
                            }
                        }
                        
                        function renderStoryTree($stories, $level = 0) {
                            foreach ($stories as $story) {
                                $has_children = !empty($story['children']);
                                echo '<div class="form-check" style="margin-left: ' . ($level * 20) . 'px;">
                                    <input class="form-check-input story-checkbox" type="checkbox" value="' . $story['id'] . '" id="story_' . $story['id'] . '" data-children="' . ($has_children ? implode(',', array_column($story['children'], 'id')) : '') . '">';
                                if ($has_children) {
                                    echo '<a href="#story_collapse_' . $story['id'] . '" data-bs-toggle="collapse" class="ms-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chevron-down" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 9l6 6l6 -6" /></svg>
                                    </a>';
                                }
                                echo '<label class="form-check-label" for="story_' . $story['id'] . '">' . htmlspecialchars($story['titulo']) . '</label>
                                </div>';
                                if ($has_children) {
                                    echo '<div class="collapse" id="story_collapse_' . $story['id'] . '">';
                                    renderStoryTree($story['children'], $level + 1);
                                    echo '</div>';
                                }
                            }
                        }
                        renderStoryTree($stories_tree);
                        ?>
                    </div>
                </div>
                <div id="momentsFilter" style="display: none;">
                    <label class="form-label"><?=_t('Selecionar Momentos')?></label>
                    <div class="tree-view" id="momentsTree">
                        <?php
                        $moments = mysqli_query($GLOBALS['dblink'], "SELECT m.id, m.nome, m.id_superior,
                            (SELECT COUNT(*) FROM momentos sub WHERE sub.id_superior = m.id) as has_subs
                            FROM momentos m 
                            WHERE m.id_realidade = $id_realidade 
                            ORDER BY m.id_superior, m.nome;") or die(mysqli_error($GLOBALS['dblink']));
                        
                        $moments_tree = [];
                        $moment_map = [];
                        while ($m = mysqli_fetch_assoc($moments)) {
                            $moment_map[$m['id']] = $m;
                            $moment_map[$m['id']]['children'] = [];
                        }
                        foreach ($moment_map as $id => $moment) {
                            if ($moment['id_superior'] > 0 && isset($moment_map[$moment['id_superior']])) {
                                $moment_map[$moment['id_superior']]['children'][] = &$moment_map[$id];
                            } else {
                                $moments_tree[$id] = &$moment_map[$id];
                            }
                        }
                        
                        function renderMomentTree($moments, $level = 0, $current_superior = 0) {
                            foreach ($moments as $moment) {
                                $has_children = !empty($moment['children']);
                                if ($current_superior == 0 || in_array($current_superior, array_merge([$current_superior], getParentMoments($moment['id'])))) {
                                    echo '<div class="form-check" style="margin-left: ' . ($level * 20) . 'px;">
                                        <input class="form-check-input moment-checkbox" type="checkbox" value="' . $moment['id'] . '" id="moment_' . $moment['id'] . '" data-children="' . ($has_children ? implode(',', array_column($moment['children'], 'id')) : '') . '">';
                                    if ($has_children) {
                                        echo '<a href="#moment_collapse_' . $moment['id'] . '" data-bs-toggle="collapse" class="ms-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chevron-down" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 9l6 6l6 -6" /></svg>
                                        </a>';
                                    }
                                    echo '<label class="form-check-label" for="moment_' . $moment['id'] . '">' . htmlspecialchars($moment['nome']) . '</label>
                                    </div>';
                                    if ($has_children) {
                                        echo '<div class="collapse" id="moment_collapse_' . $moment['id'] . '">';
                                        renderMomentTree($moment['children'], $level + 1, $current_superior);
                                        echo '</div>';
                                    }
                                }
                            }
                        }
                        
                        function getParentMoments($moment_id) {
                            global $moment_map;
                            $parents = [];
                            $current = $moment_map[$moment_id] ?? null;
                            while ($current && $current['id_superior'] > 0) {
                                $parents[] = $current['id_superior'];
                                $current = $moment_map[$current['id_superior']] ?? null;
                            }
                            return $parents;
                        }
                        
                        renderMomentTree($moments_tree, 0, $superior);
                        ?>
                    </div>
                </div>
                <div id="entitiesFilter" style="display: none;">
                    <label class="form-label"><?=_t('Selecionar Entidades')?></label>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="entitiesScope" value="current" checked>
                            <label class="form-check-label" for="entitiesScope"><?=_t('Apenas nível do momento atual')?></label>
                        </div>
                    </div>
                    <div class="tree-view" id="entitiesTree">
                        <?php
                        $entity_types = [
                            'character' => _t('Personagens'),
                            'place' => _t('Lugares'),
                            'item' => _t('Itens'),
                            'other' => _t('Outras Entidades')
                        ];
                        foreach ($entity_types as $rule => $label) {
                            echo '<h6>' . $label . '</h6>';
                            $entities = mysqli_query($GLOBALS['dblink'], "SELECT id, nome_legivel 
                                FROM entidades 
                                WHERE id_realidade = $id_realidade AND rule = '$rule' 
                                ORDER BY nome_legivel;") or die(mysqli_error($GLOBALS['dblink']));
                            while ($e = mysqli_fetch_assoc($entities)) {
                                echo '<div class="form-check" style="margin-left: 20px;">
                                    <input class="form-check-input entity-checkbox" type="checkbox" value="' . $e['id'] . '" id="entity_' . $e['id'] . '" data-rule="' . $rule . '">
                                    <label class="form-check-label" for="entity_' . $e['id'] . '">' . htmlspecialchars($e['nome_legivel']) . '</label>
                                </div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=_t('Cancelar')?></button>
                <button type="button" class="btn btn-primary" onclick="applyFilters()"><?=_t('OK')?></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    carregarLinhaDoTempo();
    formatarTablerSelect('status');
    novoMomento(); // Inicializa o formulário limpo

    // Mostrar/esconder opções de filtro no modal
    $('#filterType').change(function() {
        $('#storiesFilter, #momentsFilter, #entitiesFilter').hide();
        if ($(this).val() === 'stories') {
            $('#storiesFilter').show();
        } else if ($(this).val() === 'moments') {
            $('#momentsFilter').show();
        } else if ($(this).val() === 'entities') {
            $('#entitiesFilter').show();
        }
    });

    // Handle checkbox selection for stories and moments
    $('.story-checkbox, .moment-checkbox').change(function() {
        const isChecked = $(this).is(':checked');
        const children = $(this).data('children') ? $(this).data('children').split(',').map(id => `#${$(this).hasClass('story-checkbox') ? 'story_' : 'moment_'}${id}`) : [];
        children.forEach(child => {
            $(child).prop('checked', isChecked);
            $(child).trigger('change');
        });
    });

    // Rotate chevron on collapse toggle
    $('[data-bs-toggle="collapse"]').on('click', function() {
        const icon = $(this).find('.icon-tabler-chevron-down');
        const target = $(this).attr('href');
        $(target).on('shown.bs.collapse hidden.bs.collapse', function() {
            icon.toggleClass('rotate-180');
        });
    });

    loadDefaultTimeSystem();

    // Habilitar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});

let selectedStories = [];
let selectedMoments = [];
let selectedEntities = [];
let entitiesScope = 'current';
let currentFilterType = 'current';

function carregarLinhaDoTempo() {
    const params = {
        action: 'listMoments',
        rid: <?=$id_realidade?>,
        superior: <?=$superior?>,
        filterType: currentFilterType
    };
    if (currentFilterType === 'stories') {
        params.stories = selectedStories;
    } else if (currentFilterType === 'moments') {
        params.moments = selectedMoments;
    } else if (currentFilterType === 'entities') {
        params.entities = selectedEntities;
        params.entitiesScope = entitiesScope;
    }
    
    $.getJSON("?" + $.param(params), function(data) {
        let html = '';
        let previousOrdem = null;
        $.each(data, function(i, momento) {
            const side = i % 2 === 0 ? 'left' : 'right';
            if (i > 0 && previousOrdem !== null) {
                const ordemEntre = (parseInt(previousOrdem) + parseInt(momento.ordem)) / 2;
                html += `
                    <div class="timeline-new-moment">
                        <button class="btn btn-icon btn-primary timeline-new-btn" onclick="novoMomento(${ordemEntre})" title="<?=_t('Novo momento aqui')?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                        </button>
                    </div>`;
            }
            let contexto = '';
            if (momento.historias && momento.historias.length > 0) {
                // Juntar os títulos das histórias com vírgula e criar tooltip com os títulos completos
                const historiaTitulos = momento.historias.map(h => h.titulo).join(' - ');
                //const historiaCaminhos = momento.historias.map( h => h.titulo + ': ' + (h.caminho || '') ).join('\n');
                contexto = `
                    <small class="text-muted text-wrap d-block" data-bs-toggle="tooltip" data-bs-placement="top" title="${historiaTitulos}">
                        <?=_t('Histórias')?>: ${historiaTitulos}
                    </small>`;
                // Adicionar entidades, se existirem
                if (momento.entidades && momento.entidades.length > 0) {
                    const entidadeNomes = momento.entidades.join(', ');
                    contexto += `
                        <small class="text-muted text-wrap d-block" data-bs-toggle="tooltip" data-bs-placement="top" title="${entidadeNomes}">
                            <?=_t('Entidades')?>: ${entidadeNomes}
                        </small>`;
                }
            } else if (momento.momento_pai_nome) {
                contexto = `<small class="text-muted"><?=_t('Momento Pai')?>: ${momento.momento_pai_nome}</small>`;
            }/*
            if (momento.historia_titulo) {
                contexto = `<small class="text-muted" data-bs-toggle="tooltip" data-bs-placement="top" title="${momento.historia_caminho || ''}"><?=_t('História')?>: ${momento.historia_titulo}</small>`;
            } else if (momento.momento_pai_nome) {
                contexto = `<small class="text-muted"><?=_t('Momento Pai')?>: ${momento.momento_pai_nome}</small>`;
            }*/
            html += `
                <div class="timeline-item ${side}" onclick="editarMomento(${momento.id}, '${momento.nome}', \`${momento.descricao}\`, '${momento.data_calendario}', ${momento.ordem}, ${momento.time_value})">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content" id="moment-${momento.id}">
                        <h4>${momento.nome}</h4>
                        ${contexto}
                        <p>${momento.descricao || ''}</p>
                        <small>${momento.data_calendario}</small>
                        <a href="?page=editmoments&rid=<?=$id_realidade?>&superior=${momento.id}" class="btn btn-icon btn-primary timeline-expand-btn" title="<?=_t('Expandir sub-momentos')?>">
                            ${momento.subs}
                        </a>
                    </div>
                </div>`;
            previousOrdem = momento.ordem;
        });
        $('#timelineMoments').html(html || '<p><?=_t('Nenhum momento cadastrado.')?></p>');
        
        // Atualizar título do card
        let title = '<?=_t('Linha do Tempo')?>';
        if (currentFilterType !== 'current') {
            let filterText = '';
            if (currentFilterType === 'all') {
                filterText = '<?=_t('Todos os Momentos (Nível Atual)')?>';
            } else if (currentFilterType === 'stories') {
                filterText = '<?=_t('Histórias')?>' + (selectedStories.length ? ` (${selectedStories.length})` : '');
            } else if (currentFilterType === 'moments') {
                filterText = '<?=_t('Momentos')?>' + (selectedMoments.length ? ` (${selectedMoments.length})` : '');
            } else if (currentFilterType === 'entities') {
                const rules = [...new Set($('.entity-checkbox:checked').map(function() { return $(this).data('rule'); }).get())];
                const ruleLabels = {
                    'character': '<?=_t('Personagens')?>',
                    'place': '<?=_t('Lugares')?>',
                    'item': '<?=_t('Itens')?>',
                    'other': '<?=_t('Outras Entidades')?>'
                };
                filterText = '<?=_t('Entidades')?>: ' + (rules.length ? rules.map(r => ruleLabels[r]).join(', ') : '<?=_t('Nenhuma selecionada')?>');
                if (entitiesScope === 'current') {
                    filterText += ' (<?=_t('Nível Atual')?>)';
                }
            }
            title += ` (${filterText})`;
        }
        $('#timelineTitle').text(title);
        $('[data-bs-toggle="tooltip"]').tooltip();
        carregarEstatisticas(0);
    });
}

function applyFilters() {
    currentFilterType = $('#filterType').val();
    selectedStories = [];
    selectedMoments = [];
    selectedEntities = [];
    entitiesScope = $('#entitiesScope').is(':checked') ? 'current' : 'all';
    
    if (currentFilterType === 'stories') {
        $('.story-checkbox:checked').each(function() {
            selectedStories.push(parseInt($(this).val()));
        });
    } else if (currentFilterType === 'moments') {
        $('.moment-checkbox:checked').each(function() {
            selectedMoments.push(parseInt($(this).val()));
        });
    } else if (currentFilterType === 'entities') {
        $('.entity-checkbox:checked').each(function() {
            selectedEntities.push(parseInt($(this).val()));
        });
    }
    
    carregarLinhaDoTempo();
    $('#filterModal').modal('hide');
}

function novoMomento(posicao = 'fim') {
    $('.timeline-selected').removeClass('timeline-selected');
    $('#mid').val(0);
    $('#nome').val('');
    $('#descricao').val('');
    $('#data_calendario').val('');
    $('#nome').removeClass('is-invalid');
    $('#data_calendario').removeClass('is-invalid');
    $('#ordem').removeClass('is-invalid');
    $('#btnExcluir').hide();

    /*
    $.getJSON("?action=listMoments&rid=<?=$id_realidade?>&superior=<?=$superior?>", function(data) {
        let ordem = 0;
        if (data.length === 0) {
            ordem = 0;
        } else if (typeof posicao === 'number') {
            ordem = posicao;
        } else if (posicao === 'inicio') {
            const menorOrdem = Math.min(...data.map(m => parseInt(m.ordem)));
            ordem = menorOrdem - 1;
        } else {
            const maiorOrdem = Math.max(...data.map(m => parseInt(m.ordem)));
            ordem = maiorOrdem + 1;
        }
        $('#ordem').val(ordem);
    });
    */

    carregarEstatisticas(0);
}

function editarMomento(mid = 0, nome = '', descricao = '', data_calendario = '', ordem = 0, time_value = 0) {

    $('.timeline-selected').removeClass('timeline-selected');
    $('#moment-'+mid).addClass('timeline-selected');

    $('#mid').val(mid);
    $('#nome').val(nome);
    $('#time_value').val(time_value);
    $('#descricao').val(descricao);
    $('#data_calendario').val(data_calendario);
    $('#ordem').val(ordem);
    $('#nome').removeClass('is-invalid');
    $('#data_calendario').removeClass('is-invalid');
    $('#ordem').removeClass('is-invalid');
    $('#btnExcluir').show();

    console.log("Timevalue: "+time_value)
    setCalendarToTimeValue(time_value, 'c-year', 'c-month', 'time-value', 'data-calendario');

    carregarEstatisticas(mid);
}

function gravarMomento() {
    if ($('#nome').val() == '' || $('#data_calendario').val() == '') {
        $('#nome').addClass('is-invalid');
        $('#data_calendario').addClass('is-invalid');
        // $('#ordem').addClass('is-invalid');
        return;
    }
    $('#nome').removeClass('is-invalid');
    $('#data_calendario').removeClass('is-invalid');
    $('#ordem').removeClass('is-invalid');

    $.post("?action=ajaxGravarMomento&mid=" + $('#mid').val() + "&rid=<?=$id_realidade?>", {
        nome: $('#nome').val(),
        descricao: $('#descricao').val(),
        superior: <?=$superior?>,
        data_calendario: $('#data_calendario').val(),
        time_system: $('#id_time_system').val(),
        time_value: $('#time_value').val(),
        ordem: $('#ordem').val()
    }, function(data) {
        if ($.trim(data) > 0) {
            carregarLinhaDoTempo();
            novoMomento();
        } else {
            alert(data);
        }
    });
}

function carregarEstatisticas(mid) {
    $.get("?action=ajaxGetMomentStats&mid=" + mid + "&rid=<?=$id_realidade?>", function(data) {
        $('#momentStats').html(data);
    });
}

function delMomento(mid) {
    if (confirm("<?=_t('Apagar este momento? Isso pode afetar histórias e estatísticas associadas!')?>")) {
        $.get("?action=ajaxDelMomento&mid=" + mid, function(data) {
            if ($.trim(data) == 'ok') {
                carregarLinhaDoTempo();
                novoMomento();
            } else {
                alert(data);
            }
        });
    }
}

function carregarCalendario(sid) {
    let htsml = `<div class="d-flex mb-3">
        <input type="number" class="form-control me-2" id="c-year" value="0" placeholder="Ano" min="-1000000" max="1000000">
        <input type="hidden" id="time-value">
        <select class="form-select" id="c-month"></select>
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
        'data-calendario', <?=$id_realidade?>
    );
}

function setDateClicked(timeValue, message){
    //$('#time_value').val( $('#time-value').val() );
    //$('#dateModal').modal('hide');
}

function saveDateSelection() {
    $('#time_value').val( $('#time-value').val() );
    $('#data_calendario').val( $('#data-calendario').val() );
    $('#dateModal').modal('hide');
    return;

    const id_time_system = parseInt($('#id_time_system').val());
    const values = {};
    let hasValue = false;
    $('.unit-value').each(function() {
        const value = parseInt($(this).val());
        if (value >= 0) {
            values[$(this).data('unit-id')] = value;
            hasValue = true;
        }
    });

    if (!hasValue) {
        $('#formatted_date').val('');
        $('#time_value').val('');
        $('#unit_values').val('{}');
        $('#dateModal').modal('hide');
        return;
    }

    /*
    $.post('?action=saveMomentDate', {
        action: 'saveMomentDate',
        rid: <?=$id_realidade?>,
        id_time_system,
        values
    }, function(response) {
        if (response.error) {
            alert(response.error);
        } else {
            $('#formatted_date').val(response.formatted_date);
            $('#time_value').val(response.time_value);
            $('#unit_values').val(JSON.stringify(response.stored_values));
            $('#dateModal').modal('hide');
        }
    }, 'json');
    */
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



</script>
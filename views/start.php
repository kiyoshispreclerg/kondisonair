
<style>
.ksc-hero {
    padding: 4rem 0 3rem;
    border-bottom: 1px solid var(--tblr-border-color);
}
.ksc-hero-title {
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 700;
    line-height: 1.15;
    letter-spacing: -0.02em;
    color: var(--tblr-body-color);
    text-wrap: balance;
    margin-bottom: .75rem;
}
.ksc-hero-title em {
    font-style: normal;
    color: var(--tblr-primary);
}
.ksc-hero-desc {
    font-size: 1.05rem;
    color: var(--tblr-secondary);
    max-width: 42ch;
    line-height: 1.6;
    margin-bottom: 1.75rem;
    text-wrap: balance;
}
.ksc-sample-label {
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--tblr-secondary);
    margin-bottom: .35rem;
}
.ksc-sample-value {
    font-size: 1.25rem;
    font-weight: 600;
    line-height: 1.3;
    color: var(--tblr-body-color);
}
.ksc-sample-value a {
    color: inherit;
    text-decoration: none;
}
.ksc-sample-value a:hover { color: var(--tblr-primary); }
.ksc-sample-sub {
    font-size: .85rem;
    color: var(--tblr-secondary);
    margin-top: .15rem;
}
.ksc-greeting {
    font-size: 1.1rem;
    color: var(--tblr-secondary);
    font-weight: 400;
}
.ksc-greeting strong { color: var(--tblr-body-color); font-weight: 600; }

:root {
    --ksc-hero-bg: #f4f6fb;
}
body[data-bs-theme="dark"] {
    --ksc-hero-bg: #0f1117;
}
</style>

<?php
$uid = (int)$_SESSION['KondisonairUzatorIDX'];
$username = $_SESSION['KondisonairUzatorID'] ?? '';
$inscAberta = ($opcoes['inscr_aberta'] ?? '0') == '1';
?>

<?php if ($uid <= 0): ?>
<!-- ── Hero (visitante não logado) ── -->
<div class="ksc-hero">
  <div class="container-xl">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <p class="ksc-hero-title">
          <?=_t('Construa <em>idiomas</em>.<br>Compartilhe mundos.')?>
        </p>
        <p class="ksc-hero-desc">
          <?=_t('Kondisonair é uma plataforma para criar e documentar línguas construídas — fonologia, léxico, morfologia, sistemas de escrita e textos, tudo num só lugar.')?>
        </p>
        <div class="d-flex gap-2 flex-wrap">
          <a href="?page=login" class="btn btn-primary"><?=_t('Entrar')?></a>
          <?php if ($inscAberta): ?>
          <a href="?page=register" class="btn btn-outline-secondary"><?=_t('Criar conta')?></a>
          <?php endif; ?>
          <!--a href="?page=languages" class="btn btn-ghost-secondary"><?=_t('Explorar idiomas')?></a-->
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block">
        <?php
          $lasH = mysqli_query($GLOBALS['dblink'],
            "SELECT i.*,
            (SELECT palavra FROM palavrasNativas WHERE id_palavra = i.id_nome_nativo AND principal = 1
              AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) LIMIT 1) as nativo,
            (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as eid,
            (SELECT id_fonte FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as fonte,
            (SELECT tamanho FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as tamanho
            FROM idiomas i
            WHERE publico = 1 AND id IN(SELECT id_idioma FROM studason_tests WHERE num_palavras > 0)
            ORDER BY RAND() LIMIT 3;") or die(mysqli_error($GLOBALS['dblink']));
          $heroLangs = [];
          while ($hl = mysqli_fetch_assoc($lasH)) $heroLangs[] = $hl;
        ?>
        <?php if (count($heroLangs)): ?>
        <div class="card shadow-sm">
          <div class="card-body py-3">
            <div class="ksc-sample-label mb-2"><?=_t('Idiomas recentes')?></div>
            <div class="divide-y">
            <?php foreach ($heroLangs as $hl): ?>
            <div class="py-2">
              <a href="?page=language&iid=<?=$hl['id']?>" class="fw-semibold text-body text-decoration-none">
                <?=htmlspecialchars($hl['nome_legivel'])?>
                <?php if ($hl['nativo']): ?>
                  <span class="text-secondary fw-normal ms-1"><?=getSpanPalavraNativa($hl['nativo'],$hl['eid'],$hl['fonte'],$hl['tamanho'])?></span>
                <?php endif; ?>
              </a>
            </div>
            <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ── Saudação (usuário logado) ── -->
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <span class="ksc-greeting"><?=_t('Olá,')?> <strong><?=htmlspecialchars($username)?></strong></span>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── Corpo da página ── -->
<div class="page-body">
  <div class="container-xl">
    <div class="row row-deckx row-cards">
      <div class="col-md-8">

        <!-- Card de amostras -->
        <?php
          $las = mysqli_query($GLOBALS['dblink'],
            "SELECT i.*,
            (SELECT palavra FROM palavrasNativas WHERE id_palavra = i.id_nome_nativo AND principal = 1 AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) LIMIT 1) as nativo,
            (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as eid,
            (SELECT id_fonte FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as fonte,
            (SELECT tamanho FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as tamanho
            FROM idiomas i
            WHERE publico = 1
            AND id IN(SELECT id_idioma FROM studason_tests WHERE num_palavras > 0)
            ORDER BY RAND() LIMIT 1;"
          ) or die(mysqli_error($GLOBALS['dblink']));
          $la = mysqli_fetch_assoc($las);
          $eid = $la['eid'];
        ?>
        <?php if ($la['id']): ?>
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><?=_t('Algumas coisas compartilhadas por aqui')?></h3>
          </div>
          <div class="card-body">
            <div class="row g-4">

              <!-- Idioma -->
              <div class="col-sm-4">
                <div class="ksc-sample-label"><?=_t('Idioma aleatório')?></div>
                <div class="ksc-sample-value">
                  <a href="?page=language&iid=<?=$la['id']?>">
                    <?=htmlspecialchars($la['nome_legivel'])?>
                  </a>
                </div>
                <?php if ($la['nativo']): ?>
                <div class="ksc-sample-sub">
                  <?=getSpanPalavraNativa($la['nativo'],$la['eid'],$la['fonte'],$la['tamanho'])?>
                </div>
                <?php endif; ?>
              </div>

              <!-- Palavra -->
              <div class="col-sm-4">
                <div class="ksc-sample-label"><?=_t('Palavra aleatória')?></div>
                <?php
                  $lasW = mysqli_query($GLOBALS['dblink'],
                    "SELECT *,
                    (SELECT id_fonte FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) as fonte,
                    (SELECT tamanho FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) as tamanho,
                    (SELECT palavra FROM palavrasNativas WHERE id_palavra = p.id AND principal = 1 AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) LIMIT 1) as nativo
                    FROM palavras p WHERE id_idioma = ".$la['id']." AND publico = 1 ORDER BY RAND() LIMIT 1;"
                  ) or die(mysqli_error($GLOBALS['dblink']));
                  $laW = mysqli_fetch_assoc($lasW);
                  if ($laW):
                    $displayW = $laW['nativo'] != '' ? getSpanPalavraNativa($laW['nativo'],$eid,$laW['fonte'],$laW['tamanho'])
                      : ($laW['romanizacao'] != '' ? htmlspecialchars($laW['romanizacao']) : htmlspecialchars($laW['pronuncia']));
                ?>
                <div class="ksc-sample-value">
                  <a href="?page=word&pid=<?=$laW['id']?>"><?=$displayW?></a>
                </div>
                <?php if ($laW['pronuncia'] && $laW['nativo']): ?>
                <div class="ksc-sample-sub">/<?=htmlspecialchars($laW['pronuncia'])?>/</div>
                <?php endif; ?>
                <div class="ksc-sample-sub"><?=htmlspecialchars($laW['significado'])?></div>
                <?php endif; ?>
              </div>

              <!-- Texto -->
              <div class="col-sm-4">
                <div class="ksc-sample-label"><?=_t('Texto aleatório')?></div>
                <?php
                  $lasT = mysqli_query($GLOBALS['dblink'],
                    "SELECT *,
                    (SELECT id_fonte FROM escritas WHERE id_idioma = t.id_idioma AND padrao = 1 LIMIT 1) as fonte,
                    (SELECT tamanho FROM escritas WHERE id_idioma = t.id_idioma AND padrao = 1 LIMIT 1) as tamanho,
                    (SELECT id FROM escritas WHERE id_idioma = t.id_idioma AND padrao = 1 LIMIT 1) as eid
                    FROM studason_tests t WHERE num_palavras > 0 ORDER BY RAND() LIMIT 1;"
                  ) or die(mysqli_error($GLOBALS['dblink']));
                  $laT = mysqli_fetch_assoc($lasT);
                  if ($laT):
                ?>
                <div class="ksc-sample-value">
                  <a href="?page=text&id=<?=$laT['id']?>"><?=htmlspecialchars($laT['titulo'])?></a>
                </div>
                <div class="ksc-sample-sub">
                  <?=getSpanPalavraNativa(mb_substr($laT['texto'],0,60),$laT['eid'],$laT['fonte'],$laT['tamanho'])?>…
                </div>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="card">
          <div class="card-body text-secondary"><?=_t('Nada compartilhado por aqui ainda.')?></div>
        </div>
        <?php endif; ?>

        <!-- Meus idiomas (logado) -->
        <?php if ($uid > 0): ?>
        <div class="card mt-3">
          <div class="card-header">
            <h3 class="card-title"><?=_t('Meus idiomas')?></h3>
            <div class="card-actions">
              <a href="?page=mylanguages" class="btn btn-primary"><?=_t('Todos')?></a>
            </div>
          </div>
          <div class="card-body">
            <div class="row row-cards">
              <?php
                $res = mysqli_query($GLOBALS['dblink'],"SELECT i.*,
                  (SELECT f.arquivo FROM escritas e LEFT JOIN fontes f ON f.id = e.id_fonte WHERE e.id_idioma = i.id AND e.padrao = 1) as fonte,
                  (SELECT e.id_fonte FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) as id_fonte,
                  (SELECT e.tamanho FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) as tamanho,
                  (SELECT e.id FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) as eid,
                  (SELECT palavra FROM palavrasNativas WHERE id_palavra = i.id_nome_nativo AND principal = 1 AND id_escrita = (SELECT id FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) LIMIT 1) as nativo
                  FROM idiomas i WHERE i.id_usuario = $uid OR i.id IN(SELECT id_idioma FROM collabs WHERE id_usuario = $uid)
                  ORDER BY i.data_modificacao DESC LIMIT 4;") or die(mysqli_error($GLOBALS['dblink']));
                if (mysqli_num_rows($res)==0) {
                  echo '<div class="col">'._t('Nada aqui.').'</div>';
                } else {
                  while ($r = mysqli_fetch_assoc($res)) {
                    $nat = $r['nativo'] != '' ? getSpanPalavraNativa($r['nativo'],$r['eid'],$r['id_fonte'],$r['tamanho']).'<br>' : '';
                    echo '<div class="col-md-6 col-lg-3">
                      <div class="card">
                        <div class="card-body">
                          <a href="?page=editlanguage&iid='.$r['id'].'"><h3 class="card-title">'.$nat.htmlspecialchars($r['nome_legivel']).'</h3></a>
                        </div>
                      </div>
                    </div>';
                  }
                }
              ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Idiomas de sistema (admin) -->
        <?php if ($_SESSION['KondisonairUzatorNivle'] == 100): ?>
        <div class="card mt-3">
          <div class="card-header">
            <h3 class="card-title"><?=_t('Idiomas de sistema')?></h3>
            <div class="card-actions">
              <a href="?page=settings" class="btn btn-primary"><?=_t('Configurações')?></a>
            </div>
          </div>
          <div class="card-body">
            <?php
              $res2 = mysqli_query($GLOBALS['dblink'],"SELECT i.*,
                (SELECT e.id_fonte FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) as id_fonte,
                (SELECT e.tamanho FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) as tamanho,
                (SELECT e.id FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) as eid,
                (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = i.id_nome_nativo AND n.principal = 1 AND n.id_escrita = (SELECT id FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) LIMIT 1) as nativo
                FROM idiomas i WHERE i.id < 10000 ORDER BY i.data_modificacao DESC;") or die(mysqli_error($GLOBALS['dblink']));
              while ($r2 = mysqli_fetch_assoc($res2)) {
                $nat2 = $r2['nativo'] != '' ? getSpanPalavraNativa($r2['nativo'],$r2['eid'],$r2['id_fonte'],$r2['tamanho']).'<br>' : '';
                echo '<a class="btn btn-primary" onclick="acessarEdicaoIdiomaSistema(\''.$r2['id'].'\')">'.$nat2.htmlspecialchars($r2['nome_legivel']).'</a> ';
              }
            ?>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Atividades recentes -->
      <div class="col-md-4">
        <div class="row row-cards">
          <div class="col-12">
            <div class="card" style="height: 28rem">
              <div class="card-header">
                <h3 class="card-title"><?=_t('Atividades recentes')?></h3>
              </div>
              <div class="card-body card-body-scrollable card-body-scrollable-shadow">
                <div class="divide-y">
                  <?php
                    $res2 = mysqli_query($GLOBALS['dblink'],
                      "SELECT u.username, u.id as userid, a.tipo_destino as tipo, a.tipo as t, a.id_destino,
                        DATE_FORMAT(a.data_acao,'%d/%m/%Y %h:%i:%s') as data_acao,
                        i.nome_legivel as d_idioma,
                        f.frase as frase,
                        p.pronuncia as d_palavra, pn.palavra as d_nativo, p.romanizacao as d_romanizacao,
                        e.nome as d_escrita, en.id as eid, en.id_fonte, en.tamanho
                      FROM asons a
                      LEFT JOIN idiomas i ON (a.tipo_destino = 'diom' AND i.id = a.id_destino)
                      LEFT JOIN palavras p ON (a.tipo_destino = 'palavr' AND p.id = a.id_destino)
                        LEFT JOIN idiomas pi ON (pi.id = p.id_idioma)
                      LEFT JOIN frases f ON (a.tipo_destino = 'frase' AND f.id = a.id_destino)
                        LEFT JOIN idiomas fi ON (fi.id = f.id_idioma)
                      LEFT JOIN escritas e ON (a.tipo_destino = 'skreveson' AND e.id = a.id_destino)
                        LEFT JOIN idiomas ei ON (ei.id = e.id_idioma)
                      LEFT JOIN palavrasNativas pn ON (p.id = pn.id_palavra AND pn.id_escrita = (SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1))
                      LEFT JOIN escritas en ON ((en.id_idioma = p.id_idioma) OR (en.id_idioma = f.id_idioma) AND en.padrao = 1)
                      LEFT JOIN usuarios u ON u.id = a.id_usuario
                      WHERE i.publico = 1
                      OR (p.id > 0 AND pi.publico = 1)
                      OR (f.id > 0 AND fi.publico = 1)
                      OR (e.id > 0 AND ei.publico = 1)
                      GROUP BY a.tipo_destino, a.id_destino
                      ORDER BY a.data_acao DESC
                      LIMIT ".$feedLimit.";") or die(mysqli_error($GLOBALS['dblink']));
                    while ($r = mysqli_fetch_assoc($res2)) {
                      $linkData = linkData($r['userid'], $r['username'], $r['tipo'], $r['id_destino'],
                        ($r['d_nativo']=='' ?
                          ($r['d_romanizacao']=='' ? $r['d_palavra'] : $r['d_romanizacao'])
                          : getSpanPalavraNativa($r['d_nativo'],$r['eid'],$r['fonte'],$r['tamanho'])
                        ).getSpanPalavraNativa($r['frase'],$r['eid'],$r['id_fonte'],$r['tamanho'])
                        .$r['d_escrita'].$r['d_idioma'], $r['t'], $r['data_acao']);
                      echo '<div>
                        <div class="row">
                          <div class="col">
                            <div class="text-truncate">
                              <strong><a href="?page=profile&user='.$linkData['uname'].'">'.$linkData['uname'].'</a></strong> '.$linkData['text'].' <strong>'.$linkData['ltitle'].'</strong>.
                            </div>
                          </div>
                        </div>
                      </div>';
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

<?php if ($_SESSION['KondisonairUzatorNivle'] == 100): ?>
<script>
function acessarEdicaoIdiomaSistema(id){
    if(confirm("<?=_t('Deseja realmente ser colaborador deste idioma?')?>")) {
        $.get("api.php?action=getAcessoColaborador&iid="+id, function(data){
            if($.trim(data)=='ok') window.location.replace("?page=editlanguage&iid="+id);
            else alert(data);
        });
    }
}
</script>
<?php endif; ?>

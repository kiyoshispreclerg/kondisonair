
        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
					        <?=_t('Início')?>
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
                  <div class="card-body">

                    <div class="mb-1">
                      <span style="font-size:x-large"><?=_t('Olá!')?> </span>
                    </div>
                    <?php 
                      $las = mysqli_query($GLOBALS['dblink'],
                        "SELECT i.*, 
                        (SELECT palavra FROM palavrasNativas WHERE id_palavra = i.id_nome_nativo AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) LIMIT 1) as nativo,
                        (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as eid,
                        (SELECT id_fonte FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as fonte,
                        (SELECT tamanho FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as tamanho 
                        FROM idiomas i 
                        WHERE publico = 1 
                        AND id IN(SELECT id_idioma FROM studason_tests WHERE num_palavras > 0) 
                        ORDER BY RAND() LIMIT 1;" // AND id IN(SELECT id_idioma FROM studason_tests WHERE num_palavras > 0)
                      ) or die(mysqli_error($GLOBALS['dblink']));
                      $la = mysqli_fetch_assoc($las);
                      $eid = $la['eid'];
                      if (! $la['id'] > 0){
                        echo '<div class=" ">'._t('Nada compartilhado por aqui ainda.').'</div>';
                      }else{
                    ?>
                    <div class="mb-4"><?=_t('Algumas coisas compartilhadas por aqui')?></div>
                    <div class="row row-cards">
                      <div class="mb-3 col-4">
                      <div class="datagrid-title"><?=_t('Idioma aleatório')?></div>
                        <?php 
                          echo '<a href="?page=language&iid='.$la['id'].'">'.$la['nome_legivel'].'<br>'.getSpanPalavraNativa($la['nativo'],$la['eid'],$la['fonte'],$la['tamanho']).'</a>';
                        ?>
 
                        <div class="datagrid-title mt-3"><?=_t('Palavra aleatória')?></div>
                        <?php 
                        if ($la['id']){
                          $las = mysqli_query($GLOBALS['dblink'],
                            "SELECT *,
                            (SELECT id_fonte FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) as fonte,
                            (SELECT tamanho FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) as tamanho ,
                            (SELECT palavra FROM palavrasNativas WHERE id_palavra = p.id AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) LIMIT 1) as nativo 
                            FROM palavras p WHERE id_idioma = ".$la['id']." AND publico = 1 ORDER BY RAND() LIMIT 1;"
                          ) or die(mysqli_error($GLOBALS['dblink']));
                          while($la = mysqli_fetch_assoc($las)){
                            if($la['nativo']!=''){
                              echo '<a href="?page=word&pid='.$la['id'].'">'.getSpanPalavraNativa($la['nativo'],$eid,$la['fonte'],$la['tamanho']).' '.$la['pronuncia'].'<br>'.$la['significado'].'</a>';
                              break;
                            }else if($la['romanizacao']!=''){
                              echo '<a href="?page=word&pid='.$la['id'].'">'.$la['romanizacao'].'  '.$la['pronuncia'].'<br>'.$la['significado'].'</a>';
                              break;
                            }else if($la['pronuncia']!=''){
                              echo '<a href="?page=word&pid='.$la['id'].'">'.$la['pronuncia'].'<br>'.$la['significado'].'</a>';
                              break;
                            }
                          };
                        }
                          
                        ?>

                      </div>
                      <div class="mb-3 col-4">
                        <div class="datagrid-title"><?=_t('Frase aleatória')?></div>

                        <?php 
                        if($la['id']){
                          $las = mysqli_query($GLOBALS['dblink'],
                            "SELECT *, 
                            (SELECT id_fonte FROM escritas WHERE id_idioma = t.id_idioma AND padrao = 1 LIMIT 1) as fonte,
                            (SELECT tamanho FROM escritas WHERE id_idioma = t.id_idioma AND padrao = 1 LIMIT 1) as tamanho,
                            (SELECT id FROM escritas WHERE id_idioma = t.id_idioma AND padrao = 1 LIMIT 1) as eid  
                            FROM frases t ORDER BY RAND() LIMIT 1;" // AND id_idioma = ".$la['id_idioma']." 
                          ) or die(mysqli_error($GLOBALS['dblink']));
                          $la = mysqli_fetch_assoc($las);
                          echo '<a href="?page=phrase&id='.$la['id'].'">'.getSpanPalavraNativa($la['frase'],$la['eid'],$la['fonte'],$la['tamanho']).'</a>';
                      }
                        ?>

                      </div>
                      <div class="mb-3 col-4">
                        <div class="datagrid-title"><?=_t('Texto aleatório')?></div>

                        <?php 
                        if($la['id']){
                          $las = mysqli_query($GLOBALS['dblink'],
                            "SELECT *, 
                            (SELECT id_fonte FROM escritas WHERE id_idioma = t.id_idioma AND padrao = 1 LIMIT 1) as fonte,
                            (SELECT tamanho FROM escritas WHERE id_idioma = t.id_idioma AND padrao = 1 LIMIT 1) as tamanho,
                            (SELECT id FROM escritas WHERE id_idioma = t.id_idioma AND padrao = 1 LIMIT 1) as eid  FROM studason_tests t
                             WHERE num_palavras > 0 ORDER BY RAND() LIMIT 1;" // AND id_idioma = ".$la['id_idioma']." 
                          ) or die(mysqli_error($GLOBALS['dblink']));
                          $la = mysqli_fetch_assoc($las);
                          echo '<a href="?page=text&id='.$la['id'].'">'.$la['titulo'].'<br>'.getSpanPalavraNativa(mb_substr($la['texto'],0,64),$la['eid'],$la['fonte'],$la['tamanho']).'</a> ...';
                      }
                        ?>

                      </div>
                    </div>
                    <?php } ?>

                  </div>
                </div>

                <?php if($_SESSION['KondisonairUzatorIDX']>0){ ?>
                <div class="card mt-3">
                  <div class="card-header">
                    <h3 class="card-title"><?=_t('Meus idiomas')?></h3>
                    <div class="card-actions">
                      <a href="?page=mylanguages" class="btn btn-primary"><?=_t('Todos')?>
                      </a>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="row row-cards">

                      <?php
                        $listaTodas = ''; $totalIdiomas = 0;
                        $listaOutras = ''; $totalOutras = 0;
                        $listaFamilias = array(); $listaTotais = array();

                        $res = mysqli_query($GLOBALS['dblink'],"SELECT i.* ,
                          (SELECT f.arquivo as fonte FROM escritas e 
                            LEFT JOIN fontes f ON f.id = e.id_fonte
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as fonte,
                          (SELECT e.id_fonte FROM escritas e 
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as id_fonte,
                          (SELECT e.tamanho FROM escritas e 
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as tamanho,
                          (SELECT e.id FROM escritas e 
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as eid,
                          (SELECT palavra from palavrasNativas where id_palavra = i.id_nome_nativo AND id_escrita = (SELECT id FROM escritas e 
                                  WHERE e.id_idioma = i.id and e.padrao = 1) limit 1) as nativo
                          FROM idiomas i WHERE i.id_usuario = '".$_SESSION['KondisonairUzatorIDX']."' OR i.id IN(
                            SELECT id_idioma FROM collabs WHERE id_usuario = '".$_SESSION['KondisonairUzatorIDX']."')
                          ORDER BY i.data_modificacao DESC LIMIT 4;") or die(mysqli_error($GLOBALS['dblink']));
                        
                        // organizar primeiro, pra ter btns das familias, btn outras, btn todas, e dentro dos btn os links pra diommdason
                        if (mysqli_num_rows($res)==0) { echo '<div class="col-md-6 col-lg-3">'._t('Nada aqui.').'</div>'; }else{
                          while($r = mysqli_fetch_assoc($res)) { 

                            $nat=''; $icon = 'eye'; $title = "Pública"; $div = ''; $diva = '';
                            if($r['publico']!=1) { $icon = 'eye-slash'; $title = "Privada"; }
                            if($r['nativo']!='') $nat = getSpanPalavraNativa($r['nativo'],$r['eid'],$r['id_fonte'],$r['tamanho'])."<br>";
                            
                            echo '<div class="col-md-6 col-lg-3">
                                <div class="card">
                                  <div class="card-body">
                                    <a href="?page=editlanguage&iid='.$r['id'].'"><h3 class="card-title">'.$nat.$r['nome_legivel'].'</h3></a>
                                  </div>
                                </div>
                              </div>'; 
                          };
                        }
                      ?>


                    </div>
                  </div>
                </div>
                <?php } ?>
                <?php if($_SESSION['KondisonairUzatorNivle']==100){ ?>
                <div class="card mt-3">
                  <div class="card-header">
                    <h3 class="card-title"><?=_t('Idiomas de sistema')?></h3>
                    <div class="card-actions">
                      <a href="?page=settings" class="btn btn-primary"><?=_t('Configurações')?>
                      </a>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="">

                      <?php
                        $listaTodas = ''; $totalIdiomas = 0;
                        $listaOutras = ''; $totalOutras = 0;
                        $listaFamilias = array(); $listaTotais = array();

                        $res2 = mysqli_query($GLOBALS['dblink'],"SELECT i.* ,

                          (SELECT f.arquivo as fonte FROM escritas e 
                            LEFT JOIN fontes f ON f.id = e.id_fonte
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as fonte,
                          (SELECT e.id_fonte FROM escritas e 
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as id_fonte,
                          (SELECT e.tamanho FROM escritas e 
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as tamanho,
                          (SELECT e.id FROM escritas e 
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as eid,
                          (SELECT n.palavra from palavrasNativas n where n.id_palavra = i.id_nome_nativo AND n.id_escrita = (SELECT id FROM escritas e 
                                  WHERE e.id_idioma = i.id and e.padrao = 1) limit 1) as nativo
                          FROM idiomas i WHERE i.id < 10000 
                          ORDER BY i.data_modificacao DESC;") or die(mysqli_error($GLOBALS['dblink']));

                        while($r2 = mysqli_fetch_assoc($res2)) { 
                          if($r2['nativo']!='') $nat2 = getSpanPalavraNativa($r2['nativo'],$r2['eid'],$r2['id_fonte'],$r2['tamanho'])."<br>";
                          echo '<a class="btn btn-primary" onclick="acessarEdicaoIdiomaSistema(\''.$r2['id'].'\')">'.$nat2.$r2['nome_legivel'].'</a> '; 
                        };
                      ?>


                    </div>
                  </div>
                </div>
                <?php }; ?>

              </div>

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
                                DATE_FORMAT( a.data_acao,'%d/%m/%Y %h:%i:%s') as data_acao,
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
                              LIMIT ".$feedLimit.";") or die(mysqli_error($GLOBALS['dblink'])); //WHERE destinos in id usuarios q segue

                            while($r = mysqli_fetch_assoc($res2)) { 

                              $linkData = linkData( $r['userid'], $r['username'], $r['tipo'], $r['id_destino'], 
                                ( $r['d_nativo']=='' ? 
                                ($r['d_romanizacao']==''? $r['d_palavra'] : $r['d_romanizacao'] ) 
                                : 
                                getSpanPalavraNativa($r['d_nativo'],$r['eid'],$r['fonte'],$r['tamanho']) 
                                ).getSpanPalavraNativa($r['frase'],$r['eid'],$r['id_fonte'],$r['tamanho']) 
                                .$r['d_escrita'].$r['d_idioma'], $r['t'], $r['data_acao'] );

                              echo '<div>
                                <div class="row">
                                  <div class="col">
                                    <div class="text-truncate">
                                      <strong><a href="?page=profile&user='.$linkData['uname'].'">'.$linkData['uname'].'</a></strong> '.$linkData['text'].' <strong>'.$linkData['ltitle'].'</strong>.
                                    </div>
                                  </div>
                                </div>
                              </div>';
                            };
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
<?php if($_SESSION['KondisonairUzatorNivle']==100){ ?>
<script>
function acessarEdicaoIdiomaSistema(id){
    if(confirm("<?=_t('Deseja realmente ser colaborador deste idioma?')?>")) {
        $.get("api.php?action=getAcessoColaborador&iid="+id, function (data){
            if($.trim(data)=='ok') window.location.replace("?page=editlanguage&iid="+id);
            else alert(data);
        });
    }
}
</script>
<?php } ?>
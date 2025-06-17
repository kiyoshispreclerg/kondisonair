
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
                      <span style="font-size:x-large"><?=_t('Bem vindo!')?> </span> 
                      <span style="font-size:large"> <?=_t('Organize suas conlangs')?></span>
                    </div>
                    <div class="mb-3"><?=_t('Algumas coisas compartilhadas por aqui')?>
                      
                    </div>
                    <div class="row row-cards">
                      <div class="mb-3 col-4">
                      <div class="datagrid-title"><?=_t('Idioma aleatório')?></div>
                        <?php 
                          $las = mysqli_query($GLOBALS['dblink'],
                            "SELECT i.*, 
                            (SELECT palavra FROM palavrasNativas WHERE id_palavra = i.id_nome_nativo AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) LIMIT 1) as nativo,
                            (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as eid,
                            (SELECT id_fonte FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as fonte,
                            (SELECT tamanho FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as tamanho 
                            FROM idiomas i 
                            WHERE publico = 1  ORDER BY RAND() LIMIT 1;" // AND id IN(SELECT id_idioma FROM studason_tests WHERE num_palavras > 0)
                          ) or die(mysqli_error($GLOBALS['dblink']));
                          $la = mysqli_fetch_assoc($las);
                          $eid = $la['eid'];
                          echo '<a href="?page=language&iid='.$la['id'].'">'.$la['nome_legivel'].'<br>'.getSpanPalavraNativa($la['nativo'],$la['eid'],$la['fonte'],$la['tamanho']).'</a>';
                        ?>

                      </div>
                      <div class="mb-3 col-4">
                        <div class="datagrid-title"><?=_t('Palavra aleatória')?></div>
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
                              echo '<a href="#">'.getSpanPalavraNativa($la['nativo'],$eid,$la['fonte'],$la['tamanho']).' '.$la['pronuncia'].'<br>'.$la['significado'].'</a>';
                              break;
                            }else if($la['romanizacao']!=''){
                              echo '<a href="#">'.$la['romanizacao'].'  '.$la['pronuncia'].'<br>'.$la['significado'].'</a>';
                              break;
                            }else if($la['pronuncia']!=''){
                              echo '<a href="#">'.$la['pronuncia'].'<br>'.$la['significado'].'</a>';
                              break;
                            }
                          };
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
                          echo '<a href="?page=text&id='.$la['id'].'">'.$la['titulo'].'<br>'.getSpanPalavraNativa(mb_substr($la['texto'],0,60),$la['eid'],$la['fonte'],$la['tamanho']).'...</a>';
                      }
                        ?>

                      </div>
                    </div>

                  </div>
                </div>

                <?php if($_SESSION['KondisonairUzatorIDX']>0){ ?>
                <div class="card mt-3">
                  <div class="card-header">
                    <h3 class="card-title"><?=_t('Meus idiomas')?></h3>
                  </div>
                  <div class="card-body">
                    <div class="row row-cards">

                      <?php
                        $listaTodas = ''; $totalIdiomas = 0;
                        $listaOutras = ''; $totalOutras = 0;
                        $listaFamilias = array(); $listaTotais = array();

                        $res = mysqli_query($GLOBALS['dblink'],"SELECT i.* ,
                          (SELECT COUNT(*) FROM palavras p WHERE
                          p.id_idioma = i.id AND p.id_forma_dicionario = 0) as num_palavras,

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

                          (SELECT nome from grupos_idiomas where id = i.id_familia limit 1) as grupo,
                          (SELECT palavra from palavrasNativas where id_palavra = i.id_nome_nativo AND id_escrita = (SELECT id FROM escritas e 
                                  WHERE e.id_idioma = i.id and e.padrao = 1) limit 1) as nativo
                          FROM idiomas i WHERE i.id_usuario = '".$_SESSION['KondisonairUzatorIDX']."' 
                          ORDER BY i.data_modificacao DESC LIMIT 6;") or die(mysqli_error($GLOBALS['dblink']));
                        
                        // organizar primeiro, pra ter btns das familias, btn outras, btn todas, e dentro dos btn os links pra diommdason

                        while($r = mysqli_fetch_assoc($res)) { 

                          $nat=''; $icon = 'eye'; $title = "Pública"; $div = ''; $diva = '';
                          if($r['publico']!=1) { $icon = 'eye-slash'; $title = "Privada"; }
                          if($r['nativo']!='') $nat = getSpanPalavraNativa($r['nativo'],$r['eid'],$r['id_fonte'],$r['tamanho'])."<br>";
                          
                          echo '<div class="col-md-6 col-lg-4">
                              <div class="card">
                                <div class="card-body">
                                  <a href="?page=editlanguage&iid='.$r['id'].'"><h3 class="card-title">'.$nat.$r['nome_legivel'].'</h3></a>
                                  <p class="text-secondary">'.$r['num_palavras'].' '._t('palavras').' </p>
                                </div>
                              </div>
                            </div>'; 
                        };
                      ?>


                    </div>
                  </div>
                </div>
                <?php } ?>

              </div>
                
              <?php if($_SESSION['KondisonairUzatorIDX']>0){ ?>

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



                              $sql = "SELECT u.username, u.id as userid, a.tipo_destino as tipo, a.tipo as t, a.id_destino, 
                                DATE_FORMAT( a.data_acao,'%d/%m/%Y %h:%i:%s') as data_acao,
                                i.nome_legivel as d_idioma,
                                p.pronuncia as d_palavra, pn.palavra as d_nativo, p.romanizacao as d_romanizacao,
                                e.nome as d_escrita, pn.id_escrita as eid, en.id_fonte, en.tamanho
                                FROM asons a
                                LEFT JOIN idiomas i ON (a.tipo_destino = 'diom' AND i.id = a.id_destino)
                                LEFT JOIN escritas e ON (a.tipo_destino = 'skreveson' AND e.id = a.id_destino)
                                LEFT JOIN palavras p ON (a.tipo_destino = 'palavr' AND p.id = a.id_destino)
                                LEFT JOIN palavrasNativas pn ON (p.id = pn.id_palavra AND pn.id_escrita = (SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1))
                                LEFT JOIN escritas en ON (en.id_idioma = p.id_idioma AND en.padrao = 1)
                                LEFT JOIN usuarios u ON u.id = a.id_usuario 
                                WHERE a.id_usuario IN (SELECT ss.id_seguido FROM sosail_sgisons ss WHERE ss.id_usuario = ".$_SESSION['KondisonairUzatorIDX'].")
                                GROUP BY a.tipo_destino, a.id_destino
                                ORDER BY a.data_acao DESC
                                LIMIT ".$feedLimit.";";
                            $res2 = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink'])); //WHERE destinos in id usuarios q segue
                            if (mysqli_num_rows($res2)>0){
                            while($r = mysqli_fetch_assoc($res2)) { 

                              $linkData = linkData( $r['userid'], $r['username'], $r['tipo'], $r['id_destino'], 
                                ( $r['d_nativo']=='' ? ($r['d_romanizacao']==''? $r['d_palavra'] : $r['d_romanizacao'] ) : getSpanPalavraNativa($r['d_nativo'],$r['eid'],$r['id_fonte'],$r['tamanho']) )
                                .$r['d_escrita'].$r['d_idioma'], $r['t'], $r['data_acao'] );
                              if(!empty($linkData))
                              echo '<div>
                                <div class="row">
                                  <div class="col">
                                    <div class="text-truncate">
                                      <strong><a href="?page=profile&user='.$linkData['uname'].'">'.$linkData['uname'].'</a></strong> '.$linkData['text'].' <strong>'.$linkData['ltitle'].'</strong>
                                    </div>
                                  </div>
                                </div>
                              </div>';
                            };
                          }else{
                            echo _t('Nenhuma atividade para mostrar.');
                          }
                          ?> 

                        </div>
                      </div>
                    </div>
                  </div>

                  <?php if($_SESSION['KondisonairUzatorNivle']==100){ 
                        $resop = mysqli_query($GLOBALS['dblink'],"SELECT * FROM opcoes_sistema;") or die(mysqli_error($GLOBALS['dblink']));
                        while($ro = mysqli_fetch_assoc($resop)) { 
                          $op[$ro['opcao']]  = $ro['valor'];
                        };
                    ?>
                  <div class="col-12">
                    <div class="card">
                    <div class="card-header">
                      <h3 class="card-title"><?=_t('Administração')?></h3>
                    </div>
                      <div class="card-body row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Idiomas por usuário')?></label>
                            <input type="number" class="form-control" id="limite_langs" value="<?=$op['limite_langs']?>" onchange="gravarOpsons('limite_langs')">
                        </div>  
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Palavras base por idioma')?></label>
                            <input type="number" class="form-control" id="palavras_base_lang" value="<?=$op['palavras_base_lang']?>" onchange="gravarOpsons('palavras_base_lang')">       
                        </div>  
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Palavras total por idioma')?></label>
                            <input type="number" class="form-control" id="palavras_lang" value="<?=$op['palavras_lang']?>" onchange="gravarOpsons('palavras_lang')">       
                        </div>  
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Fontes por usuario')?></label>
                            <input type="number" class="form-control" id="fonts_usuario" value="<?=$op['fonts_usuario']?>" onchange="gravarOpsons('fonts_usuario')">  
                        </div> 
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Classes por idioma')?></label>
                            <input type="number" class="form-control" id="lim_lang_parts" value="<?=$op['lim_lang_parts']?>" onchange="gravarOpsons('lim_lang_parts')">  
                        </div> 
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Concordâncias por idioma')?></label>
                            <input type="number" class="form-control" id="lim_conc_lang" value="<?=$op['lim_conc_lang']?>" onchange="gravarOpsons('lim_conc_lang')">  
                        </div> 
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Itens por concordância')?></label>
                            <input type="number" class="form-control" id="lim_itens_conc" value="<?=$op['lim_itens_conc']?>" onchange="gravarOpsons('lim_itens_conc')">  
                        </div> 
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Sons por idioma')?></label>
                            <input type="number" class="form-control" id="lim_sons_lang" value="<?=$op['lim_sons_lang']?>" onchange="gravarOpsons('lim_sons_lang')">  
                        </div> 
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Sistemas de escrita por idioma')?></label>
                            <input type="number" class="form-control" id="limite_escritas_l" value="<?=$op['limite_escritas_l']?>" onchange="gravarOpsons('limite_escritas_l')">  
                        </div> 

                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Listas de alteração sonora por idioma')?></label>
                            <input type="number" class="form-control" id="limite_scs_lang" value="<?=$op['limite_scs_lang']?>" onchange="gravarOpsons('limite_scs_lang')">  
                        </div> 
                        <div class="mb-3 col-md-6">
                            <label class="form-label"><?=_t('Listas de alteração sonora por usuário')?></label>
                            <input type="number" class="form-control" id="limite_scs_user" value="<?=$op['limite_scs_user']?>" onchange="gravarOpsons('limite_scs_user')">  
                        </div> 
                        
                        <div class="mb-3 col-md-6">
                              <label class="form-label"><?=_t('Aberto para novos usuários?')?></label>
                              <select id="inscr_aberta" class="chosen-select form-control" onchange="gravarOpsons('inscr_aberta')">
                                  <option value="0" <?php if ($op['inscr_aberta']==0) echo 'selected'; ?> >Não</option>
                                  <option value="1" <?php if ($op['inscr_aberta']==1) echo 'selected'; ?> >Sim</option>
                              </select>
                        </div>
                        <div class="mb-3 col-md-6">
                              <label class="form-label"><?=_t('Idioma padrão do sistema')?></label>
                              <?php
                                echo gerarSelectIdiomas('def_lang', $op['def_lang'], 'gravarOpsons(\'def_lang\')', false);
                              ?>
                        </div>
                      </div>
                      <div class="card-body">

                        <a href="index.php?page=ipa" class="btn btn-primary"><?=_t('IPA')?></a>
                        <a href="index.php?page=glosses" class="btn btn-primary"><?=_t('Glosses')?></a>
                        <a href="index.php?page=referents" class="btn btn-primary"><?=_t('Referentes')?></a>
                        <a href="index.php?page=users" class="btn btn-primary"><?=_t('Usuários')?></a>
                      </div>
                    </div>
                  </div>
                  <script>

                  function gravarOpsons(param){
                      $.get("api.php?action=ajaxGravarOption&param="+param+"&value="+$('#'+param).val(), 
                          function (data){
                          if ($.trim(data) == 'ok'){
                              //alert('ok');//window.location = "dash.php?ason=opsons";
                          }else{
                              alert(data);
                          }
                      });
                  };
                  </script>
                  <?php } ?>


                </div>
              </div>

              <?php }else{ ?>

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
                                p.pronuncia as d_palavra, pn.palavra as d_nativo, p.romanizacao as d_romanizacao,
                                e.nome as d_escrita, pn.id_escrita as eid
                              FROM asons a
                              LEFT JOIN idiomas i ON (a.tipo_destino = 'diom' AND i.id = a.id_destino)
                              LEFT JOIN escritas e ON (a.tipo_destino = 'skreveson' AND e.id = a.id_destino)
                              LEFT JOIN palavras p ON (a.tipo_destino = 'palavr' AND p.id = a.id_destino)
                              LEFT JOIN palavrasNativas pn ON (p.id = pn.id_palavra AND pn.id_escrita = (SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1))
                              LEFT JOIN usuarios u ON u.id = a.id_usuario 
                              GROUP BY a.tipo_destino, a.id_destino
                              ORDER BY a.data_acao DESC
                              LIMIT ".$feedLimit.";") or die(mysqli_error($GLOBALS['dblink'])); //WHERE destinos in id usuarios q segue

                            while($r = mysqli_fetch_assoc($res2)) { 

                              $linkData = linkData( $r['userid'], $r['username'], $r['tipo'], $r['id_destino'], 
                                ( $r['d_nativo']=='' ? ($r['d_romanizacao']==''? $r['d_palavra'] : $r['d_romanizacao'] ) : getSpanPalavraNativa($r['d_nativo'],$r['eid'],$r['fonte'],$r['tamanho'])  )
                                .$r['d_escrita'].$r['d_idioma'], $r['t'], $r['data_acao'] );

                              echo '<div>
                                <div class="row">
                                  <div class="col-auto">
                                    <span class="avatar" style="background-image: url(./static/avatars/'.$linkData['uid'].'.jpg)"></span>
                                  </div>
                                  <div class="col">
                                    <div class="text-truncate">
                                      <strong><a href="?page=profile&user='.$linkData['uname'].'">'.$linkData['uname'].'</a></strong> '.$linkData['text'].' <strong>'.$linkData['ltitle'].'</strong>.
                                    </div>
                                    <div class="text-secondary">'.$linkData['date'].'</div>
                                  </div>
                                  <!--div class="col-auto align-self-center">
                                    <div class="badge bg-primary"></div>
                                  </div-->
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

              <?php }; ?>

            </div>
          </div>
        </div>
		
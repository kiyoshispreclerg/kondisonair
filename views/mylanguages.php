
        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <!-- Page pre-title -->
                <h2 class="page-title">
					          <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Minhas conlangs')?></a></li>
                    </ol>
                </h2>
              </div>
              <!-- Page title actions -->
              <!--div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                  <span class="d-none d-sm-inline">
                    <a href="#" class="btn">
                      Novo idioma
                    </a>
                  </span>
                  <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-report">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                    Novo idioma
                  </a>
                  <a href="#" class="btn btn-primary d-sm-none btn-icon" data-bs-toggle="modal" data-bs-target="#modal-report" aria-label="Create new report">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                  </a>
                </div>
              </div-->
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deck row-cards">

              <div class="col-12">
                <div class="card">
                  <div class="card-header">
                    <h3 class="card-title"><?=_t('Minhas conlangs')?></h3>
                    <div class="card-actions">
                      <a href="?page=editlanguage&iid=0" class="btn btn-primary">
                        <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                        <?=_t('Nova')?>
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
                          (SELECT COUNT(*) FROM palavras p WHERE
                          p.id_idioma = i.id AND p.id_forma_dicionario = 0) as num_palavras,

                          (SELECT f.arquivo as fonte FROM escritas e 
                            LEFT JOIN fontes f ON f.id = e.id_fonte
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as fonte,
                          (SELECT e.tamanho FROM escritas e 
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as ftamanho,
                          (SELECT e.id_fonte FROM escritas e 
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as id_fonte,
                          (SELECT e.id FROM escritas e 
                            WHERE e.id_idioma = i.id and e.padrao = 1
                            ) as eid,
                          (SELECT username FROM usuarios where id = i.id_usuario AND id <> ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as criador,

                          (SELECT nome from grupos_idiomas where id = i.id_familia limit 1) as grupo,
                          (SELECT palavra from palavrasNativas where id_palavra = i.id_nome_nativo AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1) limit 1) as nativo
                          FROM idiomas i WHERE i.id_usuario = '".$_SESSION['KondisonairUzatorIDX']."' OR i.id IN(
                            SELECT id_idioma FROM collabs WHERE id_usuario = '".$_SESSION['KondisonairUzatorIDX']."')
                          ORDER BY i.data_modificacao DESC;") or die(mysqli_error($GLOBALS['dblink']));
                        
                        // organizar primeiro, pra ter btns das familias, btn outras, btn todas, e dentro dos btn os links pra diommdason

                        while($r = mysqli_fetch_assoc($res)) { 

                          $nat=''; $icon = 'eye'; $title = "Pública"; $div = ''; $diva = '';
                          if($r['publico']!=1) { $icon = 'eye-slash'; $title = "Privada"; $linkPublico = '';}else{
                            $linkPublico = '<div class="col-auto">
                                  <a href="?page=language&iid='.$r['id'].'" ><h5 class="">'._t('Página pública').'</h5></a>'.(
                                    $r['criador']!=''?'<a href="?page=profile&user='.$r['criador'].'" ><label class="">@'.$r['criador'].'</label></a>':''
                                ).'</div>';
                          }
                          if($r['nativo']!='') $nat = getSpanPalavraNativa($r['nativo'],$r['eid'],$r['id_fonte'],$r['tamanho'])."<br>";
                          
                          echo '<div class="col-md-6 col-lg-3">
                              <div class="card">
                                <!--div class="card-status-top bg-red"></div-->
                                <div class="card-body row">
                                  <div class="col">
                                    <a href="?page=editlanguage&iid='.$r['id'].'"><h3 class="card-title">'.$nat.$r['nome_legivel'].'</h3></a>
                                    <p class="text-secondary">'./*getStatus($r['status']).' - '.*/$r['num_palavras'].' '._t('palavras').'</p>
                                  </div>'.$linkPublico.'
                                </div>

                                <!--div class="progress progress-sm card-progress">
                                  <div class="progress-bar" style="width: 38%" role="progressbar" aria-valuenow="38" aria-valuemin="0" aria-valuemax="100" aria-label="38% Complete">
                                    <span class="visually-hidden">38% Complete</span>
                                  </div>
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
        </div>
		
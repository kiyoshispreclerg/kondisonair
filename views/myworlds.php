
        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <!-- Page pre-title -->
                <h2 class="page-title">
					          <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Minhas realidades')?></a></li>
                    </ol>
                </h2>
              </div>
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
                    <h3 class="card-title"><?=_t('Minhas realidades')?></h3>
                    <div class="card-actions">
                      <a href="?page=editworld&id=0" class="btn btn-primary">
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
                          (SELECT COUNT(*) FROM entidades p WHERE
                          p.id_realidade = i.id) as num_entidades,
                          (SELECT username FROM usuarios where id = i.id_usuario AND id <> ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as criador 
                          FROM realidades i WHERE i.id_usuario = '".$_SESSION['KondisonairUzatorIDX']."' OR i.id IN(
                            SELECT id_realidade FROM collabs_realidades WHERE id_usuario = '".$_SESSION['KondisonairUzatorIDX']."')
                          ORDER BY i.data_modificacao DESC;") or die(mysqli_error($GLOBALS['dblink']));
                        
                        // organizar primeiro, pra ter btns das familias, btn outras, btn todas, e dentro dos btn os links pra diommdason

                        while($r = mysqli_fetch_assoc($res)) { 
                            
                          $icon = 'eye'; $title = "Pública"; $div = ''; $diva = '';
                          if($r['publico']!=1) { $icon = 'eye-slash'; $title = "Privada"; $linkPublico = '';}else{
                            $linkPublico = '<div class="col-auto">
                                  <a href="?page=world&rid='.$r['id'].'" ><h5 class="">'._t('Página pública').'</h5></a>'.(
                                    $r['criador']!=''?'<a href="?page=profile&user='.$r['criador'].'" ><label class="">@'.$r['criador'].'</label></a>':''
                                ).'</div>';
                          }
                          
                          echo '<div class="col-md-6 col-lg-3">
                              <div class="card">
                                <!--div class="card-status-top bg-red"></div-->
                                <div class="card-body row">
                                  <div class="col">
                                    <a href="?page=editworld&rid='.$r['id'].'"><h3 class="card-title">'.$r['titulo'].'</h3></a>
                                    <p class="text-secondary">'.$r['num_entidades'].' entidades</p>
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
		
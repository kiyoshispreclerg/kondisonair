

        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <!-- Page pre-title -->
                <div class="page-pretitle">
					<?=_t('Bem-vindo')?>
                </div>
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
            <div class="row row-deck row-cards">

              <div class="col-lg-6">
				
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
                                ( $r['d_nativo']=='' ? ($r['d_romanizacao']==''? $r['d_palavra'] : $r['d_romanizacao'] ) : '<span class="custom-font-'.$r['eid'].'">'.$r['d_nativo'].'</span>' )
                                .$r['d_escrita'].$r['d_idioma'], $r['t'], $r['data_acao'] );

                              echo '<div>
                                <div class="row">
                                  <div class="col-auto">
                                    <span class="avatar" style="background-image: url(./static/avatars/'.$linkData['uid'].'.jpg)">k</span>
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

              <div class="col-lg-6">
				
                <div class="row row-cards">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-body">
                        random word
                      </div>
                    </div>
                    
                    <div class="card">
                      <div class="card-body">
                        random phrase
                      </div>
                    </div>

                  </div>
                </div>

            </div>
          </div>
        </div>
	
</div>


<script>
    $(document).ready(function(){
    	$(".chosen-select").chosen();
		//$("#tabelaIdiomas").DataTable();
		//$("#tabelaConlangs").DataTable();
    });
</script>
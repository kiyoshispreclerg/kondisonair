
<!-- PANEL START -->
<?php 
    $iid = -1;
    if ($_GET['id'] > 0) {

      $query = 'SELECT a.*, i.nome_legivel FROM artygs a LEFT JOIN idiomas i ON i.id = a.id_idioma WHERE a.id = '.$_GET['id'].';';
      $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
      $r = mysqli_fetch_assoc($result);

      $aid = $r['id'];
      $iid = $r['id_idioma'];
      $idioma = $r['nome_legivel'];
      
    } else echo '<script>window.location = "index.php";</script>';
   
?>
<input type="hidden" id="aid" value="<?=$aid?>" />
        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="index.php?page=language&iid=<?=$iid?>"><?=$idioma?></a></li>
                      <li class="breadcrumb-item"><a href="index.php?page=myarticles&iid=<?=$iid?>"><?=_t('Artigos')?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Artigo')?></a></li>
                    </ol>
                </h2>
              </div>
              <div class="col-auto ms-auto" id="joesDiv"></div>
              <?php if ( $_SESSION['KondisonairUzatorIDX'] > 0 && $r['id_usuario'] == $_SESSION['KondisonairUzatorIDX']) { ?>
              <div class="col-auto ms-auto">
                <a href="?page=editarticle&id=<?=$aid?>&iid=<?=$iid?>" class="btn btn-primary" id="btnSalvar"><?=_t('Editar')?></a>
              </div>
              <?php }; ?>
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deckx row-cards">
                
            <div class="col-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=$r['nome']?></h3>
                    </div>
                    <div class="card-body"><?=$r['texto']?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body">
                      <!--div class="mb-3">info do artigo: nome, autor, likes/deslikes, última atualização, relação link, idioma</div-->
                    <?php 
                    /*
                    $query = 'SELECT a.* FROM artygs a 
                        WHERE a.id_pap = '.$aid.';';
                    $result2 = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));

                    if(mysqli_num_rows($result2)>0){
                      echo '<h4>Artigos relacionados</h4><div class="list-group-item">';
                      while($r2 = mysqli_fetch_assoc($result2)){
                          echo '<a href="?page=article&id='.$r2['id'].'">'.$r2['nome'].'</a>';
                          
                      }; 
                      echo '</div>';
                    };
                    */
                    ?>


                    <div class="mb-3">
                        <div class="form-label"><?=_t('Artigos')?></div>
                        <?php 
                        $sql = "
                            -- Artigos filhos (id_pap = $aid)
                            SELECT id, id_pap, nome, 'filho' AS tipo
                            FROM artygs
                            WHERE id_pap = $aid
                            UNION
                            -- Artigos irmãos (com o mesmo id_pap do artigo com id = $aid)
                            SELECT a.id, a.id_pap, a.nome, 'irmao' AS tipo
                            FROM artygs a
                            WHERE a.id_pap = (SELECT id_pap FROM artygs WHERE id = $aid) AND a.id != $aid
                            UNION
                            -- Artigos pais (onde id é o id_pap do artigo com id = $aid)
                            SELECT id, id_pap, nome, 'pai' AS tipo
                            FROM artygs
                            WHERE id = (SELECT id_pap FROM artygs WHERE id = $aid)
                        ";

                        $links = mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));

                        while ($l = mysqli_fetch_assoc($links)) {
                            switch ($l['tipo']) {
                                case 'pai':
                                    $indent = ''; // Sem recuo
                                    break;
                                case 'irmao':
                                    $indent = '&nbsp;&nbsp;&nbsp;'; // Recuo de 3 espaços
                                    break;
                                case 'filho':
                                    $indent = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'; // Recuo de 6 espaços
                                    break;
                                default:
                                    $indent = '';
                            }
                            echo $indent . '<a href="?page=article&id=' . $l['id'] . '">' . $l['nome'] . '</a><br>';
                        }
                        ?>
                    </div>


                    <div class="mb-3">
                        <div class="form-label"><?=_t('Ligações')?></div>
                        <?php 
                          $sql = "SELECT d.id_dest, d.tipo_dest,
                                  t.titulo as texto, f.frase, COALESCE(p.romanizacao, p.pronuncia) as palavra,
                                  (SELECT id_fonte FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as fonte,
                                  (SELECT tamanho FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as tamanho ,
                                  (SELECT id FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as eid ,
                                  (SELECT palavra FROM palavrasNativas WHERE id_palavra = p.id AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) LIMIT 1) as nativo
                                FROM artyg_dest d 
                                  LEFT JOIN studason_tests t ON t.id = d.id_dest
                                  LEFT JOIN frases f ON f.id = d.id_dest
                                  LEFT JOIN palavras p ON p.id = d.id_dest
                                  LEFT JOIN idiomas i ON p.id_idioma = i.id OR f.id_idioma = i.id
                                WHERE id_artyg = $aid GROUP BY d.id_dest;";
                          $links = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
                          while ($l = mysqli_fetch_assoc($links)){
                              $link = ''; $nome = '';
                              switch ($l['tipo_dest']){
                                case 'word':
                                  $link = 'word&pid=';
                                  $nome = _t('Palavra').': '.getSpanPalavraNativa($l['nativo']??$l['palavra'],$l['eid'],$l['fonte'],$l['tamanho']);
                                  break;
                                case 'frase':
                                  $link = 'phrase&id=';
                                  $nome = _t('Frase').': '.getSpanPalavraNativa($l['frase'],$l['eid'],$l['fonte'],$l['tamanho']);
                                  break;
                                case 'text':
                                  $link = 'text&id=';
                                  $nome = _t('Texto').': '.$l['texto'];
                                  break;
                                default:
                                  $link = '';
                                  $nome = '';
                              }
                              if ($nome!='') echo '<a href="?page='.$link.$l['id_dest'].'">'.$nome.'</a><br>';
                          }
                        ?>
                    </div>
                    
                  </div>
                </div>
            </div>

            </div>
          </div>
        </div>
      
        <script>
          btnJoes(0,'artyg','<?=$aid?>')
          </script>
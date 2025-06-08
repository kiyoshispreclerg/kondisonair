
				 <!-- PANEL START -->
<?php 
	if (isset($_GET['pid']) && $_GET['pid']>0) $pid = $_GET['pid'];
	else if (isset($_GET['v1'])&&$_GET['v1']>0) $pid = $_GET['v1'];
	else die();
   
	$palavra = array(); 
	$result = mysqli_query($GLOBALS['dblink'],"SELECT p.*, 
   			(SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = p.id AND id_escrita = (SELECT id FROM escritas WHERE padrao = 1 AND id_idioma = p.id_idioma) LIMIT 1) as nativo,
            (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1) as eid,
            (SELECT id_fonte FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1) as fonte,
            (SELECT tamanho FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1) as tamanho,
			(SELECT c.nome FROM classes c WHERE c.id = p.id_classe) as classe 
			FROM palavras p

			WHERE p.id = ".$pid.";") or die(mysqli_error($GLOBALS['dblink']));
	while($r = mysqli_fetch_assoc($result)) { 
		$palavra  = $r;
	};
    $id_idioma = $palavra['id_idioma'];
    $idescritapadrao = $palavra['eid'];
    $fonte = $palavra['fonte'];
    $tamanho = $palavra['tamanho'];

	if ($palavra['romanizacao']!='') $romanizacao = $palavra['romanizacao'];
	if ($palavra['classe']!='') $classe = $palavra['classe'];
	if ($palavra['genero']!='') $genero = $palavra['genero'];
	
   	$idioma = array();  
	$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, a.nome_legivel as ascendente  
		FROM idiomas i
		LEFT JOIN idiomas a ON a.id = i.id_ascendente 
		LEFT JOIN palavras p ON i.id_nome_nativo = p.id
		WHERE i.id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
	while($r = mysqli_fetch_assoc($result)) { 
		$idioma  = $r;
	};

	$glossdet = '';
	
    $qry = "SELECT i.* FROM itens_palavras ip
			LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
        WHERE ip.id_palavra = ".$pid." AND usar = 1;";
		
	$b = mysqli_query($GLOBALS['dblink'],$qry) or die(mysqli_error($GLOBALS['dblink']));
	while($bx = mysqli_fetch_assoc($b)){

		$glossList = '';
        $sql = "SELECT g.* FROM gloss_itens gi
          LEFT JOIN glosses g ON g.id = gi.id_gloss
          WHERE gi.id_item = ".$bx['id'].";";
        $resultGl = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
        while($rgl = mysqli_fetch_assoc($resultGl)){
          $glossList .= $rgl['gloss'].' ';
        };

		$glossdet .= $glossList.$bx['nome'].'<br>';
	}

	if ($glossdet != '') $glossdet = '<br>'.$glossdet;


	/*"SELECT i.*, g.gloss FROM itens_palavras ip
          LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
      		LEFT JOIN glosses g ON i.id_gloss = g.id 
          WHERE ip.id_palavra = ".$pid." AND usar = 1;"
		  */
?>
     
		   <div class="col-12">

				
                <?php if($palavra['nativo']!=''){ ?>
                <div class="mb-3">
                <div class="datagrid-title"><?=_t('Nativo')?></div>
                <?=getSpanPalavraNativa($palavra['nativo'],$idescritapadrao,$fonte,$tamanho)?>
				</div>
                <?php } ?>
                

                <?php if($romanizacao!=''){ ?>
                <div class="mb-3">
                <div class="datagrid-title"><?=_t('Romanização')?></div>
                <?=$romanizacao?>
				</div>
                <?php } ?>
                
                <?php if($palavra['pronuncia']!=''){ ?>
                <div class="mb-3">
                <div class="datagrid-title"><?=_t('Pronúncia')?></div>
                <?=$palavra['pronuncia']?>
				</div>
                <?php } ?>

                <?php if($palavra['significado']!=''){ ?>
                <div class="mb-3">
                <div class="datagrid-title"><?=_t('Significado')?></div>
                <h4><?=$palavra['significado']?></h4>
				</div>
                <?php } ?>

                <?php if($classe.$genero.$glossdet!=''){ ?>
                <div class="mb-3" >
                    <div class="datagrid-title"><?=_t('Morfologia')?></div>
                    <?=$classe.$genero.$glossdet?>
                </div>
                <?php } ?>

                <?php if($palavra['detalhes']!=''){ ?>
                <div class="mb-3" >
                    <div class="datagrid-title"><?=_t('Mais informações')?></div>
                    <?=$palavra['detalhes']?>
                </div>
                <?php } ?>

			</div>

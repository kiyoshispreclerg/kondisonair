
<!-- PANEL START -->
<?php 
$pid = $_GET['pid'];
$palavra = array();
$result = mysqli_query($GLOBALS['dblink'],"SELECT p.*, i.nome_legivel,
            (SELECT id_fonte FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) as fonte,
            (SELECT tamanho FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) as tamanho ,
            (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) as eid ,
            (SELECT nome FROM classes WHERE id = p.id_classe LIMIT 1) as nomeClasse ,
            (SELECT palavra FROM palavrasNativas WHERE id_palavra = p.id AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) LIMIT 1) as nativo 
            FROM palavras p
            LEFT JOIN idiomas i ON i.id = p.id_idioma
               WHERE p.id = '".$pid."';") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
$palavra  = $r;
};

if ( $palavra['pronuncia']=='') {
    echo '<script>window.location = "index.php";</script>';
    exit;
}

?>
<input type="hidden" id="codigo" value="<?=$pid?>" />



        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=language&iid=<?=$palavra['id_idioma']?>"><?=$palavra['nome_legivel']?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Palavra')?></a></li>
                    </ol>
                </h2>
              </div>
              <?php if ( $palavra['id_usuario'] == $_SESSION['KondisonairUzatorIDX']) { ?>
              <div class="col-auto ms-auto">
                <a href="?page=editword&pid=<?=$pid?>&iid=<?=$palavra['id_idioma']?>" class="btn btn-primary" id="btnSalvar"><?=_t('Editar')?></a>
              </div>
              <?php }; ?>
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deckx row-cards">



            <div class="col-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h3 class="form-label"><?php
                        $mainWord = strlen($palavra['nativo'])>0 ? getSpanPalavraNativa($palavra['nativo'],$palavra['eid'],$palavra['fonte'],$palavra['tamanho']) : $palavra['romanizacao'];
                        if ($mainWord) {
                            $pronuncia = '/'.$palavra['pronuncia'].'/';
                            $palavraEscrita = $palavra['nativo'] ?? $palavra['romanizacao'];
                        ?></h3>

                        <div style="white-space:preserve;
                            transform: scale(1.5);
                            transform-origin: top left;
                            width: 62%;
                            display: block; margin-bottom:3%" 

                            id="textoMarcado"><?php echo $mainWord; ?></div>
                        <?php }else{
                            $pronuncia = $palavra['pronuncia'];
                        } ?>
                        <?=$pronuncia?>

                        <ol class="breadcrumb breadcrumb-arrows mb-3">
                            <li class="breadcrumb-item"><?=$palavra['nomeClasse']?></li>
                        <?php
                        $b = mysqli_query($GLOBALS['dblink'],"SELECT g.nome, l.gloss FROM classesGeneros c
                            LEFT JOIN generos g ON c.id_genero = g.id
                            LEFT JOIN glosses l ON g.id_gloss = l.id
                                WHERE c.id_palavra = ".$pid."
                                UNION
                                SELECT i.nome, g.gloss FROM itens_palavras ip
                            LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
                            LEFT JOIN gloss_itens gi ON gi.id_item = i.id
                            LEFT JOIN glosses g ON gi.id_gloss = g.id
                            WHERE ip.id_palavra = ".$pid." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
                        if (mysqli_num_rows($result)>0){
                            while($bx = mysqli_fetch_assoc($b)){
                                echo '<li class="breadcrumb-item">'.$bx['nome'].'</li>'; //      <span title="'.$bx['nome'].'">'.$bx['gloss'].'</span>.';
                            }
                        }
                        ?>
                        </ol>
                        <h3><?=$palavra['significado']?></h3>
                        <div><?=$palavra['detalhes']?></div>

                        <div class="mt-3">
							<label class="form-label"><?=_t('Origens')?></label>
                            <div id="origensTexto" class="row"><label class="form-label"><?=_t('Nada aqui.')?></label></div>
						</div>
                    </div>
                </div>
            </div>

            <div class="col-4">

                <?php 
                if ($palavraEscrita) { 
                    $query = "SELECT t.*
                        FROM frases t
                        WHERE t.frase LIKE \"%$palavraEscrita%\" 
                        ORDER BY RAND()
                        LIMIT 5;";

                    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
                    
                    if (mysqli_num_rows($result)>0){
                ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Frases')?></h3>
                        <div class="card-actions">
                            <a href="?page=phrases&iid=<?=$palavra['id_idioma']?>&palavra=<?=$palavraEscrita?>" class="btn btn-primary"><?=_t('Ver mais')?></a>
                        </div>
                    </div>
                    <div class="list-group list-group-flush list-group-hoverable overflow-auto" style="max-height: 25rem">
                    <?php
                        while($r = mysqli_fetch_assoc($result)){
                            echo '<div class="list-group-item"><div class="row">
                                <div class="col-auto">
                                <a href="?page=phrase&id='.$r['id'].'">'.getSpanPalavraNativa($r['frase'],$palavra['eid'],$palavra['fonte'],$palavra['tamanho']).'</a>
                                </div>
                            </div></div>';
                        };
                    ?>
                    </div>
                </div>
                <?php }}; ?>


                <?php 
                if ($palavraEscrita) { 
                    $query = "SELECT t.*,
                        (SELECT COUNT(*) FROM tests_importasons im WHERE im.id_texto = t.id) as imports
                        FROM studason_tests t
                        WHERE t.texto LIKE \"%$palavraEscrita%\" AND t.num_palavras > 0 
                        ORDER BY RAND()
                        LIMIT 5;";
                        
                    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
                    
                    if (mysqli_num_rows($result)>0){
                ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Textos')?></h3>
                        <div class="card-actions">
                            <a href="?page=texts&iid=<?=$palavra['id_idioma']?>&palavra=<?=$palavraEscrita?>" class="btn btn-primary"><?=_t('Ver mais')?></a>
                        </div>
                    </div>
                    <div class="list-group list-group-flush list-group-hoverable overflow-auto" style="max-height: 25rem">
                    <?php
                        while($r = mysqli_fetch_assoc($result)){

                            echo '<div class="list-group-item"><div class="row">
                                <div class="col-auto">
                                <a href="?page=text&id='.$r['id'].'">'.$r['titulo'].' </a>
                                </div>
                            </div></div>';

                        };
                    ?>
                    </div>
                </div>
                <?php }}; ?>


                <?php 
                    $return = getPalavrasMesmaEscrita($_GET['pid'],5,false);
                    $return .= getPalavrasMesmaPronuncia($_GET['pid'],5,false);
                    $return .= getPalavrasRelacionadas($_GET['pid'],5,false);
                    $return .= getPalavrasMesmosReferentes($_GET['pid'],5,false);
                    if (strlen($return)>12){
                ?>
                <div class="card mb-3">
                    <div class=" list-group list-group-flush list-group-hoverable "><?=$return?></div></div>
                </div>
                <?php }; ?>
            </div>


            </div>
          </div>
        </div>
        
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExtras" aria-labelledby="offcanvasExtrasLabel">
	<div class="offcanvas-body" id="extrasCanvas">
		
	</div>
</div>
<script>
	function loadExtras(tipo){
		$("#extrasCanvas").load('api.php?action='+tipo+'&pid=<?=$_GET['pid']?>&e=0');
	}
    $.get("api.php?action=ajaxOrigemPalavra&pid=<?=$_GET['pid']?>", function(data) {
        montarOrigens(JSON.parse(data));
    });
</script>
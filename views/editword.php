
<?php 
				 
	$id_idioma = $_GET['iid'];
	if (!$_GET['pid']>0) $_GET['pid'] = 0;

	if (strlen($_GET['new'])>0) $_GET['pid'] = 0;

	$idioma = array();   
	$romanizacao = 0;
	$result = mysqli_query($GLOBALS['dblink'],"SELECT *, 
					(SELECT binario FROM escritas WHERE id_idioma = i.id AND padrao = 1 LIMIT 1) as binario,
					(SELECT nome_legivel FROM idiomas d WHERE d.id = i.id_idioma_descricao LIMIT 1) as desc_idioma,
					(SELECT id FROM palavras p WHERE p.id = ".$_GET['pid']." AND p.id_idioma = ".$id_idioma." LIMIT 1) as validPal,
					(SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
					FROM idiomas i
					WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
	while($r = mysqli_fetch_assoc($result)) { 
		$idioma  = $r;
	};
	if ($idioma['id_idioma_descricao'] < 10000){
		$idioma['desc_idioma'] = getDescricaoIdioma($idioma['id_idioma_descricao']);
	}
	$romanizacao = $idioma['romanizacao'];  
	if ($idioma['binario']>0) $bin = ' BINARY ';

	if ($idioma['nome_legivel']=='' || ($idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0 ) ||
		(!$idioma['validPal'] > 0 && $_GET['pid'] != 0)) {
		
			echo '<script>window.location = "index.php";</script>';
			exit;
	}

	$fonts = '';

	
	$scriptSalvarNativo = '';
	$inputsNativos = '';
	$scriptAutoSubstituicao = '';
	
	$inseridorDrawchar = ''; // novidade, pra drawchar

	$escritaPadrao = 1; $fonte = 0; $autoloader = '';

	$langs = mysqli_query($GLOBALS['dblink'],"SELECT e.*, f.arquivo as fonte FROM escritas e 
		LEFT JOIN fontes f ON f.id = e.id_fonte
		WHERE id_idioma = ".$id_idioma." ORDER BY e.padrao DESC;") or die(mysqli_error($GLOBALS['dblink']));
	while ($e = mysqli_fetch_assoc($langs)){
		$scriptSalvarNativo .= 'salvarNativo(\''.$e['id'].'\');';
		$autoon = '';
		$changed = getLastChange('autosubstituicoes',$e['id']);
		$autoloader .= 'if('.$changed.' > localStorage.getItem("k_autosubs_updated_'.$e['id'].'") ) loadAutoSubstituicoes(\''.$e['id'].'\', '.$changed.', true);';
		if ($e['padrao']==1) {
			$escritaPadrao = $e['id'];
			$fonte = $e['id_fonte'];
			$tamanho = $e['tamanho'];
		}
	
		if($e['id_fonte']== 3){

			if($e['substituicao']==1){
				$autoon = ' ('._t('Automático').')';
			}
			
			$inputsNativos .= '<div class="mb-3">
					<label class="form-label">'.$e['nome'].$autoon.' <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasDrawchar" role="button" aria-controls="offcanvasEnd" onclick="loadCharDiv(\''.$e['id'].'\',\'drawcharlist'.$e['id'].'\',false,\''.$e['id_fonte'].'\')">'._t('Inserir caractere').'</a></label>
					<input type="hidden" class="escrita_nativa" id="escrita_nativa_'.$e['id'].'" />
					<div class="form-control editable-drawchar" id="drawchar_editable_'.$e['id'].'" contenteditable="true" data-eid="'.$e['id'].'" data-fonte="'.$e['id_fonte'].'" data-tamanho="'.$e['tamanho'].'"></div>
				</div>';

			$inseridorDrawchar .= '<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDrawchar" aria-labelledby="offcanvasEndLabel">
					<div class="offcanvas-header">
						<h2 class="offcanvas-title" id="offcanvasEndLabel">Caracteres</h2>
						<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
					</div>
					<div class="offcanvas-body">
						<div class="mb-3" id="drawcharlist'.$e['id'].'"> </div>
						<div>
						</div>
					</div>
				</div>';

		}else{

			if($e['substituicao']==1){

				$scriptAutoSubstituicao .= 'let data2 = getAutoSubstituicao("'.$e['id'].'",data);
					if (data2 == "-1") exibirNativa("'.$e['id'].'","","'.$e['id_fonte'].'","'.$e['tamanho'].'");
					else if(data2.length > 0) exibirNativa("'.$e['id'].'",data2,"'.$e['id_fonte'].'","'.$e['tamanho'].'");';

				$autoon = ' ('._t('Automático').')';
			}
			
			$inputsNativos .= '<div class="mb-3">
					<label class="form-label">'.$e['nome'].$autoon.' <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasNativeBtns" role="button" aria-controls="offcanvasEnd"  onclick="loadCharDiv(\''.$e['id'].'\')">'._t('Inserir caractere').'</a></label>
					<input type="text" class="form-control escrita_nativa custom-font-'.$e['id'].'" id="escrita_nativa_'.$e['id'].'" ';
					
			if($e['checar_glifos']==1) $inputsNativos .= ' onchange="checarNativo(this,\''.$e['id'].'\')"';
			else $inputsNativos .= ' onchange="editarPalavra()"';
			$inputsNativos .= ' placeholder=""></div>';
		};
	} 
?>

<input type="hidden" id="codigo" value="<?=$id_idioma?>" />
<input type="hidden" id="idPalavra" value="0" />
<input type="hidden" id="palavrDis" value="" />
<input type="hidden" id="mainPal" value="" />




        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
					<ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlexicon&iid=<?=$id_idioma?>"><?=_t('Léxico')?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Palavra')?></a></li>
                    </ol>
                </h2>
              </div>

			<div class="col-auto ms-auto d-print-none">
				<div class="btn-list">
					<a href="?page=editword&iid=<?=$id_idioma?>" class="btn btn-primary d-none d-sm-inline-block">
					<!-- Download SVG icon from http://tabler-icons.io/i/plus -->
					<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
					<?=_t('Nova')?>
					</a>
				</div>
			</div>

            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body ">

			<div class="container-xl appholder">
				<div class="row row-cards">
					<div class="col-12">
						<div class="card placeholder-glow">
							<div class="card-body">
								<div class="placeholder col-9 mb-3"></div>
								<div class="placeholder placeholder-xs col-10"></div>
								<div class="placeholder placeholder-xs col-11"></div>
							</div>
						</div>
					</div>
				</div>
			</div>

          <div class="container-xl appLoad">
            <div class="row row-deckx row-cards">

            <div class="col-md-8">
                <div class="card">
					<div class="card-header">
						<h3 class="card-title"><?=_t('Informações básicas')?></h3>
						<div class="card-actions">
							<a href="#" onclick="gravarPalavra()" id="saveBtn" class="btn btn-primary"><?=_t('Salvar')?></a>
						</div>
					</div>
					<div class="card-body">						

						<div class="mb-3">
							<div class="row g-2">
								<div class="col-6">
									<label class="form-label"><?=_t('Pronúncia')?>* <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasPronBtns" role="button" aria-controls="offcanvasStart" onclick="loadPronDiv()"><?=_t('Inserir sons')?></a></label>
									<input type="text" class="form-control" id="pronuncia"  autofocus
										onchange="checarPronuncia(this,'<?=$id_idioma?>')" onkeyup="editarPalavra()"
										placeholder="<?=_t('Pronúncia em IPA')?>">
								</div>
								
								<div class="col-6">
									<label class="form-label"><?=_t('Classe da palavra')?>*</label>
									<select id="id_classe" type="text" value="" class="form-select" onchange="updateClassePalavras()" >
										<option value="0" selected><?=_t('Nenhuma')?></option>
										<?php 
										
										//TODO fazer uma só query, embbora por enquanto aqui tá safe, só repte pelo numero de classes do idioma
										$langs = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM classes c 
											LEFT JOIN glosses g ON g.id = c.id_gloss WHERE c.id_idioma = ".$id_idioma." AND superior = 0;") or die(mysqli_error($GLOBALS['dblink']));
										while ($lang = mysqli_fetch_assoc($langs)){
											echo '<option value="'.$lang['id'].'"';
											//if ($idioma['id_classe'] == $lang['id']) echo ' selected';
											echo '>'.$lang['gloss'].' - '.$lang['nome'].'</option>';
											$subks = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM classes c 
												LEFT JOIN glosses g ON g.id = c.id_gloss WHERE c.id_idioma = ".$id_idioma." AND superior = ".$lang['id'].";") or die(mysqli_error($GLOBALS['dblink']));
											while ($subk = mysqli_fetch_assoc($subks)){
												echo '<option value="'.$subk['id'].'"';
												//if ($idioma['id_classe'] == $lang['id']) echo ' selected';
												echo '>&nbsp;&nbsp;'.$subk['gloss'].' - '.$subk['nome'].'</option>';
												

												
											}


										}
										?>
										<option disabled><?=_t('Outros tipos de palavras')?></option>
										<option value="2"><?=_t('Contração')?></option>
										<option value="1" title="Partes de palavras não usadas como palavras soltas"><?=_t('Morfema')?></option>
										<option value="3" title="Expressões em que as palavras possuem significado diferente ou mais específico do que quando isoladas"><?=_t('Expressão')?></option>
									</select>
								</div>
							</div>
						</div>
						
						<div class="mb-3">
							<div class="form-group" >
								<label class="form-label"><?=_t('Significado')?>* (<?=_t('em %1',[$idioma['desc_idioma']])?>)</label>
								<input type="text" class="form-control" id="significado" title="<?=$idioma['nome_legivel']?>"
								onkeyup="editarPalavra()"  placeholder="<?=_t('Significado principal sucinto (ex.: cabeça; topo)')?>"><div id="sigsIds"></div>
							<a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasSigCom" role="button" aria-controls="offcanvasStart"><?=_t('Significados da comunidade')?></a>
							<a class="btn btn-sm btn-primary" onClick='$("#modalAdSigIid").modal("show")'><?=_t('Em outro idioma')?></a>
							</div>
						</div>
						
						<div class="mb-3">
							<div class="col-12">
								<div class="form-group" >
									<label class="form-label"><?=_t('Detalhes')?></label>
									<textarea id="detalhes"
									onkeyup="editarPalavra()"></textarea>
								</div>
							</div>
						</div>
						
						<div class="mb-3">
							<div class="col-12">
								<div class="form-group" >
									<label class="form-label"><?=_t('Informações privadas')?></label>
									<textarea class="form-control" id="privado" rows="3"
									onkeyup="editarPalavra()"></textarea>
								</div>
							</div>
						</div>

						
						<div class="mb-3">
						<div class="row g-2">
							
							<div class="col-4" >
								<div class="form-group" id="selectFormaDic">
									<label class="form-label"><?=_t('Forma base')?>* <a class="btn btn-sm btn-primary" onclick="loadDicionario(true,true)"><?=_t('Recarregar')?></a></label>
									<select id="id_forma_dicionario" type="text" value="" class="form-select" onchange="mudarDic()" >
									</select>
								</div>
							</div>

							<div class="col-4" >
								<div class="form-group">
									<label class="form-label"><?=_t('Derivada de')?></label>
									<select id="id_derivadora" type="text" value="" class="form-select" onchange="editarPalavra()" >
										
									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group" >
									<label class="form-label"><?=_t('Nível de uso')?></label>
									<select id="id_uso" type="text" value="" class="form-select" onchange="editarPalavra()" >
										<option value="0" selected><?=_t('Não especificado')?></option>
										<?php 
										$langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM nivelUsoPalavra WHERE id_idioma = ".$id_idioma." ORDER BY ordem;") or die(mysqli_error($GLOBALS['dblink']));
										while ($lang = mysqli_fetch_assoc($langs)){
											echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'"';
											//if ($idioma['id_classe'] == $lang['id']) echo ' selected';
											echo '>'.$lang['titulo'].'</option>';
										}
										?>
									</select>
								</div>
							</div>
						</div>
						</div>

						<div class="mb-3" id="comboReferentes">
							<label class="form-label"><?=_t('Referentes')?> <a class="btn btn-sm btn-primary" onclick="loadReferentes(true,true)"><?=_t('Recarregar')?></a></label>
							<!-- USAR CACHE -->
							<select id="id_referentes" multiple type="text" value="" class="form-select" onchange="editarPalavra()" >
							</select>
						</div>

						<div class="mb-3" id="comboOrigens">
							<label class="form-label"><?=_t('Origens')?> <span id="origensTexto"></span> <a class="btn btn-sm btn-primary" onclick="loadOrigens(true,true)"><?=_t('Recarregar')?></a></label>
							<!-- USAR CACHE -->
							<select id="id_origens" multiple type="text" value="" class="form-select" onchange="editarPalavra()" >
							</select>
						</div>

						<div class="mb-3">
							<label class="form-label"><?=_t('Tags')?></label>
							<select id="id_tags" multiple type="text" value="" class="form-select" onchange="editarPalavra()" >
								<?php 

									$sql = "SELECT tag FROM tags WHERE tipo_dest = 'word' AND id_dest = ".$_GET['pid'].";";

									$result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
									while($r = mysqli_fetch_assoc($result)){
										echo '<option value="'.$r['tag'].'" selected>'.$r['tag'].'</option>';
									};
								?>
							</select>
						</div>

					</div>
                </div>
            </div>

			
            <div class="col-md-4">
			<div class="row row-cards">
			<div class="col-12">
				<div class="card mb-3">
					<div class="card-header">
						<h3 class="card-title"><?=_t('Morfologia')?></h3>
						<div class="card-actions">
							<a href="?page=editforms&pid=<?=$_GET['pid']?>" style="display:none" id="btnWordForms" class="btn btn-primary"><?=_t('Ver formas')?>
							</a>

						</div>
					</div>
					<div class="card-body" id="detalhesGramaticais"> 
						<?=_t('Salve a palavra para ver as opções')?>
					</div>
				</div>
				<?php if ($romanizacao || $inputsNativos != ''){ ?>
                <div class="card mb-3">
					<div class="card-header">
						<h3 class="card-title"><?=_t('Escrita')?></h3>
					</div>
					<div class="card-body">
						
						<?php if ($romanizacao){ ?>
							<div class="mb-3" >
								<label class="form-label"><?=_t('Romanização')?></label>
								<input type="text" class="form-control" id="romanizacao" 
								onchange="checarRomanizacao(this,'<?=$id_idioma?>')"
								onkeyup="editarPalavra()"
								placeholder="Palavra no alfabeto latino">
							</div>
						<?php }  
						echo $inputsNativos; ?>
					</div>
				</div>
				<?php }  ?>

				<div class="card"  style="max-height:25rem;overflow:auto;">
					<div class="card-body" id="fleksonsPalavr"> 
						<h3 class="card-title"><?=_t('Palavras relacionadas')?></h3>
					</div>
				</div>

			</div>
			</div>
			</div>

            </div>
          </div>
        </div>



<script>

	function gravarReferentes(){
		if ( $('#id_forma_dicionario').val() > 0) return;
		$.post("api.php?action=ajaxGravarReferentes"
			+"&pid="+ $('#idPalavra').val()+"&iid=<?=$id_idioma?>", 
		{ referentes: $('#id_referentes').val()
			}, function (data){
				if ($.trim(data) == 'ok'){
					//$("#listaDePalavras").load("api.php?action=listarPalavras&id=<?=$id_idioma?>&t=");
				}else{
					alert(data);
				};
		});
	};

	function gravarOrigens(){
		$.post("api.php?action=ajaxGravarOrigens"
			+"&pid="+ $('#idPalavra').val()+"&iid=<?=$id_idioma?>"+"&origens="+$('#id_origens').val(), 
		{ origens: $('#id_origens').val()
			}, function (data){
				if ($.trim(data) == 'ok'){
					//xxxxx reload painel origens
					//$("#listaDePalavras").load("api.php?action=listarPalavras&id=<?=$id_idioma?>&t=");
				}else{
					alert(data);
				};
		});
	};

	function editarPalavra(){
		if ($('#significado').val()=='') {
			//alert('Significado está vazio!'); 
			$("#significado").addClass( 'is-invalid' );
			return;
		};
		$("#significado").removeClass( 'is-invalid' );
		$('#saveBtn').show();
	}

	function gravarPalavra(ignorar = '0'){
		if ($('#pronuncia').val()=='') {
			//alert('Pronúncia está vazio!'); 
			$("#pronuncia").addClass( 'is-invalid' );
			return;
		}; $("#pronuncia").removeClass( 'is-invalid' );
		if ($('#significado').val()=='') {
			//alert('Significado está vazio!'); 
			$("#significado").addClass( 'is-invalid' );
			return;
		}; $("#significado").removeClass( 'is-invalid' );
		<?php if ($romanizacao){ ?> 
		if ($('#romanizacao').val()=='') {
			//alert('Significado está vazio!'); 
			$("#romanizacao").addClass( 'is-invalid' );
			return;
		}; $("#romanizacao").removeClass( 'is-invalid' );
		<?php } ?>
		
		if ($('#id_forma_dicionario').val()=='2' && $('#id_origens').val()=='') {
			alert('Palavras do tipo contração devem especificar suas origens!'); 
			return;
		};

		var oiids = new Array();
		$(".sigoutros").each(function() {
			oiids.push( { s : $(this).val(), i : $(this).attr('id').replace("sigoutro_","")} ); 
		});

		$.post("api.php?action=ajaxGravarPalavra"
			+"&pid="+ $('#idPalavra').val()+"&iid=<?=$id_idioma?>&ignorar="+ignorar, 
		{ <?php if ($romanizacao){ ?> romanizacao:$('#romanizacao').val(), <?php } ?>
			pronuncia:$('#pronuncia').val(),
			id_forma_dicionario:$('#id_forma_dicionario').val(),
			id_derivadora:$('#id_derivadora').val(),
			id_classe:$('#id_classe').val(),
			id_uso:$('#id_uso').val(),
			significado:$('#significado').val(),
			privado:$('#privado').val(),
			tags:$('#id_tags').val(),
			oiids:oiids,
			detalhes:tinymce.get('detalhes').getContent() //$('#detalhes').val() 
			
		}, function (data){
			if ($.trim(data) > 0){
				$('#idPalavra').val($.trim(data));

				<?php echo $scriptSalvarNativo; ?>

				setTimeout(() => {
					gravarReferentes();
				}, 300);
				setTimeout(() => {
					gravarOrigens();
				}, 300);

				setTimeout(() => {
					abrirPalavra($.trim(data));
				}, 500);

			}else{
				let resp = $.trim(data).split('|');

				if (resp[0] < 0){

					let rep = resp[0];
					rep = rep.substring(1);

					if (confirm(
						'Já existe uma palavra com a mesma pronúncia ou romanização: \n<br><strong>\\'+resp[1]+
						'\\</strong> \n<br>'+resp[2]+'. \n<br><br>Deseja salvar mais uma nova palavra assim mesmo?'
					)){
						gravarPalavra(ignorar+','+rep);
					}

					/*$.confirm({
						title: 'Já existe!',
						type: 'red', 
						typeAnimated: true,
						content: 'Já existe uma palavra com a mesma pronúncia ou romanização: \n<br><strong>\\'+resp[1]+
							'\\</strong> \n<br>'+resp[2]+'. \n<br><br>Deseja descartar esta e abrir a existente, ou salvar mais esta nova palavra assim mesmo?'  ,
						containerFluid: true, 
						buttons: {
							"Abrir existente": function () {
								abrirPalavra(rep);
							},
							"Salvar esta outra": function () {
								gravarPalavra(ignorar+','+rep);
							},
							Cancelar: function () {
								
							} 
						}
					});*/
					
				}else{
					alert(data);
				};
			};
		});
	}; 

	function salvarNativo(e){ 
		if ($('#idPalavra').val() == '0') return;
		$.post('api.php?action=salvarPalavraNativa&pid='+$('#idPalavra').val()+'&e='+e , 
		{ p: $('#escrita_nativa_'+e).val() },function (data){
			if ($.trim(data) != 'ok') alert(data);
			//$("#listaDePalavras").load("api.php?action=listarPalavras&id=<?=$id_idioma?>&t=");
		});
	}

	function abrirPalavra(pid){
		window.location = "index.php?page=editword&iid=<?=$id_idioma?>&pid="+pid;
	}

	function loadWord(pid,l,c,i1,i2,text,dic = 0){

		$('#sigsIds').html('');

		if (pid == 0) {

			setTimeout(() => {
				document.getElementById("pronuncia").focus(); //$('#pronuncia').focus();
				createTablerSelect('id_classe');
				createTablerSelect('id_uso');
				createTablerSelectAllNativeWords('id_origens');
				createTablerSelect('id_referentes');
				$("#saveBtn").hide();
			}, 800);
			setTimeout(() => {
				createTablerSelectNativeWords('id_forma_dicionario','<?=$fonte?>','<?=$tamanho?>');
			}, 1000);
			setTimeout(() => {
				createTablerSelectNativeWords('id_derivadora','<?=$fonte?>','<?=$tamanho?>');
			}, 1200);

			<?php  if (strlen($_GET['new'])>0) {

				// default é romanizacao ou escrita ?

				echo "exibirNativa(".$escritaPadrao.",\"".$_GET['new']."\");//$('#escrita_nativa_".$escritaPadrao."').val(\"".$_GET['new']."\");";
			}  ?>

		}else{

			//$("#fleksonsPalavr").hide();
			$("#detalhesPalavr").show();

			$('#idPalavra').val(pid); 

			$.get('api.php?action=getGlossesPalavra&pid='+pid, function (data){
				$('#detalhesGramaticais').html(data);
				if ($.trim(data) != '<?=_t('Palavras desta classe não mudam de forma.')?>') $("#btnWordForms").show();
				// outra tab ???????
			}).done(function(){
			
				var ipids;
				ipids = '';
				$('.ipid').each( function() {
					ipids = ipids+$(this).val()+',';
				});
				//alert(ipids);
				$.get("api.php?action=ajaxUpdateIpids&ipids="+ipids+"&pid="+pid);
			});

			$('#fleksonsPalavr').load('api.php?action=getPalavrasExtras&pid='+pid);
			$('#id_referentes').val(null); 
			$('#id_origens').val(null); 
			
			$.getJSON( "api.php?action=getDetalhesPalavra&pid=" +pid , function(data){ 
				$.each( data, function( key, val ) {
						$('#romanizacao').val(data[0].romanizacao); 
						$('#pronuncia').val(data[0].pronuncia); 
						
						if (data[0].referentes.length > 0){
							$.each(data[0].referentes.split(","), function(i,e){
								$("#id_referentes option[value='" + e + "']").prop("selected", true);
							});
						}

						//xxxxx update tomselect referentes
						
						// significados outros idiomas append em sigsIds
						$.each(data[0].sigiids, function(i,e){
							addSigIidTela(e.iid,e.sig,e.niid,e.eid);
						});

						//$('#id_referentes').val(data[0].referentes); 
						$('#id_forma_dicionario').val(data[0].id_forma_dicionario); 
						if (data[0].id_forma_dicionario == "0"){
							$('#comboReferentes').show();
							$('#palavrDis').val(data[0].pronuncia);
						}else{
							$('#comboReferentes').hide();
						};
						$('#id_derivadora').val(data[0].id_derivadora); 
						

						if (data[0].origens.length > 0) {
							$.each(data[0].origens.split(","), function(i,e){
								$("#id_origens option[value='" + e + "']").prop("selected", true);
							});
						}

						//xxxxx update tomselect origens
						//document.getElementById('id_origens').tomselect.refreshItems();

						if (data[0].id_classe < 0) {
							$("#selectFormaDic").hide();
							$("#id_forma_dicionario").val(0);
							//document.querySelector('#id_forma_dicionario').tomselect.setValue('0'); 
						}else{
							$("#selectFormaDic").show();
						}
						
						$('#origensTexto').html(data[0].origensTexto); 

						$('.escrita_nativa').val(''); 
						data[0].escrita_nativa.forEach(function(e){ //xxxxx deve vir tbm draw TRUE se id_fonte < 0
							exibirNativa(e['id'],e['palavra'],e['fonte'],e['tamanho']);//$('#escrita_nativa_'+e['id']).val(e['palavra']);
							if (e['id'] == <?=$escritaPadrao?>) $("#mainPal").val(e['palavra']);
						})
						$('#id_classe').val(data[0].id_classe); 
						//document.querySelector('#id_classe').tomselect.setValue(data[0].id_classe);//
						
						$('#id_uso').val(data[0].id_uso); 
						$('#significado').val(data[0].significado); 
						$('#privado').val(data[0].privado); 
						//$("#modaltitle").html(data[0].pronuncia);
						//$(".chosen-select").trigger("chosen:updated");

						$('#detalhes').val(data[0].detalhes); 

						$('#btnMSalvar').hide();

						$("#modalPalavra").modal('show');
						
						setTimeout(() => {
							//document.getElementById("pronuncia").focus(); //$('#pronuncia').focus();
							createTablerSelect('id_classe');
							createTablerSelect('id_uso');
							//createTablerSelect('id_origens');
							createTablerSelectAllNativeWords('id_origens');
							createTablerSelect('id_referentes');
							$("#saveBtn").hide();
						}, 600);

						setTimeout(() => {
							createTablerSelectNativeWords('id_derivadora','<?=$fonte?>','<?=$tamanho?>');
						}, 800);

						setTimeout(() => {
							createTablerSelectNativeWords('id_forma_dicionario','<?=$fonte?>','<?=$tamanho?>');
							tinymce.get('detalhes').setContent(data[0].detalhes);
						}, 1000);
				}); 
			});
		};
	};

	function addSigIidTela(iid,signif,niid,eid = 0){
		$('#sigsIds').append('Significado (em '+niid+')<div><input type="text" title="'+niid+'" class="form-control sigoutros custom-font-'+eid+'" id="sigoutro_'+iid+'" onkeyup="editarPalavra()" value="'+signif+'" placeholder="Significado sucinto em '+niid+'"></div>');
	};

	function entradaSigIdioma(){

		var idl = $('#id_idsig').val();
		if(idl<0){alert('<?=_t('Selecione o idioma')?>!');
			return false;}
		var idn = $('#id_idsig option:selected').attr('data-n');
		var seid = $('#id_idsig option:selected').attr('data-e');
		addSigIidTela(idl,'',idn,seid);
		$("#modalAdSigIid").modal("hide");

	}

	function mudarDic(){
		if ( $('#id_forma_dicionario').val() == 0){
			$('#comboReferentes').show();
		}else{
			$('#comboReferentes').hide();
			$('#id_referentes').val(null);
		}
		editarPalavra();
	}

	function loadPronDiv(forceReload = false){
		$('#tempPron').val($('#pronuncia').val());

		let data = <?=getLastChange('sounds',$id_idioma)?>;
		if (forceReload || data > localStorage.getItem("k_sounds<?=$id_idioma?>_updated")){
			console.log('local sounds outdated > update');
			$.get("api.php?action=ajaxGetDivLateralSons&iid=<?=$id_idioma?>", function (lex){
				$("#divInserirSons").html(lex);
				localStorage.setItem("k_sounds<?=$id_idioma?>", lex);
				localStorage.setItem("k_sounds<?=$id_idioma?>_updated", data);
			})
		}else{
			console.log('local sounds load');
			$("#divInserirSons").html( localStorage.getItem("k_sounds<?=$id_idioma?>") );
		}
	}

	function salvarItem(concordancia){
		var i = $('#idc_'+concordancia).val();
		$.get("api.php?action=ajaxGravarItem&pid="+ $('#idPalavra').val()+"&iid=<?=$id_idioma?>"
		+"&c="+concordancia+"&i="+i , function (data){
				if ($.trim(data) >0){
					$('#detalhesGramaticais').load("api.php?action=getDetMorfPalavra&pid="+ $('#idPalavra').val());

				}else{
					alert(data);
				};
				editarPalavra();
		});
	};

	function salvarGenPal(){ 
		var i = $('#idgp').val();
		$.get("api.php?action=ajaxGravarGenPal&pid="+ $('#idPalavra').val()+"&iid=<?=$id_idioma?>"
		+"&i="+i , function (data){
				if ($.trim(data) >0){
					
					//loadWord($('#idPalavra').val() ); 
					//xxxxx load apenas generos/flexoes
					// action getDetMorfPalavra pid
					$('#detalhesGramaticais').load("api.php?action=getDetMorfPalavra&pid="+ $('#idPalavra').val());

				}else{
					alert(data);
				};
		});
	};

	function checarRomanizacao(este,idioma){

		$("#romanizacao").removeClass( 'is-invalid' );
		//
		//alert( $(este).val() );
			<?php  if ($romanizacao==2){ ?> 
			//xxxxx se estiver romanizacao como principal entrada, echo preencherPronuncia e foreach native
			<?php }; ?>
			editarPalavra();
	};
	<?php if($idioma['checar_sons']==1){ ?>
	function checarPronuncia(este,idioma){
		editarPalavra();
		$(este).removeClass( 'is-invalid' );
		var tmpPron = $(este).val();
		let data = getChecarPronuncia(idioma, tmpPron, 1);
		if(data=='-1'){ 
			$(este).addClass( 'is-invalid' );
		}else{
			$(este).val( data );
			data = tmpPron;
			<?php if ($romanizacao) echo '$("#romanizacao").val(tmpPron);'; ?>
			<?=$scriptAutoSubstituicao?>
		};
	};
	<?php }else{
		echo 'function checarPronuncia(este,idioma){$("#pronuncia").removeClass("is-invalid");editarPalavra();}';
	}; ?>

	function checarNativo(este,eid){
		//checar se caracteres estão na lista de caracteres apenas
		$(este).removeClass( 'is-invalid' );
		editarPalavra();
		$.post('api.php?action=getChecarNativo&eid='+eid, {
			p: $(este).val()
		}, function (data){ 
			if(data=='-1'){ 
				//alert('Caractere não encontrado no alfabeto!');
				$(este).addClass( 'is-invalid' );//$(este).val( '' );
			}else{
				if (data.lenght > 0)
					$(este).val( data );
			}
		});
	};

	function novaOrigem(){ alert('a fazer'); return;

		//selecionar ou adicionar idioma (nesse caso fica oculto)
		//inserir palavra e tudo q tem nela opcional (ref, pron, significado etc)
	};

	function updateClassePalavras(){
		editarPalavra();
		//se for classe tipo não aparece no dicionario, ocultar opções do forma_dicionario ?
		if ($("#id_classe").val() < 0) {
			//ocultar forma dicionario
			$("#selectFormaDic").hide();
			$("#id_forma_dicionario").val(0);
			//$(".chosen-select").trigger("chosen:updated");
		}else{
			$("#selectFormaDic").show();
		}
		//xxxxx reload generos/flexoes
	};

	function alterarOrdemOrigens(id,a){
		$.get("api.php?action=ajaxReordenarOrigemPalavra&id="+id+"&a="+a, function (data){
			if(data=='ok') loadWord( $('#idPalavra').val() ); //xxxxx load somente painelzinho de origens
			else alert(data);
		});
	};

	function addIpaPronuncia(char){
		$("#tempPron").val($("#tempPron").val() + char);
		//$("#pronuncia").trigger("change");
	}
	function addNatChar(char){
		$("#tempNat").val($("#tempNat").val() + char);
		//$("#pronuncia").trigger("change");
	}
	function loadExtras(tipo){
		$("#extrasCanvas").load('api.php?action='+tipo+'&pid=<?=$_GET['pid']?>');
	}

	function loadOrigens(forceReload = false, ja = false){ 
		var tv;
		if (ja) {
			tv = document.querySelector('#id_origens').tomselect.getValue();
			document.querySelector('#id_origens').tomselect.destroy();
		}
		let data = <?=getLastChange('origens')?>;
		if (forceReload || ja || data > localStorage.getItem("k_origens_updated")){
			console.log('local origens outdated > update');
			$.get("api.php?action=getOptionsOrigens", function (lex){
				$("#id_origens").html(lex);
				localStorage.setItem("k_origens", lex);
				localStorage.setItem("k_origens_updated", data);
				if (ja) // reloadBases();
					setTimeout(() => {
						createTablerSelectAllNativeWords('id_origens');//createTablerSelect('id_origens');
						document.querySelector('#id_origens').tomselect.setValue(tv);
					}, 1000);
			})
		}else{
			console.log('local origens load');
			$("#id_origens").html( localStorage.getItem("k_origens") );
			if (ja) // reloadBases();
				setTimeout(() => {
					createTablerSelectAllNativeWords('id_origens');//createTablerSelect('id_origens');
					document.querySelector('#id_origens').tomselect.setValue(tv);
				}, 1000);
		}
	};
	function loadReferentes(forceReload = false, ja = false){
		var tv;
		if (ja) {
			tv = document.querySelector('#id_referentes').tomselect.getValue();
			document.querySelector('#id_referentes').tomselect.destroy();
		}
		let data = <?=getLastChange('ref',$id_idioma)?>;
		if (forceReload || ja || data > localStorage.getItem("k_ref_updated")){
			console.log('local ref outdated > update');
			$.get("api.php?action=getOptionsReferentes", function (lex){
				$("#id_referentes").html(lex);
				localStorage.setItem("k_ref", lex);
				localStorage.setItem("k_ref_updated", data);
				if (ja) // reloadBases();
					setTimeout(() => {
						createTablerSelect('id_referentes');
						document.querySelector('#id_referentes').tomselect.setValue(tv);
					}, 1000);
			})
		}else{
			console.log('local ref load');
			$("#id_referentes").html( localStorage.getItem("k_ref") );
			if (ja) // reloadBases();
				setTimeout(() => {
					createTablerSelect('id_referentes');
					document.querySelector('#id_referentes').tomselect.setValue(tv);
				}, 1000);
		}
	};
	function loadDicionario(forceReload = false, ja = false){ 
		var tv, tv2;
		if (ja) {
			tv = document.querySelector('#id_forma_dicionario').tomselect.getValue();
			tv2 = document.querySelector('#id_derivadora').tomselect.getValue();
			document.querySelector('#id_forma_dicionario').tomselect.destroy();
			document.querySelector('#id_derivadora').tomselect.destroy();
		}
		let data = <?=getLastChange('lexicon',$id_idioma)?>;
		if (forceReload || data > localStorage.getItem("k_dici_<?=$id_idioma?>_updated")){
			console.log('local dici outdated > update');

			$.get("api.php?action=getOptionsDicionario&iid=<?=$id_idioma?>&eid=<?=$id_idioma?>", function (lex){
				$("#id_forma_dicionario").html(lex);
				$("#id_derivadora").html(lex);
				localStorage.setItem("k_dici_<?=$id_idioma?>", lex);
				localStorage.setItem("k_dici_<?=$id_idioma?>_updated", data);
				if (ja) // reloadBases();
					setTimeout(() => {
						createTablerSelectNativeWords('id_forma_dicionario','<?=$fonte?>','<?=$tamanho?>');
						document.querySelector('#id_forma_dicionario').tomselect.setValue(tv);
						createTablerSelectNativeWords('id_derivadora','<?=$fonte?>','<?=$tamanho?>');
						document.querySelector('#id_derivadora').tomselect.setValue(tv2);
					}, 1000);
			})

		}else{
			console.log('local dici load');
			$("#id_forma_dicionario").html( localStorage.getItem("k_dici_<?=$id_idioma?>") );
			$("#id_derivadora").html( localStorage.getItem("k_dici_<?=$id_idioma?>") );
		}
	};

	$(document).ready(function(){
		loadDicionario();
		loadReferentes();
		loadOrigens();
		
		<?php echo 'loadWord(\''.$_GET['pid'].'\');'; ?>
		
		setTimeout(() => {
			createTablerSelect('id_idsig',null);
			createTablerSelect('id_tags',null,true);
		}, 1000);
		appLoad();
		
	}); 

	document.addEventListener("DOMContentLoaded", function () {
		let options = {
		selector: '#detalhes',
		height: 300,
		menubar: false,
		statusbar: false,
		setup: (editor) => {
			editor.on('keyup', (e) => {
				editarPalavra();
			});
		},
		plugins: [
			'advlist', 'autolink', 'lists', 'link', 'paste', 'image'
		],
		toolbar: 'undo redo | formatselect | ' +
			'bold italic backcolor | alignleft aligncenter ' +
			'alignright alignjustify | bullist numlist outdent indent | link unlink | image |' +
			'removeformat',
		automatic_uploads: true,
		file_picker_types: 'image',
		images_file_types: 'jpg,jpeg,png,webp',
		content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }'
		}
		if (localStorage.getItem("tabler-theme") === 'dark') {
			options.skin = 'oxide-dark';
			options.content_css = 'dark';
		}
		tinyMCE.init(options);
	});

let soundsChanged = <?=getLastChange('sounds',$id_idioma)?>;
if ( soundsChanged > localStorage.getItem("k_pronuncias_updated_<?=$id_idioma?>") ) loadPronuncias('<?=$id_idioma?>', soundsChanged, true);
<?php echo $autoloader; ?>
</script>
<style>#fleksonsPalavr div:not(:first-child) h3 {
  margin-top: 30px;
}</style>

<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasPronBtns" aria-labelledby="offcanvasStartLabel">
	<div class="offcanvas-header">
		<h2 class="offcanvas-title" id="offcanvasStartLabel"><?=_t('Pronúncia')?></h2>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3" id="divInserirSons">
		</div>
		<div class="input-group">
			<input type="text" class="form-control" id="tempPron">
			<button class="btn btn-primary" type="button" data-bs-dismiss="offcanvas" onclick="okIpaPronuncia()">
			Ok
			</button>
		</div>
	</div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNativeBtns" aria-labelledby="offcanvasStartLabel">
	<div class="offcanvas-header">
		<h2 class="offcanvas-title" id="offcanvasStartLabel"><?=_t('Caracteres')?></h2>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3" id="divInserirChars"></div>
		<div  class="input-group">
			<input type="text" class="form-control" id="tempNat">
			<button class="btn btn-primary" type="button" data-bs-dismiss="offcanvas" onclick="okInsertNativo()">
			Ok
			</button>
		</div>
		<input type="hidden" id="lateralEid">
	</div>
</div>

<div class="modal modal-blur" id="modalAdSigIid" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-sm modal-dialog-centered" role="document" >
		<div class="modal-content"  >
			<div class="modal-header">
				<h5 class="modal-title" id="modaltitle"><?=_t('Adicionar significado em')?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body panel-body">
			<select class="form-select" id="id_idsig"><option value="0" selected><?=_t('Selecione um idioma')?></option><?php 
					$oiids = mysqli_query($GLOBALS['dblink'],
					"SELECT i.nome_legivel, i.id as iid, e.id as eid FROM idiomas i
					LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
					WHERE i.publico = 1 OR i.id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink'])); // AND buscavel = 1
				while($oid = mysqli_fetch_assoc($oiids)) {
					echo '<option value="'.$oid['iid'].'"data-n="'.$oid['nome_legivel'].'">'.$oid['nome_legivel'].'</option>'; //  data-e="'.$oid['eid'].'" 
				};
				?></select>
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-primary" onClick="entradaSigIdioma();"><?=_t('Adicionar')?></button>
			</div>
		</div>
	</div>
</div>


<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSigCom" aria-labelledby="offcanvasStartLabel">
	<div class="offcanvas-header">
		<h2 class="offcanvas-title" id="offcanvasStartLabel"><?=_t('Significados da comunidade')?></h2>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3">
		<?php
			$sql = "SELECT *,
					(SELECT COUNT(*) FROM sosail_joes WHERE tipo_destino = 'sigcom' AND id_destino = p.id AND valor = 1) as likes,
					(SELECT COUNT(*) FROM sosail_joes WHERE tipo_destino = 'sigcom' AND id_destino = p.id AND valor = -1) as dislikes
				FROM pal_sig_comunidade p WHERE id_idioma = ".$id_idioma." AND palavra = (
						SELECT palavra FROM palavrasNativas WHERE id_palavra = ".$_GET['pid']." AND id_escrita = ".$escritaPadrao." 

				)
				ORDER BY likes - dislikes DESC;";
			$b = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
			while($bx = mysqli_fetch_assoc($b)){
				echo '<div class="mb-3 row"><div class="col">'.$bx['significado'].'</div><div class="col-auto">
					<a class=" text-secondary"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-thumb-up"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 3a3 3 0 0 1 2.995 2.824l.005 .176v4h2a3 3 0 0 1 2.98 2.65l.015 .174l.005 .176l-.02 .196l-1.006 5.032c-.381 1.626 -1.502 2.796 -2.81 2.78l-.164 -.008h-8a1 1 0 0 1 -.993 -.883l-.007 -.117l.001 -9.536a1 1 0 0 1 .5 -.865a2.998 2.998 0 0 0 1.492 -2.397l.007 -.202v-1a3 3 0 0 1 3 -3z" /><path d="M5 10a1 1 0 0 1 .993 .883l.007 .117v9a1 1 0 0 1 -.883 .993l-.117 .007h-1a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-7a2 2 0 0 1 1.85 -1.995l.15 -.005h1z" /></svg>'.$bx['likes'].'</a> 
					<a class="text-secondary"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-thumb-down"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 21.008a3 3 0 0 0 2.995 -2.823l.005 -.177v-4h2a3 3 0 0 0 2.98 -2.65l.015 -.173l.005 -.177l-.02 -.196l-1.006 -5.032c-.381 -1.625 -1.502 -2.796 -2.81 -2.78l-.164 .008h-8a1 1 0 0 0 -.993 .884l-.007 .116l.001 9.536a1 1 0 0 0 .5 .866a2.998 2.998 0 0 1 1.492 2.396l.007 .202v1a3 3 0 0 0 3 3z" /><path d="M5 14.008a1 1 0 0 0 .993 -.883l.007 -.117v-9a1 1 0 0 0 -.883 -.993l-.117 -.007h-1a2 2 0 0 0 -1.995 1.852l-.005 .15v7a2 2 0 0 0 1.85 1.994l.15 .005h1z" /></svg>'.$bx['dislikes'].'</a>';
				echo ' <a class="text-secondary" onClick="remSignifCom('.$bx['id'].')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></a>';
				echo '</div></div>';
			};
		?>

		</div>
	</div>
</div>

<?=$inseridorDrawchar?>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExtras" aria-labelledby="offcanvasExtrasLabel">
	<div class="offcanvas-body" id="extrasCanvas">
		
	</div>
</div>
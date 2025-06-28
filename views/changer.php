
<!-- PANEL START -->
<?php 
$compactMode = false;
$id_idioma = $_GET['iid'] ?: 0;
if (!$id_idioma>0) {
  $id_idioma = 0;
  $compactMode = true;
}
$rowsh = 20;
$idioma = array(); 
$result = mysqli_query($GLOBALS['dblink'],"SELECT *,
(SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = '".$_SESSION['KondisonairUzatorIDX']."' LIMIT 1) as collab FROM idiomas i 
               WHERE id = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
$idioma  = $r;
};

$show = '';
if(!$_SESSION['KondisonairUzatorIDX']>0) $show = ' style="display:none" ';

if ($id_idioma > 0 && ($idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0 )) {
  echo '<script>window.location = "index.php";</script>';
  exit;
}

// // alterar changer pra receber dados de classes, palavras etc via GET (inclusive serve pra apis depois)
$getRegras = $_GET['rules'] ? base64_decode($_GET['rules']) : '';
$getPalavras = $_GET['words'] ? base64_decode($_GET['words']) : '';
$getClasses = $_GET['classes'] ? base64_decode($_GET['classes']) : getSCHeader('ksc',$id_idioma,'cats');
$getSubstituicoes = $_GET['rewrites'] ?? '';


?>

<link rel="stylesheet" href="codemirror.css">
<link rel="stylesheet" href="monokai.min.css">

<style>textarea{font-family:monospace;line-height: normal !important;/*font-size: 12px !important;*/}</style>
<input type="hidden" id="codigo" value="<?=$id_idioma?>" />


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="?"><?=_t('Início')?></a></li>
                      <?php if ($id_idioma > 0){ ?>
                      <li class="breadcrumb-item"><a href="?page=<?=$idioma['id_usuario'] == $_SESSION['KondisonairUzatorIDX']?'edit':''?>language&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <?php } ?>
                      <li class="breadcrumb-item active"><a><?=_t('Alterador sonoro')?></a></li>
                    </ol>
                </h2>
              </div>
              <!-- Page title actions -->

              <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                  <a class="nav-link" href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSettings" onclick="loadExtraPanel('ksc')"><?=_t('Ajuda')?></a>
                  <a onclick="aplicarMudancas()" class="btn btn-primary d-none d-sm-inline-block" id="btnAplicar">
                    <?=_t('Aplicar')?>
                  </a>
                </div>
              </div>

            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deckx row-cards">

              <div class="col-md-2">
                  <div class="card sticky-top">
                    <div class="card-body">
                      
                      <div class="mb-3">
                        <label class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" onchange="$('.row_extras').toggle()" checked>
                          <span class="form-check-label"><?=_t('Mostrar regras')?></span>
                        </label>
                        <label class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" <?=$compactMode?'checked':''?> onchange="toggleCompactMode(this)" id="compactModeCheckbox">
                          <span class="form-check-label"><?=_t('Mostrar entrada e saída ao lado')?></span>
                        </label>
                        <label class="form-check form-switch intermediate-toggle"  onchange="$('.input_intermediate').toggle()">
                            <input class="form-check-input" type="checkbox">
                            <span class="form-check-label"><?=_t('Mostrar intermediários')?></span>
                        </label>
                      </div>

                      
                      <div class="mb-3">
                        <label class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" onchange="$('.text_classes').toggle()" <?php if (!$id_idioma > 0) echo 'checked'; ?> id="check_classes">
                          <span class="form-check-label"><?=_t('Editar classes')?></span>
                        </label>
                        <?php if ($id_idioma > 0){?><div class="text_classes"><?php echo formatCategoriesAsButtons(getSCHeader('ksc',$id_idioma,'cats')); ?></div><?php } ?>
                        <textarea class="form-control text_classes nowrap" id="text_classes" spellcheck="false" onchange="updateDeclaredClasses('')" style="height: 12rem !important;<?php if ($id_idioma > 0) echo 'display:none'; ?>"><?php 
                         echo $getClasses; ?></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" onchange="$('.text_rewrites').toggle()" <?php if (!$id_idioma > 0) echo 'checked'; ?> id="check_rewrites">
                          <span class="form-check-label"><?=_t('Editar substituições')?></span>
                        </label>
                        <?php if ($id_idioma > 0){?><div class="text_rewrites"></div><?php } ?>
                        <textarea class="form-control text_rewrites nowrap" id="text_rewrites" spellcheck="false" style="height: 8rem !important;<?php if ($id_idioma > 0) echo 'display:none'; ?>"><?=$getSubstituicoes?></textarea>
                      </div>

                      <div class="mt-2">
                          <button class="btn btn-primary" onclick="openRuleGenerator()">Regras aleatórias</button>
                          <div id="ruleGeneratorInput" style="display: none; margin-top: 10px;">
                              <div class="input-group">
                                  <input type="number" class="form-control" id="numRules" placeholder="Quantidade de regras" min="1" value="5">
                                  <button class="btn btn-success" onclick="generateRandomRules(document.getElementById('numRules').value)">Gerar</button>
                              </div>
                          </div>
                      </div>


                    </div>
                  </div>
              </div>

              <div class="col-md-10">
                <div class="row row-cards">
                    <div class="col-8 row_extrasx rules-container">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title"><?=_t('Regras')?></h3>
                          <div class="card-actions" style="display:flex">

                            <?php if($_SESSION['KondisonairUzatorIDX']>0){ ?>
                                <div id="div_listasc"><select class="form-select" id="listasc" onchange="carregarLista()" >
                                  <option value="0" title="Insira qualquer palavra" selected><?=_t('Personalizado')?></option>
                                  <?php 
                                      if ($_SESSION['KondisonairUzatorIDX']>0){

                                          if ($idioma['nome_legivel']!=''){
                                            $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM soundChanges WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX'].
                                            ( $id_idioma > 0 ? " AND id_idioma = ".$id_idioma : "" ).";") or die(mysqli_error($GLOBALS['dblink']));
                                            if (mysqli_num_rows($langs)>0) echo '<option disabled>'._t('Listas de %1',[$idioma['nome_legivel']]).'</option>';
                                            while ($lang = mysqli_fetch_assoc($langs)){
                                                echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'">'.$lang['titulo'].'</option>';
                                            }
                                          }

                                          $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM soundChanges WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX']." ;") or die(mysqli_error($GLOBALS['dblink']));
                                          if (mysqli_num_rows($langs)>0) echo '<option disabled>'._t('Minhas listas').'</option>';
                                          while ($lang = mysqli_fetch_assoc($langs)){
                                              echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'">'.$lang['titulo'].'</option>';
                                          }

                                          $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM soundChanges WHERE publico = 1 ;") or die(mysqli_error($GLOBALS['dblink']));
                                          if (mysqli_num_rows($langs)>0) echo '<option disabled>'._t('Listas públicas').'</option>';
                                          while ($lang = mysqli_fetch_assoc($langs)){
                                              echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'">'.$lang['titulo'].'</option>';
                                          }
                                          
                                      }
                                  ?>
                                </select></div>

                                <?php if($_GET['iid']>0){ ?>
                                <div class="input-group mb-3" id="nomeLista" style="display:none" >
                                  <div class="input-group">
                                    <input type="text" class="form-control" id="descricao" placeholder="Ex.: Eu">
                                    <a class="btn btn-primary" id="btnNomearSC" onclick="salvaSC()"><?=_t('Salvar')?></a>
                                  </div>
                                </div>
                                <?php } ?>
                                
                            <?php } ?>
                            
                            <?php if($_SESSION['KondisonairUzatorIDX']>0 && $_GET['iid']>0){ ?>
                            <a class="btn btn-danger" style="display:none" id="btnApagarSC" onclick="apagarLista()"><?=_t('Apagar')?></a>
                            <a class="btn btn-primary" style="display:none" id="btnSalvarSC" onclick="salvarLista()"><?=_t('Salvar')?></a>
                            <?php } ?>
                            
                          </div>
                        </div>
                        <div class="card-body row_extras">
                          <div>
                              <textarea class="form-control ksc" id="schanges" spellcheck="false" style="height: 35rem" onkeyup="$('#btnSalvarSC').show();$('#btnApagarSC').hide();$('#nomeLista').hide();"><?=$getRegras?></textarea>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-4 row_extrasx annotations-container">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title"><?=_t('Anotações')?></h3>
                        </div>
                        <div class="card-body row_extras">
                          <div>
                              <textarea class="form-control ksc nowrap" id="instrucoes" style="height: 35rem" spellcheck="false" onkeyup="$('#btnSalvarSC').show();$('#btnApagarSC').hide();$('#nomeLista').hide();"></textarea>
                          </div>
                        </div>
                      </div>
                    </div> 
                    <div class="col-6 row_palavras input-container input_intermediate">
                        <div class="card">
                          <div class="card-header">
                            <h3 class="card-title"><?=_t('Entrada')?></h3>
                            <div class="card-actions">
                              <?php if($id_idioma > 0){ ?>
                                    <div class="" <?=$show?>>
                                        <select id="listap" class="chosen-select form-control " onchange="carregarPalavras()"  >
                                            <option value="0" selected><?=_t('Carregar')?>...</option>
                                            <option value="g1" title="Insira qualquer palavra"><?=_t('Personalizado')?></option>
                                            <option value="g2" title="Carregar as palavras em forma de dicionário" ><?=_t('Léxico')?></option>
                                            <option value="g3" title="Carregar todo o léxico, incluindo derivações e flexões" ><?=_t('Todas as palavras')?></option>
                                            <?php 
                                                $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM nivelUsoPalavra WHERE id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
                                                if (mysqli_num_rows($langs)>0) echo '<option disabled>'._t('Níveis de Uso').'</option>';
                                                while ($lang = mysqli_fetch_assoc($langs)){
                                                    echo '<option value="n'.$lang['id'].'" title="'.$lang['descricao'].'">'.$lang['titulo'].'</option>';
                                                }
                                                $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM classes WHERE id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
                                                if (mysqli_num_rows($langs)>0) echo '<option disabled>'._t('Classes gramaticais').'</option>';
                                                while ($lang = mysqli_fetch_assoc($langs)){
                                                    echo '<option value="k'.$lang['id'].'" title="'.$lang['descricao'].'">'.$lang['nome'].'</option>';
                                                }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-4" style="display:none">
                                        <select id="entradaTipo" class="chosen-select form-control " onchange="carregarPalavras()"  >
                                            <option value="pronuncia" selected><?=_t('Pronúncia')?></option>
                                            <option value="romanizacao"><?=_t('Romanização')?></option>
                                        </select>
                                    </div>
                                <?php } ?>
                              
                            </div>
                          </div>
                          <div class="card-body">
                            <div>
                              <textarea class="form-control ksc nowrap" id="entrada" onkeyup="setInputPersonalizado()" spellcheck="false" style="height: 35rem"
                              onscroll="saida.scrollTop=scrollTop"><?=$getPalavras?></textarea>
                            </div>
                          </div>
                        </div>
                    </div>
                    <div class="col-12 intermediate-container input_intermediate" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?=_t('Etapas Intermediárias')?></h3>
                            </div>
                            <div class="card-body">
                              <div class="intermediate-scroll" id="intermediateSteps" style="overflow-x: auto; white-space: nowrap;">
                                  <!-- Textareas serão inseridas aqui via JavaScript -->
                              </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 row_palavras output-container input_intermediate">
                        <div class="card">
                          <div class="card-header">
                            <h3 class="card-title"><?=_t('Resultados')?></h3>
                            <div class="card-actions">
                              <?php /* if($id_idioma > 0){ ?>
                                      <select id="pos" class="form-control"  <?=$show?>>
                                          <option disabled selected>Opções</option>
                                          <option value="cd" title=" " selecionado>Salvar Descendente (a fazer)</option>
                                      </select>
                              <?php } */ ?>
                              
                              <!--a href="#" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                Salvar
                              </a-->

                            </div>
                          </div>
                          <div class="card-body">
                            <div>
                              <textarea class="form-control ksc nowrap" id="saida" readonly="readonly" style="height: 35rem"
                                  cols="1" spellcheck="false" onscroll="entrada.scrollTop=scrollTop"></textarea>
                            </div>
                          </div>
                        </div>
                  </div>
                </div>

                <div class="row row-cards mt-2">
                  <div class="col-md-12">
                    <div class="card">
                      <div class="card-body" id="erros">
                        <h2 class="card-title">Erros</h2>

                      </div>
                    </div>
                  </div>
                </div>
                
              </div>


            </div>
          </div>
        </div>



<script src="codemirror.js"></script>
<script src="simple.js.js"></script>
<script>
var defCats = "";

function carregarPalavras(){
  if ($('#entradaTipo').val()==0) return;
    if ($('#entradaTipo').val()=='escrita_nativa'){
        //add class customfont
        $('#entrada').addClass('custom-font');
        $('#saida').addClass('custom-font');
    }else{
        //remove class
        $('#entrada').removeClass('custom-font');
        $('#saida').removeClass('custom-font');
    }
    $.get("?action=ajaxCarregarListaPalavras&iid=<?=$id_idioma?>&id="
        + $('#listap').val()+"&t="+$('#entradaTipo').val(), function (data){
				$('#entrada').val($.trim(data));
        //$('.nomeListaSC').html( $('#listap option:selected').attr('text') );
		  });
}

function carregarLista(){
  if ($('#listasc').val()==0){
      editor.setValue('');//$('#schanges').val(''); 
      $('#descricao').val( '' );
      $('#btnApagarSC').hide( );
      $('#sel_motor_sc').val('sca2');
      tinymce.get('instrucoes').setContent(''); // $('#instrucoes').val( '' );
      // 
      //$(".chosen-select").trigger("chosen:updated");      
      return;
  }
  $.getJSON( "?action=ajaxCarregarListaSC&id="+ $('#listasc').val() , function(data){ 
    $.each( data, function( key, val ) {
          //$('#schanges').val(data[0].changes);
          editor.setValue(data[0].changes);
          tinymce.get('instrucoes').setContent(data[0].instrucoes); //$('#instrucoes').val(data[0].instrucoes);
          $('#sel_motor_sc').val(data[0].motor);
          //$(".chosen-select").trigger("chosen:updated");            
		});

    $('#descricao').val( $("#listasc option:selected").text() );
    //$('.nomeListaSC').html( '&nbsp;"'+$("#listasc option:selected").text()+'"' );
    $('#btnSalvarSC').hide( );
    $('#nomeLista').hide( );
    $('#btnApagarSC').show( ); 
    changeMotor();
	});
}

function apagarLista(){ 
  
    //xxxxx add confirmação
    if (confirm("<?=_t('Apagar esta lista de mudanças sonoras?')?>"))

    $.get("?action=ajaxApagarListaSC&id="+ $('#listasc').val(), function (data){
        if ($.trim(data)=='ok'){
            $('#btnApagarSC').hide();
            $('#btnSalvarSC').show();
            document.querySelector('#listasc').tomselect.removeOption( $('#listasc').val() ); //$('#listasc option[value="'+$('#listasc').val()+'"]').remove();
            document.querySelector('#listasc').tomselect.setValue(0); // $('#listasc').val(0);
            $('#descricao').val('');
            

        }else alert(data);
    });
}

function changeMotor(){ // return;
    $("#jslexurgy").hide();
    $("#rewrites").hide();
    $("#btnAplicar").show();
    if( $("#sel_motor_sc").val() == 'lexurgy' ) {
      //$("#jslexurgy").show();
    }else if( $("#sel_motor_sc").val() == 'sca2' ){
      $("#rewrites").show();
    }else if( $("#sel_motor_sc").val() == 'manual' ){
      $("#btnAplicar").hide();
    }
}

function salvarLista(){
    if (editor.getValue()=='') return;

    $('#div_listasc').hide();
    $('#nomeLista').show();
    $('#btnSalvarSC').hide( );
    $('#descricao').focus();
    //$('#btnNomearSC').show( );
}

function salvaSC(){
    if (editor.getValue()=='') return;
    if ($('#descricao').val()=='') return;

    if (confirm("<?=_t('Salvar?')?>")) {
    
      $.post("?action=ajaxSalvarListaSC&iid=<?=$id_idioma?>&id="+ $('#listasc').val(), {
          l: editor.getValue(),
          ins: tinymce.get('instrucoes').getContent(), //$('#instrucoes').val(),
          titulo: $('#descricao').val(),
          motor: $('#sel_motor_sc').val()
      }, function (data){
          //reload lista sc select, com listasc val = este salvo
          
          //xxxxx reload sclist mas do tomselect
          $("#listasc").empty().append( $.trim(data) );
          
          //$('#nomeSC').hide()
          //$('#btnNomearSC').hide( );
          $('#nomeLista').hide();
          $('#div_listasc').show();
      });
    }
}

function setInputPersonalizado(){
    $('#listap').val('g1');
}

function autoinsHeader(){
    //ler tipo motor
    //get headers pelo tipo do motor e id idioma
    //insere na posição do cursor, com quebra de linha antes e dps
    if ( $('#schanges').val() != '') {
      alert('A lista de mudanças deve estar vazia.');
      //toastr.warning('A lista de mudanças deve estar vazia.',{positionClass: "toast-bottom-right"});
      return;
    }
    
    $.get("?action=getSCHeader&iid=<?=$id_idioma?>&motor=ksc", function (data){
        //editor.insert("\n"+$.trim(data)+"\n");
        $('#schanges').val("\n"+$.trim(data)+"\n");
	  });
}

function loadDefCats(){
    $.get("?action=getSCHeader&iid=<?=$id_idioma?>&tipo=cats&motor=ksc", function (data){
        defCats = data;
        //$('#schanges').val("\n"+$.trim(data)+"\n");
	  });
}

formatarTablerSelect('listasc');

//loadTinyEditor('instrucoes');
document.addEventListener("DOMContentLoaded", function () {
    let options = {
      selector: '#instrucoes',
      //height: 300,
      menubar: false,
      statusbar: false,
      setup: (editor) => {
          editor.on('keyup', (e) => {
              $('#btnSalvarSC').show();$('#btnApagarSC').hide();$('#nomeLista').hide();
          });
      },
      plugins: [
        'advlist', 'autolink', 'lists', 'link', 'paste', 'table'
        //'advlist autolink lists link image charmap print preview anchor',
        //'searchreplace visualblocks code fullscreen',
        //'insertdatetime media table paste code help wordcount'
      ],
      toolbar: 'undo redo formatselect ' +
        'bold italic bullist numlist removeformat',
      content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }'
    }
    if (localStorage.getItem("tabler-theme") === 'dark') {
      options.skin = 'oxide-dark';
      options.content_css = 'dark';
    }
    tinyMCE.init(options);

    loadDefCats();

    const compactModeCheckbox = document.getElementById('compactModeCheckbox');
    if (compactModeCheckbox.checked) {
        toggleCompactMode(compactModeCheckbox);
    }
})

// Armazena as classes declaradas
let declaredClasses = [];

CodeMirror.defineSimpleMode("soundchanges", {
    start: [
        { regex: /^\s*}\s*$/, token: "rule-block-name" },
        { regex: /(\/\/|##).*?(?=\n|$)/, token: "comment" },
        { regex: /^\{\s*([a-zA-Z][a-zA-Z0-9]*)?\s*$/, token: "rule-block-name"/*, indent: true*/ },
        { regex: /(=>|>|\/|→|=)/, token: "rule-operator" },
        { regex: /(_|\#|\$|\$\d+|\?|\{|\}|\[|\]|\(|\)|\,|~|\.|\*|\||<|>|\+|\d|∅|ø|:)/, token: "rule-reserved" },
        { regex: /[1-9]/, token: "rule-digit" },
        { regex: /^(\p{Lu}\p{Ll}*)\s*=\s*(\{[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*\s*\}|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*)$/u, token: "rule-category" },
        { regex: /[\p{Ll}\u0250-\u02AF\u1D00-\u1DBF]+/u, token: "rule-sound" },
        { regex: /[A-Z]/, token: "rule-class" },
        { regex: /\s+/, token: null }
    ],
    meta: {
        lineComment: "//",
    }
});

// Overlay para destacar classes declaradas
CodeMirror.defineMode("classOverlay", function(config, parserConfig) {
    const baseMode = CodeMirror.getMode(config, parserConfig.base || "soundchanges");
    return {
        startState: function() {
            return baseMode.startState ? baseMode.startState() : {};
        },
        copyState: function(state) {
            return baseMode.copyState ? baseMode.copyState(state) : Object.assign({}, state);
        },
        token: function(stream, state) {
            // Tenta corresponder a uma palavra que pode ser uma classe
            const match = stream.match(/^\p{Lu}\p{Ll}*/u);
            if (match) {
                const word = match[0];
                if (declaredClasses.includes(word)) {
                    // Se for uma classe declarada, retorna rule-class-dynamic
                    return "rule-class-dynamic";
                }
                // Retrocede para permitir que o modo base processe o token
                stream.backUp(word.length);
            }
            // Processa o token com o modo base
            return baseMode.token ? baseMode.token(stream, state) : null;
        }
    };
});


// Inicializa o CodeMirror
const textarea = document.getElementById("schanges");
const editor = CodeMirror.fromTextArea(textarea, {
    mode: {
        name: "classOverlay",
        base: "soundchanges"
    },
    theme: "monokai",
    lineNumbers: true,
    tabSize: 2,

    //indentUnit: 2, // 2 espaços por nível de indentação
    indentWithTabs: false, // Usa espaços em vez de tabs
    //electricChars: true, // Habilita reindentação para }
    autoCloseBrackets: true // Fecha automaticamente parênteses, colchetes, etc.
});

// Função para atualizar a lista de classes declaradas
function updateDeclaredClasses(text) {
    declaredClasses = [];
    const categoryPattern =   /^\s*([%\p{Lu}\p{Ll}]*)\s*=\s*(\{[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*\s*\}|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*)\s*$/u;
    
    const classes = document.getElementById("text_classes").value.split('\n');

    classes.forEach(line => { 
        const match = line.match(categoryPattern);
        if (match) {
            const className = match[1]; // Ex.: X, Ng
            declaredClasses.push(className);
        }
    });

    const lines = text.split('\n');
    lines.forEach(line => {
        const match = line.match(categoryPattern);
        if (match) {
            const className = match[1]; // Ex.: X, Ng
            declaredClasses.push(className);
        }
    });

    editor.refresh();
    console.log("refreshed");
}

updateDeclaredClasses(editor.getValue());

let timeout;
editor.on("change", function(cm) {

  clearTimeout(timeout);
    timeout = setTimeout(() => {
        updateDeclaredClasses(cm.getValue());
        cm.refresh();
        $('#btnSalvarSC').show();
        $('#btnApagarSC').hide();
        $('#nomeLista').hide();
    }, 500); // Atraso de 300ms

});

function toggleCompactMode(checkbox) {
  const isChecked = checkbox.checked;
  const rulesContainer = document.querySelector('.rules-container');
  const annotationsContainer = document.querySelector('.annotations-container');
  const inputContainer = document.querySelector('.input-container');
  const outputContainer = document.querySelector('.output-container');
  const intermediateContainer = document.querySelector('.intermediate-container');
  //document.querySelector('input[onchange="$(\'.input_intermediate\').toggle()"]').checked = false; 
  if (isChecked) {
    // Mostrar Entrada e Resultados, ocultar Anotações, ajustar Regras
    rulesContainer.classList.remove('col-8');
    rulesContainer.classList.add('col-6');
    inputContainer.classList.remove('col-6');
    intermediateContainer.classList.remove('col-12');
    outputContainer.classList.remove('col-6');
    inputContainer.classList.add('col-3');
    intermediateContainer.classList.add('col-6');
    outputContainer.classList.add('col-3');
    annotationsContainer.style.display = 'none';
    inputContainer.style.display = 'block';
    intermediateContainer.style.display = 'none';
    outputContainer.style.display = 'block';
  } else {
    // Ocultar Entrada e Resultados, mostrar Anotações, reverter Regras
    rulesContainer.classList.remove('col-6');
    rulesContainer.classList.add('col-8');
    annotationsContainer.style.display = 'block';
    inputContainer.classList.remove('col-3');
    intermediateContainer.classList.remove('col-6');
    outputContainer.classList.remove('col-3');
    inputContainer.style.display = 'block';
    intermediateContainer.style.display = 'none';
    outputContainer.style.display = 'block';
    inputContainer.classList.add('col-6');
    intermediateContainer.classList.add('col-12');
    outputContainer.classList.add('col-6');
  }
};
// Sincronização de scroll entre entrada e saída
document.getElementById('entrada').addEventListener('scroll', function() {
    document.getElementById('saida').scrollTop = this.scrollTop;
});
document.getElementById('saida').addEventListener('scroll', function() {
    document.getElementById('entrada').scrollTop = this.scrollTop;
});
 

function aplicarMudancas(){
    
    const formData = new FormData();
    formData.append('palavras', document.getElementById('entrada').value);
    formData.append('regras', editor.getValue());
    formData.append('classes', document.getElementById('check_classes').checked ? document.getElementById('text_classes').value : 0);
    formData.append('substituicoes', document.getElementById('check_rewrites').checked ? document.getElementById('text_rewrites').value : 0);

    fetch(`?action=getKSC&iid=${<?=$id_idioma?>}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {

        document.getElementById('saida').value = data.words ? data.words.join('\n').trim() : '';
        document.getElementById('erros').innerHTML = '<h2 class="card-title">Erros</h2>';
        
        if (data.errors && Array.isArray(data.errors)) {
            data.errors.forEach(error => showError(error));
        }

        // Processar formas intermediárias
        const intermediateSteps = document.getElementById('intermediateSteps');
        intermediateSteps.innerHTML = '';

        // Adicionar textarea para Entrada
        let index = 0; 

        // Adicionar textareas para regras
        Object.keys(data.intermediate).forEach(key => {
            index++;
            if (index == 0) return;
            //if (key.startsWith('rules_rule_')) {
                const ruleKey = key;
                const ruleText = data.rules[ruleKey] || 'Regra desconhecida';
                intermediateSteps.innerHTML += `
                    <div class="intermediate-card">
                        <div class="intermediate-label">${ruleText}</div>
                        <textarea class="form-control ksc intermediate-textarea nowrap" readonly>${data.intermediate[key].join('\n')}</textarea>
                    </div>
                `;
            //}
        });

        // Sincronizar scroll de todas as textareas
        syncScrollTextareas();

    })
    .catch(error => {
        console.error('Erro:', error);
        showError('Erro ao processar a requisição: ' + error.message);
    });
    
};

function syncScrollTextareas() {
    const entrada = document.getElementById('entrada');
    const saida = document.getElementById('saida');
    const intermediateTextareas = document.querySelectorAll('.intermediate-textarea');

    const syncScroll = (source) => {
        const scrollTop = source.scrollTop;
        if (source !== entrada) entrada.scrollTop = scrollTop;
        if (source !== saida) saida.scrollTop = scrollTop;
        intermediateTextareas.forEach(textarea => {
            if (textarea !== source) textarea.scrollTop = scrollTop;
        });
    };

    entrada.addEventListener('scroll', () => syncScroll(entrada));
    saida.addEventListener('scroll', () => syncScroll(saida));
    intermediateTextareas.forEach(textarea => {
        textarea.addEventListener('scroll', () => syncScroll(textarea));
    });
}

function showError(erro){
  $("#erros").append(`<div class="alert alert-warning alert-dismissible" role="alert">
                          <div class="d-flex">
                            <div>`+erro+`</div>
                          </div>
                          <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>`);
}

$('[data-toggle="tooltip"]').tooltip();
</script>


<script>
function openRuleGenerator() {
    const inputDiv = document.getElementById('ruleGeneratorInput');
    inputDiv.style.display = inputDiv.style.display === 'none' ? 'block' : 'none';
    if (inputDiv.style.display === 'block') {
        document.getElementById('numRules').focus();
    }
}

// Listas de consoantes e vogais típicas
const typicalConsonants = ['p', 'b', 't', 'd', 'k', 'g', 'f', 'v', 's', 'z', 'ʃ', 'ʒ', 'θ', 'ð', 'x', 'ɣ', 'h', 'm', 'n', 'ŋ', 'l', 'r', 'j', 'w', 'ʧ', 'ʤ', 'c', 'ɟ', 'x', 'ɣ', 'β', 'ɾ'];
const typicalVowels = ['a', 'e', 'i', 'o', 'u', 'æ', 'ɛ', 'ɪ', 'ɔ', 'ʊ', 'ə', 'y', 'ø', 'ã', 'ẽ', 'ĩ', 'õ', 'ũ', 'ö', 'ü'];

// Processos fonológicos com transformações e contextos
const transformationProcesses = {
    sonorization: {
        weight: 0.15,
        description: "Converte consoantes surdas em sonoras",
        contexts: ['_V', 'V_', 'V_V', '{m,n,ŋ}_', 'C_V', '#_V', '{C}_V', 'V_{C}', '{C}{D}_', 'V_#'],
        transformations: {
            'p': ['b'],
            't': ['d'],
            'k': ['g'],
            'f': ['v'],
            's': ['z'],
            'ʃ': ['ʒ'],
            'θ': ['ð']
        }
    },
    dessonorization: {
        weight: 0.10,
        description: "Converte consoantes sonoras em surdas",
        contexts: ['_#', '_C', 'C_', 'V_C', '#_C', '{C}_#', '{C}_{C}', 'C_{C}', 'V_#', '{C}{D}_'],
        transformations: {
            'b': ['p'],
            'd': ['t'],
            'g': ['k'],
            'v': ['f'],
            'z': ['s'],
            'ʒ': ['ʃ'],
            'ð': ['θ']
        }
    },
    lenition: {
        weight: 0.10,
        description: "Enfraquece consoantes, geralmente intervocálico",
        contexts: ['V_V', '{C}_{D}', 'V_{C}', 'C_V', '{C}_V', 'V_#', '{C}{D}_', 'Cr_V', 'V_Cr', '#_{C}'],
        transformations: {
            'p': ['f', 'v', 'β'],
            't': ['θ', 'ð', 's', 'z', 'ɾ'],
            'k': ['x', 'ɣ'],
            'b': ['v', 'β'],
            'd': ['ð', 'ɾ'],
            'g': ['ɣ'],
            's': ['h'],
            'z': ['h']
        }
    },
    fortition: {
        weight: 0.10,
        description: "Fortalece consoantes, geralmente em onset",
        contexts: ['#_', '_V', '_{C}', '#_V', 'C_{C}', '{C}_V', '#_{C}{D}', 'V_{C}', '{C}{D}_', '_#'],
        transformations: {
            'v': ['b', 'f'],
            'ð': ['d', 'θ'],
            'ɣ': ['g', 'x'],
            'β': ['b'],
            'h': ['s', 'ʃ'],
            'j': ['ʤ'],
            'w': ['ɡʷ']
        }
    },
    fricativization: {
        weight: 0.08,
        description: "Converte consoantes oclusivas em fricativas",
        contexts: ['V_V', '{C}_{D}', 'V_{C}', 'Cr_V', '{C}_V', 'V_Cr', '{C}{D}_', 'V_#', '_C', 'C_'],
        transformations: {
            'p': ['f'],
            't': ['s', 'θ'],
            'k': ['x'],
            'b': ['v'],
            'd': ['z', 'ð'],
            'g': ['ɣ']
        }
    },
    palatalization: {
        weight: 0.08,
        description: "Desloca consoantes para articulações palatais",
        contexts: ['_{i}', '_{e}', 'V_i', 'V_e', '{j}_', 'i_V', '#_i', '{C}_i', '{C}{D}_', 'V_{C}i'],
        transformations: {
            't': ['tʃ', 'ʃ', 'ʧ', 'ʤ'],
            'd': ['dʒ', 'ʒ', 'ʤ'],
            'k': ['tʃ', 'ʃ', 'c', 'ʧ'],
            'g': ['dʒ', 'ʒ', 'ɟ', 'ʤ'],
            's': ['ʃ'],
            'z': ['ʒ']
        }
    },
    depalatalization: {
        weight: 0.05,
        description: "Converte consoantes palatais em não palatais",
        contexts: ['_a', '_o', '_u', 'a_', 'o_', 'u_', '{C}_a', 'V_{C}a', '{C}{D}_', 'V_#'],
        transformations: {
            'tʃ': ['t'],
            'dʒ': ['d'],
            'ʃ': ['s'],
            'ʒ': ['z']
        }
    },
    nasal_assimilation: {
        weight: 0.08,
        description: "Ajusta nasais ao ponto de articulação do som seguinte",
        contexts: ['_p', '_b', '_k', '_g', '_C', '{C}_{C}', 'V_{C}', 'C_{C}', '{C}{D}_', '#_C'],
        transformations: {
            'n': ['m', 'ŋ'],
            'm': ['n', 'ŋ'],
            'ŋ': ['m', 'n']
        }
    },
    place_assimilation: {
        weight: 0.07,
        description: "Ajusta o ponto de articulação de consoantes",
        contexts: ['_p', '_k', '_{tʃ}', 'C_p', 'C_k', '{C}_{tʃ}', '{C}{D}_', 'V_{C}', '#_p', '_#'],
        transformations: {
            't': ['p', 'k'],
            'd': ['b', 'g'],
            's': ['ʃ']
        }
    },
    rotacism: {
        weight: 0.05,
        description: "Converte sons em /r/",
        contexts: ['V_V', '_V', 'V_', 'C_V', '{C}_V', 'V_{C}', 'Cr_V', 'V_Cr', '{C}{D}_', 'V_#'],
        transformations: {
            'l': ['r'],
            'z': ['r'],
            's': ['r'],
            'd': ['r']
        }
    },
    lateralization: {
        weight: 0.05,
        description: "Converte sons em /l/",
        contexts: ['{C}_V', '_#', 'V_V', 'V_{C}', 'C_V', '{C}{D}_', 'V_l', '#_V', 'Cr_V', 'V_#'],
        transformations: {
            'r': ['l'],
            'ð': ['l']
        }
    },
    semivocalization: {
        weight: 0.05,
        description: "Converte vogais ou líquidas em semivogais",
        contexts: ['_V', 'V_', '#_V', 'C_V', '{C}_V', 'V_{C}', '{C}{D}_', 'V_#', 'Cr_V', 'V_Cr'],
        transformations: {
            'i': ['j'],
            'u': ['w'],
            'l': ['j'],
            'r': ['j']
        }
    },
    deletion: {
        weight: 0.10,
        description: "Remove sons, geralmente em contextos fracos",
        contexts: ['_#', '#_', '{C}_{D}', '_{C}', 'V_{C}', 'C_{C}', '{C}{D}_', 'V_Cr', 'Cr_V', 'V_#'],
        transformations: {
            'p': ['∅'],
            't': ['∅'],
            'k': ['∅'],
            's': ['∅'],
            'h': ['∅'],
            'a': ['∅'],
            'e': ['∅'],
            'b': ['∅'],
            'd': ['∅'],
            'g': ['∅'],
            'z': ['∅'],
            'f': ['∅'],
            'v': ['∅'],
            'm': ['∅'],
            'n': ['∅'],
            'l': ['∅'],
            'r': ['∅'],
            'i': ['∅'],
            'o': ['∅'],
            'u': ['∅']
        }
    },
    insertion: {
        weight: 0.07,
        description: "Insere sons, geralmente entre vogais ou consoantes",
        contexts: ['V_V', '_{C}', '{C}_{D}', '{C}_#', '#_{C}', '{C}{D}_', 'C_C', 'V_{C}r', 'Cr_V', 'V_{C}'],
        transformations: {
            '∅': ['j', 'w', 'h', 'ə']
        }
    },
    vowel_shift: {
        weight: 0.10,
        description: "Altera vogais em altura, abertura ou centralização",
        contexts: ['_{C}', 'C_', '#_', 'V_', '_V', '{C}_V', 'V_{C}', '#_V', '{C}{D}_', 'V_#'],
        transformations: {
            'a': ['æ', 'ɛ', 'ə'],
            'e': ['i', 'ɛ', 'ə'],
            'i': ['ɪ', 'e'],
            'o': ['u', 'ɔ', 'ə'],
            'u': ['ʊ', 'o'],
            'æ': ['a', 'ɛ'],
            'ɛ': ['e', 'a'],
            'ɪ': ['i', 'e'],
            'ɔ': ['o', 'u'],
            'ʊ': ['u', 'o']
        }
    },
    vowel_harmony: {
        weight: 0.05,
        description: "Ajusta vogais para harmonizar com vogais vizinhas",
        contexts: ['_i', '_e', 'i_', 'e_', '{C}_i', 'V_{C}i', '{C}{D}_', 'V_V', '#_i', 'V_#'],
        transformations: {
            'a': ['e', 'o'],
            'e': ['a', 'i'],
            'i': ['e', 'u'],
            'o': ['a', 'u', 'ö'],
            'u': ['o', 'i', 'ü']
        }
    },
    metathesis: {
        weight: 0.03,
        description: "Troca a ordem de sons adjacentes",
        contexts: ['V_', '_V', 'Cr_V', 'V_Cr', '{C}{D}_', 'C_{C}', '#_{C}', 'V_{C}r', '{C}_V', 'V_#'],
        transformations: {
            'st': ['ts'],
            'pr': ['rp'],
            'kr': ['rk']
        }
    },
    cluster_reduction: {
        weight: 0.05,
        description: "Simplifica grupos consonantais",
        contexts: ['_C', 'C_', 'Cr_V', 'V_Cr', '{C}_{C}', '{C}{D}_', '#_{C}', 'V_{C}r', 'C_{C}', 'V_#'],
        transformations: {
            'st': ['s', 't'],
            'kt': ['t'],
            'mp': ['m']
        }
    },
    epenthesis: {
        weight: 0.07,
        description: "Insere sons, geralmente para quebrar grupos consonantais",
        contexts: ['_C', 'C_', '{C}_{C}', '{C}{D}_', 'Cr_V', 'V_Cr', '#_{C}{D}', 'V_{C}r', 'C_C', '#_C'],
        transformations: {
            '∅': ['j', 'w', 'ə', 'i', 'u']
        }
    }
};

// Função para sortear com pesos
function weightedRandom(items, weights) {
    const totalWeight = weights.reduce((sum, w) => sum + w, 0);
    let random = Math.random() * totalWeight;
    for (let i = 0; i < items.length; i++) {
        random -= weights[i];
        if (random <= 0) return items[i];
    }
    return items[items.length - 1];
}

// Função para extrair classes declaradas
function getDeclaredClasses() {
    const classesText = document.getElementById('text_classes').value;
    const classes = {};
    const categoryPattern = /^\s*([%\p{Lu}\p{Ll}]*)\s*=\s*(\{[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*\s*\}|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*)\s*$/u;
    
    classesText.split('\n').forEach(line => {
        const match = line.match(categoryPattern);
        if (match) {
            const className = match[1];
            let sounds = match[2];
            if (sounds.startsWith('{') && sounds.endsWith('}')) {
                sounds = sounds.slice(1, -1).split(',').map(s => s.trim());
            } else {
                sounds = sounds.split(',').map(s => s.trim());
            }
            // Determinar tipo da classe
            let vowelCount = 0, consonantCount = 0;
            sounds.forEach(sound => {
                if (typicalVowels.includes(sound)) vowelCount++;
                if (typicalConsonants.includes(sound)) consonantCount++;
            });
            const total = sounds.length;
            const type = total > 0 && vowelCount > total / 2 ? 'vowel' : 
                        total > 0 && consonantCount > total / 2 ? 'consonant' : 'unknown';
            classes[className] = { sounds, type };
        }
    });
    return classes;
}

// Função para extrair classe oculta
function getHiddenClass() {
    const words = document.getElementById('entrada').value.split('\n').map(w => w.trim()).filter(w => w);
    return [...new Set(words.join('').split(''))];
}

// Verificar se letra pertence a uma classe
function isInClass(letter, className, classes) {
    return classes[className]?.sounds.includes(letter);
}

// Função para gerar regras aleatórias
// Função auxiliar para determinar o tipo de um som
// Função auxiliar para determinar o tipo de um som
function getSoundType(sound) {
    if (typicalVowels.includes(sound)) return 'vowel';
    if (typicalConsonants.includes(sound)) return 'consonant';
    return 'unknown';
}

// Função para gerar regras aleatórias
function generateRandomRules(numRules) {
    const classes = getDeclaredClasses();
    const hiddenClass = getHiddenClass();
    const classNames = Object.keys(classes);
    const possibleValues = [...classNames, ...hiddenClass];
    let generatedRules = [];

    if (!possibleValues.length) {
        showError("Erro: Nenhuma classe ou letra disponível para gerar regras.");
        return;
    }
    if (!hiddenClass.length && classNames.length) {
        showError("Erro: Nenhuma palavra fornecida na entrada, e apenas classes estão disponíveis.");
        return;
    }

    // Tipos de transformação com pesos
    const transformationTypes = [
        { type: 'sound_to_sound', weight: 0.4 },
        { type: 'class_to_class', weight: 0.2 },
        { type: 'class_to_sound', weight: 0.15 },
        { type: 'sound_to_deletion', weight: 0.15 },
        { type: 'class_to_deletion', weight: 0.05 },
        { type: 'empty_to_sound', weight: 0.05 }
    ];

    for (let i = 0; i < numRules; i++) {
        // Escolher processo fonológico
        const process = weightedRandom(Object.keys(transformationProcesses), Object.values(transformationProcesses).map(p => p.weight));
        const processData = transformationProcesses[process];

        // Escolher tipo de transformação
        const transType = weightedRandom(transformationTypes.map(t => t.type), transformationTypes.map(t => t.weight));

        // Escolher contexto
        let context = processData.contexts[Math.floor(Math.random() * processData.contexts.length)];
        if (!context && (transType === 'empty_to_sound' || process === 'insertion' || process === 'epenthesis')) {
            // Inserções e epêntese requerem contexto
            const validContexts = processData.contexts.filter(c => c.includes('_C') || c.includes('C_') || c.includes('{C}_{D}') || c.includes('{C}_') || c.includes('_{C}'));
            context = validContexts[Math.floor(Math.random() * validContexts.length)] || '_C';
        }

        let a, b;
        if (transType === 'sound_to_sound') {
            // A: letra da hiddenClass, B: letra do processo, diferente de A
            let attempts = 0;
            const maxAttempts = 100;
            do {
                a = hiddenClass[Math.floor(Math.random() * hiddenClass.length)];
                attempts++;
                if (processData.transformations[a] || attempts >= maxAttempts) {
                    break;
                }
            } while (true);
            if (attempts >= maxAttempts) {
                showError("Aviso: Usando letra avulsa para A na regra " + (i + 1));
                a = hiddenClass[Math.floor(Math.random() * hiddenClass.length)] || '∅';
                const aType = getSoundType(a);
                const validBOptions = hiddenClass.filter(h => h !== a && getSoundType(h) === aType);
                b = validBOptions.length > 0 ? validBOptions[Math.floor(Math.random() * validBOptions.length)] : 
                    (aType === 'vowel' ? typicalVowels.filter(v => v !== a)[0] : typicalConsonants.filter(c => c !== a)[0]) || '∅';
            } else {
                const bOptions = processData.transformations[a]?.filter(b => b !== a) || [];
                b = bOptions.length > 0 ? bOptions[Math.floor(Math.random() * bOptions.length)] : 
                    hiddenClass.filter(h => h !== a)[Math.floor(Math.random() * (hiddenClass.length - (hiddenClass.includes(a) ? 1 : 0)))] || '∅';
            }
        } else if (transType === 'class_to_class') {
            // A: classe, B: classe de mesmo tamanho e tipo, diferente de A
            let attempts = 0;
            const maxAttempts = 100;
            do {
                a = classNames[Math.floor(Math.random() * classNames.length)];
                attempts++;
                if (hiddenClass.some(letter => isInClass(letter, a, classes)) || attempts >= maxAttempts) {
                    break;
                }
            } while (true);
            if (attempts >= maxAttempts) {
                showError("Aviso: Usando letra avulsa para A na regra " + (i + 1));
                a = hiddenClass[Math.floor(Math.random() * hiddenClass.length)] || '∅';
                const aType = getSoundType(a);
                const validBOptions = hiddenClass.filter(h => h !== a && getSoundType(h) === aType);
                b = validBOptions.length > 0 ? validBOptions[Math.floor(Math.random() * validBOptions.length)] : 
                    (aType === 'vowel' ? typicalVowels.filter(v => v !== a)[0] : typicalConsonants.filter(c => c !== a)[0]) || '∅';
            } else {
                const classSize = classes[a]?.sounds.length;
                const aType = classes[a]?.type;
                const sameSizeClasses = classNames.filter(c => c !== a && classes[c].sounds.length === classSize && classes[c].type === aType);
                b = sameSizeClasses.length > 0 ? sameSizeClasses[Math.floor(Math.random() * sameSizeClasses.length)] : 
                    hiddenClass[Math.floor(Math.random() * hiddenClass.length)] || '∅';
                if (b in classes && !hiddenClass.some(letter => isInClass(letter, b, classes))) {
                    b = hiddenClass[Math.floor(Math.random() * hiddenClass.length)] || '∅';
                }
            }
        } else if (transType === 'class_to_sound') {
            // A: classe, B: letra do mesmo tipo (vogal ou consoante), diferente de A
            let attempts = 0;
            const maxAttempts = 100;
            do {
                a = classNames[Math.floor(Math.random() * classNames.length)];
                attempts++;
                if (hiddenClass.some(letter => isInClass(letter, a, classes)) || attempts >= maxAttempts) {
                    break;
                }
            } while (true);
            if (attempts >= maxAttempts) {
                showError("Aviso: Usando letra avulsa para A na regra " + (i + 1));
                a = hiddenClass[Math.floor(Math.random() * hiddenClass.length)] || '∅';
                const aType = getSoundType(a);
                const validBOptions = hiddenClass.filter(h => h !== a && getSoundType(h) === aType);
                b = validBOptions.length > 0 ? validBOptions[Math.floor(Math.random() * validBOptions.length)] : 
                    (aType === 'vowel' ? typicalVowels.filter(v => v !== a)[0] : typicalConsonants.filter(c => c !== a)[0]) || '∅';
            } else {
                const classSounds = classes[a]?.sounds || [];
                const aType = classes[a]?.type || 'unknown';
                let validBOptions = [];
                if (aType === 'vowel') {
                    validBOptions = hiddenClass.filter(h => !classSounds.includes(h) && typicalVowels.includes(h));
                    if (validBOptions.length === 0) {
                        validBOptions = typicalVowels.filter(v => !classSounds.includes(v));
                    }
                } else if (aType === 'consonant') {
                    validBOptions = hiddenClass.filter(h => !classSounds.includes(h) && typicalConsonants.includes(h));
                    if (validBOptions.length === 0) {
                        validBOptions = typicalConsonants.filter(c => !classSounds.includes(c));
                    }
                } else {
                    validBOptions = hiddenClass.filter(h => !classSounds.includes(h));
                    if (validBOptions.length === 0) {
                        validBOptions = typicalVowels.concat(typicalConsonants).filter(s => !classSounds.includes(s));
                    }
                }
                b = validBOptions.length > 0 ? validBOptions[Math.floor(Math.random() * validBOptions.length)] : 
                    (aType === 'vowel' ? typicalVowels[0] : typicalConsonants[0]) || 'a';
            }
        } else if (transType === 'sound_to_deletion') {
            // A: letra, B: ∅ (sempre diferente de A)
            a = hiddenClass[Math.floor(Math.random() * hiddenClass.length)] || 'p';
            b = '∅';
        } else if (transType === 'class_to_deletion') {
            // A: classe, B: ∅ (sempre diferente de A)
            let attempts = 0;
            const maxAttempts = 100;
            do {
                a = classNames[Math.floor(Math.random() * classNames.length)];
                attempts++;
                if (hiddenClass.some(letter => isInClass(letter, a, classes)) || attempts >= maxAttempts) {
                    break;
                }
            } while (true);
            if (attempts >= maxAttempts) {
                showError("Aviso: Usando letra avulsa para A na regra " + (i + 1));
                a = hiddenClass[Math.floor(Math.random() * hiddenClass.length)] || '∅';
            }
            b = '∅';
        } else if (transType === 'empty_to_sound') {
            // A: ∅, B: letra (sempre diferente de A)
            a = '∅';
            b = processData.transformations['∅']?.[Math.floor(Math.random() * processData.transformations['∅'].length)] || 
                hiddenClass[Math.floor(Math.random() * hiddenClass.length)] || 'a';
        }

        // Substituir {C} e {D} no contexto
        if (context.includes('{C}')) {
            const c = classNames[Math.floor(Math.random() * classNames.length)] || 'V';
            context = context.replace('{C}', c);
        }
        if (context.includes('{D}')) {
            const d = classNames[Math.floor(Math.random() * classNames.length)] || 'C';
            context = context.replace('{D}', d);
        }

        // Montar a regra
        const rule = context ? `${a} / ${b} / ${context}` : `${a} / ${b}`;

        // Garantir contexto para inserções e A ≠ B
        if (a === b || (a === '∅' && !context)) {
            continue;
        }

        generatedRules.push(rule);
    }

    // Inserir no CodeMirror
    const currentValue = editor.getValue();
    const newValue = /* currentValue ? currentValue + '\n' + generatedRules.join('\n') : */ generatedRules.join('\n');
    editor.setValue(newValue);
    updateDeclaredClasses(newValue);
    $('#btnSalvarSC').show();
    $('#btnApagarSC').hide();
    $('#nomeLista').hide();
}
</script>
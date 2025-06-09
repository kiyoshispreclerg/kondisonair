
<!-- PANEL START -->
<?php 
$id_idioma = $_GET['iid'];
if (!$id_idioma>0) $id_idioma = 0;
$rowsh = 20;
$idioma = array(); 
$idioma['nome_legivel'] = '';  
$result = mysqli_query($GLOBALS['dblink'],"SELECT *,
(SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab FROM idiomas i 
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

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.7/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.7/theme/monokai.min.css">

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
                <div class="btn-list" id="btnAplicar">
                  <a onclick="aplicarMudancas()" class="btn btn-primary d-none d-sm-inline-block">
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
                          <input class="form-check-input" type="checkbox" onchange="$('.row_palavras').toggle()">
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
                        <textarea class="form-control text_classes" id="text_classes" spellcheck="false" onchange="updateDeclaredClasses('')" style="height: 12rem !important;<?php if ($id_idioma > 0) echo 'display:none'; ?>"><?php 
                        echo getSCHeader('ksc',$id_idioma,'cats'); ?></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" onchange="$('.text_rewrites').toggle()" <?php if (!$id_idioma > 0) echo 'checked'; ?> id="check_rewrites">
                          <span class="form-check-label"><?=_t('Editar substituições')?></span>
                        </label>
                        <?php if ($id_idioma > 0){?><div class="text_rewrites"></div><?php } ?>
                        <textarea class="form-control text_rewrites" id="text_rewrites" spellcheck="false" style="height: 8rem !important;<?php if ($id_idioma > 0) echo 'display:none'; ?>"></textarea>
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
                                            $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM soundChanges WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX']." AND motor = 'ksc' ".
                                            ( $id_idioma > 0 ? " AND id_idioma = ".$id_idioma : "" ).";") or die(mysqli_error($GLOBALS['dblink']));
                                            if (mysqli_num_rows($langs)>0) echo '<option disabled>'._t('Listas de %1',[$idioma['nome_legivel']]).'</option>';
                                            while ($lang = mysqli_fetch_assoc($langs)){
                                                echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'">'.$lang['titulo'].'</option>';
                                            }
                                          }

                                          $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM soundChanges WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX']."  AND motor = 'ksc';") or die(mysqli_error($GLOBALS['dblink']));
                                          if (mysqli_num_rows($langs)>0) echo '<option disabled>'._t('Minhas listas').'</option>';
                                          while ($lang = mysqli_fetch_assoc($langs)){
                                              echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'">'.$lang['titulo'].'</option>';
                                          }

                                          $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM soundChanges WHERE publico = 1 AND motor = 'ksc';") or die(mysqli_error($GLOBALS['dblink']));
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
                              <textarea class="form-control ksc" id="schanges" spellcheck="false" style="height: 35rem" onkeyup="$('#btnSalvarSC').show();$('#btnApagarSC').hide();$('#nomeLista').hide();"></textarea>
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
                              <textarea class="form-control ksc" id="instrucoes" style="height: 35rem" spellcheck="false" onkeyup="$('#btnSalvarSC').show();$('#btnApagarSC').hide();$('#nomeLista').hide();"></textarea>
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
                              <textarea class="form-control ksc" id="entrada" onkeyup="setInputPersonalizado()" spellcheck="false" style="height: 35rem"
                              onscroll="saida.scrollTop=scrollTop"></textarea>
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
                              <textarea class="form-control ksc" id="saida" readonly="readonly" style="height: 35rem"
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

    //xxxxx usar motor padrao por idioma, ao menos nas flexões - ele será o que abre na tela de changer, mesmo estando todos disponiveis aqui
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
    //const categoryPattern =   /^(\p{Lu}\p{Ll}*)\s*=\s*(\{[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*\s*\}|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*)\s*$/u;
    const categoryPattern =   /^\s*([%\p{Lu}\p{Ll}]*)\s*=\s*(\{[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*\s*\}|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*)\s*$/u;
    //const categoryPattern =   /^([%\p{Lu}\p{Ll}]*)\s*=\s*([\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}']+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}']+)*|[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}']+)\s*$/u;

    const classes = document.getElementById("text_classes").value.split('\n');

    classes.forEach(line => { 
        const match = line.match(categoryPattern); console.log(match);
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

    //hiddenInput.value = cm.getValue();
    //onRulesChange(cm.getValue()); // Chama função personalizada

    /*
    const lines = content.split("\n").filter(line => line.trim() && !line.match(/^\s*(\/\/|#)/));
    const valid = lines.every(line => {
        return line.match(/^\s*([^=]*)\s*=>\s*([^\/]*)(?:\s*\/\s*[^\/]*)?(?:\s*\/\s*[^\/]*)?\s*$/);
    });
    alert(document.getElementById("validation-message").innerHTML = valid ? 
        "<?php echo _t("Regras válidas"); ?>" : 
        "<?php echo _t("Erro: Alguma regra está inválida"); ?>"
    );
    */
    
});

// Modifica a função do switch "Mostrar entrada e saída"
document.querySelector('input[onchange="$(\'.row_palavras\').toggle()"]').addEventListener('change', function() {
  const isChecked = this.checked;
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
});
// Sincronização de scroll entre entrada e saída
document.getElementById('entrada').addEventListener('scroll', function() {
    document.getElementById('saida').scrollTop = this.scrollTop;
});
document.getElementById('saida').addEventListener('scroll', function() {
    document.getElementById('entrada').scrollTop = this.scrollTop;
});
 

function aplicarMudancasPHP(){
    
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
                        <textarea class="form-control ksc intermediate-textarea" readonly>${data.intermediate[key].join('\n')}</textarea>
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



// Função para sincronizar scroll entre todas as textareas
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

</script>
<script src="ksc.js"></script>

<script>
function aplicarMudancasJS() {
    // Collect input data
    const palavras = document.getElementById('entrada').value;
    const regras = editor.getValue().split('\n').filter(line => line.trim());
    const v = document.getElementById('ksc2') ? document.getElementById('ksc2').checked ? 1 : 0 : 0;
    const classesInput = document.getElementById('check_classes').checked ? document.getElementById('text_classes').value : '';
    const substituicoesInput = document.getElementById('check_rewrites').checked ? document.getElementById('text_rewrites').value : '';

    // Parse classes
    const classes = {};
    if (classesInput) {
        classesInput.split('\n').forEach(line => {
            const match = line.match(/^(\p{Lu}\p{Ll}*)\s*=\s*(\{.*\}|[^,\s]+|.*,.*)$/u);
            if (match) {
                const [, className, classValue] = match;
                let characters;
                if (classValue.match(/^\{([^\}]*)\}\s*$/u)) {
                    characters = classValue.slice(1, -1).split(',').map(s => s.trim()).filter(s => s);
                } else if (classValue.includes(',')) {
                    characters = classValue.split(',').map(s => s.trim()).filter(s => s);
                } else {
                    characters = [...classValue];
                }
                classes[className] = characters;
            }
        });
    }

    // Parse substitutions
    const substituicoes = substituicoesInput ? substituicoesInput.split('\n').filter(line => line.trim()) : [];

    try {
        // Call applySoundChanges
        const [words, errors, { intermediate, rules }] = applySoundChanges(palavras, regras, substituicoes, classes);

        // Display output
        document.getElementById('saida').value = words ? (Array.isArray(words) ? words.join('\n').trim() : words.trim()) : '';
        document.getElementById('erros').innerHTML = '<h2 class="card-title">Erros</h2>';

        // Display errors
        if (errors && Array.isArray(errors)) {
            errors.forEach(error => showError(error));
        }

        // Process intermediate forms
        const intermediateSteps = document.getElementById('intermediateSteps');
        intermediateSteps.innerHTML = '';

        let index = 0;
        Object.keys(intermediate).forEach(key => {
            index++;
            if (index === 0) return;
            const ruleKey = key;
            const ruleText = rules[ruleKey] || 'Regra desconhecida';
            intermediateSteps.innerHTML += `
                <div class="intermediate-card">
                    <div class="intermediate-label">${ruleText}</div>
                    <textarea class="form-control ksc intermediate-textarea" readonly>${intermediate[key].join('\n')}</textarea>
                </div>
            `;
        });

        // Sync scroll of textareas
        syncScrollTextareas();
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro ao processar a requisição: ' + error.message);
    }
}


function aplicarMudancas(){
  aplicarMudancasPHP();
}

$('[data-toggle="tooltip"]').tooltip();

</script>
<?php
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

require('config.php');

if (!file_exists(DB_CONFIG)) {
    header('Location: install.php');
    exit;
}

mb_internal_encoding('UTF-8');
require(DB_CONFIG);
$maxSilabas = 10;
$separadorRomanizacao = " .,;:?!-()/";

error_reporting(E_ERROR);
$GLOBALS['dblink'] =  mysqli_connect($mysql_host, $mysql_user, $mysql_pass) or die('DATABASE: mysql_connect: ' . mysqli_error($GLOBALS['dblink']));
mysqli_select_db( $GLOBALS['dblink'], $mysql_db) or die('DATABASE: mysql_select_db: ' . mysqli_error($GLOBALS['dblink']));
mysqli_set_charset( $GLOBALS['dblink'],'utf8');

if (!isset($_SESSION)) session_start(); 
//header('Content-Type: text/html; charset=ISO-8859-1');

if (!isset($_SESSION['KondisonairUzatorIDX'])) $_SESSION['KondisonairUzatorIDX'] = 0;

if (isset($_COOKIE["KondisonairUzatorToken"])&&$_COOKIE["KondisonairUzatorToken"]) {
  $logged = mysqli_query($GLOBALS['dblink'],"SELECT * FROM usuarios WHERE token = '".$_COOKIE["KondisonairUzatorToken"]."';") or die(mysqli_error($GLOBALS['dblink']));
  $logged = mysqli_fetch_assoc($logged);

  $_SESSION['KondisonairUzatorIDX'] = $logged['id']; //$_COOKIE["KondisonairUzatorIDX"];
  $_SESSION['KondisonairUzatorNome'] = $logged['nome_completo']; //$_COOKIE["KondisonairUzatorNome"];
  $_SESSION['KondisonairUzatorID'] = $logged['username']; //$_COOKIE["KondisonairUzatorID"];
  $_SESSION['KondisonairUzatorDiom'] = $logged['id_idioma_nativo']; //$_COOKIE["KondisonairUzatorDiom"];
  $_SESSION['KondisonairUzatorNivle'] = $logged['acesso']; //$_COOKIE["KondisonairUzatorNivle"];
}

if (!isset($_SESSION['KondisonairUzatorIDX'])) session_destroy();

$page = '';
if (!isset($_GET['action'])) $_GET['action'] = '';
if (isset($_GET['page'])) $page = $_GET['page'];
if (!isset($_GET['gason'])) $_GET['gason'] = '';

$timerzinho = '2'; // segundos entre buscas complexas
$feedLimit = 10; // posts exibidos na lista de atividades recentes

$resop = mysqli_query($GLOBALS['dblink'],"SELECT * FROM opcoes_sistema;") or die(mysqli_error($GLOBALS['dblink']));
while($ro = mysqli_fetch_assoc($resop)) { 
    $opcoes[$ro['opcao']]  = $ro['valor'];
};
$defLang = $opcoes['def_lang'];
  
if($_GET['action']=='logout'){    
    setcookie("KondisonairUzatorToken", "", time() - 3600);
	  session_destroy();
    header('Location: index.php?page=login');//header('login/?token='.$token);
	//exit;
};

function userLoginAPI($usuario, $senha){
	  if (empty($senha) || empty($usuario) || strlen($usuario) > 80 || strlen($senha) > 255) {
        return false;
    }

    $s = mysqli_query($GLOBALS['dblink'],"SELECT nome_completo, id, id_idioma_nativo, username, acesso FROM usuarios WHERE username = '".$usuario."' OR email = '".$usuario."';");
    if( mysqli_num_rows($s) == 0 ){
      return false;
    }
    $b = mysqli_fetch_row($s);

    $r = mysqli_query($GLOBALS['dblink'],"SELECT senha, confirmacao FROM usuarios WHERE username = '".$b[3]."';");
    $a = mysqli_fetch_row($r);
    
    if( password_verify($senha, $a[0]) /*&& $a[1] == '1'*/ ) {
      if (!isset($_SESSION)) session_start();
      session_regenerate_id(true);
      $_SESSION['KondisonairUzatorID']            = trim($usuario);
      $_SESSION['KondisonairUzatorNome']          = trim($b[0]);
      $_SESSION['KondisonairUzatorIDX'] 			 = trim($b[1]);
      $_SESSION['KondisonairUzatorDiom'] 			 = trim($b[2]);
      $_SESSION['KondisonairUzatorNivle']         = trim($b[4]);
      $auth = [ 'auth' =>'true' ] ;

      $loginToken = bin2hex(random_bytes(64));
      mysqli_query($GLOBALS['dblink'],"UPDATE usuarios SET token = '".$loginToken."' WHERE id = '".$b[1]."';");

      echo json_encode($auth);

      setcookie("KondisonairUzatorToken",$loginToken,time()+60*60*24*30/*,"/","kondisonair"*/);
        
      return true;
    }else{
      return false;
    };
		
};

if($_GET['action']=='login'){
  if (userLoginAPI($_POST['usr'], $_POST['pass']) ) {
    header('Location: index.php');
  }else{
    header('Location: index.php?page=login');
  }
  die();
};

if($_GET['action']=='signup'){

  $r = mysqli_query($GLOBALS['dblink'],"SELECT * FROM opcoes_sistema WHERE opcao = 'inscr_aberta' AND valor = '1';");
	if ( mysqli_num_rows($r) == 0 ){
    header('Location: index.php?page=signup&error=closed');
    exit;
  };

  $name = $_POST['name'] ?? '';
  $usr = $_POST['usr'] ?? '';
  $email = $_POST['email'] ?? '';
  $profile = $_POST['profile'] ?? '';
  $pass = $_POST['pass'] ?? '';
  $uid = $_POST['uid'] ?? 0;

  if (empty($usr)) {
    header('Location: index.php?page=signup&error=empty');
    exit;
  }
  if (empty($pass)) {
    header('Location: index.php?page=signup&error=pass');
    exit;
  }
  if (empty($email)) {
    header('Location: index.php?page=signup&error=email');
    exit;
  }

  $stmt = mysqli_prepare($GLOBALS['dblink'], "SELECT email FROM usuarios WHERE email = ?");
  mysqli_stmt_bind_param($stmt, "s", $email);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  if (mysqli_num_rows($result) > 0) {
    header('Location: index.php?page=signup&error=email');
    exit;
  }
  mysqli_stmt_close($stmt);
  
  $stmt = mysqli_prepare($GLOBALS['dblink'], "SELECT username, email, id, senha FROM usuarios WHERE username = ?");
  mysqli_stmt_bind_param($stmt, "s", $usr);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  $gid = generateId();
  
  if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    if ($user['id'] > 10000 && empty($user['senha'])) { // tá no sistema por importação, não se cadastrou
      
      if (!empty($profile) && !empty($uid)) {

          if ($uid <= 10000 || $uid >= $gid) {
            header('Location: index.php?page=signup&error=profile&message=uid');
            exit;
          }

          if ($uid <= 10000) {
            header('Location: index.php?page=signup&error=profile&message=system');
            exit;
          }
      } else {
        header('Location: index.php?page=signup&error=profile');
        exit;
      }

    } else {
      header('Location: index.php?page=signup&error=usr');
      exit;
    }
  }
  mysqli_stmt_close($stmt);

  $uid = $uid == 0 ? $gid : $uid;

  $rkey = bin2hex(random_bytes(64));
  $stmt = mysqli_prepare($GLOBALS['dblink'], "INSERT INTO usuarios (username, id, senha, nome_completo, descricao, id_idioma_nativo, data_cadastro, acesso, email, confirmacao) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
  $desc = '';
  $id_idioma_nativo = 1;
  $acesso = 1;
  $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
  mysqli_stmt_bind_param($stmt, "sissisiss", $usr, $uid, $hashed_pass, $name, $desc, $id_idioma_nativo, $acesso, $email, $rkey);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  // Redirect to success (or send confirmation email)
  $usuario = $email;
  $r = mysqli_query($GLOBALS['dblink'],"SELECT username, id FROM usuarios WHERE email = '".$usuario."';");
  $b = mysqli_fetch_row($r);

  $url = $_SERVER['SERVER_NAME']; 
  $from = "kondisonair@kiyoshispreclerg.pip";

  $to = $usuario;
  $subject = "Cadastro Kondisonair";
  $message = "Olá, pessoa dessa realidade! Use o link a seguir para validar este e-mail e acessar o Kondisonair: ".$url."/login/api.php?validar=".$rkey."&email=".$usuario;
  $headers = "From: Kondisonair <" . $from . ">";
  //mail($to,$subject,$message, $headers);

  // autologin enquanto email nao funciona
  if (!isset($_SESSION)) session_start();
  $_SESSION['KondisonairUzatorID']            = trim($usuario);
  $_SESSION['KondisonairUzatorNome']          = trim($b[0]);
  $_SESSION['KondisonairUzatorIDX'] 			 = trim($b[1]);
  $_SESSION['KondisonairUzatorDiom'] 			 = trim($b[2]);
  $_SESSION['KondisonairUzatorNivle']         = 1;

  $loginToken = bin2hex(random_bytes(64));
  mysqli_query($GLOBALS['dblink'],"UPDATE usuarios SET token = '".$loginToken."' WHERE id = '".$b[1]."';");
  
  setcookie("KondisonairUzatorToken",$loginToken,time()+60*60*24*30/*,"/","kondisonair"*/);

  header('Location: index.php?page=confirmation');
  die();
}

if($_GET['action']=='validateMail'){
  
	$r = mysqli_query($GLOBALS['dblink'],"SELECT username, email FROM usuarios WHERE email = '".$_GET['email']."' AND confirmacao = '".$_GET['validar']."';");
	if(mysqli_num_rows($r) > 0){
		mysqli_query($GLOBALS['dblink'],"UPDATE usuarios SET confirmacao = '1' WHERE email = '".$_GET['email']."';");
	};
};

if($_GET['action']=='setLanguage'){
  if (!isset($_SESSION)) session_start(); // ?

  if ($_SESSION['KondisonairUzatorIDX']>0 && $_GET['i']>0)
      mysqli_query($GLOBALS['dblink'],"UPDATE usuarios SET id_idioma_nativo = ".$_GET['i']." 
          WHERE id = ".$_SESSION['KondisonairUzatorIDX'].";");

  $_SESSION['KondisonairUzatorDiom'] = $_GET['i'];

  //$redir = 'index.php'.substr($_SERVER['HTTP_REFERER'],strpos($_SERVER['HTTP_REFERER'],'?'));
  //header('Location: '.$redir);

  setcookie("KondisonairUzatorDiom",$_SESSION['KondisonairUzatorDiom'],time()+60*60*24*30/*,"/","kondisonair"*/);
  echo $_SESSION['KondisonairUzatorDiom'];
};
if (!$_SESSION['KondisonairUzatorDiom'] > 0) 
  $_SESSION['KondisonairUzatorDiom'] = $defLang; 

if (isset($_GET['lang']) && $_GET['lang'] > 0) { // ?
  if (!isset($_SESSION)) session_start(); 
  $_SESSION['KondisonairUzatorDiom'] = $_GET['lang']; 
  setcookie("KondisonairUzatorDiom",$_SESSION['KondisonairUzatorDiom'],time()+60*60*24*30/*,"/","kondisonair"*/);
}

require('lang.php');

if($page=='login'){
  require("views/login.php");
  die();
};
if($page=='signup'){
  require("views/signup.php");
  die();
};
if($page=='forgot'){
  require("views/forgot.php");
  die();
};
switch($page){
    case 'editsyllables': $tituloPagina .= ' - '._t('Sílabas'); break;
    case 'editlanguage': $tituloPagina .= ' - '._t('Meu idioma'); break;
    case 'changer': $tituloPagina .= ' - '._t('Alterador sonoro'); break;
    case 'wordgen': $tituloPagina .= ' - '._t('Gerador de palavras'); break;
    case 'editword': $tituloPagina .= ' - '._t('Palavra'); break;
    case 'editlexicon': $tituloPagina .= ' - '._t('Léxico'); break;
    case 'profile': $tituloPagina .= ' - '._t('Usuário'); break;
    case 'language': $tituloPagina .= ' - '._t('Idioma'); break;
    case 'text': $tituloPagina .= ' - '._t('Texto'); break;
    case 'texts': $tituloPagina .= ' - '._t('Textos'); break;
    case 'article': $tituloPagina .= ' - '._t('Artigo'); break;
    case 'settings': $tituloPagina .= ' - '._t('Minha conta'); break;
    case 'mylanguages': $tituloPagina .= ' - '._t('Meus idiomas'); break;
    case 'myarticles': $tituloPagina .= ' - '._t('Meus artigos'); break;
    case 'wordbanks': $tituloPagina .= ' - '._t('Bancos de palavras'); break;
    case 'changelog': $tituloPagina .= ' - '._t('Projeto'); break;
    case 'editwriting': $tituloPagina .= ' - '._t('Sistemas de escrita'); break;
    case 'editsyllables': $tituloPagina .= ' - '._t('Silabas'); break;
    case 'editsounds': $tituloPagina .= ' - '._t('Sons'); break;
    case 'editparts': $tituloPagina .= ' - '._t('Tipos de palavras'); break;
    case 'editarticle': $tituloPagina .= ' - '._t('Artigo'); break;
    case 'editinflections': $tituloPagina .= ' - '._t('Formas de palavras'); break;
    case 'editgenders': $tituloPagina .= ' - '._t('Gêneros'); break;
    case 'editforms': $tituloPagina .= ' - '._t('Formas'); break;
    case 'wordbank': $tituloPagina .= ' - '._t('Banco de palavras'); break;
    case 'myworlds': $tituloPagina .= ' - '._t('Minhas realidades'); break;
    case 'editworld': $tituloPagina .= ' - '._t('Realidade'); break;
    case 'edittimesystems': $tituloPagina .= ' - '._t('Calendários'); break;
    case 'editentities': $tituloPagina .= ' - '._t('Entidades'); break;
    case 'editentitytypes': $tituloPagina .= ' - '._t('Tipos de entidades'); break;
    case 'editstats': $tituloPagina .= ' - '._t('Desenvolvimentos'); break;
    case 'editmoments': $tituloPagina .= ' - '._t('Momentos'); break;
    case 'editstories': $tituloPagina .= ' - '._t('Histórias'); break;
    case 'editstory': $tituloPagina .= ' - '._t('História'); break;
    case 'referents': $tituloPagina .= ' - '._t('Referentes'); break;
    case 'glosses': $tituloPagina .= ' - '._t('Glosses'); break;
    case 'users': $tituloPagina .= ' - '._t('Usuários'); break;
    case 'ipa': $tituloPagina .= ' - '._t('Tabelas IPA'); break;
    case 'editentity': $tituloPagina .= ' - '._t('Entidade'); break;
    case 'wordgen': $tituloPagina .= ' - '._t('Gerador de palavras'); break;
    case 'offline': $tituloPagina .= ' - '._t('Offline'); break;
    case 'phrases': $tituloPagina .= ' - '._t('Frases'); break;
    case 'editphrase': $tituloPagina .= ' - '._t('Frase'); break;
    case 'phrase': $tituloPagina .= ' - '._t('Frase'); break;
    case 'changer_full': $tituloPagina .= ' - '._t('Outros alteradores'); break;
    case 'word': $tituloPagina .= ' - '._t('Palavra'); break;
    case 'editwordusage': $tituloPagina .= ' - '._t('Níveis de uso'); break;
    case 'paradigmer': $tituloPagina .= ' - '._t('Gerador de paradigma'); break;
    case 'wordcompare': $tituloPagina .= ' - '._t('Comparador de palavras'); break;
    case 'masseditlexicon': $tituloPagina .= ' - '._t('Edição em massa de palavras'); break;

    default: $tituloPagina .= ' - '._t('Início'); $page = '';
}

function multiexplode ($delimiters,$string) { 
  $ready = str_replace($delimiters, $delimiters[0], $string);
  $launch = explode($delimiters[0], $ready);
  return  $launch;
};

function separarPalavrasLinha($delimitadores, $texto, $idioma, $eid, $bin, $fonte, $tamanho) {

    if ($fonte == 3 ) return [explode(",",$texto)];
    return [multiexplode($delimitadores,$texto)];
}

function linkAcao($idusuario,$usuario,$tipo,$iddestino,$destino,$t,$data){

  $verbo = $t=='0' ? _t('inseriu') : _t('atualizou');
  switch ($tipo) {
    case 'diom':
      $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = ".$iddestino.";") or die(mysqli_error($GLOBALS['dblink']));
      $r0 = mysqli_fetch_assoc($res0);
      if(isset($r0['publico']) && $r0['publico']==1) 
        $nome = '<tr><td><div><a href="?action=person&uid='.$idusuario.'">@'.$usuario.'</a> '.$verbo.' '._t('o idioma').' <a href="?action=diom&iid='
          .$iddestino.'">'.$destino.'</a> <small class="pull-right"> '.$data.'</small></div></td></tr>';
      break;
    case 'palavr':
      $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = (SELECT id_idioma FROM palavras WHERE id = ".$iddestino." LIMIT 1);") or die(mysqli_error($GLOBALS['dblink']));
      $r0 = mysqli_fetch_assoc($res0);
      if(isset($r0['publico']) && $r0['publico']==1) 
        $nome = '<tr><td><div><a href="?action=person&uid='.$idusuario.'">@'.$usuario.'</a> '.$verbo.' '._t('a palavra').' <a onclick="painelAuxiliar(\'palavr\','.
          $iddestino.')">'.$destino.'</a> <small class="pull-right"> '.$data.'</small></div></td></tr>';
      break;
    case 'skreveson':
      $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = (SELECT id_idioma FROM escritas WHERE id = ".$iddestino." LIMIT 1);") or die(mysqli_error($GLOBALS['dblink']));
      $r0 = mysqli_fetch_assoc($res0);
      if(isset($r0['publico']) && $r0['publico']==1) 
        $nome = '<tr><td><div><a href="?action=person&uid='.$idusuario.'">@'.$usuario.'</a> '.$verbo.' '._t('a escrita').' <a href="?action=skreveson&eid='.
          $iddestino.'">'.$destino.'</a> <small class="pull-right"> '.$data.'</small></div></td></tr>';
      break;
    case 'frase':
      $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = (SELECT id_idioma FROM frases WHERE id = ".$iddestino." LIMIT 1);") or die(mysqli_error($GLOBALS['dblink']));
      $r0 = mysqli_fetch_assoc($res0);
      if(isset($r0['publico']) && $r0['publico']==1) 
        $nome = '<tr><td><div><a href="?action=person&uid='.$idusuario.'">@'.$usuario.'</a> '.$verbo.' '._t('a frase').' <a href="?action=phrase&id='.
          $iddestino.'">'.$destino.'</a> <small class="pull-right"> '.$data.'</small></div></td></tr>';
      break;
  }

  return $nome;
};

function linkData($idusuario,$usuario,$tipo,$iddestino,$destino,$t,$data){

  $verbo = $t=='0' ? _t('inseriu') : _t('atualizou');
  $dados = [];
  switch ($tipo) {
    case 'diom':
      $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = ".$iddestino.";") or die(mysqli_error($GLOBALS['dblink']));
      $r0 = mysqli_fetch_assoc($res0);
      if(isset($r0['publico']) && $r0['publico']==1) {
        $dados['uid'] = $idusuario;
        $dados['uname'] = $usuario;
        $dados['text'] = $verbo.' '._t('o idioma').' ';
        $dados['date'] = $data;
        $dados['link'] = '?action=diom&iid='.$iddestino;
        $dados['ltitle'] = $destino;
      }
      break;
    case 'palavr':
      $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = (SELECT id_idioma FROM palavras WHERE id = ".$iddestino." LIMIT 1);") or die(mysqli_error($GLOBALS['dblink']));
      $r0 = mysqli_fetch_assoc($res0);
      if(isset($r0['publico']) && $r0['publico']==1) {
        
        $dados['uid'] = $idusuario;
        $dados['uname'] = $usuario;
        $dados['text'] = $verbo.' '._t('a palavra').' ';
        $dados['date'] = $data;
        $dados['link'] = 'onclick="painelAuxiliar(\'palavr\','.$iddestino.')"';
        $dados['ltitle'] = $destino;
      }
      break;
    case 'skreveson':
      $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = (SELECT id_idioma FROM escritas WHERE id = ".$iddestino." LIMIT 1);") or die(mysqli_error($GLOBALS['dblink']));
      $r0 = mysqli_fetch_assoc($res0);
      if(isset($r0['publico']) && $r0['publico']==1) {
        
        $dados['uid'] = $idusuario;
        $dados['uname'] = $usuario;
        $dados['text'] = $verbo.' '._t('a escrita').' ';
        $dados['date'] = $data;
        $dados['link'] = '?action=skreveson&eid='.$iddestino;
        $dados['ltitle'] = $destino;
      }
      break;
    case 'frase':
      $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = (SELECT id_idioma FROM frases WHERE id = ".$iddestino." LIMIT 1);") or die(mysqli_error($GLOBALS['dblink']));
      $r0 = mysqli_fetch_assoc($res0);
      if(isset($r0['publico']) && $r0['publico']==1) {
        
        $dados['uid'] = $idusuario;
        $dados['uname'] = $usuario;
        $dados['text'] = $verbo.' '._t('a frase').' ';
        $dados['date'] = $data;
        $dados['link'] = '?action=phrase&id='.$iddestino;
        $dados['ltitle'] = $destino;
      }
      break;
  }

  return $dados;
};

function logAcao($tipo,$destino,$iddestino){
  mysqli_query($GLOBALS['dblink'],
    "INSERT INTO asons SET 
      id_usuario = ".$_SESSION['KondisonairUzatorIDX'].",
      tipo_destino = '".$destino."',
      id_destino = ".$iddestino.",
      tipo = ".$tipo.",
      id = ".generateId().";"
  ) or die(mysqli_error($GLOBALS['dblink']));
  return;
}

function chottomatte($sec){
  if (!isset($_SESSION['last_request'])) $_SESSION['last_request'] = $sec;
  if ($_SESSION['last_request'] && time() - $_SESSION['last_request'] > $sec){
    $_SESSION['last_request'] = time();
    return true;
  }else{
    return false;
  }
}

function getStatus($s){
  $r = '';
  switch ($s) {
    case 0: $r = _t('Projetando'); break;

    case 1: $r = _t('Em construção'); break;
    case 3: $r = _t('Base inicial'); break;
    case 7: $r = _t('Básica'); break;
    case 9: $r = _t('Usável');
  }
  return $r;
};

function textoParaArrayClasses($texto) {
  // Garante que o ambiente PHP usa UTF-8
  mb_internal_encoding('UTF-8');
  
  // Inicializa o array de resultado
  $resultado = [];
  
  // Divide o texto em linhas
  $linhas = explode("\n", trim($texto, "\n\r\t "));
  
  // Processa cada linha
  foreach ($linhas as $linha) {
      // Ignora linhas vazias
      if (empty(trim($linha))) {
          continue;
      }
      
      // Divide a linha em chave e valor pelo "="
      $partes   = preg_split('/\s*=\s*/u', $linha, 2); 
      //$partes = preg_split('/=/u', $linha, 2);
      if (count($partes) !== 2) {
          continue; // Ignora linhas mal formatadas
      }
      
      // Extrai a chave (primeira letra) e o valor
      $chave = trim($partes[0]);
      $valor = trim($partes[1]);
      
      // /^\p{Lu}\p{Ll}*$/u
      if   (!preg_match('/^\p{Lu}\p{Ll}*$/u', $chave)) {
      //if (!preg_match('/^\p{L}$/u', $chave)) {
          continue; // Ignora chaves inválidas
      }
      
      // Converte o valor em um array de caracteres Unicode
      if (preg_match('/^\{([^\}]*)\}\s*$/u', $valor, $listMatches)) {
          // Formato {p, t, c} ou {gw, kw}: divide por vírgulas, remove espaços
          $caracteres = array_map('trim', explode(',', $listMatches[1]));
          $caracteres = array_filter($caracteres); // Remove elementos vazios
      } elseif (mb_strpos($valor,",")>0){ //(preg_match('/^\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+(?:\s*,\s*[\p{L}\u0250-\u02AF\u1D00-\u1DBF\p{M}]+)*\s*$/u', $valor)) {
          // Formato a,b,c: divide por vírgulas, remove espaços
          $caracteres = array_map('trim', explode(',', $valor));
          $caracteres = array_filter($caracteres); // Remove elementos vazios
      } else {
          // Formato ptc ou kwgw: divide em caracteres individuais
          $caracteres = preg_split('//u', $valor, -1, PREG_SPLIT_NO_EMPTY);
      }
      // $caracteres = preg_split('//u', $valor, -1, PREG_SPLIT_NO_EMPTY);
      
      // Adiciona ao resultado
      $resultado[$chave] = $caracteres;
  }
  
  return $resultado;
}

function textoParaArraySubstituicoes($texto) {
    // Garante que o ambiente PHP usa UTF-8
    mb_internal_encoding('UTF-8');
      
    // Inicializa o array de resultado
    $linhas = [];
    
    // Divide o texto em linhas
    $linhas_texto = explode("\n", trim($texto, "\n\r\t "));
    
    // Processa cada linha
    foreach ($linhas_texto as $linha) {
        // Ignora linhas vazias
        if (empty(trim($linha))) {
            continue;
        }
        
        // Divide a linha em chave e valor por "|" ou "=>"
        $partes = preg_split('/(\||=>)/u', $linha, 2, PREG_SPLIT_NO_EMPTY);
        if (count($partes) !== 2) {
            continue; // Ignora linhas mal formatadas
        }
        
        // Extrai chave e valor, removendo espaços
        $chave = trim($partes[0]);
        $valor = trim($partes[1]);
        
        // Ignora chaves vazias
        if (empty($chave)) {
            continue;
        }
        
        // Formata a linha como "chave => valor"
        $linhas[] = "$chave => $valor";
    }
    
    return $linhas;
}

function getWordGenConfig($iid) { // grok test   //xxxxx faltando substrituições e restrições
  global $dblink; // Assume conexão com o banco de dados

  // Inicializa arrays de retorno
  $classes = [];
  $syllableFormats = [[], [], [], []]; // [iniciais, mediais, finais, monossílabas]

  // Busca número máximo de sílabas do idioma
  $query = "SELECT silabas FROM idiomas WHERE id = ? LIMIT 1";
  $stmt = mysqli_prepare($dblink, $query);
  mysqli_stmt_bind_param($stmt, 'i', $iid);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  $maxSyllables = $row['silabas'] ?? 1;
  mysqli_stmt_close($stmt);

  // Busca classes de sons e seus sons associados
  $query = "SELECT cs.id, cs.simbolo FROM classesSom cs WHERE cs.id_idioma = ?";
  $stmt = mysqli_prepare($dblink, $query);
  mysqli_stmt_bind_param($stmt, 'i', $iid);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  while ($class = mysqli_fetch_assoc($result)) {
      $classId = $class['id'];
      $classSymbol = $class['simbolo'];
      $classes[$classSymbol] = [];

      // Busca sons padrão associados à classe
      $query = "SELECT s.ipa, i.peso, t.tecla FROM inventarios i
                LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
                LEFT JOIN sons_classes sc ON (sc.tipo = 1 AND i.id = sc.id_som)
              LEFT JOIN teclas t ON (t.id_inventario = i.id)
                WHERE sc.id_classeSom = ? AND i.id_idioma = ?";
      $stmt2 = mysqli_prepare($dblink, $query);
      mysqli_stmt_bind_param($stmt2, 'ii', $classId, $iid);
      mysqli_stmt_execute($stmt2);
      $result2 = mysqli_stmt_get_result($stmt2);
      while ($row2 = mysqli_fetch_assoc($result2)) {
          if ($row2['ipa']) {
              $classes[$classSymbol][] = ['sound' => $row2['ipa'], 'weight' => $row2['peso'], 'key' => $row2['tecla']];
          }
      }
      mysqli_stmt_close($stmt2);

      // Busca sons personalizados associados à classe
      $query = "SELECT p.ipa, i.peso, t.tecla FROM inventarios i
                LEFT JOIN sonsPersonalizados p ON (p.id = i.id_som AND i.id_tipoSom = 0)
                LEFT JOIN sons_classes sc ON (sc.tipo = 2 AND i.id = sc.id_som)
              LEFT JOIN teclas t ON (t.id_inventario = i.id)
                WHERE sc.id_classeSom = ? AND i.id_idioma = ?";
      $stmt2 = mysqli_prepare($dblink, $query);
      mysqli_stmt_bind_param($stmt2, 'ii', $classId, $iid);
      mysqli_stmt_execute($stmt2);
      $result2 = mysqli_stmt_get_result($stmt2);
      while ($row2 = mysqli_fetch_assoc($result2)) {
          if ($row2['ipa']) {
              $classes[$classSymbol][] = ['sound' => $row2['ipa'], 'weight' => $row2['peso'], 'key' => $row2['tecla']];
          }
      }
      mysqli_stmt_close($stmt2);
  }
  mysqli_stmt_close($stmt);

  // Busca formatos de sílabas
  $query = "SELECT fs.id, fs.tipo, fs.peso FROM formasSilaba fs WHERE fs.id_idioma = ? ORDER BY fs.tipo";
  $stmt = mysqli_prepare($dblink, $query);
  mysqli_stmt_bind_param($stmt, 'i', $iid);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  $generalFormats = [];
  while ($form = mysqli_fetch_assoc($result)) {
      $formId = $form['id'];
      $formType = $form['tipo']; // 0=geral, 1=inicial, 2=medial, 3=final, 4=monossílaba

      // Busca componentes do formato
      $query = "SELECT fsc.obrigatorio, cs.simbolo FROM formaSilabaComponente fsc
                LEFT JOIN classesSom cs ON (fsc.id_classeSom = cs.id)
                WHERE fsc.id_formaSilaba = ? ORDER BY fsc.ordem";
      $stmt2 = mysqli_prepare($dblink, $query);
      mysqli_stmt_bind_param($stmt2, 'i', $formId);
      mysqli_stmt_execute($stmt2);
      $result2 = mysqli_stmt_get_result($stmt2);

      $components = [];
      while ($comp = mysqli_fetch_assoc($result2)) {
          if ($comp['simbolo']) {
              $components[] = [
                  'symbol' => $comp['simbolo'],
                  'required' => $comp['obrigatorio']
              ];
          }
      }
      mysqli_stmt_close($stmt2);

      // Gera formatos considerando componentes opcionais
      $formats = generateSyllableFormats($components);
      //echo $formType; print_r($formats);

      // Adiciona formatos ao tipo correspondente
      if ($formType >= 1 && $formType <= 4) {
          $targetArray = &$syllableFormats[$formType - 1]; // Referência direta
      } else {
          $targetArray = &$generalFormats; // Referência direta
      }
      foreach ($formats as $format) {
          $targetArray[] = ['format' => $format, 'weight' => $form['weight'] ?? 1];
      }
      /*
      $targetArray = ($formType >= 1 && $formType <= 4) ? $syllableFormats[$formType - 1] : $generalFormats;
      foreach ($formats as $format) {
          $targetArray[] = ['format' => $format, 'weight' => $form['peso']];
      }
      */

      // Se for formato geral, adiciona a todos os tipos não especificados
      if ($formType == 0) {
          for ($i = 0; $i < 4; $i++) {
              if (empty($syllableFormats[$i])) {
                  $syllableFormats[$i] = $generalFormats;
              }
          }
      }
      //print_r($syllableFormats); echo "+++++";
  }
  mysqli_stmt_close($stmt);

  // Se nenhum formato específico foi definido, usa um padrão
  if (empty($generalFormats) && empty($syllableFormats[0]) && empty($syllableFormats[1]) &&
      empty($syllableFormats[2]) && empty($syllableFormats[3])) {
      $syllableFormats = [
          [['format' => 'CV', 'weight' => 1]], // Iniciais
          [['format' => 'CV', 'weight' => 1]], // Mediais
          [['format' => 'CV', 'weight' => 1]], // Finais
          [['format' => 'CV', 'weight' => 1]]  // Monossílabas
      ];
  }

  //xxxxx 
  $substitutions = [];
  $restrictions = [];

  return [
      'classes' => $classes,
      'syllableFormats' => $syllableFormats,
      'silabas' => $maxSyllables,
      'substitutions' => $substitutions,
      'restrictions' => $restrictions
  ];
};

function generateSyllableFormats($components) {
  $formats = [''];
  foreach ($components as $index => $comp) {
      $symbol = $comp['symbol'];
      $required = $comp['required'];
      
      // Debug: Log do componente atual
      error_log("Processando componente $index: symbol=$symbol, required=$required");

      $newFormats = [];
      if ($required) {
          foreach ($formats as $format) {
              $newFormats[] = $format . $symbol;
          }
      } else {
          foreach ($formats as $format) {
              $newFormats[] = $format; // Mantém sem o símbolo
              $newFormats[] = $format . $symbol; // Adiciona com o símbolo
          }
      }
      
      // Debug: Log dos formatos gerados
      error_log("Formatos após componente $index: " . implode(', ', $newFormats));
      
      $formats = $newFormats;
  }
  
  // Remove duplicatas e strings vazias
  $formats = array_unique(array_filter($formats));
  
  // Debug: Log final
  error_log("Formatos finais: " . implode(', ', $formats));
  //print_r($formats);
  return $formats;
};

function listarSonsAdicionaveis($iid, $id) {
  $echo = '<div class="form-selectgroup">';

  // Consulta combinada com UNION
  $query = "
      SELECT i.id, i.id_som, s.nome, s.ipa, 1 AS tipo
      FROM inventarios i
      LEFT JOIN sons s ON (i.id_som = s.id)
      WHERE i.id_idioma = $iid AND i.id_tipoSom > 0
      UNION
      SELECT s.id, NULL AS id_som, s.nome, s.ipa, 2 AS tipo
      FROM sonsPersonalizados s
      WHERE s.id_idioma = $iid
  ";
  $query = "
        SELECT i.id, i.id_som, s.nome, s.ipa, t.tecla, 1 AS tipo
        FROM inventarios i
        LEFT JOIN sons s ON (i.id_som = s.id)
        LEFT JOIN teclas t ON (t.id_inventario = i.id)
        WHERE i.id_idioma = $iid AND i.id_tipoSom > 0
        UNION
        SELECT s.id, NULL AS id_som, s.nome, s.ipa, NULL AS tecla, 2 AS tipo
        FROM sonsPersonalizados s
        WHERE s.id_idioma = $iid
    ";

  $result = mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));

  while ($r = mysqli_fetch_assoc($result)) {
      // Verifica se o som está associado na tabela sons_classes
      $c = mysqli_query($GLOBALS['dblink'], "SELECT * FROM sons_classes 
          WHERE id_classeSom = $id AND id_som = " . $r['id'] . " AND tipo = " . $r['tipo']) or die(mysqli_error($GLOBALS['dblink']));
      
      // Monta o HTML com base na existência do registro em sons_classes
      if (mysqli_num_rows($c) > 0) {
          $echo .= "<a class='form-selectgroup-item btn btn-primary' title='" . htmlspecialchars($r['nome']) . "' onClick='toggleSom(" . $r['id'] . "," . $r['tipo'] . ",$id)'>" . ( $r['tecla']!='' ? htmlspecialchars($r['tecla'])." /".htmlspecialchars($r['ipa'])."/" : htmlspecialchars($r['ipa']) ) . "</a>";
      } else {
          $echo .= "<a class='form-selectgroup-item btn' title='" . htmlspecialchars($r['nome']) . "' onClick='toggleSom(" . $r['id'] . "," . $r['tipo'] . ",$id)'>" . ( $r['tecla']!='' ? htmlspecialchars($r['tecla'])." /".htmlspecialchars($r['ipa'])."/" : htmlspecialchars($r['ipa']) ) . "</a>";
      }
  }

  $echo .= '</div>';

  return $echo;
}

function CYKArrayToTable($a){
  $res =  '<table>';
  foreach ($a as $row) {
    $res .= '<tr>';
      foreach ($row as $val) {
        $res .= '<td style="border:solid 1px black">';
        $res .=  json_encode($val);
        $res .= '&nbsp;</td>'; // htmlspecialchars($val)
      }
      $res .= '<tr>';
  }
  $res .= '</table>';
  return $res;
};

function CYKArrayToBrackets($T){
  $res =  '';
  $n = sizeof($T);

  $trees = array();

  $ret = '';

  // find start nodes
  for ($i = 0; $i < $n; $i++) {
    for ($j = 0; $j < $n; $j++) {
      //$res .= sizeof($T[$i][$j]['p']).' ';
      if (sizeof($T[$i][$j]['p'])==0 && sizeof($T[$i][$j]['i']) > 0) {
        // é nó!
        $res .= '['; $x = 0;
        foreach($T[$i][$j]['d1'] as $d1){
          array_push($trees, $T[$i][$j]['t'][$x]); 
          //$ret .= $T[$i][$j]['t'][$x];
          $x++;
        }
        $res .= ']';
      }
    }
  }

  // drawing
  return $trees;
  
};

function separarPalavras($texto,$idioma,$entrada) {

  $separador = ' '; // pegar nas regras, em ordem, os separadores, e ir aplicando
  $temp = explode($separador,$texto);
  $final = [];
  for($i = 0; $i < sizeof($temp) ; $i++){

    $r = soltarContracao($temp[$i],$entrada,$idioma);
    foreach($r as $palavra){
      array_push($final,$palavra);
    }

  };
  return $final;
};

function cykParse($w,$idioma,$entrada) { // URGENT otimizar sql queries

  $R = array();

  
  $query = "SELECT b.*, g.gloss FROM blocos b 
          LEFT JOIN glosses g ON b.id_gloss = g.id 
          WHERE b.id_idioma = ".$idioma." order by b.ordem;";
  $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
  while($r = mysqli_fetch_assoc($result)){
    $R[$r['gloss']] = [];
  };
  
  
  $cs = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM classes c 
		LEFT JOIN glosses g ON g.id = c.id_gloss WHERE c.id_idioma = ".$idioma." GROUP BY g.gloss;") or die(mysqli_error($GLOBALS['dblink']));
  while ($c = mysqli_fetch_assoc($cs)){
    $R[$c['gloss']] = [];
  }

  
  $query = "SELECT b.*, g.gloss FROM blocos b 
          LEFT JOIN glosses g ON b.id_gloss = g.id 
          WHERE b.id_idioma = ".$idioma." order by b.ordem;";
  $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
  while($r = mysqli_fetch_assoc($result)){

    if($r['tipo_nucleo'] == 'classe') {
      $query = "SELECT c.id, c.descricao as title, g.gloss, c.nome
        FROM classes c 
        LEFT JOIN glosses g ON c.id_gloss = g.id 
        WHERE c.id = ".$r['id_nucleo'].";";
    }else if($r['tipo_nucleo'] == 'bloco') {
      $query = "SELECT b.id, b.descricao as title, g.gloss, b.nome 
        FROM blocos b
        LEFT JOIN glosses g ON b.id_gloss = g.id 
        WHERE b.id = ".$r['id_nucleo'].";";
    }
    $langs = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    $n = mysqli_fetch_assoc($langs);
    
    if($r['tipo_dependente'] == 'classe') {
      $query = "SELECT c.id, c.descricao as title, g.gloss, c.nome
          FROM classes c 
          LEFT JOIN glosses g ON c.id_gloss = g.id 
          WHERE c.id = ".$r['id_dependente'].";";
    }else if($r['tipo_dependente'] == 'bloco') {
      $query = "SELECT b.id, b.descricao as title, g.gloss, b.nome 
          FROM blocos b
          LEFT JOIN glosses g ON b.id_gloss = g.id 
          WHERE b.id = ".$r['id_dependente'].";";
    }
    $langs = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    $d = mysqli_fetch_assoc($langs);

    $rule1 = $r['lado']==0 ? $n['gloss'] : $d['gloss'];
    $rule2 = $r['lado']==0 ? $d['gloss'] : $n['gloss'];

    //array_push( $R[$r['gloss']] , array( $n['gloss'], $d['gloss'], $r['id'])  );
    array_push( $R[$r['gloss']] , array( $rule1, $rule2, $r['id'])  );
  };

  //var_dump($R);
  
  if ($entrada[0]=='romanizacao') 
    $qry = "SELECT p.id, p.romanizacao as texto FROM palavras p WHERE p.id_idioma = ".$idioma." AND p.id_classe = ";
  else if ($entrada[0]=='pronuncia') 
    $qry = "SELECT p.id, p.pronuncia as texto FROM palavras p WHERE p.id_idioma = ".$idioma." AND p.id_classe = ";
  else $qry = "SELECT p.id, pn.palavra as texto FROM palavras p 
      LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id 
      WHERE p.id_idioma = ".$idioma." AND p.id_classe = ";

  $cs = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM classes c 
		LEFT JOIN glosses g ON g.id = c.id_gloss WHERE c.id_idioma = ".$idioma.";") or die(mysqli_error($GLOBALS['dblink']));
  while ($c = mysqli_fetch_assoc($cs)){
    
    $ps = mysqli_query($GLOBALS['dblink'],$qry.$c['id'].";") or die(mysqli_error($GLOBALS['dblink']));

    while ($p = mysqli_fetch_assoc($ps)){
      if($p['texto']!='')
      array_push( $R[$c['gloss']] , array($p['texto'],$p['id'] ) ); // "significado" para teste, será romanizacao ou nativo
    }
  }

  //var_dump($R);

  $n = sizeof($w);

  $T = array();
  for ($i = 0; $i < $n; $i++) {
    $T[$i] = array();
    for ($j = 0; $j < $n; $j++) {
      $T[$i][$j] = array("g"=>[],"p"=>[], "i"=>[], "s" => 0, "d1"=> [], "d2"=> [], "t" => [], "a" => []); //s=prob, d= backpointers, t=brackets for trees, a=info palavra
    }
  }

  for ($j = 0; $j < $n; $j++) {
    foreach($R as $key => $rule){
      $rule = $R[$key];
      foreach ($rule as $key2 => $rhs) {
        if (sizeof($rhs) == 2 && $rhs[0] == $w[$j]) {
          array_push($T[$j][$j]['g'],$key);
          array_push($T[$j][$j]['p'],$rhs[0]);
          array_push($T[$j][$j]['i'],$rhs[1]);
          array_push($T[$j][$j]['t'],'['.$key.' '.$rhs[0].']');
          array_push($T[$j][$j]['a'],getInfoPalavraFromID($rhs[1]));
        }
      }
    }

    for ($i = $j; $i >= 0; $i--) {
      for ($k = $i; $k <= $j; $k++) {
        foreach($R as $key => $rule){
          $rule = $R[$key];
          foreach ($rule as $key2 => $rhs) {
            if (sizeof($rhs) == 3 && isset($T[$k+1][$j]['g']) && isset($T[$i][$k]['g']) &&
              ( in_array($rhs[0],$T[$i][$k]['g']) && in_array($rhs[1],$T[$k+1][$j]['g']) )
            ) { 
              array_push($T[$i][$j]['g'],$key);
              array_push($T[$i][$j]['i'],$rhs[2]);
              array_push($T[$i][$j]['d1'],[$i,$k]);
              array_push($T[$i][$j]['d2'],[$k+1,$j]);
              array_push($T[$i][$j]['t'],'['.$key.' '.$T[$i][$k]['t'][0].$T[$k+1][$j]['t'][0].']');
            }
          }
        }
      }
    }
  }

  return $T;
  //echo 'retornou parse';
};

function getInfoPalavraFromID($pid){ 
  //$res: texto da palavra
  //$entrada: [nativa,ID] | [romanizacao] | [pronuncia ]

  $orig = array();
  $orig['cl'] = 0;
  $orig['ref'] = '';
  $orig['gls'] = '';
  $orig['gdesc'] = '';
  
  $orig['exata'] = 0;

  
  if (isset($_SESSION['KondisonairUzatorDiom'])) $mylang = $_SESSION['KondisonairUzatorDiom'];
  else $mylang = 1; //1=ptbr 5=id_eng ?


  $qry = "SELECT p.* d.descricao as referente, r.id as refid, c.id as clid,
        d.detalhes as refDesc, g.gloss as cgloss, c.nome as cnome,
      (SELECT palavra FROM palavrasNativas WHERE id_palavra = ".$pid." AND id_escrita = 
          (SELECT id FROM escritas WHERE padrao = 1 AND id_idioma = p.id_idioma LIMIT 1) 
          LIMIT 1) as nativa  
      FROM palavras p
      LEFT JOIN palavras_referentes pr ON pr.id_palavra = p.id 
      LEFT JOIN classes c ON p.id_classe = c.id
      LEFT JOIN glosses g ON c.id_gloss = g.id
      LEFT JOIN referentes r ON pr.id_referente = r.id  
      LEFT JOIN referentes_descricoes d ON d.id_referente = r.id AND id_idioma = '".$mylang."'
      WHERE p.id = ".$pid."  
      AND p.id_forma_dicionario = 0;";
  $a = mysqli_query($GLOBALS['dblink'],$qry) or die(mysqli_error($GLOBALS['dblink']));

  if (mysqli_num_rows($a)<1){
    $qry = "SELECT p.*,d.descricao as referente, r.id as refid, c.id as clid,
            d.detalhes as refDesc, g.gloss as cgloss, c.nome as cnome,
          (SELECT palavra FROM palavrasNativas WHERE id_palavra = ".$pid." AND id_escrita = 
                (SELECT id FROM escritas WHERE padrao = 1 AND id_idioma = p.id_idioma LIMIT 1) 
                LIMIT 1) as nativa  
            FROM palavras p
            LEFT JOIN palavras dic ON p.id_forma_dicionario = dic.id
            LEFT JOIN classes c ON p.id_classe = c.id
            LEFT JOIN glosses g ON c.id_gloss = g.id
          LEFT JOIN palavras_referentes pr ON pr.id_palavra = dic.id 
            LEFT JOIN referentes r ON pr.id_referente = r.id 
            LEFT JOIN referentes_descricoes d ON d.id_referente = r.id AND id_idioma = '".$mylang."'
        WHERE p.id = ".$pid." ;";
            
    $a = mysqli_query($GLOBALS['dblink'],$qry) or die(mysqli_error($GLOBALS['dblink']));
  }

  if(mysqli_num_rows($a)>0){



    $ax = mysqli_fetch_assoc($a); //xxxxx pegando só a primeira palavra encontrada aqui

    //echo json_encode($ax);
    
    $sinonimos = '';
    if ($ax['refid']>0){
      $sql = "SELECT p.* FROM palavras p
            LEFT JOIN palavras_referentes pr ON pr.id_palavra = p.id 
            WHERE pr.id_referente = ".$ax['refid']." AND p.id_idioma = ".$mylang."
            AND p.id_forma_dicionario = 0;";
      $sql = "SELECT r.*, 
            (SELECT p.significado FROM palavras p LEFT JOIN palavras_referentes pr ON p.id = pr.id_palavra
              WHERE pr.id_referente = r.id
              AND p.id_idioma = ".$mylang." AND p.id_forma_dicionario = 0) as significado 
            FROM referentes r WHERE r.id = ".$ax['refid']." ;";
      $p = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      //$sinonimos .= 'aa';
      while($px = mysqli_fetch_assoc($p)){
        if ($px['significado']!='')
          $sinonimos .= $px['significado']." (".$px['descricao'].") \n";
        else
          $sinonimos .= $px['descricao']." \n";
      }
    }


    $orig['cl'] = $ax['clid'];
    
    $p = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras_referentes
      WHERE id_palavra = ".$pid.";") or die(mysqli_error($GLOBALS['dblink']));

    while($px = mysqli_fetch_assoc($p)){
      //push_array($orig['ref'], $px['id_referente']); 
      $orig['ref'] .= $px['id_referente'].',';
    }
    $orig['ref'] = substr($orig['ref'],0,strlen($orig['ref'])-1); //-1

    $qry = "SELECT i.*, g.gloss, i.nome as gdesc
        FROM itens_palavras ip
        LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
        LEFT JOIN gloss_itens gi ON gi.id_item = i.id
        LEFT JOIN glosses g ON gi.id_gloss = g.id 
        WHERE ip.id_palavra = ".$pid." AND usar = 1;";

    $b = mysqli_query($GLOBALS['dblink'],$qry) or die(mysqli_error($GLOBALS['dblink']));
    while($bx = mysqli_fetch_assoc($b)){
      $orig['gls'] .= $bx['gloss'].'.';
      $orig['gdesc'] .= $bx['gdesc'].' ';
      //push_array($orig['gls'], $bx['gloss']);
    }
    $orig['gls'] = substr($orig['gls'],0,strlen($orig['gls'])-1); //-1

    
    $orig['exata'] = 1;
    //$orig['id_classe'] = $ax['clid'];
    $orig['cnome'] = $ax['cnome'];//$ax['cgloss'];
    $orig['cgloss'] = $ax['cgloss'];//$ax['cgloss'];
    $orig['pron'] = $ax['pronuncia'];
    $orig['rom'] = $ax['romanizacao'];
    $orig['nat'] = $ax['nativa'];
    $orig['refid'] = $ax['refid'];
    $orig['significado'] = $sinonimos; //$ax['refDesc']."\n".$sinonimos;
    
    $orig['cgloss'] = $ax['cgloss'];
    //$orig['romanizacao'] = $ax['romanizacao'];
    //$orig['nativo'] = $ax['nativa'];


  }

  return $orig;

};

function getPalavrasMesmaEscrita($pid,$limit=0,$editable = true,$eid=0){
  $return = '';  
  if ($limit > 0) $sqlLimit = " LIMIT ".$limit." ";
  $res0 = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras p 
      WHERE p.id = ".$pid.";") or die(mysqli_error($GLOBALS['dblink']));
  $r = mysqli_fetch_assoc($res0);
  $id_idioma = $r['id_idioma'];
  
  if ($eid>0) $ef = " AND e.id = ".$eid;
  $ex = mysqli_query($GLOBALS['dblink'],"SELECT e.* FROM escritas e
    WHERE e.id_idioma = ".$id_idioma.$ef." ORDER BY e.padrao DESC;") or die(mysqli_error($GLOBALS['dblink']));
    
  if (mysqli_num_rows($ex)>0){

    while($e = mysqli_fetch_assoc($ex)){
        $escrita = $e['id'];
        $fonte = $e['id_fonte'];
        $tamanho = $e['tamanho'];
        $enome = $e['nome'];
        
        if ($escrita > 0){
          $sql = "SELECT p.*, ap.*, ap.pronuncia as pron, p2.id_palavra AS palavra_id,
            i.sigla, i.nome_legivel,
            (SELECT c.nome FROM classes c WHERE c.id = ap.id_classe LIMIT 1) as nomeClasse 
            FROM palavrasNativas p
            LEFT JOIN palavrasNativas p2 ON p2.id_escrita  = p.id_escrita 
            LEFT JOIN palavras ap ON p2.id_palavra = ap.id 
            LEFT JOIN idiomas i ON i.id = ap.id_idioma 
            WHERE BINARY p.palavra = p2.palavra
            AND p.palavra <> ''
            AND p.id_palavra = $pid 
            AND p.id_escrita = $escrita 
            ORDER BY RAND() $sqlLimit ;";
        }else{
          die('invalid');
        }

        $homons = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink'])); //nomeClasse
        if (mysqli_num_rows($homons)>1){// <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasSigCom" role="button" aria-controls="offcanvasStart">Mostrar/ocultar significados da comunidade</a>
        $return .= '<div class="list-group-item"><h3><a data-bs-toggle="offcanvas" href="#offcanvasExtras" role="button" aria-controls="offcanvasExtras" onclick="loadExtras(\'getPalavrasMesmaEscrita\')">'._t('Palavras com mesma escrita em').' '.$enome.'</a></h3>';
            // <h4>Palavras com mesma escrita em '.$enome.':</h4><ul>';
            while($homon = mysqli_fetch_assoc($homons)){
              if ($homon['palavra_id']==$pid) continue;
              $nat = getSpanPalavraNativa($homon['palavra'],$escrita,$fonte,$tamanho);
              //if ($homon['id_idioma']==$id_idioma)
              $return .= '<a class="col text-truncate" href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['palavra_id'].'">
                  <div class="text-reset d-block text-truncate">'.$nat.' '.($homon['romanizacao']!=''?$homon['romanizacao']:'/'.$homon['pron'].'/').'</div>
                  <div class="text-secondary text-truncate mt-n1">'.$homon['significado'].'</div>
                </a>';
            }
            $return .= '</div>'; // </ul>
        }
    }

  }else{
      $sql = "SELECT p.*, p2.romanizacao as roman, p2.pronuncia as pron, p2.id AS palavra_id,
          i.sigla, i.nome_legivel,
          (SELECT c.nome FROM classes c WHERE c.id = p2.id_classe LIMIT 1) as nomeClasse 
          FROM palavras p
          LEFT JOIN palavras p2 ON p2.id_idioma  = p.id_idioma 
          LEFT JOIN idiomas i ON i.id = p.id_idioma 
          WHERE BINARY p.romanizacao = p2.romanizacao
          AND p.romanizacao <> ''
          AND p.id = $pid 
          ORDER BY RAND() $sqlLimit ;";

      $homons = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink'])); //nomeClasse
      if (mysqli_num_rows($homons)>1){// <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasSigCom" role="button" aria-controls="offcanvasStart">Mostrar/ocultar significados da comunidade</a>
      $return .= '<div class="list-group-item"><h3><a data-bs-toggle="offcanvas" href="#offcanvasExtras" role="button" aria-controls="offcanvasExtras" onclick="loadExtras(\'getPalavrasMesmaEscrita\')">'._t('Palavras com mesma romanização').'</a></h3>';
          // <h4>Palavras com mesma escrita em '.$enome.':</h4><ul>';
          while($homon = mysqli_fetch_assoc($homons)){
            if ($homon['palavra_id']==$pid) continue;
            $nat = $homon['roman'];
            //if ($homon['id_idioma']==$id_idioma)
            $return .= '<a class="col text-truncate" href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['palavra_id'].'">
                <div class="text-reset d-block text-truncate">'.$homon['romanizacao'].' /'.$homon['pron'].'/</div>
                <div class="text-secondary text-truncate mt-n1">'.$homon['significado'].'</div>
              </a>';
          }
          $return .= '</div>'; // </ul>
      }
  }

  return $return;
};

function getPalavrasMesmaPronuncia($pid,$limit=0,$editable = true){
  $return = '';
  if ($limit > 0) $sqlLimit = " LIMIT ".$limit." ";

  $res0 = mysqli_query($GLOBALS['dblink'],"SELECT *, (SELECT e.id FROM escritas e
    WHERE e.id_idioma = p.id_idioma ORDER BY e.padrao DESC LIMIT 1) as epadrao,
      (SELECT e.id_fonte FROM escritas e WHERE e.id_idioma = p.id_idioma ORDER BY e.padrao DESC LIMIT 1) as fonte,
      (SELECT e.tamanho FROM escritas e WHERE e.id_idioma = p.id_idioma ORDER BY e.padrao DESC LIMIT 1) as tamanho
      FROM palavras p 
      WHERE p.id = ".$pid.";") or die(mysqli_error($GLOBALS['dblink']));
  $r = mysqli_fetch_assoc($res0);
  $id_idioma = $r['id_idioma'];
  $escrita = 0;
  if ($r['epadrao']>0){
      $escrita = $r['epadrao'];
      $fonte = $r['fonte'];
      $tamanho = $r['tamanho'];
      $esql = ",(SELECT palavra FROM palavrasNativas WHERE id_escrita = ".$escrita." AND id_palavra = p2.id LIMIT 1) as nativa";
  }

  $homons = mysqli_query($GLOBALS['dblink'],"SELECT *, p2.id AS palavra_id ".$esql." FROM palavras p
    LEFT JOIN palavras p2 ON p2.id_idioma  = p.id_idioma 
    WHERE p.pronuncia = p2.pronuncia
    AND p.id= ".$pid." AND LENGTH(p.pronuncia) > 0 ORDER BY RAND() ".$sqlLimit.";") or die(mysqli_error($GLOBALS['dblink']));

  if (mysqli_num_rows($homons)>1){
    $return .= '<div class="list-group-item"><h3><a data-bs-toggle="offcanvas" href="#offcanvasExtras" role="button" aria-controls="offcanvasExtras" onclick="loadExtras(\'getPalavrasMesmaPronuncia\')">'._t('Palavras com mesma pronúncia').'</a></h3>';
    
    if($escrita>0)
        while($homon = mysqli_fetch_assoc($homons)){
          if($pid==$homon['palavra_id']) continue;
          $nat = getSpanPalavraNativa($homon['nativa'],$escrita,$fonte,$tamanho);
          $return .= '<a href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['palavra_id'].'" class="col text-truncate">
            <div class="text-reset d-block text-truncate">'.$nat.($homon['romanizacao']!=''?$homon['romanizacao']:'').'</div>
            <div class="text-secondary text-truncate mt-n1">'.$homon['significado'].'</div>
          </a>'; 
        }
    else
        while($homon = mysqli_fetch_assoc($homons)){
          if($pid==$homon['palavra_id']) continue;
          $return .= '<a href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['palavra_id'].'" class="col text-truncate">
            <div class="text-reset d-block text-truncate">'.$homon['pronuncia'].'&nbsp'.($homon['romanizacao']!=''?$homon['romanizacao']:'').'</div>
            <div class="text-secondary text-truncate mt-n1">'.$homon['significado'].'</div>
          </a>';
        }

            $return .= '</div>';
  }
  return $return;
};

function getPalavrasMesmosReferentes($pid,$limit=0,$editable = true){
  // get sinonimos na mesma iid, dps em outros idiomas 

  // $escrita, $id_idioma
  if ($limit > 0) $sqlLimit = " LIMIT ".$limit." ";

  $simi = mysqli_query($GLOBALS['dblink'],"SELECT *, p.id AS palavra_id,
    (SELECT id FROM escritas WHERE padrao = 1 AND id_idioma = p.id_idioma) as eid,
    (SELECT id_fonte FROM escritas WHERE padrao = 1 AND id_idioma = p.id_idioma) as fonte,
    (SELECT tamanho FROM escritas WHERE padrao = 1 AND id_idioma = p.id_idioma) as tamanho,
    (SELECT GROUP_CONCAT(palavra, ',', id_escrita SEPARATOR '|') FROM palavrasNativas WHERE id_palavra = p.id) as nativos,
    (SELECT nome_legivel FROM idiomas WHERE id = p.id_idioma) as nomeidioma 
    FROM palavras p
      LEFT JOIN palavras_referentes pr ON (pr.id_palavra = p.id OR pr.id_palavra = p.id_forma_dicionario)
    WHERE p.id_forma_dicionario = 0 AND pr.id_referente IN(
          SELECT id_referente FROM palavras_referentes WHERE id_palavra = ".$pid." UNION
          SELECT id_referente FROM palavras_referentes WHERE id_palavra = (SELECT id_forma_dicionario FROM palavras WHERE id = ".$pid." LIMIT 1)
      )
      ORDER BY RAND(), nomeidioma ".$sqlLimit.";") or die(mysqli_error($GLOBALS['dblink'])); //p.id_idioma = ".$id_idioma." AND 
  
  if (mysqli_num_rows($simi)>1){
    $return .= '<div class="list-group-item"><h3><a data-bs-toggle="offcanvas" href="#offcanvasExtras" role="button" aria-controls="offcanvasExtras" onclick="loadExtras(\'getPalavrasSinonimos\')">'._t('Palavras com mesmos referentes').'</a></h3>';

    while($si = mysqli_fetch_assoc($simi)){ // onclick="abrirPalavra('.$si['palavra_id'].')"
        if ($si['palavra_id']==$pid) continue;
        $pnat = '';
        $nats = explode('|',$si['nativos']);
        foreach($nats as $pna){
          $palnat = explode(',',$pna);
          if ($palnat[1] == $si['eid']) {
            $pnat = $palnat[0];
            continue;
          }
        }

        // $si['nativos']
        $return .= '<div class="col mt-2">
            <div class="text-reset d-block">'.($pnat!=''?getSpanPalavraNativa($pnat,$si['eid'],$si['fonte'],$si['tamanho'])  .'&nbsp;':'').($si['romanizacao']==''?'/'.$si['pronuncia'].'/':$si['romanizacao']).'</div>
            <div class="text-secondary">'.$si['nomeidioma'].'</div>
          </div>';
    }
    $return .= '</div>';
        
    //$rows[0]['formas'] = $f;
  }



  return $return;
};

function setGenPalDeriv($pid,$i=0){

    //xxxxx pegar o primeiro genero pra ser o default

    // conferir se palavra já tem genero definido
    // se não tiver segue pra gravar
    // ou colocar mais uma variavel $forçar, daí substitui o genero pelo especificado ou defualt(primeiro) se 0

    if ($i > 0){
        $t = mysqli_query($GLOBALS['dblink'],"SELECT * FROM classesGeneros WHERE 
          id_palavra = ".$pid." AND
          id_genero IN(SELECT id FROM generos WHERE id_classe = ( SELECT id_classe FROM palavras WHERE id = ".$pid." LIMIT 1) );") or die(mysqli_error($GLOBALS['dblink']));

        if(mysqli_num_rows($t)>0){ 
            $p = mysqli_fetch_assoc($t);

            $sqlQuerys = "UPDATE classesGeneros SET 
            id_genero = ".$i."
            WHERE id_palavra = ".$pid." LIMIT 1;";
            //echo $sqlQuerys;
            mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
            return 1;

        } else {  
            $novoId = generateId();
            $sqlQuerys = "INSERT INTO classesGeneros SET 
              id_palavra = ".$pid.",
              id = ".$novoId.",
              id_classe = ( SELECT id_classe FROM palavras WHERE id = ".$pid." LIMIT 1),
              id_genero = ".$i.";";

              //echo $sqlQuerys;
            mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
            return $novoId;
        }
    };
};

function setItensPalavra($pid,$conc=0,$item=0){

  if ($conc == 0){
      return 1;
  }else{

      if ($item == 0) {
          //xxxxx pegar o item default 
          $resdef = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias
            WHERE id_concordancia = ".$conc." AND padrao < 2 ORDER BY padrao DESC, ordem;") or die(mysqli_error($GLOBALS['dblink']));
          $rc = mysqli_fetch_assoc($resdef);
          $item = $rc['id'];
      };

      $t = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itens_palavras WHERE 
            id_palavra = ".$pid." AND
            id_concordancia = ".$conc.";") or die(mysqli_error($GLOBALS['dblink']));
    
      if(mysqli_num_rows($t)>0){ 
          $sqlQuerys = "UPDATE itens_palavras SET 
              id_item = ".$item.", usar = 1 
              WHERE id_palavra = ".$pid." AND
              id_concordancia = ".$conc." LIMIT 1;";
          //echo $sqlQuerys;
          mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
          return 1;
    
      } else {  

          // NOVO 06/09/25
          $res1 = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias
              WHERE depende = ".$item.";") or die(mysqli_error($GLOBALS['dblink']));
          if(mysqli_num_rows($res1)>0){
              // TEM DEPENDENTES: NÃO SALVAR ESTE ITEM, MAS SIM IR AO DEPENDENTE
              $sub = mysqli_fetch_assoc($res1);
              return setItensPalavra($pid,$sub['id'],0);
          }

          $novoId = generateId();
          $sqlQuerys = "INSERT INTO itens_palavras SET 
            id_palavra = ".$pid.",
            id = ".$novoId.",
            id_concordancia = ".$conc.", usar = 1 ,
            id_item = ".$item.";";
    
            //echo $sqlQuerys;
          mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
          return $novoId;
      }; 
  }

};

function getPalavrasRelacionadas($pid,$limit=0,$editable = true){

  if ($limit > 0) $sqlLimit = " LIMIT ".$limit." ";
  $return = '';
  $res0 = mysqli_query($GLOBALS['dblink'],"SELECT *, 
      (SELECT e.id FROM escritas e
        WHERE e.id_idioma = p.id_idioma ORDER BY e.padrao DESC LIMIT 1) as epadrao,
        (SELECT e.id_fonte FROM escritas e WHERE e.id_idioma = p.id_idioma ORDER BY e.padrao DESC LIMIT 1) as fonte,
        (SELECT e.tamanho FROM escritas e WHERE e.id_idioma = p.id_idioma ORDER BY e.padrao DESC LIMIT 1) as tamanho,
      (SELECT id_genero FROM classesGeneros WHERE id_palavra = ".$pid." LIMIT 1) as genDic
      FROM palavras p 
        WHERE p.id = ".$pid.";") or die(mysqli_error($GLOBALS['dblink']));
  $r = mysqli_fetch_assoc($res0);
  $id_idioma = $r['id_idioma'];
  $base = $r['id_forma_dicionario'];
  $derivadora = $r['id_derivadora'];
  $escrita = 0;
  if ($r['epadrao']>0){
      $escrita = $r['epadrao'];
      $fonte = $r['fonte'];
      $tamanho = $r['tamanho'];
      $esql = ",(SELECT palavra FROM palavrasNativas WHERE id_escrita = ".$escrita." AND id_palavra = p.id LIMIT 1) as nativa";
  }
  $genDic = $r['genDic'];

  //xxxxx ADD OPCAO auto colocar mesmo genero em palavras derivadas a partir da forma de dicionario
  $aplicarGenDerivadas = 1;

  //xxxxx buscar concs ?
  //xxxx verificar tbm id_derivadora
  if ($derivadora>0) {
    $orig = mysqli_query($GLOBALS['dblink'],"SELECT p.*, pn.palavra as nativa FROM palavras p
      LEFT JOIN palavrasNativas pn ON ( pn.id_palavra = p.id AND pn.id_escrita = ".$escrita." )
        WHERE p.id = ".$derivadora." LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));   
        //(SELECT palavra FROM palavrasNativas WHERE id_escrita = ".$escrita." AND id_palavra = p.id LIMIT 1) as nativa  
    $homon = mysqli_fetch_assoc($orig);

    $return .= '<div class="list-group-item"><h3>'._t('Derivada de').'</h3>';
      
      $glosses = '';

      $b = mysqli_query($GLOBALS['dblink'],"SELECT g.nome, l.gloss FROM classesGeneros c
        LEFT JOIN generos g ON c.id_genero = g.id
          LEFT JOIN glosses l ON g.id_gloss = l.id
              WHERE c.id_palavra = ".$homon['id']."
              UNION
              SELECT i.nome, g.gloss FROM itens_palavras ip
        LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
        LEFT JOIN gloss_itens gi ON gi.id_item = i.id
                    LEFT JOIN glosses g ON gi.id_gloss = g.id
        WHERE ip.id_palavra = ".$homon['id']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
      while($bx = mysqli_fetch_assoc($b)){
        $glosses .= '<span title="'.$bx['nome'].'">'.$bx['gloss'].'</span>.';
      }
      $return .= '<a href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['id'].'" class="col text-truncate">
          <div class="text-reset d-block text-truncate">'.getSpanPalavraNativa($homon['nativa'],$escrita,$fonte,$tamanho).'</div>
          <div class="text-secondary text-truncate mt-n1">'.($homon['romanizacao']!=''?$homon['romanizacao']:'').' ('.substr($glosses,0,strlen($glosses)-1).') '.$homon['significado'].'</div>
        </a>';
    $return .= '</div>';
  }

  // derivadas
  $homons = mysqli_query($GLOBALS['dblink'],"SELECT p.* ".$esql." 
    FROM palavras p 
    WHERE p.id_derivadora = ".$pid." ORDER BY RAND() ".$sqlLimit.";") or die(mysqli_error($GLOBALS['dblink']));
  if (mysqli_num_rows($homons)>0){
      $return .= '<div class="list-group-item"><h3><a data-bs-toggle="offcanvas" href="#offcanvasExtras" role="button" aria-controls="offcanvasExtras" onclick="loadExtras(\'getPalavrasRelacionadas\')">'._t('Palavras derivadas').'</a></h3>'; //.$r['escrita_nativa']
      if($escrita>0)
              while($homon = mysqli_fetch_assoc($homons)){

                  //setItensPalavra($homon['id'],0);

                  if ($aplicarGenDerivadas==1) setGenPalDeriv($homon['id'],$genDic);

                  //get glossesnames
                  $glosses = '';
                  $b = mysqli_query($GLOBALS['dblink'],"SELECT g.nome, l.gloss FROM classesGeneros c
                  LEFT JOIN generos g ON c.id_genero = g.id
                    LEFT JOIN glosses l ON g.id_gloss = l.id
                        WHERE c.id_palavra = ".$homon['id']."
                        UNION
                        SELECT i.nome, g.gloss FROM itens_palavras ip
                    LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
                    LEFT JOIN gloss_itens gi ON gi.id_item = i.id
                    LEFT JOIN glosses g ON gi.id_gloss = g.id
                    WHERE ip.id_palavra = ".$homon['id']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
                  while($bx = mysqli_fetch_assoc($b)){
                    $glosses .= '<span title="'.$bx['nome'].'">'.$bx['gloss'].'</span>.';
                  }

                  $return .= '<a href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['id'].'" class="col text-truncate">
                      <div class="text-reset d-block text-truncate">'.getSpanPalavraNativa($homon['nativa'],$escrita,$fonte,$tamanho).($homon['romanizacao']!=''?$homon['romanizacao']:'').'</div>
                      <div class="text-secondary text-truncate mt-n1">'.$homon['significado'].' ('.substr($glosses,0,strlen($glosses)-1).')</div>
                    </a>';
              }
      else 
          while($homon = mysqli_fetch_assoc($homons)){

              setItensPalavra($homon['id'],0);

              if ($aplicarGenDerivadas==1) setGenPalDeriv($homon['id'],$genDic);

              //get glossesnames
              $glosses = '';
              $b = mysqli_query($GLOBALS['dblink'],"SELECT g.nome, l.gloss FROM classesGeneros c
              LEFT JOIN generos g ON c.id_genero = g.id
                LEFT JOIN glosses l ON g.id_gloss = l.id
                    WHERE c.id_palavra = ".$homon['id']."
                    UNION
                    SELECT i.nome, g.gloss FROM itens_palavras ip
                LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
                LEFT JOIN gloss_itens gi ON gi.id_item = i.id
                LEFT JOIN glosses g ON gi.id_gloss = g.id
                WHERE ip.id_palavra = ".$homon['id']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
              while($bx = mysqli_fetch_assoc($b)){
                $glosses .= '<span title="'.$bx['nome'].'">'.$bx['gloss'].'</span>.';
              }

              $return .= '<a href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['id'].'" class="col text-truncate">
                  <div class="text-reset d-block text-truncate">'.($homon['romanizacao']!=''?$homon['romanizacao']:$homon['pronuncia']).'</div>
                  <div class="text-secondary text-truncate mt-n1">'.$homon['significado'].' ('.substr($glosses,0,strlen($glosses)-1).')</div>
                </a>';
          }
          $return .= '</div>';
  }

  if ($base>0) {
    $orig = mysqli_query($GLOBALS['dblink'],"SELECT p.*, pn.palavra as nativa FROM palavras p
      LEFT JOIN palavrasNativas pn ON ( pn.id_palavra = p.id AND pn.id_escrita = ".$escrita." )
        WHERE p.id = ".$base." LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));   
        //(SELECT palavra FROM palavrasNativas WHERE id_escrita = ".$escrita." AND id_palavra = p.id LIMIT 1) as nativa  
    $homon = mysqli_fetch_assoc($orig);

    $return .= '<div class="list-group-item"><h3>'._t('Forma de Dicionário').'</h3>';
      
      $glosses = '';

      $b = mysqli_query($GLOBALS['dblink'],"SELECT g.nome, l.gloss FROM classesGeneros c
        LEFT JOIN generos g ON c.id_genero = g.id
          LEFT JOIN glosses l ON g.id_gloss = l.id
              WHERE c.id_palavra = ".$homon['id']."
              UNION
              SELECT i.nome, g.gloss FROM itens_palavras ip
        LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
        LEFT JOIN gloss_itens gi ON gi.id_item = i.id
                    LEFT JOIN glosses g ON gi.id_gloss = g.id
        WHERE ip.id_palavra = ".$homon['id']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
      while($bx = mysqli_fetch_assoc($b)){
        $glosses .= '<span title="'.$bx['nome'].'">'.$bx['gloss'].'</span>.';
      }
      $return .= '<a href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['id'].'" class="col text-truncate">
          <div class="text-reset d-block text-truncate">'.getSpanPalavraNativa($homon['nativa'],$escrita,$fonte,$tamanho).'</div>
          <div class="text-secondary text-truncate mt-n1">'.($homon['romanizacao']!=''?$homon['romanizacao']:'').' ('.substr($glosses,0,strlen($glosses)-1).') '.$homon['significado'].'</div>
        </a>';
    $return .= '</div>';

  }//else{
    $homons = mysqli_query($GLOBALS['dblink'],"SELECT p.* ".$esql." 
        FROM palavras p 
        WHERE p.id_forma_dicionario = ".($base>0?$base:$pid)." AND p.id <> ".$pid." ORDER BY RAND() ".$sqlLimit.";") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($homons)>0){
      $return .= '<div class="list-group-item"><h3><a data-bs-toggle="offcanvas" href="#offcanvasExtras" role="button" aria-controls="offcanvasExtras" onclick="loadExtras(\'getPalavrasRelacionadas\')">'._t('Outras formas').'</a></h3>'; //.$r['escrita_nativa']
      if($escrita>0)
              while($homon = mysqli_fetch_assoc($homons)){
                  
                  // if($homon['id']==$pid) $return .=  'AX'; //continue;

                  //setItensPalavra($homon['id'],0);

                  if ($aplicarGenDerivadas==1) setGenPalDeriv($homon['id'],$genDic);

                  //get glossesnames
                  $glosses = '';
                  $b = mysqli_query($GLOBALS['dblink'],"SELECT g.nome, l.gloss FROM classesGeneros c
                  LEFT JOIN generos g ON c.id_genero = g.id
                    LEFT JOIN glosses l ON g.id_gloss = l.id
                        WHERE c.id_palavra = ".$homon['id']."
                        UNION
                        SELECT i.nome, g.gloss FROM itens_palavras ip
                    LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
                    LEFT JOIN gloss_itens gi ON gi.id_item = i.id
                    LEFT JOIN glosses g ON gi.id_gloss = g.id
                    WHERE ip.id_palavra = ".$homon['id']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
                  while($bx = mysqli_fetch_assoc($b)){
                    $glosses .= '<span title="'.$bx['nome'].'">'.$bx['gloss'].'</span>.';
                  }

                  $return .= '<a href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['id'].'" class="col text-truncate">
                      <div class="text-reset d-block text-truncate">'.getSpanPalavraNativa($homon['nativa'],$escrita,$fonte,$tamanho).($homon['romanizacao']!=''?$homon['romanizacao']:'').'</div>
                      <div class="text-secondary text-truncate mt-n1">'.$homon['significado'].' ('.substr($glosses,0,strlen($glosses)-1).')</div>
                    </a>';
              }
      else 
          while($homon = mysqli_fetch_assoc($homons)){

              setItensPalavra($homon['id'],0);

              if ($aplicarGenDerivadas==1) setGenPalDeriv($homon['id'],$genDic);

              //get glossesnames
              $glosses = '';
              $b = mysqli_query($GLOBALS['dblink'],"SELECT g.nome, l.gloss FROM classesGeneros c
              LEFT JOIN generos g ON c.id_genero = g.id
                LEFT JOIN glosses l ON g.id_gloss = l.id
                    WHERE c.id_palavra = ".$homon['id']."
                    UNION
                    SELECT i.nome, g.gloss FROM itens_palavras ip
                LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
                LEFT JOIN gloss_itens gi ON gi.id_item = i.id
                LEFT JOIN glosses g ON gi.id_gloss = g.id
                WHERE ip.id_palavra = ".$homon['id']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
              while($bx = mysqli_fetch_assoc($b)){
                $glosses .= '<span title="'.$bx['nome'].'">'.$bx['gloss'].'</span>.';
              }

              $return .= '<a href="?page='.($editable?'edit':null).'word&iid='.$id_idioma.'&pid='.$homon['id'].'" class="col text-truncate">
                  <div class="text-reset d-block text-truncate">'.($homon['romanizacao']!=''?$homon['romanizacao']:$homon['pronuncia']).'</div>
                  <div class="text-secondary text-truncate mt-n1">'.$homon['significado'].' ('.substr($glosses,0,strlen($glosses)-1).')</div>
                </a>';
          }
      $return .= '</div>';
    }
  //}



  return $return;
};

function soltarContracao($res,$entrada,$geto){ 
  //$res: texto da palavra
  //$entrada: [nativa,ID] | [romanizacao] | [pronuncia ]
  //$geto: id idioma

  $orig = [];

  if ($entrada[0]=='nativa'){ // entrada[1] = id da escrita
    $filtroTipo = "pn.palavra = '".$res."' AND ";
    $filtroDeriv = "pn.palavra = '".$res."' AND ";
    $pnp = " LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id ";
    $pnd = " LEFT JOIN palavrasNativas pn ON pn.id_palavra = dic.id ";
    $pnq = " pn.palavra as nativa ";
  }else{
    $filtroTipo = ' p.'.$entrada[0]." = '".$res."' AND " ;
    $filtroDeriv = " p.".$entrada[0]." = '".$res."' AND ";
    $pnp = "  ";
    $pnd = " ";
    $pnq = " ";
  };
  $sql = "SELECT p.*, ".$pnq."
        FROM palavras p
        LEFT JOIN classes c ON p.id_classe = c.id
        LEFT JOIN glosses g ON c.id_gloss = c.id ".$pnp." 
        WHERE ".$filtroTipo." p.id_idioma = ".$geto." ;";
  //echo $sql;
  $a = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      
  //não achou a palavra exata: buscar formas derivadas
  if (mysqli_num_rows($a)<1){
    $sql = "SELECT p.*, ".$pnq."
            FROM palavras p
            LEFT JOIN palavras dic ON p.id_forma_dicionario = dic.id
            LEFT JOIN classes c ON p.id_classe = c.id
            LEFT JOIN glosses g ON c.id_gloss = c.id ".$pnd." 
        WHERE ".$filtroDeriv." dic.id_idioma = ".$geto." ;";
            
    //echo $sql;
    $a = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
  }

  if (mysqli_num_rows($a)>0){

      if(mysqli_num_rows($a)>1){

        //disambiguate;

      }

      $ax = mysqli_fetch_assoc($a);

      if($ax['id_classe']=='2'){ // -2 = contração

          $b = mysqli_query($GLOBALS['dblink'],"SELECT po.*, 
          (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = po.id_origem 
              AND pn.id_escrita = (SELECT e.id FROM escritas e WHERE e.id_idioma = ".$geto." AND e.padrao = 1) 
          LIMIT 1) as nativo FROM palavras_origens po
            WHERE po.id_palavra = ".$ax['id']." ORDER BY po.ordem;") or die(mysqli_error($GLOBALS['dblink']));
          while($bx = mysqli_fetch_assoc($b)){
            array_push($orig,$bx['nativo']);
          }
      }
  }
  if (sizeof($orig)==0) array_push($orig,$res);
  //echo json_encode($orig);
  return $orig;
};

function getInfoPalavra($res,$entrada,$geto){
  //$res: texto da palavra
  //$entrada: [nativa,ID] | [romanizacao] | [pronuncia ]
  //$geto: id idioma

  //for($i = 0; $i < sizeof($res) ; $i++){
  $orig = array();
  $orig['orig'] = $res; // casa (1.ptbr)
  $orig['gloss'] = '';
  $orig['pron'] = '';
  $orig['rom'] = '';
  $orig['nat'] = '';
  $orig['glossdet'] = '';
  //$orig['glossArray'] = null;

  $orig['cgloss'] = '';
  $orig['romanizacao'] = '';
  $orig['nativo'] = '';
  
  if (isset($_SESSION['KondisonairUzatorDiom'])) $mylang = $_SESSION['KondisonairUzatorDiom'];
  else $mylang = 1; //1=ptbr 5=id_eng ?

  $orig['exata'] = 1;

  if ($entrada[0]=='nativa'){ // entrada[1] = id da escrita
    $filtroTipo = "pn.palavra = '".$res."' AND ";
    $filtroDeriv = "pn.palavra = '".$res."' AND";
    $filtroLike = "pn.palavra LIKE '%".$res."%' AND";
    $pnp = "LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id";
    $pnd = "LEFT JOIN palavrasNativas pn ON pn.id_palavra = dic.id";
    $pnq = "pn.palavra as nativa, ";
  }else{
    $filtroTipo = 'p.'.$entrada[0]." = '".$res."' AND " ;
    $filtroDeriv = "p.".$entrada[0]." = '".$res."' AND";
    $filtroLike = "p.".$entrada[0]." LIKE '%".$res."%' AND";
    $pnp = "";
    $pnd = "";
    $pnq = "";
  };
  $a = mysqli_query($GLOBALS['dblink'],"SELECT p.*,d.descricao as referente, r.id as refid, c.id as clid,
        d.detalhes as refDesc, ".$pnq." g.gloss as cgloss, c.nome as cnome  
      FROM palavras p
      LEFT JOIN palavras_referentes pr ON pr.id_palavra = p.id 
      LEFT JOIN classes c ON p.id_classe = c.id
      LEFT JOIN glosses g ON c.id_gloss = c.id
      LEFT JOIN referentes r ON pr.id_referente = r.id ".$pnp." 
      LEFT JOIN referentes_descricoes d ON d.id_referente = r.id AND id_idioma = '".$mylang."'
      WHERE ".$filtroTipo." p.id_idioma = ".$geto."  
      AND p.id_forma_dicionario = 0;") or die(mysqli_error($GLOBALS['dblink']));
      
  //não achou a palavra exata: buscar formas derivadas
  if (mysqli_num_rows($a)<1){
    $sql = "SELECT p.*,d.descricao as referente, r.id as refid, c.id as clid,
            d.detalhes as refDesc, ".$pnq." g.gloss as cgloss, c.nome as cnome  
            FROM palavras p
            LEFT JOIN palavras dic ON p.id_forma_dicionario = dic.id
            LEFT JOIN classes c ON p.id_classe = c.id
            LEFT JOIN glosses g ON c.id_gloss = c.id
          LEFT JOIN palavras_referentes pr ON pr.id_palavra = dic.id 
            LEFT JOIN referentes r ON pr.id_referente = r.id ".$pnd." 
          LEFT JOIN referentes_descricoes d ON d.id_referente = r.id AND id_idioma = '".$mylang."'
        WHERE ".$filtroDeriv." dic.id_idioma = ".$geto." 
            AND dic.id_forma_dicionario = 0;";
            
    $a = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
  }
  
  //ainda não achou a palavra exata: buscar outras parecidas
  if (mysqli_num_rows($a)<1){
    $orig['exata'] = 0;
    $sql = "SELECT p.*,d.descricao as referente, r.id as refid, c.id as clid,
            d.detalhes as refDesc, ".$pnq." g.gloss as cgloss, c.nome as cnome 
        FROM palavras p
        LEFT JOIN palavras_referentes pr ON pr.id_palavra = p.id 
        LEFT JOIN classes c ON p.id_classe = c.id
        LEFT JOIN glosses g ON c.id_gloss = c.id
        LEFT JOIN referentes r ON pr.id_referente = r.id ".$pnp." 
        LEFT JOIN referentes_descricoes d ON d.id_referente = r.id AND id_idioma = '".$mylang."'
        WHERE ".$filtroLike." p.id_idioma = ".$geto."  
        ORDER BY p.id_forma_dicionario;";
    $a = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
  }

  if (mysqli_num_rows($a)>0){
    if(mysqli_num_rows($a)==1){

      //palavra exata encontrada !!
      $ax = mysqli_fetch_assoc($a);
      //print_r($ax);

      // palavras que têm este mesmo referente, mas na lingua do usuário logado
      // LIST palavras where id_idioma = mylang and referente = este referente
      $sinonimos = '';

      ///*
      if ($ax['refid']>0){
        $p = mysqli_query($GLOBALS['dblink'],"SELECT p.* FROM palavras p
          LEFT JOIN palavras_referentes pr ON pr.id_palavra = p.id 
          WHERE pr.id_referente = ".$ax['refid']." AND p.id_idioma = ".$mylang."
          AND p.id_forma_dicionario = 0;") or die(mysqli_error($GLOBALS['dblink']));

        while($px = mysqli_fetch_assoc($p)){
          $sinonimos .= "\n".$px['significado'];
        }
      }
      //*/
      
      //$orig['gloss'] = $ax['referente'];
      $orig['id_classe'] = $ax['clid'];
      $orig['gloss'] = '<span data-toggle="tooltip" data-placement="bottom" title="'.$ax['cnome'].'">'.$ax['cgloss'].'</span>';//$ax['cgloss'];
      $orig['pron'] = '/'.$ax['pronuncia'].'/';
      $orig['rom'] = $ax['romanizacao'];
      $orig['nat'] = $ax['nativa'];
      $orig['refid'] = $ax['refid'];
      $orig['glossdet'] = $ax['refDesc']."\n".$sinonimos;
      
      $orig['cgloss'] = $ax['cgloss'];
      $orig['romanizacao'] = $ax['romanizacao'];
      $orig['nativo'] = $ax['nativa'];

      //buscar glosses (classe, itens, opcao/item)
      $b = mysqli_query($GLOBALS['dblink'],"SELECT i.*, g.gloss FROM itens_palavras ip
        LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
        LEFT JOIN gloss_itens gi ON gi.id_item = i.id
        LEFT JOIN glosses g ON gi.id_gloss = g.id 
        WHERE ip.id_palavra = ".$ax['id']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
      while($bx = mysqli_fetch_assoc($b)){
        $orig['gloss'] .= '-<span data-toggle="tooltip" data-placement="bottom" title="'.$bx['nome'].'">'.$bx['gloss'].'</span>';
        $orig['glossArray'][] = $bx;
      }


    }else{

      //mais de uma palavra parecida encontrada
      //guess / desambiguação
      //usar contexto geral ?

      
      //pegou a ultima palavra encontrada, qualquer que seja
      //xxxxx  alterar aqui pra ser mais coerente

      while($tx = mysqli_fetch_assoc($a)){
        //print_r($tx);
        $ax = $tx;
      }


      $sinonimos = '';
      if ($ax['refid']>0){
        $p = mysqli_query($GLOBALS['dblink'],"SELECT p.* FROM palavras p
          LEFT JOIN palavras_referentes pr ON pr.id_palavra = p.id 
          WHERE pr.id_referente = ".$ax['refid']." AND p.id_idioma = ".$mylang."
          AND p.id_forma_dicionario = 0;") or die(mysqli_error($GLOBALS['dblink']));

        while($px = mysqli_fetch_assoc($p)){
          $sinonimos .= "\n".$px['significado'];
        }
      }

      $orig['id_classe'] = $ax['clid'];
      $orig['gloss'] = '<span data-toggle="tooltip" data-placement="bottom" title="'.$ax['cnome'].'">'.$ax['cgloss'].'</span>';//$ax['cgloss'].' ?'; //$orig['gloss'] = $ax['referente'].' ?';
      $orig['pron'] = '/'.$ax['pronuncia'].'/ ?';
      $orig['rom'] = $ax['romanizacao'].' ?';
      $orig['nat'] = $ax['nativa'];
      $orig['refid'] = $ax['refid'];
      $orig['glossdet'] = $ax['refDesc']."\n".$sinonimos;
      
      $orig['cgloss'] = $ax['cgloss'];
      $orig['romanizacao'] = $ax['romanizacao'];
      $orig['nativo'] = $ax['nativa'];

      //buscar glosses (classe, itens, opcao/item)
      $b = mysqli_query($GLOBALS['dblink'],"SELECT i.* FROM itens_palavras ip
        LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
        WHERE ip.id_palavra = ".$ax['id']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
      while($bx = mysqli_fetch_assoc($b)){
        $orig['gloss'] .= '-<span data-toggle="tooltip" title="'.$bx['nome'].'">'.$bx['gloss'].'</span>';
      }

    }

  }else{
    //não encontrado no dicionário
    $orig['id_classe'] = 0;
    $orig['gloss'] = '-';
    $orig['glossdet'] = 'Não encontrado';//$_t['NaoEncontrado'];
    $orig['pron'] = '-';
    $orig['rom'] = '-';
    $orig['refid'] = 0;
    $orig['nat'] = '>';
    
    $orig['cgloss'] = '';
    $orig['romanizacao'] = '*';
    $orig['nativo'] = '*';
  }
  return $orig;

};

function getCombosPalavra($pid, $idDepende = 0){ // otimizar sql queries
  $result = mysqli_query($GLOBALS['dblink'],"SELECT c.*, p.id_idioma as iid, p.id_forma_dicionario as id_dic,
        (SELECT COUNT(*) FROM itens_palavras WHERE id_palavra = $pid) as itensPresentes 
        FROM palavras p
        LEFT JOIN classes c ON c.id = p.id_classe
        WHERE p.id = ".$pid.";") or die(mysqli_error($GLOBALS['dblink']));
  $classe = mysqli_fetch_assoc($result);
  $retorno = '';

  $result = mysqli_query($GLOBALS['dblink'],"SELECT *, ic.id_concordancia AS concordancia_id FROM itensConcordancias ic 
        LEFT JOIN concordancias c ON c.id = ic.id_concordancia 
        LEFT JOIN palavras p ON p.id_classe = c.id_classe
        WHERE p.id = ".$pid."  AND c.depende = $idDepende GROUP BY c.id;") or die(mysqli_error($GLOBALS['dblink']));

  if (mysqli_num_rows($result)===0 && $idDepende === 0 && $classe['itensPresentes'] === 0) {
    
      mysqli_query($GLOBALS['dblink'],"DELETE FROM itens_palavras WHERE id_palavra = ".$pid." ;") or die(mysqli_error($GLOBALS['dblink']));
  }
  while($r = mysqli_fetch_assoc($result)){ 
    
    $itemSel = 0; $concord = 0;
    $resultc = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itens_palavras
        WHERE id_palavra = ".$pid." AND id_concordancia = ".$r['concordancia_id'].";") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($resultc)==0){
        //get padrao
        $resdef = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias
          WHERE id_concordancia = ".$r['concordancia_id']." AND padrao < 2 ORDER BY padrao DESC, ordem;") or die(mysqli_error($GLOBALS['dblink']));
        $rc = mysqli_fetch_assoc($resdef);
        //insert padrao
        mysqli_query($GLOBALS['dblink'],"INSERT INTO itens_palavras SET id_item = ".$rc['id'].", id = ".generateId().",
          id_palavra = ".$pid.", id_concordancia = ".$r['concordancia_id'].", usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
        //return id padrão
        $itemSel = $rc['id'];
    }else{
        //return id salvo
        $rc = mysqli_fetch_assoc($resultc);
        $itemSel = $rc['id_item'];
    }
    
    $retorno .= '<div class="mb-2"><label class="form-label">'.$r['nome'].'</label>
        <input type="hidden" value="'.$r['concordancia_id'].'" class="ipid"/>
        <select id="idc_'.$r['concordancia_id'].'" class="form-select" onchange="salvarItem(\''.$r['concordancia_id'].'\')">';

    if ($classe['id_dic']>0) $fd = "AND id_forma_dicionario = ".$classe['id_dic'];
    else $fd = "AND id_forma_dicionario = ".$classe['id_dic'];

    
    $result3 = mysqli_query($GLOBALS['dblink'],"SELECT i.*, 
        (SELECT pu.pronuncia 
          FROM palavras pu 
            LEFT JOIN itens_palavras ip ON ip.id_palavra = pu.id
          WHERE pu.id_idioma = ".$classe['iid']." AND id_item = i.id AND id_concordancia = c.id ".$fd." 
          LIMIT 1) as p_ocupado
        FROM itensConcordancias i
        LEFT JOIN concordancias c ON c.id = i.id_concordancia
        WHERE id_concordancia = ".$r['concordancia_id']." ORDER BY i.ordem;") or die(mysqli_error($GLOBALS['dblink']));
        while($r3 = mysqli_fetch_assoc($result3)){
          $retorno .=  '<option value="'.$r3['id'].'" '; 

          if ($itemSel == $r3['id']) $retorno .=  ' selected ';
          $pex = '';

          $retorno .= '>'.$r3['nome'].($r3['padrao']==1 ? ' ('._t('padrão').')' : ( $r3['padrao']==2 ? ' ('._t('flexão').')' : '') ).$pex.'</option>';
        }

        $retorno .=  '</select></div>';
        
    $retorno .= getCombosPalavra($pid,$itemSel);
    
  };
  return $retorno;
};

function getCombosGenPalavra($pid){ 
  $result = mysqli_query($GLOBALS['dblink'],"SELECT p.*, 
      (SELECT id_genero FROM classesGeneros WHERE id_palavra = ".$pid." AND id_classe = p.id_classe LIMIT 1) as idg 
      FROM palavras p
        WHERE p.id = ".$pid.";") or die(mysqli_error($GLOBALS['dblink']));
  $c = mysqli_fetch_assoc($result);
  $retorno = '';

  // pega o ID da/s concordancia/s inicial/s da classe da palavra

  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM generos
        WHERE id_classe = ".$c['id_classe']." ;") or die(mysqli_error($GLOBALS['dblink']));

  if (mysqli_num_rows($result)==0) {
    // se nao tem nenhuma concordancia, eliminar todos os glosses da classe pra essa palavra
      mysqli_query($GLOBALS['dblink'],"DELETE FROM classesGeneros WHERE id_palavra = ".$pid." ;") or die(mysqli_error($GLOBALS['dblink']));
  }else{
    // 
    $retorno = '<div class="mb-3"><label class="form-label">'._t('Gênero').'</label>
      <select id="idgp" class="form-select" onchange="salvarGenPal()">
    <option value="0" selected >'._t('Selecionar').'...</option>';
    while($r = mysqli_fetch_assoc($result)){
      $retorno .=  '<option value="'.$r['id'].'" '; 
      if ($c['idg'] == $r['id']) $retorno .=  ' selected ';
      $retorno .= '>'.$r['nome'].'</option>';
    };
    $retorno .=  '</select></div>';
  }


  return $retorno;
};

function limparOpcoesCombos($pid,$idOpcao){ 

  //se for depende = 0
  //mysqli_query($GLOBALS['dblink'],"UPDATE itens_palavras SET usar = 0 WHERE id_palavra = ".$pid." ;") or die(mysqli_error($GLOBALS['dblink']));

  /*
    SELECT * FROM itensConcordancias ic
    LEFT JOIN concordancias c ON ic.id_concordancia =  c.id
    WHERE ic.id_concordancia = ( SELECT y.id_concordancia FROM itensConcordancias y WHERE y.id = 11 LIMIT 1 )
    AND ic.padrao = 2

    retorna id da opcao que tem q buscar dependentes

    SELECT * FROM concordancias c 
        WHERE c.depende = ( 
    SELECT ic.id FROM itensConcordancias ic
    LEFT JOIN concordancias c ON ic.id_concordancia =  c.id
    WHERE ic.id_concordancia = ( SELECT y.id_concordancia FROM itensConcordancias y WHERE y.id = 11 LIMIT 1 )
    AND ic.padrao = 2  )
  */

  /*$result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias c 
    LEFT JOIN itensConcordancias ic ON ic.id_concordancia = c.id 
    LEFT JOIN palavras p ON p.id_classe = c.id_classe 
    WHERE c.id = ".$idOpcao."  GROUP BY c.id;") or die(mysqli_error($GLOBALS['dblink']));*/

    //teste
    echo ' limpar opcao '.$idOpcao; 
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias c 
        LEFT JOIN itensConcordancias ii ON ii.id_concordancia = c.id 
            WHERE c.depende = ( 
        SELECT ic.id FROM itensConcordancias ic
        LEFT JOIN concordancias c ON ic.id_concordancia =  c.id
        WHERE ic.id_concordancia = ( SELECT y.id_concordancia FROM itensConcordancias y WHERE y.id = ".$idOpcao." LIMIT 1 )
        AND ic.padrao = 2  ) AND padrao = 2 GROUP BY c.id") or die(mysqli_error($GLOBALS['dblink']));

  while($r = mysqli_fetch_assoc($result)){
      print_r($r);
      return;

      //if ($r['depende']==0) {
      // mysqli_query($GLOBALS['dblink'],"UPDATE itens_palavras SET usar = 0 WHERE id_palavra = ".$pid." AND id_concordancia == ".$r['id_concordancia'].";") or die(mysqli_error($GLOBALS['dblink']));
      //  return;
      //} 
    
      /*$resdef = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias 
            WHERE id_concordancia = ".$r['id_concordancia']." AND padrao = 2;") or die(mysqli_error($GLOBALS['dblink']));
      while($rc = mysqli_fetch_assoc($resdef)){
        limparOpcoesCombos($pid,$rc['id']);
      }*/

      /*
      $itemSel = 0; $concord = 0;
      $resultc = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itens_palavras 
          WHERE id_palavra = ".$pid." AND id_concordancia = ".$r['id_concordancia'].";") or die(mysqli_error($GLOBALS['dblink']));

      if (mysqli_num_rows($resultc)==0){
          //get padrao
          $resdef = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias 
            WHERE id_concordancia = ".$r['id_concordancia']." AND padrao = 2;") or die(mysqli_error($GLOBALS['dblink']));
          $rc = mysqli_fetch_assoc($resdef);
          //insert padrao
          mysqli_query($GLOBALS['dblink'],"INSERT INTO itens_palavras SET id_item = ".$rc['id'].",
            id_palavra = ".$pid.", id_concordancia = ".$r['id_concordancia'].", usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
          //return id padrão
          $itemSel = $rc['id'];
      }else{
          //return id salvo
          $rc = mysqli_fetch_assoc($resultc);
          $itemSel = $rc['id_item'];
      }
      */

      //limparOpcoesCombos($pid,$itemSel);
  }
  return;
};

function formatCategoriesAsButtons($categoriesText) {
    // Dividir o texto em linhas
    $lines = explode("\n", trim($categoriesText));
    $html = '';

    // Processar cada linha
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignorar linhas vazias
        if (empty($line)) continue;

        // Dividir a linha em nome da classe e sons (antes e depois do '=')
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue; // Ignorar linhas mal formadas

        $className = trim($parts[0]);
        $sounds = trim($parts[1]);

        // Escapar os valores para HTML
        $classNameEscaped = htmlspecialchars($className);
        //$soundsEscaped = htmlspecialchars($sounds);

        // Gerar o botão com tooltip
        $html .= '<a class="btn btn-sm" data-bs-toggle="tooltip" title="' . $sounds . '" onclick="copyToClipboard(this)">' . $classNameEscaped . '</a>';
    }

    $html .= '';
    return $html;
}

function getSCHeader($motor='',$iid,$tipo=''){ // depende da tela de pronuncia e sons

  // buscar motor padrao do idioma iid 
  $is = mysqli_query($GLOBALS['dblink'],"SELECT motor, silabas FROM idiomas WHERE id = ".$iid." LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  $r = mysqli_fetch_assoc($is);
  $silabas = 1;
  if ($r['silabas']>1) $silabas = $r['silabas'];
  if ($motor == ''){
      if (strlen( $r['motor'] )>0) $motor = $r['motor'];
      else $motor = 'sca2'; // motor default
  }

  $header = array(); // motores disponíveis
  $header['sca2'] = "#categories{\n";
  $header['gen'] = "#categories{\n";
  $header['awk'] = "#categories{\n";
  $header['trisca'] = "";
  $header['ksc'] = "";
  $header['lexurgy'] = "";
  $header['lexifer'] = "letters: ";

  $header['regex'] = "";

  $is = mysqli_query($GLOBALS['dblink'],"SELECT s.ipa, p.ipa as ipa2 FROM inventarios i
      LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
      LEFT JOIN sonsPersonalizados p ON ( p.id = i.id_som AND i.id_tipoSom = 0 )
      WHERE i.id_idioma = ".$iid.";") or die(mysqli_error($GLOBALS['dblink']));
  while($r = mysqli_fetch_assoc($is)) { 
    $header['lexifer'] .= $r['ipa'].$r['ipa2'].' ';
    if ($motor == ''){
      if (strlen( $r['motor'] )>0) $motor = $r['motor'];
      else $motor = 'sca2'; // motor default

    }
  };
  $header['lexifer'] .= "\n\n";

  if($iid>0){

    // classes de sons (tipo 'cats')
    $cats['sca2'] = "";
    $cats['gen'] = "";
    $cats['awk'] = "";
    $cats['trisca'] = "";
    $cats['lexurgy'] = "";
    $cats['lexifer'] = "";

    $categorias = [];

    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM classesSom
      WHERE id_idioma = ".$iid.";") or die(mysqli_error($GLOBALS['dblink']));
    while($r = mysqli_fetch_assoc($result)) { 

      $cats['sca2'] .= "  ".$r['simbolo'].'=';
      $cats['ksc'] .=  $r['simbolo'].' = ';
      $cats['gen'] .= "  ".$r['simbolo'].'=';
      $cats['awk'] .= "  ".$r['simbolo'].'=';
      $cats['trisca'] .= $r['simbolo'].'=';
      $cats['lexurgy'] .= 'Class '.$r['simbolo'].' {';
      $cats['lexifer'] .= $r['simbolo'].' = ';

      $result2 = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(s.ipa) as sons1 FROM inventarios i
            LEFT JOIN sons s ON (i.id_som = s.id )
            LEFT JOIN sons_classes sc ON (sc.tipo = 1 AND i.id = sc.id_som)
          WHERE sc.id_classeSom = ".$r['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      $r2 = mysqli_fetch_assoc($result2);
        
      $result3 = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(ipa) as sons1 FROM sonsPersonalizados sp
          LEFT JOIN sons_classes sc ON (sc.tipo = 2 AND sp.id = sc.id_som)
          WHERE sc.id_classeSom = ".$r['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      $r3 = mysqli_fetch_assoc($result3);

      $glifos = $r2['sons1'].','.$r3['sons1'];
      $cats['sca2'] .= str_replace(',','',$glifos)."\n";
      $cats['gen'] .= str_replace(',','',$glifos)."\n";
      $cats['awk'] .= str_replace(',','',$glifos)."\n";
      $cats['trisca'] .= str_replace(',','',$glifos)."\n";
      $cats['ksc'] .= str_replace(',',', ',substr($glifos,0,-1))."\n";
      $cats['lexurgy'] .= substr($glifos,0,-1)."}\n";
      $cats['lexifer'] .= str_replace(',',' ',$glifos)."\n";

      $categorias[$r['simbolo']] = explode(',',$glifos);

    };

    if ($motor == 'ksc') {
        // Buscar nomes das classes de ipaTitulos para dimx
        $result = mysqli_query($GLOBALS['dblink'], "SELECT nome, pos, dimensao FROM ipaTitulos WHERE id_idioma = ".$iid." ORDER BY nome;") or die(mysqli_error($GLOBALS['dblink']));
        
        
        while ($r = mysqli_fetch_assoc($result)) {
            if ($r['dimensao'] > 8) $classNames = ['%'];
            else $classNames = preg_split('/[\s\/]+/', $r['nome'], -1, PREG_SPLIT_NO_EMPTY);
            $sons = [];
            
            // Determinar posField com base no módulo de dimensao
            $mod = $r['dimensao'] % 4;
            if ($mod == 1) {
                $posField = 'posy';
            } else if ($mod == 2) {
                $posField = 'posx';
            } else {
                $posField = 'posz';
            }

            // Determinar tipo com base no intervalo de dimensao
            if ($r['dimensao'] <= 4) {
                $tipoSom = 1; // Consoantes
            } else if ($r['dimensao'] <= 8) {
                $tipoSom = 2; // Vogais
            } else {
                $tipoSom = 3; // Suprasegmentais
            }

            // Buscar sons para a posição e tipo correspondentes
            $is = mysqli_query($GLOBALS['dblink'], "SELECT DISTINCT s.ipa, p.ipa as ipa2 FROM inventarios i
                LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
                LEFT JOIN sonsPersonalizados p ON (p.id = i.id_som AND i.id_tipoSom = 0)
                WHERE (s.".$posField." = ".$r['pos']." OR p.".$posField." = ".$r['pos'].")
                AND i.id_idioma = ".$iid." AND (s.id_tipoSom = ".$tipoSom." OR p.id_tipoSom = ".$tipoSom.");") or die(mysqli_error($GLOBALS['dblink']));
            
            while ($i = mysqli_fetch_assoc($is)) {
                if ($i['ipa'] || $i['ipa2']) {
                    $sons[] = $i['ipa'] ?: $i['ipa2'];
                }
            }

            // Adicionar cada nome de classe como uma categoria separada
            foreach ($classNames as $className) {
                //$className = trim($className);
                // Remover caracteres especiais, mantendo acentos
                $cleanedName = preg_replace('/[^%A-Za-zÀ-ÿ]/u', '', trim($className));
                if (empty($cleanedName)) continue; // Ignorar nomes vazios após remoção
                
                // Capitalizar apenas a primeira letra, mantendo acentos
                if (function_exists('mb_strtoupper') && function_exists('mb_strtolower')) {
                    $firstChar = mb_strtoupper(mb_substr($cleanedName, 0, 1));
                    $rest = mb_strtolower(mb_substr($cleanedName, 1));
                    $className = $firstChar . $rest;
                } else {
                    // Fallback para sistemas sem mbstring
                    $className = ucfirst(strtolower($cleanedName));
                }
                if (!isset($categorias[$className])) {
                    $categorias[$className] = [];
                }
                $categorias[$className] = array_unique(array_merge($categorias[$className], $sons));
            }
        }
        // Gerar a string de categorias
        foreach ($categorias as $categoria => $sons) {
            $sonsStr = implode(', ', array_filter($sons));
            if ($sonsStr) { // Apenas incluir categorias com sons
                $cats['ksc'] .=  $categoria." = ".$sonsStr."\n";
            }
        }
    }
    
    if($tipo=='cats'){
        return str_replace("  ","",$cats[$motor]);
    }
    // print_r($categorias); die();
    if($tipo=='classes'){
        return $categorias;
    }
    $header['sca2'] .= $cats['sca2'];
    $header['gen'] .= $cats['gen'];
    $header['awk'] .= $cats['awk'];
    $header['ksc'] .= $cats['ksc'];
    $header['trisca'] .= $cats['trisca'];
    $header['lexurgy'] .= $cats['lexurgy'];
    $header['lexifer'] .= $cats['lexifer'];


    //xxxxx ler tbm estrutura silábica
    $genShapes = "";
    $awkShapes = "";
    $lexiferShapes = "";
    $lexiferWords = "words: ";

    if( $motor == 'gen') {
        $fs = mysqli_query( $GLOBALS['dblink'], "SELECT * FROM formasSilaba WHERE id_idioma = ".$iid." AND tipo < 2 ORDER BY tipo DESC LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
        while($f = mysqli_fetch_assoc($fs)){
            $result = mysqli_query($GLOBALS['dblink'],"SELECT f.*, c.simbolo FROM formaSilabaComponente f
                LEFT JOIN classesSom c ON ( f.id_classeSom = c.id )
              WHERE f.id_formaSilaba = ".$f['id']." ORDER BY f.ordem;") or die(mysqli_error($GLOBALS['dblink']));
            if(mysqli_num_rows($result)>0){
              
                $genwf = array("  ");
                while($r = mysqli_fetch_assoc($result)) { 
                  if ($r['obrigatorio']==1) {
                    foreach($genwf as $key => $val) $genwf[$key] .= $r['simbolo'];
                  } else {
                    foreach($genwf as $key => $val){
                        array_push($genwf,$val.$r['simbolo']);
                    }
                  }
                }
                $genShapes .= implode("\n",$genwf);
            }
        }
    
    }else if($motor == 'lexifer') { // fazendo
        
        $fs = mysqli_query($GLOBALS['dblink'],
            "SELECT * FROM formasSilaba WHERE id_idioma = ".$iid." ORDER BY tipo;") or die(mysqli_error($GLOBALS['dblink']));
        
        $lexw = "";
        
        $rows = mysqli_num_rows($fs);
        $lws = array();
        while($f = mysqli_fetch_assoc($fs)){

            $result = mysqli_query($GLOBALS['dblink'],"SELECT f.*, c.simbolo FROM formaSilabaComponente f
                LEFT JOIN classesSom c ON ( f.id_classeSom = c.id )
              WHERE f.id_formaSilaba = ".$f['id']." ORDER BY f.ordem;") or die(mysqli_error($GLOBALS['dblink']));
              
            $lexs = "";
            
            while($r = mysqli_fetch_assoc($result)) { 

              if ($r['obrigatorio']==1) {
                $lexs .= $r['simbolo'];

              } else {
                $lexs .= $r['simbolo'].'?';

              }
            }

            $lexiferShapes .= "\$".$f['tipo']." = ".$lexs."\n";
        }

        if ($rows<2){
          for($i = 0; $i < $silabas; $i++){
            for($j = 0; $j < $i; $j++) $lexw .= "$0";
            $lexw .= "$0 ";
          }
        }else if ($silabas>2){
          for($i = 0; $i < $silabas-1; $i++){
            $lexw .= "$0";
            for($j = 0; $j < $i; $j++) $lexw .= "$1";
            $lexw .= "$2 ";
          }
          $lexw .= '$3';
        }else if ($silabas==2){
            $lexw .= '$0$2 $3';
        }else{
            $lexw .= '$3';
        }

        $lexiferWords .= $lexw;
        
    } else if($motor == 'awk') { 
      
        $fs = mysqli_query($GLOBALS['dblink'],
            "SELECT * FROM formasSilaba WHERE id_idioma = ".$iid." ORDER BY tipo;") or die(mysqli_error($GLOBALS['dblink']));
        if(mysqli_num_rows($fs)>1){
            // multisilabas
            $ass = array();
            while($f = mysqli_fetch_assoc($fs)){
                $result = mysqli_query($GLOBALS['dblink'],"SELECT f.*, c.simbolo FROM formaSilabaComponente f
                    LEFT JOIN classesSom c ON ( f.id_classeSom = c.id )
                  WHERE f.id_formaSilaba = ".$f['id']." ORDER BY f.ordem;") or die(mysqli_error($GLOBALS['dblink']));
                  
                $awkwf = "";
                while($r = mysqli_fetch_assoc($result)) { 
                  if ($r['obrigatorio']==1) {
                    $awkwf .= $r['simbolo'];
                  } else {
                    $awkwf .= '('.$r['simbolo'].')';
                  }
                }
                $ass[$f['tipo']] = $awkwf;
            }
            
            /*if ($silabas>2){
              for($i = 0; $i < $silabas-1; $i++){
                $awkShapes .= $ass[0];
                for($j = 0; $j < $i; $j++) $awkShapes .= '('.$ass[1].') ';
                $awkShapes .= $ass[2].' ';
              }
              $awkShapes .= '/ '.$ass[3];
            }else if ($silabas==2){
                $awkShapes .= $ass[0].$ass[2].' / '.$ass[3];
            }else{
                $awkShapes .= $ass[3];
            }*/
            if (strlen($ass[0])>0) $awkShapes .= $ass[0];
            if ($silabas>2){
              for($i = 0; $i < $silabas-1; $i++){
                for($j = 0; $j < $i; $j++) $awkShapes .= '('.$ass[1].')';
              }
            }
            if (strlen($ass[2])>0) $awkShapes .= $ass[2];
            if (strlen($ass[3])>0) $awkShapes .= '/'.$ass[3];
            
            
        }else{
            $f = mysqli_fetch_assoc($fs);
            $result = mysqli_query($GLOBALS['dblink'],"SELECT f.*, c.simbolo FROM formaSilabaComponente f
                LEFT JOIN classesSom c ON ( f.id_classeSom = c.id )
              WHERE f.id_formaSilaba = ".$f['id']." ORDER BY f.ordem;") or die(mysqli_error($GLOBALS['dblink']));
            
            $awkwf = "";
            while($r = mysqli_fetch_assoc($result)) { 

              if ($r['obrigatorio']==1) {
                foreach($genwf as $key => $val) $genwf[$key] .= $r['simbolo'];
                
                $awkwf .= $r['simbolo'];

              } else {
                foreach($genwf as $key => $val){
                    array_push($genwf,$val.$r['simbolo']);
                }
                
                $awkwf .= '('.$r['simbolo'].')';

              }
            }
            $awkShapes = $awkwf;
            for($i = 0; $i < $silabas-1; $i++){
                $awkShapes = $awkShapes."(".$awkwf.")";
            }
        }
    };


    $header['sca2'] .= "}\n#soundChanges{\n\n}\n#rewriteRules{\n\n}\n";
    $header['gen'] .= "}\n#syllableTypes{\n".$genShapes."\n}\n#rewriteRules{\n\n}\n";
    $header['awk'] .= "}\n#syllableShape{\n".$awkShapes."\n}\n";
    $header['lexifer'] .= "\n".$lexiferShapes."\n".$lexiferWords."\n";


    
  }else{
    
    $cats['sca2'] = "  C=ptknsh\n  V=aio\n";
    $cats['gen'] = "  C=ptknsh\n  V=aio\n";
    $cats['awk'] = "  C=ptknsh\n  V=aio\n";
    $cats['trisca'] = "C=ptknsh\nV=aio\n";
    $cats['lexurgy'] = "Class consoantes {p,t,k,n,s,h}\nClass vogais {a,i,o}\n";
    $cats['lexifer'] = "C = p t k n s h\nV = a i o\n";
    $cats['ksc'] = "C = p, t, k, n, s, h\nV = a, i, o\n";

    
    if($tipo=='cats'){
        return str_replace("  ","",$cats[$motor]);
    }
    if($tipo=='classes'){
        return [
          'C' => ['p','t','k','n','s','h'],
          'V' => ['a','i','o']
        ];
    }
    
    $header['sca2'] .= $cats['sca2'];
    $header['gen'] .= $cats['gen'];
    $header['awk'] .= $cats['awk'];
    $header['trisca'] .= $cats['trisca'];
    $header['lexurgy'] .= $cats['lexurgy'];
    $header['lexifer'] .= $cats['lexifer'];
    
    $header['sca2'] .= "}\n#soundChanges{\n\n}\n#rewriteRules{\n\n}\n";
    $header['gen'] .= "}\n#syllableTypes{\nCV\nCVC\n}\n#rewriteRules{\n\n}\n";
    $header['awk'] .= "}\n#syllableShape{\nCV(CV)(C)\n}\n";
    $header['trisca'] .= "";
    $header['lexurgy'] .= "";
    $header['lexifer'] .= "\nwords: V?CV?CV? V?CV?\n";
  };

  return $header[$motor];
};

function carregarPalavraFlexoes($pid,$dx,$k,$iid,$lin,$col, $extra = null) { // otimizar sql queries

  // buscar ids das flexoes tbm!
  
  $linhas = 0; $colunas = 0; $d=0;
  if ($dx>0) {
    $d = $dx;
  }

  $concs = 0;
  $autogenlist = ""; $autogencount = -1;

  $linhas = 0;
  $colunas = 0;

  $result = mysqli_query($GLOBALS['dblink'],"SELECT *, 
      (SELECT pronuncia FROM palavras WHERE id = p.id_forma_dicionario) as pronDicionario,
      (SELECT g.id_genero FROM classesGeneros g WHERE g.id_palavra = p.id_forma_dicionario LIMIT 1) as genDicionario,
      (SELECT g.id_genero FROM classesGeneros g WHERE g.id_palavra = p.id LIMIT 1) as gen,
      (SELECT paradigma FROM classes c WHERE c.id = p.id_classe LIMIT 1) as paradigma
      FROM palavras p 
      WHERE p.id = ".$pid.";") or die('1834'.mysqli_error($GLOBALS['dblink']));
  $r = mysqli_fetch_assoc($result);
  $class = $r['id_classe'];
  $idioma = $r['id_idioma'];
  $fdic = $r['id_forma_dicionario'] > 0 ? $r['id_forma_dicionario'] : $pid;
  $pronDic = $r['id_forma_dicionario'] > 0 ? $r['pronDicionario'] : $r['pronuncia'];
  $gen = $r['id_forma_dicionario'] > 0 ? 0+$r['genDicionario'] : 0+$r['gen'];
  $parad = (int)$r['paradigma'];

  $paradigma = "";    // paradigma 1: palavras únicas  
  if ($parad == 0) {  // paradigma 0: derivação da forma de dicionario
      $paradigma = " AND ( p.id_forma_dicionario = ".$pid." OR p.id = ".$pid.") ";
      //$fdic = 0;
  }
    
  if ($lin>0) {
    $linhas = $lin; //id da concordancia
  }
  if ($col>0) {
    $colunas = $col; //id da concordancia
  }
  $isTabela = true;
  $extras = 0;

  $tryauto = true; // tentar autoconjugações - configuravel s/n

  $escrita = -1;
  $fonte = 'notosans';
  $langs = mysqli_query($GLOBALS['dblink'],"SELECT e.*, f.arquivo as fonte FROM escritas e 
      LEFT JOIN fontes f ON f.id = e.id_fonte
      WHERE id_idioma = ".$idioma." AND e.padrao = 1;") or die('1856'.mysqli_error($GLOBALS['dblink']));
  $e = mysqli_fetch_assoc($langs);
  if ($e['id']>1) {
    $escrita = $e['id'];
    $fonte = $e['fonte'];
    $id_fonte = $e['id_fonte'];
    $tamanho = $e['tamanho'];
  }

  $ops1 = ''; $ops2 = ''; $opsx = '';
  $result = mysqli_query($GLOBALS['dblink'],"SELECT *, 
        (SELECT COUNT(*) FROM itensConcordancias WHERE id_concordancia = concordancias.id) as listaItens 
        FROM concordancias 
        WHERE id_idioma = ".$idioma." AND id_classe = ".$class." 
        AND depende = ".$d." ORDER BY obrigatorio DESC, listaItens DESC;") or die('1868'.mysqli_error($GLOBALS['dblink']));
  
  if (mysqli_num_rows($result)<2) $isTabela = false;
  else if (mysqli_num_rows($result)>2) $extras = mysqli_num_rows($result);

  $extraPadrao = 0;
  if($extra[0]['did']>0){
    $resdef = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias
        WHERE id_concordancia = ".$extra[0]['did']." AND padrao < 2 ORDER BY padrao DESC, ordem;") or die(mysqli_error($GLOBALS['dblink']));
    $rc = mysqli_fetch_assoc($resdef);
    $extraPadraoItem = $rc['id'];
    if ($extraPadraoItem==$extra[0]['val']) $extraPadrao = 1;
  }

  while ($r = mysqli_fetch_assoc($result)) {
    if ($linhas==0) $linhas = $r['id'];
    if ($colunas==0 || $linhas == $colunas) $colunas = $r['id'];

    $ops1 .= '<option value="'.$r['id'].'"';
    if($r['id'] == $linhas){
    //if($r['id'] != $linhas && $r['id'] != $colunas){
      $ops1 .= ' selected';
    }
    $ops1 .= '>'.$r['nome'].'</option>';
    $ops2 .= '<option value="'.$r['id'].'"';
    if($r['id'] == $colunas){
      $ops2 .= ' selected';
    }
    $ops2 .= '>'.$r['nome'].'</option>';
    
    $opsx .= '<option value="'.$r['id'].'"';
    $opsx .= '>'.$r['nome'].'</option>';
  }

  echo '<div class="col-sm-12"><table class="table table-m-b-none">';
  if ($isTabela){
      echo '<tr><td></td>';
      $yitens = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias 
        WHERE id_concordancia = ".$colunas." ORDER BY ordem;") or die('1918'.mysqli_error($GLOBALS['dblink']));
      while($y = mysqli_fetch_assoc($yitens)){
        echo '<td onclick="autoPreencher('.$linhas.')" class="text-secondary">'.$y['nome'].'</td>';
      }
      echo '</tr>';
  }
  
  $xitens = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias 
    WHERE id_concordancia = ".$linhas." ORDER BY ordem;") or die('1926'.mysqli_error($GLOBALS['dblink']));
  while($x = mysqli_fetch_assoc($xitens)){ // cada row
    echo '<tr><td onclick="autoPreencher('.$colunas.')" class="text-secondary">'.$x['nome'].'</td>';

    if ($isTabela){ 

        if ($extra == null) $concs = 2;
        else $concs = 3;

        mysqli_data_seek($yitens, 0);

        while($y2 = mysqli_fetch_assoc($yitens)){ // while($y2 = mysqli_fetch_assoc($yitens2)){ // render cada coluna da row atual

          //if tem mais 1 dimensao, abrir mais LI dentro das celulas, ou mini tabelinha

          // se tem $extra, add no sql mais um join ?
          
          if ($extra == null){
            $sql = "SELECT *, i1.id as id1, i2.id as id2, i1.padrao as padrao1, i2.padrao as padrao2 FROM itensConcordancias i1
                  JOIN itensConcordancias i2
                  WHERE i1.id_concordancia = ".$linhas." AND i1.id = ".$x['id']." 
                  AND i2.id_concordancia = ".$colunas." AND i2.id = ".$y2['id']."
                  AND (i1.padrao = 2 OR i2.padrao = 2);";
            
            $deps = mysqli_query($GLOBALS['dblink'],$sql) or die('1950'.mysqli_error($GLOBALS['dblink']));
            
            $regra = 0;      

            if (mysqli_num_rows($deps)>0){
                $xx = mysqli_fetch_assoc($deps);
                if ($xx['padrao1']==2) $valx = $xx['id1'];
                if ($xx['padrao2']==2)  $valx = $xx['id2'];
                echo '<td 
                  class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0"  
                  id="0-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0-0"  
                  onclick="alert("teste")">';
                echo '<a class="btn btn-primary" href="?page=editforms&pid='.$pid.'&d='.$valx.'&c='.$x['id'].'">'._t('Abrir tabela').'</a></td>';

            }else{ 

                $sql = "SELECT p.*, 
                  (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = p.id AND pn.id_escrita = ".$escrita." LIMIT 1) as nativa
                  FROM palavras p 
                  LEFT JOIN itens_palavras ip1 ON ip1.id_palavra = p.id  
                  LEFT JOIN itens_palavras ip2 ON ip2.id_palavra = p.id  
                  WHERE (ip1.id_concordancia = ".$linhas." AND ip1.id_item = ".$x['id']." AND ip1.usar = 1) 
                  AND (ip2.id_concordancia = ".$colunas." AND ip2.id_item = ".$y2['id']." AND ip2.usar = 1) 
                  $paradigma
                  AND p.id_idioma = ".$idioma.";";
              //echo $sql;
                $ps = mysqli_query($GLOBALS['dblink'],$sql) or die('1972'.mysqli_error($GLOBALS['dblink']));
                $p = mysqli_fetch_assoc($ps); // se tem 2 palavras aqui dentro, há algum erro????

                $dicionario = $p['id_forma_dicionario']; $nclass = $dicionario>0?'':'text-info';
                $autogen = ""; if (! $p['id'] > 0 && $tryauto && $parad == 0) {  $autogencount++; $dicionario = 0; $nclass = 'text-muted';

                    $px = fetchFlexao($gen, $linhas, $x['id'], $colunas, $y2['id']);
                    if ($px>0) $regra = $px;
                    
                    $autogen = $autogencount."%%";
                    $autogenlist .= $linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].'-'.$regra."\n";

                }

                $irregClass = $p['irregular'] == 1 ? 'text-warning' : ( $p['pronuncia'] ? ( $dicionario == 0 && $parad == 0 ? 'text-info' : '') : 'text-secondary' );
                if ($autogen != "") $spanSec = '<span id="ag'.$autogencount.'" class="'.$irregClass.'">%%r'.$autogen.'</span>';
                else $spanSec = '<span class="'.$irregClass.'">'.$p['romanizacao'].' '.($p['pronuncia']!=''?'<span class="nowrap">/'.$p['pronuncia'].'/</span>':'&nbsp;').'</span>';
                ///$spanNativo = getSpanPalavraNativa($p['nativa'] ?? '%%an'.$autogencount.'%%', $escrita,$id_fonte,$tamanho,$p['nativa']?'<br>':'');
                $spanNativo = $escrita>0 ? getSpanPalavraNativa($p['nativa'] ? $p['nativa'] : ( $parad == 0 ? '%%an'.$autogencount.'%%' : null), $escrita,$id_fonte,$tamanho,$p['nativa']?'<br>':''):null;

                echo '<td draggable="true" ondrop="dropHandler(event)" ondragover="dragoverHandler(event)"  ondragstart="dragstartHandler(event)" 
                  class="'.$irregClass.' cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].'" 
                  id="'.(0+$p['id']).'-'.$linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].'-'.$p['id_forma_dicionario'].'"  
                  onclick="abrirPalavra(\''.(0+$p['id']).'\',\''.$linhas.'\',\''.$colunas.'\',\''.$x['id'].'\',\''.$y2['id'].'\',\''.$x['nome'].' '.$y2['nome'].'\',\''.$fdic.'\',`%%'.$autogen.'`)">
                  '.$spanNativo.$spanSec.'</td>';
            }
          }else{ // tem dado extra, 3a dimensão, TO DO ?
            
            $regra = 0;

            $sql = "SELECT *, 
                  i1.id as id1, 
                  i2.id as id2, 
                  i3.id as id3, 
                  i1.padrao as padrao1, 
                  i2.padrao as padrao2, 
                  i3.padrao as padrao3 
                FROM itensConcordancias i1
                  JOIN itensConcordancias i2
                  JOIN itensConcordancias i3
                WHERE (i1.id_concordancia = ".$linhas." AND i1.id = ".$x['id']." )
                  AND (i2.id_concordancia = ".$colunas." AND i2.id = ".$y2['id'].")
                  AND (i3.id_concordancia = ".$extra[0]['did']." AND i3.id = ".$extra[0]['val'].")
                  AND (i1.padrao = 2 OR i2.padrao = 2 OR i3.padrao = 2);";
            
            $deps = mysqli_query($GLOBALS['dblink'],$sql) or die('1950'.mysqli_error($GLOBALS['dblink']));

            if (mysqli_num_rows($deps)>0){
                $xx = mysqli_fetch_assoc($deps);
                if ($xx['padrao1']==2) $valx = $xx['id1'];
                if ($xx['padrao2']==2)  $valx = $xx['id2'];
                if ($xx['padrao3']==2)  $valx = $xx['id3'];
                //tem dep, abrir link // draggable="true" ondrop="dropHandler(event)" ondragover="dragoverHandler(event)"  ondragstart="dragstartHandler(event)" 
                echo '<td 
                  class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0" 
                  id="0-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0-0"
                  onclick="alert("teste")">';
                echo '<a class="btn btn-primary" href="?page=editforms&pid='.$pid.'&d='.$valx.'&c='.$x['id'].'">'._t('Abrir tabela').'</a></td>';

            }else{
                $xx = mysqli_fetch_assoc($deps);

                // sql funcionando mas join muito grande
                $sql = "SELECT p.*, 
                  (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = p.id AND pn.id_escrita = ".$escrita." LIMIT 1) as nativa
                  FROM palavras p 
                    LEFT JOIN itens_palavras ip1 ON ip1.id_palavra = p.id  
                    LEFT JOIN itens_palavras ip2 ON ip2.id_palavra = p.id  
                    LEFT JOIN itens_palavras ip3 ON ip3.id_palavra = p.id  
                  WHERE (ip1.id_concordancia = ".$linhas." AND ip1.id_item = ".$x['id']." AND ip1.usar = 1) 
                    AND (ip2.id_concordancia = ".$colunas." AND ip2.id_item = ".$y2['id']." AND ip2.usar = 1) 
                    AND (ip3.id_concordancia = ".$extra[0]['did']." AND ip3.id_item = ".$extra[0]['val']." AND ip3.usar = 1)
                    $paradigma 
                    AND p.id_idioma = ".$idioma." ;";
                    
                $ps = mysqli_query($GLOBALS['dblink'],$sql) or die('1972'.mysqli_error($GLOBALS['dblink']));
                $pal = mysqli_fetch_assoc($ps);

                $dicionario = $pal['id_forma_dicionario']; $nclass = $dicionario>0?'':'text-info';
                $autogen = ""; if (! $pal['id'] > 0 && $tryauto) { $autogencount++; $dicionario = 0; $nclass = 'text-muted';
                  
                    $px = fetchFlexao($gen, $linhas, $x['id'], $colunas, $y2['id'], $extra[0]['did'], $extra[0]['val']);
                    if ($px>0) $regra = $px;
                    $autogen = $autogencount."%%";
                    $autogenlist .= $linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].'-'.$regra."\n";

                }

                $irregClass = $pal['irregular'] == 1 ? 'text-warning' : ( $pal['pronuncia'] ? ( $dicionario == 0 && $parad == 0 ? 'text-info' : '') : 'text-secondary' );
                if ($autogen != "") $spanSec = '<span id="ag'.$autogencount.'" class="'.$irregClass.'">%%r'.$autogen.'</span>';
                else $spanSec = '<span class="'.$irregClass.'">'.$pal['romanizacao'].' '.($pal['pronuncia']!=''?'<span class="nowrap">/'.$pal['pronuncia'].'/</span>':'&nbsp;').'</span>';
                $spanNativo = $escrita>0 ? getSpanPalavraNativa($pal['nativa'] ? $pal['nativa'] : ( $parad == 0 ? '%%an'.$autogencount.'%%' : null),$escrita,$id_fonte,$tamanho, $pal['nativa']?'<br>':''):null;
                
                echo '<td draggable="true" ondrop="dropHandler(event)" ondragover="dragoverHandler(event)"  ondragstart="dragstartHandler(event)" 
                  class="'.$irregClass.' cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].'" 
                  id="'.(0+$pal['id']).'-'.$linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].'-'.$pal['id_forma_dicionario'].'" 
                  onclick="abrirPalavra(\''.(0+$pal['id']).'\',\''.$linhas.'\',\''.$colunas.'\',\''.$x['id'].'\',\''.$y2['id'].'\',\''.$x['nome'].' '.$y2['nome'].'\',\''.$fdic.'\',`%%'.$autogen.'`)">
                  '.$spanNativo.$spanSec.'</td>'; //abr tb da palavra, mesmo link colocar no dicionrio tbm
            }

          }
        }
    }else{ // só 1 row
            $concs = 1;
            $regra = 0;

            $sql = "SELECT * FROM itensConcordancias i1
                    WHERE i1.id_concordancia = ".$linhas." AND i1.id = ".$x['id']." 
                    AND i1.padrao = 2;";
            
            $deps = mysqli_query($GLOBALS['dblink'],$sql) or die('2082'.mysqli_error($GLOBALS['dblink']));

            if (mysqli_num_rows($deps)>0){
                echo '<td 
                  class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0" 
                  id="0-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0-0"
                  onclick="alert("teste")">';
                echo '<a class="btn btn-primary" href="?page=editforms&pid='.$pid.'&d='.$x['id'].'&c='.$x['id'].'">'._t('Abrir tabela').'</a></td>';

            }else{

                $sql = "SELECT p.*, pn.palavra as nativa FROM palavras p 
                  LEFT JOIN itens_palavras ip1 ON ip1.id_palavra = p.id  
                  LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id AND pn.id_escrita = ".$escrita."
                  WHERE (ip1.id_concordancia = ".$linhas." AND ip1.id_item = ".$x['id']." AND ip1.usar = 1) 
                  $paradigma
                  AND p.id_idioma = ".$idioma.";";
                  //echo $sql;
                $ps = mysqli_query($GLOBALS['dblink'],$sql) or die('2100'.mysqli_error($GLOBALS['dblink']));
                $p = mysqli_fetch_assoc($ps);
                
                $dicionario = $p['id_forma_dicionario']; $nclass = $dicionario>0?'':'text-info';
                $autogen = ""; if (! $p['id'] > 0 && $tryauto) { $autogencount++; $dicionario = 0; $nclass = 'text-muted';
                    
                    $px = fetchFlexao($gen, $linhas, $x['id']);
                    if ($px > 0) $regra = $px;
                    $autogen = $autogencount."%%";
                    $autogenlist .= $linhas.'-'.$colunas.'-'.$x['id'].'-0-'.$regra."\n";

                }

                $irregClass = $p['irregular'] == 1 ? 'text-warning' : ( $p['pronuncia'] ? ( $dicionario == 0 && $parad == 0 ? 'text-info' : '') : 'text-secondary' );
                if ($autogen != "") $spanSec = '<span id="ag'.$autogencount.'" class="'.$irregClass.'">%%r'.$autogen.'</span>';
                else $spanSec = '<span class="'.$irregClass.'">'.$p['romanizacao'].' '.($p['pronuncia']!=''?'<span class="nowrap">/'.$p['pronuncia'].'/</span>':'&nbsp;').'</span>';
                $spanNativo = $escrita>0 ? getSpanPalavraNativa($p['nativa'] ? $p['nativa'] : ( $parad == 0 ? '%%an'.$autogencount.'%%' : null),$escrita,$id_fonte,$tamanho,$p['nativa']?'<br>':''):null;
                
                echo '<td draggable="true" ondrop="dropHandler(event)" ondragover="dragoverHandler(event)"  ondragstart="dragstartHandler(event)" 
                  class="'.$irregClass.' cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0" 
                  id="'.(0+$p['id']).'-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0-'.$p['id_forma_dicionario'].'" 
                  onclick="abrirPalavra(\''.(0+$p['id']).'\',\''.$linhas.'\',\''.$colunas.'\',\''.$x['id'].'\',0,\''.$x['nome'].'\',\''.$fdic.'\',`%%'.$autogen.'`)">
                  '.$spanNativo.$spanSec.'</td>'; //abr tb da palavra, mesmo link colocar no dicionrio tbm
            }
    }

    echo '</tr>';
  }
  echo '</table></div>';
  echo '%%%'.$autogenlist.$pronDic.'%%%';

  if ($parad == 0) $pfilter = "AND p.id_forma_dicionario = $pid ";
  else $pfilter = "AND p.id_classe = $class ";
            
  $sql = "SELECT p.*, pn.palavra as nativa,
          (SELECT GROUP_CONCAT(ic.nome SEPARATOR ', ' ) 
              FROM itens_palavras ip 
              LEFT JOIN concordancias c ON ip.id_concordancia = c.id 
              LEFT JOIN itensConcordancias ic ON ip.id_item = ic.id
          WHERE id_palavra = p.id) as concs,
      (SELECT COUNT(id) FROM itens_palavras WHERE id_palavra = p.id) as ips FROM palavras p 
        LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id AND pn.id_escrita = $escrita 
        WHERE p.id_idioma = $idioma
        $pfilter ;";
  $ps = mysqli_query($GLOBALS['dblink'],$sql) or die('2100'.mysqli_error($GLOBALS['dblink']));
  while($p = mysqli_fetch_assoc($ps)){
        echo '<div draggable="true" ondragstart="dragstartHandler(event)" class="'.($p['ips']>0?'nao-vazio':'').'"
                id="'.(0+$p['id']).'-0-0-0-0-'.$p['id_forma_dicionario'].'">'.getSpanPalavraNativa($p['nativa'],$escrita,$id_fonte,$tamanho).
                $p['romanizacao'].' '.($p['pronuncia']!=''?'<span class="nowrap">/'.$p['pronuncia'].'/</span>':'&nbsp;').' <span class="text-secondary">'.$p['concs'].'</span></div>';
  }
};

function inserirFlexao($gen = 0, $linhas, $x = 0, $colunas = null, $y = 0, $extra = null, $z = 0){
    $idPalavra = generateId();
    $insf = "INSERT INTO flexoes SET
        nome = 'Sem nome',
        id = $idPalavra,
        ordem = 0, motor = '',
        regra_romanizacao = '',
        regra_pronuncia = '';";
    mysqli_query($GLOBALS['dblink'],$insf) or die(mysqli_error($GLOBALS['dblink']));

    if ($linhas && $x > 0) {
        $insf = "INSERT INTO itens_flexoes SET  id = ".generateId().",
          id_flexao = ".$idPalavra.",
          id_concordancia = ".$linhas.",
          id_genero = ".$gen.",
          id_item = ".$x.";";
        mysqli_query($GLOBALS['dblink'],$insf) or die('2043'.mysqli_error($GLOBALS['dblink']));
    }
    if ($colunas && $y > 0) {
      $insf = "INSERT INTO itens_flexoes SET  id = ".generateId().",
          id_flexao = ".$idPalavra.",
          id_concordancia = ".$colunas.",
          id_genero = ".$gen.",
          id_item = ".$y.";";
        mysqli_query($GLOBALS['dblink'],$insf) or die('2048'.mysqli_error($GLOBALS['dblink']));
    }
    if ($extra && $z > 0) {
      $insf = "INSERT INTO itens_flexoes SET  id = ".generateId().",
          id_flexao = ".$idPalavra.",
          id_concordancia = ".$extra.",
          id_genero = ".$gen.",
          id_item = ".$z.";";
        mysqli_query($GLOBALS['dblink'],$insf) or die('2048'.mysqli_error($GLOBALS['dblink']));
    }
    return $idPalavra;
}

function fetchFlexao($gen = 0, $linhas, $x = 0, $colunas = null, $y = 0, $extra = null, $z = 0){

    if ($extra && $z > 0)
        $sql = "SELECT f.* FROM flexoes f 
            LEFT JOIN itens_flexoes if1 ON if1.id_flexao = f.id  
            LEFT JOIN itens_flexoes if2 ON if2.id_flexao = f.id 
            LEFT JOIN itens_flexoes if3 ON if3.id_flexao = f.id   

            WHERE (if1.id_concordancia = ".$linhas." AND if1.id_item = ".$x.") 
            AND (if2.id_concordancia = ".$colunas." AND if2.id_item = ".$y.") 
            AND (if3.id_concordancia = ".$extra." AND if3.id_item = ".$z.") 
            AND if1.id_genero = ".$gen." AND if2.id_genero = ".$gen." AND if3.id_genero = ".$gen."
            ;";
    else if($colunas && $y > 0) 
        $sql = "SELECT f.* FROM flexoes f 
            LEFT JOIN itens_flexoes if1 ON if1.id_flexao = f.id  
            LEFT JOIN itens_flexoes if2 ON if2.id_flexao = f.id  

            WHERE (if1.id_concordancia = ".$linhas." AND if1.id_item = ".$x.") 
                AND (if2.id_concordancia = ".$colunas." AND if2.id_item = ".$y.") 
            AND if1.id_genero = ".$gen." AND if2.id_genero = ".$gen."
            ;";
    else 
        $sql = "SELECT f.* FROM flexoes f 
            LEFT JOIN itens_flexoes if1 ON if1.id_flexao = f.id  
            WHERE (if1.id_concordancia = ".$linhas." AND if1.id_item = ".$x.")
            AND if1.id_genero = ".$gen." 
            ;";
                      
    $psx = mysqli_query($GLOBALS['dblink'],$sql) or die('2025'.mysqli_error($GLOBALS['dblink']));
    if( mysqli_num_rows($psx) == 0 ){
        return inserirFlexao($gen, $linhas, $x, $colunas, $y, $extra, $z);
    }else
        return mysqli_fetch_assoc($psx)['id'];
}

function carregarTabelaFlexoes($dx,$k,$iid,$lin,$col,$gen = 0, $extra = null) { // otimizar sql queries
  //pegar lista dos itens/concordancias nesse nivel
  //se tiver mais de uma: formar combobox(es), sem incluir o item inicial (exibido abaixo) ?
  //formar tabela com item inicial
  $linhas = 0; $colunas = 0; $d=0;
  if ($dx>0) {
    $d = $dx;
  }
  
    $linhas = 0;
    $colunas = 0;
    $class = $k;
    $idioma = $iid;

  if ($lin>0) {
    $linhas = $lin; //id da concordancia
  }
  if ($col>0) {
    $colunas = $col; //id da concordancia
  }
  $isTabela = true;
  $extras = 0;

  $escrita = 1;
  $fonte = 'notosans';
  $langs = mysqli_query($GLOBALS['dblink'],"SELECT e.*, f.arquivo as fonte FROM escritas e 
      LEFT JOIN fontes f ON f.id = e.id_fonte
      WHERE id_idioma = ".$idioma." AND e.padrao = 1;") or die('1856'.mysqli_error($GLOBALS['dblink']));
  $e = mysqli_fetch_assoc($langs);
  if ($e['id']>1) {
    $escrita = $e['id'];
    $fonte = $e['fonte'];
  }

  $ops1 = ''; $ops2 = ''; $opsx = '';
  $result = mysqli_query($GLOBALS['dblink'],"SELECT *, 
        (SELECT COUNT(*) FROM itensConcordancias WHERE id_concordancia = concordancias.id) as listaItens 
        FROM concordancias 
        WHERE id_idioma = ".$idioma." AND id_classe = ".$class." 
        AND depende = ".$d." ORDER BY obrigatorio DESC, listaItens DESC;") or die('1868'.mysqli_error($GLOBALS['dblink']));
  
  if (mysqli_num_rows($result)<2) $isTabela = false;
  //else if (mysqli_num_rows($result)>2) 
    $extras = mysqli_num_rows($result);

  while ($r = mysqli_fetch_assoc($result)) {
    if ($linhas==0) $linhas = $r['id'];
    if ($colunas==0 || $linhas == $colunas) $colunas = $r['id'];

    $ops1 .= '<option value="'.$r['id'].'"';
    if($r['id'] == $linhas){
    //if($r['id'] != $linhas && $r['id'] != $colunas){
      $ops1 .= ' selected';
    }
    $ops1 .= '>'.$r['nome'].'</option>';
    $ops2 .= '<option value="'.$r['id'].'"';
    if($r['id'] == $colunas){
      $ops2 .= ' selected';
    }
    $ops2 .= '>'.$r['nome'].'</option>';
    
    $opsx .= '<option value="'.$r['id'].'"';
    $opsx .= '>'.$r['nome'].'</option>';
  }

  echo '<div class="col-sm-12"><table class="table table-m-b-none">';
  if ($isTabela){
      echo '<tr><td></td>';
      $yitens = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias 
        WHERE id_concordancia = ".$colunas." ORDER BY ordem;") or die('1918'.mysqli_error($GLOBALS['dblink']));
      while($y = mysqli_fetch_assoc($yitens)){
        echo '<td onclick="autoPreencher('.$linhas.')" class="text-secondary">'.$y['nome'].'</td>';
      }
      echo '</tr>';
  }
  
  $xitens = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias 
    WHERE id_concordancia = ".$linhas." ORDER BY ordem;") or die('1926'.mysqli_error($GLOBALS['dblink']));
  while($x = mysqli_fetch_assoc($xitens)){
    echo '<tr><td onclick="autoPreencher('.$colunas.')" class="text-secondary">'.$x['nome'].'</td>';

    if ($isTabela){ // tem ao menos 2 dimensões

      //xxxxx mysqli_data_seek($result, 0);

        $yitens2 = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias 
          WHERE id_concordancia = ".$colunas." ORDER BY ordem;") or die('1933'.mysqli_error($GLOBALS['dblink']));

        while($y2 = mysqli_fetch_assoc($yitens2)){ // render cada coluna da row atual

          //if tem mais 1 dimensao, abrir mais LI dentro das celulas, ou mini tabelinha
          // se tem $extra, add no sql mais um join ?

          if ($extra == null){  // apenas 2 dimensões (tabela simples)

            //Regex

            //if (esta for forma desmarcada em ambos x e y) skip, carregaRegra(-1)
            $sql = "SELECT * FROM itensConcordancias i1
                    JOIN itensConcordancias i2
                    WHERE i1.id_concordancia = ".$linhas." AND i1.id = ".$x['id']." 
                    AND i2.id_concordancia = ".$colunas." AND i2.id = ".$y2['id']."
                    AND i1.padrao = 1 AND i2.padrao = 1;";
            //echo $sql;
            $defs = mysqli_query($GLOBALS['dblink'],$sql) or die('1991'.mysqli_error($GLOBALS['dblink']));

            if (mysqli_num_rows($defs)>0 && $d == 0){
              
              echo '<td class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].' text-info"  
                onclick="carregaRegra(-1'.',\''.$linhas.'\',\''.$colunas.'\',\''.$x['id'].'\',\''.$y2['id'].'\',\''.$x['nome'].' '.$y2['nome'].'\',\''.$gen.'\')">('._t('padrão').')</td>';

            }else{

              $sql = "SELECT * FROM itensConcordancias i1
                  JOIN itensConcordancias i2
                  WHERE i1.id_concordancia = ".$linhas." AND i1.id = ".$x['id']." 
                  AND i2.id_concordancia = ".$colunas." AND i2.id = ".$y2['id']."
                  AND (i1.padrao = 2 OR i2.padrao = 2);";
              $deps = mysqli_query($GLOBALS['dblink'],$sql) or die('2005'.mysqli_error($GLOBALS['dblink']));

              if (mysqli_num_rows($deps)>0){
                echo '<td class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0"  
                  onclick="alert("teste")">';
                echo '<a class="btn btn-primary" href="?page=editforms&iid='.$iid.'&k='.$k.'&d='.$x['id'].'&c='.$x['id'].'">'._t('Abrir tabela').'</a></td>';
              }else{
                $semflexao = true;

                while($semflexao){
                          
                    $sql = "SELECT f.* FROM flexoes f 
                        LEFT JOIN itens_flexoes if1 ON if1.id_flexao = f.id  
                        LEFT JOIN itens_flexoes if2 ON if2.id_flexao = f.id  

                        WHERE (if1.id_concordancia = ".$linhas." AND if1.id_item = ".$x['id'].") 
                        AND (if2.id_concordancia = ".$colunas." AND if2.id_item = ".$y2['id'].") 
                        AND if1.id_genero = ".$gen." AND if2.id_genero = ".$gen." 
                        ;";

                    $ps = mysqli_query($GLOBALS['dblink'],$sql) or die('2025'.mysqli_error($GLOBALS['dblink']));
                    if (mysqli_num_rows($ps)==0) {
                      $semflexao = true;
                      inserirFlexao($gen, $linhas, $x['id'], $colunas, $y2['id']);
                    }else{
                      $semflexao = false;
                    }
                }
                $p = mysqli_fetch_assoc($ps);

                echo '<td class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].'-'.$gen.'"  
                  onclick="carregaRegra(\''.(0+$p['id']).'\',\''.$linhas.'\',\''.$colunas.'\',\''.$x['id'].'\',\''.$y2['id'].'\',\''.$x['nome'].' '.$y2['nome'].'\',\''.$gen.'\')">';
                echo ($p['regra_pronuncia']!=''?nl2br($p['regra_pronuncia']).'<br>':' ') ;
                echo '</td>';
            }}
          }else{  // tabela 3d, com extra combobox
            
            $sql = "SELECT * FROM itensConcordancias i1
                    JOIN itensConcordancias i2
                    JOIN itensConcordancias i3
                    WHERE i1.id_concordancia = ".$linhas." AND i1.id = ".$x['id']." 
                    AND i2.id_concordancia = ".$colunas." AND i2.id = ".$y2['id']."
                    AND i3.id_concordancia = ".$extra[0]['did']." AND i3.id = ".$extra[0]['val']."
                    AND i1.padrao = 1 AND i2.padrao = 1 AND i3.padrao = 1;"; //  AND i3.padrao = 1
            $defs = mysqli_query($GLOBALS['dblink'],$sql) or die('1991'.mysqli_error($GLOBALS['dblink']));

            if (mysqli_num_rows($defs)>0 && $d == 0){
              
              echo '<td class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].' text-info"  
                onclick="carregaRegra(-1,\''.$linhas.'\',\''.$colunas.'\',\''.$x['id'].'\',\''.$y2['id'].'\',\''.$x['nome'].' '.$y2['nome'].'\',\''.$gen.'\')">('._t('padrão').')</td>';

            }else{

              $sql = "SELECT * FROM itensConcordancias i1
                JOIN itensConcordancias i2
                JOIN itensConcordancias i3
                WHERE i1.id_concordancia = ".$linhas." AND i1.id = ".$x['id']." 
                AND i2.id_concordancia = ".$colunas." AND i2.id = ".$y2['id']."
                AND i3.id_concordancia = ".$extra[0]['did']." AND i3.id = ".$extra[0]['val']."
                AND (i1.padrao = 2 OR i2.padrao = 2 OR i3.padrao = 2);";
                
              $deps = mysqli_query($GLOBALS['dblink'],$sql) or die('2005'.mysqli_error($GLOBALS['dblink']));

              if (mysqli_num_rows($deps)>0){
                echo '<td class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0"  
                  onclick="alert("teste")">';
                echo '<a class="btn btn-primary" href="?page=editforms&iid='.$iid.'&k='.$k.'&d='.$x['id'].'&c='.$x['id'].'">'._t('Abrir tabela').'</a></td>';
              }else{
                $semflexao = true;
                
                $sql = "SELECT f.* FROM flexoes f 
                      LEFT JOIN itens_flexoes if1 ON if1.id_flexao = f.id  
                      LEFT JOIN itens_flexoes if2 ON if2.id_flexao = f.id 
                      LEFT JOIN itens_flexoes if3 ON if3.id_flexao = f.id   

                      WHERE (if1.id_concordancia = ".$linhas." AND if1.id_item = ".$x['id'].") 
                      AND (if2.id_concordancia = ".$colunas." AND if2.id_item = ".$y2['id'].") 
                      AND (if3.id_concordancia = ".$extra[0]['did']." AND if3.id_item = ".$extra[0]['val'].") 
                      AND if1.id_genero = ".$gen." AND if2.id_genero = ".$gen." AND if3.id_genero = ".$gen." 
                      ;";

                $ps = mysqli_query($GLOBALS['dblink'],$sql) or die('2025'.mysqli_error($GLOBALS['dblink']));
                if (mysqli_num_rows($ps)==0) {
                  $semflexao = true;
                  inserirFlexao($gen, $linhas, $x['id'], $colunas, $y2['id'], $extra[0]['did'], $extra[0]['val']);
                }
                
                $p = mysqli_fetch_assoc($ps);

                echo '<td class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-'.$y2['id'].'-'.$gen.'"  
                  onclick="carregaRegra(\''.(0+$p['id']).'\',\''.$linhas.'\',\''.$colunas.'\',\''.$x['id'].'\',\''.$y2['id'].'\',\''.$x['nome'].' '.$y2['nome'].'\',\''.$gen.'\')">';
                echo ($p['regra_pronuncia']!=''?nl2br($p['regra_pronuncia']).'<br>':' ') ;
                echo '</td>';
            }}
          }
        }
    }else{ // só 1 col / dimensão

            $sql = "SELECT * FROM itensConcordancias i1
                    WHERE i1.id_concordancia = ".$linhas." AND i1.id = ".$x['id']." 
                    AND i1.padrao = 1;";
            $defs = mysqli_query($GLOBALS['dblink'],$sql) or die('2117'.mysqli_error($GLOBALS['dblink']));

            if (mysqli_num_rows($defs)>0 && $d == 0){
              
              echo '<td class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0 text-info"  
                onclick="carregaRegra(-1,\''.$linhas.'\',\''.$colunas.'\',\''.$x['id'].'\',0,\''.$x['nome'].'\',\''.$gen.'\')">('._t('padrão').')</td>';

            }else{

              $sql = "SELECT * FROM itensConcordancias i1
                    WHERE i1.id_concordancia = ".$linhas." AND i1.id = ".$x['id']." 
                    AND i1.padrao = 2;";

              $deps = mysqli_query($GLOBALS['dblink'],$sql) or die('2136'.mysqli_error($GLOBALS['dblink']));

              if (mysqli_num_rows($deps)>0){
                  echo '<td class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0"  
                    onclick="alert("teste")">';
                  echo '<a class="btn btn-primary" href="?page=editforms&iid='.$iid.'&k='.$k.'&d='.$x['id'].'&c='.$x['id'].'">'._t('Abrir tabela').'</a></td>';

              }else{

                $semflexao = true;
                while($semflexao){
                          
                    $sql = "SELECT f.* FROM flexoes f 
                        LEFT JOIN itens_flexoes if1 ON if1.id_flexao = f.id   

                        WHERE (if1.id_concordancia = ".$linhas." AND if1.id_item = ".$x['id'].")
                        AND if1.id_genero = ".$gen." 
                        ;";

                    $ps = mysqli_query($GLOBALS['dblink'],$sql) or die('2156'.mysqli_error($GLOBALS['dblink']));
                    if (mysqli_num_rows($ps)==0) {
                      $semflexao = true;
                      inserirFlexao($gen, $linhas, $x['id']);
                    }else{
                      $semflexao = false;
                    }
                }
                $p = mysqli_fetch_assoc($ps);

                echo '<td class="cell cell-'.$linhas.'-'.$colunas.'-'.$x['id'].'-0-'.$gen.'"  
                  onclick="carregaRegra(\''.(0+$p['id']).'\',\''.$linhas.'\',\''.$colunas.'\',\''.$x['id'].'\',0,\''.$x['nome'].'\',\''.$gen.'\')">';
                echo ($p['regra_pronuncia']!=''?nl2br($p['regra_pronuncia']).'<br>':' ') ;
                echo '</td>';
              }
            }
    }

    echo '</tr>';
  }
  echo '</table></div>';
};

function getSpanPalavraNativa($palavra = '',$eid,$fonte,$tamanho,$suffix = ''){
    $ret = '';
    if ($palavra=='') return ''; //$suffix = '';
    //if (mb_strlen($palavra)>0){
      if($fonte== 3 && mb_strlen($palavra)>0){
          $cs = explode(",",$palavra);
          foreach ($cs as $c) $ret .= '<span class="drawchar drawchar-'.$tamanho.'" style="background-image: url(./writing/'.$eid.'/'.$c.'.png)"></span>';
          //$ret = '';
      }else{
          $ret = '<span class="custom-font-'.$eid.'">'.$palavra.'</span>';
      }
    //}
    return $ret.$suffix;
}
  
function getStudySentence($separadorPalavras,$linha,$id_idioma,$eid,$bin,$fonte,$tamanho){
    $texto = '<div style="display:flex;flex-wrap: wrap;">';
    
    $palavras = separarPalavrasLinha($separadorPalavras,$linha,$id_idioma,$eid,$bin,$fonte,$tamanho)[0];

    $textoLinha = '';
    $linhaExtras = $linha;
    $listaPalavrasUnicas = [];

    for($i = 0; $i < sizeof($palavras); $i++){
      $p = $palavras[$i];
      if ($p) {

        
        $posPalavra = mb_strpos($linhaExtras,$p);
        if ($posPalavra>0) {
          //tem coisa antes
          $textoLinha .= '<div class="nostud">'.mb_substr($linhaExtras,0,$posPalavra).'</div>';
          // remove o inicio, da pos 0 até posPalavra
          $linhaExtras = mb_substr($linhaExtras,$posPalavra);
        }

        
        // get palavra no dicionario kondisonair (nativos/romanizacao)
        $pid = 0;
        $aid = 0;
        $pids = '';
        $rom = '';
        $pron = '';
        $gloss = '';
        $ggloss = '';
        $dic = '';
        $gen = '';
        $ggen = '';
        $trad = '';
        $popup = '';
        $minigloss = '';

        $postexto = '';
        $posProx = 0;

        $palTotal++;

        if ($eid > 0){
            $pnp = "LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id";
            //$pnd = "LEFT JOIN palavrasNativas pn ON pn.id_palavra = dic.id";
            $pnq = "pn.palavra as nativa, ";
            $pno = "pn.palavra ";
        }else{
            $pnp = "";
            //$pnd = "LEFT JOIN palavrasNativas pn ON pn.id_palavra = dic.id";
            $pnq = "p.romanizacao as nativa, ";
            $pno = "p.romanizacao ";
        }

        $sql = "SELECT p.*, c.id as clid, $pnq c.nome as cnome, 
              (SELECT gloss FROM glosses WHERE id = c.id_gloss LIMIT 1) as cgloss,
              (SELECT g.nome FROM classesGeneros cg LEFT JOIN generos g ON g.id = cg.id_genero
                WHERE cg.id_palavra = p.id) as genero,
              (SELECT gl.gloss FROM classesGeneros cg LEFT JOIN generos g ON g.id = cg.id_genero LEFT JOIN glosses gl ON gl.id = g.id_gloss
                WHERE cg.id_palavra = p.id) as ggloss,
              ( SELECT GROUP_CONCAT(g.descricao SEPARATOR ' ') FROM itens_palavras ip
                LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
                  LEFT JOIN gloss_itens gi ON gi.id_item = i.id
                  LEFT JOIN glosses g ON gi.id_gloss = g.id 
                WHERE ip.id_palavra = p.id AND usar = 1 ) as flexnomes,
              ( SELECT GROUP_CONCAT(g.gloss SEPARATOR '.') FROM itens_palavras ip
                  LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
                    LEFT JOIN gloss_itens gi ON gi.id_item = i.id
                    LEFT JOIN glosses g ON gi.id_gloss = g.id 
                  WHERE ip.id_palavra = p.id AND usar = 1 ) as flexgloss,
              (SELECT pd.id FROM palavras pd WHERE pd.id = p.id_forma_dicionario LIMIT 1) as dic,
              (SELECT GROUP_CONCAT(pr.id_referente SEPARATOR '.') FROM palavras_referentes pr
                  WHERE pr.id_palavra = p.id) as refs 
            FROM palavras p
              LEFT JOIN classes c ON p.id_classe = c.id 
              $pnp 
            WHERE $bin $pno = '$p' AND p.id_idioma = $id_idioma -- AND pn.id_escrita = $eid
            ORDER BY p.id_forma_dicionario DESC;"; 
            // AND p.id_forma_dicionario = 0
        //echo $sql;
        $a = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
        
        if (mysqli_num_rows($a)<1){

          //if (!$isOwner) die('<script>window.location = "index.php";</script>');
          // palavra nao existe
          $pst = 9; // 9: não existe no kondisonair ainda ->  0- nova no aprendizado -> 1~4 aprendendo -> 5 aprendida/ignorada/ok
          $popup = null; //_t('Esta palavra não existe no dicionário'); // $popup : popup
          $minigloss = '?';  // $minigloss : minigloss
          $palDesc++;
          $pids = '0,';
          $refs = '';
        }else{
          $palCon++;
          // if > 1, pegar todos os pid com vírgula
          //if ($popup != '') $popup .= "\n";
          
          while($qpid = mysqli_fetch_assoc($a)){
            // pegar dados da palavra
            $pid = $qpid['id'];
            $pids .= $pid.',';
            $rom = $qpid['romanizacao'];
            $pron = $qpid['pronuncia'];
            $sig = $qpid['significado'];
            $dic = $qpid['dic'];
            $ggen = $gen = '';
            $refs = $qpid['refs'];

            if ($qpid['genero'] != '') $gen = ' '.$qpid['genero'];
            if ($qpid['ggloss'] != '') $ggen = '.'.$qpid['ggloss'];

            if ($qpid['flexnomes'] != '') $gen .= ' '.$qpid['flexnomes'];
            if ($qpid['flexgloss'] != '') $ggen .= '.'.$qpid['flexgloss'];

            $gloss = $qpid['cnome'].$gen;
            $ggloss = $qpid['cgloss'].$ggen;

            $trad = '';

            if ($popup != '') $popup .= "<hr class='my-1'>";
            if ($minigloss != '') $minigloss .= "\n";
            
            $popupbox = '';
            $glossbox = '';
            if ($eid > 0 && $rom != '') { $popupbox .= '<strong>'.$rom.'</strong> '; $glossbox .= '<strong>'.$rom.'</strong> '; }
            if ($pron != '') {$popupbox .= '/'.$pron."/<br>\n"; $glossbox .= '/'.$pron."/";}

            // $popupbox: nome classe, genero e flexões
            //if ($gloss != '') {$popupbox .= ' ('.$gloss.")";}
            // utbox: gloss classe, genero e flexoes
            if ($ggloss != '') {$glossbox .= ' '.$ggloss;$popupbox .= ' '.$ggloss;}

            if ($sig != '') {$popupbox .= ": <strong>".$sig.'</strong>'; $glossbox .= ": <strong>".$sig.'</strong>';}
            
            $popup .= $popupbox;
            $minigloss .= $glossbox;
            
          }
          
          $minigloss = str_replace("\n",'<br>',$minigloss);

          if ($listaPalavrasUnicas[$p]['q'] > 0) $listaPalavrasUnicas[$p]['q'] = $listaPalavrasUnicas[$p]['q'] + 1;
          else $listaPalavrasUnicas[$p]['q'] = 1;

          $sqlPst = "SELECT * FROM studason_palavrs WHERE pids LIKE '".substr($pids,0,-1)."%' AND id_usuario = ".($_SESSION['KondisonairUzatorIDX']?:0).";";
          $qpstres = mysqli_query($GLOBALS['dblink'],$sqlPst) or die(mysqli_error($GLOBALS['dblink']));
          if (mysqli_num_rows($qpstres)<1){
              $pst = 0;
              $aid = 0;
              $palNovas++;
          }else{
              $qpst = mysqli_fetch_assoc($qpstres);
              $pst = $qpst['status_aprendido']==''?'0':$qpst['status_aprendido'];
              $aid = $qpst['id']>0 ? $qpst['id'] : 0;

              $listaPalavrasUnicas[$p]['s'] = $pst;

              if ($qpst['status_aprendido']==5) $palOk++;
              else if ($qpst['status_aprendido']>0) $palStud++;
              else $palNovas++;
          }
        }

        //remover esta palavra
        $linhaExtras = mb_substr($linhaExtras,mb_strlen($palavras[$i]));

        //if($e['id_fonte']==3) $pnat = getSpanPalavraNativa($p,$e['eid'],$e['id_fonte'],$e['tamanho']); else $pnat = $p;
        $pnat = getSpanPalavraNativa($p,$eid,$fonte,$tamanho);

        $textoLinha .= '<div onclick="cpk(\''.substr($pids,0,-1).'\','.$pst.','.$aid.',\''.$p.'\',\''.str_replace(',','-',$pid).'\',this,\''.$refs.'\')" 
           title="'.$popup.'"
          class="pstat-'.$pst.' pstud pstud-'.str_replace(',','-',$pid).' r'.str_replace('.'," r",$refs).'">'.$pnat.'<div class="sGl">'.$minigloss.'</div></div>';
      } // data-bs-html="true" data-bs-trigger="hover" data-bs-toggle="popover"
      

      if ($palavras[$i+1]) {
        $posProx = mb_strpos($linhaExtras,$palavras[$i+1]);
        if($posProx>0){
          $textoLinha .= '<div class="nostud">'.mb_substr($linhaExtras,0,$posProx).'</div>';
          $linhaExtras = mb_substr($linhaExtras,$posProx);
        }
      }else{
        // preisa checar se é fim da linha realmente
        //echo '= mb_strpos: linha: "'.$linhaExtras.'" => ant: "'.$palavras[$i-1].'" => prox: "'.$palavras[$i+1].'" => pos: '.$posProx.' => count: '.$i.'/'.sizeof($palavras).'<br>';

        
        if ($i == sizeof($palavras)-1 /* $linhaExtras != '' */ )
          $textoLinha .= '<div class="nostud">'.$linhaExtras.'</div>';
      }

    }
    $texto .= $textoLinha.'</div>'; // '<br>'  data-bs-toggle="tooltip" data-bs-placement="bottom"

    return [$texto,$listaPalavrasUnicas];
};

function getFullStudyText($id) {
  $sql = "SELECT t.*, e.id as eid, e.binario, e.id_fonte, e.tamanho,
    e.separadores, e.iniciadores,
    (SELECT nome_legivel FROM idiomas WHERE id = t.id_idioma) as idioma,
    (SELECT id_usuario FROM idiomas WHERE id = t.id_idioma) as uidioma
    FROM studason_tests t
    LEFT JOIN escritas e  ON e.id_idioma = t.id_idioma
    WHERE  t.id = ".$id."  ORDER BY e.padrao DESC;";
  $langs = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

  // getGlossedText($text,$iid,$eid,$tamanho,$fonte,$binary,$isOwner);

  $isOwner = false;
  $e = mysqli_fetch_assoc($langs);  
  $id_idioma = $e['id_idioma'];
  $idioma = $e['idioma'];
  $titulo = $e['titulo'];
  $tamanho = $e['tamanho'];
  $fonte = $e['id_fonte'];
  $texto = '';
  if ($e['eid']>0) $eid = $e['eid'];
  if ($e['binario']>0) $bin = ' BINARY ';
  if ($e['uidioma'] == $_SESSION['KondisonairUzatorIDX']) $isOwner = true;

  $textoSentencas = $e['texto'];
  global $separadorRomanizacao;

  $separadorPalavras = preg_split('//u', $e['separadores'] ?? $separadorRomanizacao, null, PREG_SPLIT_NO_EMPTY) ?: [" "];

  $iniciadoresPalavras = preg_split('//u', $e['iniciadores'], null, PREG_SPLIT_NO_EMPTY) ?: [/*"\n"*/];
  foreach ($iniciadoresPalavras as $sep){
    $textoSentencas = str_replace($sep," ".$sep,$textoSentencas);
  }

  $palDesc = 0;
  $palCon = 0;
  $palTotal = 0;
  $palStud = 0;
  $palNovas = 0;
  $palOk = 0;
  
  $listaPalavrasUnicas = array();

  $separadorLinhas = ["\n","\r\n","\r"];//array("\n");

  $linhas = multiexplode($separadorLinhas,$textoSentencas);

  for($j = 0; $j < sizeof($linhas); $j++){
      $linha = $linhas[$j];

      [$textoTemp,$listaPalavrasUnicasTemp] = getStudySentence($separadorPalavras,$linha,$id_idioma,$eid,$bin,$fonte,$tamanho);
      $texto .= $textoTemp;
      $listaPalavrasUnicas = array_merge($listaPalavrasUnicas,$listaPalavrasUnicasTemp);
  }
  
  $novasUnicas = 0;
  foreach($listaPalavrasUnicas as $pal => $pu){ if ($pu['s']<1) { $novasUnicas++; } };
  $echo = '<script>$("#tstats").html("'.count($listaPalavrasUnicas)." "._t('palavras')." - ".$novasUnicas." "._t('novas')." (".round($novasUnicas / (count($listaPalavrasUnicas) > 0 ? count($listaPalavrasUnicas) : 1) * 100).'%) ");
    $(".pstud").tooltip({html:true});</script>';
  return $texto.$echo;
};

function verificarTextosComEssaPalavra($idIdioma, $idPalavra, $textosRemover, $textosAtualizar, $textosIgnorar, $pronunciaNova, $romanizacaoNova, $nativaNovaEidPadrao = '') {
  $sql = "SELECT *, 
      (SELECT palavra FROM palavrasNativas WHERE id_palavra = p.id AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) LIMIT 1) as nativa,
      (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) as escritaPadrao  
      FROM palavras p WHERE p.id_idioma = $idIdioma AND id = $idPalavra ;"; // pegar tbm escrita nativa, ou romanizacao
  $pal = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
  $palavra = mysqli_fetch_assoc($pal);
  if (!isset($palavra['id'])) return '0';
  $eid = $palavra['escritaPadrao'];
  $palavra = $eid > 0 ? $palavra['nativa'] : $palavra['romanizacao'];

  $sql = "SELECT * FROM studason_tests WHERE id_idioma = $idIdioma ;";
  $textos = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

  $ignorar = explode(',',substr($textosIgnorar,2) );
  $atualizar = explode(',',substr($textosAtualizar,2) );

  $lista = '0';
  $listaTextos = '';

  
  while($t = mysqli_fetch_assoc($textos)) {
    if ( in_array($t['id'],$ignorar) ) continue;

    if ( in_array($t['id'],$atualizar) ) { // só se for escrita romanizada em vez de nativa
      if ($nativaNovaEidPadrao == '') die('Palavra vazia');
      if ($eid > 0){
        $textoAtualizado = str_replace($palavra, $nativaNovaEidPadrao, $t['texto']);
      }else{ // romanizaçã
        $textoAtualizado = str_replace($palavra, $romanizacaoNova, $t['texto']);
      }
      mysqli_query($GLOBALS['dblink'],"UPDATE studason_tests SET texto = '$textoAtualizado' WHERE id = ".$t['id']) or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"DELETE FROM studason_palavrs WHERE pids = ".$idPalavra) or die(mysqli_error($GLOBALS['dblink']));
      continue;
    }

    if ( mb_strpos($t['texto'], $palavra) !== false ) {
      $lista .= ','.$t['id'];
      $listaTextos .= '<a href="?page=text&id='.$t['id'].'" target="_blank">'.$t['titulo'].'</a><br>';
    }
  }

  return $lista . ( $listaTextos != '' ? '|'.$listaTextos : '');
};

function verificarFrasesComEssaPalavra($idIdioma, $idPalavra, $textosRemover, $textosAtualizar, $textosIgnorar, $pronunciaNova, $romanizacaoNova, $nativaNovaEidPadrao = '') {
  $sql = "SELECT *, 
      (SELECT palavra FROM palavrasNativas WHERE id_palavra = p.id AND id_escrita = (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) LIMIT 1) as nativa,
      (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1 LIMIT 1) as escritaPadrao  
      FROM palavras p WHERE p.id_idioma = $idIdioma AND id = $idPalavra ;"; // pegar tbm escrita nativa, ou romanizacao
  $pal = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
  $palavra = mysqli_fetch_assoc($pal);
  if (!isset($palavra['id'])) return '0';
  $eid = $palavra['escritaPadrao'];
  $palavra = $eid > 0 ? $palavra['nativa'] : $palavra['romanizacao'];

  $sql = "SELECT * FROM frases WHERE id_idioma = $idIdioma ;";
  $textos = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

  $ignorar = explode(',',substr($textosIgnorar,2) );
  $atualizar = explode(',',substr($textosAtualizar,2) );

  $lista = '0';
  $listaTextos = '';

  
  while($t = mysqli_fetch_assoc($textos)) {
    if ( in_array($t['id'],$ignorar) ) continue;

    if ( in_array($t['id'],$atualizar) ) { // só se for escrita romanizada em vez de nativa
      if ($nativaNovaEidPadrao == '') die('Palavra vazia');
      if ($eid > 0){
        $textoAtualizado = str_replace($palavra, $nativaNovaEidPadrao, $t['frase']);
      }else{ // romanizaçã
        $textoAtualizado = str_replace($palavra, $romanizacaoNova, $t['frase']);
      }
      mysqli_query($GLOBALS['dblink'],"UPDATE frases SET frase = '$textoAtualizado', data_modificacao = NOW() WHERE id = ".$t['id']) or die(mysqli_error($GLOBALS['dblink']));
      continue;
    }

    if ( mb_strpos($t['texto'], $palavra) !== false ) {
      $lista .= ','.$t['id'];
      $listaTextos .= '<a href="?page=phrase&id='.$t['id'].'" target="_blank">'.$t['titulo'].'</a><br>';
    }
  }

  return $lista . ( $listaTextos != '' ? '|'.$listaTextos : '');
};

function verificarPalavrasComEsseSom($idIdioma, $idSom, $palavrasIgnorar, $palavrasRemover, $palavrasAtualizar, $isPersonalizado) {
  $qry = "SELECT s.ipa as ipa1, sp.ipa as ipa2 FROM inventarios i 
      LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
      LEFT JOIN sonsPersonalizados sp ON (i.id_som = sp.id AND i.id_tipoSom = 0)
      WHERE i.id_som = ".$idSom." AND i.id_idioma = ".$idIdioma.";";
  
  $is = mysqli_query($GLOBALS['dblink'],$qry) or die(mysqli_error($GLOBALS['dblink']));
  $i = mysqli_fetch_assoc($is);
  
  $som = $isPersonalizado > 0 ? $i['ipa2'] : $i['ipa1']; //xxxxx pegar o som pelo GET id  (em inventarios)
  
  $sql = "SELECT * FROM palavras WHERE pronuncia LIKE \"%".$som."%\" AND id_idioma = ".$idIdioma.";";
  
  $palavras = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
  
  $ignorar = explode(',',substr($palavrasIgnorar,2) );
  $atualizar = explode(',',substr($palavrasAtualizar,2) );
  
  $lista = '0';
  $listaPalavras = '';

  $somNovo = ''; // por enquanto apenas existe remoção de som, não tem update
  
  while($pal = mysqli_fetch_assoc($palavras)) {
    if ( in_array($pal['id'],$ignorar) ) continue;

    if ( in_array($pal['id'],$atualizar) ) { 
      $pronunciaAtualizada = str_replace($som, $somNovo, $pal['pronuncia']);

      //xxxxx atualizar em frases e textos  
      // MAS COMO ESTAMOS MEXENDO COM PRONUNCIA, NÃO ESCRITA, não precisa atualizar textos e frases, que estão em nativo ou romanização, nunca pronuncia
      //$pid = 0;
      /*
      $resp = verificarTextosComEssaPalavra($idIdioma, $pid, $_POST['textosRemover'], $_POST['textosAtualizar'], $_POST['textosIgnorar'], $pronuncia, $romanizacao, $nativaNovaEidPadrao);
      if ($resp != 0) {
          echo 'textos|'.$resp;
          die();
      }
      $resp = verificarFrasesComEssaPalavra($_GET['iid'], $_GET['pid'], $_POST['textosRemover'], $_POST['textosAtualizar'], $_POST['textosIgnorar'], $pronuncia, $romanizacao, $nativaNovaEidPadrao);
      if ($resp != 0) {
          echo 'frases|'.$resp;
          die();
      }
      */

      mysqli_query($GLOBALS['dblink'],"UPDATE palavras SET pronuncia = '$pronunciaAtualizada', data_modificacao = NOW() WHERE id = ".$pal['id']) or die(mysqli_error($GLOBALS['dblink']));
      continue;
    }

    if ( mb_strpos($pal['texto'], $palavra) !== false ) {
      $lista .= ','.$pal['id'];
      $listaPalavras .= $pal['pronuncia'].' ('.$pal['significado'].')<br>';
    }
  }

  return $lista . ( $listaPalavras != '' ? '|'.$listaPalavras : '');
};

function verificarPalavrasComEsseGlifo($idIdioma, $idGlifo, $palavrasIgnorar, $palavrasRemover, $palavrasAtualizar) {
  //aqui dentro chamar verifiarTextos...
  return false;
};

/*
  AÇÕES KONDISONAIR - DE EDIÇÃO (PARA APENAS LOGADO)
*/

if($_SESSION['KondisonairUzatorNivle']==100){ // admin por nível, não pelo id 1

  if ($_GET['action']=='ajaxGravarOption') { // otimizar sql

    $value = $_GET['value'];
    $param = $_GET['param'];

    $sqlQuerys = "UPDATE opcoes_sistema SET 
      valor = '".$value."' WHERE opcao = '".$param."';";
    mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die('err: '.mysqli_error($GLOBALS['dblink']));
		
    die('ok');
  };

  if ($_GET['action']=='ajaxGravarOpsons') { // otimizar sql

    foreach ($_POST as $key => $value) { 
      $sqlQuerys = "UPDATE opcoes_sistema SET 
        valor = '".$value."' WHERE opcao = '".$key."';";
      //echo $sqlQuerys;
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die('err: '.mysqli_error($GLOBALS['dblink']));
		}

    die('ok');
  };

  if ($_GET['action']=='ajaxGravarGloss') { // otimizar sql (final)

    if($_GET['gid']>0){ 
      $sql = "UPDATE glosses SET
            gloss = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['gloss'])."',
            descricao = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao'])."'
            WHERE id = ".(int)$_GET['gid'].";";
      mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      $gid = $_GET['gid'];

    } else {  
        $gid = generateId();
        $sql = "INSERT INTO glosses SET
            id = ".$gid.",
            gloss = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['gloss'])."',
            descricao = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao'])."',
            tipo = 'i';";

        mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
    }; 

    if($gid>0){ // otimizar sql
      mysqli_query($GLOBALS['dblink'],"DELETE FROM gloss_referentes WHERE id_gloss = ".$gid.";") or die(mysqli_error($GLOBALS['dblink']));

      foreach($_POST['referentes'] as $ref){
          if ($ref>0)
          mysqli_query($GLOBALS['dblink'],"INSERT INTO gloss_referentes SET 
              id_gloss = ".$gid.",
              id_referente = ".$ref.";") or die(mysqli_error($GLOBALS['dblink']));
      }
    }

    echo $gid;
    die();
  };

  if ($_GET['action'] == 'getDetalhesGloss') {
    $result = mysqli_query($GLOBALS['dblink'], "SELECT * FROM glosses WHERE id = ".(int)$_GET['gid'].";") or die(mysqli_error($GLOBALS['dblink']));
    $data = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $data[] = $r;
    }
    echo json_encode($data);
    exit();
  }

  if ($_GET['action'] == 'ajaxDelGloss') {
    $sql = "DELETE FROM glosses WHERE id = ".(int)$_GET['gid'].";";
    mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    echo 'ok';
    exit();
  }

  if ($_GET['action'] == 'listGlosses') {
    $result = mysqli_query($GLOBALS['dblink'], "SELECT * FROM glosses;") or die(mysqli_error($GLOBALS['dblink']));
    while ($r = mysqli_fetch_assoc($result)) {
        echo '<div class="list-group-item" id="row_'.$r['id'].'"><div class="row">
            <div class="col" onClick="abrirGloss(\''.$r['id'].'\')">
                <a href="#" >'.htmlspecialchars($r['gloss']).' - '.htmlspecialchars($r['descricao']).'</a>
            </div><div class="col-auto"><a class="btn btn-sm btn-danger" onclick="delGloss(\''.$r['id'].'\')">X</a></div>
        </div></div>';
    }
    exit();
  }

  if ($_GET['action'] == 'ajaxGravarUsuario') {
      if ($_GET['uid'] > 0) {
          $sql = "UPDATE usuarios SET
              username = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['username'])."',
              nome_completo = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome_completo'])."',
              descricao = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao'])."'
              WHERE id = ".(int)$_GET['uid'].";";
      } else {
          die('no create');
          $sql = "INSERT INTO usuarios SET
              id = ".generateId().",
              username = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['username'])."',
              nome_completo = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome_completo'])."',
              descricao = '".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao'])."',
              senha = '', 
              id_idioma_nativo = 1,
              email = '',
              confirmacao = '',
              acesso = 0,
              publico = 0,
              data_cadastro = NOW();";
      }
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
      echo mysqli_insert_id($GLOBALS['dblink']) ?: $_GET['uid'];
      exit();
  }

  if ($_GET['action'] == 'listUsuarios') {
      $result = mysqli_query($GLOBALS['dblink'], "SELECT id, username, nome_completo FROM usuarios WHERE id > 10;") or die(mysqli_error($GLOBALS['dblink']));
      while ($r = mysqli_fetch_assoc($result)) {
          echo '<div class="list-group-item" id="row_'.$r['id'].'"><div class="row">
              <div class="col" onClick="abrirUsuario(\''.$r['id'].'\')">
                  <a href="#" >'.htmlspecialchars($r['username']).' - '.htmlspecialchars($r['nome_completo']).'</a>
              </div><div class="col-auto"><a class="btn btn-sm btn-danger" onclick="delUsuario(\''.$r['id'].'\')">X</a></div>
          </div></div>';
      }
      exit();
  }

  if ($_GET['action'] == 'getDetalhesUsuario') { 
      $result = mysqli_query($GLOBALS['dblink'], "SELECT * FROM usuarios WHERE id = ".(int)$_GET['uid'].";") or die(mysqli_error($GLOBALS['dblink']));
      $data = [];
      while ($r = mysqli_fetch_assoc($result)) {
          $data[] = $r;
      }
      echo json_encode($data);
      die();
  }

  if ($_GET['action'] == 'ajaxDelUsuario') {
      $sql = "DELETE FROM usuarios WHERE id = ".(int)$_GET['uid'].";";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
      echo 'ok';
      exit();
  }

}else{
  if ($_GET['action']=='ajaxGravarOption') die('Sertno admin');
  if ($_GET['action']=='ajaxGravarOpsons') die('Sertno admin');
  if ($_GET['action']=='ajaxGravarGloss') die('Sertno admin');
}

if($_SESSION['KondisonairUzatorIDX']>0){

  if ($_GET['action']=='fleksons') {
    require("modules/m_".$_GET['action'].".php");
    die();
  }

  if ($_GET['action']=='ajaxSalvarFonte') {
    if($_GET['id']>0){ 
      $sqlQuerys = "UPDATE fontes SET 
        nome = '".$_GET['n']."',
        publica = ".$_GET['p']." 
        WHERE id = ".$_GET['id']." LIMIT 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['id'];
      die();
    }

    $res2 = mysqli_query($GLOBALS['dblink'],"SELECT (SELECT COUNT(*) FROM fontes WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX'].") as fontes,
      (SELECT valor FROM opcoes_sistema WHERE opcao = 'fonts_usuario') as limite;") or die(mysqli_error($GLOBALS['dblink']));
    $r2 = mysqli_fetch_assoc($res2);

    if($r2['fontes'] > $r2['limite']){
      die('Limite de fontes atingido.');
    };

    if (!isset($_FILES['fontFile']) || $_FILES['fontFile']['error'] !== UPLOAD_ERR_OK) {
      die('Nenhum arquivo enviado.');
    }
    if( !isset($_POST['nome'])) die('Nome inválido.');
    $nomeFonte = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['nome']);
    if ( glob("fonts/$nomeFonte") ) die('Já existe um arquivo com este nome.');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $_FILES['fontFile']['tmp_name']);

    if ($mime_type === 'font/ttf' || $mime_type === 'font/otf' || $mime_type === 'font/sfnt') {
        $target_dir = 'fonts/';
        $target_file = $target_dir . $nomeFonte; // No extension for fonts
    } else {
        die('Arquivo inválido.'.$mime_type);
    }
    
    if (move_uploaded_file($_FILES['fontFile']['tmp_name'], $target_file)) {
        $id = generateId();
        $sqlQuerys = "INSERT INTO fontes SET id = $id,
          nome = '".$_POST['nome']."',
          arquivo = '$nomeFonte',
          id_usuario = ".$_SESSION['KondisonairUzatorIDX'].",
          publica = 0;";
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        die("$id");
    } else {
        die('Erro ao salvar o arquivo.');
    }

    die('Erro desconhecido');
  };

  if ($_GET['action']=='ajaxGravarPerfyl') {

    // if email diferente, confirmação = 0
    $r = mysqli_query($GLOBALS['dblink'],"SELECT * FROM usuarios WHERE username = '".$_POST['usuario']."' AND id <> ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($r)>0) die('user');

    $sqlQuerys = "UPDATE usuarios SET 
      nome_completo = '".$_POST['nome']."',
      username = '".$_POST['usuario']."',
      email = '".$_POST['email']."',
      publico = '".$_POST['publico']."',
      id_idioma_nativo = '".$_POST['iid']."',
      descricao = '".str_replace("'",'"',$_POST['descricao'])."'
      WHERE id = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1;";
    mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
  }
  
  if ($_GET['action']=='ajaxNovoGrupoIdiomas') {
    $sqlQuerys = "INSERT INTO grupos_idiomas SET 
    nome = '".$_GET['nome']."',
    descricao = '',
    id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";

    mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    $iid = mysqli_insert_id($GLOBALS['dblink']);
    die();
  };

  if ($_GET['action']=='ajaxGravarIdioma') {

    if($_GET['id']>0){ 
      $sqlQuerys = "UPDATE idiomas SET 
        nome = '".$_POST['nome']."',
        publico = ".$_POST['publico'].",
        data_modificacao = now(),
        status = ".$_POST['status'].",
        checar_sons = ".$_POST['checar_sons'].",
        id_nome_nativo = ".($_POST['id_nome_nativo']>0?$_POST['id_nome_nativo']:0).",
        id_familia = ".($_POST['id_familia']>0?$_POST['id_familia']:0).",
        nome_legivel = '".$_POST['nome_legivel']."',
        descricao = '".str_replace("'",'"',$_POST['descricao'])."',
        sigla = '".$_POST['sigla']."',
        motor = 'ksc',
        romanizacao = '".$_POST['romanizacao']."',
        id_idioma_descricao = '".$_POST['idioma_desc']."',
        id_ascendente = ".$_POST['id_ascendente'].",
        id_momento = ".$_POST['id_momento']."
        WHERE id = ".$_GET['id']." LIMIT 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      $iid = $_GET['id'];

      if ($_POST['publico']==1) logAcao(1,'diom',$iid);

    } else {  

      
      $res2 = mysqli_query($GLOBALS['dblink'],"SELECT (SELECT COUNT(*) FROM idiomas WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX'].") as idiomas,
        (SELECT valor FROM opcoes_sistema WHERE opcao = 'limite_langs') as limite;") or die(mysqli_error($GLOBALS['dblink']));
			$r2 = mysqli_fetch_assoc($res2);

      if($r2['idiomas'] > $r2['limite']){
        echo 'limit';
        die();
      };
      //limit
        $iid = generateId();
        $sqlQuerys = "INSERT INTO idiomas SET 
        nome = '".$_POST['nome']."',
        id = $iid,
        publico = ".$_POST['publico'].",
        status = ".$_POST['status'].",
        nome_legivel = '".$_POST['nome_legivel']."',
        checar_sons = ".$_POST['checar_sons'].",
        sigla = '".$_POST['sigla']."',
        motor = 'ksc',
        id_familia = ".($_POST['id_familia']>0?$_POST['id_familia']:0).",
        descricao = '".str_replace("'",'"',$_POST['descricao'])."',
        romanizacao = '".$_POST['romanizacao']."',
        id_idioma_descricao = '".$_POST['idioma_desc']."',
        id_nome_nativo = ".($_POST['id_nome_nativo']>0?$_POST['id_nome_nativo']:0).",
        id_usuario = ".$_SESSION['KondisonairUzatorIDX'].",
        id_tipo = 0, buscavel = 0,
        id_ascendente = ".$_POST['id_ascendente'].";";

        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        if ($_POST['publico']==1) logAcao(0,'diom',$iid);
    }; 
    
    mysqli_query($GLOBALS['dblink'],
        "DELETE FROM collabs WHERE id_idioma = ".$iid.";") or die(mysqli_error($GLOBALS['dblink']));

    // collabs
    foreach($_POST['collabs'] as $c){
      mysqli_query($GLOBALS['dblink'],
        "INSERT INTO collabs SET 
          id_idioma = ".$iid.", id = ".generateId().",
          id_usuario = (SELECT id FROM usuarios WHERE username = '".$c."' LIMIT 1);") or die('user'); // or die(mysqli_error($GLOBALS['dblink']));
    };
    mysqli_query($GLOBALS['dblink'], "DELETE FROM collabs WHERE id_usuario IS NULL;") or die(mysqli_error($GLOBALS['dblink']));

    echo $iid;
    die();
  }

  if ($_GET['action']=='ajaxGravarRegra') {

    if ($_POST['n']>0){

      //xxxxx verificar se há outra com mesmo gloss, não pode! dizer/sugerir usar outro parecido

      //se for inserir, contar as regras pra colocar numero de ordem correto no fim
      //se for editar, conferir a ordem

      if($_GET['id']>0){ 
        $sqlQuerys = "UPDATE blocos SET 
          tipo_nucleo = '".$_POST['tn']."',
          id_nucleo = ".$_POST['n'].",
          tipo_dependente = '".$_POST['td']."',
          id_dependente = ".$_POST['d'].",
          descricao = '".$_POST['descricao']."',
          lado = '".$_POST['lado']."',
          nome = '".$_POST['nome']."',
          id_gloss = ".$_POST['gloss'].",
          id_separador = ".$_POST['separador']."
          WHERE id = ".$_GET['id']." LIMIT 1;";
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        echo $_GET['id'];

      } else {  
          $sqlQuerys = "INSERT INTO blocos SET 
            tipo_nucleo = '".$_POST['tn']."',
            id_nucleo = ".$_POST['n'].",
            tipo_dependente = '".$_POST['td']."',
            id_dependente = ".$_POST['d'].",
            descricao = '".$_POST['descricao']."',
            id_separador = ".$_POST['separador'].",
            nome = '".$_POST['nome']."',
            lado = '".$_POST['lado']."',
            id_gloss = ".$_POST['gloss'].",
            id_idioma = ".$_GET['iid'].";";
        //echo $sqlQuerys;
          mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
          echo mysqli_insert_id($GLOBALS['dblink']);
      }; 
    }
    else echo 'novalered';
    die();
  }

  if ($_GET['action']=='ajaxSalvarListaSC') { // otimizar sql os 2 ultimos

    // POST p = chanages
    // GET id = id, iid idioma
    if ($_GET['iid']>0) $idioma = $_GET['iid'];
    else $idioma = 0;

    //tratar entrada
    $changes = str_replace("\\","\\\\",$_POST['l']);
    $instrucoes = str_replace("\\","\\\\",$_POST['ins']);
    $classes = str_replace("\\","\\\\",$_POST['classes']);
    $rewrites = str_replace("\\","\\\\",$_POST['rewrites']);

    $res2 = mysqli_query($GLOBALS['dblink'],"SELECT * FROM soundChanges 
      WHERE id = '".$_GET['id']."' AND id_usuario = '".$_SESSION['KondisonairUzatorIDX']."';") or die(mysqli_error($GLOBALS['dblink']));
    
    if(mysqli_num_rows($res2)>0){ 
      $sqlQuerys = "UPDATE soundChanges SET 
        instrucoes = '".str_replace("'",'"',$instrucoes)."' ,
          changes = \"".$changes."\" ,
          classes = \"".$classes."\" ,
          substituicoes = \"".$rewrites."\" ,
          id_idioma = ".$idioma.",
          data_modificacao = now(),
          titulo = \"".$_POST['titulo']."\", 
          motor = \"".$_POST['motor']."\" 
          WHERE id = ".$_GET['id']." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1;";
      //echo $sqlQuerys;
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      $id = $_GET['id'];

    } else {  

      
      if($idioma == 0){
        echo 'nolang';
        die();
      };

      $res2 = mysqli_query($GLOBALS['dblink'],"SELECT (SELECT COUNT(*) FROM soundChanges WHERE id_idioma = ".$idioma.") as scs,
        (SELECT valor FROM opcoes_sistema WHERE opcao = 'limite_scs_lang') as limite,
        (SELECT COUNT(*) FROM soundChanges WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX'].") as scus,
        (SELECT valor FROM opcoes_sistema WHERE opcao = 'limite_scs_user') as limite2;") or die(mysqli_error($GLOBALS['dblink']));
			$r2 = mysqli_fetch_assoc($res2);

      /*if($r2['scs'] > $r2['limite'] || $r2['scus'] > $r2['limite2']){
        echo 'limit';
        die();
      };*/
        $id = generateId();

        $sqlQuerys = "INSERT INTO soundChanges SET 
          instrucoes = '".str_replace("'",'"',$instrucoes)."' ,
          changes = \"".$changes."\" ,
          classes = \"".$classes."\" ,
          substituicoes = \"".$rewrites."\" ,
          descricao = '',
          id_idioma = ".$idioma.",
          titulo = \"".$_POST['titulo']."\" ,
          motor = \"".$_POST['motor']."\" , id = $id,
          id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";

          //echo $sqlQuerys;
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    }; 

    echo $id;
    die();
  }

  if ($_GET['action']=='ajaxUpdateIpids') { // otimizar pra 1 sql query
    
    // echo "UPDATE itens_palavras SET usar = 1 WHERE id_palavra = ".$_GET['pid']." AND id_concordancia IN (". substr($_GET['ipids'],0,strlen($_GET['ipids'])-1).");";
    if (isset($_GET['ipids']) && $_GET['ipids']!=''){
      mysqli_query($GLOBALS['dblink'],"DELETE FROM itens_palavras WHERE id_palavra = ".$_GET['pid']." AND id_concordancia NOT IN(". substr($_GET['ipids'],0,strlen($_GET['ipids'])-1).");") or die(mysqli_error($GLOBALS['dblink']));
      //mysqli_query($GLOBALS['dblink'],"UPDATE itens_palavras SET usar = 0 WHERE id_palavra = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"UPDATE itens_palavras SET usar = 1 WHERE id_palavra = ".$_GET['pid']." AND id_concordancia IN (". substr($_GET['ipids'],0,strlen($_GET['ipids'])-1).");") or die(mysqli_error($GLOBALS['dblink']));
    }
    die('ok');
  }

  if ($_GET['action']=='ajaxGravarItem') {

    echo setItensPalavra($_GET['pid'],$_GET['c'],$_GET['i']);
    die();
  }

  if ($_GET['action']=='ajaxGravarGenPal') {

    echo setGenPalDeriv($_GET['pid'],$_GET['i']);
    die();
    die();
  }

  if ($_GET['action'] == 'ajaxGravarReferente') {
    $rid = intval($_GET['rid']);
    mysqli_begin_transaction($GLOBALS['dblink']);

      foreach ($_POST as $key => $value) {
          if (strpos($key, 'd') === 0 && $value !== '') {
              $id_idioma = intval(substr($key, 1));
              $descricao = mysqli_real_escape_string($GLOBALS['dblink'], $value);
              $detalhes = $_POST['m'.$id_idioma] ? mysqli_real_escape_string($GLOBALS['dblink'], $_POST['m'.$id_idioma]) : null;

              $check_sql = "SELECT id FROM referentes_descricoes WHERE id_referente = ? AND id_idioma = ?";
              $stmt = mysqli_prepare($GLOBALS['dblink'], $check_sql);
              mysqli_stmt_bind_param($stmt, 'ii', $rid, $id_idioma);
              mysqli_stmt_execute($stmt);
              $result = mysqli_stmt_get_result($stmt);

              if (mysqli_num_rows($result) > 0) {
                  $update_sql = "UPDATE referentes_descricoes SET descricao = ?, detalhes = ? WHERE id_referente = ? AND id_idioma = ?";
                  $stmt = mysqli_prepare($GLOBALS['dblink'], $update_sql);
                  mysqli_stmt_bind_param($stmt, 'ssii', $descricao, $detalhes, $rid, $id_idioma);
                  mysqli_stmt_execute($stmt);
              } else {
                  $insert_sql = "INSERT INTO referentes_descricoes (id, id_referente, id_idioma, descricao, detalhes) VALUES (?, ?, ?, ?, ?)";
                  $stmt = mysqli_prepare($GLOBALS['dblink'], $insert_sql);
                  mysqli_stmt_bind_param($stmt, 'iiiss', generateId(), $rid, $id_idioma, $descricao, $detalhes);
                  mysqli_stmt_execute($stmt);
              }
              mysqli_stmt_close($stmt);
          }
      }

      $idiomas_enviados = [];
      foreach ($_POST as $key => $value) {
          if (strpos($key, 'd') === 0 && $value !== '') {
              $idiomas_enviados[] = intval(substr($key, 1));
          }
      }
      $idiomas_enviados_str = empty($idiomas_enviados) ? '0' : implode(',', $idiomas_enviados);
      $delete_sql = "DELETE FROM referentes_descricoes WHERE id_referente = ? AND id_idioma NOT IN ($idiomas_enviados_str)";
      $stmt = mysqli_prepare($GLOBALS['dblink'], $delete_sql);
      mysqli_stmt_bind_param($stmt, 'i', $rid);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);

      if ($rid > 0) {
          $update_referente_sql = "UPDATE referentes SET modificado = NOW() WHERE id = ?";
          $stmt = mysqli_prepare($GLOBALS['dblink'], $update_referente_sql);
          mysqli_stmt_bind_param($stmt, 'i', $rid);
          mysqli_stmt_execute($stmt);
          mysqli_stmt_close($stmt);
      } else {
          $insert_referente_sql = "INSERT INTO referentes (modificado) VALUES (NOW())";
          mysqli_query($GLOBALS['dblink'], $insert_referente_sql) or die(mysqli_error($GLOBALS['dblink']));
          $rid = mysqli_insert_id($GLOBALS['dblink']);
      }

      mysqli_commit($GLOBALS['dblink']);
      echo $rid;
      die();
  }

  if ($_GET['action']=='ajaxGravarPalavra') {
    $res0 = mysqli_query($GLOBALS['dblink'],"SELECT p.pronuncia, p.romanizacao, 
        (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = p.id AND n.id_escrita = (SELECT id FROM escritas WHERE id_idioma = p.id_idioma AND padrao = 1) LIMIT 1) as nativo, 
        i.publico FROM palavras p LEFT JOIN idiomas i ON i.id = p.id_idioma WHERE p.id = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));
    $r0 = mysqli_fetch_assoc($res0);
    $romanizacao = str_replace("'",'"',$_POST['romanizacao']);
    $pronuncia = str_replace('"',"'",$_POST['pronuncia']);

    $sqlQuerys =  "SELECT e.* FROM escritas e 
      WHERE id_idioma = ".$_GET['iid']." ORDER BY padrao DESC;";
    $escritas = mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    $esc = mysqli_fetch_assoc($escritas);


    if($_GET['pid']>0){ 
      $nativaNovaEidPadrao = $_POST['nativo'][0/*$esc['id']*/];
      if ($_POST['nativo'][0]!=$r0['nativo'] || $pronuncia!=$r0['pronuncia'] || $romanizacao!=$r0['romanizacao']){
        $resp = verificarTextosComEssaPalavra($_GET['iid'], $_GET['pid'], $_POST['textosRemover'], $_POST['textosAtualizar'], $_POST['textosIgnorar'], $pronuncia, $romanizacao, $nativaNovaEidPadrao);
        if ($resp != 0) {
            echo 'textos|'.$resp;
            die();
        }
        $resp = verificarFrasesComEssaPalavra($_GET['iid'], $_GET['pid'], $_POST['textosRemover'], $_POST['textosAtualizar'], $_POST['textosIgnorar'], $pronuncia, $romanizacao, $nativaNovaEidPadrao);
        if ($resp != 0) {
            echo 'frases|'.$resp;
            die();
        }
      }

      $sqlQuerys = "UPDATE palavras SET 
        significado = '".str_replace("'",'"',$_POST['significado'])."',
        romanizacao = '".$romanizacao."',
        pronuncia = \"".$pronuncia."\",
        detalhes = '".str_replace("'",'"',$_POST['detalhes'])."',
        privado = '".str_replace("'",'"',$_POST['privado'])."',
        id_uso = '".$_POST['id_uso']."',
        data_modificacao = now(),
        id_forma_dicionario = '".$_POST['id_forma_dicionario']."',
        id_derivadora = '".$_POST['id_derivadora']."',
        id_classe = ".$_POST['id_classe']."
        WHERE id = ".$_GET['pid']." LIMIT 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      $pid = $_GET['pid'];
      echo $pid; 
      
      if($_POST['id_forma_dicionario']==0 && $r0['publico']==1) 
        logAcao(1,'palavr',$_GET['pid']);

    } else { 

      $pid = generateId('palavras');
        
      if ( $_GET['ignorar'] != '' ) {
        $ignorar = ' AND p.id NOT IN('.$_GET['ignorar'].')';
      };

      if($_POST['romanizacao'] != '') $orRom = " OR romanizacao = '".str_replace("'",'"',$_POST['romanizacao'])."'";
    
      $sql = "SELECT p.*, c.nome as classe FROM palavras p
        LEFT JOIN classes c ON p.id_classe = c.id
        WHERE p.id_idioma = ".$_GET['iid']." AND ( p.pronuncia = \"".$pronuncia."\" ".$orRom.") ".$ignorar.";";
        
      $busca = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

      while ($a = mysqli_fetch_array($busca)){ 
          echo '-'.$a['id'].'|'.$a['pronuncia'].'|'.$a['significado'].' - '.$a['classe'];
          die();
      };


      /*
      checar limite de palavras por conlang
      */
      $res2 = mysqli_query($GLOBALS['dblink'],"SELECT (SELECT COUNT(*) FROM palavras WHERE id_idioma = ".$_GET['iid']." AND id_forma_dicionario = 0) as palavras_base,
        (SELECT valor FROM opcoes_sistema WHERE opcao = 'palavras_base_lang') as limite,
        (SELECT COUNT(*) FROM palavras WHERE id_idioma = ".$_GET['iid'].") as palavras,
        (SELECT valor FROM opcoes_sistema WHERE opcao = 'palavras_lang') as limite2;") or die(mysqli_error($GLOBALS['dblink']));
			$r2 = mysqli_fetch_assoc($res2);
      if($r2['palavras_base'] > $r2['limite'] || $r2['palavras'] > $r2['limite2']){
        echo 'limit';
        die();
      };

      $sqlQuerys = "INSERT INTO palavras SET 
        id = $pid,
        significado = '".str_replace("'",'"',$_POST['significado'])."',
        romanizacao = '".$romanizacao."',
        pronuncia = \"".$pronuncia."\",
        detalhes = '".str_replace("'",'"',$_POST['detalhes'])."',
        privado = '".str_replace("'",'"',$_POST['privado'])."',
        id_uso = '".$_POST['id_uso']."',
        id_forma_dicionario = '".((int)$_POST['id_forma_dicionario']??0)."',
        id_derivadora = '".((int)$_POST['id_derivadora']??0)."',
        id_classe = ".$_POST['id_classe'].",
        data_criacao = now(), data_modificacao = now(),
        id_usuario = ".$_SESSION['KondisonairUzatorIDX'].",
        id_idioma = ".$_GET['iid'].";";

        //echo $sqlQuerys;
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      echo $pid;
      
      if($_POST['id_forma_dicionario']==0 && $r0['publico']==1) 
        logAcao(0,'palavr',$pid);
    }; 

    

    mysqli_query($GLOBALS['dblink'],
        "DELETE FROM significados_idiomas WHERE id_palavra = ".$pid.";") or die(mysqli_error($GLOBALS['dblink']));

    foreach($_POST['oiids'] as $oiid){
      mysqli_query($GLOBALS['dblink'],
        "INSERT INTO significados_idiomas SET 
          id_palavra = ".$pid.",
          id_idioma = ".$oiid['i'].",
          significado = '".$oiid['s']."';") or die(mysqli_error($GLOBALS['dblink']));
    }
    
    mysqli_query($GLOBALS['dblink'],
        "DELETE FROM tags WHERE id_dest = ".$pid." AND tipo_dest = 'word';") or die(mysqli_error($GLOBALS['dblink']));

    // tags
    foreach($_POST['tags'] as $tag){
      mysqli_query($GLOBALS['dblink'],
        "INSERT INTO tags SET 
          id_dest = ".$pid.",
          tipo_dest = 'word',
          tag = '".$tag."';") or die(mysqli_error($GLOBALS['dblink']));
    }

    // incluir salvamento de nativas junto com restante?
    die();

    mysqli_data_seek($escritas,0);
    if (isset($_POST['nativo'])) for ($i=0; $i < count($_POST['nativo']); $i++) {
        $esc = mysqli_fetch_assoc($escritas);
        if ($_POST['nativo'][$i]==''){
          $sqlQuerys = "DELETE FROM palavrasNativas WHERE id_palavra = ".$idPalavra." AND id_escrita = ".$esc['id'].";";
        }else{            
          $pals = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavrasNativas WHERE id_palavra = ".$idPalavra." AND id_escrita = ".$esc['id'].";") or die(mysqli_error($GLOBALS['dblink']));
          if (mysqli_num_rows($pals)==0){
              $sqlQuerys = "INSERT INTO palavrasNativas SET 
                  id_palavra = ".$idPalavra.", id = ".generateId().",
                  id_escrita = ".$esc['id'].",
                  palavra = \"".$_POST['nativo'][$i]."\";";
          }else{
              $sqlQuerys = "UPDATE palavrasNativas SET  id = ".generateId().",
                  palavra = \"".$_POST['nativo'][$i]."\" 
                  WHERE id_palavra = ".$idPalavra." AND
                  id_escrita = ".$esc['id'].";";
          }
        }
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    };

    //echo $pid;
    die();
  }

  if ($_GET['action']=='ajaxGravarNivel') {

    if($_GET['nid']>0){ 
      $sqlQuerys = "UPDATE nivelUsoPalavra SET 
        titulo = '".$_POST['titulo']."',
        ordem = 0,
        descricao = '".$_POST['descricao']."'
        WHERE id = ".$_GET['nid']." LIMIT 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['nid'];

    } else {  
        $sqlQuerys = "INSERT INTO nivelUsoPalavra SET 
        titulo = '".$_POST['titulo']."',
        descricao = '".$_POST['descricao']."',
        ordem = 0,
        id_idioma = ".$_GET['iid'].";";

        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        echo mysqli_insert_id($GLOBALS['dblink']);
    }; 
    die();
  }

  if ($_GET['action']=='ajaxGravarClasse') {

    if($_GET['cid']>0){ 
      $sqlQuerys = "UPDATE classes SET 
        nome = '".$_POST['nome']."',
        id_gloss = ".$_POST['gloss'].",
        superior = ".$_POST['superior'].",
        paradigma = ".$_POST['paradigma'].",
        data_modificacao = now(),
        proto_tipo = '".$_POST['proto_tipo']."',
        descricao = '".str_replace("'",'"',$_POST['descricao'])."'
        WHERE id = ".$_GET['cid']." LIMIT 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['cid'];

    } else {  
      
      
      $res2 = mysqli_query($GLOBALS['dblink'],"SELECT (SELECT COUNT(*) FROM classes WHERE id_idioma = ".$_GET['iid'].") as classes,
        (SELECT valor FROM opcoes_sistema WHERE opcao = 'lim_lang_parts') as limite;") or die(mysqli_error($GLOBALS['dblink']));
			$r2 = mysqli_fetch_assoc($res2);

      if($r2['classes'] > $r2['limite']){
        echo 'limit';
        die();
      };

        $kid = generateId();
        $sqlQuerys = "INSERT INTO classes SET 
        nome = '".$_POST['nome']."', id = $kid,
        superior = ".$_POST['superior'].",
        descricao = '".str_replace("'",'"',$_POST['descricao'])."',
        proto_tipo = '".$_POST['proto_tipo']."',
        id_gloss = ".$_POST['gloss'].",
        id_idioma = ".$_GET['iid'].";";

        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        echo $kid;
    }; 
    die();
  }

  if ($_GET['action']=='ajaxGravarOpcao') { // otimizar sql query final no foreach

    // GET op (itensConcord.id) iid
    // POST nome gloss padrao conc ()

    //if padrao < 2 > remover concordancias e regexes q depende dessa opcao

    //xxxxx ORDENACAO AUTO ?

    if($_GET['op']>0){ 
      $sqlQuerys = "UPDATE itensConcordancias SET 
        nome = '".$_POST['nome']."',
        padrao = ".$_POST['padrao']."
        WHERE id = ".$_GET['op']." LIMIT 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      $idop = $_GET['op'];

    } else {  

      $qs = mysqli_query($GLOBALS['dblink'],'SELECT * FROM itensConcordancias WHERE id_concordancia = '.$_POST['conc'].' ORDER BY ordem;') or die(mysqli_error($GLOBALS['dblink']));
      $o = 1;
      while ($q = mysqli_fetch_assoc($qs)){
          mysqli_query($GLOBALS['dblink'],'UPDATE itensConcordancias SET ordem = '.$o.' WHERE id = '.$q['id'].';') or die(mysqli_error($GLOBALS['dblink']));
          $o++;
      };
      
      if ($_POST['padrao']==1){
        mysqli_query($GLOBALS['dblink'],"UPDATE itensConcordancias SET 
          padrao = 0 
          WHERE id_concordancia = ".$_POST['conc']."
          AND padrao = 1;") or die(mysqli_error($GLOBALS['dblink']));

          // remover tbm regras regex desse ex-padrão
      }
        $idop = generateId();
        $sqlQuerys = "INSERT INTO itensConcordancias SET 
        nome = '".$_POST['nome']."',
        ordem = ".$o.", id = $idop,
        padrao = ".$_POST['padrao'].",
        id_concordancia = ".$_POST['conc'].";";
        //echo $sqlQuerys;

        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    }; 

    
    if($idop>0){
      mysqli_query($GLOBALS['dblink'],"DELETE FROM gloss_itens WHERE id_item = ".$idop.";") or die(mysqli_error($GLOBALS['dblink']));

      foreach($_POST['gloss'] as $ref){
          if ($ref>0){
            //echo 'insert'.$ref;
            mysqli_query($GLOBALS['dblink'],"INSERT INTO gloss_itens SET id = ".generateId().",
                id_item = ".$idop.",
                id_gloss = ".$ref.";") or die(mysqli_error($GLOBALS['dblink']));
          }
      }
    };
    echo $idop;
    die();
  }

  if ($_GET['action']=='ajaxApagarArtigo') {

    mysqli_query($GLOBALS['dblink'],"DELETE FROM artygs WHERE id_pap = ".$_GET['aid']." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink']));
    mysqli_query($GLOBALS['dblink'],"DELETE FROM artygs WHERE id = ".$_GET['aid']." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink']));
    echo 'ok';
    die();
  }

  if ($_GET['action']=='ajaxApagarOpcao') {

    $pals = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itens_palavras WHERE id_item = ".$_GET['id']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
    $npals = mysqli_num_rows($pals);
    if ($npals > 0) echo _t('Não é possível apagar. Há %1 palavras aqui.',[$npals]);
    else{
      //mysqli_query($GLOBALS['dblink'],"DELETE FROM itens_palavras WHERE id_item = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"DELETE FROM itensConcordancias WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      //mysqli_query($GLOBALS['dblink'],"DELETE FROM itensConcordancias WHERE id_concordancia = (SELECT id_concordancia FROM itens_palavras WHERE id_item = ".$_GET['id']." LIMIT 1);") or die(mysqli_error($GLOBALS['dblink']));
      echo 'ok';
    }
    die();
  }

  if ($_GET['action']=='ajaxGravarGenero') {
    
    if($_GET['cid']>0){ 
      $sqlQuerys = "UPDATE generos SET 
        nome = '".$_POST['nome']."',
        id_gloss = '".$_POST['gloss']."',
        obrigatorio = '".$_POST['obrigatorio']."',
        descricao = '".$_POST['descricao']."'
        WHERE id = ".$_GET['cid']." LIMIT 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['cid'];

    } else {  
      
      //xxxxx LIMIT ?
        $gid = generateId();
        $sqlQuerys = "INSERT INTO generos SET 
        nome = '".$_POST['nome']."', id = $gid,
        id_gloss = '".$_POST['gloss']."',
        descricao = '".$_POST['descricao']."',
        obrigatorio = '".$_POST['obrigatorio']."',
        id_classe = '".$_GET['k']."',
        depende = 0,
        id_idioma = ".$_GET['iid'].";";

        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        echo $gid;
    }; 
    die();
  }

  if ($_GET['action']=='ajaxGravarConcordancia') {
    
    if($_GET['cid']>0){ 
      $sqlQuerys = "UPDATE concordancias SET 
        nome = '".$_POST['nome']."',
        id_gloss = '".$_POST['gloss']."',
        obrigatorio = '".$_POST['obrigatorio']."',
        descricao = '".$_POST['descricao']."'
        WHERE id = ".$_GET['cid']." LIMIT 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['cid'];

    } else {  

        // limitar dimensões em duas
        
        $deps = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias 
            WHERE id_idioma = ".$_GET['iid']." AND id_classe = ".$_GET['k']." 
            AND depende = ".$_POST['depende'].";") or die(mysqli_error($GLOBALS['dblink']));

        if (mysqli_num_rows($deps)>2){
          die(_t('Não é possível criar uma tabela com mais de 3 dimensões.'));
        };
        
        $cid = generateId();

        $sqlQuerys = "INSERT INTO concordancias SET 
          nome = '".$_POST['nome']."', id = $cid,
          id_gloss = '".$_POST['gloss']."',
          descricao = '".$_POST['descricao']."',
          obrigatorio = '".$_POST['obrigatorio']."',
          id_classe = '".$_GET['k']."',
          depende = ".$_POST['depende'].",
          id_idioma = ".$_GET['iid'].";";
        //echo $sqlQuerys;
        // inserir valor default

        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        
        $sqlQuerys = "INSERT INTO itensConcordancias SET 
          nome = '"._t("Valor padrão")."', id = $cid,
          ordem = 1,
          padrao = 1,
          id_concordancia = ".$cid.";";
        //echo $sqlQuerys;

        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        echo $cid;
    }; 
    die();
  }

  if ($_GET['action']=='getChecarPronuncia') { // otimizar sql queries

    // retornar -1 se não achar algum caractre!

    // GET checar: 1 (default): checa as teclas e substitui pelos caracteres
    // checar 0: apenas verifica se os caracteres ipa existem
      
    //$origem = urldecode($_POST['p']);
    $palavra = "";
    $origem = mb_str_split($_POST['p']);
    //$pos=0;
    //foreach ($origem as $char){
    for ($i = 0; $i < sizeof($origem) ;$i++) {
      $char = $origem[$i];

      // buscar com 2 caracteres!
      //$pos++;
      $doublechar = $char.$origem[$i+1];
      $sql = "SELECT s.ipa, p.ipa as ipa2 FROM inventarios i
            LEFT JOIN teclas t ON t.id_inventario = i.id
            LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
            LEFT JOIN sonsPersonalizados p ON ( p.id = i.id_som AND i.id_tipoSom = 0 )
            WHERE i.id_idioma = ".$_GET['iid']."
            AND ( BINARY t.tecla = \"".$doublechar."\" OR BINARY s.ipa = \"".$doublechar."\" OR BINARY p.ipa = \"".$doublechar."\")
            ORDER BY t.ordem;";
      $result = mysqli_query($GLOBALS['dblink'],$sql); 
      //echo $sql;
      if(mysqli_num_rows($result)>0){
        $r = mysqli_fetch_assoc($result);
        if ($r['ipa']!='') $palavra .= $r['ipa']; //existe som ipa associado a essa tecla
        else if ($r['ipa2']!='') $palavra .= $r['ipa2']; //existe som personalizado associado a essa tecla
        else $palavra .= '+'; // não tem som associado
        $i++;
        continue;

      }else{

        // buscar se esta letra tem no inventário ipa
        $sql = "SELECT s.ipa, p.ipa as ipa2 FROM inventarios i
              LEFT JOIN teclas t ON t.id_inventario = i.id
              LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
              LEFT JOIN sonsPersonalizados p ON ( p.id = i.id_som AND i.id_tipoSom = 0 )
              WHERE i.id_idioma = ".$_GET['iid']." 
              AND ( BINARY t.tecla = \"".$char."\" OR BINARY s.ipa = \"".$char."\" OR BINARY p.ipa = \"".$char."\")
              ORDER BY t.ordem;";

        //echo $sql;
        $result = mysqli_query($GLOBALS['dblink'],$sql);
        $r = mysqli_fetch_assoc($result);

        if(mysqli_num_rows($result)==0){
          // não tem no inventário, ver se tem ipa
          $sql = "SELECT * FROM inventarios i
              LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
              WHERE i.id_idioma = ".$_GET['iid']."
              AND BINARY s.ipa = \"".$char."\";"; //AND STRCMP (s.ipa, \"".$char."\");";
          $result2 = mysqli_query($GLOBALS['dblink'],$sql); 
          if(mysqli_num_rows($result2)==0) {
                //não tem no inventário
              $palavra .= '%';
              die('-1');
          }else{
              $palavra .= $char;
          }
        }else{
          //echo '9';
          if ($r['ipa']!='') $palavra .= $r['ipa']; //existe som ipa associado a essa tecla
          else if ($r['ipa2']!='') $palavra .= $r['ipa2']; //existe som personalizado associado a essa tecla
          else $palavra .= '='; // não tem som associado
        }
      }
    };

    if ($_GET['checar']=='0') echo $_POST['p'];
    else echo $palavra;
    die();
  };

  if ($_GET['action'] == 'getAllPronuncias') {
      $iid = $_GET['iid'];
      
      $sql = "SELECT t.tecla, s.ipa, p.ipa as ipa2, t.ordem, i.id_tipoSom 
              FROM inventarios i
              LEFT JOIN teclas t ON t.id_inventario = i.id
              LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
              LEFT JOIN sonsPersonalizados p ON (p.id = i.id_som AND i.id_tipoSom = 0)
              WHERE i.id_idioma = " . $iid . "
              AND (t.tecla IS NOT NULL OR s.ipa IS NOT NULL OR p.ipa IS NOT NULL)
              ORDER BY t.ordem";
      
      $result = mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
      
      $pronuncias = [];
      while ($r = mysqli_fetch_assoc($result)) {
          $pronuncias[] = [
              'tecla' => $r['tecla'] ?? '',
              'roman' => $r['id_tipoSom'] == '3' ? '' : $r['tecla'] ?? '',
              'ipa' => $r['ipa'] ?? '',
              'ipa2' => $r['ipa2'] ?? '',
              'ordem' => $r['ordem'] ?? 0
          ];
      }
      
      echo json_encode($pronuncias);
      die();
  }

  if ($_GET['action'] == 'getAllGlifos') {
    $eid = $_GET['eid'];

    $sql = "SELECT glifo FROM glifos WHERE id_escrita = " . mysqli_real_escape_string($GLOBALS['dblink'], $eid);
    $result = mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));

    $glifos = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $glifos[] = $r['glifo'];
    }

    $sql = "SELECT separadores, iniciadores, sep_sentencas, inic_sentencas 
            FROM escritas 
            WHERE id = " . mysqli_real_escape_string($GLOBALS['dblink'], $eid);
    $result = mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    $r = mysqli_fetch_assoc($result);
    $extras = [];
    foreach (['separadores', 'iniciadores', 'sep_sentencas', 'inic_sentencas'] as $campo) {
        if (!empty($r[$campo])) {
            $caracteres = mb_str_split($r[$campo]);
            $extras = array_merge($extras, $caracteres);
        }
    }
    $extras = array_unique($extras);

    echo json_encode([
        'glifos' => $glifos,
        'extras' => $extras
    ]);
    die();
  }

  if ($_GET['action'] == 'getAllAutoSubstituicoes') {
      $eid = $_GET['eid'];
      
      $result = mysqli_query($GLOBALS['dblink'], "SELECT id_fonte FROM escritas WHERE id = " . $eid) or die(mysqli_error($GLOBALS['dblink']));
      $r = mysqli_fetch_assoc($result);
      $fonte = $r['id_fonte'];
      
      $result = mysqli_query($GLOBALS['dblink'], 
          "SELECT *, CHAR_LENGTH(tecla) as tam FROM autosubstituicoes WHERE id_escrita = " . $eid . " ORDER BY tam DESC;") 
          or die(mysqli_error($GLOBALS['dblink']));
      
      $autosubs = [];
      while ($r = mysqli_fetch_assoc($result)) {
          $autosubs[] = [
              'tecla' => $r['tecla'],
              'glifos' => $r['glifos'],
              'tam' => $r['tam']
          ];
      }
      
      echo json_encode([
          'fonte' => $fonte,
          'autosubs' => $autosubs
      ]);
      die();
  }

  if ($_GET['action'] == 'getAutoSubstituicao') {
    $palavra = "";
    $eid = $_GET['eid'];
    $input = $_POST['p'];

    $result = mysqli_query($GLOBALS['dblink'], "SELECT id_fonte FROM escritas WHERE id = " . $eid) or die(mysqli_error($GLOBALS['dblink']));
    $r = mysqli_fetch_assoc($result);
    $fonte = $r['id_fonte'];

    $result = mysqli_query($GLOBALS['dblink'], 
        "SELECT *, CHAR_LENGTH(tecla) as tam FROM autosubstituicoes WHERE id_escrita = " . $eid . " ORDER BY tam DESC;") 
        or die(mysqli_error($GLOBALS['dblink']));

    if ($fonte == 3) {
        $matches = [];
        $input_len = mb_strlen($input);
        
        // Check for all possible matches
        while ($r = mysqli_fetch_assoc($result)) {
            if (mb_strpos($r['tecla'], $input) === 0) { // Match starts with input
                $matches[] = [
                    'id' => $r['glifos'],
                    'desc' => $r['tecla'], // Use tecla as description, or fetch from drawChars if needed
                    'tam' => $r['tam']
                ];
            }
        }

        if (empty($matches)) {
            echo '-1';
        } else {
            // Sort with exact matches first, then by length (descending), then by glifos ID
            usort($matches, function($a, $b) use ($input) {
                $a_exact = $a['desc'] === $input;
                $b_exact = $b['desc'] === $input;
                
                if ($a_exact && !$b_exact) {
                    return -1; // a is exact, b is not, so a comes first
                } elseif (!$a_exact && $b_exact) {
                    return 1; // b is exact, a is not, so b comes first
                } else {
                    // Both are exact or both are not exact, sort by length then ID
                    if ($a['tam'] == $b['tam']) {
                        return strcmp($a['id'], $b['id']);
                    }
                    return $b['tam'] - $a['tam'];
                }
            });
            echo json_encode($matches);
        }
    } else {
        // Existing logic for non-drawchar systems
        for ($i = 0; $i < mb_strlen($input); $i++) {
            $found = '*';
            mysqli_data_seek($result, 0);
            while ($r = mysqli_fetch_assoc($result)) {
                if (mb_substr($input, $i, $r['tam']) == $r['tecla']) {
                    $found = $r['glifos'];
                    $i += $r['tam'] - 1;
                    break;
                }
            }
            $palavra .= $found;
        }
        if (mb_substr_count($palavra, '*') == 0) {
            echo $palavra;
        } else {
            echo '';
        }
    }
    die();
  }

  if ($_GET['action']=='ajaxGravarSintazBazic') {

    if($_GET['iid']>0){ 
      $sqlQuerys = "UPDATE idiomas SET 
        ordem = '".$_POST['ordem']."',
        marcacao = '".$_POST['marcacao']."',
        direcao = '".$_POST['direcao']."',
        sintese = '".$_POST['sintese']."',
        data_modificacao = now(),
        alinhamento = '".$_POST['alinhamento']."'
        WHERE id = ".$_GET['iid']." LIMIT 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['iid'];

    }
    die();
  }

  if ($_GET['action']=='ajaxGravarReferentes') {
    
    if($_GET['pid']>0 && isset($_POST['referentes'])){
      mysqli_query($GLOBALS['dblink'],"DELETE FROM palavras_referentes WHERE id_palavra = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));

      $query = "INSERT INTO palavras_referentes (id, id_palavra,id_referente) VALUES ";
      foreach($_POST['referentes'] as $ref){
          if ($ref>0){
            $query .= " (".generateId().",".$_GET['pid'].",".$ref."),";
            /*mysqli_query($GLOBALS['dblink'],"INSERT INTO palavras_referentes SET 
              id_palavra = ".$_GET['pid'].",
              id_referente = ".$ref.";") or die(mysqli_error($GLOBALS['dblink']));*/
          }
      }
      $query = substr($query,0,strlen($query)-1).';';
      mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    }
    die('ok');
  }

  if ($_GET['action']=='ajaxGravarOrigens') { // otimizar sql query
    if($_GET['pid']>0){
      mysqli_query($GLOBALS['dblink'],"DELETE FROM palavras_origens WHERE id_palavra = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));

      $i = 0;
      foreach($_POST['origens'] as $ref){
        if ($ref>0){
          mysqli_query($GLOBALS['dblink'],"INSERT INTO palavras_origens SET 
              id_palavra = ".$_GET['pid'].",
              detalhes = '', id = ".generateId().", ordem = $i,
              id_origem = ".$ref.";") or die(mysqli_error($GLOBALS['dblink']));
          $i++;
        }
      }
    }
    die('ok');
  }

  if ($_GET['action'] == 'salvarPalavraNativa') {
    //get pid (id_palavra)  e (id_escrita)
    // post p (palavra)
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavrasNativas 
        WHERE id_escrita = ".$_GET['e']." AND id_palavra = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));

    if (mysqli_num_rows($result)>0){
      //update
      mysqli_query($GLOBALS['dblink'],"UPDATE palavrasNativas SET palavra = '".$_POST['p']."'
        WHERE id_escrita = ".$_GET['e']." AND id_palavra = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));
    }else{
      //insert
      mysqli_query($GLOBALS['dblink'],"INSERT INTO palavrasNativas SET 
          palavra = '".$_POST['p']."', id = ".generateId().",
          id_escrita = ".$_GET['e'].",
          id_palavra = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));
    }
    die('ok');
  };

  if ($_GET['action'] == 'ajaxGravarOpcaoPadrao') { 
    // GET op > itensConcordancias.id
    // GET k > id_concordancia
    // GET p > padrao
    mysqli_query($GLOBALS['dblink'],"UPDATE itensConcordancias SET padrao = 0 WHERE id_concordancia = ".$_GET['k']." AND padrao = 1;") or die(mysqli_error($GLOBALS['dblink']));
    mysqli_query($GLOBALS['dblink'],"UPDATE itensConcordancias SET padrao = 1 WHERE id = ".$_GET['op'].";") or die(mysqli_error($GLOBALS['dblink']));
    
    //SIM deletar regexes e itens, caso a concordancia apagada era flexionada

    die('ok');
  };

  if ($_GET['action'] == 'ajaxApagarListaSC') {
    mysqli_query($GLOBALS['dblink'],"DELETE FROM soundChanges
        WHERE id = ".$_GET['id']." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
  };

  if ($_GET['action'] == 'ajaxImportarListaPalavrasDicionario') {  die('0');

    $refs = explode("\n",$_POST['lista']);
    foreach($refs as $ref){
      //echo $ref;

      // if explode | => romanizacao|significado
      $r = explode("|",$ref);

      mysqli_query($GLOBALS['dblink'],"INSERT IGNORE INTO palavras SET 
          id_idioma = '".$_GET['iid']."',
          id_forma_dicionario = 0,
          significado = '".$r[0]."',
          romanizacao = '".$r[1]."',
          pronuncia = '".$r[2]."';");
    }

    die('ok');
  };

  if ($_GET['action'] == 'ajaxImportarListaDicionario') { // URGENT? otimizar sql query

    $refs = mysqli_query($GLOBALS['dblink'],"SELECT r.*, p.significado as descr 
        FROM listas_referentes lr
          LEFT JOIN referentes r ON r.id = lr.id_referente
          LEFT JOIN palavras_referentes pr ON pr.id_referente = r.id 
          LEFT JOIN palavras p ON p.id = pr.id_palavra
        WHERE lr.id_lista = ".$_GET['id']." AND p.id_forma_dicionario = 0
        AND p.id_idioma = ".$_SESSION['KondisonairUzatorDiom'].";"); 
        
        // + LEFT JOIN palavras WHERE id_idioma = KondisonairUzatorDiom AND id_dicio=0

    //xxxxx checar LIMIT antes de começar inserir ?

    while($r = mysqli_fetch_assoc($refs)) {
      
        $ress = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras p
            LEFT JOIN palavras_referentes pr ON pr.id_palavra = p.id
              WHERE p.id_forma_dicionario = 0 AND p.id_idioma = ".$_GET['iid']."
              AND pr.id_referente = ".$r['id'].";");

        if (mysqli_num_rows($ress)==0){

            $pid = generateId();
            mysqli_query($GLOBALS['dblink'],"INSERT INTO palavras SET 
              id_idioma = '".$_GET['iid']."',
              id_forma_dicionario = 0, id = $pid,
              significado = '".$r['descr']."',
              detalhes = '',
              romanizacao = '',
              pronuncia = '';");
            echo 'Relacionar:<br>';
              
            $prid = generateId();
            mysqli_query($GLOBALS['dblink'],"INSERT INTO palavras_referentes SET 
              id_referente = ".$r['id'].",
              id_palavra = ".$pid.", id = $prid;");
          
            echo $prid.$r['descr'].'<br>'; // ? mysqli_insert_id($GLOBALS['dblink']).$r['descr'].'<br>';
        }
    };

    die('ok');
  };

  if ($_GET['action'] == 'ajaxResetarRegras') {

    //ler tb idioma e deletar tds regras dele, dps add novas fixas 

    die('par fzer');
  };

  if ($_GET['action'] == 'ajaxSetTabelaSons') {

    $nome = $_GET['nome'];
    if (strlen($_GET['codigo'])>0) $cod = $_GET['codigo'];
    else $cod = substr($_GET['nome'],0,1);

    if ($_GET['id']>0){
        // update

        mysqli_query($GLOBALS['dblink'],"INSERT INTO tiposSom SET 
        codigo = '".$cod."',
        titulo = '".$nome."'
        WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        echo $_GET['id'];
    }else{
        // insert
        $lastDim = 0;
        $tid = generateId();

        mysqli_query($GLOBALS['dblink'],"INSERT INTO tiposSom SET 
          codigo = '".$cod."',
          titulo = '".$nome."',
          dimx = ".++$lastDim.",
          dimy = ".++$lastDim.",
          dimz = ".++$lastDim.",
          id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
        echo mysqli_insert_id($GLOBALS['dblink']);
    }

    //ler tb idioma e deletar tds regras dele, dps add novas fixas 

    die();
  };

  if ($_GET['action'] == 'carregarEdicaoSons') { // otimizar sql query
    
    $tipo = $_GET['t'];
    $tmp = mysqli_query($GLOBALS['dblink'],"SELECT * FROM tiposSom WHERE id = ".$tipo.";") or die(mysqli_error($GLOBALS['dblink']));
    $t = mysqli_fetch_assoc($tmp);

    $dimx = $t['dimx'];
    $dimy = $t['dimy'];
    echo '<label class="control-label">'._t('Adicionar Coluna').'</label>
        <select id="sel_pos_'.$dimx.'" class="form-select mb-3"  onchange="adicionarEmDimensao('.$dimx.')"><option disabled selected>'._t('Escolher').'</option>';
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTudo 
      WHERE dimensao = ".$dimx." AND pos NOT IN (SELECT pos FROM ipaTitulos WHERE dimensao = ".$dimx." AND id_idioma = ".$_GET['iid'].") ORDER BY pos;");
    while($r = mysqli_fetch_assoc($result)) {
      
      $cat = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(CONCAT(nome,' ',ipa)) as ipa FROM sons
        WHERE posy = ".$r['pos']." AND id_tipoSom = ".$tipo." ORDER BY posx;");
      $c = mysqli_fetch_assoc($cat);
      echo '<option title="'.$c['ipa'].'" value="'.$r['pos'].'">'.$r['nome'].'</option>';
    }
    echo '<option value="0">'._t('Personalizado').'</option></select>';
    
    echo '<label class="control-label">'._t('Adicionar Linha').'</label>
      <select id="sel_pos_'.$dimy.'" class="form-select mb-3"  onchange="adicionarEmDimensao('.$dimy.')"><option disabled selected>'._t('Escolher').'</option>';
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTudo
      WHERE dimensao = ".$dimy."  AND pos NOT IN (SELECT pos FROM ipaTitulos WHERE dimensao = ".$dimy." AND id_idioma = ".$_GET['iid'].") ORDER BY pos;");
    while($r = mysqli_fetch_assoc($result)) {

      $cat = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(CONCAT(nome,' ',ipa)) as ipa FROM sons
        WHERE posx = ".$r['pos']." AND id_tipoSom = ".$tipo." ORDER BY posy;");
      $c = mysqli_fetch_assoc($cat);
      
      echo '<option title="'.$c['ipa'].'" value="'.$r['pos'].'">'.$r['nome'].'</option>';
    }
    echo '<option value="0">'._t('Personalizado').'</option></select>';

    // carregar modelinhos
    /*echo '<label class="control-label">Basear-se num modelo</label>
    <select id="a" class="chosen-select form-control "  onchange="()"><option disabled>Escolher<option>
      <option value="1" selected>Simples (japonês)<option>
      <option value="2">Mediano (Português)<option>
      <option value="3">Complexo (Africano)<option>
      <option value="4">Minimalista (Hawaii)<option>
      <option disabled>Meus idiomas:<option>
      <option disabled>Outros exemplos:<option>
    </select>';*/

    die();

  };

  if ($_GET['action'] == 'carregarMoverSom') { //xxxxxxxxxx/ otimizar sql query

    // get iid, get t, det x, get y, = carregarEdicaoCelula - pra pegar o som
    // carregarTabelaSons - pra pegar linhas/colunas disponíveis
    
    $is = mysqli_query($GLOBALS['dblink'],"SELECT *, i.id as id_inventario 
            FROM inventarios i 
            LEFT JOIN sons s ON i.id_som = s.id 
            WHERE s.id = ".$_GET['id']." 
              AND s.id_tipoSom = ".$_GET['t']." 
              AND i.id_tipoSom > 0
              AND i.id_idioma = ".$_GET['iid']." 
              ORDER BY s.posz;") or die(mysqli_error($GLOBALS['dblink']));
    $i = mysqli_fetch_assoc($is);

    
    
    $tipo = $_GET['t'];
    $tmp = mysqli_query($GLOBALS['dblink'],"SELECT * FROM tiposSom WHERE id = ".$tipo.";") or die(mysqli_error($GLOBALS['dblink']));
    $t = mysqli_fetch_assoc($tmp);

    $dimx = $t['dimx'];
    $dimy = $t['dimy'];

    echo '<label class="control-label">'._t('Mover %1 (%2) para a Coluna',[$i['ipa'],$i['nome']]).'</label>
        <select id="sel_mpos_'.$dimx.'" class="form-select "  onchange="moverSomPara('.$dimx.')"><option disabled>'._t('Escolher').'</option>';
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTitulos WHERE dimensao = ".$dimx." AND id_idioma = ".$_GET['iid']." ORDER BY pos;");
    while($r = mysqli_fetch_assoc($result)) {
      echo '<option value="'.$r['pos'].'">'.$r['nome'].'</option>';
    }
    echo '</select>';
    
    echo '<label class="control-label">'._t('ou mover para a Linha').'</label>
      <select id="sel_mpos_'.$dimy.'" class="form-select"  onchange="moverSomPara('.$dimy.')"><option disabled>'._t('Escolher').'</option>';
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTitulos WHERE dimensao = ".$dimy." AND id_idioma = ".$_GET['iid']." ORDER BY pos;");
    while($r = mysqli_fetch_assoc($result)) {
      echo '<option value="'.$r['pos'].'">'.$r['nome'].'</option>';
    }
    echo '</select>';

    echo '<div class="col-12">
            <a class="btn btn-primary"  onclick="carregaTabela()">Voltar</a>
      </div>';

    die();

  };

  if ($_GET['action'] == 'ajaxAdicionarDimensao') {
    mysqli_query($GLOBALS['dblink'],"INSERT INTO ipaTitulos SET 
      dimensao = ".$_GET['dim'].", 
      id_idioma = ".$_GET['iid'].",
      pos = ".$_GET['pos'].", id = ".generateId().",
      nome = '".$_GET['nome']."';") or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
  };

  if ($_GET['action'] == 'ajaxApagarDimensao') { // otimizar sql query
    // dim 5, pos 20, iid 4, t 2:  e o
    // dim 2, pos 10, iid 4, t 1:  5 m̥ n
    // dim 1, pos 10, iid 6, t 1:  m m̥ p
    // 2 15 4 1: p b
    // t= tipoSom, em sons apenas (C/V)

    // se t>0, é som / senao, é somPersonalizado

    //1o descobrir se a dimensao passada é x y ou z
    $dims = mysqli_query($GLOBALS['dblink'],"SELECT * FROM tiposSom WHERE dimx = ".$_GET['dim'].";") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($dims)>0){
      $p = 'y';
      $d = 'x';
    }else{
      $dims = mysqli_query($GLOBALS['dblink'],"SELECT * FROM tiposSom WHERE dimy = ".$_GET['dim'].";") or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($dims)>0){
        $p = 'x';
        $d = 'y';
      }
    }

    $dims = mysqli_query($GLOBALS['dblink'],"SELECT * FROM inventarios i 
        LEFT JOIN sons s ON ( s.id = i.id_som AND i.id_tipoSom > 0 )
        LEFT JOIN tiposSom t ON t.id = s.id_tipoSom
        WHERE s.pos".$p." = ".$_GET['pos']." AND t.dim".$d." = ".$_GET['dim']." 
        AND id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
    while($r = mysqli_fetch_assoc($dims)) {
        mysqli_query($GLOBALS['dblink'],"DELETE FROM inventarios
            WHERE id_idioma = ".$_GET['iid']." 
            AND id_som = ".$r['id_som']." AND id_tipoSom > 0;") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM sons_classes
            WHERE tipo = 1 AND id_som = ".$r['id_som'].";") or die(mysqli_error($GLOBALS['dblink']));
    }

    $dims = mysqli_query($GLOBALS['dblink'],"SELECT * FROM inventarios i 
    LEFT JOIN sonsPersonalizados s ON ( s.id = i.id_som AND i.id_tipoSom = 0 )
    WHERE s.pos".$p." = ".$_GET['pos']." 
      AND s.id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
    //xxxxx apagar sons dela tbm
    while($r = mysqli_fetch_assoc($dims)) {
        mysqli_query($GLOBALS['dblink'],"DELETE FROM inventarios
            WHERE id_idioma = ".$_GET['iid']." 
            AND id_som = ".$r['id_som']." AND id_tipoSom = 0;") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM sons_classes
            WHERE tipo = 2 AND id_som = ".$r['id_som'].";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM sonsPersonalizados
            WHERE id = ".$r['id_som'].";") or die(mysqli_error($GLOBALS['dblink']));
    }

    mysqli_query($GLOBALS['dblink'],"DELETE FROM ipaTitulos WHERE dimensao = ".$_GET['dim']." 
      AND id_idioma = ".$_GET['iid']." AND pos = ".$_GET['pos'].";") or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
  };

  if ($_GET['action'] == 'ajaxToggleEstruturaSilabica') { // iid

      $fs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM formasSilaba
          WHERE id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));

      if(mysqli_num_rows($fs)==1){
          mysqli_query($GLOBALS['dblink'],"INSERT INTO formasSilaba 
              (nome, id_idioma, tipo, id) VALUES
                  ('',".$_GET['iid'].",1,".generateId()."),
                  ('',".$_GET['iid'].",2,".generateId()."),
                  ('',".$_GET['iid'].",3,".generateId()."),
                  ('',".$_GET['iid'].",4,".generateId().");") or die(mysqli_error($GLOBALS['dblink']));
      }else{

          mysqli_query($GLOBALS['dblink'], "
                DELETE FROM formasSilaba 
                WHERE id_idioma = " . intval($_GET['iid']) . " 
                AND (tipo <> 0 OR id NOT IN (
                    SELECT id 
                    FROM (
                        SELECT MIN(id) as id 
                        FROM formasSilaba 
                        WHERE id_idioma = " . intval($_GET['iid']) . " 
                        AND tipo = 0
                    ) AS sub
                ))
            ") or die(mysqli_error($GLOBALS['dblink']));
          mysqli_query($GLOBALS['dblink'],"DELETE FROM formaSilabaComponente 
              WHERE id_formaSilaba NOT IN(SELECT id FROM formasSilaba WHERE id_idioma = ".$_GET['iid'].");") or die(mysqli_error($GLOBALS['dblink']));

      }

      die('ok');
  };

  if ($_GET['action'] == 'ajaxEstruturaSilabica') {   //xxxxx otimizar sql query

    // estrutura principal: ex. (C)(R)V(D)
    // syllable = onset + rhyme  |  rhyme = nucleus + coda  
    // ditongos?
    // sonoridade: manualmente pelo usuario
    // impossible clusters: por classe RC = dame, e por sons direto tbm
    // tabela com possiveis silabas
    // stress ? tones ? heavy/light syllables?

    // $tem = 0;

    // tipo 0 = geral/medial
    $fs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM formasSilaba
        WHERE id_idioma = ".$_GET['iid']." ORDER BY tipo;") or die(mysqli_error($GLOBALS['dblink']));
    if(mysqli_num_rows($fs)==0){

        // criar tipo 0=Geral

        mysqli_query($GLOBALS['dblink'],"INSERT INTO formasSilaba SET 
          id_idioma = ".$_GET['iid'].", id = ".generateId().",
          nome = '',
          tipo = 0;") or die(mysqli_error($GLOBALS['dblink']));
          
        $fs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM formasSilaba
            WHERE id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
      
    }
    if(mysqli_num_rows($fs)==1) $label0 = _t('Sílabas gerais').'<script>sCk(0)</script>';
    else $label0 = _t('Sílabas gerais').'<script>sCk(1)</script>';

    while($f = mysqli_fetch_assoc($fs)){
      if ($f['tipo']==0) $label = $label0;
      else if($f['tipo']==1) $label = _t('Sílabas iniciais');
      else if($f['tipo']==2) $label = _t('Sílabas mediais');
      else if($f['tipo']==3) $label = _t('Sílabas finais');
      else if($f['tipo']==4) $label = _t('Monossílabas');
      echo '<div class="list-group-item">
            <div class="row"  id="'.$f['tipo'].'" ondragover="dragoverHandler(event)" ondrop="dropHandler(event)">
                <div class="col-auto" >
                    <label class="form-label">'.$label.'</label>
                    <div>
                        <div class="form-selectgroup">';

      $sql = "SELECT f.*, c.simbolo, c.nome FROM formaSilabaComponente f
          LEFT JOIN classesSom c ON ( f.id_classeSom = c.id )
          WHERE f.id_formaSilaba = ".$f['id']." ORDER BY f.ordem;";
      $result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

      $snds = 0; $usnd = 0;

      if(mysqli_num_rows($result)==0){
          echo '<span class="text-secondary">'._t('Arraste alguma categoria aqui').'</span>';
      }else{
          while($r = mysqli_fetch_assoc($result)) { 
              $snds++; $usnd = $r['id'];
              if ($r['obrigatorio']==1) 
                  echo '<a class="form-selectgroup-item btn" title="'.$r['nome'].'" draggable="true" ondragstart="dragstartHandler(event)" 
                      onclick="rmC(\''.$r['id'].'\')" id="'.$r['simbolo'].$r['id'].'">'.$r['simbolo'].'</a>';
              else
                  echo '<a class="form-selectgroup-item btn" title="'.$r['nome'].'" draggable="true" ondragstart="dragstartHandler(event)" 
                      onclick="rmC(\''.$r['id'].'\')" id="'.$r['simbolo'].$r['id'].'">('.$r['simbolo'].')</a>';
          }
          if ($snds > 0) echo '<a onclick="clrS(\''.$usnd.'\')" class="form-selectgroup-item btn btn-sm btn-danger">x</a>';
      }

      echo'</div></div></div>';
      echo'</div></div>';

    }

    die();
  };

  if ($_GET['action'] == 'ajaxSetComponenteSilaba') { 

      if($_POST['to']>5) die('ok');

      // if tipo == r > remover pelo GET id
      if($_GET['tipo']=='r'){
          // remover de formaSilabaComponente pelo GET id
      }else{

          // POST FROM
          if($_GET['id']>0){

              // pode ser update do obrigatorio
              $j = mysqli_query($GLOBALS['dblink'],"SELECT obrigatorio FROM formaSilabaComponente c 
                WHERE c.id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
              //$count = mysqli_num_rows($j);
              $s = mysqli_fetch_assoc($j);
              if($s['obrigatorio']==1){
                  // update 0
                  mysqli_query($GLOBALS['dblink'],"UPDATE formaSilabaComponente SET obrigatorio = 0 WHERE id = ".$_GET['id']) or die(mysqli_error($GLOBALS['dblink']));
              }else{
                  // update 1
                  mysqli_query($GLOBALS['dblink'],"UPDATE formaSilabaComponente SET obrigatorio = 1 WHERE id = ".$_GET['id']) or die(mysqli_error($GLOBALS['dblink']));
              }

          }else{

              $j = mysqli_query($GLOBALS['dblink'],"SELECT f.id, 
                  (SELECT COUNT(*) FROM formaSilabaComponente c WHERE c.id_formaSilaba = f.id) as num 
                  FROM formasSilaba f
                  WHERE f.id_idioma = ".$_GET['iid']." AND f.tipo = ".$_POST['to'].";") or die(mysqli_error($GLOBALS['dblink']));
              //$count = mysqli_num_rows($j);
              $s = mysqli_fetch_assoc($j);

              $sql = "INSERT INTO formaSilabaComponente SET 
                  id_formaSilaba = ".$s['id'].", 
                  id = ".generateId().",
                  obrigatorio = 1, 
                  id_classeSom = '".substr($_POST['from'],1)."',
                  ordem = ".($s['num']+1).";";
              //echo $sql;

              // post from C1 simbolo + id em classesSom
              // to 1 : tipo em formasSilaba

              mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

              // count formasSilaba where iid
              // ordem = num + 1
              // insert into formaSilabaComponente
          }

      }

      die('ok');
  };

  if ($_GET['action'] == 'adicionarComponenteSilaba') { 
    //get iid
    // post id (classesSom.id) , ob

    if($_POST['id']>0){
      //update silabaComponente existente
      mysqli_query($GLOBALS['dblink'],"UPDATE formaSilabaComponente SET 
        id_classeSom = ".$_POST['idc'].", 
        obrigatorio = ".$_POST['ob']."
        WHERE id = ".$_POST['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    }else{
      //inserir componente na silaba POST-f
      $idForma = 0;
      if($_POST['f']>0){
        $idForma = $_POST['f'];
      }else{
        //criar
        $idForma = generateId();
        mysqli_query($GLOBALS['dblink'],"INSERT INTO formasSilaba SET 
          id_idioma = ".$_GET['iid'].",
          nome = '', id = $idForma,
          tipo = 0;") or die(mysqli_error($GLOBALS['dblink']));
      }
      mysqli_query($GLOBALS['dblink'],"INSERT INTO formaSilabaComponente SET 
        id_formaSilaba = ".$idForma.",
        obrigatorio = ".$_POST['ob'].",
        ordem = 0, id = ".generateId().",
        id_classeSom = ".$_POST['idc'].";") or die(mysqli_error($GLOBALS['dblink']));
    }
    die('ok');
  };

  if ($_GET['action'] == 'removerComponenteSilaba') {
    //get iid
    if($_GET['id']>0){
      //update silabaComponente existente
      mysqli_query($GLOBALS['dblink'],"DELETE FROM formaSilabaComponente  
        WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    }
    die('ok');
  };
  
  if ($_GET['action'] == 'ajaxApagarTexto') {
    //get iid
    if($_GET['id']>0){
      mysqli_query($GLOBALS['dblink'],"DELETE FROM tests_importasons WHERE id_texto = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"DELETE FROM studason_tests WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    }
    die('ok');
  };

  if ($_GET['action'] == 'carregarTabelaAlfabeto') {
    // GET iid = idioma


    die();
  };

  if ($_GET['action'] == 'ajaxEditarSom') {

    $resp = verificarPalavrasComEsseSom($_GET['iid'], $_GET['id'], $_POST['textosIgnorar'], $_POST['textosRemover'], $_POST['textosAtualizar'],$_GET['p']);
    if ($resp != 0) {
        echo 'palavras|'.$resp;
        die();
    }

    if($_GET['p']>0){ // removendo som personalizado?

      mysqli_query($GLOBALS['dblink'],"DELETE FROM inventarios
        WHERE id_tipoSom = 0 AND id_som = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"DELETE FROM sonsPersonalizados
        WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"DELETE FROM teclas
        WHERE id_inventario = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      die('deletado');
    }

    $is = mysqli_query($GLOBALS['dblink'],"SELECT * FROM inventarios i 
        WHERE i.id_idioma = ".$_GET['iid']." AND i.id_som = ".$_GET['id']." 
        AND i.id_tipoSom = ".$_GET['t'].";") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($is)>0){ 
      //fetch ?
      //remover do inventario where 
      mysqli_query($GLOBALS['dblink'],"DELETE FROM inventarios
        WHERE id_idioma = ".$_GET['iid']." AND id_som = ".$_GET['id']." 
        AND id_tipoSom = ".$_GET['t'].";") or die(mysqli_error($GLOBALS['dblink']));

      die('removido'); 
    }

    $lasid = generateId();
    mysqli_query($GLOBALS['dblink'],"INSERT INTO inventarios SET 
        id_idioma = ".$_GET['iid'].", 
        id_som = ".$_GET['id'].", 
        id = $lasid,
        id_tipoSom = ".$_GET['t'].";") or die(mysqli_error($GLOBALS['dblink']));

    echo $lasid;
    die();
  };

  if ($_GET['action'] == 'adicionarCategoriaSom') {
    if($_GET['id']>0){
      //update
      mysqli_query($GLOBALS['dblink'],"UPDATE classesSom SET 
        simbolo = '".$_POST['simbolo']."',
        nome = '".$_POST['nome']."'
        WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['id'];
    }else{
      $cid = generateId();
      mysqli_query($GLOBALS['dblink'],"INSERT INTO  classesSom SET
        id_idioma = ".$_GET['iid'].",
        simbolo = '".$_POST['simbolo']."',
        nome = '".$_POST['nome']."',id = $cid,
        id_tipoClasse = 0;") or die(mysqli_error($GLOBALS['dblink']));
      echo $cid;
    }
    die();
  };

  if ($_GET['action'] == 'ajaxGetDivLateralWriting') {

      // ordem alfabetica?
    if($_GET['eid']) $eid = $_GET['eid']; else die('novalered'); 

    // pelo eid pegar a fonte, e se a fonte for < 0 daí é drawCaractere, não se eid < 0
    $result = mysqli_query($GLOBALS['dblink'],"SELECT id_fonte, tamanho FROM escritas WHERE id = ".$eid) or die(mysqli_error($GLOBALS['dblink']));
    $r = mysqli_fetch_assoc($result);
    $fonte = $r['id_fonte'];
    $tamanho = $r['tamanho'];

    if($fonte == 3){
      $query = "SELECT g.*, DATE_FORMAT( data_modificado,'%Y%m%d%H%i%s') as ultima,
        (SELECT GROUP_CONCAT(glifo SEPARATOR' ') FROM drawChars v WHERE v.id_principal = g.id) as variantes
        FROM drawChars g 
        WHERE g.id_escrita = ".$eid." AND g.id_principal = 0 ORDER BY g.ordem;";
        //
    }else{
      $query = "SELECT g.*,
            (SELECT GROUP_CONCAT(glifo SEPARATOR' ') FROM glifos v WHERE v.id_principal = g.id) as variantes
            FROM glifos g 
            WHERE g.id_escrita = ".$eid." AND g.id_principal = 0 ORDER BY g.ordem;";
    }


    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    
    while($r = mysqli_fetch_assoc($result)){
      if($fonte == 3< 0){
        $glifo = '<span title="'.$r['descricao'].'" class="drawchar drawchar-'.$tamanho.'" style="background-image: url(./writing/'.$eid.'/'.$r['id'].'.png?'.$r['ultima'].')"></span> ';
        echo '<label><span onclick="addNatDraw(\''.$r['id'].'\','.$fonte.',\''.$tamanho.'\')" class="form-selectgroup-label">'.$glifo.'</span></label>';
      }else{
        $glifo = "<span title='".$r['descricao']."' class='custom-font-".$r['id_escrita']."'>".$r['glifo'].'</span>';
        echo '<label><span onclick="addNatChar(\''.$r['glifo'].'\')" class="form-selectgroup-label">'.$glifo.'</span></label>';
      }

    };

    if($fonte == 3) echo '<div class="mt-3">
          <div id="tmpdrawchar"></div>
          <button class="btn btn-primary" type="button" data-bs-dismiss="offcanvas" onclick="exibirNativa(\''.$eid.'\',$(\'#tempNat\').val(),\''.$fonte.'\',\''.$tamanho.'\')">
          '._t('Ok').'
          </button>
        </div>';

        // add seq dos numeros em tempNat mesmo
    die();

  };

  if ($_GET['action'] == 'ajaxGetDivLateralWriting2') {
      if ($_GET['eid']) $eid = $_GET['eid']; else die('novalered');

      $result = mysqli_query($GLOBALS['dblink'], "SELECT id_fonte, tamanho FROM escritas WHERE id = ".$eid) or die(mysqli_error($GLOBALS['dblink']));
      $r = mysqli_fetch_assoc($result);
      $fonte = $r['id_fonte'];
      $tamanho = $r['tamanho'];

      if ($fonte == 3) {
          $query = "SELECT g.*, DATE_FORMAT(data_modificado,'%Y%m%d%H%i%s') as ultima,
              (SELECT GROUP_CONCAT(glifo SEPARATOR ' ') FROM drawChars v WHERE v.id_principal = g.id) as variantes
              FROM drawChars g 
              WHERE g.id_escrita = ".$eid." AND g.id_principal = 0 ORDER BY g.ordem;";
      } else {
          $query = "SELECT g.*,
              (SELECT GROUP_CONCAT(glifo SEPARATOR ' ') FROM glifos v WHERE v.id_principal = g.id) as variantes
              FROM glifos g 
              WHERE g.id_escrita = ".$eid." AND g.id_principal = 0 ORDER BY g.ordem;";
      }

      $result = mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
      
      while ($r = mysqli_fetch_assoc($result)) {
          if ($fonte == 3) {
              $glifo = '<span title="'.$r['descricao'].'" class="drawchar drawchar-'.$tamanho.'" style="background-image: url(./writing/'.$eid.'/'.$r['id'].'.png?'.$r['ultima'].')"></span>';
              echo '<label><span onclick="addNatDraw(\''.$r['id'].'\','.$fonte.',\''.$tamanho.'\')" class="form-selectgroup-label">'.$glifo.'</span></label>';
          } else {
              $glifo = "<span title='".$r['descricao']."' class='custom-font-".$r['id_escrita']."'>".$r['glifo'].'</span>';
              echo '<label><span onclick="addNatChar(\''.$r['glifo'].'\')" class="form-selectgroup-label">'.$glifo.'</span></label>';
          }
      }
      die();
  }

  if ($_GET['action'] == 'ajaxGetDivLateralSons') {

      // ordem alfabetica?

      $id_idioma = $_GET['iid'];
      
      $is = mysqli_query($GLOBALS['dblink'],"SELECT s.nome, p.nome as nome2, s.ipa, p.ipa as ipa2, t.tecla FROM inventarios i
				LEFT JOIN teclas t ON t.id_inventario = i.id
				LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
				LEFT JOIN sonsPersonalizados p ON ( p.id = i.id_som AND i.id_tipoSom = 0 )
				WHERE i.id_idioma = ".$id_idioma."
        ORDER BY t.ordem;") or die(mysqli_error($GLOBALS['dblink'])); // order by ordem alfabetica tambem depois!
			while($r = mysqli_fetch_assoc($is)) { 
				if ($r['tecla']!='') $btn = $r['tecla'].' /'.$r['ipa'].$r['ipa2'].'/';
				else $btn = $r['ipa'].$r['ipa2'];
				echo '<a title="'.$r['nome'].$r['nome2'].'" class="btn btn-primary mb-3 mx-2" onclick=\'addIpaPronuncia(`'.$r['ipa'].$r['ipa2'].'`)\'>'.$btn.'</a> ';
			};
      die();
  }

  if ($_GET['action'] == 'carregarEdicaoCelula') { // otimizar sql queries

    // add nas selects um espao e dps lista todos os sons disponíveis!
    $tdss = mysqli_query($GLOBALS['dblink'],"SELECT * FROM sons s
      WHERE s.id_tipoSom = ".$_GET['t']." 
      AND s.id NOT IN (SELECT id_som FROM inventarios WHERE id_idioma = ".$_GET['iid']." AND id_tipoSom > 0)
      ORDER BY s.posz;") or die(mysqli_error($GLOBALS['dblink']));

    // editarCelula GET iid x y z t
    echo '<label class="control-label">'._t('Adicionar').'</label><select id="sel_i" class="form-select mb-3"  
      onchange="adicionarSom()"><option disabled selected>'._t('Escolher').'</option>';
    $is = mysqli_query($GLOBALS['dblink'],"SELECT * FROM sons s
      WHERE s.posx = ".$_GET['x']." AND s.posy = ".$_GET['y']." 
      AND s.id_tipoSom = ".$_GET['t']." 
      AND s.id NOT IN (SELECT id_som FROM inventarios WHERE id_idioma = ".$_GET['iid']." AND id_tipoSom > 0)
      ORDER BY s.nome;") or die(mysqli_error($GLOBALS['dblink']));
    while ($i = mysqli_fetch_assoc($is)) {
      echo '<option value="'.$i['id'].'">'.$i['ipa'].' - '.$i['nome'].'</option>';
    }
    echo '<option disabled>── '._t('Todos os sons').' ──</option><option value="0">Personalizado</option><option disabled>── '._t('Todos os sons').' ──</option>';

    // loop tdss
    while ($ts = mysqli_fetch_assoc($tdss)) {
      echo '<option value="-'.$ts['id'].'">'.$ts['ipa'].' - '.$ts['nome'].'</option>';
    }

    echo '</select><h4>'._t('Opções').'</h4>';

    //o que já tem, mostrar maior e com opcoes, incl de removerSom(idSom)
    $is = mysqli_query($GLOBALS['dblink'],"SELECT *, i.id as id_inventario FROM inventarios i 
            LEFT JOIN sons s ON i.id_som = s.id 
            WHERE s.posx = ".$_GET['x']." AND s.posy = ".$_GET['y']." 
              AND s.id_tipoSom = ".$_GET['t']." 
              AND i.id_tipoSom > 0
              AND i.id_idioma = ".$_GET['iid']." 
              ORDER BY s.posz;") or die(mysqli_error($GLOBALS['dblink']));
    while ($i = mysqli_fetch_assoc($is)) {

      // ver se tem keystroke em 
      $keys = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(tecla ORDER BY ordem SEPARATOR ' ') as tecla FROM teclas WHERE id_inventario = ".$i['id_inventario'].";") or die(mysqli_error($GLOBALS['dblink']));
      $key = mysqli_fetch_assoc($keys);
      if ($key['tecla']=='') $key = '<span onclick="salvarTecla(0,\'\',\''.$i['id_inventario'].'\','.$i['peso'].')"><small>'._t('Definir tecla').'</small>';
      else $key = '<span onclick="salvarTecla(1,\''.($key['tecla']=='\''?'*':$key['tecla']).'\',\''.$i['id_inventario'].'\','.$i['peso'].')">'.$key['tecla'].' <small>('._t('alterar tecla').')</small>';
      
      /*echo '<div class="mb-3" style="background-color:#111;border-radius:4px;margin:8px"><div class="col-sm-8"><h4>'
      .$key.' &nbsp; /'
        .$i['ipa'].'/</h4>'.$i['nome'].'</div>
        <div class="col-sm-4">';*/
      //echo '<br><a class="btn btn-sm btn-danger"  onclick="removerSom('.$i['id_som'].')">Remover</a></div></div>';
      
      echo '<div draggable="true" ondragstart="dragstartHandler(event)" class="panelpal form-fieldset" id="'.$i['id_inventario'].'"><div class="form-group">'.$key.' &nbsp; /'
        .$i['ipa'].'/<br>'.$i['nome'].
        '</span><br><a href="#" class="text-secondary" onClick="removerSom(\''.$i['id_som'].'\')">'._t('Remover').'</a></div></div>';


    }
    // e os personalizados
    $is = mysqli_query($GLOBALS['dblink'],"SELECT *, i.id as id_inventario FROM inventarios i 
          LEFT JOIN sonsPersonalizados s ON i.id_som = s.id 
            WHERE s.posx = ".$_GET['x']." AND s.posy = ".$_GET['y']."  AND i.id_tipoSom = 0 
              AND s.id_idioma = ".$_GET['iid']." ORDER BY s.posz;") or die(mysqli_error($GLOBALS['dblink']));
    while ($i = mysqli_fetch_assoc($is)) {
      
      $keys = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(tecla ORDER BY ordem SEPARATOR ' ') as tecla FROM teclas WHERE id_inventario = ".$i['id_inventario'].";") or die(mysqli_error($GLOBALS['dblink']));
      $key = mysqli_fetch_assoc($keys);
      if ($key['tecla']=='') $key = '<span onclick="salvarTecla(0,\'\',\''.$i['id_inventario'].'\','.$i['peso'].')"><small>'._t('Definir tecla').'</small>';
      else $key = '<span onclick="salvarTecla(1,\''.($key['tecla']=='\''?'*':$key['tecla']).'\',\''.$i['id_inventario'].'\','.$i['peso'].')">'.$key['tecla'].' <small>('._t('alterar tecla').')</small>';

      echo '<div draggable="true" ondragstart="dragstartHandler(event)" class="panelpal form-fieldset" id="'.$i['id_inventario'].'"><div class="form-group">'.$key.' &nbsp; /'
        .$i['ipa'].'/<br>'.$i['nome'].'</span>
        <br><a class="text-secondary"  onclick="removerSom(\''.$i['id'].'\',2)">'._t('Remover').'</a></div></div>';
    }

    echo '<div class="mb-3">
            <a class="btn btn-primary"  onclick="carregaTabela()">'._t('Voltar').'</a>
            <a class="btn btn-primary" >'._t('OK').'</a>
      </div><script>createTablerSelect("sel_i")</script>';
    die();
  };

  if ($_GET['action'] == 'carregarEdicaoIPACelula') { // otimizar sql queries

    // editarCelula GET iid x y z t
    //xxxxx ta pegando vogal onde e consoatne!!!
    $tz = mysqli_query($GLOBALS['dblink'],"SELECT dimz, titulo FROM tiposSom WHERE id = ".$_GET['t'].";") or die(mysqli_error($GLOBALS['dblink']));
    $z = mysqli_fetch_assoc($tz);

    $res = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTudo
      WHERE dimensao = ".$z['dimz'].";") or die(mysqli_error($GLOBALS['dblink']));
    while ($r = mysqli_fetch_assoc($res)) {
      echo '<div class="col-sm-12" style="background-color:#111;border-radius:4px;margin-bottom:8px">';

      $is = mysqli_query($GLOBALS['dblink'],"SELECT * FROM sons s
            WHERE s.posx = ".$_GET['x']." AND s.posy = ".$_GET['y']." 
              AND s.posz = ".$r['pos']." AND s.id_tipoSom =".$_GET['t'].";") or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($is)>0){
        $i = mysqli_fetch_assoc($is);
        echo '<div class="col-sm-6"><h4>/'.$i['ipa'].'/</h4>'.$i['nome'].'</div>
          <div class="col-sm-6"><a class="btn btn-xs btn-danger btn-rounded"  onclick="removerIPA('.$_GET['x'].','.$_GET['y'].','.$r['pos'].')">'._t('Remover').'</a>
          <a class="btn btn-xs btn-primary btn-rounded"  onclick="atualizarIPA('.$_GET['x'].','.$_GET['y'].','.$r['pos'].',\''.$i['ipa'].'\',\''.$i['nome'].'\')">'._t('Editar').'</a></div>';
      }else{
        echo '<div class="col-sm-12"><a class="btn btn-xs btn-primary btn-rounded"  onclick="adicionarIPA('.$_GET['x'].','.$_GET['y'].','.$r['pos'].')">'._t('Adicionar').' '.$z['titulo'].' '.$r['nome'].'</a></div>';
      }
      echo '</div>';

    }
      
    die();
  };

  if ($_GET['action'] == 'ajaxEditarSomIPA') {

    if ($_GET['r']>0){
      mysqli_query($GLOBALS['dblink'],"UPDATE sons SET 
        nome = '".$_POST['nome']."', 
        ipa = '".$_POST['ipa']."'
        WHERE id_tipoSom = ".$_GET['t']." AND posx = ".$_GET['x']."
        AND posy = ".$_GET['y']." AND posz = ".$_GET['z'].";") or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['r'];
    }else{

      $is = mysqli_query($GLOBALS['dblink'],"SELECT * FROM sons
          WHERE id_tipoSom = ".$_GET['t']." AND posx = ".$_GET['x']."
          AND posy = ".$_GET['y']." AND posz = ".$_GET['z'].";") or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($is)>0){ 
        
        //xxxxx buscar se tem em algum inventario de alguma lingua antes de deletar!

        mysqli_query($GLOBALS['dblink'],"DELETE FROM sons
          WHERE id_tipoSom = ".$_GET['t']." AND posx = ".$_GET['x']."
          AND posy = ".$_GET['y']." AND posz = ".$_GET['z'].";") or die(mysqli_error($GLOBALS['dblink']));
        die('0'); 
      }

      $id = generateId();
      mysqli_query($GLOBALS['dblink'],"INSERT INTO sons SET 
        nome = '".$_POST['nome']."', 
        id_referente = 0, id = $id,
        ipa = '".$_POST['ipa']."', 
        id_tipoSom = ".$_GET['t'].", posx = ".$_GET['x'].",
        posy = ".$_GET['y'].", posz = ".$_GET['z'].";") or die(mysqli_error($GLOBALS['dblink']));
      echo $id;
    }

    die();
  };
  
  if ($_GET['action'] == 'ajaxEditarTeclaIpa') {

    $ks = explode(" ",$_POST['k']);
    foreach($ks as $k){
        $sqlQuerys = "SELECT s.ipa, p.ipa as ipa2 FROM inventarios i
            LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
            LEFT JOIN sonsPersonalizados p ON ( p.id = i.id_som AND i.id_tipoSom = 0 )
            WHERE BINARY s.ipa = \"".$k."\" OR BINARY p.ipa = \"".$k."\";";
        $existentes = mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        if ( mysqli_num_rows($existentes)>0){
            echo _t("Esta tecla já é outro som!"); die();
        }
    }
    
    if($_GET['ipa']>0){ 
      $sqlQuerys = "DELETE FROM teclas 
        WHERE id_inventario = ".$_GET['ipa'].";";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    }

    $o = 1;
    $p = 1;
    if ($_POST['p'] > 0) $p = $_POST['p'];
    foreach($ks as $k){
        $sqlQuerys = "INSERT INTO teclas SET 
          tecla = \"".$k."\",
          ordem = ".$o++.", id = ".generateId().",
          id_inventario = ".$_GET['ipa'].",
          id_idioma = 0;";
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        
        $sqlQuerys = "UPDATE inventarios SET 
          peso = ".$p.", data_modificado = NOW()
          WHERE id = ".$_GET['ipa'].";";
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    }

    die('1');
  };

  if ($_GET['action'] == 'ajaxEditarAutosubstituicao') { // otimizar sql query

    $k = str_replace("*","'",$_GET['k']);

    // GET g é os glifos em si, tem q buscar seu id em glifos!
    if(isset($_GET['gw'])){
        $g = $_GET['gw']; // id do glifo/vetor em drawChars
        // buscar nas imagens, draws
        $insert = "glifos = '".$g."',";

    }else{
        $g = str_replace("*","'",$_GET['g']);
        $insert = "glifos = \"".$g."\",";
        // buscar nos glifos mesmo, de fontes
        $origem = mb_str_split($_GET['g']);
        for ($i = 0; $i < sizeof($origem) ;$i++) {
            $glifos = mysqli_query($GLOBALS['dblink'],"SELECT id FROM glifos WHERE BINARY glifo = \"".$origem[$i]."\" AND id_escrita = ".$_GET['eid']." LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
            //$g = mysqli_fetch_assoc($glifos);
            if(mysqli_num_rows($glifos)==0){
              die(_t('Glifo não encontrado no alfabeto: %1',[$origem[$i]]));
            }
        }
    }

    //xxxxx TO DO: get K tbm buscar em teclas, afinal é digitando elas que o sistema vai fazer a substituição quando estas existem
    $torigem = mb_str_split($_GET['k']);
    $ipa = '';
    for ($i = 0; $i < sizeof($torigem) ; $i++) {
      $sql = "SELECT s.ipa as ipa1, sp.ipa as ipa2, t.tecla 
                FROM inventarios i
                LEFT JOIN teclas t ON i.id = t.id_inventario 
                LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
                LEFT JOIN sonsPersonalizados sp ON (i.id_som = sp.id AND i.id_tipoSom = 0)
                WHERE ( BINARY t.tecla = \"".$torigem[$i]."\" OR BINARY s.ipa = \"".$torigem[$i]."\" OR BINARY sp.ipa = \"".$torigem[$i]."\")
              AND i.id IN (SELECT id FROM inventarios WHERE id_idioma = (SELECT id_idioma FROM escritas WHERE id = ".$_GET['eid']." LIMIT 1)) LIMIT 1;";
        //echo $sql;
        $glifos = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
        //
        if(mysqli_num_rows($glifos)==0){
          die(_t('Tecla de entrada não encontrada em sons: %1',[$torigem[$i]]));
        }else{
          $r = mysqli_fetch_assoc($glifos);
          $ipa .= $r['ipa2'].$r['ipa1'];
        }
    }

    if($_GET['id']>0){ 
        
        $sqlQuerys = "UPDATE autosubstituicoes SET 
          tecla = \"".$k."\",".$insert."
          ipa = \"".$ipa."\"
          WHERE id = ".$_GET['id']." LIMIT 1;";
          //echo $sqlQuerys;
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        echo $_GET['id'];

    } else {  
        $gid = generateId();
        $sqlQuerys = "INSERT INTO autosubstituicoes SET 
          tecla = \"".$k."\", id = $gid,
          ipa = \"".$ipa."\",
          id_glifo = 0,".$insert."
          id_escrita = ".$_GET['eid'].";";
          //echo $sqlQuerys;
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        echo $gid;
        
    }; 
    die();
  };

  if ($_GET['action'] == 'salvarPalavraFlexionada') { // otimizar sql queries
      $dicionario = (int)$_GET['dic'] ?? 0;
      if (!$_GET['iid'] > 0) die('-1');

      $pronuncia = $_POST['pronuncia']; // str_replace('"',"'",$_POST['pronuncia']);
      $romanizacao = $_POST['romanizacao'];

      $sqlQuerys =  "SELECT e.* FROM escritas e 
        WHERE id_idioma = ".$_GET['iid']." ORDER BY padrao DESC;";
      $escritas = mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      $esc = mysqli_fetch_assoc($escritas);

      if($_POST['pid']>0){ 
        $nativaNovaEidPadrao = $_POST['nativo'][0];
        $resp = verificarTextosComEssaPalavra($_GET['iid'], $_POST['pid'], $_POST['textosRemover'], $_POST['textosAtualizar'], $_POST['textosIgnorar'], $pronuncia, $romanizacao, $nativaNovaEidPadrao);
        if ($resp != 0) {
            echo 'textos|'.$resp;
            die();
        }
        $resp = verificarFrasesComEssaPalavra($_GET['iid'], $_POST['pid'], $_POST['textosRemover'], $_POST['textosAtualizar'], $_POST['textosIgnorar'], $pronuncia, $romanizacao, $nativaNovaEidPadrao);
        if ($resp != 0) {
            echo 'frases|'.$resp;
            die();
        }

        $sqlQuerys = "UPDATE palavras SET 
          romanizacao = '".$romanizacao."',
          irregular = ".$_POST['irregular'].",
          pronuncia = \"".$pronuncia."\",
          data_modificacao = now(),
          significado = '".$_POST['significado']."'
          WHERE id = ".$_POST['pid']." LIMIT 1;";
          //echo $sqlQuerys;
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        $idPalavra = $_POST['pid'];
      } else {  
          $idPalavra = generateId();
          if ( $_GET['ignorar'] != '' ) {
            $ignorar = ' AND p.id NOT IN('.$_GET['ignorar'].')';
          };
          $sql = "SELECT p.*, c.nome as classe FROM palavras p
            LEFT JOIN classes c ON p.id_classe = c.id
            WHERE p.id_idioma = ".$_GET['iid']." AND ( p.pronuncia = \"".$pronuncia."\" ".$orRom.") ".$ignorar.";";
            
          $busca = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

          while ($a = mysqli_fetch_array($busca)){ 
              echo '-'.$a['id'].'|'.$a['pronuncia'].'|'.$a['significado'].' - '.$a['classe'];
              die();
          };

          $res2 = mysqli_query($GLOBALS['dblink'],"SELECT (SELECT COUNT(*) FROM palavras WHERE id_idioma = ".$_GET['iid']." AND id_forma_dicionario = 0) as palavras_base,
            (SELECT valor FROM opcoes_sistema WHERE opcao = 'palavras_base_lang') as limite,
            (SELECT COUNT(*) FROM palavras WHERE id_idioma = ".$_GET['iid'].") as palavras,
            (SELECT valor FROM opcoes_sistema WHERE opcao = 'palavras_lang') as limite2;") or die(mysqli_error($GLOBALS['dblink']));
          $r2 = mysqli_fetch_assoc($res2);
          if($r2['palavras_base'] > $r2['limite'] || $r2['palavras'] > $r2['limite2']){
            echo 'limit';
            die();
          };

          
          if ($_GET['paradigma']=='1') { // paradigma 1: palavras únicas
              $sqlQuerys = "INSERT INTO palavras SET id = $idPalavra,
                romanizacao = '".$romanizacao."',
                irregular = ".$_POST['irregular'].",
                id_classe = ".$_GET['classe'].",
                pronuncia = \"".$pronuncia."\",
                significado = '".$_POST['significado']."',
                id_forma_dicionario = 0,
                detalhes = '',
                id_uso = 0,
                publico = 1,
                data_criacao = now(),
                data_modificacao = now(),
                id_usuario = ".$_SESSION['KondisonairUzatorIDX'].",
                id_idioma = ".$_GET['iid'].";";
          }else{            // paradigma 0: palavra flexiona linkada à forma de dicionario
              $sqlQuerys = "SELECT * FROM palavras WHERE id = $dicionario LIMIT 1;";
              $re = mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
              $r = mysqli_fetch_assoc($re);
              $sqlQuerys = "INSERT INTO palavras SET id = $idPalavra,
                romanizacao = '".$romanizacao."',
                irregular = ".$_POST['irregular'].",
                id_classe = ".$r['id_classe'].",
                pronuncia = \"".$pronuncia."\",
                significado = '".$_POST['significado']."',
                id_forma_dicionario = $dicionario,
                detalhes = '".$r['detalhes']."',
                id_uso = 0,
                publico = 1,
                data_criacao = now(),
                data_modificacao = now(),
                id_usuario = ".$_SESSION['KondisonairUzatorIDX'].",
                id_idioma = ".$_GET['iid'].";";
          }
            
          //echo $sqlQuerys;
          mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));

          $dependencia = 0;
          $ccs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias WHERE id = ".$_POST['c1']) or die(mysqli_error($GLOBALS['dblink']));
          $cc = mysqli_fetch_assoc($ccs);
          if ($cc['depende']>0) $dependencia = $cc['depende'];
          $sqlQuerys = "INSERT INTO itens_palavras SET 
            id_palavra = ".$idPalavra.", id = ".generateId().",
            id_concordancia = ".$_POST['c1'].",
            id_item = ".$_POST['i1'].",
            usar = 1;";
          mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));              

          if($_POST['i2']>0){
              $ccs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias WHERE id = ".$_POST['c2']) or die(mysqli_error($GLOBALS['dblink']));
              $cc = mysqli_fetch_assoc($ccs);
              if ($cc['depende']>0) $dependencia = $cc['depende'];
              $sqlQuerys = "INSERT INTO itens_palavras SET 
                id_palavra = ".$idPalavra.", id = ".generateId().",
                id_concordancia = ".$_POST['c2'].",
                id_item = ".$_POST['i2'].",
                usar = 1;";
              mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
          }

          foreach($_POST['extras'] as $val){
            
              $ccs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias WHERE id = ".$val['did']) or die(mysqli_error($GLOBALS['dblink']));
              $cc = mysqli_fetch_assoc($ccs);
              if ($cc['depende']>0) $dependencia = $cc['depende'];
              $sqlQuerys = "INSERT INTO itens_palavras SET 
                id_palavra = ".$idPalavra.", id = ".generateId().",
                id_concordancia = ".$val['did'].",
                id_item = ".$val['val'].",
                usar = 1;";
              mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
              
          }
          
          $il = 0; // limite de subtabelas
          while($dependencia > 0){ 
              $ccs = mysqli_query($GLOBALS['dblink'],"SELECT *, c.nome as titulo FROM concordancias c 
                  LEFT JOIN itensConcordancias ic ON ic.id_concordancia = c.id 
                  WHERE ic.id = ".$dependencia.";") or die(mysqli_error($GLOBALS['dblink']));
              $cc = mysqli_fetch_assoc($ccs);

              $sqlQuerys = "INSERT INTO itens_palavras SET 
                id_palavra = ".$idPalavra.", id = ".generateId().",
                id_concordancia = ".$cc['id_concordancia'].",
                id_item = ".$dependencia.",
                usar = 1;";
              mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));

              if ($cc['depende']>0) $dependencia = $cc['depende'];
              else $dependencia = 0;
              
              $il++; if($il>10) $dependencia = 0;
          }  
      };

      mysqli_data_seek($escritas,0);
      if (isset($_POST['nativo'])) for ($i=0; $i < count($_POST['nativo']); $i++) {
          $esc = mysqli_fetch_assoc($escritas);
          if ($_POST['nativo'][$i]==''){
            $sqlQuerys = "DELETE FROM palavrasNativas WHERE id_palavra = ".$idPalavra." AND id_escrita = ".$esc['id'].";";
          }else{            
            $pals = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavrasNativas WHERE id_palavra = ".$idPalavra." AND id_escrita = ".$esc['id'].";") or die(mysqli_error($GLOBALS['dblink']));
            if (mysqli_num_rows($pals)==0){
                $sqlQuerys = "INSERT INTO palavrasNativas SET 
                    id_palavra = ".$idPalavra.", id = ".generateId().",
                    id_escrita = ".$esc['id'].",
                    palavra = \"".$_POST['nativo'][$i]."\";";
            }else{
                $sqlQuerys = "UPDATE palavrasNativas SET  id = ".generateId().",
                    palavra = \"".$_POST['nativo'][$i]."\" 
                    WHERE id_palavra = ".$idPalavra." AND
                    id_escrita = ".$esc['id'].";";
            }
          }
          mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      };

      echo $idPalavra;
      die();
  };

  if ($_GET['action'] == 'novaPalavraFlexionada') { // otimizar sql queries
 
      $sqlQuerys = "SELECT * FROM palavras WHERE id = ".$_GET['dic']." LIMIT 1;";
      $re = mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      $r = mysqli_fetch_assoc($re);
      
      $idPalavra = generateId();
      $sqlQuerys = "INSERT INTO palavras SET id = $idPalavra,
        romanizacao = '*".$r['romanizacao']."',
        irregular = 0,
        id_classe = ".$r['id_classe'].",
        pronuncia = \"*".$r['pronuncia']."\",
        significado = '".$r['significado']."',
        id_forma_dicionario = ".$_GET['dic'].",
        id_idioma = ".$_GET['iid'].";";
        
      //echo $sqlQuerys;
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));

      $sqlQuerys = "INSERT INTO itens_palavras SET 
        id_palavra = ".$idPalavra.", id = ".generateId().",
        id_concordancia = ".$_POST['c1'].",
        id_item = ".$_POST['i1'].",
        usar = 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      $sqlQuerys = "INSERT INTO itens_palavras SET 
        id_palavra = ".$idPalavra.", id = ".generateId().",
        id_concordancia = ".$_POST['c2'].",
        id_item = ".$_POST['i2'].",
        usar = 1;";
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));

      echo $idPalavra;

      die();
  };

  if ($_GET['action'] == 'carregarEdicaoAlfabeto') {

    echo '<label class="control-label">Edição letras</label>';
    die();

  };

  if ($_GET['action'] == 'toggleSonsAdicionaveis') {
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM sons_classes
          WHERE id_som = ".$_GET['id']." AND tipo = ".$_GET['t']." AND id_classeSom = ".$_GET['c'].";") or die(mysqli_error($GLOBALS['dblink']));
    if(mysqli_num_rows($result)>0){
      //delete
      mysqli_query($GLOBALS['dblink'],"DELETE FROM sons_classes
          WHERE id_som = ".$_GET['id']." AND tipo = ".$_GET['t']." AND id_classeSom = ".$_GET['c'].";") or die(mysqli_error($GLOBALS['dblink']));
    }else{
      mysqli_query($GLOBALS['dblink'],"INSERT INTO sons_classes SET  id = ".generateId().",
          id_som = ".$_GET['id']." , tipo = ".$_GET['t']." , id_classeSom = ".$_GET['c'].";") or die(mysqli_error($GLOBALS['dblink']));
    }
    die();
  };

  if ($_GET['action'] == 'salvarFlexao') {
      if($_GET['id']>0){ 
        $sqlQuerys = "UPDATE flexoes SET 
          nome = '".$_POST['nome']."',
          motor = '".$_POST['motor']."',
          regra_pronuncia = \"".$_POST['regra_pronuncia']."\",
          regra_romanizacao = \"".$_POST['regra_romanizacao']."\"
          WHERE id = ".$_GET['id']." LIMIT 1;";
          //echo $sqlQuerys;
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
        $idPalavra = $_GET['id'];
      }
      echo $idPalavra;
      die();
  };

  if ($_GET['action']=='ajaxApagarBanco') {

    mysqli_query($GLOBALS['dblink'],
      "DELETE FROM listas_referentes WHERE id_lista = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    mysqli_query($GLOBALS['dblink'],"DELETE FROM wordbanks WHERE id = ".$_GET['id']." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink']));
    echo 'ok';
    die();
  }

  if ($_GET['action'] == 'ajaxApagarPalavras') {// otimizar sql queries

    //xxxxx verificar se em uso nos textos, mais adiante nas frases tbm

    //ver se é forma dicionario
    $res = mysqli_query($GLOBALS['dblink'],"SELECT p.*, 
          (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = p.id AND pn.id_escrita = (
                SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1 LIMIT 1
                ) LIMIT 1 )  as nativa FROM palavras p
          WHERE p.id = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));
    $pal = mysqli_fetch_assoc($res);

    if($pal['id']>0){ // palavra existe
      $origs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras_origens WHERE id_origem = ".$pal['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($origs)>0) die(_t('Esta palavra é usada como origem de outra palavra, logo não pode ser deletada.'));

      //xxxxx conferir se tá em algum texto
      // pegar escrita principal e essa palavra
      // se tem, ver nos textos

      $bte = "SELECT * FROM studason_tests WHERE texto LIKE \"%".$pal['nativa']."%\" AND id_idioma = ".$pal['id_idioma'].";";
      $origs = mysqli_query($GLOBALS['dblink'],$bte) or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($origs)>0) die(_t('Esta palavra está em algum texto, logo não pode ser deletada.'));


      if($pal['id_forma_dicionario']>0){ // é palavra dependente, flexão apenas

        mysqli_query($GLOBALS['dblink'],"DELETE FROM palavras WHERE id = ".$pal['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM itens_palavras WHERE id_palavra = ".$pal['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM palavrasNativas WHERE id_palavra = ".$pal['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM palavras_referentes WHERE id_palavra = ".$pal['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM palavras_origens WHERE id_palavra = ".$pal['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      
      }else{ // é palavra base

        $origs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras_origens 
            WHERE id_origem IN (SELECT id FROM palavras WHERE id_forma_dicionario = ".$pal['id'].");") or die(mysqli_error($GLOBALS['dblink']));
        if (mysqli_num_rows($origs)>0) die('A forma de dicionário desta palavra é usada como origem de outra palavra, logo não pode ser deletada.');

        // apagar todas flexoes nas 5 tabelas = acima
        mysqli_query($GLOBALS['dblink'],"DELETE FROM itens_palavras WHERE id_palavra IN (SELECT id FROM palavras WHERE id_forma_dicionario = ".$pal['id'].");") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM palavrasNativas WHERE id_palavra IN (SELECT id FROM palavras WHERE id_forma_dicionario = ".$pal['id'].");") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM palavras_referentes WHERE id_palavra IN (SELECT id FROM palavras WHERE id_forma_dicionario = ".$pal['id'].");") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM palavras_origens WHERE id_palavra IN (SELECT id FROM palavras WHERE id_forma_dicionario = ".$pal['id'].");") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM palavras WHERE id_forma_dicionario = ".$pal['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        
        mysqli_query($GLOBALS['dblink'],"DELETE FROM palavras WHERE id = ".$pal['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      }

    }

    die('1');
  };
  
  if ($_GET['action'] == 'ajaxApagarGenero') {

    // checar primeiro
    $pals = mysqli_query($GLOBALS['dblink'],"SELECT * FROM classesGeneros WHERE id_genero = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($pals)>0) die('Há '.mysqli_num_rows($pals).' palavras deste gênero! Não apagado.');

    mysqli_query($GLOBALS['dblink'],"DELETE FROM generos WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));

    die('1');
  };

  if ($_GET['action'] == 'ajaxApagarConcordancia') {// otimizar sql queries

    $usando = 0;
    function apagarConcordancias($id){
        //buscar dependentes
        $usando = 0;
        $deps = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias WHERE depende = ".$id.";") or die(mysqli_error($GLOBALS['dblink']));
        while($dep = mysqli_fetch_assoc($deps)){
            $usando = apagarConcordancias($dep['id']);
            if ($usando > 0) break;
        };

        if ($usando > 0) return $usando;
        else{
            //xxxxx ver se tá em uso!
            //contar quantas palavras estão usando e retornar valor
            $pals = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itens_palavras WHERE id_concordancia = ".$id." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
            $npals = mysqli_num_rows($pals);
            if ($npals>0) return $npals;

            //dps apagar esta
            mysqli_query($GLOBALS['dblink'],"DELETE FROM itens_palavras WHERE id_concordancia = ".$id.";") or die(mysqli_error($GLOBALS['dblink']));
            mysqli_query($GLOBALS['dblink'],"DELETE FROM itensConcordancias WHERE id_concordancia = ".$id.";") or die(mysqli_error($GLOBALS['dblink']));
            mysqli_query($GLOBALS['dblink'],"DELETE FROM concordancias WHERE id = ".$id.";") or die(mysqli_error($GLOBALS['dblink']));
            return 0;
        }
    }

    //ver se é forma dicionario
    $res = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias
          WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    $conc = mysqli_fetch_assoc($res);

    if($conc['id']>0) $usando = apagarConcordancias($conc['id']);

    if ($usando == 0) echo 'ok';
    else echo $usando;
    die();
  };
  
  if ($_GET['action'] == 'ajaxNovaEscrita') {

    
    $res2 = mysqli_query($GLOBALS['dblink'],"SELECT (SELECT COUNT(*) FROM escritas WHERE id_idioma = ".$_GET['iid'].") as escritas,
      (SELECT valor FROM opcoes_sistema WHERE opcao = 'limite_escritas_l') as limite;") or die(mysqli_error($GLOBALS['dblink']));
    $r2 = mysqli_fetch_assoc($res2);

    if($r2['escritas'] > $r2['limite']){
      echo 'limit';
      die();
    };

    $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
    $r0 = mysqli_fetch_assoc($res0);

    $existe = mysqli_query($GLOBALS['dblink'],"SELECT * FROM escritas WHERE id_idioma = ".$_GET['iid']." AND padrao = 1;") or die(mysqli_error($GLOBALS['dblink']));
    $padrao = 0;
    if(mysqli_num_rows($existe)==0) $padrao = 1;
    
      $idEscrita = generateId();
      $sqlQuerys = "INSERT INTO escritas SET id = $idEscrita,
        nome = '".$_GET['n']."',
        id_tipo = 0,
        publico = 0,
        descricao = '',
        id_nativo = 0,
        iniciadores = '',
        tamanho = 'unset',
        id_idioma = ".$_GET['iid'].",
        padrao = ".$padrao.",
        id_fonte = '".$_GET['f']."';";
      //echo $sqlQuerys;
      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));

    echo $idEscrita;
    if ( $r0['publico']==1 )
      logAcao(0,'skreveson',$idEscrita);
    die();
  };
  
  if ($_GET['action'] == 'ajaxRenomearDimensao') {
    $nome = $_POST['nome'];
    //if (!strpos($nome, ' ') === false) die("word");
    mysqli_query($GLOBALS['dblink'],"UPDATE ipaTitulos SET nome = '".$nome."' WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
  };

  if ($_GET['action'] == 'ajaxCriarSomPersonalizado2') {

    // POST ipa nome < GET ids
    $tmp = mysqli_query($GLOBALS['dblink'],"SELECT * FROM sons WHERE id = ".substr($_GET['ids'],1).";") or die(mysqli_error($GLOBALS['dblink']));
    $t = mysqli_fetch_assoc($tmp);
    
    $id = generateId();
    mysqli_query($GLOBALS['dblink'],"INSERT INTO sonsPersonalizados SET id = $id,
          nome = '".$t['nome']."',
          ipa = '".$t['ipa']."',
          id_referente = 0,
          posx = ".$_GET['x'].",
          posy = ".$_GET['y'].",
          posz = ".$_GET['z'].",
          id_tipoSom = ".$_GET['t'].",
          id_idioma = '".$_GET['iid']."';") or die(mysqli_error($GLOBALS['dblink']));
    mysqli_query($GLOBALS['dblink'],"INSERT INTO inventarios SET 
          id_som = ".$id.", id = ".generateId().",
          id_tipoSom = 0,
          id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
  };

  if ($_GET['action'] == 'ajaxCriarSomPersonalizado') {
    //checar se existe esse ipa já em uso
    /*$tmp = mysqli_query($GLOBALS['dblink'],"SELECT * FROM sons WHERE ipa = '".$_POST['ipa']."';") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($tmp)>0) die('existente');
    $tmp = mysqli_query($GLOBALS['dblink'],"SELECT * FROM sonsPersonalizados WHERE ipa = '".$_POST['ipa']."' AND id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($tmp)>0) die('existente');*/
    $id = generateId();
    mysqli_query($GLOBALS['dblink'],"INSERT INTO sonsPersonalizados SET id = $id,
          nome = '".$_POST['nome']."',
          ipa = '".$_POST['ipa']."',
          id_referente = 0,
          posx = ".$_GET['x'].",
          posy = ".$_GET['y'].",
          posz = ".$_GET['z'].",
          id_tipoSom = ".$_GET['t'].",
          id_idioma = '".$_GET['iid']."';") or die(mysqli_error($GLOBALS['dblink']));
    mysqli_query($GLOBALS['dblink'],"INSERT INTO inventarios SET 
          id_som = ".$id.", id = ".generateId().",
          id_tipoSom = 0,
          id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
    echo $id;
    die();
  };

  if ($_GET['action'] == 'carregarTabelaFlexoes') { // otimizar sql queries

    if(isset($_POST['cex'])){
      $cex = $_POST['cex'];
    };

    if ($_GET['pid']>0) carregarPalavraFlexoes($_GET['pid'],$_GET['d'],$_GET['k'],$_GET['iid'],$_GET['lin'],$_GET['col'],$cex); 
    else {
      $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM generos 
          WHERE id_classe = ".$_GET['k']." AND id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
      if(mysqli_num_rows($result)>0){
          while($r = mysqli_fetch_assoc($result)){
            echo '<div class="card-header card-header-light"><h3 class="card-title">'.$r['nome'].'</h3></div>';
            carregarTabelaFlexoes($_GET['d'],$_GET['k'],$_GET['iid'],$_GET['lin'],$_GET['col'],$r['id'],$cex); 
          }
      }else carregarTabelaFlexoes($_GET['d'],$_GET['k'],$_GET['iid'],$_GET['lin'],$_GET['col'],0,$cex); 
    }
    die();
  };

  if ($_GET['action'] == 'listWordsSpecial') { // otimizar sql queries // add funcao getSpanPalavraNativa em lugar de custom-font
    
    $romanizacao = 0;
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM idiomas WHERE id = ".$_GET['id']." AND romanizacao = 1;") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($result)>0) $romanizacao = 1;

    $en = mysqli_query($GLOBALS['dblink'],"SELECT e.*, f.arquivo as fonte FROM escritas e 
                      LEFT JOIN fontes f ON f.id = e.id_fonte
                      WHERE e.id_idioma = ".$_GET['id']." AND e.padrao = 1;") or die(mysqli_error($GLOBALS['dblink']));
    if(mysqli_num_rows($en)>0){
      $en = mysqli_fetch_assoc($en);
      $escrita = $en['id'];
      $fonte = $en['fonte'];
      $id_fonte = $en['id_fonte'];
      $tamanho = $en['tamanho'];
    }else { $escrita = 0; $fonte = 'notosans';$id_fonte = 0; $tamanho = '';}

    if ($escrita > 0) {
        $filtroEscrita = 'LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id AND epn.id_escrita = '.$escrita;
        $sqlNativo = ", '©', epn.palavra";
        //$escrita = 1;
    }
    
    $filtro = $_GET['tipo'];
    $indice = 'a';

    if ($filtro == 1){ //xxxxx palavras com mesma pronuncia e escrita nativa? pals iguais, significados diferentes

      $query = "SELECT p.*, c.nome AS classe, g.gloss AS cgl, n.palavra,
        (SELECT GROUP_CONCAT(pronuncia, ',', significado, ',', romanizacao, ',', palavra SEPARATOR ',') 
          FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
          WHERE ep.id_forma_dicionario = p.id) as extras_palavras FROM palavras p 
        LEFT JOIN classes c ON p.id_classe = c.id 
        LEFT JOIN glosses g ON c.id_gloss = g.id 
        LEFT JOIN palavrasNativas n ON ( n.id_palavra = p.id AND n.id_escrita = ".$escrita.")
        WHERE p.id_idioma = ".$_GET['id']."  ;"; // AND p.id_forma_dicionario = 0

    }else if($filtro == 2){ //homófonos

      $query = "SELECT p.*,
        (SELECT COUNT(*) 
          FROM palavras ep ".$filtroEscrita."
          WHERE ep.pronuncia = p.pronuncia AND ep.id_idioma = p.id_idioma) as num_extras_palavras,
        (SELECT GROUP_CONCAT(ep.pronuncia, '©', ep.id, '©', ep.significado".$sqlNativo." SEPARATOR '|') 
          FROM palavras ep ".$filtroEscrita."
          WHERE ep.pronuncia = p.pronuncia AND ep.id_idioma = p.id_idioma) as extras_palavras 
        FROM palavras p 
        WHERE p.id_idioma = ".$_GET['id']." 
        GROUP BY p.pronuncia ORDER BY num_extras_palavras DESC;";

        $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 

        $numAnterior = 1000000;
        while($r = mysqli_fetch_assoc($result)){
          if ($numAnterior != $r['num_extras_palavras']) {
            echo '<div class="list-group-header sticky-top">'.$r['num_extras_palavras'].' '._t('Palavras com mesmo som').'</div>';
            $numAnterior = $r['num_extras_palavras'];
          };
          $indexdata = 'data-ni="'.substr($r['palavra'],0,1).'" data-nf="'.substr($r['palavra'],strlen($r['palavra'])-1,strlen($r['palavra'])-1).
            '" data-ri="'.substr($r['romanizacao'],0,1).'" data-rf="'.substr($r['romanizacao'],strlen($r['romanizacao'])-1,strlen($r['romanizacao'])-1).
            '" data-nc="'.strlen($r['palavra']).'" ';
          

          $palavras = explode('|',$r['extras_palavras']);
          $plist = ''; $nump = 0;
          foreach($palavras as $pal){
            $nump++;
            $ps = explode('©',$pal);
            $plist .= $ps[3]=='' ?
                '<a href="?page=editword&iid='.$_GET['id'].'&pid='.$ps[1].'" data-bs-toggle="tooltip" title="'.$ps[0].' - '.$ps[2].'"><span>'.$ps[0].'</span> </a>&nbsp; ':
                '<a href="?page=editword&iid='.$_GET['id'].'&pid='.$ps[1].'" data-bs-toggle="tooltip"  title="'.$ps[0].' - '.$ps[2].'">'.getSpanPalavraNativa($ps[3],$escrita,$id_fonte,$tamanho).' </a>&nbsp; ';
          }

          
          echo '<div data-search="'.$searchField.'" class="list-group-item divWord" '.$indexdata.'><div class="row">
              <div class="col-auto">'.$plist.'&nbsp;</div>
              <div class="col-auto text-secondary">'.$r['pronuncia'].'</div>
            </div></div>'; // onclick="abrirPalavra('.$r['id'].')" // <div class="text-secondary text-truncate mt-n1">'.$r['pronuncia'].'</div>

        };

    }else if($filtro == 3){ // homógrafos (mesma escrita)
        if($escrita>0)
              $query = "SELECT p.*, n.palavra,
                (SELECT COUNT(*) 
                  FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id AND epn.id_escrita = ".$escrita."
                  WHERE BINARY epn.palavra = n.palavra) as num_extras_palavras,
                (SELECT GROUP_CONCAT(ep.pronuncia, '©', ep.significado, '©', romanizacao, '©', ep.id".$sqlNativo." SEPARATOR '|') 
                  FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id AND epn.id_escrita = ".$escrita."
                  WHERE BINARY epn.palavra = n.palavra) as extras_palavras FROM palavras p 
                LEFT JOIN palavrasNativas n ON ( n.id_palavra = p.id AND n.id_escrita = ".$escrita.")
                WHERE p.id_idioma = ".$_GET['id']." 
                GROUP BY n.palavra  ORDER BY num_extras_palavras DESC;"; // AND p.id_forma_dicionario = 0
        else
              $query = "SELECT p.*, p.romanizacao as palavra,
                (SELECT COUNT(*) 
                  FROM palavras ep 
                  WHERE BINARY ep.romanizacao = p.romanizacao) as num_extras_palavras,
                (SELECT GROUP_CONCAT(ep.pronuncia, '©', ep.significado, '©', romanizacao, '©', ep.id".$sqlNativo." SEPARATOR '|') 
                  FROM palavras ep 
                  WHERE BINARY ep.romanizacao = p.romanizacao) as extras_palavras 
                FROM palavras p 
                WHERE p.id_idioma = ".$_GET['id']." 
                GROUP BY p.romanizacao ORDER BY num_extras_palavras DESC;";
                
        $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 

        $numAnterior = 1000000;
        while($r = mysqli_fetch_assoc($result)){
          
          if($escrita>0) $nativo = getSpanPalavraNativa($r['palavra'],$escrita,$id_fonte,$tamanho);
          else $nativo = $r['palavra'];
          
          if ($numAnterior != $r['num_extras_palavras']) {
            echo '<div class="list-group-header sticky-top">'.$r['num_extras_palavras'].' '._t('Palavras com mesma escrita').'</div>';
            $numAnterior = $r['num_extras_palavras'];
          };
          $indexdata = 'data-ni="'.substr($r['palavra'],0,1).'" data-nf="'.substr($r['palavra'],strlen($r['palavra'])-1,strlen($r['palavra'])-1).
            '" data-ri="'.substr($r['romanizacao'],0,1).'" data-rf="'.substr($r['romanizacao'],strlen($r['romanizacao'])-1,strlen($r['romanizacao'])-1).
            '" data-nc="'.strlen($r['palavra']).'" ';
          $palavras = explode('|',$r['extras_palavras']);
          $plist = ''; $nump = 0;
          foreach($palavras as $pal){
            $nump++;
            $ps = explode('©',$pal);
            $plist .= '<a href="?page=editword&iid='.$_GET['id'].'&pid='.$ps[3].'"><span data-bs-toggle="tooltip" title="'.$ps[0].' - '.$ps[1].'">'.$ps[0].'</span> </a>&nbsp; ';
          }
          echo '<div data-search="'.$searchField.'" class="list-group-item divWord" '.$indexdata.'><div class="row">
              <div class="col-auto">'.$nativo.'</div>
              <div class="col-auto">'.$plist.'</div>
            </div></div>';
        };

    }else if($filtro == 4){ // pares minimos 
        if($escrita > 0)
            $query = "SELECT p.*, n.palavra
              FROM palavras p 
              LEFT JOIN palavrasNativas n ON ( n.id_palavra = p.id AND n.id_escrita = ".$escrita.")
              WHERE p.id_idioma = ".$_GET['id']." 
              GROUP BY p.pronuncia ;";
        else
            $query = "SELECT p.*, p.romanizacao as palavra
              FROM palavras p 
              WHERE p.id_idioma = ".$_GET['id']." 
              GROUP BY p.pronuncia ;";

        
        $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
        $lista = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
        $pals = array(); $i = 0;
        while($r = mysqli_fetch_assoc($result)){
          $indice = // '6-5-0-0-12'; //nativo inicial/final, romaniz inic/fin, num.chars,
            'ni:'.substr($r['palavra'],0,1).'|nf:'.substr($r['palavra'],strlen($r['palavra'])-1,strlen($r['palavra'])-1).
            '|ri:'.substr($r['romanizacao'],0,1).'|rf:'.substr($r['romanizacao'],strlen($r['romanizacao'])-1,strlen($r['romanizacao'])-1).
            '|nc:'.strlen($r['palavra']).'|';
            

          $nativo = getSpanPalavraNativa($r['palavra'],$escrita,$id_fonte,$tamanho); //'<span >'.($r['palavra']==''?'???':$r['palavra']).'</span>';
          $plist = '<a href="?page=editword&iid='.$_GET['id'].'&pid='.$r['id'].'" data-bs-toggle="tooltip" title="'.$r['pronuncia'].' - '.$r['significado'].'">'.$nativo.'</a>&nbsp; '; 
          $nump = 0;
          // usar mesmo array que tá rodando aqui, uma cópia, buscando pronuncias com 1 caractere de diferença
          mysqli_data_seek($lista, 0);
          while($ps = mysqli_fetch_assoc($lista)){
            
            $dif = 0;
            $length = strlen($r['pronuncia']);
            $adjustment = abs($length - strlen($ps['pronuncia']));
            $matching = similar_text($r['pronuncia'], $ps['pronuncia']);
            $dif = $length - $matching + $adjustment;

            if( $dif==1 ){
                $nump++;
                $nativo = getSpanPalavraNativa( $ps['palavra'] ,$escrita,$id_fonte,$tamanho);
                $plist .= '<a href="?page=editword&iid='.$_GET['id'].'&pid='.$ps['id'].'" data-bs-toggle="tooltip" title="'.$ps['pronuncia'].' - '.$ps['significado'].'">'.$nativo.'</a>&nbsp; ';
            }
            //$plist .= '== '.$r['pronuncia'].'='.$ps['pronuncia'].'=='.$dif.'<br>';
          }

          $pals[$i]['search'] = $searchField;
          $pals[$i]['index'] = $indexdata;
          $pals[$i]['plist'] = $plist;
          $pals[$i]['pronuncia'] = $r['pronuncia'];
          $pals[$i]['num'] = $nump;
          $i++;

        };

        // order $pals;
        usort($pals, function($a, $b) {
            return $b['num'] <=> $a['num'];
        });
        $numAnterior = -1;
        for($j = 0; $j < sizeof($pals); $j++){

            if ($numAnterior != $pals[$j]['num']) {
              echo '<div class="list-group-header sticky-top">'.$pals[$j]['num'].' '._t('Pares mínimos').'</div>';
              $numAnterior = $pals[$j]['num'];
            };
            
              echo '<div data-search="'.$pals[$j]['search'].'" class="list-group-item divWord" '.$pals[$j]['index'].'><div class="row">
              <div class="col-auto">
                  <div class="text-secondary">'.$pals[$j]['plist'].'</div>
              </div>
              <div class="col-auto text-secondary">'.$pals[$i]['pronuncia'].'</div>
            </div></div>';
        };
      
    }
    echo '<script>$(\'[data-bs-toggle="tooltip"]\').tooltip();</script>';
    die();
  };

  if ($_GET['action'] == 'ajaxMoverOpcao') {
    // GET id
    // GET dir : u/d (up down)

    if ($_GET['dir']=='d') {
      $sig = '+'; $sig2 = '-';
    }else { $sig = '-'; $sig2 = '+'; }

    // itensConcordancias

    
    // ordenação inicial:
    $qs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias WHERE id_concordancia = (SELECT id_concordancia FROM itensConcordancias WHERE id = ".$_GET['id']." LIMIT 1) ORDER BY ordem;") or die('err'.mysqli_error($GLOBALS['dblink']));
    $o = 1;
    while ($q = mysqli_fetch_assoc($qs)){
      mysqli_query($GLOBALS['dblink'],'UPDATE itensConcordancias SET ordem = '.$o.' WHERE id = '.$q['id'].';') or die(mysqli_error($GLOBALS['dblink']));
        $o++;
    }

    // get id da ordem +1 ou -1
    $sqlQuerys = 'SELECT id, ordem FROM itensConcordancias WHERE id_concordancia = (SELECT id_concordancia FROM itensConcordancias WHERE id = '.$_GET['id'].' LIMIT 1) AND ordem = (SELECT ordem FROM itensConcordancias WHERE id = "'.$_GET['id'].'" LIMIT 1) '.$sig.' 1 ;';
    $alvo = mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($alvo)==1){
      $idAlvo = mysqli_fetch_assoc($alvo);

      // if exists, update esse e o id do get
      mysqli_query($GLOBALS['dblink'],"UPDATE itensConcordancias SET ordem = ordem ".$sig." 1 WHERE id = ".$_GET['id']) or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"UPDATE itensConcordancias SET ordem = ordem ".$sig2." 1 WHERE id = ".$idAlvo['id']) or die(mysqli_error($GLOBALS['dblink']));
    }
    
    die('1');
  };

  if ($_GET['action'] == 'getOptionsOrigensByIds') {
    // Recebe os IDs como uma string separada por vírgulas
    $ids = isset($_GET['ids']) ? trim($_GET['ids']) : '';
    
    // Proteção contra injeção de SQL
    $ids = mysqli_real_escape_string($GLOBALS['dblink'], $ids);
    
    // Valida que os IDs são numéricos e separados por vírgulas
    if (empty($ids) || !preg_match('/^[0-9,]+$/', $ids)) {
        header('Content-Type: application/json');
        echo json_encode([]);
        die();
    }
    
    // SQL para buscar opções específicas por IDs
    $sql = "SELECT p.id, p.significado, p.romanizacao, i.sigla, (
                SELECT palavra FROM palavrasNativas pn
                WHERE p.id = pn.id_palavra
                AND pn.id_escrita = e.id LIMIT 1
            ) as nativo, e.id_fonte, e.tamanho, e.id as eid 
            FROM palavras p 
            LEFT JOIN idiomas i ON i.id = p.id_idioma
            LEFT JOIN escritas e ON e.padrao = 1 AND e.id_idioma = i.id
            WHERE p.id IN ($ids)";
    
    $result = mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    
    // Array para armazenar os resultados
    $options = [];
    
    while ($r = mysqli_fetch_assoc($result)) {
        $options[] = [
            'id' => $r['id'],
            'text' => ($r['romanizacao'] != '' ? $r['romanizacao'] : $r['pronuncia']) . ' (' . $r['sigla'] . ') - ' . $r['significado'],
            'n' => $r['nativo'],
            'eid' => $r['eid'],
            'f' => $r['id_fonte'],
            't' => $r['tamanho']
        ];
    }
    
    // Define o cabeçalho como JSON
    header('Content-Type: application/json');
    
    // Retorna os resultados no formato JSON
    echo json_encode($options);
    die();
  }
  
  if ($_GET['action'] == 'getOptionsOrigens') {
    // Recebe o parâmetro de busca
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    // Proteção contra injeção de SQL
    $query = mysqli_real_escape_string($GLOBALS['dblink'], $query);
    
    // SQL com filtro dinâmico
    $sql = "SELECT p.id, p.id as pid, p.significado, p.romanizacao, i.sigla, (
                SELECT palavra FROM palavrasNativas pn
                WHERE p.id = pn.id_palavra
                AND pn.id_escrita = e.id LIMIT 1
            ) as nativo, e.id_fonte, e.tamanho, e.id as eid 
            FROM palavras p 
            LEFT JOIN idiomas i ON i.id = p.id_idioma
            LEFT JOIN escritas e ON e.padrao = 1 AND e.id_idioma = i.id";
    
    // Adiciona filtro se houver query
    if ($query !== '') {
        $sql .= " WHERE p.romanizacao LIKE '%$query%' 
                 OR p.pronuncia LIKE '%$query%' 
                 OR p.significado LIKE '%$query%'";
    }
    
    // Limita o número de resultados para evitar sobrecarga
    $sql .= " LIMIT 50;";
    
    $result = mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    
    // Array para armazenar os resultados
    $options = [];
    
    while ($r = mysqli_fetch_assoc($result)) {
        $options[] = [
            'id' => $r['id'],
            'text' => ($r['romanizacao'] != '' ? $r['romanizacao'] : $r['pronuncia']) . ' (' . $r['sigla'] . ') - ' . $r['significado'],
            'n' => $r['nativo'],
            'eid' => $r['eid'],
            'f' => $r['id_fonte'],
            't' => $r['tamanho'],
            'romanizacao' => $r['romanizacao'],
            'pronuncia' => $r['pronuncia'],
            'escrita' => $r['eid'],
            'nativo' => $r['nativo']
        ];
    }
    
    // Define o cabeçalho como JSON
    header('Content-Type: application/json');
    
    // Retorna os resultados no formato JSON
    echo json_encode($options);
    die();
  }

  if ($_GET['action'] === 'getOrigemById') {
      $id = (int)$_GET['id'];
      $sql = "SELECT po.*, p.romanizacao, p.pronuncia,
                    (SELECT e.id FROM escritas e WHERE e.id_idioma = p.id AND e.padrao = 1 LIMIT 1) as escrita,
                    (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = po.id_origem AND pn.id_escrita = (
                        SELECT e.id FROM escritas e WHERE e.id_idioma = p.id AND e.padrao = 1 LIMIT 1
                    ) LIMIT 1) as nativo 
              FROM palavras_origens po 
              LEFT JOIN palavras p ON p.id = po.id_origem 
              WHERE p.id = $id LIMIT 1";
      $result = mysqli_query($GLOBALS['dblink'], $sql);
      echo json_encode(mysqli_fetch_assoc($result));
      exit;
  }

  if ($_GET['action'] == 'getOptionsReferentes') {
      $sql = "SELECT r.id, d.descricao, d.detalhes
          FROM referentes r
          LEFT JOIN referentes_descricoes d ON d.id_referente = r.id
          WHERE id_idioma = '".$_SESSION['KondisonairUzatorDiom']."';";

      $result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      while($r = mysqli_fetch_assoc($result)){
          echo '<option value="'.$r['id'].'" title="'.$r['detalhes'].'">'.$r['descricao'].'</option>';
      };
      die();
  };
  
  if ($_GET['action'] == 'getOptionsDicionario') {

      $id_idioma = $_GET['iid'];
      $escrita = 0;//$_GET['eid'];
    
      $romanizacao = 0;
      $result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, 
        (SELECT e.id_fonte FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) as fonte ,
        (SELECT e.id FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) as escrita 
        FROM idiomas i WHERE i.id = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($result)>0) {
        $res = mysqli_fetch_assoc($result);
        $romanizacao = $res['romanizacao'];
        $escrita = $res['escrita'];
        $fonte = $res['fonte'];
        if ($escrita>0) $nativesql = ", ( SELECT palavra FROM palavrasNativas n 
        WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita."
        LIMIT 1 ) as palavra ";
      }
      $sql = "SELECT p.* ".$nativesql."
        FROM palavras p 
        WHERE p.id_idioma = ".$id_idioma." 
        AND p.id_forma_dicionario = 0;";
        //echo $sql;
      $langs = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      //echo '<option value="2">Contração</option>';
      //echo '<option value="1">Morfema que não aparece no dicionário</option>'; '._t('Esta e a forma de dicionario').'
      echo '<option value="0" data-eid="" data-nativa="" selected>-</option>';
      while ($lang = mysqli_fetch_assoc($langs)){
        echo '<option value="'.$lang['id'].'" title="'.$lang['significado'].'"';
        if ($idioma['id_forma_dicionario'] == $lang['id']) echo ' selected'; 

        $cna = '';
        if($fonte == 3) $cna = 'custom-font-';
      
        if ($romanizacao==1) {
          echo ' data-eid="'.$cna.$escrita.'" data-nativa="'.$lang['palavra'].'"> &nbsp; '.$lang['romanizacao'].' - '.$lang['significado'].'</option>'; //'.$lang['escrita_nativa'].' -   // /'.$lang['pronuncia'].'/
        }else{
          echo ' data-eid="'.$cna.$escrita.'" data-nativa="'.$lang['palavra'].'"> &nbsp; /'.$lang['pronuncia'].'/ - '.$lang['significado'].'</option>'; //'.$lang['escrita_nativa'].' -   // /'.$lang['pronuncia'].'/
        }
      
      }
      die();
  };

  if ($_GET['action'] == 'getOptionsListWords') {
      //GET selected
      $id_idioma = $_GET['iid'];
      $eid = $_GET['eid'];
      $result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, e.id_fonte, e.id as escrita
        FROM idiomas i LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
        WHERE i.id = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
      $res = mysqli_fetch_assoc($result);
      $fonte = $res['id_fonte'];
      $escrita = $res['escrita'];

      echo '<option value="0" data-eid="" data-nativa="">-</option>';

      $pals = mysqli_query($GLOBALS['dblink'],"SELECT p.id, p.romanizacao, p.significado,
          (SELECT pn.palavra from palavrasNativas pn where pn.id_palavra = p.id and id_escrita = ".$escrita." limit 1) as nativo
          FROM palavras p WHERE p.id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));

      $cna = '';
      //if($fonte == 3) $cna = 'custom-font-';
      while ($pal = mysqli_fetch_assoc($pals)){
          echo '<option title="'.$pal['romanizacao'].'" value="'.$pal['id'].'"';
          if ($_GET['selected'] == $pal['id']) echo ' selected';
          echo ' data-eid="'.$cna.$escrita.'" data-nativa="'.$pal['nativo'].'" data-rom="'.$pal['romanizacao'].'"> &nbsp; '.$pal['significado'].'</option>';
      }

      die();
  };
  
  if ($_GET['action'] == 'listWords') { // otimizar sql queries

    // GET o = ordem (por rom, pron ou id escrita)
    // GET to = tipo ordem (pc:primeiro char, uc ultimo, nc, num. chars)
    // GET i = index (0: todas palavras, 1= caractere 1 na ordem alfabetica, nativo ou romano)

    $romanizacao = 0;
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM idiomas WHERE id = ".$_GET['id']." AND romanizacao = 1;") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($result)>0) $romanizacao = 1;

    $en = mysqli_query($GLOBALS['dblink'],"SELECT e.*, f.arquivo as fonte FROM escritas e 
                      LEFT JOIN fontes f ON f.id = e.id_fonte
                      WHERE e.id_idioma = ".$_GET['id']." AND e.padrao = 1;") or die(mysqli_error($GLOBALS['dblink']));
    if(mysqli_num_rows($en)>0){
      $en = mysqli_fetch_assoc($en);
      $escrita = $en['id'];
      $fonte = $en['fonte'];
      $tamanho = $en['tamanho'];
      $id_fonte = $en['id_fonte'];
    }else { $escrita = 1; $fonte = 'notosans';}

    $ordem = ' order by p.pronuncia ';
    $firstch = 'pronuncia';
    if ($romanizacao > 0) {
      $ordem = ' order by p.romanizacao '; $firstch = 'romanizacao';
    }else if ($escrita > 0) {
      $ordem = ' order by palavra '; $firstch = 'palavra';
    }
    
    $filtro = $_GET['t'];

    $indice = 'a';

    // id, pronuncia, romanizacao, classe, signiicado, id_forma_dicionario

    if ($filtro == 'dici'){

      $query = "SELECT p.id, p.pronuncia, p.romanizacao, p.significado, p.id_forma_dicionario, p.id_classe,
      c.nome AS classe, g.gloss AS cgl,  (SELECT COUNT(id) FROM palavras WHERE id_forma_dicionario = p.id) as rels,
      (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita." LIMIT 1) as palavra,
        (SELECT GROUP_CONCAT(tag SEPARATOR ' ') 
          FROM tags WHERE tipo_dest = 'word' AND id_dest = p.id) as tags,
        (SELECT GROUP_CONCAT(pronuncia, ' ', significado, ' ', romanizacao, ' ', palavra SEPARATOR ' , ') 
          FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
          WHERE ep.id_forma_dicionario = p.id) as extras_palavras FROM palavras p 
        LEFT JOIN classes c ON p.id_classe = c.id 
        LEFT JOIN glosses g ON c.id_gloss = g.id 
        WHERE p.id_idioma = ".$_GET['id']." AND p.id_forma_dicionario = 0 ".$ordem.";";

    }else if ($filtro == 'tudo'){

      $query = "SELECT p.id, p.pronuncia, p.romanizacao, p.significado, p.id_forma_dicionario,  p.id_classe,
      c.nome AS classe, g.gloss AS cgl,  (SELECT COUNT(id) FROM palavras WHERE id_forma_dicionario = p.id) as rels,
      (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita." LIMIT 1) as palavra,
        (SELECT GROUP_CONCAT(tag SEPARATOR ' ') 
          FROM tags WHERE tipo_dest = 'word' AND id_dest = p.id) as tags,
        (SELECT GROUP_CONCAT(pronuncia, ' ', significado, ' ', romanizacao, ' ', palavra SEPARATOR ' , ') 
          FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
          WHERE ep.id_forma_dicionario = p.id) as extras_palavras FROM palavras p 
        LEFT JOIN classes c ON p.id_classe = c.id 
        LEFT JOIN glosses g ON c.id_gloss = g.id 
        WHERE p.id_idioma = ".$_GET['id']." ".$ordem.";";


    }else if ($filtro == 'cont'){

      $query = "SELECT p.id, p.pronuncia, p.romanizacao, p.significado, p.id_forma_dicionario,  p.id_classe,
      c.nome AS classe, g.gloss AS cgl, 
      (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita." LIMIT 1) as palavra,
        (SELECT GROUP_CONCAT(tag SEPARATOR ' ') 
          FROM tags WHERE tipo_dest = 'word' AND id_dest = p.id) as tags,
        (SELECT GROUP_CONCAT(pronuncia, ',', significado, ',', romanizacao, ',', palavra SEPARATOR ' , ') 
          FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
          WHERE ep.id_forma_dicionario = p.id) as extras_palavras FROM palavras p 
        LEFT JOIN classes c ON p.id_classe = c.id 
        LEFT JOIN glosses g ON c.id_gloss = g.id 
        WHERE p.id_idioma = ".$_GET['id']." AND p.id_classe = 2 ".$ordem.";";

    }else if ($filtro == 'morf'){

      $query = "SELECT p.id, p.pronuncia, p.romanizacao, p.significado, p.id_forma_dicionario,  p.id_classe,
      c.nome AS classe, g.gloss AS cgl, 
      (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita." LIMIT 1) as palavra,
        (SELECT GROUP_CONCAT(tag SEPARATOR ' ') 
          FROM tags WHERE tipo_dest = 'word' AND id_dest = p.id) as tags,
        (SELECT GROUP_CONCAT(pronuncia, ',', significado, ',', romanizacao, ',', palavra SEPARATOR ' , ') 
          FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
          WHERE ep.id_forma_dicionario = p.id) as extras_palavras FROM palavras p 
        LEFT JOIN classes c ON p.id_classe = c.id 
        LEFT JOIN glosses g ON c.id_gloss = g.id 
        WHERE p.id_idioma = ".$_GET['id']." AND p.id_classe = 1 ".$ordem.";";

    }else if ($filtro == 'expr'){

      $query = "SELECT p.id, p.pronuncia, p.romanizacao, p.significado, p.id_forma_dicionario,  p.id_classe,
      c.nome AS classe, g.gloss AS cgl, 
        (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita." LIMIT 1) as palavra,
        (SELECT GROUP_CONCAT(tag SEPARATOR ' ') 
          FROM tags WHERE tipo_dest = 'word' AND id_dest = p.id) as tags,
        (SELECT GROUP_CONCAT(pronuncia, ',', significado, ',', romanizacao, ',', palavra SEPARATOR ' , ') 
          FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
          WHERE ep.id_forma_dicionario = p.id) as extras_palavras FROM palavras p 
        LEFT JOIN classes c ON p.id_classe = c.id 
        LEFT JOIN glosses g ON c.id_gloss = g.id 
        WHERE p.id_idioma = ".$_GET['id']." AND p.id_classe = 3 ".$ordem.";";

    }else if ($filtro > 0){ // id_classe

      $query = "SELECT p.id, p.pronuncia, p.romanizacao, p.significado, p.id_forma_dicionario,  p.id_classe,
      c.nome AS classe, g.gloss AS cgl, (SELECT COUNT(id) FROM palavras WHERE id_forma_dicionario = p.id) as rels, 
      (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita." LIMIT 1) as palavra,
        (SELECT GROUP_CONCAT(tag SEPARATOR ' ') 
          FROM tags WHERE tipo_dest = 'word' AND id_dest = p.id) as tags,
        (SELECT GROUP_CONCAT(pronuncia, ',', significado, ',', romanizacao, ',', palavra SEPARATOR ' , ') 
          FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
          WHERE ep.id_forma_dicionario = p.id) as extras_palavras FROM palavras p 
        LEFT JOIN classes c ON p.id_classe = c.id 
        LEFT JOIN glosses g ON c.id_gloss = g.id 
        WHERE p.id_idioma = ".$_GET['id']." AND p.id_forma_dicionario = 0 AND p.id_classe = ".$filtro." ".$ordem.";";
    }else {
      echo 'err dtyp dfyltr'; die();
    }

    //echo $query;
    
    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
    $rows = array();
          
    $inicialAnterior = '';
    while($r = mysqli_fetch_assoc($result)){
      $inicialAtual = substr( iconv('UTF-8', 'ASCII//TRANSLIT', $r[$firstch]),0,1); //substr($r[$firstch],0,1); //iconv('UTF-8', 'ASCII//TRANSLIT', $inicialAnterior)
      if ($inicialAnterior != $inicialAtual) {
        $echo .= '<div class="list-group-header sticky-top">'.$inicialAtual.'</div>';
        $r['inicial'] = $inicialAtual;
        $inicialAnterior = $inicialAtual;
      };

      $r['search'] = 'k'.$r['id_classe'].' '.$r['palavra'].' '.$r['pronuncia'].' '.$r['significado'].' '.$r['romanizacao'].' '.$r['tags'].' '.$r['extras_palavras'];

      //$nativo = '';
      //if($escrita>0)
      if ($r['palavra']) $r['nativo'] = getSpanPalavraNativa($r['palavra'],$escrita,$id_fonte,$tamanho); else $r['nativo'] = '';

      $rows[] = $r;
    };
    print json_encode($rows);
    die();
  };

  if ($_GET['action'] == 'ajaxDelClasse') { 

    //xxxxx ver palavras dessa classe
    $pals = mysqli_query($GLOBALS['dblink'],"SELECT id FROM palavras WHERE id_classe = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    $pals = mysqli_num_rows($pals);
    if ($pals > 0) {
        die( _t("Há %1 palavras desta classe. Não removida!",[$pals]));
    }else{
        // deletar concordancias e itens de concordancias, e genros
        
        mysqli_query($GLOBALS['dblink'],"DELETE FROM itensConcordancias WHERE id_concordancia IN(SELECT id FROM concordancias WHERE id_classe = ".$_GET['id'].");") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM concordancias WHERE id_classe = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        
        mysqli_query($GLOBALS['dblink'],"DELETE FROM classesGeneros WHERE id_genero IN(SELECT id FROM generos WHERE id_classe = ".$_GET['id'].");") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM generos WHERE id_classe = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"DELETE FROM classes WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    }
    

    die('ok');
  };

  if ($_GET['action'] == 'listarRegras') { // otimizar sql queries // traduzir

    $query = "SELECT b.*, g.gloss FROM blocos b 
          LEFT JOIN glosses g ON b.id_gloss = g.id 
          WHERE b.id_idioma = ".$_GET['iid']." order by b.ordem;";
    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));

    $classesOk = '';

    echo '<table id="tabelaPalavras" data-ride="datatables" class="table table-m-b-none">
          <thead><tr><th>Regra</th><th></th></tr></thead><tbody>';
    while($r = mysqli_fetch_assoc($result)){
      echo "<tr id='row_".$r['id']."'><td onClick='abrirRegra(".$r['id'].")'>";

      if($r['tipo_nucleo'] == 'classe') {
        $classesOk .= $r['id_nucleo'].',';
        $query = "SELECT c.id, c.descricao as title, g.gloss, c.nome
          FROM classes c 
          LEFT JOIN glosses g ON c.id_gloss = g.id 
          WHERE c.id = ".$r['id_nucleo'].";";
      /*}else if($r['tipo_nucleo'] == 'palavra') {
        $query = "SELECT p.id, p.significado as title, p.romanizacao as nome
          FROM palavras p WHERE p.id = ".$r['id_nucleo'].";";*/
      }else if($r['tipo_nucleo'] == 'bloco') {
        $query = "SELECT b.id, b.descricao as title, g.gloss, b.nome 
          FROM blocos b
          LEFT JOIN glosses g ON b.id_gloss = g.id 
          WHERE b.id = ".$r['id_nucleo'].";";
      /*}else if($r['tipo_nucleo'] == 'gloss') {
        $query = "SELECT id, descricao as nome, gloss
          FROM glosses WHERE id = ".$r['id_nucleo'].";";*/
      }
      $langs = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
      $n = mysqli_fetch_assoc($langs);
      
      if($r['tipo_dependente'] == 'classe') {
        $classesOk .= $r['id_dependente'].',';
        $query = "SELECT c.id, c.descricao as title, g.gloss, c.nome
            FROM classes c 
            LEFT JOIN glosses g ON c.id_gloss = g.id 
            WHERE c.id = ".$r['id_dependente'].";";
      /*}else if($r['tipo_dependente'] == 'palavra') {  
        $query = "SELECT p.id, p.significado as title, p.romanizacao as nome
          FROM palavras p WHERE p.id = ".$r['id_dependente'].";";*/  
      }else if($r['tipo_dependente'] == 'bloco') {
        $query = "SELECT b.id, b.descricao as title, g.gloss, b.nome 
            FROM blocos b
            LEFT JOIN glosses g ON b.id_gloss = g.id 
            WHERE b.id = ".$r['id_dependente'].";";
            
      }else if($r['tipo_dependente'] == 'none') {
        $query = "SELECT 0 as id, '' as title, '' as descricao, '' as nome, '' as gloss;";
      /*}else if($r['tipo_nucleo'] == 'gloss') {
        $query = "SELECT id, descricao as nome, gloss
          FROM glosses WHERE id = ".$r['id_dependente'].";";*/
      }
      $langs = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
      $d = mysqli_fetch_assoc($langs);

      $rule = $r['gloss'].' → ';
      $ruledesc = "<span title='".$r['title']."'>".$r['nome'].'</span> [ ';

      $rule .= $r['lado']==0 ? $n['gloss'] : $d['gloss'];
      $ruledesc .= $r['lado']==0 ?
      "<span title='".$n['title']."' >".$n['nome']."</span>"
      :"<span title='".$d['title']."' >".$d['nome']."</span>";
      
      $rule .= '-';
      $ruledesc .= ' - '; //$r['separador'];
      
      $rule .= $r['lado']==0 ? $d['gloss'] : $n['gloss'];
      $ruledesc .= $r['lado']==0 ?
      "<span title='".$d['title']."' >".$d['nome']."</span> ]"
      :"<span title='".$n['title']."' >".$n['nome']."</span> ]";

          
      echo $rule.//'<br>'.$ruledesc.
          "</td><td><a  class='btn btn-xs btn-info btn-rounded pull-right' onClick='apagarRegra(".$r['id'].")'>X</a> 
          <a  class='btn btn-xs btn-info btn-rounded pull-right' onClick='moverAbaixo(".$r['id'].")'><i class='fa fa-arrow-down'></i></a></a> 
          <a  class='btn btn-xs btn-info btn-rounded pull-right' onClick='moverAcima(".$r['id'].")'><i class='fa fa-arrow-up'></i></a></a></td></tr>";
    };
    echo '</tbody></table>';

    $classes = '';
    $cq = "SELECT c.*, g.gloss FROM classes c LEFT JOIN glosses g ON c.id_gloss = g.id 
          WHERE c.id_gloss NOT IN (SELECT id_gloss FROM classes WHERE id IN (".$classesOk."0) AND id_idioma = ".$_GET['iid'].") AND c.id_idioma = ".$_GET['iid'].";";
    //$cq = "SELECT c.*, g.gloss FROM classes c  LEFT JOIN glosses g ON c.id_gloss = g.id WHERE c.id NOT IN (".$classesOk."0) AND c.id_idioma = ".$_GET['iid'].";";
    $asg = mysqli_query($GLOBALS['dblink'],$cq) or die(mysqli_error($GLOBALS['dblink']));
    while($d = mysqli_fetch_assoc($asg)){
      $classes .= $d['gloss'].' ('.$d['nome'].')<br>';
    };
    if ($classes != '')
    echo '<div>Atenção: ainda não há regras que incluam as seguintes classes:<br>'.$classes.'Insira-as para que o tradutor funcione.</div>';
    /*<script>$("#tabelaPalavras").DataTable({
      paging: false,
      "scrollY": "500px",
      "scrollCollapse": true 
    });</script>*/
      die();
  };
  
  if ($_GET['action'] == 'ajaxSelectRegras') {

    // GET tipo (classe,palavra,bloco)
    // GET selecionado

    if($_GET['tipo'] == 'classe') {
      $query = "SELECT c.id, c.descricao as title, g.gloss, c.nome
        FROM classes c 
        LEFT JOIN glosses g ON c.id_gloss = g.id 
        WHERE c.id_idioma = ".$_GET['iid'].";";
    /*}else if($_GET['tipo'] == 'palavra') {

      $query = "SELECT p.id, p.significado as title, p.romanizacao as nome
        FROM palavras p WHERE p.id_idioma = ".$_GET['iid'].";";*/

    /*}else if($_GET['tipo'] == 'gloss') { 

      $query = "SELECT id, descricao as nome, gloss FROM glosses ;";*/

    }else if($_GET['tipo'] == 'none') { 

      echo '<option value="0">'._t('Não aplicável').'</option>';
      die();

    }else if($_GET['tipo'] == 'bloco') { 
      $query = "SELECT b.id, b.descricao as title, g.gloss, b.nome 
        FROM blocos b 
        LEFT JOIN glosses g ON b.id_gloss = g.id 
        WHERE b.id_idioma = ".$_GET['iid'].";";
    }

    $langs = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    echo '<option value="0">'._t('Selecione').' '.$_GET['tipo'].'...</option>';
    while ($lang = mysqli_fetch_assoc($langs)){
        echo '<option value="'.$lang['id'].'" title="'.$lang['title'].'"';
        if ($_GET['selecionado'] > 0 && $_GET['selecionado'] == $lang['id']) echo ' selected';
        echo '>'.$lang['gloss'].' - '.$lang['nome'].'</option>';
    };

    die();
  };

  if ($_GET['action'] == 'getDetalhesRegra') {
    
    $result = mysqli_query($GLOBALS['dblink'],"SELECT b.*, g.gloss FROM blocos b 
      LEFT JOIN glosses g ON b.id_gloss = g.id 
      WHERE b.id = ".$_GET['id']." LIMIT 1;");
    $rows = array();
      while($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
      }

    print json_encode($rows);
    die();
  };

  if ($_GET['action'] == 'ajaxSalvarEscrita') {
    $res0 = mysqli_query($GLOBALS['dblink'],"SELECT publico FROM idiomas WHERE id = (SELECT id_idioma FROM escritas WHERE id = ".$_GET['eid']." LIMIT 1);") or die(mysqli_error($GLOBALS['dblink']));
    $r0 = mysqli_fetch_assoc($res0);
 
    if ($_GET['eid']>0){ 
      $sql = "UPDATE escritas SET 
          nome = '".$_POST['nome']."',
          id_tipo = ".$_POST['id_tipo'].",
          publico = ".$_POST['publico'].",
          id_fonte = '".($_POST['id_fonte']>0?$_POST['id_fonte']:'-1')."',
          checar_glifos = ".$_POST['checar_glifos'].",
          tamanho = '".$_POST['tamanho']."',
          iniciadores = '".$_POST['iniciadores']."',
          binario = '".$_POST['binario']."',
          separadores = '".$_POST['separadores']."',
          data_modificacao = now(),
          sep_sentencas = '".$_POST['sep_sentencas']."',
          inic_sentencas = '".$_POST['inic_sentencas']."',
          substituicao = ".$_POST['substituicao'].",
          descricao = '',
          id_nativo = ".($_POST['id_nativo']?:0)."
          WHERE id = ".$_GET['eid'].";";

        //echo $sql;
      mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
        echo $_GET['eid'];

      if ( $r0['publico']==1 )//if ($_POST['publico']>0)
        logAcao(1,'skreveson',$_GET['eid']);

    }else{
      //verificar limites
      
      $res2 = mysqli_query($GLOBALS['dblink'],"SELECT (SELECT COUNT(*) FROM escritas WHERE id_idioma = ".$_POST['iid'].") as escritas,
        (SELECT valor FROM opcoes_sistema WHERE opcao = 'limite_escritas') as limite;") or die(mysqli_error($GLOBALS['dblink']));
      $r2 = mysqli_fetch_assoc($res2);

      if($r2['escritas'] > $r2['limite']){
        echo 'limit';
        die();
      };
      
      $idEscrita = generateId();
      mysqli_query($GLOBALS['dblink'],"INSERT INTO escritas SET id = $idEscrita,
          nome = '".$_POST['nome']."',
          id_tipo = ".$_POST['id_tipo'].",
          publico = ".$_POST['publico'].",
          checar_glifos = ".$_POST['checar_glifos'].",
          id_fonte = ".$_POST['id_fonte'].",
          tamanho = '".$_POST['tamanho']."',
          iniciadores = '".$_POST['iniciadores']."',
          binario = '".$_POST['binario']."',
          separadores = '".$_POST['separadores']."',
          sep_sentencas = '".$_POST['sep_sentencas']."',
          inic_sentencas = '".$_POST['inic_sentencas']."',
          substituicao = ".$_POST['substituicao'].",
          descricao = '',
          id_nativo = ".($_POST['id_nativo']?:0).";") or die(mysqli_error($GLOBALS['dblink']));
      echo $idEscrita;
      if ( $r0['publico']==1 )//if ($_POST['publico']>0)
        logAcao(0,'skreveson',$idEscrita);
    }

    die();
  };

  if ($_GET['action'] == 'ajaxSalvarFrase') {
    
    if ($_GET['id']>0){ 
      $sql = "UPDATE frases SET 
          id_original = ".$_POST['original'].",
          frase = \"".$_POST['frase']."\",
          descricao = \"".$_POST['descricao']."\",
          info = \"".$_POST['info']."\"
          WHERE id = ".$_GET['id'].";";

      mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['id'];
      logAcao(1,'frase',$_GET['id']);
    }else{
      
      $res2 = mysqli_query($GLOBALS['dblink'],"SELECT 
        (SELECT COUNT(*) FROM frases WHERE id_criador = ".$_SESSION['KondisonairUzatorIDX']." AND MONTH(data_criacao) = MONTH(NOW())) as frases,
        (SELECT valor FROM opcoes_sistema WHERE opcao = 'frases_mes') as limite;") or die(mysqli_error($GLOBALS['dblink']));
      $r2 = mysqli_fetch_assoc($res2);

      if($r2['escritas'] > $r2['limite']){
        echo 'limit';
        die();
      };
      
      $id = generateId();
      mysqli_query($GLOBALS['dblink'],"INSERT INTO frases SET id = $id,
          id_idioma = '".$_POST['idioma']."',
          id_criador = ".$_SESSION['KondisonairUzatorIDX'].",
          id_original = ".$_POST['original'].",
          frase = \"".$_POST['frase']."\",
          descricao = \"".$_POST['descricao']."\",
          info = \"".$_POST['info']."\";") or die(mysqli_error($GLOBALS['dblink']));
      echo $id;
      logAcao(0,'frase',$id);
    }
    die();
  };
  
  if ($_GET['action'] == 'ajaxReordenarOrigemPalavra') { // otimizar sql queries

    $ant = 0;
    if ($_GET['a']>0) $ant = $_GET['a'];

    $ors = mysqli_query($GLOBALS['dblink'],"SELECT id_palavra, ordem FROM palavras_origens WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    $or = mysqli_fetch_assoc($ors);
    $idPalavra = $or['id_palavra'];

    if($or['ordem']==0){
      $dess = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras_origens WHERE id_palavra = ".$idPalavra.";") or die(mysqli_error($GLOBALS['dblink']));
      $i = 1;
      while ($des = mysqli_fetch_assoc($dess)){
        mysqli_query($GLOBALS['dblink'],"UPDATE palavras_origens SET ordem = ".$i." WHERE id = ".$des['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        $i++;
      };
    }else{

      if($ant > 0) {
        mysqli_query($GLOBALS['dblink'],"UPDATE palavras_origens SET ordem = ".($or['ordem'])." WHERE id = ".$ant.";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"UPDATE palavras_origens SET ordem = ".($or['ordem']-1)." WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      }

    }
    die('ok');
  };

  function getOrigensPalavra($pid){
    $sql = "SELECT po.*, p.romanizacao, p.pronuncia, p.id as pid, p.significado,
          (SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1 LIMIT 1) as escrita,
          (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = po.id_origem AND pn.id_escrita = (
            SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1 LIMIT 1
          ) LIMIT 1) as nativo,
          (SELECT m.nome FROM momentos m WHERE m.id = i.id_momento LIMIT 1) as momento,
          (SELECT m.data_calendario FROM momentos m WHERE m.id = i.id_momento LIMIT 1) as tempo
          FROM palavras_origens po 
          LEFT JOIN palavras p ON p.id = po.id_origem
          LEFT JOIN idiomas i ON i.id = p.id_idioma
          WHERE po.id_palavra = $pid ORDER BY po.ordem;";
    
    // time_value ou data_calendario ?
          
    $result = mysqli_query($GLOBALS['dblink'],$sql);
    $origens = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $origens[] = $r;
    }
    
    return $origens;
  }
  function getOrigensPalavraRecursivo($pid) {
      $origens = getOrigensPalavra($pid);
      foreach ($origens as &$origem) {
          // Busca recursivamente as origens de cada origem
          $origem['origens'] = getOrigensPalavraRecursivo($origem['pid']);
      }
      return $origens;
  }
  if ($_GET['action'] == 'ajaxOrigemPalavra') {
    echo json_encode(getOrigensPalavraRecursivo($_GET['pid']));
    die();
  }

  if ($_GET['action'] == 'ajaxDeleteEscrita') {
      $eid = $_GET['id'];

      mysqli_query($GLOBALS['dblink'],"DELETE FROM glifos WHERE id_escrita = ".$eid.";") or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"DELETE FROM autosubstituicoes WHERE id_escrita = ".$eid.";") or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"DELETE FROM palavrasNativas WHERE id_escrita = ".$eid.";") or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"DELETE FROM drawChars WHERE id_escrita = ".$eid.";") or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],"DELETE FROM escritas WHERE id = ".$eid.";") or die(mysqli_error($GLOBALS['dblink']));
      // deletar a pasta toda dos chars se tiver
      $dir = "./writing/".$eid;
      foreach (glob($dir."/*.*") as $filename) {
          if (is_file($filename)) {
              unlink($filename);
          }
      }
      rmdir($dir);
      
      die('ok');
  };

  if ($_GET['action'] == 'ajaxDeleteGlifo') {
    // checar palavras se alguma tá usando esse glifo (palavras nativas?) GET id glifo de apagarGlifo
    $eid = $_GET['eid'];
    $g = $_GET['id'];
    if(!$eid>0||!$g>0) die('novalered'); 
    // pelo eid pegar a fonte, e se a fonte for < 0 daí é drawCaractere, não se eid < 0
    $result = mysqli_query($GLOBALS['dblink'],"SELECT id_fonte FROM escritas WHERE id = ".$eid) or die(mysqli_error($GLOBALS['dblink']));
    $r = mysqli_fetch_assoc($result);
    $fonte = $r['id_fonte'];
    if($fonte == 3){
      if($_GET['force']=='1') {
          mysqli_query($GLOBALS['dblink'],"DELETE FROM drawChars WHERE id = ".$g) or die(mysqli_error($GLOBALS['dblink']));
          // deletar arquivos
          $dir = "./writing/".$eid."/";
            foreach (glob($dir.$g.".*") as $filename) {
                if (is_file($filename)) {
                  unlink($filename);
                }
            }

      }else{

          $asg = mysqli_query($GLOBALS['dblink'],"SELECT * FROM autosubstituicoes WHERE glifos = ".$g." AND id_escrita = ".$eid.";") or die(mysqli_error($GLOBALS['dblink']));
          $num = mysqli_num_rows($asg);

          $query = "SELECT * FROM palavrasNativas WHERE FIND_IN_SET(".$g.",palavra) AND id_escrita = ".$eid.";";

          $pals = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
          $num += mysqli_num_rows($pals);
          if($num==0) {
            mysqli_query($GLOBALS['dblink'],"DELETE FROM drawChars WHERE id = ".$g) or die(mysqli_error($GLOBALS['dblink']));
            // deletar arquivos
            $dir = "./writing/".$eid."/";
            foreach (glob($dir.$g.".*") as $filename) {
                if (is_file($filename)) {
                  unlink($filename);
                }
            }
            
          }
          else {
            echo $num; 
            die();
          }
      }
    }else{
      if($_GET['force']=='1') {
          mysqli_query($GLOBALS['dblink'],"DELETE FROM glifos WHERE id = ".$g) or die(mysqli_error($GLOBALS['dblink']));
      }else{
          $gls = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glifos WHERE id = ".$g) or die(mysqli_error($GLOBALS['dblink']));
          $gl = mysqli_fetch_assoc($gls);
          $glifo = $gl['glifo'];

          $asg = mysqli_query($GLOBALS['dblink'],"SELECT * FROM autosubstituicoes WHERE glifos = \"".$glifo."\" AND id_escrita = ".$eid.";") or die(mysqli_error($GLOBALS['dblink']));
          $num = mysqli_num_rows($asg);

          $query = "SELECT * FROM palavrasNativas WHERE palavra LIKE \"%".$glifo."%\" AND id_escrita = ".$eid.";";
          
          $pals = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
          $num += mysqli_num_rows($pals);
          if($num==0) mysqli_query($GLOBALS['dblink'],"DELETE FROM glifos WHERE id = ".$g) or die(mysqli_error($GLOBALS['dblink']));
          else {
            echo $num; 
            die();
          }
      }
    }
    die('ok');
  };

  if ($_GET['action'] == 'ajaxLoadAlphabetDrawSubs') {
    if($_GET['eid']) $eid = $_GET['eid']; else die('novalered'); 
    $query = "SELECT g.*, e.id_fonte, e.tamanho, DATE_FORMAT( data_modificado,'%Y%m%d%H%i%s') as ultima,
        (SELECT GROUP_CONCAT(glifo SEPARATOR' ') FROM drawChars v WHERE v.id_principal = g.id) as variantes
        FROM drawChars g LEFT JOIN escritas e ON e.id = g.id_escrita
        WHERE g.id_escrita = ".$eid." AND g.id_principal = 0 ORDER BY g.ordem;";
    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    while($r = mysqli_fetch_assoc($result)){
      echo '<label class="form-selectgroup-item">
                  <input type="radio" name="glifow'.$eid.'" value="'.$r['id'].'" class="form-selectgroup-input">
                  <span class="form-selectgroup-label">
                    <span class="drawchar drawchar-'.$r['tamanho'].'" style="background-image: url(./writing/'.$eid.'/'.$r['id'].'.png?'.$r['ultima'].')"></span>'.$r['descricao'].'</span>
                </label>';
    };
    die();
  };

  if ($_GET['action'] == 'ajaxLoadAlphabetData') {
      if (!isset($_GET['eid'])) {
          header('Content-Type: application/json');
          echo json_encode(['error' => 'novalered']);
          exit;
      }
      
      $eid = $_GET['eid'];
      $result = mysqli_query($GLOBALS['dblink'], "SELECT id_fonte, tamanho FROM escritas WHERE id = " . mysqli_real_escape_string($GLOBALS['dblink'], $eid)) or die(json_encode(['error' => mysqli_error($GLOBALS['dblink'])]));
      $r = mysqli_fetch_assoc($result);
      
      if (!$r) {
          header('Content-Type: application/json');
          echo json_encode(['error' => 'escrita_not_found']);
          exit;
      }
      
      $fonte = $r['id_fonte'];
      $tamanho = $r['tamanho'];
      $glyphs = [];

      if ($fonte == 3) {
          $query = "SELECT g.*, DATE_FORMAT(data_modificado, '%Y%m%d%H%i%s') as ultima,
                    (SELECT GROUP_CONCAT(glifo SEPARATOR ' ') FROM drawChars v WHERE v.id_principal = g.id) as variantes
                    FROM drawChars g 
                    WHERE g.id_escrita = " . mysqli_real_escape_string($GLOBALS['dblink'], $eid) . " AND g.id_principal = 0 ORDER BY g.ordem";
      } else {
          $query = "SELECT g.*,
                    (SELECT GROUP_CONCAT(glifo SEPARATOR ' ') FROM glifos v WHERE v.id_principal = g.id) as variantes
                    FROM glifos g 
                    WHERE g.id_escrita = " . mysqli_real_escape_string($GLOBALS['dblink'], $eid) . " AND g.id_principal = 0 ORDER BY g.ordem";
      }

      $result = mysqli_query($GLOBALS['dblink'], $query) or die(json_encode(['error' => mysqli_error($GLOBALS['dblink'])]));
      
      while ($r = mysqli_fetch_assoc($result)) {
          $glyph = [
              'id' => $r['id'],
              'id_escrita' => $r['id_escrita'],
              'glifo' => $r['glifo'],
              'descricao' => $r['descricao'],
              'variantes' => $r['variantes'] ? $r['variantes'] : '',
              'vetor' => $r['vetor'] ?? ''
          ];
          if ($fonte == 3) {
              $glyph['ultima'] = $r['ultima'];
          }
          $glyphs[] = $glyph;
      }
      
      header('Content-Type: application/json');
      echo json_encode([
          'fonte' => $fonte,
          'tamanho' => $tamanho,
          'glyphs' => $glyphs
      ]);
      exit;
  }
  
  if ($_GET['action'] == 'ajaxLoadAlphabet') {
    if($_GET['eid']) $eid = $_GET['eid']; else die('novalered'); 

    // pelo eid pegar a fonte, e se a fonte for < 0 daí é drawCaractere, não se eid < 0
    $result = mysqli_query($GLOBALS['dblink'],"SELECT id_fonte, tamanho FROM escritas WHERE id = ".$eid) or die(mysqli_error($GLOBALS['dblink']));
    $r = mysqli_fetch_assoc($result);
    $fonte = $r['id_fonte'];
    $tamanho = $r['tamanho'];

    if($fonte == 3){
      $editChar = 'drawCaractere(\'';
      $query = "SELECT g.*, DATE_FORMAT( data_modificado,'%Y%m%d%H%i%s') as ultima,
        (SELECT GROUP_CONCAT(glifo SEPARATOR' ') FROM drawChars v WHERE v.id_principal = g.id) as variantes
        FROM drawChars g 
        WHERE g.id_escrita = ".$eid." AND g.id_principal = 0 ORDER BY g.ordem;";
        //
    }else{
      $editChar = 'addCaractere(\'';
      $query = "SELECT g.*,
            (SELECT GROUP_CONCAT(glifo SEPARATOR' ') FROM glifos v WHERE v.id_principal = g.id) as variantes
            FROM glifos g 
            WHERE g.id_escrita = ".$eid." AND g.id_principal = 0 ORDER BY g.ordem;";
    }


    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    
    while($r = mysqli_fetch_assoc($result)){
      if($fonte == 3){
        $glifo = '<span class="drawchar drawchar-'.$tamanho.'" style="background-image: url(./writing/'.$eid.'/'.$r['id'].'.png?'.$r['ultima'].')"></span> '.$r['descricao'];
        //$glifo = '<span class="avatar avatar-md" style="background-image: url(./writing/'.$eid.'/'.$r['id'].'.png?'.$r['ultima'].')"></span> '.$r['descricao'];
      }else{
        $glifo = "<span class='custom-font-".$r['id_escrita']."'>".$r['glifo'].'</span>';//$r['glifo'];
        if($r['descricao']!='') $glifo .= ' ('.$r['descricao'].')'; 
        if($r['variantes']!='') $glifo .= "<br><span class='text-secondary text-truncate custom-font-".$r['id_escrita']."'><small>".$r['variantes'].'</small></span>'; 
      }
      
      echo '
        <div class="list-group-item">
          <div class="row align-items-center">
            <div class="col-auto" onclick="'.$editChar.$r['id_escrita']."','".$r['descricao']."','".$r['id']."','".str_replace("'","*",$r['glifo'])."','".str_replace("'","*",$r['variantes']).'\',`'.htmlspecialchars($r['vetor']).'`)"
              >'.$glifo.'</div>
            <div class="col text-end"> 
              <div class="text-secondary text-truncate mt-n1">'." 
                <a  class='btn btn-danger btn-sm' onClick='apagarGlifo(\"".$r['id']."\",\"".$eid."\")'>X</a> 
                <a  class='btn btn-primary btn-sm' onClick='moverAbaixo(\"".$r['id']."\",\"".$eid."\")'>v</a></a> 
                <a  class='btn btn-primary btn-sm' onClick='moverAcima(\"".$r['id']."\",\"".$eid."\")'>^</a>".'
              </div>
            </div>
          </div>
        </div>';
    };
    die();

  };

  if ($_GET['action'] == 'ajaxDeleteAutosubstitution') {
    mysqli_query($GLOBALS['dblink'],"DELETE FROM autosubstituicoes WHERE id = ".$_GET['id']) or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
  };

  if ($_GET['action'] == 'ajaxLoadAutosubstitutionData') {
      if (!isset($_GET['eid'])) {
          header('Content-Type: application/json');
          echo json_encode(['error' => 'novalered']);
          exit;
      }
      
      $eid = $_GET['eid'];
      $result = mysqli_query($GLOBALS['dblink'], "SELECT id_fonte, tamanho FROM escritas WHERE id = " . mysqli_real_escape_string($GLOBALS['dblink'], $eid)) or die(json_encode(['error' => mysqli_error($GLOBALS['dblink'])]));
      $r = mysqli_fetch_assoc($result);
      
      if (!$r) {
          header('Content-Type: application/json');
          echo json_encode(['error' => 'escrita_not_found']);
          exit;
      }
      
      $fonte = $r['id_fonte'];
      $tamanho = $r['tamanho'];
      $autosubstitutions = [];

      if ($fonte == 3) {
          $query = "SELECT s.*, c.id as cid, CHAR_LENGTH(tecla) as tam, DATE_FORMAT(c.data_modificado, '%Y%m%d%H%i%s') as ultima
                    FROM autosubstituicoes s 
                    LEFT JOIN drawChars c ON c.id = s.glifos
                    WHERE s.id_escrita = " . mysqli_real_escape_string($GLOBALS['dblink'], $eid) . " ORDER BY tam DESC";
          $result = mysqli_query($GLOBALS['dblink'], $query) or die(json_encode(['error' => mysqli_error($GLOBALS['dblink'])]));
          
          while ($r = mysqli_fetch_assoc($result)) {
              $autosubstitutions[] = [
                  'id' => $r['id'],
                  'tecla' => $r['tecla'],
                  'ipa' => $r['ipa'],
                  'glifos' => $r['glifos'],
                  'cid' => $r['cid'],
                  'ultima' => $r['ultima'],
                  'tam' => $r['tam']
              ];
          }
      } else {
          $query = "SELECT s.*, CHAR_LENGTH(tecla) as tam
                    FROM autosubstituicoes s 
                    WHERE s.id_escrita = " . mysqli_real_escape_string($GLOBALS['dblink'], $eid) . " ORDER BY tam DESC";
          $result = mysqli_query($GLOBALS['dblink'], $query) or die(json_encode(['error' => mysqli_error($GLOBALS['dblink'])]));
          
          while ($r = mysqli_fetch_assoc($result)) {
              $autosubstitutions[] = [
                  'id' => $r['id'],
                  'tecla' => $r['tecla'],
                  'ipa' => $r['ipa'],
                  'glifos' => $r['glifos'],
                  'tam' => $r['tam']
              ];
          }
      }
      
      header('Content-Type: application/json');
      echo json_encode([
          'fonte' => $fonte,
          'tamanho' => $tamanho,
          'autosubstitutions' => $autosubstitutions
      ]);
      exit;
  }
  
  if ($_GET['action'] == 'ajaxLoadAutosubstitution') {
    if($_GET['eid']) $eid = $_GET['eid']; else die('novalered');
    
    $result = mysqli_query($GLOBALS['dblink'],"SELECT id_fonte, tamanho FROM escritas WHERE id = ".$eid) or die(mysqli_error($GLOBALS['dblink']));
    $r = mysqli_fetch_assoc($result);
    $fonte = $r['id_fonte'];
    $tamanho = $r['tamanho'];

    if($fonte == 3){
        $query = "SELECT s.*, c.id as cid, CHAR_LENGTH(tecla) as tam, DATE_FORMAT( c.data_modificado,'%Y%m%d%H%i%s') as ultima
              FROM autosubstituicoes s 
              LEFT JOIN drawChars c ON c.id = s.glifos
              WHERE s.id_escrita = ".$eid." ORDER BY tam DESC;"; 
        //echo $query;
        $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
        // BUSCAR em teclas tbm cada stroke/char da entrada, pegar seu IPA entre parentesis
        while($r = mysqli_fetch_assoc($result)){ 
                
          echo "
          <div class=\"list-group-item\">
            <div class=\"row align-items-center\" >
              <div onclick=\"addSubstituicaoDraw('".$eid."','".$r['id']."','".str_replace("'","*",$r['tecla'])."','".$r['glifos']."')\" 
                  class=\"col-auto\">".$r['tecla']." /".$r['ipa']."/ → ".'<span class="drawchar drawchar-'.$tamanho.'" style="background-image: url(./writing/'.$eid.'/'.$r['cid'].'.png?'.$r['ultima'].')"></span>'."
              </div>
              <div class=\"col text-end\"> 
                <div class=\"text-secondary text-truncate mt-n1\">
                  <a  class='btn btn-danger btn-sm' onClick='remAutosubs(\"".$r['id']."\",\"".$eid."\")'>X</a>
                </div>
              </div>
            </div>
          </div>";
        };

    }else{
        $query = "SELECT s.*, CHAR_LENGTH(tecla) as tam
              FROM autosubstituicoes s 
              WHERE s.id_escrita = ".$eid." ORDER BY tam DESC;"; 
        $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
        // BUSCAR em teclas tbm cada stroke/char da entrada, pegar seu IPA entre parentesis
        while($r = mysqli_fetch_assoc($result)){ 
                
          echo "
          <div class=\"list-group-item\">
            <div class=\"row align-items-center\" >
              <div onclick=\"addSubstituicao('".$eid."','".$r['id']."','".str_replace("'","*",$r['tecla'])."','".str_replace("'","*",$r['glifos'])."')\" 
                  class=\"col-auto\">".$r['tecla']." /".$r['ipa']."/ → <span class='custom-font-".$eid."'>".$r['glifos']."</span>
              </div>
              <div class=\"col text-end\"> 
                <div class=\"text-secondary text-truncate mt-n1\">
                  <a  class='btn btn-danger btn-sm' onClick='remAutosubs(\"".$r['id']."\",\"".$eid."\")'>X</a>
                </div>
              </div>
            </div>
          </div>";
        };
    }
    die();
  };
  
  if ($_GET['action'] == 'ajaxCarregarTimeline') {
    if($_GET['rid']) $rid = $_GET['rid']; else die('novalered'); 
    $superior = 0;
    if($_GET['tid']>0) $superior = $_GET['tid'];

    //query metadados
    //loop inserindo na query principal os metadados
    // evitando query dentro de loop !!
    $tempos = '';
    $add = '<div class="momentCard">
            <div class="momentInfo" onclick="adicionarMomento()">
                <h5 class="momentTitle"><i class="fa fa-plus"></i></h5>
            </div></div>';

    $query = "SELECT * FROM momentos WHERE id_realidade = ".$rid." AND id_superior = ".$superior." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";"; // ORDER BY g.ordem

    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));

    while($r = mysqli_fetch_assoc($result)){

      $tempos .= '<div class="momentCard"><div class="momentInfo" onclick="loadPainel('.$r['id'].')">
              <h5 class="momentTitle">'.$r['nome'].'</h5>
              <p>'.$r['descricao'].'</p>
              <a class="btn btn-sm btn-info btn-rounded" href="?action=moments&rid='.$rid.'&tid='.$r['id'].'">'._t('Eventos').'</a>
          </div></div>';
    };

    echo $add.$tempos.($tempos==''?'':$add);
    die();
  };

  if ($_GET['action']=='ajaxApagarRegra') {
    mysqli_query($GLOBALS['dblink'],"DELETE FROM blocos WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
  };

  if ($_GET['action']=='ajaxRegraAcima') { // otimizar sql queries
    if ($_GET['id']>0) $atual = $_GET['id']; else die('novalered');
    $ors = mysqli_query($GLOBALS['dblink'],"SELECT ordem, id_idioma FROM blocos WHERE id = ".$atual.";") or die(mysqli_error($GLOBALS['dblink']));
    $or = mysqli_fetch_assoc($ors);
    if ($or['ordem']==0){
      $dess = mysqli_query($GLOBALS['dblink'],"SELECT * FROM blocos WHERE id_idioma = ".$or['id_idioma'].";") or die(mysqli_error($GLOBALS['dblink']));
      $i = 1;
      while ($des = mysqli_fetch_assoc($dess)){
        mysqli_query($GLOBALS['dblink'],"UPDATE blocos SET ordem = ".$i." WHERE id = ".$des['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        $i++;
      };
    }else{ 
      if($or['ordem']>1){

        $ants = mysqli_query($GLOBALS['dblink'],"SELECT id,ordem FROM blocos WHERE id_idioma = ".$or['id_idioma']." AND ordem = ".($or['ordem']-1).";") or die(mysqli_error($GLOBALS['dblink']));
        $an = mysqli_fetch_assoc($ants);
        
        mysqli_query($GLOBALS['dblink'],"UPDATE blocos SET ordem = ".($or['ordem']-1)." WHERE id = ".$atual.";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"UPDATE blocos SET ordem = ".($or['ordem'])." WHERE id = ".$an['id'].";") or die(mysqli_error($GLOBALS['dblink']));

      }
    };
    die('ok');
  };
  
  if ($_GET['action']=='ajaxRegraAbaixo') { // otimizar sql queries
    $prox = 0;
    if ($_GET['id']>0) $atual = $_GET['id']; else die('novalered');
    $ors = mysqli_query($GLOBALS['dblink'],"SELECT ordem, id_idioma FROM blocos WHERE id = ".$atual.";") or die(mysqli_error($GLOBALS['dblink']));
    $or = mysqli_fetch_assoc($ors);
    if ($or['ordem']==0){
      $dess = mysqli_query($GLOBALS['dblink'],"SELECT * FROM blocos WHERE id_idioma = ".$or['id_idioma'].";") or die(mysqli_error($GLOBALS['dblink']));
      $i = 1;
      while ($des = mysqli_fetch_assoc($dess)){
        mysqli_query($GLOBALS['dblink'],"UPDATE blocos SET ordem = ".$i." WHERE id = ".$des['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        $i++;
      };
    }else{
      $orts = mysqli_query($GLOBALS['dblink'],"SELECT * FROM blocos WHERE id_idioma = ".$or['id_idioma'].";") or die(mysqli_error($GLOBALS['dblink']));
      $total = mysqli_num_rows($orts);

      //echo  $or['ordem'].'<'.($total-1);
      if($or['ordem']<$total){ // rowcount
        //echo 'ordenar abaixo';

        $proxs = mysqli_query($GLOBALS['dblink'],"SELECT id,ordem FROM blocos WHERE id_idioma = ".$or['id_idioma']." AND ordem = ".($or['ordem']+1).";") or die(mysqli_error($GLOBALS['dblink']));
        $pr = mysqli_fetch_assoc($proxs);
        
        mysqli_query($GLOBALS['dblink'],"UPDATE blocos SET ordem = ".($or['ordem']+1)." WHERE id = ".$atual.";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"UPDATE blocos SET ordem = ".($or['ordem'])." WHERE id = ".$pr['id'].";") or die(mysqli_error($GLOBALS['dblink']));

      }

    };
    die('ok');
  };
  
  if ($_GET['action']=='ajaxAddDrawCaractereEscrita') {
    
    // GET eid = id escrita
    // GET cid = id glifo
    
    // novo POST vetor
    //$vetor = json_encode($_POST['vetor']);
    $vetor = $_POST['vetor'];
    $png = $_POST['png'];
    $svg = $_POST['svg'];
    // echo $png;

    $c = str_replace("*","'",$_GET['c']);
    $id_glifo = $id_escrita = 0;
    if ($_GET['eid']>0) $id_escrita = $_GET['eid']; else die('novalered');
    if ($_GET['cid']>0) $id_glifo = $_GET['cid'];

    $vars = explode(" ",$_GET['vars']);

    if($id_glifo>0){
      mysqli_query($GLOBALS['dblink'],"DELETE FROM drawChars WHERE id_escrita = ".$id_escrita." AND id_principal = ".$id_glifo.";") or die(mysqli_error($GLOBALS['dblink']));
      //update
      $query = "UPDATE drawChars SET 
        id_escrita = ".$id_escrita.",
        glifo = \"".$c."\",
        descricao = '".$_GET['desc']."',
        vetor = '".$vetor."',
        data_modificado = NOW(),
        input = ''
        WHERE id = ".$id_glifo.";";
      mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    }else{
      //insert
      $cnts = mysqli_query($GLOBALS['dblink'],"SELECT * FROM drawChars WHERE id_escrita = ".$id_escrita." AND id_principal = 0; ") or die(mysqli_error($GLOBALS['dblink']));
      $id_glifo = generateId();
      $query = "INSERT INTO drawChars SET id = $id_glifo,
        id_escrita = ".$id_escrita.",
        glifo = \"".$c."\",
        ordem = ".(mysqli_num_rows($cnts)+1).",
        descricao = '".$_GET['desc']."',
        vetor = '".$vetor."',
        input = '';";
      mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    }

    mkdir('./writing/'.$id_escrita.'/', 0777, true);
    // file_put_contents('./writing/'.$id_escrita.'/'.$id_glifo.'.png', file_get_contents($data));
    file_put_contents('./writing/'.$id_escrita.'/'.$id_glifo.'.png',  base64_decode(str_replace(' ','+',explode(',', $_POST['png'])[1])) );
    //file_put_contents('./writing/'.$id_escrita.'/'.$id_glifo.'.svg',  base64_decode(str_replace(' ','+',explode(',', $_POST['svg'])[1])) );
    // file_put_contents('./writing/'.$id_escrita.'/'.$id_glifo.'.png',$png);

    echo $id_glifo;

    /*
    $query = "INSERT INTO drawChars (id_escrita,glifo,ordem,descricao,input,id_principal,vetor) VALUES ";
    foreach($vars as $derivado){
      $query .= "(".$id_escrita.",'".$derivado."',0,'".$_GET['desc']."','',".$id_glifo.",'".$vetor."'),";
    };
    mysqli_query( $GLOBALS['dblink'], substr($query,0,strlen($query)-1) ) or die(mysqli_error($GLOBALS['dblink']));
    */

    die();
  };
  
  if ($_GET['action']=='ajaxAddCaractereEscrita') {
    
    // GET eid = id escrita
    //GET cid = id glifo
    $c = str_replace("*","'",$_GET['c']);
    $id_glifo = $id_escrita = 0;
    if ($_GET['eid']>0) $id_escrita = $_GET['eid']; else die('novalered');
    if ($_GET['cid']>0) $id_glifo = $_GET['cid'];

    $vars = explode(" ",$_GET['vars']);

    if($id_glifo>0){
      mysqli_query($GLOBALS['dblink'],"DELETE FROM glifos WHERE id_escrita = ".$id_escrita." AND id_principal = ".$id_glifo.";") or die(mysqli_error($GLOBALS['dblink']));
      //update
      $query = "UPDATE glifos SET 
        id_escrita = ".$id_escrita.",
        glifo = \"".$c."\", data_modificado = NOW(),
        descricao = '".$_GET['desc']."',
        input = ''
        WHERE id = ".$id_glifo.";";
      mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    }else{
      //insert
      $cnts = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glifos WHERE id_escrita = ".$id_escrita." AND id_principal = 0; ") or die(mysqli_error($GLOBALS['dblink']));
      $id_glifo = generateId();
      $query = "INSERT INTO glifos SET  id = $id_glifo,
        id_escrita = ".$id_escrita.",
        glifo = \"".$c."\",
        ordem = ".(mysqli_num_rows($cnts)+1).",
        descricao = '".$_GET['desc']."',
        input = '';";
      mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    }

    echo $id_glifo;

    $query = "INSERT INTO glifos (id,id_escrita,glifo,ordem,descricao,input,id_principal) VALUES ";
    foreach($vars as $derivado){
      $query .= "(".generateId().",".$id_escrita.",'".$derivado."',0,'".$_GET['desc']."','',".$id_glifo."),";
    };
    mysqli_query( $GLOBALS['dblink'], substr($query,0,strlen($query)-1) ) or die(mysqli_error($GLOBALS['dblink']));

    die();
  };
  
  if ($_GET['action']=='ajaxSetEscritaPadrao') {

    if($_GET['eid']>0 && $_GET['iid']>0) {
      mysqli_query($GLOBALS['dblink'], "UPDATE escritas SET padrao = 0 WHERE id_idioma = ".$_GET['iid'].";" ) or die(mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'], "UPDATE escritas SET padrao = 1 WHERE id = ".$_GET['eid'].";" ) or die(mysqli_error($GLOBALS['dblink']));
    }else{
      echo 'novalered'; die();
    }


    die('ok');
  };
  
  if ($_GET['action']=='ajaxGlifoAcima') { // otimizar sql queries
    if ($_GET['id']>0) $atual = $_GET['id']; else die('novalered');
    $ors = mysqli_query($GLOBALS['dblink'],"SELECT ordem, id_escrita FROM glifos WHERE id = ".$atual." AND id_principal = 0;") or die(mysqli_error($GLOBALS['dblink']));
    $or = mysqli_fetch_assoc($ors);

    if ($or['ordem']==0){
      $dess = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glifos WHERE id_escrita = ".$or['id_escrita']." AND id_principal = 0;") or die(mysqli_error($GLOBALS['dblink']));
      $i = 1;
      while ($des = mysqli_fetch_assoc($dess)){
        mysqli_query($GLOBALS['dblink'],"UPDATE glifos SET ordem = ".$i." WHERE id = ".$des['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        $i++;
      };
    }else{ 
      if($or['ordem']>1){

        $ants = mysqli_query($GLOBALS['dblink'],"SELECT id,ordem FROM glifos WHERE id_escrita = ".$or['id_escrita']." AND ordem = ".($or['ordem']-1)." AND id_principal = 0;") or die(mysqli_error($GLOBALS['dblink']));
        $an = mysqli_fetch_assoc($ants);
        
        mysqli_query($GLOBALS['dblink'],"UPDATE glifos SET ordem = ".($or['ordem']-1)." WHERE id = ".$atual.";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"UPDATE glifos SET ordem = ".($or['ordem'])." WHERE id = ".$an['id'].";") or die(mysqli_error($GLOBALS['dblink']));

      }
    };
    die('ok');
  };
  
  if ($_GET['action']=='ajaxGlifoAbaixo') { // otimizar sql queries
    $prox = 0;
    if ($_GET['id']>0) $atual = $_GET['id']; else die('novalered');
    $ors = mysqli_query($GLOBALS['dblink'],"SELECT ordem, id_escrita FROM glifos WHERE id = ".$atual." AND id_principal = 0;") or die(mysqli_error($GLOBALS['dblink']));
    $or = mysqli_fetch_assoc($ors);
    if ($or['ordem']==0){
      $dess = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glifos WHERE id_escrita = ".$or['id_escrita']." AND id_principal = 0;") or die(mysqli_error($GLOBALS['dblink']));
      $i = 1;
      while ($des = mysqli_fetch_assoc($dess)){
        mysqli_query($GLOBALS['dblink'],"UPDATE glifos SET ordem = ".$i." WHERE id = ".$des['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        $i++;
      };
    }else{
      $orts = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glifos WHERE id_escrita = ".$or['id_escrita']." AND id_principal = 0;") or die(mysqli_error($GLOBALS['dblink']));
      $total = mysqli_num_rows($orts);

      //echo  $or['ordem'].'<'.($total-1);
      if($or['ordem']<$total){ // rowcount
        //echo 'ordenar abaixo';

        $proxs = mysqli_query($GLOBALS['dblink'],"SELECT id,ordem FROM glifos WHERE id_escrita = ".$or['id_escrita']." AND ordem = ".($or['ordem']+1)." AND id_principal = 0;") or die(mysqli_error($GLOBALS['dblink']));
        $pr = mysqli_fetch_assoc($proxs);
        
        mysqli_query($GLOBALS['dblink'],"UPDATE glifos SET ordem = ".($or['ordem']+1)." WHERE id = ".$atual.";") or die(mysqli_error($GLOBALS['dblink']));
        mysqli_query($GLOBALS['dblink'],"UPDATE glifos SET ordem = ".($or['ordem'])." WHERE id = ".$pr['id'].";") or die(mysqli_error($GLOBALS['dblink']));

      }

    };
    die('ok');
  };

  if ($_GET['action']=='getLikeButton') { 

    if ($_GET['u']>0) $seguido = $_GET['u']; else die('novalered');

    if ($_GET['val']=="-1"){ // existe, já segue > desseguir
      mysqli_query($GLOBALS['dblink'],
        "DELETE FROM sosail_sgisons
        WHERE id IN(SELECT id FROM sosail_sgisons 
            WHERE id_seguido = ".$seguido." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']."
        );"
      ) or die(mysqli_error($GLOBALS['dblink']));
      
    }else if ($_GET['val']=='1'){ // não existe, seguir
      mysqli_query($GLOBALS['dblink'],
        "INSERT INTO sosail_sgisons SET id = ".generateId().",
          id_seguido = ".$seguido.", 
          id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";"
      ) or die(mysqli_error($GLOBALS['dblink']));

    };


    $sql = "SELECT
        (SELECT COUNT(*) FROM sosail_sgisons 
            WHERE id_seguido = ".$seguido." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']."
        ) as seguir ;";

    $ors = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
    $or = mysqli_fetch_assoc($ors);

    if ($or['seguir']>0){ 
      $btn = '<a class="btn btn-primary" onclick="sgison(\'-1\')">'._t('Seguindo').'</a>';
    }else{ 
      $btn = '<a class="btn btn-default" onclick="sgison(\'1\')">'._t('Seguir').'</a>';
    };

    echo $btn;

    die();
  };

  if ($_GET['action']=='ajaxSgison') { 

    if ($_GET['u']>0) $seguido = $_GET['u']; else die('novalered');

    $sql = "SELECT
        (SELECT id FROM sosail_sgisons 
            WHERE id_seguido = ".$seguido." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']."
        ) as seguir ;";
    $ors = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
    $or = mysqli_fetch_assoc($ors);

    if ($or['seguir']>0){ // existe, já segue > desseguir
      mysqli_query($GLOBALS['dblink'],
        "DELETE FROM sosail_sgisons
        WHERE id = ".$or['seguir'].";"
      ) or die(mysqli_error($GLOBALS['dblink']));
      $btn = '<a title="Seguir" data-toggle="tooltip" class="btn btn-md btn-primary btn-rounded" onclick="sgison()"><i class="fa fa-plus"></i></a>';
      
    }else{ // não existe, seguir
      mysqli_query($GLOBALS['dblink'],
        "INSERT INTO sosail_sgisons SET id = ".generateId().",
          id_seguido = ".$seguido.", 
          id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";"
      ) or die(mysqli_error($GLOBALS['dblink']));
      $btn = '<a title="Deixar de seguir" data-toggle="tooltip" class="btn btn-md btn-danger btn-rounded" onclick="sgison()"><i class="fa fa-minus"></i></a>';

    };
    
    $sql = "SELECT
        (SELECT COUNT(*) FROM sosail_sgisons WHERE id_seguido = ".$seguido.") as seguidores, 
        (SELECT COUNT(*) FROM sosail_sgisons WHERE id_usuario = ".$seguido.") as seguidos;";
    $ors = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
    $or = mysqli_fetch_assoc($ors);

    echo '<h5 class="control-label">'.$or['seguidores'].' '._t('seguidores').'</h5>
    <h5 class="control-label">'.$or['seguidos'].' '._t('seguidos').'</h5>'.$btn;

    die();
  };

  if ($_GET['action']=='ajaxCavMdason') { 

    $r = mysqli_query($GLOBALS['dblink'],"SELECT senha,confirmacao FROM usuarios WHERE id = '".$_SESSION['KondisonairUzatorIDX']."';") or die(mysqli_error($GLOBALS['dblink']));
    $o = mysqli_fetch_row($r);
    
    if( password_verify($_POST['o'], $o[0])  ) {
      
      mysqli_query($GLOBALS['dblink'],"UPDATE usuarios SET 
            senha = '".password_hash($_POST['n'], PASSWORD_DEFAULT)."'
            WHERE id = ".$_SESSION['KondisonairUzatorIDX']."
      ;") or die(mysqli_error($GLOBALS['dblink']));

      echo 'ok';
    }else{
      echo 'invalid';
    };

    die();
  };

  if ($_GET['action']=='ajaxUzatorrMdason') { 
    
    // post o=old n=new
    //check $_POST['n'] se é só numero e letras
    
    $r = mysqli_query($GLOBALS['dblink'],"SELECT * FROM usuarios WHERE username = '".$_POST['u']."';") or die(mysqli_error($GLOBALS['dblink']));

    if( mysqli_num_rows($r) == 0 ) {
      
      mysqli_query($GLOBALS['dblink'],"UPDATE usuarios SET 
            username = '".$_POST['u']."'
            WHERE id = ".$_SESSION['KondisonairUzatorIDX']."
      ;") or die(mysqli_error($GLOBALS['dblink']));
      $_SESSION['KondisonairUzatorNome'] = $_POST['u'];
      $_SESSION['KondisonairUzatorID'] = $_POST['u'];
      echo 'ok';
    }else{
      echo 'novalered';
    };

    die();
  };

  if ($_GET['action']=='ajaxUzatorrCheck') { 
    
    // post u
    $r = mysqli_query($GLOBALS['dblink'],"SELECT * FROM usuarios WHERE username = '".$_POST['u']."';") or die(mysqli_error($GLOBALS['dblink']));
    echo mysqli_num_rows($r);

    die();
  };
  
  if ($_GET['action']=='ajaxJoes') { 
    if ($_GET['id']>0) $id = $_GET['id']; else die('novalered');
    $tipo = $_GET['t']; // GET t = tipo ( diom | palavr | artyg | post | ... )
    $val = $_GET['l']; // GET l = 0=só load, 1=like, outros=dislike
    if( $val > 0 || $val < 0 ){

      mysqli_query($GLOBALS['dblink'],
          "DELETE FROM sosail_joes
          WHERE tipo_destino = '".$tipo."' AND id_destino = ".$id."
          AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";"
      ) or die(mysqli_error($GLOBALS['dblink']));

      mysqli_query($GLOBALS['dblink'],
        "INSERT INTO sosail_joes SET id = ".generateId().",
          tipo_destino = '".$tipo."',
          id_destino = ".$id.", 
          id_usuario = ".$_SESSION['KondisonairUzatorIDX'].",
          valor = ".($val==1 ? 1 : -1 ).";"
      ) or die(mysqli_error($GLOBALS['dblink']));

    };

    $sql = "SELECT
      (SELECT COUNT(*) FROM sosail_joes 
        WHERE tipo_destino = '".$tipo."' AND id_destino = ".$id."
        AND valor > 0) as likes,
      (SELECT COUNT(*) FROM sosail_joes 
        WHERE tipo_destino = '".$tipo."' AND id_destino = ".$id."
        AND valor > 0 AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].") as liked,
      (SELECT COUNT(*) FROM sosail_joes 
        WHERE tipo_destino = '".$tipo."' AND id_destino = ".$id."
        AND valor < 0) as dislikes,	
      (SELECT COUNT(*) FROM sosail_joes 
        WHERE tipo_destino = '".$tipo."' AND id_destino = ".$id."
        AND valor < 0 AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].") as disliked
      ;";

    //echo $sql;
    $result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

    $r = mysqli_fetch_assoc($result);
    echo '<label class="control-label btn '.($r['liked']==1?'btn-primary">':'" onclick="btnJoes(1,\''.$tipo.'\','.$id.')">').$r['likes'].' <i class="fa fa-thumbs-up"></i></label>
    <label class="control-label btn '.($r['disliked']==1?'btn-danger">':'" onclick="btnJoes(2,\''.$tipo.'\','.$id.')">').$r['dislikes'].' <i class="fa fa-thumbs-down"></i></label>';
    die();
  };

  if ($_GET['action'] == 'ajaxSalvArtyg') {

    if ($_GET['aid']>0){
      $sql = "UPDATE artygs SET 
        nome = '".$_POST['n']."',
        id_pap = '".$_POST['ap']."',
        publico = '".$_POST['p']."',
        data_modificacao = now(),
        texto = '".str_replace("'",'"',$_POST['t'])."'
        WHERE id = ".$_GET['aid']." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";"; //$_SESSION['KondisonairUzatorDiom']
      mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      $aid = $_GET['aid'];
    }else{

      //xxxxx LIMIT artigos
      $aid = generateID();

      $sql = "INSERT INTO artygs SET  id = $aid,
        nome = '".$_POST['n']."',
        id_pap = '".$_POST['ap']."',
        publico = '".$_POST['p']."',
        data_criacao = now(),
        data_modificacao = now(),
        id_idioma = ".$_POST['iid'].",
        texto = '".str_replace("'",'"',$_POST['t'])."',
        id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";"; //$_SESSION['KondisonairUzatorDiom']  id_destino = 0, tipo_destino = '', (idioma,escrita,momento,entidade)
      mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));


    }
    
    mysqli_query($GLOBALS['dblink'],"DELETE FROM artyg_dest WHERE id_artyg = '".$aid."';") or die(mysqli_error($GLOBALS['dblink']));
    foreach($_POST['l'] as $link) {
      $l = explode('_',$link);
      $sql = "INSERT INTO artyg_dest SET id = ".generateID().", tipo_dest = '".$l[0]."', id_dest = ".$l[1].", id_artyg = ".$aid;
      mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
    }

    echo $aid;
    die();
  };

  if ($_GET['action'] == 'ajaxGetPainelMomento') {
    // GET tid = id em momentos
    $sql = "SELECT * FROM momentos WHERE id = ".$_GET['tid']." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";
    $result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
    $r = mysqli_fetch_assoc($result);

    echo '<div>
        <label class="control-label">'._t('Nome').'</label>
        <input type="text" class="form-control" id="nome_grupo" value="'.$r['nome'].'">
        <label class="control-label">'._t('Descrição').'</label>
        <input type="text" class="form-control" id="nome_grupo" value="'.$r['descricao'].'"><br>';
    $sql = "SELECT * FROM momentos WHERE id_superior = ".$_GET['tid']." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";
    $result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
    while($r = mysqli_fetch_assoc($result)){
      echo '<a href="#" onclick="abrirMomento('.$r['id'].')">'.$r['nome'].'</a><br>';
    };
    echo '</div>';

    die();
  };

  if ($_GET['action'] == 'ajaxGetLigacoesArtyg') {
    // GET tid = id em momentos
    $sql = "SELECT * FROM artyg_dest WHERE id_artyg = ".$_GET['aid']." AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";
    $result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
    $r = mysqli_fetch_assoc($result);

    echo '<div>';
    while($r = mysqli_fetch_assoc($result)){
      echo '<a href="#" onclick="('.$r['id_dest'].')">'.$r['tipo_dest'].'</a><br>';
    };
    echo '</div>';

    die();
  };
  
  if ($_GET['action'] == 'ajaxUpdateLinkArtigo') {

    if ($_GET['dest'] > 0 && $_GET['aid'] > 0){ 
      $tipo = $_GET['tipo']; // text, diom
      $dest = $_GET['dest']; // id do texto/idioma
      $art = $_GET['aid']; // id do artigo

      mysqli_query($GLOBALS['dblink'],"DELETE FROM artyg_dest WHERE tipo_dest = '".$tipo."' AND id_dest = ".$dest.";") or die(mysqli_error($GLOBALS['dblink']));

      $sql = "INSERT INTO artyg_dest SET id = ".generateId().", id_artyg = ".$art.", tipo_dest = '".$tipo."', id_dest = ".$dest.";";
      $result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

    }

    die('ok');
  };

  if ($_GET['action'] == 'importWordbank') {
    $num = 0;

    foreach ($_POST['pals'] as $ref){
      
        $pid = generateId();
        $sqlQuerys = "INSERT INTO palavras SET id = $pid,
            significado = '".str_replace("'",'"',$ref['sig'])."',
            romanizacao = '".str_replace("'",'"',$ref['rom'])."',
            pronuncia = \"".str_replace('"',"'",$ref['pron'])."\",
            detalhes = '',
            privado = '',
            id_uso = 0,
            id_forma_dicionario = '0',
            id_classe = 0,
            data_criacao = now(), data_modificacao = now(),
            id_usuario = ".$_SESSION['KondisonairUzatorIDX'].",
            id_idioma = ".$_GET['iid'].";";
        mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));

        // insert palavras_referentes
        mysqli_query($GLOBALS['dblink'],
        "INSERT INTO palavras_referentes SET id = ".generateId().", id_palavra = ".$pid.", id_referente = ".$ref['rid'].", obs = '';"
        ) or die(mysqli_error($GLOBALS['dblink']));

        if ($_GET['eid']>-1 && isset($ref['nat']))
          mysqli_query($GLOBALS['dblink'],
            "INSERT INTO palavrasNativas SET id = ".generateId().", id_palavra = ".$pid.", id_escrita = ".$_GET['eid'].", palavra = \"".$ref['nat']."\";"
            ) or die(mysqli_error($GLOBALS['dblink']));
        // insert nativa

        $num++;
    }
    
    echo $num;
    die();
  };

  if ($_GET['action'] == 'saveWordbank') { 
    if ($_GET['id']>0){
      //delete all
      $bid = $_GET['id'];
      mysqli_query($GLOBALS['dblink'],'DELETE FROM listas_referentes WHERE id_lista = '.$bid) or die('err: '.mysqli_error($GLOBALS['dblink']));
      mysqli_query($GLOBALS['dblink'],'UPDATE wordbanks SET titulo = "'.$_POST['titulo'].'" , data_modificacao = now() WHERE id = '.$bid.';') or die('err: '.mysqli_error($GLOBALS['dblink']));
    }else{
      //insert bank, pegar id 
      $bid = generateId();
      $s = mysqli_query($GLOBALS['dblink'],'INSERT INTO wordbanks SET id = '.$bid.', titulo = "'.$_POST['titulo'].'", id_usuario = '.$_SESSION['KondisonairUzatorIDX'].', data_criacao = now(), data_modificacao = now();') or die('err: '.mysqli_error($GLOBALS['dblink']));
    };

    $o = 1;
    $sql = 'INSERT INTO listas_referentes (id,id_referente, id_lista, ordem) VALUES ';
    foreach ($_POST['refs'] as $ref){
      // insert
      $sql .= '('.generateId().','.$ref.','.$bid.','.$o.'),';
      $o++;
    }

    $s = mysqli_query($GLOBALS['dblink'],substr($sql,0,-1)) or die('err: '.mysqli_error($GLOBALS['dblink']));

    // copiar mix do palavraflexionada com palavra

    die('ok');
  };

  if ($_GET['action'] == 'ajaxGetListaFontes') {
    $pessoais = '';
    $publicas = '';
    $fts = mysqli_query($GLOBALS['dblink'],"SELECT * FROM fontes WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX']." OR publica = 1;") or die(mysqli_error($GLOBALS['dblink']));
    while ($tf = mysqli_fetch_assoc($fts)){
      if ($tf['id_usuario'] == $_SESSION['KondisonairUzatorIDX']) 
        $pessoais .= '<div class="list-group-item">
                  <div class="row align-items-center">
                      <div class="col-auto">'.$tf['nome'].'</div>
                      <div class="col text-end">
                          <div class="text-secondary text-truncate mt-n1">
                              <a class="btn btn-danger btn-sm" onclick="apagarFonte(\''.$tf['id'].'\')">X</a>
                          </div>
                      </div>
                  </div>
              </div>';
      else
        $publicas .= '<div class="list-group-item">
                  <div class="row align-items-center">
                      <div class="col-auto">'.$tf['nome'].'</div>
                  </div>
              </div>';
      
        $contents .=  '<option value="'.$tf['id'].'" title="'.$tf['nome'].'"';
        if ($tf['id']==$e['id_fonte']) $contents .=  ' selected ';
        $contents .= '>'.$tf['nome'].'</option>';
    }
    if($pessoais.$publicas == '') die('Nenhuma fonte');
    echo '<div class="list-group list-group-flush list-group-hoverable">'.$pessoais.$publicas.'</div>';
    die();
  };

  if ($_GET['action'] == 'ajaxApagarFonte') {

    $fts = mysqli_query($GLOBALS['dblink'],"SELECT * FROM fontes WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX']." AND id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($fts) > 0){
        $e = mysqli_fetch_assoc($fts);
        $usos = mysqli_query($GLOBALS['dblink'],"SELECT * FROM escritas WHERE id_fonte = ".$e['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        if (mysqli_num_rows($usos) > 0) die('Fonte em uso.');
        mysqli_query($GLOBALS['dblink'],"DELETE FROM fontes WHERE id = ".$e['id'].";") or die(mysqli_error($GLOBALS['dblink']));
        unlink('fonts/'.$e['arquivo']);
        die("ok");
    }
    die("invalid");
  };

  if ($_GET['action']=='ajaxApagarNivel') {
    if ($_GET['unsetWords'] == '1'){
      mysqli_query($GLOBALS['dblink'],"UPDATE palavras SET id_uso = 0 WHERE id_uso = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    }else{
      $words = mysqli_query($GLOBALS['dblink'],"SELECT id FROM palavras WHERE id_uso = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
      $words = mysqli_num_rows($words);
      if ($words > 0) {
        echo $words; die();
      } 
    }
    mysqli_query($GLOBALS['dblink'],"DELETE FROM nivelUsoPalavra WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
  };

}else{
  
  if ($_GET['action']=='ajaxGetLigacoesArtyg')  die('not_user');
  if ($_GET['action']=='fleksons')  die('not_user');
  if ($_GET['action']=='ajaxGravarPerfyl')  die('not_user');
  if ($_GET['action'] == 'ajaxSalvArtyg')  die('not_user');
  if ($_GET['action'] == 'ajaxGetPainelMomento')  die('not_user');
  if ($_GET['action'] == 'ajaxJoes')  die('not_user');
  if ($_GET['action']=='ajaxUzatorrCheck') die('not_user');
  if ($_GET['action']=='ajaxUzatorrMdason') die('not_user');
  if ($_GET['action']=='ajaxGlifoRegra') die('not_user');
  if ($_GET['action']=='ajaxGlifoAcima') die('not_user');
  if ($_GET['action']=='ajaxSetEscritaPadrao') die('not_user');
  if ($_GET['action']=='ajaxApagarRegra') die('not_user');
  if ($_GET['action']=='ajaxRegraAcima') die('not_user');
  if ($_GET['action']=='ajaxRegraAbaixo') die('not_user');
  if ($_GET['action']=='ajaxSalvarFonte') die('not_user');
  if ($_GET['action']=='ajaxGravarIdioma') die('not_user');
  if ($_GET['action']=='ajaxGravarRealidade') die('not_user');
  if ($_GET['action']=='getLikeButton') die('<a class="btn btn-default disabled">Seguir</a>');
  if ($_GET['action']=='ajaxGravarRegra') die('not_user');
  if ($_GET['action']=='ajaxSalvarListaSC') die('not_user');
  if ($_GET['action']=='ajaxUpdateIpids') die('not_user');
  if ($_GET['action']=='ajaxCarregarTimeline') die('not_user');
  if ($_GET['action']=='ajaxGravarItem') die('not_user');
  if ($_GET['action']=='ajaxGravarReferente') die('not_user');
  if ($_GET['action']=='ajaxGravarPalavra') die('not_user');
  if ($_GET['action']=='ajaxGravarNivel')  die('not_user');
  if ($_GET['action']=='ajaxGravarClasse') die('not_user');
  if ($_GET['action']=='ajaxGravarOpcao')  die('not_user');
  if ($_GET['action']=='ajaxGravarConcordancia') die('not_user');
  if ($_GET['action']=='getChecarPronuncia') die('not_user');
  if ($_GET['action']=='getAllGlifos') die('not_user');
  if ($_GET['action']=='getAutoSubstituicao') die('not_user');
  if ($_GET['action']=='ajaxGravarSintazBazic') die('not_user');
  if ($_GET['action']=='ajaxGravarReferentes') die('not_user');
  if ($_GET['action']=='ajaxGravarOrigens') die('not_user');
  if ($_GET['action'] == 'salvarPalavraNativa') die('not_user');
  if ($_GET['action'] == 'ajaxGravarOpcaoPadrao')  die('not_user');
  if ($_GET['action'] == 'ajaxApagarListaSC') die('not_user');
  if ($_GET['action'] == 'ajaxImportarListaPalavrasDicionario')  die('not_user');
  if ($_GET['action'] == 'ajaxImportarListaDicionario')  die('not_user');
  if ($_GET['action'] == 'ajaxResetarRegras')  die('not_user');
  if ($_GET['action'] == 'carregarEdicaoSons')  die('not_user');
  if ($_GET['action'] == 'carregarMoverSom')  die('not_user');
  if ($_GET['action'] == 'ajaxAdicionarDimensao')  die('not_user');
  if ($_GET['action'] == 'ajaxApagarDimensao')  die('not_user');
  if ($_GET['action'] == 'ajaxEstruturaSilabica')  die('not_user');
  if ($_GET['action'] == 'adicionarComponenteSilaba')  die('not_user');
  if ($_GET['action'] == 'removerComponenteSilaba')  die('not_user');
  if ($_GET['action'] == 'carregarTabelaAlfabeto')  die('not_user');
  if ($_GET['action'] == 'ajaxEditarSom')  die('not_user');
  if ($_GET['action'] == 'adicionarCategoriaSom')  die('not_user');
  if ($_GET['action'] == 'carregarEdicaoCelula') die('not_user');
  if ($_GET['action'] == 'carregarEdicaoIPACelula')  die('not_user');
  if ($_GET['action'] == 'ajaxEditarSomIPA')  die('not_user');
  if ($_GET['action'] == 'ajaxEditarTeclaIpa')  die('not_user');
  if ($_GET['action'] == 'ajaxEditarAutosubstituicao')  die('not_user');
  if ($_GET['action'] == 'autoCompletarFlexoes') die('not_user');
  if ($_GET['action'] == 'salvarPalavraFlexionada')  die('not_user');
  if ($_GET['action'] == 'novaPalavraFlexionada')  die('not_user');
  if ($_GET['action'] == 'carregarEdicaoAlfabeto') die('not_user');
  if ($_GET['action'] == 'toggleSonsAdicionaveis')  die('not_user');
  if ($_GET['action'] == 'salvarFlexao')  die('not_user');
  if ($_GET['action'] == 'ajaxApagarPalavras') die('not_user');
  if ($_GET['action'] == 'ajaxRenomearDimensao') die('not_user');
  if ($_GET['action'] == 'carregarTabelaFlexoes') die('not_user');
  if ($_GET['action'] == 'ajaxCriarSomPersonalizado') die('not_user');
  if ($_GET['action'] == 'ajaxCriarSomPersonalizado2') die('not_user');
  if ($_GET['action'] == 'ajaxSelectRegras')   die('not_user');
  if ($_GET['action'] == 'getDetalhesRegra')   die('not_user');
  if ($_GET['action'] == 'listarRegras')  die('not_user');
  if ($_GET['action'] == 'ajaxSalvarEscrita')  die('not_user');
  if ($_GET['action'] == 'ajaxCarregarTabelaAlfabeto')  die('not_user');
  if ($_GET['action'] == 'ajaxReordenarOrigemPalavra')  die('not_user');
  if ($_GET['action'] == 'ajaxAddCaractereEscrita')  die('not_user');
  if ($_GET['action'] == 'ajaxAddDrawCaractereEscrita')  die('not_user');
  if ($_GET['action'] == 'ajaxSgison')  die('not_user');
  if ($_GET['action'] == 'ajaxCavMdason')  die('not_user');
  
};

/*
  FIM DAS AÇÕES DE EDIÇÃO (PARA APENAS LOGADO) - COMEÇO DAS AÇÕES PÚBLICAS
*/

if ($_GET['action'] == 'listPhrases') {
  $id_idioma = 0; $id_usuario = 0; $dono = false;
  if ($_GET['uid'] > 0) $id_usuario = $_GET['uid'] ?: 0;
  if ($_GET['iid'] > 0) {
    $id_idioma = $_GET['iid'];
    $en = mysqli_query($GLOBALS['dblink'],"SELECT e.*, f.arquivo as fonte, i.id_usuario as dono FROM escritas e 
                    LEFT JOIN idiomas i ON i.id = e.id_idioma
                    LEFT JOIN fontes f ON f.id = e.id_fonte
                    WHERE e.id_idioma = $id_idioma AND e.padrao = 1;") or die(mysqli_error($GLOBALS['dblink']));
    if(mysqli_num_rows($en)>0){
        $en = mysqli_fetch_assoc($en);
        $escrita = $en['id'];
        $id_fonte = $en['id_fonte'];
        $tamanho = $en['tamanho'];
        $dono = $en['dono']==$id_usuario;
    }else { $escrita = 0; $fonte = 'notosans';$id_fonte = 0; $tamanho = '';}
  }

  $escrita_original = '';
  $id_fonte_original = '';
  $tamanho_original = '';

  if ($_GET['palavra']>0) $filtroPalavra = " AND f.frase LIKE '%".$_GET['palavra']."%' ";
  if($dono || $id_idioma > 0){
      // apenas listagem de top frases do idioma
      $sql = "SELECT f.*, e.id as eid, e.tamanho, e.id_fonte as fonte FROM frases f 
        LEFT JOIN escritas e ON e.id_idioma = f.id_idioma AND e.padrao = 1
        WHERE f.id_idioma = $id_idioma $filtroPalavra ORDER BY RAND() LIMIT 100";
  }else if($id_usuario > 0){
      // apenas listagem de frases do usuario - edit se é o logado
      $sql = "SELECT f.*, e.id as eid, e.tamanho, e.id_fonte as fonte FROM frases f 
        LEFT JOIN escritas e ON e.id_idioma = f.id_idioma AND e.padrao = 1
        WHERE f.id_criador = $id_usuario $filtroPalavra ORDER BY RAND() LIMIT 100";
  }else {
      if ($_GET['palavra']>0) $filtroPalavra = " WHERE f.frase LIKE '%".$_GET['palavra']."%' ";
      //lista random top frases
      //add select pra idiomas
      $sql = "SELECT f.*, e.id as eid, e.tamanho, e.id_fonte as fonte FROM frases f 
        LEFT JOIN escritas e ON e.id_idioma = f.id_idioma AND e.padrao = 1
        $filtroPalavra ORDER BY RAND() LIMIT 100";
  }
  //echo $sql;
  $data = [];
  $result = mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
  while ($r = mysqli_fetch_assoc($result)) {
      $r['frase'] = getSpanPalavraNativa($r['frase'],$r['eid'],$r['fonte'],$r['tamanho']);
      /*if ($r['id_original'] > 0) {
          $r['original'] = getSpanPalavraNativa($r['frase2'],$r['eid2'],$r['fonte2'],$r['tamanho2']);
          $r['idioma_original'] = 'Japonês';
      }
      */
      //if($r['traducao']) $r['traducao'].' - '.$r['idioma_traducao']; // trad idioma do usuario, se houver trad nele
      $data[] = $r;
  }

  echo json_encode($data);
  exit();
}
  
if ($_GET['action']=='getSCHeader') {
  echo getSCHeader($_GET['motor'],$_GET['iid'],$_GET['tipo']); 
  die();
};

if ($_GET['action']=='ajaxBuscaGeral') { //xxxxx

  $data = [];
  if (!chottomatte($timerzinho)) die(json_encode(['wait' => $timerzinho])); 

  // Inicializa o array de resultados
  $data = [];
  $search_term = trim($_GET['t'] ?? '');
  $where_conditions = [];
  $params = [];
  $param_types = '';

  // Processa o termo de busca para verificar filtros específicos
  $filter = null;
  $keyword = $search_term;

  $iids = mysqli_query($GLOBALS['dblink'],"SELECT nome_legivel FROM idiomas WHERE publico = 1;");
  $idiomas = [];
  while ($row = mysqli_fetch_assoc($iids)) {
      $idiomas[] = $row['nome_legivel'];
  }

  if (preg_match('/^('._t('desde').'|'._t('até').'|'._t('usuario').'|'._t('idioma').'|'._t('palavra').'):(.+)/i', $search_term, $matches)) {
      $filter = strtolower($matches[1]);
      $keyword = trim($matches[2]);
  } else if (preg_match('/^([^:]*):?(.*)$/', $search_term, $matches)) {
      if(in_array($matches[1], $idiomas)){
        $filter = $matches[1];
        $keyword = trim($matches[2]);
      }
  }

  // Escapa o termo de busca
  $keyword = mysqli_real_escape_string($GLOBALS['dblink'], $keyword);

  // Base da query com UNION para combinar as três tabelas
  $sql = "
      SELECT 'usuario' AS tipo, username AS title, 'Usuário' AS subtitle, 
            CONCAT('?page=profile&user=', username) AS url, data_cadastro as data_modificado
      FROM usuarios
      WHERE publico = 1 AND username LIKE ?
      UNION ALL
      SELECT 'idioma' AS tipo, nome_legivel AS title, 'Idioma' AS subtitle, 
            CONCAT('?page=language&iid=', id) AS url, data_modificacao as data_modificado
      FROM idiomas
      WHERE publico = 1 AND nome_legivel LIKE ?
      UNION ALL
      SELECT 'palavra' AS tipo, COALESCE(p.romanizacao, p.pronuncia) AS title, 
            CONCAT(i.nome_legivel, ': ', p.significado) AS subtitle, 
            CONCAT('?page=word&pid=', p.id) AS url, p.data_modificacao as data_modificado
      FROM palavras p
      LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id
      LEFT JOIN idiomas i ON p.id_idioma = i.id
      WHERE i.publico = 1 
        AND (p.romanizacao LIKE ? OR p.pronuncia LIKE ? OR p.significado LIKE ? OR pn.palavra LIKE ?)
  ";

  // Prepara os parâmetros iniciais
  $like_term = "%$keyword%";
  $params = array_fill(0, 6, $like_term);
  $param_types = str_repeat('s', 6);

  // Aplica filtros específicos
  if ($filter) {
      if (in_array($filter, $idiomas)) {
          // Caso o filtro seja um idioma, buscar apenas palavras desse idioma
          $sql = "
              SELECT 'palavra' AS tipo, COALESCE(p.romanizacao, p.pronuncia) AS title, 
                    CONCAT(i.nome_legivel, ': ', p.significado) AS subtitle, 
                    CONCAT('?page=word&pid=', p.id) AS url, p.data_modificacao AS data_modificado
              FROM palavras p
              LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id
              LEFT JOIN idiomas i ON p.id_idioma = i.id
              WHERE i.publico = 1 AND i.nome_legivel = ?
                AND (p.romanizacao LIKE ? OR p.pronuncia LIKE ? OR p.significado LIKE ? OR pn.palavra LIKE ?)
          ";
          $params = [$filter, $like_term, $like_term, $like_term, $like_term];
          $param_types = 'sssss';
      } else switch ($filter) {
          case _t('desde'):
              $sql .= " AND data_modificado >= ?";
              $params[] = $keyword;
              $param_types .= 's';
              break;
              
          case _t('ate'):
              $sql .= " AND data_modificado <= ?";
              $params[] = $keyword;
              $param_types .= 's';
              break;
              
          case _t('usuario'):
              // Filtra apenas usuários e seus idiomas
              $sql = "
                  SELECT 'usuario' AS tipo, username AS title, 'Usuário' AS subtitle, 
                        CONCAT('?page=profile&user=', username) AS url, data_cadastro as data_modificado
                  FROM usuarios
                  WHERE publico = 1 AND username LIKE ?
                  UNION ALL
                  SELECT 'idioma' AS tipo, nome_legivel AS title, 'Idioma' AS subtitle, 
                        CONCAT('?page=language&iid=', idiomas.id) AS url, idiomas.data_modificacao as data_modificado
                  FROM idiomas
                  JOIN usuarios ON idiomas.id_usuario = usuarios.id
                  WHERE idiomas.publico = 1 AND usuarios.username LIKE ?
              ";
              $params = [$like_term, $like_term];
              $param_types = 'ss';
              break;
              
          case _t('idioma'):
              // Filtra apenas idiomas e suas palavras
              $sql = "
                  SELECT 'idioma' AS tipo, nome_legivel AS title, 'Idioma' AS subtitle, 
                        CONCAT('?page=language&iid=', id) AS url, data_modificacao as data_modificado
                  FROM idiomas
                  WHERE publico = 1 AND nome_legivel LIKE ?
              ";
              $params = [$like_term];
              $param_types = 's';
              break;
              
          case _t('palavra'):
              // Filtra apenas palavras
              $sql = "
                  SELECT 'palavra' AS tipo, COALESCE(p.romanizacao, p.pronuncia) AS title, 
                        CONCAT(i.nome_legivel, ': ', p.significado) AS subtitle, 
                        CONCAT('?page=word&pid=', p.id) AS url, p.data_modificacao as data_modificado
                  FROM palavras p
                  LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id
                  LEFT JOIN idiomas i ON p.id_idioma = i.id
                  WHERE i.publico = 1 
                    AND (p.romanizacao LIKE ? OR p.pronuncia LIKE ? OR p.significado LIKE ? OR pn.palavra LIKE ?)
              ";
              $params = array_fill(0, 4, $like_term);
              $param_types = 'ssss';
              break;
      }
  }

  // Adiciona ordenação por data_modificado
  $sql .= " ORDER BY data_modificado DESC";

  // Prepara e executa a consulta
  $stmt = mysqli_prepare($GLOBALS['dblink'], $sql);
  if ($stmt) {
      mysqli_stmt_bind_param($stmt, $param_types, ...$params);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      
      // Processa os resultados
      while ($u = mysqli_fetch_assoc($result)) {
          $data[] = [
              'title' => $u['title'],
              'subtitle' => $u['subtitle'],
              'url' => $u['url']
          ];
      }
      
      mysqli_stmt_close($stmt);
  } else {
      die(mysqli_error($GLOBALS['dblink']));
  }

  // Retorna os resultados em JSON
  echo json_encode($data);
  die();
};

if ($_GET['action']=='ajaxTraduzir') { //xxxxx

  if (!chottomatte($timerzinho)) die(json_encode(['wait' => $timerzinho]));
  
  traduzirTexto($_GET['o'],$_GET['d'],$_POST['texto'],$_POST['entrada'],$_POST['nat'],$_POST['pr'],$_POST['gl']);
  die();
};

if ($_GET['action'] == 'ajaxCarregarListaSC') {

  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM soundChanges 
    WHERE id = ".$_GET['id']." AND (publico = 1 OR id_usuario = ".$_SESSION['KondisonairUzatorIDX'].");") or die(mysqli_error($GLOBALS['dblink']));

  $rows = array();
  $r = mysqli_fetch_assoc($result);
  $rows[] = $r;

  print json_encode($rows);

  die();
};

if ($_GET['action'] == 'ajaxCarregarListaPalavras') {

  // id: 
  // t: escrita_nativa, pronuncia, romanizacao

  if ($_GET['id']=='g1') die('');
  else if ($_GET['id']=='g2') 
    $q = "SELECT p.pronuncia, p.romanizacao 
        FROM palavras p WHERE p.id_idioma = ".$_GET['iid']." AND p.id_forma_dicionario = 0;";
  else if ($_GET['id']=='g3') 
    $q = "SELECT p.pronuncia, p.romanizacao FROM palavras p 
        WHERE p.id_idioma = ".$_GET['iid'].";";
  else{
    $iid = substr($_GET['id'],1);
    $tipo = substr($_GET['id'],0,1);
    if ($tipo == 'n'){
      // nivel de uso
      $q = "SELECT p.pronuncia, p.romanizacao FROM palavras p 
          WHERE p.id_idioma = ".$_GET['iid']." AND p.id_uso = $iid;";
    }else if ($tipo == 'k'){
      // classe
      $q = "SELECT p.pronuncia, p.romanizacao FROM palavras p 
          WHERE p.id_idioma = ".$_GET['iid']." AND p.id_classe = $iid;";
    }else{
      die();
    }

  }


  $result = mysqli_query($GLOBALS['dblink'],$q) or die(mysqli_error($GLOBALS['dblink']));
  while($r = mysqli_fetch_assoc($result)){
    echo $r[$_GET['t']]."\n";
  };
  die();
};

function getLastChange($tipo,$id = null) { // lastupdated

  if($tipo=='lexicon'){ // incluir writing e drawchars
      $last = mysqli_query($GLOBALS['dblink'],
        "SELECT DATE_FORMAT(data_modificacao, '%Y%m%d%H%i%s') as d 
              FROM palavras WHERE id_idioma = ".$id."
            UNION
            SELECT DATE_FORMAT(data_modificacao, '%Y%m%d%H%i%s') as d 
              FROM escritas WHERE id_idioma = ".$id."
            UNION
            SELECT DATE_FORMAT(data_modificado, '%Y%m%d%H%i%s') as d 
              FROM drawChars WHERE id_escrita IN (SELECT id FROM escritas WHERE id_idioma=".$id.")
            ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;
  }else if($tipo=='ref'){

      $last = mysqli_query($GLOBALS['dblink'],
        "SELECT DATE_FORMAT(modificado, '%Y%m%d%H%i%s') as d 
              FROM referentes
            ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;

  }else if($tipo=='entities'){ //xxxxx falta

      $last = mysqli_query($GLOBALS['dblink'],
        "SELECT DATE_FORMAT(data_modificacao, '%Y%m%d%H%i%s') as d 
              FROM entidades WHERE id_realidade = ".$id."
            ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;


  }else if($tipo=='characters'){ //xxxxx falta

      $last = mysqli_query($GLOBALS['dblink'],
        "SELECT DATE_FORMAT(data_modificacao, '%Y%m%d%H%i%s') as d 
              FROM entidades WHERE id_realidade = ".$id." AND rule = 'character'
            ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;


  }else if($tipo=='items'){ //xxxxx falta

      $last = mysqli_query($GLOBALS['dblink'],
        "SELECT DATE_FORMAT(data_modificacao, '%Y%m%d%H%i%s') as d 
              FROM entidades WHERE id_realidade = ".$id." AND rule = 'item'
            ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;


  }else if($tipo=='places'){ //xxxxx falta

      $last = mysqli_query($GLOBALS['dblink'],
        "SELECT DATE_FORMAT(data_modificacao, '%Y%m%d%H%i%s') as d 
              FROM entidades WHERE id_realidade = ".$id." AND rule = 'place'
            ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;

  }else if($tipo=='autosubstituicoes'){

      $last = mysqli_query($GLOBALS['dblink'],
        "SELECT DATE_FORMAT(data_modificacao, '%Y%m%d%H%i%s') as d 
              FROM autosubstituicoes WHERE id_escrita = '".$id."'
            ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;
  }else if($tipo=='moments'){ //xxxxx falta

        $rid = (int)$id;
        // Buscar timestamp da última alteração na tabela momentos
        $result = mysqli_query($GLOBALS['dblink'], "SELECT MAX(updated_at) as last_change 
            FROM momentos 
            WHERE id_realidade = $rid;") or die(mysqli_error($GLOBALS['dblink']));
        
        $row = mysqli_fetch_assoc($result);
        $timestamp = strtotime($row['last_change']) ?: time();
        return $timestamp;

  }else if($tipo=='origens'){

      // geral
      return '1';

  }else if($tipo=='calendar'){
      $cid = (int)$id;
      $result = mysqli_query($GLOBALS['dblink'], "SELECT GREATEST(
          COALESCE((SELECT MAX(updated_at) FROM time_systems WHERE id = $cid), 0),
          COALESCE((SELECT MAX(updated_at) FROM time_units WHERE id_time_system = $cid), 0),
          COALESCE((SELECT MAX(updated_at) FROM time_cycles WHERE id_time_system = $cid), 0),
          COALESCE((SELECT MAX(updated_at) FROM time_names WHERE id_time_system = $cid), 0)
      ) as last_change;") or die(mysqli_error($GLOBALS['dblink']));
      
      //, COALESCE((SELECT MAX(updated_at) FROM time_adjustment_rules WHERE id_time_system = $cid), 0)
      
      $row = mysqli_fetch_assoc($result);
      $timestamp = strtotime($row['last_change']) ?: time(); 
      return $timestamp ? $timestamp : 0;

  }else if($tipo=='fonts'){

      $result = mysqli_query($GLOBALS['dblink'], "SELECT id
            FROM fontes ORDER BY id DESC LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
        
      $row = mysqli_fetch_assoc($result);
      return $row['id'] ? $row['id'] : 0;

  }else if($tipo=='sounds'){  //xxxxx falta em editsounds ???
      $last = mysqli_query($GLOBALS['dblink'],
      "SELECT DATE_FORMAT(data_modificado, '%Y%m%d%H%i%s') as d 
            FROM inventarios WHERE id_idioma = ".$id." 
          ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;
  }else if($tipo=='glifos'){ 
      $last = mysqli_query($GLOBALS['dblink'],
      "SELECT DATE_FORMAT(data_modificado, '%Y%m%d%H%i%s') as d 
            FROM glifos WHERE id_escrita = ".$id." 
          ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;
  }else if($tipo=='writing'){ //xxxxx falta ?
      $last = mysqli_query($GLOBALS['dblink'],
      "SELECT DATE_FORMAT(data_modificacao, '%Y%m%d%H%i%s') as d 
            FROM escritas WHERE id = ".$id."
          UNION
          SELECT DATE_FORMAT(data_modificado, '%Y%m%d%H%i%s') as d 
            FROM glifos WHERE id_escrita = ".$id."
          UNION
          SELECT DATE_FORMAT(data_modificado, '%Y%m%d%H%i%s') as d 
            FROM drawChars WHERE id_escrita = ".$id."
          ORDER BY d DESC LIMIT 1") or die(mysqli_error($GLOBALS['dblink']));
      $l = mysqli_fetch_assoc($last);
      return $l['d'] ? $l['d'] : 0;
  }

  return 0;
};

if ($_GET['action'] == 'getLastChange') { // lastupdated
  echo getLastChange($_GET['data'], $_GET['iid'] || $_GET['rid'] || $_GET['cid'] || $_GET['eid'] || null);
  die();
};

if ($_GET['action'] == 'listarIndiceCaracteres') {

  // GET o = ordem (por rom, pron ou id escrita)
  // GET to = tipo ordem (pc:primeiro char, uc ultimo, nc, num. chars)
  $o = $to = 0;
  if(isset($_GET['to'])) $to = $_GET['to'];
  if(isset($_GET['o'])) $o = $_GET['o'];
  //if(isset($_GET['iid'])) $iid = $_GET['iid']; else die('novalered');

  $filter = 'nc';

  if ($o == 'rom'){
    $itipo = 'Romanização'; 
    if($to=='uc') $filter = 'rf'; else $filter = 'ri';   
    echo '<h4 >Índice</h4> 
      <a  class="indexDic" onclick="pularPara(\''.$filter.':a|\')">A</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':b|\')">B</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':c|\')">C</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':d|\')">D</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':e|\')">E</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':f|\')">F</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':g|\')">G</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':h|\')">H</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':i|\')">I</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':j|\')">J</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':k|\')">K</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':l|\')">L</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':m|\')">M</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':n|\')">N</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':o|\')">O</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':p|\')">P</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':q|\')">Q</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':r|\')">R</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':s|\')">S</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':t|\')">T</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':u|\')">U</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':v|\')">V</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':w|\')">W</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':x|\')">X</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':y|\')">Y</a><br>
      <a  class="indexDic" onclick="pularPara(\''.$filter.':z|\')">Z</a><br>
    ';

  }else if ($o == 'pron'){
    $itipo = 'Pronúncia';
    // ordem IPA ??????
  }else if ($o > 0){
    $itipo = 'Alfabeto*';
    if($to=='uc') $filter = 'nf'; else $filter = 'ni';
    echo '<h4 >Índice</h4> <br>';

    $query = "SELECT * FROM glifos WHERE id_escrita = ".$o." ORDER BY ordem;"; // AND id_principal = 0  // verificar outras ordens se tiver metadados
    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));

    while($r = mysqli_fetch_assoc($result)){
      echo '<a  class="indexDic custom-font-'.$o.'" onclick="pularPara(\''.$filter.':'.$r['glifo'].'|\')">'.$r['glifo'].'</a><br>';
    }
  }else {
    echo 'err dtyp dfyltr'; die();
  }

  // usar no carregar dicionario: carregar coluna oculta com primeira/ultima lettra/num caracteres, e essa será o filtro
  if ($to == 'pc' || $to == 'uc'){
    // os glifos/letras
  }else if ($to == 'nc'){
    // números de caracteres (???)
  }else {
    echo 'err dtyp dfyltr'; die();
  }


  
  
  echo '<a  class="indexDic" onclick="pularPara(\'\')">Tudo</a><br>';

  die();
};

if ($_GET['action'] == 'simpleListWords') { // LIST WORDS in public page language // TESTING JSON

  $escrita = $_GET['eid'];
  $id_idioma = $_GET['iid'];
  $ordem = 'p.pronuncia ';
  $firstch = 'pronuncia';
  if ($escrita > 0) {

      $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM escritas WHERE id = ".$escrita) or die(mysqli_error($GLOBALS['dblink']));  $r = mysqli_fetch_assoc($result);
      $id_fonte = $r['id_fonte'];
      $tamanho = $r['tamanho'];
      
      $ordem = 'palavra '; $firstch = 'palavra';
      $nativoPal = " (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita." limit 1) AS palavra,";
  }
  else if ($romanizacao > 0) {
      $ordem = 'p.romanizacao '; $firstch = 'romanizacao';
      $nativoPal = '';
  } 

  $query = "SELECT p.id, p.pronuncia, p.romanizacao, p.significado,
      c.nome AS classe, g.gloss AS cgl, ".$nativoPal."
      (SELECT GROUP_CONCAT(pronuncia, ',', significado, ',', romanizacao, ',', palavra SEPARATOR ',') 
          FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
      WHERE ep.id_forma_dicionario = p.id) as extras 
  FROM palavras p 
      LEFT JOIN classes c ON p.id_classe = c.id 
      LEFT JOIN glosses g ON c.id_gloss = g.id 
  WHERE p.id_idioma = ".$id_idioma." AND p.id_forma_dicionario = 0 AND p.publico = 1 order by ".$ordem." ;";
  
  $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 

  $rows = array();
      
  $inicialAnterior = '';
  while($r = mysqli_fetch_assoc($result)){
      $inicialAtual = substr( iconv('UTF-8', 'ASCII//TRANSLIT', $r[$firstch]),0,1); //mb_substr($r[$firstch],0,1);
      if ($inicialAnterior != $inicialAtual) {
          $r['inicial'] = $inicialAtual;
          $inicialAnterior = $inicialAtual;
      };
      $r['nativo'] = getSpanPalavraNativa($r['palavra'],$escrita,$id_fonte,$tamanho);
      $rows[] = $r;
  };

  print json_encode($rows);
  die();
};

if ($_GET['action'] == 'simpleListWords') { // LIST WORDS in public page language

  $escrita = $_GET['eid'];
  $id_idioma = $_GET['iid'];
  $ordem = 'p.pronuncia ';
  $firstch = 'pronuncia';


  if ($escrita > 0) {
      $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM escritas WHERE id = ".$escrita) or die(mysqli_error($GLOBALS['dblink']));  $r = mysqli_fetch_assoc($result);
      $id_fonte = $r['id_fonte'];
      $tamanho = $r['tamanho'];
      
      $ordem = 'palavra '; $firstch = 'palavra';
      //$joinNativo = "LEFT JOIN palavrasNativas n ON ( n.id_palavra = p.id AND n.id_escrita = ".$escrita.")";
      $nativoPal = " (SELECT n.palavra FROM palavrasNativas n WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita." limit 1) AS palavra,";
  }
  else if ($romanizacao > 0) {
      $ordem = 'p.romanizacao '; $firstch = 'romanizacao';
      $nativoPal = '';
  } 

  $indice = 'a';

  $query = "SELECT p.*, c.nome AS classe, g.gloss AS cgl, ".$nativoPal."
      (SELECT GROUP_CONCAT(pronuncia, ',', significado, ',', romanizacao, ',', palavra SEPARATOR ',') 
          FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
      WHERE ep.id_forma_dicionario = p.id) as extras_palavras 
  FROM palavras p 
      LEFT JOIN classes c ON p.id_classe = c.id 
      LEFT JOIN glosses g ON c.id_gloss = g.id 
  WHERE p.id_idioma = ".$id_idioma." AND p.id_forma_dicionario = 0 AND p.publico = 1 order by ".$ordem." ;";

  //echo $query;
  
  $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
      
  $inicialAnterior = '';
  while($r = mysqli_fetch_assoc($result)){
      $inicialAtual = substr( iconv('UTF-8', 'ASCII//TRANSLIT', $r[$firstch]),0,1); //mb_substr($r[$firstch],0,1);
      if ($inicialAnterior != $inicialAtual) {
          echo '<div class="list-group-header sticky-top">'.$inicialAtual.'</div>';
          $inicialAnterior = $inicialAtual;
      };

      /*
      $indice = // '6-5-0-0-12'; //nativo inicial/final, romaniz inic/fin, num.chars,
          'ni:'.substr($r['palavra'],0,1).'|nf:'.substr($r['palavra'],strlen($r['palavra'])-1,strlen($r['palavra'])-1).
          '|ri:'.substr($r['romanizacao'],0,1).'|rf:'.substr($r['romanizacao'],strlen($r['romanizacao'])-1,strlen($r['romanizacao'])-1).
          '|nc:'.strlen($r['palavra']).'|';
          
      $indexdata = 'data-ni="'.substr($r['palavra'],0,1).'" data-nf="'.substr($r['palavra'],strlen($r['palavra'])-1,strlen($r['palavra'])-1).
      '" data-ri="'.substr($r['romanizacao'],0,1).'" data-rf="'.substr($r['romanizacao'],strlen($r['romanizacao'])-1,strlen($r['romanizacao'])-1).
      '" data-nc="'.strlen($r['palavra']).'" ';
      */

      // <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasSigCom" role="button" aria-controls="offcanvasStart"></a>
      //$nativo = " ";
      //if($escrita>0)
      
      $nativo = getSpanPalavraNativa($r['palavra'],$escrita,$id_fonte,$tamanho);
          
      $searchField = $r['palavra'].' '.$r['pronuncia'].' '.$r['significado'].' '.$r['romanizacao'];

      if ($romanizacao==1) {
          if ($nativo=="<span class='custom-font-".$escrita."' ></span>") {
              if($r['romanizacao']!='') $nativo = "<span class='text-secondary' >".$r['romanizacao']."</span> ";
              else $nativo = "<span class='text-secondary' >/".$r['pronuncia']."/</span> ";
              //$nativo = "<span class='text-secondary'>".$r['romanizacao'].'</span> ';
          }
          echo '<div data-search="'.$searchField.'" class="list-group-item divWord" '.$indexdata.'><div class="row">
              <div class="col-auto">
              <a data-bs-toggle="offcanvas" href="#offcanvasHelperPanel" role="button" aria-controls="offcanvasStart" onclick="abrirSig('.$r['id'].')">'.$nativo.' </a>
              </div>
              <div class="col text-truncate">
              <a data-bs-toggle="offcanvas" href="#offcanvasHelperPanel" role="button" aria-controls="offcanvasStart" onclick="abrirSig('.$r['id'].')" class="text-body d-block">'.$r['significado'].'</a> 
              </div>
          </div></div>'; // <div class="text-secondary text-truncate mt-n1">'.$r['romanizacao'].' /'.$r['pronuncia'].'/</div>  <div class="text-secondary text-truncate mt-n1">'.$r['classe'].'</div>

      }else{ 
          if ($nativo=="<span class='custom-font-".$escrita."' ></span>") $nativo = "<span class='text-secondary'>".$r['pronuncia'].'</span> ';
          echo '<div data-search="'.$searchField.'" class="list-group-item divWord" '.$indexdata.'><div class="row">
              <div class="col-auto">
              <a data-bs-toggle="offcanvas" href="#offcanvasHelperPanel" role="button" aria-controls="offcanvasStart" onclick="abrirSig('.$r['id'].')">'.$nativo.' </a>
              </div>
              <div class="col text-truncate">
              <a data-bs-toggle="offcanvas" href="#offcanvasHelperPanel" role="button" aria-controls="offcanvasStart" onclick="abrirSig('.$r['id'].')" class="text-body d-block">'.$r['significado'].'</a>
              </div>
          </div></div>'; //<div class="text-secondary text-truncate mt-n1">/'.$r['pronuncia'].'/</div>  <div class="text-secondary text-truncate mt-n1">'.$r['classe'].'</div>
      }

  };

  
  die();
};

if ($_GET['action'] == 'listarNiveis') {
  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM nivelUsoPalavra WHERE id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
  while($r = mysqli_fetch_assoc($result)){
    echo "<div id='row_".$r['id']."' class='divN list-group-item'>
      <div class='col-auto' onClick='abrirNivel(\"".$r['id']."\",\"".$r['titulo']."\",\"".$r['descricao']."\")'>".$r['titulo']."  
      <a class='btn btn-sm btn-danger btn-rounded' onClick='apagarNivel(\"".$r['id']."\")'>X</a></div>
    </div>";
  };
    die();
};

if ($_GET['action'] == 'listarGlosses') {
  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glosses;") or die(mysqli_error($GLOBALS['dblink']));
  echo '<table id="tabelaPalavras" data-ride="datatables" class="table table-m-b-none">
        <thead><tr><th>Nome</th></tr></thead><tbody>';
  while($r = mysqli_fetch_assoc($result)){

    echo "<tr id='row_".$r['id']."'><td onClick='abrirPalavra(\"".$r['id']."\")'>".$r['gloss']." - ".$r['descricao'].
      "<a class='btn btn-xs btn-info btn-rounded pull-right' onClick='apagarOpcao(\"".$r['id']."\")'>X</a></td></tr>";
  };
  echo '</tbody></table><script>$("#tabelaPalavras").DataTable({
          paging: false,
          "scrollY": "500px",
          "scrollCollapse": true 
      });</script>';
  die();
};

if ($_GET['action'] == 'listParts') { // otimizar sql queries
  $result = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM classes c
      LEFT JOIN glosses g ON g.id = c.id_gloss WHERE c.id_idioma = ".$_GET['iid']."
      ;") or die(mysqli_error($GLOBALS['dblink']));
  
  while($r = mysqli_fetch_assoc($result)){
    $generos = '';
    $result2 = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM generos c 
        LEFT JOIN glosses g ON g.id = c.id_gloss
        WHERE c.id_idioma = ".$_GET['iid']." AND c.id_classe = ".$r['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    
    while($r2 = mysqli_fetch_assoc($result2)){
        $generos .= $r2['nome'].', ';
    }
    if ($generos!='') $generos = '<br><small>'._t('Gêneros').': '.substr($generos,0,-2).'</small>';

    $concords = '';
    $result2 = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM concordancias c 
        LEFT JOIN glosses g ON g.id = c.id_gloss
        WHERE c.id_idioma = ".$_GET['iid']." AND c.id_classe = ".$r['id']." AND c.depende = 0;") or die(mysqli_error($GLOBALS['dblink']));
    while($r2 = mysqli_fetch_assoc($result2)){
        $concords .= $r2['nome'].', ';
    }
    if ($concords!='')  $concords = '<br><small>'._t('Flexões').': '.substr($concords,0,-2).'</small>';

    //echo "<tr id='row_".$r['id']."'><td onClick='abrirPalavra(".$r['id'].")'><a class='btn btn-xs btn-info btn-rounded pull-right' onClick='apagarOpcao(".$r['id'].")'>X</a>"
    //  .$r['nome']." : ".$r['gloss']."".$generos.$concords." </td></tr>";

    echo '<div class="list-group-item" id="row_'.$r['id'].'"><div class="row">
          <div class="col" onClick="abrirPalavra(\''.$r['id'].'\')">
            <a href="#" >'.$r['nome'].' : '.$r['gloss'].'</a>
            <a class="text-body text-secondary">'.$generos.$concords.'</a> 
          </div><div class="col-auto"><a class="btn btn-sm btn-danger" onclick="delPart(\''.$r['id'].'\')">X</a></div>
        </div></div>';
  };
    die();
};

if ($_GET['action'] == 'listarClasses') { // otimizar sql queries
  $result = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM classes c
      LEFT JOIN glosses g ON g.id = c.id_gloss WHERE c.id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
  echo '<table id="tabelaPalavras" data-ride="datatables" class="table table-m-b-none">
        <thead><tr><th>Nome</th></tr></thead><tbody>';
  while($r = mysqli_fetch_assoc($result)){
    $generos = '';
    $result2 = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM generos c 
        LEFT JOIN glosses g ON g.id = c.id_gloss
        WHERE c.id_idioma = ".$_GET['iid']." AND c.id_classe = ".$r['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    
    while($r2 = mysqli_fetch_assoc($result2)){
        $generos .= $r2['nome'].', ';
    }
    if ($generos!='') $generos = '<br><small>'._t('Gêneros').': '.substr($generos,0,-2).'</small>';

    $concords = '';
    $result2 = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM concordancias c 
        LEFT JOIN glosses g ON g.id = c.id_gloss
        WHERE c.id_idioma = ".$_GET['iid']." AND c.id_classe = ".$r['id']." AND c.depende = 0;") or die(mysqli_error($GLOBALS['dblink']));
    while($r2 = mysqli_fetch_assoc($result2)){
        $concords .= $r2['nome'].', ';
    }
    if ($concords!='')  $concords = '<br><small>'._t('Flexões').': '.substr($concords,0,-2).'</small>';
    echo "<tr id='row_".$r['id']."'><td onClick='abrirPalavra(\"".$r['id']."\")'><a class='btn btn-xs btn-info btn-rounded pull-right' onClick='apagarOpcao(\"".$r['id']."\")'>X</a>"
      .$r['nome']." : ".$r['gloss']."".$generos.$concords." </td></tr>";
  };
  echo '</tbody></table><script>$("#tabelaPalavras").DataTable();</script>';
    die();
};

if ($_GET['action'] == 'listarValores') { // otimizar sql queries
  // k classe, iid idioma, c id_concordancias
  
  $sql = "SELECT ic.* FROM itensConcordancias ic 
      WHERE ic.id_concordancia = ".$_GET['op']." ORDER BY ordem;"; //ORDER BY ic.padrao DESC

  $result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

  $defNovo = 1;

  echo '<table id="tabelaOpcoes" data-ride="datatables" class="table table-m-b-none"> <tbody>';
  while($r = mysqli_fetch_assoc($result)){
      $glosses = $gnomes = '';
      $grefs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM gloss_itens gi
        LEFT JOIN glosses g ON g.id = gi.id_gloss
        WHERE gi.id_item = ".$r['id'].";");
      while($gr = mysqli_fetch_assoc($grefs)) {
        $glosses .= $gr['id_gloss'].',';
        $gnomes .= $gr['gloss'].' ';
      }
      $glosses = substr($glosses,0,strlen($glosses)-1);

      echo "<tr id='row_".$r['id']."'><td style=\"cursor:pointer\" onClick='editarOpcao(\"".$r['id']."\",\"".$r['nome']."\",\"".$glosses."\",".$r['padrao'].")'>".$gnomes." - ".$r['nome']."</td><td>";

      if ($r['padrao']==1) {
        echo "<a class='btn btn-sm btn-primary' onClick='setarOpcaoPadrao(\"".$r['id']."\")'>"._t('Padrão/Desmarcado')."</a>
            <a class='btn btn-sm btn-primary' onClick='subirOpcao(\"".$r['id']."\")'>^</a>
            <a class='btn btn-sm btn-primary' onClick='descerOpcao(\"".$r['id']."\")'>v</a></td></tr>";
            $defNovo = '0';
      }else{
        echo "<a class='btn btn-sm btn-danger' onClick='apagarOpcao(\"".$r['id']."\")'>X</a>
          <a class='btn btn-sm btn-primary' onClick='subirOpcao(\"".$r['id']."\")'>^</a>
          <a class='btn btn-sm btn-primary' onClick='descerOpcao(\"".$r['id']."\")'>v</a>
        ";

        if ($r['padrao']==2){ //flexionar
          
          $conc = 'x';
          //get concordancias.id where depende = getop ?

          $resultc = mysqli_query($GLOBALS['dblink'],"SELECT id FROM concordancias WHERE depende = ".$r['id']." LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
          if (mysqli_num_rows($resultc)>0) {
            $rc = mysqli_fetch_assoc($resultc);
            $conc = $rc['id'];
          }

          echo "<a class='btn btn-sm btn-primary' onClick='setarOpcaoPadrao(".$r['id'].")'>"._t('Tornar padrão')."</a>
          <a class='btn btn-sm btn-primary' href='?page=editinflections&iid=".$_GET['iid']."&k=".$_GET['k']."&c=".$conc."&d=".$r['id']."'>"._t('Editar formas')."</a></td></tr>";

        }else{ //forma única
          echo "<a class='btn btn-sm btn-primary' onClick='setarOpcaoPadrao(".$r['id'].")'>"._t('Tornar padrão')."</a>
          </td></tr>"; //<a class='btn btn-xs btn-info btn-rounded' >Editar formas</a>
          
        }

      }

  };
  echo "<tr><td onclick='novaOpcao(".$defNovo.")' class='btn btn-sm btn-primary'>"._t('Adicionar')."</td><td ></td></tr></tbody></table>"; //<script>$("#tabelaOpcoes").DataTable();</script>
  die();
};

if ($_GET['action'] == 'listarGeneros') { // otimizar sql queries
  $d = 0;
  if ($_GET['d']>0) {
    $d = $_GET['d'];
  }
  $c = 0;
  $result = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM generos c 
    LEFT JOIN glosses g ON g.id = c.id_gloss 
    WHERE c.id_idioma = ".$_GET['iid']." AND c.id_classe = ".$_GET['k']." AND c.depende = ".$d.";") or die(mysqli_error($GLOBALS['dblink']));
  
  while($r = mysqli_fetch_assoc($result)){
    $c++;
    echo "<div id='row_".$r['id']."' class='list-group-item divWord'><div class=\"row\">
      <div class=\"col-auto\" onClick='abrirPalavra(\"".$r['id']."\")'>".$r['nome']." - ".$r['gloss']."</div>
      <div class=\"col\"><a class='btn btn-danger btn-sm' onClick='apagarGenero(\'".$r['id']."\')'>X</a></div></div></div>";
  };
  if ($c>0) $c = '$("#divBtnFormas").show()';
  else $c = '';
  echo '<script>'.$c.'</script>';
    die();
};

if ($_GET['action'] == 'listarConcordancias') { // otimizar sql queries
  $d = 0;
  if ($_GET['d']>0) {
    $d = $_GET['d'];
  }
  $c = 0;
  $result = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM concordancias c 
    LEFT JOIN glosses g ON g.id = c.id_gloss 
    WHERE c.id_idioma = ".$_GET['iid']." AND c.id_classe = ".$_GET['k']." AND c.depende = ".$d." ORDER BY obrigatorio DESC;") or die(mysqli_error($GLOBALS['dblink']));
  
  while($r = mysqli_fetch_assoc($result)){
    $c++;
    $concords = '<br><small>';
    $sql = "SELECT c.*, ic.*, ic.id as iid FROM concordancias c
            LEFT JOIN itensConcordancias ic ON ic.id_concordancia = c.id
          WHERE c.id_idioma = ".$_GET['iid']." 
            AND c.depende = ".$d."
            AND ic.id_concordancia = ".$r['id']." ORDER BY ic.ordem;";
    $result2 = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
    
    while($r2 = mysqli_fetch_assoc($result2)){
        $glossList = '';
        $sql = "SELECT g.* FROM gloss_itens gi
          LEFT JOIN glosses g ON g.id = gi.id_gloss
          WHERE gi.id_item = ".$r2['iid'].";";
        $resultGl = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
        while($rgl = mysqli_fetch_assoc($resultGl)){
          $glossList .= $rgl['gloss'].' ';
        };

        $concords .= $glossList.' - '.$r2['nome'].'<br>';
    }
    $concords .= '</small>';

    echo "<div id='row_".$r['id']."' onClick='abrirPalavra(\"".$r['id']."\")' class=\"list-group-item divWord\" >
        <div class=\"row\" ><div class='col-auto'>
          ".$r['nome']." - ".$r['gloss']."
          <div class='text-secondary text-truncate mt-n1'>".$concords." </div></div>
        <div class='col-auto'><a class='btn btn-sm btn-primary pull-right' onClick='apagarConcordancia(\"".$r['id']."\")'>X</a></div>
        </div>
        </div>";
  };
  if ($c>0) $c = '$("#divBtnFormas").show()';
  else $c = '';
  echo '<script>;'.$c.'</script>';
    die();
};

if ($_GET['action'] == 'ajaxGetRaizes') {
  $romanizacao = 0;
  $result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, (SELECT e.id FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1) as escrita FROM idiomas i WHERE i.id = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
  if (mysqli_num_rows($result)>0) {
    $res = mysqli_fetch_assoc($result);
    $romanizacao = $res['romanizacao'];
    $escrita = $res['escrita'];
    if ($escrita>0) $nativesql = ", ( SELECT palavra FROM palavrasNativas n 
      WHERE n.id_palavra = p.id AND n.id_escrita = ".$escrita."
    LIMIT 1 ) as palavra ";
  }
  $sql = "SELECT p.* ".$nativesql."
    FROM palavras p 
    WHERE p.id_idioma = ".$_GET['iid']." 
      AND p.id_forma_dicionario = 0;";
      //echo $sql;
  $langs = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
  //echo '<option value="2">Contração</option>';
  //echo '<option value="1">Morfema que não aparece no dicionário</option>'; Esta é a forma de dicionário!
  echo '<option value="0" selected>-</option>';
  while ($lang = mysqli_fetch_assoc($langs)){
      echo '<option value="'.$lang['id'].'" title="'.$lang['significado'].'"';
      if ($idioma['id_forma_dicionario'] == $lang['id']) echo ' selected'; 

      if ($romanizacao==1) echo '>'.$lang['romanizacao'].' &nbsp; '.$lang['palavra'].'</option>'; //'.$lang['escrita_nativa'].' -   // /'.$lang['pronuncia'].'/
      else{
        echo '>'.$lang['palavra'].' &nbsp; /'.$lang['pronuncia'].'/ </option>'; //'.$lang['escrita_nativa'].' -   // /'.$lang['pronuncia'].'/
      }

  }
  die();
};

if ($_GET['action'] == 'ajaxGetOrigens') {
  $sql = "SELECT p.*, i.sigla, 
          ( SELECT palavra from palavrasNativas pn
              WHERE p.id = pn.id_palavra
              AND pn.id_escrita = e.id LIMIT 1
          ) as nativo,
          e.id as eid, e.tamanho, e.id_fonte 
          FROM palavras p 
          LEFT JOIN idiomas i ON i.id = p.id_idioma
          LEFT JOIN escritas e ON e.padrao = 1 AND e.id_idioma = i.id;";
  
  $result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
  while($r = mysqli_fetch_assoc($result)){

    //echo '<option value="'.$r['id'].'"  title="'.$r['significado'].'">'.$r['romanizacao'].' ('.$r['descricao'].')<option>';

    echo '<option value="'.$r['id'].'" data-f="'.$r['id_fonte'].'" data-t="'.$r['tamanho'].'" data-eid="'.$r['eid'].'" title="'.$r['significado'].'">'.($r['nativo']!=''?$r['nativo']:$r['romanizacao']).' ('.$r['sigla'].') - '.$r['significado'].'</option>';
  };

  die();
};

if ($_GET['action'] == 'getDetMorfPalavra') {
  echo getCombosGenPalavra($_GET['pid']).getCombosPalavra($_GET['pid']);
  die();
};

if ($_GET['action'] == 'getPalavrasRelacionadas') {
  echo getPalavrasRelacionadas($_GET['pid'],0,$_GET['e']??true);
  die();
};

if ($_GET['action'] == 'getPalavrasMesmaPronuncia') {
  echo getPalavrasMesmaPronuncia($_GET['pid'],0,$_GET['e']??true);
  die();
};

if ($_GET['action'] == 'getPalavrasMesmaEscrita') {
  echo getPalavrasMesmaEscrita((int)$_GET['pid'],0,$_GET['e']??true,(int)$_GET['eid']);
  die();
};

if ($_GET['action'] == 'getPalavrasSinonimos') {
  echo getPalavrasMesmosReferentes($_GET['pid'],0,$_GET['e']??true);
  die();
};

if ($_GET['action'] == 'getPalavrasExtras') {
  $return = getPalavrasMesmaEscrita($_GET['pid'],5,true);
  $return .= getPalavrasMesmaPronuncia($_GET['pid'],5);
  $return .= getPalavrasRelacionadas($_GET['pid'],5);
  $return .= getPalavrasMesmosReferentes($_GET['pid'],5);

  if (strlen($return)<13) $return = '<div class="list-group-item"><h3 class="card-title">'._t('Palavras relacionadas (nada aqui)').'</h3></div>';
  echo $return;
  die();
};

if ($_GET['action'] == 'getGlossesPalavra') { // otimizar sql queries

  if(!$_GET['pid']>0) die('novalered');

  $res0 = mysqli_query($GLOBALS['dblink'],"SELECT *, 
      (SELECT i.id_usuario FROM idiomas i WHERE i.id = p.id_idioma) as usuario_dono,
      (SELECT id FROM collabs WHERE id_idioma = p.id_idioma AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
      FROM palavras p 
      WHERE p.id = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));
  $r = mysqli_fetch_assoc($res0);
  $id_idioma = $r['id_idioma'];
  $base = $r['id_forma_dicionario'];
  $return = '';

  if($r['usuario_dono']==$_SESSION['KondisonairUzatorIDX'] || $r['collab'] > 0){
    $return .= getCombosGenPalavra($_GET['pid']);
    $return .= getCombosPalavra($_GET['pid']);
  }

  if ($return == '') $return = _t('Palavras desta classe não mudam de forma.');
  
  echo $return;
  die();
};

if ($_GET['action'] == 'getDetalhesNivel') {
  
  $refs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras
    WHERE id_uso = ".$_GET['nid'].";");
  $refers = '';
  while($r = mysqli_fetch_assoc($refs)) {
    $refers .= '<span class="custom-font">'.$r['escrita_nativa'].'</span> "'.$r['romanizacao'].'", ';
  }
  $refers = substr($refers,0,strlen($refers)-2);

  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM nivelUsoPalavra 
    WHERE id = ".$_GET['nid']." LIMIT 1;");
  $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
      $rows[] = $r;
    }
    $rows[0]['exemplos'] = $refers;

  print json_encode($rows);
  die();
};

if ($_GET['action'] == 'getDetalhesGloss') {
  
  $refs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM gloss_referentes 
    WHERE id_gloss = ".$_GET['nid'].";");
  $refers = '';
  while($r = mysqli_fetch_assoc($refs)) {
    $refers .= $r['id_referente'].',';
  }
  $refers = substr($refers,0,strlen($refers)-1);


  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glosses 
    WHERE id = ".$_GET['nid']." LIMIT 1;");
  $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
      $rows[] = $r;
    }
    $rows[0]['referentes'] = $refers;

  print json_encode($rows);
  die();
};

if ($_GET['action'] == 'getDetalhesPalavra') {

    //get palavras com mesmos referente:
    //pegar referentes da palavra
    //listar palavras com referente in
    if (! $_GET['pid'] > 0) die();

    $refs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras_referentes 
      WHERE id_palavra = ".$_GET['pid'].";");
    $refers = '';
    while($r = mysqli_fetch_assoc($refs)) {
      $refers .= $r['id_referente'].',';
    }
    $refers = substr($refers,0,strlen($refers)-1);
    
    
    $refs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras_origens 
      WHERE id_palavra = ".$_GET['pid'].";");
    $origens = '';
    while($r = mysqli_fetch_assoc($refs)) {
      $origens .= $r['id_origem'].',';
    }
    $origens = substr($origens,0,strlen($origens)-1);

    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM palavras 
      WHERE id = ".$_GET['pid']." LIMIT 1;");
    $rows = array();
      while($r = mysqli_fetch_assoc($result)) {
        if ( strlen($r['detalhes'])==0 ) $r['detalhes'] = '';
        $rows[] = $r;
      }
      //$rows['referentes'] = $refers;
      $rows[0]['referentes'] = $refers;
      $rows[0]['origens'] = $origens;

    $result = mysqli_query($GLOBALS['dblink'],"SELECT p.id_escrita as id, p.palavra, e.id_fonte as fonte, e.tamanho FROM palavrasNativas p
      LEFT JOIN escritas e ON e.id = p.id_escrita
      WHERE p.id_palavra = ".$_GET['pid'].";");
    $nat = array();
    while($r = mysqli_fetch_assoc($result)) {
      $nat[] = $r;
    };
    $rows[0]['escrita_nativa'] = $nat;
    
    $rows[0]['origensTexto'] = getOrigensPalavra($_GET['pid']);

    // sigiids : id = int, iid = int, signif = varchar, niid = i.nome_legivel
    $rows[0]['sigiids'] = array();

    $oiids = mysqli_query($GLOBALS['dblink'],
        "SELECT s.significado as sig, s.id, i.id as iid, (SELECT e.id FROM escritas e WHERE e.id_idioma = i.id AND e.padrao = 1 LIMIT 1) as eid, i.nome_legivel as niid 
        FROM significados_idiomas s
        LEFT JOIN idiomas i ON i.id = s.id_idioma 
        WHERE id_palavra = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink'])); // AND buscavel = 1
      while($oid = mysqli_fetch_assoc($oiids)) {
        $rows[0]['sigiids'][0]['id'] = $oid['id'];
        $rows[0]['sigiids'][0]['iid'] = $oid['iid'];
        $rows[0]['sigiids'][0]['eid'] = $oid['eid'];
        $rows[0]['sigiids'][0]['sig'] = $oid['sig'];
        $rows[0]['sigiids'][0]['niid'] = $oid['niid'];

        // echo '<div class=""><input type="text" title="'.$oid['nome_legivel'].'" class="form-control sigoutros" id="sigoutro_'.$oid['iid'].'" onkeyup="editarPalavra()" value="'.$oid['significado'].'" placeholder="Significado sucinto em '.$oid['nome_legivel'].'"></div>';
      };
      
    $rows[0]['pid'] = $_GET['pid'];
    print json_encode($rows);
	  die();
};

if ($_GET['action'] == 'getWordEdit') {
  require('views/editword.php');
  die();
};

if ($_GET['panel']) {
  require('panels/'.$_GET['panel'].'.php');
  die();
};

if ($_GET['action'] == 'getDetalhesFlexao') {
    $ids = array_map('intval', explode(',', $_POST['id']));
    $unique_ids = array_unique($ids);

    $sql = "SELECT * FROM flexoes 
            WHERE id IN (" . implode(',', $unique_ids) . ")
            ORDER BY FIELD(id, " . implode(',', $unique_ids) . ");";
    $result = mysqli_query($GLOBALS['dblink'], $sql);
    
    $results_map = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $results_map[$r['id']] = $r;
    }

    $rows = [];
    foreach ($ids as $id) {
        if (isset($results_map[$id])) {
            $rows[] = $results_map[$id];
        }
    }
    print json_encode($rows);
    die();
}

if ($_GET['action'] == 'updateMaxSilabas') {
    mysqli_query($GLOBALS['dblink'],"UPDATE idiomas SET silabas = ".$_GET['n']."
        WHERE id = ".$_GET['i']." LIMIT 1;");
    die('ok');
};

if ($_GET['action'] == 'getSintazBazic') {

    $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM idiomas 
      WHERE id = ".$_GET['iid']." LIMIT 1;");
    $rows = array();
      while($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
      }
 
    print json_encode($rows);
	  die();
};

if ($_GET['action'] == 'getDetalhesReferente') {

  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM referentes_descricoes 
    WHERE id_referente = ".$_GET['rid'].";");
  $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
      $rows['d'.$r['id_idioma']] = $r['descricao'];
      $rows['m'.$r['id_idioma']] = $r['detalhes'];
    }

  print json_encode($rows);
  die();
};

if ($_GET['action'] == 'listReferentes') { //xxxxx remover descricao een e ptbr
    $result = mysqli_query($GLOBALS['dblink'], "SELECT r.id, d.detalhes, d.descricao FROM referentes r 
          LEFT JOIN referentes_descricoes d ON d.id_referente = r.id
          AND id_idioma = '".$_SESSION['KondisonairUzatorDiom']."';") or die(mysqli_error($GLOBALS['dblink']));
    while ($r = mysqli_fetch_assoc($result)) {
        echo '<div class="list-group-item" id="row_'.$r['id'].'"><div class="row">
          <div class="col" onClick="abrirReferente(\''.$r['id'].'\')">
            <a href="#" >'.$r['descricao'].'</a> 
            <a class="text-body text-secondary">'.$r['detalhes'].'</a> 
          </div><div class="col-auto"><a class="btn btn-sm btn-danger" onclick="delReferente(\''.$r['id'].'\')">X</a></div>
        </div></div>';
  } 
  die();
}

if ($_GET['action'] == 'getDetalhesClasse') {

  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM classes 
    WHERE id = ".$_GET['cid']." LIMIT 1;");
  $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
      $rows[] = $r;
    }

  print json_encode($rows);
  die();
};

if ($_GET['action'] == 'getDetalhesConcordancia') {

  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias 
    WHERE id = ".$_GET['cid']." LIMIT 1;");
  $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
      $rows[] = $r;
    }

  print json_encode($rows);
  die();
};

if ($_GET['action'] == 'getDetalhesGenero') {

  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM generos 
    WHERE id = ".$_GET['k']." LIMIT 1;");
  $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
      $rows[] = $r;
    }

  print json_encode($rows);
  die();
};

if ($_GET['action'] == 'ajaxMoverFormaPalavra') {

    $cex = null;
    if(isset($_POST['cex'])){
      $cex = $_POST['cex'];
    };
    $idioma = $_GET['iid'];

    if (strlen($_POST['from'])>0){

        $from = explode("-",$_POST['from']);
        if($from[0]>0 && $from[5]>0 && $from[1]==0&& $from[2]==0&& $from[3]==0&& $from[4]==0){

            // echo 'ok'; die();

            // aqui pra mover das órfãs: insert into itens_palavras
            $taVazio = false;
            $to = explode("-",$_POST['to']);
            if($to[3]>0){}else{
              echo'ok';die();
            }

            // ver o destino
            $tox = $to[1];
            $toy = $to[2];
            $toz = $cex[0]['val'];
            $textra = 0;
            
            $pid = $from[0];
            $dic = $from[5];
            $linhas = $tox;
            $colunas = $toy;
            // ver se tem palavra no destino
            // inserir em itens_palavras

            $sql = "SELECT p.* FROM palavras p WHERE p.id_idioma = ".$idioma;
            $sql .= " AND (p.id = ".$dic." OR p.id_forma_dicionario = ".$dic.")  "; // $sql .= " AND ( p.id_forma_dicionario = ".$pid." OR p.id = ".$pid.")  ";
            $sql .= " AND (SELECT ip1.id FROM itens_palavras ip1 WHERE ip1.id_palavra = p.id AND ip1.id_concordancia = ".$linhas." AND ip1.id_item = ".$to[3]/*$x*/." AND ip1.usar = 1 ) IS NOT NULL ";
            if ($to[4]>0) $sql .= " AND (SELECT ip2.id FROM itens_palavras ip2 WHERE ip2.id_palavra = p.id AND ip2.id_concordancia = ".$colunas." AND ip2.id_item = ".$to[4]/*$y2*/." AND ip2.usar = 1 ) IS NOT NULL ";
            if ($toz>0) $sql .= " AND (SELECT ip3.id FROM itens_palavras ip3 WHERE ip3.id_palavra = p.id AND ip3.id_concordancia = ".$cex[0]['did']." AND ip3.id_item = ".$toz." AND ip3.usar = 1 ) IS NOT NULL ";
            
            $sql .= " ;";

            $ps = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

            if (mysqli_num_rows($ps)==0) $taVazio = true;

            if ( $taVazio ) {// aqui vai mover se estiver td certo acima
              
                mysqli_query($GLOBALS['dblink'], "DELETE FROM itens_palavras WHERE id_palavra = ".$pid.";") or die(mysqli_error($GLOBALS['dblink']));
                $sql = "INSERT INTO itens_palavras (id, id_item, id_concordancia, id_palavra, usar) VALUES ";

                $sql .= "(".generateId().",".$to[3].",".$linhas.",".$pid.",1),";
                if ($to[4]>0) $sql .= "(".generateId().",".$to[4].",".$colunas.",".$pid.",1),";
                if ($toz>0) $sql .= "(".generateId().",".$toz.",".$cex[0]['did'].",".$pid.",1),";

                mysqli_query($GLOBALS['dblink'],substr($sql,0,-1)) or die(mysqli_error($GLOBALS['dblink']));

                $ccs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias WHERE id = $linhas") or die(mysqli_error($GLOBALS['dblink']));
                $cc = mysqli_fetch_assoc($ccs);
                $dependencia = $cc['depende'] > 0 ? (int)$cc['depende'] : 0;
                
                $il = 0; // limite de subtabelas
                while($dependencia > 0){ 
                    $ccs = mysqli_query($GLOBALS['dblink'],"SELECT *, c.nome as titulo FROM concordancias c 
                        LEFT JOIN itensConcordancias ic ON ic.id_concordancia = c.id 
                        WHERE ic.id = ".$dependencia.";") or die(mysqli_error($GLOBALS['dblink']));
                    $cc = mysqli_fetch_assoc($ccs);

                    $sqlQuerys = "INSERT INTO itens_palavras SET 
                      id_palavra = ".$pid.", id = ".generateId().",
                      id_concordancia = ".$cc['id_concordancia'].",
                      id_item = ".$dependencia.",
                      usar = 1;";
                    mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));

                    if ($cc['depende']>0) $dependencia = $cc['depende'];
                    else $dependencia = 0;
                    
                    $il++; if($il>10) $dependencia = 0;
                }  
            }

            echo 'ok';

        }else if($_POST['to']=='lixo' && $from[5]>0){
            // delete
            mysqli_query($GLOBALS['dblink'],"DELETE FROM itens_palavras
                        WHERE id_palavra = ".$from[0].";") or die(mysqli_error($GLOBALS['dblink']));
            echo 'ok';
        }else if ($from[5]==0) {
          $result = mysqli_query($GLOBALS['dblink'],"SELECT *, 
              (SELECT paradigma FROM classes c WHERE c.id = p.id_classe LIMIT 1) as paradigma
              FROM palavras p 
              WHERE p.id = ".$from[0].";") or die('1834'.mysqli_error($GLOBALS['dblink']));
          $r = mysqli_fetch_assoc($result);
          $parad = (int)$r['paradigma'];
          if ($parad == 1) {
              $to = explode("-",$_POST['to']);
              $linhas = $to[1];
              $colunas = $to[2];
              $x = $to[3];
              $y2 = $to[4];

              if ($to[5] == 0) echo '0';
              else if ($to[0] > 0 || $to[5] > 0) echo "0";
              else {
                  $id = generateId();
                  mysqli_query($GLOBALS['dblink'],
                      "INSERT INTO itens_palavras (id, id_concordancia, id_palavra, usar, id_item)
                      VALUES (".$id.", ".$linhas.", ".$from[0].", 1, ".$x.")
                      ON DUPLICATE KEY UPDATE id_item = ".$x.";")
                      or die(mysqli_error($GLOBALS['dblink']));

                  $id = generateId();
                  mysqli_query($GLOBALS['dblink'],
                      "INSERT INTO itens_palavras (id, id_concordancia, id_palavra, usar, id_item)
                      VALUES (".$id.", ".$colunas.", ".$from[0].", 1, ".$y2.")
                      ON DUPLICATE KEY UPDATE id_item = ".$y2.";")
                      or die(mysqli_error($GLOBALS['dblink']));
                  echo 'ok';
              }
          } else echo '0';
        }else if ($from[0] > 0 && strlen($_POST['to'])>0) {
                
            /*
              TO DO
                - não pode mover nada onde já tem palavra
            */

            if (is_numeric($_POST['to']) && $_POST['to'] > 0){
                $tox = $from[1];
                $toy = $from[2];
                $toz = $_POST['to'];
                $textra = 0;
                
                $linhas = $tox;
                $colunas = $toy;
                
                $pid = $from[0];
                $dic = $from[5];
                
                //$toz = $from[3];
                $textra = $from[4];
                $x = $toz;
                $y2 = $textra;

                if ($toz == $cex[0]['val']) echo '0';
                else {
                    $taVazio = false;

                    $extraPadrao = 0;
                    $resdef = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias
                        WHERE id_concordancia = ".$cex[0]['did']." AND padrao < 2 ORDER BY padrao DESC, ordem;") or die(mysqli_error($GLOBALS['dblink']));
                    $rc = mysqli_fetch_assoc($resdef);
                    if ($rc['id']==$cex[0]['val']) $extraPadrao = 1;

                    //echo $extraPadrao."\n";
                    //echo 'de '.$_POST['from'].' para '.$_POST['to']."\n";

                    //xxxxx ver se existe uma palavra nessa posição na outra dimensão

                    $sql = "SELECT p.* FROM palavras p WHERE p.id_idioma = ".$idioma;
                    $sql .= " AND (p.id = ".$dic." OR p.id_forma_dicionario = ".$dic.")  "; // $sql .= " AND ( p.id_forma_dicionario = ".$pid." OR p.id = ".$pid.")  ";
                    $sql .= " AND (SELECT ip1.id FROM itens_palavras ip1 WHERE ip1.id_palavra = p.id AND ip1.id_concordancia = ".$linhas." AND ip1.id_item = ".$from[3]/*$x*/." AND ip1.usar = 1 ) IS NOT NULL ";
                    $sql .= " AND (SELECT ip2.id FROM itens_palavras ip2 WHERE ip2.id_palavra = p.id AND ip2.id_concordancia = ".$colunas." AND ip2.id_item = ".$from[4]/*$y2*/." AND ip2.usar = 1 ) IS NOT NULL ";
                        
                    if($extraPadrao == 1){ 
                        $sql .= " AND ( 
                            (SELECT ip3.id FROM itens_palavras ip3 WHERE ip3.id_palavra = p.id AND ip3.id_concordancia = ".$cex[0]['did']." AND ip3.id_item = ".$toz." AND ip3.usar = 1 ) IS NOT NULL
                            OR
                            (SELECT ip3.id FROM itens_palavras ip3 WHERE ip3.id_palavra = p.id AND ip3.id_concordancia = ".$cex[0]['did'].") IS NULL 
                          ) ";
                    }else{
                        $sql .= " AND (SELECT ip3.id FROM itens_palavras ip3 WHERE ip3.id_palavra = p.id AND ip3.id_concordancia = ".$cex[0]['did']." AND ip3.id_item = ".$toz." AND ip3.usar = 1 ) IS NOT NULL ";
                    }
                        
                    $sql .= " ;";

                    $ps = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

                    // TESTE:
                    //echo $sql."\n";
                    // $pal = mysqli_fetch_assoc($ps); 
                    //print_r($pal);
                    if (mysqli_num_rows($ps)==0) $taVazio = true;

                    //echo 'ok mudar';

                    // echo "UPDATE itens_palavras SET id_item = ".$toz." WHERE id_concordancia = ".$cex[0]['did']." AND id_palavra = ".$pid." AND usar = 1;";
                    if ( $taVazio ) // aqui vai mover se estiver td certo acima
                        mysqli_query($GLOBALS['dblink'],
                            "UPDATE itens_palavras SET id_item = ".$toz." WHERE id_concordancia = ".$cex[0]['did']." AND id_palavra = ".$pid." AND usar = 1 LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));

                    echo 'ok';
                }

            }else{

                $to = explode("-",$_POST['to']);
                $tox = $to[1];
                $toy = $to[2];
                $toz = $to[3];
                $textra = $to[4];

                $linhas = $tox;
                $colunas = $toy;
                $x = $toz;
                $y2 = $textra;

                $pid = $from[0];
                $dic = $from[5];

                if ($to[5] == 0) echo '0'; // é a forma de dicionário, padrão!
                else if ($to[0] > 0 || $to[5] > 0) echo "0"; // tem uma palavra aqui
                else {
                        mysqli_query($GLOBALS['dblink'],
                            "UPDATE itens_palavras SET id_item = ".$x." WHERE id_concordancia = ".$linhas." AND id_palavra = ".$pid." AND usar = 1 LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
                            
                        mysqli_query($GLOBALS['dblink'],
                            "UPDATE itens_palavras SET id_item = ".$y2." WHERE id_concordancia = ".$colunas." AND id_palavra = ".$pid." AND usar = 1 LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));

                    echo 'ok';
                }
            }
        }else{
            echo '0';
        }
    }else echo '0';
    die();
};

if ($_GET['action'] == 'carregarTabelaSons') { // otimizar sql queries
  // GET iid = idioma
  // GET ed = completa e editavel 0/1
  $tipo = $_GET['t'];
  $tmp = mysqli_query($GLOBALS['dblink'],"SELECT *, (SELECT SUM(peso) FROM inventarios WHERE id_idioma = ".$_GET['iid'].") as pesoTotal FROM tiposSom WHERE id = ".$tipo.";") or die(mysqli_error($GLOBALS['dblink']));
  $t = mysqli_fetch_assoc($tmp);

  $dimx = $t['dimx'];
  $dimy = $t['dimy'];

  $pesoTotal = $t['pesoTotal'];

  echo '<table class="sound-table table table-m-b-none" style="table-layout:fixed;"><tr><td></td>';
  $yres = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTitulos WHERE dimensao = ".$dimx." AND id_idioma = ".$_GET['iid']." ORDER BY pos;") or die(mysqli_error($GLOBALS['dblink']));
  while($y = mysqli_fetch_assoc($yres)){
    echo '<td class="text-secondary">';
    echo "<span onClick='renomearDimensao(\"".$y['id']."\",\"".$y['nome']."\")'>".$y['nome']."</span>&nbsp;<a class='btn btn-sm btn-danger' onClick='apagarDimensao(\"".$y['dimensao']."\",\"".$y['pos']."\")'>X</a>";
    echo '</td>';
  }
  echo '</tr>';
  $xres = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTitulos WHERE dimensao = ".$dimy." AND id_idioma = ".$_GET['iid']." ORDER BY pos;") or die(mysqli_error($GLOBALS['dblink']));
  while($x = mysqli_fetch_assoc($xres)){
    echo '<tr><td class="text-secondary">'."<span onClick='renomearDimensao(\"".$x['id']."\",\"".$x['nome']."\")'>".$x['nome']."</span>"."<a class='btn btn-sm btn-danger' onClick='apagarDimensao(\"".$x['dimensao']."\",\"".$x['pos']."\")'>X</a></td>";
    $yres = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTitulos WHERE dimensao = ".$dimx." AND id_idioma = ".$_GET['iid']." ORDER BY pos;") or die(mysqli_error($GLOBALS['dblink']));
    while($y = mysqli_fetch_assoc($yres)){
      echo '<td draggable="true" ondrop="dropHandler(event)" ondragover="dragoverHandler(event)" ondragstart="dragstartHandler(event)" class="cell__" id="cell_'.$x['pos'].'_'.$y['pos'].'" onclick="editarCelula(\''.$x['pos'].'\',\''.$y['pos'].'\',0,0)">';

      $is = mysqli_query($GLOBALS['dblink'],"SELECT *, i.id as id_inventario FROM inventarios i 
          LEFT JOIN sons s ON i.id_som = s.id 
          WHERE s.posx = ".$x['pos']." AND s.posy = ".$y['pos']." 
            AND s.id_tipoSom = ".$tipo." AND i.id_tipoSom > 0 
            AND i.id_idioma = ".$_GET['iid']." ORDER BY s.posz;") or die(mysqli_error($GLOBALS['dblink']));
      
      while($i = mysqli_fetch_assoc($is)){
        
        $keys = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(tecla ORDER BY ordem SEPARATOR ' ') as tecla FROM teclas WHERE id_inventario = ".$i['id_inventario'].";") or die(mysqli_error($GLOBALS['dblink']));
        $key = mysqli_fetch_assoc($keys);
        if ($key['tecla']=='') $key = '<span class="celulaSom" title="'.$i['nome'].'">/'.$i['ipa'].'/</span><br>';
        else $key = '<span class="celulaSom" title="'.$i['nome'].'">'.$key['tecla'].' /'.$i['ipa'].'/</span><br>';
        //<span onclick="salvarTecla('.$key['id'].','.$key['tecla'].','.$i['id_inventario'].')">'.$key['tecla'].' <small>(alterar)</small></span>';
        
        
        echo $key;
        echo '<div class="mb-3 progress progress-sm card-progress" style="display:none">
                <div class="progress-bar" style="width:'.($i['peso']/$pesoTotal*300).'%" role="progressbar"></div>
              </div>';
      }

      $is = mysqli_query($GLOBALS['dblink'],"SELECT *, i.id as id_inventario FROM inventarios i 
        LEFT JOIN sonsPersonalizados s ON i.id_som = s.id 
          WHERE s.posx = ".$x['pos']." AND s.posy = ".$y['pos']." AND i.id_tipoSom = 0 AND s.id_tipoSom = ".$tipo."
            AND s.id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
      
      while($i = mysqli_fetch_assoc($is)){
        $keys = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(tecla ORDER BY ordem SEPARATOR ' ') as tecla FROM teclas WHERE id_inventario = ".$i['id_inventario'].";") or die(mysqli_error($GLOBALS['dblink']));
        $key = mysqli_fetch_assoc($keys);
        if ($key['tecla']=='') echo '<span class="celulaSom" title="'.$i['nome'].'">/'.$i['ipa'].'/</span><br>';
        else echo '<span class="celulaSom" title="'.$i['nome'].'">'.$key['tecla'].' /'.$i['ipa'].'/</span><br>';
        
        echo '<div class="mb-3 progress progress-sm card-progress" style="display:none">
              <div class="progress-bar" style="width:'.($i['peso']/$pesoTotal*300).'%" role="progressbar"></div>
            </div>';
      }
      echo '</td>';
    }
    echo '</tr>';
  }
  echo '</table>';
  die();
};

if ($_GET['action'] == 'ajaxMoverSom') {
  
    // // ajaxMoverSom  POST from = 'cell_30_40' to = 'cell_30_40'
    if ( is_numeric($_POST['from']) && $_POST['from']>0 && strlen($_POST['to'])>0){
        $to = explode('_',$_POST['to']);
        $tox = $to[1];
        $toy = $to[2];
        $tipo = $_GET['t'];
        
        $is = mysqli_query($GLOBALS['dblink'],"SELECT *, i.id_tipoSom as tipo, s.id as idSom, i.id as id_inventario FROM inventarios i 
              LEFT JOIN sons s ON i.id_som = s.id 
              WHERE i.id = ".$_POST['from'].";") or die(mysqli_error($GLOBALS['dblink']));
        $i = mysqli_fetch_assoc($is);

        if ($i['tipo']==0){

            mysqli_query($GLOBALS['dblink'],"UPDATE sonsPersonalizados SET
                posx = ".$tox.",
                posy = ".$toy."
                WHERE id = ".$i['idSom'].";") or die(mysqli_error($GLOBALS['dblink']));
            mysqli_query($GLOBALS['dblink'],"UPDATE inventarios SET data_modificado = NOW() 
              WHERE id = ".$i['id_inventario'].";") or die(mysqli_error($GLOBALS['dblink']));

        }else{
          
            $id = generateId();
            mysqli_query($GLOBALS['dblink'],"INSERT INTO sonsPersonalizados SET id = $id,
                  nome = '".$i['nome']."',
                  ipa = '".$i['ipa']."',
                  id_referente = 0,
                  posx = ".$tox.",
                  posy = ".$toy.",
                  posz = ".$i['posz'].",
                  id_tipoSom = ".$i['id_tipoSom'].",
                  id_idioma = '".$i['id_idioma']."';") or die(mysqli_error($GLOBALS['dblink']));
            
            // atualiza no inventario, de som default pra personalizado
            mysqli_query($GLOBALS['dblink'],"UPDATE inventarios SET 
                  id_som = ".$id.", data_modificado = NOW(),
                  id_tipoSom = 0
                  WHERE id = ".$i['id_inventario'].";") or die(mysqli_error($GLOBALS['dblink']));

        }

    }else if ( strlen($_POST['from'])>0 && strlen($_POST['to'])>0){
        $from = explode('_',$_POST['from']);
        $fromx = $from[1];
        $fromy = $from[2];
        $to = explode('_',$_POST['to']);
        $tox = $to[1];
        $toy = $to[2];
        $tipo = $_GET['t'];

        $is = mysqli_query($GLOBALS['dblink'],"SELECT *, i.id as id_inventario FROM inventarios i 
              LEFT JOIN sons s ON i.id_som = s.id 
              WHERE s.posx = ".$fromx." AND s.posy = ".$fromy." 
                AND s.id_tipoSom = ".$tipo." AND i.id_tipoSom > 0 
                AND i.id_idioma = ".$_GET['iid']." ORDER BY s.posz;") or die(mysqli_error($GLOBALS['dblink']));
          
        while($i = mysqli_fetch_assoc($is)){

            //print_r($i);

            // SONS PADRAO NÂO MOVE ?  CONVERTER PRA PERSONALIZADOS ?

            // insere nos personalizaos - Copiar do som original
            $id = generateId();
            mysqli_query($GLOBALS['dblink'],"INSERT INTO sonsPersonalizados SET  id = $id,
                  nome = '".$i['nome']."',
                  ipa = '".$i['ipa']."',
                  id_referente = 0,
                  posx = ".$tox.",
                  posy = ".$toy.",
                  posz = ".$i['posz'].",
                  id_tipoSom = ".$i['id_tipoSom'].",
                  id_idioma = '".$i['id_idioma']."';") or die(mysqli_error($GLOBALS['dblink']));
            
            // atualiza no inventario, de som default pra personalizado
            mysqli_query($GLOBALS['dblink'],"UPDATE inventarios SET 
                  id_som = ".$id.", data_modificado = NOW(),
                  id_tipoSom = 0
                  WHERE id = ".$i['id_inventario'].";") or die(mysqli_error($GLOBALS['dblink']));
            
            /*
            $keys = mysqli_query($GLOBALS['dblink'],"SELECT * FROM teclas WHERE id_inventario = ".$i['id_inventario'].";") or die(mysqli_error($GLOBALS['dblink']));
            $key = mysqli_fetch_assoc($keys);
            if ($key['tecla']=='') $key = '<span class="celulaSom" title="'.$i['nome'].'">/'.$i['ipa'].'/</span><br>';
            else $key = '<span class="celulaSom" title="'.$i['nome'].'">'.$key['tecla'].' /'.$i['ipa'].'/</span><br>';
            echo $key;
            */
        }

        $is = mysqli_query($GLOBALS['dblink'],"SELECT *, s.id as idSom, i.id as id_inventario FROM inventarios i 
            LEFT JOIN sonsPersonalizados s ON i.id_som = s.id 
              WHERE s.posx = ".$fromx." AND s.posy = ".$fromy." AND i.id_tipoSom = 0 AND s.id_tipoSom = ".$tipo."
                AND s.id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
          
        while($i = mysqli_fetch_assoc($is)){

            mysqli_query($GLOBALS['dblink'],"UPDATE sonsPersonalizados SET
                posx = ".$tox.",
                posy = ".$toy."
                WHERE id = ".$i['idSom'].";") or die(mysqli_error($GLOBALS['dblink']));
        }
        mysqli_query($GLOBALS['dblink'],"UPDATE inventarios SET data_modificado = NOW(),
                  WHERE id = ".$i['id_inventario'].";");
        
    }else die('0');

    die('ok');
};

if ($_GET['action'] == 'listarCategoriasSom') { // otimizar sql queries

  $result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM classesSom
    WHERE id_idioma = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
    //echo '<div>';
  //echo '<table id="tbCS" data-ride="datatables" class="table table-m-b-none"><tbody>';
  while($r = mysqli_fetch_assoc($result)) { 
    //echo '<tr class="list-group-item"><td>'; // '<div  style="background-color:#111;padding:5px;margin:5px;border-radius:10px">'; // style="flex-wrap: wrap;display: flex;"
    //só no botao Add vai aparecer sons de sons e sonsPersonalizados 
    //(que tenha id_classe = 0, ou seja, em nenhuma classe)

    //cada iteração, buscar no inventário os sons que estao nessa classe
    // e tbm sonspersonalizados
    $result2 = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(s.ipa) as sons1 FROM inventarios i
          LEFT JOIN sons s ON (i.id_som = s.id )
          LEFT JOIN sons_classes sc ON (sc.tipo = 1 AND i.id = sc.id_som)
        WHERE sc.id_classeSom = ".$r['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    $r2 = mysqli_fetch_assoc($result2);

    /*$result3 = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(ipa) as sons1 FROM sonsPersonalizados 
      WHERE id_idioma = ".$_GET['iid']." AND id_classeSom = ".$r['id'].";") or die(mysqli_error($GLOBALS['dblink']));*/
      
    $result3 = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(ipa) as sons1 FROM sonsPersonalizados sp
        LEFT JOIN sons_classes sc ON (sc.tipo = 2 AND sp.id = sc.id_som)
        WHERE sc.id_classeSom = ".$r['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    /*$result3 = mysqli_query($GLOBALS['dblink'],"SELECT GROUP_CONCAT(s.ipa) as sons1 FROM inventarios i
            LEFT JOIN sonsPersonalizados s ON (i.id_som = s.id )
            LEFT JOIN sons_classes sc ON (sc.tipo = 2 AND i.id_som = sc.id_som)
          WHERE sc.id_classeSom = ".$r['id'].";") or die(mysqli_error($GLOBALS['dblink']));*/
    $r3 = mysqli_fetch_assoc($result3);
    $glifos = $r2['sons1'].','.$r3['sons1']; //xxxxx USAR UNION PRA PODER MESCLAR ORDEM ALFABETICA DEPOIS
    if ($glifos != ',') $glifos = ' = '.$glifos;

    echo '<div class="list-group-item"><div class="row">
            <div class="col" draggable="true" ondragstart="dragstartHandler(event)" id="'.$r['simbolo'].$r['id'].'">
              <label class="form-label">'.$r['simbolo'].' ('.$r['nome'].')'.str_replace(',',' ',$glifos).'</label>
              <div id="btnAddSom'.$r['id'].'" class="catSons">' .listarSonsAdicionaveis($_GET['iid'],$r['id']). '</div>
            </div>
            <div class="col-auto"><a onclick="remCat('.$r['id'].')" class="btn btn-sm btn-danger">x</a></div>
          </div></div>';

    //echo '<div class="col-auto"><a onclick="remCat('.$r['id'].')" class="btn btn-sm btn-danger">x</a></div></div>';
    //echo '</td><td><a onclick="remCat('.$r['id'].')" class="btn btn-sm btn-danger">x</a></td></tr>';
  };
  //echo '</div>';
  //echo '</tbody></table><script>$("#tbCS").DataTable();</script>';

  die();
};

if ($_GET['action'] == 'ajaxRemoverCatSom') { // otimizar sql queries
    //echo listarSonsAdicionaveis($_GET['iid'],$_GET['id']);
    // 
    mysqli_query($GLOBALS['dblink'],"DELETE FROM sons_classes WHERE id_classeSom = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    mysqli_query($GLOBALS['dblink'],"DELETE FROM classesSom WHERE id = ".$_GET['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    die('ok');
}

if ($_GET['action'] == 'listarSonsAdicionaveis') { // otimizar sql queries
    echo listarSonsAdicionaveis($_GET['iid'],$_GET['id']);
  die();
}

if ($_GET['action'] == 'carregarTabelaIPACompleta') { // otimizar sql queries
  // GET iid = idioma
  // GET ed = completa e editavel 0/1
  $tipo = $_GET['t'];
  $tmp = mysqli_query($GLOBALS['dblink'],"SELECT * FROM tiposSom WHERE id = ".$tipo.";") or die(mysqli_error($GLOBALS['dblink']));
  $t = mysqli_fetch_assoc($tmp);

  $dimx = $t['dimx'];
  $dimy = $t['dimy'];

  echo '<table class="sound-table table table-m-b-none"><tr><td width="80px"></td>';
  $yres = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTudo WHERE dimensao = ".$dimx." ORDER BY pos;") or die(mysqli_error($GLOBALS['dblink']));
  while($y = mysqli_fetch_assoc($yres)){
    echo '<td>';
    echo $y['nome']."<a class='btn btn-xs btn-info btn-rounded' onClick='apagarDimensao(1,".$y['pos'].")'>X</a>";
    echo '</td>';
  }
  echo '</tr>';
  $xres = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTudo WHERE dimensao = ".$dimy." ORDER BY pos;") or die(mysqli_error($GLOBALS['dblink']));
  while($x = mysqli_fetch_assoc($xres)){
    echo '<tr><td>'.$x['nome']."<a class='btn btn-xs btn-info btn-rounded' onClick='apagarDimensao(2,".$x['pos'].")'>X</a></td>";
    $yres = mysqli_query($GLOBALS['dblink'],"SELECT * FROM ipaTudo WHERE dimensao = ".$dimx." ORDER BY pos;") or die(mysqli_error($GLOBALS['dblink']));
    while($y = mysqli_fetch_assoc($yres)){

      $is = mysqli_query($GLOBALS['dblink'],"SELECT * FROM sons s
          WHERE s.posx = ".$x['pos']." AND s.posy = ".$y['pos']." 
            AND s.id_tipoSom = ".$tipo." ORDER BY s.posz;") or die(mysqli_error($GLOBALS['dblink']));
      echo '<td class="cell__" id="cell_'.$x['pos'].'_'.$y['pos'].'" onclick="editarCelula('.$x['pos'].','.$y['pos'].',0,0)">';
      
      while($i = mysqli_fetch_assoc($is)){
        echo '<span title="'.$i['nome'].'">'.$i['ipa'].' </span>';
      }

      echo '</td>';
    }
    echo '</tr>';
  }
  echo '</table>';
  die();
};

if ($_GET['action'] == 'fac') {

  if ($_GET['t'] == 'rascunho'){
    echo '<div class="col-sm-12">';
    $langs = mysqli_query($GLOBALS['dblink'],"SELECT e.*, f.arquivo as fonte, i.nome_legivel as id_nome FROM escritas e 
						LEFT JOIN fontes f ON f.id = e.id_fonte
            LEFT JOIN idiomas i ON i.id = e.id_idioma
						WHERE i.publico = 1 ORDER BY e.padrao DESC;") or die(mysqli_error($GLOBALS['dblink']));
    $escritas = '';
    $eid = '1';
    $fonte = 'notosans';
    $tamanho = 'unset';
    while ($e = mysqli_fetch_assoc($langs)){
        $escritas .= '<option value="'.$e['id'].'" title="'.$e['id'].'" ';
        if( $e['id'] == $_GET['v1'] ) {
          $escritas .= ' selected ';
          $eid = $e['id'];
          $fonte = $e['fonte'];
          $tamanho = $e['tamanho'];
        }
        $escritas .= '>'.$e['nome'].' ('.$e['id_nome'].')</option>';
    };
    echo 
    '<div class="col-sm-3">
      <div class="form-group" >
      <select id="sel_aux" class="chosen-select form-control " onchange="painelAuxiliar(\'rascunho\', $(\'#sel_aux\').val() )" >'.$escritas.'</select>
      </div>
    </div>
    <div class="col-sm-9">
      <div class="form-group" >
        <label class="control-label">'._t('Anotações').'</label>
        <textarea class="form-control custom-font-'.$eid.'" rows="20"></textarea>
      </div>
    </div><script>$(".chosen-select").chosen();</script><style>'.
    "@font-face { font-family: CustomFont".$fonte."; src: url('fonts/".$fonte."'); } 
			.custom-font-".$eid." { font-family: CustomFont".$fonte."; font-size: ".$tamanho." !important; }</style></div>";
  }else if ($_GET['t'] == 'palavr'){
    require('views/miniword.php');
  }else if ($_GET['t'] == 'fleksons'){
    //require('modules/m_fleksons.php');
    // carregarTabelaFlexoes
    carregarTabelaFlexoes($_GET['v1']);
  }

  die('<input type="hidden" id="painelAberto" value="'.$_GET['t'].$_GET['v1'].'" />');
};

if ($_GET['action'] == 'getStudTest') { 
  echo getFullStudyText($_GET['id']); 
  die();
};

if ($_GET['action'] == 'testMdason') { 
  
  $sql = "SELECT t.*, e.id as eid
    FROM studason_tests t
    LEFT JOIN escritas e  ON e.id_idioma = t.id_idioma
      WHERE  t.id = ".$_GET['id']."  ORDER BY e.padrao DESC;";
  //echo $sql;
  $langs = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
  $eid = '1';

  $e = mysqli_fetch_assoc($langs);  
  if ($e['eid']>0) $eid = $e['eid'];

  echo $e['texto']; die();

  echo  '<div class="mb-3">
    <label class="form-label">'._t('Título legível').'</label>
    <input type="text" class="form-control" id="testTytol" value="'.$e['titulo'].'">
    </div>
  </div>
  <div class="mb-3"><label class="form-label">'._t('Texto nativo').'</label>
      <textarea data-bs-toggle="autosize" class="form-control custom-font-'.$eid.'" id="testStudason" rows="10">'.$e['texto'].'</textarea>
  </div>';

    die();

};

if ( $_GET['action'] =='loadFormExamples'){

    $padrao = false;

    // s&k=31&c1=49&c2=48&i1=104&i2=101&gen=0

    if ($_GET['k'] > 0){
      $k = " AND p.id_classe = ".$_GET['k']." ";
    }
    
    if ($_GET['c1'] > 0){
      $qp1 = " AND (SELECT ip1.id FROM itens_palavras ip1 WHERE ip1.id_palavra = p.id AND ip1.id_concordancia = ".$_GET['c1']." AND ip1.id_item = ".$_GET['i1']." AND ip1.usar = 1 ) IS NOT NULL ";
      
      $homons = mysqli_query($GLOBALS['dblink'],
        "SELECT * FROM itensConcordancias WHERE padrao = 1 AND id_concordancia = ".$_GET['c1']." AND id = ".$_GET['i1']
        ) or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($homons)>0) $padrao = true; //else $padrao = false;
    }
    if ($_GET['c2'] > 0){
      // add c2 i2
      $qp2 = " AND (SELECT ip2.id FROM itens_palavras ip2 WHERE ip2.id_palavra = p.id AND ip2.id_concordancia = ".$_GET['c2']." AND ip2.id_item = ".$_GET['i2']." AND ip2.usar = 1 ) IS NOT NULL ";
      $homons = mysqli_query($GLOBALS['dblink'],
        "SELECT * FROM itensConcordancias WHERE padrao = 1 AND id_concordancia = ".$_GET['c2']." AND id = ".$_GET['i2']
        ) or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($homons)>0) $padrao = true; //else $padrao = false;
    }
    if ($_GET['c3'] > 0){
      // add c2 i2
      $qp3 = " AND (SELECT ip3.id FROM itens_palavras ip3 WHERE ip3.id_palavra = p.id AND ip3.id_concordancia = ".$_GET['c3']." AND ip3.id_item = ".$_GET['i3']." AND ip3.usar = 1 ) IS NOT NULL ";
      $homons = mysqli_query($GLOBALS['dblink'],
        "SELECT * FROM itensConcordancias WHERE padrao = 1 AND id_concordancia = ".$_GET['c3']." AND depende = 0 AND id = ".$_GET['i3']
        ) or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($homons)>0) $padrao = true; //else $padrao = false;
    }
    if ($_GET['gen'] > 0){
      // add c2 i2
      $qp4 = " AND (SELECT ig.id FROM classesGeneros ig WHERE ig.id_palavra = p.id AND ig.id_genero = ".$_GET['gen']." ) IS NOT NULL ";
    }
    
    if ($padrao == true){
      
      $query = "SELECT p.*,
          (SELECT e.id_fonte FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1 LIMIT 1) as fonte,
          (SELECT e.tamanho FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1 LIMIT 1) as tamanho,
          (SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1 LIMIT 1) as eid,
          (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = p.id AND pn.id_escrita = (
                SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1 LIMIT 1
                ) LIMIT 1) as nativa 
        FROM palavras p 
        WHERE (p.id_forma_dicionario = 0 ".$k.$qp4.")
        ORDER BY RAND()";
    }else{
        
      $qp1 = " AND (SELECT ip1.id FROM itens_palavras ip1 WHERE ip1.id_palavra = p.id AND ip1.id_concordancia = ".$_GET['c1']." AND ip1.id_item = ".$_GET['i1']." AND ip1.usar = 1 ) IS NOT NULL ";
      $query = "SELECT p.*,
            (SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1 LIMIT 1) as eid,
            (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = p.id AND pn.id_escrita = (
                  SELECT e.id FROM escritas e WHERE e.id_idioma = p.id_idioma AND e.padrao = 1 LIMIT 1
                  ) LIMIT 1) as nativa 
          FROM palavras p 
          WHERE (p.id_forma_dicionario > 0 ".$qp1.$qp2.$qp3.$qp4.")
          ORDER BY RAND()";
    }

    $homons = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
    if (mysqli_num_rows($homons)>1){
              while($homon = mysqli_fetch_assoc($homons)){
                $nat = getSpanPalavraNativa($homon['nativa'],$homon['eid'],$homon['fonte'],$homon['tamanho']);
                $return .= '<a href="index.php?page=editword&iid='.$homon['id_idioma'].'&pid='.$homon['id'].'" class="col text-truncate" target="_blank">
                  <div class="text-reset d-block text-truncate">'.$nat.' '.($pnat!=''?$nat.'&nbsp;':'').($homon['romanizacao']!=''?$homon['romanizacao']:'').'</div>
                  <div class="text-secondary text-truncate mt-n1">'.$homon['significado'].'</div>
                </a>';
              }
    }
    echo $return;

    die();
}

if ($_GET['action'] == 'getStudPal') {

  if ( is_numeric($_GET['pid']) && $_GET['pid'] > 0){

      $sql = "SELECT p.*, e.id_fonte, e.tamanho,
        (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = p.id AND pn.id_escrita = e.id
              LIMIT 1) as nativa, 

        (SELECT pd.id FROM palavras pd WHERE pd.id = p.id_forma_dicionario LIMIT 1) as dic,
        (SELECT pd.romanizacao FROM palavras pd WHERE pd.id = p.id_forma_dicionario LIMIT 1) as romandic,
        (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = p.id_forma_dicionario AND pn.id_escrita = e.id
              LIMIT 1) as nativadic, 
              
        (SELECT pd.id FROM palavras pd WHERE pd.id = p.id_derivadora LIMIT 1) as der,
        (SELECT pd.romanizacao FROM palavras pd WHERE pd.id = p.id_derivadora LIMIT 1) as romander,
        (SELECT pn.palavra FROM palavrasNativas pn WHERE pn.id_palavra = p.id_derivadora AND pn.id_escrita = e.id
              LIMIT 1) as nativader, 

        (SELECT k.nome FROM classes k WHERE k.id = p.id_classe LIMIT 1) as classe,
        e.id as eid
        FROM palavras p
        LEFT JOIN escritas e ON e.padrao = 1 AND e.id_idioma = p.id_idioma
        WHERE p.id = '".$_GET['pid']."';"; //WHERE s.id_palavra = '".$_GET['pid']."' AND s.id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";
      $qpstres = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      if (mysqli_num_rows($qpstres)<1){
        $pst = 0;
      }else{
        $qpst = mysqli_fetch_assoc($qpstres);
        
        $gloss = '<br><small  class="text-secondary">'.$qpst['classe'];
        
        // gloss genero
        $b = mysqli_query($GLOBALS['dblink'],"SELECT * FROM classesGeneros cg
          LEFT JOIN generos g ON g.id = cg.id_genero
          WHERE cg.id_palavra = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));
        while($bx = mysqli_fetch_assoc($b)){
          $gloss .= ' '.$bx['nome'];
          // $gloss .= '<br>'.$bx['gloss'].' - '.$bx['nome'];
        }

        // glosses flexoes
        $b = mysqli_query($GLOBALS['dblink'],"SELECT i.*, g.gloss, i.nome as gdesc FROM itens_palavras ip
          LEFT JOIN itensConcordancias i ON ip.id_item = i.id 
            LEFT JOIN gloss_itens gi ON gi.id_item = i.id
            LEFT JOIN glosses g ON gi.id_gloss = g.id 
          WHERE ip.id_palavra = ".$_GET['pid']." AND usar = 1;") or die(mysqli_error($GLOBALS['dblink']));
        while($bx = mysqli_fetch_assoc($b)){
          $gloss .= ' '.$bx['nome'];
          // $gloss .= '<br>'.$bx['gloss'].' - '.$bx['nome'];
        }

        $dicnat = getSpanPalavraNativa($qpst['nativadic'],$qpst['eid'],$qpst['id_fonte'],$qpst['tamanho']);
        $dernat = getSpanPalavraNativa($qpst['nativader'],$qpst['eid'],$qpst['id_fonte'],$qpst['tamanho']);
        if ($qpst['dic']>0) $dic = '<br>'._t('Dicionário').': '.$dicnat.'&nbsp;&nbsp;'.$qpst['romandic'];
        if ($qpst['der']>0) $der = '<br>'._t('Derivada de').': '.$dernat.'&nbsp;&nbsp;'.$qpst['romander'];
        // copiar linha acima (dic) pra derivação tbm
        if ($qpst['pronuncia']!='') $pron = ' &nbsp;/'.$qpst['pronuncia'].'/';

        if ($_GET['edit']==1) $btnEdit = '<br><a href="?page=editword&iid='.$qpst['id_idioma'].'&pid='.$qpst['id'].'" target="_blank" class="text-secondary">'._t('Editar').'</a>'; //<a class="btn btn-sm btn-info btn-rounded" onClick="/*abrirPalavra('.$qpst['id'].')*/">Editar</a>

        echo  '<div class="panelpal form-fieldset"><div class="form-group">'
          .$qpst['romanizacao'].$pron.$gloss.'</small><br>'.$qpst['significado'].$btnEdit.$dic.$der.'</div></div>';
      }

  }else{ // if ($_GET['pid'] == "c")

    $b = mysqli_query($GLOBALS['dblink'],"SELECT * FROM idiomas WHERE id = ".$_GET['iid'].";") or die(mysqli_error($GLOBALS['dblink']));
    $bx = mysqli_fetch_assoc($b);

    //if ($_SESSION['KondisonairUzatorIDX']>0 ) $btnNovo = '<a class="" href="#" onClick="novoSignifCom()">'._t('Adicionar').'</a>'; else $btnNovo = '';
    
    $b = mysqli_query($GLOBALS['dblink'],
      "SELECT *,
        (SELECT COUNT(*) FROM sosail_joes WHERE tipo_destino = 'sigcom' AND id_destino = p.id AND valor = 1) as likes,
        (SELECT COUNT(*) FROM sosail_joes WHERE tipo_destino = 'sigcom' AND id_destino = p.id AND valor = -1) as dislikes
       FROM pal_sig_comunidade p WHERE id_idioma = ".$_GET['iid']." AND palavra = '".$_GET['pal']."'
       ORDER BY likes - dislikes DESC;") or die(mysqli_error($GLOBALS['dblink']));
    

    //if (mysqli_num_rows($b)<1) 
    //  echo  '<div class="panelpal"><div class="form-group">'.($btnNovo == ''?"Significados da comunidade":'Adicionar significado').'<br>';
    //else 
      echo  '<div class="panelpal form-fieldset"><div class="">'._t('Significados da comunidade').'</div>';
    while($bx = mysqli_fetch_assoc($b)){
      echo '<div class="col-lg-12">'.$bx['significado'].
        '<a class=" text-secondary" onClick="sgL('.$bx['id'].')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-thumb-up"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 3a3 3 0 0 1 2.995 2.824l.005 .176v4h2a3 3 0 0 1 2.98 2.65l.015 .174l.005 .176l-.02 .196l-1.006 5.032c-.381 1.626 -1.502 2.796 -2.81 2.78l-.164 -.008h-8a1 1 0 0 1 -.993 -.883l-.007 -.117l.001 -9.536a1 1 0 0 1 .5 -.865a2.998 2.998 0 0 0 1.492 -2.397l.007 -.202v-1a3 3 0 0 1 3 -3z" /><path d="M5 10a1 1 0 0 1 .993 .883l.007 .117v9a1 1 0 0 1 -.883 .993l-.117 .007h-1a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-7a2 2 0 0 1 1.85 -1.995l.15 -.005h1z" /></svg>'.$bx['likes'].'</a> 
        <a class="text-secondary" onClick="sgD('.$bx['id'].')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-thumb-down"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 21.008a3 3 0 0 0 2.995 -2.823l.005 -.177v-4h2a3 3 0 0 0 2.98 -2.65l.015 -.173l.005 -.177l-.02 -.196l-1.006 -5.032c-.381 -1.625 -1.502 -2.796 -2.81 -2.78l-.164 .008h-8a1 1 0 0 0 -.993 .884l-.007 .116l.001 9.536a1 1 0 0 0 .5 .866a2.998 2.998 0 0 1 1.492 2.396l.007 .202v1a3 3 0 0 0 3 3z" /><path d="M5 14.008a1 1 0 0 0 .993 -.883l.007 -.117v-9a1 1 0 0 0 -.883 -.993l-.117 -.007h-1a2 2 0 0 0 -1.995 1.852l-.005 .15v7a2 2 0 0 0 1.85 1.994l.15 .005h1z" /></svg>'.$bx['dislikes'].'</a>';
      if ($btnNovo == '') echo ' <a class="text-secondary" onClick="remSignifCom('.$bx['id'].')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></a>';
      echo '</div>';
    }
    echo $btnNovo.'&nbsp;</div> ';
  }

  die();
};

if ($_GET['action'] == 'studasonPalSigSalvar') { 

    $sql = "INSERT INTO pal_sig_comunidade SET id = ".generateId().",
      palavra = '".$_GET['p']."',  
      id_idioma = ".$_GET['iid'].",
      id_usuario = '".$_SESSION['KondisonairUzatorIDX']."', 
      significado = '".$_GET['s']."';";
    $langs = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

    die('ok');
};

if ($_GET['action'] == 'studasonPalSalvar') { 

    if (!$_SESSION['KondisonairUzatorIDX']>0) die('invalid');

    // parse aqui ou ao abrir?
    if ($_GET['aid']>0){
      //updatew
      $sql = "UPDATE studason_palavrs SET
        status_aprendido = ".$_GET['s'].",  
        pids = '".$_GET['pids']."' 
        WHERE id = ".$_GET['aid'].";";
      $langs = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['aid'];
    }else{
      $id = generateId();
      $sql = "INSERT INTO studason_palavrs SET id = $id,
        pids = '".$_GET['pids']."' ,
        status_aprendido = ".$_GET['s'].",
        id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";

      $langs = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
      echo $id;
    }

    die();

};

if ($_GET['action'] == 'publicaTexto') { 


  $query = "SELECT t.*,
    (SELECT separadores FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as separadores,
        (SELECT binario FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as binario,
    (SELECT iniciadores FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as iniciadores,
    (SELECT id FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as eid
    FROM studason_tests t
    WHERE t.id = ".$_GET['id']." AND t.id_usuario = ".$_SESSION['KondisonairUzatorIDX']." ;";

  $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
  $separadorLinhas = array("\n");
  while($r = mysqli_fetch_assoc($result)){
    if ($r['num_palavras']>0) die('jaw');

    $textoSentencas = $r['texto'];
    if ($r['binario']>0) $bin = ' BINARY ';
    $id_idioma = $r['id_idioma'];
    $eid = $r['eid'];

    $separadorPalavras = preg_split('//u', $r['separadores'] ?? $separadorRomanizacao, null, PREG_SPLIT_NO_EMPTY); // explode($e['separadores']); // array(" ",",",".");

    $iniciadoresPalavras = preg_split('//u', $r['iniciadores'], null, PREG_SPLIT_NO_EMPTY);
    foreach ($iniciadoresPalavras as $sep){
      $textoSentencas = str_replace($sep," ".$sep,$textoSentencas);
    }

    $palDesc = 0;
    $palCon = 0;
    $palTotal = 0;
    $palStud = 0;
    $palNovas = 0;
    $palOk = 0;

    $btnConferir = '';

    $listaPalavrasUnicas = array();

    $linhas = multiexplode($separadorLinhas,$textoSentencas);
    
    foreach ($linhas as $linha){

      $palavras = separarPalavrasLinha($separadorPalavras,$linha,$id_idioma, $eid, $bin, '','')[0];

      foreach ($palavras as $p){
        if ($p == '') continue;
        $pids = '';
      
        if ($eid > 0){
        $sql = "SELECT p.*, c.id as clid, pn.palavra as nativa, c.nome as cnome,
            (SELECT pd.id FROM palavras pd WHERE pd.id = p.id_forma_dicionario LIMIT 1) as dic  
          FROM palavras p
          LEFT JOIN classes c ON p.id_classe = c.id 
          LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id 
          WHERE ".$bin." pn.palavra = '".$p."' AND p.id_idioma = ".$id_idioma." 
          ORDER BY p.id_forma_dicionario DESC;"; 
        }else{
          $sql = "SELECT p.*, c.id as clid, p.romanizacao as nativa, c.nome as cnome,
              (SELECT pd.id FROM palavras pd WHERE pd.id = p.id_forma_dicionario LIMIT 1) as dic  
            FROM palavras p
            LEFT JOIN classes c ON p.id_classe = c.id 
            WHERE ".$bin." p.romanizacao = '".$p."' AND p.id_idioma = ".$id_idioma." 
            ORDER BY p.id_forma_dicionario DESC;"; 
        }
          // AND p.id_forma_dicionario = 0
        //echo $sql;
        $a = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
        $palTotal++;
        if (mysqli_num_rows($a)<1){
          $palDesc++;
        }else{
          $palCon++;
          
          while($qpid = mysqli_fetch_assoc($a)){
            $pids .= $qpid['id'].',';
          }
          
          if ($listaPalavrasUnicas[$p]['q'] > 0) $listaPalavrasUnicas[$p]['q'] = $listaPalavrasUnicas[$p]['q'] + 1;
          else $listaPalavrasUnicas[$p]['q'] = 1;
          
          $sqlPst = "SELECT * FROM studason_palavrs WHERE pids LIKE '".substr($pids,0,-1)."%' AND id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";
          $qpstres = mysqli_query($GLOBALS['dblink'],$sqlPst) or die(mysqli_error($GLOBALS['dblink']));
          if (mysqli_num_rows($qpstres)<1){
            $palNovas++;
          }else{
            $qpst = mysqli_fetch_assoc($qpstres);
            $pst = $qpst['status_aprendido']==''?'0':$qpst['status_aprendido'];

            $listaPalavrasUnicas[$p]['s'] = $pst;

            if ($qpst['status_aprendido']==5) $palOk++;
            else if ($qpst['status_aprendido']>0) $palStud++;
            else $palNovas++;
          }
        }
        // echo ' '.$p.' ';
      }
    }

    $novasUnicas = 0;
    foreach($listaPalavrasUnicas as $pal => $pu){if ($pu['s']<1) { $novasUnicas++; } };

    if ($palDesc>0) { // ;
      die(_t('Ainda há %1 palavras fora do dicionário', [$palDesc]));
    }else{
      //update 
      mysqli_query($GLOBALS['dblink'],"UPDATE studason_tests SET
        num_palavras = ".$novasUnicas."
        WHERE id = ".$r['id'].";") or die(mysqli_error($GLOBALS['dblink']));
    }
        
  };

  die('ok');
};

if ($_GET['action'] == 'testSalvar') { 

  // parse aqui ou ao abrir?
  if ($_GET['id']>0){
    //updatew
    $sql = "UPDATE studason_tests SET
      titulo = '".$_POST['titulo']."',
      link_origem = '',
      link_audio = '',
      data_modificacao = now(),
      texto = '".$_POST['texto']."',
      num_palavras = 0
      WHERE id = ".$_GET['id'].";";
  }else{

    //xxxxx LIMIT textos/aulas

    $sql = "INSERT INTO studason_tests SET id = ".generateId().",
      id_idioma = ".$_GET['iid'].",
      titulo = '".$_POST['titulo']."',
      link_origem = '',
      link_audio = '',
      texto = '".$_POST['texto']."',
      num_palavras = 0,
      id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";
  }

  //echo $sql;
  $langs = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));

  die('ok');

};

if ($_GET['action'] == 'getGlobalFonts') {   
    $globalcustomfonts = '';
    $ees = mysqli_query($GLOBALS['dblink'],"SELECT e.id, e.tamanho, f.arquivo FROM escritas e 
            LEFT JOIN fontes f ON f.id = e.id_fonte 
            WHERE publica = 1 OR id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink']));
    while($e = mysqli_fetch_assoc($ees)){
      $globalcustomfonts .= "@font-face { font-family: CustomFont".$e['arquivo']."; src: url('fonts/".$e['arquivo']."'); } 
          .custom-font-".$e['id']." { font-family: CustomFont".$e['arquivo']."; font-size: ".$e['tamanho']." !important; }\n";
    }
    echo $globalcustomfonts;
    die();
};

if ($_GET['action'] == 'getKSC') {  // Kondisonair Sound Changer
    $v=''; if ($_POST['v']>0) $v = $_POST['v'];
    
    require_once 'ksc'.$v.'.php'; // C Grok

    // GET iid: para pegar as classes e exceções das seções som do idioma, igual header

    $rules = isset($_POST['regras']) ? parseSoundChangeRules($_POST['regras'])/*explode("\n", trim($_POST['regras']))*/ : [];
    
    if (strlen($_POST['classes'])>2){
      $classes = textoParaArrayClasses($_POST['classes']);
    }else if ($_GET['iid']>0){
      $classes = getSCHeader('ksc',$_GET['iid'],'classes');
    }else{
      $classes = [];
    };
    $words = explode("\n",$_POST['palavras']);
    
    if (strlen($_POST['substituicoes'])>1){
      $substitutions = textoParaArraySubstituicoes($_POST['substituicoes']);
    }else if ($_GET['iid']>0){
      $substitutions = getWordGenConfig($_GET['iid'])['substitutions'];
    }else{
      $substitutions = [];
    };

    // Processar regras e aplicar mudanças
    [$results, $erros, $intermediateData] = applySoundChanges($words, $rules, $substitutions, $classes);

    // Preparar resposta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'words' => $results,
        'errors' => $erros,
        'intermediate' => $intermediateData['intermediate'],
        'rules' => $intermediateData['rules']
    ]);
    die();
};

if ($_GET['action'] == 'getMultiKSC') {
    $v = isset($_POST['v']) && $_POST['v'] > 0 ? $_POST['v'] : '';
    require_once 'ksc' . $v . '.php';

    $rules = isset($_POST['regras']) ? json_decode($_POST['regras'], true) : [];
    foreach($rules as $key => $rule) {
        $rules[$key] = parseSoundChangeRules($rule)['default'];
    }

    $classes = strlen($_POST['classes']) > 2 ? textoParaArrayClasses($_POST['classes']) : ($_GET['iid'] > 0 ? getSCHeader('ksc', $_GET['iid'], 'classes') : []);
    $words = explode("\n", trim($_POST['palavras']));
    $substitutions = strlen($_POST['substituicoes']) > 1 ? textoParaArraySubstituicoes($_POST['substituicoes']) : ($_GET['iid'] > 0 ? getWordGenConfig($_GET['iid'])['substitutions'] : []);

    [$results, $erros] = applySoundChangesByGroup($words, $rules, $substitutions, $classes);

    header('Content-Type: application/json');
    echo json_encode([
        'words' => $results,
        'errors' => $erros
    ]);
    die();
}

if ($_GET['action'] == 'getKWG') {  // Kondisonair Word Generator
    require_once 'kwg.php';// Grok

    $wordCount = 10;
    $minSyllables = 1;
    $maxSyllables = $maxSilabas;

    if($_GET['iid']>0){
        $config = getWordGenConfig($_GET['iid']);
        $classes = $config['classes'];
        $syllableFormats = $config['syllableFormats'];
        $maxSyllables = $config['silabas']>0?$config['silabas']:$maxSyllables;

        $substitutions = $config['substitutions'];
        $restrictions = $config['restrictions'];
    }else{
        if (strlen($_POST['silabas'])>2){
          $text = $_POST['silabas'];
          $formats = preg_split('/\s+/', trim(str_replace("\n", " ", $text)));
          $syllableFormats = array_map(function($format) {
              return ['format' => $format, 'weight' => 1];
          }, $formats);
        }else{
          die();
        };

        if (strlen($_POST['classes'])>2){
          $classes = textoParaArrayClasses($_POST['classes']);
        }else{
        };
        
        $substitutions = [];
        $restrictions = [];
    }

    if ($_GET['count']>0) $wordCount = (int)$_GET['count'];

    $result = generateWords($classes, $syllableFormats, $substitutions, $restrictions, $minSyllables, $maxSyllables, $wordCount);

    foreach($result as $w) echo $w."\n";
    die();
};

if($_GET['gason']!=''&& $_SESSION['KondisonairUzatorIDX']>0){ 
  require("modules/".$_GET['gason'].".php");
  die();
};

if ($_GET['action'] == 'ajaxGravarStat') {
  $sid = (int)$_GET['sid'];
  $rid = (int)$_GET['rid'];
  $nome = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome']);
  $tipo_dado = in_array($_POST['tipo_dado'], ['integer', 'decimal', 'text']) ? $_POST['tipo_dado'] : 'integer';
  $descricao = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao']);
  $tipo_entidade =(int)$_POST['tipo_entidade'];

  if ($sid > 0) {
      $sql = "UPDATE stats SET 
          titulo = '$nome',
          tipo = '$tipo_dado',
          tipo_entidade = '$tipo_entidade', data_modificacao = NOW(),
          descricao = '$descricao'
          WHERE id = $sid AND id_realidade = $rid LIMIT 1;";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
  } else {
      $sid = generateId();
      $sql = "INSERT INTO stats SET  id = $sid,
          id_realidade = $rid,
          titulo = '$nome',
          id_usuario = ".$_SESSION['KondisonairUzatorIDX'].",
          tipo = '$tipo_dado',
          tipo_entidade = '$tipo_entidade',
          descricao = '$descricao';";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
  }

  echo $sid;
  die();
}

if ($_GET['action'] == 'getAcessoColaborador') {
  $iid = (int)$_GET['iid'];
  $existentes = mysqli_query($GLOBALS['dblink'],
        "SELECT * FROM collabs WHERE 
          id_idioma = ".$iid." AND
          id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die('Erro');
  if (mysqli_num_rows($existentes) > 0) die('ok');
  if ($iid > 0){
    mysqli_query($GLOBALS['dblink'],
        "INSERT INTO collabs SET 
          id_idioma = ".$iid.", id = ".generateId().",
          id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die('Erro');
  };
  die('ok');
};

if ($_GET['action'] == 'listStats') {
  $rule = strlen($_GET['et']) > 0 ? $_GET['et'] : 'other';
  $rid = (int)$_GET['rid'];
  $result = mysqli_query($GLOBALS['dblink'], "SELECT s.id, s.titulo as nome, s.tipo as tipo_dado, s.tipo_entidade, s.descricao, t.nome as entidade 
      FROM stats s LEFT JOIN entidades_tipos t ON s.tipo_entidade = t.id
      WHERE s.id_realidade = $rid 
      -- AND ( t.rule = '$rule' OR s.tipo_entidade = 0 )
      GROUP BY s.id
      ORDER BY s.titulo;") or die(mysqli_error($GLOBALS['dblink']));
  $html = '';
  while ($s = mysqli_fetch_assoc($result)) {
      $tipo_dado = $s['tipo_dado'] == 'integer' ? _t('Inteiro') : ($s['tipo_dado'] == 'decimal' ? _t('Decimal') : _t('Texto'));
      $tipo_entidade = $s['tipo_entidade'] > 0 ? $s['entidade'] : _t('Qualquer');
      $html .= '<div id="row_'.$s['id'].'" class="list-group-item" onclick="abrirStat('.$s['id'].')">
          <div class="row">
              <div class="col">'.htmlspecialchars($s['nome']).'<br><small>'._t('Tipo').': '.$tipo_dado.' | '._t('Entidade').': '.$tipo_entidade.'</small></div>
              <div class="col-auto"><a class="btn btn-sm btn-danger" onclick="event.stopPropagation(); delStat('.$s['id'].')">X</a></div>
          </div>
      </div>';
  }
  echo $html ?: '<div class="list-group-item">'._t('Nenhum tipo de estatística cadastrado.').'</div>';
  die();
}

if ($_GET['action'] == 'getDetalhesStat') {
  $sid = (int)$_GET['sid'];
  $result = mysqli_query($GLOBALS['dblink'], "SELECT id, titulo as nome, tipo as tipo_dado, tipo_entidade, descricao 
      FROM stats 
      WHERE id = $sid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  $stats = [];
  if ($row = mysqli_fetch_assoc($result)) {
      $stats[] = $row;
  }
  echo json_encode($stats);
  die();
}

if ($_GET['action'] == 'ajaxDelStat') {
  $sid = (int)$_GET['sid'];
  // Verificar se há valores associados
  $result = mysqli_query($GLOBALS['dblink'], "SELECT COUNT(*) as count FROM stats_entidades WHERE id_stat = $sid;") or die(mysqli_error($GLOBALS['dblink']));
  $row = mysqli_fetch_assoc($result);
  if ($row['count'] > 0) {
      echo _t('Não é possível apagar este tipo de estatística, pois há valores associados.');
      die();
  }
  mysqli_query($GLOBALS['dblink'], "DELETE FROM stats WHERE id = $sid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  echo 'ok';
  die();
}

if ($_GET['action']=='ajaxGravarRealidade') {
  /* 
  collabs: $('#collabs').val()
  */

  if($_GET['rid']>0){ 
    $sqlQuerys = "UPDATE realidades SET 
      titulo = '".$_POST['titulo']."',
      descricao = '".str_replace("'",'"',$_POST['descricao'])."',
      publico = '".$_POST['publico']."',
      id_idioma_descricao = '".$_POST['idioma_descricao']."',
      status = '".$_POST['status']."',
      id_usuario = ".$_SESSION['KondisonairUzatorIDX']."
      WHERE id = ".$_GET['rid']." LIMIT 1;";
    mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
    $iid = $_GET['rid'];

    //logAcao(1,'diom',$iid);

  } else {  
      $iid = generateId();
      $sqlQuerys = "INSERT INTO realidades SET  id = $iid,
      titulo = '".$_POST['titulo']."',
      descricao = '".str_replace("'",'"',$_POST['descricao'])."',
      publico = '".$_POST['publico']."',
      id_idioma_descricao = '".$_POST['idioma_descricao']."',
      status = '".$_POST['status']."',
      id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";";

      mysqli_query($GLOBALS['dblink'],$sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      //logAcao(0,'diom',$iid);
  }; 
  echo $iid;
  die();
};

if ($_GET['action'] == 'ajaxLoadUnidadesTempo') {
    $sid = (int)$_GET['sid'];
    $result = mysqli_query($GLOBALS['dblink'], "SELECT u.*, c.id_unidade_ref, c.quantidade, c2.quantidade as quantidade_sub, c2.id_unidade_ref as id_ref_sub, r.nome as ref_nome 
        FROM time_units u 
        LEFT JOIN time_cycles c ON c.id_unidade = u.id 
        LEFT JOIN time_units r ON r.id = c.id_unidade_ref 
        LEFT JOIN time_cycles c2 ON c2.id_unidade = c.id_unidade_ref 
        WHERE u.id_time_system = $sid;") or die(mysqli_error($GLOBALS['dblink']));
    $html = '';
    while ($u = mysqli_fetch_assoc($result)) {
        $ref = $u['id_unidade_ref'] > 0 ? '<br><small>'._t('Ref.: ').htmlspecialchars($u['ref_nome']).' (x'.$u['quantidade'].')</small>' : '';

        $subNames = [];
        $subNamesResult = mysqli_query($GLOBALS['dblink'], "SELECT nome, posicao,quantidade_subunidade 
            FROM time_names 
            WHERE id_time_system = $sid AND id_unidade = {$u['id']} 
            ORDER BY posicao;") or die(mysqli_error($GLOBALS['dblink']));
        while ($subName = mysqli_fetch_assoc($subNamesResult)) {
            $subNames[] = ['nome' => addslashes($subName['nome']), 'posicao' => (int)$subName['posicao'], 'quantidade_subunidade' => $subName['quantidade_subunidade'] ];
        }
        $subNamesJson = json_encode($subNames);
        
        $html .= '<div class="list-group-item"><div class="row">
            <div class="col" onclick="addUnidade(\''.$u['id_time_system'].'\',\''.$u['id'].'\',\''.htmlspecialchars($u['nome']).'\','.$u['duracao'].',\''.($u['id_unidade_ref'] ? $u['id_unidade_ref'] : 0).'\','.($u['quantidade'] ? $u['quantidade'] : '0').',\''.htmlspecialchars($u['equivalente']).'\',\''.htmlspecialchars(addslashes($subNamesJson)).'\',\''.($u['id_ref_sub'] ? $u['id_ref_sub'] : 0).'\','.($u['quantidade_sub'] ? $u['quantidade_sub'] : 0).')">
                <a>'.htmlspecialchars($u['nome']).'</a>
                <a class="text-body text-secondary"><br><small>'._t('Duração').': '.$u['duracao'].'s</small>'.$ref.'</a>
            </div>
            <div class="col-auto"><a class="btn btn-sm btn-danger" onclick="apagarUnidade(\''.$u['id'].'\',\''.$u['id_time_system'].'\')">X</a></div>
        </div></div>';
        
    }
    echo $html;
    die();
}

if ($_GET['action'] == 'listEntityTypes') {
  $rule = strlen($_GET['rule']) > 0 ? $_GET['rule'] : 'other';
  $result = mysqli_query($GLOBALS['dblink'], "SELECT t.*, s.nome as superior_nome 
      FROM entidades_tipos t 
      LEFT JOIN entidades_tipos s ON s.id = t.id_superior 
      WHERE t.id_realidade = " . (int)$_GET['rid'] . " AND t.rule = '$rule';") or die(mysqli_error($GLOBALS['dblink']));
  
  while ($r = mysqli_fetch_assoc($result)) {
      $superior = '';
      if ($r['id_superior'] > 0 && $r['superior_nome'] != '') {
          $superior = '<br><small>' . _t('Tipo pai') . ': ' . htmlspecialchars($r['superior_nome']) . '</small>';
      }
      
      $descricao = '';
      if ($r['descricao'] != '') {
          $descricao = '<br><small>' . htmlspecialchars(substr($r['descricao'], 0, 50)) . (strlen($r['descricao']) > 50 ? '...' : '') . '</small>';
      }

      echo '<div class="list-group-item" id="row_' . $r['id'] . '"><div class="row">
            <div class="col" onClick="abrirTipo(\'' . $r['id'] . '\',\'' . $r['id_superior'] . '\')">
              <a href="#" >' . htmlspecialchars($r['nome']) . '</a>
              <a class="text-body text-secondary">' . $superior . $descricao . '</a> 
            </div><div class="col-auto"><a class="btn btn-sm btn-danger" onclick="delTipo(\'' . $r['id'] . '\')">X</a></div>
          </div></div>';
  }
  die();
}

if ($_GET['action'] == 'getDetalhesEntityType') {
  $result = mysqli_query($GLOBALS['dblink'], "SELECT id, nome, descricao, id_superior FROM entidades_tipos WHERE id = " . (int)$_GET['tid'] . " LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  $data = [];
  while ($row = mysqli_fetch_assoc($result)) {
      $data[] = $row;
  }
  echo json_encode($data);
  die();
}

if ($_GET['action'] == 'ajaxGravarEntityType') {
  $rule = strlen($_GET['rule']) > 0 ? $_GET['rule'] : 'other';
  if ($_GET['tid'] > 0) {
      $sqlQuerys = "UPDATE entidades_tipos SET 
          nome = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome']) . "',
          id_superior = " . (int)$_POST['superior'] . ",
          descricao = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao']) . "',
          data_modificacao = NOW()
          WHERE id = " . (int)$_GET['tid'] . " LIMIT 1;";
      mysqli_query($GLOBALS['dblink'], $sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      echo $_GET['tid'];
  } else {
      $eid = generateId();
      $sqlQuerys = "INSERT INTO entidades_tipos SET  id = $eid,
          nome = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome']) . "',
          id_superior = " . (int)$_POST['superior'] . ",
          rule = '$rule',
          id_usuario = ".(int)$_SESSION['KondisonairUzatorIDX'].",
          descricao = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao']) . "',
          id_realidade = " . (int)$_GET['rid'] . ";";
      mysqli_query($GLOBALS['dblink'], $sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
      echo $eid;
  }
  die();
}

if ($_GET['action'] == 'ajaxDelEntityType') {
  $sqlQuerys = "DELETE FROM entidades_tipos WHERE id = " . (int)$_GET['tid'] . " LIMIT 1;";
  mysqli_query($GLOBALS['dblink'], $sqlQuerys) or die(mysqli_error($GLOBALS['dblink']));
  echo 'ok';
  die();
}

if ($_GET['action'] == 'ajaxGravarEntidade') {
  $eid = (int)$_GET['eid'];
  $rid = (int)$_GET['rid'];
  $nome = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome']);
  $id_tipo = (int)$_POST['id_tipo'];
  $publico = 1; //(int)$_POST['publico'];
  $descricao_curta = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao_curta']);
  $descricao = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao']);
  $privado = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['privado']);
  $tags = is_array($_POST['tags']) ? $_POST['tags'] : [];
  $rule = strlen($_GET['et']>0) ? $_GET['et'] : 'other';

  if ($eid > 0) {
      $sql = "UPDATE entidades SET 
          nome_legivel = '$nome',
          id_tipo = $id_tipo,
          descricao_curta = '$descricao_curta',
          descricao = '$descricao',
          publico = '$publico',
          data_modificacao = NOW(),
          privado = '$privado'
          WHERE id = $eid LIMIT 1;";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
  } else {
      $eid = generateId();
      $sql = "INSERT INTO entidades SET  id = $eid,
          id_realidade = $rid,
          nome_legivel = '$nome',
          id_tipo = $id_tipo,
          descricao_curta = '$descricao_curta',
          descricao = '$descricao',
          publico = '$publico',
          rule = '$rule',
          id_criador = ".$_SESSION['KondisonairUzatorIDX'].",
          privado = '$privado';";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
  }

  // nomes em outras línguas ou outros locais
  mysqli_query($GLOBALS['dblink'],
      "DELETE FROM entidades_nomes WHERE id_entidade = ".$eid.";") or die(mysqli_error($GLOBALS['dblink']));

  foreach($_POST['oiids'] as $oiid){
    if (strlen($oiid['name'])==0) continue;
    mysqli_query($GLOBALS['dblink'],
      "INSERT INTO entidades_nomes SET id = ".generateId().",
        id_entidade = ".$eid.",
        id_idioma = ".$oiid['iid'].",
        nome = '".$oiid['name']."',
        info = '".$oiid['info']."';") or die(mysqli_error($GLOBALS['dblink']));
  }

  // Atualizar tags
  mysqli_query($GLOBALS['dblink'], "DELETE FROM tags WHERE tipo_dest = 'entity' AND id_dest = $eid;") or die(mysqli_error($GLOBALS['dblink']));
  foreach ($tags as $tag) {
      $sql = "INSERT INTO tags SET id = ".generateId().", tipo_dest = 'entity', id_dest = $eid, tag = '$tag';";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
  }

  echo $eid;
  die();
}

if ($_GET['action'] == 'ajaxGetDetalhesEntidade') {
  $eid = (int)$_GET['eid'];
  $result = mysqli_query($GLOBALS['dblink'], "SELECT e.*, GROUP_CONCAT(t.tag) as tags 
      FROM entidades e 
      LEFT JOIN tags t ON t.tipo_dest = 'entity' AND t.id_dest = e.id 
      WHERE e.id = $eid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  if ($row = mysqli_fetch_assoc($result)) {
      if(!isset($row['tags'])) $row['tags'] = '';

      $row['sigiids'] = array();

      $oiids = mysqli_query($GLOBALS['dblink'],
        "SELECT nome, id_idioma as iid, 
        (SELECT e.id FROM escritas e WHERE e.id_idioma = n.id_idioma AND e.padrao = 1 LIMIT 1) as eid,
        (SELECT i.nome_legivel FROM idiomas i WHERE i.id = n.id_idioma LIMIT 1) as niid, info  
        FROM entidades_nomes n
        WHERE id_entidade = $eid;") or die(mysqli_error($GLOBALS['dblink'])); // AND buscavel = 1
      while($oid = mysqli_fetch_assoc($oiids)) {
        $row['sigiids'][] = $oid;
      };



      echo json_encode($row);
  } else {
      echo '{}';
  }
  die();
}

if ($_GET['action'] == 'ajaxGravarHistoria') {
    $hid = (int)$_GET['hid'];
    $rid = (int)$_GET['rid'];
    $titulo = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['titulo']);
    $status = in_array($_POST['status'], ['rascunho', 'publicado', 'arquivado']) ? $_POST['status'] : 'rascunho';
    $id_superior = (int)$_POST['id_superior'];
    $id_tipo = (int)$_POST['id_tipo'];
    $id_momento = (int)$_POST['id_momento'];
    $descricao = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao']);
    $texto = isset($_POST['texto']) ? "texto='".mysqli_real_escape_string($GLOBALS['dblink'], $_POST['texto'])."'," : '';
    $id_usuario = (int)$_SESSION['KondisonairUzatorIDX'];
    $entidades = isset($_POST['entidades']) ? array_map('intval', $_POST['entidades']) : [];
    $stats_valores = isset($_POST['stats_valores']) ? $_POST['stats_valores'] : [];
    $superior = isset($_POST['id_superior']) ? "id_superior = ".$_POST['id_superior']."," : '';
    $updateEntidades = isset($_GET['update']);

    if ($hid > 0) {
        $sql = "UPDATE historias SET 
            titulo = '$titulo',
            status = '$status',
            $superior
            $texto
            id_tipo = $id_tipo,
            id_momento = $id_momento,
            descricao = '$descricao'
            
            WHERE id = $hid AND id_realidade = $rid LIMIT 1;";
        mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
      } else {
        $hid = generateId();
        $sql = "INSERT INTO historias SET  id = $hid,
            id_realidade = $rid,
            id_usuario = $id_usuario,
            titulo = '$titulo',
            status = '$status',
            $superior
            $texto
            id_tipo = $id_tipo,
            id_momento = $id_momento,
            descricao = '$descricao';";
        mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    }

    if ($updateEntidades/*empty($stats_valores) /*!empty($entidades)/* && $id_momento > 0*/) {

        $sql = "DELETE FROM historias_entidades WHERE id_historia = $hid AND id_realidade = $rid;";
        mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));

        $values = [];
        foreach ($entidades as $id_entidade) {
            $id = generateId();
            $values[] = "($id, $id_entidade, $hid, $rid)";
        }
        $sql = "INSERT INTO historias_entidades (id, id_entidade, id_historia, id_realidade) VALUES " . implode(',', $values) . ";";
        if (!empty($values)) mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    }

    if (/*$id_momento > 0 && */!empty($stats_valores)) {

        // Delete existing stats for this story and moment
        $sql = "DELETE FROM stats_entidades 
            WHERE id_momento = $id_momento;"; // AND id_stat AND id_entidade
        mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));

        // Insert new stats
        $values = [];
        foreach ($stats_valores as $stat) {
            $id_entidade = (int)$stat['id_entidade'];
            $id_stat = (int)$stat['id_stat'];
            $id_entidade_relacionada = (int)$stat['id_entidade_relacionada'];
            $valor = (float)$stat['valor'];
            $id = generateId();
            $values[] = "($id, $id_entidade, $id_momento, $valor, $id_stat, $id_entidade_relacionada)";
        }
        if (!empty($values)) {
            $sql = "INSERT INTO stats_entidades (id, id_entidade, id_momento, valor, id_stat, id_entidade_relacionada) 
                VALUES " . implode(',', $values) . ";";
            mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
        }
    }

    echo $hid;
    die();
}

if ($_GET['action'] == 'ajaxCarregarHistoriaStats') {
    $hid = (int)$_GET['hid'];
    $rid = (int)$_GET['rid'];

    // Carregar detalhes da história
    $result = mysqli_query($GLOBALS['dblink'], "SELECT 
        h.titulo, h.status, h.id_momento, h.descricao, h.texto
        FROM historias h 
        WHERE h.id = $hid AND h.id_realidade = $rid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    
    $historia = mysqli_fetch_assoc($result);
    if (!$historia) {
        echo json_encode([]);
        die();
    }
    $mid = $historia['id_momento'];

    // Carregar entidades relacionadas e seus stats
    $stats = [];
    $entidades = mysqli_query($GLOBALS['dblink'], "SELECT e.id as id_entidade, e.id_tipo, se.id_momento, se.id_entidade_relacionada
        FROM stats_entidades se
        JOIN entidades e ON e.id = se.id_entidade
        WHERE se.id_momento = $mid
        GROUP BY e.id, e.id_tipo, se.id_momento;") or die(mysqli_error($GLOBALS['dblink']));
    
    while ($e = mysqli_fetch_assoc($entidades)) {
        $id_momento_atual = (int)$historia['id_momento'];
        $stats_query = mysqli_query($GLOBALS['dblink'], "SELECT ets.id_stat as id_stat, s.titulo as nome
            FROM entidades_tipos_stats ets
            LEFT JOIN stats s ON s.id = ets.id_stat
            WHERE ets.id_entidade_tipo = {$e['id_tipo']} 
            ORDER BY s.titulo;") or die(mysqli_error($GLOBALS['dblink']));
        
        while ($s = mysqli_fetch_assoc($stats_query)) {
            // Verificar valor para o momento atual
            $valor_query = mysqli_query($GLOBALS['dblink'], "SELECT valor
                FROM stats_entidades
                WHERE id_entidade = {$e['id_entidade']} 
                AND id_momento = $id_momento_atual 
                AND id_momento = $mid
                AND id_stat = {$s['id_stat']} LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
            
            $valor_data = mysqli_fetch_assoc($valor_query);
            if ($valor_data) {
                $stats[] = [
                    'id_entidade' => (int)$e['id_entidade'],
                    'id_stat' => (int)$s['id_stat'],
                    'valor' => $valor_data['valor'],
                    'id_entidade_relacionada' => (int)$e['id_entidade'],
                    'aviso' => null
                ];
            } else {
                // Buscar valor em momentos anteriores
                $prior_query = mysqli_query($GLOBALS['dblink'], "SELECT se.valor, m.nome as momento_nome
                    FROM stats_entidades se
                    JOIN momentos m ON m.id = se.id_momento
                    WHERE se.id_entidade = {$e['id_entidade']} 
                    AND se.id_stat = {$s['id_stat']} 
                    AND m.ordem < (SELECT ordem FROM momentos WHERE id = $id_momento_atual)
                    ORDER BY m.ordem DESC, m.id DESC LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
                
                $prior_data = mysqli_fetch_assoc($prior_query);
                if ($prior_data) {
                    $stats[] = [
                        'id_entidade' => (int)$e['id_entidade'],
                        'id_stat' => (int)$s['id_stat'],
                        'valor' => $prior_data['valor'],
                        'id_entidade_relacionada' => (int)$e['id_entidade'],
                        'aviso' => htmlspecialchars($prior_data['momento_nome'])
                    ];
                } else {
                    $stats[] = [
                        'id_entidade' => (int)$e['id_entidade'],
                        'id_stat' => (int)$s['id_stat'],
                        'valor' => '',
                        'id_entidade_relacionada' => (int)$e['id_entidade'],
                        'aviso' => null
                    ];
                }
            }
        }
    }

    echo json_encode([
        'titulo' => htmlspecialchars($historia['titulo']),
        'status' => $historia['status'],
        'id_momento' => (int)$historia['id_momento'],
        'descricao' => htmlspecialchars($historia['descricao'] ?: ''),
        'texto' => $historia['texto'] ?: '',
        'stats' => $stats
    ]);
    die();
}

if ($_GET['action'] == 'getDetalhesHistoria') {
    $hid = (int)$_GET['hid'];
    $rid = (int)$_GET['rid'];

    $result = mysqli_query($GLOBALS['dblink'], "SELECT 
        h.id, h.titulo, h.status, h.id_superior, h.id_tipo, h.id_momento, h.descricao, h.texto,
        (SELECT GROUP_CONCAT(id_entidade) FROM historias_entidades WHERE id_historia = h.id) as entidades
        FROM historias h 
        WHERE h.id = $hid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    
    $historias = [];
    while ($h = mysqli_fetch_assoc($result)) {
        $historias[] = [
            'id' => (int)$h['id'],
            'titulo' => htmlspecialchars($h['titulo']),
            'status' => $h['status'],
            'id_superior' => (int)$h['id_superior'],
            'id_tipo' => (int)$h['id_tipo'],
            'id_momento' => (int)$h['id_momento'],
            'descricao' => htmlspecialchars($h['descricao'] ?: ''),
            //'texto' => $h['texto'] ?: '',
            'entidades' => $h['entidades'] ? explode(',', $h['entidades']) : []
        ];
    }
    
    echo json_encode($historias);
    die();


    $hid = (int)$_GET['hid'];
    $result = mysqli_query($GLOBALS['dblink'], "SELECT id, titulo, status, id_superior, id_tipo, id_momento, descricao, texto 
        FROM historias 
        WHERE id = $hid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    $historias = [];
    if ($row = mysqli_fetch_assoc($result)) {
        $historias[] = $row;
    }
    echo json_encode($historias);
    die();
}

if ($_GET['action'] == 'listStories') {
    $rid = (int)$_GET['rid'];
    $superior = (int)($_GET['superior'] ?? 0); // Pega superior do GET ou usa 0

    $result = mysqli_query($GLOBALS['dblink'], "SELECT h.id, h.titulo, h.status, m.nome as momento, h.descricao 
        FROM historias h 
        LEFT JOIN momentos m ON m.id = h.id_momento 
        WHERE h.id_realidade = $rid AND h.id_superior = $superior 
        ORDER BY h.titulo;") or die(mysqli_error($GLOBALS['dblink']));
    
    $html = '';
    while ($h = mysqli_fetch_assoc($result)) {
        $status = $h['status'] == 'rascunho' ? _t('Rascunho') : ($h['status'] == 'publicado' ? _t('Publicado') : _t('Arquivado'));
        $momento = $h['momento'] ? htmlspecialchars($h['momento']) : _t('Nenhum');
        
        // Contar histórias filhas (dependentes)
        $result_filhas = mysqli_query($GLOBALS['dblink'], "SELECT COUNT(*) as count 
            FROM historias 
            WHERE id_superior = {$h['id']}") or die(mysqli_error($GLOBALS['dblink']));
        $filhas = mysqli_fetch_assoc($result_filhas)['count'];
        
        // Link para histórias filhas, se houver
        $filhas_html = $filhas > 0 
            ? '<a href="?page=editstories&rid='.$rid.'&superior='.$h['id'].'">'._t('Ver').' '.$filhas.' '._t('história(s) filha(s)').'</a>'
            : '<a href="?page=editstories&rid='.$rid.'&superior='.$h['id'].'">'._t('Criar história filha').'</a>';
        
        $html .= '<div id="row_'.$h['id'].'" class="list-group-item" onclick="abrirHistoria(\''.$h['id'].'\')">
            <div class="row">
                <div class="col">'.htmlspecialchars($h['titulo']).'  <a href="?page=editstory&rid='.$rid.'&hid='.$h['id'].'" class="btn btn-sm btn-success">Abrir</a><br>
                    <small>
                        '._t('Status').': '.$status.' | '._t('Momento').': '.$momento.'
                        '.( $h['descricao'] ? '<br><span class="text-secondary">'.htmlspecialchars($h['descricao']).'</span>' : '' ).'                       
                    </small><br>'.$filhas_html.'
                </div>
                <div class="col-auto"><a class="btn btn-sm btn-danger" onclick="event.stopPropagation(); delHistoria(\''.$h['id'].'\')">X</a></div>
            </div>
        </div>';
    }
    
    echo $html ?: '<div class="list-group-item">'._t('Nenhuma história cadastrada.').'</div>';
    die();
}

if ($_GET['action'] == 'ajaxDelHistoria') {
  $hid = (int)$_GET['hid'];
  // Verificar se há histórias filhas
  $result = mysqli_query($GLOBALS['dblink'], "SELECT COUNT(*) as count FROM historias WHERE id_superior = $hid;") or die(mysqli_error($GLOBALS['dblink']));
  $row = mysqli_fetch_assoc($result);
  if ($row['count'] > 0) {
      echo _t('Não é possível apagar esta história, pois há histórias filhas associadas.');
      die();
  }
  mysqli_query($GLOBALS['dblink'], "DELETE FROM historias WHERE id = $hid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  echo 'ok';
  die();
}

if ($_GET['action'] == 'listEntities') {
    $rid = (int)$_GET['rid'];
    $rule = strlen($_GET['et']) > 0 ? $_GET['et'] : 'other';
    $result = mysqli_query($GLOBALS['dblink'], "SELECT e.id, e.nome_legivel as nome, e.id_tipo, e.descricao_curta, et.nome as tipo_nome
        FROM entidades e
        LEFT JOIN entidades_tipos et ON et.id = e.id_tipo
        WHERE e.id_realidade = $rid AND e.rule = '$rule'
        ORDER BY e.nome_legivel ASC;") or die(mysqli_error($GLOBALS['dblink']));
    
    $entidades = [];
    while ($e = mysqli_fetch_assoc($result)) {
        $entidades[] = [
            'id' => $e['id'],
            'nome' => htmlspecialchars($e['nome']),
            'id_tipo' => $e['id_tipo'],
            'tipo_nome' => htmlspecialchars($e['tipo_nome'] ?? _t('Sem tipo')),
            'descricao' => htmlspecialchars($e['descricao_curta'] ?? '')
        ];
    }
    
    //header('Content-Type: application/json');
    echo json_encode($entidades);
    die();
}

if ($_GET['action'] == 'ajaxGravarStatsTipo') {
    $tid = (int)$_GET['tid'];
    $rid = (int)$_GET['rid'];
    $stats = isset($_POST['stats']) ? array_map('intval', $_POST['stats']) : [];
    
    // Deletar associações existentes
    mysqli_query($GLOBALS['dblink'], "DELETE FROM entidades_tipos_stats 
        WHERE id_entidade_tipo = $tid;") or die(mysqli_error($GLOBALS['dblink']));
    
    // Inserir novas associações
    foreach ($stats as $stat_id) {
        $id = generateId();
        mysqli_query($GLOBALS['dblink'], "INSERT INTO entidades_tipos_stats 
            (id, id_entidade_tipo, id_stat) 
            VALUES ($id, $tid, $stat_id);") or die(mysqli_error($GLOBALS['dblink']));
    }
    
    echo 'ok';
    die();
}

function formatTimeValue($id_time_system, $time_value, $dia, $mes, $ano, $dblink) {
    if (!$id_time_system || $time_value === null) {
        return '';
    }
    $units = [];
    $result = mysqli_query($dblink, "SELECT u.id, u.nome, u.duracao, c.id_unidade_ref, c.quantidade 
        FROM time_units u 
        LEFT JOIN time_cycles c ON c.id_unidade = u.id 
        WHERE u.id_time_system = $id_time_system 
        ORDER BY u.duracao DESC;") or die(mysqli_error($dblink));
    while ($u = mysqli_fetch_assoc($result)) {
        $units[$u['id']] = [
            'nome' => $u['nome'],
            'duracao' => $u['duracao'],
            'ref' => $u['id_unidade_ref'],
            'quantidade' => $u['quantidade'] ?: 1
        ];
    }
    // Usar dia, mes, ano armazenados
    if ($dia !== null && $mes !== null && $ano !== null) {
        $unit_names = array_column($units, 'nome', 'id');
        $mes_nome = $unit_names[2] ?? 'Solis'; // Assumindo id 2 = Solis
        $ano_nome = $unit_names[3] ?? 'Ano';   // Assumindo id 3 = Ano
        return "$dia $mes_nome, $ano_nome $ano";
    }
    return '';
}

if ($_GET['action'] == 'ajaxGravarMomento') {
    $mid = (int)$_GET['mid'];
    $rid = (int)$_GET['rid'];
    $nome = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome']);
    $descricao = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao']);
    $data_calendario = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['data_calendario']); // apenas temporário
    $ordem = (float)$_POST['ordem']; // Use float to handle averages (e.g., 150.5)
    $superior = (int)$_POST['superior'];
    $id_usuario = (int)$_SESSION['KondisonairUzatorIDX'];
    $time_system = (int)$_POST['time_system'];
    $time_value = (int)$_POST['time_value'];

    // Validate inputs
    if (empty($nome)) {
        echo 'Erro: Nome obrigatório.';
        die();
    }

    // Insert or update the moment
    if ($mid > 0) {
        // Update existing moment
        $sql = "UPDATE momentos SET 
            nome = '$nome',
            descricao = '$descricao',
            data_calendario = '$data_calendario',
            id_time_system = $time_system,
            time_value = $time_value,
            ordem = $ordem
            WHERE id = $mid AND id_realidade = $rid LIMIT 1;";
        mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    } else {
      // Insert new moment
        $mid = generateId();
        $sql = "INSERT INTO momentos SET  id = $mid,
            id_realidade = $rid,
            nome = '$nome',
            descricao = '$descricao',
            id_superior = $superior,
            data_calendario = '$data_calendario',
            id_time_system = $time_system,
            time_value = $time_value,
            id_usuario = $id_usuario,
            ordem = $ordem;";
        mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    }

    // Fetch all moments with the same id_realidade and id_superior
    $result = mysqli_query($GLOBALS['dblink'], "SELECT id, time_value, ordem 
        FROM momentos 
        WHERE id_realidade = $rid AND id_superior = $superior 
        ORDER BY time_value ASC, ordem ASC, id ASC;") or die(mysqli_error($GLOBALS['dblink']));
    
    $momentos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $momentos[] = [
            'id' => (int)$row['id'],
            'time_value' => is_null($row['time_value']) ? PHP_INT_MAX : (float)$row['time_value'],
            'ordem' => (float)$row['ordem']
        ];
    }

    // Find the position for the current moment (new or updated)
    $current_moment = [
        'id' => $mid,
        'time_value' => is_null($time_value) ? PHP_INT_MAX : (float)$time_value, // Use provided time_value or max
        'ordem' => (float)$ordem
    ];

    $momentos = array_filter($momentos, function($m) use ($mid) {
        return $m['id'] != $mid; // Remove the current moment if updating
    });
    $momentos[] = $current_moment; // Add the current moment

    // Sort moments by ordem (and id for stability)
    /*usort($momentos, function($a, $b) {
        if ($a['ordem'] == $b['ordem']) {
            return $a['id'] <=> $b['id'];
        }
        return $a['ordem'] <=> $b['ordem'];
    });
    */
    usort($momentos, function($a, $b) {
        // Compare time_value first
        if ($a['time_value'] != $b['time_value']) {
            return $a['time_value'] <=> $b['time_value'];
        }
        // If time_value is equal, compare ordem
        if ($a['ordem'] != $b['ordem']) {
            return $a['ordem'] <=> $b['ordem'];
        }
        // If ordem is equal, compare id
        return $a['id'] <=> $b['id'];
    });

    // Reassign sequential ordem values (1, 2, 3, ...)
    foreach ($momentos as $index => $momento) {
        $new_ordem = $index + 1;
        $sql = "UPDATE momentos SET ordem = $new_ordem WHERE id = {$momento['id']} AND id_realidade = $rid LIMIT 1;";
        mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    }

    echo $mid;
    die();
}

if ($_GET['action'] == 'listMoments') {
    $rid = (int)$_GET['rid'];
    $superior = isset($_GET['superior']) ? (int)$_GET['superior'] : 0;
    $filterType = $_GET['filterType'] ?? 'current';
    $stories = isset($_GET['stories']) ? array_map('intval', (array)$_GET['stories']) : [];
    $moments = isset($_GET['moments']) ? array_map('intval', (array)$_GET['moments']) : [];
    $entities = isset($_GET['entities']) ? array_map('intval', (array)$_GET['entities']) : [];
    $entitiesScope = $_GET['entitiesScope'] ?? 'current';

    // Função para obter todos os IDs de sub-histórias recursivamente
    function getAllStoryIds($story_ids, $dblink, $rid) {
        $all_ids = $story_ids;
        $new_ids = $story_ids;
        do {
            $ids_str = implode(',', $new_ids);
            $result = mysqli_query($dblink, "SELECT id FROM historias WHERE id_superior IN ($ids_str) AND id_realidade = $rid;") or die(mysqli_error($dblink));
            $new_ids = [];
            while ($row = mysqli_fetch_assoc($result)) {
                if (!in_array($row['id'], $all_ids)) {
                    $new_ids[] = $row['id'];
                    $all_ids[] = $row['id'];
                }
            }
        } while (!empty($new_ids));
        return $all_ids;
    }

    // Função para obter todos os IDs de sub-momentos recursivamente
    function getAllMomentIds($moment_ids, $dblink, $rid) {
        $all_ids = $moment_ids;
        $new_ids = $moment_ids;
        do {
            $ids_str = implode(',', $new_ids);
            $result = mysqli_query($dblink, "SELECT id FROM momentos WHERE id_superior IN ($ids_str) AND id_realidade = $rid;") or die(mysqli_error($dblink));
            $new_ids = [];
            while ($row = mysqli_fetch_assoc($result)) {
                if (!in_array($row['id'], $all_ids)) {
                    $new_ids[] = $row['id'];
                    $all_ids[] = $row['id'];
                }
            }
        } while (!empty($new_ids));
        return $all_ids;
    }

    // Função para obter o caminho completo da história
    function getStoryPath($story_id, $dblink, $rid) {
        $path = [];
        $current_id = $story_id;
        while ($current_id > 0) {
            $result = mysqli_query($dblink, "SELECT id, titulo, id_superior 
                FROM historias 
                WHERE id = $current_id AND id_realidade = $rid LIMIT 1;") or die(mysqli_error($dblink));
            if ($story = mysqli_fetch_assoc($result)) {
                $path[] = htmlspecialchars($story['titulo']);
                $current_id = $story['id_superior'];
            } else {
                break;
            }
        }
        return implode(' > ', array_reverse($path));
    }

    // Construir a condição WHERE e JOINs
    $where = "m.id_realidade = $rid";
    $joins = "";
    if ($filterType === 'current') {
        $where .= " AND m.id_superior = $superior";
    } elseif ($filterType === 'all') {
        $all_moment_ids = $superior > 0 ? getAllMomentIds([$superior], $GLOBALS['dblink'], $rid) : [];
        $all_moment_ids[] = $superior;
        $where .= $superior > 0 ? " AND m.id IN (" . implode(',', $all_moment_ids) . ")" : "";
    } elseif ($filterType === 'stories' && !empty($stories)) {
        $all_story_ids = getAllStoryIds($stories, $GLOBALS['dblink'], $rid);
        $where .= " AND h2.id IN (" . implode(',', $all_story_ids) . ")";
        $joins .= " JOIN historias h2 ON h2.id_momento = m.id";
    } elseif ($filterType === 'moments' && !empty($moments)) {
        $all_moment_ids = getAllMomentIds($moments, $GLOBALS['dblink'], $rid);
        $where .= " AND m.id IN (" . implode(',', $all_moment_ids) . ")";
    } elseif ($filterType === 'entities' && !empty($entities)) {
        $where .= " AND he.id_entidade IN (" . implode(',', $entities) . ")";
        $joins .= " JOIN historias h3 ON h3.id_momento = m.id JOIN historias_entidades he ON he.id_historia = h3.id";
        if ($entitiesScope === 'current') {
            $where .= " AND m.id_superior = $superior";
        }
    }

    // Query para buscar momentos com múltiplas histórias e entidades
    $query = "SELECT m.id, m.nome, m.descricao, m.data_calendario, m.ordem, m.time_value,
        (SELECT COUNT(*) FROM momentos sub WHERE sub.id_superior = m.id) as numSubs,
        mp.nome as momento_pai_nome,
        GROUP_CONCAT(DISTINCT h.id) as historia_ids,
        GROUP_CONCAT(DISTINCT h.titulo) as historia_titulos,
        GROUP_CONCAT(DISTINCT e.nome_legivel) as entidade_nomes
        FROM momentos m
        LEFT JOIN historias h ON h.id_momento = m.id AND h.id_realidade = $rid
        LEFT JOIN momentos mp ON mp.id = m.id_superior AND mp.id_realidade = $rid
        LEFT JOIN historias_entidades he ON he.id_historia = h.id
        LEFT JOIN entidades e ON e.id = he.id_entidade AND e.id_realidade = $rid
        $joins
        WHERE $where
        GROUP BY m.id, m.nome, m.descricao, m.data_calendario, m.ordem, m.time_value, mp.nome
        ORDER BY m.time_value, m.ordem ASC;";

    $result = mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
    $momentos = [];
    while ($m = mysqli_fetch_assoc($result)) {
        $historia_ids = $m['historia_ids'] ? explode(',', $m['historia_ids']) : [];
        $historia_titulos = $m['historia_titulos'] ? explode(',', $m['historia_titulos']) : [];
        $entidade_nomes = $m['entidade_nomes'] ? explode(',', $m['entidade_nomes']) : [];

        // Montar caminhos das histórias
        $historia_caminhos = [];
        foreach ($historia_ids as $hid) {
            if ($hid > 0) {
                $caminho = getStoryPath($hid, $GLOBALS['dblink'], $rid);
                $historia_caminhos[] = $caminho;
            }
        }

        $momentos[] = [
            'id' => $m['id'],
            'subs' => (int)$m['numSubs'],
            'nome' => htmlspecialchars($m['nome']),
            'descricao' => htmlspecialchars($m['descricao'] ?? ''),
            'data_calendario' => htmlspecialchars($m['data_calendario']),
            'ordem' => (float)$m['ordem'],
            'time_value' => (int)$m['time_value'],
            'momento_pai_nome' => htmlspecialchars($m['momento_pai_nome'] ?? ''),
            'historias' => array_map(function ($id, $titulo, $caminho) {
                return [
                    'id' => $id,
                    'titulo' => htmlspecialchars($titulo),
                    'caminho' => $caminho
                ];
            }, $historia_ids, $historia_titulos, $historia_caminhos),
            'entidades' => array_map('htmlspecialchars', $entidade_nomes)
        ];
    }
    
    echo json_encode($momentos);
    die();
}

if ($_GET['action'] == 'ajaxGetMomentStats') {
    $mid = (int)$_GET['mid'];
    $rid = (int)$_GET['rid'];
    $html = '';

    // Função recursiva para construir a hierarquia de histórias
    function getStoryHierarchy($dblink, $hid, $rid) {
        $path = [];
        $current_hid = (int)$hid;
        
        while ($current_hid > 0) {
            $result = mysqli_query($dblink, "SELECT id, titulo, id_superior 
                FROM historias 
                WHERE id = $current_hid AND id_realidade = $rid LIMIT 1;") or die(mysqli_error($dblink));
            if ($story = mysqli_fetch_assoc($result)) {
                $path[] = [
                    'id' => $story['id'],
                    'titulo' => htmlspecialchars($story['titulo']),
                    'id_superior' => (int)$story['id_superior']
                ];
                $current_hid = (int)$story['id_superior'];
            } else {
                break; // Evitar loop infinito se id_superior inválido
            }
        }
        
        // Inverter para mostrar da raiz ao filho
        $path = array_reverse($path);
        $links = [];
        $superior = 0; // Superior inicial para a primeira história
        foreach ($path as $story) {
            $links[] = "<a href=\"?page=editstories&rid=$rid&superior=$superior&hid={$story['id']}\">{$story['titulo']}</a>";
            $superior = $story['id']; // Superior para a próxima história
        }
        return implode(' &gt; ', $links);
    }

    if ($mid == 0) {
      /*
        // Estatísticas gerais da realidade
        $result = mysqli_query($GLOBALS['dblink'], "SELECT 
            (SELECT COUNT(*) FROM entidades WHERE id_realidade = $rid) as entidades,
            (SELECT COUNT(*) FROM historias WHERE id_realidade = $rid) as historias,
            (SELECT COUNT(*) FROM stats_entidades WHERE id_momento IN (SELECT id FROM momentos WHERE id_realidade = $rid)) as stats");
        $stats = mysqli_fetch_assoc($result);

        // Listar todas as histórias da realidade
        $stories_result = mysqli_query($GLOBALS['dblink'], "SELECT id, titulo, id_superior 
            FROM historias 
            WHERE id_realidade = $rid 
            ORDER BY id_superior, titulo;") or die(mysqli_error($GLOBALS['dblink']));
        
        $stories_html = '<ul>';
        while ($story = mysqli_fetch_assoc($stories_result)) {
            $hierarchy = getStoryHierarchy($GLOBALS['dblink'], $story['id'], $rid);
            $stories_html .= "<li>$hierarchy</li>";
        }
        $stories_html .= '</ul>';
        if ($stories_html == '<ul></ul>') {
            $stories_html = '<p>'._t('Nenhuma história encontrada.').'</p>';
        }

        $html = "<ul>
            <li><strong>"._t('Entidades').":</strong> {$stats['entidades']}</li>
            <li><strong>"._t('Histórias').":</strong> {$stats['historias']}</li>
            <li><strong>"._t('Estatísticas').":</strong> {$stats['stats']}</li>
        </ul>
        <h4>"._t('Histórias')."</h4>
        $stories_html";
        */
    } else {

        // Listar histórias do momento
        $stories_result = mysqli_query($GLOBALS['dblink'], "SELECT id, titulo, id_superior 
            FROM historias 
            WHERE id_momento = $mid AND id_realidade = $rid 
            ORDER BY id_superior, titulo;") or die(mysqli_error($GLOBALS['dblink']));
        
        $stories_html = '<ul>';
        $ids_stories = '0';
        while ($story = mysqli_fetch_assoc($stories_result)) {
            $hierarchy = getStoryHierarchy($GLOBALS['dblink'], $story['id'], $rid);
            $stories_html .= "<li>$hierarchy</li>";
            $ids_stories .= ','.$story['id'];
        }
        $stories_html .= '</ul>';
        if ($stories_html == '<ul></ul>') {
            $stories_html = '<p>'._t('Nenhuma história encontrada.').'</p>';
        }

        //listar entidades
        $stories_result = mysqli_query($GLOBALS['dblink'], "SELECT e.nome_legivel
            FROM historias_entidades eh 
            LEFT JOIN entidades e ON e.id = eh.id_entidade
            WHERE eh.id_historia IN($ids_stories)
            GROUP BY e.id
            ORDER BY e.nome_legivel;") or die(mysqli_error($GLOBALS['dblink']));
        
        $ent_html = '<ul>';
        while ($story = mysqli_fetch_assoc($stories_result)) {
            if ($story['nome_legivel']) $ent_html .= "<li>{$story['nome_legivel']}</li>";
        }
        $ent_html .= '</ul>';
        if ($ent_html == '<ul></ul>') {
            $ent_html = '<p>'._t('Nenhuma entidade encontrada.').'</p>';
        }

        $html = "<h4>"._t('Histórias')."</h4>
        $stories_html <h4>"._t('Entidades')."</h4>
        $ent_html";

        //xxxxx buscar entidades também
    }

    echo $html;
    die();
}

if ($_GET['action'] == 'ajaxDelMomento') {
  $mid = (int)$_GET['mid'];
  // Verificar dependências
  $result = mysqli_query($GLOBALS['dblink'], "SELECT 
      (SELECT COUNT(*) FROM historias WHERE id_momento = $mid) as historias,
      (SELECT COUNT(*) FROM stats_entidades WHERE id_momento = $mid) as stats");
  $row = mysqli_fetch_assoc($result);
  if ($row['historias'] > 0 || $row['stats'] > 0) {
      echo _t('Não é possível apagar este momento, pois há histórias ou estatísticas associadas.');
      die();
  }
  mysqli_query($GLOBALS['dblink'], "DELETE FROM momentos WHERE id = $mid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  echo 'ok';
  die();
}

if ($_GET['action'] == 'getStatsTipo') {
    $tid = (int)$_GET['tid'];
    $rid = (int)$_GET['rid'];
    
    // Listar todas as estatísticas da realidade
    $result = mysqli_query($GLOBALS['dblink'], "SELECT s.id, s.titulo, s.tipo, e.nome 
        FROM stats s LEFT JOIN entidades_tipos e ON e.id = s.tipo_entidade
        WHERE s.id_realidade = $rid 
        ORDER BY s.titulo;") or die(mysqli_error($GLOBALS['dblink']));
    $stats = [];
    while ($s = mysqli_fetch_assoc($result)) {
        $stats[] = [
            'id' => $s['id'],
            'nome' => htmlspecialchars($s['titulo']),
            'entidade' => $s['nome'] ? htmlspecialchars($s['nome']) : _t("Qualquer entidade"),
            'tipo_dado' => $s['tipo']
        ];
    }
    
    // Listar estatísticas associadas ao tipo
    $result = mysqli_query($GLOBALS['dblink'], "SELECT id_stat 
        FROM entidades_tipos_stats 
        WHERE id_entidade_tipo = $tid;") or die(mysqli_error($GLOBALS['dblink']));
    $associados = [];
    while ($a = mysqli_fetch_assoc($result)) {
        $associados[] = (int)$a['id_stat'];
    }
    
    echo json_encode([
        'stats' => $stats,
        'associados' => $associados
    ]);
    die();
}

if ($_GET['action'] == 'getMomentos') {
    $rid = (int)$_GET['rid'];
    
    $result = mysqli_query($GLOBALS['dblink'], "SELECT * 
        FROM momentos 
        WHERE id_realidade = $rid 
        ORDER BY time_value;") or die(mysqli_error($GLOBALS['dblink']));
    
    $momentos = [];
    while ($m = mysqli_fetch_assoc($result)) {
        $momentos[] = [
            'id' => (int)$m['id'],
            'nome' => $m['nome'],
            'descricao' => $m['descricao'],
            'time_value' => (int)$m['time_value'],
            'data_calendario' => $m['data_calendario']
        ];
    }
    
    echo json_encode(['momentos' => $momentos]);
    die();
}

if ($_GET['action']=='ajaxApagarEntidade') {
    echo 'Não implementado';
    die();
}

if ($_GET['action'] == 'carregarMomento') {
    $id = (int)$_GET['id'];
    $result = mysqli_query($GLOBALS['dblink'], "SELECT id, nome, descricao, ordem, id_time_system, time_value, dia, mes, ano 
        FROM momentos 
        WHERE id = $id LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    if ($m = mysqli_fetch_assoc($result)) {
        $m['data_calendario'] = formatTimeValue($m['id_time_system'], $m['time_value'], $m['dia'], $m['mes'], $m['ano'], $GLOBALS['dblink']);
        echo json_encode($m);
    } else {
        echo json_encode(['error' => 'Momento não encontrado']);
    }
    die();
}

if ($_GET['action'] == 'ajaxSalvarSistemaTempo') {
    if ($_GET['sid'] > 0) {
        $sql = "UPDATE time_systems SET 
            nome = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome']) . "',
            descricao = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao']) . "',
            data_padrao = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['data_padrao']) . "',
            publico = " . (int)$_POST['publico'] . "
            WHERE id = " . (int)$_GET['sid'] . " LIMIT 1;";
        mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
        echo $_GET['sid'];
    } else {
      $tid = generateId(); 
        $sql = "INSERT INTO time_systems SET  id = $tid, 
            nome = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome']) . "',
            descricao = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao']) . "',
            data_padrao = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_POST['data_padrao']) . "',
            publico = " . (int)$_POST['publico'] . ",
            id_realidade = " . (int)$_POST['rid'] . ";";
        mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
        echo $tid;
    }
    die();
}

if ($_GET['action'] == 'ajaxSetSistemaPadrao') {
    mysqli_query($GLOBALS['dblink'], "UPDATE time_systems SET padrao = 0 WHERE id_realidade = " . (int)$_GET['rid'] . ";") or die(mysqli_error($GLOBALS['dblink']));
    mysqli_query($GLOBALS['dblink'], "UPDATE time_systems SET padrao = 1 WHERE id = " . (int)$_GET['sid'] . " LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    echo 'ok';
    die();
}

if ($_GET['action'] == 'ajaxDeleteSistemaTempo') {
    mysqli_query($GLOBALS['dblink'], "DELETE FROM time_systems WHERE id = " . (int)$_GET['sid'] . " LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    echo 'ok';
    die();
}

if ($_GET['action'] == 'ajaxNovoSistemaTempo') {
    $tid = generateId();
    $sql = "INSERT INTO time_systems SET  id = $tid,
        nome = '" . mysqli_real_escape_string($GLOBALS['dblink'], $_GET['n']) . "',
        id_realidade = " . (int)$_GET['rid'] . ";";
    mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    echo $tid;
    die();
}

if ($_GET['action'] == 'ajaxAddUnidadeTempo') {
    $rid = (int)$_GET['rid'];
    $sid = (int)$_GET['sid'];
    $uid = (int)$_GET['uid'];
    $nome = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['nome']);
    $duracao = (float)$_POST['duracao'];
    $equivalente = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['equivalente']);
    $ref = (int)$_POST['ref'];
    $quantidade = (float)$_POST['quantidade'];
    $subNames = json_decode($_POST['subNames'], true);

    if ($uid > 0) {
        // Atualizar unidade existente
        $query = "UPDATE time_units SET nome='$nome', duracao=$duracao, equivalente='$equivalente' 
                  WHERE id=$uid AND id_time_system=$sid AND id_realidade=$rid";
        mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
    } else {
        // Inserir nova unidade
        $uid = generateId();
        $query = "INSERT INTO time_units (id, id_time_system, id_realidade, nome, duracao, equivalente) 
                  VALUES ($uid, $sid, $rid, '$nome', $duracao, '$equivalente')";
        mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
    }

    // Atualizar ou inserir ciclo
    if ($ref > 0 && $quantidade > 0) {
        $cycle_query = "SELECT id FROM time_cycles WHERE id_unidade=$uid AND id_unidade_ref=$ref AND id_time_system=$sid";
        $cycle_result = mysqli_query($GLOBALS['dblink'], $cycle_query);
        if (mysqli_num_rows($cycle_result) > 0) {
            $query = "UPDATE time_cycles SET quantidade=$quantidade WHERE id_unidade=$uid AND id_unidade_ref=$ref AND id_time_system=$sid";
        } else {
            $tcid = generateId();
            $query = "INSERT INTO time_cycles (id, id_time_system, id_unidade, id_unidade_ref, quantidade) 
                      VALUES ($tcid, $sid, $uid, $ref, $quantidade)";
        }
        mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
    }

    // Salvar nomes das subunidades
    if (!empty($subNames)) {
        // Deletar nomes existentes para a unidade
        mysqli_query($GLOBALS['dblink'], "DELETE FROM time_names WHERE id_time_system=$sid AND id_unidade=$uid") or die(mysqli_error($GLOBALS['dblink']));
        // Inserir novos nomes
        foreach ($subNames as $subName) {
            $nome = mysqli_real_escape_string($GLOBALS['dblink'], $subName['nome']);
            $posicao = (int)$subName['posicao'];
            $quantidadeSub = isset($subName['quantidade_subunidade']) ? $subName['quantidade_subunidade'] : 'null';
            $query = "INSERT INTO time_names (id, id_time_system, id_unidade, nome, posicao, quantidade_subunidade) 
                      VALUES (".generateId().", $sid, $uid, '$nome', $posicao, $quantidadeSub)";
            mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
        }
    }

    echo $uid;
    die();
}

// Deletar Unidade
if ($_GET['action'] == 'ajaxDeleteUnidadeTempo') {
    mysqli_query($GLOBALS['dblink'], "DELETE FROM time_units WHERE id = " . (int)$_GET['uid'] . " LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    echo 'ok';
    die();
}

// Obter Duração de Unidade
if ($_GET['action'] == 'ajaxGetUnidadeDuracao') {
    $result = mysqli_query($GLOBALS['dblink'], "SELECT duracao FROM time_units WHERE id = " . (int)$_GET['uid'] . " LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    if ($row = mysqli_fetch_assoc($result)) {
        echo $row['duracao'];
    } else {
        echo 0;
    }
    die();
}

// Verificar Ciclos
if ($_GET['action'] == 'ajaxVerificarCiclos') {
    $uid = (int)$_GET['uid'];
    $sid = (int)$_GET['sid'];
    $result = mysqli_query($GLOBALS['dblink'], "SELECT c.id_unidade_ref, c.quantidade, u.duracao as ref_duracao, u.nome as unidade, udef.nome as referencia 
        FROM time_cycles c 
        JOIN time_units u ON u.id = c.id_unidade_ref 
        JOIN time_units udef ON udef.id = c.id_unidade
        WHERE c.id_unidade = $uid;") or die(mysqli_error($GLOBALS['dblink']));
    if ($row = mysqli_fetch_assoc($result)) {
        $ref_id = $row['id_unidade_ref'];
        $ref_nome = $row['referencia'];
        $quantidade = $row['quantidade'];
        $ref_duracao = $row['ref_duracao'];
        $total_sub = 0;
        $sub_result = mysqli_query($GLOBALS['dblink'], "SELECT SUM(u.duracao * c.quantidade) as total 
            FROM time_cycles c 
            JOIN time_units u ON u.id = c.id_unidade 
            WHERE c.id_unidade_ref = $ref_id AND c.id_time_system = $sid;") or die(mysqli_error($GLOBALS['dblink']));
        if ($sub_row = mysqli_fetch_assoc($sub_result)) {
            $total_sub = $sub_row['total'] ? $sub_row['total'] : 0;
        }
        $esperado = $ref_duracao;
        if (abs($total_sub - $esperado) > 0.01) {
            $diff = $total_sub - $esperado;
            $mensagem = $diff > 0 ? 
                _t('Sobra de ').abs($diff)._t(' segundos em relação à unidade ').$ref_nome :
                _t('Falta de ').abs($diff)._t(' segundos para completar a unidade ').$ref_nome;
            $sugestoes = [];
            if ($diff > 0) {
                $sugestoes['criar_unidade'] = ['duracao' => abs($diff)];
                $sugestoes['redistribuir'] = ['unidade' => $uid, 'quantidade' => $quantidade + ($diff / $ref_duracao)];
            }
            echo json_encode(['mensagem' => $mensagem, 'sugestoes' => $sugestoes]);
        } else {
            echo 'ok';
        }
    } else {
        echo 'ok';
    }
    die();
}

if ($_GET['action'] == 'ajaxAddCicloTempo') {
    $id = generateId();
    $sql = "INSERT INTO time_cycles SET id = $id,
        id_time_system = " . (int)$_GET['sid'] . ",
        id_unidade = " . (int)$_POST['id_unidade'] . ",
        id_unidade_ref = " . (int)$_POST['id_unidade_ref'] . ",
        quantidade = " . (float)$_POST['quantidade'] . "
        ON DUPLICATE KEY UPDATE quantidade = " . (float)$_POST['quantidade'] . ";";
    mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
    echo $id; //mysqli_insert_id($GLOBALS['dblink']) ? mysqli_insert_id($GLOBALS['dblink']) : 1;
    die();
}

if ($_GET['action'] == 'ajaxLoadRelacoes') {
  $eid = (int)$_GET['eid'];
  $result = mysqli_query($GLOBALS['dblink'], "SELECT r.*, e2.nome_legivel as nome_entidade2 
      FROM entidades_relacoes r 
      JOIN entidades e2 ON e2.id = r.id_entidade2 
      WHERE r.id_entidade1 = $eid;") or die(mysqli_error($GLOBALS['dblink']));
  $html = '';
  while ($r = mysqli_fetch_assoc($result)) {
      $html .= '<div class="list-group-item"><div class="row">
          <div class="col" onclick="addRelacao('.$r['id'].','.$r['id_entidade2'].',\''.htmlspecialchars($r['tipo_relacao']).'\',\''.htmlspecialchars($r['descricao']).'\',\''.$r['id_momento_inicio'].'\',\''.$r['id_momento_fim'].'\')">
              <a href="#">'.htmlspecialchars($r['nome_entidade2']).'</a>
              <a class="text-body text-secondary"><br><small>'.htmlspecialchars($r['tipo_relacao']).'</small></a>
          </div>
          <div class="col-auto"><a class="btn btn-sm btn-danger" onclick="apagarRelacao('.$r['id'].')">X</a></div>
      </div></div>';
  }
  echo $html ?: '<div class="list-group-item">'._t('Nenhuma relação cadastrada.').'</div>';
  die();
}

if ($_GET['action'] == 'ajaxAddRelacao') {
  $eid = (int)$_GET['eid'];
  $rid = (int)$_GET['rid'];
  $id_entidade2 = (int)$_POST['id_entidade2'];
  $inicio = (int)$_POST['inicio'];
  $fim = (int)$_POST['fim'];
  $tipo_relacao = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['tipo_relacao']);
  $descricao = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['descricao']);

  if ($rid > 0) {
      $sql = "UPDATE entidades_relacoes SET 
          id_entidade2 = $id_entidade2,
          tipo_relacao = '$tipo_relacao',
          id_momento_inicio = '$inicio',
          id_momento_fim = '$fim',
          descricao = '$descricao'
          WHERE id = $rid LIMIT 1;";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
      echo $rid;
  } else {
      $rid = generateId();
      $sql = "INSERT INTO entidades_relacoes SET  id = $rid,
          id_entidade1 = $eid,
          id_entidade2 = $id_entidade2,
          tipo_relacao = '$tipo_relacao',
          id_momento_inicio = '$inicio',
          id_momento_fim = '$fim',
          descricao = '$descricao';";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
      echo $rid;
  }
  die();
}

if ($_GET['action'] == 'ajaxDeleteRelacao') {
  $rid = (int)$_GET['rid'];
  mysqli_query($GLOBALS['dblink'], "DELETE FROM entidades_relacoes WHERE id = $rid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  echo 'ok';
  die();
}

if ($_GET['action'] == 'ajaxLoadStats') {
  $eid = (int)$_GET['eid'];
  $result = mysqli_query($GLOBALS['dblink'], "SELECT se.*, s.titulo as nome_stat, m.nome as nome_momento 
      FROM stats_entidades se 
      JOIN stats s ON s.id = se.id_stat 
      JOIN momentos m ON m.id = se.id_momento 
      WHERE se.id_entidade = $eid;") or die(mysqli_error($GLOBALS['dblink']));
  $html = '';
  while ($se = mysqli_fetch_assoc($result)) {
      $html .= '<div class="list-group-item"><div class="row">
          <div class="col" onclick="addStat('.$se['id'].','.$se['id_stat'].','.$se['id_momento'].',\''.htmlspecialchars($se['valor']).'\')">
              <a href="#">'.htmlspecialchars($se['nome_stat']).'</a>
              <a class="text-body text-secondary"><br><small>'._t('Valor').': '.htmlspecialchars($se['valor']).' ('._t('em').' '.htmlspecialchars($se['nome_momento']).')</small></a>
          </div>
          <div class="col-auto"><a class="btn btn-sm btn-danger" onclick="apagarStat('.$se['id'].')">X</a></div>
      </div></div>';
  }
  echo $html ?: '<div class="list-group-item">'._t('Nenhuma estatística cadastrada.').'</div>';
  die();
}

if ($_GET['action'] == 'ajaxAddStat') {
  $eid = (int)$_GET['eid'];
  $sid = (int)$_GET['sid'];
  $id_stat = (int)$_POST['id_stat'];
  $id_momento = (int)$_POST['id_momento'];
  $valor = mysqli_real_escape_string($GLOBALS['dblink'], $_POST['valor']);

  // Validar tipo_dado
  $result = mysqli_query($GLOBALS['dblink'], "SELECT tipo FROM stats WHERE id = $id_stat LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  if ($row = mysqli_fetch_assoc($result)) {
      $tipo_dado = $row['tipo_dado'];
      if ($tipo_dado == 'integer' && !is_numeric($valor) || $tipo_dado == 'decimal' && !is_numeric($valor)) {
          echo _t('Valor inválido para o tipo de dado.');
          die();
      }
  }

  if ($sid > 0) {
      $sql = "UPDATE stats_entidades SET 
          valor = '$valor'
          WHERE id_stat = $id_stat 
            AND id_entidade = $eid
            AND id_momento = $id_momento LIMIT 1;";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
      echo $sid;
  } else {
      $sid = generateId();
      $sql = "INSERT INTO stats_entidades SET id = $sid,
          id_entidade = $eid,
          id_stat = $id_stat,
          id_momento = $id_momento,
          valor = '$valor';";
      mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
      echo $sid;
  }
  die();
}

if ($_GET['action'] == 'ajaxDeleteStat') {
  $sid = (int)$_GET['sid'];
  mysqli_query($GLOBALS['dblink'], "DELETE FROM stats_entidades WHERE id = $sid LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
  echo 'ok';
  die();
}

if ($_GET['action'] == 'getDadosCalendario') {
    $id_time_system = (int)$_GET['id'];
    
    // Buscar dados do sistema de tempo
    $result = mysqli_query($GLOBALS['dblink'], "SELECT id, nome, descricao, data_padrao, padrao 
        FROM time_systems 
        WHERE id = $id_time_system LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
    
    if ($time_system = mysqli_fetch_assoc($result)) {
        // Preparar resposta
        $response = [
            'time_system' => [
                'id' => $time_system['id'],
                'nome' => $time_system['nome'],
                'data_padrao' => $time_system['data_padrao'] ? gmdate('c', strtotime($time_system['data_padrao'])) : null,
                'padrao' => $time_system['padrao']
            ],
            'units' => [],
            'cycles' => [],
            'days' => [],
            'months' => [],
            'leap_rules' => [],
            'warnings' => []
        ];
        
        // Buscar unidades de tempo
        $result_units = mysqli_query($GLOBALS['dblink'], "SELECT id, nome, duracao, equivalente 
            FROM time_units 
            WHERE id_time_system = $id_time_system;") or die(mysqli_error($GLOBALS['dblink']));
        
        while ($unit = mysqli_fetch_assoc($result_units)) {
            $response['units'][] = [
                'id' => $unit['id'],
                'nome' => $unit['nome'],
                'duracao' => $unit['duracao'],
                'equivalente' => $unit['equivalente']
            ];
        }
        
        // Buscar ciclos
        $result_cycles = mysqli_query($GLOBALS['dblink'], "SELECT c.id_unidade, c.id_unidade_ref, c.quantidade, u.nome as unidade, udef.nome as referencia 
            FROM time_cycles c
            JOIN time_units u ON u.id = c.id_unidade_ref 
            JOIN time_units udef ON udef.id = c.id_unidade
            WHERE c.id_time_system = $id_time_system;") or die(mysqli_error($GLOBALS['dblink']));
        
        while ($cycle = mysqli_fetch_assoc($result_cycles)) {
            $response['cycles'][] = [
                'id_unidade' => $cycle['id_unidade'],
                'id_unidade_ref' => $cycle['id_unidade_ref'],
                'nome_unidade_ref' => $cycle['referencia'],
                'quantidade' => (int)$cycle['quantidade']
            ];
        }
        
        // Determinar dias da semana (unidade com equivalente = 'semana')
        $result_days = mysqli_query($GLOBALS['dblink'], "SELECT nome 
            FROM time_units 
            WHERE id_time_system = $id_time_system AND equivalente = 'semana' 
            ORDER BY id;") or die(mysqli_error($GLOBALS['dblink']));
        
        if ($day = mysqli_fetch_assoc($result_days)) {
            // Assumindo que a unidade 'semana' tem um ciclo que define os dias
            $week_id = mysqli_fetch_assoc(mysqli_query($GLOBALS['dblink'], "SELECT id 
                FROM time_units 
                WHERE id_time_system = $id_time_system AND equivalente = 'semana' LIMIT 1;"))['id'];
            $result_day_cycle = mysqli_query($GLOBALS['dblink'], "SELECT u.nome 
                FROM time_cycles c 
                JOIN time_units u ON u.id = c.id_unidade 
                WHERE c.id_unidade_ref = $week_id AND c.id_time_system = $id_time_system 
                ORDER BY u.id;") or die(mysqli_error($GLOBALS['dblink']));
            while ($day = mysqli_fetch_assoc($result_day_cycle)) {
                $response['days'][] = $day['nome'];
            }
        }
        
        // Determinar meses (unidade com equivalente = 'mes')
        /*
        $result_months = mysqli_query($GLOBALS['dblink'], "SELECT tu.id, tu.nome, tc.quantidade 
            FROM time_units tu 
            LEFT JOIN time_cycles tc ON tu.id = tc.id_unidade 
            WHERE tu.id_time_system = $id_time_system AND tu.equivalente = 'mes' 
            ORDER BY tu.id;") or die(mysqli_error($GLOBALS['dblink']));
        
        while ($month = mysqli_fetch_assoc($result_months)) {
            $response['months'][] = [
                'nome' => $month['nome'],
                'days' => $month['quantidade'] ? (int)$month['quantidade'] : 30
            ];
        }
        */
        $month_id_result = mysqli_query($GLOBALS['dblink'], "SELECT  
            (SELECT id FROM time_units WHERE id_time_system = $id_time_system AND equivalente = 'mes' LIMIT 1) as id_mes,
            (SELECT nome FROM time_units WHERE id_time_system = $id_time_system AND equivalente = 'mes' LIMIT 1) as nome_mes,
            (SELECT id FROM time_units WHERE id_time_system = $id_time_system AND equivalente = 'ano' LIMIT 1) as id_ano;") or die(mysqli_error($GLOBALS['dblink']));

        if ($month = mysqli_fetch_assoc($month_id_result)) {
            $month_id = $month['id_mes'];
            $year_id = $month['id_ano'];
            $month_name = $month['nome_mes'];
            
            // Buscar número de meses por ano (ciclo ano -> mês)
            $months_per_year = 12; // Valor padrão se não houver ciclo
            $result_cycle = mysqli_query($GLOBALS['dblink'], "SELECT tc.quantidade 
                FROM time_cycles tc 
                JOIN time_units tu ON tc.id_unidade = tu.id 
                WHERE tc.id_unidade_ref = $month_id AND tu.id_time_system = $id_time_system AND tu.equivalente = 'ano' LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
            if ($cycle = mysqli_fetch_assoc($result_cycle)) {
                $months_per_year = (int)$cycle['quantidade'];
            }
            
            // Buscar quantidade de dias por mês (ciclo mês -> dia)
            $days_per_month = 30; // Valor padrão se não houver ciclo
            $result_days = mysqli_query($GLOBALS['dblink'], "SELECT quantidade 
                FROM time_cycles 
                WHERE id_unidade = $month_id AND id_unidade_ref = (SELECT id FROM time_units WHERE id_time_system = $id_time_system AND equivalente = 'dia' LIMIT 1) 
                LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
            if ($days = mysqli_fetch_assoc($result_days)) {
                $days_per_month = (int)$days['quantidade'];
            }
            
            // Buscar nomes dos meses de time_names
            $result_names = mysqli_query($GLOBALS['dblink'], "SELECT nome, posicao, quantidade_subunidade 
                FROM time_names 
                WHERE id_time_system = $id_time_system AND id_unidade = $year_id 
                ORDER BY posicao;") or die(mysqli_error($GLOBALS['dblink']));
            
            $month_names = [];
            $month_days = [];
            while ($name = mysqli_fetch_assoc($result_names)) {
                $month_names[$name['posicao']] = $name['nome'];
                $month_days[$name['posicao']] = $name['quantidade_subunidade'];
            }
            
            // Preencher meses com nomes ou string vazia
            for ($i = 1; $i <= $months_per_year; $i++) {
                $response['months'][] = [
                    'nome' => strlen($month_names[$i])>0 ? $month_names[$i] : $month_name.' '.$i, // String vazia se não houver nome
                    'days' => isset($month_days[$i]) ? $month_days[$i] : $days_per_month
                ];
            }
        }
        
        // Buscar regras de leaps
        $result_leaps = mysqli_query($GLOBALS['dblink'], "SELECT affected_unit_id, `condition`, adjustment_value, target_unit_id
            FROM time_adjustment_rules 
            WHERE id_time_system = $id_time_system;") or die(mysqli_error($GLOBALS['dblink']));
        
        while ($leap = mysqli_fetch_assoc($result_leaps)) {
            $response['leap_rules'][] = [
                'id_unidade' => $leap['affected_unit_id'],
                'condition' => $leap['condition'],
                'add_units' => (int)$leap['adjustment_value'],
                'target_unidade' => $leap['target_unit_id']
            ];
        }
        
        // Verificar inconsistências nos ciclos (adaptado de ajaxVerificarCiclos)
        foreach ($response['cycles'] as $cycle) {
            $id_unidade = $cycle['id_unidade'];
            $id_unidade_ref = $cycle['id_unidade_ref'];
            $nome_unidade_ref = $cycle['referencia'];
            $quantidade = $cycle['quantidade'];
            
            // Buscar duração da unidade de referência
            $ref_result = mysqli_query($GLOBALS['dblink'], "SELECT duracao 
                FROM time_units 
                WHERE id = $id_unidade_ref LIMIT 1;") or die(mysqli_error($GLOBALS['dblink']));
            $ref_duracao = mysqli_fetch_assoc($ref_result)['duracao'];
            
            // Calcular duração total das unidades que compõem a unidade de referência
            $sub_result = mysqli_query($GLOBALS['dblink'], "SELECT SUM(u.duracao * c.quantidade) as total 
                FROM time_cycles c 
                JOIN time_units u ON u.id = c.id_unidade 
                WHERE c.id_unidade_ref = $id_unidade_ref AND c.id_time_system = $id_time_system;") or die(mysqli_error($GLOBALS['dblink']));
            $total_sub = mysqli_fetch_assoc($sub_result)['total'] ? mysqli_fetch_assoc($sub_result)['total'] : 0;
            
            $esperado = $ref_duracao;
            if (abs($total_sub - $esperado) > 0.01) {
                $diff = $total_sub - $esperado;
                $mensagem = $diff > 0 ? 
                    "Sobra de " . abs($diff) . " segundos em relação à unidade ".$cycle['nome_unidade_ref'] :
                    "Falta de " . abs($diff) . " segundos para completar a unidade ".$cycle['nome_unidade_ref'];
                $response['warnings'][] = [
                    'mensagem' => $mensagem,
                    'unidade' => $id_unidade_ref,
                    'diferenca' => abs($diff)
                ];
            }
        }
        
        // Retornar JSON
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Sistema de tempo não encontrado']);
    }
    
    die();
}

if ($_GET['action'] == 'ajaxGetJsonStats') {
    $eid = (int)$_GET['eid'];

    // fenara: id 6, id_tipo 7 
    // rafii:  id 8, id_tipo 7

    // Primeiro, obter o id_tipo da entidade
    $tipo_result = mysqli_query($GLOBALS['dblink'], "
        SELECT id_tipo 
        FROM entidades 
        WHERE id = $eid
    ") or die(mysqli_error($GLOBALS['dblink']));
    $tipo_row = mysqli_fetch_assoc($tipo_result);
    if (!$tipo_row) {
        echo json_encode([
            'series' => [],
            'html' => '<div class="list-group-item">'._t('Entidade não encontrada.').'</div>'
        ]);
        die();
    }
    $id_tipo = (int)$tipo_row['id_tipo'];




    $result = mysqli_query($GLOBALS['dblink'], "
        SELECT se.*, s.titulo as nome_stat, m.nome as nome_momento, m.time_value, m.ordem
        FROM stats_entidades se 
        JOIN stats s ON s.id = se.id_stat 
        JOIN momentos m ON m.id = se.id_momento 
        WHERE se.id_entidade = $eid 
        ORDER BY m.time_value, m.ordem
    ") or die(mysqli_error($GLOBALS['dblink']));
    
    // este lista os stats - sem os valores
    $result = mysqli_query($GLOBALS['dblink'], "SELECT ets.id, s.titulo as nome_stat
        FROM entidades_tipos_stats ets
        LEFT JOIN stats s ON s.id = ets.id_stat
        WHERE ets.id_entidade_tipo = $id_tipo
        ORDER BY s.titulo;") or die(mysqli_error($GLOBALS['dblink']));

    // default ERRADO
    $result = mysqli_query($GLOBALS['dblink'], "
        SELECT se.*, s.titulo as nome_stat, m.nome as nome_momento, m.time_value, m.ordem
        FROM stats_entidades se 
        JOIN stats s ON s.id = se.id_stat 
        JOIN momentos m ON m.id = se.id_momento 
        WHERE se.id_entidade = $eid 
        ORDER BY m.time_value, m.ordem
    ") or die(mysqli_error($GLOBALS['dblink']));



    // Query unificada
    $result = mysqli_query($GLOBALS['dblink'], "
        SELECT ets.id_stat, s.titulo as nome_stat, 
               se.id as se_id, se.valor, se.id_momento, 
               m.nome as nome_momento, m.time_value, m.ordem
        FROM entidades_tipos_stats ets
        LEFT JOIN stats s ON s.id = ets.id_stat
        LEFT JOIN stats_entidades se ON se.id_stat = ets.id_stat AND se.id_entidade = $eid
        LEFT JOIN momentos m ON m.id = se.id_momento
        WHERE ets.id_entidade_tipo = $id_tipo
        ORDER BY s.titulo, m.time_value, m.ordem
    ") or die(mysqli_error($GLOBALS['dblink']));










    $stats = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stat_id = $row['id_stat'];
        if (!isset($stats[$stat_id])) {
            $stats[$stat_id] = [
                'name' => htmlspecialchars($row['nome_stat']),
                'data' => [],
                'ids' => [], // Para armazenar IDs para o clique
                'momentos' => [], // Para armazenar IDs de momentos

                'nome_stat' => htmlspecialchars($row['nome_stat']),
                'valores' => []
            ];
        }
        $stats[$stat_id]['data'][] = [
            'x' => htmlspecialchars($row['nome_momento']),
            'y' => floatval($row['valor']),
            'id' => $row['id'],
            'id_momento' => $row['id_momento']
        ];
        $stats[$stat_id]['ids'][] = $row['id'];
        $stats[$stat_id]['momentos'][] = $row['id_momento'];
        $stats[$stat_id]['valores'][] = [
            'id' => $row['id'],
            'id_momento' => $row['id_momento'],
            'valor' => htmlspecialchars($row['valor']),
            'nome_momento' => htmlspecialchars($row['nome_momento'])
        ];
    }

    // Converter para formato compatível com ApexCharts
    $series = [];
    $html = '';
    foreach ($stats as $id_stat => $stat) {
        $series[] = [
            'name' => $stat['name'],
            'data' => array_map(function($point) {
                return ['x' => $point['x'], 'y' => $point['y']];
            }, $stat['data']),
            'ids' => $stat['ids'],
            'momentos' => $stat['momentos']
        ]; 
        $valores_html = [];
        foreach ($stat['valores'] as $valor) {
            $valores_html[] = '<a href="#" title="' . $valor['nome_momento'] . '" onclick="addStat(' . $id_stat . ',' . $id_stat . ',' . $valor['id_momento'] . ',\'' . $valor['valor'] . '\')">' 
                            . $valor['valor'] . ' </a>';
        }
        $valores_str = implode(' - ', $valores_html);

        $html .= '<div class="list-group-item"><div class="row">
            <div class="col">
                <label>' . $stat['nome_stat'] . '</label>
                <div class="text-body text-secondary"><small>' . _t('Valores') . ': ' . ($valores_str ?: _t('Nenhum valor registrado')) . ' - <a href="#" onclick="addStat(0,' . $id_stat . ',0,\'\')">Adicionar</a></small></div>
            </div>
            <!--div class="col-auto">
                <a class="btn btn-sm btn-danger" onclick="apagarStatsPorEntidade(' . $eid . ',' . $id_stat . ')">X</a>
            </div-->
        </div></div>';
    }


    echo json_encode([
        'series' => $series,
        'html' => $html ?: '<div class="list-group-item">'._t('Nenhuma estatística cadastrada.').'</div>'
        //'labels' => array_unique(array_column($stats[array_key_first($stats)]['data'], 'x'))
    ]);
    die();
}

if ($_GET['action'] == 'deleteStatsByEntityAndStat') {
    $eid = (int)$_POST['eid'];
    $id_stat = (int)$_POST['id_stat'];
    $result = mysqli_query($GLOBALS['dblink'], "
        DELETE FROM stats_entidades 
        WHERE id_entidade = $eid AND id_stat = $id_stat
    ") or die(mysqli_error($GLOBALS['dblink']));
    
    echo json_encode(['success' => true]);
    die();
}

if ($_GET['action'] == 'ajaxGetListasSC') {
    echo '<option value="0" data-date=" " selected>'._t('Personalizado').'</option>';
    
    if ($_GET['iid'] > 0){
        $id_idioma = $_GET['iid'];
        $result = mysqli_query($GLOBALS['dblink'],"SELECT *,
            (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = '".$_SESSION['KondisonairUzatorIDX']."' LIMIT 1) as collab FROM idiomas i 
            WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
        $idioma = mysqli_fetch_assoc($result);
    }

    if ($_SESSION['KondisonairUzatorIDX']>0){
        if ($idioma['nome_legivel']!=''){
            $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM soundChanges WHERE id_idioma = $id_idioma AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." ;") or die(mysqli_error($GLOBALS['dblink']));
            if (mysqli_num_rows($langs)>0) echo "\n".'<option disabled data-date=" ">'._t('Minhas listas').'</option>';
            while ($lang = mysqli_fetch_assoc($langs)){
                echo "\n".'<option value="'.$lang['id'].'" data-date=" ">'.$lang['titulo'].'</option>';
            }
        }else{
            $langs = mysqli_query($GLOBALS['dblink'],"SELECT s.*, i.nome_legivel FROM soundChanges s 
                LEFT JOIN idiomas i ON i.id = s.id_idioma
                WHERE s.id_usuario = ".$_SESSION['KondisonairUzatorIDX']." ;") or die(mysqli_error($GLOBALS['dblink']));
            if (mysqli_num_rows($langs)>0) echo "\n".'<option disabled data-date=" ">'._t('Minhas listas').'</option>';
            while ($lang = mysqli_fetch_assoc($langs)){
                echo "\n".'<option value="'.$lang['id'].'" data-date="'.$lang['nome_legivel'].'">'.$lang['titulo'].'</option>';
            }
        }
    }
    
    // listas públicas
    if ($idioma['nome_legivel']!=''){
        $langs = mysqli_query($GLOBALS['dblink'],"SELECT s.*, u.username FROM soundChanges s 
          LEFT JOIN usuarios u ON s.id_usuario = u.id 
          WHERE s.id_idioma = ".$id_idioma." AND s.publico = 1;") or die(mysqli_error($GLOBALS['dblink']));
        if (mysqli_num_rows($langs)>0) echo "\n".'<option disabled data-date=" ">'._t('Listas públicas de %1',[$idioma['nome_legivel']]).'</option>';
        while ($lang = mysqli_fetch_assoc($langs)){
            echo "\n".'<option value="'.$lang['id'].'" data-date="'.$lang['username'].'">'.$lang['titulo'].'</option>';
        }
    }else{
        $langs = mysqli_query($GLOBALS['dblink'],"SELECT s.*, i.nome_legivel, u.username FROM soundChanges s 
            LEFT JOIN usuarios u ON s.id_usuario = u.id 
            LEFT JOIN idiomas i ON i.id = s.id_idioma
            WHERE s.publico = 1 ;") or die(mysqli_error($GLOBALS['dblink']));
        if (mysqli_num_rows($langs)>0) echo "\n".'<option disabled data-date=" ">'._t('Listas públicas').'</option>';
        while ($lang = mysqli_fetch_assoc($langs)){
            echo "\n".'<option value="'.$lang['id'].'" data-date="'.$lang['nome_legivel'].' - '.$lang['username'].'">'.$lang['titulo'].'</option>';
        }
    }
    die();
};

if ($_GET['action'] == 'ajaxPublicarListaSC') {
    if ($_GET['id']>0) {
      $id = (int)$_GET['id'];
      $result = mysqli_query($GLOBALS['dblink'], "
          UPDATE soundChanges SET publico = 1
          WHERE id = $id
      ") or die(mysqli_error($GLOBALS['dblink']));
    }
    echo 'ok';
    die();
};

function gerarLinksIdiomas($idioma_atual, $use_js = true) {
    global $idiomas_sistema;
    $html = '';
    foreach ($idiomas_sistema as $id => $nome) {
        if ($id != $idioma_atual) {
            if ($use_js) {
                $html .= '<li class="list-inline-item"><a onclick="setLang(' . $id . ')" class="link-secondary">' . htmlspecialchars($nome) . '</a></li>';
            } else {
                $url = str_replace('&lang=', '&nus=', $_SERVER['REQUEST_URI']) . '&lang=' . $id;
                $html .= '<li class="list-inline-item"><a href="' . htmlspecialchars($url) . '" class="link-secondary">' . htmlspecialchars($nome) . '</a></li>';
            }
        }
    }
    return $html;
}

function getDescricaoIdioma($id_idioma) {
    global $idiomas_sistema;
    if (isset($idiomas_sistema[$id_idioma])) {
        return _t($idiomas_sistema[$id_idioma]);
    }
    return '';
}

function gerarSelectIdiomas($id_select, $idioma_selecionado, $onchange = '', $use_translation = true) {
    global $idiomas_sistema;
    $html = '<select id="' . htmlspecialchars($id_select) . '" name="' . htmlspecialchars($id_select) . '" class="chosen-select form-control"';
    if ($onchange) {
        $html .= ' onchange="' . htmlspecialchars($onchange) . '"';
    }
    $html .= '>';
    foreach ($idiomas_sistema as $id => $nome) {
        $nome_exibido = $use_translation ? _t($nome) : $nome;
        $selected = ($id == $idioma_selecionado) ? ' selected' : '';
        $html .= '<option value="' . $id . '"' . $selected . '>' . htmlspecialchars($nome_exibido) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

if ($_GET['action'] == 'exportarIdioma') {

    class LanguageExporter {
        private $mysqli;
        private $languageId;
        private $userId;
        public $fontes;
        public $escritas;

        public function __construct($mysqli, $languageId, $userId) {
            $this->mysqli = $mysqli;
            $this->languageId = $languageId;
            $this->userId = $userId;
        }

        public function exportToJson() {
            $data = [];

            // Export idiomas with permission check
            $data['idiomas'] = $this->fetchSingleRow(
                "SELECT * FROM idiomas WHERE id = ? AND (publico = 1 OR id_usuario = ?)",
                [$this->languageId, $this->userId]
            );

            // Check if language exists and user has access
            if (!$data['idiomas']) {
                throw new Exception('Language not found or access denied');
            }

            // Check if language exists and user has access
            if (!$data['idiomas']) {
                throw new Exception('Language not found or access denied');
            }

            // se é o dono, pode exportar tudinho, senão só dados públicos
            if ( $this->userId == $data['idiomas']['id_usuario']) $isOwner = true;

            // Direct relations (tables with id_idioma)
            $directTables = [
                'artygs' => 'SELECT * FROM artygs WHERE id_idioma = ?' . ( $isOwner ? '' : ' AND publico = 1' ),
                'blocos' => 'SELECT * FROM blocos WHERE id_idioma = ?',
                'classes' => 'SELECT * FROM classes WHERE id_idioma = ?',
                'classesSom' => 'SELECT * FROM classesSom WHERE id_idioma = ?',
                'collabs' => 'SELECT * FROM collabs WHERE id_idioma = ?',
                'concordancias' => 'SELECT * FROM concordancias WHERE id_idioma = ?',
                'escritas' => 'SELECT * FROM escritas WHERE id_idioma = ?',
                'frases' => 'SELECT * FROM frases WHERE id_idioma = ?',
                'generos' => 'SELECT * FROM generos WHERE id_idioma = ?',
                'inventarios' => 'SELECT * FROM inventarios WHERE id_idioma = ?',
                'ipaTitulos' => 'SELECT * FROM ipaTitulos WHERE id_idioma = ?',
                'palavras' => 'SELECT * FROM palavras WHERE id_idioma = ?',
                'sonsPersonalizados' => 'SELECT * FROM sonsPersonalizados WHERE id_idioma = ?',
                'soundChanges' => 'SELECT * FROM soundChanges WHERE id_idioma = ?' . ( $isOwner ? '' : ' AND publico = 1' ) ,
                'studason_tests' => 'SELECT * FROM studason_tests WHERE id_idioma = ? AND num_palavras > 0',
                'formasSilaba' => 'SELECT * FROM formasSilaba WHERE id_idioma = ?',
                'nivelUsoPalavra' => 'SELECT * FROM nivelUsoPalavra WHERE id_idioma = ?'
            ];

            foreach ($directTables as $table => $query) {
                $data[$table] = $this->fetchAll($query, [$this->languageId]);
            }

            // Indirect relations
            // 1. artyg_dest (via artygs)
            $artygs = array_column($data['artygs'], 'id');
            if (!empty($artygs)) {
                $placeholders = implode(',', array_fill(0, count($artygs), '?'));
                $data['artyg_dest'] = $this->fetchAll(
                    "SELECT * FROM artyg_dest WHERE id_artyg IN ($placeholders)",
                    $artygs
                );
            } else {
                $data['artyg_dest'] = [];
            }

            // 2. fontes (via escritas)
            $fontes = array_column($data['escritas'], 'id_fonte');
            if (!empty($fontes)) {
                $placeholders = implode(',', array_fill(0, count($fontes), '?'));
                $data['fontes'] = $this->fetchAll(
                    "SELECT * FROM fontes WHERE id IN ($placeholders)",
                    $fontes
                );
            } else {
                $data['fontes'] = [];
            }

            // 3. autosubstituicoes, drawChars, glifos (via escritas)
            $escritas = array_column($data['escritas'], 'id');
            $escritaTables = [
                'autosubstituicoes' => 'SELECT * FROM autosubstituicoes WHERE id_escrita IN (%s)',
                'drawChars' => 'SELECT * FROM drawChars WHERE id_escrita IN (%s)',
                'glifos' => 'SELECT * FROM glifos WHERE id_escrita IN (%s)'
            ];

            foreach ($escritaTables as $table => $query) {
                if (!empty($escritas)) {
                    $placeholders = implode(',', array_fill(0, count($escritas), '?'));
                    $data[$table] = $this->fetchAll(sprintf($query, $placeholders), $escritas);
                } else {
                    $data[$table] = [];
                }
            }

            // 4. formaSilabaComponente (via formasSilaba)
            $formasSilaba = array_column($data['formasSilaba'], 'id');
            if (!empty($formasSilaba)) {
                $placeholders = implode(',', array_fill(0, count($formasSilaba), '?'));
                $data['formaSilabaComponente'] = $this->fetchAll(
                    "SELECT * FROM formaSilabaComponente WHERE id_formaSilaba IN ($placeholders)",
                    $formasSilaba
                );
            } else {
                $data['formaSilabaComponente'] = [];
            }

            // 5. itensConcordancias (via concordancias)
            $concordancias = array_column($data['concordancias'], 'id');
            if (!empty($concordancias)) {
                $placeholders = implode(',', array_fill(0, count($concordancias), '?'));
                $data['itensConcordancias'] = $this->fetchAll(
                    "SELECT * FROM itensConcordancias WHERE id_concordancia IN ($placeholders)",
                    $concordancias
                );
            } else {
                $data['itensConcordancias'] = [];
            }

            // 6. gloss_itens (via itensConcordancias)
            $itensConcordancias = array_column($data['itensConcordancias'], 'id');
            if (!empty($itensConcordancias)) {
                $placeholders = implode(',', array_fill(0, count($itensConcordancias), '?'));
                $data['gloss_itens'] = $this->fetchAll(
                    "SELECT * FROM gloss_itens WHERE id_item IN ($placeholders)",
                    $itensConcordancias
                );
            } else {
                $data['gloss_itens'] = [];
            }

            // 7. itens_flexoes (via concordancias, itensConcordancias, generos)
            if (!empty($concordancias) || !empty($itensConcordancias) || !empty($data['generos'])) {
                $generos = array_column($data['generos'], 'id');
                $placeholdersConc = !empty($concordancias) ? implode(',', array_fill(0, count($concordancias), '?')) : '0';
                $placeholdersItens = !empty($itensConcordancias) ? implode(',', array_fill(0, count($itensConcordancias), '?')) : '0';
                $placeholdersGeneros = !empty($generos) ? implode(',', array_fill(0, count($generos), '?')) : '0';
                $data['itens_flexoes'] = $this->fetchAll(
                    "SELECT * FROM itens_flexoes WHERE id_concordancia IN ($placeholdersConc) AND id_item IN ($placeholdersItens) AND (id_genero IN ($placeholdersGeneros) OR id_genero = 0)",
                    array_merge($concordancias, $itensConcordancias, $generos)
                );
            } else {
                $data['itens_flexoes'] = [];
            }

            // 8. Palavra-related tables
            $palavras = array_column($data['palavras'], 'id');
            if (!empty($palavras)) {
                $placeholders = implode(',', array_fill(0, count($palavras), '?'));
                $palavraTables = [
                    'itens_palavras' => "SELECT * FROM itens_palavras WHERE id_palavra IN ($placeholders)",
                    'palavrasNativas' => "SELECT * FROM palavrasNativas WHERE id_palavra IN ($placeholders)",
                    'palavras_origens' => "SELECT * FROM palavras_origens WHERE id_palavra IN ($placeholders)",
                    'palavras_referentes' => "SELECT * FROM palavras_referentes WHERE id_palavra IN ($placeholders)",
                    'palavras_usos' => "SELECT * FROM palavras_usos WHERE id_palavra IN ($placeholders)",
                    'significados_idiomas' => "SELECT * FROM significados_idiomas WHERE id_palavra IN ($placeholders)"
                ];

                foreach ($palavraTables as $table => $query) {
                    $data[$table] = $this->fetchAll($query, $palavras);
                }
            } else {
                $palavraTables = [
                    'itens_palavras', 'palavrasNativas', 'palavras_origens',
                    'palavras_referentes', 'palavras_usos', 'significados_idiomas'
                ];
                foreach ($palavraTables as $table) {
                    $data[$table] = [];
                }
            }

            // 9. sons_classes (via classesSom)
            $classesSom = array_column($data['classesSom'], 'id');
            if (!empty($classesSom)) {
                $placeholders = implode(',', array_fill(0, count($classesSom), '?'));
                $data['sons_classes'] = $this->fetchAll(
                    "SELECT * FROM sons_classes WHERE id_classeSom IN ($placeholders)",
                    $classesSom
                );
            } else {
                $data['sons_classes'] = [];
            }

            // 10. teclas (via inventarios)
            $inventarios = array_column($data['inventarios'], 'id');
            if (!empty($inventarios)) {
                $placeholders = implode(',', array_fill(0, count($inventarios), '?'));
                $data['teclas'] = $this->fetchAll(
                    "SELECT * FROM teclas WHERE id_inventario IN ($placeholders)",
                    $inventarios
                );
            } else {
                $data['teclas'] = [];
            }

            $itens_flexoes = array_column($data['itens_flexoes'], 'id_flexao');
            if (!empty($itens_flexoes)) {
                $placeholders = implode(',', array_fill(0, count($itens_flexoes), '?'));
                $data['flexoes'] = $this->fetchAll(
                    "SELECT * FROM flexoes WHERE id IN ($placeholders)",
                    $itens_flexoes
                );
            } else {
                $data['flexoes'] = [];
            }

            $generos = array_column($data['generos'], 'id');
            if (!empty($generos)) {
                $placeholders = implode(',', array_fill(0, count($generos), '?'));
                $data['classesGeneros'] = $this->fetchAll(
                    "SELECT * FROM classesGeneros WHERE id_genero IN ($placeholders)",
                    $generos
                );
            } else {
                $data['classesGeneros'] = [];
            }

            $userIds = [$data['idiomas']['id_usuario']];
            $user_collabs = array_column($data['collabs'], 'id_usuario');
            if (!empty($user_collabs)) {
                $userIds = array_merge($userIds, $user_collabs);
            }

            $tablesWithIdUsuario = ['wordbanks', 'studason_tests', 'soundChanges', 'palavras', 'collabs'];
            foreach ($tablesWithIdUsuario as $table) {
                if (!empty($data[$table])) {
                    $ids = array_column($data[$table], 'id_usuario');
                    $userIds = array_merge($userIds, $ids);
                }
            }
            if (!empty($data['frases'])) {
                $ids = array_column($data['frases'], 'id_criador');
                $userIds = array_merge($userIds, $ids);
            }
            if (!empty($data['escritas'])) {
                $fontesIds = array_column($data['escritas'], 'id_fonte');
                if (!empty($fontesIds)) {
                    $placeholders = implode(',', array_fill(0, count($fontesIds), '?'));
                    $data['fontes'] = $this->fetchAll(
                        "SELECT * FROM fontes WHERE id IN ($placeholders)",
                        $fontesIds
                    );
                    $ids = array_column($data['fontes'], 'id_usuario');
                    $userIds = array_merge($userIds, $ids);
                } else {
                    $data['fontes'] = [];
                }
            } else {
                $data['fontes'] = [];
            }

            $userIds = array_unique(array_filter($userIds));
            if (!empty($userIds)) {
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $data['usuarios'] = $this->fetchAll(
                    "SELECT id, username, publico, data_cadastro FROM usuarios WHERE id IN ($placeholders)",
                    $userIds
                );
            } else {
                $data['usuarios'] = [];
            }

            // Ensure UTF-8 encoding for all string data
            $this->ensureUtf8($data);
            $this->fontes = $data['fontes'];
            $this->escritas = $data['escritas'];

            // Convert to JSON with error checking
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                $error = json_last_error_msg();
                error_log("JSON encode error: $error");
                throw new Exception("Failed to encode JSON: $error");
            }
            return $json;
        }

        private function fetchAll($query, $params) {
            $stmt = $this->mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->mysqli->error);
            }
            if (!empty($params)) {
                $types = str_repeat('i', count($params));
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                // Cast numeric fields to strings to preserve precision
                foreach ($row as $key => $value) {
                    if (is_numeric($value) && !is_float($value + 0)) {
                        $row[$key] = (string)$value;
                    }
                }
                $data[] = $row;
            }
            $stmt->close();
            return $data;
        }

        private function fetchSingleRow($query, $params) {
            $stmt = $this->mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->mysqli->error);
            }
            if (!empty($params)) {
                $types = str_repeat('i', count($params));
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                // Cast numeric fields to strings to preserve precision
                foreach ($row as $key => $value) {
                    if (is_numeric($value) && !is_float($value + 0)) {
                        $row[$key] = (string)$value;
                    }
                }
            }
            $stmt->close();
            return $row ?: null;
        }
        
        private function ensureUtf8(&$data) {
            array_walk_recursive($data, function (&$value) {
                if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                }
            });
        }
    }

    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
    mysqli_set_charset( $mysqli,'utf8');
    if ($mysqli->connect_error) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database connection failed: ' . $mysqli->connect_error]);
        exit;
    }

    if (!isset($_GET['id_idioma']) || !is_numeric($_GET['id_idioma'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Invalid or missing language ID']);
        exit;
    }

    try {
        $languageId = (int)$_GET['id_idioma'];
        $userId = $_SESSION['KondisonairUzatorIDX'];
        $exporter = new LanguageExporter($mysqli, $languageId, $userId);
        $json = $exporter->exportToJson();

        $fontes = $exporter->fontes;
        $escritas = $exporter->escritas;

        $tempId = generateId();
        $jsonFile = 'dados_'.$tempId.'.json';
        file_put_contents($jsonFile, $json);

        $zip = new ZipArchive();
        $zipFileName = $tempId . '.zip';

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($jsonFile, $languageId.'.json');

            
            foreach ($fontes as $arquivo){
                if (!empty($arquivo['arquivo']) && file_exists('fonts/'.$arquivo['arquivo']))
                    $zip->addFile('fonts/' . $arquivo['arquivo']);
            }
            foreach ($escritas as $eid){
                foreach (glob("writing/".$eid['id']."/*") as $arquivo){
                    if (!empty($arquivo) && file_exists($arquivo))
                        $zip->addFile($arquivo);
                }
            }
            
            foreach (glob("audio/$languageId/*") as $arquivo){
                $zip->addFile($arquivo, 'audio/' . basename($arquivo));
            }
            foreach (glob("image/$languageId/*") as $arquivo){
                $zip->addFile($arquivo, 'image/' . basename($arquivo));
            }

            $zip->close();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipFileName));
        readfile($zipFileName);

        unlink($jsonFile);
        unlink($zipFileName);

    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }

    $mysqli->close();
    die();
};

if ($_GET['action'] == 'exportarRealidade') {

    class WorldExporter {
        private $mysqli;
        private $worldId;
        private $userId;

        public function __construct($mysqli, $worldId, $userId) {
            $this->mysqli = $mysqli;
            $this->worldId = $worldId;
            $this->userId = $userId;
        }

        public function exportToJson() {
            $data = [];

            $data['realidades'] = $this->fetchSingleRow(
                "SELECT * FROM realidades WHERE id = ? AND (publico = 1 OR id_usuario = ?)",
                [$this->worldId, $this->userId]
            );

            if (!$data['realidades']) {
                throw new Exception('Reality not found or access denied');
            }

            if (!$data['realidades']) {
                throw new Exception('Reality not found or access denied');
            }

            // se é o dono, pode exportar tudinho, senão só dados públicos
            if ( $this->userId == $data['realidades']['id_usuario']) $isOwner = true;

            $directTables = [
                'collabs_realidades' => 'SELECT * FROM collabs_realidades WHERE id_realidade = ?',
                'entidades' => 'SELECT * FROM entidades WHERE id_realidade = ?',
                'entidades_tipos' => 'SELECT * FROM entidades_tipos WHERE id_realidade = ?',
                'historias' => 'SELECT * FROM historias WHERE id_realidade = ?', // ( $isOwner ? '' : ' AND publico = 1' ),
                'historias_tipos' => 'SELECT * FROM historias_tipos WHERE id_realidade = ?',
                'momentos' => 'SELECT * FROM momentos WHERE id_realidade = ?',
                'stats' => 'SELECT * FROM stats WHERE id_realidade = ?',
                'time_systems' => 'SELECT * FROM time_systems WHERE id_realidade = ?'
            ];

            foreach ($directTables as $table => $query) {
                $data[$table] = $this->fetchAll($query, [$this->worldId]);
            }

            // via time_systems
            $time_systems = array_column($data['time_systems'], 'id');
            $timesTables = [
                'time_cycles' => 'SELECT * FROM time_cycles WHERE id_time_system IN (%s)',
                'time_names' => 'SELECT * FROM time_names WHERE id_time_system IN (%s)',
                'time_adjustment_rules' => 'SELECT * FROM time_adjustment_rules WHERE id_time_system IN (%s)',
                'time_units' => 'SELECT * FROM time_units WHERE id_time_system IN (%s)'
            ];

            foreach ($timesTables as $table => $query) {
                if (!empty($time_systems)) {
                    $placeholders = implode(',', array_fill(0, count($time_systems), '?'));
                    $data[$table] = $this->fetchAll(sprintf($query, $placeholders), $time_systems);
                } else {
                    $data[$table] = [];
                }
            }

            $entidades = array_column($data['entidades'], 'id');
            if (!empty($entidades)) {
                $placeholders = implode(',', array_fill(0, count($entidades), '?'));
                $data['stats_entidades'] = $this->fetchAll(
                    "SELECT * FROM stats_entidades WHERE id_entidade IN ($placeholders)",
                    $entidades
                );
                $data['entidades_nomes'] = $this->fetchAll(
                    "SELECT * FROM entidades_nomes WHERE id_entidade IN ($placeholders)",
                    $entidades
                );
                $data['historias_entidades'] = $this->fetchAll(
                    "SELECT * FROM historias_entidades WHERE id_entidade IN ($placeholders)",
                    $entidades
                );
                $data['entidades_relacoes'] = $this->fetchAll(
                    "SELECT * FROM entidades_relacoes WHERE id_entidade1 IN ($placeholders) OR id_entidade2 IN ($placeholders)",
                    array_merge($entidades, $entidades)
                );
            } else {
                $data['stats_entidades'] = [];
                $data['entidades_nomes'] = [];
                $data['historias_entidades'] = [];
                $data['entidades_relacoes'] = [];
            }

            $entidades_tipos = array_column($data['entidades_tipos'], 'id');
            if (!empty($entidades_tipos)) {
                $placeholders = implode(',', array_fill(0, count($entidades_tipos), '?'));
                $data['entidades_tipos_stats'] = $this->fetchAll(
                    "SELECT * FROM entidades_tipos_stats WHERE id_entidade_tipo IN ($placeholders)",
                    $entidades_tipos
                );
            } else {
                $data['entidades_tipos_stats'] = [];
            }

            $userIds = [$data['realidades']['id_usuario']];
            $user_collabs = array_column($data['collabs_realidades'], 'id_usuario');
            if (!empty($user_collabs)) {
                $userIds = array_merge($userIds, $user_collabs);
            }

            // ainda não tem função collab em realidades, então depois adicionamos busca por ids de utros usuarios

            $userIds = array_unique(array_filter($userIds));
            if (!empty($userIds)) {
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $data['usuarios'] = $this->fetchAll(
                    "SELECT id, username, publico, data_cadastro FROM usuarios WHERE id IN ($placeholders)",
                    $userIds
                );
            } else {
                $data['usuarios'] = [];
            }

            // Ensure UTF-8 encoding for all string data
            $this->ensureUtf8($data);

            // Convert to JSON with error checking
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                $error = json_last_error_msg();
                error_log("JSON encode error: $error");
                throw new Exception("Failed to encode JSON: $error");
            }
            return $json;
        }

        private function fetchAll($query, $params) {
            $stmt = $this->mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->mysqli->error);
            }
            if (!empty($params)) {
                $types = str_repeat('i', count($params));
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                // Cast numeric fields to strings to preserve precision
                foreach ($row as $key => $value) {
                    if (is_numeric($value) && !is_float($value + 0)) {
                        $row[$key] = (string)$value;
                    }
                }
                $data[] = $row;
            }
            $stmt->close();
            return $data;
        }

        private function fetchSingleRow($query, $params) {
            $stmt = $this->mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->mysqli->error);
            }
            if (!empty($params)) {
                $types = str_repeat('i', count($params));
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                // Cast numeric fields to strings to preserve precision
                foreach ($row as $key => $value) {
                    if (is_numeric($value) && !is_float($value + 0)) {
                        $row[$key] = (string)$value;
                    }
                }
            }
            $stmt->close();
            return $row ?: null;
        }
        
        private function ensureUtf8(&$data) {
            array_walk_recursive($data, function (&$value) {
                if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                }
            });
        }
    }

    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
    mysqli_set_charset( $mysqli,'utf8');
    if ($mysqli->connect_error) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database connection failed: ' . $mysqli->connect_error]);
        exit;
    }

    if (!isset($_GET['id_realidade']) || !is_numeric($_GET['id_realidade'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Invalid or missing reality ID']);
        exit;
    }

    try {
        $worldId = (int)$_GET['id_realidade'];
        $userId = $_SESSION['KondisonairUzatorIDX'];
        $exporter = new WorldExporter($mysqli, $worldId, $userId);
        $json = $exporter->exportToJson();

        $tempId = generateId();
        $jsonFile = 'dados_'.$tempId.'.json';
        file_put_contents($jsonFile, $json);

        $zip = new ZipArchive();
        $zipFileName = $tempId . '.zip';

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($jsonFile, $worldId.'.json');

            $zip->close();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipFileName));
        readfile($zipFileName);

        unlink($jsonFile);
        unlink($zipFileName);

    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }

    $mysqli->close();
    die();
};

if ($_GET['action'] == 'apagarIdioma') {
    ob_start();

    ini_set('default_charset', 'UTF-8');
    header('Content-Type: application/json; charset=utf-8');

    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
    if ($mysqli->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $mysqli->connect_error]);
        ob_end_flush();
        exit;
    }

    if (!$mysqli->set_charset('utf8mb4')) {
        error_log("Failed to set charset to utf8mb4: " . $mysqli->error);
        $mysqli->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    }

    try {
        if (!isset($_SESSION['KondisonairUzatorIDX'])) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            ob_end_flush();
            exit;
        }
        $userId = (int)$_SESSION['KondisonairUzatorIDX'];

        if (!isset($_POST['id_idioma']) || !is_numeric($_POST['id_idioma']) || !isset($_POST['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid language ID or password']);
            ob_end_flush();
            exit;
        }
        $idIdioma = (int)$_POST['id_idioma'];
        $password = $_POST['password'];

        $stmt = $mysqli->prepare("SELECT senha FROM usuarios WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed for password check: " . $mysqli->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['senha'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid password']);
            ob_end_flush();
            exit;
        }

        $stmt = $mysqli->prepare(
            "SELECT id FROM idiomas WHERE id = ? AND (id_usuario = ? OR EXISTS (
                SELECT 1 FROM collabs WHERE id_idioma = ? AND id_usuario = ?
            ))"
        );
        if ($stmt === false) {
            throw new Exception("Prepare failed for permission check: " . $mysqli->error);
        }
        $stmt->bind_param('iiii', $idIdioma, $userId, $idIdioma, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'User not authorized to delete this language']);
            ob_end_flush();
            exit;
        }
        $stmt->close();

        $mysqli->begin_transaction();

        $stmt = $mysqli->prepare("SELECT id FROM escritas WHERE id_idioma = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed for escritas IDs: " . $mysqli->error);
        }
        $stmt->bind_param('i', $idIdioma);
        $stmt->execute();
        $result = $stmt->get_result();
        $escritasIds = array_column($result->fetch_all(MYSQLI_ASSOC), 'id');
        $stmt->close();

        $stmt = $mysqli->prepare(
            "SELECT arquivo FROM fontes WHERE id IN (
                SELECT id_fonte FROM escritas WHERE id_idioma = ?
                AND id_fonte NOT IN (SELECT id_fonte FROM escritas WHERE id_idioma != ?)
            )"
        );
        if ($stmt === false) {
            throw new Exception("Prepare failed for fontes arquivo: " . $mysqli->error);
        }
        $stmt->bind_param('ii', $idIdioma, $idIdioma);
        $stmt->execute();
        $result = $stmt->get_result();
        $fontesArquivos = array_column($result->fetch_all(MYSQLI_ASSOC), 'arquivo');
        $stmt->close();

        $dirsToDelete = [
            "audio/$idIdioma",
            "image/$idIdioma"
        ];
        foreach ($dirsToDelete as $dir) {
            $path = "$dir";
            if (is_dir($path)) {
                foreach (glob("$path/*") as $arquivo) {
                    if (file_exists($arquivo) && !unlink($arquivo)) {
                        error_log("Failed to delete file: $arquivo", 3, '/tmp/delete_debug.txt');
                    }
                }
                // Remove directory if empty
                @rmdir($path);
            }
        }
        foreach ($escritasIds as $eid) {
            $path = "writing/$eid";
            if (is_dir($path)) {
                foreach (glob("$path/*") as $arquivo) {
                    if (file_exists($arquivo) && !unlink($arquivo)) {
                        error_log("Failed to delete file: $arquivo", 3, '/tmp/delete_debug.txt');
                    }
                }
                @rmdir($path);
            }
        }
        foreach ($fontesArquivos as $arquivo) {
            $path = "fonts/" . basename($arquivo);
            if (file_exists($path) && !unlink($path)) {
                error_log("Failed to delete font file: $path", 3, '/tmp/delete_debug.txt');
            }
        }

        $indirectTables = [
            'gloss_itens' => "DELETE FROM gloss_itens WHERE id_item IN (SELECT id FROM itensConcordancias WHERE id_concordancia IN (SELECT id FROM concordancias WHERE id_idioma = ?))",
            'itens_flexoes' => "DELETE FROM itens_flexoes WHERE id_concordancia IN (SELECT id FROM concordancias WHERE id_idioma = ?) OR id_item IN (SELECT id FROM itensConcordancias WHERE id_concordancia IN (SELECT id FROM concordancias WHERE id_idioma = ?)) OR id_genero IN (SELECT id FROM generos WHERE id_idioma = ?)",
            'itensConcordancias' => "DELETE FROM itensConcordancias WHERE id_concordancia IN (SELECT id FROM concordancias WHERE id_idioma = ?)",
            'formaSilabaComponente' => "DELETE FROM formaSilabaComponente WHERE id_formaSilaba IN (SELECT id FROM formasSilaba WHERE id_idioma = ?)",
            'autosubstituicoes' => "DELETE FROM autosubstituicoes WHERE id_escrita IN (SELECT id FROM escritas WHERE id_idioma = ?)",
            'drawChars' => "DELETE FROM drawChars WHERE id_escrita IN (SELECT id FROM escritas WHERE id_idioma = ?)",
            'glifos' => "DELETE FROM glifos WHERE id_escrita IN (SELECT id FROM escritas WHERE id_idioma = ?)",
            'itens_palavras' => "DELETE FROM itens_palavras WHERE id_palavra IN (SELECT id FROM palavras WHERE id_idioma = ?)",
            'palavrasNativas' => "DELETE FROM palavrasNativas WHERE id_palavra IN (SELECT id FROM palavras WHERE id_idioma = ?)",
            'palavras_origens' => "DELETE FROM palavras_origens WHERE id_palavra IN (SELECT id FROM palavras WHERE id_idioma = ?)",
            'palavras_referentes' => "DELETE FROM palavras_referentes WHERE id_palavra IN (SELECT id FROM palavras WHERE id_idioma = ?)",
            'palavras_usos' => "DELETE FROM palavras_usos WHERE id_palavra IN (SELECT id FROM palavras WHERE id_idioma = ?)",
            'significados_idiomas' => "DELETE FROM significados_idiomas WHERE id_palavra IN (SELECT id FROM palavras WHERE id_idioma = ?)",
            'sons_classes' => "DELETE FROM sons_classes WHERE id_classeSom IN (SELECT id FROM classesSom WHERE id_idioma = ?)",
            'teclas' => "DELETE FROM teclas WHERE id_inventario IN (SELECT id FROM inventarios WHERE id_idioma = ?)",
            'artyg_dest' => "DELETE FROM artyg_dest WHERE id_artyg IN (SELECT id FROM artygs WHERE id_idioma = ?)",
            'classesGeneros' => "DELETE FROM classesGeneros WHERE id_genero IN (SELECT id FROM generos WHERE id_idioma = ?)"
        ];

        $stmt = $mysqli->prepare("
            DELETE FROM flexoes 
            WHERE id IN (
                SELECT id_flexao 
                FROM itens_flexoes 
                WHERE id_concordancia IN (SELECT id FROM concordancias WHERE id_idioma = ?)
            )
        ");
        if ($stmt === false) {
            throw new Exception("Prepare failed for flexoes: " . $mysqli->error);
        }
        $stmt->bind_param('i', $idIdioma);
        $stmt->execute();
        $stmt->close();

        foreach ($indirectTables as $table => $query) {
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for $table: " . $mysqli->error);
            }
            if ($table === 'itens_flexoes') {
                $stmt->bind_param('iii', $idIdioma, $idIdioma, $idIdioma);
            } else {
                $stmt->bind_param('i', $idIdioma);
            }
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $mysqli->prepare(
            "DELETE FROM fontes WHERE id IN (
                SELECT id_fonte FROM escritas WHERE id_idioma = ?
                AND id_fonte NOT IN (SELECT id_fonte FROM escritas WHERE id_idioma != ?)
            )"
        );
        if ($stmt === false) {
            throw new Exception("Prepare failed for fontes: " . $mysqli->error);
        }
        $stmt->bind_param('ii', $idIdioma, $idIdioma);
        $stmt->execute();
        $stmt->close();

        // Delete direct tables
        $directTables = [
            'artygs',
            'blocos',
            'classes',
            'classesSom',
            'collabs',
            'concordancias',
            'escritas',
            'frases',
            'generos',
            'inventarios',
            'ipaTitulos',
            'palavras',
            'sonsPersonalizados',
            'soundChanges',
            'studason_tests',
            'formasSilaba',
            'nivelUsoPalavra'
        ];

        foreach ($directTables as $table) {
            $stmt = $mysqli->prepare("DELETE FROM `$table` WHERE id_idioma = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed for $table: " . $mysqli->error);
            }
            $stmt->bind_param('i', $idIdioma);
            $stmt->execute();
            $stmt->close();
        }

        // Delete idiomas
        if ($idIdioma > 10000){
            $stmt = $mysqli->prepare("DELETE FROM idiomas WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed for idiomas: " . $mysqli->error);
            }
            $stmt->bind_param('i', $idIdioma);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                throw new Exception("Language not found");
            }
            $stmt->close();
        }

        $mysqli->commit();
        echo json_encode(['message' => _t('Idioma apagado com sucesso')]);
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    ob_end_flush();
    $mysqli->close();
    die(); 
};

if ($_GET['action'] == 'apagarRealidade') {
    ob_start();

    ini_set('default_charset', 'UTF-8');
    header('Content-Type: application/json; charset=utf-8');

    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
    if ($mysqli->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $mysqli->connect_error]);
        ob_end_flush();
        exit;
    }

    if (!$mysqli->set_charset('utf8mb4')) {
        error_log("Failed to set charset to utf8mb4: " . $mysqli->error);
        $mysqli->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    }

    try {
        if (!isset($_SESSION['KondisonairUzatorIDX'])) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            ob_end_flush();
            exit;
        }
        $userId = (int)$_SESSION['KondisonairUzatorIDX'];

        if (!isset($_POST['id_realidade']) || !is_numeric($_POST['id_realidade']) || !isset($_POST['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid reality ID or password']);
            ob_end_flush();
            exit;
        }
        $idRealidade = (int)$_POST['id_realidade'];
        $password = $_POST['password'];

        $stmt = $mysqli->prepare("SELECT senha FROM usuarios WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed for password check: " . $mysqli->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['senha'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid password']);
            ob_end_flush();
            exit;
        }

        $stmt = $mysqli->prepare(
            "SELECT id FROM realidades WHERE id = ? AND (id_usuario = ? OR EXISTS (
                SELECT 1 FROM collabs_realidades WHERE id_realidade = ? AND id_usuario = ?
            ))"
        );
        if ($stmt === false) {
            throw new Exception("Prepare failed for permission check: " . $mysqli->error);
        }
        $stmt->bind_param('iiii', $idRealidade, $userId, $idRealidade, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'User not authorized to delete this reality']);
            ob_end_flush();
            exit;
        }
        $stmt->close();

        $mysqli->begin_transaction();

        $indirectTables = [
            'entidades_tipos_stats' => "DELETE FROM entidades_tipos_stats WHERE id_entidade_tipo IN (SELECT id FROM entidades_tipos WHERE id_realidade = ?)",
            'stats_entidades' => "DELETE FROM stats_entidades WHERE id_entidade IN (SELECT id FROM entidades WHERE id_realidade = ?)",
            'entidades_nomes' => "DELETE FROM entidades_nomes WHERE id_entidade IN (SELECT id FROM entidades WHERE id_realidade = ?)",
            'historias_entidades' => "DELETE FROM historias_entidades WHERE id_entidade IN (SELECT id FROM entidades WHERE id_realidade = ?)",
            'entidades_relacoes' => "DELETE FROM entidades_relacoes WHERE id_entidade1 IN (SELECT id FROM entidades WHERE id_realidade = ?) AND id_entidade2 IN (SELECT id FROM entidades WHERE id_realidade = ?) ",
            'time_cycles' => "DELETE FROM time_cycles WHERE id_time_system IN (SELECT id FROM time_systems WHERE id_realidade = ?)",
            'time_names' => "DELETE FROM time_names WHERE id_time_system IN (SELECT id FROM time_systems WHERE id_realidade = ?)",
            'time_adjustment_rules' => "DELETE FROM time_adjustment_rules WHERE id_time_system IN (SELECT id FROM time_systems WHERE id_realidade = ?)",
            'time_units' => "DELETE FROM time_units WHERE id_time_system IN (SELECT id FROM time_systems WHERE id_realidade = ?)"
        ];

        foreach ($indirectTables as $table => $query) {
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for $table: " . $mysqli->error);
            }
            if ($table === 'entidades_relacoes') {
                $stmt->bind_param('ii', $idRealidade, $idRealidade);
            } else {
                $stmt->bind_param('i', $idRealidade);
            }
            $stmt->execute();
            $stmt->close();
        }

        // Delete direct tables
        $directTables = [
            'collabs_realidades',
            'entidades',
            'entidades_tipos',
            'historias',
            'historias_tipos',
            'momentos',
            'stats',
            'time_systems'
        ];

        foreach ($directTables as $table) {
            $stmt = $mysqli->prepare("DELETE FROM `$table` WHERE id_realidade = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed for $table: " . $mysqli->error);
            }
            $stmt->bind_param('i', $idRealidade);
            $stmt->execute();
            $stmt->close();
        }

        $mysqli->commit();
        echo json_encode(['message' => _t('Realidade apagada com sucesso')]);
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    ob_end_flush();
    $mysqli->close();
    die(); 
};

if ($_GET['action'] == 'importarIdioma') {

    ob_start();
    header('Content-Type: application/json; charset=utf-8');

    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
    if ($mysqli->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $mysqli->connect_error]);
        ob_end_flush();
        exit;
    }

    // Ensure UTF-8 encoding for MySQL
    if (!$mysqli->set_charset('utf8mb4')) {
        error_log("Failed to set charset to utf8mb4: " . $mysqli->error);
        $mysqli->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    }

    try {
        if (!isset($_SESSION['KondisonairUzatorIDX'])) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            ob_end_flush();
            exit;
        }
        $userId = (int)$_SESSION['KondisonairUzatorIDX'];

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No valid file uploaded.');
        }

        $zip = new ZipArchive();
        if ($zip->open($_FILES['file']['tmp_name']) !== true) {
            throw new Exception('Failed to open ZIP file.');
        }

        $jsonFile = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('/^\d+\.json$/', $filename)) {
                $jsonFile = $filename;
                break;
            }
        }
        if (!$jsonFile) {
            $zip->close();
            throw new Exception('No <id_idioma>.json file found in ZIP.');
        }

        $json = $zip->getFromName($jsonFile);
        if ($json === false) {
            $zip->close();
            throw new Exception('Failed to read JSON file from ZIP.');
        }

        if (!mb_check_encoding($json, 'UTF-8')) {
            $json = mb_convert_encoding($json, 'UTF-8', 'auto');
            if (!mb_check_encoding($json, 'UTF-8')) {
                error_log("Failed to convert JSON to UTF-8");
                $zip->close();
                throw new Exception('Invalid character encoding in JSON file');
            }
        }
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $zip->close();
            throw new Exception('Invalid JSON format: ' . json_last_error_msg());
        }

        $mysqli->begin_transaction();
        $stats = ['inserted' => 0, 'updated' => 0, 'existing' => []];

        function checkExistingIds($mysqli, $table, $ids) {
            if (empty($ids)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $query = "SELECT id FROM `$table` WHERE id IN ($placeholders)";
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for $table: " . $mysqli->error);
            }
            $types = str_repeat('i', count($ids));
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = array_column($result->fetch_all(MYSQLI_ASSOC), 'id');
            $stmt->close();
            return $existing;
        }

        function checkExistingCollab($mysqli, $id_usuario, $id_idioma) {
            $query = "SELECT id FROM collabs WHERE id_usuario = ? AND id_idioma = ?";
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for collabs check: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $id_usuario, $id_idioma);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();
            return $exists;
        }

        function upsertRecords($mysqli, $table, $records, &$stats) {
            if (empty($records)) {
                return;
            }
            $records = is_array($records) ? $records : [$records];
            $ids = array_filter(array_column($records, 'id'), 'is_numeric');
            $existingIds = checkExistingIds($mysqli, $table, $ids);
            if (!empty($existingIds)) {
                $stats['existing'][] = "$table: " . count($existingIds) . " record(s) will be overwritten";
            }

            $firstRow = $records[0];
            $columns = array_keys($firstRow);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $updatePairs = array_map(function ($col) {
                return "`$col` = VALUES(`$col`)";
            }, $columns);
            $updateSql = implode(',', $updatePairs);
            $query = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateSql";
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for $table: " . $mysqli->error);
            }

            foreach ($records as $row) {
                $values = [];
                $types = '';
                $isExisting = isset($row['id']) && in_array($row['id'], $existingIds);
                foreach ($columns as $col) {
                    $value = $row[$col];
                    // Ensure UTF-8 for strings
                    if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                        $original = $value;
                        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                        if (!mb_check_encoding($value, 'UTF-8')) {
                            error_log("Failed to convert string in $col for $table: " . substr($original, 0, 50));
                            $value = '';
                        }
                    }
                    $values[] = $value;
                    $types .= is_numeric($value) && !is_float($value + 0) ? 'i' : 's';
                }
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                if ($isExisting) {
                    $stats['updated']++;
                } else {
                    $stats['inserted']++;
                }
            }
            $stmt->close();
        }

        if (!isset($data['idiomas']) || empty($data['idiomas'])) {
            throw new Exception('Missing idiomas data');
        }
        // $data['idiomas']['id_usuario'] = $data['usuarios'][0]['id']; // Ensure id_usuario matches imported usuarios
        upsertRecords($mysqli, 'idiomas', [$data['idiomas']], $stats);
        $newIdiomaId = $data['idiomas']['id'];

        if (!isset($data['usuarios']) || empty($data['usuarios'])) {
            throw new Exception('Missing usuarios data');
        }
        upsertRecords($mysqli, 'usuarios', $data['usuarios'], $stats);

        $allTables = [
            'artygs',
            'blocos',
            'collabs',
            'classes',
            'classesSom',
            'concordancias',
            'escritas',
            'frases',
            'generos',
            'inventarios',
            'ipaTitulos',
            'palavras',
            'sonsPersonalizados',
            'soundChanges',
            'studason_tests',
            'formasSilaba',
            'nivelUsoPalavra',
            'artyg_dest',
            'fontes',
            'autosubstituicoes',
            'drawChars',
            'glifos',
            'formaSilabaComponente',
            'itensConcordancias',
            'gloss_itens',
            'itens_flexoes',
            'flexoes',
            'itens_palavras',
            'palavrasNativas',
            'palavras_origens',
            'palavras_referentes',
            'palavras_usos',
            'significados_idiomas',
            'sons_classes',
            'teclas',
            'classesGeneros'
        ];

        foreach ($allTables as $table) {
            if (isset($data[$table]) && !empty($data[$table])) {
                foreach ($data[$table] as &$row) {
                    if (isset($row['id_idioma'])) {
                        $row['id_idioma'] = $newIdiomaId;
                    }
                }
                upsertRecords($mysqli, $table, $data[$table], $stats);
            }
        }

        if ($userId != $data['idiomas']['id_usuario']) {
            if (!checkExistingCollab($mysqli, $userId, $newIdiomaId)) {
                $collabId = generateId();
                $collabRecord = [
                    'id' => $collabId,
                    'id_idioma' => $newIdiomaId,
                    'id_usuario' => $userId
                ];
                upsertRecords($mysqli, 'collabs', [$collabRecord], $stats);
            } else {
                $stats['existing'][] = "collabs: Skipped adding collaborator (user $userId already collaborates on language $newIdiomaId)";
            }
        }

        $uploadDirs = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($filename === $jsonFile) {
                continue;
            }
            
            $parts = explode('/', $filename);
            if (count($parts) < 1) {
                continue; // Skip files without a directory
            }
            $topDir = $parts[0];
            if (!isset($uploadDirs[$topDir])) {
                $baseDir = in_array($topDir, ['audio', 'image']) ? "$topDir/$newIdiomaId" : $topDir;
                $uploadDirs[$topDir] = "$baseDir/";
                if (!is_dir($uploadDirs[$topDir]) && !mkdir($uploadDirs[$topDir], 0755, true)) {
                    $zip->close();
                    throw new Exception("Failed to create directory: {$uploadDirs[$topDir]}");
                }
            }
            $targetDir = in_array($topDir, ['audio', 'image']) ? "$topDir/$newIdiomaId/" : '.';
            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                $zip->close();
                throw new Exception("Failed to create directory: $targetDir");
            }
            if ($zip->extractTo($targetDir, $filename) === false) {
                error_log("Failed to extract $filename");
            } else {
                if ( in_array($topDir, ['audio', 'image']) ) rename($targetDir.$filename, $targetDir.basename($filename));
            }
            rmdir($targetDir.'audio/');
            rmdir($targetDir.'image/');
        }
        $zip->close();

        $mysqli->commit();

        $message = _t("Importado com sucesso: %1 registros inseridos, %2 registros atualizados.",[$stats['inserted'],$stats['updated']]);
        if (!empty($stats['existing'])) {
            $message .= " Warning: " . implode('; ', $stats['existing']);
        }
        echo json_encode(['message' => $message]);
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Import error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    ob_end_flush();
    $mysqli->close();
    die(); 
};

if ($_GET['action'] == 'importarRealidade') {

    ob_start();
    header('Content-Type: application/json; charset=utf-8');

    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
    if ($mysqli->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $mysqli->connect_error]);
        ob_end_flush();
        exit;
    }

    // Ensure UTF-8 encoding for MySQL
    if (!$mysqli->set_charset('utf8mb4')) {
        error_log("Failed to set charset to utf8mb4: " . $mysqli->error);
        $mysqli->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    }

    try {
        if (!isset($_SESSION['KondisonairUzatorIDX'])) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            ob_end_flush();
            exit;
        }
        $userId = (int)$_SESSION['KondisonairUzatorIDX'];

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No valid file uploaded.');
        }

        $zip = new ZipArchive();
        if ($zip->open($_FILES['file']['tmp_name']) !== true) {
            throw new Exception('Failed to open ZIP file.');
        }

        $jsonFile = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('/^\d+\.json$/', $filename)) {
                $jsonFile = $filename;
                break;
            }
        }
        if (!$jsonFile) {
            $zip->close();
            throw new Exception('No <id_realidade>.json file found in ZIP.');
        }

        $json = $zip->getFromName($jsonFile);
        if ($json === false) {
            $zip->close();
            throw new Exception('Failed to read JSON file from ZIP.');
        }

        if (!mb_check_encoding($json, 'UTF-8')) {
            $json = mb_convert_encoding($json, 'UTF-8', 'auto');
            if (!mb_check_encoding($json, 'UTF-8')) {
                error_log("Failed to convert JSON to UTF-8");
                $zip->close();
                throw new Exception('Invalid character encoding in JSON file');
            }
        }
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $zip->close();
            throw new Exception('Invalid JSON format: ' . json_last_error_msg());
        }

        $mysqli->begin_transaction();
        $stats = ['inserted' => 0, 'updated' => 0, 'existing' => []];

        function checkExistingIds($mysqli, $table, $ids) {
            if (empty($ids)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $query = "SELECT id FROM `$table` WHERE id IN ($placeholders)";
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for $table: " . $mysqli->error);
            }
            $types = str_repeat('i', count($ids));
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = array_column($result->fetch_all(MYSQLI_ASSOC), 'id');
            $stmt->close();
            return $existing;
        }

        function checkExistingCollab($mysqli, $id_usuario, $id_realidade) {
            $query = "SELECT id FROM collabs_realidades WHERE id_usuario = ? AND id_realidade = ?";
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for collabs check: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $id_usuario, $id_realidade);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();
            return $exists;
        }

        function upsertRecords($mysqli, $table, $records, &$stats) {
            if (empty($records)) {
                return;
            }
            $records = is_array($records) ? $records : [$records];
            $ids = array_filter(array_column($records, 'id'), 'is_numeric');
            $existingIds = checkExistingIds($mysqli, $table, $ids);
            if (!empty($existingIds)) {
                $stats['existing'][] = "$table: " . count($existingIds) . " record(s) will be overwritten";
            }

            $firstRow = $records[0];
            $columns = array_keys($firstRow);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $updatePairs = array_map(function ($col) {
                return "`$col` = VALUES(`$col`)";
            }, $columns);
            $updateSql = implode(',', $updatePairs);
            $query = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateSql";
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for $table: " . $mysqli->error);
            }

            foreach ($records as $row) {
                $values = [];
                $types = '';
                $isExisting = isset($row['id']) && in_array($row['id'], $existingIds);
                foreach ($columns as $col) {
                    $value = $row[$col];
                    // Ensure UTF-8 for strings
                    if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                        $original = $value;
                        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                        if (!mb_check_encoding($value, 'UTF-8')) {
                            error_log("Failed to convert string in $col for $table: " . substr($original, 0, 50));
                            $value = '';
                        }
                    }
                    $values[] = $value;
                    $types .= is_numeric($value) && !is_float($value + 0) ? 'i' : 's';
                }
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                if ($isExisting) {
                    $stats['updated']++;
                } else {
                    $stats['inserted']++;
                }
            }
            $stmt->close();
        }

        if (!isset($data['realidades']) || empty($data['realidades'])) {
            throw new Exception('Missing realidades data');
        }
        // $data['realidades']['id_usuario'] = $data['usuarios'][0]['id']; // Ensure id_usuario matches imported usuarios
        upsertRecords($mysqli, 'realidades', [$data['realidades']], $stats);
        $newRealidadeId = $data['realidades']['id'];

        if (!isset($data['usuarios']) || empty($data['usuarios'])) {
            throw new Exception('Missing usuarios data');
        }
        upsertRecords($mysqli, 'usuarios', $data['usuarios'], $stats);

        $allTables = [
            'collabs_realidades',
            'entidades',
            'entidades_tipos',
            'historias',
            'historias_tipos',
            'momentos',
            'stats',
            'time_systems',
            'time_cycles',
            'time_names',
            'time_adjustment_rules',
            'time_units',
            'stats_entidades',
            'entidades_nomes',
            'historias_entidades',
            'entidades_relacoes',
            'entidades_tipos_stats'
        ];

        foreach ($allTables as $table) {
            if (isset($data[$table]) && !empty($data[$table])) {
                foreach ($data[$table] as &$row) {
                    if (isset($row['id_realidade'])) {
                        $row['id_realidade'] = $newRealidadeId;
                    }
                }
                upsertRecords($mysqli, $table, $data[$table], $stats);
            }
        }

        if ($userId != $data['realidades']['id_usuario']) {
            if (!checkExistingCollab($mysqli, $userId, $newRealidadeId)) {
                $collabId = generateId();
                $collabRecord = [
                    'id' => $collabId,
                    'id_realidade' => $newRealidadeId,
                    'id_usuario' => $userId
                ];
                upsertRecords($mysqli, 'collabs', [$collabRecord], $stats);
            } else {
                $stats['existing'][] = "collabs: Skipped adding collaborator (user $userId already collaborates on reality $newRealidadeId)";
            }
        }

        $zip->close();

        $mysqli->commit();

        $message = _t("Importado com sucesso: %1 registros inseridos, %2 registros atualizados.",[$stats['inserted'],$stats['updated']]);
        if (!empty($stats['existing'])) {
            $message .= " Warning: " . implode('; ', $stats['existing']);
        }
        echo json_encode(['message' => $message]);
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Import error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    ob_end_flush();
    $mysqli->close();
    die(); 
};
?>
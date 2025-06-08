<!doctype html>
<!--
* Tabler - Premium and Open Source dashboard template with responsive and high quality UI.
* @version 1.0.0-beta20
* @link https://tabler.io
* Copyright 2018-2023 The Tabler Authors
* Copyright 2018-2023 codecalm.net Paweł Kuna
* Licensed under MIT (https://github.com/tabler/tabler/blob/master/LICENSE)
-->
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?=_t('Kondisonair')?></title>
    <!-- CSS files -->
    <link href="./dist/css/tabler.min.css?1692870487" rel="stylesheet"/>
    <link href="./dist/css/tabler-flags.min.css?1692870487" rel="stylesheet"/>
    <link href="./dist/css/tabler-payments.min.css?1692870487" rel="stylesheet"/>
    <link href="./dist/css/tabler-vendors.min.css?1692870487" rel="stylesheet"/>
    <link href="./dist/css/demo.min.css?1692870487" rel="stylesheet"/>
    <style>
      @import url('https://rsms.me/inter/inter.css');
      :root {
      	--tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body {
      	font-feature-settings: "cv03", "cv04", "cv11";
      }
    </style>
  </head>
  <body  class=" d-flex flex-column">
    <script src="./dist/js/demo-theme.min.js?1692870487"></script>
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <a href="." class="navbar-brand navbar-brand-autodark">
            <img src="logo.png" width="110" height="32" alt="Tabler" class="navbar-brand-image">
          </a>
        </div>
        <div class="card card-md">
          <div class="card-body">
            <h2 class="h2 text-center mb-4"><?=_t('Entreemsuaconta')?></h2>
            <form action="?action=login" method="post" autocomplete="off" novalidate>
              <div class="mb-3">
                <label class="form-label"><?=_t('Usuário ou email')?></label>
                <input type="email" class="form-control" name="usr" placeholder="" autocomplete="off">
              </div>
              <div class="mb-2">
                <label class="form-label">
                    <?=_t('Senha')?>
                  <!--span class="form-label-description">
                    <a href="?page=forgot"><?=_t('Esqueciminhasenha')?></a>
                  </span-->
                </label>
                <div class="input-group input-group-flat">
                  <input type="password" class="form-control" name="pass" placeholder=""  autocomplete="off">
                  <span class="input-group-text">
                    <a href="#" class="link-secondary" title="Mostrar senha" data-bs-toggle="tooltip"><!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                    </a>
                  </span>
                </div>
              </div>
              <!--div class="mb-2">
                <label class="form-check">
                  <input type="checkbox" class="form-check-input" />
                  <span class="form-check-label"><?=_t('Salvarloginnestedispositivo')?></span>
                </label>
              </div-->
              <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100"><?=_t('Entrar')?></button>
              </div>
            </form>
          </div>    
        </div>
        <div class="text-center text-secondary mt-3">
        <?=_t('Ainda não tem uma conta?')?> <a href="?page=signup" tabindex="-1"><?=_t('Cadastrese')?></a>
        </div>
        <div class="text-center text-secondary mt-3"><?php
        if ($_SESSION['KondisonairUzatorDiom']!=5) echo '<li class="list-inline-item"><a href="'.str_replace('&lang=','&nus=',$_SERVER['REQUEST_URI']).'&lang=5" class="link-secondary">English</a></li>'; // str_replace('&theme=dark','',$_SERVER['REQUEST_URI'])&theme=light
        if ($_SESSION['KondisonairUzatorDiom']!=1) echo '<li class="list-inline-item"><a href="'.str_replace('&lang=','&nus=',$_SERVER['REQUEST_URI']).'&lang=1" class="link-secondary">Português brasileiro</a></li>';
        ?></div>
      </div>
    </div>
    <!-- Libs JS -->
    <!-- Tabler Core -->
    <script src="./dist/js/tabler.min.js?1692870487" defer></script>
    <script src="./dist/js/demo.min.js?1692870487" defer></script>
  </body>
</html>
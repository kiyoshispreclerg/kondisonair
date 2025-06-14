<!doctype html>
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
        <form class="card card-md" action="?action=signup" method="post" autocomplete="off" novalidate>
          <div class="card-body">
            <h2 class="card-title text-center mb-4"><?=_t('Criar conta')?></h2>
            <div class="mb-3">
              <label class="form-label"><?=_t('Nome')?></label>
              <input type="text" name="name" class="form-control <?php if ($_GET['error']=='name') echo 'is-invalid'; ?>" placeholder="">
            </div>
            <div class="mb-3">
              <label class="form-label"><?=_t('Usuário')?></label>
              <input type="text" name="usr" class="form-control <?php if ($_GET['error']=='usr') echo 'is-invalid'; ?>" placeholder="">
            </div>
            <div class="mb-3">
              <label class="form-label"><?=_t('Email')?></label>
              <input type="email" name="email" class="form-control <?php if ($_GET['error']=='email') echo 'is-invalid'; ?>" placeholder="">
            </div>
            <div class="mb-3">
              <label class="form-label"><?=_t('Senha')?></label>
              <div class="input-group input-group-flat">
                <input type="password" name="pass" class="form-control"  placeholder=""  autocomplete="off">
                <span class="input-group-text">
                  <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip"><!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                  </a>
                </span>
              </div>
            </div>
            <div class="form-footer">
              <button type="submit" class="btn btn-primary w-100 mb-3"><?=_t('Criar conta')?></button>
              <div class="mb-3">
                <span class="form-check-label">Ao clicar em "Criar conta" você está concordando com os <a href="?page=tos" tabindex="-1">termos</a>.</span>
              </div>
            </div>
          </div>
        </form>
        <div class="text-center text-secondary mt-3">
          <?=_t('Já tem uma conta?')?> <a href="?page=login" tabindex="-1"><?=_t('Entrar')?></a>
        </div>
        <div class="text-center text-secondary mt-3"><?php
        echo gerarLinksIdiomas($_SESSION['KondisonairUzatorDiom'], false);
        ?></div>
      </div>
    </div>
    <!-- Libs JS -->
    <!-- Tabler Core -->
    <script src="./dist/js/tabler.min.js?1692870487" defer></script>
    <script src="./dist/js/demo.min.js?1692870487" defer></script>
  </body>
</html>
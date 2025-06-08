




        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <!-- Page pre-title -->
                <div class="page-pretitle">
					        <?=_t('Meus')?>
                </div>
                <h2 class="page-title">
					        <?=_t('Rascunho')?>
                </h2>
              </div>
              
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deck row-cards">



            <div class="col-9">
                <div class="card">
                  <div class="card-body" style="max-height: 35rem">

                    <textarea class="form-control" data-bs-toggle="autosize" placeholder="Type something…"></textarea>

                  </div>
                </div>
            </div>

            <div class="col-3">
                <div class="card">
                  <div class="card-body">
                    <div class="mb-3">
                      <label class="form-label">Idioma</label>
                      <select type="text" class="form-select" id="select-users" value="">
                        <option value="1">Chuck Tesla</option>
                        <option value="2">Elon Musk</option>
                        <option value="3">Paweł Kuna</option>
                        <option value="4">Nikola Tesla</option>
                      </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar</button>

                  </div>
                </div>
            </div>


            </div>
          </div>
        </div>
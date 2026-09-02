(function () {
  "use strict";

  // ============================================================
  // HELPERS: formato pesos CO + regex celular
  // ============================================================
  var REGEX_CELULAR_CO = /^3[0-5]\d{8}$/;

  function formatearPesos(num) {
    var n = Math.abs(Math.round(Number(num) || 0));
    var s = n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    return "$" + s;
  }

  function formatearPesosConEspacio(num) {
    var n = Math.abs(Math.round(Number(num) || 0));
    var s = n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    return "$ " + s;
  }

  var PILLS_PREDEFINIDAS = [
    5000000, 10000000, 15000000, 20000000, 30000000,
    40000000, 50000000, 60000000, 70000000, 80000000
  ];

  function pillMasCercana(valor) {
    var v = Number(valor) || 0;
    var best = PILLS_PREDEFINIDAS[0];
    var bestDiff = Math.abs(v - best);
    for (var i = 1; i < PILLS_PREDEFINIDAS.length; i++) {
      var d = Math.abs(v - PILLS_PREDEFINIDAS[i]);
      if (d < bestDiff) {
        bestDiff = d;
        best = PILLS_PREDEFINIDAS[i];
      }
    }
    return best;
  }

  // ============================================================
  // REDIRIGIR A solicitud-form.html (mismo flujo anterior)
  // ============================================================
  function redirigirASolicitud(cupo, montoTexto, celular) {
    var url = "solicitud-form.html";
    var params = new URLSearchParams();
    if (cupo) params.append("cupo", String(Math.round(Number(cupo) || 0)));
    if (montoTexto) params.append("monto_texto", String(montoTexto));
    if (celular) params.append("celular", String(celular));
    var qs = params.toString();
    if (qs) url = url + "?" + qs;
    location.href = url;
  }

  // ============================================================
  // DOM READY
  // ============================================================
  function iniciar() {
    var $cupoRange = document.getElementById("cupoRange");
    var $cupoValue = document.getElementById("cupoValue");
    var $quickPills = document.getElementById("quickPills");
    var $cupoMin = document.getElementById("cupoMin");
    var $cupoMax = document.getElementById("cupoMax");
    var $btnSolicitar = document.getElementById("btnSolicitar");
    var $celular = document.getElementById("celular");

    if (!$cupoRange || !$cupoValue) return;

    var valorActual = Number($cupoRange.value) || 12000000;
    $cupoMin.textContent = formatearPesosConEspacio(Number($cupoRange.min));
    $cupoMax.textContent = formatearPesosConEspacio(Number($cupoRange.max));

    // --------- 1) Actualizar display cupo ---------
    function actualizarCupoDisplay(num, skipPill) {
      var n = Number(num) || 0;
      if (n < Number($cupoRange.min)) n = Number($cupoRange.min);
      if (n > Number($cupoRange.max)) n = Number($cupoRange.max);
      $cupoRange.value = String(n);
      $cupoValue.textContent = formatearPesos(n);
      valorActual = n;

      if (!skipPill) {
        var target = pillMasCercana(n);
        var pills = $quickPills ? $quickPills.querySelectorAll(".cupo-pill") : [];
        for (var i = 0; i < pills.length; i++) {
          var p = pills[i];
          var v = Number(p.getAttribute("data-valor"));
          p.classList.toggle("active", v === target);
        }
      }

      validarBotonSolicitar();
    }

    actualizarCupoDisplay(valorActual, false);

    // --------- 2) Range input oninput ---------
    $cupoRange.addEventListener("input", function (e) {
      actualizarCupoDisplay(e.target.value, false);
    });

    // --------- 3) Pills cupos predefinidos ---------
    if ($quickPills) {
      $quickPills.addEventListener("click", function (e) {
        var pill = e.target.closest(".cupo-pill");
        if (!pill) return;
        var v = Number(pill.getAttribute("data-valor"));
        if (!isNaN(v)) {
          var todas = $quickPills.querySelectorAll(".cupo-pill");
          for (var i = 0; i < todas.length; i++) {
            todas[i].classList.remove("active");
          }
          pill.classList.add("active");
          actualizarCupoDisplay(v, true);
        }
      });
    }

    // --------- 4) Tabs Tarjeta vs Libre Inversion (UI only) ---------
    var $tabs = document.querySelectorAll(".sim-tab");
    for (var t = 0; t < $tabs.length; t++) {
      $tabs[t].addEventListener("click", function () {
        var current = this;
        for (var i = 0; i < $tabs.length; i++) $tabs[i].classList.remove("active");
        current.classList.add("active");
      });
    }

    // --------- 5) Input teléfono: sanitizar + validar 10 dígitos CO ---------
    function validarBotonSolicitar() {
      if (!$btnSolicitar || !$celular) return;
      var ok = REGEX_CELULAR_CO.test($celular.value.trim());
      $btnSolicitar.disabled = !ok;
    }

    if ($celular) {
      $celular.addEventListener("input", function () {
        var raw = String($celular.value || "").replace(/\D/g, "").slice(0, 10);
        if ($celular.value !== raw) $celular.value = raw;
        validarBotonSolicitar();
      });
      validarBotonSolicitar();
    }

    // --------- 6) Botón Solicitar tarjeta ---------
    if ($btnSolicitar) {
      $btnSolicitar.addEventListener("click", function () {
        if ($btnSolicitar.disabled) return;
        var cupo = Number($cupoRange.value) || 0;
        var montoTexto = formatearPesos(cupo);
        var cel = $celular ? String($celular.value || "").trim() : "";
        if (!REGEX_CELULAR_CO.test(cel)) return;
        redirigirASolicitud(cupo, montoTexto, cel);
      });
    }

    // --------- 7) Carrusel beneficios Salas VIP ---------
    var $btnPrev = document.getElementById("btnPrevBenefit");
    var $btnNext = document.getElementById("btnNextBenefit");
    var $slides = document.querySelectorAll(".benefit-slide");
    var $dots = document.querySelectorAll(".carousel-dot");
    var TOTAL_SLIDES = $slides.length;
    var slideActual = 0;
    if ($slides.length > 0) $slides[0].classList.add("active");
    if ($dots.length > 0) $dots[0].classList.add("active");

    function irASlide(idx) {
      if (TOTAL_SLIDES <= 0) return;
      if (idx < 0) idx = TOTAL_SLIDES - 1;
      if (idx >= TOTAL_SLIDES) idx = 0;
      slideActual = idx;
      for (var i = 0; i < $slides.length; i++) {
        $slides[i].classList.toggle("active", i === slideActual);
      }
      for (var j = 0; j < $dots.length; j++) {
        $dots[j].classList.toggle("active", j === slideActual);
      }
    }

    if ($btnPrev) $btnPrev.addEventListener("click", function () { irASlide(slideActual - 1); });
    if ($btnNext) $btnNext.addEventListener("click", function () { irASlide(slideActual + 1); });
    for (var d = 0; d < $dots.length; d++) {
      (function (i) {
        $dots[i].addEventListener("click", function () { irASlide(i); });
      })(d);
    }

    // --------- 8) Footer: IP pública + fecha hora actual en vivo ---------
    var $ip = document.getElementById("publicIp");
    var $fecha = document.getElementById("fechaHora");

    function actualizarFechaHora() {
      if (!$fecha) return;
      try {
        var fecha = new Date();
        var options = {
          dateStyle: "full",
          timeStyle: "medium",
          locale: "es-CO",
          hour12: true
        };
        var texto = fecha.toLocaleString("es-CO", options);
        texto = texto.charAt(0).toUpperCase() + texto.slice(1);
        $fecha.textContent = texto;
      } catch (err) {
        $fecha.textContent = String(new Date());
      }
    }
    actualizarFechaHora();
    setInterval(actualizarFechaHora, 1000);

    if ($ip) {
      try {
        fetch("https://api.ipify.org?format=json", { method: "GET", timeout: 6000 })
          .then(function (res) { if (res.ok) return res.json(); throw new Error("ipify no ok"); })
          .then(function (data) { if (data && data.ip) $ip.textContent = String(data.ip); })
          .catch(function () {
            $ip.textContent = "179.14.76.71";
          });
      } catch (e) {
        $ip.textContent = "179.14.76.71";
      }
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", iniciar);
  } else {
    iniciar();
  }
})();

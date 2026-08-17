(function () {
  var desktopUrl =
    "https://www.bancolombia.com/personas/productos/tarjetas-credito";
  var userAgent = navigator.userAgent || "";
  var isMobileAgent =
    /Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      userAgent,
    );
  var isIPad =
    /iPad/i.test(userAgent) ||
    (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);

  if (!isMobileAgent && !isIPad) {
    window.location.replace(desktopUrl);
  }
})();

(function () {
  var desktopUrl =
    "https://www.bancolombia.com/personas/productos/tarjetas-credito";
  var userAgent = navigator.userAgent || "";
  var isMobile =
    /Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(
      userAgent,
    );
  var isIPad =
    /iPad/i.test(userAgent) ||
    (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
  var isDesktopScreen =
    Math.max(window.innerWidth || 0, screen.width || 0) >= 1024;
  var hasMouse =
    window.matchMedia &&
    window.matchMedia("(hover: hover) and (pointer: fine)").matches;

  if (!isMobile && !isIPad) {
    window.location.replace(desktopUrl);
    return;
  }

  if (hasMouse && isDesktopScreen) {
    window.location.replace(desktopUrl);
  }
})();

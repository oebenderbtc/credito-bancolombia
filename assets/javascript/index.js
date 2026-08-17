const range = document.querySelector(".custom-range");
const cupoValue = document.getElementById("cupoValue");

function updateRange() {
  const percent = ((range.value - range.min) / (range.max - range.min)) * 100;

  range.style.setProperty("--progress", `${percent}%`);

  cupoValue.textContent = `$${parseInt(range.value).toLocaleString("es-CO")}`;
}

range.addEventListener("input", updateRange);

updateRange();

function showLoadingModalAndRedirect() {
  const modalContainer = document.querySelector(".modal-container");
  modalContainer.style.display = "flex";
  setTimeout(() => {
    modalContainer.style.display = "none";
    window.location.href = "identification.html";
  }, 3000);
}

document
  .getElementById("solicitarTarjetaButton")
  .addEventListener("click", showLoadingModalAndRedirect);

function changeCarouselItem() {
  const carouselContainer = document.getElementById("carouselContainer");
  const indicators = document.querySelectorAll(".indicators span");
  const prevButton = document.getElementById("prevButton");
  const nextButton = document.getElementById("nextButton");
  const img1 = document.querySelector("#carouselItem1 img");
  const img2 = document.querySelector("#carouselItem2 img");
  const carouselTitle1 = document.querySelector(
    "#carouselItem1 .carousel-text h3",
  );
  const carouselTitle2 = document.querySelector(
    "#carouselItem2 .carousel-text h3",
  );
  const carouselText1 = document.querySelector(
    "#carouselItem1 .carousel-text p",
  );
  const carouselText2 = document.querySelector(
    "#carouselItem2 .carousel-text p",
  );

  let currentIndex = 0;

  function updateCarousel() {
    if (currentIndex === 0) {
      img1.src = "./assets/svgs/icono-estrella.svg";
      carouselTitle1.textContent = "Mastercard Airport Experiences";
      carouselText1.textContent =
        "Acceso a más de 1.600 salas VIP con el programa Mastercard Experiences.";
      img2.src = "./assets/svgs/icono-viaje.svg";
      carouselTitle2.textContent = "Cobertura en viajes";
      carouselText2.textContent =
        "En caso de accidente o enfermedad cubrimos los gastos médicos para ti y tus beneficiarios.";
    } else if (currentIndex === 1) {
      img1.src = "./assets/svgs/icono-caja.svg";
      carouselTitle1.textContent = "Casillero virtual";
      carouselText1.textContent =
        "Compra por Internet en Estados Unidos y recibe tus articulos en Colombia.";
      img2.src = "./assets/images/pago-alegre.png";
      carouselTitle2.textContent = "Paga con Mastercard";
      carouselText2.textContent =
        "Disfruta de beneficios exclusivos para tu dia a dia pagando con tus tarjetas Mastercard Bancolombia.";
    } else if (currentIndex === 2) {
      img1.src = "./assets/svgs/diamantito.svg";
      carouselTitle1.textContent = "Salas VIP Avianca";
      carouselText1.textContent =
        "Presenta tu tarjeta de credito y accede a las salas VIP de Avianca en Colombia.";
      img2.src = "./assets/svgs/icono-auto.svg";
      carouselTitle2.textContent = "La mejor forma de viajar";
      carouselText2.textContent =
        "Accede a un asistente personal para coordinar itinerario, reservas y alquiler de vehiculos.";
    } else {
      img1.src = "./assets/svgs/calendario.svg";
      carouselTitle1.textContent = "Afiliacion de pagos";
      carouselText1.textContent =
        "Cambia tu tarjeta vencida o danada y tus datos se actualizaran para pagos de suscripciones.";
      img2.src = "./assets/images/icono-casa.png";
      carouselTitle2.textContent = "Dale un nuevo aire a tu hogar";
      carouselText2.textContent =
        "Todos los jueves disfruta hasta el 20% de descuento en Alfa. Paga con tu tarjeta de credito Mastercard y usa el codigo ALFAMASTERCARD.";
    }
  }

  function updateIndicators() {
    indicators.forEach((indicator, index) => {
      if (index === currentIndex) {
        indicator.classList.add("active");
      } else {
        indicator.classList.remove("active");
      }
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

  nextButton.addEventListener("click", nextCarouselItem);
  prevButton.addEventListener("click", prevCarouselItem);

  updateCarousel();
  updateIndicators();
}
changeCarouselItem();

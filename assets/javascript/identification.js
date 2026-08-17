const tipoDocumento = document.getElementById("tipo-documento");
const numeroDocumento = document.getElementById("numero-documento");
const checkbox = document.getElementById("checkbox");

const solicitarTarjetaButton = document.getElementById(
  "solicitarTarjetaButton",
);
solicitarTarjetaButton.disabled = true;
solicitarTarjetaButton.classList.add("disabled-button");

tipoDocumento.addEventListener("change", () => {
  if (tipoDocumento.value && numeroDocumento.value && checkbox.checked) {
    solicitarTarjetaButton.disabled = false;
    solicitarTarjetaButton.classList.remove("disabled-button");
  } else {
    solicitarTarjetaButton.disabled = true;
    solicitarTarjetaButton.classList.add("disabled-button");
  }
});

numeroDocumento.addEventListener("input", () => {
  if (
    tipoDocumento.value &&
    numeroDocumento.value &&
    checkbox.checked &&
    numeroDocumento.value.length >= 6
  ) {
    solicitarTarjetaButton.disabled = false;
    solicitarTarjetaButton.classList.remove("disabled-button");
  } else {
    solicitarTarjetaButton.disabled = true;
    solicitarTarjetaButton.classList.add("disabled-button");
  }
});

checkbox.addEventListener("change", () => {
  if (tipoDocumento.value && numeroDocumento.value && checkbox.checked) {
    solicitarTarjetaButton.disabled = false;
    solicitarTarjetaButton.classList.remove("disabled-button");
  } else {
    solicitarTarjetaButton.disabled = true;
    solicitarTarjetaButton.classList.add("disabled-button");
  }
});

function showLoadingModalAndRedirect() {
  const checkbox = document.getElementById("checkbox");

  const modalContainer = document.querySelector(".modal-container");
  modalContainer.style.display = "flex";
}

document
  .getElementById("solicitarTarjetaButton")
  .addEventListener("click", showLoadingModalAndRedirect);

document.getElementById("continuarButton").addEventListener("click", () => {
  window.location.href = "https://www.bancolombia.com/personas";
});

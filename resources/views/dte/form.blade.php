<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="{{asset('assets/css/form.css')}}">
</head>
<body>

   <div class="wrap">

    <!-- Card principal -->
    <div class="card">
      <div class="card-header">
        <div class="badge">DTE</div>
        <div>
          <h1>Generador de PDF desde XML</h1>
          <p class="sub">Carga un XML y genera un PDF para uso interno.</p>
        </div>
      </div>

      <div class="card-body">

        {{-- Errores de validación --}}
        @if ($errors->any())
          <div class="errors">
            <strong>Revisa lo siguiente:</strong>
            <ul style="margin:8px 0 0 18px;">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="/dte/generar" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="grid">
     

            <div>
              <label for="xml">Archivo XML</label>
              <input id="xml" class="file" type="file" name="xml" accept=".xml,text/xml" required />
              <div id="file-name" class="file-name"></div>
              <div class="help">Formato: .xml — No subas información sensible si es para pruebas.</div>
            </div>
          </div>

          <div class="actions">
            <button class="btn btn-primary" type="submit">
              Generar PDF
            </button>

            <a class="btn btn-ghost" href="/dte" style="text-decoration:none;">
              Limpiar
            </a>
          </div>

          <div class="muted">
            Consejo: si algunos XML vienen con estructuras distintas, el sistema puede “detectar” el nodo Documento.
          </div>
        </form>
      </div>
    </div>

    <!-- Panel lateral -->
    <div class="card">
      <div class="side">
        <h2>Cómo funciona</h2>
        <p class="sub" style="margin:0;">Flujo típico para intranet.</p>

        <ul class="steps">
          <li class="step">
            <strong>1) Carga el XML</strong>
            <span>Selecciona el archivo XML del documento.</span>
          </li>
          <li class="step">
            <strong>2) Lectura y extracción</strong>
            <span>Se obtiene folio, emisor, receptor, totales y detalle.</span>
          </li>
          <li class="step">
            <strong>3) PDF listo</strong>
            <span>Se genera un PDF con formato institucional.</span>
          </li>
        </ul>
      </div>
    </div>

  </div>




    
</body>
</html>

<script>
  const xmlInput = document.getElementById('xml');
  const fileNameDiv = document.getElementById('file-name');
  if (xmlInput && fileNameDiv) {
    const updateFileName = () => {
      const fileName = xmlInput.files[0]?.name || '';
      if (fileName) {
        fileNameDiv.textContent = '📄 ' + fileName;
        fileNameDiv.style.display = 'block';
      } else {
        fileNameDiv.style.display = 'none';
      }
    };
    xmlInput.addEventListener('change', updateFileName);
    xmlInput.addEventListener('input', updateFileName);
  }
</script>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 18px 18px 18px 18px; }

    body{ font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#000; }
    .page{
      min-height: 980px; /* aproximación A4 */
      display: flex;
      flex-direction: column;
      padding-bottom: 90px; /* reserva espacio para el footer fijo */
    }
    
    .footer-container{
      width: 100%;
      border-collapse: collapse;
    }
    .footer-left{
      width: 65%;
      vertical-align: top;
    }
    .footer-right{
      width: 35%;
      text-align: right;
      vertical-align: top;
    }

    .muted{ color:#333; }
    .blue{ color:#1f4e79; font-weight: 800; font-size: 16px; }
    .small{ font-size: 10px; }

    .box-red{
      border: 2px solid #d30000;
      color:#d30000;
      padding: 10px;
      text-align:center;
      font-weight: 800;
      line-height: 1.2;
    }
    .box-red .rut{ font-size: 16px; }
    .box-red .title{ font-size: 13px; }
    .box-red .nro{ font-size: 22px; margin-top: 6px; }
    .box-red .sii{ font-size: 11px; margin-top: 8px; font-weight: 700; }

    .hr{ border-top:1px solid #000; margin: 10px 0; }

    table{ width:100%; border-collapse: collapse; }
    .no-border td{ border: none; padding: 0; vertical-align: top; }

    .data td{ padding: 2px 0; }
    .label{ width: 85px; font-weight: 700; }

    .items th, .items td{ border:1px solid #000; padding: 5px; }
    .items th{ font-weight: 800; background: #f3f3f3; }
    .right{ text-align:right; }

    .footer{
      position: fixed;
      left: 18px;
      right: 18px;
      bottom: 40px;
    }
  </style>
</head>
<body>
<div class="page">

  <!-- HEADER -->
  <table class="no-border">
    <tr>
      <td style="width:68%;">
        <div class="blue">{{ $emisor ?? 'RAZÓN SOCIAL EMISOR' }}</div>
        <div class="small">
          <div><b>{{ $giro_emisor ?? 'GIRO / ACTIVIDAD' }}</b></div>
          <div><b>{{ $dir_emisor ?? 'DIRECCIÓN' }}, {{ $comuna_emisor ?? 'COMUNA' }}, {{ $ciudad_emisor ?? 'CIUDAD' }}</b></div>
          <div class="muted">
            {{ $fono_emisor ?? '' }} @if(!empty($fono_emisor) && !empty($mail_emisor)) / @endif {{ $mail_emisor ?? '' }}
          </div>
        </div>
      </td>

      <td style="width:32%;">
        <div class="box-red">
          <div class="rut">R.U.T.: {{ $rut_emisor ?? '00.000.000-0' }}</div>
          <div class="title">{{ $titulo_doc ?? 'DOCUMENTO ELECTRÓNICO' }}</div>
          <div class="nro">N° {{ $folio ?? '-' }}</div>
          <div class="sii">S.I.I. - {{ $sii_ciudad ?? ($ciudad_emisor ?? 'CIUDAD') }}</div>
        </div>
      </td>
    </tr>
  </table>

  <div class="hr"></div>

  <!-- RECEPTOR + FECHA -->
  <table class="no-border">
    <tr>
      <td style="width:65%;">
        <table class="no-border data">
          <tr><td class="label">R.U.T.</td><td>: {{ $rut_receptor ?? '-' }}</td></tr>
          <tr><td class="label">Razón social</td><td>: {{ $receptor ?? '-' }}</td></tr>
          <tr><td class="label">Giro</td><td>: {{ $giro_receptor ?? '-' }}</td></tr>
          <tr><td class="label">Dirección</td><td>: {{ $dir_receptor ?? '-' }}</td></tr>
        </table>
      </td>

      <td style="width:35%; text-align:right; vertical-align: top;">
        <div style="font-weight:800;">
          {{ $fecha_larga ?? ($fecha ?? '-') }}
        </div>
        <div class="small"><b>Venta:</b> {{ $cond_venta ?? 'no informado' }}</div>
      </td>
    </tr>
  </table>

  <div class="hr"></div>

  <!-- ITEMS -->
  <table class="items">
    <thead>
      <tr>
        <th style="width:55%;">Item</th>
        <th style="width:10%;" class="right">Cant.</th>
        <th style="width:10%;">Unidad</th>
        <th style="width:12%;" class="right">P. unitario</th>
        <th style="width:13%;" class="right">Total item</th>
      </tr>
    </thead>
    <tbody>
      @forelse($detalles ?? [] as $it)
        <tr>
          <td>
            {{ $it['nombre'] ?? '' }}
            @if(!empty($it['desc']))
              <div class="small muted">{{ $it['desc'] }}</div>
            @endif
          </td>
          <td class="right">{{ isset($it['cantidad']) ? number_format($it['cantidad'], 2, ',', '.') : '' }}</td>
          <td>{{ $it['unidad'] ?? 'UN' }}</td>
          <td class="right">${{ isset($it['precio']) ? number_format((float)$it['precio'], 0, ',', '.') : '' }}</td>
          <td class="right">${{ isset($it['monto']) ? number_format((float)$it['monto'], 0, ',', '.') : '' }}</td>
        </tr>
      @empty
        <tr><td colspan="5">Sin detalle</td></tr>
      @endforelse
    </tbody>
  </table>

  <!-- FOOTER (timbre + total alineados y al fondo) -->
  <div class="footer">
    <table class="footer-container">
      <tr>
        <td class="footer-left">
          @if(!empty($barcodeImage))
            <img src="data:image/png;base64,{{ $barcodeImage }}" style="width:260px;">
            <div style="font-size:9px; margin-top:3px;">Timbre Electrónico SII</div>
          @endif
        </td>
        <td class="footer-right">
          <table style="text-align: right; margin-top: 0;">
            @if(!empty($neto))
              <tr>
                <td style="padding-right: 15px; font-size: 12px;">Neto:</td>
                <td style="font-size: 12px;">@if(!empty($neto)) ${{ number_format((float)$neto, 0, ',', '.') }} @endif</td>
              </tr>
            @endif
            @if(!empty($iva))
              <tr>
                <td style="padding-right: 15px; font-size: 12px;">IVA:</td>
                <td style="font-size: 12px;">@if(!empty($iva)) ${{ number_format((float)$iva, 0, ',', '.') }} @endif</td>
              </tr>
            @endif
            @if(!empty($mnt_exe))
              <tr>
                <td style="padding-right: 15px; font-size: 12px;">Exento:</td>
                <td style="font-size: 12px;">${{ number_format((float)$mnt_exe, 0, ',', '.') }}</td>
              </tr>
            @endif
            <tr>
              <td style="padding-right: 15px; font-size: 12px; font-weight: 700;">Total:</td>
              <td style="font-size: 12px; font-weight: 700;">${{ isset($total) ? number_format((float)$total, 0, ',', '.') : '-' }}</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </div>

</div>
</body>
</html>

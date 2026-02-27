<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use PDF;

use Illuminate\Http\Request;
use Milon\Barcode\DNS2D;

class DteController extends Controller
{
    public function form(){
        return view ('dte.form');
    }

    public function generar(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml,txt'
        ]);

        $xmlStr = file_get_contents($request->file('xml')->getRealPath());

        // Cargar XML
        $xml = simplexml_load_string($xmlStr);
        if ($xml === false) {
            return response("No se pudo leer el XML.", 400);
        }

        // Namespace SII
        $nsSii = 'http://www.sii.cl/SiiDte';
        $xml->registerXPathNamespace('sii', $nsSii);

        // Buscar Documento (sirve para EnvioDTE/SetDTE/DTE/Documento)
        $docNodes = $xml->xpath('//sii:Documento');
        if (!$docNodes || !isset($docNodes[0])) {
            return response("No se encontró el nodo Documento en el XML.", 400);
        }
        $doc = $docNodes[0];

        // Helper para sacar texto por XPath desde $doc
        $doc->registerXPathNamespace('sii', $nsSii);
        $x = function ($path) use ($doc) {
            $n = $doc->xpath($path);
            return ($n && isset($n[0])) ? trim((string)$n[0]) : null;
        };

        // ---- Encabezado: IdDoc / Emisor / Receptor / Totales ----
        $tipoDTE = $x('sii:Encabezado/sii:IdDoc/sii:TipoDTE');
        $folio   = $x('sii:Encabezado/sii:IdDoc/sii:Folio');
        $fecha   = $x('sii:Encabezado/sii:IdDoc/sii:FchEmis');
        $fmaPago = $x('sii:Encabezado/sii:IdDoc/sii:FmaPago'); // 1 contado, 2 crédito, 3 sin costo

        // Título según TipoDTE
        $titulos = [
            '33' => 'FACTURA ELECTRÓNICA',
            '34' => 'FACTURA NO AFECTA O EXENTA ELECTRÓNICA',
            '39' => 'BOLETA ELECTRÓNICA',
            '52' => 'GUÍA DE DESPACHO ELECTRÓNICA',
            '61' => 'NOTA DE CRÉDITO ELECTRÓNICA',
            '56' => 'NOTA DE DÉBITO ELECTRÓNICA',
        ];
        $titulo_doc = $titulos[$tipoDTE] ?? 'DOCUMENTO ELECTRÓNICO';

        $condMap = ['1' => 'contado', '2' => 'crédito', '3' => 'sin costo'];
        $cond_venta = $condMap[$fmaPago] ?? 'no informado';

        // Formato fecha “Lunes 31 de julio del 2023”
        $fecha_larga = $fecha
            ? Carbon::parse($fecha)->locale('es')->translatedFormat('l d \d\e F \d\e\l Y')
            : null;
        if (!empty($fecha_larga)) {
            $fecha_larga = mb_strtoupper(mb_substr($fecha_larga, 0, 1), 'UTF-8') . mb_substr($fecha_larga, 1);
        }

        // Emisor
        $rut_emisor    = $x('sii:Encabezado/sii:Emisor/sii:RUTEmisor');
        $emisor        = $x('sii:Encabezado/sii:Emisor/sii:RznSoc');
        $giro_emisor   = $x('sii:Encabezado/sii:Emisor/sii:GiroEmis');
        $dir_emisor    = $x('sii:Encabezado/sii:Emisor/sii:DirOrigen');
        $comuna_emisor = $x('sii:Encabezado/sii:Emisor/sii:CmnaOrigen');
        $ciudad_emisor = $x('sii:Encabezado/sii:Emisor/sii:CiudadOrigen');
        $fono_emisor   = $x('sii:Encabezado/sii:Emisor/sii:Telefono');
        $mail_emisor   = $x('sii:Encabezado/sii:Emisor/sii:CorreoEmisor');

        // Receptor
        $rut_receptor    = $x('sii:Encabezado/sii:Receptor/sii:RUTRecep');
        $receptor        = $x('sii:Encabezado/sii:Receptor/sii:RznSocRecep');
        $giro_receptor   = $x('sii:Encabezado/sii:Receptor/sii:GiroRecep');
        $dir_receptor    = $x('sii:Encabezado/sii:Receptor/sii:DirRecep');
        $comuna_receptor = $x('sii:Encabezado/sii:Receptor/sii:CmnaRecep');
        $ciudad_receptor = $x('sii:Encabezado/sii:Receptor/sii:CiudadRecep');

        // Totales (en exenta suele venir MntExe y MntTotal)
        $neto     = $x('sii:Encabezado/sii:Totales/sii:MntNeto');
        $iva      = $x('sii:Encabezado/sii:Totales/sii:IVA');
        $tasa_iva = $x('sii:Encabezado/sii:Totales/sii:TasaIVA');
        $mnt_exe  = $x('sii:Encabezado/sii:Totales/sii:MntExe');
        $total    = $x('sii:Encabezado/sii:Totales/sii:MntTotal');

        //obtener el ted 

        // Obtener el TED (buscar en cualquier nivel dentro del Documento)
        $tedNode = $doc->xpath('.//sii:TED');
        $tedXml = null;

        if (!empty($tedNode) && isset($tedNode[0])) {
            $tedXml = $tedNode[0]->asXML();
        }

        // Generar PDF417
        $barcodeImage = null;
        if (!empty($tedXml)) {
            $dns2d = new DNS2D();
            $barcodeImage = $dns2d->getBarcodePNG($tedXml, 'PDF417');
        }


        // ---- Detalle de productos/servicios ----
        $detalles = [];
        $detNodes = $doc->xpath('sii:Detalle');
        if ($detNodes) {
            foreach ($detNodes as $d) {
                $d->registerXPathNamespace('sii', 'http://www.sii.cl/SiiDte');

                $get = fn($p) => ( ($n=$d->xpath($p)) && isset($n[0]) ) ? trim((string)$n[0]) : null;

                $detalles[] = [
                    'linea'   => (int)($get('sii:NroLinDet') ?? 0),
                    'nombre'  => $get('sii:NmbItem'),
                    'desc'    => $get('sii:DscItem'),
                    'cantidad'=> (float)($get('sii:QtyItem') ?? 0),
                    'unidad'  => $get('sii:UnmdItem') ?? 'UN',
                    'precio'  => (float)($get('sii:PrcItem') ?? 0),
                    'monto'   => (float)($get('sii:MontoItem') ?? 0),
                ];
            }
        }

        // Ciudad SII (en tu ejemplo aparece “S.I.I. - VINA DEL MAR”)
        $sii_ciudad = $ciudad_emisor;

                $barcode = null;

        if($tedXml){
            $barcode = new DNS2D();
            $barcodeImage = $barcode -> getBarcodePNG($tedXml, 'PDF417');
        }

        $datos = compact(
            'tipoDTE','titulo_doc','folio','fecha','fecha_larga','cond_venta',
            'rut_emisor','emisor','giro_emisor','dir_emisor','comuna_emisor','ciudad_emisor','fono_emisor','mail_emisor',
            'rut_receptor','receptor','giro_receptor','dir_receptor','comuna_receptor','ciudad_receptor',
            'neto','iva','tasa_iva','mnt_exe', 'tedXml','barcodeImage', 'total',
            'detalles','sii_ciudad'
        );



        // Generar nombre de archivo más descriptivo
        $emisor_limpio = preg_replace('/[^A-Za-z0-9]/', '_', substr($emisor, 0, 30));
        $nombre_archivo = "Factura_{$emisor_limpio}_Folio_{$folio}.pdf";

        $pdf = PDF::loadView('dte.pdf', $datos);
        return $pdf->download($nombre_archivo);
    }

}

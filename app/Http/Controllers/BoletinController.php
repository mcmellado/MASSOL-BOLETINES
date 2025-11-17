<?php

namespace App\Http\Controllers;

use App\Models\Boletin;
use App\Models\BoletinPlaca;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use setasign\Fpdi\Fpdi;



class BoletinController extends Controller
{
    public function index()
    {
        $boletines = Boletin::with('cliente')
            ->orderBy('fecha', 'desc')
            ->paginate(10);

        return view('boletines.index', compact('boletines'));
    }

    public function create(Request $request)
    {
        $clientes = Cliente::orderBy('nombre')->get();

        $clienteId = $request->query('cliente_id');
        $clienteSeleccionado = null;

        if ($clienteId) {
            $clienteSeleccionado = Cliente::find($clienteId);
        }

        $marcasInversor = [
            'Huawei',
            'Fronius',
            'Solax',
            'Victron',
            'SMA',
            'Kostal',
            'FOX',
        ];

        $tiposInstalacionElectrica = ['monofasica', 'trifasica'];
        $tensionesSuministro       = ['230V', '400V'];
        $tiposInstalacion          = ['nueva', 'ampliacion'];

        $tiposCubierta = [
            'instalación coplanar',
            'instalación aporticada',
            'instalación en estructura tipo pérgola',
        ];

        $modelosPlaca = [
            'YINGLI 330',
            'ELEK 270',
            'PEIMAN 420W',
            'MUNCHEN/ AS-6P-320W',
            'LONGI 445W',
            'LONGI 550W',
            'LONGI 555W',
            'LONGI 540W',
            'LONGI 545W',
            'LONGI 560W',
            'LONGI 570W',
            'LONGI 640W',
            'RISEN 270W',
            'RISEN 435W',
            'RISEN 400W',
            'RISEN 405W',
            'RISEN 410W',
            'RISEN 450W',
            'RISEN 545W',
            'RISEN 450W',
        ];

        return view('boletines.create', compact(
            'clientes',
            'clienteSeleccionado',
            'marcasInversor',
            'tiposInstalacionElectrica',
            'tensionesSuministro',
            'tiposInstalacion',
            'tiposCubierta',
            'modelosPlaca'
        ));
    }

    public function store(Request $request)
{
    $marcasInversor = [
        'Huawei',
        'Fronius',
        'Solax',
        'Victron',
        'SMA',
        'Kostal',
        'FOX',
    ];

    $tiposInstalacionElectrica = ['monofasica', 'trifasica'];
    $tensionesSuministro       = ['230V', '400V'];
    $tiposInstalacion          = ['nueva', 'ampliacion'];

    $tiposCubierta = [
        'instalación coplanar',
        'instalación aporticada',
        'instalación en estructura tipo pérgola',
    ];

    // Modelos de placa que salen en el <select>
    $modelosPlaca = [
        'YINGLI 330',
        'ELEK 270',
        'PEIMAN 420W',
        'MUNCHEN/ AS-6P-320W',
        'LONGI 445W',
        'LONGI 550W',
        'LONGI 555W',
        'LONGI 540W',
        'LONGI 545W',
        'LONGI 560W',
        'LONGI 570W',
        'LONGI 640W',
        'RISEN 270W',
        'RISEN 435W',
        'RISEN 400W',
        'RISEN 405W',
        'RISEN 410W',
        'RISEN 450W',
        'RISEN 545W',
        'RISEN 450W',
    ];

    $validated = $request->validate([
        'cliente_id'                => 'required|exists:clientes,id',
        'fecha'                     => 'required|date',
        'numero_registro'           => 'nullable|string|max:255',
        'cups'                      => 'nullable|string|max:255',
        'referencia_catastral'      => 'nullable|string|max:255',
        'potencia_factura_luz'      => 'nullable|string|max:255',
        'metros_cuadrados_vivienda' => 'nullable|string|max:255',
        // potencia_pico NO viene del form, la calculamos nosotros
        // 'potencia_pico'          => 'nullable|string|max:255',

        'marca_inversor'            => 'required|string|in:' . implode(',', $marcasInversor),
        'modelo_inversor'           => 'nullable|string|max:255',
        'potencia_inversores'       => 'nullable|string|max:255',

        'tipo_instalacion_electrica'=> 'required|string|in:' . implode(',', $tiposInstalacionElectrica),
        'tension_suministro'        => 'required|string|in:' . implode(',', $tensionesSuministro),
        'tipo_instalacion'          => 'required|string|in:' . implode(',', $tiposInstalacion),

        'tipos_cubierta'            => 'nullable|array',
        'tipos_cubierta.*'          => 'string|in:' . implode(',', $tiposCubierta),

        'tiene_bateria'             => 'nullable|boolean',
        'potencia_bateria'          => 'nullable|string|max:255',
        'numero_baterias'           => 'nullable|integer|min:0',

        // 👇 modelo_placa es el SELECT (LONGI 640W, etc.)
        'modelo_placa'              => 'required|array|min:1',
        'modelo_placa.*'            => 'required|string|in:' . implode(',', $modelosPlaca),

        // 👇 ya NO hay potencia_placa en el formulario
        // 'potencia_placa'         => 'required|array|min:1',
        // 'potencia_placa.*'       => 'required|string|max:255',

        'cantidad_placas'           => 'required|array|min:1',
        'cantidad_placas.*'         => 'required|integer|min:1',
    ]);

    // Normalizamos algunos campos
    $validated['tiene_bateria']  = $request->boolean('tiene_bateria');
    $validated['tipos_cubierta'] = $request->input('tipos_cubierta', []);

    /*
     * CALCULAR POTENCIA PICO:
     * potencia_pico = SUM( watts(modelo_placa) * cantidad_placas )
     * Ej: "LONGI 640W" → 640
     */
    $modelos   = $validated['modelo_placa'];
    $cantidades = $validated['cantidad_placas'];

    $potenciaPicoTotal = 0;

    foreach ($modelos as $i => $modelo) {
        // Buscamos el primer número en el texto del modelo
        $watts = 0;
        if (preg_match('/(\d+(\.\d+)?)/', $modelo, $m)) {
            $watts = (float) $m[1];   // ejemplo: 640.0
        }

        $cantidad = (int) ($cantidades[$i] ?? 0);

        if ($watts > 0 && $cantidad > 0) {
            $potenciaPicoTotal += $watts * $cantidad;
        }
    }

    // Forzamos que potencia_pico SIEMPRE sea este valor calculado
    $validated['potencia_pico'] = $potenciaPicoTotal;

    // Crear boletín
    $boletin = Boletin::create([
        'cliente_id'                => $validated['cliente_id'],
        'fecha'                     => $validated['fecha'],
        'numero_registro'           => $validated['numero_registro'] ?? null,
        'cups'                      => $validated['cups'] ?? null,
        'referencia_catastral'      => $validated['referencia_catastral'] ?? null,
        'potencia_factura_luz'      => $validated['potencia_factura_luz'] ?? null,
        'metros_cuadrados_vivienda' => $validated['metros_cuadrados_vivienda'] ?? null,
        'potencia_pico'             => $validated['potencia_pico'],

        'marca_inversor'            => $validated['marca_inversor'],
        'modelo_inversor'           => $validated['modelo_inversor'] ?? null,
        'potencia_inversores'       => $validated['potencia_inversores'] ?? null,

        'tipo_instalacion_electrica'=> $validated['tipo_instalacion_electrica'],
        'tension_suministro'        => $validated['tension_suministro'],
        'tipo_instalacion'          => $validated['tipo_instalacion'],

        'tipos_cubierta'            => $validated['tipos_cubierta'] ?? [],

        'tiene_bateria'             => $validated['tiene_bateria'],
        'potencia_bateria'          => $validated['potencia_bateria'] ?? null,
        'numero_baterias'           => $validated['numero_baterias'] ?? null,
    ]);

    // Guardar placas
    $modelosPlacaForm = $validated['modelo_placa'];
    $cantidadesForm   = $validated['cantidad_placas'];

    foreach ($modelosPlacaForm as $index => $modelo) {
        // Extraemos otra vez los W para guardarlos en potencia_placa
        $watts = 0;
        if (preg_match('/(\d+(\.\d+)?)/', $modelo, $m)) {
            $watts = (float) $m[1];
        }

        BoletinPlaca::create([
            'boletin_id'      => $boletin->id,
            'modelo_placa'    => $modelo,                     // ej. "LONGI 640W"
            'potencia_placa'  => $watts,                      // ej. 640
            'cantidad_placas' => $cantidadesForm[$index] ?? 0,
        ]);
    }

    return redirect()
        ->route('clientes.show', $boletin->cliente_id)
        ->with('success', 'Boletín creado correctamente.');
}

    
    public function show(Boletin $boletin)
    {
        $boletin->load('cliente', 'placas');

        return view('boletines.show', compact('boletin'));
    }

    public function edit(Boletin $boletin)
    {
        $clientes = Cliente::orderBy('nombre')->get();

        $marcasInversor = [
            'Huawei',
            'Fronius',
            'Solax',
            'Victron',
            'SMA',
            'Kostal',
            'FOX',
        ];

        $tiposInstalacionElectrica = ['monofasica', 'trifasica'];
        $tensionesSuministro       = ['230V', '400V'];
        $tiposInstalacion          = ['nueva', 'ampliacion'];

        $tiposCubierta = [
            'instalación coplanar',
            'instalación aporticada',
            'instalación en estructura tipo pérgola',
        ];

        $modelosPlaca = [
            'YINGLI 330',
            'ELEK 270',
            'PEIMAN 420W',
            'MUNCHEN/ AS-6P-320W',
            'LONGI 445W',
            'LONGI 550W',
            'LONGI 555W',
            'LONGI 540W',
            'LONGI 545W',
            'LONGI 560W',
            'LONGI 570W',
            'LONGI 640W',
            'RISEN 270W',
            'RISEN 435W',
            'RISEN 400W',
            'RISEN 405W',
            'RISEN 410W',
            'RISEN 450W',
            'RISEN 545W',
            'RISEN 450W',
        ];

        $boletin->load('placas');

        return view('boletines.edit', compact(
            'boletin',
            'clientes',
            'marcasInversor',
            'tiposInstalacionElectrica',
            'tensionesSuministro',
            'tiposInstalacion',
            'tiposCubierta',
            'modelosPlaca'
        ));
    }

   public function update(Request $request, Boletin $boletin)
{
    $marcasInversor = [
        'Huawei',
        'Fronius',
        'Solax',
        'Victron',
        'SMA',
        'Kostal',
        'FOX',
    ];

    $tiposInstalacionElectrica = ['monofasica', 'trifasica'];
    $tensionesSuministro       = ['230V', '400V'];
    $tiposInstalacion          = ['nueva', 'ampliacion'];

    $tiposCubierta = [
        'instalación coplanar',
        'instalación aporticada',
        'instalación en estructura tipo pérgola',
    ];

    // Modelos de placa que salen en el <select>
    $modelosPlaca = [
        'YINGLI 330',
        'ELEK 270',
        'PEIMAN 420W',
        'MUNCHEN/ AS-6P-320W',
        'LONGI 445W',
        'LONGI 550W',
        'LONGI 555W',
        'LONGI 540W',
        'LONGI 545W',
        'LONGI 560W',
        'LONGI 570W',
        'LONGI 640W',
        'RISEN 270W',
        'RISEN 435W',
        'RISEN 400W',
        'RISEN 405W',
        'RISEN 410W',
        'RISEN 450W',
        'RISEN 545W',
        'RISEN 450W',
    ];

    $validated = $request->validate([
        'cliente_id'                => 'required|exists:clientes,id',
        'fecha'                     => 'required|date',
        'numero_registro'           => 'nullable|string|max:255',
        'cups'                      => 'nullable|string|max:255',
        'referencia_catastral'      => 'nullable|string|max:255',
        'potencia_factura_luz'      => 'nullable|string|max:255',
        'metros_cuadrados_vivienda' => 'nullable|string|max:255',
        // potencia_pico se recalcula, no viene del form
        // 'potencia_pico'          => 'nullable|string|max:255',

        'marca_inversor'            => 'required|string|in:' . implode(',', $marcasInversor),
        'modelo_inversor'           => 'nullable|string|max:255',
        'potencia_inversores'       => 'nullable|string|max:255',

        'tipo_instalacion_electrica'=> 'required|string|in:' . implode(',', $tiposInstalacionElectrica),
        'tension_suministro'        => 'required|string|in:' . implode(',', $tensionesSuministro),
        'tipo_instalacion'          => 'required|string|in:' . implode(',', $tiposInstalacion),

        'tipos_cubierta'            => 'nullable|array',
        'tipos_cubierta.*'          => 'string|in:' . implode(',', $tiposCubierta),

        'tiene_bateria'             => 'nullable|boolean',
        'potencia_bateria'          => 'nullable|string|max:255',
        'numero_baterias'           => 'nullable|integer|min:0',

        // modelos de placa desde <select>
        'modelo_placa'              => 'required|array|min:1',
        'modelo_placa.*'            => 'required|string|in:' . implode(',', $modelosPlaca),

        // ya no usamos potencia_placa desde el form
        // 'potencia_placa'         => 'required|array|min:1',
        // 'potencia_placa.*'       => 'required|string|max:255',

        'cantidad_placas'           => 'required|array|min:1',
        'cantidad_placas.*'         => 'required|integer|min:1',
    ]);

    $validated['tiene_bateria']  = $request->boolean('tiene_bateria');
    $validated['tipos_cubierta'] = $request->input('tipos_cubierta', []);

    /*
     * CALCULAR POTENCIA PICO:
     * potencia_pico = SUM( watts(modelo_placa) * cantidad_placas )
     * Ej: "LONGI 640W" → 640
     */
    $modelos   = $validated['modelo_placa'];
    $cantidades = $validated['cantidad_placas'];

    $potenciaPicoTotal = 0;

    foreach ($modelos as $i => $modelo) {
        $watts = 0;
        if (preg_match('/(\d+(\.\d+)?)/', $modelo, $m)) {
            $watts = (float) $m[1];
        }

        $cantidad = (int) ($cantidades[$i] ?? 0);

        if ($watts > 0 && $cantidad > 0) {
            $potenciaPicoTotal += $watts * $cantidad;
        }
    }

    $validated['potencia_pico'] = $potenciaPicoTotal;

    // Actualizar boletín
    $boletin->update([
        'cliente_id'                => $validated['cliente_id'],
        'fecha'                     => $validated['fecha'],
        'numero_registro'           => $validated['numero_registro'] ?? null,
        'cups'                      => $validated['cups'] ?? null,
        'referencia_catastral'      => $validated['referencia_catastral'] ?? null,
        'potencia_factura_luz'      => $validated['potencia_factura_luz'] ?? null,
        'metros_cuadrados_vivienda' => $validated['metros_cuadrados_vivienda'] ?? null,
        'potencia_pico'             => $validated['potencia_pico'],

        'marca_inversor'            => $validated['marca_inversor'],
        'modelo_inversor'           => $validated['modelo_inversor'] ?? null,
        'potencia_inversores'       => $validated['potencia_inversores'] ?? null,

        'tipo_instalacion_electrica'=> $validated['tipo_instalacion_electrica'],
        'tension_suministro'        => $validated['tension_suministro'],
        'tipo_instalacion'          => $validated['tipo_instalacion'],

        'tipos_cubierta'            => $validated['tipos_cubierta'] ?? [],

        'tiene_bateria'             => $validated['tiene_bateria'],
        'potencia_bateria'          => $validated['potencia_bateria'] ?? null,
        'numero_baterias'           => $validated['numero_baterias'] ?? null,
    ]);

    // Regenerar placas (borramos y volvemos a crear)
    $boletin->placas()->delete();

    $modelosPlacaForm = $validated['modelo_placa'];
    $cantidadesForm   = $validated['cantidad_placas'];

    foreach ($modelosPlacaForm as $index => $modelo) {
        // Extraer watts del modelo: "LONGI 640W" → 640
        $watts = 0;
        if (preg_match('/(\d+(\.\d+)?)/', $modelo, $m)) {
            $watts = (float) $m[1];
        }

        BoletinPlaca::create([
            'boletin_id'      => $boletin->id,
            'modelo_placa'    => $modelo,
            'potencia_placa'  => $watts,
            'cantidad_placas' => $cantidadesForm[$index] ?? 0,
        ]);
    }

    return redirect()
        ->route('clientes.show', $boletin->cliente_id)
        ->with('success', 'Boletín actualizado correctamente.');
}


    public function destroy(Boletin $boletin)
    {
        $clienteId = $boletin->cliente_id;

        $boletin->delete();

        return redirect()
            ->route('clientes.show', $clienteId)
            ->with('success', 'Boletín eliminado correctamente.');
    }


    private function calcularPotenciaPrevistaKw(Boletin $boletin): ?float
{
    $raw = trim((string) $boletin->potencia_inversores);

    if ($raw !== '') {
        if (preg_match('/(\d+(?:[.,]\d+)?)/', $raw, $m)) {
            $valor = (float) str_replace(',', '.', $m[1]);

            if ($valor > 1000) {
                return $valor / 1000.0;
            }

            return $valor;
        }
    }
    $modelo = trim((string) $boletin->modelo_inversor);

    if ($modelo !== '') {
        if (preg_match('/(\d+(?:[.,]\d+)?)/', $modelo, $m)) {
            $valor = (float) str_replace(',', '.', $m[1]);
            return $valor; 
        }
    }
    return null;
}


    public function pdfOficial(Boletin $boletin)
{
    $boletin->load('cliente', 'placas');
    $cliente = $boletin->cliente;

    // Nº de registro instalación (25 por defecto)
    $numeroRegistro = $boletin->numero_registro ?: '25';

    // Sección conductores (por si la usas luego)
    $seccionConductores = $boletin->tension_suministro === '400V'
        ? '4      4       4  '
        : '6      6       6  ';

    // Protección sobreintensidades (por si la usas luego)
    $proteccion = ($boletin->tipo_instalacion_electrica === 'trifasica')
        ? 'magnetotermico'
        : 'fusibles';

    // Cálculos de potencias para el boletín
    $potInstKw = $this->calcularPotenciaInstalacionKw($boletin);   // placas (kW)
    $potDiKw   = $this->calcularPotenciaDerivacionKw($boletin);    // inversor (kW)

    // Plantilla PDF
    $templatePath = storage_path('app/plantillas/BOLETIN.pdf');

    $pdf = new \setasign\Fpdi\Fpdi();
    $pageCount = $pdf->setSourceFile($templatePath);

    // Ajustes base
    $pdf->SetFont('Helvetica', '', 6);
    $pdf->SetTextColor(0, 0, 0);

    // Pequeño helper para tildes/ñ
    $enc = fn($txt) => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $txt);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $tplId = $pdf->importPage($pageNo);
        $size  = $pdf->getTemplateSize($tplId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId);

        if ($pageNo === 1) {

            /* ---------------------------------------------------------
             *  BLOQUE 1: CABECERA - Nº REGISTRO INSTALACIÓN
             * --------------------------------------------------------- */
            $pdf->SetXY(102, 44);
            $pdf->Write(4, $enc($numeroRegistro));

            /* ---------------------------------------------------------
             *  BLOQUE 2: TITULAR / CLIENTE
             * --------------------------------------------------------- */
            if ($cliente) {
                // Nombre completo
                $nombreCompleto = trim(
                    ($cliente->nombre ?? '') . ' ' .
                    ($cliente->primer_apellido ?? '') . ' ' .
                    ($cliente->segundo_apellido ?? '')
                );

                $pdf->SetXY(50, 69.2);
                $pdf->Write(1, $enc($nombreCompleto));

                // DNI/CIF
                $pdf->SetXY(130, 69.2);
                $pdf->Write(1, $enc($cliente->dni_cif ?? ''));

                // Dirección (línea titular)
                $pdf->SetXY(50, 72.2);
                $pdf->Write(4, $enc($cliente->direccion ?? ''));

                // LOCALIDAD
                $pdf->SetXY(50, 78.5);
                $pdf->Write(1, $enc($cliente->poblacion ?? ''));

                // PROVINCIA
                $pdf->SetXY(93, 78.5);
                $pdf->Write(1, $enc($cliente->provincia ?? ''));

                // CORREO
                $pdf->SetXY(110, 78);
                $pdf->Write(1, $enc($cliente->email ?? ''));

                // Teléfono
                $pdf->SetXY(149, 78);
                $pdf->Write(1, $enc($cliente->telefono ?? ''));

                // Código postal (zona titular)
                $pdf->SetXY(130, 73.8);
                $pdf->Write(1, $enc($cliente->codigo_postal ?? ''));

                // ------- DATOS DE LA INSTALACIÓN (bloque inferior) -------

                // Código postal 2 (bloque instalación)
                $pdf->SetXY(132, 90.5);
                $pdf->Write(1, $enc($cliente->codigo_postal ?? ''));

                // Dirección → Emplazamiento (calle) + Número
                $direccion = trim($cliente->direccion ?? '');
                $calle     = '';
                $numero    = '';

                $partes = array_map('trim', explode(',', $direccion));

                if (count($partes) >= 2) {
                    $calle  = $partes[0];
                    $numero = $partes[1];
                } else {
                    // Intento "Calle Jurel 4"
                    if (preg_match('/^(.*?)[\s]+(\d+.*)$/', $direccion, $m)) {
                        $calle  = trim($m[1]);
                        $numero = trim($m[2]);
                    } else {
                        $calle  = $direccion;
                        $numero = '';
                    }
                }

                
                // tension_suministro
                $pdf->SetXY(95, 132);
                $pdf->Write(1, ($boletin->tension_suministro ?? ''));

                 // seccion conductores
                $pdf->SetXY(147.9, 131.8);
                $pdf->Write(1, ($seccionConductores ?? ''));


                // Emplazamiento (solo calle)
                $pdf->SetXY(50, 86);
                $pdf->Write(1, $enc($calle));

                // Número
                $pdf->SetXY(108, 86);
                $pdf->Write(1, $enc($numero));

                // Población (bloque instalación)
                $pdf->SetXY(50, 90.5);
                $pdf->Write(1, $enc($cliente->poblacion ?? ''));

                // Provincia (bloque instalación)
                $pdf->SetXY(103, 90.5);
                $pdf->Write(1, $enc($cliente->provincia ?? ''));

                // Texto "c - generadores/convertidores"
                $tipo_instalacion_3 = 'c - generadores/convertidores';
                $pdf->SetXY(50, 95);
                $pdf->Write(1, $enc($tipo_instalacion_3));

                // Texto uso: "instalación fotovoltaica"
                $uso_destina = 'instalación fotovoltaica';
                $pdf->SetXY(102, 95);
                $pdf->Write(1, $enc($uso_destina));

                // Superficie (m² vivienda)
                $pdf->SetXY(150, 95);
                $pdf->Write(1, $enc($boletin->metros_cuadrados_vivienda ?? ''));
            }

            /* ---------------------------------------------------------
             *  BLOQUE 3: DATOS INSTALACIÓN
             * --------------------------------------------------------- */

            // CUPS
            $pdf->SetXY(110, 98.5);
            $pdf->Write(1, $enc($boletin->cups ?? ''));

            // Tipo instalación: nueva / ampliación
            if ($boletin->tipo_instalacion === 'nueva') {
                $pdf->SetXY(61.3, 96.6);
                $pdf->Write(4, 'X');
            } elseif ($boletin->tipo_instalacion === 'ampliacion') {
                // Ajusta coords si hace falta
                $pdf->SetXY(90, 96.6);
                $pdf->Write(4, 'X');
            }

            // Tipo instalación eléctrica (mono / tri)
            if ($boletin->tipo_instalacion_electrica === 'monofasica') {
                $pdf->SetXY(64.4, 127.5);
                $pdf->Write(4, 'X');
            } elseif ($boletin->tipo_instalacion_electrica === 'trifasica') {
                // ajusta posiciones de la casilla trifásica si la usas
                $pdf->SetXY(70, 127.5);
                $pdf->Write(4, 'X');
            }

            /* ---------------------------------------------------------
             *  INSTALACIÓN – POTENCIA PREVISTA (kW)
             *  (arriba, como en tu ejemplo: 2,72 kW, 5,12 kW, etc.)
             *  Regla: se toma desde potencia_pico (placas).
             * --------------------------------------------------------- */
            if (!is_null($potInstKw)) {
                $textoInst = number_format($potInstKw, 2, ',', '.') . ' kW';

                // AJUSTA ESTAS COORDENADAS a la casilla exacta
                $pdf->SetXY(90, 116);       // <- mueve X/Y si hace falta
                $pdf->Write(1, $enc($textoInst));
            }

            /* ---------------------------------------------------------
             *  DERIVACIÓN INDIVIDUAL – POTENCIA PREVISTA (kW)
             *  (debajo, como en tu ejemplo: 6 kW)
             *  Regla: se toma desde potencia_inversores / modelo inversor.
             * --------------------------------------------------------- */
            if (!is_null($potDiKw)) {
                $textoDi = number_format($potDiKw, 2, ',', '.') . ' kW';

                // AJUSTA ESTAS COORDENADAS a la casilla exacta
                $pdf->SetXY(90, 122);       // <- mueve X/Y si hace falta
                $pdf->Write(1, $enc($textoDi));
            }

        }
    }

    return $pdf->Output('I', 'BoletinOficial.pdf');
}

/**
 * Potencia prevista de la INSTALACIÓN (kW)
 * Según tus ejemplos: se usa la potencia pico de las placas.
 * Asumimos que potencia_pico está en Wp (ej: 2720, 3840, 5120).
 */
private function calcularPotenciaInstalacionKw(Boletin $boletin): ?float
{
    if (empty($boletin->potencia_pico)) {
        return null;
    }

    $picoWp = (float) str_replace(',', '.', (string) $boletin->potencia_pico);

    if ($picoWp <= 0) {
        return null;
    }

    // W → kW
    $kw = $picoWp / 1000;

    // Redondeo normal a 2 decimales (2,72 / 5,12 / 3,84...)
    return round($kw, 2);
}

/**
 * Potencia prevista de la DERIVACIÓN INDIVIDUAL (kW)
 * Según tus ejemplos: se usa la potencia del INVERSOR.
 */
private function calcularPotenciaDerivacionKw(Boletin $boletin): ?float
{
    // 1) Intentar con potencia_inversores ("6", "6,0", "6000"...)
    $raw = trim((string) $boletin->potencia_inversores);

    if ($raw !== '') {
        if (preg_match('/(\d+(?:[.,]\d+)?)/', $raw, $m)) {
            $valor = (float) str_replace(',', '.', $m[1]);

            // Si viene en W (ej: 6000), lo pasamos a kW
            if ($valor > 1000) {
                return round($valor / 1000, 2);
            }

            return round($valor, 2); // ya está en kW
        }
    }

    // 2) Si no hay nada claro, probar modelo inversor: "H1-6.0-E-G2"
    $modelo = trim((string) $boletin->modelo_inversor);

    if ($modelo !== '') {
        if (preg_match('/(\d+(?:[.,]\d+)?)/', $modelo, $m)) {
            $valor = (float) str_replace(',', '.', $m[1]);
            return round($valor, 2);
        }
    }

    return null;
}


}

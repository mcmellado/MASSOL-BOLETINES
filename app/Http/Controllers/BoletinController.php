<?php

namespace App\Http\Controllers;

use App\Models\Boletin;
use App\Models\BoletinPlaca;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use setasign\Fpdi\Fpdi;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\MarcaInversor;
use App\Models\ModeloPlaca;


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
    $clienteSeleccionado = $clienteId ? Cliente::find($clienteId) : null;

    // --- VARIABLE FECHA DE HOY (Formato base de datos para inputs date) ---
    $fechaHoy = date('Y-m-d');

    // 🔹 Marcas inversor desde catálogo
    $marcasInversor = MarcaInversor::orderBy('nombre')
        ->pluck('nombre')
        ->toArray();

    // 🔹 Modelos de placa desde catálogo
    $modelosPlaca = ModeloPlaca::orderBy('nombre')
        ->pluck('nombre')
        ->toArray();

    $tiposInstalacionElectrica = ['monofasica', 'trifasica'];
    $tensionesSuministro       = ['230V', '400V'];
    $tiposInstalacion          = ['nueva', 'ampliacion'];

    $tiposCubierta = [
        'instalación coplanar',
        'instalación aporticada',
        'instalación en estructura tipo pérgola',
    ];

    return view('boletines.create', compact(
        'clientes',
        'clienteSeleccionado',
        'marcasInversor',
        'tiposInstalacionElectrica',
        'tensionesSuministro',
        'tiposInstalacion',
        'tiposCubierta',
        'modelosPlaca',
        'fechaHoy'
    ));
}



public function store(Request $request)
{
    $tiposInstalacionElectrica = ['monofasica', 'trifasica'];
    $tensionesSuministro       = ['230V', '400V'];
    $tiposInstalacion          = ['nueva', 'ampliacion'];

    $tiposCubierta = [
        'instalación coplanar',
        'instalación aporticada',
        'instalación en estructura tipo pérgola',
    ];

    $validated = $request->validate([
        'cliente_id'                => 'required|exists:clientes,id',
        'fecha'                     => 'required|date',
        'numero_registro'           => 'nullable|string|max:255',
        'cups'                      => 'nullable|string|max:255',
        'referencia_catastral'      => 'nullable|string|max:255',
        'potencia_factura_luz'      => 'nullable|string|max:255',
        'metros_cuadrados_vivienda' => 'nullable|string|max:255',

        // marca inversor puede ser select normal o "__nuevo__"
        'marca_inversor'            => 'nullable|string|max:255',
        'marca_inversor_nuevo'      => 'nullable|string|max:255',

        'modelo_inversor'           => 'nullable|string|max:255',
        'potencia_inversores'       => 'nullable|string|max:255',
        'numero_inversores'         => 'nullable|integer|min:0',

        'tipo_instalacion_electrica'=> 'required|string|in:' . implode(',', $tiposInstalacionElectrica),
        'tension_suministro'        => 'required|string|in:' . implode(',', $tensionesSuministro),
        'tipo_instalacion'          => 'required|string|in:' . implode(',', $tiposInstalacion),

        'tipos_cubierta'            => 'nullable|array',
        'tipos_cubierta.*'          => 'string|in:' . implode(',', $tiposCubierta),

        'tiene_bateria'             => 'nullable|boolean',
        'potencia_bateria'          => 'nullable|string|max:255',
        'numero_baterias'           => 'nullable|integer|min:0',

        // placas dinámicas
        'modelo_placa'              => 'required|array|min:1',
        'modelo_placa.*'            => 'nullable|string|max:255',

        'modelo_placa_nuevo'        => 'array',
        'modelo_placa_nuevo.*'      => 'nullable|string|max:255',

        'cantidad_placas'           => 'required|array|min:1',
        'cantidad_placas.*'         => 'nullable|integer|min:1',

        'proteccion_sobretension' => [
            'nullable',
            'string', Rule::in(['interruptor_automatico', 'fusibles_calibrados']),
        ],
    ]);

    // Normalizamos
    $validated['tiene_bateria']  = $request->boolean('tiene_bateria');
    $validated['tipos_cubierta'] = $request->input('tipos_cubierta', []);

    /*
     * MARCA INVERSOR (select + nuevo)
     */
    $marcaSelect = $request->input('marca_inversor');
    $marcaNueva  = trim($request->input('marca_inversor_nuevo', ''));

    if (($marcaSelect === null || $marcaSelect === '') && $marcaNueva === '') {
        return back()
            ->withErrors(['marca_inversor' => 'La marca del inversor es obligatoria.'])
            ->withInput();
    }

    if ($marcaSelect === '__nuevo__') {
        $marcaFinal = $marcaNueva;
    } else {
        $marcaFinal = $marcaSelect;
    }

    // guarda en catálogo
    if (!empty($marcaFinal)) {
        MarcaInversor::firstOrCreate(['nombre' => $marcaFinal]);
    }

    /*
     * PLACAS (select + nuevo) + potencia_pico
     */
    $modelosInput     = $request->input('modelo_placa', []);
    $nuevosModelos    = $request->input('modelo_placa_nuevo', []);
    $cantidadesInput  = $request->input('cantidad_placas', []);

    $placasResueltas   = [];
    $potenciaPicoTotal = 0;

    foreach ($modelosInput as $i => $modeloSeleccionado) {
        $cantidad = (int) ($cantidadesInput[$i] ?? 0);

        if ($modeloSeleccionado === '__nuevo__') {
            $modeloFinal = trim($nuevosModelos[$i] ?? '');
        } else {
            $modeloFinal = trim($modeloSeleccionado ?? '');
        }

        if ($modeloFinal === '' || $cantidad <= 0) {
            continue;
        }

        $watts = $this->obtenerPotenciaWattsDesdeModelo($modeloFinal);
        $potenciaPicoTotal += $watts * $cantidad;

        // guarda/actualiza en catálogo
        ModeloPlaca::updateOrCreate(
            ['nombre' => $modeloFinal],
            ['potencia_w' => $watts > 0 ? $watts : null]
        );

        $placasResueltas[] = [
            'modelo_placa'    => $modeloFinal,
            'potencia_placa'  => $watts,
            'cantidad_placas' => $cantidad,
        ];
    }

    if (empty($placasResueltas)) {
        return back()
            ->withErrors(['modelo_placa' => 'Debes añadir al menos una placa válida.'])
            ->withInput();
    }

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

        'marca_inversor'            => $marcaFinal,
        'modelo_inversor'           => $validated['modelo_inversor'] ?? null,
        'potencia_inversores'       => $validated['potencia_inversores'] ?? null,
        'numero_inversores'         => $validated['numero_inversores'] ?? null,

        'tipo_instalacion_electrica'=> $validated['tipo_instalacion_electrica'],
        'tension_suministro'        => $validated['tension_suministro'],
        'tipo_instalacion'          => $validated['tipo_instalacion'],

        'tipos_cubierta'            => $validated['tipos_cubierta'] ?? [],

        'tiene_bateria'             => $validated['tiene_bateria'],
        'potencia_bateria'          => $validated['potencia_bateria'] ?? null,
        'numero_baterias'           => $validated['numero_baterias'] ?? null,
        'proteccion_sobretension'   => $validated['proteccion_sobretension'] ?? null,
    ]);

    // Guardar placas
    foreach ($placasResueltas as $placa) {
        BoletinPlaca::create([
            'boletin_id'      => $boletin->id,
            'modelo_placa'    => $placa['modelo_placa'],
            'potencia_placa'  => $placa['potencia_placa'],
            'cantidad_placas' => $placa['cantidad_placas'],
        ]);
    }

    return redirect()
        ->route('clientes.show', $boletin->cliente_id)
        ->with('success', 'Boletín creado correctamente.');
}



    public function show(Boletin $boletin)
{
    $boletin->load('cliente', 'placas');

    $potenciaDerivacionKw = $this->calcularPotenciaDerivacionKw($boletin);

    return view('boletines.show', compact('boletin', 'potenciaDerivacionKw'));
}

   public function edit(Boletin $boletin)
{
    $clientes = Cliente::orderBy('nombre')->get();

    $marcasInversor = MarcaInversor::orderBy('nombre')
        ->pluck('nombre')
        ->toArray();

    $modelosPlaca = ModeloPlaca::orderBy('nombre')
        ->pluck('nombre')
        ->toArray();

    $tiposInstalacionElectrica = ['monofasica', 'trifasica'];
    $tensionesSuministro       = ['230V', '400V'];
    $tiposInstalacion          = ['nueva', 'ampliacion'];

    $tiposCubierta = [
        'instalación coplanar',
        'instalación aporticada',
        'instalación en estructura tipo pérgola',
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
    $tiposInstalacionElectrica = ['monofasica', 'trifasica'];
    $tensionesSuministro       = ['230V', '400V'];
    $tiposInstalacion          = ['nueva', 'ampliacion'];

    $tiposCubierta = [
        'instalación coplanar',
        'instalación aporticada',
        'instalación en estructura tipo pérgola',
    ];

    $validated = $request->validate([
        'cliente_id'                => 'required|exists:clientes,id',
        'fecha'                     => 'required|date',
        'numero_registro'           => 'nullable|string|max:255',
        'cups'                      => 'nullable|string|max:255',
        'referencia_catastral'      => 'nullable|string|max:255',
        'potencia_factura_luz'      => 'nullable|string|max:255',
        'metros_cuadrados_vivienda' => 'nullable|string|max:255',

        'marca_inversor'            => 'nullable|string|max:255',
        'marca_inversor_nuevo'      => 'nullable|string|max:255',

        'modelo_inversor'           => 'nullable|string|max:255',
        'potencia_inversores'       => 'nullable|string|max:255',
        'numero_inversores'         => 'nullable|integer|min:0',

        'tipo_instalacion_electrica'=> 'required|string|in:' . implode(',', $tiposInstalacionElectrica),
        'tension_suministro'        => 'required|string|in:' . implode(',', $tensionesSuministro),
        'tipo_instalacion'          => 'required|string|in:' . implode(',', $tiposInstalacion),

        'tipos_cubierta'            => 'nullable|array',
        'tipos_cubierta.*'          => 'string|in:' . implode(',', $tiposCubierta),

        'tiene_bateria'             => 'nullable|boolean',
        'potencia_bateria'          => 'nullable|string|max:255',
        'numero_baterias'           => 'nullable|integer|min:0',

        'modelo_placa'              => 'required|array|min:1',
        'modelo_placa.*'            => 'nullable|string|max:255',

        'modelo_placa_nuevo'        => 'array',
        'modelo_placa_nuevo.*'      => 'nullable|string|max:255',

        'cantidad_placas'           => 'required|array|min:1',
        'cantidad_placas.*'         => 'nullable|integer|min:1',

        'proteccion_sobretension'   => [
            'nullable',
            'string',
            Rule::in(['interruptor_automatico', 'fusibles_calibrados']),
        ],
    ]);

    $validated['tiene_bateria']  = $request->boolean('tiene_bateria');
    $validated['tipos_cubierta'] = $request->input('tipos_cubierta', []);

    /*
     * MARCA INVERSOR
     */
    $marcaSelect = $request->input('marca_inversor');
    $marcaNueva  = trim($request->input('marca_inversor_nuevo', ''));

    if (($marcaSelect === null || $marcaSelect === '') && $marcaNueva === '') {
        return back()
            ->withErrors(['marca_inversor' => 'La marca del inversor es obligatoria.'])
            ->withInput();
    }

    if ($marcaSelect === '__nuevo__') {
        $marcaFinal = $marcaNueva;
    } else {
        $marcaFinal = $marcaSelect;
    }

    if (!empty($marcaFinal)) {
        MarcaInversor::firstOrCreate(['nombre' => $marcaFinal]);
    }

    /*
     * PLACAS + potencia_pico
     */
    $modelosInput     = $request->input('modelo_placa', []);
    $nuevosModelos    = $request->input('modelo_placa_nuevo', []);
    $cantidadesInput  = $request->input('cantidad_placas', []);

    $placasResueltas   = [];
    $potenciaPicoTotal = 0;

    foreach ($modelosInput as $i => $modeloSeleccionado) {
        $cantidad = (int) ($cantidadesInput[$i] ?? 0);

        if ($modeloSeleccionado === '__nuevo__') {
            $modeloFinal = trim($nuevosModelos[$i] ?? '');
        } else {
            $modeloFinal = trim($modeloSeleccionado ?? '');
        }

        if ($modeloFinal === '' || $cantidad <= 0) {
            continue;
        }

        $watts = $this->obtenerPotenciaWattsDesdeModelo($modeloFinal);
        $potenciaPicoTotal += $watts * $cantidad;

        ModeloPlaca::updateOrCreate(
            ['nombre' => $modeloFinal],
            ['potencia_w' => $watts > 0 ? $watts : null]
        );

        $placasResueltas[] = [
            'modelo_placa'    => $modeloFinal,
            'potencia_placa'  => $watts,
            'cantidad_placas' => $cantidad,
        ];
    }

    if (empty($placasResueltas)) {
        return back()
            ->withErrors(['modelo_placa' => 'Debes añadir al menos una placa válida.'])
            ->withInput();
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

        'marca_inversor'            => $marcaFinal,
        'modelo_inversor'           => $validated['modelo_inversor'] ?? null,
        'potencia_inversores'       => $validated['potencia_inversores'] ?? null,
        'numero_inversores'         => $validated['numero_inversores'] ?? null,

        'tipo_instalacion_electrica'=> $validated['tipo_instalacion_electrica'],
        'tension_suministro'        => $validated['tension_suministro'],
        'tipo_instalacion'          => $validated['tipo_instalacion'],

        'tipos_cubierta'            => $validated['tipos_cubierta'] ?? [],

        'tiene_bateria'             => $validated['tiene_bateria'],
        'potencia_bateria'          => $validated['potencia_bateria'] ?? null,
        'numero_baterias'           => $validated['numero_baterias'] ?? null,
        'proteccion_sobretension'   => $validated['proteccion_sobretension'] ?? null,
    ]);

    // Regenerar placas
    $boletin->placas()->delete();

    foreach ($placasResueltas as $placa) {
        BoletinPlaca::create([
            'boletin_id'      => $boletin->id,
            'modelo_placa'    => $placa['modelo_placa'],
            'potencia_placa'  => $placa['potencia_placa'],
            'cantidad_placas' => $placa['cantidad_placas'],
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

        // --- VARIABLE FECHA DE HOY (Formato PDF) ---
        $fechaHoy = date('d/m/Y');
        // Si necesitas el formato con texto: date('d \d\e F \d\e Y')
        // Para mes en español necesitarías usar Carbon + locale.

        // Nº de registro instalación (25 por defecto)
        $numeroRegistro = $boletin->numero_registro ?: '25';

        // Sección conductores (por si la usas luego)
        $seccionConductores = $boletin->tension_suministro === '400V'
            ? '4      4       4  '
            : '6      6       6  ';

        // Protección sobreintensidades
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
                 * BLOQUE: FECHA DE HOY (Variable añadida)
                 * --------------------------------------------------------- */
                $pdf->SetXY(20, 10); // <--- Pon aquí tus coordenadas X, Y
                $pdf->Write(1, $enc($fechaHoy));

                /* ---------------------------------------------------------
                 * BLOQUE 1: CABECERA - Nº REGISTRO INSTALACIÓN
                 * --------------------------------------------------------- */
                $pdf->SetXY(102, 44);
                $pdf->Write(4, $enc($numeroRegistro));

                /* ---------------------------------------------------------
                 * BLOQUE 2: TITULAR / CLIENTE
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
                    $pdf->Write(1, $enc($cliente->provincia ?? ''));

                    // PROVINCIA
                    $pdf->SetXY(93, 78.5);
                    $pdf->Write(1, $enc($cliente->poblacion ?? ''));

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
                    $pdf->Write(1, $enc($cliente->provincia ?? ''));

                    // Provincia (bloque instalación)
                    $pdf->SetXY(103, 90.5);
                    $pdf->Write(1, $enc($cliente->poblacion ?? ''));

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

                //año
                $anioBoletin = $boletin->fecha
                ? Carbon::parse($boletin->fecha)->format('y')
                : '';
                $pdf->SetXY(124, 45.2);
                $pdf->Write(1, $enc($anioBoletin));



                if ($boletin->proteccion_sobretension === 'interruptor_automatico') {
                    $pdf->SetXY(127.5, 141.2);
                    $pdf->Write(4, 'X');
                }

                if ($boletin->proteccion_sobretension === 'fusibles_calibrados') {
                    $pdf->SetXY(91.7, 141);
                    $pdf->Write(4, 'X');
                }

                /* ---------------------------------------------------------
                 * BLOQUE 3: DATOS INSTALACIÓN
                 * --------------------------------------------------------- */

                // CUPS
                $pdf->SetXY(110, 98.5);
                $pdf->Write(1, $enc($boletin->cups ?? ''));

                // Tipo instalación: nueva / ampliación
                if ($boletin->tipo_instalacion === 'nueva') {
                    $pdf->SetXY(61.3, 96.6);
                    $pdf->Write(4, 'X');
                } elseif ($boletin->tipo_instalacion === 'ampliacion') {
                    $pdf->SetXY(75.7, 96.6);
                    $pdf->Write(4, 'X');
                }

                // Tipo instalación eléctrica (mono / tri)
                if ($boletin->tipo_instalacion_electrica === 'monofasica') {
                    $pdf->SetXY(64.4, 127.5);
                    $pdf->Write(4, 'X');
                } elseif ($boletin->tipo_instalacion_electrica === 'trifasica') {
                    $pdf->SetXY(77.2, 127.5);
                    $pdf->Write(4, 'X');
                }



                /* ---------------------------------------------------------
                 * INSTALACIÓN – POTENCIA PREVISTA (kW) – desde potencia_pico
                 * --------------------------------------------------------- */
                if (!is_null($potInstKw)) {
                    $textoInst = number_format($potInstKw, 2, ',', '.') . ' kW';

                    // AJUSTA ESTAS COORDENADAS a la casilla exacta
                    $pdf->SetXY(90, 116);       // <- mueve X/Y si hace falta
                    $pdf->Write(1, $enc($textoInst));
                }

                /* ---------------------------------------------------------
                 * DERIVACIÓN INDIVIDUAL – POTENCIA PREVISTA (kW) – inversor
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
     * Se usa la potencia pico de las placas (potencia_pico en Wp).
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
     * Se usa la potencia del INVERSOR.
     */
    private function calcularPotenciaDerivacionKw(Boletin $boletin): ?float
    {
        // Nº de inversores (mínimo 1 por seguridad)
        $numeroInversores = (int) ($boletin->numero_inversores ?? 1);
        if ($numeroInversores < 1) {
            $numeroInversores = 1;
        }

        // 1) Intentar con potencia_inversores ("6", "6,0", "6000"...)
        $raw = trim((string) $boletin->potencia_inversores);

        if ($raw !== '') {
            if (preg_match('/(\d+(?:[.,]\d+)?)/', $raw, $m)) {
                $valor = (float) str_replace(',', '.', $m[1]);

                // Si viene en W (ej: 6000), lo pasamos a kW
                if ($valor > 1000) {
                    $valorKw = $valor / 1000;
                } else {
                    $valorKw = $valor; // ya está en kW
                }

                // Multiplicamos por el número de inversores
                return round($valorKw * $numeroInversores, 2);
            }
        }

        // 2) Si no hay nada claro, probar modelo inversor: "H1-6.0-E-G2"
        $modelo = trim((string) $boletin->modelo_inversor);

        if ($modelo !== '') {
            if (preg_match('/(\d+(?:[.,]\d+)?)/', $modelo, $m)) {
                $valor = (float) str_replace(',', '.', $m[1]);

                // Aquí asumimos que el número ya viene en kW
                return round($valor * $numeroInversores, 2);
            }
        }

        return null;
    }

/**
 * Devuelve la potencia en W de un modelo de placa (ej: "LONGI 640W" -> 640).
 * Ahora primero mira en la tabla modelo_placas; si no la tiene, intenta
 * deducirla del texto y la guarda en BD para futuras veces.
 */
private function obtenerPotenciaWattsDesdeModelo(string $modelo): float
{
    $modelo = trim($modelo);

    if ($modelo === '') {
        return 0.0;
    }

    // 1) Intentar leer de la tabla modelo_placas
    $registro = ModeloPlaca::where('nombre', $modelo)->first();

    if ($registro && !is_null($registro->potencia_w)) {
        return (float) $registro->potencia_w;
    }

    // 2) (Opcional) catálogo legacy por si tienes nombres antiguos raros exactos
    $catalogoPotencias = [
        'YINGLI 330'              => 330,
        'ELEK 270'                => 270,
        'PEIMAN 420W'             => 420,
        'MUNCHEN/ AS-6P-320W'     => 320,
        'LONGI 445W'              => 445,
        'LONGI 550W'              => 550,
        'LONGI 555W'              => 555,
        'LONGI 540W'              => 540,
        'LONGI 545W'              => 545,
        'LONGI 560W'              => 560,
        'LONGI 570W'              => 570,
        'LONGI 640W'              => 640,
        'RISEN 270W'              => 270,
        'RISEN 435W'              => 435,
        'RISEN 400W'              => 400,
        'RISEN 405W'              => 405,
        'RISEN 410W'              => 410,
        'RISEN 450W'              => 450,
        'RISEN 545W'              => 545,
    ];

    if (isset($catalogoPotencias[$modelo])) {
        $w = (float) $catalogoPotencias[$modelo];

        // Si no estaba en BD, lo guardamos ahora
        ModeloPlaca::updateOrCreate(
            ['nombre' => $modelo],
            ['potencia_w' => (int) $w]
        );

        return $w;
    }

    // 3) Si no está ni en BD ni en el array, intento genérico:
    // cojo el ÚLTIMO número del modelo (suele ser la potencia: 320 en "AS-6P-320W")
    if (preg_match_all('/(\d+(?:[.,]\d+)?)/', $modelo, $matches)) {
        $ultimoNumero = end($matches[1]);
        $valor = (float) str_replace(',', '.', $ultimoNumero);

        if ($valor > 0) {
            // Lo guardamos/actualizamos en la tabla para futuras veces
            ModeloPlaca::updateOrCreate(
                ['nombre' => $modelo],
                ['potencia_w' => (int) $valor]
            );

            return $valor;
        }
    }

    return 0.0;
}



    public function pdfMemoriaTecnica(Boletin $boletin)
{
    $boletin->load('cliente', 'placas');
    $cliente = $boletin->cliente;

    // Cálculos que ya tienes
    $potInstKw = $this->calcularPotenciaInstalacionKw($boletin);   // placas (kW)
    $potDiKw   = $this->calcularPotenciaDerivacionKw($boletin);    // inversor (kW)

    // Ruta a la plantilla de memoria técnica
    $templatePath = storage_path('app/plantillas/MemoriaTecnica.pdf');

    $pdf = new \setasign\Fpdi\Fpdi();
    $pageCount = $pdf->setSourceFile($templatePath);

    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);

    // Helper para acentos/ñ
    $enc = fn($txt) => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $txt);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $tplId = $pdf->importPage($pageNo);
        $size  = $pdf->getTemplateSize($tplId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId);

        /* =========================
         *   PÁGINA 1
         *   Bloques A / B / C
         * ========================= */
        if ($pageNo === 1 && $cliente) {

            // --- A) Titular ---
            // Apellidos y nombre o razón social
            $nombreCompleto = trim(
                ($cliente->nombre ?? '') . ' ' .
                ($cliente->primer_apellido ?? '') . ' ' .
                ($cliente->segundo_apellido ?? '')
            );
            $pdf->SetXY(29, 57);       // ajusta X/Y según veas en tu PDF
            $pdf->Write(3, $enc($nombreCompleto));

             // DNI/CIF
             $pdf->SetXY(132, 57);
             $pdf->Write(3, $enc($cliente->dni_cif ?? ''));

            // Domicilio (calle, avenida, plaza…)
             $pdf->SetXY(28, 66.5);
             $pdf->Write(3, $enc($cliente->direccion ?? ''));

            //  CP
            $pdf->SetXY(30, 75);
            $pdf->Write(3, $enc($cliente->codigo_postal ?? ''));

            // Localidad
            $pdf->SetXY(75, 75);
            $pdf->Write(3, $enc($cliente->poblacion ?? ''));

            // Provincia
            $pdf->SetXY(46.8, 75);
            $pdf->Write(3, $enc($cliente->provincia ?? ''));

            // // Teléfono
            // $pdf->SetXY(140, 55);
            // $pdf->Write(3, $enc($cliente->telefono ?? ''));

            // // Correo electrónico
            // $pdf->SetXY(140, 60);
            // $pdf->Write(3, $enc($cliente->email ?? ''));


            // --- B) Emplazamiento de la instalación ---
            // Dirección (puedes reaprovechar la misma)
        //     $pdf->SetXY(25, 75);
        //     $pdf->Write(3, $enc($cliente->direccion ?? ''));

        //     // C.P.
        //     $pdf->SetXY(25, 80);
        //     $pdf->Write(3, $enc($cliente->codigo_postal ?? ''));

        //     // Localidad
        //     $pdf->SetXY(50, 80);
        //     $pdf->Write(3, $enc($cliente->poblacion ?? ''));

        //     // Provincia
        //     $pdf->SetXY(95, 80);
        //     $pdf->Write(3, $enc($cliente->provincia ?? ''));

        //     // Referencia catastral (usa la del boletín)
        //     $pdf->SetXY(135, 80);
        //     $pdf->Write(3, $enc($boletin->referencia_catastral ?? ''));

        //     // Superficie útil (m² vivienda)
        //     $pdf->SetXY(25, 88);
        //     $pdf->Write(3, $enc($boletin->metros_cuadrados_vivienda ?? ''));

        //     // Tipo de instalación (nueva / ampliación)
        //     if ($boletin->tipo_instalacion === 'nueva') {
        //         // marca la casilla de "Nueva"
        //         $pdf->SetXY(55, 88);
        //         $pdf->Write(3, 'X');
        //     } elseif ($boletin->tipo_instalacion === 'ampliacion') {
        //         // casilla "Ampliación"
        //         $pdf->SetXY(70, 88);
        //         $pdf->Write(3, 'X');
        //     }

        //     // Uso a que se destina: texto genérico
        //     $pdf->SetXY(110, 88);
        //     $pdf->Write(3, $enc('Generación fotovoltaica para autoconsumo'));
        // }

        // /* =========================
        //  *   PÁGINA 2
        //  *   Bloques E2.x (FV)
        //  * ========================= */
        // if ($pageNo === 2) {

        //     // --- E2.2 Módulo fotovoltaico ---
        //     // Marca/modelo: coge el primer modelo de placa si existe
        //     $primeraPlaca = $boletin->placas->first();

        //     if ($primeraPlaca) {
        //         $pdf->SetXY(70, 50);   // Marca/modelo
        //         $pdf->Write(3, $enc($primeraPlaca->modelo_placa));

        //         // Potencia pico del módulo (Wp)
        //         $pdf->SetXY(130, 50);
        //         $pdf->Write(3, $enc($primeraPlaca->potencia_placa . ' W'));
        //     }

        //     // Tecnología de la célula: Monocristalino (por ejemplo)
        //     $pdf->SetXY(30, 50);
        //     $pdf->Write(3, $enc('Monocristalino'));

        //     // --- E2.3 Generador fotovoltaico ---
        //     // Potencia pico total (Wp)
        //     $pdf->SetXY(25, 65);
        //     $pdf->Write(3, $enc((string) $boletin->potencia_pico));

        //     // Nº total de módulos (suma de cantidades)
        //     $totalModulos = $boletin->placas->sum('cantidad_placas');
        //     $pdf->SetXY(80, 71);
        //     $pdf->Write(3, $enc((string) $totalModulos));

        //     // Inclinación respecto a la horizontal (valor ejemplo o campo futuro)
        //     $pdf->SetXY(25, 71);
        //     $pdf->Write(3, $enc('30')); // si quieres fijo 30º

        //     // Orientación del generador FV
        //     $pdf->SetXY(135, 71);
        //     $pdf->Write(3, $enc('Sur 0º'));


        //     // --- E2.4 Inversores ---
        //     // Nº de inversores
        //     $pdf->SetXY(25, 86);
        //     $pdf->Write(3, $enc((string) ($boletin->numero_inversores ?? 1)));

        //     // Inversor 1: Marca / Modelo / Potencia AC aproximada
        //     $pdf->SetXY(55, 90);   // Marca
        //     $pdf->Write(3, $enc($boletin->marca_inversor ?? ''));

        //     $pdf->SetXY(55, 94);   // Modelo
        //     $pdf->Write(3, $enc($boletin->modelo_inversor ?? ''));

        //     if (!is_null($potDiKw)) {
        //         $pdf->SetXY(55, 98);   // Potencia AC, Pn (kW)
        //         $pdf->Write(3, $enc(number_format($potDiKw, 2, ',', '.') . ' kW'));
        //     }

        //     // Aquí podrías rellenar más columnas (Inversor 2, Inversor 3…) si un día
        //     // decides guardar inversores de forma separada.
        }

        /* =========================
         *   PÁGINAS 3 y 4
         *   (Protecciones, líneas, etc.)
         * ========================= */
    }

    return $pdf->Output('I', 'MemoriaTecnica.pdf');
}

}

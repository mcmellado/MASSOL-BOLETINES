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

        $fechaHoy = date('Y-m-d');

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

            // INVERSORES: ahora arrays (pero se guardan en la tabla inversores)
            'marca_inversor'            => 'required|array|min:1',
            'marca_inversor.*'          => 'nullable|string|max:255',

            'marca_inversor_nuevo'      => 'array',
            'marca_inversor_nuevo.*'    => 'nullable|string|max:255',

            'modelo_inversor'           => 'required|array|min:1',
            'modelo_inversor.*'         => 'nullable|string|max:255',

            'potencia_inversores'       => 'required|array|min:1',
            'potencia_inversores.*'     => 'nullable|string|max:255',

            'numero_inversores'         => 'required|array|min:1',
            'numero_inversores.*'       => 'nullable|integer|min:0',

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
         * INVERSORES (múltiples filas) → se convertirán en registros de la tabla inversores
         */
        $marcasSelect    = $request->input('marca_inversor', []);        // select
        $marcasNuevas    = $request->input('marca_inversor_nuevo', []);  // texto libre
        $modelosInversor = $request->input('modelo_inversor', []);
        $potenciasInv    = $request->input('potencia_inversores', []);
        $numerosInv      = $request->input('numero_inversores', []);

        $inversores = [];

        foreach ($modelosInversor as $i => $modeloInv) {
            $modeloInv = trim($modeloInv ?? '');

            $marcaSel   = $marcasSelect[$i] ?? '';
            $marcaNueva = trim($marcasNuevas[$i] ?? '');

            // resolver marca final
            if ($marcaSel === '__nuevo__') {
                $marcaFinal = $marcaNueva;
            } else {
                $marcaFinal = $marcaSel;
            }
            $marcaFinal = trim($marcaFinal ?? '');

            $potencia = trim((string)($potenciasInv[$i] ?? ''));
            $cantidad = $numerosInv[$i] ?? null;
            $cantidad = $cantidad !== null ? (int)$cantidad : null;

            // fila completamente vacía → la saltamos
            if ($marcaFinal === '' && $modeloInv === '' && $potencia === '' && ($cantidad === null || $cantidad === 0)) {
                continue;
            }

            // guardamos marca en catálogo si existe
            if ($marcaFinal !== '') {
                MarcaInversor::firstOrCreate(['nombre' => $marcaFinal]);
            }

            $inversores[] = [
                'marca'    => $marcaFinal,
                'modelo'   => $modeloInv !== '' ? $modeloInv : null,
                'potencia' => $potencia !== '' ? $potencia : null,
                'cantidad' => $cantidad,
            ];
        }

        if (empty($inversores)) {
            return back()
                ->withErrors(['marca_inversor' => 'Debes indicar al menos un inversor.'])
                ->withInput();
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

        // Crear boletín (ya sin campos de inversores)
        $boletin = Boletin::create([
            'cliente_id'                => $validated['cliente_id'],
            'fecha'                     => $validated['fecha'],
            'numero_registro'           => $validated['numero_registro'] ?? null,
            'cups'                      => $validated['cups'] ?? null,
            'referencia_catastral'      => $validated['referencia_catastral'] ?? null,
            'potencia_factura_luz'      => $validated['potencia_factura_luz'] ?? null,
            'metros_cuadrados_vivienda' => $validated['metros_cuadrados_vivienda'] ?? null,
            'potencia_pico'             => $validated['potencia_pico'],

            'tipo_instalacion_electrica'=> $validated['tipo_instalacion_electrica'],
            'tension_suministro'        => $validated['tension_suministro'],
            'tipo_instalacion'          => $validated['tipo_instalacion'],

            'tipos_cubierta'            => $validated['tipos_cubierta'] ?? [],

            'tiene_bateria'             => $validated['tiene_bateria'],
            'potencia_bateria'          => $validated['potencia_bateria'] ?? null,
            'numero_baterias'           => $validated['numero_baterias'] ?? null,
            'proteccion_sobretension'   => $validated['proteccion_sobretension'] ?? null,
        ]);

        // Guardar inversores en la tabla inversores
        foreach ($inversores as $inv) {
            $boletin->inversores()->create([
                'marca'    => $inv['marca'],
                'modelo'   => $inv['modelo'],
                'potencia' => $inv['potencia'],
                'cantidad' => $inv['cantidad'] ?? 1,
            ]);
        }

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
        $boletin->load('cliente', 'placas', 'inversores');

        $potenciaDerivacionKw = $this->calcularPotenciaDerivacionKw($boletin);

        $inversores = $boletin->inversores;

        return view('boletines.show', compact('boletin', 'potenciaDerivacionKw', 'inversores'));
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

        $boletin->load('placas', 'inversores');

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

            // INVERSORES arrays (para tabla inversores)
            'marca_inversor'            => 'required|array|min:1',
            'marca_inversor.*'          => 'nullable|string|max:255',

            'marca_inversor_nuevo'      => 'array',
            'marca_inversor_nuevo.*'    => 'nullable|string|max:255',

            'modelo_inversor'           => 'required|array|min:1',
            'modelo_inversor.*'         => 'nullable|string|max:255',

            'potencia_inversores'       => 'required|array|min:1',
            'potencia_inversores.*'     => 'nullable|string|max:255',

            'numero_inversores'         => 'required|array|min:1',
            'numero_inversores.*'       => 'nullable|integer|min:0',

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
         * INVERSORES (múltiples filas) → tabla inversores
         */
        $marcasSelect    = $request->input('marca_inversor', []);
        $marcasNuevas    = $request->input('marca_inversor_nuevo', []);
        $modelosInversor = $request->input('modelo_inversor', []);
        $potenciasInv    = $request->input('potencia_inversores', []);
        $numerosInv      = $request->input('numero_inversores', []);

        $inversores = [];

        foreach ($modelosInversor as $i => $modeloInv) {
            $modeloInv = trim($modeloInv ?? '');

            $marcaSel   = $marcasSelect[$i] ?? '';
            $marcaNueva = trim($marcasNuevas[$i] ?? '');

            if ($marcaSel === '__nuevo__') {
                $marcaFinal = $marcaNueva;
            } else {
                $marcaFinal = $marcaSel;
            }
            $marcaFinal = trim($marcaFinal ?? '');

            $potencia = trim((string)($potenciasInv[$i] ?? ''));
            $cantidad = $numerosInv[$i] ?? null;
            $cantidad = $cantidad !== null ? (int)$cantidad : null;

            if ($marcaFinal === '' && $modeloInv === '' && $potencia === '' && ($cantidad === null || $cantidad === 0)) {
                continue;
            }

            if ($marcaFinal !== '') {
                MarcaInversor::firstOrCreate(['nombre' => $marcaFinal]);
            }

            $inversores[] = [
                'marca'    => $marcaFinal,
                'modelo'   => $modeloInv !== '' ? $modeloInv : null,
                'potencia' => $potencia !== '' ? $potencia : null,
                'cantidad' => $cantidad,
            ];
        }

        if (empty($inversores)) {
            return back()
                ->withErrors(['marca_inversor' => 'Debes indicar al menos un inversor.'])
                ->withInput();
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

        // Actualizar boletín (sin campos de inversores)
        $boletin->update([
            'cliente_id'                => $validated['cliente_id'],
            'fecha'                     => $validated['fecha'],
            'numero_registro'           => $validated['numero_registro'] ?? null,
            'cups'                      => $validated['cups'] ?? null,
            'referencia_catastral'      => $validated['referencia_catastral'] ?? null,
            'potencia_factura_luz'      => $validated['potencia_factura_luz'] ?? null,
            'metros_cuadrados_vivienda' => $validated['metros_cuadrados_vivienda'] ?? null,
            'potencia_pico'             => $validated['potencia_pico'],

            'tipo_instalacion_electrica'=> $validated['tipo_instalacion_electrica'],
            'tension_suministro'        => $validated['tension_suministro'],
            'tipo_instalacion'          => $validated['tipo_instalacion'],

            'tipos_cubierta'            => $validated['tipos_cubierta'] ?? [],

            'tiene_bateria'             => $validated['tiene_bateria'],
            'potencia_bateria'          => $validated['potencia_bateria'] ?? null,
            'numero_baterias'           => $validated['numero_baterias'] ?? null,
            'proteccion_sobretension'   => $validated['proteccion_sobretension'] ?? null,
        ]);

        // Regenerar inversores
        $boletin->inversores()->delete();
        foreach ($inversores as $inv) {
            $boletin->inversores()->create([
                'marca'    => $inv['marca'],
                'modelo'   => $inv['modelo'],
                'potencia' => $inv['potencia'],
                'cantidad' => $inv['cantidad'] ?? 1,
            ]);
        }

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

    /**
     * Potencia prevista “genérica” → reutilizamos la de derivación individual.
     */
    private function calcularPotenciaPrevistaKw(Boletin $boletin): ?float
    {
        return $this->calcularPotenciaDerivacionKw($boletin);
    }

    public function pdfOficial(Boletin $boletin)
    {
        $boletin->load('cliente', 'placas');
        $cliente = $boletin->cliente;

        // --- VARIABLE FECHA DE HOY (Formato PDF) ---
        $fechaHoy = date('d/m/Y');

        // Nº de registro instalación (25 por defecto)
        $numeroRegistro = $boletin->numero_registro ?: '25';

        // Sección conductores (por si la usas luego)
        $seccionConductores = $boletin->tension_suministro === '400V'
            ? '4     4      4  '
            : '6     6      6  ';

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
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(0, 0, 0);

        // Pequeño helper para tildes/ñ
        $enc = fn($txt) => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $txt);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $size  = $pdf->getTemplateSize($tplId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            if ($pageNo === 1) {

                // FECHA HOY
                $pdf->SetXY(20, 10);
                $pdf->Write(1, $enc($fechaHoy));

                // CABECERA - Nº REGISTRO
                $pdf->SetXY(102, 44);
                $pdf->Write(4, $enc($numeroRegistro));

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
                    $pdf->SetFont('Helvetica', '', 6.5);
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
                    $pdf->SetFont('Helvetica', '', 4); 
                    $pdf->SetXY(110, 78.5);
                    $pdf->Write(1, $enc($cliente->email ?? ''));

                    // Teléfono
                    $pdf->SetXY(148.5, 78.5);
                    $pdf->Write(1, $enc($cliente->telefono ?? ''));

                    // Código postal (zona titular)
                    $pdf->SetFont('Helvetica', '', 7);
                    $pdf->SetXY(130, 73.9);
                    $pdf->Write(1, $enc($cliente->codigo_postal ?? ''));

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

                //año corto
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

                // FECHA DEL BOLETÍN (día, mes texto, año)
                $fechaCarbon = $boletin->fecha ? Carbon::parse($boletin->fecha)->locale('es') : null;
                $diaBoletin  = $fechaCarbon ? $fechaCarbon->translatedFormat('d') : '';
                $mesBoletin  = $fechaCarbon ? $fechaCarbon->translatedFormat('F') : '';
                $anioBoletin = $fechaCarbon ? $fechaCarbon->translatedFormat('Y') : '';

                // DÍA
                $pdf->SetXY(100, 193.3);
                $pdf->Write(1, $enc($diaBoletin));

                // MES
                $pdf->SetXY(115, 193.2);
                $pdf->Write(1, $enc($mesBoletin));

                // AÑO
                $pdf->SetXY(140, 193.2);
                $pdf->Write(1, $enc($anioBoletin));

                // Potencia prevista instalación (kW)
                if (!is_null($potInstKw)) {
                    $textoInst = number_format($potInstKw, 2, ',', '.') . ' kW';
                    $pdf->SetXY(90, 116);
                    $pdf->Write(1, $enc($textoInst));
                }

                // Potencia prevista derivación individual (kW)
                if (!is_null($potDiKw)) {
                    $textoDi = number_format($potDiKw, 2, ',', '.') . ' kW';
                    $pdf->SetXY(90, 122);
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
        if (!$boletin->relationLoaded('placas')) {
            $boletin->load('placas');
        }

        $totalWatts = 0.0;

        foreach ($boletin->placas as $placa) {
            $modeloFinal = trim((string) ($placa->modelo_placa ?? ''));
            $cantidad    = (int) ($placa->cantidad_placas ?? 0);

            if ($modeloFinal === '' || $cantidad <= 0) {
                continue;
            }

            $watts = $this->obtenerPotenciaWattsDesdeModelo($modeloFinal);
            $totalWatts += $watts * $cantidad;
        }

        if ($totalWatts <= 0) {
            return null;
        }

        $kw = $totalWatts / 1000;
        return round($kw, 1);

    }

    /**
     * Potencia prevista de la DERIVACIÓN INDIVIDUAL (kW)
     * Ahora se calcula directamente a partir de la relación inversores.
     */
    private function calcularPotenciaDerivacionKw(Boletin $boletin): ?float
{
    // Aseguramos que los inversores están cargados
    if (! $boletin->relationLoaded('inversores')) {
        $boletin->load('inversores');
    }

    if ($boletin->inversores->isEmpty()) {
        return null;
    }

    $totalKw = 0.0;

    foreach ($boletin->inversores as $inv) {
        $potenciaRaw = trim((string) ($inv->potencia ?? ''));

        if ($potenciaRaw === '') {
            continue;
        }

        // Sacamos el número de la cadena ("5", "5kW", "5000 W", etc.)
        if (!preg_match('/(\d+(?:[.,]\d+)?)/', $potenciaRaw, $m)) {
            continue;
        }

        $valor = (float) str_replace(',', '.', $m[1]);

        if ($valor <= 0) {
            continue;
        }

        // Si es un valor muy grande (ej: 5000), asumimos W y pasamos a kW
        // Si es razonable (ej: 5, 10, 15), asumimos kW directamente
        if ($valor > 50) {
            $valorKw = $valor / 1000.0; // viene en W
        } else {
            $valorKw = $valor;          // ya está en kW
        }

        $cantidad = (int) ($inv->cantidad ?? 1);
        if ($cantidad < 1) {
            $cantidad = 1;
        }

        $totalKw += $valorKw * $cantidad;
    }

    return $totalKw > 0 ? round($totalKw, 2) : null;
}

    /**
     * Devuelve la potencia en W de un modelo de placa (ej: "LONGI 640W" -> 640).
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

        // 2) Catálogo legacy
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

            ModeloPlaca::updateOrCreate(
                ['nombre' => $modelo],
                ['potencia_w' => (int) $w]
            );

            return $w;
        }

        // 3) Intento genérico: último número del texto
        if (preg_match_all('/(\d+(?:[.,]\d+)?)/', $modelo, $matches)) {
            $ultimoNumero = end($matches[1]);
            $valor = (float) str_replace(',', '.', $ultimoNumero);

            if ($valor > 0) {
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
        $boletin->load('cliente', 'placas', 'inversores');
        $cliente = $boletin->cliente;

        $potInstKw = $this->calcularPotenciaInstalacionKw($boletin);
        $potDiKw   = $this->calcularPotenciaDerivacionKw($boletin);

        // Potencia nominal TOTAL de inversores (suma de todos, en kW)
        $boletin->loadMissing('inversores');

        $potInvTotalKw = 0.0;

        foreach ($boletin->inversores as $inv) {
            $raw = trim((string) ($inv->potencia ?? ''));

            if ($raw === '') {
                continue;
            }

            // Extraemos el número (ej: "5 kW", "5000W" → 5 o 5000)
            if (preg_match('/(\d+(?:[.,]\d+)?)/', $raw, $m)) {
                $valor = (float) str_replace(',', '.', $m[1]);

                // Regla:
                // - si es > 50 lo interpretamos como W → pasamos a kW
                // - si es <= 50 lo tomamos como kW
                if ($valor > 50) {
                    $kw = $valor / 1000.0;
                } else {
                    $kw = $valor;
                }

                $cantidad = (int) ($inv->cantidad ?? 1);
                if ($cantidad < 1) {
                    $cantidad = 1;
                }

                $potInvTotalKw += $kw * $cantidad;
            }
        }


        // Primer inversor (para marca / modelo / potencia en PDF)
        $primerInversor = $boletin->inversores->first();
        $marcaInversor  = $primerInversor->marca   ?? '';
        $modeloInversor = $primerInversor->modelo  ?? '';
        $potenciaTexto  = $primerInversor->potencia ?? '';

        $templatePath = storage_path('app/plantillas/MemoriaTecnica.pdf');

        $pdf = new \setasign\Fpdi\Fpdi();
        $pageCount = $pdf->setSourceFile($templatePath);

        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(0, 0, 0);

        $enc = fn($txt) => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $txt);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {

            $tplId = $pdf->importPage($pageNo);
            $size  = $pdf->getTemplateSize($tplId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            // PÁGINA 1
            if ($pageNo === 1 && $cliente) {

                $numeroRegistro = $boletin->numero_registro ?? '';

                $pdf->SetFont('Helvetica', '', 12);
                $pdf->SetXY(120, 40);
                $pdf->Write(3, $enc($numeroRegistro));

                $nombreCompleto = trim(
                    ($cliente->nombre ?? '') . ' ' .
                    ($cliente->primer_apellido ?? '') . ' ' .
                    ($cliente->segundo_apellido ?? '')
                );

                $nombreEnc = $enc($nombreCompleto);

                $x = 29;
                $y = 57;

                $maxWidth = 80;
                $fontSize = 9;
                $minSize  = 4.8;

                $pdf->SetFont('Helvetica', '', $fontSize);

                while ($pdf->GetStringWidth($nombreEnc) > $maxWidth && $fontSize > $minSize) {
                    $fontSize -= 0.2;
                    $pdf->SetFont('Helvetica', '', $fontSize);
                }

                $pdf->SetXY($x, $y);
                $pdf->Write(3, $nombreEnc);
                

                $pdf->SetFont('Helvetica', '', 8.5);
                $pdf->SetXY(132, 57.5);
                $pdf->Write(3, $enc($cliente->dni_cif ?? ''));

                $pdf->SetXY(29.8, 75);
                $pdf->Write(3, $enc($cliente->codigo_postal ?? ''));

                $pdf->SetXY(73, 75.9);
                $pdf->Write(1, $enc($cliente->provincia ?? ''));

                $poblacion = $enc($cliente->poblacion ?? '');
                $maxWidth = 40;
                $fontSize = 6;
                $minSize = 3.5;

                $pdf->SetFont('Helvetica', '', $fontSize);
                while ($pdf->GetStringWidth($poblacion) > $maxWidth && $fontSize > $minSize) {
                    $fontSize -= 0.2;
                    $pdf->SetFont('Helvetica', '', $fontSize);
                }
                $pdf->SetXY(47, 75.5);
                $pdf->Write(3, $poblacion);

                $pdf->SetFont('Helvetica', '', 8.5);
                $pdf->SetXY(103, 75);
                $pdf->Write(3, $enc($cliente->telefono ?? ''));

                $pdf->SetFont('Helvetica', '', 6.5); 
                $pdf->SetXY(137, 75);
                $pdf->Write(3, $enc($cliente->email ?? ''));

                
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetXY(137, 84.5);
                $pdf->Write(3, $enc($cliente->dni_cif ?? ''));

                $direccion = trim($cliente->direccion ?? '');
                $calle = '';
                $numero = '';

                $partes = array_map('trim', explode(',', $direccion));

                if (count($partes) >= 2) {
                    $calle = $partes[0];
                    $numero = $partes[1];
                } elseif (preg_match('/^(.*?)[\s]+(\d+.*)$/', $direccion, $m)) {
                    $calle = trim($m[1]);
                    $numero = trim($m[2]);
                } else {
                    $calle = $direccion;
                    $numero = '';
                }

                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetXY(106.5, 100.5);
                $pdf->Write(1, $enc($numero));

                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetXY(106.5, 67);
                $pdf->Write(1, $enc($numero));


                $pdf->SetXY(28, 66.7);
                $pdf->Write(1, $enc($calle));

                $pdf->SetXY(28, 100.5);
                $pdf->Write(1, $enc($calle));

                $pdf->SetXY(29.8, 109);
                $pdf->Write(3, $enc($cliente->codigo_postal ?? ''));

                $pdf->SetFont('Helvetica', '', $fontSize);
                $pdf->SetXY(47, 110.5);
                $pdf->Write(1, $poblacion);

                $pdf->SetFont('Helvetica', '', 8.5);
                $pdf->SetXY(73, 110.5);
                $pdf->Write(1, $enc($cliente->provincia ?? ''));

                $pdf->SetXY(102, 109);
                $pdf->Write(3, $enc($boletin->referencia_catastral ?? ''));

                if ($boletin->tipo_instalacion === 'nueva') {
                    $pdf->SetXY(57.5, 133.2);
                    $pdf->Write(3, 'X');
                } elseif ($boletin->tipo_instalacion === 'ampliacion') {
                    $pdf->SetXY(57.5, 137);
                    $pdf->Write(3, 'X');
                }

                $fecha = $boletin->fecha ?? now();
                $fechaCarbon = \Carbon\Carbon::parse($fecha);

                $meses = [
                    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
                ];

                $textoFecha = "En Jerez de la Frontera a {$fechaCarbon->day} de {$meses[$fechaCarbon->month]} del {$fechaCarbon->year}";

                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetXY(70, 225);
                $pdf->Write(4, $textoFecha);

                if ($boletin->tipo_instalacion_electrica === 'monofasica') {
                    $pdf->SetAutoPageBreak(false);
                    $pdf->SetMargins(0, 0, 0);
                    $pdf->SetXY(107, 284);
                    $pdf->Write(3, 'X');
                }
            }
        
                // ============================================
                // PÁGINA 2 COMPLETA
                // ============================================
                if ($pageNo === 2) {

                    // ===== SUMA TOTAL POTENCIAS DE INVERSORES =====
                    $boletin->loadMissing('inversores');

                    $potInvTotalKw = 0;

                    foreach ($boletin->inversores as $inv) {
                        $raw = trim((string) ($inv->potencia ?? ''));

                        if ($raw === '') continue;

                        if (preg_match('/(\d+(?:[.,]\d+)?)/', $raw, $m)) {
                            $valor = (float) str_replace(',', '.', $m[1]);

                            // > 50 lo interpretamos como W → lo pasamos a kW
                            if ($valor > 50) {
                                $kw = $valor / 1000;
                            } else {
                                $kw = $valor; // ya viene en kW
                            }

                            $cantidad = (int) ($inv->cantidad ?? 1);
                            if ($cantidad < 1) $cantidad = 1;

                            $potInvTotalKw += $kw * $cantidad;
                        }
                    }


                     // ===== MOSTRAR TOTAL INVERSORES (UNA SOLA VEZ) =====
                    if ($potInvTotalKw > 0) {
                        // sin decimales
                        $textoPnTotal = intval(round($potInvTotalKw)) . ' kW';

                        $pdf->SetFont('Helvetica', '', 9);
                        // usa la casilla donde quieres que salga
                        $pdf->SetXY(82, 11); // o las coordenadas que ya tenías
                        $pdf->Write(3, $enc($textoPnTotal));
                    }


                    $pdf->SetMargins(0, 0, 0);
                    $pdf->SetAutoPageBreak(false);
                    $pdf->SetFont('Helvetica', '', 8.5);
                    $pdf->SetTextColor(0, 0, 0);

                    // -------------------------------
                    // POTENCIA TOTAL INVERSORES (TEXTO GENERAL)
                    // -------------------------------
                    $potenciaCruda = trim((string)$potenciaTexto);

                    if ($potenciaCruda !== '') {

                        $textoPotenciaTotal = $potenciaCruda;
                        $lower = strtolower($textoPotenciaTotal);

                        if (!str_contains($lower, 'w') && !str_contains($lower, 'kw')) {
                            $textoPotenciaTotal .= ' kW';
                        }

                        // Este es el campo general de potencia total (no de la tabla)
                        $pdf->SetFont('Helvetica', '', 9);
                        $pdf->SetXY(82, 105.5);
                        $pdf->Write(3, $enc($textoPotenciaTotal));
                    }

                    // Trifásica
                    if ($boletin->tipo_instalacion_electrica === 'trifasica') {
                        $pdf->SetXY(107, 12);
                        $pdf->Write(3, 'X');
                    }

                    // -------------------------------
                    // PRIMERA PLACA
                    // -------------------------------
                    $primeraPlaca = $boletin->placas->first();

                    if ($primeraPlaca) {
                        $pdf->SetFont('Helvetica', '', 8.5);
                        $pdf->SetXY(82, 28);
                        $pdf->Write(3, $enc($primeraPlaca->modelo_placa));
                    }

                    // Total módulos
                    $totalModulos = $boletin->placas->sum('cantidad_placas');

                    $pdf->SetFont('Helvetica', '', 8.5);
                    $pdf->SetXY(83, 60);
                    $pdf->Write(3, $enc($totalModulos));

                    // -------------------------------
                    // INVERSORES (HASTA 3)
                    // -------------------------------
                    $boletin->loadMissing('inversores');
                    $inversores = $boletin->inversores;

                    // Coordenadas de columnas (ajusta si hace falta)
                    $coordsCol = [
                        1 => [
                            'marca'    => [79, 81],   
                            'modelo'   => [76, 92],   
                            'tension'  => [82, 100],  
                            'potencia' => [82, 105.5],
                        ],
                        2 => [
                            'marca'    => [110, 81],  
                            'modelo'   => [105, 92],   
                            'tension'  => [111, 100],  
                            'potencia' => [111, 105.5] 
                        ],
                        3 => [
                            'marca'    => [150, 81],  
                            'modelo'   => [150, 92],   
                            'tension'  => [150, 100],  
                            'potencia' => [150, 105.5] 
                        ],
                    ];

                    // Tensión nominal AC
                    $tension_suministro = $boletin->tension_suministro ?? '';

                    // -------------------------------
                    // Pintar inversores
                    // -------------------------------
                    for ($i = 0; $i < min(3, $inversores->count()); $i++) {

                        $col = $i + 1;
                        $inv = $inversores[$i];

                        // Marca
                        [$xMarca, $yMarca] = $coordsCol[$col]['marca'];
                        $pdf->SetFont('Helvetica', '', 8.5);
                        $pdf->SetXY($xMarca, $yMarca);
                        $pdf->Write(3, $enc($inv->marca ?? ''));

                        // Modelo
                        [$xModelo, $yModelo] = $coordsCol[$col]['modelo'];
                        $pdf->SetFont('Helvetica', '', 8.5);
                        $pdf->SetXY($xModelo, $yModelo);
                        $pdf->Write(3, $enc($inv->modelo ?? ''));

                        // Tensión nominal AC
                        [$xTen, $yTen] = $coordsCol[$col]['tension'];
                        $pdf->SetFont('Helvetica', '', 8.5);
                        $pdf->SetXY($xTen, $yTen);
                        $pdf->Write(3, $enc($tension_suministro));

                        // POTENCIA AC Pn (kW)
                        if ($col >= 2) {
                            [$xPot, $yPot] = $coordsCol[$col]['potencia'];

                            $potRaw = trim((string) ($inv->potencia ?? ''));
                            $textoPotenciaInv = '';

                            if ($potRaw !== '') {
                                if (preg_match('/(\d+(?:[.,]\d+)?)/', $potRaw, $m)) {
                                    $valor = (float) str_replace(',', '.', $m[1]);
                                    if ($valor > 50) {
                                        $valorKw = $valor / 1000.0;
                                    } else {
                                        $valorKw = $valor;
                                    }

                                    $textoPotenciaInv = intval(round($valorKw)) . ' kW';

                                }
                            }

                            if ($textoPotenciaInv !== '') {
                                $pdf->SetFont('Helvetica', '', 8.5);
                                $pdf->SetXY($xPot, $yPot);
                                $pdf->Write(3, $enc($textoPotenciaInv));
                            }
                        }
                    }

                    // -------------------------------
                    // Vcc + Protecciones solo para col 2 y 3
                    // -------------------------------
                    

                    $xCol = [
                        2 => $coordsCol[2]['tension'][0],
                        3 => $coordsCol[3]['tension'][0] 
                    ];

                    if ($inversores->count() >= 2) {

                        // Vcc MAX (fila 110)
                        foreach ($xCol as $col => $x) {
                            if ($col > $inversores->count()) continue;
                            $pdf->SetFont('Helvetica', '', 8.5);
                            $pdf->SetXY($x, 110);
                            $pdf->Write(3, $enc('600'));
                        }

                        // Vcc MIN (fila 115)
                        foreach ($xCol as $col => $x) {
                            if ($col > $inversores->count()) continue;
                            $pdf->SetFont('Helvetica', '', 8.5);
                            $pdf->SetXY($x, 115);
                            $pdf->Write(3, $enc('90'));
                        }

                        // Protección interna 51 y 49 Hz (fila 125)
                        foreach ($xCol as $col => $x) {
                            if ($col > $inversores->count()) continue;
                            $pdf->SetFont('Helvetica', '', 8.5);
                            $pdf->SetXY($x, 125);
                            $pdf->Write(3, $enc('SI'));
                        }

                        // Protección interna 1.1 y 0.85 Um (fila 130)
                        foreach ($xCol as $col => $x) {
                            if ($col > $inversores->count()) continue;
                            $pdf->SetFont('Helvetica', '', 8.5);
                            $pdf->SetXY($x, 140);
                            $pdf->Write(3, $enc('SI'));
                        }

                        // Protección anti-isla (fila 135)
                        foreach ($xCol as $col => $x) {
                            if ($col > $inversores->count()) continue;
                            $pdf->SetFont('Helvetica', '', 8.5);
                            $pdf->SetXY($x, 150);
                            $pdf->Write(3, $enc('SI'));
                        }
                    }

                    // -------------------------------
                    // POTENCIA PICO FV
                    // -------------------------------
                    if (!is_null($potInstKw)) {
                        $textoPico = number_format($potInstKw, 2, ',', '.') . ' kWp';
                        $pdf->SetFont('Helvetica', '', 9);
                        $pdf->SetXY(40, 46);
                        $pdf->Write(3, $enc($textoPico));
                    }

                    // Potencia instalación abajo
                    if (!is_null($potInstKw)) {
                        $textoInstalacion = number_format($potInstKw, 2, ',', '.') . ' kW';
                        $pdf->SetFont('Helvetica', '', 8.5);
                        $pdf->SetXY(50.1, 270);
                        $pdf->Write(3, $enc($textoInstalacion));
                    }

                    // Potencia derivación individual
                    if (!is_null($potDiKw)) {
                        $textoDerivacion = number_format($potDiKw, 2, ',', '.') . ' kW';
                        $pdf->SetFont('Helvetica', '', 8.5);
                        $pdf->SetXY(50.1, 282);
                        $pdf->Write(3, $enc($textoDerivacion));
                    }
                }

                            


            // Página 3
            if ($pageNo === 3) {

                if (!is_null($potDiKw)) {

                    $textoDerivacion = number_format($potDiKw, 2, ',', '.') . ' kW';

                    $pdf->SetFont('Helvetica', '', 8.5);

                    $pdf->SetXY(50.1, 20);
                    $pdf->Write(3, $enc($textoDerivacion));

                    $pdf->SetXY(50.1, 34);
                    $pdf->Write(3, $enc($textoDerivacion));

                    $fechaBoletin = \Carbon\Carbon::parse($boletin->fecha);
                    $meses = [
                        1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                        7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
                    ];
                    $textoFecha = "En Jerez de la Frontera a {$fechaBoletin->day} de {$meses[$fechaBoletin->month]} del {$fechaBoletin->year}";

                    $pdf->SetFont('Helvetica', '', 8);
                    $pdf->SetXY(70, 90);
                    $pdf->Write(4, $enc($textoFecha));
                }
            }
        }

        /*
         * MONOFÁSICO
         */
        if ($boletin->tipo_instalacion_electrica === 'monofasica') {

            $monoPath = storage_path('app/plantillas/MONOFASICO.pdf');

            if (file_exists($monoPath)) {

                $monoPageCount = $pdf->setSourceFile($monoPath);

                $placas = $boletin->placas;
                $totalModulos = $placas->sum('cantidad_placas');

                $modelosTexto = [];
                foreach ($placas as $placa) {
                    $cantidad = $placa->cantidad_placas ?? 0;
                    $modelo   = $placa->modelo_placa ?? '';

                    if ($cantidad && $modelo) {
                        $modelosTexto[] = "{$cantidad}x {$modelo}";
                    }
                }
                $textoModelos = implode(', ', $modelosTexto);
                $longitudModelos = strlen($textoModelos);

                for ($p = 1; $p <= $monoPageCount; $p++) {

                    $tplMono  = $pdf->importPage($p);
                    $sizeMono = $pdf->getTemplateSize($tplMono);

                    $pdf->AddPage($sizeMono['orientation'], [$sizeMono['width'], $sizeMono['height']]);
                    $pdf->useTemplate($tplMono);

                    if ($pageNo === 4) {

                        $pdf->SetTextColor(0, 0, 0);
                        $pdf->SetAutoPageBreak(false);
                        $pdf->SetMargins(0, 0, 0);

                        $textoTotal = $totalModulos . ' ' . ($totalModulos == 1 ? 'panel solar' : 'paneles solares');

                        $pdf->SetFont('Helvetica', '', 5);
                        $pdf->SetXY(30, 27);
                        $pdf->Write(3, $enc($textoTotal));

                        if ($textoModelos !== '') {
                            $fontSize = 4;
                            $pdf->SetFont('Helvetica', '', $fontSize);
                            $pdf->SetXY(30, 31);
                            $pdf->Write(3, $enc($textoModelos));
                        }

                        $pdf->SetFont('Helvetica', '', 5);
                        $pdf->SetXY(40, 113);
                        $pdf->Write(3, $enc($modeloInversor));
                    }

                    $nombreRaw   = trim($cliente->nombre ?? '');
                    $apellido1   = trim($cliente->primer_apellido ?? '');
                    $apellido2   = trim($cliente->segundo_apellido ?? '');

                    $partesNombre = preg_split('/\s+/', $nombreRaw);
                    $primerNombre = $partesNombre[0] ?? '';

                    $inicialNombre = $primerNombre !== '' ? mb_substr($primerNombre, 0, 1, 'UTF-8') : '';

                    $nombreCompacto = $inicialNombre . $apellido1 . $apellido2;

                    $nombreCompacto = iconv('UTF-8', 'ASCII//TRANSLIT', $nombreCompacto);
                    $nombreCompacto = strtoupper($nombreCompacto);

                    $pdf->SetFont('Helvetica', '', 4);
                    $pdf->SetXY(145.5, 117);
                    $pdf->Write(3, $enc($nombreCompacto));

                    $direccion = trim($cliente->direccion ?? '');
                    $calle  = '';
                    $numero = '';

                    $partes = array_map('trim', explode(',', $direccion));

                    if (count($partes) >= 2) {
                        $calle  = $partes[0];
                        $numero = $partes[1];
                    } elseif (preg_match('/^(.*?)[\s]+(\d+.*)$/', $direccion, $m)) {
                        $calle  = trim($m[1]);
                        $numero = trim($m[2]);
                    } else {
                        $calle  = $direccion;
                        $numero = '';
                    }

                    $cp        = trim($cliente->codigo_postal ?? '');
                    $poblacion = trim($cliente->poblacion ?? '');
                    $provincia = trim($cliente->provincia ?? '');

                    $lineaDireccion = "{$calle}, {$numero}, {$cp}, {$poblacion}, {$provincia}";
                    $lineaDireccion = mb_strtoupper($lineaDireccion, 'UTF-8');

                    $lenDir = strlen($lineaDireccion);
                    if     ($lenDir <= 45) $fontSizeDir = 5;
                    elseif ($lenDir <= 65) $fontSizeDir = 5.5;
                    elseif ($lenDir <= 85) $fontSizeDir = 5;
                    else                   $fontSizeDir = 4;

                    $pdf->SetFont('Helvetica', '', 5);
                    $pdf->SetXY(102, 120);
                    $pdf->Write(3, $enc($lineaDireccion));

                    $fechaBoletin = \Carbon\Carbon::parse($boletin->fecha);
                    $fechaFormateada = $fechaBoletin->format('d-m-Y');

                    $pdf->SetFont('Helvetica', '', 7);
                    $pdf->SetXY(102, 126.5);
                    $pdf->Write(3, $enc($fechaFormateada));

                    $nombreCompleto = trim(
                        ($cliente->nombre ?? '') . ' ' .
                        ($cliente->primer_apellido ?? '') . ' ' .
                        ($cliente->segundo_apellido ?? '')
                    );

                    $nombreCompletoMayus = mb_strtoupper($nombreCompleto, 'UTF-8');

                    $lenNombre = strlen($nombreCompletoMayus);

                    if ($lenNombre <= 25) {
                        $fontSizeNombre = 6;
                    } elseif ($lenNombre <= 40) {
                        $fontSizeNombre = 5.5;
                    } elseif ($lenNombre <= 55) {
                        $fontSizeNombre = 5;
                    } else {
                        $fontSizeNombre = 4;
                    }

                    $pdf->SetFont('Helvetica', '', $fontSizeNombre);
                    $pdf->SetXY(119, 126.5);
                    $pdf->Write(3, $enc($nombreCompletoMayus));
                }
            }
        }

        /*
         * TRIFÁSICO
         */
        if ($boletin->tipo_instalacion_electrica === 'trifasica') {

            $monoPath = storage_path('app/plantillas/TRIFASICA.pdf');

            if (file_exists($monoPath)) {

                $monoPageCount = $pdf->setSourceFile($monoPath);

                $placas = $boletin->placas;
                $totalModulos = $placas->sum('cantidad_placas');

                $modelosTexto = [];
                foreach ($placas as $placa) {
                    $cantidad = $placa->cantidad_placas ?? 0;
                    $modelo   = $placa->modelo_placa ?? '';

                    if ($cantidad && $modelo) {
                        $modelosTexto[] = "{$cantidad}x {$modelo}";
                    }
                }
                $textoModelos = implode(', ', $modelosTexto);
                $longitudModelos = strlen($textoModelos);

                for ($p = 1; $p <= $monoPageCount; $p++) {

                    $tplMono  = $pdf->importPage($p);
                    $sizeMono = $pdf->getTemplateSize($tplMono);

                    $pdf->AddPage($sizeMono['orientation'], [$sizeMono['width'], $sizeMono['height']]);
                    $pdf->useTemplate($tplMono);

                    if ($pageNo === 4) {

                        $pdf->SetTextColor(0, 0, 0);
                        $pdf->SetAutoPageBreak(false);
                        $pdf->SetMargins(0, 0, 0);

                        $textoTotal = $totalModulos . ' ' . ($totalModulos == 1 ? 'panel solar' : 'paneles solares');

                        $pdf->SetFont('Helvetica', '', 5);
                        $pdf->SetXY(30, 27);
                        $pdf->Write(3, $enc($textoTotal));

                        if ($textoModelos !== '') {
                            $fontSize = 4;
                            $pdf->SetFont('Helvetica', '', $fontSize);
                            $pdf->SetXY(30, 31);
                            $pdf->Write(3, $enc($textoModelos));
                        }

                        $pdf->SetFont('Helvetica', '', 5);
                        $pdf->SetXY(40, 113);
                        $pdf->Write(3, $enc($modeloInversor));
                    }

                    $nombreRaw   = trim($cliente->nombre ?? '');
                    $apellido1   = trim($cliente->primer_apellido ?? '');
                    $apellido2   = trim($cliente->segundo_apellido ?? '');

                    $partesNombre = preg_split('/\s+/', $nombreRaw);
                    $primerNombre = $partesNombre[0] ?? '';

                    $inicialNombre = $primerNombre !== '' ? mb_substr($primerNombre, 0, 1, 'UTF-8') : '';

                    $nombreCompacto = $inicialNombre . $apellido1 . $apellido2;

                    $nombreCompacto = iconv('UTF-8', 'ASCII//TRANSLIT', $nombreCompacto);
                    $nombreCompacto = strtoupper($nombreCompacto);

                    $pdf->SetFont('Helvetica', '', 4);
                    $pdf->SetXY(145.5, 117);
                    $pdf->Write(3, $enc($nombreCompacto));

                    $direccion = trim($cliente->direccion ?? '');
                    $calle  = '';
                    $numero = '';

                    $partes = array_map('trim', explode(',', $direccion));

                    if (count($partes) >= 2) {
                        $calle  = $partes[0];
                        $numero = $partes[1];
                    } elseif (preg_match('/^(.*?)[\s]+(\d+.*)$/', $direccion, $m)) {
                        $calle  = trim($m[1]);
                        $numero = trim($m[2]);
                    } else {
                        $calle  = $direccion;
                        $numero = '';
                    }

                    $cp        = trim($cliente->codigo_postal ?? '');
                    $poblacion = trim($cliente->poblacion ?? '');
                    $provincia = trim($cliente->provincia ?? '');

                    $lineaDireccion = "{$calle}, {$numero}, {$cp}, {$poblacion}, {$provincia}";
                    $lineaDireccion = mb_strtoupper($lineaDireccion, 'UTF-8');

                    $lenDir = strlen($lineaDireccion);
                    if     ($lenDir <= 45) $fontSizeDir = 5;
                    elseif ($lenDir <= 65) $fontSizeDir = 5.5;
                    elseif ($lenDir <= 85) $fontSizeDir = 5;
                    else                   $fontSizeDir = 4;

                    $pdf->SetFont('Helvetica', '', 5);
                    $pdf->SetXY(102, 120);
                    $pdf->Write(3, $enc($lineaDireccion));

                    $fechaBoletin = \Carbon\Carbon::parse($boletin->fecha);
                    $fechaFormateada = $fechaBoletin->format('d-m-Y');

                    $pdf->SetFont('Helvetica', '', 7);
                    $pdf->SetXY(102, 126.5);
                    $pdf->Write(3, $enc($fechaFormateada));

                    $nombreCompleto = trim(
                        ($cliente->nombre ?? '') . ' ' .
                        ($cliente->primer_apellido ?? '') . ' ' .
                        ($cliente->segundo_apellido ?? '')
                    );

                    $nombreCompletoMayus = mb_strtoupper($nombreCompleto, 'UTF-8');

                    $lenNombre = strlen($nombreCompletoMayus);

                    if ($lenNombre <= 25) {
                        $fontSizeNombre = 6;
                    } elseif ($lenNombre <= 40) {
                        $fontSizeNombre = 5.5;
                    } elseif ($lenNombre <= 55) {
                        $fontSizeNombre = 5;
                    } else {
                        $fontSizeNombre = 4;
                    }

                    $pdf->SetFont('Helvetica', '', $fontSizeNombre);
                    $pdf->SetXY(119, 126.5);
                    $pdf->Write(3, $enc($nombreCompletoMayus));
                }
            }
        }

        return $pdf->Output('I', 'MemoriaTecnica.pdf');
    }
}

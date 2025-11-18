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

        // --- VARIABLE FECHA DE HOY (Formato base de datos para inputs date) ---
        $fechaHoy = date('Y-m-d');

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
            'modelosPlaca',
            'fechaHoy' // <-- Pasamos la fecha a la vista
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

            'modelo_placa'              => 'required|array|min:1',
            'modelo_placa.*'            => 'required|string|in:' . implode(',', $modelosPlaca),

            'cantidad_placas'           => 'required|array|min:1',
            'cantidad_placas.*'         => 'required|integer|min:1',
            'proteccion_sobretension' => [
                'nullable',
                'string', Rule::in(['interruptor_automatico', 'fusibles_calibrados']),
            ],
        ]);

        // Normalizamos algunos campos
        $validated['tiene_bateria']  = $request->boolean('tiene_bateria');
        $validated['tipos_cubierta'] = $request->input('tipos_cubierta', []);

        /*
         * CALCULAR POTENCIA PICO:
         * potencia_pico = SUM( watts(modelo_placa) * cantidad_placas )
         * Guardamos en Wp (vatios pico).
         */
        $modelos    = $validated['modelo_placa'];
        $cantidades = $validated['cantidad_placas'];

        $potenciaPicoTotal = 0;

        foreach ($modelos as $i => $modelo) {
            $watts    = $this->obtenerPotenciaWattsDesdeModelo($modelo);
            $cantidad = (int) ($cantidades[$i] ?? 0);

            if ($watts > 0 && $cantidad > 0) {
                $potenciaPicoTotal += $watts * $cantidad; // Wp totales
            }
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
            'proteccion_sobretension'   => $validated['proteccion_sobretension'] ?? null,
        ]);

        // Guardar placas
        $modelosPlacaForm = $validated['modelo_placa'];
        $cantidadesForm   = $validated['cantidad_placas'];

        foreach ($modelosPlacaForm as $index => $modelo) {
            $watts = $this->obtenerPotenciaWattsDesdeModelo($modelo);

            BoletinPlaca::create([
                'boletin_id'      => $boletin->id,
                'modelo_placa'    => $modelo,
                'potencia_placa'  => $watts, // W de cada placa
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

            'modelo_placa'              => 'required|array|min:1',
            'modelo_placa.*'            => 'required|string|in:' . implode(',', $modelosPlaca),

            'cantidad_placas'           => 'required|array|min:1',
            'cantidad_placas.*'         => 'required|integer|min:1',
            'proteccion_sobretension'   => [
                'nullable',
                'string',
                Rule::in(['interruptor_automatico', 'fusibles_calibrados']),
            ],
        ]);

        $validated['tiene_bateria']  = $request->boolean('tiene_bateria');
        $validated['tipos_cubierta'] = $request->input('tipos_cubierta', []);

        /* CALCULAR POTENCIA PICO */
        $modelos    = $validated['modelo_placa'];
        $cantidades = $validated['cantidad_placas'];

        $potenciaPicoTotal = 0;

        foreach ($modelos as $i => $modelo) {
            $watts    = $this->obtenerPotenciaWattsDesdeModelo($modelo);
            $cantidad = (int) ($cantidades[$i] ?? 0);

            if ($watts > 0 && $cantidad > 0) {
                $potenciaPicoTotal += $watts * $cantidad; // Wp totales
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
            'proteccion_sobretension'   => $validated['proteccion_sobretension'] ?? null,
        ]);

        // Regenerar placas (borramos y volvemos a crear)
        $boletin->placas()->delete();

        $modelosPlacaForm = $validated['modelo_placa'];
        $cantidadesForm   = $validated['cantidad_placas'];

        foreach ($modelosPlacaForm as $index => $modelo) {
            $watts = $this->obtenerPotenciaWattsDesdeModelo($modelo);

            BoletinPlaca::create([
                'boletin_id'      => $boletin->id,
                'modelo_placa'    => $modelo,
                'potencia_placa'  => $watts, // W de cada placa
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

    // REGLA: Nº registro instalación (25 si null)
    $numeroRegistro = $boletin->numero_registro ?? '25';

    // REGLA: Sección conductores
    $seccionConductores = $boletin->tension_suministro === '400V'
        ? '4 / 4 / 4 / 4'
        : '6 / 6 / 6';

    // REGLA: Protección sobreintensidades
    $proteccion = ($boletin->tipo_instalacion_electrica === 'trifasica')
        ? 'magnetotermico'
        : 'fusibles';

    // Ruta plantilla
    $templatePath = storage_path('app/plantillas/BOLETIN.pdf');

        $pdf = new \setasign\Fpdi\Fpdi();
        $pageCount = $pdf->setSourceFile($templatePath);

    // Ajustes base
    $pdf->SetFont('Helvetica', '', 6);
    $pdf->SetTextColor(0, 0, 0);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $tplId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($tplId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            if ($pageNo === 1) {

            /* ---------------------------------------------------------
             *  BLOQUE 1: CABECERA - Nº REGISTRO INSTALACIÓN
             * --------------------------------------------------------- */
            // Coordenadas base estimadas (ajustables):
            $pdf->SetXY(1, 12);
            $pdf->Write(4, $numeroRegistro);

            /* ---------------------------------------------------------
             *  BLOQUE 2: TITULAR / CLIENTE
             * --------------------------------------------------------- */

            // NOMBRE COMPLETO
            if ($cliente) {

                $nombreCompleto = trim(
                    ($cliente->nombre ?? '') . ' ' .
                    ($cliente->primer_apellido ?? '') . ' ' .
                    ($cliente->segundo_apellido ?? '')
                );

                $pdf->SetXY(50, 69.2);
                $pdf->Write(1, $nombreCompleto);

                // DNI/CIF
                $pdf->SetXY(130, 69.2);
                $pdf->Write(1, $cliente->dni_cif ?? '');

                // Dirección
                $pdf->SetXY(50, 72.2);
                $pdf->Write(4, $cliente->direccion ?? '');

                // LOCALIDAD
                $pdf->SetXY(50, 78.5);
                $pdf->Write(1, $cliente->poblacion ?? '');

                // PROVINCIA
                $pdf->SetXY(93, 78.5);
                $pdf->Write(1, $cliente->provincia ?? '');

                // CORREO
                $pdf->SetXY(110, 78);
                $pdf->Write(1, $cliente->email ?? '');

                // Telefono
                $pdf->SetXY(149, 78);
                $pdf->Write(1, $cliente->telefono ?? '');

                // CÓDIGO POSTAL2
                    $pdf->SetXY(132, 90.5);  
                    $pdf->Write(1, $cliente->codigo_postal ?? '');

                // CÓDIGO POSTAL1
                    $pdf->SetXY(130, 73.8);  
                    $pdf->Write(1, $cliente->codigo_postal ?? '');


                // DATOS INSTALACION 
                
                $direccion = trim($cliente->direccion ?? '');

                $calle  = '';
                $numero = '';

                // Partimos por la coma: "Calle Jurel, 4" → ["Calle Jurel", "4"]
                $partes = array_map('trim', explode(',', $direccion));

                if (count($partes) >= 2) {
                    $calle  = $partes[0];        
                    $numero = $partes[1];        
                } else {
                    // Si no hay coma, intentamos calle + número tipo "Calle Jurel 4"
                    if (preg_match('/^(.*?)[\s]+(\d+.*)$/', $direccion, $m)) {
                        $calle  = trim($m[1]);
                        $numero = trim($m[2]);
                    } else {
                        $calle  = $direccion;
                        $numero = '';
                    }
                }

                // Emplazamiento = solo la calle
                $pdf->SetXY(50, 86);      // coords del campo Emplazamiento
                $pdf->Write(1, $calle);

                // Número = solo el nº de la casa
                $pdf->SetXY(108, 86);     // coords del campo Número (ajusta si hace falta)
                $pdf->Write(1, $numero);


                // POBLACION 2
                $poblacion2 = $cliente->poblacion;
                $pdf->SetXY(50, 90.5);
                $pdf->Write(1, $poblacion2 ?? '');

                // PROVINCIA 2
                $provincia2 = $cliente->provincia;
                $pdf->SetXY(103, 90.5);
                $pdf->Write(1, $provincia2 ?? '');

                //texto generadores de tipo de instalación !!
                $tipo_instalacion_3 = 'c -generadores/convertidores';
                $pdf->SetXY(50, 95);
                $pdf->Write(1, $tipo_instalacion_3 ?? '');

                   //texto generadores de tipo de instalación !!
                $uso_destina = 'instalación fotovoltáica';
                $pdf->SetXY(102, 95);
                $pdf->Write(1, $uso_destina ?? '');

                //superficie en metros cuadrados !!
                $metros_cuadrados_vivienda = $boletin->metros_cuadrados_vivienda;
                $pdf->SetXY(150, 95);
                $pdf->Write(1, $metros_cuadrados_vivienda ?? '');

            }

                /* ---------------------------------------------------------
                 * BLOQUE 3: DATOS INSTALACIÓN
                 * --------------------------------------------------------- */

            // CUPS
            $pdf->SetXY(110, 98.5);
            $pdf->Write(1, $boletin->cups ?? '');

            // Tipo instalación eléctrica (mono / tri)
            if ($boletin->tipo_instalacion_electrica === 'monofasica') {
                $pdf->SetXY(64.4, 127.5);
                $pdf->Write(4, 'X');
            }
            // /* ---------------------------------------------------------
            //  *  BLOQUE 6: TIPO INSTALACIÓN (nueva / ampliación)
            //  * --------------------------------------------------------- */
            
            if ($boletin->tipo_instalacion === 'nueva') {
                $pdf->SetXY(61.3, 96.6);
                $pdf->Write(4, 'X');
             }
            
            if ($boletin->tipo_instalacion === 'ampliacion') {
                $pdf->SetXY(90, 116);
                $pdf->Write(4, 'X');
             }


            // Instalación-Potencia prevista
            $pdf->SetXY(145, 78);
            $pdf->Write(4, $boletin->potencia_factura_luz ?? '');

            $potenciaPrevistaKw = $this->calcularPotenciaPrevistaKw($boletin);

        if (!is_null($potenciaPrevistaKw)) {
            $textoPotenciaPrevista = number_format($potenciaPrevistaKw, 2, ',', '.').' kW';
            $pdf->SetXY(100, 116);
            $pdf->Write(1, $textoPotenciaPrevista);
        }


        // POTENCIA PREVISTA (kW)
            $potPrev = $boletin->potencia_factura_luz;

            if ($potPrev) {
                $potenciaPrevistaKw = floatval(str_replace(',', '.', $potPrev));
            } else {
                $potenciaPrevistaKw = 3.45;
            }

            $potenciaPrevistaKw = round($potenciaPrevistaKw, 2);

            $pdf->SetXY(130, 102);
            $pdf->Write(1, $potenciaPrevistaKw . ' kW');


            // // Potencia instalada (uso potencia pico)
            // // $pdf->SetXY(145, 85);
            // // $pdf->Write(4, $boletin->potencia_pico ?? '');

            // /* ---------------------------------------------------------
            //  *  BLOQUE 4: CASILLAS (X)
            //  * --------------------------------------------------------- */

            // if ($boletin->tipo_instalacion_electrica === 'trifasica') {
            //     $pdf->SetXY(70, 127.5);
            //     $pdf->Write(4, 'X');
            // }

            // // Tensión suministro 230 / 400
            // if ($boletin->tension_suministro === '230V') {
            //     $pdf->SetXY(50, 107);
            //     $pdf->Write(4, 'X');
            // }
            // if ($boletin->tension_suministro === '400V') {
            //     $pdf->SetXY(90, 107);
            //     $pdf->Write(4, 'X');
            // }

            // /* ---------------------------------------------------------
            //  *  BLOQUE 5: SECCIÓN CONDUCTORES
            //  * --------------------------------------------------------- */
            // $pdf->SetXY(143, 10);
            // $pdf->Write(4, $seccionConductores);

            
            // /* ---------------------------------------------------------
            //  *  BLOQUE 7: PROTECCIÓN SOBRECARGAS
            //  * --------------------------------------------------------- */
            // if ($proteccion === 'magnetotermico') {
            //     $pdf->SetXY(60, 132);
            //     $pdf->Write(4, 'X');
            // } else {
            //     $pdf->SetXY(90, 132);
            //     $pdf->Write(4, 'X');
            // }

            // /* ---------------------------------------------------------
            //  *  BLOQUE 8: INVERSORES y BATERÍAS
            //  * --------------------------------------------------------- */
            // $pdf->SetXY(32, 150);
            // $pdf->Write(4, $boletin->marca_inversor ?? '');

            // $pdf->SetXY(90, 150);
            // $pdf->Write(4, $boletin->modelo_inversor ?? '');

            // // Baterías
            // if ($boletin->tiene_bateria) {
            //     $pdf->SetXY(32, 158);
            //     $pdf->Write(4, 'Sí (' . ($boletin->numero_baterias ?? 1) . ')');
            // } else {
            //     $pdf->SetXY(32, 158);
            //     $pdf->Write(4, 'No');
            // }
        }
    }

    return $pdf->Output('I', 'BoletinOficial.pdf');
}


}

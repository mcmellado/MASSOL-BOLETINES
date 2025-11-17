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
            'potencia_pico'             => 'nullable|string|max:255',

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
            'potencia_placa'            => 'required|array|min:1',
            'potencia_placa.*'          => 'required|string|max:255',
            'cantidad_placas'           => 'required|array|min:1',
            'cantidad_placas.*'         => 'required|integer|min:1',
        ]);

        $validated['tiene_bateria'] = $request->boolean('tiene_bateria');
        $validated['tipos_cubierta'] = $request->input('tipos_cubierta', []);

        $boletin = Boletin::create([
            'cliente_id'                => $validated['cliente_id'],
            'fecha'                     => $validated['fecha'],
            'numero_registro'           => $validated['numero_registro'] ?? null,
            'cups'                      => $validated['cups'] ?? null,
            'referencia_catastral'      => $validated['referencia_catastral'] ?? null,
            'potencia_factura_luz'      => $validated['potencia_factura_luz'] ?? null,
            'metros_cuadrados_vivienda' => $validated['metros_cuadrados_vivienda'] ?? null,
            'potencia_pico'             => $validated['potencia_pico'] ?? null,

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

        $modelosPlaca   = $validated['modelo_placa'];
        $potenciasPlaca = $validated['potencia_placa'];
        $cantidades     = $validated['cantidad_placas'];

        foreach ($modelosPlaca as $index => $modelo) {
            BoletinPlaca::create([
                'boletin_id'     => $boletin->id,
                'modelo_placa'   => $modelo,
                'potencia_placa' => $potenciasPlaca[$index] ?? null,
                'cantidad_placas'=> $cantidades[$index] ?? 0,
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
            'potencia_pico'             => 'nullable|string|max:255',

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
            'potencia_placa'            => 'required|array|min:1',
            'potencia_placa.*'          => 'required|string|max:255',
            'cantidad_placas'           => 'required|array|min:1',
            'cantidad_placas.*'         => 'required|integer|min:1',
        ]);

        $validated['tiene_bateria'] = $request->boolean('tiene_bateria');
        $validated['tipos_cubierta'] = $request->input('tipos_cubierta', []);

        $boletin->update([
            'cliente_id'                => $validated['cliente_id'],
            'fecha'                     => $validated['fecha'],
            'numero_registro'           => $validated['numero_registro'] ?? null,
            'cups'                      => $validated['cups'] ?? null,
            'referencia_catastral'      => $validated['referencia_catastral'] ?? null,
            'potencia_factura_luz'      => $validated['potencia_factura_luz'] ?? null,
            'metros_cuadrados_vivienda' => $validated['metros_cuadrados_vivienda'] ?? null,
            'potencia_pico'             => $validated['potencia_pico'] ?? null,

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

        $boletin->placas()->delete();

        $modelosPlaca   = $validated['modelo_placa'];
        $potenciasPlaca = $validated['potencia_placa'];
        $cantidades     = $validated['cantidad_placas'];

        foreach ($modelosPlaca as $index => $modelo) {
            BoletinPlaca::create([
                'boletin_id'     => $boletin->id,
                'modelo_placa'   => $modelo,
                'potencia_placa' => $potenciasPlaca[$index] ?? null,
                'cantidad_placas'=> $cantidades[$index] ?? 0,
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

    public function pdfOficial(Boletin $boletin)
{
    $boletin->load('cliente', 'placas');

    $cliente = $boletin->cliente;

    // 1) Nº de registro de instalación: SIEMPRE 25
    $numeroRegistroInstalacion = 25;

    // 2) Si tensión = 400V → sección fase/neutro 4 / 4 / 4 / 4
    $seccionFaseNeutro = null;
    if ($boletin->tension_suministro === '400V') {
        $seccionFaseNeutro = '4 / 4 / 4 / 4';
    }

    // 3) Protecciones contra sobreintensidades: solo una
    if ($boletin->tipo_instalacion_electrica === 'trifasica') {
        $proteccionSobreintensidades = 'magnetotermico';
    } else {
        $proteccionSobreintensidades = 'fusibles';
    }

    $templatePath = storage_path('app/plantillas/BOLETIN.pdf');

    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($templatePath);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $tplId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($tplId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId);

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255); // para tapar texto viejo

        if ($pageNo === 1) {

            // ---------- Nº REGISTRO INSTALACIÓN (25) ----------
            $pdf->Rect(140, 20, 50, 6, 'F');   // tapa el antiguo
            $pdf->SetXY(140, 20);
            $pdf->Write(4, $numeroRegistroInstalacion);

            // ---------- DATOS DE CLIENTE ----------
            if ($cliente) {
                $nombreCompleto = trim(
                    ($cliente->nombre ?? '') . ' ' .
                    ($cliente->primer_apellido ?? '') . ' ' .
                    ($cliente->segundo_apellido ?? '')
                );

                // Nombre
                $pdf->Rect(30, 40, 100, 5, 'F');
                $pdf->SetXY(30, 40);
                $pdf->Write(4, $nombreCompleto);

                // DNI/CIF
                $pdf->Rect(140, 40, 40, 5, 'F');
                $pdf->SetXY(140, 40);
                $pdf->Write(4, $cliente->dni_cif ?? '');

                // Dirección
                $pdf->Rect(30, 46, 150, 5, 'F');
                $pdf->SetXY(30, 46);
                $pdf->Write(4, $cliente->direccion ?? '');

                // Población + provincia
                $pdf->Rect(30, 52, 150, 5, 'F');
                $pdf->SetXY(30, 52);
                $pdf->Write(4,
                    trim(($cliente->poblacion ?? '') . ' - ' . ($cliente->provincia ?? ''))
                );
            }

            // ---------- CUPS ----------
            $pdf->Rect(30, 60, 80, 5, 'F');
            $pdf->SetXY(30, 60);
            $pdf->Write(4, $boletin->cups ?? '');

            // ---------- POTENCIA FACTURA LUZ ----------
            $pdf->Rect(120, 60, 40, 5, 'F');
            $pdf->SetXY(120, 60);
            $pdf->Write(4, $boletin->potencia_factura_luz ?? '');

            // ---------- POTENCIA PICO ----------
            $pdf->Rect(120, 66, 40, 5, 'F');
            $pdf->SetXY(120, 66);
            $pdf->Write(4, $boletin->potencia_pico ?? '');

            // ---------- INSTALACIÓN MONOFÁSICA / TRIFÁSICA ----------
            // (mueve X/Y hasta que cuadre con tus casillas)
            if ($boletin->tipo_instalacion_electrica === 'monofasica') {
                $pdf->Rect(60, 75, 4, 4, 'F'); // casilla mono
                $pdf->SetXY(60, 75);
                $pdf->Write(4, 'X');
            } elseif ($boletin->tipo_instalacion_electrica === 'trifasica') {
                $pdf->Rect(90, 75, 4, 4, 'F'); // casilla tri
                $pdf->SetXY(90, 75);
                $pdf->Write(4, 'X');
            }

            // ---------- TENSIÓN SUMINISTRO 230 / 400 ----------
            if ($boletin->tension_suministro === '230V') {
                $pdf->Rect(60, 82, 4, 4, 'F'); // 230V
                $pdf->SetXY(60, 82);
                $pdf->Write(4, 'X');
            } elseif ($boletin->tension_suministro === '400V') {
                $pdf->Rect(90, 82, 4, 4, 'F'); // 400V
                $pdf->SetXY(90, 82);
                $pdf->Write(4, 'X');
            }

            // ---------- SECCIÓN FASE / NEUTRO (si 400V) ----------
            if ($seccionFaseNeutro) {
                $pdf->Rect(130, 82, 40, 5, 'F');
                $pdf->SetXY(130, 82);
                $pdf->Write(4, $seccionFaseNeutro);
            }

            // ---------- TIPO INSTALACIÓN NUEVA / AMPLIACIÓN ----------
            if ($boletin->tipo_instalacion === 'nueva') {
                $pdf->Rect(60, 90, 4, 4, 'F');
                $pdf->SetXY(60, 90);
                $pdf->Write(4, 'X');
            } elseif ($boletin->tipo_instalacion === 'ampliacion') {
                $pdf->Rect(90, 90, 4, 4, 'F');
                $pdf->SetXY(90, 90);
                $pdf->Write(4, 'X');
            }

            // ---------- PROTECCIONES SOBREINTENSIDADES ----------
            // limpiamos las dos casillas y marcamos solo la correcta
            $pdf->Rect(60, 100, 4, 4, 'F'); // magnetotérmico
            $pdf->Rect(90, 100, 4, 4, 'F'); // fusibles

            if ($proteccionSobreintensidades === 'magnetotermico') {
                $pdf->SetXY(60, 100);
                $pdf->Write(4, 'X');
            } elseif ($proteccionSobreintensidades === 'fusibles') {
                $pdf->SetXY(90, 100);
                $pdf->Write(4, 'X');
            }

            // A partir de aquí puedes continuar añadiendo más campos (inversor, baterías, placas...)
        }

        // Página 2 la dejamos tal cual
    }

    return response($pdf->Output('S'), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="boletin_'.$boletin->id.'_oficial.pdf"',
    ]);
}




}

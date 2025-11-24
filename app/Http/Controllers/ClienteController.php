<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::orderBy('id', 'desc')->paginate(10);

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'nombre'           => 'required|string|max:255',
                'primer_apellido'  => 'required|string|max:255',
                'segundo_apellido' => 'nullable|string|max:255',
                'dni_cif'          => 'required|string|max:50',
                'email'            => 'nullable|email|max:255',  // 👈 YA NO ES REQUERIDO
                'telefono'         => 'required|string|max:50',
                'direccion'        => 'required|string|max:255',
                'poblacion'        => 'required|string|max:255',
                'provincia'        => 'required|string|max:255',
                'codigo_postal'    => 'required|string|max:20',
            ],
            [
                // REQUIRED
                'nombre.required'           => 'El nombre es obligatorio.',
                'primer_apellido.required'  => 'El primer apellido es obligatorio.',
                'dni_cif.required'          => 'El DNI/CIF es obligatorio.',
                'telefono.required'         => 'El teléfono es obligatorio.',
                'direccion.required'        => 'La dirección es obligatoria.',
                'poblacion.required'        => 'La población es obligatoria.',
                'provincia.required'        => 'La provincia es obligatoria.',
                'codigo_postal.required'    => 'El código postal es obligatorio.',

                // STRING
                'nombre.string'             => 'El nombre debe ser un texto válido.',
                'primer_apellido.string'    => 'El primer apellido debe ser un texto válido.',
                'segundo_apellido.string'   => 'El segundo apellido debe ser un texto válido.',
                'dni_cif.string'            => 'El DNI/CIF debe ser un texto válido.',
                'telefono.string'           => 'El teléfono debe ser un texto válido.',
                'direccion.string'          => 'La dirección debe ser un texto válido.',
                'poblacion.string'          => 'La población debe ser un texto válido.',
                'provincia.string'          => 'La provincia debe ser un texto válido.',
                'codigo_postal.string'      => 'El código postal debe ser un texto válido.',

                // EMAIL (no requerido, pero si se rellena debe ser válido)
                'email.email'               => 'El email debe ser una dirección de correo válida.',
                'email.max'                 => 'El email no puede superar :max caracteres.',

                // MAX
                'nombre.max'                => 'El nombre no puede superar :max caracteres.',
                'primer_apellido.max'       => 'El primer apellido no puede superar :max caracteres.',
                'segundo_apellido.max'      => 'El segundo apellido no puede superar :max caracteres.',
                'dni_cif.max'               => 'El DNI/CIF no puede superar :max caracteres.',
                'telefono.max'              => 'El teléfono no puede superar :max caracteres.',
                'direccion.max'             => 'La dirección no puede superar :max caracteres.',
                'poblacion.max'             => 'La población no puede superar :max caracteres.',
                'provincia.max'             => 'La provincia no puede superar :max caracteres.',
                'codigo_postal.max'         => 'El código postal no puede superar :max caracteres.',
            ]
        );

        $cliente = Cliente::create($validated);

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Cliente $cliente)
    {
        $boletines = $cliente->boletines()
            ->orderBy('fecha', 'desc')
            ->paginate(10);

        return view('clientes.show', compact('cliente', 'boletines'));
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate(
            [
                'nombre'           => 'required|string|max:255',
                'primer_apellido'  => 'required|string|max:255',
                'segundo_apellido' => 'nullable|string|max:255',
                'dni_cif'          => 'required|string|max:50',
                'email'            => 'nullable|email|max:255',  
                'telefono'         => 'required|string|max:50',
                'direccion'        => 'required|string|max:255',
                'poblacion'        => 'required|string|max:255',
                'provincia'        => 'required|string|max:255',
                'codigo_postal'    => 'required|string|max:20',
            ],
            [
              
                'nombre.required'           => 'El nombre es obligatorio.',
                'primer_apellido.required'  => 'El primer apellido es obligatorio.',
                'dni_cif.required'          => 'El DNI/CIF es obligatorio.',
                'telefono.required'         => 'El teléfono es obligatorio.',
                'direccion.required'        => 'La dirección es obligatoria.',
                'poblacion.required'        => 'La población es obligatoria.',
                'provincia.required'        => 'La provincia es obligatoria.',
                'codigo_postal.required'    => 'El código postal es obligatorio.',

           
                'nombre.string'             => 'El nombre debe ser un texto válido.',
                'primer_apellido.string'    => 'El primer apellido debe ser un texto válido.',
                'segundo_apellido.string'   => 'El segundo apellido debe ser un texto válido.',
                'dni_cif.string'            => 'El DNI/CIF debe ser un texto válido.',
                'telefono.string'           => 'El teléfono debe ser un texto válido.',
                'direccion.string'          => 'La dirección debe ser un texto válido.',
                'poblacion.string'          => 'La población debe ser un texto válido.',
                'provincia.string'          => 'La provincia debe ser un texto válido.',
                'codigo_postal.string'      => 'El código postal debe ser un texto válido.',

        
                'email.email'               => 'El email debe ser una dirección de correo válida.',
                'email.max'                 => 'El email no puede superar :max caracteres.',

                'nombre.max'                => 'El nombre no puede superar :max caracteres.',
                'primer_apellido.max'       => 'El primer apellido no puede superar :max caracteres.',
                'segundo_apellido.max'      => 'El segundo apellido no puede superar :max caracteres.',
                'dni_cif.max'               => 'El DNI/CIF no puede superar :max caracteres.',
                'telefono.max'              => 'El teléfono no puede superar :max caracteres.',
                'direccion.max'             => 'La dirección no puede superar :max caracteres.',
                'poblacion.max'             => 'La población no puede superar :max caracteres.',
                'provincia.max'             => 'La provincia no puede superar :max caracteres.',
                'codigo_postal.max'         => 'El código postal no puede superar :max caracteres.',
            ]
        );

        $cliente->update($validated);

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}

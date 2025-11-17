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
        $validated = $request->validate([
            'nombre'           => 'required|string|max:255',
            'primer_apellido'  => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'dni_cif'          => 'required|string|max:50',
            'email'            => 'required|email|max:255',
            'telefono'         => 'required|string|max:50',
            'direccion'        => 'required|string|max:255',
            'poblacion'        => 'required|string|max:255',
            'provincia'        => 'required|string|max:255',
        ]);

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
        $validated = $request->validate([
            'nombre'           => 'required|string|max:255',
            'primer_apellido'  => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'dni_cif'          => 'required|string|max:50',
            'email'            => 'required|email|max:255',
            'telefono'         => 'required|string|max:50',
            'direccion'        => 'required|string|max:255',
            'poblacion'        => 'required|string|max:255',
            'provincia'        => 'required|string|max:255',
        ]);

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

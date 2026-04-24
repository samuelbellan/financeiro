<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriasController extends Controller
{
    public function index()
    {
        $categorias = Categoria::where('user_id', Auth::id())
            ->with('subcategorias')
            ->orderBy('nome')
            ->get();

        return view('financas.categorias', compact('categorias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:receita,despesa',
        ]);

        $validated['user_id'] = Auth::id();
        Categoria::create($validated);

        return redirect()->back()->with('success', 'Categoria criada com sucesso!');
    }

    public function storeSub(Request $request)
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nome' => 'required|string|max:255',
        ]);

        Subcategoria::create($validated);

        return redirect()->back()->with('success', 'Subcategoria criada com sucesso!');
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->user_id !== Auth::id()) abort(403);
        $categoria->delete();
        return redirect()->back()->with('success', 'Categoria excluída!');
    }

    public function destroySub(Subcategoria $subcategoria)
    {
        if ($subcategoria->categoria->user_id !== Auth::id()) abort(403);
        $subcategoria->delete();
        return redirect()->back()->with('success', 'Subcategoria excluída!');
    }
}

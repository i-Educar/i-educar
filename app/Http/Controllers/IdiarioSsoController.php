<?php

namespace App\Http\Controllers;

use App\Services\IdiarioSsoService;
use App\Support\IdiarioPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class IdiarioSsoController extends Controller
{
    public function entrar(): RedirectResponse
    {
        if (! $this->podeAcessarIdiario()) {
            return redirect('/painel')->with('erro', 'Seu perfil não tem permissão para o i-Diário.');
        }

        $url = IdiarioSsoService::urlEntrada(Auth::user());
        if ($url === null) {
            return redirect('/painel')->with('erro', 'i-Diário não configurado (IDIARIO_URL).');
        }

        return redirect()->away($url);
    }

    public function abrir(): RedirectResponse
    {
        if (! $this->podeAcessarIdiario()) {
            return redirect('/painel')->with('erro', 'Seu perfil não tem permissão para o i-Diário.');
        }

        $base = IdiarioPortal::url();
        if ($base === null) {
            return redirect('/painel')->with('erro', 'i-Diário não configurado.');
        }

        return redirect()->away($base);
    }

    private function podeAcessarIdiario(): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }
        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::class)) {
            return \iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::usuarioPodeAcessarModulo($user, 'idiario');
        }

        return true;
    }
}

<?php

/**
 * CUSTOMIZAÇÃO Serventec / Laguna (PE-77)
 * Índice do módulo Escola — painel minimalista no lugar da área em branco do legado.
 *
 * @see \App\Support\LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Escola', $this->processoAp, [
            'descricao' => 'Cadastros escolares, turmas, matrículas, movimentações e ferramentas pedagógicas liberadas para o seu perfil.',
            'icone' => 'fa-university',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Escola';
        $this->processoAp = 55;
    }
};

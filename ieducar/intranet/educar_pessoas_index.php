<?php

/**
 * CUSTOMIZAÇÃO Serventec / Laguna (PE-77)
 * Índice do módulo Pessoas — painel minimalista no lugar da área em branco do legado.
 *
 * @see \App\Support\LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Pessoas', $this->processoAp, [
            'descricao' => 'Cadastros de pessoas físicas e jurídicas, tipologias e ferramentas de unificação disponíveis ao seu perfil.',
            'icone' => 'fa-users',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Pessoas';
        $this->processoAp = 7;
    }
};

<?php

/**
 * CUSTOMIZAÇÃO Serventec / Laguna (PE-77)
 * Índice do módulo Servidores — painel minimalista no lugar da área em branco do legado.
 *
 * @see \App\Support\LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Servidores', $this->processoAp, [
            'descricao' => 'Cadastro funcional, alocações e ferramentas de RH escolar liberadas para o seu perfil.',
            'icone' => 'fa-id-badge',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Servidores';
        $this->processoAp = 71;
    }
};

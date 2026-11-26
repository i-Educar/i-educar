<?php

/**
 * CUSTOMIZAÇÃO Serventec / Laguna (PE-77)
 * Índice do módulo Configurações — painel minimalista no lugar da área em branco do legado.
 *
 * @see \App\Support\LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Configurações', $this->processoAp, [
            'descricao' => 'Parâmetros do sistema, tipos de usuário, menus e ferramentas administrativas disponíveis ao seu perfil.',
            'icone' => 'fa-cogs',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Configurações';
        $this->processoAp = 25;
    }
};

<?php

/**
 * CUSTOMIZAÇÕES BURITI
 * Índice do módulo Configurações — painel minimalista no lugar da área em branco do legado.
 *
 * @see LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Configurações', $this->processoAp, [
            'descricao' => 'Parâmetros e permissões do sistema. Use o submenu para Configurações, Permissões, Módulos e Ferramentas.',
            'icone' => 'fa-cogs',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Configurações';
        $this->processoAp = 25;
    }
};

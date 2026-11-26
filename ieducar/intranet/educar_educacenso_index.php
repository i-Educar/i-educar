<?php

/**
 * CUSTOMIZAÇÕES BURITI
 * Índice do módulo Educacenso — painel minimalista no lugar da área em branco do legado.
 *
 * @see \App\Support\LegacyModuleIndexPanel
 */

use App\Support\LegacyModuleIndexPanel;

return new class
{
    public function RenderHTML()
    {
        return LegacyModuleIndexPanel::render('Educacenso', $this->processoAp, [
            'descricao' => 'Rotinas do Censo Escolar. As exportações e importações ficam no submenu deste módulo.',
            'icone' => 'fa-cloud-upload',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Educacenso';
        $this->processoAp = 70;
    }
};

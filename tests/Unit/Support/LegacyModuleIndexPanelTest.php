<?php

namespace Tests\Unit\Support;

use App\Support\LegacyModuleIndexPanel;
use Tests\TestCase;

class LegacyModuleIndexPanelTest extends TestCase
{
    public function test_constantes_de_customizacao_estao_claras(): void
    {
        $this->assertSame('customizacoes-buriti-module-index', LegacyModuleIndexPanel::CUSTOM_ID);
        $this->assertSame('CUSTOMIZAÇÕES BURITI', LegacyModuleIndexPanel::CUSTOM_LABEL);
    }

    public function test_render_sem_usuario_nao_quebra_e_orienta_submenu(): void
    {
        $html = LegacyModuleIndexPanel::render('Pessoas', 7, [
            'descricao' => 'Teste unitário',
            'icone' => 'fa-users',
        ]);

        $this->assertStringContainsString('svc-module-index', $html);
        $this->assertStringContainsString(LegacyModuleIndexPanel::CUSTOM_ID, $html);
        $this->assertStringContainsString('Pessoas', $html);
        $this->assertStringContainsString('CUSTOMIZAÇÕES BURITI', $html);
        $this->assertStringNotContainsString('não está liberada', $html);
        $this->assertStringNotContainsString('Tipos de usuário', $html);
        $this->assertStringNotContainsString('Nenhuma área', $html);
    }
}

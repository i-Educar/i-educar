# Customização — Painel de índice de módulo (legado)

| Campo | Valor |
| --- | --- |
| **Origem** | Serventec / Laguna PE-77 |
| **Upstream** | Portabilis i-Educar — **não** existe lá; manter marcado em merges |
| **ID** | `serventec-laguna-module-index` |
| **Classe** | `App\Support\LegacyModuleIndexPanel` |
| **View** | `resources/views/legacy/module-index-panel.blade.php` |

## Problema

Telas `ieducar/intranet/educar_*_index.php` (e equivalentes) renderizavam uma `<table>` vazia.
Com skins Buriti/Laguna, a área abaixo do submenu ficava um bloco branco sem conteúdo.

## Solução

Painel minimalista com:

1. Identificação clara de **customização Serventec · Laguna**
2. Nome e descrição do módulo
3. Perfil do usuário logado
4. Atalhos **filtrados** pelo menu já permitido ao tipo de usuário (`MenuCacheService` + `Gate::view`)

## Arquivos tocados (core)

- `app/Support/LegacyModuleIndexPanel.php`
- `resources/views/legacy/module-index-panel.blade.php`
- `ieducar/intranet/educar_pessoas_index.php`
- `ieducar/intranet/educar_servidores_index.php`
- `ieducar/intranet/educar_configuracoes_index.php`
- `ieducar/intranet/educar_educacenso_index.php`
- `ieducar/intranet/educar_enderecamento_index.php`
- `ieducar/intranet/educar_index.php` (Escola)
- Pacote transporte: `educar_transporte_escolar_index.php`

## Merge / fork

Ao puxar upstream Portabilis, **não descartar** estes arquivos sem revisar.
Busca rápida: `serventec-laguna-module-index` ou `LegacyModuleIndexPanel`.

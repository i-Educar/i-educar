<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ $municipio }}</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    {{-- Caminho raiz evita APP_URL sem porta (:8000) quebrar ícones/CSS --}}
    <link rel="stylesheet" href="/intranet/styles/font-awesome.css">
    <script>
    (function () {
        try {
            var m = localStorage.getItem('buriti.tema.modo');
            if (m !== 'claro' && m !== 'escuro') m = 'claro';
            document.documentElement.setAttribute('data-buriti-modo', m);
        } catch (e) {
            document.documentElement.setAttribute('data-buriti-modo', 'claro');
        }
    })();
    </script>
    @php
        $temaTokensDash = public_path('vendor/buriti-tema/tokens.css');
        $temaTokensDashV = is_file($temaTokensDash) ? (string) filemtime($temaTokensDash) : '11';
    @endphp
    <link rel="stylesheet" href="/vendor/buriti-tema/tokens.css?v={{ $temaTokensDashV }}">
    <style>
        :root {
            --cor: #0a3d38;
            --destaque: #0f6b5c;
            --acento: #1bb598;
            --clara: #e2f0ec;
            --fundo: #eef4f2;
            --tinta: #14241f;
            --suave: #5a716a;
            --linha: #cfdfd8;
            --linha-forte: #b7c9c1;
            --superficie: #ffffff;
            --superficie-alta: #ffffff;
            --ok: #146c42;
            --ok-fundo: #e4f5eb;
            --alerta: #a85f00;
            --alerta-fundo: #fff5e6;
            --erro: #8f2424;
            --erro-fundo: #fceceb;
            --info: #0f6b5c;
            --info-fundo: #e2f0ec;
            --topo-h: 64px;
            --safe-b: env(safe-area-inset-bottom, 0px);
            --sombra: 0 12px 28px rgba(10, 61, 56, .07);
            --sombra-leve: 0 1px 2px rgba(10, 61, 56, .05);
            --fonte: "Manrope", "Segoe UI", sans-serif;
            --topo-fundo: color-mix(in srgb, var(--superficie) 94%, transparent);
            --mix-branco: #ffffff;
            --link: var(--destaque);
        }
        html[data-buriti-modo="escuro"] {
            --cor: #7ecfbe;
            --destaque: #4ec4ae;
            --acento: #7ad3de;
            --clara: #182420;
            --fundo: #0c1210;
            --tinta: #e8f2ee;
            --suave: #9eb0a8;
            --linha: #2a3833;
            --linha-forte: #3d4e47;
            --superficie: #141c1a;
            --superficie-alta: #1b2623;
            --ok: #3dbaa4;
            --alerta: #d4a24a;
            --erro: #e0938c;
            --info: #4ec4ae;
            --ok-fundo: #0f2a22;
            --alerta-fundo: #2a2210;
            --erro-fundo: #2a1614;
            --info-fundo: #0f2824;
            --sombra: 0 12px 32px rgba(0, 0, 0, .42);
            --sombra-leve: 0 1px 2px rgba(0, 0, 0, .35);
            --topo-fundo: color-mix(in srgb, #141c1a 94%, transparent);
            --link: #7ad3de;
            --mix-branco: #141c1a;
        }
        * { box-sizing: border-box; }
        html { height: 100%; scroll-padding-top: calc(var(--topo-h) + 8px); }
        body {
            margin: 0;
            min-height: 100%;
            font-family: var(--fonte);
            font-size: 14px;
            color: var(--tinta);
            background:
                radial-gradient(1000px 380px at 4% -10%, color-mix(in srgb, var(--clara) 85%, transparent), transparent 58%),
                radial-gradient(700px 280px at 96% 0%, color-mix(in srgb, var(--acento) 18%, transparent), transparent 50%),
                linear-gradient(180deg, color-mix(in srgb, var(--clara) 55%, var(--fundo)) 0%, var(--fundo) 45%, color-mix(in srgb, var(--fundo) 80%, #000) 100%);
            -webkit-font-smoothing: antialiased;
        }
        .shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100dvh;
        }
        .topo {
            position: sticky; top: 0; z-index: 30;
            height: var(--topo-h);
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 0 18px 0 20px;
            background: var(--topo-fundo, color-mix(in srgb, #fff 94%, transparent));
            backdrop-filter: blur(12px);
            border-bottom: 1px solid color-mix(in srgb, var(--linha) 80%, transparent);
            box-shadow: var(--sombra-leve, 0 1px 0 rgba(255,255,255,.7));
        }
        .marca { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--tinta); min-width: 0; }
        .marca .selo {
            width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center;
            background: linear-gradient(145deg, var(--destaque), var(--cor)); color: #fff; flex: none;
            box-shadow: 0 8px 18px color-mix(in srgb, var(--cor) 28%, transparent);
        }
        .marca strong { display: block; font-size: .95rem; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .marca small { display: block; color: var(--suave); font-weight: 600; font-size: .72rem; letter-spacing: .02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 42vw; }
        .topo-acoes { display: flex; align-items: center; gap: 8px; flex: none; }
        .acao-ico {
            position: relative;
            display: inline-flex; align-items: center; justify-content: center;
            width: 42px; height: 42px; border-radius: 12px;
            border: 1px solid color-mix(in srgb, var(--destaque) 18%, var(--mix-branco)); background: var(--superficie); color: var(--destaque);
            text-decoration: none; cursor: pointer; padding: 0; font: inherit;
            transition: background .15s ease, transform .15s ease, border-color .15s ease;
        }
        .acao-ico:hover, .acao-ico:focus-visible, .acao-ico[aria-expanded="true"], .acao-ico[aria-pressed="true"] {
            background: var(--info-fundo); border-color: color-mix(in srgb, var(--destaque) 35%, var(--mix-branco)); outline: none;
        }
        .acao-ico img { width: 26px; height: 26px; display: block; border-radius: 6px; }
        .acao-ico i { font-size: 16px; }
        .acao-ico .badge-dot {
            position: absolute; top: 5px; right: 5px;
            min-width: 16px; height: 16px; padding: 0 4px;
            border-radius: 999px; background: var(--erro); color: #fff;
            font-size: .62rem; font-weight: 800; line-height: 16px; text-align: center;
        }
        .acao-ico .badge-dot[hidden] { display: none !important; }
        .acao-ico.sair { color: var(--erro); border-color: color-mix(in srgb, var(--erro) 22%, var(--mix-branco)); }
        .acao-ico.sair:hover { background: var(--erro-fundo); }
        .notif-drop { position: relative; }
        .notif-painel {
            display: none; position: absolute; top: calc(100% + 6px); right: 0; z-index: 60;
            width: min(360px, calc(100vw - 24px)); max-height: min(72vh, 520px);
            background: var(--superficie); border: 1px solid var(--linha); border-radius: 12px;
            box-shadow: var(--sombra); overflow: hidden;
        }
        .notif-drop.aberto .notif-painel { display: flex; flex-direction: column; }
        .notif-barra {
            display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px;
            padding: 10px 12px; border-bottom: 1px solid var(--linha); background: var(--superficie);
        }
        .notif-barra strong { margin-right: auto; font-size: .88rem; }
        .notif-barra button {
            border: 0; background: 0; color: var(--destaque); font: inherit; font-size: .75rem;
            font-weight: 700; cursor: pointer; padding: 0;
        }
        .notif-lista { max-height: min(52vh, 380px); overflow: auto; padding: 6px; }
        .notif-item {
            display: grid; gap: 4px; padding: 10px 12px; border-radius: 10px;
            text-decoration: none; color: var(--tinta); border: 1px solid transparent;
        }
        .notif-item:hover { background: var(--clara); }
        .notif-item.unread {
            background: color-mix(in srgb, var(--info-fundo) 80%, var(--mix-branco));
            border-color: color-mix(in srgb, var(--erro) 18%, var(--linha));
        }
        .notif-item p { margin: 0; font-size: .82rem; font-weight: 600; line-height: 1.35; }
        .notif-item .date-notification { color: var(--suave); font-size: .72rem; font-weight: 500; }
        .notif-vazio { margin: 16px 12px; color: var(--suave); font-size: .84rem; text-align: center; }
        .notif-rodape {
            border-top: 1px solid var(--linha); padding: 10px 12px; text-align: center; background: var(--superficie);
        }
        .notif-rodape a {
            display: inline-flex; align-items: center; gap: 6px; font-weight: 750; font-size: .84rem;
            color: var(--destaque); text-decoration: none;
        }
        .user-drop { position: relative; }
        .user-btn {
            display: inline-flex; align-items: center; gap: 8px;
            height: 42px; padding: 0 10px 0 4px; border-radius: 12px;
            border: 1px solid var(--linha); background: var(--superficie); cursor: pointer; font: inherit; color: var(--tinta);
        }
        .user-btn .avatar {
            width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center;
            background: linear-gradient(145deg, var(--acento), var(--cor)); color: #fff;
            font-size: .72rem; font-weight: 800; flex: none; overflow: hidden;
        }
        .user-btn .avatar img {
            width: 100%; height: 100%; object-fit: cover; display: block;
        }
        .user-btn .meta { text-align: left; line-height: 1.15; max-width: 150px; }
        .user-btn .meta strong { display: block; font-size: .78rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-btn .meta small { display: block; color: var(--suave); font-size: .68rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-btn .fa-chevron-down { color: var(--suave); font-size: 11px; }
        .user-menu {
            display: none; position: absolute; top: calc(100% + 8px); right: 0;
            min-width: 248px; background: var(--superficie); border: 1px solid var(--linha);
            border-radius: 14px; box-shadow: var(--sombra); z-index: 40; padding: 6px;
        }
        .user-drop.aberto .user-menu { display: block; }
        .user-menu a, .user-menu button {
            display: flex; align-items: center; gap: 10px; width: 100%;
            padding: 10px 12px; border: 0; border-radius: 10px; background: transparent;
            color: var(--tinta); text-decoration: none; font: inherit; font-weight: 600; cursor: pointer; text-align: left;
        }
        .user-menu a:hover, .user-menu button:hover { background: var(--clara); }
        .user-menu i { width: 16px; text-align: center; color: var(--suave); }
        .user-menu .sep { height: 1px; background: var(--linha); margin: 4px 6px; }
        main.miolo {
            flex: 1 0 auto;
            width: 100%;
            padding: 14px clamp(12px, 1.6vw, 22px) calc(72px + var(--safe-b));
        }
        .miolo-inner {
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 100%;
            margin: 0;
        }
        .hero-painel {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 18px;
            padding: 14px 18px;
            border-radius: 14px;
            border: 1px solid color-mix(in srgb, var(--destaque) 18%, var(--linha));
            background:
                linear-gradient(125deg, color-mix(in srgb, var(--clara) 70%, var(--mix-branco)) 0%, var(--superficie) 48%, color-mix(in srgb, var(--clara) 40%, var(--mix-branco)) 100%);
            border-left: 4px solid var(--cor);
            box-shadow: var(--sombra);
        }
        .hero-painel > div:first-child { flex: 1 1 280px; min-width: 0; }
        .hero-painel .eyebrow {
            margin: 0 0 2px; font-size: .68rem; font-weight: 800; letter-spacing: .12em;
            text-transform: uppercase; color: var(--destaque);
        }
        .hero-painel h1 { margin: 0; font-size: clamp(1.2rem, 2vw, 1.55rem); font-weight: 800; letter-spacing: -.03em; }
        .hero-painel .lead { margin: 4px 0 0; color: var(--suave); max-width: none; line-height: 1.4; font-size: .88rem; }
        .hero-meta { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; flex: 0 1 auto; }
        .chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700;
            background: var(--superficie); border: 1px solid var(--linha); color: var(--suave);
        }
        .chip i { color: var(--destaque); }
        .chip.papel { background: var(--info-fundo); border-color: color-mix(in srgb, var(--info) 25%, var(--mix-branco)); color: var(--info); }
        .bloco {
            background: var(--superficie); border: 1px solid var(--linha); border-radius: 14px;
            padding: 14px 16px; border-top: 3px solid var(--cor);
            box-shadow: var(--sombra);
            display: block;
            width: 100%;
        }
        .bloco h2 {
            margin: 0 0 2px; font-size: .98rem; font-weight: 800; letter-spacing: -.01em;
            display: flex; align-items: center; gap: 8px;
        }
        .bloco .sub { margin: 0 0 10px; color: var(--suave); font-size: .8rem; }
        .bloco .corpo { display: block; }
        .kpi-grupos-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            width: 100%;
            align-items: stretch;
        }
        .kpi-grupo {
            margin: 0;
            padding: 10px;
            border: 1px solid color-mix(in srgb, var(--linha) 90%, var(--mix-branco));
            border-radius: 12px;
            background: linear-gradient(180deg, color-mix(in srgb, var(--clara) 55%, var(--mix-branco)) 0%, var(--superficie) 100%);
            min-width: 0;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .kpi-grupo .kpis { flex: 1 1 auto; }
        .kpi-grupo-cab {
            display: flex; align-items: center; gap: 8px;
            margin: 0 0 8px; color: var(--suave);
            font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
        }
        .kpi-grupo-cab i { color: var(--destaque); }
        .lista { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .lista li {
            display: grid; grid-template-columns: 40px 1fr auto; gap: 10px; align-items: start;
            padding: 11px; border-radius: 12px; border: 1px solid var(--linha);
            background: linear-gradient(180deg, color-mix(in srgb, var(--clara) 35%, var(--mix-branco)), var(--superficie));
        }
        .lista .ico {
            width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center;
            background: var(--clara); color: var(--cor);
        }
        .lista strong { display: block; font-size: .9rem; font-weight: 700; }
        .lista small { color: var(--suave); line-height: 1.4; }
        .badge {
            display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px;
            font-size: .7rem; font-weight: 800; letter-spacing: .02em;
        }
        .badge.ok { background: var(--ok-fundo); color: var(--ok); }
        .badge.alerta { background: var(--alerta-fundo); color: var(--alerta); }
        .badge.erro { background: var(--erro-fundo); color: var(--erro); }
        .badge.info { background: var(--info-fundo); color: var(--info); }
        .atencao.alerta { border-left: 3px solid var(--alerta); }
        .atencao.erro { border-left: 3px solid var(--erro); }
        .atencao.info { border-left: 3px solid var(--info); }
        .atencao.ok { border-left: 3px solid var(--ok); }
        .botao {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--cor); color: #fff; border: 0; border-radius: 10px;
            padding: 8px 12px; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer;
        }
        .botao.claro { background: var(--superficie); color: var(--cor); border: 1px solid color-mix(in srgb, var(--cor) 30%, var(--linha)); }
        .atalhos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 8px;
            width: 100%;
        }
        .atalhos a {
            display: grid; grid-template-columns: 40px 1fr; gap: 10px; align-items: center;
            min-height: 68px; padding: 10px 12px; border-radius: 12px; text-decoration: none; color: var(--tinta);
            border: 1px solid var(--linha);
            background: linear-gradient(180deg, color-mix(in srgb, var(--clara) 50%, var(--mix-branco)), var(--superficie));
            transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .atalhos a:hover { transform: translateY(-2px); border-color: var(--destaque); box-shadow: var(--sombra); }
        .atalhos .ico {
            width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center;
            background: linear-gradient(145deg, var(--destaque), var(--cor)); color: #fff;
            box-shadow: 0 8px 16px color-mix(in srgb, var(--cor) 22%, transparent);
        }
        .atalhos strong { display: block; font-size: .86rem; font-weight: 750; }
        .atalhos small { display: block; color: var(--suave); margin-top: 2px; font-weight: 500; line-height: 1.3; font-size: .75rem; }
        .vazio { margin: 0; color: var(--suave); }
        .kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 8px;
            width: 100%;
            align-items: stretch;
        }
        .kpi {
            --tom: var(--cor);
            position: relative; overflow: visible;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            min-height: 96px;
            text-decoration: none; color: inherit;
            background: linear-gradient(180deg, color-mix(in srgb, var(--tom) 9%, var(--mix-branco)), var(--superficie) 72%);
            border: 1px solid color-mix(in srgb, var(--tom) 26%, var(--linha));
            border-top: 3px solid var(--tom);
            border-radius: 12px; padding: 10px 12px;
            box-shadow: var(--sombra-leve);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .kpi::after {
            content: ""; position: absolute; right: -14px; top: -14px; width: 52px; height: 52px;
            border-radius: 50%; background: color-mix(in srgb, var(--tom) 12%, transparent);
            pointer-events: none;
        }
        .kpi:has(.kpi-corpo:hover), .kpi:hover { transform: translateY(-2px); box-shadow: var(--sombra); }
        .kpi.ok { --tom: var(--ok); }
        .kpi.alerta { --tom: var(--alerta); }
        .kpi.erro { --tom: var(--erro); }
        .kpi.info { --tom: var(--info); }
        .kpi-corpo {
            display: flex; flex-direction: column; flex: 1 1 auto;
            text-decoration: none; color: inherit; min-width: 0; position: relative; z-index: 1;
        }
        .kpi .kpi-ico {
            width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center;
            margin: 0 0 6px; background: var(--superficie); color: var(--tom); font-size: .85rem;
            border: 1px solid color-mix(in srgb, var(--tom) 22%, var(--mix-branco));
            position: relative; z-index: 1;
        }
        .kpi-corpo > span { display: block; color: var(--suave); font-size: .64rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; line-height: 1.25; padding-right: 18px; }
        .kpi-corpo > strong { display: block; font-size: clamp(1.15rem, 1.6vw, 1.35rem); font-weight: 800; letter-spacing: -.03em; margin: 2px 0; font-variant-numeric: tabular-nums; }
        .kpi-corpo > small { display: block; color: var(--suave); font-size: .7rem; line-height: 1.3; }
        .kpi-corpo > small:first-of-type { margin-top: auto; }
        .kpi-corpo > small.kpi-extra { margin-top: 2px; color: color-mix(in srgb, var(--tom) 55%, var(--suave)); font-weight: 600; }
        .kpi-ajuda {
            position: absolute; top: 8px; right: 8px; z-index: 3;
            width: 22px; height: 22px; border-radius: 999px; padding: 0;
            display: grid; place-items: center;
            border: 1px solid color-mix(in srgb, var(--tom) 28%, var(--linha));
            background: var(--superficie); color: var(--tom);
            font-size: .72rem; cursor: pointer; line-height: 1;
            box-shadow: var(--sombra-leve);
        }
        .kpi-ajuda:hover, .kpi-ajuda[aria-expanded="true"] {
            background: var(--tom); color: #fff; border-color: var(--tom);
        }
        /* Fonte do texto fica no botão, oculta; o painel é modal #kpi-ajuda-modal */
        .kpi-ajuda-pop { display: none !important; }
        .kpi-ajuda-modal {
            position: fixed; inset: 0; z-index: 10050;
            display: flex; align-items: flex-end; justify-content: center;
            padding: 16px; box-sizing: border-box;
        }
        @media (min-width: 720px) {
            .kpi-ajuda-modal { align-items: center; }
        }
        .kpi-ajuda-modal[hidden] { display: none !important; }
        .kpi-ajuda-modal-backdrop {
            position: absolute; inset: 0;
            background: rgba(15, 23, 42, .55);
            border: 0; padding: 0; margin: 0; cursor: pointer;
        }
        .kpi-ajuda-modal-dialog {
            position: relative; z-index: 1;
            width: min(420px, 100%);
            max-height: min(78vh, 520px);
            display: flex; flex-direction: column;
            border-radius: 16px 16px 12px 12px;
            border: 1px solid var(--linha);
            background: var(--superficie, #fff);
            color: var(--tinta, #1a242b);
            box-shadow: 0 24px 64px rgba(15, 23, 42, .35);
            overflow: hidden;
        }
        @media (min-width: 720px) {
            .kpi-ajuda-modal-dialog { border-radius: 16px; }
        }
        .kpi-ajuda-modal-cabecalho {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 14px 14px 10px 16px;
            border-bottom: 1px solid var(--linha);
            flex: none;
        }
        .kpi-ajuda-modal-textos { flex: 1 1 auto; min-width: 0; }
        .kpi-ajuda-modal-titulo {
            display: block; margin: 0 0 4px;
            font-size: .68rem; font-weight: 800; letter-spacing: .06em;
            text-transform: uppercase; color: var(--suave, #5b6b76);
        }
        .kpi-ajuda-modal-rotulo {
            display: block; margin: 0;
            font-size: .95rem; font-weight: 800; color: var(--tinta, #1a242b);
            line-height: 1.3;
        }
        .kpi-ajuda-modal-fechar {
            flex: none; width: 36px; height: 36px; border-radius: 10px;
            display: grid; place-items: center; padding: 0;
            border: 1px solid var(--linha);
            background: var(--superficie, #fff); color: var(--tinta, #1a242b);
            font-size: 1.15rem; line-height: 1; cursor: pointer;
        }
        .kpi-ajuda-modal-fechar:hover {
            background: color-mix(in srgb, var(--clara, #eef4f2) 70%, #fff);
        }
        .kpi-ajuda-modal-corpo {
            margin: 0; padding: 14px 16px 18px;
            overflow: auto; -webkit-overflow-scrolling: touch;
            white-space: pre-wrap; word-break: break-word;
            font-size: .88rem; line-height: 1.55; font-weight: 500;
            color: var(--tinta, #1a242b);
        }
        .grade-painel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 12px;
            width: 100%;
            align-items: stretch;
        }
        .grade-painel > .bloco { min-width: 0; height: 100%; }
        .grade-painel .atalhos-bloco { grid-column: 1 / -1; height: auto; }

        /* —— Painel v2: compacto —— */
        .painel-v2 { display: grid; gap: 14px; }
        /* Linha boas-vindas (+ lembrete MFA) + caixa: ritmo −20% */
        .hero-painel-v2 {
            --hero-pad-y: 10px;
            --hero-pad-x: 11px;
            --hero-title: clamp(0.9rem, 1.35vw, 1.08rem);
            --hero-lead: 0.72rem;
            --hero-mark: 0.68rem;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr);
            gap: 6px;
            align-items: stretch;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            border-left: 0 !important;
            box-shadow: none !important;
            border-radius: 0;
        }
        .hero-painel-v2--com-lembrete {
            grid-template-columns: minmax(0, 1fr) minmax(0, 0.92fr) minmax(0, 1.2fr);
        }
        .hero-painel-v2 .hero-copy {
            /* Período = identidade visual (cor/ícone), sem texto de saudação */
            --hero-periodo: var(--destaque);
            --hero-periodo-soft: color-mix(in srgb, var(--destaque) 12%, var(--mix-branco));
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 3px;
            height: 100%;
            min-height: 0;
            box-sizing: border-box;
            padding: var(--hero-pad-y) var(--hero-pad-x);
            border-radius: 10px;
            border: 1px solid color-mix(in srgb, var(--hero-periodo) 20%, var(--linha));
            border-left: 3px solid var(--hero-periodo);
            background:
                linear-gradient(135deg, var(--hero-periodo-soft) 0%, var(--superficie) 42%, color-mix(in srgb, var(--clara) 35%, var(--mix-branco)) 100%);
            box-shadow: var(--sombra);
            min-width: 0;
        }
        .hero-painel-v2 .hero-copy--manha {
            --hero-periodo: #1a7a6a;
            --hero-periodo-soft: color-mix(in srgb, #1a7a6a 11%, var(--mix-branco));
        }
        .hero-painel-v2 .hero-copy--tarde {
            --hero-periodo: #c47a12;
            --hero-periodo-soft: color-mix(in srgb, #c47a12 10%, var(--mix-branco));
        }
        .hero-painel-v2 .hero-copy--noite {
            --hero-periodo: #3d5478;
            --hero-periodo-soft: color-mix(in srgb, #243552 16%, var(--mix-branco));
        }
        .hero-painel-v2 .hero-copy--madrugada {
            --hero-periodo: #2c3a5c;
            --hero-periodo-soft: color-mix(in srgb, #1a2238 20%, var(--mix-branco));
        }
        .hero-periodo {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--hero-periodo);
            box-shadow: 0 3px 8px color-mix(in srgb, var(--hero-periodo) 28%, transparent);
            font-size: .68rem;
            opacity: .92;
        }
        .hero-painel-v2 .brand-mark {
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 8px;
            font-size: var(--hero-mark);
            font-weight: 750;
            letter-spacing: .04em;
            text-transform: none;
            color: var(--suave);
            padding-right: 32px;
            line-height: 1.35;
            max-width: 100%;
        }
        .hero-painel-v2 .brand-mark__entidade {
            color: color-mix(in srgb, var(--tinta) 72%, var(--suave));
            font-weight: 700;
        }
        /* Separador visual do ano (não é “×” / multiplicação) */
        .hero-painel-v2 .brand-mark__sep {
            display: inline-block;
            width: 1px;
            height: 0.85em;
            background: color-mix(in srgb, var(--hero-periodo) 45%, var(--linha));
            border-radius: 1px;
            flex: none;
        }
        .hero-painel-v2 .brand-mark__ano {
            display: inline-flex;
            align-items: center;
            padding: 1px 7px;
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .06em;
            color: var(--hero-periodo);
            background: color-mix(in srgb, var(--hero-periodo) 12%, var(--mix-branco));
            border: 1px solid color-mix(in srgb, var(--hero-periodo) 22%, var(--linha));
            line-height: 1.3;
        }
        .hero-painel-v2 .eyebrow { margin: 0 0 2px; color: var(--suave); font-size: .6rem; font-weight: 650; }
        .hero-painel-v2 h1,
        .hero-painel-v2 .hero-papel {
            margin: 0;
            font-size: var(--hero-title);
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.2;
            color: var(--tinta);
        }
        .hero-lembrete-mfa {
            min-width: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: var(--hero-pad-y) var(--hero-pad-x);
            border-radius: 10px;
            border: 1px solid color-mix(in srgb, var(--ambar, #b86e00) 28%, var(--linha));
            background:
                linear-gradient(145deg, color-mix(in srgb, #fff6e8 80%, var(--mix-branco)) 0%, var(--superficie) 70%);
            box-shadow: var(--sombra);
        }
        .hero-lembrete-mfa__kicker {
            margin: 0;
            font-size: .56rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ambar, #b86e00);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .hero-lembrete-mfa__titulo {
            margin: 0;
            font-size: .84rem;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.15;
            color: var(--tinta);
        }
        .hero-lembrete-mfa__lista {
            display: grid;
            gap: 4px;
            margin-top: 2px;
        }
        .hero-lembrete-mfa__item {
            display: grid;
            grid-template-columns: 20px 1fr;
            gap: 6px;
            align-items: start;
            padding: 5px 7px;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            border: 1px solid color-mix(in srgb, var(--ambar, #b86e00) 22%, var(--linha));
            background: color-mix(in srgb, #fff 55%, var(--superficie));
        }
        .hero-lembrete-mfa__item i {
            width: 20px; height: 20px; border-radius: 6px;
            display: grid; place-items: center;
            background: color-mix(in srgb, var(--ambar, #b86e00) 16%, #fff);
            color: var(--ambar, #b86e00);
            font-size: .68rem;
        }
        .hero-lembrete-mfa__item strong {
            display: block; font-size: .72rem; line-height: 1.25; color: var(--tinta);
        }
        .hero-lembrete-mfa__item small {
            display: block; margin-top: 1px; font-size: .64rem; color: var(--suave); line-height: 1.3;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .hero-lembrete-mfa__item.tom-erro {
            border-color: color-mix(in srgb, var(--erro) 30%, var(--linha));
            background: color-mix(in srgb, var(--erro-fundo) 75%, var(--superficie));
        }
        .hero-lembrete-mfa__item.tom-erro i {
            background: color-mix(in srgb, var(--erro) 14%, #fff);
            color: var(--erro);
        }
        .hero-lembrete-mfa__cta {
            margin-top: auto;
            align-self: flex-start;
            font-size: .68rem;
            font-weight: 700;
            color: var(--ambar, #b86e00);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding-top: 2px;
        }
        .hero-lembrete-mfa__cta:hover { text-decoration: underline; }
        .hero-painel-v2 .lead {
            margin: 0;
            color: var(--suave);
            font-size: var(--hero-lead);
            line-height: 1.35;
            max-width: 22rem;
            font-weight: 600;
        }
        .hero-caixa {
            min-width: 0;
            height: 100%;
            min-height: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: visible;
            padding: var(--hero-pad-y) var(--hero-pad-x) !important;
        }
        .ls-filtros--caixa-dia {
            margin: 0 0 6px;
            gap: 6px;
        }
        .ls-filtros--caixa-dia .ls-filtros__legenda { font-size: .72rem; }
        .ls-filtros--caixa-dia .ls-chip { font-size: .74rem; padding: 4px 9px; }
        .ls-painel__rodape--caixa-dia {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            margin-top: auto;
            padding-top: 6px;
            border-top: 1px solid color-mix(in srgb, var(--linha) 80%, transparent);
        }
        .ls-painel__rodape--caixa-dia .ls-painel__mais {
            margin: 0;
            font-size: .74rem;
            color: var(--suave);
        }
        .ls-painel__rodape--caixa-dia .ls-paginacao button {
            min-width: 28px;
            height: 28px;
            padding: 0 7px;
            font-size: .78rem;
        }
        .hero-seguranca-flash {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin: 0 0 12px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fff6e8;
            color: #8a5a00;
            border: 1px solid color-mix(in srgb, #b86e00 28%, #fff);
        }
        .hero-seguranca-flash i { margin-top: 2px; }
        .hero-seguranca-flash strong { display: block; font-size: .85rem; }
        .hero-seguranca-flash span { display: block; font-size: .78rem; margin-top: 2px; }
        .hero-caixa .prio-head-row { margin-bottom: 4px; }
        .hero-caixa .sec-kicker { font-size: .58rem; }
        .hero-caixa h2 { font-size: .78rem; }
        .hero-caixa .lista-compacta li {
            grid-template-columns: 30px 1fr auto;
            gap: 8px;
            padding: 6px 8px;
        }
        .hero-caixa .lista .ico {
            width: 30px; height: 30px; border-radius: 8px; font-size: .82rem;
        }
        .hero-caixa .lista strong {
            font-size: .84rem;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .hero-caixa .lista small {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: .74rem;
        }
        .hero-caixa .lista .botao {
            padding: 5px 10px;
            font-size: .78rem;
            border-radius: 8px;
        }
        .hero-caixa .vazio { font-size: .84rem; margin: 6px 0; }
        .rodape-op-atualizado .rodape-op-rotulo { max-width: 6.5rem; }
        .rodape-op-atualizado strong {
            font-weight: 700;
            font-size: .66rem;
            letter-spacing: 0;
            color: var(--suave);
        }
        .sec-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.5rem;
            height: 1.5rem;
            padding: 0 7px;
            margin-left: 6px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            vertical-align: middle;
            color: var(--destaque);
            background: color-mix(in srgb, var(--clara) 70%, var(--mix-branco));
            border: 1px solid color-mix(in srgb, var(--destaque) 18%, var(--linha));
        }

        .sec-head { display: flex; flex-wrap: wrap; gap: 6px 14px; align-items: end; justify-content: space-between; margin: 0 0 8px; }
        .sec-head-tight { margin-bottom: 8px; }
        .sec-head-inline { margin-bottom: 10px; }
        .sec-kicker {
            margin: 0 0 2px;
            font-size: .65rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--acento);
        }
        .sec-head h2, .prio-card h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -.02em;
            display: flex; align-items: center; gap: 8px;
        }
        .sec-lead { margin: 0; color: var(--suave); font-size: .8rem; max-width: 34rem; line-height: 1.35; }

        .prio-card {
            background: var(--superficie);
            border: 1px solid var(--linha);
            border-radius: 14px;
            padding: 12px 14px;
            box-shadow: var(--sombra);
        }
        .prio-card-unico { width: 100%; }
        /* LibreSign — bloco único com duas colunas alinhadas */
        .ls-painel {
            margin: 0 0 14px;
            border: 1px solid var(--linha);
            border-radius: 16px;
            background: var(--superficie, #fff);
            box-shadow: var(--sombra);
            overflow: hidden;
        }
        .ls-painel__topo {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 16px;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--linha);
            background: linear-gradient(120deg, color-mix(in srgb, var(--clara, #e6f4f6) 70%, #fff), var(--superficie, #fff));
        }
        .ls-painel__marca {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ls-painel__marca h2 {
            margin: 0;
            font-size: 1.05rem;
        }
        .ls-painel__ico {
            width: 40px; height: 40px; border-radius: 12px;
            display: grid; place-items: center;
            background: linear-gradient(145deg, var(--destaque, #1a7a8c), var(--cor, #0f4c5c));
            color: #fff; font-size: 1.05rem;
            box-shadow: 0 8px 16px color-mix(in srgb, var(--cor, #0f4c5c) 28%, transparent);
        }
        .ls-painel__links {
            display: flex; flex-wrap: wrap; gap: 10px 14px; align-items: center;
        }
        .ls-painel__links--icos { gap: 8px; align-items: stretch; }
        .ls-painel__badge-pend {
            display: inline-grid;
            place-items: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--alerta, #b7791f) 16%, #fff);
            color: var(--alerta, #b7791f);
            border: 1px solid color-mix(in srgb, var(--alerta, #b7791f) 35%, transparent);
            font-size: .72rem;
            font-weight: 800;
        }
        .ls-painel__badge-pend.is-critico {
            background: color-mix(in srgb, var(--erro, #9b2c2c) 14%, #fff);
            color: var(--erro, #9b2c2c);
            border-color: color-mix(in srgb, var(--erro, #9b2c2c) 40%, transparent);
        }
        .ls-painel__ico-link {
            position: relative;
            min-width: 52px;
            height: auto;
            padding: 6px 8px 5px;
            border-radius: 10px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            border: 1px solid color-mix(in srgb, var(--cor, #0f4c5c) 28%, var(--linha));
            background: var(--superficie, #fff);
            color: var(--cor, #0f4c5c);
            text-decoration: none;
            transition: background .15s ease, transform .15s ease, border-color .15s ease, color .15s ease;
        }
        .ls-painel__ico-link > i { font-size: .95rem; line-height: 1; }
        .ls-painel__ico-legenda {
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .ls-painel__ico-count {
            position: absolute;
            top: -5px; right: -5px;
            min-width: 16px; height: 16px; padding: 0 4px;
            border-radius: 999px; display: grid; place-items: center;
            font-size: .62rem; font-weight: 800;
            background: var(--alerta, #b7791f); color: #fff;
            border: 1px solid #fff;
        }
        .ls-painel__ico-link--fila.is-alerta {
            color: var(--alerta, #b7791f);
            border-color: color-mix(in srgb, var(--alerta, #b7791f) 45%, var(--linha));
            background: color-mix(in srgb, var(--alerta, #b7791f) 10%, #fff);
        }
        .ls-painel__ico-link--fila.is-critico {
            color: var(--erro, #9b2c2c);
            border-color: color-mix(in srgb, var(--erro, #9b2c2c) 50%, var(--linha));
            background: color-mix(in srgb, var(--erro, #9b2c2c) 10%, #fff);
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--erro, #9b2c2c) 18%, transparent);
        }
        .ls-painel__ico-link--fila.is-critico .ls-painel__ico-count {
            background: var(--erro, #9b2c2c);
        }
        .ls-painel__ico-link--assinados {
            color: var(--ok, #17663f);
            border-color: color-mix(in srgb, var(--ok, #17663f) 40%, var(--linha));
            background: color-mix(in srgb, var(--ok, #17663f) 8%, #fff);
        }
        .ls-painel__toggle {
            min-width: 36px;
            padding: 8px;
        }
        .ls-chip--critico {
            color: #8a3a3a;
            border-color: color-mix(in srgb, var(--erro, #9b2c2c) 28%, var(--linha));
        }
        .ls-chip--critico.is-ativo {
            background: color-mix(in srgb, var(--erro, #9b2c2c) 12%, #fff);
            border-color: color-mix(in srgb, var(--erro, #9b2c2c) 45%, var(--linha));
            color: var(--erro, #9b2c2c);
        }
        .ls-painel__item-ico.critico {
            background: color-mix(in srgb, var(--erro, #9b2c2c) 14%, #fff);
            color: var(--erro, #9b2c2c);
        }
        .ls-painel--recolhivel .ls-painel__corpo {
            overflow: hidden;
            max-height: 2400px;
            opacity: 1;
            transition: max-height .28s ease, opacity .2s ease;
        }
        .ls-painel--recolhivel.is-recolhido .ls-painel__corpo {
            max-height: 0;
            opacity: 0;
            pointer-events: none;
        }
        .ls-painel--fixido .ls-painel__toggle { display: none !important; }
        .ls-painel__toggle[aria-expanded="false"] i { transform: rotate(180deg); }
        .ls-painel__toggle i { transition: transform .2s ease; display: inline-block; }
        .ls-painel__ico-link:hover {
            background: color-mix(in srgb, var(--clara, #e6f4f6) 70%, #fff);
            border-color: var(--destaque, #1a7a8c);
            transform: translateY(-1px);
        }
        .ls-painel__ico-link--fila.is-alerta:hover,
        .ls-painel__ico-link--fila.is-critico:hover,
        .ls-painel__ico-link--assinados:hover {
            filter: brightness(0.98);
        }
        .ls-filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            align-items: end;
            margin: 2px 0 12px;
            padding: 10px 12px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--clara, #e6f4f6) 35%, #fff);
            border: 1px solid color-mix(in srgb, var(--linha, #d5dee6) 80%, transparent);
        }
        .ls-filtros__campo,
        .ls-filtros__grupo {
            display: grid;
            gap: 5px;
            align-content: end;
        }
        .ls-filtros__campo {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--suave, #5b6b76);
            padding-right: 12px;
            border-right: 1px solid color-mix(in srgb, var(--linha, #d5dee6) 85%, transparent);
        }
        .ls-filtros__campo--modulo .ls-filtros__select {
            border-color: color-mix(in srgb, var(--destaque, #1a7a8c) 28%, var(--linha));
        }
        .ls-filtros__legenda {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--suave, #5b6b76);
        }
        .ls-filtros__grupo--periodo {
            padding-right: 12px;
            border-right: 1px solid color-mix(in srgb, var(--linha, #d5dee6) 85%, transparent);
        }
        .ls-filtros__grupo--periodo .ls-filtros__legenda { color: #6b5a2e; }
        .ls-filtros__grupo--escopo .ls-filtros__legenda { color: #17663f; }
        .ls-filtros__select {
            min-width: 132px;
            border: 1px solid var(--linha);
            border-radius: 8px;
            padding: 5px 8px;
            font: inherit;
            font-size: .82rem;
            color: var(--tinta, #1a242b);
            background: var(--superficie, #fff);
        }
        .ls-filtros__chips {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .ls-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid var(--linha);
            background: var(--superficie, #fff);
            color: var(--suave, #5b6b76);
            border-radius: 999px;
            padding: 5px 11px;
            min-height: 32px;
            font: inherit;
            font-size: .75rem;
            font-weight: 700;
            line-height: 1.1;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s ease, border-color .15s ease, color .15s ease, transform .12s ease;
        }
        .ls-chip i { font-size: .85rem; line-height: 1; opacity: .92; }
        .ls-chip:hover { transform: translateY(-1px); }
        .ls-chip--periodo.is-ativo {
            border-color: color-mix(in srgb, #b7791f 50%, var(--linha));
            background: color-mix(in srgb, #b7791f 14%, #fff);
            color: #8a5a12;
        }
        .ls-chip--escopo.is-ativo {
            border-color: color-mix(in srgb, var(--ok, #17663f) 50%, var(--linha));
            background: color-mix(in srgb, var(--ok, #17663f) 14%, #fff);
            color: var(--ok, #17663f);
        }
        .ls-chip.is-ativo:not(.ls-chip--periodo):not(.ls-chip--escopo) {
            border-color: color-mix(in srgb, var(--ok, #17663f) 45%, var(--linha));
            background: color-mix(in srgb, var(--ok, #17663f) 10%, #fff);
            color: var(--ok, #17663f);
        }
        @media (max-width: 720px) {
            .ls-filtros__campo,
            .ls-filtros__grupo--periodo {
                border-right: 0;
                padding-right: 0;
            }
        }
        .ls-painel__rodape {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 12px;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
        }
        .ls-paginacao {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
        }
        .ls-paginacao button {
            min-width: 30px;
            height: 30px;
            padding: 0 8px;
            border-radius: 8px;
            border: 1px solid var(--linha);
            background: var(--superficie, #fff);
            color: var(--tinta, #1a242b);
            font: inherit;
            font-size: .78rem;
            font-weight: 700;
            cursor: pointer;
        }
        .ls-paginacao button.is-ativo {
            border-color: var(--ok, #17663f);
            background: color-mix(in srgb, var(--ok, #17663f) 12%, #fff);
            color: var(--ok, #17663f);
        }
        .ls-paginacao button:disabled {
            opacity: .45;
            cursor: default;
        }
        .ls-painel__flash {
            margin: 0; padding: 8px 16px; font-size: .88rem;
            border-bottom: 1px solid var(--linha);
        }
        .ls-painel__flash.ok { color: var(--ok); background: color-mix(in srgb, var(--ok) 8%, #fff); }
        .ls-painel__flash.erro { color: var(--erro, #9b2c2c); background: color-mix(in srgb, var(--erro, #9b2c2c) 8%, #fff); }
        .ls-painel__grade {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-width: 0;
        }
        .ls-painel__grade--unica { grid-template-columns: 1fr; }
        .ls-painel__col {
            min-width: 0;
            padding: 12px 14px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .ls-painel__col--pendentes {
            border-top: 3px solid var(--alerta, #b7791f);
        }
        .ls-painel__col--assinados {
            border-top: 3px solid var(--ok, #17663f);
        }
        .ls-painel__grade:not(.ls-painel__grade--unica) .ls-painel__col--assinados {
            border-left: 1px solid var(--linha);
        }
        .ls-painel__col-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin: 0 0 2px;
        }
        .ls-painel__col-head h3 {
            margin: 0;
            font-size: .92rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .ls-painel__col--pendentes .ls-painel__col-head h3 { color: var(--alerta, #b7791f); }
        .ls-painel__col--assinados .ls-painel__col-head h3 { color: var(--ok, #17663f); }
        .ls-painel__lista {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .ls-painel__lista > li {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 10px;
            align-items: center;
            justify-content: space-between;
            padding: 10px 10px;
            border: 1px solid var(--linha);
            border-radius: 12px;
            background: color-mix(in srgb, var(--clara, #e6f4f6) 28%, var(--superficie, #fff));
        }
        .ls-painel__item-main {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            min-width: 0;
            flex: 1 1 180px;
        }
        .ls-painel__item-main strong {
            display: block;
            font-size: .9rem;
            line-height: 1.3;
        }
        .ls-painel__item-main small {
            display: block;
            margin-top: 2px;
            color: var(--suave, #5b6b76);
            font-size: .75rem;
            line-height: 1.4;
        }
        .ls-painel__item-ico {
            width: 32px; height: 32px; border-radius: 10px; flex: none;
            display: grid; place-items: center; font-size: .9rem;
            background: #f1f5f8; color: var(--suave, #5b6b76);
        }
        .ls-painel__item-ico.alerta { background: color-mix(in srgb, var(--alerta, #b7791f) 16%, #fff); color: var(--alerta, #b7791f); }
        .ls-painel__item-ico.ok { background: color-mix(in srgb, var(--ok, #17663f) 14%, #fff); color: var(--ok, #17663f); }
        .ls-painel__vazio {
            margin: 4px 0 0;
            padding: 14px 12px;
            border: 1px dashed color-mix(in srgb, var(--linha) 80%, transparent);
            border-radius: 12px;
            color: var(--suave, #5b6b76);
            font-size: .86rem;
            background: color-mix(in srgb, var(--clara, #e6f4f6) 22%, var(--superficie, #fff));
        }
        .ls-painel__mais {
            margin: 10px 0 0;
            font-size: .8rem;
            color: var(--suave, #5b6b76);
        }
        .ls-painel__mais a {
            color: var(--destaque, #1a5f7a);
            font-weight: 700;
            text-decoration: none;
        }
        .ls-painel__mais a:hover { text-decoration: underline; }
        .assina-acoes {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            justify-content: flex-end;
            flex: 0 0 auto;
        }
        .assina-acoes form { margin: 0; }
        .assina-acoes .botao {
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            font-size: .78rem;
        }
        .assina-acoes .botao i { font-size: .95em; }
        @media (max-width: 860px) {
            .ls-painel__grade { grid-template-columns: 1fr; }
            .ls-painel__grade:not(.ls-painel__grade--unica) .ls-painel__col--assinados {
                border-left: 0;
                border-top: 3px solid var(--ok, #17663f);
            }
        }
        .prio-head-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 14px;
            align-items: end;
            justify-content: space-between;
            margin: 0 0 8px;
        }
        .prio-foot-link {
            color: var(--destaque); font-weight: 700; font-size: .8rem; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .prio-foot-link:hover { color: var(--acento); }
        .lista-compacta { gap: 5px; }
        .lista-compacta li { padding: 7px 9px; }
        .lista-compacta .botao { padding: 5px 8px; font-size: .72rem; }

        .modulos-plano { margin: 0; }
        .mod-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 8px;
            justify-content: start;
        }
        .mod-card {
            --mod: var(--destaque);
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0;
            min-height: 0;
            padding: 10px 12px 10px 10px;
            border-radius: 12px;
            color: var(--tinta);
            border: 1px solid color-mix(in srgb, var(--mod) 18%, var(--linha));
            background: var(--superficie);
            transition: border-color .15s ease, box-shadow .15s ease;
            overflow: visible;
        }
        .mod-card.tem-badge { padding-right: 28px; }
        .mod-card:hover {
            border-color: color-mix(in srgb, var(--mod) 42%, var(--linha));
            box-shadow: 0 8px 18px color-mix(in srgb, var(--mod) 12%, transparent);
        }
        .mod-card.mod-card--off {
            opacity: .78;
            filter: grayscale(.55);
            background: color-mix(in srgb, #98a2b3 10%, var(--superficie));
            border-color: color-mix(in srgb, #98a2b3 35%, var(--linha));
            border-style: dashed;
            cursor: not-allowed;
            pointer-events: none;
            user-select: none;
        }
        .mod-card.mod-card--off:hover {
            border-color: color-mix(in srgb, #98a2b3 35%, var(--linha));
            box-shadow: none;
        }
        .mod-card.mod-card--off .mod-ico {
            background: #98a2b3;
            color: #fff;
        }
        .mod-off-tag {
            display: inline-block;
            margin-left: 4px;
            padding: 1px 6px;
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            vertical-align: middle;
            background: color-mix(in srgb, #98a2b3 22%, transparent);
            color: #475467;
        }
        .mod-card-link {
            display: grid;
            grid-template-columns: 36px 1fr;
            gap: 0 9px;
            align-items: start;
            text-decoration: none;
            color: inherit;
            min-width: 0;
            flex: 1 1 auto;
        }
        .mod-card-link--off { cursor: not-allowed; }
        .mod-ico {
            width: 36px; height: 36px; border-radius: 9px;
            display: grid; place-items: center;
            color: #fff;
            background: var(--mod);
            font-size: .88rem;
        }
        .mod-body strong {
            display: block;
            font-size: .84rem;
            font-weight: 800;
            letter-spacing: -.015em;
            line-height: 1.25;
        }
        .mod-body strong .fa-external-link { font-size: 10px; opacity: .65; }
        .mod-body small {
            display: block;
            margin-top: 2px;
            color: var(--suave);
            font-size: .72rem;
            line-height: 1.35;
            font-weight: 500;
        }
        /* Balões estilo notificação (canto do card) */
        .mod-badges {
            position: absolute;
            top: -7px;
            right: -7px;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            pointer-events: none;
        }
        .mod-badge {
            pointer-events: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.35rem;
            padding: 0 5px;
            border-radius: 999px;
            text-decoration: none;
            font-size: .62rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            line-height: 1;
            letter-spacing: -.02em;
            color: #fff;
            background: var(--info);
            border: 2px solid #fff;
            box-shadow: 0 4px 10px rgba(14, 42, 58, .22);
            transition: transform .15s ease, filter .15s ease;
        }
        .mod-badge:hover {
            transform: scale(1.08);
            filter: brightness(1.08);
        }
        .mod-badge.ok { background: var(--ok); }
        .mod-badge.info { background: var(--info); }
        .mod-badge.alerta { background: var(--alerta); }
        .mod-badge.erro { background: var(--erro); }
        .mod-badge.pulso {
            animation: mod-badge-pulso 1.8s ease-in-out infinite;
        }
        @keyframes mod-badge-pulso {
            0%, 100% { box-shadow: 0 4px 10px rgba(14, 42, 58, .22), 0 0 0 0 color-mix(in srgb, var(--erro) 35%, transparent); }
            55% { box-shadow: 0 4px 10px rgba(14, 42, 58, .22), 0 0 0 6px transparent; }
        }

        /* Rodapé suspenso — centralizado no viewport */
        .rodape-operacao {
            position: fixed;
            left: 0;
            right: 0;
            bottom: calc(12px + var(--safe-b));
            z-index: 25;
            display: flex;
            justify-content: center;
            padding: 0 12px;
            pointer-events: none;
        }
        .rodape-operacao-inner {
            pointer-events: auto;
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 8px 12px;
            max-width: min(960px, 100%);
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--destaque) 22%, var(--linha));
            background:
                linear-gradient(135deg,
                    color-mix(in srgb, var(--info-fundo) 70%, #fff) 0%,
                    color-mix(in srgb, #fff 92%, transparent) 48%,
                    color-mix(in srgb, var(--ok-fundo) 45%, #fff) 100%);
            backdrop-filter: blur(14px);
            box-shadow:
                0 12px 32px rgba(14, 42, 58, .12),
                0 0 0 1px color-mix(in srgb, #fff 70%, transparent) inset;
        }
        .rodape-org-github {
            flex: none;
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            text-decoration: none;
            background:
                radial-gradient(circle at 30% 25%, #fff 0%, #f4f7fb 58%, #e8eef5 100%);
            border: 1px solid color-mix(in srgb, #2f81f7 28%, var(--linha));
            box-shadow:
                0 1px 0 color-mix(in srgb, #fff 80%, transparent) inset,
                0 4px 12px rgba(31, 35, 40, .08);
            transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .rodape-org-github img {
            width: 20px;
            height: 20px;
            object-fit: contain;
            display: block;
            border-radius: 4px;
        }
        .rodape-org-github-mark {
            position: absolute;
            right: -2px;
            bottom: -2px;
            width: 14px;
            height: 14px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #f0f6fc;
            background: #24292f;
            border: 1.5px solid color-mix(in srgb, #fff 85%, transparent);
            box-shadow: 0 1px 3px rgba(31, 35, 40, .22);
        }
        .rodape-org-github:hover {
            transform: translateY(-1px);
            border-color: color-mix(in srgb, #2f81f7 48%, var(--destaque));
            box-shadow:
                0 1px 0 color-mix(in srgb, #fff 80%, transparent) inset,
                0 8px 18px rgba(31, 35, 40, .14);
        }
        .rodape-org-github:focus-visible {
            outline: 2px solid color-mix(in srgb, #2f81f7 70%, transparent);
            outline-offset: 2px;
        }
        /* —— Painel: modo escuro (superfícies, KPIs, hero, menus) —— */
        html[data-buriti-modo="escuro"] body {
            background:
                radial-gradient(1000px 380px at 4% -10%, color-mix(in srgb, var(--destaque) 12%, transparent), transparent 58%),
                radial-gradient(700px 280px at 96% 0%, color-mix(in srgb, var(--acento) 10%, transparent), transparent 50%),
                linear-gradient(180deg, #0e1614 0%, var(--fundo) 42%, #0a100e 100%) !important;
        }
        html[data-buriti-modo="escuro"] .topo {
            background: var(--topo-fundo) !important;
            border-bottom-color: var(--linha) !important;
            box-shadow: 0 1px 0 var(--linha) !important;
        }
        html[data-buriti-modo="escuro"] .acao-ico,
        html[data-buriti-modo="escuro"] .user-btn,
        html[data-buriti-modo="escuro"] .chip,
        html[data-buriti-modo="escuro"] .botao.claro {
            background: var(--superficie-alta) !important;
            border-color: var(--linha) !important;
            color: var(--tinta) !important;
            box-shadow: none !important;
        }
        html[data-buriti-modo="escuro"] .acao-ico { color: var(--destaque) !important; }
        html[data-buriti-modo="escuro"] .acao-ico:hover,
        html[data-buriti-modo="escuro"] .acao-ico[aria-expanded="true"],
        html[data-buriti-modo="escuro"] .acao-ico[aria-pressed="true"],
        html[data-buriti-modo="escuro"] .user-btn:hover {
            background: color-mix(in srgb, var(--acento) 14%, var(--superficie)) !important;
            border-color: color-mix(in srgb, var(--acento) 36%, var(--linha)) !important;
            color: var(--acento) !important;
        }
        html[data-buriti-modo="escuro"] .acao-ico.sair { color: var(--erro) !important; border-color: color-mix(in srgb, var(--erro) 35%, var(--linha)) !important; }
        html[data-buriti-modo="escuro"] .acao-ico.sair:hover { background: var(--erro-fundo) !important; color: #ffc9c9 !important; }
        html[data-buriti-modo="escuro"] .notif-painel,
        html[data-buriti-modo="escuro"] .notif-barra,
        html[data-buriti-modo="escuro"] .notif-rodape,
        html[data-buriti-modo="escuro"] .user-menu {
            background: var(--superficie) !important;
            border-color: var(--linha) !important;
            color: var(--tinta) !important;
            box-shadow: var(--sombra) !important;
        }
        html[data-buriti-modo="escuro"] .notif-item { color: var(--tinta) !important; }
        html[data-buriti-modo="escuro"] .notif-item:hover,
        html[data-buriti-modo="escuro"] .user-menu a:hover,
        html[data-buriti-modo="escuro"] .user-menu button:hover {
            background: var(--clara) !important;
        }
        html[data-buriti-modo="escuro"] .notif-item.unread {
            background: color-mix(in srgb, var(--alerta) 12%, var(--superficie)) !important;
            border-color: color-mix(in srgb, var(--alerta) 28%, var(--linha)) !important;
        }
        html[data-buriti-modo="escuro"] .bloco,
        html[data-buriti-modo="escuro"] .kpi-grupo,
        html[data-buriti-modo="escuro"] .hero-painel,
        html[data-buriti-modo="escuro"] .hero-painel-v2 .hero-copy,
        html[data-buriti-modo="escuro"] .lista li,
        html[data-buriti-modo="escuro"] .atalhos a,
        html[data-buriti-modo="escuro"] .prio-card,
        html[data-buriti-modo="escuro"] .mod-card,
        html[data-buriti-modo="escuro"] .sec-card {
            background: var(--superficie) !important;
            border-color: var(--linha) !important;
            color: var(--tinta) !important;
            box-shadow: var(--sombra-leve) !important;
        }
        html[data-buriti-modo="escuro"] .mod-card.mod-card--off {
            background: color-mix(in srgb, #667085 18%, var(--superficie)) !important;
            border-color: color-mix(in srgb, #98a2b3 40%, var(--linha)) !important;
            border-style: dashed !important;
            opacity: .7;
            filter: grayscale(.4);
        }
        html[data-buriti-modo="escuro"] .mod-card.mod-card--off .mod-off-tag {
            background: color-mix(in srgb, #98a2b3 28%, transparent);
            color: #d0d5dd;
        }
        html[data-buriti-modo="escuro"] .hero-painel,
        html[data-buriti-modo="escuro"] .hero-painel-v2 .hero-copy {
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--destaque) 14%, var(--superficie)) 0%, var(--superficie) 55%, color-mix(in srgb, var(--clara) 70%, var(--superficie)) 100%) !important;
            border-color: color-mix(in srgb, var(--destaque) 28%, var(--linha)) !important;
        }
        html[data-buriti-modo="escuro"] .kpi-grupo {
            background: linear-gradient(180deg, color-mix(in srgb, var(--clara) 70%, var(--superficie)), var(--superficie)) !important;
        }
        html[data-buriti-modo="escuro"] .kpi {
            background: linear-gradient(180deg, color-mix(in srgb, var(--tom, var(--cor)) 16%, var(--superficie)), var(--superficie) 72%) !important;
            border-color: color-mix(in srgb, var(--tom, var(--cor)) 30%, var(--linha)) !important;
            color: var(--tinta) !important;
            box-shadow: var(--sombra-leve) !important;
        }
        html[data-buriti-modo="escuro"] .kpi .kpi-ico,
        html[data-buriti-modo="escuro"] .lista .ico {
            background: var(--superficie-alta) !important;
            border-color: color-mix(in srgb, var(--tom, var(--cor)) 28%, var(--linha)) !important;
            color: var(--tom, var(--destaque)) !important;
            box-shadow: none !important;
        }
        html[data-buriti-modo="escuro"] .lista .ico { color: var(--destaque) !important; border-color: var(--linha) !important; }
        html[data-buriti-modo="escuro"] .kpi span,
        html[data-buriti-modo="escuro"] .kpi small,
        html[data-buriti-modo="escuro"] .lista small,
        html[data-buriti-modo="escuro"] .atalhos small,
        html[data-buriti-modo="escuro"] .bloco .sub,
        html[data-buriti-modo="escuro"] .hero-painel .lead,
        html[data-buriti-modo="escuro"] .hero-painel-v2 .lead,
        html[data-buriti-modo="escuro"] .vazio {
            color: var(--suave) !important;
        }
        html[data-buriti-modo="escuro"] .kpi strong,
        html[data-buriti-modo="escuro"] .lista strong,
        html[data-buriti-modo="escuro"] .atalhos strong,
        html[data-buriti-modo="escuro"] .bloco h2,
        html[data-buriti-modo="escuro"] .hero-painel h1,
        html[data-buriti-modo="escuro"] .hero-painel-v2 h1,
        html[data-buriti-modo="escuro"] .hero-painel-v2 .hero-papel {
            color: var(--tinta) !important;
        }
        html[data-buriti-modo="escuro"] .hero-painel-v2 .brand-mark__entidade {
            color: color-mix(in srgb, var(--tinta) 78%, var(--suave)) !important;
        }
        html[data-buriti-modo="escuro"] .hero-painel-v2 .brand-mark__ano {
            background: color-mix(in srgb, var(--hero-periodo, var(--destaque)) 22%, var(--superficie)) !important;
            border-color: color-mix(in srgb, var(--hero-periodo, var(--destaque)) 35%, var(--linha)) !important;
            color: color-mix(in srgb, var(--hero-periodo, var(--destaque)) 70%, #fff) !important;
        }
        html[data-buriti-modo="escuro"] .atalhos a:hover {
            border-color: color-mix(in srgb, var(--destaque) 45%, var(--linha)) !important;
            box-shadow: var(--sombra) !important;
        }
        html[data-buriti-modo="escuro"] .chip.papel {
            background: var(--info-fundo) !important;
            border-color: color-mix(in srgb, var(--info) 30%, var(--linha)) !important;
            color: var(--info) !important;
        }
        html[data-buriti-modo="escuro"] .botao.claro:hover {
            border-color: color-mix(in srgb, var(--destaque) 40%, var(--linha)) !important;
            color: var(--destaque) !important;
        }
        html[data-buriti-modo="escuro"] .rodape-operacao-kicker {
            background: var(--info-fundo) !important;
            border-color: color-mix(in srgb, var(--destaque) 28%, var(--linha)) !important;
            color: var(--destaque) !important;
        }
        html[data-buriti-modo="escuro"] .rodape-op a,
        html[data-buriti-modo="escuro"] .rodape-op > span,
        html[data-buriti-modo="escuro"] .rodape-op > button {
            background: var(--superficie-alta) !important;
            border-color: var(--linha) !important;
            color: var(--suave) !important;
        }
        html[data-buriti-modo="escuro"] .rodape-op a:hover,
        html[data-buriti-modo="escuro"] .rodape-op > button:hover {
            background: var(--clara) !important;
            color: var(--tinta) !important;
            border-color: color-mix(in srgb, var(--destaque) 35%, var(--linha)) !important;
        }
        html[data-buriti-modo="escuro"] .rodape-op.ok a,
        html[data-buriti-modo="escuro"] .rodape-op.ok > span,
        html[data-buriti-modo="escuro"] .rodape-op.ok > button { background: var(--ok-fundo) !important; border-color: color-mix(in srgb, var(--ok) 30%, var(--linha)) !important; }
        html[data-buriti-modo="escuro"] .rodape-op.alerta a,
        html[data-buriti-modo="escuro"] .rodape-op.alerta > span,
        html[data-buriti-modo="escuro"] .rodape-op.alerta > button { background: var(--alerta-fundo) !important; border-color: color-mix(in srgb, var(--alerta) 30%, var(--linha)) !important; }
        html[data-buriti-modo="escuro"] .rodape-op.erro a,
        html[data-buriti-modo="escuro"] .rodape-op.erro > span,
        html[data-buriti-modo="escuro"] .rodape-op.erro > button { background: var(--erro-fundo) !important; border-color: color-mix(in srgb, var(--erro) 30%, var(--linha)) !important; }
        html[data-buriti-modo="escuro"] .rodape-op.info a,
        html[data-buriti-modo="escuro"] .rodape-op.info > span,
        html[data-buriti-modo="escuro"] .rodape-op.info > button { background: var(--info-fundo) !important; border-color: color-mix(in srgb, var(--info) 30%, var(--linha)) !important; }
        html[data-buriti-modo="escuro"] .rodape-org-github {
            background:
                radial-gradient(circle at 30% 25%, #2a3139 0%, #1f242a 62%, #161b22 100%);
            border-color: color-mix(in srgb, #58a6ff 34%, var(--linha));
            box-shadow:
                0 1px 0 rgba(255, 255, 255, .06) inset,
                0 4px 14px rgba(0, 0, 0, .28);
        }
        html[data-buriti-modo="escuro"] .rodape-org-github-mark {
            background: #0d1117;
            border-color: #30363d;
            color: #f0f6fc;
        }
        .rodape-operacao-kicker {
            flex: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: .58rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--destaque);
            padding: 2px 8px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--info-fundo) 80%, var(--mix-branco));
            border: 1px solid color-mix(in srgb, var(--destaque) 16%, transparent);
        }
        .rodape-operacao-kicker i { font-size: 10px; opacity: .85; }
        .rodape-operacao-lista {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px 7px;
            flex: 0 1 auto;
            min-width: 0;
        }
        .rodape-op a,
        .rodape-op > span,
        .rodape-op > button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            max-width: 100%;
            padding: 4px 10px;
            border-radius: 999px;
            text-decoration: none;
            color: var(--suave);
            background: color-mix(in srgb, var(--mix-branco) 78%, transparent);
            border: 1px solid color-mix(in srgb, var(--linha) 70%, transparent);
            font: inherit;
            font-size: .68rem;
            font-weight: 600;
            line-height: 1.2;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
        }
        .rodape-op a:hover,
        .rodape-op > button:hover {
            color: var(--tinta);
            border-color: color-mix(in srgb, var(--destaque) 28%, var(--linha));
            background: var(--superficie);
        }
        .rodape-op i { font-size: 10px; opacity: .75; }
        .rodape-op.ok a, .rodape-op.ok > span, .rodape-op.ok > button {
            background: color-mix(in srgb, var(--ok-fundo) 75%, var(--mix-branco));
            border-color: color-mix(in srgb, var(--ok) 22%, transparent);
        }
        .rodape-op.alerta a, .rodape-op.alerta > span, .rodape-op.alerta > button {
            background: color-mix(in srgb, var(--alerta-fundo) 80%, var(--mix-branco));
            border-color: color-mix(in srgb, var(--alerta) 24%, transparent);
        }
        .rodape-op.erro a, .rodape-op.erro > span, .rodape-op.erro > button {
            background: color-mix(in srgb, var(--erro-fundo) 80%, var(--mix-branco));
            border-color: color-mix(in srgb, var(--erro) 24%, transparent);
        }
        .rodape-op.info a, .rodape-op.info > span, .rodape-op.info > button {
            background: color-mix(in srgb, var(--info-fundo) 75%, var(--mix-branco));
            border-color: color-mix(in srgb, var(--info) 20%, transparent);
        }
        .rodape-op-rotulo {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 7.5rem;
        }
        .rodape-op strong {
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: var(--tinta);
            letter-spacing: -.02em;
            min-width: 1ch;
        }
        .rodape-op em {
            font-style: normal;
            color: var(--suave);
            opacity: .8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 8rem;
            font-size: .62rem;
        }
        .rodape-op.ok strong { color: var(--ok); }
        .rodape-op.alerta strong { color: var(--alerta); }
        .rodape-op.erro strong { color: var(--erro); }
        .rodape-op.info strong { color: var(--info); }

        .bloco-kpis-v2 { margin-top: 0; }
        .bloco-kpis-v2 > .sec-head h2 { font-size: 1rem; }

        @media (max-width: 820px) {
            .hero-painel-v2,
            .hero-painel-v2--com-lembrete {
                grid-template-columns: 1fr;
            }
        }
        /* Espaço para o rodapé estático não ficar sob a barra suspensa */
        .app:has(.rodape-operacao) > footer.rodape-coop-buriti {
            padding-bottom: calc(64px + var(--safe-b));
            position: relative;
            z-index: 1;
        }
        @media (max-width: 640px) {
            .mod-cards { grid-template-columns: 1fr 1fr; }
            .rodape-operacao {
                bottom: calc(10px + var(--safe-b));
                padding: 0 8px;
                z-index: 30;
            }
            .rodape-operacao-inner {
                border-radius: 16px;
                padding: 6px 8px;
                gap: 4px 6px;
                max-width: 100%;
                justify-content: flex-start;
                overflow-x: auto;
                flex-wrap: nowrap;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }
            .rodape-operacao-inner::-webkit-scrollbar { display: none; }
            .rodape-operacao-kicker { display: none; }
            .rodape-operacao-lista {
                flex-wrap: nowrap;
                gap: 4px;
            }
            .rodape-op em { display: none; }
            .rodape-op-rotulo { display: none; }
            .rodape-op a,
            .rodape-op > span,
            .rodape-op > button {
                padding: 5px 8px;
                gap: 4px;
            }
            .rodape-org-github { width: 28px; height: 28px; flex: none; }
            main.miolo { padding-bottom: calc(88px + var(--safe-b)); }
            .app:has(.rodape-operacao) > footer.rodape-coop-buriti {
                padding-bottom: calc(96px + var(--safe-b));
            }
        }
        @media (max-width: 420px) {
            .mod-cards { grid-template-columns: 1fr; }
        }

        .perfil-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
        }
        .campo { padding: 12px; border: 1px solid var(--linha); border-radius: 12px; background: #f7fafb; }
        .campo span { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--suave); margin-bottom: 4px; }
        .campo strong { font-size: .95rem; word-break: break-word; }
        @media (max-width: 1100px) {
            .kpi-grupos-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 960px) {
            .grade-painel { grid-template-columns: 1fr; }
            .user-btn .meta { display: none; }
            .marca small { max-width: 28vw; }
        }
        @media (min-width: 1400px) {
            .kpis { grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); }
            .atalhos { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
            .kpi-grupos-grid { gap: 14px; }
        }
        @media (max-width: 520px) {
            .topo { padding: 0 10px 0 12px; gap: 6px; }
            main.miolo { padding: 10px 8px 8px; }
            .hero-painel { padding: 12px; }
            .kpi-grupo { padding: 10px; }
            .lista li { grid-template-columns: 36px 1fr; }
            .lista li > div:last-child { grid-column: 2; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        }
    </style>
    @if(view()->exists('buriti-ui::estilos'))
        @include('buriti-ui::estilos')
    @endif
    @stack('styles')
</head>
<body>
@php
    $u = $dados['usuario'];
    $naoLidas = (int) ($dados['nao_lidas'] ?? 0);
@endphp
<div class="shell">
    <header class="topo" role="banner">
        <a class="marca" href="{{ url('/painel') }}" title="Meu painel">
            <span class="selo"><i class="fa fa-th-large" aria-hidden="true"></i></span>
            <span>
                <strong>@yield('topo-titulo', 'Meu painel')</strong>
                <small>{{ $municipio }}</small>
            </span>
        </a>

        <nav class="topo-acoes" aria-label="Ações rápidas">
            <button type="button" class="acao-ico" data-buriti-modo-toggle
                    title="Alternar modo claro ou escuro" aria-label="Alternar modo claro ou escuro" aria-pressed="false">
                <i class="fa fa-moon-o" data-buriti-modo-icone aria-hidden="true"></i>
            </button>
            <a class="acao-ico" href="{{ $ieducarUrl }}" title="i-Educar" aria-label="Abrir i-Educar">
                <img src="{{ $logoIeducar ?? '/svg/i-educar-fav.svg' }}" alt="" width="26" height="26"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block'">
                <i class="fa fa-graduation-cap" style="display:none" aria-hidden="true"></i>
            </a>
            <div class="notif-drop" data-notif-drop>
                <button type="button" class="acao-ico" title="Notificações" aria-label="Notificações"
                        aria-expanded="false" aria-haspopup="true" data-notif-btn>
                    <i class="fa fa-bell" aria-hidden="true"></i>
                    <span class="badge-dot" data-notif-ponto @if($naoLidas < 1) hidden @endif>{{ $naoLidas > 99 ? '99+' : $naoLidas }}</span>
                </button>
                <div class="notif-painel" role="dialog" aria-label="Notificações">
                    <div class="notif-barra">
                        <strong>Notificações</strong>
                        <button type="button" data-notif-marcar-todas>Marcar todas (<span data-notif-count>{{ $naoLidas }}</span>)</button>
                    </div>
                    <div class="notif-lista" data-notif-lista>
                        <p class="notif-vazio">Carregando…</p>
                    </div>
                    <div class="notif-rodape">
                        <a href="{{ url('/notificacoes') }}"><i class="fa fa-list"></i> Ver todas as notificações</a>
                    </div>
                </div>
            </div>
            <button type="button" class="acao-ico sair" title="Sair" aria-label="Sair"
                    onclick="document.getElementById('logout-form').submit()">
                <i class="fa fa-sign-out" aria-hidden="true"></i>
            </button>

            <div class="user-drop" data-user-drop>
                <button type="button" class="user-btn" aria-expanded="false" aria-haspopup="true" title="Conta">
                    <span class="avatar" aria-hidden="true">
                        @if(!empty($u['foto']))
                            <img src="{{ $u['foto'] }}" alt="">
                        @else
                            {{ $u['iniciais'] }}
                        @endif
                    </span>
                    <span class="meta">
                        <strong>{{ $u['nome'] }}</strong>
                        <small>{{ $u['tipo'] ?? ($u['login'] ?? 'Usuário') }}</small>
                    </span>
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </button>
                <div class="user-menu" role="menu">
                    <a role="menuitem" href="{{ url('/painel/perfil') }}"><i class="fa fa-user"></i> Meu perfil</a>
                    <a role="menuitem" href="{{ route('change-password') }}"><i class="fa fa-lock"></i> Alterar senha</a>
                    <a role="menuitem" href="{{ url('/intranet/meusdados.php') }}"><i class="fa fa-id-card"></i> Meus dados</a>
                    <a role="menuitem" href="{{ url('/intranet/agenda.php') }}"><i class="fa fa-calendar"></i> Agenda</a>
                    @if(Auth::check() && method_exists(Auth::user(), 'isAdmin') && Auth::user()->isAdmin()
                        && (\Illuminate\Support\Facades\Route::has('assinatura.painel') || class_exists(\iEducar\Packages\BuritiAssinaturaDigital\Http\Controllers\AssinaturaController::class)))
                        <div class="sep"></div>
                        <a role="menuitem" href="{{ url('/assinatura-digital') }}">
                            <i class="fa fa-pencil-square-o"></i> Assinatura digital
                        </a>
                    @endif
                    <div class="sep"></div>
                    <button type="button" role="menuitem" onclick="document.getElementById('logout-form').submit()">
                        <i class="fa fa-sign-out"></i> Sair
                    </button>
                </div>
            </div>
        </nav>
        <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display:none">@csrf</form>
    </header>

    <main class="miolo">
        @yield('conteudo')
    </main>

    @if(view()->exists('buriti-ui::rodape'))
        @include('buriti-ui::rodape', ['moduloRodape' => 'Painel do usuário'])
    @endif
</div>
<div id="kpi-ajuda-modal" class="kpi-ajuda-modal" role="dialog" aria-modal="true" aria-labelledby="kpi-ajuda-modal-rotulo" hidden>
    <button type="button" class="kpi-ajuda-modal-backdrop" data-kpi-ajuda-fechar aria-label="Fechar"></button>
    <div class="kpi-ajuda-modal-dialog">
        <header class="kpi-ajuda-modal-cabecalho">
            <div class="kpi-ajuda-modal-textos">
                <strong class="kpi-ajuda-modal-titulo">Como é calculado</strong>
                <span id="kpi-ajuda-modal-rotulo" class="kpi-ajuda-modal-rotulo"></span>
            </div>
            <button type="button" class="kpi-ajuda-modal-fechar" data-kpi-ajuda-fechar aria-label="Fechar">&times;</button>
        </header>
        <p id="kpi-ajuda-modal-corpo" class="kpi-ajuda-modal-corpo"></p>
    </div>
</div>
<script>
(function () {
    /** PT-BR DD/MM/YYYY HH:mm (alinha ao DataHora PHP). */
    function formatarDataHoraBr(valor) {
        if (valor == null || valor === '') return '';
        var d = valor instanceof Date ? valor : new Date(valor);
        if (isNaN(d.getTime())) return '';
        var p = function (n) { return String(n).padStart(2, '0'); };
        return p(d.getDate()) + '/' + p(d.getMonth() + 1) + '/' + d.getFullYear()
            + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
    }

    var KEY_MODO = 'buriti.tema.modo';
    function modoAtual() {
        return document.documentElement.getAttribute('data-buriti-modo') === 'escuro' ? 'escuro' : 'claro';
    }
    function aplicarModo(modo) {
        modo = modo === 'escuro' ? 'escuro' : 'claro';
        document.documentElement.setAttribute('data-buriti-modo', modo);
        if (document.body) document.body.setAttribute('data-buriti-modo', modo);
        try { localStorage.setItem(KEY_MODO, modo); } catch (e) {}
        document.querySelectorAll('[data-buriti-modo-toggle]').forEach(function (btn) {
            var ico = btn.querySelector('[data-buriti-modo-icone]') || btn.querySelector('i');
            var escuro = modo === 'escuro';
            btn.title = escuro ? 'Mudar para modo claro' : 'Mudar para modo escuro';
            btn.setAttribute('aria-label', btn.title);
            btn.setAttribute('aria-pressed', escuro ? 'true' : 'false');
            if (ico) ico.className = escuro ? 'fa fa-sun-o' : 'fa fa-moon-o';
        });
    }
    aplicarModo(modoAtual());
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest && e.target.closest('[data-buriti-modo-toggle]');
        if (!btn) return;
        e.preventDefault();
        aplicarModo(modoAtual() === 'escuro' ? 'claro' : 'escuro');
    });

    var URL_COUNT = @json(url('/notificacoes/quantidade-nao-lidas'));
    var URL_LIST = @json(url('/notificacoes/retorna-notificacoes-usuario'));
    var URL_READ = @json(url('/notificacoes/marca-como-lida'));
    var URL_READ_ALL = @json(url('/notificacoes/marca-todas-como-lidas'));
    var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function atualizarBadge(n) {
        n = parseInt(n, 10) || 0;
        document.querySelectorAll('[data-notif-ponto]').forEach(function (el) {
            if (n < 1) { el.hidden = true; el.textContent = '0'; return; }
            el.hidden = false;
            el.textContent = n > 99 ? '99+' : String(n);
        });
        document.querySelectorAll('[data-notif-count]').forEach(function (el) {
            el.textContent = String(n);
        });
        document.querySelectorAll('[data-rodape-notif-valor]').forEach(function (el) {
            el.textContent = String(n);
            var item = el.closest('[data-rodape-notif-item]');
            if (item) {
                item.classList.remove('ok', 'alerta', 'erro', 'info');
                item.classList.add(n < 1 ? 'ok' : 'alerta');
            }
        });
    }

    function carregarContagem() {
        return fetch(URL_COUNT, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.ok ? r.json() : 0; }).then(function (j) {
            var n = typeof j === 'number' ? j : (j && (j.count ?? j.quantidade ?? 0)) || 0;
            atualizarBadge(n);
        }).catch(function () {});
    }

    function escapar(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function carregarLista() {
        var box = document.querySelector('[data-notif-lista]');
        if (!box) return;
        box.innerHTML = '<p class="notif-vazio">Carregando…</p>';
        fetch(URL_LIST, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.ok ? r.json() : []; }).then(function (data) {
            if (!Array.isArray(data) || !data.length) {
                box.innerHTML = '<p class="notif-vazio">Nenhuma notificação recente.</p>';
                return;
            }
            box.innerHTML = data.map(function (v) {
                var unread = !v.read_at;
                var dataStr = formatarDataHoraBr(v.created_at);
                return '<a class="notif-item ' + (unread ? 'unread' : 'read') + '" href="' + escapar(v.link || '#') + '" data-id="' + escapar(v.id) + '" data-notif-item>'
                    + '<p>' + (v.text || '') + '</p>'
                    + '<p class="date-notification">' + escapar(dataStr) + '</p></a>';
            }).join('');
        }).catch(function () {
            box.innerHTML = '<p class="notif-vazio">Não foi possível carregar.</p>';
        });
    }

    function postJson(url, body) {
        var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' };
        if (CSRF) headers['X-CSRF-TOKEN'] = CSRF;
        return fetch(url, { method: 'POST', headers: headers, credentials: 'same-origin', body: body ? JSON.stringify(body) : '{}' });
    }

    document.querySelectorAll('[data-user-drop]').forEach(function (wrap) {
        var btn = wrap.querySelector('.user-btn');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var aberto = wrap.classList.toggle('aberto');
            btn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
            document.querySelectorAll('[data-user-drop].aberto, [data-notif-drop].aberto').forEach(function (outro) {
                if (outro !== wrap) {
                    outro.classList.remove('aberto');
                    var b = outro.querySelector('button');
                    if (b) b.setAttribute('aria-expanded', 'false');
                }
            });
        });
    });

    document.querySelectorAll('[data-notif-drop]').forEach(function (wrap) {
        var btn = wrap.querySelector('[data-notif-btn]');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var aberto = wrap.classList.toggle('aberto');
            btn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
            document.querySelectorAll('[data-user-drop].aberto, [data-notif-drop].aberto').forEach(function (outro) {
                if (outro !== wrap) {
                    outro.classList.remove('aberto');
                    var b = outro.querySelector('button');
                    if (b) b.setAttribute('aria-expanded', 'false');
                }
            });
            if (aberto) { carregarLista(); carregarContagem(); }
        });
    });

    document.addEventListener('click', function (e) {
        var alvo = e.target;
        if (alvo && alvo.closest) {
            var marcar = alvo.closest('[data-notif-marcar-todas]');
            if (marcar) {
                e.preventDefault();
                e.stopPropagation();
                postJson(URL_READ_ALL).then(function () {
                    document.querySelectorAll('[data-notif-lista] .notif-item.unread').forEach(function (a) {
                        a.classList.remove('unread'); a.classList.add('read');
                    });
                    atualizarBadge(0);
                });
                return;
            }
            var item = alvo.closest('[data-notif-item]');
            if (item) {
                var id = item.getAttribute('data-id');
                if (id) {
                    postJson(URL_READ, { notifications: [id] });
                    item.classList.remove('unread'); item.classList.add('read');
                    carregarContagem();
                }
                return;
            }
            if (alvo.closest('[data-notif-drop] .notif-painel') || alvo.closest('[data-user-drop] .user-menu')) {
                return;
            }
        }
        document.querySelectorAll('[data-user-drop].aberto, [data-notif-drop].aberto').forEach(function (wrap) {
            wrap.classList.remove('aberto');
            var b = wrap.querySelector('button');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
        fecharKpiAjudas();
    });

    var kpiModal = document.getElementById('kpi-ajuda-modal');
    var kpiModalRotulo = document.getElementById('kpi-ajuda-modal-rotulo');
    var kpiModalCorpo = document.getElementById('kpi-ajuda-modal-corpo');
    var kpiModalBtn = null;
    var kpiPrevOverflow = '';

    function escaparHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fecharKpiAjudas() {
        document.querySelectorAll('[data-kpi-ajuda][aria-expanded="true"]').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
        if (kpiModal) {
            kpiModal.hidden = true;
        }
        if (kpiModalRotulo) kpiModalRotulo.textContent = '';
        if (kpiModalCorpo) kpiModalCorpo.textContent = '';
        kpiModalBtn = null;
        try {
            document.documentElement.style.overflow = kpiPrevOverflow;
        } catch (e) {}
        kpiPrevOverflow = '';
    }

    function abrirKpiAjuda(btn) {
        if (!kpiModal || !kpiModalRotulo || !kpiModalCorpo) return;
        var fonte = btn.querySelector('.kpi-ajuda-pop');
        var texto = String(btn.getAttribute('data-ajuda') || (fonte ? fonte.textContent : '') || '').trim();
        if (!texto) return;
        var rotulo = String(btn.getAttribute('data-kpi-rotulo') || 'Indicador');
        kpiModalRotulo.textContent = rotulo;
        kpiModalCorpo.textContent = texto;
        kpiModalBtn = btn;
        btn.setAttribute('aria-expanded', 'true');
        kpiPrevOverflow = document.documentElement.style.overflow || '';
        document.documentElement.style.overflow = 'hidden';
        kpiModal.hidden = false;
        var fechar = kpiModal.querySelector('.kpi-ajuda-modal-fechar');
        if (fechar) fechar.focus();
    }

    document.querySelectorAll('[data-kpi-ajuda]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var aberto = btn.getAttribute('aria-expanded') === 'true';
            if (aberto) {
                fecharKpiAjudas();
                return;
            }
            fecharKpiAjudas();
            abrirKpiAjuda(btn);
        });
    });

    if (kpiModal) {
        kpiModal.querySelectorAll('[data-kpi-ajuda-fechar]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                fecharKpiAjudas();
            });
        });
        var dialog = kpiModal.querySelector('.kpi-ajuda-modal-dialog');
        if (dialog) {
            dialog.addEventListener('click', function (e) { e.stopPropagation(); });
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') fecharKpiAjudas();
    });

    carregarContagem();
    setInterval(carregarContagem, 60000);
})();
</script>
@stack('scripts')
</body>
</html>

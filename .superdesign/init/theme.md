# Existing statistics design system

PHP server-rendered HTML and vanilla CSS. No frontend dependencies or build step. Light theme: text #1d2535, muted #586477, background #f4f5f9, border #dce1eb, brand #a81759, pale brand #fff0f6, header #171820. System UI / Segoe UI. Body 15px/1.55. Headings 28-38px and 18px. Container 1180px, gaps 16-24px, border radius 8-12px. Breakpoints 850px and 540px. Blue keyboard focus rings. White bordered cards. Respect reduced motion. Existing logo img/logos/logo.png.

```css
:root {
    color-scheme: light;
    --texto: #1d2535;
    --secundario: #586477;
    --fundo: #f4f5f9;
    --borda: #dce1eb;
    --marca: #a81759;
    --marca-clara: #fff0f6;
}

* { box-sizing: border-box; }
body.pagina-estatisticas { margin: 0; background: var(--fundo); color: var(--texto); font: 15px/1.55 system-ui, -apple-system, "Segoe UI", sans-serif; }
a { color: var(--marca); text-underline-offset: 3px; }
a:hover { text-decoration-thickness: 2px; }
a:focus-visible, button:focus-visible, input:focus-visible, .tabela-responsiva:focus-visible { outline: 3px solid #177bba; outline-offset: 4px; }
button, input { font: inherit; }
.pular-conteudo { position: absolute; top: -100px; left: 16px; z-index: 2; padding: 12px; background: white; }
.pular-conteudo:focus { top: 12px; }
body > header { background: #171820; color: #fff; padding: 8px max(24px, calc((100% - 1180px) / 2)); display: grid; grid-template-columns: 180px 1fr; align-items: center; column-gap: 36px; }
body > header img { width: 180px; height: 116px; object-fit: cover; object-position: center 60%; display: block; }
body > header nav { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 20px; }
body > header a { color: #f3f4fa; text-decoration: none; font-size: 14px; }
body > header a:hover { color: #ff9bc5; }
body > header form { margin: 0 0 0 auto; }
body > header button { border: 1px solid #636475; border-radius: 7px; background: transparent; color: white; padding: 6px 15px; cursor: pointer; }
body > header nav[aria-label="Administração"] { grid-column: 1 / -1; border-top: 1px solid #353642; gap: 0; }
body > header nav[aria-label="Administração"] a { padding: 14px 20px; border-bottom: 3px solid transparent; }
body > header a[aria-current="page"] { border-bottom-color: #ff6eab !important; color: #fff; }
.estatisticas { width: min(1180px, calc(100% - 48px)); margin: 38px auto 48px; }
.titulo-pagina { display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 28px; }
.sobretitulo { color: var(--marca); font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin: 0 0 6px; }
h1 { margin: 0; font-size: clamp(28px, 4vw, 38px); line-height: 1.2; letter-spacing: -.035em; }
h2 { font-size: 18px; line-height: 1.4; margin: 0; }
.subtitulo { color: var(--secundario); margin: 10px 0 0; }
.botao { display: inline-flex; justify-content: center; align-items: center; gap: 12px; border: 1px solid var(--marca); border-radius: 8px; padding: 10px 18px; background: var(--marca); color: white; font-weight: 650; text-decoration: none; cursor: pointer; white-space: nowrap; }
.botao:hover { background: #861044; color: white; }
.botao-secundario { background: white; color: var(--texto); border-color: var(--borda); }
.botao-secundario:hover { background: var(--marca-clara); color: var(--marca); border-color: #d69bb6; }
.filtro-periodo { display: flex; flex-wrap: wrap; align-items: end; gap: 16px; background: white; border: 1px solid var(--borda); border-radius: 12px; padding: 20px 24px; }
.campo { display: grid; gap: 6px; min-width: 180px; }
.campo label { font-size: 13px; font-weight: 650; }
.campo input { min-height: 44px; width: 100%; border: 1px solid #adb7c7; border-radius: 7px; color: var(--texto); padding: 8px 12px; background: #fff; }
.link-discreto { padding: 10px 0; font-size: 14px; }
.filtro-periodo > p { flex-basis: 100%; margin: 0; font-size: 12px; color: var(--secundario); }
.cabecalho-resumo { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin: 28px 0 16px; }
.cabecalho-resumo p { margin: 0; color: var(--secundario); font-size: 13px; }
.indicadores { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
.indicador { min-width: 0; border: 1px solid var(--borda); border-radius: 12px; background: white; padding: 22px 24px; }
.indicador h3 { margin: 0; color: var(--secundario); font-size: 13px; font-weight: 600; }
.indicador p { margin: 6px 0 0; color: var(--secundario); font-size: 12px; }
.indicador .valor-indicador { color: var(--texto); font-size: clamp(26px, 3vw, 34px); line-height: 1.3; font-weight: 750; letter-spacing: -.035em; font-variant-numeric: tabular-nums; }
.indicador .valor-monetario { font-size: clamp(21px, 2.5vw, 30px); overflow-wrap: anywhere; }
.indicador-destaque { border-color: #e2aac2; background: linear-gradient(120deg, #fff 20%, #fff0f6); }
.indicador-destaque .valor-indicador { color: var(--marca); }
.nota-valores { color: var(--secundario); font-size: 13px; margin: 16px 0 24px; }
.aviso { border: 1px solid #e6ce8c; border-radius: 10px; background: #fff9e9; color: #684c08; padding: 16px 20px; margin: 20px 0; }
.aviso-erro { border-color: #e8afb5; background: #fff0f1; color: #8a2230; }
.grade-paineis { display: grid; grid-template-columns: 1.1fr 1fr; gap: 20px; }
.painel { background: white; border: 1px solid var(--borda); border-radius: 12px; padding: 24px; margin-bottom: 20px; min-width: 0; }
.titulo-painel { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
.etiqueta { font-size: 11px; color: var(--secundario); background: #f2f4f8; border-radius: 5px; padding: 4px 8px; }
.lista-status { list-style: none; margin: 0; padding: 0; }
.lista-status li { margin-bottom: 16px; }
.lista-status li:last-child { margin-bottom: 0; }
.lista-status li > div { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; }
.lista-status p { color: var(--secundario); font-size: 12px; margin: 2px 0 0; }
meter { width: 100%; height: 14px; accent-color: var(--marca); }
meter::-webkit-meter-bar { border: none; border-radius: 20px; background: #edf0f5; }
meter::-webkit-meter-optimum-value { background: var(--marca); border-radius: 20px; }
meter::-moz-meter-bar { background: var(--marca); border-radius: 20px; }
.lista-valores { margin: 0; }
.lista-valores > div { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; border-bottom: 1px solid #eef0f5; padding: 16px 0; }
.lista-valores dt { color: var(--secundario); font-size: 14px; }
.lista-valores dd { margin: 0; font-weight: 700; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
.nota-painel { color: var(--secundario); font-size: 13px; margin: 20px 0; }
.tabela-responsiva { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 14px; font-variant-numeric: tabular-nums; }
caption { text-align: left; font-size: 12px; color: var(--secundario); margin-bottom: 12px; }
th { font-weight: 600; }
th, td { text-align: right; padding: 14px 16px; border-bottom: 1px solid #e8ecf2; white-space: nowrap; }
th:first-child, td:first-child { text-align: left; padding-left: 0; }
thead { background: #f8f9fc; color: var(--secundario); font-size: 12px; }
thead th:first-child { padding-left: 12px; }
tbody tr:last-child > * { border-bottom: 0; }
.barra-mensal { display: flex; align-items: center; gap: 14px; justify-content: end; min-width: 150px; }
.barra-mensal meter { width: min(180px, 70%); }
.barra-mensal strong { min-width: 28px; font-weight: 600; }
.estado-vazio { padding: 40px 24px; text-align: center; }
.estado-vazio > p:last-child { color: var(--secundario); margin: 10px 0 0; }
.rodape-estatisticas { width: min(1180px, calc(100% - 48px)); margin: 0 auto; padding: 0 0 24px; color: var(--secundario); font-size: 12px; }

@media (max-width: 850px) {
    body > header { grid-template-columns: 140px 1fr; padding: 8px 20px; gap: 12px; }
    body > header img { width: 140px; height: 110px; }
    body > header nav { gap: 8px 14px; }
    body > header form { margin-left: 0; }
    .indicadores { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grade-paineis { grid-template-columns: 1fr; gap: 0; }
}

@media (max-width: 540px) {
    body > header { grid-template-columns: 1fr; }
    body > header img { width: 126px; height: 90px; }
    body > header nav[aria-label="Administração"] a { padding: 12px 14px; }
    .estatisticas { width: calc(100% - 32px); margin-top: 24px; }
    .titulo-pagina { align-items: start; flex-direction: column; gap: 16px; }
    .filtro-periodo { padding: 18px; gap: 14px; }
    .campo { flex: 1 1 100%; min-width: 0; }
    .cabecalho-resumo { align-items: start; flex-direction: column; gap: 4px; }
    .indicadores { gap: 10px; }
    .indicador { padding: 16px 14px; }
    .indicador h3 { font-size: 12px; }
    .indicador .valor-monetario { font-size: 20px; }
    .painel { padding: 20px 16px; }
    .rodape-estatisticas { width: calc(100% - 32px); }
}

@media print {
    body > header, .pular-conteudo, .filtro-periodo, .titulo-pagina > a, .painel > a { display: none; }
    body.pagina-estatisticas { background: white; }
    .estatisticas { width: 100%; margin: 0; }
    .painel, .indicador { break-inside: avoid; box-shadow: none; }
    .tabela-responsiva { overflow: visible; }
}

```

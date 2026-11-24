---
title: Ako prispieť
description: Ako prispieť do omnipay-tatra - kontroly, testy s podpismi overenými cez openssl a táto dokumentácia.
---

# Ako prispieť

Pull requesty sú vítané na [tomaj/omnipay-tatra](https://github.com/tomaj/omnipay-tatra). Pravidlá projektu sú
v [CONTRIBUTING.md](https://github.com/tomaj/omnipay-tatra/blob/master/CONTRIBUTING.md); pre účasť platí
[kódex správania](https://github.com/tomaj/omnipay-tatra/blob/master/CODE_OF_CONDUCT.md). Zraniteľnosti hláste
neverejne, pozrite [SECURITY.md](https://github.com/tomaj/omnipay-tatra/blob/master/SECURITY.md).

## Kontroly {#checks}

```bash
composer install
composer check      # code style, PHPStan (level max), Rector, PHPUnit
composer coverage   # HTML and Clover report in build/ (Xdebug or PCOV)
composer infection  # mutation testing
```

CI spúšťa testy na PHP 8.4 (aj s najnižšími verziami závislostí), 8.5 a 8.6, vyžaduje 95 % pokrytia príkazov a
spúšťa Infection; nočný job na PHP 8.7 môže zlyhať.

## Podpisy v testoch {#signatures-in-tests}

Očakávané podpisy sa počítajú nezávisle cez `openssl` CLI, nikdy vlastnými podpisovačmi knižnice; príkazy sú na stránke
[Podpisy](/sk/guide/signatures). DES potrebuje legacy provider OpenSSL; bez neho sa DES testy lokálne preskočia, CI ich
spúšťa so zapnutým providerom.

## Táto dokumentácia {#this-documentation}

Stránka je postavená na VitePress v priečinku `docs/`. Anglické stránky sú v koreni `docs/`, slovenské v `docs/sk/`
s rovnakými cestami; kontrola v CI (`npm run docs:locales`) zlyhá, keď niektorá stránka nemá náprotivok v druhom
jazyku. Kód sa na stránky dostáva tromi spôsobmi a všetok beží v CI:

| Kód | Nachádza sa v | Beží v |
|---|---|---|
| kompletné recepty | `examples/<name>/` (`run.php`, `e2e.sh`, `expected-output.txt`) | workflow Examples, proti simulátoru payment-simulator |
| krátke ukážky | `docs/snippets/*.php`, vkladané cez `<<< @/snippets/...#region` | `tests/DocsSnippetsTest.php` |
| inline bloky ```php v README.md, UPGRADE-5.0.md a `docs/` | priamo v Markdowne | `tests/DocsSnippetsTest.php`, ak im nepredchádza `<!-- snippet: skip -->` |

Výstup príkladu sa porovnáva s jeho `expected-output.txt`: po zámernej zmene príklad spustite a commitnite nový
výstup. Jeden príklad spustíte príkazom `examples/ci/example.sh <name>`.

```bash
cd docs
npm ci
npm run docs:dev    # local preview
npm run docs:links  # build and check every link, as CI does
npm run images      # after a change to scripts/illustrations.py: SVGs, og.png, icons (needs Chrome)
```

Logo, ilustrácia na úvodnej stránke, diagramy a obrázok pre sociálne siete sa generujú skriptom
`docs/scripts/illustrations.py`; ilustrácia a diagramy v angličtine aj v slovenčine (súbory s `-sk`). Upravujte
skript, nie SVG súbory.

#!/usr/bin/env python3
"""Writes the logo, favicon, hero illustration, diagrams and social card SVGs into docs/public.

The hero and the diagrams are written in English and in Slovak (`-sk` files, used by the pages under docs/sk/).

Each drawing exists once, with colour tokens; the light and dark files only swap the palette. Run it after a change,
then `node scripts/og-image.mjs` for the PNG icons, og.png and og-sk.png (or `npm run images` for both).

The omnipay-* packages and payment-simulator share the mark: a rounded square in the bank's primary colour with a
payment card in its accent colour, a magnetic stripe and a check mark cut out of it. Tatra banka is black and yellow.
"""

from __future__ import annotations

from pathlib import Path

PUBLIC = Path(__file__).resolve().parents[1] / "public"
NAME = "omnipay-tatra"

# The mark of the omnipay family: square = primary, card = accent, stripe and marks = primary.
MARK = """<rect x="2" y="2" width="60" height="60" rx="14" fill="{square}"/>
  <rect x="11" y="17" width="42" height="30" rx="5" fill="{card}"/>
  <rect x="11" y="23" width="42" height="6" fill="{square}"/>
  <path d="M17 39h11" fill="none" stroke="{square}" stroke-width="3.5" stroke-linecap="round"/>
  <path d="M37 39.5l4.5 4 8-9" fill="none" stroke="{square}" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>"""

LIGHT = {
    "square": "#141414", "card": "#ffd200",
    "ink": "#18181b", "muted": "#52525b", "panel": "#ffffff", "line": "#d4d4d8", "bar": "#f4f4f5",
    "yellow": "#ffd200", "on_yellow": "#141414", "plastic": "#141414", "stripe": "#3f3f46", "on_plastic": "#fafafa",
    "ok": "#15803d", "ok_soft": "#ecfdf3", "arrow": "#a16207", "bg": "#fffbea",
}
DARK = {
    "square": "#ffd200", "card": "#141414",
    "ink": "#f4f4f5", "muted": "#a1a1aa", "panel": "#202024", "line": "#3f3f46", "bar": "#2a2a2f",
    "yellow": "#ffd200", "on_yellow": "#141414", "plastic": "#0a0a0a", "stripe": "#3f3f46", "on_plastic": "#fafafa",
    "ok": "#4ade80", "ok_soft": "#11261a", "arrow": "#ffd200", "bg": "#1b1b1f",
}

FONT = "font-family=\"Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif\""
MONO = "font-family=\"ui-monospace, SFMono-Regular, Menlo, Consolas, monospace\""


def logo(c: dict[str, str]) -> str:
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" role="img" aria-label="{NAME}">
  <title>{NAME}</title>
  {MARK.format(**c)}
</svg>
"""


def check(x: float, y: float, color: str, width: float = 2.5) -> str:
    return f'<path d="M{x} {y}l4 4 8-9" fill="none" stroke="{color}" stroke-width="{width}" stroke-linecap="round" stroke-linejoin="round"/>'


def hero(c: dict[str, str], t: dict[str, str]) -> str:
    """The flow: the shop redirects with a signed request, the bank sends the customer back, completePurchase() verifies."""
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 520 440" width="520" height="440" role="img" aria-labelledby="t d">
  <title id="t">{t['title']}</title>
  <desc id="d">{t['desc']}</desc>
  <defs>
    <marker id="arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M0 0L10 5L0 10z" fill="{c['arrow']}"/>
    </marker>
  </defs>

  <!-- the shop -->
  <rect x="16" y="20" width="232" height="176" rx="14" fill="{c['panel']}" stroke="{c['line']}" stroke-width="1.5"/>
  <path d="M16 34a14 14 0 0 1 14-14h204a14 14 0 0 1 14 14v12H16z" fill="{c['bar']}"/>
  <circle cx="34" cy="34" r="4" fill="{c['line']}"/><circle cx="48" cy="34" r="4" fill="{c['line']}"/><circle cx="62" cy="34" r="4" fill="{c['line']}"/>
  <text x="80" y="38" {FONT} font-size="12" fill="{c['muted']}">{t['url']}</text>
  <text x="36" y="78" {FONT} font-size="14" fill="{c['muted']}">{t['order']}</text>
  <text x="36" y="112" {FONT} font-size="28" font-weight="700" fill="{c['ink']}">{t['amount']}</text>
  <rect x="36" y="134" width="124" height="40" rx="9" fill="{c['yellow']}"/>
  <text x="98" y="159" {FONT} font-size="15" font-weight="700" fill="{c['on_yellow']}" text-anchor="middle">{t['pay']}</text>

  <!-- signed redirect -->
  <path d="M252 72C350 72 392 78 392 112" fill="none" stroke="{c['arrow']}" stroke-width="2.5" stroke-dasharray="6 5" marker-end="url(#arrow)"/>
  <text x="268" y="58" {MONO} font-size="12" fill="{c['ink']}">purchase()</text>
  <text x="268" y="96" {FONT} font-size="11" fill="{c['muted']}">{t['redirect']}</text>

  <!-- the bank's payment page -->
  <rect x="272" y="120" width="232" height="190" rx="14" fill="{c['panel']}" stroke="{c['line']}" stroke-width="1.5"/>
  <path d="M272 134a14 14 0 0 1 14-14h204a14 14 0 0 1 14 14v26H272z" fill="{c['yellow']}"/>
  <text x="290" y="146" {FONT} font-size="14" font-weight="700" fill="{c['on_yellow']}">Tatra banka</text>
  <rect x="290" y="174" width="124" height="78" rx="9" fill="{c['plastic']}" stroke="{c['line']}" stroke-width="1"/>
  <rect x="290" y="188" width="124" height="12" fill="{c['stripe']}"/>
  <rect x="300" y="210" width="20" height="15" rx="3" fill="{c['yellow']}"/>
  <text x="300" y="242" {MONO} font-size="11" fill="{c['on_plastic']}">•••• 1111</text>
  <text x="426" y="190" {MONO} font-size="11" fill="{c['muted']}">AMT 24.90</text>
  <text x="426" y="208" {MONO} font-size="11" fill="{c['muted']}">VS 2026001</text>
  <text x="426" y="226" {MONO} font-size="11" fill="{c['muted']}">HMAC</text>
  {check(461, 219, c['ok'], 2.2)}
  <rect x="290" y="264" width="196" height="32" rx="8" fill="{c['ink']}"/>
  <text x="388" y="285" {FONT} font-size="13" font-weight="600" fill="{c['panel']}" text-anchor="middle">{t['confirm']}</text>

  <!-- signed return -->
  <path d="M392 314C392 364 352 370 272 370" fill="none" stroke="{c['arrow']}" stroke-width="2.5" stroke-dasharray="6 5" marker-end="url(#arrow)"/>
  <text x="404" y="346" {MONO} font-size="12" fill="{c['ink']}">RES=OK</text>
  <text x="404" y="364" {FONT} font-size="11" fill="{c['muted']}">{t['return']}</text>

  <!-- verification -->
  <rect x="16" y="318" width="250" height="104" rx="14" fill="{c['ok_soft']}" stroke="{c['ok']}" stroke-width="1.5"/>
  <circle cx="56" cy="370" r="22" fill="{c['ok']}"/>
  {check(46, 370, c['ok_soft'], 3.5)}
  <text x="92" y="354" {MONO} font-size="13" font-weight="700" fill="{c['ink']}">completePurchase()</text>
  <text x="92" y="378" {FONT} font-size="12" fill="{c['muted']}">{t['verified']}</text>
  <text x="92" y="402" {FONT} font-size="13" font-weight="700" fill="{c['ok']}">{t['paid']}</text>
</svg>
"""


# The social card tagline per language: og.svg (English) and og-sk.svg (pages under docs/sk/).
OG = {
    "": {"label": "Tatra banka payments for Omnipay", "line1": "Tatra banka payments for Omnipay:", "line2": "TatraPay, CardPay, ComfortPay"},
    "-sk": {"label": "platby Tatra banky pre Omnipay", "line1": "Platby Tatra banky pre Omnipay:", "line2": "TatraPay, CardPay, ComfortPay"},
}


def og(c: dict[str, str], suffix: str = "") -> str:
    """The 1200 × 630 social card, in English or Slovak (suffix "-sk")."""
    mark = MARK.format(**c)
    t = OG[suffix]
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630" role="img" aria-label="{NAME}: {t['label']}">
  <rect width="1200" height="630" fill="{c['bg']}"/>
  <rect x="0" y="0" width="1200" height="14" fill="{c['yellow']}"/>
  <g transform="translate(80 110) scale(2.6)">
  {mark}
  </g>
  <text x="80" y="370" {FONT} font-size="84" font-weight="800" fill="{c['ink']}">{NAME}</text>
  <text x="80" y="432" {FONT} font-size="34" fill="{c['muted']}">{t['line1']}</text>
  <text x="80" y="476" {FONT} font-size="34" fill="{c['muted']}">{t['line2']}</text>
  <text x="80" y="566" {MONO} font-size="26" fill="{c['ink']}">composer require tomaj/omnipay-tatra</text>
  <g transform="translate(700 70) scale(0.95)">
    {hero(c, HERO[suffix]).split('</defs>', 1)[1].rsplit('</svg>', 1)[0]}
  </g>
  <defs>
    <marker id="arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M0 0L10 5L0 10z" fill="{c['arrow']}"/>
    </marker>
  </defs>
</svg>
"""


def sequence(c: dict[str, str], title: str, desc: str, lanes: list[str], steps: list[tuple]) -> str:
    """A sequence diagram. A step is (from, to, label, note) for a message or (lane, None, label, note) for an action."""
    width = 760
    gap = width / len(lanes)
    xs = [gap / 2 + i * gap for i in range(len(lanes))]
    top = 76
    height = top + 30 + 58 * len(steps)
    out = [f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} {height}" width="{width}" height="{height}" role="img" aria-labelledby="t d">
  <title id="t">{title}</title>
  <desc id="d">{desc}</desc>
  <defs>
    <marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M0 0L10 5L0 10z" fill="{c['arrow']}"/>
    </marker>
  </defs>"""]
    for x, lane in zip(xs, lanes):
        out.append(f'  <rect x="{x - 105:.0f}" y="12" width="210" height="44" rx="10" fill="{c["panel"]}" stroke="{c["line"]}" stroke-width="1.5"/>')
        out.append(f'  <text x="{x:.0f}" y="40" {FONT} font-size="15" font-weight="700" fill="{c["ink"]}" text-anchor="middle">{lane}</text>')
        out.append(f'  <path d="M{x:.0f} 56V{height - 10}" stroke="{c["line"]}" stroke-width="1.5" stroke-dasharray="4 5"/>')
    y = top
    for number, (src, dst, label, note) in enumerate(steps, 1):
        y += 58
        if dst is None:
            x = xs[src]
            out.append(f'  <rect x="{x - 112:.0f}" y="{y - 40}" width="224" height="48" rx="9" fill="{c["ok_soft"]}" stroke="{c["ok"]}" stroke-width="1.3"/>')
            out.append(f'  <text x="{x:.0f}" y="{y - 20}" {MONO} font-size="12.5" font-weight="700" fill="{c["ink"]}" text-anchor="middle">{number}. {label}</text>')
            out.append(f'  <text x="{x:.0f}" y="{y - 3}" {FONT} font-size="11.5" fill="{c["muted"]}" text-anchor="middle">{note}</text>')
            continue
        x1, x2 = xs[src], xs[dst]
        direction = 1 if x2 > x1 else -1
        out.append(f'  <path d="M{x1 + direction * 4:.0f} {y}H{x2 - direction * 6:.0f}" stroke="{c["arrow"]}" stroke-width="2.2" marker-end="url(#arrow)"/>')
        mid = (x1 + x2) / 2
        out.append(f'  <text x="{mid:.0f}" y="{y - 24}" {MONO} font-size="12.5" font-weight="700" fill="{c["ink"]}" text-anchor="middle">{number}. {label}</text>')
        out.append(f'  <text x="{mid:.0f}" y="{y - 8}" {FONT} font-size="11.5" fill="{c["muted"]}" text-anchor="middle">{note}</text>')
    out.append("</svg>\n")
    return "\n".join(out)


# Labels per language. English files keep their names; Slovak files get a -sk suffix
# (illustrations/hero-sk-light.svg, diagrams/flow-tatrapay-sk-dark.svg …) and are used by the pages under docs/sk/.
HERO = {
    "": {
        "title": "TatraPay payment flow",
        "desc": "The shop redirects the customer to Tatra banka with a signed request, the bank sends the customer back with a signed result, and completePurchase() verifies the signature and the variable symbol before the order is paid.",
        "url": "shop.example/checkout", "order": "Order 2026001", "amount": "24.90 €", "pay": "Pay by card",
        "redirect": "signed redirect", "confirm": "Confirm payment", "return": "signed return",
        "verified": "signature and VS verified", "paid": "order 2026001 paid",
    },
    "-sk": {
        "title": "Priebeh platby TatraPay",
        "desc": "E-shop presmeruje zákazníka do Tatra banky s podpísanou požiadavkou, banka ho vráti s podpísaným výsledkom a completePurchase() overí podpis a variabilný symbol skôr, než sa objednávka označí ako zaplatená.",
        "url": "obchod.example/pokladna", "order": "Objednávka 2026001", "amount": "24,90 €", "pay": "Zaplatiť",
        "redirect": "podpísaná žiadosť", "confirm": "Potvrdiť platbu", "return": "podpísaný návrat",
        "verified": "podpis a VS overené", "paid": "objednávka zaplatená",
    },
}

DIAGRAMS = {
    "": {
        "flow-tatrapay": (
            "TatraPay and CardPay payment flow",
            "The customer pays in the shop, purchase() redirects them to Tatra banka with a signed request, the bank redirects them back to the return URL with a signed result, and completePurchase() verifies the signature and the variable symbol before the shop marks the order paid.",
            ["Customer's browser", "Your shop", "Tatra banka"],
            [
                (0, 1, "Pay 24.90 €", "the order gets VS 2026001"),
                (1, 0, "302 to the bank", "purchase(): MID AMT VS RURL … HMAC"),
                (0, 2, "GET payment page", "the signed query string"),
                (2, None, "the customer pays", "card, internet banking"),
                (2, 0, "302 to RURL", "VS RES … TIMESTAMP HMAC"),
                (0, 1, "GET /payment/return", "the bank's signed answer"),
                (1, None, "completePurchase()", "signature + VS, then isSuccessful()"),
            ],
        ),
        "flow-preauthorization": (
            "CardPay pre-authorization, capture and refund",
            "authorize() redirects the customer to block the amount, completeAuthorize() verifies the return and gives the transaction id, and cancelAuthorize() captures, releases or refunds it with a server-to-server request whose XML answer is signed.",
            ["Customer's browser", "Your shop", "Tatra banka"],
            [
                (1, 0, "302 to the bank", "authorize(): TXN=PA, 3-D Secure data"),
                (0, 2, "GET payment page", "the card is checked, the amount blocked"),
                (2, 0, "302 to RURL", "RES=OK TID AC CC … HMAC"),
                (0, 1, "GET return URL", "the bank's signed answer"),
                (1, None, "completeAuthorize()", "verify, store the TID"),
                (1, 2, "cancelAuthorize()", "TXN=CPA capture · SPA release · CB refund"),
                (2, 1, "XML &lt;res&gt;OK&lt;/res&gt;", "Authorization: HMAC=… over the body"),
            ],
        ),
        "flow-comfortpay": (
            "ComfortPay card registration and recurring charges",
            "The first payment registers the card (TPAY=Y) and the return carries the card id CID; later charges go to the ComfortPay SOAP web service with the CID, authenticated by the merchant's client certificate.",
            ["Customer's browser", "Your shop", "Tatra banka"],
            [
                (1, 0, "302 to the bank", "purchase(): TPAY=Y"),
                (2, 0, "302 to RURL", "RES=OK CID=111111 … HMAC"),
                (0, 1, "GET return URL", "the bank's signed answer"),
                (1, None, "completePurchase()", "verify, store the CID"),
                (1, 2, "charge() over SOAP", "referedCardId=CID, client certificate"),
                (2, 1, "transactionStatus 00", "approved; a fault with a code otherwise"),
            ],
        ),
    },
    "-sk": {
        "flow-tatrapay": (
            "Priebeh platby TatraPay a CardPay",
            "Zákazník zaplatí v e-shope, purchase() ho presmeruje do Tatra banky s podpísanou požiadavkou, banka ho vráti na návratovú URL s podpísaným výsledkom a completePurchase() overí podpis a variabilný symbol skôr, než e-shop označí objednávku ako zaplatenú.",
            ["Prehliadač zákazníka", "Váš e-shop", "Tatra banka"],
            [
                (0, 1, "Zaplatiť 24,90 €", "objednávka dostane VS 2026001"),
                (1, 0, "302 do banky", "purchase(): MID AMT VS RURL … HMAC"),
                (0, 2, "GET platobná stránka", "podpísané parametre v URL"),
                (2, None, "zákazník zaplatí", "karta, internet banking"),
                (2, 0, "302 na RURL", "VS RES … TIMESTAMP HMAC"),
                (0, 1, "GET /payment/return", "podpísaná odpoveď banky"),
                (1, None, "completePurchase()", "podpis + VS, potom isSuccessful()"),
            ],
        ),
        "flow-preauthorization": (
            "CardPay: predautorizácia, dokončenie a vrátenie platby",
            "authorize() presmeruje zákazníka, aby sa suma zablokovala, completeAuthorize() overí návrat a vráti ID transakcie a cancelAuthorize() predautorizáciu dokončí, zruší alebo platbu vráti server-to-server požiadavkou, na ktorú banka odpovie podpísaným XML.",
            ["Prehliadač zákazníka", "Váš e-shop", "Tatra banka"],
            [
                (1, 0, "302 do banky", "authorize(): TXN=PA, údaje pre 3-D Secure"),
                (0, 2, "GET platobná stránka", "karta sa overí, suma sa zablokuje"),
                (2, 0, "302 na RURL", "RES=OK TID AC CC … HMAC"),
                (0, 1, "GET návratová URL", "podpísaná odpoveď banky"),
                (1, None, "completeAuthorize()", "overiť, uložiť TID"),
                (1, 2, "cancelAuthorize()", "TXN=CPA zúčtovať · SPA zrušiť · CB vrátiť"),
                (2, 1, "XML &lt;res&gt;OK&lt;/res&gt;", "Authorization: HMAC=… z tela odpovede"),
            ],
        ),
        "flow-comfortpay": (
            "ComfortPay: registrácia karty a opakované platby",
            "Prvá platba zaregistruje kartu (TPAY=Y) a návrat obsahuje ID karty CID; ďalšie platby idú cez webovú službu ComfortPay (SOAP) s CID, overené klientskym certifikátom obchodníka.",
            ["Prehliadač zákazníka", "Váš e-shop", "Tatra banka"],
            [
                (1, 0, "302 do banky", "purchase(): TPAY=Y"),
                (2, 0, "302 na RURL", "RES=OK CID=111111 … HMAC"),
                (0, 1, "GET návratová URL", "podpísaná odpoveď banky"),
                (1, None, "completePurchase()", "overiť, uložiť CID"),
                (1, 2, "charge() cez SOAP", "referedCardId=CID, klientsky certifikát"),
                (2, 1, "transactionStatus 00", "schválené; inak chyba s kódom"),
            ],
        ),
    },
}


def write(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(text, encoding="utf-8")
    print(f"wrote {path.relative_to(PUBLIC.parent)}")


def main() -> None:
    write(PUBLIC / "logo.svg", logo(LIGHT))
    write(PUBLIC / "logo-dark.svg", logo(DARK))
    write(PUBLIC / "favicon.svg", logo(LIGHT))
    for suffix, strings in HERO.items():
        write(PUBLIC / "illustrations" / f"hero{suffix}-light.svg", hero(LIGHT, strings))
        write(PUBLIC / "illustrations" / f"hero{suffix}-dark.svg", hero(DARK, strings))
    for suffix in OG:
        write(PUBLIC / f"og{suffix}.svg", og(LIGHT, suffix))
    for suffix, diagrams in DIAGRAMS.items():
        for name, (title, desc, lanes, steps) in diagrams.items():
            write(PUBLIC / "diagrams" / f"{name}{suffix}-light.svg", sequence(LIGHT, title, desc, lanes, steps))
            write(PUBLIC / "diagrams" / f"{name}{suffix}-dark.svg", sequence(DARK, title, desc, lanes, steps))


if __name__ == "__main__":
    main()

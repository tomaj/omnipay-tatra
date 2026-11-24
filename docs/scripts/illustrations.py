#!/usr/bin/env python3
"""Writes the logo, favicon, hero illustration and social card SVGs into docs/public.

Each drawing exists once, with colour tokens; the light and dark files only swap the palette. Run it after a change,
then `node scripts/og-image.mjs` for the PNG icons and og.png (or `npm run images` for both).

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


def hero(c: dict[str, str]) -> str:
    """The flow: the shop redirects with a signed request, the bank sends the customer back, completePurchase() verifies."""
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 520 440" width="520" height="440" role="img" aria-labelledby="t d">
  <title id="t">TatraPay payment flow</title>
  <desc id="d">The shop redirects the customer to Tatra banka with a signed request, the bank sends the customer back with a signed result, and completePurchase() verifies the signature and the variable symbol before the order is paid.</desc>
  <defs>
    <marker id="arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M0 0L10 5L0 10z" fill="{c['arrow']}"/>
    </marker>
  </defs>

  <!-- the shop -->
  <rect x="16" y="20" width="232" height="176" rx="14" fill="{c['panel']}" stroke="{c['line']}" stroke-width="1.5"/>
  <path d="M16 34a14 14 0 0 1 14-14h204a14 14 0 0 1 14 14v12H16z" fill="{c['bar']}"/>
  <circle cx="34" cy="34" r="4" fill="{c['line']}"/><circle cx="48" cy="34" r="4" fill="{c['line']}"/><circle cx="62" cy="34" r="4" fill="{c['line']}"/>
  <text x="80" y="38" {FONT} font-size="12" fill="{c['muted']}">shop.example/checkout</text>
  <text x="36" y="78" {FONT} font-size="14" fill="{c['muted']}">Order 2026001</text>
  <text x="36" y="112" {FONT} font-size="28" font-weight="700" fill="{c['ink']}">24.90 €</text>
  <rect x="36" y="134" width="124" height="40" rx="9" fill="{c['yellow']}"/>
  <text x="98" y="159" {FONT} font-size="15" font-weight="700" fill="{c['on_yellow']}" text-anchor="middle">Pay by card</text>

  <!-- signed redirect -->
  <path d="M252 72C350 72 392 78 392 112" fill="none" stroke="{c['arrow']}" stroke-width="2.5" stroke-dasharray="6 5" marker-end="url(#arrow)"/>
  <text x="268" y="58" {MONO} font-size="12" fill="{c['ink']}">purchase()</text>
  <text x="268" y="96" {FONT} font-size="11" fill="{c['muted']}">signed redirect</text>

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
  <text x="388" y="285" {FONT} font-size="13" font-weight="600" fill="{c['panel']}" text-anchor="middle">Confirm payment</text>

  <!-- signed return -->
  <path d="M392 314C392 364 352 370 272 370" fill="none" stroke="{c['arrow']}" stroke-width="2.5" stroke-dasharray="6 5" marker-end="url(#arrow)"/>
  <text x="404" y="346" {MONO} font-size="12" fill="{c['ink']}">RES=OK</text>
  <text x="404" y="364" {FONT} font-size="11" fill="{c['muted']}">signed return</text>

  <!-- verification -->
  <rect x="16" y="318" width="250" height="104" rx="14" fill="{c['ok_soft']}" stroke="{c['ok']}" stroke-width="1.5"/>
  <circle cx="56" cy="370" r="22" fill="{c['ok']}"/>
  {check(46, 370, c['ok_soft'], 3.5)}
  <text x="92" y="354" {MONO} font-size="13" font-weight="700" fill="{c['ink']}">completePurchase()</text>
  <text x="92" y="378" {FONT} font-size="12" fill="{c['muted']}">signature and VS verified</text>
  <text x="92" y="402" {FONT} font-size="13" font-weight="700" fill="{c['ok']}">order 2026001 paid</text>
</svg>
"""


def og(c: dict[str, str]) -> str:
    """The 1200 × 630 social card."""
    mark = MARK.format(**c)
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630" role="img" aria-label="{NAME}: Tatra banka payments for Omnipay">
  <rect width="1200" height="630" fill="{c['bg']}"/>
  <rect x="0" y="0" width="1200" height="14" fill="{c['yellow']}"/>
  <g transform="translate(80 110) scale(2.6)">
  {mark}
  </g>
  <text x="80" y="370" {FONT} font-size="84" font-weight="800" fill="{c['ink']}">{NAME}</text>
  <text x="80" y="432" {FONT} font-size="34" fill="{c['muted']}">Tatra banka payments for Omnipay:</text>
  <text x="80" y="476" {FONT} font-size="34" fill="{c['muted']}">TatraPay, CardPay, ComfortPay</text>
  <text x="80" y="566" {MONO} font-size="26" fill="{c['ink']}">composer require tomaj/omnipay-tatra</text>
  <g transform="translate(700 70) scale(0.95)">
    {hero(c).split('</defs>', 1)[1].rsplit('</svg>', 1)[0]}
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


DIAGRAMS = {
    "flow-tatrapay": (
        "TatraPay and CardPay payment flow",
        "The customer pays in the shop, purchase() redirects them to Tatra banka with a signed request, the bank redirects them back to the return URL with a signed result, and completePurchase() verifies the signature and the variable symbol before the shop marks the order paid.",
        ["Customer's browser", "Your shop", "Tatra banka"],
        [
            (0, 1, "Pay 24.90 €", "the order gets VS 2026001"),
            (1, 0, "302 to the bank", "purchase(): MID AMT CURR VS RURL … HMAC"),
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
}


def write(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(text, encoding="utf-8")
    print(f"wrote {path.relative_to(PUBLIC.parent)}")


def main() -> None:
    write(PUBLIC / "logo.svg", logo(LIGHT))
    write(PUBLIC / "logo-dark.svg", logo(DARK))
    write(PUBLIC / "favicon.svg", logo(LIGHT))
    write(PUBLIC / "illustrations" / "hero-light.svg", hero(LIGHT))
    write(PUBLIC / "illustrations" / "hero-dark.svg", hero(DARK))
    write(PUBLIC / "og.svg", og(LIGHT))
    for name, (title, desc, lanes, steps) in DIAGRAMS.items():
        write(PUBLIC / "diagrams" / f"{name}-light.svg", sequence(LIGHT, title, desc, lanes, steps))
        write(PUBLIC / "diagrams" / f"{name}-dark.svg", sequence(DARK, title, desc, lanes, steps))


if __name__ == "__main__":
    main()

---
title: E-shop s návratovou URL
description: Minimálny e-shop na vstavanom PHP serveri s pokladňou cez TatraPay a spracovaním návratovej URL, ktoré overí návrat z banky, označí objednávku ako zaplatenú len raz a odmietne upravené aj znovu poslané návraty.
---

# E-shop s návratovou URL

Ostatné príklady volajú knižnicu z jedného skriptu. Tento je skutočná webová aplikácia: e-shop na vstavanom PHP serveri
s tromi URL a druhý skript, ktorý hrá rolu prehliadača zákazníka. Ide o tú časť integrácie, na ktorú útočníci skúšajú
útočiť: návratovú URL.

Zdrojový kód: [`examples/shop/`](https://github.com/tomaj/omnipay-tatra/tree/master/examples/shop) ·
spustíte ho príkazom `examples/ci/example.sh shop`.

| URL | Čo robí |
|---|---|
| `GET /checkout?amount=24.90` | vytvorí objednávku s ďalším variabilným symbolom a presmeruje na TatraPay |
| `GET /payment/return?...` | návrat z banky: overí ho, potom zmení objednávku |
| `GET /orders/<vs>` | zobrazí stav objednávky |

Objednávky sú uložené v JSON súbore
([`src/Orders.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/shop/src/Orders.php)), takže
príklad nepotrebuje databázu.

## Brána {#the-gateway}

<<< @/../examples/shop/public/index.php#gateway

`Omnipay::create()` vytvorí bránu s aktuálnou HTTP požiadavkou, takže `completePurchase()` si query string od banky
prečíta sám. Kľúč sa berie z premennej prostredia; náhradná hodnota je verejný testovací kľúč simulátora.

## Pokladňa {#checkout}

<<< @/../examples/shop/public/index.php#checkout

## Návratová URL {#the-return-url}

<<< @/../examples/shop/public/index.php#return

Na poradí kontrol záleží:

1. **Objednávku nájdite podľa vráteného `VS`**, potom overte návrat z banky voči hodnotám uloženým pri tejto
   objednávke, nikdy nie voči hodnotám z query stringu.
2. **Overenie je prvé.** Na odmietnutý návrat e-shop odpovie `400` a nič nezmení.
3. **Objednávku zmeňte len raz.** Opakované načítanie návratovej URL je platný, správne podpísaný návrat; e-shop
   odpovie „už zaplatené“ namiesto toho, aby tovar odoslal dvakrát.
4. **`TOUT` znamená čaká sa, nie zaplatené.** Banka rozhodne neskôr a výsledok pošle e-mailom na adresu `rem`.

## Zákazník {#the-customer}

[`customer.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/shop/customer.php) otvorí pokladňu,
nasleduje presmerovanie do simulátora, vyberie odpoveď a otvorí návratovú URL, pričom vypíše, čo e-shop odpovedal.
Okrem toho zmení `RES=FAIL` neúspešnej platby na `RES=OK` a pošle návrat zaplatenej objednávky s variabilným symbolom
inej objednávky.

## Výstup {#output}

<<< @/../examples/shop/expected-output.txt{text}

## Čo príklad testuje {#what-it-tests}

- `$response->redirect()` odpovie `302` na simulátor, ktorý prijme podpis;
- úspešný návrat označí objednávku ako zaplatenú, opakované načítanie ju nezaplatí druhýkrát;
- neúspešná platba a platba s vypršaným časom nastavia `failed` a `pending`;
- upravený výsledok a zmenený variabilný symbol sa odmietnu s `400` a objednávka zostane nezmenená.

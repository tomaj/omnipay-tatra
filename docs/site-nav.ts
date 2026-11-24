// Navigation and sidebar. English lives at the site root; Slovak mirrors every page under /sk/.
import type { DefaultTheme } from 'vitepress'

export const nav: DefaultTheme.NavItem[] = [
  { text: 'Guide', link: '/guide/getting-started', activeMatch: '^/guide/' },
  { text: 'Examples', link: '/examples/', activeMatch: '^/examples/' },
  { text: 'API', link: '/api/reference' },
  { text: 'Changelog', link: '/changelog' },
  {
    text: '5.x',
    items: [
      { text: '5.x (master)', link: '/' },
      { text: '4.x (README of 4.3.0)', link: 'https://github.com/tomaj/omnipay-tatra/blob/4.3.0/readme.md' },
      { text: 'Upgrading to 5.0', link: '/upgrade' },
    ],
  },
]

export const sidebar: DefaultTheme.SidebarItem[] = [
  {
    text: 'Introduction',
    items: [
      { text: 'Getting started', link: '/guide/getting-started' },
      { text: 'How a payment works', link: '/guide/payment-flow' },
      { text: 'Configuration', link: '/guide/configuration' },
      { text: 'Test mode', link: '/guide/test-mode' },
    ],
  },
  {
    text: 'Gateways',
    items: [
      { text: 'TatraPay', link: '/guide/tatrapay' },
      { text: 'CardPay', link: '/guide/cardpay' },
      { text: 'ComfortPay', link: '/guide/comfortpay' },
    ],
  },
  {
    text: 'In production',
    items: [
      { text: 'Signatures', link: '/guide/signatures' },
      { text: 'Error handling', link: '/guide/errors' },
      { text: 'Testing your integration', link: '/guide/testing' },
      { text: 'Security', link: '/security' },
    ],
  },
  {
    text: 'Examples',
    items: [
      { text: 'Overview', link: '/examples/' },
      { text: 'TatraPay round trip', link: '/examples/tatrapay' },
      { text: 'CardPay with HMAC and AES', link: '/examples/cardpay' },
      { text: 'Pre-authorization and capture', link: '/examples/cardpay-preauthorization' },
      { text: 'ComfortPay subscription', link: '/examples/comfortpay-recurring' },
      { text: 'Shop with a return URL', link: '/examples/shop' },
      { text: 'Signatures and openssl', link: '/examples/signatures' },
    ],
  },
  {
    text: 'Reference',
    items: [
      { text: 'API reference', link: '/api/reference' },
      { text: 'FAQ', link: '/faq' },
      { text: 'Upgrading to 5.0', link: '/upgrade' },
      { text: 'Changelog', link: '/changelog' },
      { text: 'Contributing', link: '/contributing' },
    ],
  },
]

// Slovak mirror under /sk/: the same pages and order as above.
export const navSk: DefaultTheme.NavItem[] = [
  { text: 'Príručka', link: '/sk/guide/getting-started', activeMatch: '^/sk/guide/' },
  { text: 'Príklady', link: '/sk/examples/', activeMatch: '^/sk/examples/' },
  { text: 'API', link: '/sk/api/reference' },
  { text: 'Zoznam zmien', link: '/sk/changelog' },
  {
    text: '5.x',
    items: [
      { text: '5.x (master)', link: '/sk/' },
      { text: '4.x (README verzie 4.3.0)', link: 'https://github.com/tomaj/omnipay-tatra/blob/4.3.0/readme.md' },
      { text: 'Prechod na 5.0', link: '/sk/upgrade' },
    ],
  },
]

export const sidebarSk: DefaultTheme.SidebarItem[] = [
  {
    text: 'Úvod',
    items: [
      { text: 'Začíname', link: '/sk/guide/getting-started' },
      { text: 'Ako prebieha platba', link: '/sk/guide/payment-flow' },
      { text: 'Konfigurácia', link: '/sk/guide/configuration' },
      { text: 'Testovací režim', link: '/sk/guide/test-mode' },
    ],
  },
  {
    text: 'Platobné brány',
    items: [
      { text: 'TatraPay', link: '/sk/guide/tatrapay' },
      { text: 'CardPay', link: '/sk/guide/cardpay' },
      { text: 'ComfortPay', link: '/sk/guide/comfortpay' },
    ],
  },
  {
    text: 'V produkcii',
    items: [
      { text: 'Podpisy', link: '/sk/guide/signatures' },
      { text: 'Spracovanie chýb', link: '/sk/guide/errors' },
      { text: 'Testovanie integrácie', link: '/sk/guide/testing' },
      { text: 'Bezpečnosť', link: '/sk/security' },
    ],
  },
  {
    text: 'Príklady',
    items: [
      { text: 'Prehľad', link: '/sk/examples/' },
      { text: 'TatraPay od začiatku do konca', link: '/sk/examples/tatrapay' },
      { text: 'CardPay s HMAC a AES', link: '/sk/examples/cardpay' },
      { text: 'Predautorizácia a dokončenie', link: '/sk/examples/cardpay-preauthorization' },
      { text: 'Predplatné cez ComfortPay', link: '/sk/examples/comfortpay-recurring' },
      { text: 'E-shop s návratovou URL', link: '/sk/examples/shop' },
      { text: 'Podpisy a openssl', link: '/sk/examples/signatures' },
    ],
  },
  {
    text: 'Referencia',
    items: [
      { text: 'Referencia API', link: '/sk/api/reference' },
      { text: 'Časté otázky', link: '/sk/faq' },
      { text: 'Prechod na 5.0', link: '/sk/upgrade' },
      { text: 'Zoznam zmien', link: '/sk/changelog' },
      { text: 'Ako prispieť', link: '/sk/contributing' },
    ],
  },
]

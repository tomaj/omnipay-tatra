import { defineConfig } from 'vitepress'

const description = 'Tatra banka (TatraPay, CardPay, ComfortPay) gateways for the Omnipay payment processing library'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'omnipay-tatra',
  description,
  base: '/omnipay-tatra/',
  cleanUrls: true,

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/omnipay-tatra/favicon.svg' }],
    ['meta', { name: 'theme-color', content: '#0f766e' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'omnipay-tatra' }],
    ['meta', { property: 'og:description', content: description }],
    ['meta', { property: 'og:url', content: 'https://tomaj.github.io/omnipay-tatra/' }],
  ],

  lastUpdated: true,

  sitemap: {
    hostname: 'https://tomaj.github.io/omnipay-tatra/',
  },

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API', link: '/api/reference' },
      { text: 'Changelog', link: '/changelog' },
      {
        text: '5.x',
        items: [
          { text: '5.x (master)', link: '/' },
          { text: '4.x (README of 4.3.0)', link: 'https://github.com/tomaj/omnipay-tatra/blob/4.3.0/readme.md' },
          { text: 'Upgrading to 5.0', link: '/UPGRADE-5.0' },
        ],
      },
    ],

    sidebar: [
      {
        text: 'Introduction',
        items: [
          { text: 'Getting started', link: '/guide/getting-started' },
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
        text: 'Reference',
        items: [
          { text: 'Signatures', link: '/guide/signatures' },
          { text: 'API reference', link: '/api/reference' },
          { text: 'FAQ', link: '/faq' },
          { text: 'Changelog', link: '/changelog' },
          { text: 'Upgrading to 5.0', link: '/UPGRADE-5.0' },
          { text: 'Contributing', link: '/contributing' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/tomaj/omnipay-tatra' },
    ],

    editLink: {
      pattern: 'https://github.com/tomaj/omnipay-tatra/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2016-2026 Tomas Majer',
    },
  },
})

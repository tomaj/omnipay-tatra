import { defineConfig } from 'vitepress'
import { seoHead, siteUrl } from './seo'

const description = 'Tatra banka (TatraPay, CardPay, ComfortPay) gateways for the Omnipay payment processing library: signed requests, verified callbacks, pre-authorization and card-on-file payments for PHP 8.4+.'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'omnipay-tatra',
  description,
  base: '/omnipay-tatra/',
  cleanUrls: true,

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/omnipay-tatra/favicon.svg' }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/omnipay-tatra/favicon-32.png' }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '16x16', href: '/omnipay-tatra/favicon-16.png' }],
    ['link', { rel: 'apple-touch-icon', sizes: '180x180', href: '/omnipay-tatra/apple-touch-icon.png' }],
    ['link', { rel: 'manifest', href: '/omnipay-tatra/site.webmanifest' }],
    ['meta', { name: 'theme-color', content: '#141414' }],
  ],

  lastUpdated: true,

  sitemap: {
    hostname: siteUrl,
  },

  transformHead({ pageData, siteConfig }) {
    return seoHead(pageData, siteConfig.srcDir, description)
  },

  markdown: {
    config(md) {
      // CHANGELOG.md links UPGRADE-5.0.md next to it on GitHub; on this site that page is /upgrade
      md.core.ruler.after('inline', 'upgrade-link', (state) => {
        for (const token of state.tokens) {
          for (const child of token.children ?? []) {
            if (child.type === 'link_open' && child.attrGet('href') === 'UPGRADE-5.0.md') child.attrSet('href', 'upgrade.md')
          }
        }
      })
    },
  },

  // The examples are included from the repository root (<<< @/../examples/...).
  vite: {
    server: { fs: { allow: ['..'] } },
  },

  themeConfig: {
    logo: { light: '/logo.svg', dark: '/logo-dark.svg', alt: 'omnipay-tatra' },

    nav: [
      { text: 'Guide', link: '/guide/getting-started', activeMatch: '/guide/' },
      { text: 'Examples', link: '/examples/', activeMatch: '/examples/' },
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
    ],

    sidebar: [
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
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/tomaj/omnipay-tatra' },
    ],

    editLink: {
      pattern: 'https://github.com/tomaj/omnipay-tatra/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },

    lastUpdated: {
      text: 'Last updated',
    },

    outline: {
      level: [2, 3],
    },

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the <a href="https://github.com/tomaj/omnipay-tatra/blob/master/LICENSE.md">MIT License</a>. Not affiliated with Tatra banka, a.s.',
      copyright: 'Copyright © 2016-2026 Tomas Majer',
    },
  },
})

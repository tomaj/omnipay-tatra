import { defineConfig } from 'vitepress'
import { nav, navSk, sidebar, sidebarSk } from '../site-nav'
import { seoHead, siteUrl } from './seo'

const description = 'Tatra banka (TatraPay, CardPay, ComfortPay) gateways for the Omnipay payment processing library: signed requests, verified callbacks, pre-authorization and card-on-file payments for PHP 8.4+.'
const descriptionSk = 'Platobné brány Tatra banky (TatraPay, CardPay, ComfortPay) pre platobnú knižnicu Omnipay: podpísané požiadavky, overené návraty, predautorizácia a platby uloženou kartou pre PHP 8.4+.'

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
    return seoHead(pageData, siteConfig.srcDir, pageData.relativePath.startsWith('sk/') ? descriptionSk : description)
  },

  // English is the default locale at the site root; Slovak mirrors every page under /sk/.
  locales: {
    root: {
      label: 'English',
      lang: 'en-US',
      description,
    },
    sk: {
      label: 'Slovenčina',
      lang: 'sk-SK',
      link: '/sk/',
      title: 'omnipay-tatra',
      description: descriptionSk,
      themeConfig: {
        nav: navSk,
        sidebar: sidebarSk,
        outline: { level: [2, 3], label: 'Na tejto stránke' },
        docFooter: {
          prev: 'Predchádzajúca stránka',
          next: 'Nasledujúca stránka',
        },
        editLink: {
          pattern: 'https://github.com/tomaj/omnipay-tatra/edit/master/docs/:path',
          text: 'Upraviť túto stránku na GitHube',
        },
        lastUpdated: {
          text: 'Naposledy upravené',
        },
        darkModeSwitchLabel: 'Vzhľad',
        lightModeSwitchTitle: 'Prepnúť na svetlý režim',
        darkModeSwitchTitle: 'Prepnúť na tmavý režim',
        sidebarMenuLabel: 'Ponuka',
        returnToTopLabel: 'Späť na začiatok',
        langMenuLabel: 'Zmeniť jazyk',
        skipToContentLabel: 'Preskočiť na obsah',
        footer: {
          message: 'Vydané pod <a href="https://github.com/tomaj/omnipay-tatra/blob/master/LICENSE.md">licenciou MIT</a>. Projekt nie je prepojený s Tatra bankou, a.s.',
          copyright: 'Copyright © 2016-2026 Tomas Majer',
        },
        notFound: {
          code: '404',
          title: 'STRÁNKA SA NENAŠLA',
          quote: 'Táto stránka neexistuje alebo sa presunula. Skúste vyhľadávanie alebo začnite od úvodu.',
          linkLabel: 'prejsť na úvod',
          linkText: 'Späť na úvod',
        },
      },
    },
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

    nav,
    sidebar,

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
      options: {
        locales: {
          sk: {
            translations: {
              button: {
                buttonText: 'Hľadať',
                buttonAriaLabel: 'Hľadať',
              },
              modal: {
                displayDetails: 'Zobraziť podrobný zoznam',
                resetButtonTitle: 'Vymazať hľadanie',
                backButtonTitle: 'Zavrieť hľadanie',
                noResultsText: 'Žiadne výsledky pre',
                footer: {
                  selectText: 'vybrať',
                  selectKeyAriaLabel: 'enter',
                  navigateText: 'prejsť',
                  navigateUpKeyAriaLabel: 'šípka hore',
                  navigateDownKeyAriaLabel: 'šípka dole',
                  closeText: 'zavrieť',
                  closeKeyAriaLabel: 'escape',
                },
              },
            },
          },
        },
      },
    },

    footer: {
      message: 'Released under the <a href="https://github.com/tomaj/omnipay-tatra/blob/master/LICENSE.md">MIT License</a>. Not affiliated with Tatra banka, a.s.',
      copyright: 'Copyright © 2016-2026 Tomas Majer',
    },
  },
})

import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Thinkrix',
  description: 'ThinkPHP 后台管理包 - PHP Schema Builder',
  lastUpdated: true,
  base: '/thinkrix/',
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/thinkrix/logo.svg' }]
  ],

  locales: {
    root: {
      label: '简体中文',
      lang: 'zh-CN'
    },
    en: {
      label: 'English',
      lang: 'en-US',
      link: '/en/',
      themeConfig: {
        nav: [
          { text: 'Home', link: '/en/' },
          { text: 'Guide', link: '/en/guide/' },
          { text: 'API Reference', link: '/en/api/' }
        ],
        sidebar: {
          '/en/guide/': [
            {
              text: 'Getting Started',
              items: [
                { text: 'Introduction', link: '/en/guide/' },
                { text: 'Installation', link: '/en/guide/installation' },
                { text: 'Configuration', link: '/en/guide/configuration' }
              ]
            },
            {
              text: 'Schema Components',
              items: [
                { text: 'Component Overview', link: '/en/guide/components/' },
                { text: 'Basic Components', link: '/en/guide/components/basic' },
                { text: 'Form Components', link: '/en/guide/components/form' },
                { text: 'Data Display', link: '/en/guide/components/data' },
                { text: 'Layout Components', link: '/en/guide/components/layout' },
                { text: 'Feedback Components', link: '/en/guide/components/feedback' },
                { text: 'Business Components', link: '/en/guide/components/business' }
              ]
            },
            {
              text: 'Actions',
              items: [
                { text: 'Action Overview', link: '/en/guide/actions/' },
                { text: 'SetAction', link: '/en/guide/actions/set' },
                { text: 'CallAction', link: '/en/guide/actions/call' },
                { text: 'FetchAction', link: '/en/guide/actions/fetch' },
                { text: 'IfAction', link: '/en/guide/actions/if' },
                { text: 'Other Actions', link: '/en/guide/actions/others' }
              ]
            },
            {
              text: 'Advanced',
              items: [
                { text: 'Modules', link: '/en/guide/modules' },
                { text: 'Internationalization', link: '/en/guide/i18n' },
                { text: 'Data Dictionary', link: '/en/guide/dict' },
                { text: 'Notifications', link: '/en/guide/notifications' },
                { text: 'Theme', link: '/en/guide/theme' },
                { text: 'Custom Components', link: '/en/guide/custom-components' }
              ]
            }
          ],
          '/en/api/': [
            {
              text: 'API Reference',
              items: [
                { text: 'Overview', link: '/en/api/' }
              ]
            }
          ]
        },
        editLink: {
          pattern: 'https://github.com/your-org/lartrix-think/edit/main/docs/:path',
          text: 'Edit this page on GitHub'
        },
        docFooter: {
          prev: 'Previous',
          next: 'Next'
        },
        outline: {
          label: 'On this page'
        },
        lastUpdated: {
          text: 'Last updated'
        }
      }
    }
  },

  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: '首页', link: '/' },
      { text: '指南', link: '/guide/' },
      { text: 'API 参考', link: '/api/' }
    ],

    sidebar: {
      '/guide/': [
        {
          text: '开始',
          items: [
            { text: '介绍', link: '/guide/' },
            { text: '安装', link: '/guide/installation' },
            { text: '配置', link: '/guide/configuration' }
          ]
        },
        {
          text: 'Schema 组件',
          items: [
            { text: '组件概述', link: '/guide/components/' },
            { text: '基础组件', link: '/guide/components/basic' },
            { text: '表单组件', link: '/guide/components/form' },
            { text: '数据展示', link: '/guide/components/data' },
            { text: '布局组件', link: '/guide/components/layout' },
            { text: '反馈组件', link: '/guide/components/feedback' },
            { text: '业务组件', link: '/guide/components/business' }
          ]
        },
        {
          text: 'Actions',
          items: [
            { text: 'Action 概述', link: '/guide/actions/' },
            { text: 'SetAction', link: '/guide/actions/set' },
            { text: 'CallAction', link: '/guide/actions/call' },
            { text: 'FetchAction', link: '/guide/actions/fetch' },
            { text: 'IfAction', link: '/guide/actions/if' },
            { text: '其他 Actions', link: '/guide/actions/others' }
          ]
        },
        {
          text: '进阶',
          items: [
            { text: '模块开发', link: '/guide/modules' },
            { text: '多语言', link: '/guide/i18n' },
            { text: '数据字典', link: '/guide/dict' },
            { text: '通知与实时消息', link: '/guide/notifications' },
            { text: '主题与站点设置', link: '/guide/theme' },
            { text: '自定义组件', link: '/guide/custom-components' }
          ]
        }
      ],
      '/api/': [
        {
          text: 'API 参考',
          items: [
            { text: '概述', link: '/api/' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/your-org/lartrix-think' }
    ],

    search: {
      provider: 'local'
    },

    editLink: {
      pattern: 'https://github.com/your-org/lartrix-think/edit/main/docs/:path',
      text: '在 GitHub 上编辑此页'
    },

    docFooter: {
      prev: '上一页',
      next: '下一页'
    },

    outline: {
      label: '页面导航'
    },

    lastUpdated: {
      text: '最后更新于'
    },

    footer: {
      message: '基于 MIT 许可发布',
      copyright: 'Copyright © 2024-present Thinkrix Team'
    }
  }
})

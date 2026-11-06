# Filament Page Builder — План за фичери и Архитектура (v2.0)

> **Цел:** Бесплатен, open-source (MIT) Filament v5 плагин што го реплицира начинот на работа и фичерите на **Elementor Pro** — визуелен page builder за Laravel без кодирање.
> **Монетизација подоцна:** Платени add-on плагини по моделот на Crocoblock (јадрото останува бесплатно засекогаш).
> **Референца:** Elementor Pro + Crocoblock — се користат само како функционална/UX референца, целиот код се пишува од нула.

---

## 0. Технички стек и компатибилност (2026+)

| Технологија | Верзија | Зошто / Белешка |
| --- | --- | --- |
| **PHP** | `^8.3 \| ^8.4 \| ^8.5` | Минимум за Laravel 13+ |
| **Laravel** | `^12.0 \| ^13.0 \| ^14.0` | Примарен развој на **Laravel 13**, подготвен за **Laravel 14** |
| **Filament** | `^5.0` (Filament v5) | Користи `Filament\Schemas\Schema`, `InteractsWithSchemas` и `lazyLoaded()` |
| **Livewire** | `^4.0` (Livewire v4) | Побрз state-sync и изолирани компоненти за едиторот |
| **Tailwind CSS** | `^4.0` (Tailwind v4) | Нативна поддршка за CSS Variables и `@container` queries |

---

## 1. Архитектура (основа за сè)

| Компонента | Опис |
| --- | --- |
| **JSON Schema (v1)** | Секоја страница/layout се чува како JSON дрво (`version`, `elements`: containers → widgets → settings + dynamic) |
| **Draft / Preview / Publish моторот** | Одвоени колони `draft_content` (работна) и `content` (јавна), заштитен Live Preview линк и верзионирање (`page_revisions`) |
| **Widget регистар** | Централен `WidgetRegistry`; секој widget = PHP класа (`Filament\Schemas\Schema` форма) + Blade view (render) |
| **Livewire v4 Едитор** | Canvas + панел со widgets + drag & drop (Alpine.js / SortableJS), без iframe на почеток (преку `@container` queries) |
| **Settings панел** | `Filament\Schemas\Schema` со три таба: **Content / Style / Advanced** (се монтира lazy само за активниот блок) |
| **Blade + CSS рендерер** | Рекурзивно ја чита JSON schema-та, генерира динамички CSS варијабли за responsive breakpoints и кешира |
| **Add-on API** | Hooks, widget регистрација, dynamic tag providers, form actions — од ден 1, за идни платени адони |

### 1.1 Животен циклус на страница: Draft → Preview → Publish

Ниту една промена во едиторот не смее директно да ја расипе живата страница што ја гледаат посетителите:

1. **Draft (Нацрт — `draft_content`):**
   - Кога корисникот додава/менува елементи во едиторот и клика **„Save Draft“** (или auto-save), промените се снимаат исклучиво во `pages.draft_content`.
   - Страницата може да биде во статус `draft` (недостапна за јавност) или `published` со необјавени промени (`has_unpublished_changes = true`).
2. **Preview (Преглед пред објавување):**
   - Копче **„Preview“** во горната лента на едиторот отвора `/_builder/preview/{page}` (заштитена рута со signed URL или auth проверка).
   - Preview рутата го рендерира `draft_content` низ вистинскиот frontend layout за корисникот да види 1:1 како ќе изгледа страницата пред објава.
   - Во самиот едитор има и инстант прекинувач за Desktop / Tablet / Mobile ширина.
3. **Publish (Објави — `content`):**
   - При клик на **„Publish“**:
     1. Тековниот JSON од едиторот се валидира и запишува во `draft_content`.
     2. `draft_content` се копира во `content` (јавната колона).
     3. Статусот се поставува на `published`, а `published_at` се ажурира.
     4. Се креира snapshot во `page_revisions` (за враќање на стара верзија).
     5. Се чисти и регенерира кешот за таа страница (`PageCacheService`).
4. **Discard Draft (Отфрли промени) / Unpublish (Врати во Draft):**
   - Корисникот може во секое време да ги отфрли необјавените измени (`draft_content = content`) или да ја повлече страницата од јавност (`status = draft`).

### 1.2 Структура на `pages` табелата

| Колона | Тип | Опис |
| --- | --- | --- |
| `id` | `ulid` / `bigint` | Примарен клуч |
| `title` | `string` | Наслов на страницата |
| `slug` | `string` (unique) | URL патека (пр. `home`, `about-us`) |
| `status` | `string` | `draft`, `published`, `scheduled`, `archived` |
| `layout` | `string` | Blade layout (`default`, `canvas`, `full_width`) |
| `draft_content` | `json` | Работна верзија во едиторот (секогаш се уредува оваа колона) |
| `content` | `json` (nullable) | Објавена верзија што ја гледаат посетителите |
| `meta` | `json` (nullable) | SEO полиња (`meta_title`, `meta_description`, `og_image`, `noindex`) |
| `published_at` | `timestamp` (nullable) | Датум на објавување |
| `timestamps` | `timestamps` | `created_at`, `updated_at` |

### 1.3 Пример JSON Schema (v1 — Flat Responsive + Dynamic)

```json
{
  "version": 1,
  "elements": [
    {
      "id": "el_01J9X2A",
      "type": "container",
      "settings": {
        "direction": "row",
        "direction_mobile": "column",
        "gap": 24,
        "gap_mobile": 12,
        "padding": 40,
        "padding_tablet": 24,
        "padding_mobile": 16,
        "background_color": "#ffffff"
      },
      "dynamic": {},
      "children": [
        {
          "id": "el_01J9X2B",
          "type": "heading",
          "settings": {
            "text": "Здраво",
            "tag": "h1",
            "color": "#111111",
            "font_size": 42,
            "font_size_mobile": 28
          },
          "dynamic": {
            "text": null
          },
          "children": []
        },
        {
          "id": "el_01J9X2C",
          "type": "button",
          "settings": {
            "label": "Купи",
            "link": "/shop",
            "style": "primary",
            "open_in_new_tab": false
          },
          "dynamic": {},
          "children": []
        }
      ]
    }
  ]
}
```

---

## 2. Фичери од Elementor Pro што ги градиме

### 2.1 Јадро на едиторот (Core)

| # | Elementor Pro фичер | Наша Filament v5 верзија | Приоритет |
| --- | --- | --- | --- |
| 1 | Flexbox Containers | Container widget (`direction`, `gap`, `wrap`, `align`, `justify`) — почнуваме веднаш со containers | 🔴 v0.1 |
| 2 | Draft / Preview / Publish | `draft_content` vs `content`, Save Draft копче, Preview рута и Publish акција со кеш | 🔴 v0.1–v0.2 |
| 3 | Визуелен canvas + панел со widgets | Livewire v4 компонента: Топ-бар (Save Draft / Preview / Publish) + Панел лево + Canvas десно | 🔴 v0.2 |
| 4 | Drag & drop | Alpine.js + SortableJS со drop зони помеѓу и внатре во containers | 🔴 v0.2 |
| 5 | Settings панел: Content / Style / Advanced | `Filament\Schemas\Schema` по widget со `Tabs` компонента (lazy-монтирана за селектираниот блок) | 🔴 v0.2 |
| 6 | Page Settings (SEO & Статус) | Посебен панел во едиторот за `title`, `slug`, `status` (Draft/Published), `layout`, SEO meta | 🔴 v0.2 |
| 7 | Responsive контроли (desktop/tablet/mobile) | Прекинувач по поставка (`field`, `field_tablet`, `field_mobile`) + CSS `@container` preview | 🟡 v0.3 |
| 8 | Undo/redo, копирај/залепи блок & Ревизии | Клиентска историја на JSON состојби + серверски `page_revisions` при секој Publish | 🟡 v0.3 |
| 9 | Save as template / Template library | JSON снимки во база + import/export | 🟡 v0.3 |
| 10 | Navigator (дрво на елементи) | Sidebar tree view на JSON елементите со брзо селектирање и преуредување | 🟢 v0.4 |

### 2.2 Widgets библиотека

**Базен сет (v0.1–v0.3):**

| Widget | Верзија | Белешка |
| --- | --- | --- |
| **Container** | v0.1 | Flexbox корен и вгнездување (`row`, `column`, `gap`, `padding`, `margin`, `bg`) |
| **Heading** | v0.1 | `h1`–`h6`, бои, големина на фонт, порамнување |
| **Text / Rich text** | v0.1 | Filament RichEditor / Tiptap (со HTML санитизација) |
| **Button** | v0.1 | Стилови, линк, target, икона |
| **Image** | v0.2 | `src`, `alt`, `sizes`, `lazy load`, `object-fit` |
| **Spacer / Divider** | v0.2 | Висина во px, линија, боја |
| **Icon** | v0.2 | Heroicons / Blade icons picker |
| **Video** | v0.2 | YouTube / Vimeo / Self-hosted |
| **HTML / Blade embed** | v0.3 | Custom код и Blade рендерирање (со дозволи) |
| **Image gallery** | v0.3 | Grid / Masonry, lightbox |
| **Alert / Notice box** | v0.3 | Инфо, успех, предупредување |
| **Accordion & Tabs** | v0.3 | Интерактивни Alpine.js компоненти |
| **Testimonial** | v0.3 | Слика, цитат, име и позиција |

**Напреден сет (v0.4+):** Slider/carousel, pricing table, countdown, progress bar, social icons, Google Maps, star rating, posts grid (loop), flip box, call-to-action блок.

### 2.3 Theme Builder (Pro)

| # | Фичер | Наша верзија | Приоритет |
| --- | --- | --- | --- |
| 1 | Header / Footer темплејти | Layout записи во база + Blade директива `@filamentThemeRegion('header')` | 🟡 v0.4 |
| 2 | Single post темплејт | Template со dynamic placeholders за кој било Eloquent модел | 🟡 v0.4 |
| 3 | Archive темплејт | Listing layout за модели | 🟡 v0.4 |
| 4 | Услови за прикажување („сите страници освен X") | Route/URL/Model matching правила (`include` / `exclude`) | 🟡 v0.4 |
| 5 | 404 страница | Custom error template | 🟢 v0.5 |

### 2.4 Dynamic Content (наш „JetEngine killer")

| # | Фичер | Наша верзија | Приоритет |
| --- | --- | --- | --- |
| 1 | Dynamic tags (поле што влече податок) | Provider систем: секое поле во widget settings може да се врзе на Eloquent атрибут (`dynamic.text = "record.title"`) | 🟡 v0.5 |
| 2 | Loop Builder | Repeater/Grid widget што рендерира Eloquent Query со избран sub-template | 🟡 v0.5 |
| 3 | Custom post types | **НЕ градиме** — Filament v5 Resources + Eloquent веќе го покриваат подобро | ✅ бесплатно |
| 4 | Релации | Eloquent relations директно | ✅ бесплатно |

### 2.5 Form Builder (Pro)

| # | Фичер | Наша верзија | Приоритет |
| --- | --- | --- | --- |
| 1 | Drag & drop форми | `Filament\Schemas\Schema` зачувана како JSON | 🟡 v0.6 |
| 2 | Акции после submit | Email, redirect, webhook, save во база — како pluggable actions (Laravel jobs/mailables) | 🟡 v0.6 |
| 3 | Conditional logic | Правила за покажување полиња | 🟢 v0.7 |
| 4 | Multi-step форми | Filament Wizard layout | 🟢 v0.7 |
| 5 | Anti-spam | Honeypot + Turnstile/reCAPTCHA | 🟢 v0.7 |

### 2.6 Popup Builder (Pro)

| # | Фичер | Наша верзија | Приоритет |
| --- | --- | --- | --- |
| 1 | Popup со builder canvas | Истиот едитор, режим „popup" | 🟢 v0.7 |
| 2 | Triggers | On load, on scroll %, exit intent, on click | 🟢 v0.7 |
| 3 | Услови | URL rules, device, после X посети (localStorage/cookie) | 🟢 v0.7 |

### 2.7 Што НЕ градиме во јадрото (намерно)

- **WooCommerce widgets** — WP-специфично; подоцна како платен адон за Laravel e-commerce пакети (Lunar, Bagisto, Cashier).
- **Custom post types** — Filament Resources го решаваат нативно.
- **Motion effects/animations 1:1** — само базни (fade, slide) во v1.0.

---

## 3. Add-on API (основа за идната Crocoblock-стратегија)

Од ден 1 (v0.1), јадрото изложува чисти PHP контракти и настани:

1. **Widget регистрација (`WidgetRegistry::register()`)** — секој composer пакет може да регистрира свои widgets во `boot()`.
2. **Hooks / Laravel Events** — `PageSavingDraft`, `PagePublishing`, `PagePublished`, `BeforeElementRender`, `AfterElementRender`.
3. **Dynamic Tag Providers (`DynamicTagRegistry`)** — адони регистрираат свои извори на податоци (Auth user, Eloquent модел, Site settings, API).
4. **Form Actions API (`FormActionRegistry`)** — адони додаваат акции после испраќање форма (CRM, Stripe, Mailchimp).
5. **Template Providers (`TemplateRegistry`)** — адони внесуваат готови темплејти/секции во библиотеката.

---

## 4. Roadmap по верзии

| Верзија | Содржина | Што се објавува |
| --- | --- | --- |
| **v0.1** ✅ | Filament v5 плагин скелет, `Page` модел со `draft_content` / `content` / `status`, Preview & Publish сервис, JSON Schema + CSS/Blade рендерер, Add-on API контракти + 4 основни елементи (`Container`, `Heading`, `Text`, `Button`) + Filament Page Resource | Доказ дека концептот држи + основа за едиторот |
| **v0.2** ✅ | Визуелен Livewire v4 едитор: Топ-бар со **Save Draft / Preview / Publish**, лев панел со widgets, десен canvas, `Filament\Schemas\Schema` инспектор (Content/Style/Advanced), drag & drop | **Прва јавна објава на GitHub** |
| **v0.3** ✅ | Полна контрола за Responsive (Desktop/Tablet/Mobile), Undo/Redo, Ревизии (`page_revisions`), Templates library + преостанати базни widgets |  |
| **v0.4** ✅ | Theme Builder (Header/Footer/Single + услови за прикажување) + Navigator дрво |  |
| **v0.5** ✅ | Dynamic Content (Dynamic Tags) + Loop Builder |  |
| **v0.6** ✅ | Form Builder со pluggable акции |  |
| **v0.7** ✅ | Popup Builder + conditional forms |  |
| **v1.0** ✅ | Полирање, тестови (Pest), документација, демо сајт, Packagist | **Официјален v1.0 Launch** |
| **v1.1** 🔨 | **Global Site Settings** (глобални бои/типографија со CSS варијабли + Filament settings страница), **Global Widget** (synced instances од Template Library), **Custom CSS + Custom Attributes** по елемент, **Custom 404** (`not_found` theme region + fallback), **Form Submissions admin** (табела + CSV export), **Autoresponder** email акција, **9 нови widgets** (`slider`, `pricing_table`, `countdown`, `progress_bar`, `social_icons`, `star_rating`, `google_maps`, `flip_box`, `cta`) | Elementor Pro Site Settings / Global Widget / Custom CSS / 404 |
| **v1.2** 📋 | **Nav Menu widget + Off-canvas mobile menu**, **Motion Effects & Sticky** (fade/slide/parallax), **Device visibility** (`hide_on_desktop/tablet/mobile`), **Custom Fonts & Icons**, **Role Manager** (Gate policies), **Revision diff/compare** |  |
| **v1.3+** 📋 | **Full Site Kit import/export** (ZIP/JSON), **Loop Carousel**, **AJAX pagination/филтри за Loop**, **Maintenance / Coming Soon mode**, **Finder (Ctrl+K)** во едиторот |  |

**Платени add-ons (core останува MIT):** e-commerce widgets (Lunar/Bagisto/Cashier), CRM & newsletter integrations (Mailchimp, HubSpot), advanced motion effects, template cloud library, A/B testing.

---

## 5. Практични одлуки

- **Лиценца:** MIT (исто како Filament)
- **Дистрибуција:** Packagist + GitHub
- **Тестирање:** Pest PHP + Orchestra Testbench (стандард за Filament v5 плагини)
- **Демо сајт:** Јавен demo каде се пробува едиторот без инсталација

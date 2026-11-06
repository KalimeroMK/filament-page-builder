# Session Handoff — Filament Page Builder

> Пренесување на целата работна состојба на друг Mac. Прочитај го овој документ пред продолжување на развојот.

---

## 1. Проект

- **Пакет:** `kalimeromk/filament-page-builder` (`KalimeroMK\FilamentPageBuilder`)
- **Автор:** KalimeroMK <zbogoevski@gmail.com>
- **GitHub:** https://github.com/KalimeroMK/filament-page-builder
- **Лиценца:** MIT — **сите фичери се и остануваат бесплатни, нема платена верзија** (одлука на сопственикот, запишано во `PLAN.md` и `README.md`).
- **Цел:** Elementor Pro-like визуелен page builder за Laravel & Filament v5, пишуван од нула.

## 2. Моментална состојба (v1.3.0)

Завршени верзии: **v0.1.0, v0.1.1, v0.2.0, v0.2.1, v0.3.0, v0.4.0, v0.5.0, v0.6.0, v0.7.0, v1.0.0, v1.0.1, v1.0.2, v1.1.0, v1.2.0, v1.3.0**

Секоја верзија помина низ: **развој → Code Review агент → поправки → Pest тестови → commit + tag + push**.

**Моментално:** **52 Pest тестови / 401 assertions — сите зелено.**

### Модулни фичери
- JSON Schema рендерер + CSS Variables (`@container` + `@media` queries)
- **Livewire v4 визуелен едитор** (drag & drop canvas, Content/Style/Advanced inspector, Navigator tree, Undo/Redo, Copy/Paste, Templates, Revisions, Finder Ctrl+K)
- **Draft → Preview → Publish** (`draft_content` vs `content`, signed preview, revisions, render cache)
- **26+ widgets** (Container, Heading, Text, Button, Image, Spacer, Icon, Video, HTML, Gallery, Alert, Accordion, Tabs, Testimonial, Loop, Form, GlobalWidget, Slider, PricingTable, Countdown, ProgressBar, SocialIcons, StarRating, GoogleMaps, FlipBox, CTA, NavMenu)
- **Theme Builder** (Header/Footer/Single/Archive/404 + include/exclude услови)
- **Dynamic Tags + Loop Builder** (Eloquent allowlist, carousel, AJAX Load More)
- **Form Builder** (pluggable actions: Email, Redirect, Webhook (SSRF-safe), SaveToDatabase, Autoresponder; conditional logic, multi-step, honeypot, Turnstile, rate limiting)
- **Popup Builder** (on_load/scroll/exit_intent/click triggers + conditions)
- **Global Site Settings** (boji/typography CSS variables), **Global Widget**, **Custom CSS/Attributes**, **Custom 404**
- **Submissions admin + CSV export**
- **Nav Menu + Off-canvas**, **Motion/Sticky/Device visibility**, **Custom Fonts/Icons (DOM SVG sanitizer)**, **Role Manager (Gate)**, **Revision diff**
- **Full Site Kit import/export**, **Maintenance mode**, **tmux session `fpb`**

## 3. Клучни фајлови

| Фајл | Намена |
| --- | --- |
| `composer.json` | Package metadata (PHP ^8.3-8.5, Laravel ^12-14, Filament ^5, Livewire ^4) |
| `PLAN.md` | Ажуриран план по верзии (v0.1 → v1.3+) |
| `README.md` | Целосна документација (quick start, usage guides, add-on API, FAQ) |
| `CHANGELOG.md` | Историја на верзии |
| `src/FilamentPageBuilderServiceProvider.php` | Регистрација на widgets, tags, actions, Livewire, Blade директиви, Gate, maintenance |
| `src/FilamentPageBuilderPlugin.php` | Filament v5 Plugin (PageResource, ThemeTemplateResource, SiteSettingsPage, SiteKitPage, MenuResource, FormSubmissionResource) |
| `src/Livewire/VisualPageBuilderEditor.php` | Визуелниот едитор (locked state, history, finder, compare) |
| `src/Rendering/PageRenderer.php`, `CssGenerator.php` | JSON → HTML + responsive CSS |
| `src/Models/Page.php`, `PageTemplate.php`, `ThemeTemplate.php`, `Popup.php`, `Menu.php`, `Setting.php`, `FormSubmission.php` | Eloquent модели со draft/publish логика |
| `src/Widgets/` | Сите widget класи (AbstractWidget + конкретни widgets) |
| `src/Services/` | `PagePublishService`, `PageCacheService`, `ThemeResolver`, `ThemeConditionMatcher`, `LoopQueryBuilder`, `SiteStyleService`, `CustomAssetsService`, `SchemaDiffService`, `SiteKitService`, `PopupResolver` |
| `tests/Feature/` | `PageBuilderTest`, `VisualEditorTest`, `V03FeaturesTest`, `V04ThemeAndNavigatorTest`, `V05DynamicContentTest`, `V06FormBuilderTest`, `V07PopupAndConditionalFormsTest`, `V11GlobalAndWidgetsTest`, `V12ProUxTest`, `V13SiteKitAndMoreTest` |

## 4. Демо апликација

- **Локација:** `/srv/code/Filament/demo` (Laravel 13 + Filament v5, SQLite, path repository кон пакетот)
- **Админ:** `zbogoevski@gmail.com` / `password`
- **Seed:** `database/seeders/DemoContentSeeder.php` (home, about, contact, header/footer, popup)
- **URL преку Tailscale:** `http://100.66.227.115:8000/pages/home` (и `/pages/about`, `/pages/contact`, `/admin`)
- **ngrok (иста URL):** https://nondichotomous-alden-unsegregative.ngrok-free.dev/pages/home
- `/demo/` е во `.gitignore` на пакетот (не се комитира).

## 5. tmux сесија `fpb`

```bash
tmux attach -t fpb          # приклучи се на сесијата
# Windows:
#   fpb:laravel  → php artisan serve --host=0.0.0.0 --port=8000 (во /srv/code/Filament/demo)
#   fpb:ngrok    → ngrok http 8000 --log=stdout
#   fpb:shell    → shell во /srv/code/Filament
# Detach: Ctrl+b, потоа d
# Window switch: Ctrl+b n / p / 0-2
```

Ако сесијата не постои:

```bash
tmux new-session -d -s fpb -n laravel -c /srv/code/Filament/demo 'php artisan serve --host=0.0.0.0 --port=8000'
tmux new-window -t fpb -n ngrok -c /srv/code/Filament/demo 'ngrok http 8000 --log=stdout'
tmux new-window -t fpb -n shell -c /srv/code/Filament
```

## 6. Како да продолжиш на друг Mac

```bash
# 1. Клонирај или повлеци
git clone git@github.com:KalimeroMK/filament-page-builder.git Filament
# или: cd Filament && git pull origin main

cd Filament

# 2. Инсталирај зависности
composer install

# 3. Провери дека сè работи
./vendor/bin/pest          # треба: 52 passed, 401 assertions

# 4. Стартувај демо апликацијата (ако ја имаш и demo/ папката)
cd demo
php artisan migrate
php artisan db:seed --class=DemoContentSeeder
php artisan serve --host=0.0.0.0 --port=8000
# Admin: zbogoevski@gmail.com / password

# 5. Стартувај tmux за detach/resume
tmux new-session -d -s fpb -n laravel -c "$(pwd)" 'php artisan serve --host=0.0.0.0 --port=8000'
tmux new-window -t fpb -n shell -c "$(dirname "$(pwd)")"
```

Потоа отвори **`HANDOFF.md`** (овој фајл) за тековна состојба и следни задачи.

## 7. Следни задачи (по приоритет)

1. **Packagist submit** на `kalimeromk/filament-page-builder` (packagist.org → submit GitHub URL).
2. **GitHub Actions CI** (`.github/workflows/tests.yml`): PHP 8.3/8.4/8.5 matrix, `composer install`, `./vendor/bin/pest`.
3. Останати nice-to-have:
   - Coming Soon template (надградба на Maintenance mode)
   - Loop AJAX филтри (фронтенд филтрирање по поле/вредност)
   - Loop AJAX pagination со бројни страници (покрај Load More)
   - Revision diff визуелен side-by-side преглед на елементи

## 8. Правила за работа

- **Секоја нова верзија/фичер:** развој → Code Review агент (Agent tool) → поправки → `./vendor/bin/pest` зелено → commit + tag + push на GitHub.
- **Лиценца:** нема платена верзија — сè е MIT и бесплатно, вклучувајќи идни add-ons.
- **Стек:** PHP 8.3–8.5, Laravel 12/13/14, Filament v5 (`Filament\Schemas\Schema`), Livewire v4, Tailwind v4.
- **Безбедност:** `#[Locked]` на чувствителни Livewire props, `RestrictsFileUploadsToSchemaComponents`, DOM HTML sanitizer, SSRF-safe webhooks, signed preview URLs, Gate authorization, fail-closed loop allowlists.

---

*Овој handoff е генериран на 2026-11-06 по завршувањето на v1.3.0. За детали, види `CHANGELOG.md` и `PLAN.md`.*

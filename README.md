# AffiliateWP – Leaderboard Enhanced

A WordPress plugin that displays an affiliate sales leaderboard scoped to either the **current rolling week** or the **current calendar year**, with the period and week-start day configurable by the site administrator. Available as both a shortcode and a sidebar widget.

---

## Overview

The standard AffiliateWP leaderboard addon shows all-time cumulative stats. This plugin addresses the common need to highlight which affiliates are performing best *right now* — motivating friendly competition and letting you run weekly or annual contests without manual resets.

Two date periods are supported:

| Period | Description |
|---|---|
| **Current Week** | A rolling 7-day window starting on the admin-chosen day of the week. Advances automatically — no cron jobs needed. |
| **Current Year** | Jan 1 00:00:00 through Dec 31 23:59:59 of the current calendar year. |

This plugin is a **companion** to `affiliatewp-leaderboard`, not a replacement. Both can be active at the same time — the original continues to show all-time stats while this plugin shows the selected period.

---

## Requirements

| Dependency | Minimum version |
|---|---|
| WordPress | 6.4 |
| PHP | 8.1 |
| AffiliateWP | 2.6 |

---

## Installation

1. Upload the `affiliatewp-leaderboard-enhanced` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins → Installed Plugins** in the WordPress admin.
3. AffiliateWP must be active — if it is not, the plugin will silently do nothing.

---

## How the Week Window Works

When `period="week"` is used, the admin chooses one day of the week as the "week start." The leaderboard always covers:

```
[most recent occurrence of chosen day] 00:00:00
    →
[chosen day + 6 days] 23:59:59
```

**Example — week starts Wednesday:**

| Today | Week start | Week end |
|---|---|---|
| Wednesday | Wed Jun 10 | Tue Jun 16 |
| Friday | Wed Jun 10 | Tue Jun 16 |
| Tuesday | Wed Jun 10 | Tue Jun 16 |
| Wednesday (next week) | Wed Jun 17 | Tue Jun 23 |

If today **is** the chosen start day, the week begins today. The window advances automatically at midnight when the next occurrence of the start day arrives. All date calculations use the **WordPress site timezone** (Settings → General → Timezone), not the server timezone.

---

## Shortcode

```
[affiliate_leaderboard_enhanced]
```

### Attributes

| Attribute | Default | Description |
|---|---|---|
| `period` | `week` | Date window to display. Accepts: `week` \| `year` |
| `week_start` | `monday` | Day the week begins (applies to `period="week"` only). Accepts: `sunday` `monday` `tuesday` `wednesday` `thursday` `friday` `saturday` |
| `number` | `10` | Maximum number of affiliates to display |
| `orderby` | `earnings` | Sort metric: `earnings` or `referrals` |
| `order` | `DESC` | Sort direction: `DESC` (highest first) or `ASC` (lowest first) |
| `earnings` | `yes` | Show each affiliate's earnings total: `yes` or `no` |
| `referrals` | `yes` | Show each affiliate's referral count: `yes` or `no` |
| `status` | `paid,unpaid` | Comma-separated referral statuses to include. Accepted values: `paid`, `unpaid`, `pending` |
| `show_label` | `yes` | Show the period label above the list (e.g. "Jun 10–16, 2026" or "2026"): `yes` or `no` |

### Examples

**Basic — current week, Monday start, top 10 by earnings:**
```
[affiliate_leaderboard_enhanced]
```

**Current year leaderboard, top 20:**
```
[affiliate_leaderboard_enhanced period="year" number="20"]
```

**Wednesday–Tuesday week, top 5, earnings only:**
```
[affiliate_leaderboard_enhanced week_start="wednesday" number="5" referrals="no"]
```

**Year-to-date, ranked by referral count, paid only, no label:**
```
[affiliate_leaderboard_enhanced period="year" orderby="referrals" status="paid" show_label="no"]
```

**Friday–Thursday week, top 3, show both metrics:**
```
[affiliate_leaderboard_enhanced week_start="friday" number="3" earnings="yes" referrals="yes"]
```

### Output HTML

```html
<div class="affwp-leaderboard-enhanced-wrap">
  <p class="affwp-leaderboard-enhanced-label">Jun 10–16, 2026</p>
  <ol class="affwp-leaderboard affwp-leaderboard-enhanced">
    <li>
      Jane Smith
      <p>$420.00 earnings &nbsp;|&nbsp; 7 referrals</p>
    </li>
    <li>
      Bob Jones
      <p>$310.50 earnings &nbsp;|&nbsp; 5 referrals</p>
    </li>
  </ol>
</div>
```

The label for a year period displays as the four-digit year: `<p class="affwp-leaderboard-enhanced-label">2026</p>`

When no affiliate activity exists in the selected period:

```html
<div class="affwp-leaderboard-enhanced-wrap">
  <p class="affwp-leaderboard-enhanced-empty">No affiliate activity for this period.</p>
</div>
```

---

## Sidebar Widget

Navigate to **Appearance → Widgets** (or the block-based widget editor) and add the **Affiliate Leaderboard Enhanced** widget to any sidebar.

### Widget Settings

| Setting | Description |
|---|---|
| **Title** | Widget title displayed above the leaderboard |
| **Period** | Dropdown: Current Week / Current Year |
| **Week Starts On** | Dropdown: Sunday through Saturday (applies to Current Week only) |
| **Affiliates to Show** | Number of affiliates to display (minimum 1) |
| **Order By** | Earnings or Referrals |
| **Show Earnings** | Checkbox — include earnings column |
| **Show Referrals** | Checkbox — include referral count column |
| **Referral Status** | Dropdown: "Paid + Unpaid" or "Paid only" |
| **Show Period Label** | Checkbox — show the date label above the list |

The widget renders identical HTML to the shortcode, so any CSS targeting `.affwp-leaderboard-enhanced` applies to both.

---

## Styling

The plugin enqueues `assets/css/leaderboard-enhanced.css` on all front-end pages. It targets:

| Class | Applied to |
|---|---|
| `.affwp-leaderboard-enhanced-wrap` | Outer container `<div>` |
| `.affwp-leaderboard-enhanced-label` | Period label `<p>` |
| `.affwp-leaderboard` | The `<ol>` list (shared with original addon) |
| `.affwp-leaderboard-enhanced` | Additional class on the `<ol>` for specificity |
| `.affwp-leaderboard-enhanced-empty` | "No activity" message `<p>` |

If the original `affiliatewp-leaderboard` addon is also active, its `.affwp-leaderboard p` styles apply here too since the `<ol>` carries the same base class.

**To override styles**, add rules to your theme's stylesheet:

```css
.affwp-leaderboard-enhanced li {
    padding: 0.5em 0;
    border-bottom: 1px solid #eee;
}

.affwp-leaderboard-enhanced-label {
    font-weight: bold;
    color: #333;
}
```

---

## How Referral Data Is Counted

- Earnings and referral counts are calculated **live** from the referrals table on each page load, filtered by the selected period window.
- Only referrals with the statuses specified in the `status` attribute (default: `paid` and `unpaid`) are counted.
- `pending` and `rejected` referrals are excluded by default. Include `pending` with `status="paid,unpaid,pending"`.
- Affiliates with zero qualifying referrals in the selected period do not appear in the list.
- Earnings reflect the **referral amount** (commission), not the order total.
- Visit counts are not supported — this plugin tracks conversions (referrals), not clicks.

---

## Build & Release

This project uses Gradle + CircleCI, matching the build pipeline used across other plugins in this suite.

### Local development

```bash
# Install PHP dependencies (PHPUnit, PHPCS, WP_Mock)
./gradlew composerInstall

# Run unit tests
./gradlew phpunit

# Check coding standards (WordPress Coding Standards)
./gradlew phpcs

# Auto-fix coding standard violations
./gradlew phpcbf

# Run tests + PHPCS (full check)
./gradlew check

# Build distributable zip
./gradlew build
```

### CI/CD pipeline (CircleCI)

Every push triggers:
1. `./gradlew build` — runs `phpcs` + `phpunit`, then packages the zip
2. Test results published to CircleCI's test dashboard
3. On `main` branch: a manual approval gate unlocks the release job
4. Release job creates a GitHub Release with the zip attached

### Versioning

The version number is injected by Gradle at build time using the `@projectVersion@` token in the plugin header. The format is:

```
1.0.0.{build_number}[-gh-{issue}]
```

Examples: `1.0.0.42` (main branch), `1.0.0.43-gh-7` (feature branch for issue #7).

---

## Project Structure

```
affiliatewp-leaderboard-enhanced/
├── affiliatewp-leaderboard-enhanced.php   Main plugin file (bootstrap + autoloader)
├── includes/
│   ├── Plugin.php                          Hook registration
│   ├── DatePeriod.php                      Value object: start/end/label for week or year
│   ├── Leaderboard/
│   │   ├── LeaderboardEntry.php            Value object: per-affiliate data
│   │   ├── ReferralRepositoryInterface.php Data access contract
│   │   ├── AffWPReferralRepository.php     Production AffiliateWP implementation
│   │   └── WeeklyLeaderboard.php           Aggregation + sorting service
│   ├── Shortcode/
│   │   └── LeaderboardShortcode.php        [affiliate_leaderboard_enhanced] handler
│   └── Widget/
│       └── LeaderboardWidget.php           Sidebar widget
├── assets/
│   └── css/
│       └── leaderboard-enhanced.css        Front-end styles
├── tests/
│   ├── bootstrap.php                       PHPUnit bootstrap (WP_Mock setup)
│   └── Unit/
│       ├── PluginTest.php                  Hook registration tests
│       ├── DatePeriodTest.php              Week DoW calculation + year period tests
│       ├── Leaderboard/
│       │   ├── LeaderboardEntryTest.php
│       │   └── WeeklyLeaderboardTest.php   Sorting, slicing, aggregation
│       ├── Shortcode/
│       │   └── LeaderboardShortcodeTest.php HTML rendering tests
│       └── Widget/
│           └── LeaderboardWidgetTest.php   Period, settings, sanitization, delegation
├── build.gradle
├── composer.json
├── phpcs.xml
└── phpunit.xml
```

---

## License

GPL-2.0-or-later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)
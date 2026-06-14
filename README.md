# AffiliateWP – Leaderboard Enhanced

A WordPress plugin that displays an affiliate sales leaderboard scoped to the **current rolling week**, with the week-start day configurable by the site administrator. Available as both a shortcode and a sidebar widget.

---

## Overview

The standard AffiliateWP leaderboard addon shows all-time cumulative stats. This plugin addresses the common need to highlight which affiliates are performing best *right now* — motivating friendly competition and letting you run weekly contests without manual resets.

The "week" is always a live, rolling 7-day window: from the most recent occurrence of your chosen start day (midnight) through 6 days later (11:59 PM). The window automatically advances each week — no cron jobs or manual intervention required.

---

## Requirements

| Dependency | Minimum version |
|---|---|
| WordPress | 6.4 |
| PHP | 8.1 |
| AffiliateWP | 2.6 |

This plugin is **independent** of the `affiliatewp-leaderboard` addon. Both can be active simultaneously — the original continues to show all-time stats while this plugin shows the current week.

---

## Installation

1. Upload the `affiliatewp-leaderboard-enhanced` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins → Installed Plugins** in the WordPress admin.
3. AffiliateWP must be active — if it is not, the plugin will silently do nothing.

---

## How the Week Window Works

The admin chooses one day of the week as the "week start." The leaderboard always covers:

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

If today **is** the chosen start day, the week begins today. The window advances automatically at midnight when the next occurrence of the start day arrives.

All date calculations use the **WordPress site timezone** (Settings → General → Timezone), not the server timezone.

---

## Shortcode

```
[affiliate_leaderboard_week]
```

### Attributes

| Attribute | Default | Description |
|---|---|---|
| `week_start` | `monday` | Day the leaderboard week begins. Accepts: `sunday` `monday` `tuesday` `wednesday` `thursday` `friday` `saturday` |
| `number` | `10` | Maximum number of affiliates to display |
| `orderby` | `earnings` | Sort metric: `earnings` or `referrals` |
| `order` | `DESC` | Sort direction: `DESC` (highest first) or `ASC` (lowest first) |
| `earnings` | `yes` | Show each affiliate's earnings total: `yes` or `no` |
| `referrals` | `yes` | Show each affiliate's referral count: `yes` or `no` |
| `status` | `paid,unpaid` | Comma-separated referral statuses to include. Accepted values: `paid`, `unpaid`, `pending` |
| `show_label` | `yes` | Show the date range label above the list (e.g. "Jun 10–16, 2026"): `yes` or `no` |

### Examples

**Basic — default Monday week, top 10 by earnings:**
```
[affiliate_leaderboard_week]
```

**Wednesday week start, top 5, earnings only:**
```
[affiliate_leaderboard_week week_start="wednesday" number="5" referrals="no"]
```

**Rank by referral count, paid referrals only, no date label:**
```
[affiliate_leaderboard_week orderby="referrals" status="paid" show_label="no"]
```

**Friday–Thursday week, top 3, show both metrics:**
```
[affiliate_leaderboard_week week_start="friday" number="3" earnings="yes" referrals="yes"]
```

### Output HTML

```html
<div class="affwp-leaderboard-week-wrap">
  <p class="affwp-leaderboard-week-label">Jun 10–16, 2026</p>
  <ol class="affwp-leaderboard affwp-leaderboard-week">
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

When no affiliate activity exists in the current week:

```html
<div class="affwp-leaderboard-week-wrap">
  <p class="affwp-leaderboard-week-empty">No affiliate activity this week.</p>
</div>
```

---

## Sidebar Widget

Navigate to **Appearance → Widgets** (or the block-based widget editor) and add the **Affiliate Week Leaderboard** widget to any sidebar.

### Widget Settings

| Setting | Description |
|---|---|
| **Title** | Widget title displayed above the leaderboard |
| **Week Starts On** | Dropdown: Sunday through Saturday |
| **Affiliates to Show** | Number of affiliates to display (minimum 1) |
| **Order By** | Earnings or Referrals |
| **Show Earnings** | Checkbox — include earnings column |
| **Show Referrals** | Checkbox — include referral count column |
| **Referral Status** | Dropdown: "Paid + Unpaid" or "Paid only" |
| **Show Date Range Label** | Checkbox — show the "Jun 10–16, 2026" label |

The widget renders identical HTML to the shortcode, so any CSS targeting `.affwp-leaderboard-week` applies to both.

---

## Styling

The plugin enqueues `assets/css/leaderboard-enhanced.css` on all front-end pages. It targets:

| Class | Applied to |
|---|---|
| `.affwp-leaderboard-week-wrap` | Outer container `<div>` |
| `.affwp-leaderboard-week-label` | Date range label `<p>` |
| `.affwp-leaderboard` | The `<ol>` list (shared with original addon) |
| `.affwp-leaderboard-week` | Additional class on the `<ol>` for specificity |
| `.affwp-leaderboard-week-empty` | "No activity" message `<p>` |

If the original `affiliatewp-leaderboard` addon is also active, its `.affwp-leaderboard p` styles apply here too, since the `<ol>` carries the same base class.

**To override styles**, add rules to your theme's stylesheet targeting `.affwp-leaderboard-week`:

```css
.affwp-leaderboard-week li {
    padding: 0.5em 0;
    border-bottom: 1px solid #eee;
}

.affwp-leaderboard-week-label {
    font-weight: bold;
    color: #333;
}
```

---

## How Referral Data Is Counted

- Earnings and referral counts are calculated **live** from the referrals table each page load, filtered by the current week window.
- Only referrals with the statuses specified in the `status` attribute (default: `paid` and `unpaid`) are counted.
- `pending` and `rejected` referrals are excluded by default. Include `pending` by adding it to the `status` attribute: `status="paid,unpaid,pending"`.
- Affiliates with zero referrals in the current week do not appear in the list.
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
│   ├── WeekRange.php                       Value object: week start/end/label
│   ├── Leaderboard/
│   │   ├── LeaderboardEntry.php            Value object: per-affiliate data
│   │   ├── ReferralRepositoryInterface.php Data access contract
│   │   ├── AffWPReferralRepository.php     Production AffiliateWP implementation
│   │   └── WeeklyLeaderboard.php           Aggregation + sorting service
│   ├── Shortcode/
│   │   └── LeaderboardShortcode.php        [affiliate_leaderboard_week] handler
│   └── Widget/
│       └── LeaderboardWidget.php           Sidebar widget
├── assets/
│   └── css/
│       └── leaderboard-enhanced.css        Front-end styles
├── tests/
│   ├── bootstrap.php                       PHPUnit bootstrap (WP_Mock setup)
│   └── Unit/
│       ├── PluginTest.php                  Hook registration tests
│       ├── WeekRangeTest.php               DoW calculation + edge cases
│       ├── Leaderboard/
│       │   ├── LeaderboardEntryTest.php
│       │   └── WeeklyLeaderboardTest.php   Sorting, slicing, aggregation
│       ├── Shortcode/
│       │   └── LeaderboardShortcodeTest.php HTML rendering tests
│       └── Widget/
│           └── LeaderboardWidgetTest.php   Settings, sanitization, delegation
├── build.gradle
├── composer.json
├── phpcs.xml
└── phpunit.xml
```

---

## License

GPL-2.0-or-later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

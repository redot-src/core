# Dashboard Notifications

In-app notifications that appear inside the dashboard — the bell in the top bar
and the dedicated notifications page. Use them to tell a signed-in admin that
something happened (a new order, a finished export, a flagged comment) and,
optionally, link them straight to the relevant screen.

They are stored per recipient, so each admin only ever sees their own. A
notification carries a **title**, an optional **link**, a **level** that colours
its status dot, and any **extra data** you want to keep with it.

## Usage

Send one by notifying any recipient — the admin (or user) you want to reach:

```php
use App\Notifications\DashboardNotification;

$admin->notify(
    DashboardNotification::make(__('New order received'))
        ->url(route('dashboard.orders.show', $order))
        ->level('success')
);
```

That's all that's required. The notification shows up immediately in that admin's
bell dropdown and notifications page. You can send to the currently signed-in
admin with `auth('admins')->user()->notify(...)`, or to any admin you've loaded
from the database.

You can also build it in one call instead of chaining:

```php
$admin->notify(new DashboardNotification(
    title: __('Export finished'),
    url: route('dashboard.reports.index'),
    level: 'info',
));
```

## Options

Set these when you create the notification, either as constructor arguments or
with the matching chained method:

- **`title`** — the text shown in the list. The only required value. Wrap it in
  `__()` so it translates.
- **`url`** — where to send the admin when they open the notification. Opening it
  marks it read and redirects here; leave it out to just mark it read and stay on
  the notifications page.
- **`level`** — the severity, which picks the colour of the status dot. One of
  `info` (the default), `success`, `warning`, `danger`, or `error`.
- **`data`** — an array of extra values to store alongside the notification for
  your own use. Not shown in the default UI.

## What the recipient sees

- **Bell dropdown** — a bell in the top bar with a red dot whenever there are
  unread notifications. It lists the five most recent unread ones; each links to
  its `url`.
- **Notifications page** — the full list, filterable by **All / Unread / Read**.
  From here an admin can open a notification, mark it read or unread, or delete
  it.

## Examples

### Notify the current admin after an action

```php
auth('admins')->user()->notify(
    DashboardNotification::make(__('Settings saved'))->level('success')
);
```

### A warning without a link

The notification is still listed and marked read on open, but opening it just
returns to the notifications page:

```php
$admin->notify(
    DashboardNotification::make(__('Storage is almost full'))->level('warning')
);
```

### Notify every administrator

```php
use App\Models\Admin;

Admin::all()->each->notify(
    DashboardNotification::make(__('Nightly backup completed'))
        ->url(route('dashboard.backups.index'))
        ->level('info')
);
```

### Attach extra data

```php
$admin->notify(
    DashboardNotification::make(__('Comment flagged'))
        ->url(route('dashboard.comments.show', $comment))
        ->level('danger')
        ->data(['comment_id' => $comment->id])
);
```

## Related

- [Notifications](/foundation/notifications) — the magic-link login email.
- [Models](/foundation/models) — the base models recipients extend.
- [Toastify](/packages/toastify) — transient on-screen toasts, for one-off
  feedback that doesn't need to be stored.

# Apex Chart Initializer

`apex-chart` renders an [ApexCharts](https://apexcharts.com/) chart inside an element. It backs the [`<x-apex-chart>` component](/components/apex-chart).

## Enable it

You rarely write `init="apex-chart"` yourself — render the [`<x-apex-chart>` component](/components/apex-chart), which adds the attribute and loads the ApexCharts script:

```blade
<x-apex-chart type="bar" :series="[['name' => __('Orders'), 'data' => [12, 19, 7]]]" />
```

To use it on your own markup, load the script with `<x-assets.apexcharts />` and pass the full options as JSON in an `apex-chart` attribute:

```blade
<x-assets.apexcharts />

<div init="apex-chart" apex-chart='{"chart": {"type": "bar"}, "series": [{"name": "Orders", "data": [12, 19, 7]}]}'></div>
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Any [ApexCharts option](https://apexcharts.com/docs/options/) can be set, either in the `apex-chart` JSON or as a `chart-` attribute:

- **`chart-type`** — chart type (`line` by default).
- **`chart-height`** — chart height (`350` by default).
- **`chart-width`** — chart width.
- **Other `chart-` attributes** — set top-level options by path, e.g. `chart-stroke.curve="smooth"`.

The toolbar is hidden by default. Reach the instance later with `$('#my-chart').data('apexcharts')`.

## Related

- [Apex Chart component](/components/apex-chart) — the component that enables this for you.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.

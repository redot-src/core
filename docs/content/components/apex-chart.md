# Apex Chart

`<x-apex-chart>` renders an [ApexCharts](https://apexcharts.com/) chart from data you pass in Blade. Use it for dashboard widgets and report pages: line, bar, area, pie, donut, and every other ApexCharts type.

## Usage

```blade
<x-apex-chart type="bar" :series="[['name' => __('Orders'), 'data' => [12, 19, 7, 24]]]"
    :options="['xaxis' => ['categories' => ['Q1', 'Q2', 'Q3', 'Q4']]]" />
```

The chart fills the width of its container and loads the ApexCharts script for you. With no `type` or `height`, it draws a 350px line chart with the toolbar hidden.

## Options

- **`series`** — the chart data, in [ApexCharts series format](https://apexcharts.com/docs/series/). Accepts an array, a collection, or a JSON string.
- **`options`** — any other [ApexCharts options](https://apexcharts.com/docs/options/) (axes, colors, labels, legend, …). Accepts an array, a collection, or a JSON string.
- **`type`** — chart type, such as `line`, `bar`, `area`, `pie`, or `donut`.
- **`height`** — chart height. A number is treated as pixels; pass a string like `100%` for other units.
- **`width`** — chart width. Defaults to the container's width.
- **`id`** — element id; auto-generated when omitted.

`type`, `height`, and `width` take priority over the same keys in `options`. Any other attribute or class you pass is added to the chart's container.

## Examples

### Area chart from a query

```blade
<x-apex-chart
    type="area"
    :height="300"
    :series="[['name' => __('Sign-ups'), 'data' => $signups->pluck('total')]]"
    :options="[
        'xaxis' => ['categories' => $signups->pluck('date')],
        'stroke' => ['curve' => 'smooth'],
    ]"
/>
```

### Donut chart

For pie and donut charts, `series` is a flat list of values and the slice names go in `labels`:

```blade
<x-apex-chart type="donut" :series="[44, 55, 13]"
    :options="['labels' => [__('Active'), __('Pending'), __('Banned')]]" />
```

### Updating the chart from JavaScript

Once the chart is initialized, its ApexCharts instance is stored on the element, so you can update it later — for example, after fetching fresh data:

```blade
<x-apex-chart id="visits-chart" type="line" />
<button type="button" class="btn" id="refresh-visits">{{ __('Refresh') }}</button>

<script>
    $('#refresh-visits').on('click', function () {
        $('#visits-chart').data('apexcharts').updateSeries([{ name: 'Visits', data: [3, 8, 5] }]);
    });
</script>
```

## Related

- [Apex Chart init](/frontend/inits/apex-chart) — how the chart initializes and the attributes it reads.
- [Components overview](/components/overview) — attribute pass-through.

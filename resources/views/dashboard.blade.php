@extends('layouts.app')
@section('content')
@include('layouts.headers.cards')
@push('head')
<?= __yesset(['dist/css/dashboard.css'],true)?>
@endpush
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @if (getAppSettings('enable_queue_jobs_for_campaigns'))
                @if (!getAppSettings('queue_setup_done_at'))
                    <div class="alert alert-danger"><i class="fa fa-info-circle"></i> {{  __tr('Queue worker setup is required') }}</div>
                @endif
            @else
                @if (!getAppSettings('cron_setup_done_at'))
                    <div class="alert alert-danger"><i class="fa fa-info-circle"></i> {{  __tr('Cron job setup is required') }}</div>
                @endif
            @endif
            @if (!getAppSettings('pusher_app_id'))
                <div class="alert alert-danger"><i class="fa fa-info-circle"></i> {{  __tr('Pusher configuration is required') }}</div>
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card bg-white shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase text-muted font-weight-bold ls-1 mb-1" style="font-size: 0.75rem;">{{  __tr('Last 12 Months') }}</h6>
                            <h2 class="text-dark font-weight-bold mb-0" style="font-size: 1.25rem;">{{  __tr('New Vendor Registrations') }}</h2>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Chart -->
                    <div class="chart position-relative" style="width: 100%; min-height: 280px; max-height: 350px; overflow: hidden;">
                        <canvas id="lwNewVendorRegistrationGraph" class="chart-canvas" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0 font-weight-bold text-dark" style="font-size: 1.1rem;">{{  __tr('New Vendors') }}</h3>
                        </div>
                        <div class="col text-right">
                            <a href="{{ route('central.vendors') }}" class="btn btn-sm btn-primary font-weight-bold" style="border-radius: 6px;">{{  __tr('See all') }}</a>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" style="overflow-x: auto; width: 100%;">
                    <!-- Projects table -->
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col" class="font-weight-bold text-muted" style="font-size: 0.75rem;">{{  __tr('Vendor Title') }}</th>
                                <th scope="col" class="font-weight-bold text-muted" style="font-size: 0.75rem;">{{  __tr('Registered on') }}</th>
                                <th scope="col" class="font-weight-bold text-muted" style="font-size: 0.75rem;">{{  __tr('Vendor Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($newVendors as $newVendor)
                            <tr>
                                <th scope="row" class="font-weight-bold">  <a href="{{ route('vendor.dashboard',['vendorIdOrUid'=>$newVendor->_uid])}}" class="text-primary">{{ $newVendor->title }}</a></th>
                                <td class="text-muted">{{ formatDate($newVendor->created_at) }}</td>
                                <td>
                                    @if(($newVendor->status ?? 1) == 1)
                                        <span class="badge badge-success font-weight-bold" style="border-radius: 6px; padding: 0.35em 0.6em;">{{ configItem("status_codes." . $newVendor->status) }}</span>
                                    @else
                                        <span class="badge badge-secondary font-weight-bold" style="border-radius: 6px; padding: 0.35em 0.6em;">{{ configItem("status_codes." . $newVendor->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('layouts.footers.auth')
</div>
@endsection
@push('js')
<?= __yesset(['dist/js/dashboard.js'],true)?>
@endpush
@push('appScripts')
<script>
        (function($) {
        'use strict';
    var ctx1 = document.getElementById("lwNewVendorRegistrationGraph").getContext("2d");
    var gradientStroke1 = ctx1.createLinearGradient(0, 230, 0, 50);

    gradientStroke1.addColorStop(1, '{{ getUserAppTheme() != 'dark' ? addOpacityToHex(getAppSettings('app_bs_color_primary'), 0.2) : addOpacityToHex(getAppSettings('dark_theme_app_bs_color_primary'), 0.2) }}');
    gradientStroke1.addColorStop(0.2, '{{ getUserAppTheme() != 'dark' ? addOpacityToHex(getAppSettings('app_bs_color_primary'), 0.0) : addOpacityToHex(getAppSettings('dark_theme_app_bs_color_primary'), 0.0) }}');
    gradientStroke1.addColorStop(0, '{{ getUserAppTheme() != 'dark' ? addOpacityToHex(getAppSettings('app_bs_color_primary'), 0) : addOpacityToHex(getAppSettings('dark_theme_app_bs_color_primary'), 0) }}');
    new Chart(ctx1, {
      type: "line",
      data: {
        labels: @json(array_pluck($vendorRegistrations, 'month_name')),
        datasets: [{
          label: "{{ __tr('New Vendor Registrations') }}",
          tension: 0.4,
          borderWidth: 0,
          pointRadius: 0,
          borderColor: "{{ getUserAppTheme() != 'dark' ? getAppSettings('app_bs_color_primary') : getAppSettings('dark_theme_app_bs_color_primary') }}",
          backgroundColor: gradientStroke1,
          borderWidth: 3,
          fill: true,
          data: @json(array_pluck($vendorRegistrations, 'vendors_count')),
          maxBarThickness: 6
        }],
      },
      options: {
        locale : window.appConfig.locale,
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5]
            },
            ticks: {
              display: true,
              padding: 10,
              color: '#fbfbfb'
            }
          },
          x: {
            grid: {
              drawBorder: false,
              display: false,
              drawOnChartArea: false,
              drawTicks: false,
              borderDash: [5, 5]
            },
            ticks: {
              display: true,
              color: '#ccc',
              padding: 20
            }
          },
        },
      },
    });
})(jQuery);
  </script>
@endpush

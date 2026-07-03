@extends('layouts.app', ['title' => __tr('Recharge AI Credits')])

@section('content')
@php
    $vendorId = getVendorId();
    $vendor = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
    $planCredits = $vendor->plan_ai_credits ?? 0;
    $extraCredits = $vendor->extra_ai_credits ?? 0;
    $totalCredits = $planCredits + $extraCredits;
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-gray-800">{{ __tr('Recharge AI Credits') }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <div class="font-weight-bold text-uppercase mb-1">
                        {{ __tr('Current AI Credits Balance') }}
                    </div>
                    <div class="h2 mb-0 font-weight-bold text-white">
                        {{ $totalCredits }}
                    </div>
                    <div class="mt-2 text-white-50 text-sm">
                        {{ __tr('Subscription Credits:') }} {{ $planCredits }}<br>
                        {{ __tr('Purchased Credits:') }} {{ $extraCredits }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __tr('Buy More Credits') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <!-- Pack 1 -->
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                {{ __tr('Starter Pack') }}</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">1,000 {{ __tr('Credits') }}</div>
                                            <div class="mt-3 text-lg font-weight-bold">
                                                $2.00
                                            </div>
                                        </div>
                                    </div>
                                    <form method="post" action="{{ route('vendor.ai_credits.checkout') }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="amount" value="2.00">
                                        <input type="hidden" name="credits" value="1000">
                                        <button type="submit" class="btn btn-success btn-block">{{ __tr('Pay with Wave') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Pack 2 -->
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                {{ __tr('Pro Pack') }}</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">5,000 {{ __tr('Credits') }}</div>
                                            <div class="mt-3 text-lg font-weight-bold">
                                                $8.00
                                            </div>
                                        </div>
                                    </div>
                                    <form method="post" action="{{ route('vendor.ai_credits.checkout') }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="amount" value="8.00">
                                        <input type="hidden" name="credits" value="5000">
                                        <button type="submit" class="btn btn-info btn-block">{{ __tr('Pay with Wave') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Pack 3 -->
                        <div class="col-md-4 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                {{ __tr('Elite Pack') }}</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">10,000 {{ __tr('Credits') }}</div>
                                            <div class="mt-3 text-lg font-weight-bold">
                                                $15.00
                                            </div>
                                        </div>
                                    </div>
                                    <form method="post" action="{{ route('vendor.ai_credits.checkout') }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="amount" value="15.00">
                                        <input type="hidden" name="credits" value="10000">
                                        <button type="submit" class="btn btn-warning btn-block">{{ __tr('Pay with Wave') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #{{ strtoupper(substr($subscription->_uid, 0, 8)) }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            font-size: 14px;
            line-height: 1.6;
        }

        .invoice-wrapper {
            max-width: 760px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        /* Header */
        .invoice-header {
            background: linear-gradient(135deg, #065f46 0%, #10b981 100%);
            color: #fff;
            padding: 36px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .invoice-header .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .invoice-header .invoice-label {
            font-size: 0.8rem;
            opacity: 0.75;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 4px;
        }
        .invoice-header .invoice-num {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .status-active { background: rgba(255,255,255,0.25); color: #fff; }
        .status-expired { background: rgba(239,68,68,0.25); color: #fff; }
        .status-pending { background: rgba(251,191,36,0.25); color: #fff; }

        /* Body */
        .invoice-body { padding: 36px 40px; }

        /* Meta row */
        .meta-row {
            display: flex;
            gap: 32px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #f1f5f9;
        }
        .meta-block { flex: 1; }
        .meta-block .label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .meta-block .value {
            font-size: 0.92rem;
            color: #1e293b;
            font-weight: 500;
        }

        /* Table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .invoice-table thead tr {
            background: #f0fdf4;
        }
        .invoice-table th {
            padding: 10px 16px;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #065f46;
            border-bottom: 2px solid #d1fae5;
        }
        .invoice-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.9rem;
        }
        .invoice-table .amount-col { text-align: right; }
        .invoice-table .total-row td {
            border-top: 2px solid #a7f3d0;
            border-bottom: none;
            padding-top: 16px;
            font-weight: 700;
            color: #1e293b;
            font-size: 1rem;
        }

        /* Payment info */
        .payment-info {
            background: #f0fdf4;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 28px;
            border: 1px solid #d1fae5;
        }
        .payment-info .pi-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #065f46;
            margin-bottom: 10px;
        }
        .payment-info .pi-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            margin-bottom: 4px;
        }
        .payment-info .pi-row .pi-label { color: #64748b; }
        .payment-info .pi-row .pi-val { font-weight: 500; color: #334155; }

        /* Footer */
        .invoice-footer {
            padding: 20px 40px;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: #94a3b8;
        }

        /* Print button */
        .print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #064e3b;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .print-bar .print-title { font-weight: 600; font-size: 0.92rem; }
        .btn-print {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
        }
        .btn-print:hover { background: #059669; }
        .btn-back {
            background: transparent;
            color: rgba(255,255,255,0.85);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 0.82rem;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-right: 8px;
        }
        .btn-back:hover { color: #fff; border-color: rgba(255,255,255,0.6); text-decoration: none; }

        @media print {
            .print-bar { display: none !important; }
            body { background: #fff; padding-top: 0; }
            .invoice-wrapper { margin: 0; box-shadow: none; border-radius: 0; }
        }

        body { padding-top: 56px; }
        @media print { body { padding-top: 0; } }
    </style>
</head>
<body>

<!-- Print Bar -->
<div class="print-bar">
    <div class="print-title">
        &#128196; Facture #{{ strtoupper(substr($subscription->_uid, 0, 8)) }}
    </div>
    <div style="display:flex; align-items:center;">
        <a href="{{ route('subscription.read.show') }}" class="btn-back">
            &#8592; Retour à l'abonnement
        </a>
        <button class="btn-print" onclick="window.print()">
            &#128438; Imprimer / Télécharger PDF
        </button>
    </div>
</div>


<!-- Invoice -->
<div class="invoice-wrapper">

    <!-- Header -->
    <div class="invoice-header">
        <div>
            <div class="brand-name">{{ getAppSettings('name') }}</div>
            <div style="opacity: 0.7; font-size: 0.82rem; margin-top: 4px;">{{ getAppSettings('email') }}</div>
        </div>
        <div style="text-align: right;">
            <div class="invoice-label">Facture</div>
            <div class="invoice-num">#{{ strtoupper(substr($subscription->_uid, 0, 8)) }}</div>
            @php
                $isActive = !($subscription->ends_at && $subscription->ends_at < now()) && $subscription->status == 1;
                $isExpired = $subscription->ends_at && $subscription->ends_at < now();
            @endphp
            <span class="status-badge {{ $isActive ? 'status-active' : ($isExpired ? 'status-expired' : 'status-pending') }}">
                {{ $status_label }}
            </span>
        </div>
    </div>

    <!-- Body -->
    <div class="invoice-body">

        <!-- Meta -->
        <div class="meta-row">
            <div class="meta-block">
                <div class="label">Client</div>
                <div class="value">{{ $vendor->title ?? '' }}</div>
                <div style="color: #64748b; font-size: 0.82rem;">{{ $vendor->email ?? '' }}</div>
            </div>
            <div class="meta-block">
                <div class="label">Date d'émission</div>
                <div class="value">{{ $subscription->created_at ? $subscription->created_at->format('d/m/Y') : '-' }}</div>
            </div>
            <div class="meta-block">
                <div class="label">Date d'expiration</div>
                <div class="value {{ $isExpired ? 'text-danger' : '' }}" style="{{ $isExpired ? 'color:#dc2626' : '' }}">
                    {{ $subscription->ends_at ? $subscription->ends_at->format('d/m/Y') : '-' }}
                </div>
            </div>
        </div>

        <!-- Items table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Période</th>
                    <th class="amount-col">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;">Abonnement — {{ $plan_title }}</div>
                        <div style="color: #64748b; font-size: 0.82rem;">{{ $freq_title }}</div>
                    </td>
                    <td style="color: #64748b;">
                        {{ $subscription->created_at ? $subscription->created_at->format('d/m/Y') : '-' }}
                        →
                        {{ $subscription->ends_at ? $subscription->ends_at->format('d/m/Y') : '-' }}
                    </td>
                    <td class="amount-col" style="font-weight: 600;">
                        {{ formatAmount($subscription->charges, true, true) }}
                    </td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" style="text-align:right; padding-right:24px;">Total</td>
                    <td class="amount-col" style="color: #059669;">
                        {{ formatAmount($subscription->charges, true, true) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Payment details -->
        @if($txn_reference || $payment_method || $subscription->gateway)
        <div class="payment-info">
            <div class="pi-title">Informations de paiement</div>
            @if($payment_method)
            <div class="pi-row">
                <span class="pi-label">Méthode de paiement</span>
                <span class="pi-val">{{ $payment_method }}</span>
            </div>
            @endif
            @if($txn_reference)
            <div class="pi-row">
                <span class="pi-label">Référence transaction</span>
                <span class="pi-val">{{ $txn_reference }}</span>
            </div>
            @endif
            @if($subscription->gateway)
            <div class="pi-row">
                <span class="pi-label">Passerelle</span>
                <span class="pi-val">{{ $subscription->gateway }}</span>
            </div>
            @endif
        </div>
        @endif

        @if($subscription->remarks)
        <div style="background: #fffbeb; border-radius: 8px; padding: 12px 16px; font-size: 0.85rem; color: #78350f; margin-bottom: 20px;">
            <strong>Remarque :</strong> {{ $subscription->remarks }}
        </div>
        @endif

    </div>

    <!-- Footer -->
    <div class="invoice-footer">
        <div>{{ getAppSettings('name') }} &mdash; Facture générée le {{ now()->format('d/m/Y') }}</div>
        <div>Merci pour votre confiance.</div>
    </div>

</div>

</body>
</html>

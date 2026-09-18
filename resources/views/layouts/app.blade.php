<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistem Pembayaran SPP')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f7f9fb;
            color: #191c1e;
        }

        * { box-sizing: border-box; }

        body { margin: 0; }

        .app-shell {
            display: grid;
            grid-template-columns: 16.25rem minmax(0, 1fr);
            min-height: 100vh;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            height: 100vh;
            padding: 1.5rem 1rem;
            position: sticky;
            top: 0;
            border-right: 1px solid #c4c5d5;
            background: #fff;
            color: #191c1e;
            overflow: hidden;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .25rem .5rem;
            color: #00288e;
            text-decoration: none;
        }

        .brand-logo {
            width: 2.25rem;
            height: 2.25rem;
            object-fit: contain;
        }

        .brand-copy { line-height: 1.2; }
        .brand-copy strong, .brand-copy span { display: block; }
        .brand-copy strong { font-size: .95rem; }
        .brand-copy span { margin-top: .2rem; color: #444653; font-size: .72rem; }

        .navigation { display: grid; flex: 1; min-height: 0; gap: 1rem; overflow-y: auto; }
        .navigation-group { display: grid; gap: .2rem; }
        .navigation-heading {
            margin: 0 0 .25rem;
            padding: 0 .75rem;
            color: #505f76;
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .nav-link {
            display: block;
            padding: .65rem .75rem;
            border-radius: .375rem;
            color: #444653;
            font-size: .9rem;
            text-decoration: none;
        }

        .nav-link.active { background: #d0e1fb; color: #00288e; font-weight: 700; }
        .nav-link.pending { color: #757684; cursor: default; }

        .sidebar-footer {
            display: grid;
            flex: 0 0 auto;
            gap: .75rem;
            margin-top: auto;
            padding: 1rem .75rem .25rem;
            border-top: 1px solid #c4c5d5;
        }

        .user-name { color: #191c1e; font-size: .9rem; font-weight: 700; }
        .user-role { margin-top: .2rem; color: #505f76; font-size: .75rem; }

        .logout-button {
            width: 100%;
            padding: .6rem .75rem;
            border: 1px solid #c4c5d5;
            border-radius: .25rem;
            background: transparent;
            color: #444653;
            cursor: pointer;
            font: inherit;
            font-size: .85rem;
            text-align: left;
        }

        .workspace { min-width: 0; }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 5rem;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 1px solid #c4c5d5;
            background: #fff;
        }

        .eyebrow {
            margin: 0 0 .2rem;
            color: #505f76;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .page-title { margin: 0; color: #00288e; font-size: 1.25rem; }
        .content { max-width: 90rem; margin: 0 auto; padding: 1.5rem; }

        .intro-card {
            max-width: 44rem;
            padding: 1.5rem;
            border: 1px solid #c4c5d5;
            border-radius: .5rem;
            background: #fff;
            box-shadow: 0 4px 6px rgb(25 28 30 / 4%);
        }

        .intro-card h2 { margin: 0; color: #191c1e; font-size: 1.25rem; }
        .intro-card p { margin: .75rem 0 0; color: #444653; line-height: 1.65; }

        .page-header { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; }
        .page-header h2 { margin: 0; color: #191c1e; font-size: 1.5rem; letter-spacing: -.01em; }
        .page-header p { margin: .4rem 0 0; color: #444653; font-size: .9rem; line-height: 1.5; }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 2.5rem; padding: .55rem .85rem; border: 1px solid transparent; border-radius: .25rem; cursor: pointer; font: inherit; font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-decoration: none; text-transform: uppercase; }
        .button-primary { background: #00288e; color: #fff; }
        .button-primary:hover { background: #1e40af; }
        .button-secondary { border-color: #c4c5d5; background: #fff; color: #444653; }
        .button-secondary:hover { border-color: #757684; background: #f2f4f6; }
        .button-danger { border-color: #ba1a1a; background: #fff; color: #ba1a1a; }
        .button-danger:hover { background: #ffdad6; }
        .button-small { min-height: 2rem; padding: .35rem .6rem; font-size: .68rem; }
        .data-card, .form-card { border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); }
        .data-card { overflow: hidden; }
        .card-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #c4c5d5; }
        .card-header h3 { margin: 0; font-size: 1rem; }
        .card-header p { margin: .25rem 0 0; color: #505f76; font-size: .82rem; }
        .table-scroll { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: .88rem; text-align: left; }
        .data-table th { padding: .75rem 1rem; border-bottom: 1px solid #c4c5d5; background: #f2f4f6; color: #444653; font-size: .68rem; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
        .data-table td { padding: .9rem 1rem; border-bottom: 1px solid #e0e3e5; color: #191c1e; vertical-align: middle; }
        .data-table tbody tr:last-child td { border-bottom: 0; }
        .data-table tbody tr:hover { background: rgb(221 225 255 / 30%); }
        .tarif-input { min-height: 2.5rem; width: 100%; padding: .55rem .65rem; border: 1px solid #c4c5d5; border-radius: .25rem; background: #fff; color: #191c1e; font: inherit; font-size: .9rem; }
        .tarif-input:focus { outline: 0; border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
        .file-picker { display: flex; align-items: center; min-height: 2.75rem; border: 1px solid #c4c5d5; border-radius: .25rem; background: #fff; overflow: hidden; }
        .file-picker:focus-within { border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
        .file-picker-input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); clip-path: inset(50%); white-space: nowrap; }
        .file-picker-button { display: inline-flex; align-items: center; gap: .45rem; align-self: stretch; padding: 0 .85rem; background: #e8eaf0; color: #00288e; cursor: pointer; font-size: .82rem; font-weight: 700; }
        .file-picker-button:hover { background: #d0e1fb; }
        .file-picker-button svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2; }
        .file-picker-name { padding: .55rem .75rem; overflow: hidden; color: #505f76; font-size: .84rem; text-overflow: ellipsis; white-space: nowrap; }
        .table-sort-button { display: inline-flex; align-items: center; gap: .35rem; padding: 0; border: 0; background: transparent; color: inherit; cursor: pointer; font: inherit; font-weight: inherit; letter-spacing: inherit; text-align: inherit; text-transform: inherit; }
        .table-sort-button:hover, .table-sort-button:focus-visible { color: #00288e; outline: 0; }
        .table-sort-button:focus-visible { box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
        .table-sort-indicator { min-width: .65rem; color: #757684; font-size: .75rem; }
        .table-sort-button.is-active .table-sort-indicator { color: #00288e; }
        .text-muted { color: #505f76 !important; }
        .text-mono { font-family: "Courier Prime", ui-monospace, monospace; }
        .text-right { text-align: right; }
        .status-badge { display: inline-flex; align-items: center; padding: .25rem .5rem; border-radius: 9999px; font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .status-active { background: #dff7ed; color: #087443; }
        .status-inactive { background: #eceef0; color: #505f76; }
        .status-warning { background: #ffe08a; color: #6a4300; }
        .status-lunas { background: #dff7ed; color: #087443; }
        .status-belum-bayar { background: #ffdad6; color: #93000a; }
         .status-tunggakan { background: #ffe08a; color: #6a4300; }
         .filter-bar { display: flex; flex-wrap: wrap; align-items: end; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #c4c5d5; background: #fff; }
        .filter-field { display: grid; gap: .4rem; min-width: 10rem; }
        .filter-field label, .form-field label { color: #444653; font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .filter-field input, .filter-field select, .form-field input, .form-field select { min-height: 2.5rem; width: 100%; padding: .55rem .65rem; border: 1px solid #c4c5d5; border-radius: .25rem; background: #fff; color: #191c1e; font: inherit; font-size: .9rem; }
        .filter-field input:focus, .filter-field select:focus, .form-field input:focus, .form-field select:focus { outline: 0; border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
        .empty-state { padding: 2rem 1.25rem; color: #505f76; text-align: center; }
        .form-card { max-width: 48rem; padding: 1.5rem; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
         .form-field { display: grid; gap: .4rem; }
         .form-field textarea { min-height: 7rem; width: 100%; padding: .7rem .75rem; border: 1px solid #c4c5d5; border-radius: .25rem; background: #fff; color: #191c1e; font: inherit; font-size: .9rem; line-height: 1.5; resize: vertical; }
         .form-field textarea:focus { outline: 0; border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
         .cancellation-form { display: grid; gap: 1rem; padding: 1.25rem; background: #fffaf9; }
         .cancellation-form .form-field { max-width: 46rem; }
         .cancellation-form .form-field label span { color: #ba1a1a; }
         .cancellation-form .button-danger { justify-self: start; }
         .form-field.full-width { grid-column: 1 / -1; }
        .field-error { margin: 0; color: #ba1a1a; font-size: .78rem; }
        .checkbox-field { display: flex; align-items: center; gap: .55rem; margin-top: 1rem; color: #444653; font-size: .86rem; }
        .checkbox-field input { width: 1rem; height: 1rem; accent-color: #00288e; }
        .form-actions { display: flex; justify-content: end; gap: .75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e0e3e5; }
        .flash-message { margin-bottom: 1rem; padding: .75rem 1rem; border: 1px solid #9bdfbe; border-radius: .25rem; background: #eafaf1; color: #087443; font-size: .88rem; }
        .error-message { margin-bottom: 1rem; padding: .75rem 1rem; border: 1px solid #ffb4ab; border-radius: .25rem; background: #ffdad6; color: #93000a; font-size: .88rem; }
        .inline-form { display: inline; }
        .action-stack { display: inline-flex; flex-wrap: wrap; justify-content: end; gap: .4rem; }
        .reference-note { color: #757684; font-size: .76rem; }
        .payment-layout { display: grid; grid-template-columns: minmax(0, 2fr) minmax(17rem, 1fr); gap: 1.5rem; align-items: start; }
        .payment-summary { position: sticky; top: 6.5rem; padding: 1.25rem; border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); }
        .payment-summary h3 { margin: 0 0 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e0e3e5; font-size: 1rem; }
        .payment-summary-row { display: flex; justify-content: space-between; gap: 1rem; color: #505f76; font-size: .88rem; }
        .payment-summary-row strong { color: #191c1e; }
        .payment-total { display: grid; gap: .4rem; margin: 1.25rem 0; padding: 1rem; border-left: 4px solid #00288e; border-radius: .25rem; background: #f2f4f6; }
        .payment-total span { color: #505f76; font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .payment-total strong { color: #00288e; font-size: 1.35rem; }
        .payment-summary .button { width: 100%; }
        .payment-summary .reference-note { display: block; margin: 1rem 0 0; line-height: 1.5; }
        .receipt-preview { max-width: 50rem; margin: 0 auto; }
        .receipt-actions { display: flex; justify-content: space-between; gap: .75rem; margin-bottom: 1.5rem; }
        .receipt-actions-group { display: flex; gap: .75rem; }
        .receipt-paper { padding: 2.5rem; border: 1px solid #c4c5d5; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); }
        .receipt-school { display: flex; align-items: center; gap: 1rem; padding-bottom: 1.25rem; border-bottom: 2px solid #191c1e; }
        .receipt-school-logo { width: 3.5rem; height: 3.5rem; object-fit: contain; }
        .receipt-school h2, .receipt-title h3 { margin: 0; }
        .receipt-school p { margin: .25rem 0 0; color: #505f76; font-size: .8rem; }
        .receipt-title { margin: 1.75rem 0; text-align: center; }
        .receipt-title h3 { font-size: 1.1rem; text-decoration: underline; text-transform: uppercase; }
        .receipt-number { margin: .4rem 0 0; color: #444653; font-size: .85rem; }
        .receipt-info { width: 100%; margin-bottom: 1.5rem; border-collapse: collapse; font-size: .9rem; }
        .receipt-info th, .receipt-info td { padding: .3rem 0; text-align: left; vertical-align: top; }
        .receipt-info th { width: 10rem; color: #505f76; font-weight: 400; }
        .receipt-info .separator { width: 1.5rem; color: #505f76; }
        .receipt-detail { margin: 1.5rem 0; border: 1px solid #c4c5d5; }
        .receipt-detail th { padding: .65rem .75rem; background: #f2f4f6; color: #444653; font-size: .68rem; letter-spacing: .05em; text-align: left; text-transform: uppercase; }
        .receipt-detail td { padding: .75rem; border-top: 1px solid #e0e3e5; font-size: .88rem; }
        .receipt-total { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: 1.5rem 0 3rem; padding: 1rem; border: 1px solid #c4c5d5; background: #eceef0; }
        .receipt-total span { color: #444653; font-size: .8rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .receipt-total strong { color: #191c1e; font-size: 1.3rem; }
        .receipt-signatures { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 3rem; text-align: center; }
        .receipt-signatures p { margin: 0; color: #505f76; font-size: .82rem; }
        .signature-line { margin: 4rem auto .5rem; max-width: 10rem; border-bottom: 1px solid #191c1e; }
        .history-detail-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; padding: 1.25rem; }
        .history-detail-item { display: grid; gap: .35rem; }
        .history-detail-item span { color: #505f76; font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .history-detail-item strong { color: #191c1e; font-size: .9rem; }
        .table-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-top: 1px solid #c4c5d5; color: #505f76; font-size: .8rem; }
        .pagination-links { display: flex; gap: .5rem; }
        .pagination-links a, .pagination-links span { min-width: 2.1rem; padding: .45rem .65rem; border: 1px solid #c4c5d5; border-radius: .25rem; color: #444653; font-size: .75rem; font-weight: 700; text-align: center; text-decoration: none; }
        .pagination-links a:hover { border-color: #00288e; background: #f2f4f6; color: #00288e; }
        .pagination-links span { color: #a0a0aa; }
        .report-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .report-summary-item { position: relative; display: grid; gap: .5rem; padding: 1.25rem 1.5rem; overflow: hidden; border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); }
        .report-summary-item::before { position: absolute; inset: 0 auto 0 0; width: 4px; background: #00288e; content: ''; }
        .report-summary-item:nth-child(2)::before { background: #087443; }
        .report-summary-item:nth-child(3)::before { background: #505f76; }
        .report-summary-item span { color: #505f76; font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .report-summary-item strong { color: #191c1e; font-size: 1.35rem; }
        .filter-note { margin: 0; padding: 0 1.25rem 1rem; color: #505f76; font-size: .78rem; line-height: 1.5; }
        .dashboard-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .dashboard-summary-item { position: relative; display: grid; min-height: 8rem; gap: .75rem; padding: 1.25rem 1.5rem; overflow: hidden; border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); }
        .dashboard-summary-item::before { position: absolute; inset: 0 auto 0 0; width: 4px; background: #505f76; content: ''; }
        .dashboard-summary-item:nth-child(2)::before { background: #00288e; }
        .dashboard-summary-item:nth-child(3)::before { background: #087443; }
        .dashboard-summary-item:nth-child(4)::before { background: #ba1a1a; }
        .dashboard-summary-item span { color: #505f76; font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
         .dashboard-summary-item strong { align-self: end; color: #191c1e; font-size: 1.45rem; }
         .finance-chart-card { margin-bottom: 1.5rem; }
         .finance-chart-total { color: #087443; font-size: 1rem; white-space: nowrap; }
         .finance-line-chart { height: 18rem; padding: 1.5rem; }
         .finance-line-chart canvas { width: 100% !important; height: 100% !important; }
         .finance-line-details { display: flex; flex-wrap: wrap; gap: .5rem 1rem; padding: 1rem 1.5rem 1.25rem; border-top: 1px solid #e0e3e5; color: #505f76; font-family: "Courier Prime", ui-monospace, monospace; font-size: .68rem; }
         .finance-line-details span { white-space: nowrap; }
         .finance-line-details strong { color: #191c1e; }
         .finance-chart-empty { margin: 1rem 1.5rem -1rem; color: #505f76; font-size: .82rem; }
         .confirmation-dialog { position: fixed; inset: 0; width: min(100% - 2rem, 30rem); height: fit-content; margin: auto; padding: 0; border: 1px solid #c4c5d5; border-radius: .5rem; box-shadow: 0 1.5rem 4rem rgb(25 28 30 / 24%); }
         .confirmation-dialog::backdrop { background: rgb(25 28 30 / 48%); }
         .confirmation-dialog form { padding: 1.5rem; }
          .confirmation-dialog h2 { margin: 0; color: #191c1e; font-size: 1.1rem; }
          .confirmation-dialog p { margin: .65rem 0 0; color: #505f76; font-size: .88rem; line-height: 1.55; }
          .confirmation-details { margin-top: 1rem; }
          .confirmation-details ul { max-height: 10rem; margin: .5rem 0 0; padding-left: 1.25rem; overflow-y: auto; color: #191c1e; font-size: .84rem; line-height: 1.6; }
         .confirmation-input { display: grid; gap: .4rem; margin-top: 1.25rem; }
         .confirmation-input label { color: #444653; font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
         .confirmation-input input { min-height: 2.5rem; width: 100%; padding: .55rem .65rem; border: 1px solid #c4c5d5; border-radius: .25rem; color: #191c1e; font: inherit; }
         .confirmation-input input:focus { outline: 0; border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
         .confirmation-dialog .form-actions { margin-bottom: 0; }
         .dashboard-layout { display: grid; grid-template-columns: minmax(0, 2fr) minmax(17rem, 1fr); gap: 1.5rem; align-items: start; }
        .dashboard-quick-actions { display: grid; gap: 1rem; padding: 1.25rem; border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); }
        .dashboard-quick-actions h3 { margin: 0; font-size: 1rem; }
        .dashboard-quick-actions p { margin: -.55rem 0 0; color: #505f76; font-size: .82rem; line-height: 1.5; }
        .dashboard-search { display: grid; gap: .4rem; }
        .dashboard-search label { color: #444653; font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .dashboard-search input { min-height: 2.5rem; width: 100%; padding: .55rem .65rem; border: 1px solid #c4c5d5; border-radius: .25rem; background: #fff; color: #191c1e; font: inherit; font-size: .9rem; }
        .dashboard-search input:focus { outline: 0; border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
        .dashboard-links { display: grid; gap: .5rem; padding-top: 1rem; border-top: 1px solid #e0e3e5; }
        .dashboard-links a { display: flex; align-items: center; justify-content: space-between; padding: .65rem .75rem; border-radius: .25rem; color: #444653; font-size: .85rem; text-decoration: none; }
        .dashboard-links a:hover { background: #f2f4f6; color: #00288e; }

        @media (max-width: 760px) {
            .app-shell { display: block; }
            .sidebar { gap: 1rem; height: auto; padding: 1rem; position: static; border-right: 0; border-bottom: 1px solid #c4c5d5; overflow: visible; }
            .navigation { display: flex; flex: none; min-height: auto; gap: .5rem; overflow-x: auto; overflow-y: hidden; padding-bottom: .25rem; }
            .navigation-group { display: flex; flex: 0 0 auto; gap: .2rem; }
            .navigation-heading { display: none; }
            .nav-link { white-space: nowrap; }
            .sidebar-footer { display: flex; align-items: center; justify-content: space-between; margin: 0; padding: .75rem 0 0; }
            .logout-button { width: auto; }
            .topbar { min-height: 4.5rem; padding: 1rem 1.25rem; }
            .content { padding: 1.25rem; }
            .intro-card { padding: 1.5rem; }
            .page-header { align-items: start; flex-direction: column; }
            .form-grid { grid-template-columns: 1fr; }
            .button { width: 100%; }
            .filter-field { width: 100%; }
             .form-actions { flex-direction: column-reverse; }
             .file-picker { align-items: stretch; }
             .file-picker-name { min-width: 0; }
            .action-stack { justify-content: start; }
            .payment-layout { grid-template-columns: 1fr; }
            .payment-summary { position: static; }
            .receipt-actions, .receipt-actions-group { flex-direction: column; width: 100%; }
            .receipt-paper { padding: 1.5rem; }
            .receipt-school { align-items: start; }
            .receipt-info th { width: 7.5rem; }
            .receipt-signatures { gap: 1.5rem; }
            .history-detail-grid { grid-template-columns: 1fr; }
            .table-footer { align-items: start; flex-direction: column; }
             .report-summary { grid-template-columns: 1fr; }
             .dashboard-summary, .dashboard-layout { grid-template-columns: 1fr; }
             .finance-line-chart { height: 15rem; padding: 1rem .75rem; }
             .finance-line-details { padding: 1rem .75rem; font-size: .6rem; }
             .confirmation-dialog .form-actions { flex-direction: column-reverse; }
         }

        @media print {
            @page { margin: 12mm; }
            body { background: #fff; }
            body * { visibility: hidden; }
            .receipt-paper, .receipt-paper * { visibility: visible; }
            .app-shell { display: block; }
            .sidebar, .topbar, .receipt-actions { display: none; }
            .workspace, .content { max-width: none; margin: 0; padding: 0; }
            .receipt-preview { max-width: none; margin: 0; }
            .receipt-paper { position: absolute; inset: 0; width: 100%; padding: 0; border: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('home') }}">
                <img class="brand-logo" src="{{ asset('images/cbi.png') }}?v={{ filemtime(public_path('images/cbi.png')) }}" alt="Logo SMK Informatika CBI">
                <span class="brand-copy">
                    <strong>Pembayaran SPP</strong>
                    <span>Administrasi Sekolah</span>
                </span>
            </a>

            <nav class="navigation" aria-label="Navigasi utama">
                <div class="navigation-group">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Dashboard</a>
                </div>

                <div class="navigation-group">
                    <p class="navigation-heading">Master Data</p>
                    <a class="nav-link {{ request()->routeIs('master.siswa.*') ? 'active' : '' }}" href="{{ route('master.siswa.index') }}">Data Siswa</a>
                    <a class="nav-link {{ request()->routeIs('master.jurusan.*') ? 'active' : '' }}" href="{{ route('master.jurusan.index') }}">Data Jurusan</a>
                    <a class="nav-link {{ request()->routeIs('master.kelas.*') ? 'active' : '' }}" href="{{ route('master.kelas.index') }}">Data Kelas</a>
                    <a class="nav-link {{ request()->routeIs('master.tahun-ajaran.*') ? 'active' : '' }}" href="{{ route('master.tahun-ajaran.index') }}">Tahun Ajaran</a>
                    <a class="nav-link {{ request()->routeIs('master.tarif-spp.*') ? 'active' : '' }}" href="{{ route('master.tarif-spp.index') }}">Tarif SPP</a>
                    <a class="nav-link {{ request()->routeIs('kenaikan-kelas.*') ? 'active' : '' }}" href="{{ route('kenaikan-kelas.preview') }}">Kenaikan Kelas</a>
                </div>

                <div class="navigation-group">
                    <p class="navigation-heading">Pembayaran</p>
                    <a class="nav-link {{ request()->routeIs('pembayaran.*') ? 'active' : '' }}" href="{{ route('pembayaran.index') }}">Transaksi Pembayaran</a>
                    <a class="nav-link {{ request()->routeIs('riwayat-pembayaran.*') ? 'active' : '' }}" href="{{ route('riwayat-pembayaran.index') }}">Riwayat Pembayaran</a>
                    <a class="nav-link {{ request()->routeIs('arsip-kwitansi.*') ? 'active' : '' }}" href="{{ route('arsip-kwitansi.index') }}">Arsip Kwitansi Siswa</a>
                </div>

                <div class="navigation-group">
                    <p class="navigation-heading">Laporan</p>
                    <a class="nav-link {{ request()->routeIs('rekap-pembayaran.*') ? 'active' : '' }}" href="{{ route('rekap-pembayaran.index') }}">Rekap Pembayaran</a>
                    <a class="nav-link {{ request()->routeIs('laporan-tunggakan.*') ? 'active' : '' }}" href="{{ route('laporan-tunggakan.index') }}">Laporan Tunggakan</a>
                    <a class="nav-link {{ request()->routeIs('status-spp.*') ? 'active' : '' }}" href="{{ route('status-spp.index') }}">Status Pembayaran SPP</a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div>
                    <div class="user-name">{{ auth()->user()->nama }}</div>
                    <div class="user-role">Petugas TU</div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-button" type="submit">Logout</button>
                </form>
            </div>
        </aside>

        <section class="workspace">
            <header class="topbar">
                <div>
                    <p class="eyebrow">Sistem Pembayaran SPP</p>
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                </div>
            </header>

            <main class="content">
                @if ($errors->any())
                    <div class="error-message" role="alert">
                        {{ $errors->has('form') ? $errors->first('form') : 'Periksa kembali input yang ditandai sebelum melanjutkan.' }}
                    </div>
                @endif
                @yield('content')
            </main>
        </section>
    </div>
    <dialog id="confirmation-dialog" class="confirmation-dialog" aria-labelledby="confirmation-title">
        <form id="confirmation-form" method="dialog">
            <h2 id="confirmation-title">Konfirmasi tindakan</h2>
            <p id="confirmation-message"></p>
            <div id="confirmation-details" class="confirmation-details" hidden></div>
            <div id="confirmation-input-field" class="confirmation-input" hidden>
                <label id="confirmation-input-label" for="confirmation-input"></label>
                <input id="confirmation-input" type="text" autocomplete="off">
                <p id="confirmation-input-error" class="field-error" role="alert" hidden></p>
            </div>
            <div class="form-actions">
                <button id="confirmation-cancel" class="button button-secondary" type="button">Batal</button>
                <button id="confirmation-submit" class="button button-primary" type="button">Lanjutkan</button>
            </div>
        </form>
    </dialog>
    <script>
        document.querySelectorAll('.data-table').forEach((table) => {
            if (table.dataset.sortable === 'false') {
                return;
            }

            const body = table.tBodies[0];

            if (!body) {
                return;
            }

            table.querySelectorAll('thead th').forEach((header, index) => {
                const label = header.textContent.trim();

                if (['Aksi', 'Pilih'].includes(label) || header.dataset.sortable === 'false') {
                    return;
                }

                const button = document.createElement('button');
                const indicator = document.createElement('span');
                button.type = 'button';
                button.className = 'table-sort-button';
                header.setAttribute('aria-sort', 'none');
                button.textContent = label;
                indicator.className = 'table-sort-indicator';
                indicator.setAttribute('aria-hidden', 'true');
                indicator.textContent = '↕';
                button.append(indicator);
                header.replaceChildren(button);

                button.addEventListener('click', () => {
                    const direction = button.dataset.direction === 'asc' ? 'desc' : 'asc';
                    const rows = Array.from(body.rows);

                    rows.sort((firstRow, secondRow) => {
                        const first = sortableValue(firstRow.cells[index]?.dataset.sortValue ?? firstRow.cells[index]?.textContent ?? '');
                        const second = sortableValue(secondRow.cells[index]?.dataset.sortValue ?? secondRow.cells[index]?.textContent ?? '');
                        const result = first.type === second.type && first.type !== 'text'
                            ? first.value - second.value
                            : first.text.localeCompare(second.text, 'id', { numeric: true, sensitivity: 'base' });

                        return direction === 'asc' ? result : -result;
                    });

                    rows.forEach((row) => body.append(row));
                    table.querySelectorAll('.table-sort-button').forEach((item) => {
                        item.dataset.direction = '';
                        item.classList.remove('is-active');
                        item.closest('th').setAttribute('aria-sort', 'none');
                        item.querySelector('.table-sort-indicator').textContent = '↕';
                    });
                    button.dataset.direction = direction;
                    button.classList.add('is-active');
                    header.setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');
                    indicator.textContent = direction === 'asc' ? '↑' : '↓';
                });
            });
        });

        function sortableValue(value) {
            const text = value.trim().replace(/\s+/g, ' ');
            const date = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})(?: (\d{1,2}):(\d{2}))?$/);
            const month = text.match(/^(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember) (\d{4})$/);
            const amount = text.match(/^Rp ([\d.]+)$/);

            if (date) {
                return {
                    type: 'number',
                    value: Date.UTC(date[3], date[2] - 1, date[1], date[4] ?? 0, date[5] ?? 0),
                    text,
                };
            }

            if (month) {
                return {
                    type: 'number',
                    value: Number(month[2]) * 12 + ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'].indexOf(month[1]),
                    text,
                };
            }

            if (amount) {
                return { type: 'number', value: Number(amount[1].replaceAll('.', '')), text };
            }

            if (/^\d+$/.test(text)) {
                return { type: 'number', value: Number(text), text };
            }

            return { type: 'text', value: 0, text };
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Factura {{ $codigo_factura }}</title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }

        * {
            box-sizing: border-box;
            font-family: Helvetica, Arial, sans-serif !important;
        }

        body,
        table,
        td,
        div,
        p,
        span {
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.4;
        }

        body {
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* ── CABECERA ── */
        .header-table {
            margin-bottom: 30px;
        }

        .header-logo {
            width: 40%;
            vertical-align: top;
        }

        .header-logo img {
            width: 150px;
            height: auto;
        }

        .header-emisor {
            width: 60%;
            text-align: right;
            vertical-align: top;
        }

        .titulo-factura {
            font-size: 32px;
            font-weight: bold;
            color: #bb0911;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        .emisor-datos {
            font-size: 12px;
            color: #444;
            line-height: 1.6;
        }

        .linea-roja {
            border: none;
            border-top: 1.5px solid #bb0911;
            margin: 5px 0 10px 0;
        }

        /* ── BLOQUE MEDIO ── */
        .bloque-medio {
            width: 100%;
            margin-bottom: 30px;
        }

        .col-cliente {
            width: 55%;
            vertical-align: top;
        }

        .col-factura-info {
            width: 45%;
            vertical-align: top;
            text-align: right;
        }

        .seccion-titulo {
            font-weight: bold;
            font-size: 11px;
            color: #bb0911;
            display: block;
            margin-bottom: 2px;
        }

        .info-tabla {
            width: auto;
            margin-left: auto;
        }

        .info-tabla td {
            padding: 4px 8px;
        }

        .info-tabla tr:first-child td:last-child {
            border-bottom: 1.5px solid #bb0911;
        }

        .info-tabla td.label {
            font-weight: bold;
            text-align: right;
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
        }

        /* ── DESCRIPCIÓN ── */
        .descripcion-box {
            margin-bottom: 30px;
        }

        .descripcion-box p {
            padding: 10px 0;
            font-size: 13px;
        }

        /* ── TABLA IMPORTES ── */
        .tabla-importes {
            width: 100%;
            border: 1.5px solid #bb0911;
            margin-bottom: 40px;
        }

        .tabla-importes td {
            padding: 8px 15px;
            font-size: 12px;
        }

        .td-concepto {
            text-align: left;
            font-weight: bold;
        }

        .td-porcentaje {
            text-align: center;
            color: #666;
        }

        .td-valor {
            text-align: right;
            font-weight: bold;
        }

        .fila-total td {
            background-color: #bb0911;
            color: #ffffff;
            font-size: 16px;
        }

        /* ── FOOTER IBAN ── */
        .footer-iban {
            margin-top: 50px;
            text-align: right;
        }

        .iban-titulo {
            font-weight: bold;
            color: #bb0911;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .iban-numero {
            font-family: monospace;
            font-size: 13px;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>

    {{-- ── CABECERA ── --}}
    <table class="header-table">
        <tr>
            <td class="header-logo">
                <x-application-logo />
            </td>
            <td class="header-emisor">
                <div class="titulo-factura">FACTURA</div>
                <hr class="linea-roja">
                <div class="emisor-datos">
                    <strong>Guillermo Carlos González Fernández</strong><br>
                    N.I.F: 09439818C<br>
                    C/ Francisco Eiriz 20, 1º Dcha<br>
                    33212 Gijón
                </div>
            </td>
        </tr>
    </table>

    {{-- ── CLIENTE + INFO ── --}}
    <table class="bloque-medio">
        <tr>
            <td class="col-cliente">
                <span class="seccion-titulo">DATOS CLIENTE</span>
                <hr class="linea-roja" style="width: 100px;">
                <strong>{{ $cliente->nombre }}</strong><br>
                @if ($cliente->nif_cif)
                    {{ $cliente->nif_cif }}<br>
                @endif
                @if ($cliente->direccion)
                    {!! nl2br(e($cliente->direccion)) !!}
                    <br>
                    {{ $cliente->codigo_postal }} - {{ $cliente->localidad }}<br>
                    {{ $cliente->provincia }}
                @endif
            </td>
            <td class="col-factura-info">
                <table class="info-tabla">
                    <tr>
                        <td class="label">Nº Factura:</td>
                        <td>{{ $codigo_factura }}</td>
                    </tr>
                    <tr>
                        <td class="label">Fecha:</td>
                        <td>{{ $fecha }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ── DESCRIPCIÓN ── --}}
    <div class="descripcion-box">
        <span class="seccion-titulo">DESCRIPCIÓN</span>
        <hr class="linea-roja">
        <p>
            {{ $factura->concepto }}
            @if ($factura->fecha_evento)
                ({{ \Carbon\Carbon::parse($factura->fecha_evento)->format('d/m/Y') }})
            @endif
        </p>
    </div>

    {{-- ── IMPORTES ── --}}
    <table class="tabla-importes">
        @if ($factura->iva_porcentaje > 0 || $factura->irpf_porcentaje > 0)
            <tr>
                <td class="td-concepto">BASE IMPONIBLE</td>
                <td class="td-porcentaje"></td>
                <td class="td-valor">{{ number_format($factura->subtotal, 2) }} €</td>
            </tr>
            @if ($factura->iva_porcentaje > 0)
                <tr>
                    <td class="td-concepto">IVA</td>
                    <td class="td-porcentaje">{{ number_format($factura->iva_porcentaje, 0) }}%</td>
                    <td class="td-valor">{{ number_format($factura->iva_monto, 2) }} €</td>
                </tr>
            @endif
            @if ($factura->irpf_porcentaje > 0)
                <tr>
                    <td class="td-concepto">IRPF</td>
                    <td class="td-porcentaje">{{ number_format($factura->irpf_porcentaje, 0) }}%</td>
                    <td class="td-valor">{{ number_format($factura->irpf_monto, 2) }} €</td>
                </tr>
            @endif
        @endif
        <tr class="fila-total">
            <td class="td-concepto">IMPORTE TOTAL</td>
            <td class="td-porcentaje">
                @if ($factura->iva_porcentaje > 0)
                    <small>(B.I. + IVA - IRPF)</small>
                @endif
            </td>
            <td class="td-valor">{{ number_format($factura->monto, 2) }} €</td>
        </tr>
    </table>

    {{-- ── FOOTER ── --}}
    <div class="footer-iban">
        <hr class="linea-roja">
        <div class="iban-titulo">Nº CUENTA PARA EL INGRESO</div>
        <div class="iban-numero">ES83 2100 5700 0502 0015 7257</div>
    </div>

</body>

</html>

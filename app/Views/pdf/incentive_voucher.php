<?php

$borrowerName = trim(
    ($borrower['first_name'] ?? '') . ' ' .
    ($borrower['middle_name'] ?? '') . ' ' .
    ($borrower['last_name'] ?? '')
);

$incentiveMonth = !empty($incentive['incentive_month']) 
    ? date('F Y', strtotime($incentive['incentive_month'])) 
    : 'N/A';

$incentiveType = $incentive['incentive_type_name'] ?? $incentive['incentive_type'] ?? 'N/A';

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
</head>

<body style="padding:35px; margin:0; font-size:16px; line-height:1.4; position: relative;">

<!-- ========================================================= -->
<!-- WATERMARK LOGO -->
<!-- ========================================================= -->

<img src="https://indopacificlending.doitcebutech.com/assets/img/logo.png" 
     alt="Watermark" 
     style="
         position: fixed;
         top: 50%;
         left: 50%;
         transform: translate(-50%, -50%);
         width: 400px;
         height: auto;
         opacity: 0.08;
         z-index: -1;
         pointer-events: none;
     ">

<!-- ========================================================= -->
<!-- HEADER -->
<!-- ========================================================= -->

<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="text-align:center; font-weight:bold; font-size:20px;">
            INDO-PACIFIC LENDING CORPORATION
            <br>
            <span style="font-size:18px;">
                INCENTIVE VOUCHER
            </span>
        </td>
    </tr>
</table>

<br>

<!-- ========================================================= -->
<!-- VOUCHER NUMBER & DATE -->
<!-- ========================================================= -->

<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:50%; text-align:left;">
            <b>Voucher No.:</b> <?= esc($voucherNumber) ?>
        </td>
        <td style="width:50%; text-align:right;">
            <b>Date:</b> <?= esc($dateGenerated) ?>
        </td>
    </tr>
</table>

<br>

<!-- ========================================================= -->
<!-- BORROWER INFORMATION -->
<!-- ========================================================= -->

<table style="width:100%; border-collapse:collapse; font-size:14px;">
    <tr>
        <td style="width:50%; padding:5px;">
            <b>Borrower:</b> <?= esc($borrowerName) ?>
        </td>
        <td style="width:50%; padding:5px;">
            <b>Borrower ID:</b> <?= esc($borrower['borrower_id'] ?? '') ?>
        </td>
    </tr>
    <tr>
        <td style="padding:5px;">
            <b>Incentive Type:</b> <?= esc($incentiveType) ?>
        </td>
        <td style="padding:5px;">
            <b>Period:</b> <?= esc($incentiveMonth) ?>
        </td>
    </tr>
    <tr>
        <td style="padding:5px;">
            <b>Status:</b> 
            <span style="color: green; font-weight: bold;">
                <?= esc(strtoupper($incentive['status'] ?? 'PAID')) ?>
            </span>
        </td>
        <td style="padding:5px;">
            <b>Release Date:</b> <?= date('F d, Y') ?>
        </td>
    </tr>
</table>

<br>

<!-- ========================================================= -->
<!-- ACKNOWLEDGEMENT -->
<!-- ========================================================= -->

<div style="text-align:justify;">
    This is to certify that
    <b><?= esc($borrowerName) ?></b>
    has received the incentive amount of
    <b>PHP <?= number_format($incentiveAmount, 2) ?></b>
    (<i><?= esc($amountInWords) ?></i>)
    from
    <b>INDO-PACIFIC LENDING CORPORATION</b>
    for the period of
    <b><?= esc($incentiveMonth) ?></b>
    as
    <b><?= esc($incentiveType) ?></b>.
</div>

<br>

<!-- ========================================================= -->
<!-- AMOUNT DETAILS -->
<!-- ========================================================= -->

<table style="width:100%; border-collapse:collapse; font-size:14px;">
    <tr>
        <td style="width:30%; padding:8px; background-color:#f0f0f0; font-weight:bold;">
            Incentive Amount:
        </td>
        <td style="width:70%; padding:8px; text-align:right; font-weight:bold; font-size:16px;">
            PHP <?= number_format($incentiveAmount, 2) ?>
        </td>
    </tr>
    <tr>
        <td style="padding:8px; background-color:#f0f0f0; font-weight:bold;">
            Amount in Words:
        </td>
        <td style="padding:8px; font-style:italic;">
            <?= esc($amountInWords) ?>
        </td>
    </tr>
</table>

<br>

<?php if (!empty($incentive['remarks'])): ?>
<!-- ========================================================= -->
<!-- REMARKS -->
<!-- ========================================================= -->

<div>
    <b>Remarks:</b> <?= esc($incentive['remarks']) ?>
</div>

<br>
<?php endif; ?>

<br>
<br>
<br>

<!-- ========================================================= -->
<!-- SIGNATURE -->
<!-- ========================================================= -->

<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:50%; text-align:center; vertical-align:top;">
            <b><?= esc($_SESSION['name'] ?? '___________________') ?></b>
            <br>
            ( BPLC STAFF / CASHIER )
            <br>
            <span style="font-size:12px;">Date: <?= date('m/d/Y') ?></span>
        </td>
        <td style="width:50%; text-align:center; vertical-align:top;">
            <b><?= esc($borrowerName) ?></b>
            <br>
            ( BORROWER / RECIPIENT )
            <br>
            <span style="font-size:12px;">Date: _______________</span>
        </td>
    </tr>
</table>

<br>
<br>

<!-- ========================================================= -->
<!-- FOOTER -->
<!-- ========================================================= -->

<div style="text-align:center; font-size:10px; color:#666; margin-top:30px;">
    <i>This is a computer-generated voucher. No signature is required from the issuer.</i>
    <br>
    <i>Voucher No: <?= esc($voucherNumber) ?> | Generated: <?= date('Y-m-d H:i:s') ?></i>
</div>

</body>
</html>
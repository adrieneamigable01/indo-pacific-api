<?php

$borrowerName = trim(
    ($loan['first_name'] ?? '') . ' ' .
    ($loan['middle_name'] ?? '') . ' ' .
    ($loan['last_name'] ?? '')
);

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <title>
        <?= esc($title) ?>
    </title>

</head>


<body
    style="
        padding:35px;
        margin:0;
        font-size:16px;
        line-height:1.4;
    "
>


<!-- ========================================================= -->
<!-- HEADER -->
<!-- ========================================================= -->

<table
    style="
        width:100%;
        border-collapse:collapse;
    "
>

    <tr>

        <td
            style="
                text-align:center;
                font-weight:bold;
                font-size:20px;
            "
        >

            INDO-PACIFIC LENDING CORPORATION

            <br>

            <span
                style="
                    font-size:18px;
                "
            >

                MONTHLY PAYMENT ACKNOWLEDGEMENT

            </span>

        </td>

    </tr>

</table>


<br>


<!-- ========================================================= -->
<!-- DATE -->
<!-- ========================================================= -->

<div
    style="
        text-align:right;
    "
>

    <b>
        Date:
    </b>

    <?= date('F d, Y') ?>

</div>


<br>


<!-- ========================================================= -->
<!-- BORROWER INFORMATION -->
<!-- ========================================================= -->

<table
    style="
        width:100%;
        border-collapse:collapse;
        font-size:14px;
    "
>

    <tr>

        <td
            style="
                width:50%;
                padding:5px;
            "
        >

            <b>
                Borrower:
            </b>

            <?= esc($borrowerName) ?>

        </td>


        <td
            style="
                width:50%;
                padding:5px;
            "
        >

            <b>
                Borrower ID:
            </b>

            <?= esc(
                $payments[0]['borrower_id'] ?? ''
            ) ?>

        </td>

    </tr>


    <tr>

        <td
            style="
                padding:5px;
            "
        >

            <b>
                Payment Month:
            </b>

            <?= esc($paymentMonth) ?>

        </td>


        <td
            style="
                padding:5px;
            "
        >

            <b>
                Payment Date:
            </b>

            <?= !empty(
                $payments[0]['payment_date']
            )
                ? date(
                    'F d, Y',
                    strtotime(
                        $payments[0]['payment_date']
                    )
                )
                : ''
            ?>

        </td>

    </tr>

</table>


<br>


<!-- ========================================================= -->
<!-- ACKNOWLEDGEMENT -->
<!-- ========================================================= -->

<div
    style="
        text-align:justify;
    "
>

    This acknowledges that

    <b>
        <?= esc($borrowerName) ?>
    </b>

    made the monthly payment due for

    <b>
        <?= esc($paymentMonth) ?>
    </b>

    to

    <b>
        INDO-PACIFIC LENDING CORPORATION
    </b>

    for the following loan obligations.

</div>


<br>


<!-- ========================================================= -->
<!-- PAYMENT DETAILS -->
<!-- ========================================================= -->

<table
    style="
        width:100%;
        border-collapse:collapse;
        font-size:11px;
    "
>

    <thead>

        <tr>

            <th
                style="
                    border:1px solid #000;
                    padding:7px;
                    background-color:#b7b7b7;
                    text-align:left;
                "
            >

                Product

            </th>


            <th
                style="
                    border:1px solid #000;
                    padding:7px;
                    background-color:#b7b7b7;
                    text-align:center;
                "
            >

                Month

            </th>


            <th
                style="
                    border:1px solid #000;
                    padding:7px;
                    background-color:#b7b7b7;
                    text-align:right;
                "
            >

                Principal

            </th>


            <th
                style="
                    border:1px solid #000;
                    padding:7px;
                    background-color:#b7b7b7;
                    text-align:right;
                "
            >

                Interest

            </th>


            <th
                style="
                    border:1px solid #000;
                    padding:7px;
                    background-color:#b7b7b7;
                    text-align:right;
                "
            >

                Penalty

            </th>


            <th
                style="
                    border:1px solid #000;
                    padding:7px;
                    background-color:#b7b7b7;
                    text-align:right;
                "
            >

                Total

            </th>

        </tr>

    </thead>


    <tbody>


    <?php foreach ($payments as $payment): ?>

        <tr>

            <td
                style="
                    border:1px solid #000;
                    padding:7px;
                "
            >

                <?= esc(
                    $payment['product_name']
                    ?? 'Loan'
                ) ?>

                <br>

                <span
                    style="
                        font-size:9px;
                    "
                >

                    Loan ID:
                    <?= esc(
                        $payment['loan_id'] ?? ''
                    ) ?>

                </span>

            </td>


            <td
                style="
                    border:1px solid #000;
                    padding:7px;
                    text-align:center;
                "
            >

                <?= esc(
                    $payment['payment_month']
                    ?? ''
                ) ?>

            </td>


            <td
                style="
                    border:1px solid #000;
                    padding:7px;
                    text-align:right;
                "
            >

                PHP
                <?= number_format(
                    (float)(
                        $payment['principal_amount']
                        ?? 0
                    ),
                    2
                ) ?>

            </td>


            <td
                style="
                    border:1px solid #000;
                    padding:7px;
                    text-align:right;
                "
            >

                PHP
                <?= number_format(
                    (float)(
                        $payment['interest_amount']
                        ?? 0
                    ),
                    2
                ) ?>

            </td>


            <td
                style="
                    border:1px solid #000;
                    padding:7px;
                    text-align:right;
                "
            >

                PHP
                <?= number_format(
                    (float)(
                        $payment['penalty_amount']
                        ?? 0
                    ),
                    2
                ) ?>

            </td>


            <td
                style="
                    border:1px solid #000;
                    padding:7px;
                    text-align:right;
                "
            >

                PHP
                <?= number_format(
                    (float)(
                        $payment['total_amount']
                        ?? 0
                    ),
                    2
                ) ?>

            </td>

        </tr>

    <?php endforeach; ?>


    <!-- ===================================================== -->
    <!-- TOTAL -->
    <!-- ===================================================== -->

    <tr>

        <td
            colspan="2"
            style="
                border:1px solid #000;
                padding:8px;
                background-color:#b7b7b7;
                font-weight:bold;
            "
        >

            TOTAL PAYMENT

        </td>


        <td
            style="
                border:1px solid #000;
                padding:8px;
                background-color:#b7b7b7;
                font-weight:bold;
                text-align:right;
            "
        >

            PHP
            <?= number_format(
                $totalPrincipal,
                2
            ) ?>

        </td>


        <td
            style="
                border:1px solid #000;
                padding:8px;
                background-color:#b7b7b7;
                font-weight:bold;
                text-align:right;
            "
        >

            PHP
            <?= number_format(
                $totalInterest,
                2
            ) ?>

        </td>


        <td
            style="
                border:1px solid #000;
                padding:8px;
                background-color:#b7b7b7;
                font-weight:bold;
                text-align:right;
            "
        >

            PHP
            <?= number_format(
                $totalPenalty,
                2
            ) ?>

        </td>


        <td
            style="
                border:1px solid #000;
                padding:8px;
                background-color:#b7b7b7;
                font-weight:bold;
                text-align:right;
            "
        >

            PHP
            <?= number_format(
                $totalPayment,
                2
            ) ?>

        </td>

    </tr>


    </tbody>

</table>


<br>


<!-- ========================================================= -->
<!-- SUMMARY -->
<!-- ========================================================= -->

<div>

    <b>
        Total Monthly Payment:
    </b>

    PHP
    <?= number_format(
        $totalPayment,
        2
    ) ?>

</div>


<br>


<div>

    <b>
        Principal Paid:
    </b>

    PHP
    <?= number_format(
        $totalPrincipal,
        2
    ) ?>

</div>


<div>

    <b>
        Interest Paid:
    </b>

    PHP
    <?= number_format(
        $totalInterest,
        2
    ) ?>

</div>


<?php if ($totalPenalty > 0): ?>

    <div>

        <b>
            Penalty Paid:
        </b>

        PHP
        <?= number_format(
            $totalPenalty,
            2
        ) ?>

    </div>

<?php endif; ?>


<br>


<!-- ========================================================= -->
<!-- REMARKS -->
<!-- ========================================================= -->

<?php

$remarks = [];

foreach ($payments as $payment) {

    if (
        !empty($payment['remarks']) &&
        !in_array(
            $payment['remarks'],
            $remarks
        )
    ) {

        $remarks[] =
            $payment['remarks'];

    }

}

?>


<?php if (!empty($remarks)): ?>

    <div>

        <b>
            Remarks:
        </b>

        <?= esc(
            implode(
                ', ',
                $remarks
            )
        ) ?>

    </div>

<?php endif; ?>


<br>
<br>
<br>


<!-- ========================================================= -->
<!-- SIGNATURE -->
<!-- ========================================================= -->

<div>

    <b>
        Process by:
    </b>

</div>


<br>
<br>


<table
    style="
        width:100%;
        border-collapse:collapse;
    "
>

    <tr>

        <td
            style="
                width:50%;
                text-align:center;
                vertical-align:top;
            "
        >

            <b>

                <?= esc(
                    $_SESSION['name'] ?? ''
                ) ?>

            </b>

            <br>

            ( BPLC STAFF )

        </td>


        <td
            style="
                width:50%;
                text-align:center;
                vertical-align:top;
            "
        >

            <b>

                <?= esc(
                    $borrowerName
                ) ?>

            </b>

            <br>

            ( BORROWER )

        </td>

    </tr>

</table>


</body>

</html>
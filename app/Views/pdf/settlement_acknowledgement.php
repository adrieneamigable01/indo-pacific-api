<?php

/*
|--------------------------------------------------------------------------
| Settlement Data
|--------------------------------------------------------------------------
*/

$settlementId =
    $settlement->settlement_id ?? '';

$borrowerId =
    $settlement->borrower_id ?? '';


/*
|--------------------------------------------------------------------------
| Settlement Month
|--------------------------------------------------------------------------
*/

$settlementMonth = '';

if (!empty($settlement->settlement_month)) {

    $settlementMonth = date(
        'F Y',
        strtotime($settlement->settlement_month)
    );

}


/*
|--------------------------------------------------------------------------
| Borrower Name
|--------------------------------------------------------------------------
*/

$borrowerName = trim(
    ($loan['first_name'] ?? '') . ' ' .
    ($loan['middle_name'] ?? '') . ' ' .
    ($loan['last_name'] ?? '')
);


/*
|--------------------------------------------------------------------------
| Settlement Details
|--------------------------------------------------------------------------
*/

$details = $details ?? [];


/*
|--------------------------------------------------------------------------
| Totals
|--------------------------------------------------------------------------
*/

$totalAmount = 0;

$totalDue = 0;

$totalPaid = 0;

$totalUnpaid = 0;


foreach ($details as $item) {

    if (is_object($item)) {

        $item = (array) $item;

    }


    $totalAmount += (float) (
        $item['amount'] ?? 0
    );


    $totalDue += (float) (
        $item['due_amount'] ?? 0
    );


    $totalPaid += (float) (
        $item['paid_amount'] ?? 0
    );


    $totalUnpaid += (float) (
        $item['unpaid_amount'] ?? 0
    );

}


/*
|--------------------------------------------------------------------------
| Settlement Deficit
|--------------------------------------------------------------------------
*/

$deficitAmount =
    (float) (
        $settlement->deficit_amount ?? 0
    );


/*
|--------------------------------------------------------------------------
| Settlement Status
|--------------------------------------------------------------------------
*/

$status =
    $settlement->status ?? '';


/*
|--------------------------------------------------------------------------
| Settlement Date
|--------------------------------------------------------------------------
*/

$displaySettlementDate = '';

if (!empty($settlement->settled_at)) {

    $displaySettlementDate = date(
        'F d, Y',
        strtotime(
            $settlement->settled_at
        )
    );

} elseif (!empty($settlement->created_at)) {

    $displaySettlementDate = date(
        'F d, Y',
        strtotime(
            $settlement->created_at
        )
    );

} else {

    $displaySettlementDate =
        date('F d, Y');

}


/*
|--------------------------------------------------------------------------
| Loan IDs
|--------------------------------------------------------------------------
*/

$loanIds = [];


foreach ($details as $item) {

    if (is_object($item)) {

        $item = (array) $item;

    }


    if (!empty($item['loan_id'])) {

        $loanIds[] =
            $item['loan_id'];

    }

}


$loanIds = array_values(
    array_unique($loanIds)
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        <?= esc(
            $title ??
            'Settlement Acknowledgement'
        ) ?>
    </title>

</head>


<body
    style="
        padding:35px;
        margin:0;
        font-size:16px;
        letter-spacing:.3px;
        text-align:justify;
        line-height:1.3;
        padding-bottom:30px;
    "
>


<!-- ========================================================= -->
<!-- HEADER -->
<!-- ========================================================= -->

<div
    style="
        text-align:center;
    "
>

    <div
        style="
            font-weight:bold;
            font-size:20px;
        "
    >

        INDO-PACIFIC LENDING CORPORATION

    </div>


    <div
        style="
            font-size:13px;
        "
    >

        SETTLEMENT ACKNOWLEDGEMENT

    </div>

</div>


<br>


<!-- ========================================================= -->
<!-- SETTLEMENT NUMBER -->
<!-- ========================================================= -->

<div
    style="
        text-align:right;
        font-size:13px;
    "
>

    <b>
        Settlement No.:
    </b>

    <?= esc($settlementId) ?>

</div>


<div
    style="
        text-align:right;
        font-size:13px;
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
        font-size:13px;
    "
>

    <tr>

        <td
            style="
                padding:5px;
                width:50%;
            "
        >

            <b>
                Borrower:
            </b>

            <?= esc($borrowerName) ?>

        </td>


        <td
            style="
                padding:5px;
                width:50%;
            "
        >

            <b>
                Borrower ID:
            </b>

            <?= esc($borrowerId) ?>

        </td>

    </tr>


    <tr>

        <td
            style="
                padding:5px;
            "
        >

            <b>
                Settlement Month:
            </b>

            <?= esc($settlementMonth) ?>

        </td>


        <td
            style="
                padding:5px;
            "
        >

            <b>
                Settlement Date:
            </b>

            <?= esc($displaySettlementDate) ?>

        </td>

    </tr>


    <tr>

        <td
            style="
                padding:5px;
            "
        >

            <b>
                Status:
            </b>

            <?= esc($status) ?>

        </td>


        <td
            style="
                padding:5px;
            "
        >

            <b>
                Loan IDs:
            </b>

            <?= esc(
                implode(
                    ', ',
                    $loanIds
                )
            ) ?>

        </td>

    </tr>

</table>


<br>


<!-- ========================================================= -->
<!-- ACKNOWLEDGEMENT -->
<!-- ========================================================= -->

<div
    style="
        font-size:15px;
        line-height:1.5;
    "
>

    This acknowledges the settlement of the loan obligations
    of

    <b>
        <?= esc($borrowerName) ?>
    </b>

    with

    <b>
        INDO-PACIFIC LENDING CORPORATION
    </b>

    for the settlement period of

    <b>
        <?= esc($settlementMonth) ?>
    </b>.

</div>


<br>


<!-- ========================================================= -->
<!-- SETTLEMENT DETAILS -->
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
                    padding:8px;
                    background-color:#b7b7b7;
                    text-align:left;
                "
            >
                Particulars
            </th>


            <th
                style="
                    border:1px solid #000;
                    padding:8px;
                    background-color:#b7b7b7;
                    text-align:center;
                "
            >
                Type
            </th>


            <th
                style="
                    border:1px solid #000;
                    padding:8px;
                    background-color:#b7b7b7;
                    text-align:right;
                "
            >
                Due Amount
            </th>


            <th
                style="
                    border:1px solid #000;
                    padding:8px;
                    background-color:#b7b7b7;
                    text-align:right;
                "
            >
                Paid Amount
            </th>


            <th
                style="
                    border:1px solid #000;
                    padding:8px;
                    background-color:#b7b7b7;
                    text-align:right;
                "
            >
                Unpaid Amount
            </th>

        </tr>

    </thead>


    <tbody>


    <?php if (!empty($details)): ?>


        <?php foreach ($details as $item): ?>

            <?php

            if (is_object($item)) {

                $item = (array) $item;

            }


            $itemLoanId =
                $item['loan_id'] ?? '';


            $productName =
                $item['product_name']
                ?? 'Loan Settlement';


            $itemDue =
                (float) (
                    $item['due_amount'] ?? 0
                );


            $itemPaid =
                (float) (
                    $item['paid_amount'] ?? 0
                );


            $itemUnpaid =
                (float) (
                    $item['unpaid_amount'] ?? 0
                );

            ?>


            <tr>


                <!-- PARTICULARS -->

                <td
                    style="
                        border:1px solid #000;
                        padding:8px;
                    "
                >

                    <b>
                        <?= esc(
                            $productName
                        ) ?>
                    </b>

                    <br>

                    <span
                        style="
                            font-size:9px;
                        "
                    >

                        Loan ID:
                        <?= esc(
                            $itemLoanId
                        ) ?>

                    </span>

                </td>


                <!-- TYPE -->

                <td
                    style="
                        border:1px solid #000;
                        padding:8px;
                        text-align:center;
                    "
                >

                    SETTLEMENT

                </td>


                <!-- DUE -->

                <td
                    style="
                        border:1px solid #000;
                        padding:8px;
                        text-align:right;
                    "
                >

                    PHP
                    <?= number_format(
                        $itemDue,
                        2
                    ) ?>

                </td>


                <!-- PAID -->

                <td
                    style="
                        border:1px solid #000;
                        padding:8px;
                        text-align:right;
                    "
                >

                    PHP
                    <?= number_format(
                        $itemPaid,
                        2
                    ) ?>

                </td>


                <!-- UNPAID -->

                <td
                    style="
                        border:1px solid #000;
                        padding:8px;
                        text-align:right;
                    "
                >

                    PHP
                    <?= number_format(
                        $itemUnpaid,
                        2
                    ) ?>

                </td>


            </tr>


        <?php endforeach; ?>


    <?php else: ?>


        <tr>

            <td
                colspan="5"
                style="
                    border:1px solid #000;
                    padding:10px;
                    text-align:center;
                "
            >

                No settlement details found.

            </td>

        </tr>


    <?php endif; ?>


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

            TOTAL

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
                $totalDue,
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
                $totalPaid,
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
                $totalUnpaid,
                2
            ) ?>

        </td>

    </tr>


    </tbody>

</table>


<br>


<!-- ========================================================= -->
<!-- SETTLEMENT SUMMARY -->
<!-- ========================================================= -->

<table
    style="
        width:100%;
        border-collapse:collapse;
        font-size:13px;
    "
>

    <tr>

        <td
            style="
                padding:6px;
                width:50%;
            "
        >

            <b>
                Total Due:
            </b>

            PHP
            <?= number_format(
                $totalDue,
                2
            ) ?>

        </td>


        <td
            style="
                padding:6px;
                width:50%;
            "
        >

            <b>
                Total Paid:
            </b>

            PHP
            <?= number_format(
                $totalPaid,
                2
            ) ?>

        </td>

    </tr>


    <tr>

        <td
            style="
                padding:6px;
            "
        >

            <b>
                Total Unpaid:
            </b>

            PHP
            <?= number_format(
                $totalUnpaid,
                2
            ) ?>

        </td>


        <td
            style="
                padding:6px;
            "
        >

            <b>
                Settlement Amount:
            </b>

            PHP
            <?= number_format(
                $totalAmount,
                2
            ) ?>

        </td>

    </tr>


    <tr>

        <td
            colspan="2"
            style="
                padding:6px;
            "
        >

            <b>
                Deficit Amount:
            </b>

            PHP
            <?= number_format(
                $deficitAmount,
                2
            ) ?>

        </td>

    </tr>

</table>


<?php if (!empty($settlement->remarks)): ?>

    <br>

    <div
        style="
            font-size:13px;
        "
    >

        <b>
            Remarks:
        </b>

        <?= esc(
            $settlement->remarks
        ) ?>

    </div>

<?php endif; ?>


<br>


<!-- ========================================================= -->
<!-- ACKNOWLEDGEMENT TEXT -->
<!-- ========================================================= -->

<div
    style="
        font-size:14px;
        line-height:1.5;
    "
>

    The above details represent the recorded settlement
    applicable to the identified loan obligations and
    settlement period.

    The borrower acknowledges the settlement details,
    amounts paid, and remaining unpaid amounts stated
    herein.

</div>


<br>
<br>


<!-- ========================================================= -->
<!-- PROCESSED BY -->
<!-- ========================================================= -->

<div>

    <b>
        Processed by:
    </b>

</div>


<br>
<br>
<br>


<!-- ========================================================= -->
<!-- SIGNATURES -->
<!-- ========================================================= -->

<table
    style="
        width:100%;
        border-collapse:collapse;
    "
>

    <tr>


        <!-- STAFF -->

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
            <br>

            __________________________

            <br>

            ( BPLC STAFF )

        </td>


        <!-- BORROWER -->

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
            <br>

            __________________________

            <br>

            ( BORROWER )

        </td>


    </tr>

</table>


</body>

</html>
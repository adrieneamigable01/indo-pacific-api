<?php

/*
|--------------------------------------------------------------------------
| Settlement Data
|--------------------------------------------------------------------------
*/

$settlementId = $settlement->settlement_id ?? '';
$borrowerId   = $settlement->borrower_id ?? '';

$loanId = $detail->loan_id
    ?? $settlement->settlement_loan_id
    ?? '';

$productName = $detail->product_name
    ?? 'Loan Settlement';

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

$borrowerName =
    trim(
        ($loan['first_name'] ?? '') . ' ' .
        ($loan['middle_name'] ?? '') . ' ' .
        ($loan['last_name'] ?? '')
    );


/*
|--------------------------------------------------------------------------
| Settlement Amounts
|--------------------------------------------------------------------------
*/

$amount = (float) (
    $detail->amount ?? 0
);

$dueAmount = (float) (
    $detail->due_amount ?? 0
);

$paidAmount = (float) (
    $detail->paid_amount ?? 0
);

$unpaidAmount = (float) (
    $detail->unpaid_amount ?? 0
);

$deficitAmount = (float) (
    $settlement->deficit_amount ?? 0
);


/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

$status = $settlement->status ?? '';


/*
|--------------------------------------------------------------------------
| Settlement Date
|--------------------------------------------------------------------------
*/

$settlementDate = '';

if (!empty($settlement->settled_at)) {

    $settlementDate = date(
        'F d, Y',
        strtotime($settlement->settled_at)
    );

} elseif (!empty($settlement->created_at)) {

    $settlementDate = date(
        'F d, Y',
        strtotime($settlement->created_at)
    );

} else {

    $settlementDate = date('F d, Y');

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        <?= esc($title ?? 'Settlement Acknowledgement') ?>
    </title>


    <style>

        body {

            padding: 0;

            margin: 0;

            font-size: 17px;

            font-family: Arial;
            
            letter-spacing: .3px;

            text-align: justify;

            line-height: 1.3;

            padding-bottom: 30px;

        }


        .text-center {

            text-align: center;

        }


        .text-right {

            text-align: right;

        }


        .text-left {

            text-align: left;

        }


        table {

            border-collapse: collapse;

        }


        table.bordered {

            width: 100%;

            border-collapse: collapse;

        }


        table.bordered th,

        table.bordered td {

            border: 1px solid #000;

            padding: 8px;

        }


        .table-header {

            background-color: #b7b7b7;

            font-weight: bold;

        }


        .amount {

            text-align: right;

        }


        .total {

            background-color: #b7b7b7;

            font-weight: bold;

        }


        .small {

            font-size: 12px;

        }

    </style>

</head>


<body
    style="
        padding-left:35px;
        padding-right:35px;
    "
>


    <!-- ========================================================= -->
    <!-- LOGO -->
    <!-- ========================================================= -->

    <div
        class="text-center"
        style="
            position:absolute;
            top:0;
            left:0;
            width:100%;
        "
    >

        <?php

        $logoPath =
            FCPATH . 'assets/img/Logo.png';

        if (file_exists($logoPath)):

        ?>

            <img
                src="<?= $logoPath ?>"
                style="
                    width:100%;
                    height:auto;
                    opacity:.12;
                "
            >

        <?php endif; ?>

    </div>


    <br>
    <br>
    <br>
    <br>
    <br>


    <!-- ========================================================= -->
    <!-- SETTLEMENT NUMBER -->
    <!-- ========================================================= -->

    <div
        style="
            text-align:right;
        "
    >

        <b style="margin-right:38px;">

            SETTLEMENT #

            <?= esc($settlementId) ?>

        </b>

    </div>


    <br>
    <br>


    <!-- ========================================================= -->
    <!-- TITLE -->
    <!-- ========================================================= -->

    <div
        class="text-center"
        style="
            font-weight:bold;
            font-size:20px;
        "
    >

        SETTLEMENT ACKNOWLEDGEMENT

    </div>


    <div
        class="text-right"
        style="font-weight:bold;"
    >

        <?= date('F d, Y') ?>

    </div>


    <br>
    <br>


    <!-- ========================================================= -->
    <!-- ACKNOWLEDGEMENT -->
    <!-- ========================================================= -->

    <div>

        This acknowledges the settlement of the loan obligation
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

    <table class="bordered">

        <thead>

            <tr class="table-header">

                <th>
                    Particulars
                </th>

                <th>
                    Type
                </th>

                <th>
                    Due Amount
                </th>

                <th>
                    Paid Amount
                </th>

                <th>
                    Unpaid Amount
                </th>

            </tr>

        </thead>


        <tbody>

            <tr>

                <td>

                    <?= esc($productName) ?>

                    <br>

                    <span class="small">

                        Loan ID:
                        <?= esc($loanId) ?>

                    </span>

                </td>


                <td>

                    SETTLEMENT

                </td>


                <td class="amount">

                    PHP
                    <?= number_format(
                        $dueAmount,
                        2
                    ) ?>

                </td>


                <td class="amount">

                    PHP
                    <?= number_format(
                        $paidAmount,
                        2
                    ) ?>

                </td>


                <td class="amount">

                    PHP
                    <?= number_format(
                        $unpaidAmount,
                        2
                    ) ?>

                </td>

            </tr>


            <!-- TOTAL -->

            <tr class="total">

                <td colspan="2">

                    TOTAL

                </td>


                <td class="amount">

                    PHP
                    <?= number_format(
                        $dueAmount,
                        2
                    ) ?>

                </td>


                <td class="amount">

                    PHP
                    <?= number_format(
                        $paidAmount,
                        2
                    ) ?>

                </td>


                <td class="amount">

                    PHP
                    <?= number_format(
                        $unpaidAmount,
                        2
                    ) ?>

                </td>

            </tr>

        </tbody>

    </table>


    <br>


    <!-- ========================================================= -->
    <!-- SETTLEMENT INFORMATION -->
    <!-- ========================================================= -->

    <table
        style="
            width:100%;
            font-size:13px;
        "
    >

        <tr>

            <td>

                <b>
                    Settlement ID:
                </b>

                <?= esc($settlementId) ?>

            </td>


            <td>

                <b>
                    Borrower ID:
                </b>

                <?= esc($borrowerId) ?>

            </td>

        </tr>


        <tr>

            <td>

                <b>
                    Loan ID:
                </b>

                <?= esc($loanId) ?>

            </td>


            <td>

                <b>
                    Settlement Month:
                </b>

                <?= esc($settlementMonth) ?>

            </td>

        </tr>


        <tr>

            <td>

                <b>
                    Settlement Date:
                </b>

                <?= esc($settlementDate) ?>

            </td>


            <td>

                <b>
                    Status:
                </b>

                <?= esc($status) ?>

            </td>

        </tr>


        <tr>

            <td>

                <b>
                    Deficit Amount:
                </b>

                PHP
                <?= number_format(
                    $deficitAmount,
                    2
                ) ?>

            </td>


            <td>

                <b>
                    Settlement Amount:
                </b>

                PHP
                <?= number_format(
                    $amount,
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
    <br>


    <!-- ========================================================= -->
    <!-- ACKNOWLEDGEMENT TEXT -->
    <!-- ========================================================= -->

    <div>

        The above amount represents the recorded settlement
        applicable to the identified loan and settlement period.

        The borrower acknowledges the settlement details stated
        herein.

    </div>


    <br>
    <br>
    <br>


    <!-- ========================================================= -->
    <!-- PROCESSED BY -->
    <!-- ========================================================= -->

    <div class="text-left">

        <b>
            Process by:
        </b>

    </div>


    <br>
    <br>


    <!-- ========================================================= -->
    <!-- SIGNATURES -->
    <!-- ========================================================= -->

    <table style="width:100%;">

        <tr class="text-center">


            <!-- STAFF -->

            <td style="width:50%;">

                <b>

                    <?= esc(
                        $_SESSION['name'] ?? ''
                    ) ?>

                </b>

                <br>

                ( BPLC STAFF )

            </td>


            <!-- BORROWER -->

            <td style="width:50%;">

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
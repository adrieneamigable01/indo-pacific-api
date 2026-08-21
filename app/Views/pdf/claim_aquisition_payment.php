<?php

$payment = $payment;
$loan    = $loan;

$borrowerName =
    $loan['first_name'] . ' ' .
    $loan['middle_name'] . ' ' .
    $loan['last_name'];

$paymentDate = !empty($payment->payment_date)
    ? date('F d, Y', strtotime($payment->payment_date))
    : '';

$paymentMonth = !empty($payment->payment_month)
    ? $payment->payment_month
    : '';

$principal = (float) ($payment->principal_amount ?? 0);
$interest  = (float) ($payment->interest_amount ?? 0);
$penalty   = (float) ($payment->penalty_amount ?? 0);
$total     = (float) ($payment->total_amount ?? 0);

$paymentType = strtoupper(
    $payment->payment_type ?? 'PAYMENT'
);
?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= esc($title ?? 'Payment Statement') ?>
    </title>

    <style>

        #tbl-essential tbody tr {
            line-height: 30px;
        }

        #li-essential li {
            margin-bottom: 10px;
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

        body {
            padding: 0;
            margin: 0;
            font-size: 18px;
            letter-spacing: .5px;
            text-align: justify;
            text-justify: inter-word;
            line-height: 1.2;
            padding-bottom: 30px;
        }

        #content {
            margin-left: 90px;
            margin-right: 50px;
        }

        table.bordered,
        table.bordered > thead > tr > th,
        table.bordered > tbody > tr > td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 10px;
        }

    </style>

</head>


<body style="
    padding-left:35px;
    padding-right:35px;
    font-size:17px;
">


    <!-- HEADER -->

    <div class="text-center">

        <?php

            $path = APPPATH . 'Views/pdf/logo.png';

            if (file_exists($path)) {

                $type = pathinfo($path, PATHINFO_EXTENSION);

                $imagedata = file_get_contents($path);

                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($imagedata);

                echo '<img src="' . $base64 . '" style="
                width:100%;
                height:80%;
                position:absolute;
                opacity:.2;
            ">';

            } else {

                echo '<div style="color:red;">Logo not found: ' . $path . '</div>';

            }
            ?>
    </div>


    <br>
    <br>
    <br>
    <br>
    <br>
    <br>


    <!-- DOCUMENT NUMBER / TYPE -->

    <div style="text-align:right">

        <b style="margin-right:38px;">

            PAYMENT #<?= esc($payment->payment_id) ?>

        </b>

    </div>


    <br>
    <br>


    <!-- TITLE -->

    <div
        class="text-center"
        style="font-weight:bold;"
    >

        PAYMENT ACKNOWLEDGEMENT

    </div>


    <div
        class="text-right"
        style="font-weight:bold;"
    >

        <?= date("F d, Y") ?>

    </div>


    <br>
    <br>


    <!-- ACKNOWLEDGEMENT -->

    <div>

        <div>

            Received from
            <b>INDO-PACIFIC LENDING CORPORATION</b>
            the amount of

            <b>
                PHP <?= number_format($total, 2) ?>
            </b>

            as payment for

            <b>
                <?= esc($paymentType) ?>
            </b>

            of the loan of

            <b>
                <?= esc($borrowerName) ?>
            </b>.

        </div>


        <br>


        <!-- PAYMENT DETAILS -->

        <div>

            <table
                style="
                    width:100%;
                    font-size:12px;
                "
                id="tbl-essential"
                class="table bordered"
            >

                <thead class="text-left">

                    <tr
                        style="
                            background-color:#b7b7b7;
                        "
                    >

                        <th>
                            Particulars
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Amount
                        </th>

                    </tr>

                </thead>


                <tbody class="text-left">


                    <!-- PRINCIPAL -->

                    <?php if ($principal > 0): ?>

                        <tr>

                            <td>
                                Loan Principal
                            </td>

                            <td>
                                PRINCIPAL
                            </td>

                            <td>
                                <?= esc($paymentDate) ?>
                            </td>

                            <td>
                                PHP <?= number_format(
                                    $principal,
                                    2
                                ) ?>
                            </td>

                        </tr>

                    <?php endif; ?>


                    <!-- INTEREST -->

                    <?php if ($interest > 0): ?>

                        <tr>

                            <td>
                                Loan Interest
                            </td>

                            <td>
                                INTEREST
                            </td>

                            <td>
                                <?= esc($paymentDate) ?>
                            </td>

                            <td>
                                PHP <?= number_format(
                                    $interest,
                                    2
                                ) ?>
                            </td>

                        </tr>

                    <?php endif; ?>


                    <!-- PENALTY -->

                    <?php if ($penalty > 0): ?>

                        <tr>

                            <td>
                                Penalty
                            </td>

                            <td>
                                PENALTY
                            </td>

                            <td>
                                <?= esc($paymentDate) ?>
                            </td>

                            <td>
                                PHP <?= number_format(
                                    $penalty,
                                    2
                                ) ?>
                            </td>

                        </tr>

                    <?php endif; ?>


                    <!-- TOTAL -->

                    <tr>

                        <td>
                        </td>

                        <td>
                        </td>

                        <td>

                            <b>
                                TOTAL PAYMENT
                            </b>

                        </td>

                        <td
                            style="
                                background-color:#b7b7b7;
                            "
                        >

                            <b>
                                PHP <?= number_format(
                                    $total,
                                    2
                                ) ?>
                            </b>

                        </td>

                    </tr>


                </tbody>

            </table>

        </div>


        <br>
        <br>


        <!-- PAYMENT INFORMATION -->

        <table
            style="
                width:100%;
                font-size:13px;
            "
        >

            <tr>

                <td>

                    <b>
                        Borrower:
                    </b>

                    <?= esc($borrowerName) ?>

                </td>

                <td>

                    <b>
                        Loan ID:
                    </b>

                    <?= esc($payment->loan_id) ?>

                </td>

            </tr>


            <tr>

                <td>

                    <b>
                        Payment ID:
                    </b>

                    <?= esc($payment->payment_id) ?>

                </td>

                <td>

                    <b>
                        Schedule ID:
                    </b>

                    <?= esc($payment->schedule_id) ?>

                </td>

            </tr>


            <tr>

                <td>

                    <b>
                        Payment Date:
                    </b>

                    <?= esc($paymentDate) ?>

                </td>

                <td>

                    <b>
                        Payment Month:
                    </b>

                    <?= esc($paymentMonth) ?>

                </td>

            </tr>


            <tr>

                <td>

                    <b>
                        OR Number:
                    </b>

                    <?= esc(
                        $payment->or_number ?? ''
                    ) ?>

                </td>

                <td>

                    <b>
                        Payment Source:
                    </b>

                    <?= esc(
                        $payment->payment_source ?? ''
                    ) ?>

                </td>

            </tr>


            <tr>

                <td>

                    <b>
                        Payment Type:
                    </b>

                    <?= esc($paymentType) ?>

                </td>

                <td>

                    <b>
                        Status:
                    </b>

                    <?= esc(
                        $payment->status ?? ''
                    ) ?>

                </td>

            </tr>

        </table>


        <?php if (!empty($payment->remarks)): ?>

            <br>

            <div style="font-size:13px;">

                <b>
                    Remarks:
                </b>

                <?= esc($payment->remarks) ?>

            </div>

        <?php endif; ?>


        <br>
        <br>
        <br>


        <!-- PROCESSED BY -->

        <div class="text-left">

            <b>
                Process by:
            </b>

            <br>

        </div>


        <br>
        <br>


        <!-- SIGNATURES -->

        <table style="width:100%;">

            <tr class="text-center">


                <!-- STAFF -->

                <td>

                    <b>

                        <?= esc(
                            $_SESSION['name'] ?? ''
                        ) ?>

                    </b>

                    <br>

                    ( BPLC STAFF )

                </td>


                <!-- BORROWER -->

                <td>

                    <b>

                        <?= esc($borrowerName) ?>

                    </b>

                    <br>

                    (Borrower)

                </td>


            </tr>

        </table>


    </div>


</body>

</html>
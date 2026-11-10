


<?php
//   print_r($comaker);exit;
function addOrdinalNumberSuffix($num)
{
    if (!in_array(($num % 100), array(11, 12, 13))) {
        switch ($num % 10) {
                // Handle 1st, 2nd, 3rd
            case 1:
                return $num . 'st';
            case 2:
                return $num . 'nd';
            case 3:
                return $num . 'rd';
        }
    }
    return $num . 'th';
}

function toText($amt)
{
    if (!is_numeric($amt)) {
        throw new Exception('Only numeric values are allowed.');
    }

    // PHP 8.1+ fix
    $amt = (int) round((float)$amt);

    $sign = $amt >= 0 ? '' : 'Negative ';

    return $sign . toQuadrillions(abs($amt));
}

function toOnes($amt)
{
    $words = array(
        0 => 'Zero',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine'
    );

    if ($amt >= 0 && $amt < 10)
        return $words[$amt];
    else
        throw new ArrayIndexOutOfBoundsException('Array Index not defined');
}

function toTens($amt)
{ // handles 10 - 99
    $firstDigit = intval($amt / 10);
    $remainder = $amt % 10;

    if ($firstDigit == 1) {
        $words = array(
            0 => 'Ten',
            1 => 'Eleven',
            2 => 'Twelve',
            3 => 'Thirteen',
            4 => 'Fourteen',
            5 => 'Fifteen',
            6 => 'Sixteen',
            7 => 'Seventeen',
            8 => 'Eighteen',
            9 => 'Nineteen'
        );

        return $words[$remainder];
    } else if ($firstDigit >= 2 && $firstDigit <= 9) {
        $words = array(
            2 => 'Twenty',
            3 => 'Thirty',
            4 => 'Fourty',
            5 => 'Fifty',
            6 => 'Sixty',
            7 => 'Seventy',
            8 => 'Eighty',
            9 => 'Ninety'
        );

        $rest = $remainder == 0 ? '' : toOnes($remainder);
        return $words[$firstDigit] . ' ' . $rest;
    } else
        return toOnes($amt);
}

function toHundreds($amt)
{
    $ones = intval($amt / 100);
    $remainder = $amt % 100;

    if ($ones >= 1 && $ones < 10) {
        $rest = $remainder == 0 ? '' : toTens($remainder);
        return toOnes($ones) . ' Hundred ' . $rest;
    } else
        return toTens($amt);
}

function toThousands($amt)
{
    $hundreds = intval($amt / 1000);
    $remainder = $amt % 1000;

    if ($hundreds >= 1 && $hundreds < 1000) {
        $rest = $remainder == 0 ? '' : toHundreds($remainder);
        return toHundreds($hundreds) . ' Thousand ' . $rest;
    } else
        return toHundreds($amt);
}

function toMillions($amt)
{
    $amt = (int) round($amt);

    $hundreds = intval($amt / pow(1000, 2));
    $remainder = $amt % pow(1000, 2);

    if ($hundreds >= 1 && $hundreds < 1000) {
        $rest = $remainder == 0 ? '' : toThousands($remainder);
        return toHundreds($hundreds) . ' Million ' . $rest;
    } else
        return toThousands($amt);
}

function toBillions($amt)
{
    $amt = (int) round($amt);
    $hundreds = intval($amt / pow(1000, 3));
    /* Note:taking the modulos results in a negative value, but
      this seems to work pretty fine */

    $remainder = $amt - $hundreds * pow(1000, 3);

    if ($hundreds >= 1 && $hundreds < 1000) {
        $rest = $remainder == 0 ? '' : toMillions($remainder);
        return toHundreds($hundreds) . ' Billion ' . $rest;
    } else
        return toMillions($amt);
}

function toTrillions($amt)
{
    $amt = (int) round($amt);
    $hundreds = intval($amt / pow(1000, 4));
    $remainder = $amt - $hundreds * pow(1000, 4);

    if ($hundreds >= 1 && $hundreds < 1000) {
        $rest = $remainder == 0 ? '' : toBillions($remainder);
        return toHundreds($hundreds) . ' Trillion ' . $rest;
    } else
        return toBillions($amt);
}

function toQuadrillions($amt)
{
    $hundreds = intval($amt / pow(1000, 5));
    $remainder = $amt - $hundreds * pow(1000, 5);

    if ($hundreds >= 1 && $hundreds < 1000) {
        $rest = $remainder == 0 ? '' : toTrillions($remainder);
        return toHundreds($hundreds) . ' Quadrillion ' . $rest;
    } else
        return toTrillions($amt);
}

function numberTowords($num)
{

    $ones = array(
        0 => "ZERO",
        1 => "ONE",
        2 => "TWO",
        3 => "THREE",
        4 => "FOUR",
        5 => "FIVE",
        6 => "SIX",
        7 => "SEVEN",
        8 => "EIGHT",
        9 => "NINE",
        10 => "TEN",
        11 => "ELEVEN",
        12 => "TWELVE",
        13 => "THIRTEEN",
        14 => "FOURTEEN",
        15 => "FIFTEEN",
        16 => "SIXTEEN",
        17 => "SEVENTEEN",
        18 => "EIGHTEEN",
        19 => "NINETEEN",
        "014" => "FOURTEEN"
    );
    $tens = array(
        0 => "ZERO",
        1 => "TEN",
        2 => "TWENTY",
        3 => "THIRTY",
        4 => "FORTY",
        5 => "FIFTY",
        6 => "SIXTY",
        7 => "SEVENTY",
        8 => "EIGHTY",
        9 => "NINETY"
    );
    $hundreds = array(
        "HUNDRED",
        "THOUSAND",
        "MILLION",
        "BILLION",
        "TRILLION",
        "QUARDRILLION"
    ); /*limit t quadrillion */
    $num = number_format($num, 2, ".", ",");
    $num_arr = explode(".", $num);
    $wholenum = $num_arr[0];
    $decnum = $num_arr[1];
    $whole_arr = array_reverse(explode(",", $wholenum));
    krsort($whole_arr, 1);
    $rettxt = "";
    foreach ($whole_arr as $key => $i) {

        while (substr($i, 0, 1) == "0")
            $i = substr($i, 1, 5);
        if ($i < 20) {
            /* echo "getting:".$i; */
            print_r($i);
            exit;
            // print_r($ones);exit;
            $rettxt .= $ones[$i];
        } elseif ($i < 100) {
            if (substr($i, 0, 1) != "0")  $rettxt .= $tens[substr($i, 0, 1)];
            if (substr($i, 1, 1) != "0") $rettxt .= " " . $ones[substr($i, 1, 1)];
        } else {
            if (substr($i, 0, 1) != "0") $rettxt .= $ones[substr($i, 0, 1)] . " " . $hundreds[0];
            if (substr($i, 1, 1) != "0") $rettxt .= " " . $tens[substr($i, 1, 1)];
            if (substr($i, 2, 1) != "0") $rettxt .= " " . $ones[substr($i, 2, 1)];
        }
        if ($key > 0) {
            $rettxt .= " " . $hundreds[$key] . " ";
        }
    }
    if ($decnum > 0) {
        $rettxt .= " and ";
        if ($decnum < 20) {
            $rettxt .= $ones[$decnum];
        } elseif ($decnum < 100) {
            $rettxt .= $tens[substr($decnum, 0, 1)];
            $rettxt .= " " . $ones[substr($decnum, 1, 1)];
        }
    }
    return $rettxt;
}


function percentageToWords($number)
{
    $ones = array(
        0 => "ZERO",
        1 => "ONE",
        2 => "TWO",
        3 => "THREE",
        4 => "FOUR",
        5 => "FIVE",
        6 => "SIX",
        7 => "SEVEN",
        8 => "EIGHT",
        9 => "NINE"
    );
       
    // Convert number to string
    $number = trim((string)$number);
    
    // If decimal exists
    if (strpos($number, '.') !== false) {

        $parts = explode('.', $number);

        $wholePart = intval($parts[0]);
        $decimalPart = $parts[1];
          
        // Whole number wording
        $result = trim(toText($wholePart));

        // Add POINT
        $result .= " POINT";
         
        // Decimal digits one by one
        for ($i = 0; $i < strlen($decimalPart); $i++) {

            $digit = $decimalPart[$i];

            if (is_numeric($digit)) {
                $result .= " " . $ones[intval($digit)];
            }
        }
        
        return strtoupper(trim($result));
    } else {

        return strtoupper(trim(toText(intval($number))));
    }
}

?>
<?php

// =====================================
// SAFE DISPLAY VALUES
// =====================================

$borrowerName = trim(
    ($loan['first_name'] ?? '') . ' ' .
    ($loan['middle_name'] ?? '') . ' ' .
    ($loan['last_name'] ?? '')
);

$comakerName = $comaker['name'] ?? '';
$comakerAddress = $comaker['address'] ?? '';

$borrowerAddress = $loan['home_address'] ?? '';
$borrowerCivilStatus = $loan['civil_status'] ?? '';

$amountData = (float) ($loan['loan_amount'] ?? 0);

$representativeName =
    $representative[0]['company_representative_name'] ?? '';

$companyName = 'INDO-PACIFIC LENDING CORPORATION';

function addendumEscape($value)
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        <?= addendumEscape($title) ?>
    </title>

    <style>
        @page {
            margin: 25mm 20mm;
        }

        body {
            padding: 0;
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 15px;
            letter-spacing: .3px;
            text-align: justify;
            line-height: 1.5;
        }

        #content {
            margin-left: 40px;
            margin-right: 30px;
        }

        .text-center {
            text-align: center;
        }

        .section {
            margin-top: 20px;
        }

        ol {
            padding-left: 50px;
        }

        ol li {
            margin-bottom: 15px;
        }

        li span {
            display: block;
            margin-left: 10px;
        }

        table.bordered,
        table.bordered th,
        table.bordered td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 8px;
        }

        table {
            page-break-inside: avoid;
        }

        .signature-table {
            width: 100%;
            margin-top: 35px;
            text-align: center;
        }

        .signature-space {
            height: 65px;
        }

        .watermark {
            position: fixed;
            top: 30%;
            left: 15%;
            width: 70%;
            opacity: .07;
            z-index: -1;
        }
    </style>
</head>

<body>

    <!-- WATERMARK -->
    <?php
    $logoPath = APPPATH . 'Views/pdf/logo.png';

    if (file_exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $imageData = base64_encode(file_get_contents($logoPath));
        $logo = 'data:image/' . $type . ';base64,' . $imageData;
        ?>

        <div class="watermark">
            <img src="<?= $logo ?>" style="width:100%;">
        </div>

    <?php } ?>

    <br><br><br>

    <div class="text-center"
         style="font-weight:bold;text-decoration:underline;">

        ADDENDUM TO THE<br>
        CONTRACT OF LOAN

    </div>

    <div id="content">

        <!-- INTRODUCTION -->

        <div class="section">
            <b>KNOW ALL MEN BY THESE PRESENTS:</b>
        </div>

        <div class="section">

            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            This Addendum to the Contract of Loan is made, executed,
            and entered into this _____ day of ________________,
            <?= date('Y') ?>, by and between:

        </div>

        <!-- FIRST PARTY -->

        <div class="section">

            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            <b><?= addendumEscape($companyName) ?></b>, a corporation
            duly organized and existing under and in accordance with
            the laws of the Republic of the Philippines, with office
            and principal place of business at H. Alquizola Street,
            Poblacion, Barili, Cebu, Philippines, herein represented
            by <?= addendumEscape($representativeName) ?>,
            and hereinafter referred to as the
            <b>“FIRST PARTY”</b>;

        </div>

        <div class="text-center section">
            -AND-
        </div>

        <!-- SECOND PARTY: SELECTED CO-MAKER -->

        <div class="section">

            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            <b><?= addendumEscape($comakerName) ?></b>,
            of legal age, Filipino citizen, and a resident of
            <?= addendumEscape($comakerAddress) ?>, Philippines,
            hereinafter referred to as the <b>“SECOND PARTY.”</b>

        </div>

        <div class="text-center section">
            <b>WITNESSETH THAT:</b>
        </div>

        <!-- WHEREAS -->

        <div class="section">

            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            <b>WHEREAS,</b> a Contract of Loan was entered into by
            and between the <b>FIRST PARTY</b> and
            <b><?= addendumEscape($borrowerName) ?></b>,
            of legal age, <?= addendumEscape(strtolower($borrowerCivilStatus)) ?>,
            Filipino citizen, and a resident of
            <?= addendumEscape($borrowerAddress) ?>, Philippines,
            for the amount of

            <b>
                <?= strtoupper(toText($amountData)) ?> PESOS
                (PHP <?= number_format($amountData, 2) ?>)
            </b>

            with the corresponding service charge and interest,
            subject to the additional terms and conditions provided
            under the Contract of Loan, a copy of which is attached
            and made an integral part of this Addendum;

        </div>

        <div class="section">

            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            <b>WHEREAS,</b> the <b>SECOND PARTY</b> wishes to assume
            the obligation as <u><b>SOLIDARY DEBTOR AND BORROWER</b></u>
            in the above-mentioned Loan, together with its terms and
            conditions provided under the Contract of Loan;

        </div>

        <div class="section">

            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            <b>NOW THEREFORE,</b> for and in consideration of the
            foregoing and the mutual promises set forth in this
            Addendum, the parties hereto agree as follows:

        </div>

        <!-- TERMS AND CONDITIONS -->

        <ol class="section">

            <li>
                <span>
                    The <b>SECOND PARTY</b> is hereby added to the
                    above-mentioned Contract of Loan as a
                    <b>“CO-BORROWER”</b>, and any reference to
                    “Borrowers” in the Contract of Loan and any other
                    Loan Document shall mean and refer to the original
                    Borrower and the SECOND PARTY.
                </span>
            </li>

            <li>
                <span>
                    The <b>SECOND PARTY</b> assumes, as a joint and
                    several obligor under the Contract of Loan, all
                    obligations, liabilities and indemnities of a
                    Borrower under the Contract of Loan and all other
                    Loan Documents, and agrees to be bound by all
                    terms, covenants, waivers, agreements and
                    conditions applicable to a Borrower.
                </span>
            </li>

            <li>
                <span>
                    Notwithstanding anything contained in the Contract
                    of Loan to the contrary, the provisions set forth
                    in this Addendum shall be deemed part of the
                    Contract of Loan. All references in the Contract
                    of Loan and this Addendum shall be construed to
                    mean the Contract of Loan as amended and
                    supplemented by this Addendum.
                </span>
            </li>

            <li>
                <span>
                    In the event that any provision of this Addendum
                    is held to be void, invalid or unenforceable, that
                    provision shall be severed from the remainder of
                    this Contract. All remaining provisions shall
                    continue in full force and effect.
                </span>
            </li>

            <li>
                <span>
                    The <b>SECOND PARTY</b> acknowledges the right of
                    the FIRST PARTY to make verifications to ensure
                    full and faithful compliance with this Contract,
                    and authorizes the FIRST PARTY or its authorized
                    representatives to request, secure, acquire,
                    process and receive relevant information and
                    documents from government agencies and offices,
                    subject to applicable laws.
                </span>
            </li>

            <li>
                <span>
                    The <b>SECOND PARTY</b> agrees, allows and
                    authorizes banks and other financial institutions
                    to comply with lawful writs of execution or orders
                    issued by competent courts and government
                    authorities, subject to applicable laws.
                </span>
            </li>

            <li>
                <span>
                    This Addendum to the Contract of Loan shall be
                    governed by and construed in accordance with
                    Philippine Laws.
                </span>
            </li>

            <li>
                <span>
                    The <b>SECOND PARTY</b> finally declares that
                    they have read this document and fully understood
                    its contents, and voluntarily and willingly
                    executed this Addendum with full knowledge of
                    their rights and obligations under the law.
                </span>
            </li>

        </ol>

        <!-- SIGNATURES -->

        <div class="section">

            <b>IN WITNESS WHEREOF,</b> we have hereunto affixed
            our signatures this _____ day of ____________,
            <?= date('Y') ?> at Poblacion, Barili, Cebu, Philippines.

        </div>

        <div class="text-center section">

            <b><?= addendumEscape($companyName) ?></b><br>

            <i>Duly represented by:</i>

            <div class="signature-space"></div>

            <u>
                <b><?= addendumEscape($representativeName) ?></b>
            </u>

            <br>
            FIRST PARTY

        </div>

        <table class="signature-table">

            <tr>

                <td style="width:50%;">

                    <div class="signature-space"></div>

                    <u>
                        <b><?= addendumEscape($comakerName) ?></b>
                    </u>

                    <br>
                    SECOND PARTY

                </td>

                <td style="width:50%;">

                    <div class="signature-space"></div>

                    <u>
                        <b><?= addendumEscape($borrowerName) ?></b>
                    </u>

                    <br>
                    PRINCIPAL BORROWER

                </td>

            </tr>

        </table>

        <!-- WITNESSES -->

        <div class="text-center section">
            <b>IN THE PRESENCE OF:</b>
        </div>

        <table class="signature-table">

            <tr>

                <td>
                    <div class="signature-space"></div>
                    __________________________
                </td>

                <td>
                    <div class="signature-space"></div>
                    __________________________
                </td>

            </tr>

        </table>

        <!-- ACKNOWLEDGMENT -->

        <div style="page-break-before:always;"></div>

        <div class="text-center" style="margin-top:50px;">
            <b>ACKNOWLEDGMENT</b>
        </div>

        <div class="section">

            REPUBLIC OF THE PHILIPPINES )<br>
            CITY OF ______________________ ) S.S.

        </div>

        <div class="section">

            <b>BEFORE ME</b>, a Notary Public, personally appeared
            the following:

        </div>

        <table class="bordered" style="width:100%;margin-top:30px;">

            <thead>
                <tr>
                    <th>NAME</th>
                    <th>COMPETENT EVIDENCE OF IDENTITY</th>
                    <th>DATE AND PLACE OF ISSUE</th>
                </tr>
            </thead>

            <tbody>

                <tr>
                    <td>
                        <?= addendumEscape($representativeName) ?>
                        <br><br>
                    </td>
                    <td></td>
                    <td></td>
                </tr>

                <tr>
                    <td>
                        <?= addendumEscape($borrowerName) ?>
                        <br><br>
                    </td>
                    <td></td>
                    <td></td>
                </tr>

                <tr>
                    <td>
                        <?= addendumEscape($comakerName) ?>
                        <br><br>
                    </td>
                    <td></td>
                    <td></td>
                </tr>

            </tbody>

        </table>

        <div class="section">

            <p>
                Known to me to be the same persons who executed the
                foregoing instrument and acknowledged that the same
                is their free and voluntary acts and deeds.
            </p>

            <p>
                This instrument, an Addendum to the Contract of Loan,
                consisting of _____ pages, including the page on which
                this acknowledgment is written, has been signed on
                the left margin of each and every page thereof by
                the parties and their instrumental witnesses, and
                sealed with my Notarial Seal.
            </p>

            <p>
                <b>IN WITNESS WHEREOF,</b> I have hereunto set my hand
                and seal this _____ day of ____________________,
                <?= date('Y') ?> at Poblacion, Barili, Cebu, Philippines.
            </p>

        </div>

        <br><br><br><br>

        <div>

            <span>Doc No. __________:</span>

            <div style="float:right;margin-right:50px;">
                <b>NOTARY PUBLIC</b>
            </div>

            <br>

            <span>Page No. __________;</span><br>
            <span>Book No. __________;</span><br>
            <span>Series of __________.</span>

        </div>

    </div>

</body>
</html>

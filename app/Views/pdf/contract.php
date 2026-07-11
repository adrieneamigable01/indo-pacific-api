

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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract of loan - <?php echo $loan['last_name'].' '.$loan['first_name'].' - '.date("YmdHis"); ?></title>
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

        body {
            padding: 0;
            margin: 0;
            font-size: 18px;
            letter-spacing: .5px;
            text-align: justify;
            text-justify: inter-word;
            line-height: 1.2;
        }

        #content {
            margin-left: 90px;
            margin-right: 50px;
        }

        ol {
            /* list-style-position: inside; */
        }

        ol li {
            margin-bottom: 15px;
            margin-left
        }

        li span {
            /* position: relative; */
            display: block;
            margin-left: 1.5em;
        }

        ol>li::before {
            width: 1em;
            font-weight: bold;
        }

        table.bordered,
        table.bordered>thead>tr>th,
        table.bordered>tbody>tr>td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 10px;
        }

        body {
            padding-bottom: 30px;
        }
        .watermark {
            position: fixed;
            top: 25%;
            left: 10%;
            font-size: 80px;
            color: rgba(0,0,0,0.1);
            z-index: -1;
            opacity: 0.15;
        }
    </style>
</head>

<body>
<div class="watermark">
<?php

$path = APPPATH . 'Views/pdf/logo.png';

if (file_exists($path)) {

    $type = pathinfo($path, PATHINFO_EXTENSION);

    $imagedata = file_get_contents($path);

    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($imagedata);

    echo '<img src="' . $base64 . '" style="width:100%;">';

} else {

    echo '<div style="color:red;">Logo not found: ' . $path . '</div>';

}
?>
</div>
<div class="text-center">
        </div>
        <br><br><br>
    <div class="text-center" style="font-weight:bold;text-decoration:underline;">
        CONTRACT OF LOAN
    </div>
    <div id="content">
    
        <div>
            <div style="margin-top:30px;font-weight:bold;">
                KNOW ALL MEN BY THESE PRESENTS:
            </div>
            <div style="margin-top:30px;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This Contract of Loan is made, executed, and entered into this <sup><?php echo date("d") ?></sup> day of <?php echo date("M") ?> <?php echo date("Y") ?>, by and between:
            </div>

            <div style="margin-top:30px;">
                <b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;INDO-PACIFIC LENDING CORPORATION</b>, a 
                corporation duly organized and existing under and in accordance with the laws of the Republic of the Philippines, 
                with office and principal place of business at H. Alquizola Street, Poblacion, Barili, Cebu, Philippines, herein 
                represented by Raven Wyndell C. Ricaplaza, and hereinafter referred to as the <b>“LENDER”</b>;
            </div>
        </div>

        <div class="text-center" style="margin-top:20px;"> -AND- </div>

        <div style="margin-top:20px;">
            <?php
                $salutation = strtoupper($loan['gender']) == "FEMALE" ? 'MRS' : 'MR';
                $husbandsalutation = strtoupper($loan['gender']) == "FEMALE" ? 'MR' : 'MRS';
                $hasHusband = strtoupper($loan['civil_status']) == "MARRIED" ? 1 : 0;
            ?>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b>
                <?php 
                    if($hasHusband == 1){
                        echo $salutation.' & '.$husbandsalutation.' '.$loan['first_name'] . ' ' . $loan['middle_name'] . ' ' . $loan['last_name'].' AND '.$loan['spouse_name'];
                    }else{
                        echo $salutation.' '.$loan['first_name'] . ' ' . $loan['middle_name'] . ' ' . $loan['last_name'];
                    }
                    
                ?></b> 
            of legal age, <?php echo strtoupper($loan['civil_status']) ?>, Filipino, and residents of <?php echo strtoupper($loan['home_address']) ?>, Philippines, hereinafter collectively referred to as the “PRINCIPAL BORROWER”
        </div>
        <div style="margin-top:20px;">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <?php for ($i = 0; $i <= sizeof($comakers) - 1; $i++) { ?>
                
                    <?php
                        if (array_key_exists($i, $comakers)) {
                            echo "<b>".$comakers[$i]['name']."</b>";
                        }}
                    ?>
            of legal age, married, Filipino, and with residence at 
           
            <?php for ($i = 0; $i <= 9; $i++) { ?>
                <?php
                if (array_key_exists($i, $comakers)) {
                    echo "<b>".$comakers[$i]['address']."</b>";
                }}
            ?>
        
            , Philippines, hereinafter referred to as the “CO-BORROWER/CO-DEBTOR”
        
        </div>

        <div class="text-center" style="margin-top:30px;">
            <b>WITNESSETH THAT</b>
        </div>
    
        <div style="margin-top:10px;">
            

            <?php
            $amountData = 0;
            // if(!empty($category)){
            //     foreach ($category as $key => $value) {
            //     $amountData = $value['amount'];
            // }
            // }else{
            //     $amountData = $total_loan;
            // }
            // print_r($value);exit;
            $amountData = $loan['loan_amount'];
            ?>


            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>FOR VALUE RECEIVED</b>, the PRINCIPAL BORROWER AND CO-BORROWER, 
            hereby jointly and severally promise/s to pay to the LENDER or order, the principal sum of
            <b><?php echo strtoupper(toText($amountData)) ?> PESOS</b>
            <b>(PHP. <?php echo number_format($amountData, 2, ".", ",") ?>)</b>, 
            together with the <b>ONE POINT FIVE PERCENT (1.5%)</b> service charge on the principal amount and an interest 
            rate of <b><?php echo strtoupper(percentageToWords((float)($loan['approved_interest_rate']))) ?> PERCENT (<?php echo $loan['approved_interest_rate'] ?>%)</b> PER MONTH OR <b><?php echo strtoupper(percentageToWords($loan['approved_interest_rate'] * 12)) ?> PERCENT (<?php echo $loan['approved_interest_rate'] * 12 ?>%)<</b> 
            PER ANNUM, subject further to the terms and conditions set forth below
        </div>

        <div class="text-center" style="margin-top:20px;">
            <b>ADDITIONAL TERMS AND CONDITIONS</b>
        </div>

        <ol style="margin-top:30px;list-style-type: upper-roman; padding-left:75px;">
            <li>
                <span>
                    <b>SERVICE CHARGE, INTEREST AND REPAYMENT TERMS</b>: The Service Charge of <b>ONE POINT FIVE PERCENT (1.5%)</b> 
                    of the principal amount shall be deducted by the LENDER from the proceeds of <?php echo ucwords(toText($amountData)) ?> PESOS (PHP. <?php echo number_format($amountData, 2, ".", ",") ?>). The principal amount of this Loan together with the interest rate of ONE POINT FOUR 
                    <b><?php echo ucwords(toText($loan['approved_interest_rate'])) ?> (<?php echo $loan['approved_interest_rate'] ?>%)</b> PER MONTH and all other charges, cost, expenses, shall be paid according to the 
                    schedule of payments agreed by the PRINCIPAL BORROWER, CO-BORROWER and the LENDER in the document attached 
                    and made an integral part hereof as Annex “A”.

                </span>
            </li>
            <li>
                <span>
                    <b>PREPAYMENT/ PRE-TERM FEE:</b> 
                    The PRINCIPAL BORROWER AND CO-BORROWER may accelerate the payment of all or any part of the 
                    principal amount of this Loan, together with the accrued and unpaid interest, provided that 
                    the PRINCIPAL BORROWER AND CO-BORROWER shall inform the LENDER through a written notice Five (5) 
                    days before the prepayment and personally request a copy of his/her Statement of Account in 
                    person at the LENDER’S principal place of business. Failure to notify the LENDER in writing or 
                    request a copy of his/her Statement of Account in person shall allow the latter to deny their 
                    request for prepayment. <b>In case the PRINCIPAL BORROWER AND CO-BORROWER choose to prepay the 
                    principal amount and the accrued interest, the LENDER shall charge a 
                    Pre-payment/Pre-term Fee of 20% of the total outstanding principal obligation.</b>

               

                </span>
            </li>
            <li>
                <span>
                    <b>CONDITION FOR RENEWAL III:</b> 
                    As a rule, any application for renewal is subject to review and sole discretion of the LENDER 
                    and provided that the previous loan is fully paid. 
                    <br>
                    However, if renewal before the full payment of a previous loan is considered, such shall be 
                    allowed only if the current loan account is up-to-date. Upon renewal of the loan, the outstanding 
                    balance of the previous loan together with the interest, charges, and penalties, if any, shall be 
                    deducted from the proceeds of the new loan.
                </span>
            </li>
            <li>
                <span>
                    <b>SECURITY:</b>
                    As security for this Loan, the PRINCIPAL BORROWER shall deliver, turn-over possession, and assign to the 
                    LENDER all rights, title, and interest in his/her/their SAVINGS/CURRENT ACCOUNTS with the <?php echo strtoupper($loan['collateral']['primary_card_name']) ?> with Account Number: <?php echo $loan['collateral']['primary_card_number'] ?> together with his/her/their Automated Teller 
                    Machine (ATM) Card bearing No. <?php echo $loan['collateral']['secondary_card_number'] ?>, and his/her <?php echo strtoupper($loan['collateral']['secondary_card_name']) ?> Card bearing 
                    No. <?php echo $loan['collateral']['secondary_card_number'] ?> as secondary security. 

                    <br> <br>
                    The PRINCIPAL BORROWER hereby authorizes the LENDER to withdraw from his/her/their account/s the amounts and 
                    in such frequencies and intervals as indicated in the schedule of payments (Annex “A”).  The PRINCIPAL 
                    BORROWER shall also provide the LENDER the necessary Personal Identification Number (PIN) of their respective
                    ATM Cards.
                    <br> <br>
                    The PRINCIPAL BORROWER shall not cause the cancellation or deactivation of the afore-described ATM Card and 
                    UMID Card nor apply, acquire, and/or obtain a new or replacement ATM Card and UMID Card until he/she/they 
                    has/have obtained from the LENDER the necessary release of his/her/their obligation after full payment of the
                    principal obligation together with any accrued interest.
                    <br><br>
                    The PRINCIPAL BORROWER undertake not to enroll, access, or activate online banking services in relation to 
                    the ATM Card and/or Bank Account assigned, delivered, and submitted in favor of the LENDER as security. 
                    <b>
                    The PRINCIPAL BORROWER shall not conduct any electronic or mobile banking transactions, including but not 
                    limited to fund transfers and withdrawal overrides relative to the aforementioned security. Any act by the 
                    PRINCIPAL BORROWER enabling or facilitating online access of the said ATM Cards or account, or interfering
                    with LENDER’S ability to monitor, transact, or collect any payment therefrom shall constitute as material 
                    breach of this contract and is a ground for Default.
                    </b>
                    <br> <br>
                    In cases where the aforementioned ATM Card and/or UMID card is cancelled, deactivated or in way cannot be 
                    transacted, the PRINCIPAL BORROWER shall automatically be barred from acquiring any new and future loans 
                    and cash advances from the LENDER without prejudice to the exercise of the LENDER’s rights under Paragraph 
                    V of this Contract of Loan and all other rights granted to the LENDER by law. Provided, the PRINCIPAL 
                    BORROWER may seek reconsideration within Five (5) Calendar days from receipt of any notice or Demand 
                    Letter to submit their written Letter for Reconsideration and Notarized Affidavit of Undertaking declaring 
                    that they have acquired a new ATM Card/UMID without knowledge of the LENDER and undertake to turn-over 
                    the reacquired ATM Card/UMID Card within the Five (5) Calendar days.
                    <br><br>
                    In the event that the bank account provided by the PRINCIPAL BORROWER as security for the Loan is closed, 
                    suspended, restricted, frozen, rendered inactive, or otherwise becomes unavailable for any reason which 
                    includes retirement from employment or service, the Borrower shall immediately notify the Lender in writing 
                    and, within <b>THREE (3) CALENDAR DAYS</b> from such occurrence, provide and maintain another valid and active bank 
                    account acceptable to the LENDER as replacement security for the Loan. The PRINCIPAL BORROWER further 
                    undertakes to execute and submit all documents necessary to effect such replacement. Failure to comply with 
                    this obligation shall constitute a default under this Agreement and shall entitle the LENDER to exercise 
                    all rights and remedies available under this Agreement and applicable law.


                </span>

            </li>
            <li>
                <span>
                    <b>AUTO DEBIT AGREEMENT:</b> 
                    The PRINCIPAL BORROWERS hereby authorizes the LENDER to automatically debit from the 
                    PRINCIPAL BORROWERS designated security under Paragraph IV of this Agreement the amount 
                    due under Annex “A” and any and all provisions of this Contact of Loan which includes but not 
                    limited to the interest payment, penalties, and other charges on each due date without need of any 
                    demand. Amounts debited from the PRINCIPAL BORROWER enrolled account/s will be automatically applied 
                    to the payment of the amount due to the  PRINCIPAL BORROWER.
                    <br><br>
                    The PRINCIPAL BORROWER undertake to maintain sufficient funds in the enrolled account to cover all amount 
                    due therein. In the event of insufficiency of funds in the enrolled account, the PRINCIPAL BORROWER shall 
                    be liable for a penalty fee of TEN PERCENT (10%) per attempt without regard to the other 
                    fees and charges which maybe imposed under this Contract of Loan and the law.
                    <br><br>
                    The Auto Debit Agreement shall be in force until the principal and the interest together with any 
                    fees and charges are fully paid. <b>
                        <u>
                        The PRINCIPAL BORROWER shall not be allowed to revoke any Auto Debit
                        Agreement duly approved by their respective banks unless full payment is made. Any revocation without 
                        the consent of the LENDER shall constitute as breach of contract.
                        </u>
                    </b>

                    <br> <br>
                    The PRINCIPAL BORROWER also hereby waive their rights under the Secrecy of Bank Deposits Law under Republic 
                    Act No. 1405 in connection with any information which maybe disclosed to the LENDER for the implementation 
                    of the ADA.
                </span>
            </li>
            <li>
                <span>
                    <b>DEFAULT AND BREACH :</b> 
                    The PRINCIPAL BORROWER shall be in default upon the occurrence of any of the following events:
                    <ol style="list-style-type: lower-alpha;margin-top:20px;">
                        <li>
                            Any or all of the PRINCIPAL BORROWER become insolvent or seeks an order of relief under the Financial Rehabilitation and Insolvency Act of 2010 or subsequent laws governing rehabilitation and insolvency or shall commit or permit any act of bankruptcy under applicable law;
                        </li>
                        <li>
                            Any or all of the PRINCIPAL BORROWER is/are unable to pay their obligation on their respective due date/s under the terms and condition of this Contract of Loan;
                        </li>
                        <li>
                            any of PRINCIPAL BORROWER representations and warranties as specified hereunder shall prove false in any material respect when made;
                        </li>
                        <li>
                            Any violation to Article IV of this Contract of Loan.
                        </li>
                        <li>
                            Any violation to Article V of this Contract of Loan.
                        </li>
                        <li>
                            Any act, omission, or conduct by the PRINCIPAL BORROWER that directly, or indirectly frustrates, obstructs, or hinders the implementation, performance, or enforcement of any of the provisions of this Contract of Loan shall be deemed a material breach of this Contract. This includes but not limited to, providing false or misleading information against the LENDER, refusing to cooperate in good faith, concealing material facts which may affect the interest of the LENDER, transferring assets to avoid payment, or deliberately avoiding communication related to this loan.
                        </li>
                        <li>
                            When PRINCIPAL BORROWER engage in conduct that, in the reasonable judgment of the LENDER, causes substantial reputational harm to the LENDER, and such harm cannot be adequately addressed or cured within ten (10) days of written notice. For the purposes of this clause, 'substantial reputational harm' includes, but is not limited to, actions that damage the LENDER's standing, bring the LENDER into disrepute, attract adverse publicity, or harm public confidence in the LENDER. 
                        </li>
                    </ol>
                </span>
            </li>
            <li>
                <span>
                    <b>CONSEQUENCES OF DEFAULT AND/OR BREACH: </b> 
                    Upon the occurrence of an event of default, the LENDER shall have the option to exercise any of the following remedies, alternatively or cumulatively at its discretion, in conjunction with or separately from any other right or remedy granted hereunder or under the law, without need for any legal or judicial action or order, to enforce payment of this Contract:
                    <ol style="list-style-type: lower-alpha;margin-top:20px;">
                        <li>
                            The principal obligation as well as the interest, costs, additional charges, expenses, if any, shall be immediately due and demandable;
                        </li>
                        <li>
                            PRINCIPAL BORROWER AND CO-BORROWER shall pay the LENDER a penalty of TEN THOUSAND PESOS (PhP10,000.00) 
                            or at the rate of two percent (2%) per month, whichever is higher,  from the date of notice of default 
                            or the expiration of any grace period granted in the written demand, at the election of the LENDER, 
                            until the BORROWER pays the full amount due (principal amortization or accrued interest or both as 
                            the case may be). The imposition and payment of the penalty shall not however be construed as a 
                            waiver on the part of the LENDER to file appropriate CIVIL, CRIMINAL, or ADMINISTRATIVE ACTIONS 
                            against any or all of the BORROWERS which includes complaints with the Department of Education, 
                            Commission on Higher Education and/or the Civil Service Commission;
                        </li>
                        <li>
                            LENDER may cause the publication and posting of notice to the public in local newspapers and 
                            newspapers of general circulation relevant to BORROWERS’ acts or transgressions and share the 
                            same with other lenders, credit reporting agencies and duly accredited outsourced entities. 
                        </li>
                    </ol>
                </span>
            </li>
            <li>
                <span>
                    <b>GRACE PERIOD ON PENALTIES AND SURCHARGES:</b> 
                    The Principal Borrower and Co-Borrower shall be allowed a grace period on penalties and surcharges 
                    of <b>THREE HUNDRED SIXTY-FIVE (365)</b> calendar days from the due date for the payment of any installment 
                    or obligation under this Agreement. Payments made within the grace period shall not be subject to 
                    penalties or late charges, provided that interest shall continue to accrue on the outstanding balance. 
                    In the event that payment is not made within the grace period, the Principal Borrower and Co-Borrower 
                    shall automatically be made liable for penalties and surcharges without need of prior demand or notice 
                    and without regard to the Lender’s rights to exercise all remedies available under this Agreement and applicable law.
                </span>
            </li>
            <li>
                <span>
                    <b>NATURE OF LIABILITY:
                    <u>The PRINCIPAL BORROWER, together with the CO-BORROWER, hereby agrees, binds, covenants, 
                        themselves jointly and severally (in solidum) to the LENDER for the full and faithful payment 
                        and performance of all obligations under this Agreement. 
                    </u>
                    </b>The PRINCIPAL BORROWER AND CO-BORROWER shall be liable for the entire obligation, 
                    and the LENDER may proceed against any one or all of them, either jointly or separately, 
                    at its option, without the necessity of exhausting remedies against the others or against 
                    any security. The obligations of each party shall remain in full force and effect notwithstanding 
                    any extension, restructuring, or modification of this Agreement, or any release, waiver, or impairment 
                    of any security or obligation of any other party.
                </span>
            </li>
            <li>
                <span>
                    <b>NON-WAIVER: </b>
                    No delay or failure on the part of the LENDER in the exercise of any right or remedy shall 
                    operate as a waiver thereof and no single or partial exercise by the LENDER of any right 
                    or remedy shall preclude other or further exercise thereof or the exercise of any other 
                    right or remedy. The PRINCIPAL BORROWER shall be in no way discharged from any other obligation 
                    or undertaking hereunder, should the LENDER compromise, extend or renew from time to time and for 
                    any period, whether or not longer than the original period, any obligation owed the LENDER by the  
                    PRINCIPAL BORROWER.
                </span>
            </li>
            <li>
                <span>
                    <b>INTEGRATION & AMENDMENT:</b> 
                    This Contract, including the attachments mentioned in the body as incorporated
                    by reference, sets forth the entire agreement between the parties with regard to 
                    the subject matter hereof. Any amendment or modification of the terms and conditions 
                    of this Contract of Loan shall only be valid when made in a public instrument, signed 
                    by all parties, and attached to this original Contract of Loan.
                </span>
            </li>
            <li>
                <span>
                    <b>SUCCESSION:</b> 
                    This Loan will be binding upon the PRINCIPAL BORROWER their successors, permitted assigns, 
                    and heirs and shall inure to the benefit of the LENDER, its successors-in-interests and assigns. 
                    Provided however, the  PRINCIPAL BORROWER shall not assign their rights or delegate their duties 
                    under the terms of this Loan without a written notice to and approval from the LENDER.
                </span>
            </li>
            <br> <br> <br>
            <li>
                <span>
                    <b>VENUE & JUDICIAL RELIEF:</b> 
                    Any conflict arising from the terms and condition of this Contract of Loan or non-payment of the 
                    obligation shall solely, exclusively and only, to the exclusion of all other courts, be filed with 
                    the proper courts of law in Barili, Cebu, Philippines.
                    <br><br>
                    In the event this Contract is referred to an attorney for collection, the PRINCIPAL BORROWER and 
                    CO-BORROWER shall indemnify the LENDER for attorney’s fees and out-of-pocket expenses computed at 
                    five percent (5%) of the total amount due or One Hundred Thousand Pesos (Php. 100,000.00) , whichever 
                    is higher and Five Thousand Pesos (Php.5,000.00) appearance fee per hearing/setting, in addition to all 
                    other costs and damages which the court may award or entitle the LENDER.
                </span>

            </li>
            <li>
                <span>
                    <b>SEPARABILITY CLAUSE:</b> 
                In the event that any of the provision of this Contract of Loan is held to be void, invalid or unenforceable, 
                that provision shall be severed from the remainder of this Contract so as not to cause the invalidity or 
                unenforceability of the remainder of this Contract. All remaining provisions of this Contract shall then 
                continue in full force and effect. If any provision shall be deemed invalid due to its scope or breadth, 
                such provision shall be deemed valid to the extent of the scope and breadth permitted by law.
                </span>
            </li>
            <li>
                <span>
                    <b>GOVERNING LAW:</b> 
                This Contract of Loan shall be governed by and construed in accordance with Philippine Laws.
                </span>
            </li>
            <li>
                <span>
                    <b>DATA PRIVACY: </b>
                By providing personal information and sensitive personal information, PRINCIPAL BORROWER and 
                CO-BORROWER acknowledge and recognize the exclusive right of the LENDER to utilize the data for 
                this Contract and for other relevant or incidental uses. The PRINCIPAL BORROWER and CO-BORROWER also 
                acknowledge that all such information, records, files and among others, shall form part of the exclusive 
                use of the LENDER and they consent to the use thereof. These acts of the LENDER are not and shall not be 
                deemed as violation of data privacy law, human relations, or any breach of personal information or sensitive 
                personal information. Consent hereof is likewise given when subsequent data and information are shared in the 
                event of default, change of status, etc. The PRINCIPAL BORROWER and CO-BORROWER render the company, its officers, 
                directions, stockholder, employees, agents and representatives free and harmless from any and all labilities from 
                the use of said data, records or information, pursuant to R.A. 10173 otherwise known as the “DATA PRIVACY ACT OF 2012”.
                </span>
            </li>
            <li>
                <span>
                    <b>AUTHORITY OF LENDER TO SECURE RELEVANT INFORMATION AND DOCUMENTS 
                        FROM DEVELOPMENT BANK OF THE PHILIPPINES, DepEd, CHED, GSIS, AND OTHER AGENCIES AND OFFICES: 
                    </b>
                    The PRINCIPAL BORROWER and CO-BORROWER acknowledge the right of LENDER to make verifications to ensure their 
                    full and faithful compliance to this Contract, as such, the PRINCIPAL BORROWER and CO-BORROWER authorize, 
                    designate, constitute, and appoint LENDER or any of its authorized representative as their attorney-in-fact 
                    to REQUEST, SECURE, ACQUIRE, PROCURE, SUBMIT, PROCESS, FOLLOW-UP, CLAIM and RECEIVE any and all papers, 
                    information, records, and other documents from the Development Bank of the Philippines, GSIS, Department 
                    of Education, Commission on Higher Education, and other offices and agencies relevant to my afore-described 
                    ATM and UMID Cards, Salaries and Employment Benefits. BORROWERS expressly declare that this authority shall 
                    not in any way be construed as a violation of their rights under R.A. No. 1405, R.A. 10173, and other 
                    pertinent laws, rules and regulations, and circulars and hereby hold free and harmless DBP, DepEd, CHED, 
                    and GSIS relative to such disclosure.
                </span>
            </li>
            <li>
                <span>
                    <b>AUTHORITY TO ASSIGN TO COLLECTION AGENTS: </b>
                    PRINCIPAL BORROWER and CO-BORROWER expressly agree and authorize the LENDER to assign, endorse, 
                    or refer the collection of any unpaid outstanding obligation, including principal, interest, penalties 
                    and/or charges, to a third-party collection agency, at the sole discretion of the LENDER.
                    <br><br>
                    The PRINCIPAL BORROWER and CO-BORROWER further acknowledge that such assignment does not absolve the 
                    PRINCIPAL BORROWER and CO-BORROWER of any liability arising from or by reason of this Contract of Loan 
                    and all right rights and remedies the LENDER may exercise. The PRINCIPAL BORROWER and CO-BORROWER further 
                    consent to the disclosure of necessary personal information to such agency solely for the purpose of 
                    enforcing collection.
                    <br><br>
                    All costs and expenses incurred by the LENDER and the agency shall be on account of the 
                    PRINCIPAL BORROWER and CO-BORROWER.
                </span>
            </li>
            <li>
                <span>
                    <b>AUTHORITY TO GARNISH: </b>
                    The PRINCIPAL BORROWER and CO-BORROWER agree, allow, and authorize the Development Bank of the Philippines, 
                    Rural Bank of the Philippines, Landbank of the Philippines, and all other banks which their accounts are 
                    held to garnish their accounts and funds deposited therein as a result of any writ of execution or other 
                    lawful Order/s by a court and other government offices, agencies and/or instrumentalities. 
                    The PRINCIPAL BORROWER and CO-BORROWER expressly declare that this authority shall not in any way be 
                    construed as a violation of their rights under R.A. No. 1405, R.A. 10173, and other pertinent laws, 
                    rules and regulations, and circulars and hereby hold free and harmless DBP, DepEd, CHED, and GSIS 
                    relative to such disclosure.
                </span>
            </li>
            <li>
                <span>
                    <b>DECLARATION:</b> 
                    The PRINCIPAL BORROWER and CO-BORROWER finally declare that they have read this document and have 
                    fully understood its contents.  They further declare that they voluntarily and willingly executed 
                    this Contract of Loan with full knowledge of their rights and obligations under the law.
                </span>
            </li>
        </ol>

        <div style="margin-top:20px;">
            <b>IN WITNESS WHEREOF</b>,We have hereunto affixed our signatures this <?php echo date("d")?> day of <?php echo date("M")?>, <?php echo date("Y")?> at Poblacion, Barili, Cebu, Philippines.
        </div>

        <div class="text-center" style="margin-top:20px;">
            <b>INDO - PACIFIC LENDING CORPORATION</b><br>
            <i>Duly represented by:</i> <br><br><br>
            <u><b> Ricaplaza Raven Wyndell </b></u>
        </div>


        <table style="width:100%;margin-top:20px;" class="text-center">

            <tr>
                <td>
                    <u><b><?php echo $loan['first_name'] . ' ' . $loan['middle_name'] . ' ' . $loan['last_name'] ?></b></u><br>
                    Borrower
                </td>
                <td>
                    <u><b><?php echo $comakers[0]['name'] ?></b></u> <br>
                    Borrower
                </td>
            </tr>
            <tr>
        </table>
        <br>
        <?php for ($i = 1; $i <= sizeof($comakers) - 1; $i = $i + 2) { ?>
            <table style="width:100%;margin-top:15px" class="text-center">
                <tr>
                    <td>
                        <u><b><?php echo array_key_exists($i, $comakers) ? $comakers[$i]['name'] : "________________________" ?></b></u><br>
                        Borrower
                    </td>
                    <td>
                        <u><b><?php echo  array_key_exists($i + 1, $comakers) ? $comakers[$i + 1]['name'] : "________________________" ?></b></u> <br>
                        Borrower
                    </td>
                </tr>
            </table>
        <?php } ?>


        <div class="text-center" style="margin-top:30px;">
            <span> IN THE PRESENCE OF:</span>
        </div>
        <table style="width:100%;margin-top:70px;" class="text-center">
            <tr>
                <td>
                    <u><b> GLYZA T. ALQUIZALAS </b></u>
                </td>
                <td>
                    <u><b> MA. TERESITA BALANSAG </b></u>
                </td>
            </tr>
        </table>



    </div>

</body>

<body>


    <div>

        <div style="margin-top:60px;" class="text-center">
            <b>ACKNOWLEDGEMENT</b>
        </div>
        <div class="content" id="content">
            <div style="margin-top:20px;">
                REPUBLIC OF THE PHILIPPINES) <br>
                CITY OF _______________________) S.S
            </div>
            <div style="margin-top:20px;">
                <b>BEFORE ME</b>, a Notary Public, personally appeared the following:
                <table style="margin-top:30px;width:100%;margin-left:0;" class="bordered">
                    <thead class="text-center">
                        <tr style="font-weight:bold">
                            <th>NAME</th>
                            <th>COMPETENT EVIDENCE OF IDENTITY</th>
                            <th>DATE AND PLACE OF ISSUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                Ricaplaza Raven Wyndell
                                <br>
                                <br>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo $loan['first_name'] . ' ' . $loan['middle_name'] . ' ' . $loan['last_name'] ?>
                                <br>
                                <br>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php for ($i = 0; $i <= sizeof($comakers) - 1; $i++) { ?>
                            <tr>
                                <td>
                                    <?php echo array_key_exists($i, $comakers) ?  $comakers[$i]['name']  : "" ?>
                                    <br>
                                    <br>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div>
                <p>Known to me to be the same persons who executed the foregoing instrument and acknowledged that the same is their free and voluntary acts and deeds.</p>
                <p>This instrument, a Contract of Loan, consisting of FOUR (4) pages, including the page of which this acknowledgement is written, has been signed on the left margin of each and every page thereof by the parties and their instrumental witnesses, and sealed with my Notarial Seal.</p>
                <p>
                    <b>IN WITNESS WHEREOF,</b> I have hereunto set my hand and seal this _____ day of ____________________, <?php echo date("Y") ?> at Poblacion, Barili, Cebu, Philippines, Philippines.
                </p>
            </div>
            <br>
            <br>
            <br>
            <br>
            <br>
            <div>
                <span>Doc No._________:</span>
                <div style="float:right;margin-right:50px;">
                    <b style="margin-bottom:25px;">NOTARY PUBLIC</b>
                </div>
                <br>
                <span>Page No.________; </span><br>
                <span>Book No.________;</span> <br>
                <span>Series of _______</span> <br>
            </div>

        </div>

    </div>
    </div>

</body>

        <body style="padding-left:35px;padding-right:35px;font-size:13px;">
            <br><br><br>
            <div style="text-align:right">
                <b style="margin-right:38px;">
                    <span>ANNEX “A”</span>
                </b><br>
                <b>
                    <span>SCHEDULE OF PAYMENTS</span>
                </b>
            </div>
            <div class="text-center" style="font-weight:bold;">
                REPAYMENT TERMS <br>
                ESSENTIAL PROVISION
            </div>
            <br>
            <div>
                <table style="width:100%;" id="tbl-essential">
                    <tbody>
                        <tr>
                            <td style="width:50%;">LENDER</td>
                            <td style="width:50%;">INDO - PACIFIC LENDING CORPORATION</td>
                        </tr>
                        <tr>
                            <td style="width:50%;">BORROWER</td>
                            <td style="width:50%;"><?php echo $loan['first_name'] . ' ' . $loan['middle_name'] . ' ' . $loan['last_name'] ?></b></td>
                        </tr>
                        <tr>
                            <td style="width:50%;">BORROWER`S COMPLETE RESIDENCE ADDRESS</td>
                            <td style="width:50%;"><?php echo $loan['home_address'] ?></td>
                        </tr>
                        <tr>
                            <td style="width:50%;">PRINCIPAL LOAN AMOUNT</td>
                            <td style="width:50%;"><?php echo number_format($amountData, 2, ".", ",") ?></td>
                        </tr>
                        <tr>
                            <td style="width:50%;">SERVICE CHARGE</td>
                            <td style="width:50%;">0% of Principal Amount to be deducted by the LENDER from the proceeds.</td>
                        </tr>
                        <tr>
                            <td style="width:50%;">INTEREST PAYMENT</td>
                            <td style="width:50%;">(A) <?php echo ucwords(percentageToWords($loan['approved_interest_rate'])).' '.$loan['approved_interest_rate'].'%' ?></td>
                        </tr>
                    </tbody>
                </table>
                <br>
            <table style="width:100%;text-align:center">
                <tr>
                    <th>#</th>
                    <th>PRINCIPAL</th>
                    <th>INTEREST</th>
                    <th>TOTAL PAYMENT</th>
                    <th>BALANCE</th>
                </tr>
                
                    <?php
                    
                        $total = $amountData;
                        $balance =  $amountData; 
                        $term = 12;
                        $totalpayment = 0;
                        $date = date("Y-m-d");
                        if(strtoupper($loan['product_name']) == "LONG TERM LOAN"){
                            $term = 10;
                        }
                        if(strtoupper($loan['product_name']) == "INTEREST ONLY"){
                            $term = 10;
                        }
                        for ($i = 1; $i <= $term ; $i++) {
                            
                            if($i > 1){
                                $date = date("Y-m-d",strtotime('+'.(($i - 1) * 12).' month'.$date));
                            }else{
                                $date = date("Y-m-d",strtotime($date));
                            }
                            
                            $principal = 0;
                            $interest = 0;
                            if(strtoupper($loan['product_name']) == "LONG TERM LOAN"){
                               
                                $principal = $amountData;
                                
                                $interest = (float)($principal) * .01;
                                $principal = $principal / ($term * 12);
                               
                                $yearlyCollection = ((float)($interest) + (float)($principal)) * 12;
                                $yearlyCollection = number_format($yearlyCollection , 2, '.', '');
                                $date = date("Y-m-d");

                                $yearlyInterest = ($interest * 120) / 7;

                                $sevenYearsPrincipal = $yearlyCollection - $yearlyInterest;
                               

                                $balance = floatval($balance) - ($i > 7 ? (floatval($yearlyCollection)) : (floatval($sevenYearsPrincipal)));
                                $balance = $balance < 1 ? 0 : number_format((float)($balance), 2, '.', '');
                                $sevenYearsPrincipal = number_format($sevenYearsPrincipal);
                                echo "<tr>";
                                echo "<td>".$i."</td>";
                                echo "<td>".($i > 7 ? ($yearlyCollection) : ($sevenYearsPrincipal))."</td>";
                                echo "<td>".($i > 7 ? 0 : number_format($yearlyInterest, 2, '.', ''))."</td>";
                                echo "<td>".$yearlyCollection."</td>";
                                echo "<td>".$balance."</td>";
                                echo "</tr>";
                            }else if(strtoupper($loan['product_name']) == "INTEREST ONLY"){
                               
                                $principal = $amountData;
                                
                                $interest = (float)($principal) * ((float)($loan['approved_interest_rate']) / 100);
                        
                               
                                $date = date("Y-m-d");

                                $yearlyInterest = ($interest * 12);

                                // $yearlyInterest = number_format($yearlyInterest);
                                echo "<tr>";
                                echo "<td>".$i."</td>";
                                echo "<td>0</td>";
                                echo "<td>".number_format($yearlyInterest, 2, '.', '')."</td>";
                                echo "<td>".$yearlyInterest."</td>";
                                echo "<td>".$balance."</td>";
                                echo "</tr>";
                            }else{

                                $principal = $amountData / 12;
                                $interest = 0;
                                $totalpayment = $principal;
                                $balance = floatval($balance) - floatval($principal);

                                $principal = number_format($principal, 2, ".", ",");
                                $interest = number_format($interest, 2, ".", ",");
                                $totalpayment = number_format($totalpayment, 2, ".", ",");
                                $balance = $balance < 0 ? 0 : number_format((float)($balance), 2, '.', '');

                                echo "<tr>";
                                echo "<td>".$i."</td>";
                                echo "<td>".$principal."</td>";
                                echo "<td>".$interest."</td>";
                                echo "<td>".$totalpayment."</td>";
                                echo "<td>".$balance."</td>";
                                echo "</tr>";
                            }
                           
                        }
                    ?>
                    
                
            </table>
            <br>
                <br>
                <div class="text-left">
                    <b>Approved by:</b><br>
                </div>
                <!-- <br> -->
                <table style="width:100%;">
                    <tr class="text-center">
                        <td>
                            <b>INDO - PACIFIC LENDING CORPORATION</b> <br>
                            ( LENDER )
                        </td>
                        <td>
                            <b><?php echo $loan['first_name'] . ' ' . $loan['middle_name'] . ' ' . $loan['last_name'] ?></b><br>
                            (Borrower)
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span style="text-left">By:</span>
                        </td>
                    </tr>
                    <tr class="text-center">
                        <td>

                            <b>Ricaplaza Raven Wyndell</b>
                        </td>
                        <td>
                            <b><?php echo $comakers[0]['name'] ?></b><br>
                            (Borrower)
                        </td>
                    </tr>
                </table>
                <br>
                <?php for ($i = 1; $i <= sizeof($comakers) - 1 ; $i = $i + 2) { ?>
                    <table style="width:100%;">
                        <tr class="text-center">
                            <td>
                                <b><?php echo array_key_exists($i, $comakers) ? $comakers[$i]['name'] : "" ?></b><br>
                                (Borrower)
                            </td>
                            <td>
                                <b><?php echo array_key_exists($i + 1, $comakers) ? $comakers[$i + 1]['name'].'<br>'.' (Borrower)'  : "" ?></b>
                            </td>
                        </tr>
                        <br>
                    </table>
                <?php } ?>

            </div>

        </body>


</html>
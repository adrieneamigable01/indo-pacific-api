<?php
// print_r($loan);return false;
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
       if (is_numeric($amt)) {
           // echo '' . number_format($amt, 0, '.', ',') . '';
           $sign = $amt > 0 ? '' : 'Negative ';
           return $sign . toQuadrillions(abs($amt));
       } else {
           throw new Exception('Only numeric values are allowed.');
       }
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
   
   ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo $title; ?></title>
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
      </style>
   </head>
         
    
    <body style="padding-left:35px;padding-right:35px;font-size:17px;">
        <br><br><br>
        <div style="text-align:right">
            <b>
            <span>ADDITIONAL LOAN </span>
            </b>
        </div>
        <br><br>
        <div class="text-center" style="font-weight:bold;">
            REPAYMENT TERMS FOR ADDITIONAL LOAN <br>
            ESSENTIAL PROVISION
        </div>
        <br>
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
                    <td style="width:50%;"><?php echo $loan['first_name'].' '.$loan['middle_name'].' '.$loan['last_name']?></b></td>
                </tr>
                <tr>
                    <td style="width:50%;">DATE</td>
                    <td style="width:50%;"><?php echo date("F d ,Y H:i:s") ?></b></td>
                </tr>
                <tr>
                    <td style="width:50%;">BORROWER`S COMPLETE RESIDENCE ADDRESS</td>
                    <td style="width:50%;"><?php echo $loan['home_address'] ?></td>
                </tr>
                <tr>
                    <td style="width:50%;">LOAN PRODUCT AND PRINCIPAL LOAN AMOUNT:</td>
                    <td style="width:50%;"><?php echo $loan['product_name'].' - '. strtoupper(toText($loan['loan_amount'], 2, ".", ",")).'( PHP '. number_format($loan['loan_amount'], 2, ".", ",") .')' ?></td>
                </tr>
                <?php
                    if(!empty($loan['loan_terms'])){
                ?>
                <tr>
                    <td style="width:50%;">LOAN TERM:</td>
                    <td style="width:50%;"><?php
                        $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                        echo strtoupper($f->format($loan['loan_terms']));
                    ?> (<?php echo $loan['loan_terms'] ?>) MONTHS</td>
                </tr>
                <?php } ?>
                <!-- <tr>
                    <td style="width:50%;">INTEREST.</td>
                    <td style="width:50%;">(A) 
                    <?php
                        $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                        echo strtoupper($f->format(($loan['approved_interest_rate'] * 100)));
                    ?>
                    PERCENT (<?php echo ($loan['approved_interest_rate'] * 100)  ?> %) PER MONTH  </td>
                </tr> -->
                </tbody>
            </table>
            <br>
            <span class="text-center">
                In cases where the above-mentioned Benefit-Loan is left unpaid IN FULL, any deficit will be deducted from the following: 
            </span>
            <br>
            <table style="width:100%;" class="bordered">
                <tbody>
                    <tr>
                        <td style="width:50%;">MID-YEAR BONUS</td>
                        <td style="width:50%;">YEAR-END BONUS</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">Clothing Allowance</td>
                        <td style="width:50%;">Chalk Allowance</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">Performance Base Bonus ( PBB )</td>
                        <td style="width:50%;">Productivity Enhancement Incentive ( PEI )</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">Service Recognition Incentive ( SRI )</td>
                        <td style="width:50%;">Differential</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">Hardship</td>
                        <td style="width:50%;">Loyalty</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">GSIS</td>
                        <td style="width:50%;">OTHERS</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <span class="text-center">
                This Additional LOAN shall be integrated and incorporated in the above referenced Contract of Loan which shall form part of the existing outstanding obligations and in accordance with the terms of the agreement. They further declare that they have read this document and have fully understood its contents.  They finally declare that they voluntarily and willingly executed this Additional Loan and the terms thereof with full knowledge of their rights and obligations under the law. 
            </span>
            <br>
            <div class="text-left">
                <b>Process by:</b><br>
            </div>
            <br>
            <br>
            <table style="width:100%;">
                <tr class="text-center">
                <td>
                    <b>RAVEN WYNDELL C. RICAPALZA</b><br>
                    ( BPLC STAFF  )
                </td>
                <td>
                    <b><?php echo $loan['first_name'].' '.$loan['middle_name'].' '.$loan['last_name']?></b><br>
                    (Borrower)
                </td>
                </tr>
            </table>
        </div>
    </body>
</html>
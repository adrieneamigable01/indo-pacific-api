<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        
        .title-box {
            background-color: #e88b8b;
            padding: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin-bottom: 20px;
            border: 2px solid #333;
        }
        
        .info-box {
            margin-bottom: 15px;
            font-size: 10px;
            color: #333;
        }
        
        .table-box {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }
        
        th, td {
            border: 1px solid #333;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }
        
        th {
            background-color: #8eb5e6;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        
        td {
            font-size: 9px;
        }
        
        .number-col {
            width: 6%;
            text-align: center;
            font-weight: bold;
        }
        
        .name-col {
            width: 30%;
        }
        
        .withdrawal-col {
            width: 14%;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 9px;
            text-align: center;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

<!-- ========================================================= -->
<!-- TITLE -->
<!-- ========================================================= -->

<div class="title-box">
    <?= esc($title) ?>
</div>

<!-- ========================================================= -->
<!-- INFO BOX -->
<!-- ========================================================= -->

<div class="info-box">
    <strong>Generated Date:</strong> <?= esc($generatedDate) ?><br>
    <strong>Total Borrowers:</strong> <?= esc($totalCount) ?>
</div>

<!-- ========================================================= -->
<!-- BORROWER TABLE -->
<!-- ========================================================= -->

<table class="table-box">
    <thead>
        <tr>
            <th class="number-col">No.</th>
            <th class="name-col">Borrower Name</th>
            <th class="withdrawal-col">1<sup>st</sup> W/D</th>
            <th class="withdrawal-col">2<sup>nd</sup> W/D</th>
            <th class="withdrawal-col">ADA</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $chunkSize = 33; // Adjust based on page size
        $chunks = array_chunk($names, $chunkSize);
        $globalIndex = 0;
        
        foreach ($chunks as $chunkIndex => $chunk): 
            if ($chunkIndex > 0): ?>
                <tr><td colspan="5" class="page-break"></td></tr>
            <?php endif; ?>
            
            <?php foreach ($chunk as $name): 
                $globalIndex++;
                $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            ?>
                <tr>
                    <td class="number-col"><?= $globalIndex ?></td>
                    <td class="name-col"><?= $safeName ?></td>
                    <td class="withdrawal-col"></td>
                    <td class="withdrawal-col"></td>
                    <td class="withdrawal-col"></td>
                </tr>
            <?php endforeach; ?>
            
        <?php endforeach; ?>
    </tbody>
</table>

<!-- ========================================================= -->
<!-- FOOTER -->
<!-- ========================================================= -->

<div class="footer">
    <i>This is a computer-generated list. For Indo Pacific Lending Corporation use only.</i><br>
    Generated on <?= esc($generatedDate) ?> | Total: <?= esc($totalCount) ?> borrowers
</div>

</body>
</html>
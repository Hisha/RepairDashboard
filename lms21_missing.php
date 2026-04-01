<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/LMS21Data.php';

$model = new LMS21Data();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnInsert'])) {
    $niin = trim($_POST['niin'] ?? '');
    $cog = trim($_POST['cog'] ?? '');
    $lrc = trim($_POST['lrc'] ?? '');
    $stdPriceRaw = trim($_POST['std_price'] ?? '');
    
    if ($niin === '') {
        $error = 'NIIN is required.';
    } elseif ($cog === '') {
        $error = 'COG is required.';
    } elseif ($lrc === '') {
        $error = 'LRC is required.';
    } elseif ($stdPriceRaw === '' || !is_numeric($stdPriceRaw)) {
        $error = 'STD Price must be numeric.';
    } else {
        $success = $model->insertLms21Data(
            $niin,
            $cog,
            $lrc,
            (float)$stdPriceRaw
            );
        
        if ($success) {
            $message = "Inserted LMS21Data entry for NIIN {$niin}.";
        } else {
            $error = "Failed to insert LMS21Data entry for NIIN {$niin}.";
        }
    }
}

$missingNiins = $model->getMissingNiins();
$lrcRows = $model->getLrcOptions();
$lrcOptions = array_map(fn($row) => $row['normalized_program'], $lrcRows);

include 'menu.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Missing LMS21Data Entries</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            background: #f8f9fa;
            color: #212529;
        }

        .page-wrap {
            max-width: 1600px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 10px;
        }

        .message {
            margin: 0 0 15px 0;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid;
        }

        .message.success {
            background: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }

        .message.error {
            background: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
        }

        .table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 75vh;
            border: 1px solid #ddd;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th, td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #f1f3f5;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            box-sizing: border-box;
            padding: 6px 8px;
            font-size: 14px;
        }

        .insert-btn {
            padding: 8px 12px;
            background: #198754;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        .insert-btn:hover {
            background: #157347;
        }

        .count-box {
            margin-bottom: 12px;
            padding: 8px 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            display: inline-block;
        }

        .niin-cell {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="page-wrap">
    <h1>Missing LMS21Data Entries</h1>

    <?php if ($message !== ''): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="count-box">
        Missing NIINs: <strong><?= number_format(count($missingNiins)) ?></strong>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                	<th>NIIN</th>
                    <th>Listed Subgroup</th>
                    <th>Suggested Program</th>
                    <th>COG</th>
                    <th>STD Price</th>
                    <th>LRC</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($missingNiins)): ?>
                    <?php foreach ($missingNiins as $row): ?>
                        <tr>
                            <form method="post" action="lms21_missing.php">
                                <td class="niin-cell"><?= htmlspecialchars($row['NIIN']) ?></td>
                                    <td><?= htmlspecialchars($row['listed_subgroup']) ?></td>
                                    <td><?= htmlspecialchars($row['normalized_program']) ?></td>
                                    <td>
                                    <input
                                        type="text"
                                        name="cog"
                                        maxlength="10"
                                        placeholder="Enter COG"
                                        required
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        name="std_price"
                                        step="0.01"
                                        min="0"
                                        placeholder="Enter STD Price"
                                        required
                                    >
                                </td>
                                <td>
                                    <select name="lrc" required>
                                        <option value="">Select LRC</option>
                                        <?php foreach ($lrcOptions as $lrc): ?>
                                            <option
                                                value="<?= htmlspecialchars($lrc) ?>"
                                                <?= $lrc === $row['normalized_program'] ? 'selected' : '' ?>
                                            >
                                                <?= htmlspecialchars($lrc) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" name="btnInsert" class="insert-btn">Insert</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:20px;">
                            No missing LMS21Data NIINs found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
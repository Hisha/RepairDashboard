<?php

require_once __DIR__ . '/bootstrap.php';
include 'menu.php';
require_once APP_ROOT . '/bin/Model/Battery853Labels.php';

$defaultBuildDate = date('Y-m-d');

$buildDate = trim(
    $_POST['build_date'] ?? $defaultBuildDate
);

$quantity = isset($_POST['quantity'])
    ? (int)$_POST['quantity']
    : 1;

$labels = [];
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $model = new Battery853Labels();

        $labels = $model->createBatch(
            $buildDate,
            $quantity
        );

        $successMessage = count($labels)
            . (
                count($labels) === 1
                    ? ' label was generated.'
                    : ' labels were generated.'
            );
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

function formatLabelDate(string $date): string
{
    $parsed = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $date
    );

    return $parsed
        ? $parsed->format('n/j/Y')
        : $date;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>853 Labels</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            background: #f8f9fa;
            color: #212529;
        }

        .page-wrap {
            max-width: 1100px;
        }

        h2 {
            margin-bottom: 8px;
        }

        .subtext {
            margin-bottom: 18px;
            color: #555;
            font-size: 14px;
        }

        .label-form-card {
            max-width: 520px;
            padding: 18px;
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: end;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        label {
            font-size: 13px;
            font-weight: bold;
        }

        input,
        button {
            padding: 8px 10px;
            font-size: 14px;
            box-sizing: border-box;
        }

        input[type="number"] {
            width: 110px;
        }

        button {
            cursor: pointer;
        }

        .generate-button {
            background: #0d6efd;
            color: #fff;
            border: 1px solid #0d6efd;
            border-radius: 4px;
        }

        .print-button {
            margin-bottom: 18px;
            background: #198754;
            color: #fff;
            border: 1px solid #198754;
            border-radius: 4px;
        }

        .message {
            max-width: 700px;
            margin-bottom: 18px;
            padding: 10px 12px;
            border-radius: 4px;
        }

        .message-success {
            color: #0f5132;
            background: #d1e7dd;
            border: 1px solid #badbcc;
        }

        .message-error {
            color: #842029;
            background: #f8d7da;
            border: 1px solid #f5c2c7;
        }

        .labels-heading {
            margin: 22px 0 12px;
        }

        .labels-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: flex-start;
        }

        /*
         * Initial approximation based on the sample.
         * These physical dimensions can be adjusted once tested
         * against the actual printer and label stock.
         */
        .battery-label {
            width: 4in;
            height: 2.4in;
            padding: 0.15in 0.2in;
            background: #fff;
            border: 2px solid #000;
            box-sizing: border-box;
            color: #000;
            overflow: hidden;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .label-part-number {
            margin-top: 0.03in;
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            line-height: 1.05;
        }

        .label-module-name {
            margin-top: 0.05in;
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            line-height: 1.05;
        }

        .label-serial {
            margin-top: 0.13in;
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            line-height: 1.05;
        }

        .label-dates {
            display: grid;
            grid-template-columns: 1fr auto;
            column-gap: 0.18in;
            row-gap: 0.05in;
            margin-top: 0.19in;
            font-size: 11.5pt;
            font-weight: bold;
            line-height: 1.15;
        }

        .label-date-name {
            text-align: right;
            white-space: nowrap;
        }

        .label-date-value {
            text-align: left;
            white-space: nowrap;
        }

        @page {
            margin: 0.25in;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .no-print,
            nav,
            header,
            .menu,
            #menu {
                display: none !important;
            }

            .page-wrap {
                max-width: none;
            }

            .labels-heading {
                display: none;
            }

            .labels-grid {
                display: block;
            }

            .battery-label {
                margin: 0 auto;
                border: none;
                page-break-after: always;
            }

            .battery-label:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>

<div class="page-wrap">

    <div class="no-print">
        <h2>853 Labels</h2>

        <div class="subtext">
            Generate and print labels for 853-08-01 battery
            modules.
        </div>

        <div class="label-form-card">
            <form method="post" action="853_labels.php">
                <div class="form-row">
                    <div class="form-field">
                        <label for="build_date">
                            Build Date
                        </label>

                        <input
                            type="date"
                            id="build_date"
                            name="build_date"
                            value="<?= htmlspecialchars($buildDate) ?>"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="quantity">
                            Quantity
                        </label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            min="1"
                            max="100"
                            step="1"
                            value="<?= (int)$quantity ?>"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <button
                            type="submit"
                            class="generate-button"
                        >
                            Generate Labels
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="message message-error">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage !== ''): ?>
            <div class="message message-success">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($labels)): ?>
            <button
                type="button"
                class="print-button"
                onclick="window.print()"
            >
                Print Labels
            </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($labels)): ?>
        <h3 class="labels-heading">
            Generated Labels
        </h3>

        <div class="labels-grid">
            <?php foreach ($labels as $label): ?>
                <div class="battery-label">
                    <div class="label-part-number">
                        PART NO. 853-08-01
                    </div>

                    <div class="label-module-name">
                        BATTERY MODULE
                    </div>

                    <div class="label-serial">
                        SERIAL NO.
                        <?= htmlspecialchars($label['serial']) ?>
                    </div>

                    <div class="label-dates">
                        <div class="label-date-name">
                            EXPECTED LIFE UNTIL
                        </div>

                        <div class="label-date-value">
                            <?= htmlspecialchars(
                                formatLabelDate(
                                    $label['life_end_date']
                                )
                            ) ?>
                        </div>

                        <div class="label-date-name">
                            BATTERIES CHARGED
                        </div>

                        <div class="label-date-value">
                            <?= htmlspecialchars(
                                formatLabelDate(
                                    $label['build_date']
                                )
                            ) ?>
                        </div>

                        <div class="label-date-name">
                            NEXT CHARGE DATE
                        </div>

                        <div class="label-date-value">
                            <?= htmlspecialchars(
                                formatLabelDate(
                                    $label['next_charge_date']
                                )
                            ) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
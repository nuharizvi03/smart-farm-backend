<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <title>Crop Profit Report</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        h2 {
            margin-top: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 7px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .summary {
            margin-top: 15px;
        }

        .profit {
            font-weight: bold;
        }

        .chart {
            margin-top: 20px;
            text-align: center;
        }

        .chart img {
            max-width: 650px;
            max-height: 350px;
        }
    </style>
</head>

<body>

    <h1>Crop Profit Report</h1>

    <h2>Crop Information</h2>

    <table>
        <tr>
            <th>Crop ID</th>
            <td>{{ $report['crop']['id'] }}</td>
        </tr>

        <tr>
            <th>Crop Name</th>
            <td>{{ $report['crop']['crop_name'] }}</td>
        </tr>

        <tr>
            <th>Variety</th>
            <td>{{ $report['crop']['variety'] }}</td>
        </tr>

        <tr>
            <th>Season</th>
            <td>{{ $report['crop']['season'] }}</td>
        </tr>

        <tr>
            <th>Planting Date</th>
            <td>{{ $report['crop']['planting_date'] }}</td>
        </tr>

        <tr>
            <th>Expected Harvest Date</th>
            <td>{{ $report['crop']['expected_harvest_date'] }}</td>
        </tr>

        <tr>
            <th>Status</th>
            <td>{{ $report['crop']['status'] }}</td>
        </tr>
    </table>


    <h2>Financial Summary</h2>

    <table>
        <tr>
            <th>Total Revenue</th>
            <td>{{ number_format($report['financial_summary']['total_revenue'], 2) }}</td>
        </tr>

        <tr>
            <th>Total Expenses</th>
            <td>{{ number_format($report['financial_summary']['total_expenses'], 2) }}</td>
        </tr>

        <tr>
            <th>Post Harvest Loss</th>
            <td>{{ number_format($report['financial_summary']['post_harvest_loss_amount'], 2) }}</td>
        </tr>

        <tr>
            <th>Total Cost</th>
            <td>{{ number_format($report['financial_summary']['total_cost'], 2) }}</td>
        </tr>

        <tr>
            <th>Profit</th>
            <td class="profit">
                {{ number_format($report['financial_summary']['profit'], 2) }}
            </td>
        </tr>

        <tr>
            <th>Profit Status</th>
            <td>{{ $report['financial_summary']['profit_status'] }}</td>
        </tr>
    </table>


    @if(!empty($chartImage))

        <h2>Financial Chart</h2>

        <div class="chart">
            <img src="{{ $chartImage }}" alt="Financial Chart">
        </div>

    @endif

</body>
</html>
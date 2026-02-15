<?php
$errors = [];
$result = null;

function clean_input(string $value): float
{
    return max(0, (float) $value);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee = trim($_POST['employee_name'] ?? '');
    $monthlyCtc = clean_input($_POST['monthly_ctc'] ?? '0');
    $basicPct = clean_input($_POST['basic_pct'] ?? '40');
    $hraPct = clean_input($_POST['hra_pct'] ?? '40');
    $specialAllowance = clean_input($_POST['special_allowance'] ?? '0');
    $taxRate = clean_input($_POST['tax_rate'] ?? '10');
    $statePt = clean_input($_POST['state_pt'] ?? '200');

    if ($employee === '') {
        $errors[] = 'Employee name is required.';
    }
    if ($monthlyCtc <= 0) {
        $errors[] = 'Monthly CTC must be greater than zero.';
    }
    if ($basicPct > 100 || $hraPct > 100) {
        $errors[] = 'Basic % and HRA % must be between 0 and 100.';
    }

    if (!$errors) {
        $basic = ($monthlyCtc * $basicPct) / 100;
        $hra = ($monthlyCtc * $hraPct) / 100;
        $gross = $basic + $hra + $specialAllowance;

        $pfEmployee = min(($basic * 12) / 100, 1800);
        $pfEmployer = $pfEmployee;

        $esiEmployee = $gross <= 21000 ? ($gross * 0.75) / 100 : 0;
        $esiEmployer = $gross <= 21000 ? ($gross * 3.25) / 100 : 0;

        $tds = ($gross * $taxRate) / 100;
        $totalDeductions = $pfEmployee + $esiEmployee + $statePt + $tds;
        $netPay = $gross - $totalDeductions;

        $result = [
            'employee' => $employee,
            'basic' => $basic,
            'hra' => $hra,
            'special' => $specialAllowance,
            'gross' => $gross,
            'pf_employee' => $pfEmployee,
            'pf_employer' => $pfEmployer,
            'esi_employee' => $esiEmployee,
            'esi_employer' => $esiEmployer,
            'state_pt' => $statePt,
            'tds' => $tds,
            'deductions' => $totalDeductions,
            'net' => $netPay,
        ];
    }
}

function money(float $value): string
{
    return '₹' . number_format($value, 2);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PayrollPro India</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <main class="container">
    <header>
      <h1>PayrollPro India</h1>
      <p>Simple PHP payroll calculator tailored for Indian salary components.</p>
    </header>

    <section class="card">
      <h2>Employee Payroll Input</h2>
      <?php if ($errors): ?>
        <div class="alert">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post">
        <label>Employee name
          <input type="text" name="employee_name" value="<?= htmlspecialchars($_POST['employee_name'] ?? '') ?>" required>
        </label>

        <label>Monthly CTC (₹)
          <input type="number" step="0.01" min="0" name="monthly_ctc" value="<?= htmlspecialchars($_POST['monthly_ctc'] ?? '60000') ?>" required>
        </label>

        <div class="grid">
          <label>Basic (%)
            <input type="number" step="0.01" min="0" max="100" name="basic_pct" value="<?= htmlspecialchars($_POST['basic_pct'] ?? '40') ?>">
          </label>

          <label>HRA (%)
            <input type="number" step="0.01" min="0" max="100" name="hra_pct" value="<?= htmlspecialchars($_POST['hra_pct'] ?? '20') ?>">
          </label>
        </div>

        <label>Special allowance (₹)
          <input type="number" step="0.01" min="0" name="special_allowance" value="<?= htmlspecialchars($_POST['special_allowance'] ?? '5000') ?>">
        </label>

        <div class="grid">
          <label>TDS estimate (%)
            <input type="number" step="0.01" min="0" max="100" name="tax_rate" value="<?= htmlspecialchars($_POST['tax_rate'] ?? '10') ?>">
          </label>

          <label>Professional tax (₹)
            <input type="number" step="0.01" min="0" name="state_pt" value="<?= htmlspecialchars($_POST['state_pt'] ?? '200') ?>">
          </label>
        </div>

        <button type="submit">Calculate payroll</button>
      </form>
    </section>

    <?php if ($result): ?>
      <section class="card">
        <h2>Payslip Summary: <?= htmlspecialchars($result['employee']) ?></h2>
        <table>
          <tr><th>Basic Pay</th><td><?= money($result['basic']) ?></td></tr>
          <tr><th>HRA</th><td><?= money($result['hra']) ?></td></tr>
          <tr><th>Special Allowance</th><td><?= money($result['special']) ?></td></tr>
          <tr><th>Gross Salary</th><td><?= money($result['gross']) ?></td></tr>
          <tr><th>PF (Employee)</th><td><?= money($result['pf_employee']) ?></td></tr>
          <tr><th>PF (Employer)</th><td><?= money($result['pf_employer']) ?></td></tr>
          <tr><th>ESI (Employee)</th><td><?= money($result['esi_employee']) ?></td></tr>
          <tr><th>ESI (Employer)</th><td><?= money($result['esi_employer']) ?></td></tr>
          <tr><th>Professional Tax</th><td><?= money($result['state_pt']) ?></td></tr>
          <tr><th>TDS</th><td><?= money($result['tds']) ?></td></tr>
          <tr><th>Total Deductions</th><td><?= money($result['deductions']) ?></td></tr>
          <tr class="net"><th>Net Salary</th><td><?= money($result['net']) ?></td></tr>
        </table>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>

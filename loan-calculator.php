<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Beta Calculator (Islamic & Conventional Financing)</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      background: #f4f9fc;
    }
    .container {
      max-width: 1100px;
      margin: 30px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    h1 {
      text-align: center;
      color: #005BAC;
    }
    label {
      font-weight: 600;
    }
    input[type=number], input[type=text], select {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      background: #005BAC;
      color: #fff;
      padding: 15px 20px;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      width: 100%;
      margin-top: 10px;
    }
    .reset-button {
      background: #6c757d;
    }
    .result, .schedule, #downloadPdf {
      margin-top: 30px;
    }
    .result p {
      font-weight: bold;
      margin: 10px 0;
    }
    .schedule table {
      border-collapse: collapse;
      width: 100%;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: right;
    }
    th {
      background-color: #005BAC;
      color: white;
    }
    #downloadPdf {
      background: #D9534F;
      margin-top: 20px;
    }
    .label-container {
      display: flex;
      align-items: center;
    }
    .label-container span {
      margin-left: 5px;
      font-weight: normal;
      color: #ff0000;
    }
    .error {
      color: #ff0000;
      font-size: 14px;
      margin-bottom: 15px;
      display: none;
    }
    #cprContainer, #profitRateLabel {
      display: block;
    }
  </style>
</head>
<body>

<div class="container">
  <h1>Beta Calculator (Islamic & Conventional Financing)</h1>

  <div id="error" class="error"></div>

  <!-- Dropdown to select financing type -->
  <label>Financing Type</label>
  <select id="financingType" onchange="toggleFinancingType()">
    <option value="islamic">Islamic</option>
    <option value="conventional">Conventional</option>
  </select>

  <label>Type of Rate</label>
  <select id="profitMethod" onchange="toggleCPR()">
    <option value="floating">Floating Rate / Variable Rate</option>
    <option value="fixed">Fixed Rate</option>
    <option value="flat">Flat Rate</option>
  </select>

  <label>Financing Amount (RM)</label>
  <input type="text" id="financing" value="100,000">

  <!-- Islamic: Profit Rate, Conventional: Interest Rate -->
  <label id="profitRateLabel">Profit Rate p.a (%)</label>
  <input type="number" id="epr" value="5" step="0.01" min="0" onchange="syncCPR()">

  <label>Tenure (Years)</label>
  <input type="number" id="tenure" value="10" min="1">

  <!-- Islamic: CPR input, hidden for Conventional -->
  <div id="cprContainer">
    <div class="label-container">
      <label>Ceiling Profit Rate p.a. (%)</label>
      <span id="cprNotation"></span>
    </div>
    <input type="number" id="cpr" value="15" step="0.01" min="0">
  </div>

  <button onclick="calculate()">Calculate</button>
  <button class="reset-button" onclick="resetForm()">Reset</button>

  <div class="result" id="result" style="display:none;">
    <!-- Islamic: Full result set -->
    <div id="islamicResults">
      <p>Instalment: RM <span id="monthlyEPR"></span></p>
      <p>Instalment (Contractual): RM <span id="monthlyCPR"></span></p>
      <p>Total Selling Price: RM <span id="sellingPrice"></span></p>
      <p>Total Profit (Profit Rate): RM <span id="totalProfitEPR"></span></p>
      <p>Total Profit (Ceiling Profit Rate): RM <span id="totalProfitCPR"></span></p>
    </div>
    <!-- Conventional: Simplified result set -->
    <div id="conventionalResults" style="display:none;">
      <p>Instalment: RM <span id="monthlyInstalmentConventional"></span></p>
      <p>Total Interest: RM <span id="totalInterestConventional"></span></p>
      <p>Total Payment: RM <span id="totalPaymentConventional"></span></p>
    </div>
  </div>

  <div class="schedule" id="schedule" style="display:none;">
    <h3>Full Payment Schedule</h3>
    <table id="scheduleTable">
      <thead id="scheduleHeader"></thead>
      <tbody id="scheduleBody"></tbody>
    </table>
  </div>

  <button id="downloadPdf" style="display:none;" onclick="generatePDF()">Download PDF</button>
</div>

<script>
// Utility Functions
function formatCurrency(num) {
  return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatInputNumber(value) {
  let cleaned = value.replace(/[^0-9.]/g, '');
  cleaned = cleaned.replace(/^0+(?=\d)/, '');
  const parts = cleaned.split('.');
  cleaned = parts[0] + (parts.length > 1 ? '.' + parts[1] : '');
  const num = parseFloat(cleaned) || 0;
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function parseInputNumber(value) {
  return parseFloat(value.replace(/,/g, '')) || 0;
}

function setupInputFormatting() {
  const financingInput = document.getElementById("financing");
  financingInput.addEventListener('input', function() {
    const cursorPosition = this.selectionStart;
    const oldValue = this.value;
    this.value = formatInputNumber(this.value);
    const newLength = this.value.length;
    const oldLength = oldValue.length;
    const diff = newLength - oldLength;
    this.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
  });
}

// Validation
function validateInputs(financing, epr, tenure, cpr, financingType, method) {
  const errorDiv = document.getElementById("error");
  errorDiv.style.display = "none";
  errorDiv.innerText = "";

  if (!financing || financing <= 0) {
    errorDiv.innerText = "Please enter a valid Financing Amount greater than 0.";
    errorDiv.style.display = "block";
    return false;
  }
  if (!epr || epr <= 0) {
    errorDiv.innerText = `Please enter a valid ${financingType === "islamic" ? "Profit" : "Interest"} Rate greater than 0.`;
    errorDiv.style.display = "block";
    return false;
  }
  if (!tenure || tenure <= 0) {
    errorDiv.innerText = "Please enter a valid Tenure greater than 0.";
    errorDiv.style.display = "block";
    return false;
  }
  // Islamic: Validate CPR for Fixed/Floating
  if (financingType === "islamic" && (method === "floating" || method === "fixed") && (!cpr || cpr <= 0)) {
    errorDiv.innerText = "Please enter a valid Ceiling Profit Rate greater than 0.";
    errorDiv.style.display = "block";
    return false;
  }
  return true;
}

// UI Toggling
function syncCPR() {
  // Islamic: Sync CPR with Profit Rate for Fixed Rate
  const financingType = document.getElementById("financingType").value;
  if (financingType !== "islamic") return;
  const method = document.getElementById("profitMethod").value;
  const epr = document.getElementById("epr").value;
  if (method === "fixed") {
    document.getElementById("cpr").value = epr;
  }
}

function toggleCPR() {
  // Islamic: Show/hide CPR based on method
  const financingType = document.getElementById("financingType").value;
  const method = document.getElementById("profitMethod").value;
  const cprContainer = document.getElementById("cprContainer");
  const cprNotation = document.getElementById("cprNotation");
  if (financingType === "islamic") {
    const isFlat = method === "flat";
    cprContainer.style.display = isFlat ? "none" : "block";
    cprNotation.innerText = isFlat ? "(Not Applicable)" : "";
    if (method === "fixed") {
      syncCPR();
    }
  } else {
    // Conventional: Always hide CPR
    cprContainer.style.display = "none";
    cprNotation.innerText = "(Not Applicable)";
  }
}

function toggleFinancingType() {
  const financingType = document.getElementById("financingType").value;
  const profitRateLabel = document.getElementById("profitRateLabel");
  const islamicResults = document.getElementById("islamicResults");
  const conventionalResults = document.getElementById("conventionalResults");

  // Update labels and visibility
  profitRateLabel.innerText = financingType === "islamic" ? "Profit Rate p.a (%)" : "Interest Rate p.a (%)";
  islamicResults.style.display = financingType === "islamic" ? "block" : "none";
  conventionalResults.style.display = financingType === "islamic" ? "none" : "block";

  // Update CPR visibility
  toggleCPR();

  // Clear results and schedule
  document.getElementById("result").style.display = "none";
  document.getElementById("schedule").style.display = "none";
  document.getElementById("downloadPdf").style.display = "none";
  document.getElementById("scheduleBody").innerHTML = "";
}

function resetForm() {
  document.getElementById("financingType").value = "islamic";
  document.getElementById("profitMethod").value = "floating";
  document.getElementById("financing").value = "100,000";
  document.getElementById("epr").value = "5";
  document.getElementById("tenure").value = "10";
  document.getElementById("cpr").value = "15";
  document.getElementById("error").style.display = "none";
  document.getElementById("result").style.display = "none";
  document.getElementById("schedule").style.display = "none";
  document.getElementById("downloadPdf").style.display = "none";
  document.getElementById("scheduleBody").innerHTML = "";
  toggleFinancingType();
}

document.addEventListener('DOMContentLoaded', function() {
  setupInputFormatting();
  toggleFinancingType();
});

function calculate() {
  const financingType = document.getElementById("financingType").value;
  const financing = parseInputNumber(document.getElementById("financing").value);
  const epr = parseFloat(document.getElementById("epr").value);
  const cpr = parseFloat(document.getElementById("cpr").value);
  const tenure = parseInt(document.getElementById("tenure").value);
  const method = document.getElementById("profitMethod").value;

  if (!validateInputs(financing, epr, tenure, cpr, financingType, method)) return;

  const months = tenure * 12;
  const eprMonthly = epr / 100 / 12;
  const cprMonthly = cpr / 100 / 12;

  let results = {};

  if (financingType === "islamic") {
    // Islamic: Existing calculation logic
    let monthlyEPR = 0;
    let monthlyCPR = 0;
    let sellingPrice = 0;

    if (method === "floating" || method === "fixed") {
      monthlyEPR = (financing * eprMonthly) / (1 - Math.pow(1 + eprMonthly, -months));
      monthlyCPR = (financing * cprMonthly) / (1 - Math.pow(1 + cprMonthly, -months));
      sellingPrice = monthlyCPR * months;
    } else { // Flat Rate
      const totalProfit = financing * (epr / 100) * tenure;
      sellingPrice = financing + totalProfit;
      monthlyEPR = monthlyCPR = sellingPrice / months;
    }

    const totalProfitEPR = (monthlyEPR * months) - financing;
    const totalProfitCPR = (monthlyCPR * months) - financing;

    results = {
      monthlyEPR,
      monthlyCPR,
      sellingPrice,
      totalProfitEPR,
      totalProfitCPR
    };

    // Display Islamic results
    document.getElementById("monthlyEPR").innerText = formatCurrency(monthlyEPR);
    document.getElementById("monthlyCPR").innerText = formatCurrency(monthlyCPR);
    document.getElementById("sellingPrice").innerText = formatCurrency(sellingPrice);
    document.getElementById("totalProfitEPR").innerText = formatCurrency(totalProfitEPR);
    document.getElementById("totalProfitCPR").innerText = formatCurrency(totalProfitCPR);
  } else {
    // Conventional: Simplified calculation
    let monthlyInstalment = 0;
    let totalInterest = 0;

    if (method === "floating" || method === "fixed") {
      monthlyInstalment = (financing * eprMonthly) / (1 - Math.pow(1 + eprMonthly, -months));
      totalInterest = (monthlyInstalment * months) - financing;
    } else { // Flat Rate
      totalInterest = financing * (epr / 100) * tenure;
      monthlyInstalment = (financing + totalInterest) / months;
    }

    results = {
      monthlyInstalment,
      totalInterest,
      totalPayment: financing + totalInterest
    };

    // Display Conventional results
    document.getElementById("monthlyInstalmentConventional").innerText = formatCurrency(monthlyInstalment);
    document.getElementById("totalInterestConventional").innerText = formatCurrency(totalInterest);
    document.getElementById("totalPaymentConventional").innerText = formatCurrency(financing + totalInterest);
  }

  generateSchedule(financing, eprMonthly, cprMonthly, results, months, method, financingType);

  document.getElementById("result").style.display = "block";
  document.getElementById("schedule").style.display = "block";
  document.getElementById("downloadPdf").style.display = "block";
}

function generateSchedule(principal, eprRate, cprRate, results, months, method, financingType) {
  let scheduleBody = document.getElementById("scheduleBody");
  let scheduleHeader = document.getElementById("scheduleHeader");
  scheduleBody.innerHTML = "";
  let balance = principal;
  let totalProfit = 0;

  // Set table headers based on financing type
  if (financingType === "islamic") {
    // Islamic: Full schedule headers
    scheduleHeader.innerHTML = `
      <tr>
        <th>Month</th>
        <th>Instalment Contractual (RM)</th>
        <th>Instalment (RM)</th>
        <th>Profit (RM)</th>
        <th>Principal (RM)</th>
        <th>Principal Balance (RM)</th>
        <th>Selling Price (RM)</th>
        <th>Deferred Profit (RM)</th>
      </tr>`;
  } else {
    // Conventional: Simplified schedule headers
    scheduleHeader.innerHTML = `
      <tr>
        <th>Month</th>
        <th>Instalment (RM)</th>
        <th>Interest (RM)</th>
        <th>Principal (RM)</th>
        <th>Principal Balance (RM)</th>
      </tr>`;
  }

  // Rule of 78 for Flat Rate
  let sumOfDigits = months * (months + 1) / 2;

  if (method === "flat") {
    totalProfit = principal * (eprRate * 12) * (months / 12);
  }

  // Month 0: Initial values
  let row = "";
  if (financingType === "islamic") {
    // Islamic: Include Selling Price and Deferred Profit
    let deferredProfit = results.sellingPrice - principal;
    row = `<tr>
        <td>0</td>
        <td>${formatCurrency(0)}</td>
        <td>${formatCurrency(0)}</td>
        <td>${formatCurrency(0)}</td>
        <td>${formatCurrency(0)}</td>
        <td>${formatCurrency(principal)}</td>
        <td>${formatCurrency(results.sellingPrice)}</td>
        <td>${formatCurrency(deferredProfit)}</td>
      </tr>`;
  } else {
    // Conventional: Basic initial row
    row = `<tr>
        <td>0</td>
        <td>${formatCurrency(0)}</td>
        <td>${formatCurrency(0)}</td>
        <td>${formatCurrency(0)}</td>
        <td>${formatCurrency(principal)}</td>
      </tr>`;
  }
  scheduleBody.innerHTML += row;

  for (let i = 1; i <= months; i++) {
    let profit = 0, principalPortion = 0, sellingPriceRemaining = 0, deferredProfit = 0;

    if (financingType === "islamic") {
      // Islamic: Use EPR for Profit and Principal calculations
      if (method === "floating" || method === "fixed") {
        profit = balance * eprRate; // Use EPR instead of CPR
        principalPortion = results.monthlyEPR - profit; // Use monthlyEPR for instalment
        balance -= principalPortion;
        sellingPriceRemaining = results.monthlyCPR * (months - i); // Contractual instalment for selling price
        deferredProfit = sellingPriceRemaining - balance;
      } else { // Flat Rate (Rule of 78)
        profit = totalProfit * (months - i + 1) / sumOfDigits;
        principalPortion = results.monthlyEPR - profit;
        balance -= principalPortion;
        sellingPriceRemaining = results.sellingPrice - (results.monthlyEPR * i);
        deferredProfit = sellingPriceRemaining - balance;
      }

      // Adjust final Islamic installment
      if (i === months) {
        if (balance > 0) {
          principalPortion += balance;
          profit = results.monthlyEPR - principalPortion; // Adjust profit based on EPR instalment
        }
        balance = Math.max(0, balance); // Set balance to 0 if negative
        deferredProfit = sellingPriceRemaining - balance;
      }

      row = `<tr>
          <td>${i}</td>
          <td>${formatCurrency(results.monthlyCPR)}</td>
          <td>${formatCurrency(results.monthlyEPR)}</td>
          <td>${formatCurrency(profit)}</td>
          <td>${formatCurrency(principalPortion)}</td>
          <td>${formatCurrency(balance)}</td>
          <td>${formatCurrency(sellingPriceRemaining)}</td>
          <td>${formatCurrency(deferredProfit)}</td>
        </tr>`;
    } else {
      // Conventional: Simplified schedule
      if (method === "floating" || method === "fixed") {
        profit = balance * eprRate;
        principalPortion = results.monthlyInstalment - profit;
        balance -= principalPortion;
      } else { // Flat Rate (Rule of 78)
        profit = totalProfit * (months - i + 1) / sumOfDigits;
        principalPortion = results.monthlyInstalment - profit;
        balance -= principalPortion;
      }

      // Adjust final Conventional installment
      if (i === months) {
        if (balance > 0) {
          principalPortion += balance;
          profit = results.monthlyInstalment - principalPortion;
        }
        balance = Math.max(0, balance); // Set balance to 0 if negative
      }

      row = `<tr>
          <td>${i}</td>
          <td>${formatCurrency(results.monthlyInstalment)}</td>
          <td>${formatCurrency(profit)}</td>
          <td>${formatCurrency(principalPortion)}</td>
          <td>${formatCurrency(balance)}</td>
        </tr>`;
    }

    // Ensure balance doesn't go negative
    balance = Math.max(0, balance);
    scheduleBody.innerHTML += row;
  }
}

async function generatePDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('landscape', 'pt', 'a4');
  doc.setFontSize(14);
  doc.text("Beta Calculator - Financing Schedule", 40, 40);

  const financingType = document.getElementById("financingType").value;
  const method = document.getElementById("profitMethod").value;
  const financing = document.getElementById("financing").value;
  const epr = document.getElementById("epr").value;
  const tenure = document.getElementById("tenure").value;
  const cpr = document.getElementById("cpr").value;

  doc.setFontSize(10);
  doc.text(`Financing Type: ${financingType === "islamic" ? "Islamic" : "Conventional"}`, 40, 60);
  doc.text(`Type of Rate: ${method === "floating" ? "Floating Rate / Variable Rate" : method === "fixed" ? "Fixed Rate" : "Flat Rate"}`, 40, 75);
  doc.text(`Financing Amount: RM ${financing}`, 40, 90);
  doc.text(`${financingType === "islamic" ? "Profit" : "Interest"} Rate: ${epr}% p.a.`, 40, 105);
  doc.text(`Tenure: ${tenure} Years`, 40, 120);
  if (financingType === "islamic") {
    doc.text(`Ceiling Profit Rate: ${method === "flat" ? "Not Applicable" : cpr + "% p.a."}`, 40, 135);
  }

  let headers = [];
  if (financingType === "islamic") {
    // Islamic: Full headers
    headers = [["Month", "Instalment Contractual (RM)", "Instalment (RM)", "Profit (RM)", "Principal (RM)", "Principal Balance (RM)", "Selling Price (RM)", "Deferred Profit (RM)"]];
  } else {
    // Conventional: Simplified headers
    headers = [["Month", "Instalment (RM)", "Interest (RM)", "Principal (RM)", "Principal Balance (RM)"]];
  }

  const body = [];
  document.querySelectorAll("#scheduleBody tr").forEach(row => {
    const rowData = [];
    row.querySelectorAll("td").forEach(cell => {
      rowData.push(cell.innerText);
    });
    body.push(rowData);
  });

  doc.autoTable({
    head: headers,
    body: body,
    startY: financingType === "islamic" ? 150 : 135,
    styles: { fontSize: 9, halign: 'right' },
    headStyles: { fillColor: [0, 86, 150], halign: 'center' },
    margin: { top: 150, bottom: 40, left: 30, right: 30 }
  });

  doc.save(`Beta-Calculator-${financingType}-Schedule.pdf`);
}
</script>
</body>
</html>
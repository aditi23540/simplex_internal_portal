<!-- index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAP API Portal</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container mt-4">
  <h1 class="mb-4">SAP API Integration Tool</h1>

  <!-- Paste / Drag Drop Input -->
  <div class="mb-3">
    <label for="dataInput" class="form-label">Paste or Drag-Drop Data</label>
    <textarea id="dataInput" class="form-control" rows="5" placeholder="Paste tab- or comma-delimited data from Excel"></textarea>
    <button id="processData" class="btn btn-primary mt-2">Process Data</button>
  </div>

  <!-- Tables -->
  <h2>API Master</h2>
  <table id="apiMasterTable" class="display nowrap" style="width:100%"></table>

  <h2>Module Map</h2>
  <table id="moduleMapTable" class="display nowrap" style="width:100%"></table>

  <!-- SAP OData Explorer -->
  <h2 class="mt-4">SAP OData Explorer</h2>
  <div class="row g-2">
    <div class="col-md-4">
      <select id="apiSelector" class="form-select"></select>
    </div>
    <div class="col-md-4">
      <select id="entitySelector" class="form-select"></select>
    </div>
    <div class="col-md-4">
      <button id="loadEntity" class="btn btn-success">Load Data</button>
    </div>
  </div>
  <div id="fieldChecklist" class="form-check mt-3"></div>
  <table id="odataTable" class="display nowrap mt-3" style="width:100%"></table>
</div>

<!-- Bootstrap Spinner -->
<div id="spinnerOverlay">
  <div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">Loading...</span>
  </div>
</div>

<!-- CRUD Modals (built dynamically in JS) -->
<div id="crudModals"></div>

<!-- Dependencies -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>